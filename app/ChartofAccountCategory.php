<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChartofAccountCategory extends Model
{
    protected $fillable = ['name'];

    public function chartofAccounts()
    {
        return $this->hasMany(ChartofAccount::class);
    }
}
