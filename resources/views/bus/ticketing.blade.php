@extends('layout.main')
@section('content')

@if(session()->has('not_permitted'))
  <div class="alert alert-danger alert-dismissible text-center">
    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
      <span aria-hidden="true">&times;</span>
    </button>
    {{ session()->get('not_permitted') }}
  </div>
@endif
<section class="forms">
  <div class="container-fluid">
    <div class="row">
      <div class="col-md-12">
        <div class="card">
          <div class="card-header d-flex align-items-center">
            <h4>Ticketing</h4>
          </div>
          <div class="card-body">
            <p class="italic"><small>{{ trans('file.The field labels marked with * are required input fields') }}.</small></p>
            @if ($routes->isEmpty())
            <p>There is no Registered Route</p>
            @else
            <div class="row">
              <div class="col-md-6 form-group">
                <label for="routeDropdown">Select Arival City:</label>
                <select id="routeDropdown" class="selectpicker form-control" required data-live-search="true"
                  data-live-search-style="begins" title="Select Arival City here...">
                  @foreach($routes as $route)
                  <option value="{{ $route->id }}">{{ $route->arrivalCity->name }}</option>
                  @endforeach
                </select>
              </div>

              <div class="col-md-6 form-group">
              <label for=""></label>
              <label for=""></label>
               <button style="display: none;" id="reloadButton" class="btn btn-primary" onclick="reloadPage()">Change Bus</button>

              </div>
            </div>
            <div id="invoiceContainer"></div>

            <!-- Add a new div to display bus details and seats -->
            <div id="busDetails" style="display: none;">
            <input type="hidden"id="seatn" name="seatn">

              <h5>Bus Details:</h5>
              <p id="busInfo"></p>
              <h5>Seat Numbers:</h5>
              <div id="seatButtons"></div>

            </div>

            <div id="nobusDetails" style="display: none;">
               
              <p id="nobusInfo"></p>
             </div>
            <div id="printableInvoice" style="display: none;">
                <!-- Content of printable invoice -->
                <div>
                    <h2>Invoice</h2>
                    <p id="invoiceContent"></p>
                </div>
                <button id="printButton" class="btn btn-primary">Print Invoice</button>
            </div>
            <script>
              const routeDropdown = document.getElementById('routeDropdown');
              const busDetails = document.getElementById('busDetails');
              const nobusDetails = document.getElementById('nobusDetails');
              const busInfo = document.getElementById('busInfo');
              const seatn = document.getElementById('seatn');
              const nobusInfo = document.getElementById('nobusInfo');
              const seatButtons = document.getElementById('seatButtons');
              const printableInvoice = document.getElementById('printableInvoice');
                                    const printButton = document.getElementById('printButton');
              routeDropdown.addEventListener('change', () => {
                const selectedRouteId = routeDropdown.value;
                // Replace with your logic to fetch bus details and seat numbers
                getBusDetails(selectedRouteId);
              });



              function getBusDetails(selectedRouteId) {
    // Make an AJAX request to fetch bus details and seat numbers
         fetch(`get-bus-details/${selectedRouteId}`)
        .then(response => response.json())
        .then(data => {
            if (data) {
                busDetails.style.display = 'none';
                nobusDetails.style.display = 'none';
                 busInfo.textContent = data.busInfo;
                seatn.value = data.Capacity;
                seatButtons.innerHTML = ''; // Clear existing buttons

                const table = document.createElement('table');
                table.className = 'table';

                let row = document.createElement('tr');

                data.seats.forEach((seat, index) => {
                    if (index > 0 && index % 7 === 0) {
                        // Create a new row after every 7 cells
                        table.appendChild(row);
                        row = document.createElement('tr');
                    }

                    const cell = document.createElement('td');
                    const seatButton = document.createElement('button');
                    seatButton.textContent = seat.seatNumber;
                    seatButton.className = 'btn btn-primary';
                    seatButton.id = `seatButton_${seat.seatNumber}`; // Set a unique id for each button

                    // Disable the seat button if it's not active
                    if (!seat.isActive) {
                        seatButton.disabled = true;
                    }

                    seatButton.addEventListener('click', () => {
                        // Handle seat selection
                        // Example: seatSelected(selectedRouteId, seat.seatNumber);

                        // Generate a ticket here
                        generateTicket(data.queueId, seat.seatNumber);
                        disableSeatButton(seat.seatNumber);


                    });

                    cell.appendChild(seatButton);
                    row.appendChild(cell);
                });

                // Append the last row if it has fewer than 7 cells
                if (row.childElementCount > 0) {
                    table.appendChild(row);
                }

                // Append the table to the seatButtons container
                seatButtons.appendChild(table);

                // Display the bus details and seats
                busDetails.style.display = 'block';
            } else {
                // Hide the bus details if no data is available
                busDetails.style.display = 'none';
                nobusInfo.textContent = "No bus queue data available in this route";
                nobusDetails.style.display = 'block';            }

            if ( data.length === 0) {
                busDetails.style.display = 'none';
                nobusInfo.textContent = "No bus queue data available in this route";
                nobusDetails.style.display = 'block';


            }
        })
        .catch(error => {
            console.error('Error fetching bus details:', error);
        });
}

 

