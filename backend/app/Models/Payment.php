<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_id',
        'school_id',
        'transaction_id',
        'amount',
        'payment_method',
        'status',
        'payment_date',
        'reference_number',
        'notes',
        'metadata',
        'processed_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_date' => 'date',
        'metadata' => 'array',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function processor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function generateTransactionId(): string
    {
        $year = now()->year;
        $lastPayment = static::whereYear('created_at', $year)
            ->orderBy('id', 'desc')
            ->first();

        $nextNumber = $lastPayment ? 
            intval(substr($lastPayment->transaction_id, -6)) + 1 : 1;

        return "TXN-{$year}-" . str_pad($nextNumber, 6, '0', STR_PAD_LEFT);
    }

    public function markAsCompleted(): void
    {
        $this->update(['status' => 'completed']);

        // Update invoice
        if ($this->invoice) {
            $this->invoice->paid_amount = ($this->invoice->paid_amount ?? 0) + $this->amount;
            $this->invoice->balance = max(0, ($this->invoice->total_amount ?? 0) - $this->invoice->paid_amount);
            
            if ($this->invoice->balance <= 0) {
                $this->invoice->markAsPaid($this->payment_method);
            } else {
                $this->invoice->status = 'partial';
                $this->invoice->save();
            }
        }
    }
}
