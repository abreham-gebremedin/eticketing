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
    @if(in_array("shareholders-add", $all_permission))
    <div class="container-fluid">
        <a href="{{route('shareholders.create')}}" class="btn btn-info"><i class="dripicons-plus"></i> Add Shareholder</a>
    </div>
    @endif
    <div class="table-responsive">
        <table id="employee-table" class="table shareholder" >
            <thead>
                <tr>
                    <th class="not-exported"></th>
                    <th>{{trans('file.Image')}}</th>
                    <th>{{trans('file.name')}}</th>
                    <th>{{trans('file.Email')}}</th>
                    <th>{{trans('file.Phone Number')}}</th>
                    <th>Share</th>
                    <th>Share Value</th>
                    <th>Dividend</th>
                    <th>{{trans('file.Address')}}</th>
                    <th class="not-exported">{{trans('file.action')}}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($lims_shareholders_all as $key=>$shareholder)
                <tr data-id="{{$shareholder->id}}">
                    <td>{{$key}}</td>
                    @if($shareholder->image)
                    <td> <img src="{{url('public/images/shareholder',$shareholder->image)}}" height="80" width="80">
                    </td>
                    @else
                    <td>No Image</td>
                    @endif
                    <td>{{ $shareholder->name }}</td>
                    <td>{{ $shareholder->email}}</td>
                    <td>{{ $shareholder->phone_number}}</td>
                    <td>{{ $shareholder->share}}</td>
                    <td>{{ $shareholder->share/$general_setting->one_share_value}}</td>
                    <td>{{ $shareholder->dividend}}</td>
                     <td>{{ $shareholder->address}}
                            @if($shareholder->city){{ ', '.$shareholder->city}}@endif
                            @if($shareholder->state){{ ', '.$shareholder->state}}@endif
                            @if($shareholder->postal_code){{ ', '.$shareholder->postal_code}}@endif
                            @if($shareholder->country){{ ', '.$shareholder->country}}@endif</td>
                    <td>
                        <div class="btn-group">
                            <button type="button" class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">{{trans('file.action')}}
                                <span class="caret"></span>
                                <span class="sr-only">Toggle Dropdown</span>
                            </button>
                            <ul class="dropdown-menu edit-options dropdown-menu-right dropdown-default" user="menu">
                            @if($shareholder->is_registration_fee_paid==0)
                            @if(in_array("shareholders-add", $all_permission))
                             <li>
                            <button type="button" class="registration-fee btn btn-link" data-id = "{{$shareholder->id}}" data-toggle="modal" data-target="#registration-fee"><i class="fa fa-plus"></i>Add Registeration Fee</button>
                            </li>
                            @endif 
                            @endif 

                            @if(in_array("shareholders-add", $all_permission))
                             <li>
                            <button type="button" class="add-payment btn btn-link" data-id = "{{$shareholder->id}}" data-toggle="modal" data-target="#add-payment"><i class="fa fa-plus"></i> Add share</button>
                            </li>
                            @endif  
                           
                            @if(in_array("shareholders-add", $all_permission))
                             <li>
                            <button type="button" class="withdraw-share btn btn-link" data-id = "{{$shareholder->id}}" data-toggle="modal" data-target="#withdraw-share"><i class="fa fa-minus"></i> withdraw Share</button>
                            </li>
                            @endif  
                            
                            @if(in_array("shareholders-add", $all_permission))
                             <li>
                            <button type="button" class="withdraw-dividend btn btn-link" data-id = "{{$shareholder->id}}" data-toggle="modal" data-target="#withdraw-dividend"><i class="fa fa-minus"></i> withdraw Dividend</button>
                            </li>
                            @endif 
                            
                            @if(in_array("shareholders-edit", $all_permission))
                                <li>
                                    <button type="button" data-id="{{$shareholder->id}}"  data-share="{{$shareholder->share}}" data-name="{{$shareholder->name}}" data-email="{{$shareholder->email}}" data-phone_number="{{$shareholder->phone_number}}" data-address="{{$shareholder->address}}" data-city="{{$shareholder->city}}" data-country="{{$shareholder->country}}" class="edit-btn btn btn-link" data-toggle="modal" data-target="#editModal"><i class="dripicons-document-edit"></i> {{trans('file.edit')}}</button>
                                </li>
                                @endif
                                <li class="divider"></li>
                                @if(in_array("shareholders-delete", $all_permission))
                                {{ Form::open(['route' => ['shareholders.destroy', $shareholder->id], 'method' => 'DELETE'] ) }}
                                <li>
                                    <button type="submit" class="btn btn-link" onclick="return confirmDelete()"><i class="dripicons-trash"></i> {{trans('file.delete')}}</button>
                                </li>
                                {{ Form::close() }}
                                @endif
                            </ul>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</section>




