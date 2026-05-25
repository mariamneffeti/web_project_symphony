<?php

namespace App\Controller;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

final class LoginController extends AbstractController
{
    #[Route('/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        /** @var User|null $user */
        $user = $this->getUser();

        // If user is already authenticated, redirect by role
        if ($user !== null) {
            $roles = $user->getRoles();

            return match (true) {
                in_array('ROLE_COMPANY', $roles, true)  => $this->redirectToRoute('company_dashboard'),
                in_array('ROLE_EMPLOYEE', $roles, true) => $this->redirectToRoute('employee_dashboard'),
                default                                 => $this->redirectToRoute('normal_user_home'),
            };
        }

        // Login form data
        $error = $authenticationUtils->getLastAuthenticationError();
        $error        = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('login/index.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }
}
