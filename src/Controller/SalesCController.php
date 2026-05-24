<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class SalesCController extends AbstractCompanyController
{
    #[Route('/sales/c', name: 'salesC')]
    public function index(): Response
    {
        $company = $this->getCompanyContext();
        return $this->render('sales_c/index.html.twig', [
            'company_name' => $company->getCompanyName(),
        ]);
    }
}