<div id="withdraw-share" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true" class="modal fade text-left">
    <div role="document" class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 id="exampleModalLabel" class="modal-title">Withdraw Share</h5>
                <button type="button" data-dismiss="modal" aria-label="Close" class="close"><span aria-hidden="true"><i class="dripicons-cross"></i></span></button>
            </div>
            <div class="modal-body">
                {!! Form::open(['route' => 'shareholder.withdraw-share', 'method' => 'post', 'class' => 'payment-form' ]) !!}
                <div class="row">
                        <input type="hidden" name="balance">
  
                        <div class="col-md-6">
                            <label>Amount</label>
                            <input type="number" id="share-amount" name="amount" class="form-control"  step="any" required disabled>
                        </div>
                        <div class="col-md-6 mt-1">
                            <label>{{trans('file.Change')}} : </label>
                            <p class="change ml-2">0.00</p>
                        </div>
                        <div class="col-md-6 mt-1">
                            <label>{{trans('file.Paid By')}}</label>
                            <select name="paid_by_id" class="form-control">
                                <option value="1">Cash</option>
                                <!-- <option value="2">Gift Card</option> -->
                                <!-- <option value="3">Credit Card</option> -->
                                <option value="4">Cheque</option>
                                <!-- <option value="11">Mobile</option>
                                <option value="12">POS ATM</option> -->

                                <!-- <option value="5">Paypal</option> -->
                                <!-- <option value="6">Deposit</option> -->
                                 <!-- <option value="7">Points</option> -->
                             </select>
                        </div>
                    </div>
       
                    <div class="form-group mt-2">
                        <div class="card-element" class="form-control">
                        </div>
                        <div class="card-errors" role="alert"></div>
                    </div>
                    <div id="cheque">
                        <div class="form-group">
                            <label>{{trans('file.Cheque Number')}} *</label>
                            <input type="text" name="cheque_no" class="form-control">
                        </div>
                    </div>

                    <div id="mobile">
                           
                            <div class="form-group">
                                <label>Mobile TXN *</label>
                                <input type="text" name="mbtn_no" class="form-control">
                            </div>
                        </div>
                        <div class="form-group">
                        <label> {{trans('file.Account')}}</label>
                        <select class="form-control selectpicker" name="account_id">
                        @foreach($lims_account_list as $baccount)
                            @if($baccount->is_default)
                            <option selected value="{{$baccount->id}}">{{$baccount->name}} [{{$baccount->account_no}}]</option>
                            @else
                            <option value="{{$baccount->id}}">{{$baccount->name}} [{{$baccount->account_no}}]</option>
                            @endif
                        @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>{{trans('file.Payment Note')}}</label>
                        <textarea rows="3" class="form-control" name="payment_note"></textarea>
                    </div>

                    <input type="hidden" name="share_holder_id">

                    <button type="submit" class="btn btn-primary">{{trans('file.submit')}}</button>
                {{ Form::close() }}
            </div>
        </div>
    </div>
</div>


