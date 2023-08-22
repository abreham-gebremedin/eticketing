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
                        <h4>{{trans('file.Add Purchase')}}</h4>
                    </div>
                    <div class="card-body">
                        <p class="italic"><small>{{trans('file.The field labels marked with * are required input fields')}}.</small></p>
                        {!! Form::open(['route' => 'purchases.store', 'method' => 'post', 'files' => true, 'id' => 'purchase-form']) !!}
                        <div class="row">
                            <div class="col-md-12"> 
                                <div class="row mt-4">
                                    <div class="col-md-12">
                                        <h5>{{trans('file.Order Table')}} *</h5>
                                        <div class="table-responsive mt-3">
                                            <table id="myTable" class="table table-hover order-list">
                                                <thead>
                                                    <tr>
                                                        <th>{{trans('file.name')}}</th>
                                                        <th>{{trans('file.Code')}}</th>
                                                        <th>{{trans('file.Quantity')}}</th>
                                                        <th class="recieved-product-qty d-none">{{trans('file.Recieved')}}</th>
                                                        <th>{{trans('file.Batch No')}}</th>
                                                         
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                </tbody>

                                                <tfoot class="tfoot active">
                                                    <th colspan="2">{{trans('file.Total')}}</th>
                                                    <th id="total-qty">0</th>
                                                    <th class="recieved-product-qty d-none"></th>
                                                    <th></th>
                                                    <th></th>
                                              
                                                
                                                </tfoot>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <button type="submit" class="btn btn-primary" id="submit-btn">{{trans('file.submit')}}</button>
                                </div>
                            </div>
                        </div>
                        {!! Form::close() !!}
                    </div>
                </div>
            </div>
        </div>
    </div>


     

</section>
@endsection


<script src="https://code.jquery.com/jquery-3.6.0.min.js"   ></script>

<script>
    
            console.log("dddddddddddddddddd");

 $(document).ready(function() {
  $.ajax({
    url:"/Invosale2/getdata",
    type:"GET",
    dataType:"json",
    success:function(data) {
        console.log(data[0]);
        console.log("dddddddddddddddddd");
      var table = $('#myTable').DataTable({
        data: data[0],
        columns: [
          { data: 'id' },
          { data: 'name' },
          { data: 'designation' },
          { data: 'salary' },
          { data: 'working_days' }

        ]
      });
    }
  });
});

</script>