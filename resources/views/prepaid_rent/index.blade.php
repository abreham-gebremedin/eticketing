@extends('layout.main') @section('content')
@if(session()->has('message'))
  <div class="alert alert-success alert-dismissible text-center">
    <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
    {{ session()->get('message') }}

    @if(session()->has('undoUrl'))
    <form action="{{ session()->get('undoUrl') }}" method="POST">
      @csrf
      <button type="submit" class="btn btn-danger">Undo  change</button>
    </form>
    @endif
  </div>
@endif

@if(session()->has('not_permitted'))
  <div class="alert alert-danger alert-dismissible text-center">
    <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
    {{ session()->get('not_permitted') }}
    @if(session()->has('deleteUrl'))
    <form action="{{ session()->get('undoUrl') }}" method="POST">
      @csrf
      <button type="submit" class="btn btn-success">Restore</button>
    </form>
    @endif
</div>
@endif

<section>
    <div class="container-fluid">
        <div class="card">
            <div class="card-header mt-2">
                <h3 class="text-center">Pre Paid Rent List</h3>
            </div>
            {!! Form::open(['route' => 'prepaid_rent.index', 'method' => 'get']) !!}
            <div class="row mb-3">
                <div class="col-md-4 offset-md-2 mt-3">
                    <div class="form-group row">
                         <div class="d-tc">
                            <div class="input-group">                                 
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mt-3 @if(\Auth::user()->role_id > 2){{'d-none'}}@endif">
                    <div class="form-group row">
                        <label class="d-tc mt-2"><strong>{{trans('file.Choose Warehouse')}}</strong> &nbsp;</label>
                        <div class="d-tc">
                            <select id="warehouse_id" name="warehouse_id" class="selectpicker form-control" data-live-search="true" data-live-search-style="begins" >
                                <option value="0">{{trans('file.All Warehouse')}}</option>
                                @foreach($lims_warehouse_list as $warehouse)
                                    @if($warehouse->id == $warehouse_id)
                                        <option selected value="{{$warehouse->id}}">{{$warehouse->name}}</option>
                                    @else
                                        <option value="{{$warehouse->id}}">{{$warehouse->name}}</option>
                                    @endif
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
        @if(in_array("prepaid_rent-add", $all_permission))
            <button class="btn btn-info" data-toggle="modal" data-target="#prepaid_rent-modal"><i class="dripicons-plus"></i> Add Fixed Asset</button>
        @endif
    </div>
    <div class="table-responsive">
        <table id="prepaid_rent-table" class="table prepaid_rent-list" style="width: 100%">
            <thead>
                <tr>
                    <th class="not-exported"></th>
                    <th>{{trans('file.Date')}}</th>
                    <th>{{trans('file.reference')}} No</th>
                    <th>Name</th>
                    <th>{{trans('file.Warehouse')}}</th>
                     <th>Rent Duration</th>
                    <th>Total Cost</th>
                     <th>Rent Expense</th>
                    <th>Remaining PrePaid Rent</th>
                    <th>{{trans('file.Note')}}</th>
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
                <th></th>
            </tfoot>
        </table>
    </div>
</section>





