{{-- File: resources/views/tenants/admin/branch-sales-settings.blade.php --}}
@extends('tenants.admin.dashboard')
@section('content')

@php
    $branchId = request('id');
    $branch   = $branchId ? DB::connection('tenant')->table('branches')->where('id', $branchId)->first() : null;
    $settings = $branch ? DB::connection('tenant')->table('branch_sales_settings')->where('branch_id', $branch->id)->first() : null;

    // Every field falls back to a sane default when no settings row exists
    // yet (first time this branch's settings are opened) — the row itself
    // is only created on first Save, in TenantAdminController::updateBranchSalesSettings.
    $get = function (string $field, $default = false) use ($settings) {
        return $settings->$field ?? $default;
    };

    // Flat, ungrouped list of every setting — each entry carries its own
    // icon so the field is recognisable at a glance without a section
    // header to lean on. Rendered two-per-row (col-md-6) below.
    //
    //   type 'toggle'          -> a single boolean switch
    //   type 'toggle_interval' -> a boolean switch + its own interval (minutes) input
    //   type 'numeric_toggle'  -> a plain nullable minutes field; the switch itself
    //                             isn't submitted, it just enables/disables the input
    //                             (null = off when the input is disabled and excluded)
    $allFields = [
        [
            'type' => 'toggle_interval', 'icon' => 'ri-upload-cloud-2-line',
            'field' => 'auto_upload_cloud_sales', 'interval_field' => 'auto_upload_cloud_sales_interval_minutes',
            'interval_default' => 2, 'input_id' => 'numAutoUploadInterval',
            'label' => 'Auto-upload cloud sales',
            'desc' => "Automatically push this branch's sales to the cloud on a timer.",
        ],
        [
            'type' => 'toggle', 'icon' => 'ri-delete-bin-6-line',
            'field' => 'allow_to_clear_cloud_sales', 'default' => false,
            'label' => 'Allow clearing cloud sales',
            'desc' => 'Lets sales staff clear pending sales sitting in local storage that have not yet uploaded to the cloud.',
        ],
        [
            'type' => 'toggle', 'icon' => 'ri-history-line',
            'field' => 'display_yesterdays_sales', 'default' => true,
            'label' => "Yesterday's sales",
            'desc' => "Show yesterday's sales total on the default view.",
        ],
        [
            'type' => 'toggle', 'icon' => 'ri-price-tag-3-line',
            'field' => 'display_price_changes', 'default' => true,
            'label' => 'Price changes',
            'desc' => 'Show a widget listing recent price changes.',
        ],
        [
            'type' => 'toggle', 'icon' => 'ri-file-list-3-line',
            'field' => 'display_deliverynotes_this_month', 'default' => true,
            'label' => 'Delivery notes this month',
            'desc' => "Show this month's delivery notes count/value.",
        ],
        [
            'type' => 'toggle', 'icon' => 'ri-line-chart-line',
            'field' => 'display_sales_this_month', 'default' => true,
            'label' => 'Sales this month',
            'desc' => "Show this month's running sales total.",
        ],
        [
            'type' => 'toggle', 'icon' => 'ri-group-line',
            'field' => 'display_number_of_customers_today', 'default' => true,
            'label' => 'Number of customers today',
            'desc' => "Show today's customer count.",
        ],
        [
            'type' => 'toggle', 'icon' => 'ri-shopping-cart-2-line',
            'field' => 'display_regular_orders_short_cut', 'default' => true,
            'label' => 'Regular orders shortcut',
            'desc' => 'Show a quick-access shortcut to Regular Orders.',
        ],
        [
            'type' => 'toggle', 'icon' => 'ri-alarm-warning-line',
            'field' => 'display_emergency_order_short_cut', 'default' => true,
            'label' => 'Emergency order shortcut',
            'desc' => 'Show a quick-access shortcut to Emergency Orders.',
        ],
        [
            'type' => 'toggle', 'icon' => 'ri-error-warning-line',
            'field' => 'display_low_stock_alerts', 'default' => false,
            'label' => 'Low stock alerts',
            'desc' => 'Show a widget flagging products running low on stock.',
        ],
        [
            'type' => 'toggle_interval', 'icon' => 'ri-refresh-line',
            'field' => 'auto_refresh_page', 'interval_field' => 'auto_refresh_interval_minutes',
            'interval_default' => 5, 'input_id' => 'numAutoRefreshInterval',
            'label' => 'Auto-refresh page',
            'desc' => 'Automatically reload the sales dashboard on a timer.',
        ],
        [
            'type' => 'numeric_toggle', 'icon' => 'ri-time-line',
            'field' => 'idle_timeout_minutes', 'default' => 15, 'input_id' => 'numIdleTimeout',
            'label' => 'Idle timeout',
            'desc' => 'Log sales users out after this many minutes of inactivity. Off = never.',
        ],
        [
            'type' => 'numeric_toggle', 'icon' => 'ri-shield-keyhole-line',
            'field' => 'session_lifetime_minutes', 'default' => 480, 'input_id' => 'numSessionLifetime',
            'label' => 'Session lifetime',
            'desc' => 'Force sales users to log back in after this many minutes, regardless of activity. Off = no limit.',
        ],
    ];
