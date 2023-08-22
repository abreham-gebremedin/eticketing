<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class GeneralSetting extends Model
{
    protected $fillable =[

        "site_title","fiscal_year","one_share_value","company_name", "site_logo", "is_rtl", "currency", "currency_position", "staff_access", "date_format", "theme", "developed_by", "invoice_format", "state"
    ];
}
