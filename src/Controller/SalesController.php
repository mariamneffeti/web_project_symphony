<?php

namespace App\Controller;

use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/sales', name: 'sales_')]
class SalesController extends AbstractController
{
    // ── Page ────────────────────────────────────────────────────────────────────

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(): Response
    {
        //$this->denyAccessUnlessGranted('ROLE_EMPLOYEE');
        return $this->render('sales/index.html.twig');
    }

    // ── Helper ───────────────────────────────────────────────────────────────────

    private function getCompanyId(): int
    {
        // TODO: replace with real user when auth is set up
        // return $this->getUser()->getCompanyId();
        return 1;
    }

    // ── LIST ─────────────────────────────────────────────────────────────────────

    #[Route('/api/list', name: 'api_list', methods: ['GET'])]
    public function list(Request $request, Connection $db): JsonResponse
    {
        $page      = max(1, (int) $request->query->get('page', 1));
        $perPage   = min(200, max(1, (int) $request->query->get('per_page', 10)));
        $offset    = ($page - 1) * $perPage;
        $search    = $request->query->get('search', '');
        $start     = $request->query->get('start_date', '');
        $end       = $request->query->get('end_date', '');
        $companyId = $this->getCompanyId();

        $qb = $db->createQueryBuilder()
            ->select('s.*', 'c.client_name',
                     "CONCAT(e.first_name, ' ', e.last_name) AS employee_name")
            ->from('sales', 's')
            ->leftJoin('s', 'clients',   'c', 's.client_id   = c.id')
            ->leftJoin('s', 'employees', 'e', 's.employee_id = e.id')
            ->where('s.company_id = :company_id')
            ->setParameter('company_id', $companyId)
            ->orderBy('s.sale_date', 'DESC')
            ->addOrderBy('s.id', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($perPage);

        if ($search) {
            $qb->andWhere('s.transaction_id LIKE :search OR c.client_name LIKE :search')
               ->setParameter('search', "%$search%");
        }
        if ($start) {
            $qb->andWhere('s.sale_date >= :start')->setParameter('start', $start);
        }
        if ($end) {
            $qb->andWhere('s.sale_date <= :end')->setParameter('end', $end);
        }

        $sales = $qb->executeQuery()->fetchAllAssociative();

        $countQb = $db->createQueryBuilder()
            ->select('COUNT(*) AS total')
            ->from('sales', 's')
            ->leftJoin('s', 'clients', 'c', 's.client_id = c.id')
            ->where('s.company_id = :company_id')
            ->setParameter('company_id', $companyId);

        if ($search) {
            $countQb->andWhere('s.transaction_id LIKE :search OR c.client_name LIKE :search')
                    ->setParameter('search', "%$search%");
        }
        if ($start) { $countQb->andWhere('s.sale_date >= :start')->setParameter('start', $start); }
        if ($end)   { $countQb->andWhere('s.sale_date <= :end')->setParameter('end', $end); }

        $total = (int) $countQb->executeQuery()->fetchOne();

        return $this->json([
            'success' => true,
            'data'    => $sales,
            'pagination' => [
                'total'       => $total,
                'page'        => $page,
                'per_page'    => $perPage,
                'total_pages' => (int) ceil($total / $perPage),
            ],
        ]);
    }

    // ── GET SINGLE ───────────────────────────────────────────────────────────────

    #[Route('/api/get/{id}', name: 'api_get', methods: ['GET'])]
    public function get(int $id, Connection $db): JsonResponse
    {
        $sale = $db->fetchAssociative(
            "SELECT s.*, c.client_name, c.email AS client_email, c.phone AS client_phone,
                    CONCAT(e.first_name, ' ', e.last_name) AS employee_name
             FROM sales s
             LEFT JOIN clients   c ON s.client_id   = c.id
             LEFT JOIN employees e ON s.employee_id = e.id
             WHERE s.id = ? AND s.company_id = ?",
            [$id, $this->getCompanyId()]
        );

        if (!$sale) {
            return $this->json(['success' => false, 'error' => 'Sale not found'], 404);
        }

        $sale['product_items'] = $db->fetchAllAssociative(
            'SELECT * FROM sale_items WHERE sale_id = ?', [$id]
        );
        $sale['service_items'] = $db->fetchAllAssociative(
            'SELECT * FROM service_sale_items WHERE sale_id = ?', [$id]
        );

        return $this->json(['success' => true, 'data' => $sale]);
    }

    // ── CREATE ───────────────────────────────────────────────────────────────────

    #[Route('/api/create', name: 'api_create', methods: ['POST'])]
    public function create(Request $request, Connection $db): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];

        foreach (['client_id', 'sale_date', 'payment_method'] as $field) {
            if (empty($data[$field])) {
                return $this->json(['success' => false, 'error' => "Missing field: $field"], 400);
            }
        }

        $companyId = $this->getCompanyId();

        $productSubtotal = 0;
        foreach ($data['product_items'] ?? [] as $item) {
            $productSubtotal += $item['quantity'] * $item['unit_price'];
        }
        $serviceSubtotal = 0;
        foreach ($data['service_items'] ?? [] as $svc) {
            $serviceSubtotal += $svc['quantity_hours'] * $svc['unit_price'];
        }
        $subtotal      = $productSubtotal + $serviceSubtotal;
        $discount      = (float) ($data['discount'] ?? 0);
        $tax           = (float) ($data['tax'] ?? $subtotal * 0.1);
        $total         = $subtotal - $discount + $tax;
        $transactionId = 'TX-' . date('Y') . '-' . str_pad(rand(1, 99999), 5, '0', STR_PAD_LEFT);

