@extends('layout.main') @section('content')
<section class="forms">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header d-flex align-items-center">
                        <h4>Add Shareholder</h4>
                    </div>
                    <div class="card-body">
                        <p class="italic"><small>{{trans('file.The field labels marked with * are required input fields')}}.</small></p>
                        {!! Form::open(['route' => 'shareholders.store', 'method' => 'post', 'files' => true]) !!}
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Full Name *</strong> </label>
                                    <input type="text" name="shareholder_name" required class="form-control" placeholder="eg. Abreham Gebremedin Debele">
                                </div>
                                <div class="form-group">
                                    <label>{{trans('file.Image')}}</label>
                                    <input type="file" name="image" class="form-control">
                                    @if($errors->has('image'))
                                   <span>
                                       <strong>{{ $errors->first('image') }}</strong>
                                    </span>
                                    @endif
                                </div>
                            
                                <div class="form-group">
                                    <label>{{trans('file.Email')}} *</label>
                                    <input type="email" name="email" placeholder="example@example.com" required class="form-control">
                                    @if($errors->has('email'))
                                   <span>
                                       <strong>{{ $errors->first('email') }}</strong>
                                    </span>
                                    @endif
                                </div>

                                <div class="form-group">
                                    <label>{{trans('file.Phone Number')}} *</label>
                                    <input type="text" name="phone_number" required class="form-control">
                                </div>

                            </div>

                            <div class="col-md-6">
                      
                                
                                <!-- <div class="form-group">
                                    <label>Share By Birr *</label>
                                    <input  type="number" value="0" required step="any" placeholder="eg. 100000"   name="deduction" required class="form-control"/>
                                </div> -->

                                <div class="form-group">
                                    <label>{{trans('file.Address')}}</label>
                                    <input type="text" name="address" class="form-control" placeholder="03 Kebele, Eresha sub city">
                                </div>
                                <div class="form-group">
                                    <label>{{trans('file.City')}}</label>
                                    <input type="text" name="city" class="form-control">
                                </div>
                                <div class="form-group">
                                    <label>{{trans('file.Country')}}</label>
                                    <input type="text" name="country" class="form-control">
                                </div>
                            </div>
                        
                            <div class="col-md-12">
                                <div class="form-group mt-4">
                                    <input type="submit" value="{{trans('file.submit')}}" class="btn btn-primary">
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

@push('scripts')
<script type="text/javascript">
     $("ul#share").siblings('a').attr('aria-expanded','true');
    $("ul#share").addClass("show");
    $("ul#share #shareholder-menu").addClass("active");

    $('#warehouse').hide();
    $('#biller').hide();

    $('input[name="user"]').on('change', function() {
        if ($(this).is(':checked')) {
            $('#user-input').show(400);
            $('input[name="name"]').prop('required',true);
            $('input[name="password"]').prop('required',true);
            $('select[name="role_id"]').prop('required',true);
        }
        else{
            $('#user-input').hide(400);
            $('input[name="name"]').prop('required',false);
            $('input[name="password"]').prop('required',false);
            $('select[name="role_id"]').prop('required',false);
            $('select[name="warehouse_id"]').prop('required',false);
            $('select[name="biller_id"]').prop('required',false);
        }
    });

    $('select[name="role_id"]').on('change', function() {
        if($(this).val() > 2){
            $('#warehouse').show(400);
            $('#biller').show(400);
            $('select[name="warehouse_id"]').prop('required',true);
            $('select[name="biller_id"]').prop('required',true);
        }
        else{
            $('#warehouse').hide(400);
            $('#biller').hide(400);
            $('select[name="warehouse_id"]').prop('required',false);
            $('select[name="biller_id"]').prop('required',false);
        }
    });
</script>
@endpush
