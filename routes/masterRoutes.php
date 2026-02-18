<?php


Route::group(['prefix' => 'master' ], function () {
   Route::get('/', [MasterController::class, 'showMasterLoginPage'])->name('master.login.page');
   Route::post('/master-logout', [MasterAuthController::class, 'masterLogout'])->name('master.logout'); 
   Route::post('/master-login', [MasterAuthController::class, 'masterLogin'])->name('master.login');  
});

Route::group(['prefix' => 'master', 'middleware' => ['master.auth','master.admin'] ], function () {

    Route::get('/dashboard', [MasterController::class, 'showMasterDashboard'])->name('master.dashboard');
    Route::get('/support-center', [MasterController::class, 'showMasterSupportCenter'])->name('master.support.center');
    Route::get('/roles', [MasterController::class, 'showRolesView'])->name('master.roles');
    Route::get('/profile', [MasterController::class, 'showProfileView'])->name('master.profile');
    Route::get('/forgot-password', [MasterAuthController::class, 'forgotPasswordView'])->name('master.forgot.password');
    Route::post('/send-password-reset-link', [MasterAuthController::class, 'sendPasswordResetLink'])->name('master.password.reset.link');
    Route::get('/reset-password-view', [MasterAuthController::class, 'resetPasswordView'])->name('master.reset.password.view');
    Route::post('/submit-password-reset', [MasterAuthController::class, 'submitPasswordReset'])->name('master.submit.password.reset');


    Route::get('/events-view', [MasterController::class, 'showMasterEvents'])->name('master.events');
    Route::get('/events-data', [MasterController::class, 'fetchMasterEvents'])->name('master.fetch.events');
    Route::post('/event-create', [MasterController::class, 'storeMasterEvent'])->name('master.add.event');
    Route::post('/event-create-table', [MasterController::class, 'addEventForTableView'])->name('master.add.event.table');
    Route::post('/event-update/{id}', [MasterController::class, 'updateMasterEvent'])->name('master.update.event');
    Route::post('/event-delete/{id}', [MasterController::class, 'destroyMasterEvent'])->name('master.delete.event');
    Route::get('/events-table', [MasterController::class, 'showEventsTable'])->name('master.events.table');
    Route::post('/events-bulk-delete', [MasterController::class, 'bulkDeleteMasterEvents'])->name('master.bulk.delete.events');
    Route::post('/event-create-table', [MasterController::class, 'addEventViewTable'])->name('master.create.event.table');  
    

    Route::post('/update-profile-info', [MasterAuthController::class, 'updateProfileInfo'])->name('master.update.profile.info');
    Route::post('/profile-change-password', [MasterAuthController::class, 'profileChangePassword'])->name('master.profile.change.password');
    Route::post('/update-employee-info', [MasterAuthController::class, 'updateEmployeeInfo'])->name('master.update.employee.info');
    Route::post('/employee-change-password', [MasterAuthController::class, 'employeeChangePassword'])->name('master.employee.change.password');
    Route::get('/company-info', [MasterController::class, 'showCompanyInfoView'])->name('master.company.info');
    Route::post('/update-company-general-info', [MasterController::class, 'updateCompanyGeneralInfo'])->name('master.company.general.info.update');
    Route::post('/update-company-contact-info', [MasterController::class, 'updateCompanyContactInfo'])->name('master.company.contact.info.update');

   
    Route::get('/subscription-plans', [MasterController::class, 'showSubscriptionPlansView'])->name('master.subscription.plans');
    Route::post('/insert-subscription-plan', [MasterController::class, 'insertSubscriptionPlan'])->name('master.subscription.plan.insert');
    Route::post('/delete-subscription-plan', [MasterController::class, 'deleteSubscriptionPlan'])->name('master.subscription.plan.delete');
    Route::post('/update-subscription-plan', [MasterController::class, 'updateSubscriptionPlan'])->name('master.subscription.plan.update');
    

     Route::get('/employees', [MasterController::class, 'showEmployeesView'])->name('master.employees');
     Route::get('/employee-details', [MasterController::class, 'showEmployeesDetailsView'])->name('master.employee.details');            
     Route::post('/employee/insert', [MasterController::class, 'insertEmployee'])->name('master.employee.insert');               
     Route::post('/employee/update', [MasterController::class, 'updateEmployee'])->name('master.employee.update');               
     Route::post('/employee/delete', [MasterController::class, 'deleteEmployee'])->name('master.employee.delete');               
     Route::get('/employee/{id}/pdf', [MasterController::class, 'pdf'])->name('master.employee.pdf');





    Route::get('/payment-methods', [MasterController::class, 'showPaymentMethodsView'])->name('master.payment.methods');
    Route::post('/insert-payment-method', [MasterController::class, 'insertPaymentMethod'])->name('master.payment.method.insert');
    Route::post('/update-payment-method', [MasterController::class, 'updatePaymentMethod'])->name('master.payment.method.update');
    Route::post('/delete-payment-method', [MasterController::class, 'deletePaymentMethod'])->name('master.payment.method.delete');

    

    Route::get('/company/files/list', [MasterController::class, 'listCompanyFiles']) ->name('master.company.files.list');
    Route::post('/company/upload/document', [MasterController::class, 'uploadDocument'])->name('master.company.upload.document');
    Route::post('/company/upload/image', [MasterController::class, 'uploadImage'])->name('master.company.upload.image');
    Route::post('/company/edit/name/{id}', [MasterController::class, 'updateName'])->name('master.company.edit.name');
    Route::post('/company/delete/{id}', [MasterController::class, 'deleteFile'])->name('master.company.delete.file');
    Route::get('/company/download/{id}', [MasterController::class, 'downloadFile'])->name('master.company.download.file');
    Route::post('company/files/bulk-delete', [MasterController::class, 'bulkDeleteFiles'])->name('master.company.files.bulk-delete');



    Route::get('/invoice-templates-view', [MasterController::class, 'showInvoiceTemplatesView'])->name('master.invoice.templates');
    Route::post('/insert-invoice-template', [MasterController::class, 'insertInvoiceTemplate'])->name('master.invoice.template.insert');
    Route::post('/update-invoice-template', [MasterController::class, 'updateInvoiceTemplate'])->name('master.invoice.template.update');
    Route::post('/delete-invoice-template', [MasterController::class, 'deleteInvoiceTemplate'])->name('master.invoice.template.delete');
    Route::get('/preview-invoice-template/{filename}', [MasterController::class, 'previewInvoiceTemplate'])->name('preview.invoice');
    Route::get('/preview/invoice-template/pdf/{filename}', [MasterController::class, 'previewInvoiceTemplatePdf'])->name('preview.invoice.pdf');




    Route::get('/tenants-view', [MasterTenantController::class, 'showTenantsView'])->name('master.tenants');
    Route::post('/add-tenant', [MasterTenantController::class, 'masterAddTenant'])->name('master.add.tenant');
    Route::get('/tenant-details', [MasterTenantController::class, 'showTenantDetailsView'])->name('master.tenant.details');
    Route::post('/update-tenant-details', [MasterTenantController::class, 'updateTenantDetails'])->name('master.tenant.details.update');
    Route::post('/approve-tenant-local', [MasterTenantController::class, 'approveTenantLocal'])->name('master.tenant.approve.local');
    Route::post('/approve-tenant-remote', [MasterTenantController::class, 'approveTenantRemote'])->name('master.tenant.approve.remote');

    Route::get('/tenant/invoices-view', [MasterTenantInvoicesController::class, 'showTenantInvoicesView'])->name('master.tenant.invoices');
    Route::post('/tenant/send-invoice', [MasterTenantInvoicesController::class, 'masterSendInvoiceFromTenantDetails'])->name('master.tenant.send.invoice');
    Route::get('/tenant/invoices/pdf/{id}', [MasterTenantInvoicesController::class, 'tenantInvoicePdfPreview'])->name('master.tenant.invoices.pdf');
    Route::get('/tenant/invoices/download/{id}', [MasterTenantInvoicesController::class, 'tenantInvoiceDownloadPdf'])->name('master.tenant.invoices.download');
    Route::post('/tenant/invoices/pay/{id}', [MasterTenantInvoicesController::class, 'tenantInvoiceMarkAsPaid'])->name('master.tenant.invoices.pay');
    Route::post('/tenant/invoices/cancel/{id}', [MasterTenantInvoicesController::class, 'tenantInvoiceCancel'])->name('master.tenant.invoices.cancel');
    Route::post('/tenant/invoices/send/{id}', [MasterTenantInvoicesController::class, 'tenantSendInvoiceFromInvoicesTable'])->name('master.tenant.invoices.send');



    Route::post('/tenant-quotations', [MasterTenantController::class, 'masterAddTenant'])->name('master.tenant.quotations');
    Route::post('/tenant-deliverynotes', [MasterTenantController::class, 'masterAddTenant'])->name('master.tenant.deliverynotes'); 
    Route::post('/tenant-receipts', [MasterTenantController::class, 'masterAddTenant'])->name('master.tenant.receipts'); 
    

    Route::get('/point-of-sales', [MasterController::class, 'showPointOfSalesView'])->name('master.point.of.sales');


    Route::get('/currency', [MasterController::class, 'showCurrencyView'])->name('master.currency');
    Route::post('/insert-currency', [MasterController::class, 'insertCurrency'])->name('master.currency.insert');
    Route::post('/update-currency', [MasterController::class, 'updateCurrency'])->name('master.currency.update');
    Route::post('/delete-currency', [MasterController::class, 'deleteCurrency'])->name('master.currency.delete');


    Route::get('/tenant/migrations', [TenantMigrationController::class, 'showTenantMigrationView'])->name('master.tenant.migrations');
    Route::get('/tenant/migrations/{tenant}/actions', [TenantMigrationController::class, 'showTenantMigrationActionsView'])->name('master.tenant.migrations.actions');
    Route::post('/tenant/migrations/{tenant}/run', [TenantMigrationController::class, 'executePendingMigrations'])->name('master.tenant.migrations.run');
    Route::post('/tenant/migrations/{tenant}/reset', [TenantMigrationController::class, 'resetTenantDatabaseCompletely'])->name('master.tenant.migrations.reset');
    

    // Global Migration Management
    Route::get('/tenant/global-migrations', [TenantMigrationController::class, 'showGlobalMigrations'])->name('master.global.migrations');
    Route::post('/tenant/global-migrations/run-pending-all', [TenantMigrationController::class, 'runPendingForAll'])->name('master.global.migrations.run-pending-all');
});

