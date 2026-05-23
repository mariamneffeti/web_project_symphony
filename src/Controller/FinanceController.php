<?php

namespace App\Controller;

use App\Entity\Expense;
use App\Repository\ExpenseRepository;
use App\Repository\CompanyRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class FinanceController extends AbstractController
{
    #[Route('/finance', name: 'finance')]
    public function index(ExpenseRepository $repo, CompanyRepository $companyRepo): Response
    {
        $company = $companyRepo->find(1);

        $expenses = $repo->findBy(
            ['company' => $company],
            ['id' => 'DESC']
        );

        return $this->render('finance/index.html.twig', [
            'expenses' => $expenses
        ]);
    }

    // KPI
    #[Route('/finance/kpis', name: 'finance_kpis')]
    public function kpis(ExpenseRepository $repo, CompanyRepository $companyRepo): JsonResponse
    {
        $company = $companyRepo->find(1);
        $expenses = $repo->findBy(['company' => $company]);

        $total = 0;

        foreach ($expenses as $e) {
            $total += (float) $e->getAmount();
        }

        return $this->json([
            'status' => 'success',
            'data' => [
                'revenue' => 0,
                'expenses' => $total,
                'profit' => -$total,
                'salesCount' => 0
            ]
        ]);
    }

    // Chart
    #[Route('/finance/chart', name: 'finance_chart')]
    public function chart(Request $request, ExpenseRepository $repo, CompanyRepository $companyRepo): JsonResponse
    {
        $company = $companyRepo->find(1);
        $year = (int) $request->query->get('year');

        $expenses = $repo->findBy(['company' => $company]);

        $monthly = array_fill(1, 12, 0);

        foreach ($expenses as $e) {
            $date = $e->getExpenseDate();

            if ((int)$date->format('Y') === $year) {
                $m = (int)$date->format('n');
                $monthly[$m] += (float)$e->getAmount();
            }
        }

        $data = [];

        foreach ($monthly as $m => $total) {
            $data[] = [
                'month' => $m,
                'total' => $total
            ];
        }

        return $this->json([
            'status' => 'success',
            'data' => [
                'expenses' => $data
            ]
        ]);
    }

    // Adding expenses
    #[Route('/finance/add', name: 'finance_add', methods: ['POST'])]
    public function add(Request $request, EntityManagerInterface $em, CompanyRepository $companyRepo): JsonResponse
    {
        $company = $companyRepo->find(1);

        $expense = new Expense();
        $expense->setCompany($company);
        $expense->setExpenseDate(new \DateTime($request->request->get('date')));
        $expense->setCategory($request->request->get('type'));
        $expense->setAmount($request->request->get('amount'));
        $expense->setDescription($request->request->get('description'));

        $em->persist($expense);
        $em->flush();

        return $this->json([
            'status' => 'success',
            'expense' => [
                'date' => $expense->getExpenseDate()->format('Y-m-d'),
                'category' => $expense->getCategory(),
                'amount' => $expense->getAmount(),
                'description' => $expense->getDescription()
            ]
        ]);
    }
}