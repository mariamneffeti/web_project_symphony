<?php

namespace App\Controller;

use App\Entity\Expense;
use App\Form\ExpenseFormType;
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
    public function index(
        Request $request,
        ExpenseRepository $expenseRepository,
        CompanyRepository $companyRepository,
        EntityManagerInterface $em
    ): Response {

        $company = $companyRepository->find(1);

        if (!$company) {
            throw $this->createNotFoundException('Company not found');
        }

        // Transaction form
        $expense = new Expense();
        $form = $this->createForm(ExpenseFormType::class, $expense);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $expense->setCompany($company);

            $em->persist($expense);
            $em->flush();

            return $this->redirectToRoute('finance');
        }

        $expenses = $expenseRepository->findBy(
            ['company' => $company],
            ['id' => 'DESC']
        );

        // KPIs
        $conn = $em->getConnection();
        $year = date('Y');

        $revenue = $conn->fetchOne("
            SELECT COALESCE(SUM(total_amount), 0)
            FROM sales
            WHERE company_id = :id
            AND payment_status = 'Paid'
            AND YEAR(sale_date) = :year
        ", [
            'id' => $company->getId(),
            'year' => $year
        ]);

        $totalExpenses = $conn->fetchOne("
            SELECT COALESCE(SUM(amount), 0)
            FROM expenses
            WHERE company_id = :id
            AND YEAR(expense_date) = :year
        ", [
            'id' => $company->getId(),
            'year' => $year
        ]);

        $salesCount = $conn->fetchOne("
            SELECT COUNT(*)
            FROM sales
            WHERE company_id = :id
            AND YEAR(sale_date) = :year
        ", [
            'id' => $company->getId(),
            'year' => $year
        ]);

        $revenue = (float) $revenue;
        $totalExpenses = (float) $totalExpenses;

        $netProfit = $revenue - $totalExpenses;

        return $this->render('finance/index.html.twig', [
            'expenses' => $expenses,

            'totalRevenue' => $revenue,
            'totalExpenses' => $totalExpenses,
            'netProfit' => $netProfit,
            'salesCount' => (int) $salesCount,

            'expenseForm' => $form->createView(),
        ]);
    }

    // KPIs
    #[Route('/finance/kpis', name: 'finance_kpis')]
    public function kpis(
        EntityManagerInterface $em,
        CompanyRepository $companyRepository
    ): JsonResponse {

        $company = $companyRepository->find(1);
        $year = date('Y');

        $conn = $em->getConnection();

        $revenue = $conn->fetchOne("
            SELECT COALESCE(SUM(total_amount), 0)
            FROM sales
            WHERE company_id = :id
            AND payment_status = 'Paid'
            AND YEAR(sale_date) = :year
        ", [
            'id' => $company->getId(),
            'year' => $year
        ]);

        $expenses = $conn->fetchOne("
            SELECT COALESCE(SUM(amount), 0)
            FROM expenses
            WHERE company_id = :id
            AND YEAR(expense_date) = :year
        ", [
            'id' => $company->getId(),
            'year' => $year
        ]);

        $salesCount = $conn->fetchOne("
            SELECT COUNT(*)
            FROM sales
            WHERE company_id = :id
            AND YEAR(sale_date) = :year
        ", [
            'id' => $company->getId(),
            'year' => $year
        ]);

        $revenue = (float) $revenue;
        $expenses = (float) $expenses;

        return $this->json([
            'status' => 'success',
            'data' => [
                'revenue' => $revenue,
                'expenses' => $expenses,
                'profit' => $revenue - $expenses,
                'salesCount' => (int) $salesCount
            ]
        ]);
    }

    // chart
    #[Route('/finance/chart', name: 'finance_chart')]
    public function chart(
        Request $request,
        EntityManagerInterface $em,
        CompanyRepository $companyRepository
    ): JsonResponse {

        $company = $companyRepository->find(1);
        $year = $request->query->get('year') ?? date('Y');

        $conn = $em->getConnection();

        $expenses = $conn->fetchAllAssociative("
            SELECT MONTH(expense_date) as month, SUM(amount) as total
            FROM expenses
            WHERE company_id = :id
            AND YEAR(expense_date) = :year
            GROUP BY MONTH(expense_date)
            ORDER BY month ASC
        ", [
            'id' => $company->getId(),
            'year' => $year
        ]);

        $sales = $conn->fetchAllAssociative("
            SELECT MONTH(sale_date) as month, SUM(total_amount) as total
            FROM sales
            WHERE company_id = :id
            AND YEAR(sale_date) = :year
            GROUP BY MONTH(sale_date)
            ORDER BY month ASC
        ", [
            'id' => $company->getId(),
            'year' => $year
        ]);

        return $this->json([
            'data' => [
                'expenses' => $expenses,
                'sales' => $sales
            ]
        ]);
    }

    // adding transarction via API (used in JS to add without refreshing)
    #[Route('/finance/add', name: 'finance_add', methods: ['POST'])]
    public function add(
        Request $request,
        CompanyRepository $companyRepository,
        EntityManagerInterface $em
    ): JsonResponse {

        $company = $companyRepository->find(1);

        if (!$company) {
            return $this->json([
                'status' => 'error',
                'message' => 'Company not found'
            ]);
        }

        $expense = new Expense();
        $form = $this->createForm(ExpenseFormType::class, $expense);
        $form->handleRequest($request);

        if (!$form->isSubmitted() || !$form->isValid()) {
            return $this->json([
                'status' => 'error',
                'message' => 'Invalid form data'
            ]);
        }

        $expense->setCompany($company);

        $em->persist($expense);
        $em->flush();

        return $this->json([
            'status' => 'success',
            'expense' => [
                'date' => $expense->getExpenseDate()->format('Y-m-d'),
                'category' => $expense->getCategory(),
                'amount' => $expense->getAmount(),
                'description' => $expense->getDescription(),
            ]
        ]);
    }
}