<div id="withdraw-dividend" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true" class="modal fade text-left">
    <div role="document" class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 id="exampleModalLabel" class="modal-title">Withdraw Dividend</h5>
                <button type="button" data-dismiss="modal" aria-label="Close" class="close"><span aria-hidden="true"><i class="dripicons-cross"></i></span></button>
            </div>
            <div class="modal-body">
                {!! Form::open(['route' => 'shareholder.withdraw-dividend', 'method' => 'post', 'class' => 'payment-form' ]) !!}
                <div class="row">
                        <input type="hidden" name="balance">
                        
                        <div class="col-md-6">
                            <label>Amount *</label>
                            <input type="number" id="dividend-amount" name="amount" class="form-control"  step="any" required>
                        </div>
                        <div class="col-md-6 mt-1">
                            <label>{{trans('file.Change')}} : </label>
                            <p class="change ml-2">0.00</p>
                        </div>
                        <div class="col-md-6 mt-1">
                            <label>{{trans('file.Paid By')}}</label>
                            <select name="dividend_paid_by_id" class="form-control">
                                <option value="1">Cash</option>
                                <!-- <option value="2">Gift Card</option> -->
                                <!-- <option value="3">Credit Card</option> -->
                                <option value="4">Cheque</option>
                                <!-- <option value="11">Mobile</option>
                                <option value="12">POS ATM</option> -->

                                <!-- <option value="5">Paypal</option> -->
                                <!-- <option value="6">Deposit</option> -->
                                 <!-- <option value="7">Points</option> -->
                             </select>
                        </div>
                    </div>
       
                    <div class="form-group mt-2">
                        <div class="card-element" class="form-control">
                        </div>
                        <div class="card-errors" role="alert"></div>
                    </div>
                    <div id="dividend-cheque">
                        <div class="form-group">
                            <label>{{trans('file.Cheque Number')}} *</label>
                            <input type="text" name="dividend-cheque_no" class="form-control">
                        </div>
                    </div>
<!-- 
                    <div id="dividend-mobile">
                           
                            <div class="form-group">
                                <label>Mobile TXN *</label>
                                <input type="text" name="mbtn_no" class="form-control">
                            </div>
                        </div> -->
                        <div class="form-group">
                        <label> {{trans('file.Account')}}</label>
                        <select class="form-control selectpicker" name="account_id">
                        @foreach($lims_account_list as $baccount)
                            @if($baccount->is_default)
                            <option selected value="{{$baccount->id}}">{{$baccount->name}} [{{$baccount->account_no}}]</option>
                            @else
                            <option value="{{$baccount->id}}">{{$baccount->name}} [{{$baccount->account_no}}]</option>
                            @endif
                        @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>{{trans('file.Payment Note')}}</label>
                        <textarea rows="3" class="form-control" name="payment_note"></textarea>
                    </div>

                    <input type="hidden" name="share_holder_id">

                    <button type="submit" class="btn btn-primary">{{trans('file.submit')}}</button>
                {{ Form::close() }}
            </div>
        </div>
    </div>
</div>

<div id="registration-fee" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true" class="modal fade text-left">
    <div role="document" class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 id="exampleModalLabel" class="modal-title">Add Registration Fee</h5>
                <button type="button" data-dismiss="modal" aria-label="Close" class="close"><span aria-hidden="true"><i class="dripicons-cross"></i></span></button>
            </div>
            <div class="modal-body">
                {!! Form::open(['route' => 'shareholder.add-registration-fee', 'method' => 'post', 'class' => 'payment-form' ]) !!}
                <div class="row">
                        <input type="hidden" name="balance">
                        <div class="col-md-6">
                            <label>{{trans('file.Paying Amount')}} *</label>
                            <input type="text" name="amount" class="form-control numkey" step="any">
                        </div>
                          
                        <div class="col-md-6 mt-1">
                            <label>{{trans('file.Paid By')}}</label>
                            <select name="paid_by_id" class="form-control">
                                <option value="1">Cash</option>
                             </select>
                        </div>
                    </div>
                        <div class="form-group">
                        <label> {{trans('file.Account')}}</label>
                        <select class="form-control selectpicker" name="account_id">
                        @foreach($lims_account_list as $baccount)
                            @if($baccount->is_default)
                            <option selected value="{{$baccount->id}}">{{$baccount->name}} [{{$baccount->account_no}}]</option>
                            @else
                            <option value="{{$baccount->id}}">{{$baccount->name}} [{{$baccount->account_no}}]</option>
                            @endif
                        @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>{{trans('file.Payment Note')}}</label>
                        <textarea rows="3" class="form-control" name="payment_note"></textarea>
                    </div>

                    <input type="hidden" name="share_holder_id">

                    <button type="submit" class="btn btn-primary">{{trans('file.submit')}}</button>
                {{ Form::close() }}
            </div>
        </div>
    </div>
