@extends('operations.retail.dashboard')

{{--
    resources/views/operations/retail/dashboard-settings.blade.php

    Settings page for OperationsDashboardSettingsController. Extends
    operations/retail/dashboard.blade.php (the Retail sidebar/layout) rather
    than operations/dashboard.blade.php (the Operations landing hub) — this
    page now lives under the Retail sector routes (sector:Retail middleware:
    Admin always, or Operations staff with a Retail row), not the Operations
    hub (role:Operations). Sidebar link lives in that layout under
    System > Settings (route: retail.operations.dashboard.settings).

    operations_dashboard_settings is per-user — every Operations user gets
    their own row, created lazily on first save. $hasSavedSettings (from
    OperationsDashboardSettingsController::showSettingsView) drives the
    "not saved yet" notice/modal below.

    Note: operations/retail/dashboard.blade.php does NOT auto-wrap
    @yield('content') and has no @yield('styles') — same pattern as
    tenants/admin/dashboard.blade.php, unlike operations/dashboard.blade.php
    (the old hub layout, which self-wrapped). So — unlike the previous
    version of this file — content here is manually wrapped in
    .content-page > .content > .container-fluid, and styles stay inline
    within @section('content'), matching
    tenants/admin/dashboard-settings-admin.blade.php's pattern.

    Header bar and modal styling copied from operations/retail/branchproducts.blade.php
    (blue gradient .card-header, .mh-blue modal header) so this page matches
    that visual language.

    UX: a "Save Changes" button sits at the bottom right of the card and
    submits the form directly — no confirmation modal, no change-tracking.

    NOTE: an Admin can reach this same route (sector:Retail lets Admin
    through) — OperationsDashboardSettingsController::showSettingsView
    detects that and delegates straight to AdminDashboardSettingsController,
    which returns tenants.admin.dashboard-settings-admin instead. This file
    only ever renders for an Operations-role viewer.
--}}

@section('content')

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

<form action="{{ route('retail.operations.dashboard.settings.update', ['tenantName' => $tenantName]) }}" method="POST" id="operationsSettingsForm" autocomplete="off">
@csrf
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h4 class="mb-0"><i class="ri-team-line"></i> Operations Dashboard Settings</h4>
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
                <select class="form-select" name="default_landing_sector" data-default="">
                    <option value="">— No default (land on the Operations dashboard) —</option>
                    @foreach($availableSectors as $sector)
                        <option value="{{ $sector }}" {{ old('default_landing_sector', optional($operationsSettings)->default_landing_sector) === $sector ? 'selected' : '' }}>
                            {{ $sector }}
                        </option>
                    @endforeach
                </select>
                <div class="settings-help">If set and you have access to this sector, you'll skip the Operations dashboard and land here directly after login. If left blank, you'll always land on the Operations dashboard and pick a sector from there.</div>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label d-block">Sector Switching</label>
                <div class="form-check form-switch">
                    <input type="hidden" name="allow_sector_switching" value="0">
                    <input class="form-check-input" type="checkbox" role="switch" name="allow_sector_switching" value="1"
                           id="allow_sector_switching" data-default-checked="0" {{ old('allow_sector_switching', optional($operationsSettings)->allow_sector_switching) ? 'checked' : '' }}>
                    <label class="form-check-label" for="allow_sector_switching">Allowed</label>
                </div>
            </div>
        </div>

        <div class="settings-group-title">Session &amp; Security</div>
        <div class="row">
            <div class="col-md-4 mb-3">
                <label class="form-label d-block">Idle Timeout</label>
                <div class="form-check form-switch">
                    <input type="hidden" name="idle_timeout_enabled" value="0">
                    <input class="form-check-input" type="checkbox" role="switch" name="idle_timeout_enabled" value="1"
                           id="idle_timeout_enabled" data-default-checked="1" {{ old('idle_timeout_enabled', optional($operationsSettings)->idle_timeout_enabled) ? 'checked' : '' }}>
                    <label class="form-check-label" for="idle_timeout_enabled">Enabled</label>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label">Idle Timeout (minutes)</label>
                <input type="number" class="form-control" name="idle_timeout_minutes" min="0.1" max="1440" step="0.1" data-default="30" autocomplete="off"
                       value="{{ old('idle_timeout_minutes', optional($operationsSettings)->idle_timeout_minutes ?? 30) }}" required>
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label">Session Lifetime (minutes)</label>
                <input type="number" class="form-control @error('session_lifetime_minutes') is-invalid @enderror" name="session_lifetime_minutes" min="0.1" max="10080" step="0.1" data-default="90" autocomplete="off"
                       value="{{ old('session_lifetime_minutes', optional($operationsSettings)->session_lifetime_minutes ?? 90) }}" required>
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
     form="operationsSettingsForm" attribute, independent of Reset
     Defaults — this is the "Use These Defaults" banner action, kept
     separate so a first-time user has a one-click way to create their
     settings row without touching anything first. --}}
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
      <button type="submit" form="operationsSettingsForm" class="btn btn-primary btn-sm">
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
      <p>These settings are personal to your account — they don't affect any other Operations user on this tenant.</p>
      <p><strong>Default Landing Sector</strong> only affects what happens right after login. If it's set, users with access to that sector skip straight past this dashboard. It has no effect once you're already logged in — you can always reach this page from the sidebar.</p>
      <p class="mb-0">To manage a specific sector day-to-day (Retail, etc.), use the <strong>Sectors</strong> links in the sidebar rather than this settings page.</p>
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
     OperationsDashboardSettingsController::updateSettings). --}}
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
    var form = document.getElementById('operationsSettingsForm');
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