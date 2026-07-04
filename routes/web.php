<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Website\WebsiteController;
use App\Http\Controllers\Master\MasterController;
use App\Http\Controllers\Master\MasterAuthController;
use App\Http\Controllers\Master\MasterTenantController;
use App\Http\Controllers\Master\MasterApproveTenantController;
use App\Http\Controllers\Master\MasterTenantInvoicesController;
use App\Http\Controllers\Master\TenantMigrationController;
use App\Http\Controllers\Master\InvoiceTemplateController;
use App\Http\Controllers\Tenant\TenantAdminController;
use App\Http\Controllers\Tenant\TenantCommonController;
use App\Http\Controllers\Operations\Retail\RetailOperationsController;
use App\Http\Controllers\Operations\Retail\BaseproductsController;
use App\Http\Controllers\Operations\Retail\RetailBranchProductsController;
use App\Http\Controllers\Operations\Retail\RetailAuditLogsController;
use App\Http\Controllers\Operations\Retail\RetailActionCenterController;
use App\Http\Controllers\Operations\Retail\RetailDeliverynotesController;
use App\Http\Controllers\Operations\Retail\EisController;
use App\Http\Controllers\Operations\Retail\SupplierController;
use App\Http\Controllers\Operations\Wholesale\WholesaleOperationsController;
use App\Http\Controllers\Operations\Finance\FinanceOperationsController;
use App\Http\Controllers\Sales\Retail\RetailSalesController;
use App\Http\Controllers\Sales\Retail\RetailPointOfSaleController;
use App\Http\Controllers\Operations\Retail\RetailFullstocktakingController;
use App\Http\Controllers\Operations\Retail\OperationSalesController;
 



// ══════════════════════════════════════════════════════════════════════════════
// PUBLIC WEBSITE
// ══════════════════════════════════════════════════════════════════════════════

Route::get('/',               [WebsiteController::class, 'showHomePage']);
Route::get('/get-started',    [WebsiteController::class, 'showGetStartedView']);
Route::get('/about', [WebsiteController::class, 'showAboutUsView']);
Route::get('/features',       [WebsiteController::class, 'showFeaturesView']);
Route::get('/pricing',        [WebsiteController::class, 'showPricingView']);
Route::get('/contact',        [WebsiteController::class, 'showContactView']);
Route::get('/help-center',    [WebsiteController::class, 'showHelpcenterView']);
Route::post('client-registration', [WebsiteController::class, 'clientRegistration'])->name('client.registration');
Route::get('/login',                [WebsiteController::class,    'showLoginByCodeView'])->name('login.by.code.view');
Route::post('/submit-login-by-code',[TenantCommonController::class, 'loginByCode'])->name('submit.login.by.code');


// ══════════════════════════════════════════════════════════════════════════════
// MASTER PANEL — public (login only)
// ══════════════════════════════════════════════════════════════════════════════

Route::group(['prefix' => 'master'], function () {
    Route::get('/',             [MasterController::class,  'showMasterLoginPage'])->name('master.login.page');
    Route::post('/master-login',[MasterAuthController::class, 'masterLogin'])->name('master.login');
    Route::post('/master-logout',[MasterAuthController::class,'masterLogout'])->name('master.logout');
});




// ══════════════════════════════════════════════════════════════════════════════
// TENANT — public login (URL-based)
// ══════════════════════════════════════════════════════════════════════════════

Route::group(['prefix' => '{tenantName}', 'middleware' => ['tenancy']], function () {
    Route::get('/',              [TenantCommonController::class, 'showTenantLoginPageByUrl'])->name('tenant.login.by.url');
    Route::post('login',         [TenantCommonController::class, 'submitUrlBasedLogin'])->name('tenant.submit.login');
    Route::post('/tenant-logout',[TenantCommonController::class, 'tenantLogout'])->name('tenant.logout');
});




// ══════════════════════════════════════════════════════════════════════════════
// TENANT ADMIN
// ══════════════════════════════════════════════════════════════════════════════