</div>


<div id="add-payment" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true" class="modal fade text-left">
    <div role="document" class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 id="exampleModalLabel" class="modal-title">Add Share</h5>
                <button type="button" data-dismiss="modal" aria-label="Close" class="close"><span aria-hidden="true"><i class="dripicons-cross"></i></span></button>
            </div>
            <div class="modal-body">
                {!! Form::open(['route' => 'shareholder.add-share', 'method' => 'post', 'class' => 'payment-form' ]) !!}
                <div class="row">
                        <input type="hidden" name="balance">
                        <div class="col-md-6">
                            <label>Share Amount*</label>
                            <input type="number" id="payment-amount" name="amount" class="form-control"  step="any" required >
                        </div>

                        <div class="col-md-6 mt-1">
                            <label>{{trans('file.Paid By')}}</label>
                            <select name="paid_by_id" class="form-control">
                                <option value="1">Cash</option>
                                <!-- <option value="2">Gift Card</option> -->
                                <!-- <option value="3">Credit Card</option> -->
                                <!-- <option value="4">Cheque</option>
                                <option value="11">Mobile</option>
                                <option value="12">POS ATM</option> -->

                                <!-- <option value="5">Paypal</option> -->
                                <!-- <option value="6">Deposit</option> -->
                                 <!-- <option value="7">Points</option> -->
                             </select>
                        </div>
                    </div>
       
                   
                        <div class="form-group">
                        <label> {{trans('file.Account')}}</label>
                        <select class="form-control selectpicker" name="account_id">
                        @foreach($lims_account_list as $baccount)
                            @if($baccount->is_default)
                            <option selected value="{{$baccount->id}}">{{$baccount->name}} [{{$baccount->account_no}}]</option>
                            @else
                            <option value="{{$baccount->id}}">{{$baccount->name}} [{{$baccount->account_no}}]</option>
                            @endif
                        @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>{{trans('file.Payment Note')}}</label>
                        <textarea rows="3" class="form-control" name="payment_note"></textarea>
                    </div>

                    <input type="hidden" name="share_holder_id">

                    <button type="submit" class="btn btn-primary">{{trans('file.submit')}}</button>
                {{ Form::close() }}
            </div>
        </div>
    </div>
</div>