<div id="editModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true" class="modal fade text-left">
    <div role="document" class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 id="exampleModalLabel" class="modal-title">Update Expense</h5>
                <button type="button" data-dismiss="modal" aria-label="Close" class="close"><span aria-hidden="true"><i class="dripicons-cross"></i></span></button>
            </div>
            <div class="modal-body">
              <p class="italic"><small>{{trans('file.The field labels marked with * are required input fields')}}.</small></p>
                {!! Form::open(['route' => ['prepaid_rent.update', 1], 'method' => 'put']) !!}
                <?php
                     if(Auth::user()->role_id > 2)
                        $lims_warehouse_list = DB::table('warehouses')->where([
                            ['is_active', true],
                            ['id', Auth::user()->warehouse_id]
                        ])->get();
                    else
                        $lims_warehouse_list = DB::table('warehouses')->where('is_active', true)->get();
                ?>
                  <div class="form-group">
                      <input type="hidden" name="prepaid_rent_id">
                      <label>{{trans('file.reference')}}</label>
                      <p id="reference">{{'er-' . date("Ymd") . '-'. date("his")}}</p>
                  </div>
                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label>{{trans('file.Date')}}</label>
                            <input type="text" name="created_at" class="form-control date" placeholder="Choose date"/>
                        </div>
                      
                        <div class="col-md-6 form-group">
                            <label>{{trans('file.Warehouse')}} *</label>
                            <select name="warehouse_id" class="selectpicker form-control" required data-live-search="true" data-live-search-style="begins" title="Select Warehouse...">
                                @foreach($lims_warehouse_list as $warehouse)
                                <option value="{{$warehouse->id}}">{{$warehouse->name}}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6 form-group">
                            <label>Name *</label>
                            <input type="text" name="name" step="any" required class="form-control">
                        </div>
                        <div class="col-md-6 form-group">
                            <label>Rent Duration *</label>
                            <p class="italic"><small>Rent duration should be specified in a year , for Example 1, 2 , or 4 year e.t.c</small></p>
                            <input type="number" name="life_time" step="any" required class="form-control">
                        </div>
                        <div class="col-md-6 form-group">
                            <label>PrePaid Amount *</label>
                            <input type="number" name="total_cost" step="any" required class="form-control">
                        </div>
                        <div class="col-md-6 form-group">
                            <label> {{trans('file.Account')}}</label>
                            <select class="form-control selectpicker" name="account_id">
                            @foreach($lims_account_list as $account)
                                @if($account->is_default)
                                <option selected value="{{$account->id}}">{{$account->name}} [{{$account->account_no}}]</option>
                                @else
                                <option value="{{$account->id}}">{{$account->name}} [{{$account->account_no}}]</option>
                                @endif
                            @endforeach
                            </select>
                        </div>
                    </div>
                  <div class="form-group">
                      <label>{{trans('file.Note')}}</label>
                      <textarea name="note" rows="3" class="form-control"></textarea>
                  </div>
                  <div class="form-group">
                      <button type="submit" class="btn btn-primary">{{trans('file.submit')}}</button>
                  </div>
                {{ Form::close() }}
            </div>
        </div>
    </div>
</div>


@endsection

