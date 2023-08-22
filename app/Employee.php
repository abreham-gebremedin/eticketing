<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    protected $fillable =[
        "name", "image", "department_id", "email", "phone_number",
        "user_id", "address", "city", "country", "is_active", "transport_allowance"  , "basic_salary", "house_allowance"  , "fuel_allowance" ,"position", "deduction",

    ];

    public function payroll()
    {
    	return $this->hasMany('App\Payroll');
    }
    
}
