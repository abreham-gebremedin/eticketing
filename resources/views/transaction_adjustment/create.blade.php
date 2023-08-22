<script type="text/javascript">
    function clearThis(target) {
        target.value= "";
    }
</script>

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
                        <h4>Add Adjustment</h4>
                    </div>
                    <div class="card-body">
                        <p class="italic"><small>{{trans('file.The field labels marked with * are required input fields')}}.</small></p>
                        {!! Form::open(['route' => 'transaction_adjustments.store', 'method' => 'post', 'files' => true, 'id' => 'purchase-form']) !!}
                        <div class="row">
                            <div class="col-md-12">
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>{{trans('file.Date')}}</label>
                                            <input type="text" name="created_at" class="form-control date" placeholder="Choose date"/>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>{{trans('file.Warehouse')}} *</label>
                                            <select required name="warehouse_id" class="selectpicker form-control" data-live-search="true" title="Select warehouse...">
                                                @foreach($lims_warehouse_list as $warehouse)
                                                <option value="{{$warehouse->id}}">{{$warehouse->name}}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
           
                                    <div class="col-md-12 mt-3">
                                        <label>{{trans('file.Select Product')}}</label>
                                        <div class="search-box input-group">
                                            <button class="btn btn-secondary"><i class="fa fa-barcode"></i></button>
                                            <input type="text" name="product_code_name" id="lims_productcodeSearch" onfocus="clearThis(this)" placeholder="Please type Chart of Account name or  code and select..." class="form-control" />
                                        </div>
                                    </div>
                                </div>
                                <div class="row mt-4">
                                    <div class="col-md-12">
                                        <h5>Journal Entries*</h5>
                                        <div class="table-responsive mt-3">
                                            <table id="myTable" class="table table-hover order-list">
                                                <thead>
                                                    <tr>
                                                        <th>{{trans('file.Code')}}</th>
                                                        <th>{{trans('file.name')}}</th>
                                                        <th></th>
                                                        <th>Debit</th>
                                                        <th>Credit</th>
                                                        <th><i class="dripicons-trash"></i></th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                </tbody>
                                                <tfoot class="tfoot active">
                                                    <th colspan="2">{{trans('file.Total')}}</th>
                                                     <th ></th>
                                                     <th id="total-debit">0</th>
                                                    <th id="total-credit">0</th>
                                                    <th><i class="dripicons-trash"></i></th>
                                                </tfoot>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label>{{trans('file.Note')}}</label>
                                            <textarea rows="5" class="form-control" name="reason"  required></textarea>
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
@push('scripts')
<script type="text/javascript">

