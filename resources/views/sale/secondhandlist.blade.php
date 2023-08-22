@extends('layout.main') @section('content')
@if($errors->has('name'))
<div class="alert alert-danger alert-dismissible text-center">
    <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>{{ $errors->first('name') }}</div>
@endif
@if($errors->has('image'))
<div class="alert alert-danger alert-dismissible text-center">
    <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>{{ $errors->first('image') }}</div>
@endif
@if($errors->has('email'))
<div class="alert alert-danger alert-dismissible text-center">
    <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>{{ $errors->first('email') }}</div>
@endif
@if(session()->has('message'))
  <div class="alert alert-success alert-dismissible text-center"><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>{!! session()->get('message') !!}</div>
@endif
@if(session()->has('not_permitted'))
  <div class="alert alert-danger alert-dismissible text-center"><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>{{ session()->get('not_permitted') }}</div>
@endif
<section>
    @if(in_array("secondhandlists-add", $all_permission))
    <div class="container-fluid">
        <a href="{{route('secondhandlists.create')}}" class="btn btn-info"><i class="dripicons-plus"></i> Add secondhandlist</a>
    </div>
    @endif
    <div class="table-responsive">
        <table id="employee-table" class="table secondhandlist" >
            <thead>
                <tr>
                    <th class="not-exported"></th>
                     <th>Reference</th>
                     <th>{{trans('file.name')}}</th>
                    <th>Quantity</th>
                    <th>Transfered Qty</th>
                    <th>Returned Qty Value</th>
                    <th>Sold Qty</th>
                     <th class="not-exported">{{trans('file.action')}}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($lims_secondhandlist_all as $key=>$secondhandlist)
                <tr data-id="{{$secondhandlist->id}}">
                    <td>{{$key}}</td>
                      <td>{{ $secondhandlist->reference_no}}</td>
                     <td>{{ $secondhandlist->name }} {{ $secondhandlist->item_code }}</td>
                    <td>{{ $secondhandlist->qty }}</td>
                    <td>{{ $secondhandlist->transferred_qty }}</td>
                    <td>{{ $secondhandlist->returned_qty }}</td>
                    <td>{{ $secondhandlist->sold_qty }}</td>
                      <td>
                      {!! Form::open(['route' => 'sales.createsecondhand', 'method' => 'get']) !!}
                      <div class="col-md-6 form-group">
                      <input type="hidden" name="reference_no" value="{{$secondhandlist->reference_no}}">
                  </div>
                  <div class="col-md-6 form-group">
                      <input type="hidden" name="product_purchase" value="{{$secondhandlist->id}}">
                  </div>
                  <div class="col-md-6 form-group">
                      <input type="hidden" name="warehouse_id" value="{{$secondhandlist->warehouse_id}}">
                  </div>
                  <div class="col-md-12 form-group">
                      <button type="submit" class="btn btn-primary">Sale</button>
                  </div>
                      {{ Form::close() }}
                                                
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</section>



 
   

@endsection

@push('scripts')
<script type="text/javascript">

 
    $("ul#sale").siblings('a').attr('aria-expanded','true');
    $("ul#sale").addClass("show");
    $("ul#sale #sale-secondhand-menu").addClass("active");
    $("#payment").hide();
