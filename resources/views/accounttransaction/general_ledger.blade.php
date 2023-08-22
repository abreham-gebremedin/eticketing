@extends('layout.main') @section('content')
@if(session()->has('not_permitted'))
  <div class="alert alert-danger alert-dismissible text-center"><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>{{ session()->get('not_permitted') }}</div>
@endif

<section class="forms">
    <div class="container-fluid">
        <div class="card">
            <div class="card-header mt-2">
                <h3 class="text-center">General Ledger</h3>
                <h3 class="text-center">For {{$warehouse_name}} Branche</h3>
                <h3 class="text-center">As Of {{$end_date}}  </h3>
            </div>
            {!! Form::open(['route' => 'accounttransaction.ledger', 'method' => 'GET']) !!}
            <div class="row mb-3 product-report-filter">
                <div class="col-md-4 offset-md-2 mt-3">
                    <div class="form-group row">
                        <label class="d-tc mt-2"><strong>{{trans('file.Choose Your Date')}}</strong> &nbsp;</label>
                        <div class="d-tc">
                            <div class="input-group">
                                <input type="text" class="daterangepicker-field form-control" value="{{$start_date}} To {{$end_date}}" required />
                                <input type="hidden" name="start_date" value="{{$start_date}}" />
                                <input type="hidden" name="end_date" value="{{$end_date}}" />
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mt-3">
                    <div class="form-group row">
                        <label class="d-tc mt-2"><strong>{{trans('file.Choose Warehouse')}}</strong> &nbsp;</label>
                        <div class="d-tc">
                            <select name="warehouse_id" class="selectpicker form-control" data-live-search="true" data-live-search-style="begins" >
                                <option value="0">{{trans('file.All Warehouse')}}</option>
                                @foreach($lims_warehouse_list as $warehousel)
                                <option value="{{$warehousel->id}}">{{$warehousel->name}}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
                <div class="col-md-2 mt-3">
                    <div class="form-group">
                        <button class="btn btn-primary" type="submit">{{trans('file.submit')}}</button>
                    </div>
                </div>
            </div>
            {!! Form::close() !!}
        </div>
    </div>
    <div class="table-responsive">
        <table id="product-report-table" class="table table-hover" style="width: 100%">
            <thead>
                <tr>
                    <th class="not-exported"></th>
                    <th>Date</th>
                     <th>Reference No</th> 
                     <th>Warehouse</th> 
                    <th>Account</th>
                    <th>debit</th>
                    <th>credit</th> 
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

@endsection

@push('scripts')
<script type="text/javascript">

