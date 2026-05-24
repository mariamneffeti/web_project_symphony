<?php

namespace App\Controller;

use App\Repository\ServiceRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/services', name: 'services_')]
class ServicesController extends AbstractController
{
    public function __construct(
        private readonly ServiceRepository $serviceRepository
    ) {}

    /**
     * GET /services/
     * Render the services index page
     */
    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(): Response
    {
        //$this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        return $this->render('services/index.html.twig');
    }

    /**
     * GET /services/list
     * Return all services for the authenticated user's company
     */
    #[Route('/list', name: 'list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        //$this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $services = $this->serviceRepository->findAll();

        $data = array_map(fn($service) => [
            'id'           => $service->getId(),
            'service_name' => $service->getServiceName(),
            'base_price'   => $service->getBasePrice(),
        ], $services);

        return $this->json([
            'success' => true,
            'data'    => $data,
        ]);
    }

    /**
     * GET /services/{id}
     * Return a single service by ID for the authenticated user's company
     */
    #[Route('/{id}', name: 'get', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function get(int $id): JsonResponse
    {
        //$this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $user = $this->getUser();

        $service = $this->serviceRepository->findOneByIdAndCompany(
            $id,
            $user->getCompany()->getId()
        );

        if (!$service) {
            return $this->json(['error' => 'Service not found'], Response::HTTP_NOT_FOUND);
        }

        return $this->json([
            'success' => true,
            'data'    => [
                'id'           => $service->getId(),
                'service_name' => $service->getServiceName(),
                'base_price'   => $service->getBasePrice(),
                'description'  => $service->getDescription(),
                'company_id'   => $service->getCompany()->getId(),
            ],
        ]);
    }

    /**
     * POST /services/create
     * Create a new service for the authenticated user's company
     */
    #[Route('/create', name: 'create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        //$this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $data = json_decode($request->getContent(), true);

        if (empty($data['service_name']) || !isset($data['base_price'])) {
            return $this->json(
                ['error' => 'service_name and base_price are required'],
                Response::HTTP_BAD_REQUEST
            );
        }

        $user = $this->getUser();

        $service = $this->serviceRepository->createService(
            serviceName: $data['service_name'],
            basePrice:   (float) $data['base_price'],
            description: $data['description'] ?? null,
            company:     $user->getCompany()
        );

        return $this->json([
            'success' => true,
            'data'    => [
                'id'           => $service->getId(),
                'service_name' => $service->getServiceName(),
                'base_price'   => $service->getBasePrice(),
            ],
        ], Response::HTTP_CREATED);
    }

    /**
     * PUT /services/{id}
     * Update an existing service
     */
    #[Route('/{id}', name: 'update', methods: ['PUT'], requirements: ['id' => '\d+'])]
    public function update(int $id, Request $request): JsonResponse
    {
        //$this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $user = $this->getUser();

        $service = $this->serviceRepository->findOneByIdAndCompany(
            $id,
            $user->getCompany()->getId()
        );

        if (!$service) {
            return $this->json(['error' => 'Service not found'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);

        $this->serviceRepository->updateService($service, [
            'service_name' => $data['service_name'] ?? null,
            'base_price'   => isset($data['base_price']) ? (float) $data['base_price'] : null,
            'description'  => $data['description'] ?? null,
        ]);

        return $this->json([
            'success' => true,
            'data'    => [
                'id'           => $service->getId(),
                'service_name' => $service->getServiceName(),
                'base_price'   => $service->getBasePrice(),
            ],
        ]);
    }

    /**
     * DELETE /services/{id}
     * Delete a service
     */
    #[Route('/{id}', name: 'delete', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    public function delete(int $id): JsonResponse
    {
        //$this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $user = $this->getUser();

        $service = $this->serviceRepository->findOneByIdAndCompany(
            $id,
            $user->getCompany()->getId()
        );

        if (!$service) {
            return $this->json(['error' => 'Service not found'], Response::HTTP_NOT_FOUND);
        }

        $this->serviceRepository->deleteService($service);

        return $this->json(['success' => true, 'message' => 'Service deleted']);
    }
}