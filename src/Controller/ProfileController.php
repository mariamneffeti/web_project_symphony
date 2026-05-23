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
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $hasher,
        SluggerInterface $slugger,
        UserRepository $userRepo
    ): Response {
        $user = $userRepo->find(1);

        if (!$user) {
            $user = new \App\Entity\User();
            $user->setFirstName('Admin');
            $user->setLastName('User');
            $user->setEmail('admin@entreprisa.com');
            $user->setRole('company');
            $user->setPassword('temp');
        }

        $form = $this->createForm(ProfileType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $plainPassword = $form->get('plainPassword')->getData();
            if (!empty($plainPassword)) {
                $user->setPassword($hasher->hashPassword($user, $plainPassword));
            }

            /** @var \Symfony\Component\HttpFoundation\File\UploadedFile|null $imageFile */
            $imageFile = $form->get('imageFile')->getData();
            if ($imageFile) {
                $safeFilename = $slugger->slug($user->getFirstName() . '-' . $user->getLastName());
                $newFilename  = $safeFilename . '-' . uniqid() . '.' . $imageFile->guessExtension();

                try {
                    $imageFile->move(
                        $this->getParameter('uploads_directory'),
                        $newFilename
                    );
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
