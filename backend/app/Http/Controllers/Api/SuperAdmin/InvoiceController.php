<?php

namespace App\Http\Controllers\Api\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\School;
use App\Models\Subscription;
use App\Traits\SendsNotifications;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class InvoiceController extends Controller
{
    use SendsNotifications;
    /**
     * Display a listing of invoices.
     */
    public function index(Request $request)
    {
        $query = Invoice::with(['school', 'subscription.planModel', 'creator']);

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by billing cycle
        if ($request->has('billing_cycle')) {
            $query->where('billing_cycle', $request->billing_cycle);
        }

        // Filter by school
        if ($request->has('school_id')) {
            $query->where('school_id', $request->school_id);
        }

        // Filter by date range
        if ($request->has('start_date') && $request->has('end_date')) {
            $query->whereBetween('invoice_date', [$request->start_date, $request->end_date]);
        }

        // Filter by type
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        // Search by invoice number or school name
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                  ->orWhereHas('school', function ($q) use ($search) {
                      $q->where('name', 'like', "%{$search}%");
                  });
            });
        }

        $invoices = $query->orderBy('invoice_date', 'desc')
            ->paginate($request->get('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => ['invoices' => $invoices],
        ]);
    }

    /**
     * Store a newly created invoice.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'school_id' => 'required|exists:schools,id',
            'subscription_id' => 'nullable|exists:subscriptions,id',
            'type' => 'required|in:subscription,one_time,credit,debit',
            'billing_cycle' => 'required|in:monthly,quarterly,yearly',
            'amount' => 'required|numeric|min:0',
            'tax_amount' => 'nullable|numeric|min:0',
            'discount_amount' => 'nullable|numeric|min:0',
            'invoice_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:invoice_date',
            'billing_period_start' => 'nullable|date',
            'billing_period_end' => 'nullable|date|after_or_equal:billing_period_start',
            'notes' => 'nullable|string',
            'metadata' => 'nullable|array',
        ]);

        $taxAmount = $validated['tax_amount'] ?? 0;
        $discountAmount = $validated['discount_amount'] ?? 0;
        $baseAmount = $validated['amount'];
        $totalAmount = $baseAmount + $taxAmount - $discountAmount;

        $invoice = new Invoice();
        $invoice->school_id = $validated['school_id'];
        $invoice->subscription_id = $validated['subscription_id'] ?? null;
        $invoice->invoice_number = (new Invoice())->generateInvoiceNumber();
        $invoice->status = 'pending';
        $invoice->type = $validated['type'];
        $invoice->amount = $baseAmount;
        $invoice->tax_amount = $taxAmount;
        $invoice->discount_amount = $discountAmount;
        $invoice->total_amount = $totalAmount;
        $invoice->paid_amount = 0;
        $invoice->balance = $totalAmount;
        $invoice->currency = 'INR';
        $invoice->billing_cycle = $validated['billing_cycle'];
        $invoice->invoice_date = $validated['invoice_date'];
        $invoice->due_date = $validated['due_date'];
        $invoice->billing_period_start = $validated['billing_period_start'] ?? null;
        $invoice->billing_period_end = $validated['billing_period_end'] ?? null;
        $invoice->notes = $validated['notes'] ?? null;
        $invoice->metadata = $validated['metadata'] ?? null;
        $invoice->created_by = auth()->id();
        $invoice->save();

        return response()->json([
            'success' => true,
            'message' => 'Invoice created successfully',
            'data' => ['invoice' => $invoice->load(['school', 'subscription.planModel'])],
        ], 201);
    }

    /**
     * Display the specified invoice.
     */
    public function show($id)
    {
        $invoice = Invoice::with(['school', 'subscription.planModel', 'creator', 'payments.processor'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => ['invoice' => $invoice],
        ]);
    }

    /**
     * Update the specified invoice.
     */
    public function update(Request $request, $id)
    {
        $invoice = Invoice::findOrFail($id);

        $validated = $request->validate([
            'status' => 'sometimes|in:pending,paid,overdue,cancelled',
            'amount' => 'sometimes|numeric|min:0',
            'tax_amount' => 'sometimes|numeric|min:0',
            'discount_amount' => 'sometimes|numeric|min:0',
            'due_date' => 'sometimes|date',
            'notes' => 'nullable|string',
            'metadata' => 'nullable|array',
        ]);

        if (isset($validated['amount']) || isset($validated['tax_amount']) || isset($validated['discount_amount'])) {
            $baseAmount = $validated['amount'] ?? $invoice->amount;
            $taxAmount = $validated['tax_amount'] ?? $invoice->tax_amount;
            $discountAmount = $validated['discount_amount'] ?? $invoice->discount_amount;
            $totalAmount = $baseAmount + $taxAmount - $discountAmount;

            $validated['total_amount'] = $totalAmount;
            $validated['balance'] = $totalAmount - ($invoice->paid_amount ?? 0);
        }

        $invoice->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Invoice updated successfully',
            'data' => ['invoice' => $invoice->fresh(['school', 'subscription.planModel'])],
        ]);
    }

    /**
     * Record a payment for an invoice.
     */
    public function recordPayment(Request $request, $id)
    {
        $invoice = Invoice::findOrFail($id);

        if ($invoice->isPaid()) {
            return response()->json([
                'success' => false,
                'message' => 'Invoice is already paid',
            ], 422);
        }

        $validated = $request->validate([
            'amount' => 'required|numeric|min:0|max:' . $invoice->balance,
            'payment_method' => 'required|in:cash,card,online,bank_transfer,cheque',
            'payment_date' => 'required|date',
            'reference_number' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            // Create payment record
            $payment = new Payment();
            $payment->invoice_id = $invoice->id;
            $payment->school_id = $invoice->school_id;
            $payment->transaction_id = (new Payment())->generateTransactionId();
            $payment->amount = $validated['amount'];
            $payment->payment_method = $validated['payment_method'];
            $payment->status = 'completed';
            $payment->payment_date = $validated['payment_date'];
            $payment->reference_number = $validated['reference_number'] ?? null;
            $payment->notes = $validated['notes'] ?? null;
            $payment->processed_by = auth()->id();
            $payment->save();

            // Update invoice
            $invoice->paid_amount = ($invoice->paid_amount ?? 0) + $validated['amount'];
            $invoice->balance = max(0, $invoice->total_amount - $invoice->paid_amount);

            if ($invoice->balance <= 0) {
                $invoice->status = 'paid';
                $invoice->paid_at = now();
                $invoice->payment_method = $validated['payment_method'];
            } else {
                $invoice->status = 'partial';
            }

            $invoice->save();

            DB::commit();

            // Send payment confirmation email
            $school = $invoice->school;
            if ($school && $school->email) {
                $this->sendPaymentConfirmationNotification(
                    $school->email,
                    $school->name,
                    $invoice->invoice_number,
                    $validated['amount'],
                    $validated['payment_method'],
                    $payment->transaction_id
                );
            }

            return response()->json([
                'success' => true,
                'message' => 'Payment recorded successfully',
                'data' => [
                    'payment' => $payment->load('processor'),
                    'invoice' => $invoice->fresh(['school', 'subscription.planModel']),
                ],
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to record payment: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get invoice statistics.
     */
    public function stats(Request $request)
    {
        $query = Invoice::query();

        // Filter by date range
        if ($request->has('start_date') && $request->has('end_date')) {
            $query->whereBetween('invoice_date', [$request->start_date, $request->end_date]);
        }

        $totalInvoices = $query->count();
        $paidInvoices = (clone $query)->where('status', 'paid')->count();
        $pendingInvoices = (clone $query)->where('status', 'pending')->count();
        $overdueInvoices = (clone $query)->where('status', 'overdue')->count();

        $totalRevenue = $query->where('status', 'paid')->sum('paid_amount');
        $pendingRevenue = (clone $query)->where('status', 'pending')->sum('total_amount');
        $overdueRevenue = (clone $query)->where('status', 'overdue')->sum('total_amount');

        // Group by billing cycle
        $byCycle = Invoice::select('billing_cycle')
            ->selectRaw('count(*) as count')
            ->selectRaw('sum(total_amount) as total')
            ->groupBy('billing_cycle')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'total_invoices' => $totalInvoices,
                'paid_invoices' => $paidInvoices,
                'pending_invoices' => $pendingInvoices,
                'overdue_invoices' => $overdueInvoices,
                'total_revenue' => $totalRevenue,
                'pending_revenue' => $pendingRevenue,
                'overdue_revenue' => $overdueRevenue,
                'by_billing_cycle' => $byCycle,
            ],
        ]);
    }

    /**
     * Cancel an invoice.
     */
    public function cancel($id)
    {
        $invoice = Invoice::findOrFail($id);

        if ($invoice->isPaid()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot cancel a paid invoice',
            ], 422);
        }

        $invoice->update(['status' => 'cancelled']);

        return response()->json([
            'success' => true,
            'message' => 'Invoice cancelled successfully',
            'data' => ['invoice' => $invoice],
        ]);
    }

    /**
     * Mark invoice as overdue (can be run via scheduled task).
     */
    public function markOverdue()
    {
        $count = Invoice::where('status', 'pending')
            ->where('due_date', '<', Carbon::today())
            ->update(['status' => 'overdue']);

        return response()->json([
            'success' => true,
            'message' => "{$count} invoices marked as overdue",
        ]);
    }
}
