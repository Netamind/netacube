<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Website\WebsiteController;
use App\Http\Controllers\Master\MasterController;
use App\Http\Controllers\Master\MasterAuthController;
use App\Http\Controllers\Master\MasterTenantController;
use App\Http\Controllers\Master\MasterTenantInvoicesController;
use App\Http\Controllers\Master\TenantMigrationController;
use App\Http\Controllers\Tenant\TenantAuthController;
use App\Http\Controllers\Tenant\TenantAdminController;
use App\Http\Controllers\Tenant\TenantCommonController;
use App\Http\Controllers\Tenant\TenantSuperAdminController;
use App\Http\Controllers\Tenant\TenantRetailAdminController;
use App\Http\Controllers\Tenant\TenantWholesaleAdminController;


Route::get('/', [WebsiteController::class, 'showHomePage']);
Route::get('/get-started', [WebsiteController::class, 'showGetStartedView']);
Route::get('/about-netacube', [WebsiteController::class, 'showAboutUsView']);
Route::get('/features', [WebsiteController::class, 'showFeaturesView']);
Route::get('/pricing', [WebsiteController::class, 'showPricingView']);
Route::get('/contact', [WebsiteController::class, 'showContactView']);
Route::get('/help-center', [WebsiteController::class, 'showHelpcenterView']);
Route::post('client-registration', [WebsiteController::class, 'clientRegistration'])->name('client.registration');
Route::get('/login', [WebsiteController::class, 'showLoginByCodeView'])->name('login.by.code.view');
Route::post('/submit-login-by-code', [TenantCommonController::class, 'loginByCode'])->name('submit.login.by.code');



Route::group(['prefix' => 'master' ], function () {
   Route::get('/', [MasterController::class, 'showMasterLoginPage'])->name('master.login.page');
   Route::post('/master-logout', [MasterAuthController::class, 'masterLogout'])->name('master.logout'); 
   Route::post('/master-login', [MasterAuthController::class, 'masterLogin'])->name('master.login');  
});


Route::group(['prefix' => '{tenantName}', 'middleware' => ['tenancy'] ], function () {
  Route::get('/', [TenantCommonController::class, 'showTenantLoginPageByUrl'])->name('tenant.login.by.url');
  Route::post('login', [TenantCommonController::class, 'submitUrlBasedLogin'])->name('tenant.submit.login');
  Route::post('/tenant-logout', [TenantCommonController::class, 'masterLogout'])->name('tenant.logout'); 
});


 Route::post('/temporal-route-name', [TenantCommonController::class, 'masterLogout'])->name('tenant.super.admin.branches'); 