@endphp

<style>
.card-header {
  padding: 0.5rem 1.5rem !important;
  background: linear-gradient(to right, #4B5EBD, #576CC0);
  color: #fff;
}
.card {
  border: none;
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
  border-radius: 10px;
}
.card-header h4 {
  color: #fff;
  font-weight: 600;
  margin-bottom: 0;
  display: flex;
  align-items: center;
}
.card-header h4 i { margin-right: 0.25rem; }
.card-header .btn-light {
  height: 28px; padding: 0 10px;
  display: flex; align-items: center; justify-content: center; line-height: 1;
}
.card-header .btn-light:hover { background-color: #f8f9fa; }
.card-body { padding: 1.5rem; }

/* Field card — icon + label/description on the left, its control on the
   right, two per row on md+ screens. No section grouping — every setting
   stands on its own. */
.bss-field {
    background: #f8f9fc;
    border: 1px solid #eef0f7;
    border-radius: 10px;
    padding: 14px 16px;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 14px;
    transition: border-color .15s ease, box-shadow .15s ease;
}
.bss-field:hover {
    border-color: #d7dcf5;
    box-shadow: 0 2px 8px rgba(75, 94, 189, 0.08);
}
.bss-field-left { display: flex; align-items: flex-start; gap: 12px; min-width: 0; }
.bss-field-icon {
    width: 36px; height: 36px; border-radius: 9px; flex-shrink: 0;
    background: rgba(75, 94, 189, .1); color: #4B5EBD;
    display: flex; align-items: center; justify-content: center; font-size: 18px;
}
.bss-field-text { min-width: 0; }
.bss-field-label { font-size: 13.5px; font-weight: 600; color: #1e293b; }
.bss-field-desc { font-size: 11.5px; color: #8792a2; margin-top: 3px; line-height: 1.4; }
.bss-field-control { flex-shrink: 0; display: flex; align-items: center; gap: 8px; }

.bss-num-input {
    width: 84px; border: 1px solid silver; border-radius: 6px; padding: 5px 8px;
    font-size: 12px; font-weight: 600; text-align: center; color: #1a1a1a;
    background: #fff; outline: none;
}
.bss-num-input:disabled { background: #eef0f5; color: #b3b8c4; }
.bss-num-suffix { font-size: 11px; color: #8792a2; }
.form-check.form-switch .form-check-input {
    width: 2.5em; height: 1.3em; cursor: pointer; margin-top: 0;
}
.bss-branch-tag {
    font-size: 10px; font-weight: 700; background: rgba(255,255,255,.2); color: #fff;
    border-radius: 5px; padding: 3px 9px; margin-left: 10px;
}
input[type=number]::-webkit-outer-spin-button,
input[type=number]::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }
input[type=number] { -moz-appearance: textfield; }

.bss-save-bar {
    margin-top: 24px; padding-top: 20px; border-top: 1px solid #eef0f7;
    display: flex; justify-content: flex-end;
}
.bss-save-btn {
    background: linear-gradient(to right, #4B5EBD, #576CC0);
    border: none; color: #fff; font-weight: 600; font-size: 13.5px;
    padding: 10px 26px; border-radius: 8px;
    display: inline-flex; align-items: center; gap: 8px;
    transition: opacity .15s ease;
}
.bss-save-btn:hover { opacity: .92; color: #fff; }
.bss-save-btn.disabled { opacity: .6; pointer-events: none; }

@media (max-width: 767.98px) {
    .bss-field { flex-wrap: wrap; }
    .bss-save-bar { justify-content: stretch; }
    .bss-save-btn { width: 100%; justify-content: center; }
}
</style>

<div class="progress" id="progressBar" role="progressbar" style="height:8px;transform:rotate(180deg);display:none">
    <div class="progress-bar progress-bar-striped progress-bar-animated" style="width:100%"></div>
</div>

<div class="content-page"><div class="content"><div class="container-fluid">

<div class="row mb-3"></div>

<div class="card">
<div class="card-header d-flex justify-content-between align-items-center">
    <h4 class="header-title mb-0">
        <i class="ri-store-2-line"></i> Sales Settings
        @if($branch)<span class="bss-branch-tag">{{ $branch->name }}</span>@endif
    </h4>
    <div class="d-flex align-items-center">
        <a href="{{ route('tenant.admin.branch.sales.settings.list') }}" class="btn btn-light text-primary fs-16 mx-1" title="Back to Sales Branches">
            <i class="ri-arrow-left-line"></i>
        </a>
        @if($branch)
        <a href="#" class="btn btn-light text-primary fs-16 mx-1" id="infoBtn" title="What do these settings do?">
            <i class="ri-information-line"></i>
        </a>
        @endif
    </div>
</div>

<div class="card-body">
@if(!$branch)
    <div class="text-center py-5" style="color:#94a3b8;">
        <i class="ri-error-warning-line" style="font-size:48px;display:block;margin-bottom:10px;color:#c8d0ed;"></i>
        <h5 style="color:#64748b;">Branch not found</h5>
        <p style="font-size:13px;">Go back to <a href="{{ route('tenant.admin.branch.sales.settings.list') }}">Sales Branches</a> and open a branch's settings from there.</p>
    </div>
@else
    <form id="salesSettingsForm">
        @csrf
        <input type="hidden" name="branch_id" value="{{ $branch->id }}">

        <div class="row g-3">
            @foreach($allFields as $f)
            <div class="col-md-6">
                <div class="bss-field">
                    <div class="bss-field-left">
                        <div class="bss-field-icon"><i class="{{ $f['icon'] }}"></i></div>
                        <div class="bss-field-text">
                            <div class="bss-field-label">{{ $f['label'] }}</div>
                            <div class="bss-field-desc">{{ $f['desc'] }}</div>
                        </div>
                    </div>
                    <div class="bss-field-control">
                        @if($f['type'] === 'toggle')
                            <div class="form-check form-switch mb-0">
                                <input class="form-check-input" type="checkbox" name="{{ $f['field'] }}"
                                       {{ $get($f['field'], $f['default']) ? 'checked' : '' }}>
                            </div>
                        @elseif($f['type'] === 'toggle_interval')
                            <input type="number" class="bss-num-input bss-linked-input" id="{{ $f['input_id'] }}"
                                   name="{{ $f['interval_field'] }}"
                                   value="{{ $get($f['interval_field'], $f['interval_default']) }}" min="1" max="1440">
                            <span class="bss-num-suffix">min</span>
                            <div class="form-check form-switch mb-0">
                                <input class="form-check-input bss-switch" type="checkbox" name="{{ $f['field'] }}"
                                       data-target="{{ $f['input_id'] }}"
                                       {{ $get($f['field']) ? 'checked' : '' }}>
                            </div>
                        @elseif($f['type'] === 'numeric_toggle')
                            <input type="number" class="bss-num-input bss-linked-input" id="{{ $f['input_id'] }}"
                                   name="{{ $f['field'] }}"
                                   value="{{ $get($f['field'], $f['default']) }}" min="1" max="1440">
                            <span class="bss-num-suffix">min</span>
                            <div class="form-check form-switch mb-0">
                                <input class="form-check-input bss-switch" type="checkbox"
                                       data-target="{{ $f['input_id'] }}"
                                       {{ $get($f['field']) !== null ? 'checked' : '' }}>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
            @endforeach
        </div>

        <div class="bss-save-bar">
            <a href="#" class="bss-save-btn" id="saveSettingsBtn">
                <i class="ri-save-line"></i> Save Changes
            </a>
        </div>
    </form>
@endif
</div>
</div>

</div>
</div>
</div>

<!-- Info Modal -->
<div class="modal fade" id="infoModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">About These Settings</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" style="font-size:13.5px;color:#475569;">
                <p>These settings apply to this branch only — other branches are unaffected. Only <strong>Dashboard display</strong> below controls the Sales Dashboard's default (landing) view; everything else is general branch-level behaviour, not tied to that one page.</p>
                <ul class="ps-3 mb-2">
                    <li class="mb-2"><strong>Cloud sales</strong> — a general, background setting: whether this branch's sales auto-upload to the cloud, how often (in minutes), and whether staff can clear <em>pending</em> cloud sales — sales sitting in local storage on the device that haven't uploaded to the cloud yet.</li>
                    <li class="mb-2"><strong>Dashboard display</strong> — the only setting scoped to the Sales Dashboard's default view. Each toggle shows or hides one specific widget or shortcut on that landing page: yesterday's sales, price changes, this month's delivery notes, this month's sales, today's customer count, the Regular/Emergency order shortcuts, and the low-stock alert widget. Switch one off and that widget won't appear when staff open the dashboard.</li>
                    <li class="mb-2"><strong>Auto-refresh</strong> — a general page-behaviour setting: reloads whichever sales page is open on the interval set here. Not limited to the landing view.</li>
                    <li class="mb-0"><strong>Idle timeout / session lifetime</strong> — a general session rule for this branch: signs sales staff out after inactivity, or after a fixed number of minutes regardless of activity, no matter which page they're on. Leave a switch off to disable that limit.</li>
                </ul>
                <p class="mb-0">Any switch left off for auto-upload, auto-refresh, idle timeout, or session lifetime disables that behaviour entirely — the linked minutes field next to it is ignored.</p>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
$(document).ready(function () {
    toastr.options = {
        closeButton: true, progressBar: true, showMethod: 'slideDown',
        timeOut: 5000, allowHtml: true
    };

    /* Every switch that has a data-target links to a number input — keep
       the input's enabled state in sync with the switch, both on load and
       on every toggle. */
    function syncLinkedInput($switch) {
        var targetId = $switch.data('target');
        var $input   = $('#' + targetId);
        $input.prop('disabled', !$switch.is(':checked'));
    }
    $('.bss-switch[data-target]').each(function () { syncLinkedInput($(this)); });
    $(document).on('change', '.bss-switch[data-target]', function () { syncLinkedInput($(this)); });

    $('#infoBtn').on('click', function (e) {
        e.preventDefault();
        $('#infoModal').modal('show');
    });

    $('#saveSettingsBtn').on('click', function (e) {
        e.preventDefault();
        var $btn  = $(this);
        var $form = $('#salesSettingsForm');

        // A disabled input is never sent with the form — which is exactly
        // what we want for the plain-nullable fields (idle timeout, session
        // lifetime, max discount): switch off -> field excluded -> server
        // sees it empty -> stores null ("disabled").
        var payload = $form.serialize();

        $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });

        $btn.addClass('disabled');
        $.ajax({
            type: 'POST',
            url: '{{ route("tenant.admin.branch.sales.settings.update") }}',
            data: payload,
            timeout: 60000,
            beforeSend: function () { $('#progressBar').show(); },
            complete: function () { $('#progressBar').hide(); $btn.removeClass('disabled'); },
            success: function (data) {
                if (data.status === 201) {
                    toastr.success(data.success || 'Settings saved.', 'Success');
                    setTimeout(function () { location.reload(); }, 1200);
                } else if (data.status === 422) {
                    var msg = '';
                    $.each(data.errors || {}, function (key, value) {
                        msg += (Array.isArray(value) ? value.join('<br>') : value) + '<br>';
                    });
                    toastr.error(msg || 'Validation failed.', 'Error');
                } else {
                    toastr.info('Unspecified error occurred.', 'Error');
                }
            },
            error: function (xhr, status) {
                if (status === 'timeout') {
                    toastr.error('The request timed out. Please check your internet connection and try again.', 'Timeout Error');
                } else if (xhr.status === 0) {
                    toastr.error('Unable to connect. Please check your internet connection and try again.', 'Connection Error');
                } else if (xhr.status === 422) {
                    var msg = '';
                    $.each((xhr.responseJSON && xhr.responseJSON.errors) || {}, function (key, value) {
                        msg += (Array.isArray(value) ? value.join('<br>') : value) + '<br>';
                    });
                    toastr.error(msg, 'Validation Errors');
                } else {
                    toastr.error('Unspecified error occurred. Try again later.', 'Unspecified Error');
                }
            }
        });
    });
});
</script>
@endsection