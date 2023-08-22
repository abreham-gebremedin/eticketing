<?php

namespace App\Http\Controllers;

use App\chartofAccount;
use App\ChartofAccountCategory;
use DB;
use Exception;
use Illuminate\Http\Request;
use Keygen\Keygen;
use Illuminate\Validation\Rule;
class ChartofAccountController extends Controller
{    public function index()
    {
        $lims_account_category_list=ChartofAccountCategory::get();
        $lims_expense_category_all = ChartofAccount::get();
        return view('chart_of_account.index', compact('lims_expense_category_all','lims_account_category_list'));
    }

    public function create()
    {
        //
    }

    public function generateCode()
    {
        $id = Keygen::numeric(4)->generate();
        return $id;
    }

    public function store(Request $request)
    {

        try {
            DB::beginTransaction();
        $this->validate($request, [
            'code' => [
                'max:255',
                    Rule::unique('chartof_accounts')->where(function ($query) {
                    return $query;
                }),
            ]
        ]);


        $this->validate($request, [
            'name' => [
                'max:255',
                    Rule::unique('chartof_accounts')->where(function ($query) {
                    return $query;
                }),
            ]
        ]);

        $data = $request->all();
        ChartofAccount::create($data);

        DB::commit(); 
        return redirect('chart_of_accounts')->with('message', 'Data inserted successfully');


    }  catch (Exception $e) {
        DB::rollBack();
        return redirect()->back()->with('not_permitted', $e->getMessage());
    }
    }

    public function show($id)
    {
        //
    }

    public function edit($id)
    {
        $lims_expense_category_data = ChartofAccount::find($id);
        return $lims_expense_category_data;
    }

    public function update(Request $request, $id)
    {

        try {
            DB::beginTransaction();
        $this->validate($request, [
            'code' => [
                'max:255',
                    Rule::unique('chartof_accounts')->ignore($request->expense_category_id)->where(function ($query) {
                    return $query;
                }),
            ]
        ]);

        $this->validate($request, [
            'name' => [
                'max:255',
                    Rule::unique('chartof_accounts')->where(function ($query) {
                    return $query;
                }),
            ]
        ]);

        $data = $request->all();
       
        $lims_expense_category_data = ChartofAccount::find($data['expense_category_id']);
        if($lims_expense_category_data->fixed_asset_category_id!=null && $data['name'] != $lims_expense_category_data->name){
                 throw new Exception("You can't Edit the name of this chart of account ");
                
         }
        $lims_expense_category_data->update($data);

        DB::commit(); 
        return redirect('chart_of_accounts')->with('message', 'Data updated successfully');


    }  catch (Exception $e) {
        DB::rollBack();
        return redirect()->back()->with('not_permitted', $e->getMessage());
    }
    }

    public function import(Request $request)
    {
        //get file
        $upload=$request->file('file');
        $ext = pathinfo($upload->getClientOriginalName(), PATHINFO_EXTENSION);
        if($ext != 'csv')
            return redirect()->back()->with('not_permitted', 'Please upload a CSV file');
        $filename =  $upload->getClientOriginalName();
        $filePath=$upload->getRealPath();
        //open and read
        $file=fopen($filePath, 'r');
        $header= fgetcsv($file);
        $escapedHeader=[];
        //validate
        foreach ($header as $key => $value) {
            $lheader=strtolower($value);
            $escapedItem=preg_replace('/[^a-z]/', '', $lheader);
            array_push($escapedHeader, $escapedItem);
        }
        //looping through othe columns
        while($columns=fgetcsv($file))
        {
            if($columns[0]=="")
                continue;
            foreach ($columns as $key => $value) {
                $value=preg_replace('/\D/','',$value);
            }
           $data= array_combine($escapedHeader, $columns);
           $expense_category = ChartofAccount::firstOrNew(['code' => $data['code'], 'is_active' => true ]);
           $expense_category->code = $data['code'];
           $expense_category->name = $data['name'];
           $expense_category->save();
        }
        return redirect('chart_of_accounts')->with('message', 'ChartofAccount imported successfully');
    }

    public function deleteBySelection(Request $request)
    {
        $expense_category_id = $request['expense_categoryIdArray'];
        foreach ($expense_category_id as $id) {
            $lims_expense_category_data = ChartofAccount::find($id);
             $lims_expense_category_data->delete();
        }
        return 'chart_of_account  deleted successfully!';
    }

    public function destroy($id)
    {
        $lims_expense_category_data = ChartofAccount::find($id);
        $lims_expense_category_data->delete();
        return redirect('chart_of_accounts')->with('not_permitted', 'Data deleted successfully');
    }
}
