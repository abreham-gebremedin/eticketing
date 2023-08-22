<?php

namespace App\Http\Controllers;

use App\User;
use Auth;
use Illuminate\Http\Request;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Role;

class ActivityLogController extends Controller
{ 
    
    
    public function index(Request $request)
    {
      
        $role = Role::find(Auth::user()->role_id);
        if($role->hasPermissionTo('fixed_asset-index')){
            $permissions = Role::findByName($role->name)->permissions;
            foreach ($permissions as $permission)
                $all_permission[] = $permission->name;
            if(empty($all_permission))
                $all_permission[] = 'dummy text';

             
             
            return view('activity_log.index', compact('all_permission'));
        }
        else
            return redirect()->back()->with('not_permitted', 'Sorry! You are not allowed to access this module');
    }
    public function log_history(Request $request)
    {
      
        $role = Role::find(Auth::user()->role_id);
        if($role->hasPermissionTo('fixed_asset-index')){
            $permissions = Role::findByName($role->name)->permissions;
            foreach ($permissions as $permission)
                $all_permission[] = $permission->name;
            if(empty($all_permission))
                $all_permission[] = 'dummy text';

             
             
            return view('activity_log.loghistory', compact('all_permission'));
        }
        else
            return redirect()->back()->with('not_permitted', 'Sorry! You are not allowed to access this module');
    }

    public function history_activity_logData(Request $request)
    {
        $q = Activity::where('log_name',"default" );

        if (Auth::user()->role_id > 2 && config('staff_access') == 'own') {
            $q = $q->where('causer_id', Auth::id())->orderBy('created_at', 'desc');
        } else {
            $q = $q->orderBy('created_at', 'desc');
        }
    
        $totalData = $q->count();
        $totalFiltered = $totalData;
    
        if ($request->input('length') != -1) {
            $limit = $request->input('length');
        } else {
            $limit = $totalData;
        }
    
        if (empty($request->input('search.value'))) {
            $q = Activity::where('log_name',"default" );
            if (Auth::user()->role_id > 2 && config('staff_access') == 'own') {
                $q =$q->where('causer_id', Auth::id())
                    ->orderBy('created_at', 'desc')
                    ->limit($limit);
            } else {
                $q = $q->limit($limit)->orderBy('created_at', 'desc');
            }
    
            $activity_log = $q->get();
        } else {
            $search = $request->input('search.value');
            $q = Activity::whereDate('activity_log.created_at', '=', date('Y-m-d', strtotime(str_replace('/', '-', $search))))
                ->orderBy('created_at', 'desc')
                ->limit($limit);
    
            if (Auth::user()->role_id > 2 && config('staff_access') == 'own') {
                $activity_log = $q->select('activity_log.*')
                    ->where('activity_log.causer_id', Auth::id())
                    ->get();
                $totalFiltered = $q->where('activity_log.causer_id', Auth::id())->count();
            } else {
                $activity_log = $q->select('activity_log.*')
                    ->orWhere('subject_type', 'LIKE', "%{$search}%")
                    ->get();
                $totalFiltered = $q->orWhere('activity_log.subject_type', 'LIKE', "%{$search}%")->count();
            }
        }
        $data = array();
        if(!empty($activity_log))
        {
            foreach ($activity_log as $key=>$al)
            {
                $nestedData['id'] = $al->id;
                $nestedData['key'] = $key;
                $nestedData['date'] = date(config('date_format'), strtotime($al->created_at->toDateString()));
                $nestedData['subject_type'] = $al->subject_type;
                 $nestedData['description'] = $al->description;
                $user=User::find($al->causer_id);
                $nestedData['user'] = $user->name;
                 if (count($al->properties)> 0) {
                    $nestedData['options'] = '<div class="btn-group">
                    <button type="button" class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">'.trans("file.action").'
                      <span class="caret"></span>
                      <span class="sr-only">Toggle Dropdown</span>
                    </button>
                    <ul class="dropdown-menu edit-options dropdown-menu-right dropdown-default" user="menu">';
                    if(in_array("fixed_asset-edit", $request['all_permission'])) {
                        $nestedData['options'] .= '<li>
                            <button type="button" data-id="'.$al->id.'" class="open-EditFixedAsset_categoryDialog btn btn-link" data-toggle="modal" data-target="#editModal"><i class="dripicons-document-edit"></i>View</button>
                            </li>';
                    }
                    '</ul>
                    </div>';
                } else {
                    $nestedData['options'] = "";
                }
                
                 
             
                $data[] = $nestedData;
            }
        }
        $json_data = array(
            "draw"            => intval($request->input('draw')),  
            "recordsTotal"    => intval($totalData),  
            "recordsFiltered" => intval($totalFiltered), 
            "data"            => $data   
        );    
        echo json_encode($json_data);
    }


