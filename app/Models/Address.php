<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Address extends Model
{
    use HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'line1',
        'line2',
        'city',
        'state',
        'zip',
        'lat',
        'long',
        'country_id',
    ];

    protected $casts = [
        'lat' => 'decimal:6',
        'long' => 'decimal:6',
    ];

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }
}
