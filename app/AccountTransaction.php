<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AccountTransaction extends Model
{


    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */

     protected $table = 'account_transactions';

    protected $fillable = [
        'date', 'chartof_accounts_id', 'debit', 'credit','warehouse_id','is_auto_generated','fixed_asset_id'
    ];




    const STATUS_DRAFT = 'draft';
    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';
   // ...

  public function isDraft()
  {
      return $this->status === self::STATUS_DRAFT;
  }
  

  public function isApproved()
  {
      return $this->status === self::STATUS_DRAFT;
  }
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
    
    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);

     }

   
    

    /**
     * Automatically validate the transaction data when creating or updating.
     *
     * @param  array  $options
     * @return bool
     */
    public function save(array $options = [])
    {
        // if (!$this->validate()) {
        //     return false;
        // }

        return parent::save($options);
    }

    /**
     * Ensure that debit and credit are kept in balance.
     *
     * @return bool
     */
 
}
