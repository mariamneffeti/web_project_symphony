<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use App\Repository\MeetingRepository;
use App\Repository\EmployeeRepository;
use App\Repository\ClientRepository;
use App\Entity\Meeting;
use App\Entity\Company;
use App\Form\MeetingType;
use App\Form\MeetingUpdateType;
use App\Form\ClientEmailType;

final class ManagementController extends AbstractController
{
    private HttpClientInterface $httpClient;

    public function __construct(HttpClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    #[Route('/management', name: 'management')]
    public function index(): Response
    {
        $meeting = new Meeting();
        $addForm = $this->createForm(MeetingType::class, $meeting);
        $updateForm = $this->createForm(MeetingUpdateType::class, $meeting);
        $emailForm = $this->createForm(ClientEmailType::class);

        return $this->render('management/index.html.twig', [
            'controller_name' => 'ManagementController',
            'form'            => $addForm->createView(), 
            'updateForm'      => $updateForm->createView(),
            'emailForm'       => $emailForm->createView(),
            'zapier_webhook_url' => $this->getParameter('app.zapier_webhook'),
        ]);
    }

    // Fetching meetings sorted by status (scheduled first) and date (closest to farthest)
    #[Route('/api/meetings', name: 'api_meetings', methods: ['GET'])]
    public function getMeetings(MeetingRepository $repo): JsonResponse
    {
        $meetings = $repo->createQueryBuilder('m')
            ->addSelect('CASE WHEN m.status = :sched THEN 0 ELSE 1 END AS HIDDEN status_priority')
            ->setParameter('sched', 'scheduled')
            ->orderBy('status_priority', 'ASC')       
            ->addOrderBy('m.meetingDate', 'ASC')     
            ->addOrderBy('m.meetingTime', 'ASC')     
            ->getQuery()
            ->getResult();

        $data = array_map(function ($m) {
            return [
                'id'           => $m->getId(),
                'title'        => $m->getTitle(),
                'meeting_date' => $m->getMeetingDate()->format('Y-m-d'),
                'meeting_time' => $m->getMeetingTime()->format('H:i:s'),
                'status'       => $m->getStatus(),
                'meet_link'    => $m->getMeetLink(),
                'notes'        => $m->getNotes(),
            ];
        }, $meetings);

        return new JsonResponse($data);
    }

    // Add Meeting
    #[Route('/api/meetings/add', name: 'api_meetings_add', methods: ['POST'])]
    public function addMeeting(Request $request, EntityManagerInterface $em, EmployeeRepository $employeeRepo): JsonResponse
    {
        $meeting = new Meeting();
        $form = $this->createForm(MeetingType::class, $meeting);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $company = $em->getRepository(Company::class)->find(1);
                if (!$company) {
                    return new JsonResponse(['success' => false, 'error' => 'Company with ID 1 does not exist.'], 400);
                }
                $meeting->setCompany($company);
            } catch (\Exception $e) {
                return new JsonResponse(['success' => false, 'error' => 'Company lookup error.'], 500);
            }

            $employeeIds = $request->request->all('employee_ids');
            if (is_array($employeeIds)) {
                foreach ($employeeIds as $empId) {
                    $employee = $employeeRepo->find($empId);
                    if ($employee) {
                        $meeting->addEmployee($employee);
                    }
                }
            }

            $em->persist($meeting);
            $em->flush();
            return new JsonResponse(['success' => true]);
        }