Route::group(['prefix' => '{tenantName}', 'middleware' => ['web', 'tenancy', 'hydrate.auth', 'tenant.admin']], function () {

    // Dashboard & Profile
    Route::get('/admin',                          [TenantAdminController::class, 'showTenantAdminDashboard'])->name('tenant.admin.dashboard');
    Route::get('/admin/profile',                  [TenantAdminController::class, 'showProfileView'])->name('tenant.admin.profile');
    Route::post('/admin/update-profile-info',     [TenantCommonController::class,  'updateProfileInfo'])->name('tenant.admin.update.profile.info');
    Route::post('/admin/profile-change-password', [TenantCommonController::class,  'profileChangePassword'])->name('tenant.admin.profile.change.password');

    // Password Reset
    Route::get('/admin/forgot-password',             [TenantCommonController::class, 'forgotPasswordView'])->name('tenant.admin.forgot.password');
    Route::post('/admin/send-password-reset-link',   [TenantCommonController::class, 'sendPasswordResetLink'])->name('tenant.admin.password.reset.link');
    Route::get('/admin/reset-password-view',         [TenantCommonController::class, 'resetPasswordView'])->name('tenant.admin.reset.password.view');
    Route::post('/admin/submit-password-reset',      [TenantCommonController::class, 'submitPasswordReset'])->name('tenant.admin.submit.password.reset');

    // Employees
    Route::get('/admin/employees',                   [TenantAdminController::class, 'showEmployeesView'])->name('tenant.admin.employees');
    Route::post('/admin/employee/insert',            [TenantAdminController::class, 'insertEmployee'])->name('tenant.admin.employee.insert');
    Route::post('/admin/employee/update',            [TenantAdminController::class, 'updateEmployee'])->name('tenant.admin.employee.update');
    Route::post('/admin/employee/delete',            [TenantAdminController::class, 'deleteEmployee'])->name('tenant.admin.employee.delete');
    Route::get('/admin/employee-details',            [TenantAdminController::class, 'showEmployeeDetailsView'])->name('tenant.admin.employee.details');
    Route::post('/admin/employee/details/update',    [TenantAdminController::class, 'updateEmployeeDetails'])->name('tenant.admin.employee.details.update');
    Route::get('/admin/employee/{id}/pdf',           [TenantAdminController::class, 'downloadEployeeProfile'])->name('tenant.admin.employee.pdf');

    // Company Info & Files
    Route::get('/admin/company-info',                        [TenantAdminController::class, 'showCompanyInfoView'])->name('tenant.admin.company.info');
    Route::post('/admin/update-company-general-info',        [TenantAdminController::class, 'updateCompanyGeneralInfo'])->name('tenant.admin.company.general.info.update');
    Route::post('/admin/update-company-contact-info',        [TenantAdminController::class, 'updateCompanyContactInfo'])->name('tenant.admin.company.contact.info.update');
    Route::get('/admin/company/files/list',                  [TenantAdminController::class, 'listCompanyFiles'])->name('tenant.admin.company.files.list');
    Route::post('/admin/company/upload/document',            [TenantAdminController::class, 'uploadDocument'])->name('tenant.admin.company.upload.document');
    Route::post('/admin/company/upload/image',               [TenantAdminController::class, 'uploadImage'])->name('tenant.admin.company.upload.image');
    Route::post('/admin/company/files/edit',                 [TenantAdminController::class, 'updateName'])->name('tenant.admin.company.edit.name');
    Route::post('/admin/company/files/delete',               [TenantAdminController::class, 'deleteFile'])->name('tenant.admin.company.delete.file');
    Route::get('/admin/company/files/download',              [TenantAdminController::class, 'downloadFile'])->name('tenant.admin.company.download.file');
    Route::post('/admin/company/files/bulk-delete',          [TenantAdminController::class, 'bulkDeleteFiles'])->name('tenant.admin.company.files.bulk-delete');

    // Payment Methods
    Route::get('/admin/payment-methods',             [TenantAdminController::class, 'showPaymentMethodsView'])->name('tenant.admin.payment.methods');
    Route::post('/admin/insert-payment-method',      [TenantAdminController::class, 'insertPaymentMethod'])->name('tenant.admin.payment.method.insert');
    Route::post('/admin/update-payment-method',      [TenantAdminController::class, 'updatePaymentMethod'])->name('tenant.admin.payment.method.update');
    Route::post('/admin/delete-payment-method',      [TenantAdminController::class, 'deletePaymentMethod'])->name('tenant.admin.payment.method.delete');

    // Events
    Route::get('/admin/events',                      [TenantAdminController::class, 'showEventsView'])->name('tenant.admin.events');
    Route::get('/admin/events-data',                 [TenantAdminController::class, 'fetchEvents'])->name('tenant.admin.fetch.events');
    Route::get('/admin/events-table',                [TenantAdminController::class, 'showEventsTable'])->name('tenant.admin.events.table');
    Route::post('/admin/event-create',               [TenantAdminController::class, 'storeEvent'])->name('tenant.admin.add.event');
    Route::post('/admin/event-create-table',         [TenantAdminController::class, 'addEventForTableView'])->name('tenant.admin.add.event.table');
    Route::post('/admin/event-update/{id}',          [TenantAdminController::class, 'updateEvent'])->name('tenant.admin.update.event');
    Route::post('/admin/event-delete/{id}',          [TenantAdminController::class, 'deleteEvent'])->name('tenant.admin.delete.event');
    Route::post('/admin/events-bulk-delete',         [TenantAdminController::class, 'bulkDeleteEvents'])->name('tenant.admin.bulk.delete.events');

    // Currency
    Route::get('/admin/currency',                    [TenantAdminController::class, 'showCurrencyView'])->name('tenant.admin.currency');
    Route::post('/admin/insert-currency',            [TenantAdminController::class, 'insertCurrency'])->name('tenant.admin.currency.insert');
    Route::post('/admin/update-currency',            [TenantAdminController::class, 'updateCurrency'])->name('tenant.admin.currency.update');
    Route::post('/admin/delete-currency',            [TenantAdminController::class, 'deleteCurrency'])->name('tenant.admin.currency.delete');

    // Roles, Branches, Categories, Sectors
    Route::get('/admin/roles',                       [TenantAdminController::class, 'showRolesView'])->name('tenant.admin.roles');
    Route::get('/admin/branches',                    [TenantAdminController::class, 'showBranchesView'])->name('tenant.admin.branches');
    Route::post('/admin/insert-branch',              [TenantAdminController::class, 'insertBranch'])->name('tenant.admin.branch.insert');
    Route::post('/admin/update-branch',              [TenantAdminController::class, 'updateBranch'])->name('tenant.admin.branch.update');
    Route::post('/admin/delete-branch',              [TenantAdminController::class, 'deleteBranch'])->name('tenant.admin.branch.delete');
    Route::get('/admin/branch-details',              [TenantAdminController::class, 'showBranchDetailsView'])->name('tenant.admin.branch.details');
    Route::get('/admin/business/categories',         [TenantAdminController::class, 'showCategoriesView'])->name('tenant.admin.categories');
    Route::get('/admin/business/sectors',            [TenantAdminController::class, 'showSectorsView'])->name('tenant.admin.sectors');
    Route::post('/admin/insert-category',            [TenantAdminController::class, 'insertCategory'])->name('tenant.admin.category.insert');
    Route::post('/admin/update-category',            [TenantAdminController::class, 'updateCategory'])->name('tenant.admin.category.update');
    Route::post('/admin/delete-category',            [TenantAdminController::class, 'deleteCategory'])->name('tenant.admin.category.delete');

    // Permissions
    Route::get('/admin/permissions',                 [TenantAdminController::class, 'showPermissionsView'])->name('tenant.admin.permissions');
    Route::post('/admin/add-permission',             [TenantAdminController::class, 'addPermission'])->name('tenant.admin.permission.add');
    Route::post('/admin/remove-permission',          [TenantAdminController::class, 'removePermission'])->name('tenant.admin.permission.remove');

    // Suppliers
    Route::get('/admin/suppliers',                   [TenantAdminController::class, 'showSuppliersView'])->name('tenant.admin.suppliers');
    Route::post('/admin/insert-supplier',            [TenantAdminController::class, 'insertSupplier'])->name('tenant.admin.supplier.insert');
    Route::post('/admin/update-supplier',            [TenantAdminController::class, 'updateSupplier'])->name('tenant.admin.supplier.update');
    Route::post('/admin/delete-supplier',            [TenantAdminController::class, 'deleteSupplier'])->name('tenant.admin.supplier.delete');

    // System
    Route::get('/admin/system/settings',             [TenantAdminController::class, 'showSystemSettingsView'])->name('tenant.admin.system.settings');
    Route::get('/admin/system/helpcenter',           [TenantAdminController::class, 'showSystemHelpcenterView'])->name('tenant.admin.system.helpcenter');
    Route::get('/admin/system/subscription',         [TenantAdminController::class, 'showSystemSubscriptionView'])->name('tenant.admin.system.subscription');

    // User Filters
    Route::post('/admin/update-user-filters',        [TenantAdminController::class, 'updateUserFilters'])->name('tenant.admin.update.filters');

    // HR — Payroll Periods
    Route::get('/admin/hr/payroll/periods',          [TenantAdminController::class, 'showPayrollPeriodsView'])->name('tenant.admin.hr.payroll.periods');
    Route::post('/admin/hr/payroll/periods/store',   [TenantAdminController::class, 'storePayrollPeriod'])->name('tenant.admin.hr.payroll.period.store');
    Route::post('/admin/hr/payroll/periods/update',  [TenantAdminController::class, 'updatePayrollPeriod'])->name('tenant.admin.hr.payroll.period.update');
    Route::post('/admin/hr/payroll/periods/generate',[TenantAdminController::class, 'generatePayrollEntries'])->name('tenant.admin.hr.payroll.period.generate');
    Route::post('/admin/hr/payroll/periods/approve', [TenantAdminController::class, 'approvePayrollPeriod'])->name('tenant.admin.hr.payroll.period.approve');
    Route::post('/admin/hr/payroll/periods/markpaid',[TenantAdminController::class, 'markPayrollPeriodPaid'])->name('tenant.admin.hr.payroll.period.markpaid');
    Route::post('/admin/hr/payroll/periods/delete',  [TenantAdminController::class, 'deletePayrollPeriod'])->name('tenant.admin.hr.payroll.period.delete');

    // HR — Wage Bill
    Route::get('/admin/hr/payroll/wagebill',               [TenantAdminController::class, 'showWageBillView'])->name('tenant.admin.hr.payroll.wagebill');
    Route::post('/admin/hr/payroll/wagebill/entry/update', [TenantAdminController::class, 'updatePayrollEntry'])->name('tenant.admin.hr.payroll.wagebill.entry.update');
    Route::get('/admin/hr/payroll/wagebill/payslip',       [TenantAdminController::class, 'downloadPayslip'])->name('tenant.admin.hr.payroll.wagebill.payslip');

    // HR — Payslips
    Route::get('/admin/hr/payroll/payslips',           [TenantAdminController::class, 'showPayslipsView'])->name('tenant.admin.hr.payroll.payslips');
    Route::get('/admin/hr/payroll/payslips/stats',     [TenantAdminController::class, 'getPayslipStats'])->name('tenant.admin.hr.payroll.payslips.stats');
    Route::post('/admin/hr/payroll/payslips/email',    [TenantAdminController::class, 'emailPayslip'])->name('tenant.admin.hr.payroll.payslips.email');
    Route::post('/admin/hr/payroll/payslips/bulkemail',[TenantAdminController::class, 'bulkEmailPayslips'])->name('tenant.admin.hr.payroll.payslips.bulkemail');

    // HR — Pension
    Route::get('/admin/hr/pension',         [TenantAdminController::class, 'showPensionView'])->name('tenant.admin.hr.pension');
    Route::post('/admin/hr/pension/store',  [TenantAdminController::class, 'storePension'])->name('tenant.admin.hr.pension.store');
    Route::post('/admin/hr/pension/update', [TenantAdminController::class, 'updatePension'])->name('tenant.admin.hr.pension.update');
    Route::post('/admin/hr/pension/delete', [TenantAdminController::class, 'deletePension'])->name('tenant.admin.hr.pension.delete');

    // HR — Loans
    Route::get('/admin/hr/loans',         [TenantAdminController::class, 'showLoansView'])->name('tenant.admin.hr.loans');
    Route::post('/admin/hr/loans/store',  [TenantAdminController::class, 'storeLoan'])->name('tenant.admin.hr.loans.store');
    Route::post('/admin/hr/loans/update', [TenantAdminController::class, 'updateLoan'])->name('tenant.admin.hr.loans.update');
    Route::post('/admin/hr/loans/delete', [TenantAdminController::class, 'deleteLoan'])->name('tenant.admin.hr.loans.delete');

    // HR — Advances
    Route::get('/admin/hr/advances',         [TenantAdminController::class, 'showAdvancesView'])->name('tenant.admin.hr.advances');
    Route::post('/admin/hr/advances/store',  [TenantAdminController::class, 'storeAdvance'])->name('tenant.admin.hr.advances.store');
    Route::post('/admin/hr/advances/update', [TenantAdminController::class, 'updateAdvance'])->name('tenant.admin.hr.advances.update');
    Route::post('/admin/hr/advances/delete', [TenantAdminController::class, 'deleteAdvance'])->name('tenant.admin.hr.advances.delete');

    // HR — Offer Letters
    Route::get('/admin/hr/offer-letters',           [TenantAdminController::class, 'showOfferLettersView'])->name('tenant.admin.hr.offer.letters');
    Route::post('/admin/hr/offer-letters/store',    [TenantAdminController::class, 'storeOfferLetter'])->name('tenant.admin.hr.offer.letters.store');
    Route::post('/admin/hr/offer-letters/update',   [TenantAdminController::class, 'updateOfferLetter'])->name('tenant.admin.hr.offer.letters.update');
    Route::post('/admin/hr/offer-letters/delete',   [TenantAdminController::class, 'deleteOfferLetter'])->name('tenant.admin.hr.offer.letters.delete');
    Route::get('/admin/hr/offer-letters/download',  [TenantAdminController::class, 'downloadOfferLetter'])->name('tenant.admin.hr.offer.letters.download');

    // HR — PAYE Brackets
    Route::get('/admin/hr/paye/brackets',          [TenantAdminController::class, 'showPayeBracketsView'])->name('tenant.admin.hr.paye.brackets');
    Route::post('/admin/hr/paye/brackets/store',   [TenantAdminController::class, 'storePayeBracket'])->name('tenant.admin.hr.paye.brackets.store');
    Route::post('/admin/hr/paye/brackets/update',  [TenantAdminController::class, 'updatePayeBracket'])->name('tenant.admin.hr.paye.brackets.update');
    Route::post('/admin/hr/paye/brackets/retire',  [TenantAdminController::class, 'retirePayeBracket'])->name('tenant.admin.hr.paye.brackets.retire');
    Route::post('/admin/hr/paye/brackets/delete',  [TenantAdminController::class, 'deletePayeBracket'])->name('tenant.admin.hr.paye.brackets.delete');

    // HR — Allowances
    Route::get('/admin/hr/allowances',             [TenantAdminController::class, 'showAllowancesView'])->name('tenant.admin.hr.allowances');
    Route::post('/admin/hr/allowances/store',      [TenantAdminController::class, 'storeAllowance'])->name('tenant.admin.hr.allowances.store');
    Route::post('/admin/hr/allowances/update',     [TenantAdminController::class, 'updateAllowance'])->name('tenant.admin.hr.allowances.update');
    Route::post('/admin/hr/allowances/delete',     [TenantAdminController::class, 'deleteAllowance'])->name('tenant.admin.hr.allowances.delete');
    Route::get('/admin/hr/allowances/history',     [TenantAdminController::class, 'showAllowanceHistoryView'])->name('tenant.admin.hr.allowances.history');

});




