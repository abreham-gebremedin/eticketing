@extends('layout.main') @section('content')
@if(session()->has('message'))
  <div class="alert alert-success alert-dismissible text-center"><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>{!! session()->get('message') !!}</div>
@endif
@if(session()->has('not_permitted'))
  <div class="alert alert-danger alert-dismissible text-center"><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>{{ session()->get('not_permitted') }}</div>
@endif

<section>
    <div class="container-fluid">
        <div class="card">
            <div class="card-header mt-2">
                <h3 class="text-center">Payroll Lists</h3>
            </div>
        
        </div>
     
    </div>
    <div class="table-responsive">
        <table id="return-table" class="table return-list" style="width: 100%">
            <thead>
                <tr>
                    <th class="not-exported"></th>
                    <th>{{trans('file.Date')}}</th>
                    <th>{{trans('file.reference')}}</th>
                    <th>Account</th>
                    <th>User</th>
                    <th>Net Total</th>
                    <th>Total Pension</th>
                    <th>Total Income Tax</th>
                    <th>Total Deduction</th>
                    <th>Grand Total</th>
                     
                    <th class="not-exported">{{trans('file.action')}}</th>
                </tr>
            </thead>

            <tfoot class="tfoot active">
                <th></th>
                <th>{{trans('file.Total')}}</th>
                <th></th>
                <th></th>
                <th></th>
                <th></th>
                <th></th>
            </tfoot>
        </table>
    </div>
</section>

<div id="add-purchase-return" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true" class="modal fade text-left">
    <div role="document" class="modal-dialog">
      <div class="modal-content">
        {!! Form::open(['route' => 'return-purchase.create', 'method' => 'get']) !!}
        <div class="modal-header">
          <h5 id="exampleModalLabel" class="modal-title">Add Purchase Return</h5>
          <button type="button" data-dismiss="modal" aria-label="Close" class="close"><span aria-hidden="true"><i class="dripicons-cross"></i></span></button>
        </div>
        <div class="modal-body">
          <p class="italic"><small>{{trans('file.The field labels marked with * are required input fields')}}.</small></p>
           <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>{{trans('file.Purchase Reference')}} *</label>
                        <input type="text" name="reference_no" class="form-control">
                    </div>
                </div>
           </div>
            {{Form::submit('Submit', ['class' => 'btn btn-primary'])}}
        </div>
        {!! Form::close() !!}
      </div>
    </div>
</div>

<div id="return-details" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true" class="modal fade text-left">
    <div role="document" class="modal-dialog">
      <div class="modal-content">
        <div class="container mt-3 pb-2 border-bottom">
        <div class="row">
            <div class="col-md-6 d-print-none">
                <button id="print-btn" type="button" class="btn btn-default btn-sm"><i class="dripicons-print"></i> {{trans('file.Print')}}</button>
            </div>
            <div class="col-md-6 d-print-none">
                <button type="button" id="close-btn" data-dismiss="modal" aria-label="Close" class="close"><span aria-hidden="true"><i class="dripicons-cross"></i></span></button>
            </div>
            <div class="col-md-12">
                <h3 id="exampleModalLabel" class="modal-title text-center container-fluid">{{$general_setting->site_title}}</h3>
            </div>
            <div class="col-md-12 text-center">
                <i style="font-size: 15px;">Payroll Details</i>
            </div>
        </div>
    </div>
            <div id="return-content" class="modal-body">
            </div>
            <br>
            <div class="table-responsive">
            <table class="table table-bordered product-return-list">
                <thead>

                    <th>#</th>
                    <th>Employee</th>
                    <th>Number of working day</th>
                    <th>Basic Salary</th>
                    <th>Transport Allowance</th>
                    <th>House Allowance</th>
                    <th>Fuel</th>
                    <th>Over Time</th>
                    <th>Position</th>
                    <th>Gross</th>
                    <th>Total Taxable</th>
                    <th>Income Tax</th>
                    <th>Employee Pension</th>
                    <th>Company Pension</th>
                    <th>Total Pension</th>
                    <th>Total Salary </th>
                    <th>Deduction</th>
                    <th>Net Pay</th>
                    <th>Signature</th>
                </thead>
                <tbody>
                </tbody>
            </table>
        </div>

            <div id="return-footer" class="modal-body"></div>
      </div>
    </div>
</div>

@endsection

