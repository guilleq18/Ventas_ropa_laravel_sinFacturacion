<?php

namespace App\Domain\Core\Models;

use Illuminate\Database\Eloquent\Model;

class AppSetting extends Model
{
    protected $table = 'app_settings';

    public const CREATED_AT = null;
    public const UPDATED_AT = 'updated_at';

    protected $fillable = [
        'key',
        'value_bool',
        'value_int',
        'value_str',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'value_bool' => 'boolean',
            'value_int' => 'integer',
            'updated_at' => 'datetime',
        ];
    }
}