// ══════════════════════════════════════════════════════════════════════════════
// RETAIL OPERATIONS
// ══════════════════════════════════════════════════════════════════════════════

Route::group(['prefix' => '{tenantName}', 'middleware' => ['web', 'tenancy', 'hydrate.auth', 'tenant.operations', 'retail.allowed']], function () {

    // Dashboard & Supporting Views
    Route::get('/operations/retail',           [RetailOperationsController::class, 'showDashboardView'])->name('retail.operations.dashboard');
    Route::get('operations/retail/branches',   [RetailOperationsController::class, 'showBranchesView'])->name('retail.operations.branches');
    Route::get('operations/retail/suppliers',  [RetailOperationsController::class, 'showSuppliersView'])->name('retail.operations.suppliers');

    // Base Products
    Route::get('operations/retail/baseproducts',               [BaseproductsController::class, 'showBaseproductsView'])->name('retail.operations.baseproducts');
    Route::post('operations/retail/baseproducts/insert',       [BaseproductsController::class, 'insertBaseproduct'])->name('retail.operations.baseproducts.insert');
    Route::post('operations/retail/baseproducts/update',       [BaseproductsController::class, 'updateBaseproduct'])->name('retail.operations.baseproducts.update');
    Route::post('operations/retail/baseproducts/delete',       [BaseproductsController::class, 'deleteBaseproduct'])->name('retail.operations.baseproducts.delete');
    Route::post('operations/retail/baseproducts/bulkdelete',   [BaseproductsController::class, 'bulkDeleteBaseproducts'])->name('retail.operations.baseproducts.bulkdelete');
    Route::post('operations/retail/baseproducts/bulkstatus',   [BaseproductsController::class, 'bulkStatusBaseproducts'])->name('retail.operations.baseproducts.bulkstatus');
    Route::post('operations/retail/baseproducts/bulksupplier', [BaseproductsController::class, 'bulkSupplierBaseproducts'])->name('retail.operations.baseproducts.bulksupplier');
    Route::post('operations/retail/baseproducts/csv/upload',   [BaseproductsController::class, 'uploadBaseproductsCsv'])->name('retail.operations.baseproducts.csv.upload');
    Route::post('operations/retail/baseproducts/import/row',   [BaseproductsController::class, 'importBaseproductRow'])->name('retail.operations.baseproducts.import.row');
    Route::post('operations/retail/baseproducts/bulktax',      [BaseproductsController::class, 'bulkTaxBaseproducts'])->name('retail.operations.baseproducts.bulktax');
    Route::post('operations/retail/baseproducts/bulktype',     [BaseproductsController::class, 'bulkTypeBaseproducts'])->name('retail.operations.baseproducts.bulktype');
    Route::get('operations/retail/baseproducts/search',        [RetailBranchProductsController::class, 'searchBaseproducts'])->name('retail.operations.baseproducts.search');
    Route::get('operations/retail/baseproducts/branch-overrides', [BaseproductsController::class, 'getBranchOverrides'])->name('retail.operations.baseproducts.branchoverrides');

    // Branch Products
    Route::get('operations/retail/branchproducts',             [RetailBranchProductsController::class, 'showBranchproductsView'])->name('retail.operations.branchproducts');
    Route::post('operations/retail/branchproducts/upsert',     [RetailBranchProductsController::class, 'upsertBranchproduct'])->name('retail.operations.branchproducts.upsert');
    Route::post('operations/retail/branchproducts/update',     [RetailBranchProductsController::class, 'updateBranchproduct'])->name('retail.operations.branchproducts.update');
    Route::post('operations/retail/branchproducts/delete',     [RetailBranchProductsController::class, 'deleteBranchproduct'])->name('retail.operations.branchproducts.delete');
    Route::post('operations/retail/branchproducts/bulkdelete', [RetailBranchProductsController::class, 'bulkDeleteBranchproducts'])->name('retail.operations.branchproducts.bulkdelete');
    Route::post('operations/retail/branchproducts/bulkstatus', [RetailBranchProductsController::class, 'bulkStatusBranchproducts'])->name('retail.operations.branchproducts.bulkstatus');
    Route::post('operations/retail/branchproducts/bulktax',    [RetailBranchProductsController::class, 'bulkTaxBranchproducts'])->name('retail.operations.branchproducts.bulktax');


Route::post('operations/retail/branchproducts/bulk/use-base-prices', [RetailBranchProductsController::class, 'bulkUseBasePrices'])->name('retail.operations.branchproducts.bulk.usebaseprices');

Route::post('operations/retail/branchproducts/bulk/set-branch-prices', [RetailBranchProductsController::class, 'bulkSetBranchPrices'])->name('retail.operations.branchproducts.bulk.setbranchprices');



    // CSV import (new)
    Route::post('operations/retail/branchproducts/csv/upload',     [RetailBranchProductsController::class, 'uploadBranchproductsCsv'])->name('retail.operations.branchproducts.csv.upload');
    Route::post('operations/retail/branchproducts/csv/import-row', [RetailBranchProductsController::class, 'importBranchproductRow'])->name('retail.operations.branchproducts.csv.import-row');

    // Suppliers dropdown helper (new)
    Route::get('operations/retail/suppliers/dropdown', [RetailBranchProductsController::class, 'listSuppliersForDropdown'])->name('retail.operations.suppliers.dropdown');



    // Shop Values
    Route::get('/retail/shopvalues',              [RetailBranchProductsController::class, 'showShopvaluesOverview'])->name('retail.operations.shopvalues.overview');
    Route::get('/retail/shopvalues/movement',     [RetailBranchProductsController::class, 'showShopvaluesMovement'])->name('retail.operations.shopvalues.movement');
    Route::get('/retail/shopvalues/movement/data',[RetailBranchProductsController::class, 'getMovementData'])->name('retail.operations.shopvalues.movement.data');
    Route::get('/retail/shopvalues/audit',        [RetailBranchProductsController::class, 'getAuditLog'])->name('retail.operations.shopvalues.audit');

    // Audit Logs
    Route::get('operations/retail/auditlogs',            [RetailAuditLogsController::class, 'showAuditLogsView'])->name('retail.operations.auditlogs');
    Route::get('operations/retail/auditlogs/dates',      [RetailAuditLogsController::class, 'getDatesWithLogs'])->name('retail.auditlogs.dates');
    Route::get('operations/retail/auditlogs/by-date',    [RetailAuditLogsController::class, 'getLogsByDate'])->name('retail.auditlogs.bydate');
    Route::get('operations/retail/auditlogs/download-pdf',[RetailAuditLogsController::class, 'downloadPdf'])->name('retail.auditlogs.downloadpdf');
    Route::post('operations/retail/auditlogs/update',    [RetailAuditLogsController::class, 'updateLog'])->name('retail.auditlogs.update');
    Route::post('operations/retail/auditlogs/reverse',   [RetailAuditLogsController::class, 'reverseLog'])->name('retail.auditlogs.reverse');
    Route::post('operations/retail/auditlogs/delete',    [RetailAuditLogsController::class, 'deleteLog'])->name('retail.auditlogs.delete');
    Route::post('operations/retail/auditlogs/bulkdelete',[RetailAuditLogsController::class, 'bulkDeleteLogs'])->name('retail.auditlogs.bulkdelete');
    Route::post('operations/retail/auditlogs/bulkreverse',[RetailAuditLogsController::class, 'bulkReverse'])->name('retail.auditlogs.bulkreverse');

    // Action Centre
    Route::get('retail/operations/action-center',              [RetailActionCenterController::class, 'showActioncenterView'])->name('retail.operations.actioncenter');
    Route::get('retail/operations/price-changes',              [RetailActionCenterController::class, 'showPricechangesView'])->name('retail.operations.pricechanges');
    Route::get('retail/operations/action-center/branch-grid',  [RetailActionCenterController::class, 'getBranchGrid'])->name('retail.operations.actioncenter.branch.grid');
    Route::get('retail/operations/action-center/dates',        [RetailActionCenterController::class, 'getDatesWithNotes'])->name('retail.operations.actioncenter.dates');
    Route::post('retail/operations/action-center/dnote/save',       [RetailActionCenterController::class, 'saveDeliveryNote'])->name('retail.operations.actioncenter.dnote.save');
    Route::post('retail/operations/action-center/dnote/update',     [RetailActionCenterController::class, 'updateDeliveryNote'])->name('retail.operations.actioncenter.dnote.update');
    Route::post('retail/operations/action-center/dnote/delete',     [RetailActionCenterController::class, 'deleteDeliveryNote'])->name('retail.operations.actioncenter.dnote.delete');
    Route::post('retail/operations/action-center/dnote/bulk-delete',[RetailActionCenterController::class, 'bulkDeleteDeliveryNotes'])->name('retail.operations.actioncenter.dnote.bulk-delete');
    Route::post('retail/operations/action-center/dnote/submit',     [RetailActionCenterController::class, 'submitDeliveryNotes'])->name('retail.operations.actioncenter.dnote.submit');
    Route::post('retail/operations/action-center/dnote/submit-all', [RetailActionCenterController::class, 'submitAllDeliveryNotes'])->name('retail.operations.actioncenter.dnote.submit-all');
    Route::post('retail/operations/action-center/dnote/cancel',     [RetailActionCenterController::class, 'cancelDeliveryNotes'])->name('retail.operations.actioncenter.dnote.cancel');
    Route::post('retail/operations/action-center/product/delete',      [RetailActionCenterController::class, 'deleteBaseProduct'])->name('retail.operations.actioncenter.product.delete');
    Route::post('retail/operations/action-center/product/branch-price',[RetailActionCenterController::class, 'saveBranchPrice'])->name('retail.operations.actioncenter.product.branch-price');

    // Delivery Notes
    Route::get('retail/operations/delivery-notes',                    [RetailDeliveryNotesController::class, 'showDeliverynotesView'])->name('retail.operations.deliverynotes');
    Route::get('retail/operations/delivery-notes/branch-summary',     [RetailDeliveryNotesController::class, 'fetchBranchDeliveryNoteSummaryByDate'])->name('retail.operations.deliverynotes.branch-summary');
    Route::get('retail/operations/delivery-notes/branch/details',     [RetailDeliveryNotesController::class, 'showBranchDeliveryNoteDetailsView'])->name('retail.operations.deliverynotes.branch.details');
    Route::get('retail/operations/delivery-notes/branch/lines',       [RetailDeliveryNotesController::class, 'fetchBranchDeliveryNoteLines'])->name('retail.operations.deliverynotes.branch.lines');
    Route::get('retail/operations/delivery-notes/branch/edit',        [RetailDeliveryNotesController::class, 'showBranchDeliveryNoteEditView'])->name('retail.operations.deliverynotes.branch.edit-view');
    Route::get('retail/operations/delivery-notes/branch/export-pdf',  [RetailDeliveryNotesController::class, 'exportBranchDeliveryNotesToPdf'])->name('retail.operations.deliverynotes.branch.export-pdf');
    Route::post('retail/operations/delivery-notes/branch/submit-pending',[RetailDeliveryNotesController::class, 'submitAllPendingNotesForBranch'])->name('retail.operations.deliverynotes.branch.submit-pending');
    Route::post('retail/operations/delivery-notes/line/submit',       [RetailDeliveryNotesController::class, 'submitDeliverynoteFromDetailsView'])->name('retail.operations.deliverynotes.line.submit');
    Route::post('retail/operations/delivery-notes/line/unsubmit',     [RetailDeliveryNotesController::class, 'unsubmitDeliverynoteFromDetailsView'])->name('retail.operations.deliverynotes.line.unsubmit');
    Route::post('retail/operations/delivery-notes/line/update',       [RetailDeliveryNotesController::class, 'updateDeliverynoteFromDetailsView'])->name('retail.operations.deliverynotes.line.update');
    Route::post('retail/operations/delivery-notes/line/delete',       [RetailDeliveryNotesController::class, 'deleteDeliverynoteFromDetailsView'])->name('retail.operations.deliverynotes.line.delete');
    Route::post('retail/operations/delivery-notes/lines/bulk/submit',  [RetailDeliveryNotesController::class, 'bulkSubmitDeliverynoteLinesFromDetailsView'])->name('retail.operations.deliverynotes.lines.bulk.submit');
    Route::post('retail/operations/delivery-notes/lines/bulk/unsubmit',[RetailDeliveryNotesController::class, 'bulkUnsubmitDeliverynoteLinesFromDetailsView'])->name('retail.operations.deliverynotes.lines.bulk.unsubmit');
    Route::post('retail/operations/delivery-notes/lines/bulk/delete',  [RetailDeliveryNotesController::class, 'bulkDeleteDeliverynoteLinesFromDetailsView'])->name('retail.operations.deliverynotes.lines.bulk.delete');
    Route::post('retail/operations/delivery-notes/bulk/submit-selected',  [RetailDeliveryNotesController::class, 'bulkSubmitSelectedDeliveryNotes'])->name('retail.operations.deliverynotes.bulk.submit-selected');
    Route::post('retail/operations/delivery-notes/bulk/unsubmit-selected',[RetailDeliveryNotesController::class, 'bulkUnsubmitSelectedDeliveryNotes'])->name('retail.operations.deliverynotes.bulk.unsubmit-selected');
    Route::post('retail/operations/delivery-notes/bulk/delete-selected',  [RetailDeliveryNotesController::class, 'bulkDeleteSelectedDeliveryNotes'])->name('retail.operations.deliverynotes.bulk.delete-selected');

Route::get('/operations/retail/fullstocktaking', [RetailFullstocktakingController::class, 'showCountingView'])->name('retail.operations.fullstocktaking');
Route::get('/operations/retail/fullstocktaking/merged-data', [RetailFullstocktakingController::class, 'showMergedDataView'])->name('retail.operations.fullstocktaking.merged-data');
Route::get('/operations/retail/fullstocktaking/missing-products', [RetailFullstocktakingController::class, 'showMissingProductsView'])->name('retail.operations.fullstocktaking.missing-products');
Route::get('/operations/retail/fullstocktaking/actions-and-info', [RetailFullstocktakingController::class, 'showActionsAndInfoView'])->name('retail.operations.fullstocktaking.actions-and-info');
Route::get('/operations/retail/fullstocktaking/history', [RetailFullstocktakingController::class, 'showHistoryView'])->name('retail.operations.fullstocktaking.history');
Route::get('/operations/retail/fullstocktaking/history/details', [RetailFullstocktakingController::class, 'showHistoryDetailsView'])->name('retail.operations.fullstocktaking.history.details');

Route::post('/operations/retail/fullstocktaking/merge', [RetailFullstocktakingController::class, 'mergeCounts'])->name('retail.operations.fullstocktaking.merge');
Route::post('/operations/retail/fullstocktaking/merged-data/update', [RetailFullstocktakingController::class, 'updateMergedRow'])->name('retail.operations.fullstocktaking.merged-data.update');
Route::post('/operations/retail/fullstocktaking/merged-data/delete', [RetailFullstocktakingController::class, 'deleteMergedRow'])->name('retail.operations.fullstocktaking.merged-data.delete');
Route::post('/operations/retail/fullstocktaking/merged-data/sync', [RetailFullstocktakingController::class, 'syncMergedData'])->name('retail.operations.fullstocktaking.merged-data.sync');
Route::post('/operations/retail/fullstocktaking/missing-products/sync', [RetailFullstocktakingController::class, 'syncMissingProducts'])->name('retail.operations.fullstocktaking.missing-products.sync');
Route::post('/operations/retail/fullstocktaking/rectify', [RetailFullstocktakingController::class, 'submitRectification'])->name('retail.operations.fullstocktaking.rectify');

Route::post('/operations/retail/fullstocktaking/report/full', [RetailFullstocktakingController::class, 'downloadFullReport'])->name('retail.operations.fullstocktaking.report.full');
Route::post('/operations/retail/fullstocktaking/report/delivery', [RetailFullstocktakingController::class, 'downloadDeliveryNote'])->name('retail.operations.fullstocktaking.report.delivery');
Route::post('/operations/retail/fullstocktaking/report/merged-data', [RetailFullstocktakingController::class, 'downloadMergedDataReport'])->name('retail.operations.fullstocktaking.report.merged-data');
Route::post('/operations/retail/fullstocktaking/report/missing-products', [RetailFullstocktakingController::class, 'downloadMissingProductsReport'])->name('retail.operations.fullstocktaking.report.missing-products');




Route::post('/operations/retail/fullstocktaking/seed-session', [RetailFullstocktakingController::class, 'seedSession'])->name('retail.operations.fullstocktaking.seed-session');
Route::post('/operations/retail/fullstocktaking/device-sync', [RetailFullstocktakingController::class, 'reportDeviceSync'])->name('retail.operations.fullstocktaking.device-sync');
Route::get('/operations/retail/fullstocktaking/sync-status', [RetailFullstocktakingController::class, 'getSyncStatus'])->name('retail.operations.fullstocktaking.sync-status');
 



Route::get('/operations/retail/sales/today',          [OperationSalesController::class, 'showTodaysSalesView'])->name('retail.operations.sales.today');
Route::post('/operations/retail/sales/update',         [OperationSalesController::class, 'updateSale'])->name('retail.operations.sales.update');
Route::post('/operations/retail/sales/reverse',        [OperationSalesController::class, 'reverseSales'])->name('retail.operations.sales.reverse');
Route::post('/operations/retail/sales/change-date',    [OperationSalesController::class, 'changeSalesDate'])->name('retail.operations.sales.change-date');
Route::post('/operations/retail/sales/interval/update',[OperationSalesController::class, 'updateIntervalSale'])->name('retail.operations.sales.interval.update');
Route::post('/operations/retail/sales/interval/delete',[OperationSalesController::class, 'deleteIntervalSale'])->name('retail.operations.sales.interval.delete');

});




