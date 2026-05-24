<?php

namespace App\Controller;

use App\Entity\Article;
use App\Entity\User;
use App\Form\ProfileType;
use App\Repository\ArticleRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

class NormalUserController extends AbstractController
{
    // ─── MOCK USER ────────────────────────────────────────────────────────────
    private function getMockOrRealUser(UserRepository $userRepository): ?User
    {
        $realUser = $this->getUser();
        if ($realUser instanceof User) {
            return $realUser;
        }
        return $userRepository->findOneBy(['email' => 'meriam.cherif2005@gmail.com']);
    }
    // ──────────────────────────────────────────────────────────────────────────

    #[Route('/normaluser/home', name: 'normal_user_home')]
    public function home(
        ArticleRepository $articleRepository,
        UserRepository $userRepository
    ): Response {
        $articles = $articleRepository->findBy([], ['arDate' => 'DESC'], 9);
        $mockUser = $this->getMockOrRealUser($userRepository);

        return $this->render('normaluser/home.html.twig', [
            'articles'  => $articles,
            'mock_user' => $mockUser,
        ]);
    }

    #[Route('/normaluser/profile', name: 'normal_user_profile', methods: ['GET', 'POST'])]
    public function profile(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $hasher,
        SluggerInterface $slugger,
        UserRepository $userRepository
    ): Response {
        /** @var User $user */
        $user = $this->getMockOrRealUser($userRepository);

        if (!$user) {
            throw $this->createNotFoundException('User not found.');
        }

        $form = $this->createForm(ProfileType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            // Handle password change
            $plainPassword = $form->get('plainPassword')->getData();
            if (!empty($plainPassword)) {
                $user->setPassword($hasher->hashPassword($user, $plainPassword));
            }

            // Handle image upload
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
                    return $this->redirectToRoute('normal_user_profile');
                }
            }

            $em->flush();
            $this->addFlash('success', 'Profile updated successfully!');
            return $this->redirectToRoute('normal_user_profile');
        }

        return $this->render('normaluser/profile.html.twig', [
            'form' => $form->createView(),
            'user' => $user,
        ]);
    }
}
