<?php

namespace App\Controller;

use App\Entity\Article;
use App\Form\ArticleType;
use App\Repository\ArticleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/articles', name: 'article_')]
class ArticleController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        ArticleRepository $repo,
        EntityManagerInterface $em
    ): Response {
        $article = new Article();
        $form    = $this->createForm(ArticleType::class, $article);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $article->setCompanyId(1);

            $em->persist($article);
            $em->flush();

            if ($request->isXmlHttpRequest()) {
                return new JsonResponse([
                    'status'  => 'success',
                    'message' => 'Article added successfully',
                    'id'      => $article->getId(),
                ]);
            }

            $this->addFlash('success', 'Article published successfully.');
            return $this->redirectToRoute('article_index');
        }

        if ($form->isSubmitted() && $request->isXmlHttpRequest()) {
            $errors = [];
            foreach ($form->getErrors(true) as $e) {
                $errors[] = $e->getMessage();
            }
            return new JsonResponse(['status' => 'error', 'message' => implode(', ', $errors)], 422);
        }

        $search   = $request->query->get('search', '');
        $category = $request->query->get('category', '');

        if ($search) {
            $articles = $repo->findBySearch($search);
        } elseif ($category) {
            $articles = $repo->findByCategory($category);
        } else {
            $articles = $repo->findAll();
        }

        return $this->render('article/index.html.twig', [
            'form'     => $form->createView(),
            'articles' => $articles,
            'search'   => $search,
            'category' => $category,
        ]);
    }

    #[Route('/{id}', name: 'view', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function view(Article $article): Response
    {
        return $this->render('article/view.html.twig', ['article' => $article]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function edit(Request $request, Article $article, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(ArticleType::class, $article);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            if ($request->isXmlHttpRequest()) {
                return new JsonResponse(['status' => 'success', 'message' => 'Article updated successfully']);
            }

            $this->addFlash('success', 'Article updated successfully.');
            return $this->redirectToRoute('article_index');
        }

        return $this->render('article/edit.html.twig', [
            'form'    => $form->createView(),
            'article' => $article,
        ]);
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(Request $request, Article $article, EntityManagerInterface $em): JsonResponse
    {
        if ($this->isCsrfTokenValid('delete_article_' . $article->getId(), $request->request->get('_token'))) {
            $em->remove($article);
            $em->flush();
            return new JsonResponse(['status' => 'success', 'message' => 'Article deleted successfully']);
        }

        return new JsonResponse(['status' => 'error', 'message' => 'Invalid CSRF token'], 403);
    }
}