$(".card-element").hide();
$("#gift-card").hide();
$("#cheque").hide();
    var employee_id = [];
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
 

    $('#employee-table').DataTable( {
        "order": [],
        'language': {
            'lengthMenu': '_MENU_ {{trans("file.records per page")}}',
             "info":      '<small>{{trans("file.Showing")}} _START_ - _END_ (_TOTAL_)</small>',
            "search":  '{{trans("file.Search")}}',
            'paginate': {
                    'previous': '<i class="dripicons-chevron-left"></i>',
                    'next': '<i class="dripicons-chevron-right"></i>'
            }
        },
        'columnDefs': [
            {
                "orderable": false,
                'targets': [0, 1, 6]
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
        buttons: [
            {
                extend: 'pdf',
                text: '<i title="export to pdf" class="fa fa-file-pdf-o"></i>',
                exportOptions: {
                    columns: ':visible:Not(.not-exported)',
                    rows: ':visible',
                    stripHtml: false
                },
                customize: function(doc) {
                    for (var i = 1; i < doc.content[1].table.body.length; i++) {
                        if (doc.content[1].table.body[i][0].text.indexOf('<img src=') !== -1) {
                            var imagehtml = doc.content[1].table.body[i][0].text;
                            var regex = /<img.*?src=['"](.*?)['"]/;
                            var src = regex.exec(imagehtml)[1];
                            var tempImage = new Image();
                            tempImage.src = src;
                            var canvas = document.createElement("canvas");
                            canvas.width = tempImage.width;
                            canvas.height = tempImage.height;
                            var ctx = canvas.getContext("2d");
                            ctx.drawImage(tempImage, 0, 0);
                            var imagedata = canvas.toDataURL("image/png");
                            delete doc.content[1].table.body[i][0].text;
                            doc.content[1].table.body[i][0].image = imagedata;
                            doc.content[1].table.body[i][0].fit = [30, 30];
                        }
                    }
                },
            },
            {
                extend: 'csv',
                text: '<i title="export to csv" class="fa fa-file-text-o"></i>',
                exportOptions: {
                    columns: ':visible:Not(.not-exported)',
                    rows: ':visible',
                    format: {
                        body: function ( data, row, column, node ) {
                            if (column === 0 && (data.indexOf('<img src=') != -1)) {
                                var regex = /<img.*?src=['"](.*?)['"]/;
                                data = regex.exec(data)[1];
                            }
                            return data;
                        }
                    }
                },
            },
            {
                extend: 'print',
                text: '<i title="print" class="fa fa-print"></i>',
                exportOptions: {
                    columns: ':visible:Not(.not-exported)',
                    rows: ':visible',
                    stripHtml: false
                },
            },
            {
                text: '<i title="delete" class="dripicons-cross"></i>',
                className: 'buttons-delete',
                action: function ( e, dt, node, config ) {
                    if(user_verified == '1') {
                        employee_id.length = 0;
                        $(':checkbox:checked').each(function(i){
                            if(i){
                                employee_id[i-1] = $(this).closest('tr').data('id');
                            }
                        });
                        if(employee_id.length && confirm("Are you sure want to delete?")) {
                            $.ajax({
                                type:'POST',
                                url:'secondhandlists/deletebyselection',
                                data:{
                                    employeeIdArray: employee_id
                                },
                                success:function(data){
                                    alert(data);
                                }
                            });
                            dt.rows({ page: 'current', selected: true }).remove().draw(false);
                        }
                        else if(!employee_id.length)
                            alert('No employee is selected!');
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
    } );


  
 
$('select[name="payment_status"]').on("change", function() {
    var payment_status = $(this).val();
    if (payment_status == 3 || payment_status == 4) {
        $("#paid-amount").prop('disabled',false);
        $("#payment").show();
        $("#paying-amount").prop('required',true);
        $("#paid-amount").prop('required',true);
        if(payment_status == 4){
            $("#paid-amount").prop('disabled',true);
            $('input[name="paying_amount"]').val($('input[name="grand_total"]').val());
            $('input[name="paid_amount"]').val($('input[name="grand_total"]').val());
        }
    }
    else{
        $("#paying-amount").prop('required',false);
        $("#paid-amount").prop('required',false);
        $('input[name="paying_amount"]').val('');
        $('input[name="paid_amount"]').val('');
        $("#payment").hide();
    }
});

$('select[name="paid_by_id"]').on("change", function() {
    var id = $(this).val();
    $(".payment-form").off("submit");
    $('input[name="cheque_no"]').attr('required', false);
    $('select[name="gift_card_id"]').attr('required', false);
    if(id == 2) {
        $("#gift-card").show();
        $.ajax({
            url: 'get_gift_card',
            type: "GET",
            dataType: "json",
            success:function(data) {
                $('select[name="gift_card_id"]').empty();
                $.each(data, function(index) {
                    gift_card_amount[data[index]['id']] = data[index]['amount'];
                    gift_card_expense[data[index]['id']] = data[index]['expense'];
                    $('select[name="gift_card_id"]').append('<option value="'+ data[index]['id'] +'">'+ data[index]['card_no'] +'</option>');
                });
                $('.selectpicker').selectpicker('refresh');
            }
        });
        $(".card-element").hide();
        $("#cheque").hide();
        $('select[name="gift_card_id"]').attr('required', true);
    }
    else if (id == 3) {
        $.getScript( "../public/vendor/stripe/checkout.js" );
        $(".card-element").show();
        $("#gift-card").hide();
        $("#cheque").hide();
    }
    else if (id == 4) {
        $("#cheque").show();
        $("#gift-card").hide();
        $(".card-element").hide();
        $('input[name="cheque_no"]').attr('required', true);
    }
    else {
        $("#gift-card").hide();
        $(".card-element").hide();
        $("#cheque").hide();
        if (id == 6) {
            if($('input[name="paid_amount"]').val() > deposit[$('#customer_id').val()]){
                alert('Amount exceeds customer deposit! Customer deposit : '+ deposit[$('#customer_id').val()]);
            }
        }
        else if (id == 7) {
            pointCalculation();
        }
    }
});

 
    
</script>
@endpush
