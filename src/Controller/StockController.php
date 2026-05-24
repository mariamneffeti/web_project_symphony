<?php

namespace App\Controller;

use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/stock', name: 'stock_')]
class StockController extends AbstractController
{
    public function __construct(
        private readonly ProductRepository $productRepository
    ) {}

    /**
     * GET /stock/
     * Render the stock management page
     */
    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('stock/index.html.twig');
    }

    /**
     * GET /stock/list
     * Return all products with stock info
     */
    #[Route('/list', name: 'list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $products = $this->productRepository->findAll();

        $data = array_map(fn($product) => [
            'id'             => $product->getId(),
            'product_name'   => $product->getProductName(),
            'sku'            => $product->getSku(),
            'category'       => $product->getCategory(),
            'price'          => $product->getPrice(),
            'stock_quantity' => $product->getStockQuantity(),
            'description'    => $product->getDescription(),
        ], $products);

        return $this->json(['success' => true, 'data' => $data]);
    }

    /**
     * GET /stock/{id}
     * Return a single product by ID
     */
    #[Route('/{id}', name: 'get', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function get(int $id): JsonResponse
    {
        $product = $this->productRepository->find($id);

        if (!$product) {
            return $this->json(['error' => 'Product not found'], Response::HTTP_NOT_FOUND);
        }

        return $this->json([
            'success' => true,
            'data'    => [
                'id'             => $product->getId(),
                'product_name'   => $product->getProductName(),
                'sku'            => $product->getSku(),
                'category'       => $product->getCategory(),
                'price'          => $product->getPrice(),
                'stock_quantity' => $product->getStockQuantity(),
                'description'    => $product->getDescription(),
            ],
        ]);
    }
}