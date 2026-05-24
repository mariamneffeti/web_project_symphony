<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use App\Entity\User;


final class LoginController extends AbstractController
{
    #[Route('/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
<<<<<<< HEAD
        /** @var User|null $user */
        $user = $this->getUser();

        if ($user !== null) {
            $roles = $user->getRoles();

            return match (true) {
                in_array('ROLE_COMPANY',  $roles) => $this->redirectToRoute('company_dashboard'),
                in_array('ROLE_EMPLOYEE', $roles) => $this->redirectToRoute('employee_dashboard'),
                default                           => $this->redirectToRoute('normal_user_home'),
            };
        }

        $error        = $authenticationUtils->getLastAuthenticationError();
=======
        $error = $authenticationUtils->getLastAuthenticationError();
>>>>>>> 3bbcf2c0ed251fe0eadf006f6371cc12e77ac804
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('login/index.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }
}
