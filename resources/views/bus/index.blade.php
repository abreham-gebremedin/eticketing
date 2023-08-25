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
@if(in_array("buses-add", $all_permission))
            <div class="container-fluid">
            <button class="btn btn-info" data-toggle="modal" data-target="#bus-modal"><i class="dripicons-plus"></i> Add Bus</button>
    </div>
        @endif


    <div class="table-responsive">
        <table id="employee-table" class="table bus" >
            <thead>
                <tr>
                    <th class="not-exported"></th>
                     <th>Plate Number</th>
                    <th>Capacity</th>
                    <th>Route</th>
                   <th class="not-exported">{{trans('file.action')}}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($lims_buses_all as $key=>$bus)
                <tr data-id="{{$bus->id}}">
                    <td>{{$key}}</td>
                    
                    <td>{{ $bus->BusNumber }}</td>
                    <td>{{ $bus->Capacity}}</td>
                    @if ($bus->route)
                         <td>{{ $bus->route->departureCity->name}} -> {{ $bus->route->arrivalCity->name}}</td>
                    @else
                    <td>No route</td>
                     @endif
 
                     <td>
                     <div class="btn-group">
                            <button type="button" class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">{{trans('file.action')}}
                                <span class="caret"></span>
                                <span class="sr-only">Toggle Dropdown</span>
                            </button>
                            <ul class="dropdown-menu edit-options dropdown-menu-right dropdown-default" user="menu">
                            
                             
                            
                            @if(in_array("buses-edit", $all_permission))
                                <li>
                                    <button type="button" data-id="{{$bus->id}}" data-share="{{$bus->BusNumber}}" data-capacity="{{$bus->Capacity}}"  class="edit-btn btn btn-link" data-toggle="modal" data-target="#editModal"><i class="dripicons-document-edit"></i> {{trans('file.edit')}}</button>
                                </li>
                                @endif
                                <li class="divider"></li>
                               
                            </ul>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</section>



 

<div id="editModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true" class="modal fade text-left">
    <div role="document" class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 id="exampleModalLabel" class="modal-title">Update bus</h5>
                <button type="button" data-dismiss="modal" aria-label="Close" class="close"><span aria-hidden="true"><i class="dripicons-cross"></i></span></button>
            </div>
            <div class="modal-body">
              <p class="italic"><small>{{trans('file.The field labels marked with * are required input fields')}}.</small></p>
                {!! Form::open(['route' => ['buses.update', 1], 'method' => 'put', 'files' => true]) !!}
                <div class="row">
                    <div class="col-md-6 form-group">
                        <input type="hidden" name="BusID" />
                        <label >Plate Number *</label>
                        <label for="BusNumber"></label>
                     </div>  
                    
                    <div class="col-md-6 form-group">
                         <label>Capacity *</label>
                        <input type="text" name="Capacity" required class="form-control">
                    </div>  
                    <?php
                        $lims_route_list=  DB::table('routes')
                        ->join('cities as departureCity', 'routes.DepartureCity', '=', 'departureCity.id')
                        ->join('cities as arrivalCity', 'routes.ArrivalCity', '=', 'arrivalCity.id')
                        ->select('routes.*', 'departureCity.name as departureCityName', 'arrivalCity.name as arrivalCityName')
                        ->where('warehouse_id',Auth::user()->warehouse_id)
                        ->get();
                    
                          ?>
                        <div class="col-md-6 form-group">
                            <label>Route *</label>
                            <select name="RouteID" class="selectpicker form-control"  data-live-search="true" data-live-search-style="begins" title="Select route...">
                                @foreach($lims_route_list as $route)
                                <option value="{{$route->id}}">{{$route->departureCityName}} ->{{$route->arrivalCityName}}</option>
                                @endforeach
                            </select>
                        </div>
                        <?php
                        $lims_warehouse_list = DB::table('warehouses')->where('is_active', true)->get();

                        ?>

                        <div class="col-md-6 form-group @if(\Auth::user()->role_id >= 2){{'d-none'}}@endif">
                                 <label><strong>Bus Station *</strong></label>
                                <select id="warehouse_id" name="warehouse_id" class="selectpicker form-control"  data-live-search="true" data-live-search-style="begins" title="Select Bus Station...">
                                     @foreach($lims_warehouse_list as $warehouse)
                                        <option value="{{$warehouse->id}}">{{$warehouse->name}}</option>
                                    @endforeach
                                </select>
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

    $("ul#bus").siblings('a').attr('aria-expanded','true');
    $("ul#bus").addClass("show");
    $("ul#bus #bus-menu").addClass("active");

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
        console.log($(this).data );
        $("#editModal input[name='BusID']").val( $(this).data('id') );
        $("#editModal label[for='BusNumber']").text($(this).data('share'));
        $("#editModal input[name='Capacity']").val( $(this).data('capacity') );
        
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
                'targets': [0, 1]
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
                                url:'buses/deletebyselection',
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


     

 
 
 

    
</script>
@endpush
