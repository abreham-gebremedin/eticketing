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
                        <h4>Assign Ticket Officer to Route</h4>
                    </div>
                    <div class="card-body">
                        <p class="italic"><small>{{trans('file.The field labels marked with * are required input fields')}}.</small></p>
                        @if ($lims_user_list->isEmpty())
                            <p>No user.</p>
                        @else
                        {!! Form::open(['route' => ['routes1.update', 1], 'method' => 'put', 'files' => true]) !!}
                        <div class="row">
                        <div class="col-md-6 form-group">
                            <label for="userDropdown">Select a User:</label>
                            <select id="userDropdown" name="TicketOfficerID" class="selectpicker form-control" required data-live-search="true" data-live-search-style="begins" title="Select Bus...">
                                @foreach($lims_user_list as $user)
                                    <option value="{{ $user->id }}">{{ $user->name }}</option>
                                @endforeach
                            </select>
                        </div>
                         
                    </div>
                    

                    <div class="row">
                        <div class="col-md-6 form-group">
                             @csrf
                            <input type="hidden" name="route_id" value="{{$routeId}}">

                                <button type="submit"  class="button form-control">Submit</button>
                        </div>
                    </div>
                    {{ Form::close() }}

                     @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>



@endsection

@push('scripts')
<script>
    
</script>


@endpush