// ══════════════════════════════════════════════════════════════════════════════
// WHOLESALE OPERATIONS
// ══════════════════════════════════════════════════════════════════════════════

Route::group(['prefix' => '{tenantName}', 'middleware' => ['web', 'tenancy', 'hydrate.auth', 'tenant.operations', 'wholesale.allowed']], function () {

    Route::get('/operations/wholesale', [WholesaleOperationsController::class, 'showDashboardView'])->name('wholesale.operations.dashboard');

});




// ══════════════════════════════════════════════════════════════════════════════
// FINANCE OPERATIONS
// ══════════════════════════════════════════════════════════════════════════════

Route::group(['prefix' => '{tenantName}', 'middleware' => ['web', 'tenancy', 'hydrate.auth', 'tenant.operations', 'finance.allowed']], function () {

    Route::get('/operations/financial-services', [FinanceOperationsController::class, 'showDashboardView'])->name('finance.operations.dashboard');

});




// ══════════════════════════════════════════════════════════════════════════════
// SALES
// ══════════════════════════════════════════════════════════════════════════════
Route::group(['prefix' => '{tenantName}', 'middleware' => ['web', 'tenancy', 'hydrate.auth', 'sales.allowed']], function () {

    Route::get('sales/retail/dashboard', [RetailSalesController::class, 'showDashboardView'])->name('retail.sales.dashboard');
    Route::get('sales/retail/profile', [RetailSalesController::class, 'showProfileView'])->name('retail.sales.profile');
    Route::post('/sales/retail/update-profile-info',     [TenantCommonController::class,  'updateProfileInfo'])->name('tenant.sales.retail.update.profile.info');
    Route::post('/sales/retail/profile-change-password', [TenantCommonController::class,  'profileChangePassword'])->name('tenant.sales.retail.profile.change.password');


    
    // Read only 
    Route::get('sales/retail/products',                [RetailSalesController::class, 'showProductsView'])->name('retail.sales.products');
    Route::get('sales/retail/deliverynotes',                [RetailSalesController::class, 'showDeliverynotesView'])->name('retail.sales.deliverynotes');
    Route::get('sales/retail/products/search/',                [RetailSalesController::class, 'showProductSearchView'])->name('retail.sales.products.search');
    
    
    // POS 
    Route::get('sales/retail/pos/mobile',                [RetailPointOfSaleController::class, 'showMobilePosView'])->name('retail.pos.mobile');
    Route::get('sales/retail/pos/desktop',                [RetailPointOfSaleController::class, 'showDesktopPosView'])->name('retail.pos.desktop');


        Route::post('sales/retail/upload-sales',         [RetailPointOfSaleController::class, 'uploadSales'])->name('retail.pos.upload.sales');

        Route::post('sales/retail/insert-interval-sale', [RetailPointOfSaleController::class, 'insertIntervalSale'])->name('retail.pos.insert.interval.sale');
        Route::post('sales/retail/edit-interval-sale',   [RetailPointOfSaleController::class, 'editIntervalSale'])->name('retail.pos.edit.interval.sale');
        Route::post('sales/retail/delete-interval-sale', [RetailPointOfSaleController::class, 'deleteIntervalSale'])->name('retail.pos.delete.interval.sale');
        
    Route::post('/sales/retail/payment-summary', [RetailPointOfSaleController::class, 'getPaymentSummary'])->name('sales.retail.payment-summary');



    // Events
    Route::get('sales/retail/events',                [RetailSalesController::class, 'showEventsView'])->name('retail.sales.events');
    Route::get('sales/retail/events-data',           [RetailSalesController::class, 'fetchEvents'])->name('retail.sales.fetch.events');
    Route::get('sales/retail/events-table',          [RetailSalesController::class, 'showEventsTable'])->name('retail.sales.events.table');
    Route::post('sales/retail/event-create',         [RetailSalesController::class, 'storeEvent'])->name('retail.sales.add.event');
    Route::post('sales/retail/event-create-table',   [RetailSalesController::class, 'addEventForTableView'])->name('retail.sales.add.event.table');
    Route::post('sales/retail/event-update/{id}',    [RetailSalesController::class, 'updateEvent'])->name('retail.sales.update.event');
    Route::post('sales/retail/event-delete/{id}',    [RetailSalesController::class, 'deleteEvent'])->name('retail.sales.delete.event');
    Route::post('sales/retail/events-bulk-delete',   [RetailSalesController::class, 'bulkDeleteEvents'])->name('retail.sales.bulk.delete.events');

});




