<?php

namespace App\Controller;

use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/products', name: 'product_')]
class ProductController extends AbstractController
{
    // ── Page ─────────────────────────────────────────────────────────────────

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(): Response
    {
        //$this->denyAccessUnlessGranted('ROLE_EMPLOYEE');
        return $this->render('products/index.html.twig');
    }

    // ── Helper ────────────────────────────────────────────────────────────────

    private function getCompanyId(): int
    {
        // TODO: replace with real user when auth is set up
        // return $this->getUser()->getCompanyId();
        return 1;
    }

    // ── LIST ──────────────────────────────────────────────────────────────────

    #[Route('/api/list', name: 'api_list', methods: ['GET'])]
    public function list(Request $request, Connection $db): JsonResponse
    {
        $page      = max(1, (int) $request->query->get('page', 1));
        $perPage   = min(200, max(1, (int) $request->query->get('per_page', 50)));
        $offset    = ($page - 1) * $perPage;
        $search    = $request->query->get('search', '');
        $category  = $request->query->get('category', '');
        $companyId = $this->getCompanyId();

        $qb = $db->createQueryBuilder()
            ->select('id', 'product_name', 'sku', 'price', 'stock_quantity',
                     'category', 'description', 'min_threshold')
            ->from('products')
            ->where('company_id = :company_id')
            ->setParameter('company_id', $companyId)
            ->orderBy('product_name', 'ASC')
            ->setFirstResult($offset)
            ->setMaxResults($perPage);

        if ($search) {
            $qb->andWhere('product_name LIKE :search OR sku LIKE :search')
               ->setParameter('search', "%$search%");
        }

        if ($category) {
            $qb->andWhere('category = :category')
               ->setParameter('category', $category);
        }

        $products = $qb->executeQuery()->fetchAllAssociative();

        // Count
        $countQb = $db->createQueryBuilder()
            ->select('COUNT(*) AS total')
            ->from('products')
            ->where('company_id = :company_id')
            ->setParameter('company_id', $companyId);

        if ($search) {
            $countQb->andWhere('product_name LIKE :search OR sku LIKE :search')
                    ->setParameter('search', "%$search%");
        }
        if ($category) {
            $countQb->andWhere('category = :category')
                    ->setParameter('category', $category);
        }

        $total = (int) $countQb->executeQuery()->fetchOne();

        return $this->json([
            'success' => true,
            'data'    => $products,
            'pagination' => [
                'total'       => $total,
                'page'        => $page,
                'per_page'    => $perPage,
                'total_pages' => (int) ceil($total / $perPage),
            ],
        ]);
    }

    // ── GET SINGLE ────────────────────────────────────────────────────────────

    #[Route('/api/get/{id}', name: 'api_get', methods: ['GET'])]
    public function get(int $id, Connection $db): JsonResponse
    {
        $product = $db->fetchAssociative(
            'SELECT * FROM products WHERE id = ? AND company_id = ?',
            [$id, $this->getCompanyId()]
        );

        if (!$product) {
            return $this->json(['success' => false, 'error' => 'Product not found'], 404);
        }

        return $this->json(['success' => true, 'data' => $product]);
    }

    // ── CREATE ────────────────────────────────────────────────────────────────

    #[Route('/api/create', name: 'api_create', methods: ['POST'])]
    public function create(Request $request, Connection $db): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];

        if (empty($data['product_name'])) {
            return $this->json(['success' => false, 'error' => 'Missing field: product_name'], 400);
        }

        $companyId = $this->getCompanyId();

        try {
            $db->insert('products', [
                'company_id'     => $companyId,
                'product_name'   => $data['product_name'],
                'sku'            => $data['sku']            ?? null,
                'category'       => $data['category']       ?? null,
                'price'          => $data['price']          ?? 0,
                'stock_quantity' => $data['stock_quantity'] ?? 0,
                'min_threshold'  => $data['min_threshold']  ?? 20,
                'description'    => $data['description']    ?? null,
            ]);

            $id = (int) $db->lastInsertId();

            return $this->json([
                'success'    => true,
                'message'    => 'Product created successfully',
                'product_id' => $id,
            ], 201);

        } catch (\Throwable $e) {
            return $this->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    // ── UPDATE ────────────────────────────────────────────────────────────────

    #[Route('/api/update/{id}', name: 'api_update', methods: ['POST', 'PUT'])]
    public function update(int $id, Request $request, Connection $db): JsonResponse
    {
        $data      = json_decode($request->getContent(), true) ?? [];
        $companyId = $this->getCompanyId();

        if (empty($data['product_name'])) {
            return $this->json(['success' => false, 'error' => 'Missing field: product_name'], 400);
        }

        // Verify ownership
        $existing = $db->fetchAssociative(
            'SELECT id FROM products WHERE id = ? AND company_id = ?',
            [$id, $companyId]
        );
        if (!$existing) {
            return $this->json(['success' => false, 'error' => 'Product not found'], 404);
        }

        try {
            $affected = $db->update('products', [
                'product_name'   => $data['product_name'],
                'sku'            => $data['sku']            ?? null,
                'category'       => $data['category']       ?? null,
                'price'          => $data['price']          ?? 0,
                'stock_quantity' => $data['stock_quantity'] ?? 0,
                'min_threshold'  => $data['min_threshold']  ?? 20,
                'description'    => $data['description']    ?? null,
            ], ['id' => $id, 'company_id' => $companyId]);

            return $this->json([
                'success' => true,
                'message' => $affected ? 'Product updated successfully' : 'No changes made',
            ]);

        } catch (\Throwable $e) {
            return $this->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    // ── DELETE ────────────────────────────────────────────────────────────────

    #[Route('/api/delete/{id}', name: 'api_delete', methods: ['POST', 'DELETE'])]
    public function delete(int $id, Connection $db): JsonResponse
    {
        $companyId = $this->getCompanyId();

        $product = $db->fetchAssociative(
            'SELECT id FROM products WHERE id = ? AND company_id = ?',
            [$id, $companyId]
        );

        if (!$product) {
            return $this->json(['success' => false, 'error' => 'Product not found'], 404);
        }

        try {
            $db->delete('products', ['id' => $id, 'company_id' => $companyId]);
            return $this->json(['success' => true, 'message' => 'Product deleted successfully']);
        } catch (\Throwable $e) {
            return $this->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    // ── CATEGORIES ────────────────────────────────────────────────────────────

    #[Route('/api/categories', name: 'api_categories', methods: ['GET'])]
    public function categories(Connection $db): JsonResponse
    {
        $rows = $db->fetchAllAssociative(
            'SELECT DISTINCT category FROM products WHERE company_id = ? AND category IS NOT NULL ORDER BY category ASC',
            [$this->getCompanyId()]
        );

        $categories = array_column($rows, 'category');

        return $this->json(['success' => true, 'data' => $categories]);
    }
}