<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Auth::routes();

Route::group(['middleware' => 'auth'], function() {
	Route::get('/dashboard', 'HomeController@dashboard');
});

Route::group(['middleware' => ['auth', 'active']], function() {
	Route::get('/', 'HomeController@index');
	Route::get('switch-theme/{theme}', 'HomeController@switchTheme')->name('switchTheme');
	Route::get('/dashboard-filter/{start_date}/{end_date}', 'HomeController@dashboardFilter');
	Route::get('check-batch-availability/{product_id}/{batch_no}/{warehouse_id}', 'ProductController@checkBatchAvailability');

	Route::get('language_switch/{locale}', 'LanguageController@switchLanguage');

	Route::get('role/permission/{id}', 'RoleController@permission')->name('role.permission');
	Route::post('role/set_permission', 'RoleController@setPermission')->name('role.setPermission');
	Route::resource('role', 'RoleController');

	Route::post('importunit', 'UnitController@importUnit')->name('unit.import');
	Route::post('unit/deletebyselection', 'UnitController@deleteBySelection');
	Route::get('unit/lims_unit_search', 'UnitController@limsUnitSearch')->name('unit.search');
	Route::resource('unit', 'UnitController');

	Route::post('category/import', 'CategoryController@import')->name('category.import');
	Route::post('category/deletebyselection', 'CategoryController@deleteBySelection');
	Route::post('category/category-data', 'CategoryController@categoryData');
	Route::resource('category', 'CategoryController');

	Route::post('importbrand', 'BrandController@importBrand')->name('brand.import');
	Route::post('brand/deletebyselection', 'BrandController@deleteBySelection');
	Route::get('brand/lims_brand_search', 'BrandController@limsBrandSearch')->name('brand.search');
	Route::resource('brand', 'BrandController');

	Route::post('importsupplier', 'SupplierController@importSupplier')->name('supplier.import');
	Route::post('supplier/deletebyselection', 'SupplierController@deleteBySelection');
	Route::get('supplier/lims_supplier_search', 'SupplierController@limsSupplierSearch')->name('supplier.search');
	Route::resource('supplier', 'SupplierController');

	Route::post('importwarehouse', 'WarehouseController@importWarehouse')->name('warehouse.import');
	Route::post('warehouse/deletebyselection', 'WarehouseController@deleteBySelection');
	Route::get('warehouse/lims_warehouse_search', 'WarehouseController@limsWarehouseSearch')->name('warehouse.search');
	Route::resource('warehouse', 'WarehouseController');

	Route::post('importtax', 'TaxController@importTax')->name('tax.import');
	Route::post('tax/deletebyselection', 'TaxController@deleteBySelection');
	Route::get('tax/lims_tax_search', 'TaxController@limsTaxSearch')->name('tax.search');
	Route::resource('tax', 'TaxController');

	//Route::get('products/getbarcode', 'ProductController@getBarcode');
	Route::post('products/product-data', 'ProductController@productData');
	Route::get('products/gencode', 'ProductController@generateCode');
	Route::get('products/search', 'ProductController@search');
	Route::get('products/saleunit/{id}', 'ProductController@saleUnit');
	Route::get('products/getdata/{id}/{variant_id}', 'ProductController@getData');
	Route::get('products/product_warehouse/{id}', 'ProductController@productWarehouseData');
	Route::post('importproduct', 'ProductController@importProduct')->name('product.import');
	Route::post('exportproduct', 'ProductController@exportProduct')->name('product.export');
	Route::get('products/print_barcode','ProductController@printBarcode')->name('product.printBarcode');
	Route::get('products/lims_product_search', 'ProductController@limsProductSearch')->name('product.search');
	Route::post('products/deletebyselection', 'ProductController@deleteBySelection');
	Route::post('products/update', 'ProductController@updateProduct');
	Route::get('products/variant-data/{id}','ProductController@variantData');
	Route::resource('products', 'ProductController');

	Route::post('importcustomer_group', 'CustomerGroupController@importCustomerGroup')->name('customer_group.import');
	Route::post('customer_group/deletebyselection', 'CustomerGroupController@deleteBySelection');
	Route::get('customer_group/lims_customer_group_search', 'CustomerGroupController@limsCustomerGroupSearch')->name('customer_group.search');
	Route::resource('customer_group', 'CustomerGroupController');

	Route::resource('discount-plans', 'DiscountPlanController');
	Route::resource('discounts', 'DiscountController');
	Route::get('discounts/product-search/{code}', 'DiscountController@productSearch');

	Route::post('importcustomer', 'CustomerController@importCustomer')->name('customer.import');
	Route::get('customer/getDeposit/{id}', 'CustomerController@getDeposit');
	Route::post('customer/add_deposit', 'CustomerController@addDeposit')->name('customer.addDeposit');
	Route::post('customer/update_deposit', 'CustomerController@updateDeposit')->name('customer.updateDeposit');
	Route::post('customer/deleteDeposit', 'CustomerController@deleteDeposit')->name('customer.deleteDeposit');
	Route::post('customer/deletebyselection', 'CustomerController@deleteBySelection');
	Route::get('customer/lims_customer_search', 'CustomerController@limsCustomerSearch')->name('customer.search');
	Route::post('customers/clear-due', 'CustomerController@clearDue')->name('customer.clearDue');
	Route::resource('customer', 'CustomerController');

	Route::post('importbiller', 'BillerController@importBiller')->name('biller.import');
	Route::post('biller/deletebyselection', 'BillerController@deleteBySelection');
	Route::get('biller/lims_biller_search', 'BillerController@limsBillerSearch')->name('biller.search');
	Route::resource('biller', 'BillerController');

	Route::post('sales/sale-data', 'SaleController@saleData');
	Route::post('sales/sendmail', 'SaleController@sendMail')->name('sale.sendmail');
	Route::get('sales/sale_by_csv', 'SaleController@saleByCsv');
	Route::get('sales/product_sale/{id}','SaleController@productSaleData');
	Route::post('importsale', 'SaleController@importSale')->name('sale.import');
	Route::get('pos', 'SaleController@posSale')->name('sale.pos');
	Route::get('sales/lims_sale_search', 'SaleController@limsSaleSearch')->name('sale.search');
	Route::get('sales/lims_product_search', 'SaleController@limsProductSearch')->name('product_sale.search');
	Route::get('sales/getcustomergroup/{id}', 'SaleController@getCustomerGroup')->name('sale.getcustomergroup');
	Route::get('sales/getproduct/{id}', 'SaleController@getProduct')->name('sale.getproduct');
	Route::get('sales/getproduct/{category_id}/{brand_id}', 'SaleController@getProductByFilter');
	Route::get('sales/getfeatured', 'SaleController@getFeatured');
	Route::get('sales/get_gift_card', 'SaleController@getGiftCard');
	Route::get('sales/paypalSuccess', 'SaleController@paypalSuccess');
	Route::get('sales/paypalPaymentSuccess/{id}', 'SaleController@paypalPaymentSuccess');
	Route::get('sales/gen_invoice/{id}', 'SaleController@genInvoice')->name('sale.invoice');
	Route::post('sales/add_payment', 'SaleController@addPayment')->name('sale.add-payment');
	Route::get('sales/getpayment/{id}', 'SaleController@getPayment')->name('sale.get-payment');
	Route::post('sales/updatepayment', 'SaleController@updatePayment')->name('sale.update-payment');
	Route::post('sales/secondhand', 'SaleController@store2')->name('sales.store2');
	Route::post('sales/deletepayment', 'SaleController@deletePayment')->name('sale.delete-payment');
	Route::get('sales/{id}/create', 'SaleController@createSale');
	Route::post('sales/deletebyselection', 'SaleController@deleteBySelection');
	Route::get('sales/print-last-reciept', 'SaleController@printLastReciept')->name('sales.printLastReciept');
	Route::get('sales/today-sale', 'SaleController@todaySale');
	Route::get('sales/today-profit/{warehouse_id}', 'SaleController@todayProfit');
	Route::get('sales/check-discount', 'SaleController@checkDiscount');
	Route::resource('sales', 'SaleController');
	Route::get('secondhand/sale', 'SaleController@secondHandList')->name('sales.secondhand');
	Route::get('secondhand/sales', 'SaleController@createsecondhandsale')->name('sales.createsecondhand');


	Route::get('delivery', 'DeliveryController@index')->name('delivery.index');
	Route::get('delivery/product_delivery/{id}','DeliveryController@productDeliveryData');
	Route::get('delivery/create/{id}', 'DeliveryController@create');
	Route::post('delivery/store', 'DeliveryController@store')->name('delivery.store');
	Route::post('delivery/sendmail', 'DeliveryController@sendMail')->name('delivery.sendMail');
	Route::get('delivery/{id}/edit', 'DeliveryController@edit');
	Route::post('delivery/update', 'DeliveryController@update')->name('delivery.update');
	Route::post('delivery/deletebyselection', 'DeliveryController@deleteBySelection');
	Route::post('delivery/delete/{id}', 'DeliveryController@delete')->name('delivery.delete');

	Route::post('quotations/quotation-data', 'QuotationController@quotationData')->name('quotations.data');
	Route::get('quotations/product_quotation/{id}','QuotationController@productQuotationData');
	Route::get('quotations/lims_product_search', 'QuotationController@limsProductSearch')->name('product_quotation.search');
	Route::get('quotations/getcustomergroup/{id}', 'QuotationController@getCustomerGroup')->name('quotation.getcustomergroup');
	Route::get('quotations/getproduct/{id}', 'QuotationController@getProduct')->name('quotation.getproduct');
	Route::get('quotations/{id}/create_sale', 'QuotationController@createSale')->name('quotation.create_sale');
	Route::get('quotations/{id}/create_purchase', 'QuotationController@createPurchase')->name('quotation.create_purchase');
	Route::post('quotations/sendmail', 'QuotationController@sendMail')->name('quotation.sendmail');
	Route::post('quotations/deletebyselection', 'QuotationController@deleteBySelection');
	Route::resource('quotations', 'QuotationController');

	Route::post('purchases/purchase-data', 'PurchaseController@purchaseData')->name('purchases.data');
	Route::get('purchases/product_purchase/{id}','PurchaseController@productPurchaseData');
	Route::get('purchases/lims_product_search', 'PurchaseController@limsProductSearch')->name('product_purchase.search');
	Route::post('purchases/add_payment', 'PurchaseController@addPayment')->name('purchase.add-payment');
	Route::get('purchases/getpayment/{id}', 'PurchaseController@getPayment')->name('purchase.get-payment');
	Route::post('purchases/updatepayment', 'PurchaseController@updatePayment')->name('purchase.update-payment');
	Route::post('purchases/deletepayment', 'PurchaseController@deletePayment')->name('purchase.delete-payment');

	Route::post('purchases/payment/reject/{id}', 'PurchaseController@rejectupdatePayment')->name('purchasespayment.reject');
	Route::post('purchases/payment/approve/{id}', 'PurchaseController@approveUdatePayment')->name('purchasespayment.approve');

	Route::get('purchases/purchase_by_csv', 'PurchaseController@purchaseByCsv');
	Route::post('importpurchase', 'PurchaseController@importPurchase')->name('purchase.import');
	Route::post('purchases/deletebyselection', 'PurchaseController@deleteBySelection');
	Route::resource('purchases', 'PurchaseController');
	Route::post('purchases/payment/restore/{id}', 'PurchaseController@rejectdeletePayment')->name('purchases.paymentrestore');
	
	Route::post('purchases/payment/Permanentdestroy/{id}', 'PurchaseController@approvedeletePayment')->name('purchases.paymentpermanentdestroy');
 
	Route::post('purchases/reject/{id}', 'PurchaseController@reject')->name('purchases.reject');
	Route::post('purchases/approve/{id}', 'PurchaseController@approve')->name('purchases.approve');


	Route::post('transfers/transfer-data', 'TransferController@transferData')->name('transfers.data');
	Route::get('transfers/product_transfer/{id}','TransferController@productTransferData');
	Route::get('transfers/transfer_by_csv', 'TransferController@transferByCsv');
	Route::post('importtransfer', 'TransferController@importTransfer')->name('transfer.import');
	Route::get('transfers/getproduct/{id}', 'TransferController@getProduct')->name('transfer.getproduct');
	Route::get('transfers/lims_product_search', 'TransferController@limsProductSearch')->name('product_transfer.search');
	Route::post('transfers/deletebyselection', 'TransferController@deleteBySelection');
	Route::resource('transfers', 'TransferController');

	Route::get('qty_adjustment/getproduct/{id}', 'AdjustmentController@getProduct')->name('adjustment.getproduct');
	Route::get('qty_adjustment/lims_product_search', 'AdjustmentController@limsProductSearch')->name('product_adjustment.search');
	Route::post('qty_adjustment/deletebyselection', 'AdjustmentController@deleteBySelection');
	Route::resource('qty_adjustment', 'AdjustmentController');

	Route::post('return-sale/return-data', 'ReturnController@returnData');
	Route::get('return-sale/getcustomergroup/{id}', 'ReturnController@getCustomerGroup')->name('return-sale.getcustomergroup');
	Route::post('return-sale/sendmail', 'ReturnController@sendMail')->name('return-sale.sendmail');
	Route::get('return-sale/getproduct/{id}', 'ReturnController@getProduct')->name('return-sale.getproduct');
	Route::get('return-sale/lims_product_search', 'ReturnController@limsProductSearch')->name('product_return-sale.search');
	Route::get('return-sale/product_return/{id}','ReturnController@productReturnData');
	Route::post('return-sale/deletebyselection', 'ReturnController@deleteBySelection');
	Route::resource('return-sale', 'ReturnController');

	Route::post('return-purchase/return-data', 'ReturnPurchaseController@returnData');
	Route::get('return-purchase/getcustomergroup/{id}', 'ReturnPurchaseController@getCustomerGroup')->name('return-purchase.getcustomergroup');
	Route::post('return-purchase/sendmail', 'ReturnPurchaseController@sendMail')->name('return-purchase.sendmail');
	Route::get('return-purchase/getproduct/{id}', 'ReturnPurchaseController@getProduct')->name('return-purchase.getproduct');
	Route::get('return-purchase/lims_product_search', 'ReturnPurchaseController@limsProductSearch')->name('product_return-purchase.search');
	Route::get('return-purchase/product_return/{id}','ReturnPurchaseController@productReturnData');
	Route::post('return-purchase/deletebyselection', 'ReturnPurchaseController@deleteBySelection');
	Route::resource('return-purchase', 'ReturnPurchaseController');

	Route::get('report/product_quantity_alert', 'ReportController@productQuantityAlert')->name('report.qtyAlert');
	Route::get('report/daily-sale-objective', 'ReportController@dailySaleObjective')->name('report.dailySaleObjective');
	Route::post('report/daily-sale-objective-data', 'ReportController@dailySaleObjectiveData');
	Route::get('report/product-expiry', 'ReportController@productExpiry')->name('report.productExpiry');
	Route::get('report/warehouse_stock', 'ReportController@warehouseStock')->name('report.warehouseStock');
	Route::post('report/warehouse_stock', 'ReportController@warehouseStockById')->name('report.warehouseStock');
	Route::get('report/daily_sale/{year}/{month}', 'ReportController@dailySale');
	Route::post('report/daily_sale/{year}/{month}', 'ReportController@dailySaleByWarehouse')->name('report.dailySaleByWarehouse');
	Route::get('report/monthly_sale/{year}', 'ReportController@monthlySale');
	Route::post('report/monthly_sale/{year}', 'ReportController@monthlySaleByWarehouse')->name('report.monthlySaleByWarehouse');
	Route::get('report/daily_purchase/{year}/{month}', 'ReportController@dailyPurchase');
	Route::post('report/daily_purchase/{year}/{month}', 'ReportController@dailyPurchaseByWarehouse')->name('report.dailyPurchaseByWarehouse');
	Route::get('report/monthly_purchase/{year}', 'ReportController@monthlyPurchase');
	Route::post('report/monthly_purchase/{year}', 'ReportController@monthlyPurchaseByWarehouse')->name('report.monthlyPurchaseByWarehouse');
	Route::get('report/best_seller', 'ReportController@bestSeller');
	Route::post('report/best_seller', 'ReportController@bestSellerByWarehouse')->name('report.bestSellerByWarehouse');
	Route::post('report/profit_loss', 'ReportController@profitLoss')->name('report.profitLoss');
	Route::get('report/product_report', 'ReportController@productReport')->name('report.product');
	Route::post('report/product_report_data', 'ReportController@productReportData');
	Route::post('report/purchase', 'ReportController@purchaseReport')->name('report.purchase');
	Route::post('report/sale_report', 'ReportController@saleReport')->name('report.sale');
	Route::post('report/sale-report-chart', 'ReportController@saleReportChart')->name('report.saleChart');
	Route::post('report/payment_report_by_date', 'ReportController@paymentReportByDate')->name('report.paymentByDate');
	Route::post('report/warehouse_report', 'ReportController@warehouseReport')->name('report.warehouse');
	Route::post('report/user_report', 'ReportController@userReport')->name('report.user');
	Route::post('report/customer_report', 'ReportController@customerReport')->name('report.customer');
	Route::post('report/supplier', 'ReportController@supplierReport')->name('report.supplier');
	Route::post('report/due-report', 'ReportController@dueReportByDate')->name('report.dueByDate');

	Route::get('user/profile/{id}', 'UserController@profile')->name('user.profile');
	Route::put('user/update_profile/{id}', 'UserController@profileUpdate')->name('user.profileUpdate');
	Route::put('user/changepass/{id}', 'UserController@changePassword')->name('user.password');
	Route::get('user/genpass', 'UserController@generatePassword');
	Route::post('user/deletebyselection', 'UserController@deleteBySelection');
	Route::resource('user','UserController');

	Route::get('setting/general_setting', 'SettingController@generalSetting')->name('setting.general');
	Route::post('setting/general_setting_store', 'SettingController@generalSettingStore')->name('setting.generalStore');
	
	Route::get('setting/reward-point-setting', 'SettingController@rewardPointSetting')->name('setting.rewardPoint');
	Route::post('setting/reward-point-setting_store', 'SettingController@rewardPointSettingStore')->name('setting.rewardPointStore');

	Route::get('backup', 'SettingController@backup')->name('setting.backup');
	Route::get('setting/general_setting/change-theme/{theme}', 'SettingController@changeTheme');
	Route::get('setting/mail_setting', 'SettingController@mailSetting')->name('setting.mail');
	Route::get('setting/sms_setting', 'SettingController@smsSetting')->name('setting.sms');
	Route::get('setting/createsms', 'SettingController@createSms')->name('setting.createSms');
	Route::post('setting/sendsms', 'SettingController@sendSms')->name('setting.sendSms');
	Route::get('setting/hrm_setting', 'SettingController@hrmSetting')->name('setting.hrm');
	Route::post('setting/hrm_setting_store', 'SettingController@hrmSettingStore')->name('setting.hrmStore');
	Route::post('setting/mail_setting_store', 'SettingController@mailSettingStore')->name('setting.mailStore');
	Route::post('setting/sms_setting_store', 'SettingController@smsSettingStore')->name('setting.smsStore');
	Route::get('setting/pos_setting', 'SettingController@posSetting')->name('setting.pos');
	Route::post('setting/pos_setting_store', 'SettingController@posSettingStore')->name('setting.posStore');
	Route::get('setting/empty-database', 'SettingController@emptyDatabase')->name('setting.emptyDatabase');

	
	Route::get('expense_categories/gencode', 'ExpenseCategoryController@generateCode');
	Route::post('expense_categories/import', 'ExpenseCategoryController@import')->name('expense_category.import');
	Route::post('expense_categories/deletebyselection', 'ExpenseCategoryController@deleteBySelection');
	Route::resource('expense_categories', 'ExpenseCategoryController');



	Route::post('expenses/expense-data', 'ExpenseController@expenseData')->name('expenses.data');
	Route::post('expenses/deletebyselection', 'ExpenseController@deleteBySelection');
	Route::resource('expenses', 'ExpenseController');
	Route::post('expenses/reject/{id}', 'ExpenseController@reject')->name('expenses.reject');
	Route::post('expenses/approve/{id}', 'ExpenseController@approve')->name('expenses.approve');

 	Route::post('buses/deletebyselection', 'BusController@deleteBySelection');
	Route::resource('buses', 'BusController');
	Route::post('buses/reject/{id}', 'BusController@reject')->name('buses.reject');
	Route::post('buses/approve/{id}', 'BusController@approve')->name('buses.approve');
	Route::get('buses/create-que/{routeId}', 'BusController@create');
	Route::get('buses/assign-route/{routeId}', 'BusController@assign');


	Route::post('queue/add', 'QueueController@addToQueue')->name('queue.add');
	Route::get('queue/remove', 'QueueController@removeFromQueue')->name('queue.remove');
	Route::resource('queue', 'QueueController');
	Route::post('queue/restore/{id}', 'QueueController@restore')->name('queue.restore');
	Route::get('ticket/get-bus-details/{routeId}', 'TicketController@getBusDetails');
	Route::post('ticket/generate-ticket', 'TicketController@generateTicket')->name('ticket.generate');
	Route::get('ticket/generate-ticket/{queueId}', 'TicketController@generateTicket');

	Route::post('queue/Permanentdestroy/{id}', 'QueueController@Permanentdestroy')->name('queue.Permanentdestroy');
	
	
	Route::resource('ticket', 'TicketController');



	Route::post('cities/deletebyselection', 'CityController@deleteBySelection');
	Route::resource('cities', 'CityController');
	Route::post('cities/reject/{id}', 'CityController@reject')->name('cities.reject');
	Route::post('cities/approve/{id}', 'CityController@approve')->name('cities.approve');

	Route::post('routes1/deletebyselection', 'RouteController@deleteBySelection');
	Route::resource('routes1', 'RouteController');
	Route::post('routes1/reject/{id}', 'RouteController@reject')->name('routes1.reject');
	Route::post('routes1/approve/{id}', 'RouteController@approve')->name('routes1.approve');
	Route::post('routes1/add', 'RouteController@assign')->name('routes1.add');



  	Route::post('fixed_asset_categories/deletebyselection', 'FixedAssetCategoryController@deleteBySelection');
	  Route::post('fixed_asset_categories/reject/{id}', 'FixedAssetCategoryController@reject')->name('fixed_asset_categories.reject');
	  Route::post('fixed_asset_categories/approve/{id}', 'FixedAssetCategoryController@approve')->name('fixed_asset_categories.approve');

	Route::resource('fixed_asset_categories', 'FixedAssetCategoryController');




	Route::post('fixed_asset/fixed_asset-data', 'FixedAssetController@fixed_assetData')->name('fixed_asset.data');
	Route::post('fixed_asset/deletebyselection', 'FixedAssetController@deleteBySelection');
	Route::resource('fixed_asset', 'FixedAssetController');
	Route::post('fixed_asset/reject/{id}', 'FixedAssetController@reject')->name('fixed_asset.reject');
	Route::post('fixed_asset/approve/{id}', 'FixedAssetController@approve')->name('fixed_asset.approve');

  
	Route::post('fixed_asset/payment/reject/{id}', 'FixedAssetController@rejectupdatePayment')->name('fixed_assetpayment.reject');
	Route::post('fixed_asset/payment/approve/{id}', 'FixedAssetController@approveUdatePayment')->name('fixed_assetpayment.approve');

	Route::post('fixed_asset/add_payment', 'FixedAssetController@addPayment')->name('fixed_asset.add-payment');
	Route::get('fixed_asset/getpayment/{id}', 'FixedAssetController@getPayment')->name('fixed_asset.get-payment');
	Route::post('fixed_asset/updatepayment', 'FixedAssetController@updatePayment')->name('fixed_asset.update-payment');
	Route::post('fixed_asset/deletepayment', 'FixedAssetController@deletePayment')->name('fixed_asset.delete-payment');
 
	Route::post('fixed_asset/payment/restore/{id}', 'FixedAssetController@rejectdeletePayment')->name('fixed_asset.paymentrestore');
	
	Route::post('fixed_asset/payment/Permanentdestroy/{id}', 'FixedAssetController@approvedeletePayment')->name('fixed_asset.paymentpermanentdestroy');
 

	Route::post('fixed_asset/restore/{id}', 'FixedAssetController@restore')->name('fixed_asset.restore');
	
	Route::post('fixed_asset/Permanentdestroy/{id}', 'FixedAssetController@Permanentdestroy')->name('fixed_asset.Permanentdestroy');
 

    
	Route::post('prepaid_rent/prepaid_rent-data', 'PrePaidRentController@prepaid_rentData')->name('prepaid_rent.data');
	Route::post('prepaid_rent/deletebyselection', 'PrePaidRentController@deleteBySelection');
	Route::resource('prepaid_rent', 'PrePaidRentController');
	Route::post('prepaid_rent/reject/{id}', 'PrePaidRentController@reject')->name('prepaid_rent.reject');
	Route::post('prepaid_rent/approve/{id}', 'PrePaidRentController@approve')->name('prepaid_rent.approve');	
	Route::post('prepaid_rent/restore/{id}', 'PrePaidRentController@restore')->name('prepaid_rent.restore');
	
	Route::post('prepaid_rent/Permanentdestroy/{id}', 'PrePaidRentController@Permanentdestroy')->name('prepaid_rent.Permanentdestroy');
 


	Route::post('activity_log/activity_log-data', 'ActivityLogController@activity_logData')->name('activity_log.data');	 
	Route::post('activity_log/log_history-data', 'ActivityLogController@history_activity_logData')->name('log_history.data');
	Route::get('log_history', 'ActivityLogController@log_history')->name('log_history');
 	Route::resource('activity_log', 'ActivityLogController');


	Route::get('gift_cards/gencode', 'GiftCardController@generateCode');
	Route::post('gift_cards/recharge/{id}', 'GiftCardController@recharge')->name('gift_cards.recharge');
	Route::post('gift_cards/deletebyselection', 'GiftCardController@deleteBySelection');
	Route::resource('gift_cards', 'GiftCardController');

	Route::get('coupons/gencode', 'CouponController@generateCode');
	Route::post('coupons/deletebyselection', 'CouponController@deleteBySelection');
	Route::resource('coupons', 'CouponController');
	//accounting routes
	Route::get('accounts/make-default/{id}', 'AccountsController@makeDefault');
	Route::get('accounts/balancesheet', 'AccountsController@balanceSheet')->name('accounts.balancesheet');
	Route::post('accounts/account-statement', 'AccountsController@accountStatement')->name('accounts.statement');
	Route::resource('accounts', 'AccountsController');
	Route::resource('money-transfers', 'MoneyTransferController');
	//HRM routes
	Route::post('departments/deletebyselection', 'DepartmentController@deleteBySelection');
	Route::resource('departments', 'DepartmentController');
	
	Route::post('employees/deletebyselection', 'EmployeeController@deleteBySelection');
	Route::resource('employees', 'EmployeeController');

	Route::post('shareholders/deletebyselection', 'ShareholderController@deleteBySelection');
	Route::post('shareholders/add_registration_fee', 'ShareholderController@addregistrationfee')->name('shareholder.add-registration-fee');
	Route::post('shareholders/withdraw_share', 'ShareholderController@withdrawshare')->name('shareholder.withdraw-share');
	Route::post('shareholders/withdraw_dividend', 'ShareholderController@withdrawdividend')->name('shareholder.withdraw-dividend');
	Route::post('shareholders/add_share', 'ShareholderController@addshare')->name('shareholder.add-share');
	Route::get('shareholders/gen_invoice/{id}', 'ShareholderController@genInvoice')->name('shareholders.invoice');
	Route::get('shareholders/gen_detail/{id}', 'ShareholderController@gendetail')->name('shareholders.detail');
	Route::get('shareholders/getpayment', 'ShareholderController@getPayment')->name('shareholders.payment');

	Route::resource('shareholders', 'ShareholderController');

	Route::post('payroll/payroll/payroll-data', 'PayrollController@payrollData');
	Route::get('payroll/payroll/monthly_payroll/{id}','PayrollController@monthlyPayrollData');

	Route::post('payroll/deletebyselection', 'PayrollController@deleteBySelection');
	Route::get('payroll/indexone', 'PayrollController@indexone')->name('payroll.indexone');
	Route::get('payroll/destroyone', 'PayrollController@payrolldestroy')->name('payroll.destroyone');
	Route::get('payroll/create_payroll', 'PayrollController@create')->name('payroll.create_payroll');
	Route::post('payroll/storepayroll', 'PayrollController@storeone')->name('payroll.storepayroll');;


	Route::resource('perdime', 'PayrollController');

	Route::post('attendance/deletebyselection', 'AttendanceController@deleteBySelection');
	Route::resource('attendance', 'AttendanceController');

	Route::resource('stock-count', 'StockCountController');
	Route::post('stock-count/finalize', 'StockCountController@finalize')->name('stock-count.finalize');
	Route::get('stock-count/stockdif/{id}', 'StockCountController@stockDif');
	Route::get('stock-count/{id}/qty_adjustment', 'StockCountController@qtyAdjustment')->name('stock-count.adjustment');

	Route::post('holidays/deletebyselection', 'HolidayController@deleteBySelection');
	Route::get('approve-holiday/{id}', 'HolidayController@approveHoliday')->name('approveHoliday');
	Route::get('holidays/my-holiday/{year}/{month}', 'HolidayController@myHoliday')->name('myHoliday');
	Route::resource('holidays', 'HolidayController');

	Route::get('cash-register', 'CashRegisterController@index')->name('cashRegister.index');
	Route::get('cash-register/check-availability/{warehouse_id}', 'CashRegisterController@checkAvailability')->name('cashRegister.checkAvailability');
	Route::post('cash-register/store', 'CashRegisterController@store')->name('cashRegister.store');
	Route::get('cash-register/getDetails/{id}', 'CashRegisterController@getDetails');
	Route::get('cash-register/showDetails/{warehouse_id}', 'CashRegisterController@showDetails');
	Route::post('cash-register/close', 'CashRegisterController@close')->name('cashRegister.close');

	Route::get('notifications', 'NotificationController@index')->name('notifications.index');
	Route::post('notifications/store', 'NotificationController@store')->name('notifications.store');
	Route::get('notifications/mark-as-read', 'NotificationController@markAsRead');

	Route::resource('currency', 'CurrencyController');

	Route::get('/home', 'HomeController@index')->name('home');
	Route::get('my-transactions/{year}/{month}', 'HomeController@myTransaction');
	Route::get('accounttransaction/general_ledger', 'AccountTransactionController@generalLedger')->name('accounttransaction.ledger');
	Route::post('accounttransaction/general_ledger_data', 'AccountTransactionController@generalLedgerData');

	Route::get('accounttransaction/general_journal', 'AccountTransactionController@journalEntries')->name('accounttransaction.journal');
	Route::post('accounttransaction/general_journal_data', 'AccountTransactionController@journalEntriesData');
	Route::get('accounts/taccount/{id}', 'AccountsController@taccount');
	Route::get('accounts/taccount2/{id}', 'AccountsController@taccount2');
	
	
	Route::get('accounttransaction/financial_statement', 'AccountTransactionController@financialStatement')->name('accounttransaction.financialstatement');
	Route::post('accounttransaction/balance_sheet', 'AccountTransactionController@balanceSheet');
	Route::post('accounttransaction/trial_balance', 'AccountTransactionController@trialBalance');

	Route::get('accounttransaction/income_statement', 'AccountTransactionController@Income_Statement')->name('accounttransaction.Income_Statement');
	Route::get('accounttransaction/balance_sheet', 'AccountTransactionController@Balance_Sheet')->name('accounttransaction.Balance_Sheet');
	Route::post('accounttransaction/close_account', 'AccountTransactionController@Close_Account')->name('accounttransaction.Close_Account');
	Route::get('accounttransaction/trial_balance', 'AccountTransactionController@Trial_Balance')->name('accounttransaction.Trial_Balance');
	
	Route::post('accounttransaction/income_statement', 'AccountTransactionController@incomeStatement');

	Route::post('shareholders/share-data', 'ShareholderController@shareData')->name('share.data');
	Route::post('shareholders/deletebyselection', 'ShareholderController@deleteBySelection');
	Route::resource('shareholders', 'ShareholderController');
 




	
	Route::post('warehousepurchases/warehouse_purchase-data', 'WarehousePurchaseController@warehousepurchaseData')->name('warehousepurchases.data');
	Route::get('warehousepurchases/product_warehouse_purchase/{id}','WarehousePurchaseController@warehouseproductPurchaseData');
	Route::get('warehousepurchases/lims_product_search', 'WarehousePurchaseController@limsProductSearch')->name('warehouse_product_purchase.search');
	Route::post('warehousepurchases/add_payment', 'WarehousePurchaseController@addPayment')->name('warehousepurchases.add-payment');
	Route::get('warehousepurchases/getpayment/{id}', 'WarehousePurchaseController@getPayment')->name('warehousepurchases.get-payment');
	Route::post('warehousepurchases/updatepayment', 'WarehousePurchaseController@updatePayment')->name('warehousepurchases.update-payment');
	Route::post('warehousepurchases/deletepayment', 'WarehousePurchaseController@deletePayment')->name('warehousepurchases.delete-payment');
	Route::get('warehousepurchases/purchase_by_csv', 'WarehousePurchaseController@purchaseByCsv');
	Route::post('importpurchase', 'WarehousePurchaseController@importPurchase')->name('purchase.import');
	Route::post('warehousepurchases/deletebyselection', 'WarehousePurchaseController@deleteBySelection');
	Route::resource('warehousepurchases', 'WarehousePurchaseController');


	Route::post('transfer-purchase/transfer-data', 'PurchaseTransferController@transferData');
	Route::get('transfer-purchase/getcustomergroup/{id}', 'PurchaseTransferController@getCustomerGroup')->name('transfer-purchase.getcustomergroup');
	Route::post('transfer-purchase/sendmail', 'PurchaseTransferController@sendMail')->name('transfer-purchase.sendmail');
	Route::get('transfer-purchase/getproduct/{id}', 'PurchaseTransferController@getProduct')->name('transfer-purchase.getproduct');
	Route::get('transfer-purchase/lims_product_search', 'PurchaseTransferController@limsProductSearch')->name('product_transfer-purchase.search');
	Route::get('transfer-purchase/purchase_transfer/{id}','PurchaseTransferController@purchaseTransfer');
	Route::post('transfer-purchase/deletebyselection', 'PurchaseTransferController@deleteBySelection');
	Route::resource('transfer-purchase', 'PurchaseTransferController');



	Route::get('chart_of_account/gencode', 'ChartofAccountController@generateCode');
 	Route::post('chart_of_account/import', 'ChartofAccountController@import')->name('chart_of_account.import');
	Route::post('chart_of_account/deletebyselection', 'ChartofAccountController@deleteBySelection');
	Route::resource('chart_of_accounts', 'ChartofAccountController');


	Route::post('transaction_adjustments/journal_history-data', 'AccountTransactionAdjustmentController@journalAdjustmentData')->name('transaction_adjustments.data');
	Route::post('transaction_adjustments/transaction_adjustments-data', 'AccountTransactionAdjustmentController@expenseData');
 	Route::post('transaction_adjustments/deletebyselection', 'AccountTransactionAdjustmentController@deleteBySelection');
	Route::resource('transaction_adjustments', 'AccountTransactionAdjustmentController');
	Route::get('transaction_adjustments/journal_entries/{id}','AccountTransactionAdjustmentController@journalData');
	Route::get('journal_entries','AccountTransactionAdjustmentController@index1')->name('journal_entries');
	

	Route::get('/barcode', 'BarcodeController@index')->name('home.index');
 
	Route::get('/service-worker.js', function () {
		return response()->file(public_path('service-worker.js'), [        'Content-Type' => 'text/javascript',    ]);
	});



});

