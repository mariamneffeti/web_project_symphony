<?php

namespace App\Controller;

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
        EntityManagerInterface $em
    ): Response {
        $employee = new Employee();
        $form = $this->createForm(EmployeeType::class, $employee);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Handle AJAX JSON submission
            if ($request->isXmlHttpRequest()) {
                $employee->setLastName('NN');
                $employee->setHireDate(new \DateTime());
                $employee->setUserId(1);
                $employee->setCompanyId(1);

                $em->persist($employee);
                $em->flush();

                return new JsonResponse([
                    'status'  => 'success',
                    'message' => 'Employee added successfully',
                    'id'      => $employee->getId(),
                ]);
            }

            // Normal form submission fallback
            $employee->setLastName('NN');
            $employee->setHireDate(new \DateTime());
            $employee->setUserId(1);
            $employee->setCompanyId(1);

            $em->persist($employee);
            $em->flush();

            $this->addFlash('success', 'Employee added successfully.');
            return $this->redirectToRoute('employee_index');
        }

        // AJAX validation error response
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
    public function delete(Request $request, Employee $employee, EntityManagerInterface $em): JsonResponse
    {
        if ($this->isCsrfTokenValid('delete_employee_' . $employee->getId(), $request->request->get('_token'))) {
            $em->remove($employee);
            $em->flush();
            return new JsonResponse(['status' => 'success', 'message' => 'Employee deleted successfully']);
        }

        return new JsonResponse(['status' => 'error', 'message' => 'Invalid CSRF token'], 403);
    }
}
