<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Allowance extends Model
{
    use HasFactory;
    protected $fillable = [
        'workspace_id',
        'title',
        'amount',
        'admin_id',

    ];

    public function payslips()
    {
        return $this->belongsToMany(Payslip::class)->where('payslips.workspace_id', session()->get('workspace_id'));
    }
}
