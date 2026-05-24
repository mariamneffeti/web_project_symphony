<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class SalesCController extends AbstractController
{
    #[Route('/sales/c', name: 'salesC')]
    public function index(): Response
    {
        return $this->render('sales_c/index.html.twig', [
            'controller_name' => 'SalesCController',
        ]);
    }
}
