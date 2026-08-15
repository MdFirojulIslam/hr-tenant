<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Brands live in artisan_admin DB (same MySQL server as hr_db).
 *
 * Uses Laravel connection name "artisan_admin" — not the default hr_db connection.
 * SoftDeletes intentionally omitted: some live brands tables have no deleted_at.
 */
class Brand extends Model
{
    protected $connection = 'artisan_admin';

    protected $table = 'brands';

    protected $fillable = [
        'name',
        'thumbnail',
    ];
}
