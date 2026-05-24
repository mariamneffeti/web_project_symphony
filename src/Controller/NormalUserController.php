<?php

namespace App\Controller;

use App\Entity\Article;
use App\Entity\User;
use App\Repository\ArticleRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

class NormalUserController extends AbstractController
{
    // ─── MOCK USER ────────────────────────────────────────────────────────────
    // TODO: remove this method and use $this->getUser() everywhere once login is wired up
    private function getMockOrRealUser(UserRepository $userRepository): ?User
    {
        $realUser = $this->getUser();
        if ($realUser instanceof User) {
            return $realUser; // ← automatically used once login works
        }

        // Temporary mock: loads the first user from the DB (Yasmine from your seed data)
        return $userRepository->findOneBy(['email' => 'meriam.cherif2005@gmail.com']);
    }
    // ──────────────────────────────────────────────────────────────────────────

    #[Route('/normaluser/home', name: 'normal_user_home')]
    public function home(
        ArticleRepository $articleRepository,
        UserRepository $userRepository
    ): Response {
        $articles  = $articleRepository->findBy([], ['arDate' => 'DESC'], 9);
        $mockUser  = $this->getMockOrRealUser($userRepository);

        return $this->render('normaluser/home.html.twig', [
            'articles' => $articles,
            'mock_user' => $mockUser,   // passed to Twig for the navbar
        ]);
    }

    #[Route('/normaluser/profile', name: 'normal_user_profile', methods: ['GET', 'POST'])]
    public function profile(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $hasher,
        UserRepository $userRepository
    ): Response {
        /** @var User $user */
        $user = $this->getMockOrRealUser($userRepository);

        if ($request->isMethod('POST')) {
            $user->setFirstName($request->request->get('first_name'));
            $user->setLastName($request->request->get('last_name'));
            $user->setEmail($request->request->get('email'));

            $password = $request->request->get('password');
            $confirm  = $request->request->get('confirm_password');

            if (!empty($password)) {
                if ($password !== $confirm) {
                    $this->addFlash('error', 'Passwords do not match.');
                    return $this->redirectToRoute('normal_user_profile');
                }
                $user->setPassword($hasher->hashPassword($user, $password));
            }

            $imageFile = $request->files->get('image');
            if ($imageFile) {
                $allowed = ['image/jpeg', 'image/png', 'image/webp'];
                if (!in_array($imageFile->getMimeType(), $allowed)) {
                    $this->addFlash('error', 'Invalid image format. Use JPG, PNG or WEBP.');
                    return $this->redirectToRoute('normal_user_profile');
                }
                if ($imageFile->getSize() > 2 * 1024 * 1024) {
                    $this->addFlash('error', 'Image too large (max 2MB).');
                    return $this->redirectToRoute('normal_user_profile');
                }

                $newFilename = uniqid('img_') . '.' . $imageFile->guessExtension();
                $imageFile->move($this->getParameter('uploads_dir'), $newFilename);
                $user->setImage($newFilename);
            }

            $em->flush();
            $this->addFlash('success', 'Profile updated successfully!');
            return $this->redirectToRoute('normal_user_profile');
        }

        return $this->render('normaluser/profile.html.twig', [
            'user' => $user,
        ]);
    }
}
