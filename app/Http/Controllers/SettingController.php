<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Customer;
use App\CustomerGroup;
use App\Warehouse;
use App\Biller;
use App\Account;
use App\Currency;
use App\PosSetting;
use App\GeneralSetting;
use App\HrmSetting;
use App\RewardPointSetting;
use DB;
use Illuminate\Support\Facades\Artisan;
use Spatie\Backup\Tasks\Backup\BackupJob;
use ZipArchive;
use Twilio\Rest\Client;
use Clickatell\Rest;
use Clickatell\ClickatellException;

 
class SettingController extends Controller
{
    public function emptyDatabase()
    {
        if(!env('USER_VERIFIED'))
            return redirect()->back()->with('not_permitted', 'This feature is disable for demo!');
        $tables = DB::select('SHOW TABLES');
        $str = 'Tables_in_' . env('DB_DATABASE');
        foreach ($tables as $table) { 
            if($table->$str != 'chartof_account_categories' &&$table->$str != 'accounts' &&$table->$str != 'chartof_accounts' && $table->$str != 'general_settings' && $table->$str != 'hrm_settings' && $table->$str != 'languages' && $table->$str != 'migrations' && $table->$str != 'password_resets' && $table->$str != 'permissions' && $table->$str != 'pos_setting' && $table->$str != 'roles' && $table->$str != 'role_has_permissions' && $table->$str != 'users' && $table->$str != 'currencies' && $table->$str != 'reward_point_settings') {
                DB::table($table->$str)->truncate();    
            }
        }
        return redirect()->back()->with('message', 'Database cleared successfully');
    }
    public function generalSetting()
    {
        $lims_general_setting_data = GeneralSetting::latest()->first();
        $lims_account_list = Account::where('is_active', true)->get();
        $lims_currency_list = Currency::get();
        $zones_array = array();
        $timestamp = time();
        foreach(timezone_identifiers_list() as $key => $zone) {
            date_default_timezone_set($zone);
            $zones_array[$key]['zone'] = $zone;
            $zones_array[$key]['diff_from_GMT'] = 'UTC/GMT ' . date('P', $timestamp);
        }
        return view('setting.general_setting', compact('lims_general_setting_data', 'lims_account_list', 'zones_array', 'lims_currency_list'));
    }

    public function generalSettingStore(Request $request)
    {
        if(!env('USER_VERIFIED'))
            return redirect()->back()->with('not_permitted', 'This feature is disable for demo!');

        $this->validate($request, [
            'site_logo' => 'image|mimes:jpg,jpeg,png,gif|max:100000',
        ]);

        $data = $request->except('site_logo');
        //return $data;
        //writting timezone info in .env file
        $path = '.env';
        $searchArray = array('APP_TIMEZONE='.env('APP_TIMEZONE'));
        $replaceArray = array('APP_TIMEZONE='.$data['timezone']);

        file_put_contents($path, str_replace($searchArray, $replaceArray, file_get_contents($path)));
        if(isset($data['is_rtl']))
            $data['is_rtl'] = true;
        else
            $data['is_rtl'] = false;
            

        $general_setting = GeneralSetting::latest()->first();
        $general_setting->id = 1;
        $general_setting->site_title = $data['site_title'];
        $general_setting->company_name = $data['company_name'];
        $general_setting->is_rtl = $data['is_rtl'];
        $general_setting->currency = $data['currency'];
        $general_setting->currency_position = $data['currency_position'];
        $general_setting->staff_access = $data['staff_access'];
        $general_setting->date_format = $data['date_format'];
        $general_setting->developed_by = $data['developed_by'];
        $general_setting->invoice_format = $data['invoice_format'];
        $general_setting->state = $data['state'];
        $general_setting->one_share_value = $data['one_share_value'];
        $logo = $request->site_logo;
        if ($logo) {
            $ext = pathinfo($logo->getClientOriginalName(), PATHINFO_EXTENSION);
            $logoName = date("Ymdhis") . '.' . $ext;
            $logo->move('public/logo', $logoName);
            $general_setting->site_logo = $logoName;
        }
        $general_setting->save();
        return redirect()->back()->with('message', 'Data updated successfully');
    }