// ══════════════════════════════════════════════════════════════════════════════
// MASTER ADMIN
// ══════════════════════════════════════════════════════════════════════════════

Route::group(['prefix' => 'master', 'middleware' => 'master.admin'], function () {

    // Dashboard & Profile
    Route::get('/dashboard',    [MasterController::class,    'showMasterDashboard'])->name('master.dashboard');
    Route::get('/support-center',[MasterController::class,   'showMasterSupportCenter'])->name('master.support.center');
    Route::get('/roles',        [MasterController::class,    'showRolesView'])->name('master.roles');
    Route::get('/profile',      [MasterController::class,    'showProfileView'])->name('master.profile');
    Route::post('/update-profile-info',      [MasterAuthController::class, 'updateProfileInfo'])->name('master.update.profile.info');
    Route::post('/profile-change-password',  [MasterAuthController::class, 'profileChangePassword'])->name('master.profile.change.password');
    Route::post('/update-employee-info',     [MasterAuthController::class, 'updateEmployeeInfo'])->name('master.update.employee.info');
    Route::post('/employee-change-password', [MasterAuthController::class, 'employeeChangePassword'])->name('master.employee.change.password');

    // Password Reset
    Route::get('/forgot-password',             [MasterAuthController::class, 'forgotPasswordView'])->name('master.forgot.password');
    Route::post('/send-password-reset-link',   [MasterAuthController::class, 'sendPasswordResetLink'])->name('master.password.reset.link');
    Route::get('/reset-password-view',         [MasterAuthController::class, 'resetPasswordView'])->name('master.reset.password.view');
    Route::post('/submit-password-reset',      [MasterAuthController::class, 'submitPasswordReset'])->name('master.submit.password.reset');

    // Company Info & Files
    Route::get('/company-info',                  [MasterController::class, 'showCompanyInfoView'])->name('master.company.info');
    Route::post('/update-company-general-info',  [MasterController::class, 'updateCompanyGeneralInfo'])->name('master.company.general.info.update');
    Route::post('/update-company-contact-info',  [MasterController::class, 'updateCompanyContactInfo'])->name('master.company.contact.info.update');
    Route::get('/company/files/list',            [MasterController::class, 'listCompanyFiles'])->name('master.company.files.list');
    Route::post('/company/upload/document',      [MasterController::class, 'uploadDocument'])->name('master.company.upload.document');
    Route::post('/company/upload/image',         [MasterController::class, 'uploadImage'])->name('master.company.upload.image');
    Route::post('/company/edit/file/name',       [MasterController::class, 'updateName'])->name('master.company.edit.file.name');
    Route::post('/company/delete/file',          [MasterController::class, 'deleteFile'])->name('master.company.delete.file');
    Route::get('/company/download/file',         [MasterController::class, 'downloadFile'])->name('master.company.download.file');
    Route::post('company/files/bulk-delete',     [MasterController::class, 'bulkDeleteFiles'])->name('master.company.files.bulk-delete');

    // Events
    Route::get('/events-view',              [MasterController::class, 'showMasterEvents'])->name('master.events');
    Route::get('/events-data',              [MasterController::class, 'fetchMasterEvents'])->name('master.fetch.events');
    Route::get('/events-table',             [MasterController::class, 'showEventsTable'])->name('master.events.table');
    Route::post('/event-create',            [MasterController::class, 'storeMasterEvent'])->name('master.add.event');
    Route::post('/event-create-table',      [MasterController::class, 'addEventForTableView'])->name('master.add.event.table');
    Route::post('/event-update/{id}',       [MasterController::class, 'updateMasterEvent'])->name('master.update.event');
    Route::post('/event-delete/{id}',       [MasterController::class, 'destroyMasterEvent'])->name('master.delete.event');
    Route::post('/events-bulk-delete',      [MasterController::class, 'bulkDeleteMasterEvents'])->name('master.bulk.delete.events');

    // Subscription Plans
    Route::get('/subscription-plans',        [MasterController::class, 'showSubscriptionPlansView'])->name('master.subscription.plans');
    Route::post('/insert-subscription-plan', [MasterController::class, 'insertSubscriptionPlan'])->name('master.subscription.plan.insert');
    Route::post('/update-subscription-plan', [MasterController::class, 'updateSubscriptionPlan'])->name('master.subscription.plan.update');
    Route::post('/delete-subscription-plan', [MasterController::class, 'deleteSubscriptionPlan'])->name('master.subscription.plan.delete');

    // Employees
    Route::get('/employees',              [MasterController::class, 'showEmployeesView'])->name('master.employees');
    Route::get('/employee-details',       [MasterController::class, 'showEmployeesDetailsView'])->name('master.employee.details');
    Route::post('/employee/insert',       [MasterController::class, 'insertEmployee'])->name('master.employee.insert');
    Route::post('/employee/update',       [MasterController::class, 'updateEmployee'])->name('master.employee.update');
    Route::post('/employee/delete',       [MasterController::class, 'deleteEmployee'])->name('master.employee.delete');
    Route::get('/master/employee/{id}/pdf',[MasterController::class, 'downloadEployeeProfile'])->name('master.employee.pdf');

    // Payment Methods
    Route::get('/payment-methods',           [MasterController::class, 'showPaymentMethodsView'])->name('master.payment.methods');
    Route::post('/insert-payment-method',    [MasterController::class, 'insertPaymentMethod'])->name('master.payment.method.insert');
    Route::post('/update-payment-method',    [MasterController::class, 'updatePaymentMethod'])->name('master.payment.method.update');
    Route::post('/delete-payment-method',    [MasterController::class, 'deletePaymentMethod'])->name('master.payment.method.delete');

    // Currency
    Route::get('/currency',              [MasterController::class, 'showCurrencyView'])->name('master.currency');
    Route::post('/insert-currency',      [MasterController::class, 'insertCurrency'])->name('master.currency.insert');
    Route::post('/update-currency',      [MasterController::class, 'updateCurrency'])->name('master.currency.update');
    Route::post('/delete-currency',      [MasterController::class, 'deleteCurrency'])->name('master.currency.delete');

    // Invoice Templates
    Route::get('/invoice-templates',          [InvoiceTemplateController::class, 'showInvoiceTemplatesView'])->name('master.invoice.template.view');
    Route::post('/invoice-templates/insert',  [InvoiceTemplateController::class, 'insertInvoiceTemplate'])->name('master.invoice.template.insert');
    Route::post('/invoice-templates/update',  [InvoiceTemplateController::class, 'updateInvoiceTemplate'])->name('master.invoice.template.update');
    Route::post('/invoice-templates/delete',  [InvoiceTemplateController::class, 'deleteInvoiceTemplate'])->name('master.invoice.template.delete');
    Route::get('/invoice-templates/preview',  [InvoiceTemplateController::class, 'previewInvoiceTemplate'])->name('master.invoice.template.preview');
    Route::get('/invoice-templates/pdf/{id}', [InvoiceTemplateController::class, 'generateInvoiceTemplatePdf'])->name('master.invoice.template.generate_pdf');

    // Tenants
    Route::get('/tenants-view',                         [MasterTenantController::class, 'showTenantsView'])->name('master.tenants');
    Route::get('/tenant-details',                       [MasterTenantController::class, 'showTenantDetailsView'])->name('master.tenant.details');
    Route::post('/add-tenant',                          [MasterTenantController::class, 'masterAddTenant'])->name('master.add.tenant');
    Route::post('/update-tenant-details',               [MasterTenantController::class, 'updateTenantDetails'])->name('master.tenant.details.update');
   
    Route::post('/master/tenant/approve',               [MasterApproveTenantController::class, 'approveTenant'])->name('master.tenant.approve');
    Route::get('/master/tenant/approve/status/{id}', [MasterApproveTenantController::class, 'approveStatus'])->name('master.tenant.approve.status');
    
    
    Route::post('/master/tenant/delete',                [MasterTenantController::class, 'deleteTenant'])->name('master.tenant.delete');
    Route::post('/master/tenant/hold',                  [MasterTenantController::class, 'toggleTenantHold'])->name('master.tenant.hold');
    Route::post('/master/tenant/payment-dates',         [MasterTenantController::class, 'updatePaymentDates'])->name('master.tenant.payment.dates');
    Route::post('/master/tenant/subscription-plan',     [MasterTenantController::class, 'updateSubscriptionPlan'])->name('master.tenant.subscription.plan');

    // Tenant Invoices
    Route::get('/tenant/invoices-view',              [MasterTenantInvoicesController::class, 'showTenantInvoicesView'])->name('master.tenant.invoices');
    Route::post('/tenant/send-invoice',              [MasterTenantInvoicesController::class, 'masterSendInvoiceFromTenantDetails'])->name('master.tenant.send.invoice');
    Route::post('/master/tenant/send-custom-invoice',[MasterTenantInvoicesController::class, 'masterSendCustomInvoice'])->name('master.tenant.send.custom.invoice');
    Route::get('/tenant/invoices/pdf/{id}',          [MasterTenantInvoicesController::class, 'tenantInvoicePdfPreview'])->name('master.tenant.invoices.pdf');
    Route::get('/tenant/invoices/download/{id}',     [MasterTenantInvoicesController::class, 'tenantInvoiceDownloadPdf'])->name('master.tenant.invoices.download');
    Route::post('/tenant/invoices/pay/{id}',         [MasterTenantInvoicesController::class, 'tenantInvoiceMarkAsPaid'])->name('master.tenant.invoices.pay');
    Route::post('/tenant/invoices/cancel/{id}',      [MasterTenantInvoicesController::class, 'tenantInvoiceCancel'])->name('master.tenant.invoices.cancel');
    Route::post('/tenant/invoices/send/{id}',        [MasterTenantInvoicesController::class, 'tenantSendInvoiceFromInvoicesTable'])->name('master.tenant.invoices.send');


    // Tenant Migrations
Route::get('/tenant/migrations',                          [TenantMigrationController::class, 'showTenantMigrationView'])->name('master.tenant.migrations');
Route::get('/tenant/migrations/{tenant}/actions',         [TenantMigrationController::class, 'showTenantMigrationActionsView'])->name('master.tenant.migrations.actions');
Route::post('/tenant/migrations/{tenant}/run',            [TenantMigrationController::class, 'executePendingMigrations'])->name('master.tenant.migrations.run');
Route::post('/tenant/migrations/{tenant}/reset',          [TenantMigrationController::class, 'resetTenantDatabaseCompletely'])->name('master.tenant.migrations.reset');
Route::get('/tenant/migrations/{tenant}/pending',         [TenantMigrationController::class, 'getPendingMigrationsList'])->name('master.tenant.migrations.pending');
Route::post('/tenant/migrations/{tenant}/next',           [TenantMigrationController::class, 'runNextMigration'])->name('master.tenant.migrations.next');
Route::get('/tenant/global-migrations',                   [TenantMigrationController::class, 'showGlobalMigrations'])->name('master.global.migrations');
Route::post('/tenant/global-migrations/run-pending-all',  [TenantMigrationController::class, 'runPendingForAll'])->name('master.global.migrations.run-pending-all');

Route::get('/tenant/global-migrations/pending-list',         [TenantMigrationController::class, 'getGlobalPendingList'])->name('master.global.migrations.pending-list');
Route::post('/tenant/global-migrations/next/{tenantId}',     [TenantMigrationController::class, 'runNextMigrationForTenant'])->name('master.global.migrations.next');

});