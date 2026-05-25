<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ClientsCompanyController extends AbstractCompanyController
{
    #[Route('/clientsCompany', name: 'clients_company')]
    public function index(): Response
    {
        $companyName = $this->getCompanyContext()->getCompanyName();
        return $this->render('clients_company/index.html.twig', [
            'company_name' => $companyName,
        ]);
    }
}