    public function rewardPointSetting()
    {
        $lims_reward_point_setting_data = RewardPointSetting::latest()->first();
        return view('setting.reward_point_setting', compact('lims_reward_point_setting_data'));               
    }

    public function rewardPointSettingStore(Request $request)
    {
        $data = $request->all();
        if(isset($data['is_active']))
            $data['is_active'] = true;
        else
            $data['is_active'] = false;
        RewardPointSetting::latest()->first()->update($data);
        return redirect()->back()->with('message', 'Reward point setting updated successfully');      
    }

    public function backup()
    {
        if(!env('USER_VERIFIED'))
            return redirect()->back()->with('not_permitted', 'This feature is disable for demo!');

        // // Database configuration
        // $host = env('DB_HOST');
        // $username = env('DB_USERNAME');
        // $password = env('DB_PASSWORD');
        // $database_name = env('DB_DATABASE');

        // // Get connection object and set the charset
        // $conn = mysqli_connect($host, $username, $password, $database_name);
        // $conn->set_charset("utf8");


        // // Get All Table Names From the Database
        // $tables = array();
        // $sql = "SHOW TABLES";
        // $result = mysqli_query($conn, $sql);

        // while ($row = mysqli_fetch_row($result)) {
        //     $tables[] = $row[0];
        // }

        // $sqlScript = "";
        // foreach ($tables as $table) {
            
        //     // Prepare SQLscript for creating table structure
        //     $query = "SHOW CREATE TABLE $table";
        //     $result = mysqli_query($conn, $query);
        //     $row = mysqli_fetch_row($result);
            
        //     $sqlScript .= "\n\n" . $row[1] . ";\n\n";
            
            
        //     $query = "SELECT * FROM $table";
        //     $result = mysqli_query($conn, $query);
            
        //     $columnCount = mysqli_num_fields($result);
            
        //     // Prepare SQLscript for dumping data for each table
        //     for ($i = 0; $i < $columnCount; $i ++) {
        //         while ($row = mysqli_fetch_row($result)) {
        //             $sqlScript .= "INSERT INTO $table VALUES(";
        //             for ($j = 0; $j < $columnCount; $j ++) {
        //                 $row[$j] = $row[$j];
                        
        //                 if (isset($row[$j])) {
        //                     $sqlScript .= '"' . $row[$j] . '"';
        //                 } else {
        //                     $sqlScript .= '""';
        //                 }
        //                 if ($j < ($columnCount - 1)) {
        //                     $sqlScript .= ',';
        //                 }
        //             }
        //             $sqlScript .= ");\n";
        //         }
        //     }
            
        //     $sqlScript .= "\n"; 
        // }

        // if(!empty($sqlScript))
        // {
        //     // Save the SQL script to a backup file
        //     $backup_file_name = public_path().'/'.$database_name . '_backup_' . time() . '.sql';
        //     //return $backup_file_name;
        //     $fileHandler = fopen($backup_file_name, 'w+');
        //     $number_of_lines = fwrite($fileHandler, $sqlScript);
        //     fclose($fileHandler);
        //     $date=date("Y-m-d H:i:s");
 

        //     $zip = new ZipArchive();
        //     $zipFileName = $database_name . '_backup_'.date("Y-m-d"). '.zip';
        //     $zip->open(public_path() . '/' . $zipFileName, ZipArchive::CREATE);
        //     $zip->addFile($backup_file_name, $database_name . '_backup_' .date("Y-m-d") . '.sql');
        //     $zip->close();

        //     // Download the SQL backup file to the browser
        //     /*header('Content-Description: File Transfer');
        //     header('Content-Type: application/octet-stream');
        //     header('Content-Disposition: attachment; filename=' . basename($backup_file_name));
        //     header('Content-Transfer-Encoding: binary');
        //     header('Expires: 0');
        //     header('Cache-Control: must-revalidate');
        //     header('Pragma: public');
        //     header('Content-Length: ' . filesize($backup_file_name));
        //     ob_clean();
        //     flush();
        //     readfile($backup_file_name);
        //     exec('rm ' . $backup_file_name); */
        // }
        // return redirect('public/' . $zipFileName);


// // Set the name for the backup file
// $backupFileName = 'invosale_' . date('Y-m-d_H-i-s') . '.sql';

// // Set the path where the backup file will be stored
// $backupPath = storage_path('app/backups/');

// // Create the backup command
// $command = 'mysqldump -u ' . env('DB_USERNAME') . ' -p' . env('DB_PASSWORD') . ' ' . env('DB_DATABASE') . ' > ' . $backupPath . $backupFileName;

// // Execute the backup command
// exec($command, $output, $returnValue);

// // Check if the backup file was created
// if ($returnValue == 0 && file_exists($backupPath . $backupFileName)) {
//     // Backup completed successfully
//     return 'Database backup completed successfully!';
// } else {
//     // Backup failed
//     return 'Database backup failed!';
// }



    }

