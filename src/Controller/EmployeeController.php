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
use Symfony\Component\Routing\Annotation\Route;

#[Route('/employees', name: 'employee_')]
class EmployeeController extends AbstractController
{

    #[Route('', name: 'index', methods: ['GET', 'POST'])]
    public function index(
        Request                     $request,
        EmployeeRepository          $repo,
        EntityManagerInterface      $em,
        UserRepository              $userRepo,
        UserPasswordHasherInterface $hasher
    ): Response {
        $employee = new Employee();
        $form     = $this->createForm(EmployeeType::class, $employee);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {


            $existingUser = $userRepo->findOneBy(['email' => $employee->getEmail()]);
            if ($existingUser) {
                if ($request->isXmlHttpRequest()) {
                    return new JsonResponse([
                        'status'  => 'error',
                        'message' => 'A user with this email already exists.'
                    ]);
                }
                $this->addFlash('error', 'A user with this email already exists.');
                return $this->redirectToRoute('employee_index');
            }


            $cvFile = $request->files->get('cv_file');
            if ($cvFile) {
                $allowedMimes = [
                    'application/pdf',
                    'application/msword',
                    'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
                ];

                if (!in_array($cvFile->getMimeType(), $allowedMimes)) {
                    return new JsonResponse(['status' => 'error', 'message' => 'Invalid file format. Only PDF, DOC, DOCX allowed.']);
                }

                if ($cvFile->getSize() > 5 * 1024 * 1024) {
                    return new JsonResponse(['status' => 'error', 'message' => 'File too large (max 5MB).']);
                }

                $cvDir = $this->getParameter('kernel.project_dir') . '/public/uploads/cv';
                if (!is_dir($cvDir)) {
                    mkdir($cvDir, 0755, true);
                }

                $newFilename = 'cv_' . uniqid() . '.' . $cvFile->guessExtension();
                $cvFile->move($cvDir, $newFilename);
                $employee->setCvPath('uploads/cv/' . $newFilename);
            }


            $tempPassword = 'Emp@123';

            $user = new User();
            $user->setFirstName($employee->getFirstName());
            $user->setLastName('NN');
            $user->setEmail($employee->getEmail());
            $user->setRole('employee');
            $user->setPassword($hasher->hashPassword($user, $tempPassword));
            $em->persist($user);
            $em->flush();


            $employee->setLastName('NN');
            $employee->setHireDate(new \DateTime());
            $employee->setUserId($user->getId());
            $employee->setCompanyId(1);

            $em->persist($employee);
            $em->flush();

            if ($request->isXmlHttpRequest()) {
                return new JsonResponse([
                    'status'        => 'success',
                    'message'       => 'Employee added successfully',
                    'temp_password' => $tempPassword,
                    'email'         => $employee->getEmail(),
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
        $employees = $search ? $repo->findBySearch($search) : $repo->findAll();

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
                return new JsonResponse([
                    'status'  => 'success',
                    'message' => 'Employee updated successfully'
                ]);
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
        Request                $request,
        Employee               $employee,
        EntityManagerInterface $em,
        UserRepository         $userRepo
    ): JsonResponse {
        if ($this->isCsrfTokenValid('delete_employee_' . $employee->getId(), $request->request->get('_token'))) {


            $user = $userRepo->findOneBy(['email' => $employee->getEmail()])
                ?? $userRepo->find($employee->getUserId());

            if ($user) {
                $em->remove($user);
            }

            $em->remove($employee);
            $em->flush();

            return new JsonResponse([
                'status'  => 'success',
                'message' => 'Employee and user account deleted successfully'
            ]);
        }

        return new JsonResponse([
            'status'  => 'error',
            'message' => 'Invalid CSRF token'
        ], 403);
    }
}