        $db->beginTransaction();
        try {
            $db->insert('sales', [
                'transaction_id' => $transactionId,
                'company_id'     => $companyId,
                'employee_id'    => $data['employee_id'] ?? null,
                'client_id'      => $data['client_id'],
                'sale_date'      => $data['sale_date'],
                'subtotal'       => $subtotal,
                'discount'       => $discount,
                'tax'            => $tax,
                'total_amount'   => $total,
                'payment_method' => $data['payment_method'],
                'payment_status' => $data['payment_status'] ?? 'Pending',
                'notes'          => $data['notes'] ?? null,
            ]);
            $saleId = (int) $db->lastInsertId();

            foreach ($data['product_items'] ?? [] as $item) {
                $db->insert('sale_items', [
                    'sale_id'      => $saleId,
                    'product_id'   => $item['product_id'] ?? null,
                    'product_name' => $item['product_name'],
                    'quantity'     => $item['quantity'],
                    'unit_price'   => $item['unit_price'],
                    'total_price'  => $item['quantity'] * $item['unit_price'],
                ]);
            }

            foreach ($data['service_items'] ?? [] as $svc) {
                $db->insert('service_sale_items', [
                    'sale_id'        => $saleId,
                    'service_name'   => $svc['service_name'],
                    'quantity_hours' => $svc['quantity_hours'],
                    'unit_price'     => $svc['unit_price'],
                    'total_price'    => $svc['quantity_hours'] * $svc['unit_price'],
                ]);
            }

            $invoiceNumber = 'INV-' . date('Y') . '-' . str_pad(rand(1, 99999), 5, '0', STR_PAD_LEFT);
            $db->insert('invoices', [
                'invoice_number' => $invoiceNumber,
                'sale_id'        => $saleId,
                'issue_date'     => $data['sale_date'],
                'due_date'       => date('Y-m-d', strtotime($data['sale_date'] . ' +30 days')),
            ]);

            $db->executeStatement(
                'UPDATE clients SET total_spent = total_spent + ?, last_purchase_date = ? WHERE id = ?',
                [$total, $data['sale_date'], $data['client_id']]
            );

            $db->commit();

            return $this->json([
                'success'        => true,
                'message'        => 'Sale created successfully',
                'sale_id'        => $saleId,
                'transaction_id' => $transactionId,
                'invoice_number' => $invoiceNumber,
            ], 201);

        } catch (\Throwable $e) {
            $db->rollBack();
            return $this->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    // ── UPDATE STATUS ────────────────────────────────────────────────────────────

    #[Route('/api/update/{id}', name: 'api_update', methods: ['POST', 'PUT'])]
    public function updateStatus(int $id, Request $request, Connection $db): JsonResponse
    {
        $data      = json_decode($request->getContent(), true) ?? [];
        $newStatus = $data['payment_status'] ?? 'Pending';

        $affected = $db->executeStatement(
            'UPDATE sales SET payment_status = ? WHERE id = ? AND company_id = ?',
            [$newStatus, $id, $this->getCompanyId()]
        );

        if (!$affected) {
            return $this->json(['success' => false, 'error' => 'Sale not found'], 404);
        }

        return $this->json(['success' => true]);
    }

    // ── DELETE ───────────────────────────────────────────────────────────────────

    #[Route('/api/delete/{id}', name: 'api_delete', methods: ['POST', 'DELETE'])]
    public function delete(int $id, Connection $db): JsonResponse
    {
        $sale = $db->fetchAssociative(
            'SELECT * FROM sales WHERE id = ? AND company_id = ?',
            [$id, $this->getCompanyId()]
        );

        if (!$sale) {
            return $this->json(['success' => false, 'error' => 'Sale not found'], 404);
        }

        $db->beginTransaction();
        try {
            $db->executeStatement(
                'UPDATE clients SET total_spent = total_spent - ? WHERE id = ?',
                [$sale['total_amount'], $sale['client_id']]
            );
            $db->executeStatement('DELETE FROM sales WHERE id = ?', [$id]);
            $db->commit();
            return $this->json(['success' => true, 'message' => 'Sale deleted successfully']);
        } catch (\Throwable $e) {
            $db->rollBack();
            return $this->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    // ── STATS ────────────────────────────────────────────────────────────────────

    #[Route('/api/stats', name: 'api_stats', methods: ['GET'])]
    public function stats(Connection $db): JsonResponse
    {
        $companyId = $this->getCompanyId();

        $today = $db->fetchAssociative(
            "SELECT COUNT(*) AS count, COALESCE(SUM(total_amount), 0) AS total
             FROM sales WHERE company_id = ? AND DATE(sale_date) = CURDATE()",
            [$companyId]
        );

        $thisMonth = $db->fetchAssociative(
            "SELECT COUNT(*) AS count, COALESCE(SUM(total_amount), 0) AS total
             FROM sales WHERE company_id = ?
             AND YEAR(sale_date) = YEAR(CURDATE())
             AND MONTH(sale_date) = MONTH(CURDATE())",
            [$companyId]
        );

        $totalClients = $db->fetchOne(
            'SELECT COUNT(*) FROM clients WHERE company_id = ?', [$companyId]
        );

        $recentSales = $db->fetchAllAssociative(
            "SELECT DATE(sale_date) AS date, COUNT(*) AS count, SUM(total_amount) AS total
             FROM sales WHERE company_id = ?
             AND sale_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
             GROUP BY DATE(sale_date) ORDER BY date DESC",
            [$companyId]
        );

        return $this->json([
            'success' => true,
            'data'    => [
                'today'         => $today,
                'this_month'    => $thisMonth,
                'total_clients' => (int) $totalClients,
                'recent_sales'  => $recentSales,
            ],
        ]);
    }
}