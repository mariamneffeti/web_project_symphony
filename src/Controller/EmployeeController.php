<?php

namespace App\Controller;

use App\Entity\Employee;
use App\Entity\User;
use App\Form\EmployeeType;
use App\Repository\EmployeeRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/employee', name: 'employee_')]
#[IsGranted('ROLE_EMPLOYEE')]
class EmployeeController extends AbstractController
{
    #[Route('/dashboard', name: 'dashboard')]
    public function dashboard(EmployeeRepository $repo): Response
    {
        /** @var User $currentUser */
        $currentUser = $this->getUser();

        $employee = $repo->findOneBy(['email' => $currentUser->getEmail()]);

        if (!$employee) {
            return $this->redirectToRoute('app_login');
        }

        return $this->redirectToRoute('employee_view', ['id' => $employee->getId()]);
    }

    #[Route('/list', name: 'index', methods: ['GET', 'POST'])]
    public function index(
        Request                     $request,
        EmployeeRepository          $repo,
        EntityManagerInterface      $em,
        UserRepository              $userRepo,
        UserPasswordHasherInterface $hasher
    ): Response {
        /** @var User $currentUser */
        $currentUser = $this->getUser();
        $company     = $currentUser->getCompany();

        $employee = new Employee();
        $form     = $this->createForm(EmployeeType::class, $employee);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            // Check duplicate email
            if ($userRepo->findOneBy(['email' => $employee->getEmail()])) {
                if ($request->isXmlHttpRequest()) {
                    return new JsonResponse([
                        'status'  => 'error',
                        'message' => 'A user with this email already exists.',
                    ]);
                }
                $this->addFlash('error', 'A user with this email already exists.');
                return $this->redirectToRoute('employee_index');
            }

            // Handle CV upload
            $cvFile = $request->files->get('cv_file');
            if ($cvFile) {
                $allowedMimes = [
                    'application/pdf',
                    'application/msword',
                    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                ];

                if (!in_array($cvFile->getMimeType(), $allowedMimes)) {
                    return new JsonResponse(['status' => 'error', 'message' => 'Invalid file format.']);
                }

                if ($cvFile->getSize() > 5 * 1024 * 1024) {
                    return new JsonResponse(['status' => 'error', 'message' => 'File too large (max 5MB).']);
                }

                $cvDir = $this->getParameter('kernel.project_dir') . '/public/uploads/cv';
                if (!is_dir($cvDir)) {
                    mkdir($cvDir, 0755, true);
                }

                $newFilename = uniqid('cv_') . '.' . $cvFile->guessExtension();
                $cvFile->move($cvDir, $newFilename);
                $employee->setCvPath('uploads/cv/' . $newFilename);
            }

            // Create linked User account
            $tempPassword = 'Emp@' . rand(1000, 9999);
            $user         = new User();
            $user->setFirstName($employee->getFirstName());
            $user->setLastName($employee->getLastName() ?? 'NN');
            $user->setEmail($employee->getEmail());
            $user->setRoles(['ROLE_EMPLOYEE']);
            $user->setPassword($hasher->hashPassword($user, $tempPassword));

            // Link employee to company
            $employee->setHireDate(new \DateTime());
            $employee->setCompanyId($currentUser->getCompany()->getId());
            $employee->setUserId($currentUser->getId());

            // Persist both together
            $em->persist($user);
            $em->persist($employee);
            $em->flush();

            if ($request->isXmlHttpRequest()) {
                return new JsonResponse([
                    'status'  => 'success',
                    'message' => 'Employee added successfully.',
                    'id'      => $employee->getId(),
                ]);
            }

            $this->addFlash('success', 'Employee added successfully.');
            return $this->redirectToRoute('employee_index');
        }

        if ($form->isSubmitted() && $request->isXmlHttpRequest()) {
            $errors = [];
            foreach ($form->getErrors(true) as $e) {
                $errors[] = $e->getMessage();
            }
            return new JsonResponse(['status' => 'error', 'message' => implode(', ', $errors)], 422);
        }

        $search    = $request->query->get('search', '');
        $companyId = $currentUser->getCompany()->getId();
        $employees = $search ? $repo->findBySearch($search) : $repo->findBy(['companyId' => $companyId]);

        return $this->render('employee/index.html.twig', [
            'form'      => $form->createView(),
            'employees' => $employees,
            'search'    => $search,
        ]);
    }


    #[Route('/{id}', name: 'view', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function view(Employee $employee): Response
    {
        return $this->render('employee/view.html.twig', [
            'employee' => $employee,
        ]);
    }


    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function edit(
        Request                     $request,
        Employee                    $employee,
        EntityManagerInterface      $em,
        UserRepository              $userRepo,
        UserPasswordHasherInterface $hasher
    ): Response {
        $form = $this->createForm(EmployeeType::class, $employee);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {


            $cvFile = $request->files->get('cv_file');
            if ($cvFile) {
                $allowedMimes = [
                    'application/pdf',
                    'application/msword',
                    'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
                ];

                if (!in_array($cvFile->getMimeType(), $allowedMimes)) {
                    if ($request->isXmlHttpRequest()) {
                        return new JsonResponse(['status' => 'error', 'message' => 'Invalid file format.']);
                    }
                    $this->addFlash('error', 'Invalid file format.');
                    return $this->redirectToRoute('employee_edit', ['id' => $employee->getId()]);
                }

                if ($cvFile->getSize() > 5 * 1024 * 1024) {
                    if ($request->isXmlHttpRequest()) {
                        return new JsonResponse(['status' => 'error', 'message' => 'File too large (max 5MB).']);
                    }
                    $this->addFlash('error', 'File too large (max 5MB).');
                    return $this->redirectToRoute('employee_edit', ['id' => $employee->getId()]);
                }

                $cvDir = $this->getParameter('kernel.project_dir') . '/public/uploads/cv';
                if (!is_dir($cvDir)) {
                    mkdir($cvDir, 0755, true);
                }

                $newFilename = 'cv_' . uniqid() . '.' . $cvFile->guessExtension();
                $cvFile->move($cvDir, $newFilename);
                $employee->setCvPath('uploads/cv/' . $newFilename);
            }


            $user = $userRepo->findOneBy(['email' => $employee->getEmail()])
                ?? $userRepo->find($employee->getUserId());

            if ($user) {
                $user->setFirstName($employee->getFirstName());
                $user->setEmail($employee->getEmail());
                $em->persist($user);
            }

            $em->flush();

            if ($request->isXmlHttpRequest()) {
                return new JsonResponse(['status' => 'success', 'message' => 'Employee updated successfully.']);
            }

            $this->addFlash('success', 'Employee updated successfully.');
            return $this->redirectToRoute('employee_index');
        }

        return $this->render('employee/edit.html.twig', [
            'form'     => $form->createView(),
            'employee' => $employee,
        ]);
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(
        Request $request,
        Employee $employee,
        EntityManagerInterface $em,
        UserRepository $userRepo
    ): JsonResponse {
        if (!$this->isCsrfTokenValid('delete_employee_' . $employee->getId(), $request->request->get('_token'))) {
            return new JsonResponse(['status' => 'error', 'message' => 'Invalid CSRF token.'], 403);
        }

        $user = $userRepo->findOneBy(['email' => $employee->getEmail()]);
        if ($user) {
            $em->remove($user);
        }

        $em->remove($employee);
        $em->flush();

        return new JsonResponse(['status' => 'success', 'message' => 'Employee deleted successfully.']);
    }
}