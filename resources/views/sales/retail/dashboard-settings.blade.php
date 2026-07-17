@extends('sales.retail.dashboard')

{{--
    resources/views/sales/retail/dashboard-settings.blade.php

    Restyled to match operations/dashboard-settings.blade.php exactly:
      - inline <style> lives inside @section('content') (no separate
        @section('styles') block — sales.retail.dashboard doesn't need one
        the way operations/dashboard.blade.php's @yield('styles') pattern
        does, so keeping the style block in 'content' matches how
        tenants/admin/dashboard-settings-admin.blade.php does it too).
      - card-footer "Save Changes" button submits the form directly — no
        confirmation modal, no client-side change-tracking. Replaces the
        earlier pending-changes-badge/modal version of this page.
      - "Defaults" header button opens a Reset-Defaults info modal
        (data-default / data-default-checked attributes drive the reset,
        same script as Operations).
      - Outer boundary: sales.retail.dashboard's @yield('content') does NOT
        wrap itself in .content-page > .content > .container-fluid the way
        operations/dashboard.blade.php does — every sales content page has
        to supply that wrapper itself (see products_blade.php, which does
        the exact same <div class="content-page"><div class="content">
        <div class="container-fluid"> ... </div></div></div> around its
        card, closing it BEFORE its modals). This page follows that same
        pattern below; without it the page renders outside the sidebar
        layout's intended width/padding.

    Sales settings live per sector (Retail here, same pattern as
    sales/retail/dashboard, sales/retail/profile) rather than at a
    sales-wide level, so this page and its route sit under sales/retail/.
    sales_dashboard_settings is per-user, same as operations_dashboard_settings.

    Differences from the Operations version (unavoidable, not stylistic):
      - No "Sector Switching" toggle — that concept doesn't apply to Sales.
      - "Default Landing Sector" stays on the form but is disabled: Sales
        users are tied to a branch, not a sector, so this field does nothing
        yet (see the migration's comment). Disabled inputs never submit and
        have no data-default — Reset Defaults doesn't touch them.
      - No separate Branch/Sector readouts — folded into the Default
        Landing Sector field itself, which displays "Sector (Branch)" from
        $myBranch (fetched in SalesDashboardSettingsController::
        showSettingsView() via Auth::user()->branch -> branches table), with
        help text noting it's determined by an Admin, not set here.
--}}

@section('content')

@php
    // $myBranch can be null (user not yet assigned a branch) — compute the
    // display string once here instead of inline in the blade, so there's
    // no risk of touching ->sector / ->name on a null object.
    $landingSectorDisplay = $myBranch
        ? $myBranch->sector . ($myBranch->name ? ' (' . $myBranch->name . ')' : '')
        : '— Not assigned —';
@endphp

<style>
    .card        { border:none; box-shadow:0 4px 8px rgba(0,0,0,0.1); border-radius:10px; }
    .card-header { padding:0.5rem 1.5rem !important; background:linear-gradient(to right,#4B5EBD,#576CC0); color:#fff; border-radius:10px 10px 0 0 !important; flex-wrap:wrap; gap:8px; }
    .card-body   { padding:1.25rem 1.5rem 1.5rem 1.5rem !important; }
    .card-header h4 { color:#fff; font-weight:600; margin-bottom:0; display:flex; align-items:center; gap:6px; }
    .card-header-actions { display:flex; align-items:center; gap:4px; flex-wrap:wrap; justify-content:flex-end; }
    .card-header .btn-light { height:28px; padding:0 10px; display:flex; align-items:center; justify-content:center; line-height:1; }
    .card-header .btn-light:hover { background-color:#f8f9fa; transition:background-color 0.2s; }

    .mh-blue  { background:linear-gradient(135deg,#4B5EBD,#576CC0); padding:14px 18px !important; border-bottom:none; border-radius:8px 8px 0 0; }
    .mh-title { color:#fff; font-size:15px; font-weight:600; display:flex; align-items:center; gap:6px; }
    .mh-close { filter:brightness(0) invert(1); opacity:.8; }
    .mh-close:hover { opacity:1; }

    .settings-group-title { font-size: 13px; text-transform: uppercase; letter-spacing: .04em; color: #6c757d; font-weight: 700; margin: 1.5rem 0 .75rem; }
    .settings-group-title:first-child { margin-top: 0; }
    .settings-help { font-size: 12.5px; color: #8a92a5; margin-top: .25rem; }
    .form-check.form-switch { padding-left: 2.75em; min-height: 38px; display: flex; align-items: center; }
    .form-check.form-switch .form-check-input { width: 2.25em; margin-left: -2.75em; }
    .form-check.form-switch .form-check-label { margin-left: .25rem; }
</style>

<div class="content-page"><div class="content"><div class="container-fluid">
<div class="row mb-3"></div>

@unless($hasSavedSettings)
<div class="alert d-flex align-items-center justify-content-between flex-wrap gap-2" id="noSettingsNotice"
     style="background:#eef2ff;border:1px solid #c7d2fe;border-radius:8px;padding:12px 16px;margin-bottom:1rem;">
    <div class="d-flex align-items-center gap-2">
        <i class="ri-information-line fs-18" style="color:#4B5EBD;"></i>
        <span style="font-size:13.5px;color:#334155;">No settings saved yet for your account — the defaults shown below are currently active. Save them to lock these in, or change anything first.</span>
    </div>
    <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#confirmDefaultsModal">
        <i class="ri-save-line me-1"></i> Use These Defaults
    </button>
</div>
@endunless

<form action="{{ route('retail.sales.dashboard.settings.update', ['tenantName' => $tenantName]) }}" method="POST" id="salesSettingsForm" autocomplete="off">
@csrf
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h4 class="mb-0"><i class="ri-team-line"></i> Sales Dashboard Settings</h4>
        <div class="card-header-actions">
            <button type="button" class="btn btn-light text-primary fs-16 mx-1" id="defaultsSettingsBtn" title="Defaults" data-bs-toggle="modal" data-bs-target="#defaultsInfoModal">
                <i class="ri-settings-4-line"></i>
            </button>
            <a href="#" class="btn btn-light text-primary fs-16 mx-1" id="infoBtn" title="About These Settings" data-bs-toggle="modal" data-bs-target="#settingsInfoModal"><i class="ri-information-line"></i></a>
        </div>
    </div>
    <div class="card-body">

        <div class="settings-group-title">Landing &amp; Navigation</div>
        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">Default Landing Sector</label>
                <select class="form-select" name="default_landing_sector" disabled>
                    <option value="" selected>{{ $landingSectorDisplay }}</option>
                </select>
                <div class="settings-help">Determined by your Admin, not set here.</div>
            </div>
        </div>

        <div class="settings-group-title">Session &amp; Security</div>
        <div class="row">
            <div class="col-md-4 mb-3">
                <label class="form-label d-block">Idle Timeout</label>
                <div class="form-check form-switch">
                    <input type="hidden" name="idle_timeout_enabled" value="0">
                    <input class="form-check-input" type="checkbox" role="switch" name="idle_timeout_enabled" value="1"
                           id="idle_timeout_enabled" data-default-checked="1" {{ old('idle_timeout_enabled', $salesSettings->idle_timeout_enabled) ? 'checked' : '' }}>
                    <label class="form-check-label" for="idle_timeout_enabled">Enabled</label>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label">Idle Timeout (minutes)</label>
                <input type="number" class="form-control" name="idle_timeout_minutes" min="0.1" max="1440" step="0.1" data-default="30" autocomplete="off"
                       value="{{ old('idle_timeout_minutes', $salesSettings->idle_timeout_minutes) }}" required>
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label">Session Lifetime (minutes)</label>
                <input type="number" class="form-control @error('session_lifetime_minutes') is-invalid @enderror" name="session_lifetime_minutes" min="0.1" max="10080" step="0.1" data-default="90" autocomplete="off"
                       value="{{ old('session_lifetime_minutes', $salesSettings->session_lifetime_minutes) }}" required>
                @error('session_lifetime_minutes')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
                <div class="settings-help">Must be greater than Idle Timeout.</div>
            </div>
        </div>

    </div>
    <div class="card-footer d-flex justify-content-end" style="background:#fff;border-top:1px solid #eef0f7;padding:14px 1.5rem;">
        <button type="submit" class="btn btn-primary">
            <i class="ri-save-line me-1"></i> Save Changes
        </button>
    </div>
</div>
</form>
</div></div></div>

{{-- ══ DEFAULTS INFO MODAL ══ --}}
<div class="modal fade" id="defaultsInfoModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog"><div class="modal-content" style="border:none;border-radius:10px;overflow:hidden;box-shadow:0 8px 32px rgba(0,0,0,0.18);">
    <div class="modal-header mh-blue">
      <h5 class="modal-title mh-title"><i class="ri-settings-4-line"></i> Defaults</h5>
      <button type="button" class="btn-close mh-close" data-bs-dismiss="modal"></button>
    </div>
    <div class="modal-body" style="padding:18px 20px !important;font-size:13.5px;color:#334155;">
      @if($hasSavedSettings)
      <p class="mb-0">This resets every field below to the system defaults and saves immediately.</p>
      @else
      <p class="mb-0">No settings saved yet for your account — the defaults shown below are currently active. Reset to lock these in, or change anything first.</p>
      @endif
    </div>
    <div class="modal-footer" style="padding:10px 20px 14px;">
      <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
      <button type="button" class="btn btn-outline-primary btn-sm" id="resetDefaultsBtn">
        <i class="ri-restart-line me-1"></i> Reset Defaults
      </button>
    </div>
  </div></div>
</div>

{{-- ══ CONFIRM DEFAULTS MODAL ══
     For a user with no saved row: submits the form as-is via the
     form="salesSettingsForm" attribute, independent of Reset Defaults —
     this is the "Use These Defaults" banner action, kept separate so a
     first-time user has a one-click way to create their settings row
     without touching anything first. --}}
<div class="modal fade" id="confirmDefaultsModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog"><div class="modal-content" style="border:none;border-radius:10px;overflow:hidden;box-shadow:0 8px 32px rgba(0,0,0,0.18);">
    <div class="modal-header mh-blue">
      <h5 class="modal-title mh-title"><i class="ri-save-line"></i> Save Default Settings</h5>
      <button type="button" class="btn-close mh-close" data-bs-dismiss="modal"></button>
    </div>
    <div class="modal-body" style="padding:18px 20px !important;font-size:13.5px;color:#334155;">
      <p class="mb-0">You haven't saved any settings yet, so the values shown on this page are just defaults, applied automatically. Saving creates your own settings record with these exact values — you can change any of them at any time after.</p>
    </div>
    <div class="modal-footer" style="padding:10px 20px 14px;">
      <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
      <button type="submit" form="salesSettingsForm" class="btn btn-primary btn-sm">
        <i class="ri-save-line me-1"></i> Save Defaults
      </button>
    </div>
  </div></div>
</div>

{{-- ══ ABOUT / INFO MODAL ══ --}}
<div class="modal fade" id="settingsInfoModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog"><div class="modal-content" style="border:none;border-radius:10px;overflow:hidden;box-shadow:0 8px 32px rgba(0,0,0,0.18);">
    <div class="modal-header mh-blue">
      <h5 class="modal-title mh-title"><i class="ri-information-line"></i> About These Settings</h5>
      <button type="button" class="btn-close mh-close" data-bs-dismiss="modal"></button>
    </div>
    <div class="modal-body" style="padding:18px 20px !important;font-size:13.5px;color:#334155;">
      <p>These settings are personal to your account — they don't affect any other Sales user on this tenant.</p>
      <p class="mb-0"><strong>Default Landing Sector</strong> is disabled — it's set by an Admin, not from this page. Sales users land based on branch access, not a chosen sector.</p>
    </div>
    <div class="modal-footer" style="padding:10px 20px 14px;">
      <button type="button" class="btn btn-primary btn-sm" data-bs-dismiss="modal">Got it</button>
    </div>
  </div></div>
</div>

@endsection

@section('scripts')
{{-- Server-flashed result of the last form submission (Save Changes or
     Reset Defaults both redirect back with these session keys — see
     SalesDashboardSettingsController::updateSettings). --}}
@if(session('message'))
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (typeof toastr !== 'undefined') {
        toastr['{{ session('alert-type') === 'error' ? 'error' : 'success' }}']({!! json_encode(session('message')) !!});
    }
});
</script>
@endif
<script>
document.addEventListener('DOMContentLoaded', function () {
    var form = document.getElementById('salesSettingsForm');
    var resetBtn = document.getElementById('resetDefaultsBtn');
    if (!form || !resetBtn) return;

    resetBtn.addEventListener('click', function () {
        form.querySelectorAll('[data-default]').forEach(function (el) {
            el.value = el.getAttribute('data-default');
        });
        form.querySelectorAll('[data-default-checked]').forEach(function (el) {
            el.checked = el.getAttribute('data-default-checked') === '1';
        });

        var modalEl = document.getElementById('defaultsInfoModal');
        var modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
        modal.hide();

        // Reset Defaults saves immediately — click the real Save Changes
        // button so validation/submission run exactly as they would for a
        // manual save (no separate confirmation step).
        var submitBtn = form.querySelector('button[type="submit"]');
        if (submitBtn) {
            submitBtn.click();
        } else if (form.requestSubmit) {
            form.requestSubmit();
        } else {
            form.submit();
        }
    });
});
</script>
@endsection