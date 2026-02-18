<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tenant extends Model
{
    use HasFactory;

    protected $fillable = [
        'full_name',
        'email',
        'phone_number',
        'business_name',
        'client_url',
        'status',
        'approved_at',
        'put_on_hold',
        'physical_address',
        'postal_address',
        'approved_by',
        'subscription_plan',
        'payment_amount',
        'payment_method',
        'next_payment_date',
        'last_payment_date',
        'created_at',
        'updated_at',
        'data',
    ];

}