$("ul#transaction_adjustments").siblings('a').attr('aria-expanded','true');
    $("ul#transaction_adjustments").addClass("show");
    $("ul#transaction_adjustments #transaction_adjustments-create-menu").addClass("active");


    // array data depend on warehouse
    var product_code = [];
    var product_name = [];
    var product_qty = [];

    // array data with selection
    var product_cost = [];
    var product_discount = [];
    var tax_rate = [];
    var tax_name = [];
    var tax_method = [];
    var unit_name = [];
    var unit_operator = [];
    var unit_operation_value = [];
    var is_imei = [];

    // temporary array
    var temp_unit_name = [];
    var temp_unit_operator = [];
    var temp_unit_operation_value = [];

    var rowindex;
    var customer_group_rate;
    var row_product_cost;

    $('.selectpicker').selectpicker({
        style: 'btn-link',
    });

    $('[data-toggle="tooltip"]').tooltip();

    $('select[name="status"]').on('change', function() {
        if($('select[name="status"]').val() == 2){
            $(".recieved-product-qty").removeClass("d-none");
            $(".qty").each(function() {
                rowindex = $(this).closest('tr').index();
                $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.recieved').val($(this).val());
            });

        }
        else if(($('select[name="status"]').val() == 3) || ($('select[name="status"]').val() == 4)) {
            $(".recieved-product-qty").addClass("d-none");
            $(".recieved").each(function() {
                $(this).val(0);
            });
        }
        else {
            $(".recieved-product-qty").addClass("d-none");
            $(".qty").each(function() {
                rowindex = $(this).closest('tr').index();
                $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.recieved').val($(this).val());
            });
        }
    });

    <?php $productArray = []; ?>
    var lims_product_code = [
        
        @foreach($lims_product_list_with_variant as $product)
            <?php
                $productArray[] = htmlspecialchars($product->id) . '|' . htmlspecialchars($product->code) . '|' . preg_replace('/[\n\r]/', "<br>", htmlspecialchars($product->name));
            ?>
        @endforeach
        
        <?php
            echo  '"'.implode('","', $productArray).'"';
        ?>
    ];

    var lims_productcodeSearch = $('#lims_productcodeSearch');

    lims_productcodeSearch.autocomplete({
    source: function(request, response) {
        var matcher = new RegExp(".?" + $.ui.autocomplete.escapeRegex(request.term), "i");
        response($.grep(lims_product_code, function(item) {
            return matcher.test(item);
        }));
    },
    response: function(event, ui) {
        if (ui.content.length == 1) {
            var data = ui.content[0].value;
            $(this).autocomplete( "close" );
            
            productSearch(data);
 
        };
    },
    select: function(event, ui) {

        var data = ui.item.value;
        
        productSearch(data);
        $(this).val(''); 
        return false;
 
    }

    
 }); 


    $('body').on('focus',".expired-date", function() {
        $(this).datepicker({
            format: "yyyy-mm-dd",
            startDate: "<?php echo date("Y-m-d", strtotime('+ 1 days')) ?>",
            autoclose: true,
            todayHighlight: true
        });
    });



    //Change quantity
    $("#myTable").on('input', '.qty', function() {
        rowindex = $(this).closest('tr').index();
        if($(this).val() < 1 && $(this).val() != '') {
        $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ') .qty').val(1);
        alert("Quantity can't be less than 1");
        }
        checkQuantity($(this).val(), true);
        calculateTotal();

    });


    $("#myTable").on('input', '.debit', function() {
        rowindex = $(this).closest('tr').index();
        if($(this).val() < 0 && $(this).val() != '') {
        $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ') .debit').val(0);
        alert("debit can't be less than 0");
        }

        if($(this).val() > 0 && $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.credit').val()>0) {
        alert("Debit and credit can't be greater than 0 at the same time, therefore  one side should be zero");
        $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.credit').val(0);
        }

        calculateTotal();

    });



    $("#myTable").on('input', '.credit', function() {
        rowindex = $(this).closest('tr').index();
        if($(this).val() < 0 && $(this).val() != '') {
        $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ') .credit').val(1);
        alert("credit can't be less than 0");
        }
 
        if($(this).val() > 0 && $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.debit').val()>0) {
        alert("Debit and credit can't be greater than 0 at the same time, therefore  one side should be zero");
        $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.debit').val(0);
        }

        calculateTotal();

    
    });
    //Delete product
    $("table.order-list tbody").on("click", ".ibtnDel", function(event) {
        rowindex = $(this).closest('tr').index();
        product_cost.splice(rowindex, 1);
        product_discount.splice(rowindex, 1);
        tax_rate.splice(rowindex, 1);
        tax_name.splice(rowindex, 1);
        tax_method.splice(rowindex, 1);
        unit_name.splice(rowindex, 1);
        unit_operator.splice(rowindex, 1);
        unit_operation_value.splice(rowindex, 1);
        console.log(product_cost);
        $(this).closest("tr").remove();
        calculateTotal();
    });

    //Edit product
    $("table.order-list").on("click", ".edit-product", function() {
    rowindex = $(this).closest('tr').index();
    $(".imei-section").remove();
    if(is_imei[rowindex]) {
        var imeiNumbers = $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.imei-number').val();

        htmlText = '<div class="col-md-12 form-group imei-section"><label>IMEI or Serial Numbers</label><input type="text" name="imei_numbers" value="'+imeiNumbers+'" class="form-control imei_number" placeholder="Type imei or serial numbers and separate them by comma. Example:1001,2001" step="any"></div>';
        $("#editModal .modal-element").append(htmlText);
    }

    var row_product_name = $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('td:nth-child(1)').text();
    var row_product_code = $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('td:nth-child(2)').text();
    $('#modal-header').text(row_product_name + '(' + row_product_code + ')');

    var qty = $(this).closest('tr').find('.qty').val();
    $('input[name="edit_qty"]').val(qty);

    $('input[name="edit_discount"]').val(parseFloat(product_discount[rowindex]).toFixed(2));

    unitConversion();
    $('input[name="edit_unit_cost"]').val(row_product_cost.toFixed(2));

 
    temp_unit_name = (unit_name[rowindex]).split(',');
    temp_unit_name.pop();
    temp_unit_operator = (unit_operator[rowindex]).split(',');
    temp_unit_operator.pop();
    temp_unit_operation_value = (unit_operation_value[rowindex]).split(',');
    temp_unit_operation_value.pop();
    $('select[name="edit_unit"]').empty();
    $.each(temp_unit_name, function(key, value) {
        $('select[name="edit_unit"]').append('<option value="' + key + '">' + value + '</option>');
    });
    $('.selectpicker').selectpicker('refresh');
 });

    //Update product
    $('button[name="update_btn"]').on("click", function() {
        if(is_imei[rowindex]) {
            var imeiNumbers = $("#editModal input[name=imei_numbers]").val();
            $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.imei-number').val(imeiNumbers);
        }

        var edit_discount = $('input[name="edit_discount"]').val();
        var edit_qty = $('input[name="edit_qty"]').val();
        var edit_unit_cost = $('input[name="edit_unit_cost"]').val();
        if (parseFloat(edit_discount) > parseFloat(edit_unit_cost)) {
            alert('Invalid Discount Input!');
            return;
        }

        if(edit_qty < 1) {
            $('input[name="edit_qty"]').val(1);
            edit_qty = 1;
            alert("Quantity can't be less than 1");
        }

        var row_unit_operator = unit_operator[rowindex].slice(0, unit_operator[rowindex].indexOf(","));
        var row_unit_operation_value = unit_operation_value[rowindex].slice(0, unit_operation_value[rowindex].indexOf(","));
        row_unit_operation_value = parseFloat(row_unit_operation_value);

  
        if (row_unit_operator == '*') {
            product_cost[rowindex] = $('input[name="edit_unit_cost"]').val() / row_unit_operation_value;
        } else {
            product_cost[rowindex] = $('input[name="edit_unit_cost"]').val() * row_unit_operation_value;
        }

        product_discount[rowindex] = $('input[name="edit_discount"]').val();
        var position = $('select[name="edit_unit"]').val();
        var temp_operator = temp_unit_operator[position];
        var temp_operation_value = temp_unit_operation_value[position];
        $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.purchase-unit').val(temp_unit_name[position]);
        temp_unit_name.splice(position, 1);
        temp_unit_operator.splice(position, 1);
        temp_unit_operation_value.splice(position, 1);

        temp_unit_name.unshift($('select[name="edit_unit"] option:selected').text());
        temp_unit_operator.unshift(temp_operator);
        temp_unit_operation_value.unshift(temp_operation_value);

        unit_name[rowindex] = temp_unit_name.toString() + ',';
        unit_operator[rowindex] = temp_unit_operator.toString() + ',';
        unit_operation_value[rowindex] = temp_unit_operation_value.toString() + ',';
        checkQuantity(edit_qty, false);
    });

    function productSearch(data) {
        console.log(document.getElementById("lims_productcodeSearch").value);
          document.getElementById("lims_productcodeSearch").value="";
        const myarray= data.split("|");
        console.log(myarray[0]);

                var flag = 1;
                $(".product-code").each(function(i) {
                    if ($(this).val() == myarray[1]) {
                        rowindex = i;
                        var qty = parseFloat($('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ') .qty').val()) + 1;
                        $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ') .qty').val(qty);
                        if($('select[name="status"]').val() == 1 || $('select[name="status"]').val() == 1) {
                            $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ') .recieved').val(qty);
                        }
                        flag = 0;

                    }
                });


                if(flag){

                    var newRow = $("<tr>");
                    var cols = '';

                    cols += '<td>' + myarray[1] + '</td>';
                    cols += '<td>' + myarray[2] + '</td>';
                    cols += '<td><input type="hidden" class="form-control qty" name="qty[]" value="1" step="any" required/></td>';
                    cols += '<td><input type="number" class="form-control debit" name="debit[]" value="0" step="any" required/></td>';
                    cols += '<td><input type="number" class="form-control credit" name="credit[]" value="0" step="any" required/></td>';
                    

         
                    cols += '<td><button type="button" class="ibtnDel btn btn-md btn-danger">{{trans("file.delete")}}</button></td>';
                    cols += '<input type="hidden" class="product-code" name="product_code[]" value="' + myarray[1] + '"/>';
                    cols += '<input type="hidden" class="product-id" name="product_id[]" value="' + myarray[0] + '"/>';
                    cols += '<input type="hidden" class="product-id" name="product_name[]" value="' + myarray[2] + '"/>';
                    newRow.append(cols);
                    $("table.order-list tbody").prepend(newRow);

                    rowindex = newRow.index();
                    product_cost.splice(rowindex,0, parseFloat(data[2]));
                    product_discount.splice(rowindex,0, '0.00');
                    tax_rate.splice(rowindex,0, parseFloat(data[3]));
                    tax_name.splice(rowindex,0, data[4]);
                    tax_method.splice(rowindex,0, data[5]);
                    unit_name.splice(rowindex,0, data[6]);
                    unit_operator.splice(rowindex,0, data[7]);
                    unit_operation_value.splice(rowindex,0, data[8]);
                    is_imei.splice(rowindex, 0, data[11]);
                    calculateRowProductData(1);
                    if(data[11]) {
                        $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.edit-product').click();
                    }
                }


        $.ajax({
            
        });
    }


    
    function checkdebitcredit(type) {
        $("#myTable").on('input', '.'+type, function() {
        rowindex = $(this).closest('tr').index();
        
       var balance= $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ') .'+type).val();
        return balance;
    });

    }
    function checkQuantity(purchase_qty, flag) {
        var row_product_code = $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('td:nth-child(2)').text();
        var pos = product_code.indexOf(row_product_code);
        var operator = unit_operator[rowindex].split(',');
        var operation_value = unit_operation_value[rowindex].split(',');
        if(operator[0] == '*')
            total_qty = purchase_qty * operation_value[0];
        else if(operator[0] == '/')
            total_qty = purchase_qty / operation_value[0];

        $('#editModal').modal('hide');
        $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.qty').val(purchase_qty);
        var status = $('select[name="status"]').val();
        if(status == '1' || status == '2' )
            $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.recieved').val(purchase_qty);
        else
            $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.recieved').val(0);
        calculateRowProductData(purchase_qty);
    }

    function calculateRowProductData(quantity) {
    

     }

 

    function calculateTotal() {
        //Sum of quantity
        var total_qty = 0;
        $(".qty").each(function() {

            if ($(this).val() == '') {
                total_qty += 0;
            } else {
                total_qty += parseFloat($(this).val());
            }
        });
        $("#total-qty").text(total_qty);
        $('input[name="total_qty"]').val(total_qty);



        var total_credit = 0;
        $(".credit").each(function() {

            if ($(this).val() == '') {
                total_credit += 0;
            } else {
                total_credit += parseFloat($(this).val());
            }
        });
        $("#total-credit").text(total_credit);





        var total_debit = 0;
        $(".debit").each(function() {

            if ($(this).val() == '') {
                total_debit += 0;
            } else {
                total_debit += parseFloat($(this).val());
            }
        });
        $("#total-debit").text(total_debit);
    }

    function calculateGrandTotal() {

        var item = $('table.order-list tbody tr:last').index();

        var total_qty = parseFloat($('#total-qty').text());
        var subtotal = parseFloat($('#total').text());
        var order_tax = parseFloat($('select[name="order_tax_rate"]').val());
        var order_discount = parseFloat($('input[name="order_discount"]').val());
        var shipping_cost = parseFloat($('input[name="shipping_cost"]').val());

        if (!order_discount)
            order_discount = 0.00;
        if (!shipping_cost)
            shipping_cost = 0.00;

        item = ++item + '(' + total_qty + ')';
        order_tax = (subtotal - order_discount) * (order_tax / 100);
        var grand_total = (subtotal + order_tax + shipping_cost) - order_discount;

        $('#item').text(item);
        $('input[name="item"]').val($('table.order-list tbody tr:last').index() + 1);
        $('#subtotal').text(subtotal.toFixed(2));
        $('#order_tax').text(order_tax.toFixed(2));
        $('input[name="order_tax"]').val(order_tax.toFixed(2));
        $('#order_discount').text(order_discount.toFixed(2));
        $('#shipping_cost').text(shipping_cost.toFixed(2));
        $('#grand_total').text(grand_total.toFixed(2));
        $('input[name="grand_total"]').val(grand_total.toFixed(2));
    }

    $('input[name="order_discount"]').on("input", function() {
        calculateGrandTotal();
    });

    $('input[name="shipping_cost"]').on("input", function() {
        calculateGrandTotal();
    });

    $('select[name="order_tax_rate"]').on("change", function() {
        calculateGrandTotal();
    });

    $(window).keydown(function(e){
        if (e.which == 13) {
            var $targ = $(e.target);
            if (!$targ.is("textarea") && !$targ.is(":button,:submit")) {
                var focusNext = false;
                $(this).find(":input:visible:not([disabled],[readonly]), a").each(function(){
                    if (this === e.target) {
                        focusNext = true;
                    }
                    else if (focusNext){
                        $(this).focus();
                        return false;
                    }
                });
                return false;
            }
        }
    });

    $('#purchase-form').on('submit',function(e){
    var rownumber = $('table.order-list tbody tr:last').index();
    if (rownumber < 0) {
        alert("Please insert product to order table!")
        e.preventDefault();
    }

    else if($('select[name="status"]').val() != 1)
    {
        flag = 0;
        $(".qty").each(function() {
            rowindex = $(this).closest('tr').index();
            quantity =  $(this).val();
            recieved = $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.recieved').val();

            if(quantity != recieved){
                flag = 1;
                return false;
            }
        });
        if(!flag){
            alert('Quantity and Recieved value is same! Please Change Purchase Status or Recieved value');
            e.preventDefault();
        }
        else
            $(".batch-no, .expired-date").prop('disabled', false);
    }
    else {
        $(".batch-no, .expired-date").prop('disabled', false);
        $("#submit-btn").prop('disabled', true);
    }
 });
</script>

<script type="text/javascript" src="https://js.stripe.com/v3/"></script>
@endpush
