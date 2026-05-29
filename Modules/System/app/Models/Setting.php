<?php

declare(strict_types=1);

namespace Modules\System\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use BelongsToTenant, HasUlids;

    protected $fillable = ['tenant_id', 'key', 'value'];

    /**
     * Gets a setting value by key, returning a default if not found.
     * This is now scoped to the current active tenant automatically.
     */
    public static function getValue(string $key, mixed $default = null): mixed
    {
        $setting = self::where('key', $key)->first();

        return $setting ? $setting->value : $default;
    }

    /**
     * Sets a setting value by key for the current tenant.
     */
    public static function setValue(string $key, mixed $value): void
    {
        self::updateOrCreate(
            ['key' => $key],
            ['value' => $value]
        );
    }
}