$("ul#account").siblings('a').attr('aria-expanded','true');
    $("ul#account").addClass("show");
    $("ul#account #general-leadger-menu").addClass("active");
    
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    var warehouse_id = <?php echo json_encode($warehouse_id)?>;
    $('.product-report-filter select[name="warehouse_id"]').val(warehouse_id);
    $('.selectpicker').selectpicker('refresh');

 

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
  endDate: end_date,
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
    $(".product-report-filter input[name=start_date]").val(start_date.format('YYYY-MM-DD'));
    $(".product-report-filter input[name=end_date]").val(end_date.format('YYYY-MM-DD'));
  }
});



    var companyname = <?php echo json_encode($general_setting->company_name); ?>;
    var sitename = <?php echo json_encode($general_setting->site_title); ?>;
    var start_date = $(".product-report-filter input[name=start_date]").val();
    var end_date = $(".product-report-filter input[name=end_date]").val();
    var warehouse_id = $(".product-report-filter select[name=warehouse_id]").val();
    $('#product-report-table').DataTable( {
        "processing": true,
        "serverSide": true,
        "ajax":{
            url:"general_ledger_data",
            data:{
                start_date: start_date,
                end_date: end_date,
                warehouse_id: warehouse_id
            },
            dataType: "json",
            type:"post",
            // success:function(data){
            //     console.log(data);
            // }
        },
        /*"createdRow": function( row, data, dataIndex ) {
            console.log(data);
            $(row).addClass('purchase-link');
            //$(row).attr('data-purchase', data['purchase']);
        },*/
        "columns": [
            {"data": "key"},
            {"data": "date"}, 
            {"data": "reference_no"},
            {"data": "warehouse"},
            {"data": "account"},
            {"data": "debit"},
            {"data": "credit"},
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
                'targets': [0, 2, 3, 4, 5,/* 6, 7, /*8, 9, 10, 11, 12, 13*/]
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
        'lengthMenu': [[10, 25, 50, 100, 500], [10, 25, 50, 100, 500]],
        dom: '<"row"lfB>rtip',
        buttons: [
            {
        extend: 'pdf',
        text: '<i title="Export to PDF" class="fa fa-file-pdf-o"></i>',
        exportOptions: {
            columns: ':visible:not(.not-exported)',
            rows: ':visible'
        },
        customize: function(doc) {
            doc.content.splice(0, 0, {
                text: companyname+'\n '+sitename+"\nGeneral Ledger\n From "+start_date+" - To - "+ end_date ,
                bold: true,
                fontSize: 20,
                alignment: 'center',
                margin: [0, 0, 0, 10]
            });
            doc.content.splice(1, 0, {
                text: 'Date: ' + new Date().toLocaleString(),
                bold: true,
                alignment: 'center',
                margin: [0, 0, 0, 10]
            });
        },
        footer: true
    },
    {
        extend: 'csv',
        text: '<i title="Export to CSV" class="fa fa-file-excel-o"></i>',
        exportOptions: {
            columns: ':visible:not(.not-exported)',
            rows: ':visible'
        },
        header: true,
        footer: true,
        title: 'General Ledger - ' + new Date().toLocaleString(),
        fieldSeparator: ',',
        fieldBoundary: '"',
        charset: 'utf-8'
    },
    {
        extend: 'print',
        text: '<i title="Print" class="fa fa-print"></i>',
        exportOptions: {
            columns: ':visible:not(.not-exported)',
            rows: ':visible'
        },
        customize: function(win) {
            $(win.document.body).prepend('<h1>Company Name</h1>');
            $(win.document.body).prepend('<h2>Date: ' + new Date().toLocaleString() + '</h2>');
            $(win.document.body).find('table').addClass('display').css('font-size', '12px');
        }
    }
            ,{
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

            // $( dt_selector.column( 2 ).footer() ).html(dt_selector.cells( rows, 2, { page: 'current' } ).data().sum().toFixed(2));
            // $( dt_selector.column( 3 ).footer() ).html(dt_selector.cells( rows, 3, { page: 'current' } ).data().sum().toFixed(2));
            // $( dt_selector.column( 4 ).footer() ).html(dt_selector.cells( rows, 4, { page: 'current' } ).data().sum().toFixed(2));
            // $( dt_selector.column( 5 ).footer() ).html(dt_selector.cells( rows, 5, { page: 'current' } ).data().sum().toFixed(2));
            // $( dt_selector.column( 6 ).footer() ).html(dt_selector.cells( rows, 6, { page: 'current' } ).data().sum().toFixed(2));
            // $( dt_selector.column( 7 ).footer() ).html(dt_selector.cells( rows, 7, { page: 'current' } ).data().sum().toFixed(2));
            // $( dt_selector.column( 8 ).footer() ).html(dt_selector.cells( rows, 8, { page: 'current' } ).data().sum().toFixed(2));
            // $( dt_selector.column( 9 ).footer() ).html(dt_selector.cells( rows, 9, { page: 'current' } ).data().sum().toFixed(2));
            // $( dt_selector.column( 10 ).footer() ).html(dt_selector.cells( rows, 10, { page: 'current' } ).data().sum().toFixed(2));
            // $( dt_selector.column( 11 ).footer() ).html(dt_selector.cells( rows, 11, { page: 'current' } ).data().sum().toFixed(2));
            // /*$( dt_selector.column( 12 ).footer() ).html(dt_selector.cells( rows, 12, { page: 'current' } ).data().sum().toFixed(2));
            // $( dt_selector.column( 13 ).footer() ).html(dt_selector.cells( rows, 13, { page: 'current' } ).data().sum().toFixed(2));*/
        }
        else {
        //     $( dt_selector.column( 2 ).footer() ).html(dt_selector.column( 2, {page:'current'} ).data().sum().toFixed(2));
        //     $( dt_selector.column( 3 ).footer() ).html(dt_selector.column( 3, {page:'current'} ).data().sum().toFixed(2));
        //     $( dt_selector.column( 4 ).footer() ).html(dt_selector.column( 4, {page:'current'} ).data().sum().toFixed(2));
        //     $( dt_selector.column( 5 ).footer() ).html(dt_selector.column( 5, {page:'current'} ).data().sum().toFixed(2));
            // $( dt_selector.column( 6 ).footer() ).html(dt_selector.column( 6, {page:'current'} ).data().sum().toFixed(2));
            // $( dt_selector.column( 7 ).footer() ).html(dt_selector.column( 7, {page:'current'} ).data().sum().toFixed(2));
            // $( dt_selector.column( 8 ).footer() ).html(dt_selector.column( 8, {page:'current'} ).data().sum().toFixed(2));
            // $( dt_selector.column( 9 ).footer() ).html(dt_selector.column( 9, {page:'current'} ).data().sum().toFixed(2));
            // $( dt_selector.column( 10 ).footer() ).html(dt_selector.column( 10, {page:'current'} ).data().sum().toFixed(2));
            // $( dt_selector.column( 11 ).footer() ).html(dt_selector.column( 11, {page:'current'} ).data().sum().toFixed(2));
            // /*$( dt_selector.column( 12 ).footer() ).html(dt_selector.column( 12, {page:'current'} ).data().sum().toFixed(2));
            // $( dt_selector.column( 13 ).footer() ).html(dt_selector.column( 13, {page:'current'} ).data().sum().toFixed(2));*/
        }
    }
</script>
@endpush
