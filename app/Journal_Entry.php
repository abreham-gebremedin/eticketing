<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Journal_Entry extends Model
{

    protected $table = 'journal_entries';

    protected $fillable = [
         'account_transaction_id', 'lims_AccountTransactionAdjustment_id','chartof_accounts_id', 'debit', 'credit', 'cash_register_id','account_id',
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

  
 
    
    public function chartofAccounts()
    {
        return $this->belongsTo(ChartofAccount::class);
        
    }

    
}