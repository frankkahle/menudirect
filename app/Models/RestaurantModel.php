<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

abstract class RestaurantModel extends Model
{
    /**
     * All restaurant-domain models use the menudirect connection.
     *
     * During test phase this is the cross-host portal-host DB; at cutover
     * the MENUDIRECT_DB_HOST env var flips to 127.0.0.1 and Eloquent
     * transparently switches to local without any model code change.
     */
    protected $connection = "menudirect";
}
