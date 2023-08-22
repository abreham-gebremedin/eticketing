@extends('layout.main') @section('content')
@if(session()->has('message'))
  <div class="alert alert-success alert-dismissible text-center"><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>{{ session()->get('message') }}</div>
@endif
@if(session()->has('not_permitted'))
  <div class="alert alert-danger alert-dismissible text-center"><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>{{ session()->get('not_permitted') }}</div>
@endif

<section>
    <div class="container-fluid">
        <div class="card">
            <div class="card-header mt-2">
                <h3 class="text-center">Adjustment List</h3>
            </div>
   
        </div>
        @if(in_array("transaction_adjustments-add", $all_permission))
            <!-- <button class="btn btn-info" data-toggle="modal" data-target="#transaction_adjustments-modal"><i class="dripicons-plus"></i> Add Adjustment</button> -->
            <a href="{{route('transaction_adjustments.create')}}" class="btn btn-info"><i class="dripicons-plus"></i>  Add Adjustment</a>&nbsp;

        @endif
    </div>
    <div class="table-responsive">
        <table id="expense-table" class="table expense-list" style="width: 100%">
            <thead>
                <tr>
                    <th class="not-exported"></th>
                    <th>{{trans('file.Date')}}</th>
                    <th>{{trans('file.reference')}} No</th>
                    <th>{{trans('file.Warehouse')}}</th>
                    <th>User</th>
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
            </tfoot>
        </table>
    </div>
</section>

<div id="purchase-details" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true" class="modal fade text-left">
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
                    <i style="font-size: 15px;">Journal Entries</i>
                </div>
            </div>
        </div>
            <div id="purchase-content" class="modal-body"></div>
            <br>
            <table class="table table-bordered product-purchase-list">
                <thead>
                    <th>#</th>
                    <th>Code</th>
                    <th>Account Name</th>
                    <th>Debit</th>
                    <th>Credit</th>
                </thead>
                <tbody>
                </tbody>
            </table>
            <div id="purchase-footer" class="modal-body"></div>
      </div>
    </div>
</div> 


@endsection

@push('scripts')
<script type="text/javascript">

    $("ul#transaction_adjustments").siblings('a').attr('aria-expanded','true');
    $("ul#transaction_adjustments").addClass("show");
    $("ul#transaction_adjustments #transaction_adjustments-list-menu").addClass("active");

    var expense_id = [];
    var user_verified = <?php echo json_encode(env('USER_VERIFIED')) ?>;
    var all_permission = <?php echo json_encode($all_permission) ?>;

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

 
// Set the academic year start date to July 8 of the current year
<?php
    $fiscal_year=$general_setting->fiscal_year;
    $fiscal_year=new DateTime($fiscal_year);

    $month = $fiscal_year->format('m');
    $day = $fiscal_year->format('d');
    $year = $fiscal_year->format('Y');
     ?>
    var month =<?php echo json_encode($month-1);?>;
    var day =<?php echo json_encode($day+1)?>;
    var year =<?php echo json_encode($year)?>;

 
    var jsDateObj = new Date(year, month, day);  
    var academicYearStart = moment(jsDateObj);

 
// Set the academic year end date to July 7 of the following year
var academicYearEnd = moment();

