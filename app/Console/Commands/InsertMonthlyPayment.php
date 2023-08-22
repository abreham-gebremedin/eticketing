<?php

namespace App\Console\Commands;

use App\AccountTransaction;
use App\ChartofAccount;
use Illuminate\Console\Command;

class InsertMonthlyPayment extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pay:auto';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add rent expense';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $transaction = new AccountTransaction;
        $transaction->reference_no = "Scheduler";
        $transaction->date = date("Y-m-d H:i:s");
        $transaction->user_id = 1;
        $transaction->debit = 500;
        $transaction->warehouse_id = 1;
        $transaction->credit = 0;
        $accountType = ChartofAccount::where('name', 'Cost of Goods Sold')->first();
        $transaction->chartof_accounts_id = $accountType->id;
        $transaction->save();
        return 0; // or any other integer value

     }
}