Route::group(['prefix' => '{tenantName}', 'middleware' => ['web','tenancy','tenant.auth','tenant.admin'] ], function () {
  Route::get('/admin', [TenantAdminController::class, 'showTenantAdminDashboard'])->name('tenant.admin.dashboard');
  Route::get('/admin/employees', [TenantAdminController::class, 'showTenantEmployeesView'])->name('tenant.admin.employees'); 
  Route::get('/admin/roles', [TenantAdminController::class, 'showRolesView'])->name('tenant.roles');
  Route::get('/admin/profile', [TenantAdminController::class, 'showProfileView'])->name('tenant.profile');
  Route::get('/admin/forgot-password', [TenantAuthController::class, 'forgotPasswordView'])->name('tenant.forgot.password');
  Route::post('/admin/send-password-reset-link', [TenantAuthController::class, 'sendPasswordResetLink'])->name('tenant.password.reset.link');
  Route::get('/admin/reset-password-view', [TenantAuthController::class, 'resetPasswordView'])->name('tenant.reset.password.view');
  Route::post('/admin/submit-password-reset', [TenantAuthController::class, 'submitPasswordReset'])->name('tenant.submit.password.reset');

  Route::get('/admin/events', [TenantAdminController::class, 'showEventsView'])->name('tenant.events');
  Route::get('/admin/events-data', [TenantAdminController::class, 'fetchEvents'])->name('tenant.fetch.events');
  Route::post('/admin/event-create', [TenantAdminController::class, 'storeEvent'])->name('tenant.add.event');
  Route::post('/admin/event-create-table', [TenantAdminController::class, 'addEventForTableView'])->name('tenant.add.event.table');
  Route::post('/admin/event-update/{id}', [TenantAdminController::class, 'updateEvent'])->name('tenant.update.event');
  Route::post('/admin/event-delete/{id}', [TenantAdminController::class, 'deleteEvent'])->name('tenant.delete.event');
  Route::get('/admin/events-table', [TenantAdminController::class, 'showEventsTable'])->name('tenant.events.table');
  Route::post('/admin/events-bulk-delete', [TenantAdminController::class, 'bulkDeleteEvents'])->name('tenant.bulk.delete.events');
  Route::post('/admin/event-create-table', [TenantAdminController::class, 'addEventViewTable'])->name('tenant.create.event.table');

  Route::post('/admin/update-profile-info', [TenantAuthController::class, 'updateProfileInfo'])->name('tenant.update.profile.info');
  Route::post('/admin/profile-change-password', [TenantAuthController::class, 'profileChangePassword'])->name('tenant.profile.change.password');
  Route::post('/admin/update-employee-info', [TenantAdminController::class, 'updateEmployeeInfo'])->name('tenant.update.employee.info');
  Route::post('/admin/employee-change-password', [TenantAdminController::class, 'employeeChangePassword'])->name('tenant.employee.change.password');
  Route::get('/admin/company-info', [TenantAdminController::class, 'showCompanyInfoView'])->name('tenant.company.info');
  Route::post('/admin/update-company-general-info', [TenantAdminController::class, 'updateCompanyGeneralInfo'])->name('tenant.company.general.info.update');
  Route::post('/admin/update-company-contact-info', [TenantAdminController::class, 'updateCompanyContactInfo'])->name('tenant.company.contact.info.update');

  Route::get('/admin/employees', [TenantAdminController::class, 'showEmployeesView'])->name('tenant.employees');
  Route::get('/admin/employee-details', [TenantAdminController::class, 'showEmployeeDetailsView'])->name('tenant.employee.details');
  Route::post('/admin/employee/insert', [TenantAdminController::class, 'insertEmployee'])->name('tenant.employee.insert');
  Route::post('/admin/employee/update', [TenantAdminController::class, 'updateEmployee'])->name('tenant.employee.update');
  Route::post('/admin/employee/delete', [TenantAdminController::class, 'deleteEmployee'])->name('tenant.employee.delete');
  Route::get('/admin/employee/{id}/pdf', [TenantAdminController::class, 'employeePdf'])->name('tenant.employee.pdf');

  Route::get('/admin/payment-methods', [TenantAdminController::class, 'showPaymentMethodsView'])->name('tenant.payment.methods');
  Route::post('/admin/insert-payment-method', [TenantAdminController::class, 'insertPaymentMethod'])->name('tenant.payment.method.insert');
  Route::post('/admin/update-payment-method', [TenantAdminController::class, 'updatePaymentMethod'])->name('tenant.payment.method.update');
  Route::post('/admin/delete-payment-method', [TenantAdminController::class, 'deletePaymentMethod'])->name('tenant.payment.method.delete');

  Route::get('/admin/company/files/list', [TenantAdminController::class, 'listCompanyFiles'])->name('tenant.company.files.list');
  Route::post('/admin/company/upload/document', [TenantAdminController::class, 'uploadDocument'])->name('tenant.company.upload.document');
  Route::post('/admin/company/upload/image', [TenantAdminController::class, 'uploadImage'])->name('tenant.company.upload.image');
  Route::post('/admin/company/edit/name/{id}', [TenantAdminController::class, 'updateName'])->name('tenant.company.edit.name');
  Route::post('/admin/company/delete/{id}', [TenantAdminController::class, 'deleteFile'])->name('tenant.company.delete.file');
  Route::get('/admin/company/download/{id}', [TenantAdminController::class, 'downloadFile'])->name('tenant.company.download.file');
  Route::post('/admin/company/files/bulk-delete', [TenantAdminController::class, 'bulkDeleteFiles'])->name('tenant.company.files.bulk-delete');

  Route::get('/admin/currency', [TenantAdminController::class, 'showCurrencyView'])->name('tenant.currency');
  Route::post('/admin/insert-currency', [TenantAdminController::class, 'insertCurrency'])->name('tenant.currency.insert');
  Route::post('/admin/update-currency', [TenantAdminController::class, 'updateCurrency'])->name('tenant.currency.update');
  Route::post('/admin/delete-currency', [TenantAdminController::class, 'deleteCurrency'])->name('tenant.currency.delete');
});