<div id="editModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true" class="modal fade text-left">
    <div role="document" class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 id="exampleModalLabel" class="modal-title">{{trans('file.Update Shareholder')}}</h5>
                <button type="button" data-dismiss="modal" aria-label="Close" class="close"><span aria-hidden="true"><i class="dripicons-cross"></i></span></button>
            </div>
            <div class="modal-body">
              <p class="italic"><small>{{trans('file.The field labels marked with * are required input fields')}}.</small></p>
                {!! Form::open(['route' => ['shareholders.update', 1], 'method' => 'put', 'files' => true]) !!}
                <div class="row">
                    <div class="col-md-6 form-group">
                        <input type="hidden" name="employee_id" />
                        <label>{{trans('file.name')}} *</label>
                        <input type="text" name="name" required class="form-control">
                    </div>  
                    <div class="col-md-6 form-group">
                        <label>{{trans('file.Image')}}</label>
                        <input type="file" name="image" class="form-control">
                    </div>
                     
                    <div class="col-md-6 form-group">
                        <label>{{trans('file.Email')}} *</label>
                        <input type="email" name="email" required class="form-control">
                    </div>
                    <div class="col-md-6 form-group">
                        <label>{{trans('file.Phone Number')}} *</label>
                        <input type="text" name="phone_number" required class="form-control">
                    </div>
                    <!-- <div class="col-md-6 form-group">
                        <label>Share *</label>
                        <input type="text" name="share" required class="form-control">
                    </div> -->
                    <div class="col-md-6 form-group">
                        <label>{{trans('file.Address')}}</label>
                        <input type="text" name="address" class="form-control">
                    </div>
                    <div class="col-md-6 form-group">
                        <label>{{trans('file.City')}}</label>
                        <input type="text" name="city" class="form-control">
                    </div>
                    <div class="col-md-6 form-group">
                        <label>{{trans('file.Country')}}</label>
                        <input type="text" name="country" class="form-control">
                    </div>
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

    $("ul#share").siblings('a').attr('aria-expanded','true');
    $("ul#share").addClass("show");
    $("ul#share #shareholder-menu").addClass("active");

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

    $(document).on('click', '.edit-btn', function() {
        $("#editModal input[name='employee_id']").val( $(this).data('id') );
        $("#editModal input[name='name']").val( $(this).data('name') );
        $("#editModal input[name='share']").val( $(this).data('share') );
        $("#editModal select[name='department_id']").val( $(this).data('department_id') );
        $("#editModal input[name='email']").val( $(this).data('email') );
        $("#editModal input[name='phone_number']").val( $(this).data('phone_number') );
        $("#editModal input[name='address']").val( $(this).data('address') );
        $("#editModal input[name='city']").val( $(this).data('city') );
        $("#editModal input[name='country']").val( $(this).data('country') );
        $('.selectpicker').selectpicker('refresh');
    });

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
                                url:'shareholders/deletebyselection',
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


    $(document).on("click", "table.shareholder tbody .withdraw-share", function(event) {
        $("#cheque").hide();
        $("#mobile").hide();
        $(".card-element").hide();
        $('select[name="paid_by_id"]').val(1);
        rowindex = $(this).closest('tr').index();
        var shareholder_id = $(this).data('id').toString();
        var balance = $('table.shareholder tbody tr:nth-child(' + (rowindex + 1) + ')').find('td:nth-child(6)').text();
        balance = parseFloat(balance.replace(/,/g, ''));
        $('input[name="amount"]').val(balance);
        $('input[name="balance"]').val(balance);
        $('input[name="paying_amount"]').val(balance);
        $('input[name="share_holder_id"]').val(shareholder_id);
        
        // Set minimum and maximum values for the amount input field
        $('input[name="amount"]').attr('min', 0);
        $('input[name="amount"]').attr('max', balance);
            // Reset the value to the maximum if user enters a greater value manually
        $('input[name="amount"]').on('input', function() {
            var enteredValue = parseFloat($(this).val());
            if (enteredValue > balance) {
            $(this).val(balance);
            }
        });

        // Reset the value to the minimum if user enters a lessthan value manually
        $('input[name="amount"]').on('input', function() {
            var enteredValue = parseFloat($(this).val());
            if (enteredValue < 0) {
            $(this).val(0);
            }
        });
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


    $(document).on("click", "table.shareholder tbody .registration-fee", function(event) {
        $("#cheque").hide();
        $("#mobile").hide();
        $(".card-element").hide();
        $('select[name="add_payment_paid_by_id"]').val(1);
        rowindex = $(this).closest('tr').index();
        var shareholder_id = $(this).data('id').toString();
        // var balance = $('table.shareholder tbody tr:nth-child(' + (rowindex + 1) + ')').find('td:nth-child(6)').text();
        // balance = parseFloat(balance.replace(/,/g, ''));
        $('input[name="amount"]').val(0);
        // $('input[name="balance"]').val(balance);
        // $('input[name="paying_amount"]').val(balance);
        $('input[name="share_holder_id"]').val(shareholder_id);

        // Set minimum and maximum values for the amount input field
        $('input[name="amount"]').attr('min', 0);
        // Reset the value to the minimum if user enters a lessthan value manually
        $('input[name="amount"]').on('input', function() {
            var enteredValue = parseFloat($(this).val());
            if (enteredValue < 0) {
            $(this).val(0);
            }
        });
    });


    $(document).on("click", "table.shareholder tbody .add-payment", function(event) {
        $("#cheque").hide();
        $("#mobile").hide();
        $(".card-element").hide();
        $('select[name="add_payment_paid_by_id"]').val(1);
        rowindex = $(this).closest('tr').index();
        var shareholder_id = $(this).data('id').toString();
        var balance = $('table.shareholder tbody tr:nth-child(' + (rowindex + 1) + ')').find('td:nth-child(6)').text();
        balance = parseFloat(balance.replace(/,/g, ''));
        $('input[name="amount"]').val(0);
        $('input[name="balance"]').val(0);
        $('input[name="paying_amount"]').val(0);
        $('input[name="share_holder_id"]').val(shareholder_id);

        // Set minimum and maximum values for the amount input field
        $('input[name="amount"]').attr('min', 0);
            // Reset the value to the maximum if user enters a greater value manually
        $('input[name="amount"]').on('input', function() {
            var enteredValue = parseFloat($(this).val());
            if (enteredValue < 0) {
            $(this).val(0);
            }
        });

        // // Reset the value to the minimum if user enters a lessthan value manually
        // $('input[name="amount"]').on('input', function() {
        //     var enteredValue = parseFloat($(this).val());
        //     if (enteredValue < 0) {
        //     $(this).val(0);
        //     }
        // });
    });
 


    $('select[name="add_payment_paid_by_id"]').on("change", function() {
        var id = $('select[name="add_payment_paid_by_id"]').val();
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


    

    $(document).on("click", "table.shareholder tbody .withdraw-dividend", function(event) {
        $("#dividend-cheque").hide();
        $("#dividend-mobile").hide();
        $(".card-element").hide();
        $('select[name="dividend_paid_by_id"]').val(1);
        rowindex = $(this).closest('tr').index();
        var share_holder_id = $(this).data('id').toString();
        var balance = $('table.shareholder tbody tr:nth-child(' + (rowindex + 1) + ')').find('td:nth-child(8)').text();
        balance = parseFloat(balance.replace(/,/g, ''));
        $('input[name="amount"]').val(balance);
        $('input[name="balance"]').val(balance);
        $('input[name="paying_amount"]').val(balance);
        $('input[name="share_holder_id"]').val(share_holder_id);

        // Set minimum and maximum values for the amount input field
        $('input[name="amount"]').attr('min', 0);
        $('input[name="amount"]').attr('max', balance);
            // Reset the value to the maximum if user enters a greater value manually
        $('input[name="amount"]').on('input', function() {
            var enteredValue = parseFloat($(this).val());
            if (enteredValue > balance) {
            $(this).val(balance);
            }
        });

        // Reset the value to the minimum if user enters a lessthan value manually
        $('input[name="amount"]').on('input', function() {
            var enteredValue = parseFloat($(this).val());
            if (enteredValue < 0) {
            $(this).val(0);
            }
        });
    });


    $('select[name="dividend_paid_by_id"]').on("change", function() {
        var id = $('select[name="dividend_paid_by_id"]').val();
        $('input[name="dividend-cheque_no"]').attr('required', false);
        $('input[name="mbtn_no"]').attr('required', false);
        $(".payment-form").off("submit");
        if (id == 3) {
            $.getScript( "public/vendor/stripe/checkout.js" );
            $(".card-element").show();
            $("#dividend-cheque").hide();
            $("#dividend-mobile").hide();
        } else if (id == 4) {
            $("#dividend-cheque").show();
            $("#dividend-mobile").hide();
            $(".card-element").hide();
            $('input[name="dividend-cheque_no"]').attr('required', true);
            $('input[name="mbtn_no"]').attr('required', false);    
        } 
        else if (id == 11) {
            $("#dividend-cheque").hide();
            $("#dividend-mobile").show();

            $(".gift-card").hide();
            $(".card-element").hide();
            $('input[name="dividend-cheque_no"]').attr('required', false);
            $('input[name="mbtn_no"]').attr('required', true);
        } 
        else if (id == 12) {
            $("#dividend-cheque").hide();
            $("#dividend-mobile").hide();

            $(".gift-card").hide();
            $(".card-element").hide();
            $('input[name="dividend-cheque_no"]').attr('required', false);
            $('input[name="mbtn_no"]').attr('required', false);
        } else {
            $(".card-element").hide();
            $("#dividend-cheque").hide();
            $("#dividend-mobile").hide();
        }
    });

    
</script>
@endpush
