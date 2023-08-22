@extends('layout.main') @section('content')
@if(session()->has('not_permitted'))
  <div class="alert alert-danger alert-dismissible text-center"><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>{{ session()->get('not_permitted') }}</div>
@endif
<section class="forms">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header d-flex align-items-center">
                        <h4>Payroll   for  Month of {{ \Carbon\Carbon::now()->subDays(15)->format('F') }}   </h4>
                    </div>
                    {!! Form::open(['route' => 'payroll.create_payroll', 'method' => 'get']) !!}
                    <div class="row ml-1 mt-2">

                    <div class="col-md-3">

                                        <div class="form-group">
                                            <label>{{trans('file.Warehouse')}} *</label>
                                            <select required  id="warehouse_id" name="warehouse_id" class="selectpicker form-control" data-live-search="true" title="Select warehouse...">
                                            @if($warehouse_id==0)
                                                <option selected value="0">All Warehouse</option>
                                                 @else
                                                <option value="0">All Warehouse</option>
                                                @endif
                                            
                                            @foreach($lims_warehouse_list as $warehouse)
                                                @if($warehouse_id== $warehouse->id)
                                                <option selected value="{{$warehouse->id}}">{{$warehouse->name}}</option>
                                                @endif


                                                <option value="{{$warehouse->id}}">{{$warehouse->name}}</option>
                                                @endforeach

                                               

                                            </select>
                                        </div>
                    </div>

                    <div class="col-md-2 mt-3">
                    <div class="form-group">
                        <button class="btn btn-primary" id="filter-btn" type="submit">{{trans('file.submit')}}</button>
                    </div>
                    </div>
                    </div>

                
            {!! Form::close() !!}
                    <div class="card-body">
                        
                        <p class="italic"><small>{{trans('file.The field labels marked with * are required input fields')}}.</small></p>
                        {!! Form::open(['route' => 'payroll.storepayroll', 'method' => 'post', 'files' => true, 'class' => 'payment-form']) !!}
                        <div class="row">
                            <div class="col-md-12">                                
                                <div class="row">
                                    <div class="col-md-12">
                                        <h5>Create Payroll *</h5>
                                        <div class="table-responsive mt-3">
                                            <table id="myTable" class="table table-hover order-list">
                                                <thead>
                                                    <tr>
                                                        <th>{{trans('file.name')}}</th>
                                                        <th>Basic Salary</th>
                                                         <th>{{trans('file.Quantity')}}</th>
                                                         <th>Transpotation Allowance</th>
                                                         <th>House Allowance</th>
                                                         <th>Fuel</th>
                                                         <th>Over Time</th>
                                                         <th>Deduction</th>
                                                         <th>Position</th> 
                                                         <th>Gross</th> 
                                                         <th>Taxable Earning</th> 
                                                          <th>Tax</th> 
                                                        <th>Pesion from  Employee</th> 
                                                        <th>Pesion from  Company</th> 

                                                         <th>Total pension</th> 
                                                          <th>Net Income</th> 
                                                          <th>Total</th>

                                                        <th>Choose</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($lims_employee_data as $lims_employee)
                                                    <tr> 
                                                        <td>{{$lims_employee->name}}</td>
                                                        <td  class="basic-salary" >{{$lims_employee->basic_salary}}</td>
                                                        <td>
                                                             <input type="number" class="form-control qty"   value="30" required step="any" max="30"  style="width:100px;"/>
                                                        </td>
                                                        <td class="transport-allowance">
                                                        {{$lims_employee->transport_allowance}}
                                                        </td>
                                                        <td class="house-allowance">
                                                        {{$lims_employee->house_allowance}}
                                                        </td>
                                                        <td class="fuel-allowance">
                                                        {{$lims_employee->fuel_allowance}}
                                                        </td>
                                                        <td>
                                                             <input  type="number" class="form-control ot"   value="0" required step="any" style="width:100px;"/>
                                                        </td>
                                                        <td>
                                                             <input  type="number" class="form-control deduction"   value="{{$lims_employee->deduction}}" required step="any" style="width:100px;"/>
                                                        </td>
                                                        <td class="position">
                                                        {{$lims_employee->position}}
                                                        </td>
                                                        <td class="gross">0</td>
                                                        
                                                        <td class="total-taxable">0</td>                                                    
                                                                                                                 
                                                        <td class="income_tax">0</td>
                                                        
                                                        <td class="epension">0</td>
 
                                                        <td class="cpension">0</td>
                                                        <td class="total_pension">0</td>
                                                        <td class="net_income">0</td>
                                                         <td class="total_salary">0</td>


                                                        <td><input checked type="checkbox" class="is-return" name="is_pay[]" value="{{$lims_employee->id}}"></td>
                                                         <input type="hidden" name="employee_id[]" class="employee-id" value="{{$lims_employee->id}}"/>
                                                        <input type="hidden" class="day-value" name=day[] value="{{ number_format((float)$lims_employee->basic_salary, 2, '.', '') }}">
                                                        <input type="hidden" class="basic-salary-value"name="basic_salary[]" value="14555"/>
                                                        <input type="hidden" class="transport-allowance-value" name="transport_allowance[]" value="47855"/>
                                                        <input type="hidden" class="house-allowance-value" name="house_allowance[]" value="jgjgjg"/>
                                                        <input type="hidden" class="fuel-value" name="fuel[]" value="hghghgh" />
                                                        <input type="hidden" class="ot-value" name="ot[]" value="hghghgh" />
                                                        <input type="hidden" class="deduction-value" name="deduction[]" value="hhghgh"/>
                                                         <input type="hidden" class="position-value" name="position[]" value="455" />
                                                         <input type="hidden" class="gross-value" name="gross[]" value="455" />
                                                        <input type="hidden" class="total-taxable-value" name="total_taxable[]"  value="54545"/> 
                                                        <input type="hidden" class="income-tax-value" name="income_tax[]"  value="54545"/> 
                                                        <input type="hidden" class="employee-pension-value" name="employee_pension[]"  value="54545"/> 
                                                        <input type="hidden" class="company-pension-value" name="company_pension[]"  value="54545"/> 
                                                        <input type="hidden" class="total-pension-value" name="total_pension[]"  value="54545"/> 
                                                        <input type="hidden" class="net-income-value" name="net_income[]"  value="54545"/> 
                                                        <input type="hidden" class="total-value" name="total[]"  value="54545"/> 
                                                    </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <input type="hidden" name="total_deduction" />
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <input type="hidden" name="total_income_tax" />
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <input type="hidden" name="total_net" />
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <input type="hidden" name="add_total_pension" />
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <input type="hidden" name="item" />
                                            <input type="hidden" name="order_tax" />
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-group">
                                        <input type="hidden" name="grand_total" />
                                        <input type="hidden" name="warehouse_id" value="{{$warehouse_id}}" />
                                        </div>
                                    </div>
                                </div>
                                <div class="row mt-3">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>{{trans('file.Account')}}</label>
                                            <select class="form-control" name="account_id">
                                                @foreach($lims_account_list as $account)
                                                <option value="{{$account->id}}">{{$account->name}}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    
                                	<div class="col-md-4">
                                        <div class="form-group">
                                             
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                             
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <input type="submit" value="{{trans('file.submit')}}" class="btn btn-primary" id="submit-button" disabled>
                                </div>
                            </div>
                        </div>
                        {!! Form::close() !!}
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="container-fluid">
        <table class="table table-bordered table-condensed totals">
            
            <td><strong>Net Total</strong>
                <span class="pull-right" id="total_net">0.00</span>
            </td>
            <td><strong>Total pension</strong>
                <span class="pull-right" id="add_total_pension">0.00</span>
            </td>

            <td><strong>Total Tax</strong>
                <span class="pull-right" id="total_income_tax">0.00</span>
            </td>

            <td><strong>Total Deduction</strong>
                <span class="pull-right" id="total_deduction">0.00</span>
            </td>
            <td><strong>{{trans('file.grand total')}}</strong>
                <span class="pull-right" id="grand_total">0.00</span>
            </td>
        </table>
    </div>

