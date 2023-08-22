@extends('layout.main') @section('content')
@if(session()->has('message'))
  <div class="alert alert-success alert-dismissible text-center"><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>{{ session()->get('message') }}</div>
@endif
@if(session()->has('not_permitted'))
  <div class="alert alert-danger alert-dismissible text-center"><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>{{ session()->get('not_permitted') }}</div>
@endif

<section>
    <div class="container-fluid">
       
        
    <div class="table-responsive">
        <table id="fixed_asset-table" class="table fixed_asset-list" style="width: 100%">
            <thead>
                <tr>
                    <th class="not-exported"></th>
                    <th>{{trans('file.Date')}}</th>
                    <th>subject type</th>
                    <th>Description</th>
                    <th>User</th>
                    
                    <th class="not-exported">{{trans('file.action')}}</th>
                </tr>
            </thead>
            <tfoot class="tfoot active">
                <th></th>
                <!-- <th>{{trans('file.Total')}}</th> -->
                <th></th>
                <th></th>
                <th></th>
                <th></th>
                 
            </tfoot>
        </table>
    </div>
</section>

<div id="editModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true" class="modal fade text-left">
    <div role="document" class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 id="exampleModalLabel" class="modal-title">updated Data</h5>
                <button type="button" data-dismiss="modal" aria-label="Close" class="close"><span aria-hidden="true"><i class="dripicons-cross"></i></span></button>
            </div>
            

            <div class="modal-body">

            
 
            </div>
       
            
        </div>
    </div>
</div>



<div id="deleteModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true" class="modal fade text-left">
    <div role="document" class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 id="exampleModalLabel" class="modal-title">Deleted Data</h5>
                <button type="button" data-dismiss="modal" aria-label="Close" class="close"><span aria-hidden="true"><i class="dripicons-cross"></i></span></button>
            </div>
            {!! Form::open([  'method' => 'put']) !!}

            <div class="modal-body">

            
 
            </div>
            <div class="form-group">
                      <input type="hidden" name="id">
                       <p id="reference">{{'er-' . date("Ymd") . '-'. date("his")}}</p>
                  </div>
            <div class="form-group">
                      <button type="submit" class="btn btn-primary">{{trans('file.submit')}}</button>
            </div>
                {{ Form::close() }}
            
        </div>
    </div>
</div>


@endsection

@push('scripts')
<script type="text/javascript">

    $("ul#activity_log").siblings('a').attr('aria-expanded','true');
    $("ul#activity_log").addClass("show");
    $("ul#activity_log #activity_log_history-list-menu").addClass("active");

    var fixed_asset_id = [];
    var user_verified = <?php echo json_encode(env('USER_VERIFIED')) ?>;
    var all_permission = <?php echo json_encode($all_permission) ?>;

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

   

    $(document).ready(function() {
        $(document).on('click', 'button.open-EditFixedAsset_categoryDialog', function() {
            var url = "activity_log/";
            var id = $(this).data('id').toString();
            var route = "fixed_asset_categories.reject"; // define the variable with the route

            url = url.concat(id).concat("/edit");
            $.get(url, function(data) {
            // Create a table element
            var table = $('<table>').addClass('table');

            // Add a table header row with column titles
            var headerRow = $('<tr>').appendTo(table);
            $('<th>').text('Property').appendTo(headerRow);
            $('<th>').text('Old Value').appendTo(headerRow);
            $('<th>').text('New Value').appendTo(headerRow);

            // Loop through each property in the properties array
            $.each(data.properties.old, function(key, value) {
                // If the property exists in both old and new arrays
                if (key in data.properties.new) {
                    // Create a table row with the property key, old value, and new value
                    var row = $('<tr>').appendTo(table);
                    $('<td>').text(key).appendTo(row);
                    $('<td>').text(value).appendTo(row);
                    $('<td>').text(data.properties.new[key]).appendTo(row);
                }
            });

            // Append the table to the modal body
            $('#editModal .modal-body').html(table);
            $("#editModal input[name='activity_log_id']").val(data['id']);
            var form = $('#approve');
            form.attr('action', data.url+'/approve/' + data.subject_id);
            var form = $('#reject');
            form.attr('action', data.url+'/reject/' + data.subject_id);
            // Refresh the select picker
            $('.selectpicker').selectpicker('refresh');
            });
        });
    });

    function confirmDelete() {
    if (confirm("Are you sure want to delete?")) {
        return true;
    }
    return false;
    }

     
     $('#fixed_asset-table').DataTable( {
        "processing": true,
        "serverSide": true,
        "ajax":{
            url:"activity_log/log_history-data",
            data:{
                all_permission: all_permission,
             },
            dataType: "json",
            type:"post"
        },
        // success:function(data){
        //         console.log(data);
        //     },
        // "createdRow": function( row, data, dataIndex ) {
            
        // console.log(all_permission);
        // console.log(data);
        // },
        "columns": [
            {"data": "key"},
            {"data": "date"},
            {"data": "subject_type"},
            {"data": "description"},
            {"data": "user"},
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
                    //datatable_sum(dt, true);(dt, true);
                    $.fn.dataTable.ext.buttons.pdfHtml5.action.call(this, e, dt, button, config);
                    //datatable_sum(dt, true);(dt, false);
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
                    //datatable_sum(dt, true);(dt, true);
                    $.fn.dataTable.ext.buttons.csvHtml5.action.call(this, e, dt, button, config);
                    //datatable_sum(dt, true);(dt, false);
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
                    //datatable_sum(dt, true);(dt, true);
                    $.fn.dataTable.ext.buttons.print.action.call(this, e, dt, button, config);
                    //datatable_sum(dt, true);(dt, false);
                },
                footer:true
            },
            {
                text: '<i title="delete" class="dripicons-cross"></i>',
                className: 'buttons-delete',
                action: function ( e, dt, node, config ) {
                    if(user_verified == '1') {
                        fixed_asset_id.length = 0;
                        $(':checkbox:checked').each(function(i){
                            if(i){
                                var expense = $(this).closest('tr').data('expense');
                                fixed_asset_id[i-1] = expense[3];
                            }
                        });
                        if(fixed_asset_id.length && confirm("Are you sure want to delete?")) {
                            $.ajax({
                                type:'POST',
                                url:'expenses/deletebyselection',
                                data:{
                                    expenseIdArray: fixed_asset_id
                                },
                                success:function(data){
                                    alert(data);
                                    //dt.rows({ page: 'current', selected: true }).deselect();
                                    dt.rows({ page: 'current', selected: true }).remove().draw(false);
                                }
                            });
                        }
                        else if(!fixed_asset_id.length)
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
            //datatable_sum(dt, true);(api, false);
        }
    } );

    // function datatable_sum(dt_selector, is_calling_first) {
    //     if (dt_selector.rows( '.selected' ).any() && is_calling_first) {
    //         var rows = dt_selector.rows( '.selected' ).indexes();
    //         $( dt_selector.column( 5 ).footer() ).html(dt_selector.cells( rows, 5, { page: 'current' } ).data().sum().toFixed(2));
    //     }
    //     else {
    //         $( dt_selector.column( 5 ).footer() ).html(dt_selector.cells( rows, 5, { page: 'current' } ).data().sum().toFixed(2));
    //     }
    // }

    if(all_permission.indexOf("fixed_asset-delete") >0)
        $('.buttons-delete').addClass('d-none');

</script>
@endpush
