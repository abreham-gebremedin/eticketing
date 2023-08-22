<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Employee1 extends Model
{
     /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'employees1';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'designation', 'working_days',
    ];
}
