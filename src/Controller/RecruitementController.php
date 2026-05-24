<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class RecruitementController extends AbstractCompanyController
{
    #[Route('/recruitement', name: 'recruitement')]
    public function index(): Response
    {
        $company = $this->getCompanyContext();

        return $this->render('recruitement/index.html.twig', [
            'company_name' => $company->getCompanyName(),
        ]);
    }
}