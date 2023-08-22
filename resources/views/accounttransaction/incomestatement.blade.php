@extends('layout.main') @section('content')
@if(session()->has('not_permitted'))
  <div class="alert alert-danger alert-dismissible text-center"><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>{{ session()->get('not_permitted') }}</div>
@endif

 



<section class="forms">
<div class="container-fluid">
        <div class="card">
            <div class="card-header mt-2">
                <h3 class="text-center">Income Statement</h3>
                <h3 class="text-center">For {{$warehouse_name}} Branche</h3>
                <h3 class="text-center">As Of {{$end_date}}  </h3>

            </div>
            {!! Form::open(['route' => 'accounttransaction.Income_Statement', 'method' => 'get']) !!}
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
                                @foreach($lims_warehouse_list as $warehouse)
                                <option value="{{$warehouse->id}}">{{$warehouse->name}}</option>
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
        <table id="income-statement-table" class="table table-hover" style="width: 100%">
            <thead> 
                <tr>
                     <th> </th>
                    <th> </th>
                    <th> </th>
                    <th></th> 
                    <th></th> 
                 </tr>
            </thead>

          
        </table>
    </div>
     
</section>
 
@endsection

@push('scripts')
<script type="text/javascript">
$("ul#account").siblings('a').attr('aria-expanded','true');
    $("ul#account").addClass("show");
    $("ul#account #financial-Income_Statement-menu").addClass("active");

    
    
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
        "searching": false,
        "paging": false,
        "info": false,
        "ordering": true,
         "serverSide": true,
        "ajax":{
            url:"trial_balance",
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
            {"data": "account"},
            {"data": "debit"}, 
            {"data": "credit"}, 
        ], 
        'columnDefs': [
            {
                'render': function(data, type, row, meta){
                    if(type === 'display'){
                        data = '<div<label></label></div>';
                    }

                   return data;
                }
            }
        ],
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
                text: companyname+'\n '+sitename+"\ Trial Balance \n From As Of "+ end_date ,
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
        footer: false
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
            , 
        ],
        drawCallback: function () {
            var api = this.api();
            // datatable_sum(api, false);
        }
    } );
 
</script>
@endpush




@push('scripts')
<script type="text/javascript">
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
    $('#ledger-report-table').DataTable( {
        "searching": false,
        "paging": false,
        "info": false,
        "ordering": true,
         "serverSide": true,
        "ajax":{
            url:"balance_sheet",
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
        // "createdRow": function( row, data, dataIndex ) {
        //     console.log(end_date);
        
        // },

         
        "columns": [
             {"data": "Account_category"},
            {"data": "Account_category_type"},
            {"data": "account"},
            {"data": "Debit"}, 
            {"data": "Credit"}, 
            {"data": "total"}, 

        ], 
        'columnDefs': [
            {
                'render': function(data, type, row, meta){
                    if(type === 'display'){
                        data = '<div<label></label></div>';
                    }

                   return data;
                }
            }
        ],
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
                text: companyname+'\n '+sitename+"\nBalance Sheet\n  As Of "+ end_date ,
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
        footer: false
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
            , 
        ],
        drawCallback: function () {
            var api = this.api();
            // datatable_sum(api, false);
        }
    } );
 
</script>
@endpush


@push('scripts')
<script type="text/javascript">
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
    $('#income-statement-table').DataTable( {
        "searching": false,
        "paging": false,
        "info": false,
        "ordering": true,
         "serverSide": true,
        "ajax":{
            url:"income_statement",
            data:{
                start_date: start_date,
                end_date: end_date,
                warehouse_id: warehouse_id
            },
            dataType: "json",
            type:"post",
          
        },
        // "createdRow": function( row, data, dataIndex ) {
        //     console.log(data);
        //     // $(row).addClass('purchase-link');
        //     //$(row).attr('data-purchase', data['purchase']);
        // },

         
        "columns": [
            {"data": "Account_category"},
            {"data": "Account_category_type"},
            {"data": "account"},
            {"data": "debit"},
            {"data": "credit"},
  // success:function(data){
            //     console.log(data);
            // }
        ], 
        'columnDefs': [
            {
                'render': function(data, type, row, meta){
                    if(type === 'display'){
                        data = '<div<label></label></div>';
                    }

                   return data;
                }
            }
        ],
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
                text: companyname+'\n '+sitename+"\n Income Statement \n As of  "+ end_date ,
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
        footer: false
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
            , 
        ],
        drawCallback: function () {
            var api = this.api();
            // datatable_sum(api, false);
        }
    } );
 
</script>
@endpush
