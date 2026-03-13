<?php

namespace App\Console\Commands;

use App\Models\Invoice;
use App\Models\Subscription;
use App\Models\School;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class GenerateInvoices extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'invoices:generate 
                            {--cycle= : Billing cycle (monthly, quarterly, yearly)}
                            {--school= : Specific school ID}
                            {--force : Force generate even if invoice exists}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate invoices for school subscriptions based on billing cycle';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting invoice generation...');

        $cycle = $this->option('cycle');
        $schoolId = $this->option('school');
        $force = $this->option('force');

        // Get active subscriptions
        $query = Subscription::where('status', 'active')
            ->whereHas('school', function ($q) {
                $q->where('is_active', true);
            });

        if ($schoolId) {
            $query->where('school_id', $schoolId);
        }

        if ($cycle) {
            $query->where('billing_cycle', $cycle);
        }

        $subscriptions = $query->with(['school', 'planModel'])->get();

        if ($subscriptions->isEmpty()) {
            $this->warn('No active subscriptions found.');
            return 0;
        }

        $this->info("Found {$subscriptions->count()} active subscription(s).");

        $generated = 0;
        $skipped = 0;

        foreach ($subscriptions as $subscription) {
            try {
                // Check if invoice already exists for this period
                $periodStart = $this->getBillingPeriodStart($subscription);
                $periodEnd = $this->getBillingPeriodEnd($subscription, $periodStart);

                $existingInvoice = Invoice::where('subscription_id', $subscription->id)
                    ->where('billing_period_start', $periodStart)
                    ->where('billing_period_end', $periodEnd)
                    ->first();

                if ($existingInvoice && !$force) {
                    $this->warn("Invoice already exists for school: {$subscription->school->name}. Skipping...");
                    $skipped++;
                    continue;
                }

                // Calculate dates
                $invoiceDate = Carbon::today();
                $dueDate = $this->getDueDate($subscription->billing_cycle, $invoiceDate);

                // Calculate amounts
                $baseAmount = $subscription->amount ?? 0;
                $taxRate = $subscription->school->config['tax_rate'] ?? 0;
                $taxAmount = $baseAmount * ($taxRate / 100);
                $discountAmount = $subscription->school->config['discount_amount'] ?? 0;
                $totalAmount = $baseAmount + $taxAmount - $discountAmount;

                // Create invoice
                $invoice = new Invoice();
                $invoice->school_id = $subscription->school_id;
                $invoice->subscription_id = $subscription->id;
                $invoice->invoice_number = (new Invoice())->generateInvoiceNumber();
                $invoice->status = 'pending';
                $invoice->type = 'subscription';
                $invoice->amount = $baseAmount;
                $invoice->tax_amount = $taxAmount;
                $invoice->discount_amount = $discountAmount;
                $invoice->total_amount = $totalAmount;
                $invoice->paid_amount = 0;
                $invoice->balance = $totalAmount;
                $invoice->currency = 'INR';
                $invoice->billing_cycle = $subscription->billing_cycle;
                $invoice->invoice_date = $invoiceDate;
                $invoice->due_date = $dueDate;
                $invoice->billing_period_start = $periodStart;
                $invoice->billing_period_end = $periodEnd;
                $invoice->notes = "Auto-generated invoice for {$subscription->billing_cycle} subscription";
                $invoice->metadata = [
                    'plan_name' => $subscription->planModel?->name,
                    'generated_by' => 'artisan_command',
                ];
                $invoice->created_by = null; // System generated
                $invoice->save();

                $this->info("✓ Invoice {$invoice->invoice_number} created for {$subscription->school->name}");
                $generated++;

            } catch (\Exception $e) {
                $this->error("Failed to create invoice for {$subscription->school->name}: {$e->getMessage()}");
            }
        }

        $this->info("\nInvoice generation complete!");
        $this->info("Generated: {$generated}, Skipped: {$skipped}");

        return 0;
    }

    /**
     * Get the billing period start date.
     */
    private function getBillingPeriodStart($subscription): Carbon
    {
        // If subscription has starts_at, use it as reference
        $referenceDate = $subscription->starts_at ? 
            Carbon::parse($subscription->starts_at) : 
            Carbon::today();

        $cycle = $subscription->billing_cycle;

        // Calculate the start of current billing period
        $today = Carbon::today();
        $monthsDiff = $referenceDate->diffInMonths($today, false);

        if ($cycle === 'monthly') {
            $periodStart = $referenceDate->copy()->addMonths($monthsDiff);
        } elseif ($cycle === 'quarterly') {
            $quartersDiff = floor($monthsDiff / 3);
            $periodStart = $referenceDate->copy()->addMonths($quartersDiff * 3);
        } else { // yearly
            $yearsDiff = floor($monthsDiff / 12);
            $periodStart = $referenceDate->copy()->addYears($yearsDiff);
        }

        return $periodStart;
    }

    /**
     * Get the billing period end date.
     */
    private function getBillingPeriodEnd($subscription, $periodStart): Carbon
    {
        $cycle = $subscription->billing_cycle;

        if ($cycle === 'monthly') {
            return $periodStart->copy()->addMonth()->subDay();
        } elseif ($cycle === 'quarterly') {
            return $periodStart->copy()->addMonths(3)->subDay();
        } else { // yearly
            return $periodStart->copy()->addYear()->subDay();
        }
    }

    /**
     * Get the due date based on billing cycle.
     */
    private function getDueDate($cycle, $invoiceDate): Carbon
    {
        // Default: due in 15 days for monthly, 30 days for quarterly/yearly
        if ($cycle === 'monthly') {
            return $invoiceDate->copy()->addDays(15);
        } else {
            return $invoiceDate->copy()->addDays(30);
        }
    }
}
