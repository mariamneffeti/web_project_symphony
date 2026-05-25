<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_COMPANY')]
class StockCompanyController extends AbstractCompanyController
{
    #[Route('/stockCompany', name: 'stock_company', methods: ['GET'])]
    public function index(): Response
    {
        $companyName = $this->getCompanyContext()->getCompanyName();

        return $this->render('stock_company/index.html.twig', [
            'company_name' => $companyName,
        ]);
    }
}