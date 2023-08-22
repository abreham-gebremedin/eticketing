<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AccountTransactionAdjustment extends Model
{

    protected $table = 'account_transactions_adjusment';

    protected $fillable = [
         'is_adjustment','user_id','warehouse_id','reference_no','reason',"created_at"
    ];

    /**
     * The rules for validating the transaction data.
     *
     * @var array
     */
  

    /**
     * The account associated with the transaction.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */

     public function warehouse()
     {
         return $this->belongsTo('App\Warehouse');
     }
 
  
}
