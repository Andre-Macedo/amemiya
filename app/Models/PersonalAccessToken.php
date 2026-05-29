<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Laravel\Sanctum\PersonalAccessToken as SanctumPersonalAccessToken;

class PersonalAccessToken extends SanctumPersonalAccessToken
{
    use HasUlids;

    /**
     * Indica que o ID não é auto-incremental (é uma string ULID).
     */
    public $incrementing = false;

    /**
     * O tipo de chave primária.
     */
    protected $keyType = 'string';
}
