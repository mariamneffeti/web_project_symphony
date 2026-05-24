<?php

namespace App\Controller;

use App\Repository\UserRepository;
use App\Repository\EmployeeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/company', name: 'company_')]
#[IsGranted('ROLE_COMPANY')]
class CompanyController extends AbstractController
{
    #[Route('/dashboard', name: 'dashboard')]
    public function dashboard(
        UserRepository $userRepo,
        EmployeeRepository $employeeRepo
    ): Response {
        return $this->render('home/index.html.twig', [
            'total_users'     => count($userRepo->findAll()),
            'total_employees' => count($employeeRepo->findAll()),
        ]);
    }

    #[Route('/users', name: 'users', methods: ['GET'])]
    public function users(UserRepository $userRepo): Response
    {
        return $this->render('admin/users.html.twig', [
            'users' => $userRepo->findAll(),
        ]);
    }

    #[Route('/users/{id}/delete', name: 'user_delete', methods: ['POST'])]
    public function deleteUser(
        Request $request,
        int $id,
        UserRepository $userRepo,
        EntityManagerInterface $em
    ): JsonResponse {
        $user = $userRepo->find($id);

        if (!$user) {
            return new JsonResponse(['status' => 'error', 'message' => 'User not found.'], 404);
        }

        if (!$this->isCsrfTokenValid('delete_user_' . $id, $request->request->get('_token'))) {
            return new JsonResponse(['status' => 'error', 'message' => 'Invalid CSRF token.'], 403);
        }

        $em->remove($user);
        $em->flush();

        return new JsonResponse(['status' => 'success', 'message' => 'User deleted successfully.']);
    }
}