@push('scripts')
<script type="text/javascript">

    $("ul#prepaid_rent").siblings('a').attr('aria-expanded','true');
    $("ul#prepaid_rent").addClass("show");
    $("ul#prepaid_rent #prepaid_rent-list-menu").addClass("active");

    var prepaid_rent_id = [];
    var user_verified = <?php echo json_encode(env('USER_VERIFIED')) ?>;
    var all_permission = <?php echo json_encode($all_permission) ?>;

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    function confirmDeletePayment() {
        if (confirm("Are you sure want to delete? If you delete this money will be refunded")) {
            return true;
        }
        return false;
    }

    $(document).on("click", "table.prepaid_rent-list tbody .add-payment", function(event) {
        $("#cheque").hide();
        $("#mobile").hide();
        $(".card-element").hide();
        $('select[name="paid_by_id"]').val(1);
        rowindex = $(this).closest('tr').index();
        var prepaid_rent_id = $(this).data('id').toString();
        var balance = $('table.prepaid_rent-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('td:nth-child(11)').text();
        balance = parseFloat(balance.replace(/,/g, ''));
        $('input[name="amount"]').val(balance);
        $('input[name="balance"]').val(balance);
        $('input[name="paying_amount"]').val(balance);
        $('input[name="prepaid_rent_id"]').val(prepaid_rent_id);
    });

    $(document).on("click", "table.prepaid_rent-list tbody .get-payment", function(event) {
        var id = $(this).data('id').toString();
        $.get('prepaid_rent/getpayment/' + id, function(data) {
            $(".payment-list tbody").remove();
            var newBody = $("<tbody>");
            payment_date  = data[0];
            payment_reference = data[1];
            paid_amount = data[2];
            paying_method = data[3];
            payment_id = data[4];
            payment_note = data[5];
            cheque_no = data[6];
            change = data[7];
            paying_amount = data[8];
            account_name = data[9];
            account_id = data[10];
            cheque_bank=data[11];

            $.each(payment_date, function(index){
                var newRow = $("<tr>");
                var cols = '';
                
                cols += '<td>' + payment_date[index] + '</td>';
                cols += '<td>' + payment_reference[index] + '</td>';
                cols += '<td>' + account_name[index] + '</td>';
                cols += '<td>' + paid_amount[index] + '</td>';
                cols += '<td>' + paying_method[index] + '</td>';
                cols += '<td>' + cheque_bank[index] + '</td>';
                cols += '<td>' + cheque_no[index] + '</td>';

                cols += '<td><div class="btn-group"><button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">Action<span class="caret"></span><span class="sr-only">Toggle Dropdown</span></button><ul class="dropdown-menu edit-options dropdown-menu-right dropdown-default" user="menu">';
               
                
                cols += '</ul></div></td>';
                newRow.append(cols);
                newBody.append(newRow);
                $("table.payment-list").append(newBody);
            });
            $('#view-payment').modal('show');
        });
    });

    $(document).on("click", "table.payment-list .edit-btn", function(event) {
        $(".edit-btn").attr('data-clicked', true);
        $(".card-element").hide();
        $("#edit-cheque").hide();
        $("#edit-mobile").hide();
        $('#edit-payment select[name="edit_paid_by_id"]').prop('disabled', false);
        var id = $(this).data('id').toString();
        $.each(payment_id, function(index){
            if(payment_id[index] == parseFloat(id)){
                $('input[name="payment_id"]').val(payment_id[index]);
                $('#edit-payment select[name="account_id"]').val(account_id[index]);
                if(paying_method[index] == 'Cash')
                    $('select[name="edit_paid_by_id"]').val(1);
                else if(paying_method[index] == 'Credit Card'){
                    $('select[name="edit_paid_by_id"]').val(3);
                    $.getScript( "public/vendor/stripe/checkout.js" );
                    $(".card-element").show();
                    $("#edit-cheque").hide();
                    $('#edit-payment select[name="edit_paid_by_id"]').prop('disabled', true);
                }
                else{
                    $('select[name="edit_paid_by_id"]').val(4);
                    $("#edit-cheque").show();
                    $('input[name="edit_cheque_no"]').val(cheque_no[index]);
                    $('input[name="edit_cheque_no"]').attr('required', true);
                }
                $('input[name="edit_date"]').val(payment_date[index]);
                $("#payment_reference").html(payment_reference[index]);
                $('input[name="edit_amount"]').val(paid_amount[index]);
                $('input[name="edit_paying_amount"]').val(paying_amount[index]);
                $('.change').text(change[index]);
                $('textarea[name="edit_payment_note"]').val(payment_note[index]);
                return false;
            }
        });
        $('.selectpicker').selectpicker('refresh');
        $('#view-payment').modal('hide');
    });

    $('select[name="paid_by_id"]').on("change", function() {
        var id = $('select[name="paid_by_id"]').val();
        $('input[name="cheque_no"]').attr('required', false);
        $('input[name="mbtn_no"]').attr('required', false);
        $(".payment-form").off("submit");
        if (id == 3) {
            $.getScript( "public/vendor/stripe/checkout.js" );
            $(".card-element").show();
            $("#cheque").hide();
            $("#mobile").hide();
        } else if (id == 4) {
            $("#cheque").show();
            $("#mobile").hide();
            $(".card-element").hide();
            $('input[name="cheque_no"]').attr('required', true);
            $('input[name="mbtn_no"]').attr('required', false);    
        } 
        else if (id == 11) {
            $("#cheque").hide();
            $("#mobile").show();

            $(".gift-card").hide();
            $(".card-element").hide();
            $('input[name="cheque_no"]').attr('required', false);
            $('input[name="mbtn_no"]').attr('required', true);
        } 
        else if (id == 12) {
            $("#cheque").hide();
            $("#mobile").hide();

            $(".gift-card").hide();
            $(".card-element").hide();
            $('input[name="cheque_no"]').attr('required', false);
            $('input[name="mbtn_no"]').attr('required', false);
        } else {
            $(".card-element").hide();
            $("#cheque").hide();
            $("#mobile").hide();
        }
    });

    $('input[name="paying_amount"]').on("input", function() {
        $(".change").text(parseFloat( $(this).val() - $('input[name="amount"]').val() ).toFixed(2));
    });

    $('input[name="amount"]').on("input", function() {
        if( $(this).val() > parseFloat($('input[name="paying_amount"]').val()) ) {
            alert('Paying amount cannot be bigger than recieved amount');
            $(this).val('');
        }
        else if( $(this).val() > parseFloat($('input[name="balance"]').val()) ) {
            alert('Paying amount cannot be bigger than due amount');
            $(this).val('');
        }
        $(".change").text(parseFloat($('input[name="paying_amount"]').val() - $(this).val()).toFixed(2));
    });

    $('select[name="edit_paid_by_id"]').on("change", function() {
        var id = $('select[name="edit_paid_by_id"]').val();
        $('input[name="edit_cheque_no"]').attr('required', false);
        $('input[name="edit_mbtn_no"]').attr('required', false);
        $(".payment-form").off("submit");
        if (id == 3) {
            $(".edit-btn").attr('data-clicked', true);
            $.getScript( "public/vendor/stripe/checkout.js" );
            $(".card-element").show();
            $("#edit-cheque").hide();
            $("#edit-mobile").hide();
        } else if (id == 4) {
            $("#edit-cheque").show();
            $("#edit-mobile").hide();
            $(".card-element").hide();
            $('.gift-card').hide();
            $('input[name="edit_cheque_no"]').attr('required', true);
            $('input[name="edit_mbtn_no"]').attr('required', false);

        } else if (id == 12) {
            $("#edit-cheque").hide();
            $("#edit-mobile").hide();
            $(".card-element").hide();
            $('.gift-card').hide();
            $('input[name="edit_cheque_no"]').attr('required', false);
            $('input[name="edit_mbtn_no"]').attr('required', false);

        }else if (id == 11) {
            $("#edit-cheque").hide();
            $("#edit-mobile").show();
            $(".card-element").hide();
            $('.gift-card').hide();
            $('input[name="edit_cheque_no"]').attr('required', false);
            $('input[name="edit_mbtn_no"]').attr('required', true);

        } else {
            $(".card-element").hide();
            $("#edit-cheque").hide();
            $("#edit-mobile").hide();

        }
    });

    $('input[name="edit_amount"]').on("input", function() {
        if( $(this).val() > parseFloat($('input[name="edit_paying_amount"]').val()) ) {
            alert('Paying amount cannot be bigger than recieved amount');
            $(this).val('');
        }
        $(".change").text(parseFloat($('input[name="edit_paying_amount"]').val() - $(this).val()).toFixed(2));
    });

    $('input[name="edit_paying_amount"]').on("input", function() {
        $(".change").text(parseFloat( $(this).val() - $('input[name="edit_amount"]').val() ).toFixed(2));
    });


    $(document).ready(function() {
        $(document).on('click', 'button.open-EditPrePaidRent_categoryDialog', function() {
            var url = "prepaid_rent/";
            var id = $(this).data('id').toString();
            url = url.concat(id).concat("/edit");
            $.get(url, function(data) {
                $('#editModal #reference').text(data['reference_no']);
                $("#editModal input[name='created_at']").val(data['date']);
                $("#editModal input[name='name']").val(data['name']);
                $("#editModal input[name='life_time']").val(data['life_time']);
                $("#editModal select[name='warehouse_id']").val(data['warehouse_id']);
                $("#editModal select[name='account_id']").val(data['account_id']);
                $("#editModal input[name='total_cost']").val(data['total_cost']);
                $("#editModal input[name='prepaid_rent_id']").val(data['id']);
                $("#editModal textarea[name='note']").val(data['note']);
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

     
    var warehouse_id = $("#warehouse_id").val();
    $('#prepaid_rent-table').DataTable( {
        "processing": true,
        "serverSide": true,
        "ajax":{
            url:"prepaid_rent/prepaid_rent-data",
            data:{
                all_permission: all_permission,
                warehouse_id: warehouse_id
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
            {"data": "reference_no"},
            {"data": "name"},
            {"data": "warehouse"},
            {"data": "qty"},
            {"data": "total_cost"},
            {"data": "rent_expense"},
            {"data": "accdep"},
            {"data": "note"},
            {"data": "options"}
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
        order:[['1', 'asc']],
        'columnDefs': [
            {
                "orderable": false,
                'targets': [0, 3, 4, 6, 7]
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
            // {
            //     text: '<i title="delete" class="dripicons-cross"></i>',
            //     className: 'buttons-delete',
            //     action: function ( e, dt, node, config ) {
            //         if(user_verified == '1') {
            //             prepaid_rent_id.length = 0;
            //             $(':checkbox:checked').each(function(i){
            //                 if(i){
            //                     var expense = $(this).closest('tr').data('expense');
            //                     prepaid_rent_id[i-1] = expense[3];
            //                 }
            //             });
            //             if(prepaid_rent_id.length && confirm("Are you sure want to delete?")) {
            //                 $.ajax({
            //                     type:'POST',
            //                     url:'prepaid_rent/deletebyselection',
            //                     data:{
            //                         expenseIdArray: prepaid_rent_id
            //                     },
            //                     success:function(data){
            //                         alert(data);
            //                         //dt.rows({ page: 'current', selected: true }).deselect();
            //                         dt.rows({ page: 'current', selected: true }).remove().draw(false);
            //                     }
            //                 });
            //             }
            //             else if(!prepaid_rent_id.length)
            //                 alert('Nothing is selected!');
            //         }
            //         else
            //             alert('This feature is disable for demo!');
            //     }
            // },
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

    if(all_permission.indexOf("prepaid_rent-delete") == -1)
        $('.buttons-delete').addClass('d-none');

</script>
@endpush
