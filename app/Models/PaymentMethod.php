<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentMethod extends Model
{
    use HasFactory;
    protected $fillable = [
        'workspace_id',
        'title',
        'admin_id',
    ];

    public function payslips()
    {
        return $this->hasMany(Payslip::class)->where('payslips.workspace_id', session()->get('workspace_id'));
    }
}