Route::group(['prefix' => '{tenantName}', 'middleware' => ['web', 'tenancy', 'tenant.super.auth', 'tenant.super.admin']], function () {

    Route::get('/super-admin', [TenantSuperAdminController::class, 'showTenantAdminDashboard'])->name('tenant.super.admin.dashboard');

    Route::get('/super-admin/profile', [TenantSuperAdminController::class, 'showProfileView'])->name('tenant.super.admin.profile');

    Route::post('/super-admin/update-profile-info', [TenantAuthController::class, 'updateProfileInfo'])->name('tenant.super.admin.update.profile.info');

    Route::post('/super-admin/profile-change-password', [TenantAuthController::class, 'profileChangePassword'])->name('tenant.super.admin.profile.change.password');

    Route::get('/super-admin/forgot-password', [TenantAuthController::class, 'forgotPasswordView'])->name('tenant.super.admin.forgot.password');

    Route::post('/super-admin/send-password-reset-link', [TenantAuthController::class, 'sendPasswordResetLink'])->name('tenant.super.admin.password.reset.link');

    Route::get('/super-admin/reset-password-view', [TenantAuthController::class, 'resetPasswordView'])->name('tenant.super.admin.reset.password.view');

    Route::post('/super-admin/submit-password-reset', [TenantAuthController::class, 'submitPasswordReset'])->name('tenant.super.admin.submit.password.reset');

    Route::get('/super-admin/employees', [TenantSuperAdminController::class, 'showEmployeesView'])->name('tenant.super.admin.employees');

    Route::get('/super-admin/employee-details', [TenantSuperAdminController::class, 'showEmployeeDetailsView'])->name('tenant.super.admin.employee.details');

    Route::post('/super-admin/employee/insert', [TenantSuperAdminController::class, 'insertEmployee'])->name('tenant.super.admin.employee.insert');

    Route::post('/super-admin/employee/update', [TenantSuperAdminController::class, 'updateEmployee'])->name('tenant.super.admin.employee.update');

    Route::post('/super-admin/employee/delete', [TenantSuperAdminController::class, 'deleteEmployee'])->name('tenant.super.admin.employee.delete');

    Route::get('/super-admin/employee/{id}/pdf', [TenantSuperAdminController::class, 'employeePdf'])->name('tenant.super.admin.employee.pdf');

    Route::get('/super-admin/company-info', [TenantSuperAdminController::class, 'showCompanyInfoView'])->name('tenant.super.admin.company.info');

    Route::post('/super-admin/update-company-general-info', [TenantSuperAdminController::class, 'updateCompanyGeneralInfo'])->name('tenant.super.admin.company.general.info.update');

    Route::post('/super-admin/update-company-contact-info', [TenantSuperAdminController::class, 'updateCompanyContactInfo'])->name('tenant.super.admin.company.contact.info.update');

    Route::get('/super-admin/payment-methods', [TenantSuperAdminController::class, 'showPaymentMethodsView'])->name('tenant.super.admin.payment.methods');

    Route::post('/super-admin/insert-payment-method', [TenantSuperAdminController::class, 'insertPaymentMethod'])->name('tenant.super.admin.payment.method.insert');

    Route::post('/super-admin/update-payment-method', [TenantSuperAdminController::class, 'updatePaymentMethod'])->name('tenant.super.admin.payment.method.update');

    Route::post('/super-admin/delete-payment-method', [TenantSuperAdminController::class, 'deletePaymentMethod'])->name('tenant.super.admin.payment.method.delete');

    Route::get('/super-admin/events', [TenantSuperAdminController::class, 'showEventsView'])->name('tenant.super.admin.events');

    Route::get('/super-admin/events-data', [TenantSuperAdminController::class, 'fetchEvents'])->name('tenant.super.admin.fetch.events');

    Route::post('/super-admin/event-create', [TenantSuperAdminController::class, 'storeEvent'])->name('tenant.super.admin.add.event');

    Route::post('/super-admin/event-create-table', [TenantSuperAdminController::class, 'addEventForTableView'])->name('tenant.super.admin.add.event.table');

    Route::post('/super-admin/event-update/{id}', [TenantSuperAdminController::class, 'updateEvent'])->name('tenant.super.admin.update.event');

    Route::post('/super-admin/event-delete/{id}', [TenantSuperAdminController::class, 'deleteEvent'])->name('tenant.super.admin.delete.event');

    Route::get('/super-admin/events-table', [TenantSuperAdminController::class, 'showEventsTable'])->name('tenant.super.admin.events.table');

    Route::post('/super-admin/events-bulk-delete', [TenantSuperAdminController::class, 'bulkDeleteEvents'])->name('tenant.super.admin.bulk.delete.events');

    Route::get('/super-admin/company/files/list', [TenantSuperAdminController::class, 'listCompanyFiles'])->name('tenant.super.admin.company.files.list');

    Route::post('/super-admin/company/upload/document', [TenantSuperAdminController::class, 'uploadDocument'])->name('tenant.super.admin.company.upload.document');

    Route::post('/super-admin/company/upload/image', [TenantSuperAdminController::class, 'uploadImage'])->name('tenant.super.admin.company.upload.image');

    Route::post('/super-admin/company/edit/name/{id}', [TenantSuperAdminController::class, 'updateName'])->name('tenant.super.admin.company.edit.name');

    Route::post('/super-admin/company/delete/{id}', [TenantSuperAdminController::class, 'deleteFile'])->name('tenant.super.admin.company.delete.file');

    Route::get('/super-admin/company/download/{id}', [TenantSuperAdminController::class, 'downloadFile'])->name('tenant.super.admin.company.download.file');

    Route::post('/super-admin/company/files/bulk-delete', [TenantSuperAdminController::class, 'bulkDeleteFiles'])->name('tenant.super.admin.company.files.bulk-delete');

    Route::get('/super-admin/currency', [TenantSuperAdminController::class, 'showCurrencyView'])->name('tenant.super.admin.currency');

    Route::post('/super-admin/insert-currency', [TenantSuperAdminController::class, 'insertCurrency'])->name('tenant.super.admin.currency.insert');

    Route::post('/super-admin/update-currency', [TenantSuperAdminController::class, 'updateCurrency'])->name('tenant.super.admin.currency.update');

    Route::post('/super-admin/delete-currency', [TenantSuperAdminController::class, 'deleteCurrency'])->name('tenant.super.admin.currency.delete');

    Route::get('/super-admin/roles', [TenantSuperAdminController::class, 'showRolesView'])->name('tenant.super.admin.roles');
    
    Route::get('/super-admin/branches', [TenantSuperAdminController::class, 'showBranchesView'])->name('tenant.super.admin.branches');
    
    Route::post('/super-admin/insert-branch', [TenantSuperAdminController::class, 'insertBranch'])->name('tenant.super.admin.branch.insert');

    Route::post('/super-admin/update-branch', [TenantSuperAdminController::class, 'updateBranch'])->name('tenant.super.admin.branch.update');

    Route::post('/super-admin/delete-branch', [TenantSuperAdminController::class, 'deleteBranch'])->name('tenant.super.admin.branch.delete');

    
    Route::get('/super-admin/branch-details', [TenantSuperAdminController::class, 'showBranchDetailsView'])->name('tenant.super.admin.branch.details');



});








Route::group(['prefix' => '{tenantName}', 'middleware' => ['web', 'tenancy', 'tenant.retail.auth', 'tenant.retail.admin']], function () {

    Route::get('/retail-admin', [TenantRetailAdminController::class, 'showTenantAdminDashboard'])->name('tenant.retail.admin.dashboard');

    Route::get('/retail-admin/profile', [TenantRetailAdminController::class, 'showProfileView'])->name('tenant.retail.admin.profile');

});




Route::group(['prefix' => '{tenantName}', 'middleware' => ['web', 'tenancy', 'tenant.wholesale.auth', 'tenant.wholesale.admin']], function () {

    Route::get('/wholesale-admin', [TenantWholesaleAdminController::class, 'showTenantAdminDashboard'])->name('tenant.wholesale.admin.dashboard');

    Route::get('/wholesale-admin/profile', [TenantWholesaleAdminController::class, 'showProfileView'])->name('tenant.wholesale.admin.profile');

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