$(".daterangepicker-field").daterangepicker({
  startDate: academicYearStart,
  endDate: ending_date,
  minDate: academicYearStart,
  maxDate: academicYearEnd,
  locale: {
    format: 'YYYY-MM-DD'
  },
  ranges: {},
  callback: function(startDate, endDate, period){
    // Get the selected start date
    var start_date = startDate.startOf('day');

    
    // Check if the selected start date is before the academic year start date
    if (start_date.isBefore(academicYearStart)) {
      start_date = academicYearStart.clone();
    }
    
    // Check if the selected start date is after the academic year end date
    if (start_date.isAfter(academicYearStart)) {
      start_date = academicYearStart.clone();
    }
    
    // Get the selected end date
    var end_date = endDate.startOf('day');
    
    // Check if the selected end date is before the academic year start date
    if (end_date.isBefore(academicYearStart)) {
      end_date = academicYearEnd.clone();
    }
    
    // Check if the selected end date is after the academic year end date
    if (end_date.isAfter(academicYearEnd)) {
      end_date = academicYearEnd.clone();
    }
    
    // Update the date range picker with the corrected start and end dates
    var title = start_date.format('YYYY-MM-DD') + ' To ' + end_date.format('YYYY-MM-DD');
    $(this).val(title);
    $(".product-report-filter input[name=starting_date]").val(start_date.format('YYYY-MM-DD'));
    $(".product-report-filter input[name=ending_date]").val(end_date.format('YYYY-MM-DD'));
  }
});
 

  
    function confirmDelete() {
    if (confirm("Are you sure want to delete?")) {
        return true;
    }
    return false;
    }

    $("#print-btn").on("click", function(){
        var divContents = document.getElementById("purchase-details").innerHTML;
        var a = window.open('');
        a.document.write('<html>');
        a.document.write('<body><style>body{font-family: sans-serif;line-height: 1.15;-webkit-text-size-adjust: 100%;}.d-print-none{display:none}.text-center{text-align:center}.row{width:100%;margin-right: -15px;margin-left: -15px;}.col-md-12{width:100%;display:block;padding: 5px 15px;}.col-md-6{width: 50%;float:left;padding: 5px 15px;}table{width:100%;margin-top:30px;}th{text-aligh:left;}td{padding:10px}table, th, td{border: 1px solid black; border-collapse: collapse;}</style><style>@media print {.modal-dialog { max-width: 1000px;} }</style>');
        a.document.write(divContents);
        a.document.write('</body></html>');
        a.document.close();
        setTimeout(function(){a.close();},10);
        a.print();
    });

 
    $(document).on("click", "tr.purchase-link td:not(:first-child, :last-child)", function(){
        var purchase = $(this).parent().data('purchase');
        purchaseDetails(purchase);
    });

    $(document).on("click", ".view", function(){
        var purchase = $(this).parent().parent().parent().parent().parent().data('purchase');
        purchaseDetails(purchase);
    });

    var starting_date = $("input[name=starting_date]").val();
    var ending_date = $("input[name=ending_date]").val();
    var warehouse_id = $("#warehouse_id").val();
    $('#expense-table').DataTable( {
        "processing": true,
        "serverSide": true,

        "ajax":{
            url:"transaction_adjustments/journal_history-data",
            data:{
                all_permission: all_permission,
                starting_date: starting_date,
                ending_date: ending_date,
                warehouse_id: warehouse_id
            },
            dataType: "json",
            type:"post"
        },
        "createdRow": function( row, data, dataIndex ) {
            $(row).addClass('purchase-link');
            $(row).attr('data-purchase', data['purchase']);
        },
        "columns": [
            {"data": "key"},
            {"data": "date"},
            {"data": "reference_no"},
            {"data": "warehouse"},
            {"data": "user"}, 
            {"data": "options"}
        ],
        'language': {
            'searchPlaceholder': "{{trans('file.Type date or  reference...')}}",
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
                'targets': [0, 3]
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
                    // datatable_sum(dt, true);
                    $.fn.dataTable.ext.buttons.pdfHtml5.action.call(this, e, dt, button, config);
                    // datatable_sum(dt, false);
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
                        expense_id.length = 0;
                        $(':checkbox:checked').each(function(i){
                            if(i){
                                var expense = $(this).closest('tr').data('expense');
                                expense_id[i-1] = expense[3];
                            }
                        });
                        if(expense_id.length && confirm("Are you sure want to delete?")) {
                            $.ajax({
                                type:'POST',
                                url:'expenses/deletebyselection',
                                data:{
                                    expenseIdArray: expense_id
                                },
                                success:function(data){
                                    alert(data);
                                    //dt.rows({ page: 'current', selected: true }).deselect();
                                    dt.rows({ page: 'current', selected: true }).remove().draw(false);
                                }
                            });
                        }
                        else if(!expense_id.length)
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
        }
    } );

  

    
    function purchaseDetails(purchase){
        var htmltext = '<strong>{{trans("file.Date")}}: </strong>'+purchase[0] + '</br> <strong>Reference: </strong>'+purchase[1];

        $.get('transaction_adjustments/journal_entries/' + purchase[2], function(data){
            $(".product-purchase-list tbody").remove();
            var code = data[0];
            var name = data[1];
            var debit = data[2];
            var credit = data[3]; 
            var newBody = $("<tbody>");
            $.each(code, function(index) {
                var newRow = $("<tr>");
                var cols = '';
                cols += '<td><strong>' + (index+1) + '</strong></td>';
                cols += '<td>' + code[index] + '</td>';
                cols += '<td>' + name[index] + '</td>';
                cols += '<td>' + debit[index]+ '</td>';
                cols += '<td>' + credit[index] + '</td>';
                newRow.append(cols);
                newBody.append(newRow);
            });

             $("table.product-purchase-list").append(newBody);
        });

        var htmlfooter = '<p><strong>Reason:</strong> '+purchase[4]+'</p><strong>{{trans("file.Created By")}}:</strong><br>'+purchase[5]+'<br>'+purchase[6];

        $('#purchase-content').html(htmltext);
        $('#purchase-footer').html(htmlfooter);
        $('#purchase-details').modal('show');
    }

    if(all_permission.indexOf("expenses-delete") == -1)
        $('.buttons-delete').addClass('d-none');

</script>
@endpush
