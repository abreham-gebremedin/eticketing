<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MonthlyPayroll extends Model
{

    protected $table = 'monthlypayroll';

    protected $fillable =[

    "payrollone_id","employee_id","day","basic_salary","transport_allowance","house_allowance","fuel","ot","deduction","position","gross","total_taxable","income_tax","employee_pension","company_pension","total_pension","net_income","total", 
 
    ];

    public function employee()
    {
    	return $this->belongsTo('App\Employee');
    }
  
}