</section>

@endsection

@push('scripts')
<script type="text/javascript">
    $("ul#hrm").siblings('a').attr('aria-expanded','true');
    $("ul#hrm").addClass("show");
    $("ul#hrm #create_payroll-menu #create_payroll-link").addClass("active");

var product_code = [];
var product_cost = [];
var product_discount = [];
 
// temporary array
var temp_unit_name = [];
var temp_unit_operator = [];
var temp_unit_operation_value = [];

var rowindex;
var row_product_cost;
calculateTotal();

$('.selectpicker').selectpicker({
    style: 'btn-link',
});

$('[data-toggle="tooltip"]').tooltip();

//choosing the returned product
$("#myTable").on("change", ".is-return", function () {
    calculateTotal();
});

 

//Change quantity
$("#myTable").on('input', '.qty', function() {
    rowindex = $(this).closest('tr').index();

    if($(this).val() == '') {
      $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ') .qty').val(0);
      alert("Number of Date can't be Empty");
     }
    if($(this).val() < 0 && $(this).val() != '') {
      $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ') .qty').val(0);
      alert("Number of Date can't be less than 0");
     }

     if($(this).val() > 30 ) {
      $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ') .qty').val(30);
      alert("Number of date can't be greater than 30");
     }
    calculateTotal();
});


//Change ot
$("#myTable").on('input', '.ot', function() {
    rowindex = $(this).closest('tr').index();

    if($(this).val() == '') {
      $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ') .ot').val(0);
      alert("The value of Over Time can't be Empty");
     }
    if($(this).val() < 0 && $(this).val() != '') {
      $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ') .ot').val(0);
      alert("The value of Over Time can't beless than 0");
     }

      
    calculateTotal();
});

