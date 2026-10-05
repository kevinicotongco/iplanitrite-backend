<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\InvoicePaymentStatusEnum;
use App\Enums\PaymentMethodEnum;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class InvoicePayment extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'invoice_payments';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $dateFormat = 'Y-m-d H:i:s.u';

    protected $guarded = [];

    protected $casts = [
        'status' => InvoicePaymentStatusEnum::class,
        'payment_method' => PaymentMethodEnum::class,
        'amount' => 'decimal:2',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'invoice_id');
    }

    public function proofDocument(): BelongsTo
    {
        return $this->belongsTo(Document::class, 'proof_document_id');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(InvoicePaymentLog::class, 'invoice_payment_id');
    }
}