    public function changeTheme($theme)
    {
        $lims_general_setting_data = GeneralSetting::latest()->first();
        $lims_general_setting_data->theme = $theme;
        $lims_general_setting_data->save();
    }

    public function mailSetting()
    {  
        return view('setting.mail_setting');
    }

    public function mailSettingStore(Request $request)
    {
        if(!env('USER_VERIFIED'))
            return redirect()->back()->with('not_permitted', 'This feature is disable for demo!');

        $data = $request->all();
        //writting mail info in .env file
        $path = '.env';
        $searchArray = array('MAIL_HOST="'.env('MAIL_HOST').'"', 'MAIL_PORT='.env('MAIL_PORT'), 'MAIL_FROM_ADDRESS="'.env('MAIL_FROM_ADDRESS').'"', 'MAIL_FROM_NAME="'.env('MAIL_FROM_NAME').'"', 'MAIL_USERNAME="'.env('MAIL_USERNAME').'"', 'MAIL_PASSWORD="'.env('MAIL_PASSWORD').'"', 'MAIL_ENCRYPTION="'.env('MAIL_ENCRYPTION').'"');
        //return $searchArray;

        $replaceArray = array('MAIL_HOST="'.$data['mail_host'].'"', 'MAIL_PORT='.$data['port'], 'MAIL_FROM_ADDRESS="'.$data['mail_address'].'"', 'MAIL_FROM_NAME="'.$data['mail_name'].'"', 'MAIL_USERNAME="'.$data['mail_address'].'"', 'MAIL_PASSWORD="'.$data['password'].'"', 'MAIL_ENCRYPTION="'.$data['encryption'].'"');
        
        file_put_contents($path, str_replace($searchArray, $replaceArray, file_get_contents($path)));

        return redirect()->back()->with('message', 'Data updated successfully');
    }

    public function smsSetting()
    {
        return view('setting.sms_setting');
    }

    public function smsSettingStore(Request $request)
    {
        if(!env('USER_VERIFIED'))
            return redirect()->back()->with('not_permitted', 'This feature is disable for demo!');
        
        $data = $request->all();
        //writting bulksms info in .env file
        $path = '.env';
        if($data['gateway'] == 'twilio'){
            $searchArray = array('SMS_GATEWAY='.env('SMS_GATEWAY'), 'ACCOUNT_SID='.env('ACCOUNT_SID'), 'AUTH_TOKEN='.env('AUTH_TOKEN'), 'Twilio_Number='.env('Twilio_Number') );

            $replaceArray = array('SMS_GATEWAY='.$data['gateway'], 'ACCOUNT_SID='.$data['account_sid'], 'AUTH_TOKEN='.$data['auth_token'], 'Twilio_Number='.$data['twilio_number'] );
        }
        else{
            $searchArray = array( 'SMS_GATEWAY='.env('SMS_GATEWAY'), 'CLICKATELL_API_KEY='.env('CLICKATELL_API_KEY') );
            $replaceArray = array( 'SMS_GATEWAY='.$data['gateway'], 'CLICKATELL_API_KEY='.$data['api_key'] );
        }

        file_put_contents($path, str_replace($searchArray, $replaceArray, file_get_contents($path)));
        return redirect()->back()->with('message', 'Data updated successfully');
    }

    public function createSms()
    {
        $lims_customer_list = Customer::where('is_active', true)->get();
        return view('setting.create_sms', compact('lims_customer_list'));
    }

