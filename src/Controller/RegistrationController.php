<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Entity\Company;
use App\Entity\User;
use App\Form\RegistrationFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;

use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class RegistrationController extends AbstractController
{
    #[Route('/registration', name: 'app_registration')]
    public function register(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $em
    ): Response
    {
        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $user->setPassword(
                $passwordHasher->hashPassword($user, $form->get('plainPassword')->getData())
            );

            if ($user->getRole() === 'company') {
                $company = new Company();
                $company->setUser($user);
                $company->setCompanyName($form->get('companyName')->getData());
                $company->setIndustry($form->get('industry')->getData());
                $company->setAddress($form->get('address')->getData());
                $company->setPhone($form->get('phone')->getData());
                $em->persist($company);
            }

            $em->persist($user);
            $em->flush();

            return $this->redirectToRoute(
                $user->getRole() === 'company' ? 'app_rh' : 'app_client'
            );
        }
            return $this->render('registration/registration.html.twig', [
                'registrationForm' => $form->createView()]);
        }

}
