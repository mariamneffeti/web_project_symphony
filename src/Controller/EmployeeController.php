<?php

namespace App\Controller;
use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use App\Entity\Employee;
use App\Form\EmployeeType;
use App\Repository\EmployeeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/employees', name: 'employee_')]
class EmployeeController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        EmployeeRepository $repo,
        EntityManagerInterface $em,
        UserRepository $userRepo,
        UserPasswordHasherInterface $hasher
    ): Response {
        $employee = new Employee();
        $form = $this->createForm(EmployeeType::class, $employee);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $existingUser = $userRepo->findOneBy(['email' => $employee->getEmail()]);
            if ($existingUser && $request->isXmlHttpRequest()) {
                return new JsonResponse([
                    'status'  => 'error',
                    'message' => 'A user with this email already exists.'
                ]);
            }
            $tempPassword = 'Emp@' . rand(1000, 9999);
            $user = new User();
            $user->setFirstName($employee->getFirstName());
            $user->setLastName('NN');
            $user->setEmail($employee->getEmail());
            $user->setRole('employee');
            $user->setPassword($hasher->hashPassword($user, $tempPassword));
            $em->persist($user);
            $em->flush();
            if ($request->isXmlHttpRequest()) {
                $employee->setLastName('NN');
                $employee->setHireDate(new \DateTime());
                $employee->setUserId(1);
                $employee->setCompanyId(1);
                $cvFile = $request->files->get('cv_file');
                if ($cvFile) {
                    $allowedMimes = ['application/pdf', 'application/msword',
                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];

                    if (!in_array($cvFile->getMimeType(), $allowedMimes)) {
                        return new JsonResponse(['status' => 'error', 'message' => 'Invalid file format.']);
                    }

                    if ($cvFile->getSize() > 5 * 1024 * 1024) {
                        return new JsonResponse(['status' => 'error', 'message' => 'File too large (max 5MB).']);
                    }

                    $cvDir      = $this->getParameter('kernel.project_dir') . '/public/uploads/cv';
                    if (!is_dir($cvDir)) {
                        mkdir($cvDir, 0755, true);
                    }

                    $newFilename = uniqid('cv_') . '.' . $cvFile->guessExtension();
                    $cvFile->move($cvDir, $newFilename);
                    $employee->setCvPath('uploads/cv/' . $newFilename);
                }
                $em->persist($employee);
                $em->flush();

                return new JsonResponse([
                    'status'  => 'success',
                    'message' => 'Employee added successfully',
                    'id'      => $employee->getId(),
                ]);
            }

            $employee->setLastName('NN');
            $employee->setHireDate(new \DateTime());
            $employee->setUserId(1);
            $employee->setCompanyId(1);

            $em->persist($employee);
            $em->flush();

            $this->addFlash('success', 'Employee added successfully.');
            return $this->redirectToRoute('employee_index');
        }

        if ($form->isSubmitted() && $request->isXmlHttpRequest()) {
            $errors = [];
            foreach ($form->getErrors(true) as $error) {
                $errors[] = $error->getMessage();
            }
            return new JsonResponse(['status' => 'error', 'message' => implode(', ', $errors)], 422);
        }

        $search = $request->query->get('search', '');
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
    public function edit(Request $request, Employee $employee, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(EmployeeType::class, $employee);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            if ($request->isXmlHttpRequest()) {
                return new JsonResponse(['status' => 'success', 'message' => 'Employee updated successfully']);
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
    public function delete(Request $request, Employee $employee, EntityManagerInterface $em , UserRepository $userRepo): JsonResponse
    {
        if ($this->isCsrfTokenValid('delete_employee_' . $employee->getId(), $request->request->get('_token'))) {
            $user = $userRepo->findOneBy(['email' => $employee->getEmail()]);
            if ($user) {
                $em->remove($user);
            }
            $em->remove($employee);
            $em->flush();
            return new JsonResponse(['status' => 'success', 'message' => 'Employee deleted successfully']);
        }

        return new JsonResponse(['status' => 'error', 'message' => 'Invalid CSRF token'], 403);
    }
}
