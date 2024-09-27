<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Sanctum\HasApiTokens;

class RestaurantOperationHour extends Model
{
    use HasFactory, HasApiTokens, SoftDeletes, HasUuids;

    protected $primaryKey = 'id';
}
