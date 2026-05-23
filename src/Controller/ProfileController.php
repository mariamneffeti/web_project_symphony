<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\ProfileType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/profile', name: 'profile_')]
class ProfileController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET', 'POST'])]
    public function index(
        Request                     $request,
        EntityManagerInterface      $em,
        UserPasswordHasherInterface $hasher,
        SluggerInterface            $slugger,
        UserRepository              $userRepo
    ): Response {

        // Récupérer l'utilisateur (temporaire — remplacer par $this->getUser() avec auth)
        $user = $userRepo->find(1);

        if (!$user) {
            $user = new User();
            $user->setFirstName('Admin');
            $user->setLastName('User');
            $user->setEmail('admin@entreprisa.com');
            $user->setRole('company');
            $user->setPassword('temp');
            $em->persist($user);
            $em->flush();
        }

        $form = $this->createForm(ProfileType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            // ── Mot de passe ────────────────────────────────────
            $plainPassword = $form->get('plainPassword')->getData();
            if (!empty($plainPassword)) {
                $user->setPassword($hasher->hashPassword($user, $plainPassword));
            }

            // ── Upload image ────────────────────────────────────
            $imageFile = $form->get('imageFile')->getData();
            if ($imageFile) {
                $safeFilename = $slugger->slug($user->getFirstName() . '-' . $user->getLastName());
                $newFilename  = $safeFilename . '-' . uniqid() . '.' . $imageFile->guessExtension();

                try {
                    $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads';

                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }

                    $imageFile->move($uploadDir, $newFilename);
                    $user->setImage($newFilename);

                } catch (FileException $e) {
                    $this->addFlash('error', 'Image upload failed: ' . $e->getMessage());
                    return $this->redirectToRoute('profile_index');
                }
            }

            $em->flush();
            $this->addFlash('success', 'Profile updated successfully.');
            return $this->redirectToRoute('profile_index');
        }

        return $this->render('profile/index.html.twig', [
            'form' => $form->createView(),
            'user' => $user,
        ]);
    }
}
