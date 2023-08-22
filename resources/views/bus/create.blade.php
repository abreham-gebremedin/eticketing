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
                        <h4>Add Bus to Queue List</h4>
                    </div>
                    <div class="card-body">
                        <p class="italic"><small>{{trans('file.The field labels marked with * are required input fields')}}.</small></p>
                        @if ($filteredBuses->isEmpty())
                            <p>All Buses in this Route are on Que.</p>
                        @else
                        <div class="row">
                        <div class="col-md-6 form-group">
                            <label for="busDropdown">Select a Bus:</label>
                            <select id="busDropdown" class="selectpicker form-control" required data-live-search="true" data-live-search-style="begins" title="Select Bus...">
                                @foreach($filteredBuses as $bus)
                                    <option value="{{ $bus->id }}">{{ $bus->BusNumber }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-1 form-group d-flex align-items-end">
                            <button id="addButton" class="button form-control">Add</button>
                        </div>
                    </div>
                    <ul id="selectedBuses"></ul>

                    <div class="row">
                        <div class="col-md-6 form-group">
                            <form id="queueForm" action="{{ route('queue.add') }}" method="post">
                            @csrf
                            <input type="hidden" name="route_id" value="{{$routeId}}">

                                <button type="button" id="submitButton" class="button form-control">Submit to Queue</button>
                            </form>
                        </div>
                    </div>
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
    let selectedBuses = [];

    const busDropdown = document.getElementById('busDropdown');
    const addButton = document.getElementById('addButton');
    const submitButton = document.getElementById('submitButton');
    const selectedBusesList = document.getElementById('selectedBuses');
    let counter = 1;

    addButton.addEventListener('click', function() {
        const selectedBusId = busDropdown.value;
        const selectedBusText = busDropdown.options[busDropdown.selectedIndex].text;

        if (selectedBusId && !selectedBuses.includes(selectedBusId)) {
            selectedBuses.push(selectedBusId);
            updateSelectedBusesList();
            counter++;

            // Show the submit button when at least one bus is added
            if (selectedBuses.length > 0) {
                submitButton.style.display = 'block';
            }
        }
    });

    function updateSelectedBusesList() {
        selectedBusesList.innerHTML = '';
        selectedBuses.forEach((busId, index) => {
            const selectedBusText = getSelectedBusText(busId);
            selectedBusesList.innerHTML += `<li>${index + 1}.      ${selectedBusText} <button class="removeButton btn btn-danger" data-id="${busId}">X</button></li>`;
        });
    }

    function getSelectedBusText(busId) {
        const selectedBusOption = busDropdown.querySelector(`[value="${busId}"]`);
        return selectedBusOption ? selectedBusOption.text : '';
    }

    selectedBusesList.addEventListener('click', function(event) {
        if (event.target.classList.contains('removeButton')) {
            const busIdToRemove = event.target.getAttribute('data-id');
            selectedBuses = selectedBuses.filter(busId => busId !== busIdToRemove);
            updateSelectedBusesList();
            counter--;

            // Hide the submit button if no buses are left
            if (selectedBuses.length === 0) {
                submitButton.style.display = 'none';
            }
        }
    });

    submitButton.addEventListener('click', function() {
        const form = document.getElementById('queueForm');
        selectedBuses.forEach((busId, index) => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = `selectedBuses[${index}]`;
            input.value = busId;
            form.appendChild(input);
        });
        form.submit();
    });
</script>


@endpush