    public function activity_logData(Request $request)
    {
        $q = Activity::where('is_root', 1);

        if (Auth::user()->role_id > 2 && config('staff_access') == 'own') {
            $q =$q-> where('causer_id', Auth::id())->where('is_active', 1)->orderBy('created_at', 'desc');
        } else {
            $q = $q->where('is_active', 1)->orderBy('created_at', 'desc');
        }
    
        $totalData = $q->count();
        $totalFiltered = $totalData;
    
        if ($request->input('length') != -1) {
            $limit = $request->input('length');
        } else {
            $limit = $totalData;
        }
    
        if (empty($request->input('search.value'))) {
            $q = Activity::where('is_root', 1);
            if (Auth::user()->role_id > 2 && config('staff_access') == 'own') {
                $q=$q->where('causer_id', Auth::id())
                ->where('is_root', true)
                    ->where('is_active', 1)
                    ->orderBy('created_at', 'desc')
                    ->limit($limit);
            } else {
                $q = $q->where('is_active', 1)->orderBy('created_at', 'desc')->limit($limit);
            }
    
            $activity_log = $q->get();
        } else {
            $search = $request->input('search.value');
            $q = Activity::whereDate('activity_log.created_at', '=', date('Y-m-d', strtotime(str_replace('/', '-', $search))))
            ->where('is_root', true)
            ->where('is_active', 1)
                 ->orderBy('created_at', 'desc')
                ->limit($limit);
    
            if (Auth::user()->role_id > 2 && config('staff_access') == 'own') {
                $activity_log = $q->select('activity_log.*')
                    ->where('activity_log.causer_id', Auth::id())
                    ->where('is_root', true)
                    ->where('is_active', 1)
                    ->get();
                $totalFiltered = $q->where('activity_log.causer_id', Auth::id())->count();
            } else {
                $activity_log = $q->select('activity_log.*')
                    ->orWhere('subject_type', 'LIKE', "%{$search}%")
                    ->where('is_root', true)
                    ->where('is_active', 1)
                    ->get();
                $totalFiltered = $q->orWhere('activity_log.subject_type', 'LIKE', "%{$search}%")->count();
            }
        }
        $data = array();
        if(!empty($activity_log))
        {
            foreach ($activity_log as $key=>$al)
            {
                $nestedData['id'] = $al->id;
                $nestedData['key'] = $key;
                $nestedData['date'] = date(config('date_format'), strtotime($al->created_at->toDateString()));
                $nestedData['subject_type'] = $al->subject_type;
                 $nestedData['description'] = $al->description;
                $user=User::find($al->causer_id);
                $nestedData['user'] = $user->name;
                // $nestedData['options'] = "Abebe";
                if ($al->is_deleted==1) {
                    # code...
                    if (count($al->properties)> 0) {
                        $nestedData['options'] = '<div class="btn-group">
                        <button type="button" class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">'.trans("file.action").'
                          <span class="caret"></span>
                          <span class="sr-only">Toggle Dropdown</span>
                        </button>
                        <ul class="dropdown-menu delete-options dropdown-menu-right dropdown-default" user="menu">';
            if(in_array("fixed_asset-edit", $request['all_permission'])) {
                $nestedData['options'] .= '<li>
                    <button type="button" data-id="'.$al->id.'" class="open-deleteFixedAsset_categoryDialog btn btn-link" data-toggle="modal" data-target="#deleteModal"><i class="dripicons-document-edit"></i>Delete View</button>
                    </li>';
            }
             '
                    </ul>
                </div>';
                    } else {
                        $nestedData['options'] = "";
                    }
                    
                } else {
                    # code...
                    if (count($al->properties)> 0) {
                        $nestedData['options'] = '<div class="btn-group">
                        <button type="button" class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">'.trans("file.action").'
                          <span class="caret"></span>
                          <span class="sr-only">Toggle Dropdown</span>
                        </button>
                        <ul class="dropdown-menu edit-options dropdown-menu-right dropdown-default" user="menu">';
            if(in_array("fixed_asset-edit", $request['all_permission'])) {
                $nestedData['options'] .= '<li>
                    <button type="button" data-id="'.$al->id.'" class="open-EditFixedAsset_categoryDialog btn btn-link" data-toggle="modal" data-target="#editModal"><i class="dripicons-document-edit"></i>View</button>
                    </li>';
            }
             '
                    </ul>
                </div>';


                    } else {
                        $nestedData['options'] = "";
                    }
                }
                
                 



                    
                
                $data[] = $nestedData;
            }
        }
        $json_data = array(
            "draw"            => intval($request->input('draw')),  
            "recordsTotal"    => intval($totalData),  
            "recordsFiltered" => intval($totalFiltered), 
            "data"            => $data   
        );    
        echo json_encode($json_data);
    }


    public function edit($id)
    {
        $role = Role::firstOrCreate(['id' => Auth::user()->role_id]);
        if ($role->hasPermissionTo('fixed_asset-edit')) {
            $lims_fixed_asset_data = Activity::find($id);
            $lims_fixed_asset_data->date = date('d-m-Y', strtotime($lims_fixed_asset_data->created_at->toDateString()));
            return $lims_fixed_asset_data;
        }
        else
            return redirect()->back()->with('not_permitted', 'Sorry! You are not allowed to access this module');
    }


  
   
    

    

   
}
