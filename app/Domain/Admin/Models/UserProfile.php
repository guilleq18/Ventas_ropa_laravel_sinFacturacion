<?php

namespace App\Domain\Admin\Models;

use App\Domain\Core\Models\Sucursal;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserProfile extends Model
{
    protected $table = 'user_profiles';

    public const CREATED_AT = null;
    public const UPDATED_AT = 'updated_at';

    protected $fillable = [
        'user_id',
        'sucursal_id',
    ];

    protected function casts(): array
    {
        return [
            'updated_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function sucursal(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class, 'sucursal_id');
    }
}
