<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomeCompanyController extends AbstractCompanyController
{
    #[Route('/homeCompany', name: 'homeCompany')]
    public function index(): Response
    {
        $company = $this->getCompanyContext();
        return $this->render('home_company/index.html.twig', [
            'company_name' => $company->getCompanyName(),
        ]);
    }
}