//Change ot
$("#myTable").on('input', '.deduction', function() {
    rowindex = $(this).closest('tr').index();

    if($(this).val() == '') {
      $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ') .deduction').val(0);
      alert("Deduction amount can't be Empty");
     }
    if($(this).val() < 0 && $(this).val() != '') {
      $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ') .deduction').val(0);
      alert("Deduction amount can't be less than 0");
     }

      
    calculateTotal();
});


$('select[name="order_tax_rate"]').on("change", function() {
    calculateGrandTotal();
});

function calculateTotal() {
    var total_qty = 0;
    var total_deduction = 0;
    var total_net = 0;
    var total = 0;
    var item = 0;
    var add_total_pension=0;
    var checked = false;
    var total_income_tax=0;
    var submitButton = $('#submit-button');
    $(".is-return").each(function(i) {
        if ($(this).is(":checked")) {
            checked = true;



 

            var actual_qty = $('table.order-list tbody tr:nth-child(' + (i + 1) + ') .actual-qty').val();
            var qty = $('table.order-list tbody tr:nth-child(' + (i + 1) + ') .qty').val();

            var basic_salary1 = parseFloat($('table.order-list tbody tr:nth-child(' + (i + 1) + ') .basic-salary').text());
            var basic_salary =(basic_salary1/30) * qty;
            var house_allowance = parseFloat($('table.order-list tbody tr:nth-child(' + (i + 1) + ') .house-allowance').text());
            var transport_allowance = parseFloat($('table.order-list tbody tr:nth-child(' + (i + 1) + ') .transport-allowance').text());
            var fuel_allowance = parseFloat($('table.order-list tbody tr:nth-child(' + (i + 1) + ') .fuel-allowance').text());
            var ot =  parseFloat($('table.order-list tbody tr:nth-child(' + (i + 1) + ') .ot').val());
            var deduction =  parseFloat($('table.order-list tbody tr:nth-child(' + (i + 1) + ') .deduction').val());
            var position = parseFloat($('table.order-list tbody tr:nth-child(' + (i + 1) + ') .position').text());


            var transport_allowance_taxable=0;
            var house_allowance_taxable=0;
            


            if (transport_allowance > 2200) {
                transport_allowance_taxable=transport_allowance-2200;
            }
            else if (transport_allowance < 2200 && transport_allowance> (basic_salary1*(4/100))) {
                transport_allowance_taxable=transport_allowance-(basic_salary1*(4/100));
            }else if (transport_allowance < 600 ) {
                transport_allowance_taxable=0;
            }else{
                transport_allowance_taxable=0;


            }
 
            if (house_allowance > 600) {
                house_allowance_taxable=house_allowance - 600.0;
            }


            // var qty = 12;
             
            var unit_cost = $('table.order-list tbody tr:nth-child(' + (i + 1) + ') .unit-cost').val();
            // var unit_cost =10;

            // total_qty += parseFloat(qty);
            $('table.order-list tbody tr:nth-child(' + (i + 1) + ') .day-value').val(qty);
            $('table.order-list tbody tr:nth-child(' + (i + 1) + ') .basic-salary-value').val(basic_salary1.toFixed(2));
            $('table.order-list tbody tr:nth-child(' + (i + 1) + ') .transport-allowance-value').val(transport_allowance.toFixed(2));
            $('table.order-list tbody tr:nth-child(' + (i + 1) + ') .house-allowance-value').val(house_allowance.toFixed(2));
            $('table.order-list tbody tr:nth-child(' + (i + 1) + ') .fuel-value').val(fuel_allowance.toFixed(2));
            $('table.order-list tbody tr:nth-child(' + (i + 1) + ') .ot-value').val(ot.toFixed(2));
            $('table.order-list tbody tr:nth-child(' + (i + 1) + ') .deduction-value').val(deduction.toFixed(2));
            $('table.order-list tbody tr:nth-child(' + (i + 1) + ') .position-value').val(position.toFixed(2));



            $('table.order-list tbody tr:nth-child(' + (i + 1) + ') .sub-total').text(parseFloat(unit_cost * qty).toFixed(2));

             $('table.order-list tbody tr:nth-child(' + (i + 1) + ') .total-taxable').text(parseFloat(position + ot + basic_salary  + transport_allowance_taxable+ house_allowance_taxable+fuel_allowance).toFixed(2));

            $('table.order-list tbody tr:nth-child(' + (i + 1) + ') .gross').text(parseFloat(position + ot + basic_salary  + transport_allowance+ house_allowance+fuel_allowance).toFixed(2));
     // Define the salary, tax rate, and deduction based on the salary range
            var total_taxable =parseFloat(position + ot + basic_salary  + transport_allowance_taxable+ house_allowance_taxable+fuel_allowance).toFixed(2);
            var gross =parseFloat(position + ot + basic_salary  + transport_allowance+ house_allowance+fuel_allowance).toFixed(2);

            var taxRate = 0;
            var tax_deduction = 0;

            if (total_taxable <= 600) {
            taxRate = 0;
            tax_deduction = 0;
            } else if (total_taxable <= 1650) {
            taxRate = 0.1;
            tax_deduction = 60;
            } else if (total_taxable <= 3200) {
            taxRate = 0.15;
            tax_deduction = 142.50;
            } else if (total_taxable <= 5250) {
            taxRate = 0.2;
            tax_deduction = 302.50;
            } else if (total_taxable <= 7800) {
            taxRate = 0.25;
            tax_deduction = 565;
            } else if (total_taxable <= 10900) {
            taxRate = 0.3;
            tax_deduction = 955;
            } else {
            taxRate = 0.35;
            tax_deduction = 1500;
            }

            // Calculate the salary income tax
            var salaryIncomeTax = (total_taxable * taxRate) - tax_deduction ;

            // Calculate the employee pension
            var employeePension = basic_salary * 0.07;
            var companyPension = basic_salary * 0.11;
            var total_pension=employeePension + companyPension;


            // Calculate the net income
            var netIncome = gross - salaryIncomeTax - employeePension - parseFloat(deduction);
            var totalsalary = parseFloat(gross) + parseFloat(companyPension) ;

            $('table.order-list tbody tr:nth-child(' + (i + 1) + ') .income_tax').text(parseFloat(salaryIncomeTax).toFixed(2));
            $('table.order-list tbody tr:nth-child(' + (i + 1) + ') .net_income').text(parseFloat(netIncome).toFixed(2));
            $('table.order-list tbody tr:nth-child(' + (i + 1) + ') .epension').text(parseFloat(employeePension).toFixed(2));
            $('table.order-list tbody tr:nth-child(' + (i + 1) + ') .cpension').text(parseFloat(companyPension).toFixed(2));
            $('table.order-list tbody tr:nth-child(' + (i + 1) + ') .total_pension').text(parseFloat(total_pension).toFixed(2));
            $('table.order-list tbody tr:nth-child(' + (i + 1) + ') .total_salary').text(parseFloat(totalsalary).toFixed(2));


            
            $('table.order-list tbody tr:nth-child(' + (i + 1) + ') .total-taxable-value').val(parseFloat(total_taxable).toFixed(2));    
            $('table.order-list tbody tr:nth-child(' + (i + 1) + ') .gross-value').val(parseFloat(gross).toFixed(2));    
            $('table.order-list tbody tr:nth-child(' + (i + 1) + ') .income-tax-value').val(parseFloat(salaryIncomeTax).toFixed(2));    
            $('table.order-list tbody tr:nth-child(' + (i + 1) + ') .employee-pension-value').val(parseFloat(employeePension).toFixed(2));
            $('table.order-list tbody tr:nth-child(' + (i + 1) + ') .company-pension-value').val(parseFloat(companyPension).toFixed(2));
            $('table.order-list tbody tr:nth-child(' + (i + 1) + ') .total-pension-value').val(total_pension);
            $('table.order-list tbody tr:nth-child(' + (i + 1) + ') .net-income-value').val(parseFloat(netIncome).toFixed(2));
            $('table.order-list tbody tr:nth-child(' + (i + 1) + ') .total-value').val(parseFloat(totalsalary).toFixed(2));
            total=total+totalsalary;
            total_net=total_net+netIncome;
            add_total_pension=add_total_pension+total_pension;
            total_income_tax=total_income_tax+salaryIncomeTax;
            

            total_deduction=total_deduction +  parseFloat(deduction);

            item++;

             
 
    
        }
    });

    if (!checked) {
    submitButton.prop('disabled', true);
} else {
    submitButton.prop('disabled', false);
}


 
    $('input[name="total_net"]').val(total_net.toFixed(2));
    $('#total_net').text(total_net.toFixed(2));

    
    $('input[name="add_total_pension"]').val(add_total_pension.toFixed(2));
    $('#add_total_pension').text(add_total_pension.toFixed(2));


    $('input[name="total_deduction"]').val(total_deduction.toFixed(2));
    $('#total_deduction').text(total_deduction.toFixed(2));


    $('input[name="total_income_tax"]').val(total_income_tax.toFixed(2));
    $('#total_income_tax').text(total_income_tax.toFixed(2));

    
    $('#grand_total').text(total.toFixed(2));
  
    $('input[name="grand_total"]').val(total.toFixed(2));
    $('input[name="item"]').val(item);
    item += '(' + total_qty + ')';
    $('#item').text(item);

    calculateGrandTotal();
}


 

function calculateGrandTotal() {
    // var total_qty = parseFloat($('input[name="total_qty"]').val());
    // var subtotal = parseFloat($('input[name="total_cost"]').val());
    // var order_tax = parseFloat($('select[name="order_tax_rate"]').val());
    // var order_tax = subtotal * (order_tax / 100);
    // var grand_total = subtotal + order_tax;

    
    
   
}

$(window).keydown(function(e){
    if (e.which == 13) {
        var $targ = $(e.target);
        if (!$targ.is("textarea") && !$targ.is(":button,:submit")) {
            var focusNext = false;
            $(this).find(":input:visible:not([disabled],[readonly]), a").each(function(){
                if (this === e.target) {
                    focusNext = true;
                }
                else if (focusNext){
                    $(this).focus();
                    return false;
                }
            });
            return false;
        }
    }
});

$('.payment-form').on('submit',function(e){
    var rownumber = $('table.order-list tbody tr:last').index();
    if (rownumber < 0) {
        alert("Please insert product to order table!")
        e.preventDefault();
    }
    else {
        $("#submit-button").prop('disabled', true);
    }
});

</script>
@endpush
