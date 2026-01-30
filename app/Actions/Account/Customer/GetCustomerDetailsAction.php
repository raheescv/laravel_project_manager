<?php

namespace App\Actions\Account\Customer;

use App\Models\Account;
use App\Models\Sale;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class GetCustomerDetailsAction
{
    private const RECENT_SALES_LIMIT = 5;

    public function execute(int $id): array
    {
        try {
            $customer = $this->getCustomer($id);
            $salesStatistics = $this->getSalesStatistics($id);
            $recentSales = $this->getRecentSales($id);
            $feedbacks = $this->getFeedbacks($id);

            return [
                'success' => true,
                'customer' => $customer,
                'total_sales' => $salesStatistics['total_sales'],
                'total_amount' => $salesStatistics['total_amount'],
                'total_paid' => $salesStatistics['total_paid'],
                'total_balance' => $salesStatistics['total_balance'],
                'last_purchase' => $salesStatistics['last_purchase'],
                'recent_sales' => $recentSales,
                'feedbacks' => $feedbacks,
            ];
        } catch (ModelNotFoundException $e) {
            return [
                'success' => false,
                'message' => 'Customer not found',
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to load customer details: '.$e->getMessage(),
            ];
        }
    }

    private function getCustomer(int $id): Account
    {
        return Account::with('customerType')->findOrFail($id);
    }

    private function getSalesStatistics(int $accountId): array
    {
        $statistics = Sale::where('account_id', $accountId)
            ->selectRaw('
                COUNT(*) as total_sales,
                COALESCE(SUM(grand_total), 0) as total_amount,
                COALESCE(SUM(paid), 0) as total_paid,
                COALESCE(SUM(balance), 0) as total_balance,
                MAX(date) as last_purchase
            ')
            ->first();

        return [
            'total_sales' => (int) ($statistics->total_sales ?? 0),
            'total_amount' => (float) ($statistics->total_amount ?? 0),
            'total_paid' => (float) ($statistics->total_paid ?? 0),
            'total_balance' => (float) ($statistics->total_balance ?? 0),
            'last_purchase' => $statistics->last_purchase,
        ];
    }

    private function getRecentSales(int $accountId): array
    {
        return Sale::where('account_id', $accountId)
            ->select([
                'id',
                'invoice_no',
                'date',
                'grand_total',
                'balance',
                'status',
                'rating',
            ])
            ->withCount('items')
            ->orderBy('date', 'desc')
            ->limit(self::RECENT_SALES_LIMIT)
            ->get()
            ->map(fn ($sale) => $this->mapSaleToArray($sale))
            ->toArray();
    }

    private function getFeedbacks(int $accountId): array
    {
        return Sale::where('account_id', $accountId)
            ->where(function ($query) {
                $query->whereNotNull('feedback');
            })
            ->select([
                'id',
                'invoice_no',
                'date',
                'rating',
                'feedback_type',
                'feedback',
            ])
            ->orderBy('date', 'desc')
            ->get()
            ->map(fn ($sale) => $this->mapFeedbackToArray($sale))
            ->toArray();
    }

    private function mapSaleToArray($sale): array
    {
        return [
            'id' => $sale->id,
            'invoice_no' => $sale->invoice_no,
            'date' => $sale->date,
            'total' => $sale->grand_total,
            'balance' => $sale->balance,
            'status' => $sale->status,
            'items_count' => $sale->items_count,
            'rating' => $sale->rating,
        ];
    }

    private function mapFeedbackToArray($sale): array
    {
        return [
            'id' => $sale->id,
            'invoice_no' => $sale->invoice_no,
            'date' => $sale->date,
            'rating' => $sale->rating,
            'feedback_type' => $sale->feedback_type,
            'feedback' => $sale->feedback,
        ];
    }
}