    public function sendSms(Request $request)
    {
        $data = $request->all();
        $numbers = explode(",", $data['mobile']);

        if( env('SMS_GATEWAY') == 'twilio') {
            $account_sid = env('ACCOUNT_SID');
            $auth_token = env('AUTH_TOKEN');
            $twilio_phone_number = env('Twilio_Number');
            try{
                $client = new Client($account_sid, $auth_token);
                foreach ($numbers as $number) {
                    $client->messages->create(
                        $number,
                        array(
                            "from" => $twilio_phone_number,
                            "body" => $data['message']
                        )
                    );
                }
            }
            catch(\Exception $e){
                //return $e;
                return redirect()->back()->with('not_permitted', 'Please setup your <a href="sms_setting">SMS Setting</a> to send SMS.');
            }
            $message = "SMS sent successfully";
        }
        elseif( env('SMS_GATEWAY') == 'clickatell') {
            try {
                $clickatell = new \Clickatell\Rest(env('CLICKATELL_API_KEY'));
                foreach ($numbers as $number) {
                    $result = $clickatell->sendMessage(['to' => [$number], 'content' => $data['message']]);
                }
            } 
            catch (ClickatellException $e) {
                return redirect()->back()->with('not_permitted', 'Please setup your <a href="sms_setting">SMS Setting</a> to send SMS.');
            }
            $message = "SMS sent successfully";
        }
        else
            return redirect()->back()->with('not_permitted', 'Please setup your <a href="sms_setting">SMS Setting</a> to send SMS.');    
        return redirect()->back()->with('message', $message);
    }
    
    public function hrmSetting()
    {
        $lims_hrm_setting_data = HrmSetting::latest()->first();
        return view('setting.hrm_setting', compact('lims_hrm_setting_data'));
    }

    public function hrmSettingStore(Request $request)
    {
        $data = $request->all();
        $lims_hrm_setting_data = HrmSetting::firstOrNew(['id' => 1]);
        $lims_hrm_setting_data->checkin = $data['checkin'];
        $lims_hrm_setting_data->checkout = $data['checkout'];
        $lims_hrm_setting_data->save();
        return redirect()->back()->with('message', 'Data updated successfully');

    }
    public function posSetting()
    {
    	$lims_customer_list = Customer::where('is_active', true)->get();
        $lims_warehouse_list = Warehouse::where('is_active', true)->get();
        $lims_biller_list = Biller::where('is_active', true)->get();
        $lims_pos_setting_data = PosSetting::latest()->first();
        
    	return view('setting.pos_setting', compact('lims_customer_list', 'lims_warehouse_list', 'lims_biller_list', 'lims_pos_setting_data'));
    }

    public function posSettingStore(Request $request)
    {
        if(!env('USER_VERIFIED'))
            return redirect()->back()->with('not_permitted', 'This feature is disable for demo!');

    	$data = $request->all();
        //writting paypal info in .env file
        $path = '.env';
        $searchArray = array('PAYPAL_LIVE_API_USERNAME='.env('PAYPAL_LIVE_API_USERNAME'), 'PAYPAL_LIVE_API_PASSWORD='.env('PAYPAL_LIVE_API_PASSWORD'), 'PAYPAL_LIVE_API_SECRET='.env('PAYPAL_LIVE_API_SECRET') );

        $replaceArray = array('PAYPAL_LIVE_API_USERNAME='.$data['paypal_username'], 'PAYPAL_LIVE_API_PASSWORD='.$data['paypal_password'], 'PAYPAL_LIVE_API_SECRET='.$data['paypal_signature'] );

        file_put_contents($path, str_replace($searchArray, $replaceArray, file_get_contents($path)));

    	$pos_setting = PosSetting::firstOrNew(['id' => 1]);
    	$pos_setting->id = 1;
    	$pos_setting->customer_id = $data['customer_id'];
    	$pos_setting->warehouse_id = $data['warehouse_id'];
    	$pos_setting->biller_id = $data['biller_id'];
    	$pos_setting->product_number = $data['product_number'];
    	$pos_setting->stripe_public_key = $data['stripe_public_key'];
    	$pos_setting->stripe_secret_key = $data['stripe_secret_key'];
        if(!isset($data['keybord_active']))
            $pos_setting->keybord_active = false;
        else
            $pos_setting->keybord_active = true;
    	$pos_setting->save();
    	return redirect()->back()->with('message', 'POS setting updated successfully');
    }
}