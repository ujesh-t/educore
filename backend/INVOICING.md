# Invoice Management System

## Overview
The Invoice Management System allows super admins to create, track, and manage invoices for school subscriptions. Invoices can be generated automatically or created manually.

## Features

### 1. Automatic Invoice Generation
Generate invoices for all active subscriptions based on their billing cycle:
- **Monthly** - Invoices generated every month
- **Quarterly** - Invoices generated every 3 months
- **Yearly** - Invoices generated once a year

### 2. Manual Invoice Creation
Create invoices manually for:
- Subscription renewals
- One-time charges
- Credit notes
- Debit notes

### 3. Payment Tracking
Record payments against invoices with multiple payment methods:
- Cash
- Card
- Online (UPI, Net Banking)
- Bank Transfer
- Cheque

### 4. Invoice Status
- **Pending** - Invoice created but not paid
- **Paid** - Fully paid
- **Partial** - Partially paid
- **Overdue** - Past due date
- **Cancelled** - Cancelled invoice

## Usage

### Command Line (Artisan)

**Generate invoices for all active subscriptions:**
```bash
php artisan invoices:generate
```

**Generate invoices for specific billing cycle:**
```bash
php artisan invoices:generate --cycle=monthly
php artisan invoices:generate --cycle=quarterly
php artisan invoices:generate --cycle=yearly
```

**Generate for specific school:**
```bash
php artisan invoices:generate --school=1
```

**Force generate (even if invoice exists):**
```bash
php artisan invoices:generate --force
```

### Super Admin Dashboard

1. **View All Invoices**: Navigate to `/super-admin/invoices`
2. **Generate Invoices**: Click "Generate Invoices" button
3. **Create Manual Invoice**: Click "Create Invoice" button
4. **Record Payment**: Click "Payment" on any unpaid invoice
5. **View Details**: Click "View" to see full invoice details

## API Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/super-admin/invoices` | List all invoices |
| GET | `/api/super-admin/invoices/stats` | Get invoice statistics |
| GET | `/api/super-admin/invoices/{id}` | Get invoice details |
| POST | `/api/super-admin/invoices` | Create new invoice |
| PUT | `/api/super-admin/invoices/{id}` | Update invoice |
| POST | `/api/super-admin/invoices/{id}/record-payment` | Record payment |
| POST | `/api/super-admin/invoices/{id}/cancel` | Cancel invoice |

## Database Schema

### Invoices Table
- `invoice_number` - Unique identifier (e.g., INV-2026-000001)
- `school_id` - Reference to school
- `subscription_id` - Reference to subscription
- `status` - pending/paid/overdue/cancelled
- `type` - subscription/one_time/credit/debit
- `billing_cycle` - monthly/quarterly/yearly
- `amount` - Base amount
- `tax_amount` - Tax
- `discount_amount` - Discount
- `total_amount` - Final amount
- `paid_amount` - Amount paid
- `balance` - Remaining balance
- `invoice_date` - Invoice creation date
- `due_date` - Payment due date
- `billing_period_start/end` - Service period

### Payments Table
- `transaction_id` - Unique identifier (e.g., TXN-2026-000001)
- `invoice_id` - Reference to invoice
- `amount` - Payment amount
- `payment_method` - cash/card/online/bank_transfer/cheque
- `payment_date` - Date of payment
- `reference_number` - Cheque #, UTR, etc.

## Scheduled Task (Optional)

Set up automatic monthly invoice generation by adding to `app/Console/Kernel.php`:

```php
protected function schedule(Schedule $schedule)
{
    // Generate invoices on the 1st of every month
    $schedule->command('invoices:generate')
             ->monthlyOn(1, '00:00');
    
    // Mark overdue invoices daily
    $schedule->command('invoices:mark-overdue')
             ->daily();
}
```

Then add to your crontab:
```bash
* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
```

## Invoice Number Format
- Format: `INV-YYYY-NNNNNN`
- Example: `INV-2026-000001`
- Resets yearly

## Transaction ID Format
- Format: `TXN-YYYY-NNNNNN`
- Example: `TXN-2026-000001`
- Resets yearly

## Currency
All invoices use **INR (₹)** as the default currency.