@push('scripts')
<script type="text/javascript">
    $("ul#hrm").siblings('a').attr('aria-expanded','true');
    $("ul#hrm").addClass("show");
    $("ul#hrm #payroll-menu").addClass("active");
    
    $(".daterangepicker-field").daterangepicker({
      callback: function(startDate, endDate, period){
        var starting_date = startDate.format('YYYY-MM-DD');
        var ending_date = endDate.format('YYYY-MM-DD');
        var title = starting_date + ' To ' + ending_date;
        $(this).val(title);
        $('input[name="starting_date"]').val(starting_date);
        $('input[name="ending_date"]').val(ending_date);
      }
    });

    var all_permission = <?php echo json_encode($all_permission) ?>;
    var return_id = [];
    var user_verified = <?php echo json_encode(env('USER_VERIFIED')) ?>;

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    function confirmDelete() {
        if (confirm("Are you sure want to delete?")) {
            return true;
        }
        return false;
    }

    $(document).on("click", "tr.return-link td:not(:first-child, :last-child)", function() {
        var returns = $(this).parent().data('return');
        returnDetails(returns);
    });

    $(document).on("click", ".view", function() {
        var returns = $(this).parent().parent().parent().parent().parent().data('return');
        returnDetails(returns);
    });

    $("#print-btn").on("click", function(){
        var divContents = document.getElementById("return-details").innerHTML;
        var a = window.open('');
        a.document.write('<html>');
        a.document.write('<body>');
        a.document.write('<style>body{font-family: sans-serif;line-height: 1.15;-webkit-text-size-adjust: 100%;}.d-print-none{display:none}.text-center{text-align:center}.row{width:100%;margin-right: -15px;margin-left: -15px;}.col-md-12{width:100%;display:block;padding: 5px 15px;}.col-md-6{width: 50%;float:left;padding: 5px 15px;}table{width:100%;margin-top:30px;}th{text-aligh:left}td{padding:10px}table,th,td{border: 1px solid black; border-collapse: collapse;}</style><style>@media print {.modal-dialog { max-width: 1000px;} }</style>');
        a.document.write(divContents);
        a.document.write('</body></html>');
        a.document.close();
        setTimeout(function(){a.close();},10);
        a.print();
    });

    

    $('#return-table').DataTable( {
        "processing": true,
        "serverSide": true,
        "ajax":{
            url:"payroll/payroll-data",
            data:{
                all_permission: all_permission,
                
            },
            dataType: "json",
            type:"post"
        },
        "createdRow": function( row, data, dataIndex ) {
            //alert(data);
            $(row).addClass('return-link');
            $(row).attr('data-return', data['return']);
        },
        "columns": [
            {"data": "key"},
            {"data": "date"},
            {"data": "reference_no"},
            {"data": "account"},
            {"data": "user"},
            {"data": "total_net"},
            {"data": "add_total_pension"},
            {"data": "total_income_tax"},
            {"data": "total_deduction"},
            {"data": "grand_total"},
            {"data": "options"},
        ],
        'language': {

            'lengthMenu': '_MENU_ {{trans("file.records per page")}}',
             "info":      '<small>{{trans("file.Showing")}} _START_ - _END_ (_TOTAL_)</small>',
            "search":  '{{trans("file.Search")}}',
            'paginate': {
                    'previous': '<i class="dripicons-chevron-left"></i>',
                    'next': '<i class="dripicons-chevron-right"></i>'
            }
        },
        order:[['1', 'desc']],
        'columnDefs': [
            {
                "orderable": false,
                'targets': [0, 3, 4, 6]
            },
            {
                'render': function(data, type, row, meta){
                    if(type === 'display'){
                        data = '<div class="checkbox"><input type="checkbox" class="dt-checkboxes"><label></label></div>';
                    }

                   return data;
                },
                'checkboxes': {
                   'selectRow': true,
                   'selectAllRender': '<div class="checkbox"><input type="checkbox" class="dt-checkboxes"><label></label></div>'
                },
                'targets': [0]
            }
        ],
        'select': { style: 'multi',  selector: 'td:first-child'},
        'lengthMenu': [[10, 25, 50, -1], [10, 25, 50, "All"]],
        dom: '<"row"lfB>rtip',
        rowId: 'ObjectID',
        buttons: [
            {
                extend: 'pdf',
                text: '<i title="export to pdf" class="fa fa-file-pdf-o"></i>',
                exportOptions: {
                    columns: ':visible:Not(.not-exported)',
                    rows: ':visible'
                },
                action: function(e, dt, button, config) {
                    datatable_sum(dt, true);
                    $.fn.dataTable.ext.buttons.pdfHtml5.action.call(this, e, dt, button, config);
                    datatable_sum(dt, false);
                },
                footer:true
            },
            {
                extend: 'csv',
                text: '<i title="export to csv" class="fa fa-file-text-o"></i>',
                exportOptions: {
                    columns: ':visible:Not(.not-exported)',
                    rows: ':visible'
                },
                action: function(e, dt, button, config) {
                    datatable_sum(dt, true);
                    $.fn.dataTable.ext.buttons.csvHtml5.action.call(this, e, dt, button, config);
                    datatable_sum(dt, false);
                },
                footer:true
            },
            {
                extend: 'print',
                text: '<i title="print" class="fa fa-print"></i>',
                exportOptions: {
                    columns: ':visible:Not(.not-exported)',
                    rows: ':visible'
                },
                action: function(e, dt, button, config) {
                    datatable_sum(dt, true);
                    $.fn.dataTable.ext.buttons.print.action.call(this, e, dt, button, config);
                    datatable_sum(dt, false);
                },
                footer:true
            },
            {
                text: '<i title="delete" class="dripicons-cross"></i>',
                className: 'buttons-delete',
                action: function ( e, dt, node, config ) {
                    if(user_verified == '1') {
                        sale_id.length = 0;
                        $(':checkbox:checked').each(function(i){
                            if(i){
                                var sale = $(this).closest('tr').data('sale');
                                sale_id[i-1] = sale[13];
                            }
                        });
                        if(sale_id.length && confirm("Are you sure want to delete?")) {
                            $.ajax({
                                type:'POST',
                                url:'sales/deletebyselection',
                                data:{
                                    saleIdArray: sale_id
                                },
                                success:function(data){
                                    alert(data);
                                    //dt.rows({ page: 'current', selected: true }).deselect();
                                    dt.rows({ page: 'current', selected: true }).remove().draw(false);
                                }
                            });
                        }
                        else if(!sale_id.length)
                            alert('Nothing is selected!');
                    }
                    else
                        alert('This feature is disable for demo!');
                }
            },
            {
                extend: 'colvis',
                text: '<i title="column visibility" class="fa fa-eye"></i>',
                columns: ':gt(0)'
            },
        ],
        drawCallback: function () {
            var api = this.api();
            datatable_sum(api, false);
        }
        
    } );

    function datatable_sum(dt_selector, is_calling_first) {
        if (dt_selector.rows( '.selected' ).any() && is_calling_first) {
            var rows = dt_selector.rows( '.selected' ).indexes();

            $( dt_selector.column( 5 ).footer() ).html(dt_selector.cells( rows, 5, { page: 'current' } ).data().sum().toFixed(2));
        }
        else {
            $( dt_selector.column( 5 ).footer() ).html(dt_selector.cells( rows, 5, { page: 'current' } ).data().sum().toFixed(2));
        }
    }

    function returnDetails(returns){
        $('input[name="payroll_id"]').val(returns[2]);
        var htmltext = '<strong>{{trans("file.Date")}}: </strong>'+returns[0]+'<br><strong>{{trans("file.reference")}}: </strong>'+returns[1]+'<br>'+'<strong>Account: </strong>'+returns[3]+'<br>'+'<strong>User: </strong>'+returns[4]+'<br>'+'<strong>Net Total: </strong>'+returns[5]+'<br>'+'<strong>Total Pension: </strong>'+returns[6]+'<br>'+'<strong>Total Income Tax: </strong>'+returns[7]+'<br>'+'<strong>Total Deduction: </strong>'+returns[8]+'<br>'+'<strong>Grand Total: </strong>'+returns[9];
        $.get('payroll/monthly_payroll/' + returns[2], function(data){
            $(".product-return-list tbody").remove();
            var employee_name = data[0];
            var day = data[1];
            var basic_salary = data[2];
            var transport_allowance = data[3];
            var house_allowance = data[4];
            var fuel = data[5];
            var ot = data[6];
            var deduction = data[7];
            var position = data[8];
            var gross = data[9];
            var total_taxable = data[10];
            var income_tax = data[11];
            var employee_pension = data[12];
            var company_pension = data[13];
            var total_pension = data[14];
            var net_income = data[15];
            var total = data[16];
            var newBody = $("<tbody>");
            $.each(employee_name, function(index){
                var newRow = $("<tr>");
                var cols = '';
                cols += '<td><strong>' + (index+1) + '</strong></td>';
                cols += '<td>' + employee_name[index] + '</td>';
                cols += '<td>' + day[index] + '</td>';
                cols += '<td>' + basic_salary[index] +'</td>';
                cols += '<td>' +  transport_allowance[index] + '</td>';
                cols += '<td>' + house_allowance[index] + '</td>';
                cols += '<td>' + fuel[index] + '</td>';
                cols += '<td>' + ot[index] + '</td>';
                cols += '<td>' + position[index] + '</td>';
                cols += '<td>' + gross[index] + '</td>';
                cols += '<td>' + total_taxable[index] + '</td>';
                cols += '<td>' + income_tax[index] + '</td>';
                cols += '<td>' + employee_pension[index] + '</td>';
                cols += '<td>' + company_pension[index] + '</td>';
                cols += '<td>' + total_pension[index] + '</td>';
                cols += '<td>' + total[index] + '</td>';
                cols += '<td>' + deduction[index] + '</td>';
                cols += '<td>' + net_income[index] + '</td>';
                cols += '<td>' + " " + '</td>';

                 newRow.append(cols);
                newBody.append(newRow);
            });

            
            $("table.product-return-list").append(newBody);
        });
        var htmlfooter = '</p><strong>{{trans("file.Created By")}}:</strong><br>'+returns[4];
        $('#return-content').html(htmltext);
        $('#return-footer').html(htmlfooter);
        $('#return-details').modal('show');
    }

    if(all_permission.indexOf("returns-delete") == -1)
        $('.buttons-delete').addClass('d-none');

</script>
<script type="text/javascript" src="https://js.stripe.com/v3/"></script>
@endpush