        $errors = [];
        foreach ($form->getErrors(true) as $error) {
            $errors[] = $error->getMessage();
        }
        return new JsonResponse(['success' => false, 'error' => implode(', ', $errors)], 400);
    }

    // Update Meeting with Automated Employee Email Notification Triggers
    #[Route('/api/meetings/update', name: 'api_meetings_update', methods: ['POST'])]
    public function updateMeeting(Request $request, MeetingRepository $meetingRepo, EntityManagerInterface $em): JsonResponse
    {
        $meetingId = $request->request->get('meeting_id');
        if (!$meetingId) {
            return new JsonResponse(['success' => false, 'error' => 'Missing meeting identifier.'], 400);
        }

        $meeting = $meetingRepo->find($meetingId);
        if (!$meeting) {
            return new JsonResponse(['success' => false, 'error' => 'Meeting not found.'], 404);
        }

        $oldStatus = $meeting->getStatus();

        $form = $this->createForm(MeetingUpdateType::class, $meeting);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $em->flush();

                if ($meeting->getStatus() === 'cancelled' && $oldStatus !== 'cancelled') {
                    $this->broadcastMeetingCancellation($meeting);
                }

                return new JsonResponse(['success' => true]);
            } catch (\Exception $e) {
                return new JsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
            }
        }

        $errors = [];
        foreach ($form->getErrors(true) as $error) {
            $errors[] = $error->getMessage();
        }
        return new JsonResponse(['success' => false, 'error' => implode(', ', $errors)], 400);
    }

    // Delete Meeting 
    #[Route('/api/meetings/delete', name: 'api_meetings_delete', methods: ['POST'])]
    public function deleteMeeting(Request $request, MeetingRepository $meetingRepo, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $meeting = $meetingRepo->find($data['id'] ?? null);
        if (!$meeting) {
            return new JsonResponse(['success' => false, 'error' => 'Meeting not found.'], 404);
        }

        if ($meeting->getStatus() !== 'cancelled') {
            $this->broadcastMeetingCancellation($meeting);
        }

        $em->remove($meeting);
        $em->flush();
        return new JsonResponse(['success' => true]);
    }

    // Fetch employees associated with a specific meeting
    #[Route('/api/meetings/{id}/employees', name: 'api_meetings_employees', methods: ['GET'])]
    public function getMeetingEmployees(int $id, MeetingRepository $meetingRepo): JsonResponse
    {
        $meeting = $meetingRepo->find($id);
        if (!$meeting) {
            return new JsonResponse([], 404);
        }

        $employees = method_exists($meeting, 'getEmployees') ? $meeting->getEmployees() : [];
        $data = array_map(function($e) {
            return [
                'id'         => $e->getId(),
                'first_name' => $e->getFirstName(),
                'last_name'  => $e->getLastName(),
                'position'   => $e->getPosition(),
                'department' => $e->getDepartment(),
                'email'      => $e->getEmail(),
            ];
        }, (is_array($employees) ? $employees : $employees->toArray()));

        return new JsonResponse($data);
    }

    // Fetching employees
    #[Route('/api/employees', name: 'api_employees', methods: ['GET'])]
    public function getEmployees(EmployeeRepository $repo): JsonResponse
    {
        return new JsonResponse(array_map(function ($e) {
            return [
                'id'         => $e->getId(),
                'first_name' => $e->getFirstName(),
                'last_name'  => $e->getLastName(),
                'position'   => $e->getPosition(),
                'department' => $e->getDepartment(),
                'email'      => $e->getEmail(),
            ];
        }, $repo->findAll()));
    }

    // Fetching clients
    #[Route('/api/clients', name: 'api_clients', methods: ['GET'])]
    public function getClients(ClientRepository $repo): JsonResponse
    {
        return new JsonResponse(array_map(function ($c) {
            return [
                'id'          => $c->getId(),
                'client_name' => $c->getClientName(),
                'email'       => $c->getEmail(),
                'status'      => $c->getStatus(),
            ];
        }, $repo->findAll()));
    }

    // Client form 
    #[Route('/api/clients/validate-email', name: 'api_clients_validate_email', methods: ['POST'])]
    public function validateEmailPayload(Request $request): JsonResponse
    {
        $form = $this->createForm(ClientEmailType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            return new JsonResponse(['success' => true, 'data' => $form->getData()]);
        }

        $errors = [];
        foreach ($form->getErrors(true) as $error) {
            $errors[] = $error->getMessage();
        }
        return new JsonResponse(['success' => false, 'error' => implode(', ', $errors)], 400);
    }

    // Helper method for webhook distributions 
    private function broadcastMeetingCancellation(Meeting $meeting): void
    {
        $webhookUrl = $this->getParameter('app.zapier_webhook');
        if (!$webhookUrl) {
            return;
        }

        $employees = method_exists($meeting, 'getEmployees') ? $meeting->getEmployees() : [];
        
        foreach ($employees as $emp) {
            $email = method_exists($emp, 'getEmail') ? $emp->getEmail() : null;
            if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                continue;
            }

            $subject = "Meeting Cancelled: " . $meeting->getTitle();
            $message = "Hello " . $emp->getFirstName() . ",\n\n"
                     . "The meeting \"" . $meeting->getTitle() . "\" scheduled for "
                     . $meeting->getMeetingDate()->format('Y-m-d') . " has been cancelled.\n\n"
                     . "Regards,\nManagement";

            try {
                $this->httpClient->request('POST', $webhookUrl, [
                    'json' => [
                        'to'      => $email,
                        'subject' => $subject,
                        'body'    => $message,
                        'type'    => 'cancelled',
                    ],
                ]);
            } catch (\Exception $e) {
                continue;
            }
        }
    }
}