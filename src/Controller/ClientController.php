<?php

namespace App\Controller;

use App\Entity\Client;
use App\Repository\ClientRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/client', name: 'client_')]
#[IsGranted('ROLE_USER')]

class ClientController extends AbstractController
{
    #[Route('/dashboard', name: 'dashboard')]
    public function dashboard(): Response
    {
        /** @var User $currentUser */
        $currentUser = $this->getUser();

        return $this->render('client/index.html.twig', [
            'user' => $currentUser,
        ]);
    }

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(): Response
    {
        //$this->denyAccessUnlessGranted('ROLE_EMPLOYEE');

        return $this->render('client/index.html.twig');
    }

    #[Route('/api/list', name: 'api_list', methods: ['GET'])]
    public function list(ClientRepository $repo): JsonResponse
    {
        //$this->denyAccessUnlessGranted('ROLE_EMPLOYEE');

        return $this->json([
            'success' => true,
            'data'    => $repo->findAllAsArray(),
        ]);
    }


    #[Route('/api/create', name: 'api_create', methods: ['POST'])]
    public function create(
        Request $request,
        EntityManagerInterface $em,
        ValidatorInterface $validator
    ): JsonResponse {
        //$this->denyAccessUnlessGranted('ROLE_EMPLOYEE');

        $data = json_decode($request->getContent(), true) ?? [];

        $client = new Client();
        $client->setClientName($data['client_name'] ?? '');
        $client->setEmail($data['email'] ?? '');
        $client->setPhone($data['phone'] ?? null);
        $client->setAddress($data['address'] ?? null);
        $client->setClientType($data['client_type'] ?? 'B2C');
        $client->setStatus($data['status'] ?? 'Active');

        $errors = $validator->validate($client);
        if (count($errors) > 0) {
            return $this->json(['success' => false, 'error' => (string) $errors], 422);
        }

        $em->persist($client);
        $em->flush();

        return $this->json(['success' => true, 'data' => $client->toArray()], 201);
    }


    #[Route('/api/update/{id}', name: 'api_update', methods: ['POST', 'PUT'])]
    public function update(
        int $id,
        Request $request,
        ClientRepository $repo,
        EntityManagerInterface $em,
        ValidatorInterface $validator
    ): JsonResponse {
        //$this->denyAccessUnlessGranted('ROLE_EMPLOYEE');

        $client = $repo->find($id);
        if (!$client) {
            return $this->json(['success' => false, 'error' => 'Client not found'], 404);
        }

        $data = json_decode($request->getContent(), true) ?? [];

        if (isset($data['client_name'])) $client->setClientName($data['client_name']);
        if (isset($data['email']))       $client->setEmail($data['email']);
        if (array_key_exists('phone', $data))   $client->setPhone($data['phone']);
        if (array_key_exists('address', $data)) $client->setAddress($data['address']);
        if (isset($data['client_type'])) $client->setClientType($data['client_type']);
        if (isset($data['status']))      $client->setStatus($data['status']);

        $errors = $validator->validate($client);
        if (count($errors) > 0) {
            return $this->json(['success' => false, 'error' => (string) $errors], 422);
        }

        $em->flush();

        return $this->json(['success' => true, 'data' => $client->toArray()]);
    }


    #[Route('/api/delete/{id}', name: 'api_delete', methods: ['POST', 'DELETE'])]
    public function delete(
        int $id,
        ClientRepository $repo,
        EntityManagerInterface $em
    ): JsonResponse {
        //$this->denyAccessUnlessGranted('ROLE_EMPLOYEE');

        $client = $repo->find($id);
        if (!$client) {
            return $this->json(['success' => false, 'error' => 'Client not found'], 404);
        }

        $em->remove($client);
        $em->flush();

        return $this->json(['success' => true]);
    }
}