// ... Your existing code ...

function generateTicket(queueId, seatNumber) {
    // Make an AJAX request to generate a ticket and invoice
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    fetch(`generate-ticket`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken, // Include the CSRF token in the headers

        },
        body: JSON.stringify({
            queueId: queueId,
            seatNumber: seatNumber,
        }),
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
             // Display a success message or perform other actions
            alert('Invoice generated successfully');

            // Format the invoice content with CSS styling
 
            // Create a new window with the generated invoice content
          // Find an element where you want to append the invoice content (e.g., a <div> with an id "invoiceContainer")
            



                                // Create an iframe element
                    const iframe = document.createElement('iframe');

                    // Set some styles to hide the iframe
                    iframe.style.position = 'absolute';
                    iframe.style.left = '-9999px';

                    // Append the iframe to the document body
                    document.body.appendChild(iframe);

                    // Get the iframe's content window and document
                    const iframeWindow = iframe.contentWindow;
                    const iframeDocument = iframeWindow.document;

                    // Write the invoice content to the iframe's document
                    iframeDocument.open();
                    iframeDocument.write(`
                        <style>
                            /* Apply CSS to fill the whole page */
                            
                            
                                /* Your existing styles here */
                                /* Apply CSS to fill the whole page */
                                body {
                                    margin: 0;
                                    padding: 0;
                                }
                                .invoice {
                                    position: absolute;
                                    top: 0;
                                    left: 0;
                                    width: 100%;
                                    height: 100%;
                                    display: flex;
                                    flex-direction: column;
                                    justify-content: center;
                                    align-items: center;
                                    /* Add additional styling as needed */
                                }
                            

                        @media print {
                        ::-webkit-print-cancel-button {
                            display: none;
                        }
                    }
                        </style>
                        
             
                <div class="invoice">
                <img src="/eticketing/public/logo/${data.site_logo}" height="42" width="50" style="filter: brightness(0);">
                    <h2>${data.company_name}</h2>
                    <p>Date: ${new Date(data.date).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' })} 
                    </br>=========================== 

                    </br>Plate Number: ${data.busNumber} 
                    </br>Seat Number: ${data.seatNumber} 
                    </br>Route: ${data.routeInfo} 
                    </br>=========================== 
                    </br>Price: ${data.price} 
                    </br>CommissionFee: ${data.commissionFee} 
                    </br>Total: ${data.total} 
                    </br>===========================</p>

                    <p>TicketOfficer: ${data.ticketOfficerEmail}</p>
                </div>
            
                    `);
                    iframeDocument.close();
                    // Print the iframe's content
                    iframeWindow.print();

                    // Remove the iframe from the document
                    document.body.removeChild(iframe);

                    checkSeatButton()


        } else {
            // Display an error message or perform error handling
            alert('Error generating invoice');
        }
    })
    .catch(error => {
        console.error('Error generating invoice:', error);
    });
}

function printInvoice(invoice) {
    // Replace this with your logic to display and print the invoice
    // You can create a printable invoice popup or use a library like jsPDF
    // Example: Print invoice using jsPDF
    const doc = new jsPDF();
    doc.text(invoice, 10, 10);
    doc.autoPrint();
    doc.output('dataurlnewwindow');
}

function disableSeatButton(seatNumber) {
    // Disable the seat button once the ticket is generated
    const seatButton = document.getElementById(`seatButton_${seatNumber}`);
    if (seatButton) {
        seatButton.disabled = true;
    }
}

function reloadPage() {
    window.location.reload();
}


function checkSeatButton() {
    // Disable the seat button once the ticket is generated
    const reloadButton = document.getElementById('reloadButton');
    const seatn = document.getElementById('seatn');
    var flag =1;
    for (let index = 1; index < seatn.value; index++) {
        const seatButton = document.getElementById(`seatButton_${index}`);
        if (!seatButton.disabled) {
            flag=0;
            
        }

        
    }

 
    if (flag) {
        reloadButton.style.display = 'block';            
     }
}
// ... Rest of your code ...






              
            </script>

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
  // Additional scripts can be added here if needed
</script>
@endpush
