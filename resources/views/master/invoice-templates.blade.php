@extends('master.dashboard')
@section('content')

{{-- ══════════════════════════════════════════════════════════
     CodeMirror — loaded from CDN, no npm needed
     ══════════════════════════════════════════════════════════ --}}
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.17/codemirror.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.17/theme/dracula.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.17/codemirror.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.17/mode/htmlmixed/htmlmixed.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.17/mode/xml/xml.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.17/mode/javascript/javascript.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.17/mode/css/css.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.17/addon/edit/matchbrackets.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.17/addon/edit/closetag.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.17/addon/comment/comment.min.js"></script>
<link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">

<style>
/* ══════════════════════════════════════════════════════════
   TOP BAR
   ══════════════════════════════════════════════════════════ */
.card-header {
    padding: 0.5rem 1.5rem !important;
    background: linear-gradient(to right, #4B5EBD, #576CC0);
    color: #fff;
    border-radius: 10px 10px 0 0;
}
.card-header h4 {
    color: #fff;
    font-weight: 600;
    margin: 0;
    font-size: 1.1rem;
}
.card-header .btn-light {
    height: 28px;
    padding: 0 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
}
.card-header .btn-light:hover { background-color: #f8f9fa; }

/* ══════════════════════════════════════════════════════════
   CONTROLS BAR
   ══════════════════════════════════════════════════════════ */
.controls-section {
    background: #fff;
    padding: 1.2rem 1.5rem;
    border-radius: 0 0 10px 10px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    margin-top: -1px;
}
.controls-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 0.75rem;
}
#searchInput {
    max-width: 300px;
    border-radius: 20px;
    padding-left: 2.5rem;
    background: #f8f9fa url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='%236c757d' viewBox='0 0 16 16'%3E%3Cpath d='M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1.007 1.007 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0z'/%3E%3C/svg%3E") no-repeat 0.75rem center;
    background-size: 16px;
    transition: all 0.2s;
}
#searchInput:focus {
    background-color: #fff;
    box-shadow: 0 0 0 0.2rem rgba(75,94,189,0.25);
}
@media (max-width: 768px) {
    .controls-row { flex-direction: column; align-items: stretch; }
    #searchInput  { max-width: 100%; }
}

/* ══════════════════════════════════════════════════════════
   TEMPLATE CARDS
   ══════════════════════════════════════════════════════════ */
#templateCards { margin-top: 0.8rem !important; }

.template-card {
    width: 300px;
    height: 400px;
    background: #fff;
    border: none;
    border-radius: 16px;
    box-shadow: 0 8px 24px rgba(0,0,0,0.12);
    transition: all 0.3s cubic-bezier(0.25,0.8,0.25,1);
    display: flex;
    flex-direction: column;
    overflow: hidden;
    margin: 0.5rem auto;
    position: relative;
}
.template-card::before {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0;
    height: 4px;
    background: linear-gradient(90deg, #4B5EBD, #6c7bd8);
    border-radius: 16px 16px 0 0;
}
.template-card:hover {
    transform: translateY(-10px);
    box-shadow: 0 16px 32px rgba(0,0,0,0.18);
}

/* Card header */
.card-header-section {
    padding: 1.2rem 1.2rem 0.75rem;
    text-align: center;
}
.card-title {
    font-size: 1.15rem;
    font-weight: 700;
    color: #2c3e50;
    margin: 0 0 0.4rem;
    line-height: 1.3;
}
.badge-default {
    font-size: 0.7rem;
    font-weight: 600;
    padding: 0.3em 0.8em;
    border-radius: 50px;
    background: #28a745;
    color: #fff;
    text-transform: uppercase;
    letter-spacing: 0.7px;
    box-shadow: 0 2px 4px rgba(40,167,69,0.3);
}

/* Card mini-preview area */
.card-preview {
    flex: 1;
    padding: 0.75rem;
    background: #f8f9fb;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
}
.card-preview-iframe {
    width: 100%;
    height: 100%;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    background: #fff;
    pointer-events: none;
    box-shadow: inset 0 2px 6px rgba(0,0,0,0.06);
}
.card-preview-empty {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 0.4rem;
    color: #adb5bd;
    height: 100%;
}
.card-preview-empty i    { font-size: 2.5rem; }
.card-preview-empty span { font-size: 0.8rem; }

/* Card action buttons */
.card-actions {
    padding: 0.85rem;
    display: flex;
    justify-content: center;
    gap: 10px;
    background: #f9f9f9;
    border-top: 1px solid #eee;
}
.btn-icon {
    width: 44px;
    height: 44px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    font-size: 1.15rem;
    transition: all 0.3s ease;
    box-shadow: 0 4px 10px rgba(0,0,0,0.12);
    position: relative;
    overflow: hidden;
}
.btn-icon::after {
    content: '';
    position: absolute;
    top: 50%; left: 50%;
    width: 0; height: 0;
    background: rgba(255,255,255,0.3);
    border-radius: 50%;
    transform: translate(-50%,-50%);
    transition: width 0.4s, height 0.4s;
}
.btn-icon:active::after { width: 200px; height: 200px; }

.btn-preview { background: linear-gradient(135deg,#e3f2fd,#bbdefb); color: #1565c0; }
.btn-preview:hover { background: linear-gradient(135deg,#bbdefb,#90caf9); transform: scale(1.18); box-shadow: 0 6px 16px rgba(21,101,192,0.4); }

.btn-code { background: linear-gradient(135deg,#f3e5f5,#e1bee7); color: #6a1b9a; }
.btn-code:hover { background: linear-gradient(135deg,#e1bee7,#ce93d8); transform: scale(1.18); box-shadow: 0 6px 16px rgba(106,27,154,0.4); }

.btn-edit { background: linear-gradient(135deg,#e8f5e8,#c8e6c9); color: #2e7d32; }
.btn-edit:hover { background: linear-gradient(135deg,#c8e6c9,#a5d6a7); transform: scale(1.18); box-shadow: 0 6px 16px rgba(46,125,50,0.4); }

.btn-delete { background: linear-gradient(135deg,#ffebee,#ffcdd2); color: #c62828; }
.btn-delete:hover { background: linear-gradient(135deg,#ffcdd2,#ef9a9a); transform: scale(1.18); box-shadow: 0 6px 16px rgba(198,40,40,0.4); }

/* ══════════════════════════════════════════════════════════
   FULL PREVIEW MODAL
   ══════════════════════════════════════════════════════════ */
#previewModal .modal-dialog { max-width: 900px; margin: 1.75rem auto; }
#previewModal .preview-scroll { overflow: auto; max-height: 82vh; }
#previewModal .preview-scroll iframe {
    width: 100%; height: 800px; border: none; display: block;
}

/* ══════════════════════════════════════════════════════════
   CODE EDITOR MODAL
   ══════════════════════════════════════════════════════════ */
#codeEditorModal .modal-dialog {
    max-width: 98vw;
    width: 1400px;
    margin: 0.75rem auto;
}
#codeEditorModal .modal-content {
    border-radius: 12px;
    overflow: hidden;
    border: none;
    box-shadow: 0 20px 60px rgba(0,0,0,0.4);
}
#codeEditorModal .modal-header {
    background: #1e1e2e;
    border-bottom: 1px solid #313244;
    padding: 0.65rem 1.25rem;
}
#codeEditorModal .modal-title {
    color: #cdd6f4;
    font-size: 0.92rem;
    font-weight: 600;
}
#codeEditorModal .modal-body { padding: 0; background: #1e1e2e; }

/* Split layout */
.editor-layout { display: flex; height: 84vh; overflow: hidden; }

/* Left — CodeMirror pane */
.editor-pane {
    width: 55%;
    display: flex;
    flex-direction: column;
    border-right: 2px solid #313244;
    background: #1e1e2e;
}
.editor-toolbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0.45rem 1rem;
    background: #181825;
    border-bottom: 1px solid #313244;
    flex-shrink: 0;
}
.editor-toolbar .tl { display: flex; align-items: center; gap: 0.5rem; }
.editor-toolbar .tr { display: flex; align-items: center; gap: 0.5rem; }
.lang-badge {
    background: #45475a;
    color: #cdd6f4;
    font-size: 0.7rem;
    font-weight: 600;
    padding: 0.2em 0.7em;
    border-radius: 4px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}
.editor-filename { color: #a6adc8; font-size: 0.8rem; }

.editor-status {
    font-size: 0.72rem;
    padding: 0.2em 0.7em;
    border-radius: 4px;
    font-weight: 700;
}
.editor-status.saved   { background: #a6e3a1; color: #1e1e2e; }
.editor-status.unsaved { background: #f38ba8; color: #1e1e2e; }
.editor-status.saving  { background: #fab387; color: #1e1e2e; }
.editor-status.loading { background: #89b4fa; color: #1e1e2e; }

#codeEditorWrapper { flex: 1; overflow: hidden; position: relative; }
#codeEditorWrapper .CodeMirror {
    height: 100%;
    font-size: 13.5px;
    font-family: 'JetBrains Mono', 'Fira Code', monospace;
    line-height: 1.65;
}

/* Loading overlay inside editor pane */
#editorLoadingOverlay {
    position: absolute; inset: 0;
    background: rgba(30,30,46,0.88);
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    z-index: 10;
    color: #cdd6f4;
    gap: 0.75rem;
    font-size: 0.9rem;
}
.editor-spinner {
    width: 34px; height: 34px;
    border: 3px solid #45475a;
    border-top-color: #89b4fa;
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
}
@keyframes spin { to { transform: rotate(360deg); } }

/* Keyboard hints bar */
.editor-bottom-bar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0.5rem 1rem;
    background: #181825;
    border-top: 1px solid #313244;
    flex-shrink: 0;
}
.editor-bottom-bar .hints { color: #585b70; font-size: 0.72rem; }
.editor-bottom-bar .hints kbd {
    background: #313244;
    color: #cdd6f4;
    padding: 0.1em 0.35em;
    border-radius: 3px;
    font-size: 0.7rem;
    border: 1px solid #45475a;
}

/* Right — live preview pane */
.preview-pane { width: 45%; display: flex; flex-direction: column; }
.preview-pane-toolbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0.45rem 1rem;
    background: #181825;
    border-bottom: 1px solid #313244;
    flex-shrink: 0;
}
.preview-pane-toolbar span { color: #a6adc8; font-size: 0.8rem; }
#livePreviewFrame { flex: 1; border: none; width: 100%; background: #fff; }
</style>

{{-- Progress bar --}}
<div class="progress" id="progressBar" style="height:8px;display:none">
    <div class="progress-bar progress-bar-striped progress-bar-animated" style="width:100%"></div>
</div>

<div class="content-page">
<div class="content">
<div class="container-fluid">
<div class="row mb-3"></div>

{{-- ══════════ TOP BAR ══════════ --}}
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h4 class="header-title mb-0">
            <i class="ri-file-list-3-line"></i> Invoice Templates
        </h4>
        <a href="#" class="btn btn-light text-primary" id="infoBtn" title="How it works">
            <i class="ri-information-line"></i>
        </a>
    </div>
    <div class="controls-section">
        <div class="controls-row">
            <a href="#" class="btn btn-primary" id="newTemplateBtn">
                <i class="ri-add-circle-line"></i> Add New Template
            </a>
            <input type="text" class="form-control" id="searchInput" placeholder="Search templates…">
        </div>
    </div>
</div>

{{-- ══════════ CARDS ══════════ --}}
<div class="row g-3 mt-1" id="templateCards">
    @foreach($templates as $tpl)
        <div class="col-md-4 col-lg-3 d-flex justify-content-center template-card-wrapper" id="card{{ $tpl->id }}">
            <div class="template-card">

                <div class="card-header-section">
                    <h5 class="card-title">{{ $tpl->name }}</h5>
                    @if($tpl->is_default)
                        <span class="badge-default">Default</span>
                    @endif
                </div>

                <div class="card-preview">
                    @if($tpl->content)
                        <iframe class="card-preview-iframe"
                                srcdoc="{{ htmlspecialchars($tpl->content, ENT_QUOTES, 'UTF-8') }}"
                                sandbox="allow-same-origin"
                                loading="lazy"></iframe>
                    @else
                        <div class="card-preview-empty">
                            <i class="ri-file-edit-line"></i>
                            <span>No content yet — click <i class="ri-code-s-slash-line"></i></span>
                        </div>
                    @endif
                </div>

                <div class="card-actions">
                    <a href="#" class="btn btn-icon btn-preview previewBtn"
                       data-id="{{ $tpl->id }}"
                       data-name="{{ $tpl->name }}"
                       title="Full Preview">
                        <i class="ri-eye-line"></i>
                    </a>
                    <a href="#" class="btn btn-icon btn-code codeEditorBtn"
                       data-id="{{ $tpl->id }}"
                       data-name="{{ $tpl->name }}"
                       title="Edit HTML / CSS">
                        <i class="ri-code-s-slash-line"></i>
                    </a>
                    <a href="#" class="btn btn-icon btn-edit editTemplateBtn"
                       data-id="{{ $tpl->id }}"
                       data-name="{{ $tpl->name }}"
                       data-default="{{ $tpl->is_default ? '1' : '0' }}"
                       title="Settings">
                        <i class="ri-settings-3-line"></i>
                    </a>
                    <a href="#" class="btn btn-icon btn-delete deleteTemplateBtn"
                       data-id="{{ $tpl->id }}"
                       data-name="{{ $tpl->name }}"
                       title="Delete">
                        <i class="ri-delete-bin-line"></i>
                    </a>
                </div>

            </div>
        </div>
    @endforeach
</div>

</div>
</div>
</div>

{{-- ══════════════════════════════════════════════════════════
     INFO MODAL
     ══════════════════════════════════════════════════════════ --}}
<div class="modal fade" id="infoModal" data-bs-backdrop="static" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="ri-information-line"></i> Invoice Templates</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <ul class="mb-0" style="line-height:2">
                    <li>Templates are stored <strong>entirely in the database</strong> — no blade files involved.</li>
                    <li>Click <strong><i class="ri-code-s-slash-line"></i></strong> to open the built-in HTML/CSS editor.</li>
                    <li>The right pane shows a <strong>live preview</strong> as you type.</li>
                    <li><kbd>Ctrl+S</kbd> / <kbd>Cmd+S</kbd> saves without clicking the button.</li>
                    <li>Click <strong><i class="ri-eye-line"></i></strong> to open the full rendered preview.</li>
                    <li>Click <strong><i class="ri-settings-3-line"></i></strong> to rename or change the default flag.</li>
                </ul>
            </div>
        </div>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════════
     ADD MODAL
     ══════════════════════════════════════════════════════════ --}}
<div class="modal fade" id="addTemplateModal" data-bs-backdrop="static" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="ri-add-circle-line"></i> Add New Template</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="addTemplateForm">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Template Name <span class="text-danger">*</span></label>
                        <input class="form-control" name="name" required placeholder="e.g. Standard Invoice">
                        <div class="form-text">Must be unique across all templates.</div>
                    </div>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" name="is_default" value="1" id="addIsDefault">
                        <label class="form-check-label" for="addIsDefault">Set as default template</label>
                    </div>
                    <div class="d-flex justify-content-end gap-2 mt-3">
                        <button type="button" class="btn btn-secondary" id="cancelAddTemplateBtn">Cancel</button>
                        <button type="button" class="btn btn-primary"   id="submitAddTemplateBtn">
                            <i class="ri-save-line"></i> Create
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════════
     EDIT SETTINGS MODAL
     ══════════════════════════════════════════════════════════ --}}
<div class="modal fade" id="editTemplateModal" data-bs-backdrop="static" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="ri-settings-3-line"></i> Template Settings</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="editTemplateForm">
                    @csrf
                    <input type="hidden" name="id" id="editTemplateId">
                    <div class="mb-3">
                        <label class="form-label">Template Name <span class="text-danger">*</span></label>
                        <input class="form-control" name="name" id="editTemplateName" required>
                    </div>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" name="is_default" value="1" id="editTemplateIsDefault">
                        <label class="form-check-label" for="editTemplateIsDefault">Set as default template</label>
                    </div>
                    <div class="d-flex justify-content-end gap-2 mt-3">
                        <button type="button" class="btn btn-secondary" id="cancelEditTemplateBtn">Cancel</button>
                        <button type="button" class="btn btn-primary"   id="submitEditTemplateBtn">
                            <i class="ri-save-line"></i> Update
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════════
     DELETE MODAL
     ══════════════════════════════════════════════════════════ --}}
<div class="modal fade" id="deleteTemplateModal" data-bs-backdrop="static" tabindex="-1">
    <div class="modal-dialog" style="max-width:370px;margin:1.75rem auto">
        <div class="modal-content">
            <div class="modal-body text-center pt-4 pb-4">
                <i class="ri-error-warning-line text-danger" style="font-size:70px"></i>
                <h4 class="mt-2">Delete <span id="deleteTemplateName" class="text-danger"></span>?</h4>
                <h6 class="text-muted">This will permanently remove the template and all its content.</h6>
                <input type="hidden" id="deleteTemplateId">
                <div class="mt-3 d-flex justify-content-center gap-2">
                    <button type="button" class="btn btn-info"   id="cancelDeleteTemplateBtn">No, Keep It</button>
                    <button type="button" class="btn btn-danger" id="submitDeleteTemplateBtn">
                        <i class="ri-delete-bin-line"></i> Yes, Delete
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════════
     FULL PREVIEW MODAL
     ══════════════════════════════════════════════════════════ --}}
<div class="modal fade" id="previewModal" data-bs-backdrop="static" tabindex="-1">
    <div class="modal-dialog" style="max-width:900px;margin:1.75rem auto">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="ri-eye-line"></i> Preview: <span id="previewModalTitle"></span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <div class="preview-scroll">
                    <iframe id="previewFrame" style="width:100%;height:800px;border:none;display:block;"></iframe>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════════
     CODE EDITOR MODAL
     ══════════════════════════════════════════════════════════ --}}
<div class="modal fade" id="codeEditorModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1">
    <div class="modal-dialog" style="max-width:98vw;width:1400px;margin:0.75rem auto">
        <div class="modal-content">

            {{-- Dark header --}}
            <div class="modal-header">
                {{-- macOS-style traffic dots --}}
                <div class="d-flex align-items-center gap-2">
                    <span style="width:12px;height:12px;border-radius:50%;background:#ff5f56;display:inline-block"></span>
                    <span style="width:12px;height:12px;border-radius:50%;background:#ffbd2e;display:inline-block"></span>
                    <span style="width:12px;height:12px;border-radius:50%;background:#27c93f;display:inline-block"></span>
                    <span class="modal-title ms-2" id="codeEditorTitle">Editing: —</span>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span class="editor-status loading" id="editorStatus">Loading…</span>
                    <button class="btn btn-sm btn-outline-secondary text-white border-secondary" id="saveCodeBtn">
                        <i class="ri-save-line"></i> Save
                    </button>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
            </div>

            <div class="modal-body">
                <div class="editor-layout">

                    {{-- Left: CodeMirror --}}
                    <div class="editor-pane">
                        <div class="editor-toolbar">
                            <div class="tl">
                                <span class="lang-badge">HTML / CSS</span>
                                <span class="editor-filename" id="editorSubtitle">—</span>
                            </div>
                            <div class="tr">
                                <button class="btn btn-sm btn-outline-secondary text-secondary border-secondary"
                                        id="wrapToggleBtn"
                                        style="font-size:0.73rem;padding:0.15rem 0.5rem"
                                        title="Toggle line wrap">
                                    <i class="ri-text-wrap"></i> Wrap
                                </button>
                                <button class="btn btn-sm btn-outline-secondary text-secondary border-secondary"
                                        id="formatBtn"
                                        style="font-size:0.73rem;padding:0.15rem 0.5rem"
                                        title="Re-indent all lines">
                                    <i class="ri-layout-left-line"></i> Format
                                </button>
                            </div>
                        </div>

                        <div id="codeEditorWrapper">
                            <textarea id="codeEditorTextarea"></textarea>
                            <div id="editorLoadingOverlay">
                                <div class="editor-spinner"></div>
                                <span>Loading template…</span>
                            </div>
                        </div>

                        <div class="editor-bottom-bar">
                            <span class="hints">
                                <kbd>Ctrl+S</kbd> Save &nbsp;·&nbsp;
                                <kbd>Ctrl+/</kbd> Comment &nbsp;·&nbsp;
                                <kbd>Ctrl+Z</kbd> Undo &nbsp;·&nbsp;
                                <kbd>Tab</kbd> Indent
                            </span>
                            <span class="hints" id="cursorPos">Ln 1, Col 1</span>
                        </div>
                    </div>

                    {{-- Right: Live preview --}}
                    <div class="preview-pane">
                        <div class="preview-pane-toolbar">
                            <span>
                                <i class="ri-eye-line" style="color:#89b4fa"></i>
                                &nbsp; Live Preview
                            </span>
                            <button class="btn btn-sm btn-outline-secondary text-secondary border-secondary"
                                    id="refreshPreviewBtn"
                                    style="font-size:0.73rem;padding:0.15rem 0.5rem">
                                <i class="ri-refresh-line"></i> Refresh
                            </button>
                        </div>
                        <iframe id="livePreviewFrame"
                                src="about:blank"
                                sandbox="allow-same-origin allow-scripts"></iframe>
                    </div>

                </div>
            </div>

        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
$(document).ready(function () {

    toastr.options = { closeButton: true, progressBar: true, timeOut: 5000 };

    // ── Route map (names stay stable even if URLs change) ────────
    const ROUTES = {
        insert:      '{{ route("master.invoice.template.insert") }}',
        update:      '{{ route("master.invoice.template.update") }}',
        delete:      '{{ route("master.invoice.template.delete") }}',
        getContent:  '{{ route("master.invoice.template.get_content") }}',
        saveContent: '{{ route("master.invoice.template.save_content") }}',
        preview:     '{{ route("master.invoice.template.preview") }}',
    };
    const CSRF = '{{ csrf_token() }}';

    // ── Search ────────────────────────────────────────────────────
    $('#searchInput').on('input', function () {
        const q = $(this).val().toLowerCase().trim();
        $('.template-card-wrapper').each(function () {
            const name  = $(this).find('.card-title').text().toLowerCase();
            const isDef = $(this).find('.badge-default').length > 0;
            $(this).toggle(!q || name.includes(q) || (q === 'default' && isDef));
        });
    });

    // ── Helpers ───────────────────────────────────────────────────
    const escText = s => $('<span>').text(s).html();
    const escAttr = s => s
        .replace(/&/g, '&amp;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');

    function buildCard(t) {
        const badge   = t.is_default
            ? `<span class="badge-default">Default</span>`
            : '';
        const preview = t.content
            ? `<iframe class="card-preview-iframe" srcdoc="${escAttr(t.content)}" sandbox="allow-same-origin" loading="lazy"></iframe>`
            : `<div class="card-preview-empty">
                   <i class="ri-file-edit-line"></i>
                   <span>No content yet — click <i class="ri-code-s-slash-line"></i></span>
               </div>`;

        return `
        <div class="col-md-4 col-lg-3 d-flex justify-content-center template-card-wrapper" id="card${t.id}">
            <div class="template-card">
                <div class="card-header-section">
                    <h5 class="card-title">${escText(t.name)}</h5>
                    ${badge}
                </div>
                <div class="card-preview">${preview}</div>
                <div class="card-actions">
                    <a href="#" class="btn btn-icon btn-preview previewBtn"
                       data-id="${t.id}" data-name="${escText(t.name)}" title="Full Preview">
                        <i class="ri-eye-line"></i>
                    </a>
                    <a href="#" class="btn btn-icon btn-code codeEditorBtn"
                       data-id="${t.id}" data-name="${escText(t.name)}" title="Edit HTML / CSS">
                        <i class="ri-code-s-slash-line"></i>
                    </a>
                    <a href="#" class="btn btn-icon btn-edit editTemplateBtn"
                       data-id="${t.id}" data-name="${escText(t.name)}"
                       data-default="${t.is_default ? '1' : '0'}" title="Settings">
                        <i class="ri-settings-3-line"></i>
                    </a>
                    <a href="#" class="btn btn-icon btn-delete deleteTemplateBtn"
                       data-id="${t.id}" data-name="${escText(t.name)}" title="Delete">
                        <i class="ri-delete-bin-line"></i>
                    </a>
                </div>
            </div>
        </div>`;
    }

    // ── Open info / add modal ─────────────────────────────────────
    $('#infoBtn').on('click', e => {
        e.preventDefault();
        $('#infoModal').modal('show');
    });

    $('#newTemplateBtn').on('click', e => {
        e.preventDefault();
        $('#addTemplateForm')[0].reset();
        $('#addTemplateModal').modal('show');
    });

    $('#cancelAddTemplateBtn').on('click', () => {
        $('#addTemplateForm')[0].reset();
        $('#addTemplateModal').modal('hide');
    });

    // ── ADD ───────────────────────────────────────────────────────
    $('#submitAddTemplateBtn').on('click', function () {
        const $btn = $(this).prop('disabled', true);
        $('#progressBar').show();

        $.post(ROUTES.insert, $('#addTemplateForm').serialize())
            .done(res => {
                if (res.status === 201) {
                    toastr.success(res.success);
                    if (res.template.is_default) $('.badge-default').remove();
                    $('#templateCards').append(buildCard(res.template));
                    $('#addTemplateModal').modal('hide');
                } else {
                    toastr.error(res.error || 'Failed to create template.');
                }
            })
            .fail(() => toastr.error('Request failed. Please try again.'))
            .always(() => { $btn.prop('disabled', false); $('#progressBar').hide(); });
    });

    // ── EDIT SETTINGS ─────────────────────────────────────────────
    $(document).on('click', '.editTemplateBtn', function (e) {
        e.preventDefault();
        $('#editTemplateId').val($(this).data('id'));
        $('#editTemplateName').val($(this).data('name'));
        $('#editTemplateIsDefault').prop('checked', String($(this).data('default')) === '1');
        $('#editTemplateModal').modal('show');
    });

    $('#cancelEditTemplateBtn').on('click', () => {
        $('#editTemplateForm')[0].reset();
        $('#editTemplateModal').modal('hide');
    });

    $('#submitEditTemplateBtn').on('click', function () {
        const $btn = $(this).prop('disabled', true);
        $('#progressBar').show();

        $.post(ROUTES.update, $('#editTemplateForm').serialize())
            .done(res => {
                if (res.status === 201) {
                    toastr.success(res.success);
                    if (res.template.is_default) $('.badge-default').remove();
                    $('#card' + res.template.id).replaceWith(buildCard(res.template));
                    $('#editTemplateModal').modal('hide');
                } else {
                    toastr.error(res.error || 'Failed to update template.');
                }
            })
            .fail(() => toastr.error('Request failed. Please try again.'))
            .always(() => { $btn.prop('disabled', false); $('#progressBar').hide(); });
    });

    // ── DELETE ────────────────────────────────────────────────────
    $(document).on('click', '.deleteTemplateBtn', function (e) {
        e.preventDefault();
        $('#deleteTemplateName').text($(this).data('name'));
        $('#deleteTemplateId').val($(this).data('id'));
        $('#deleteTemplateModal').modal('show');
    });

    $('#cancelDeleteTemplateBtn').on('click', () => $('#deleteTemplateModal').modal('hide'));

    $('#submitDeleteTemplateBtn').on('click', function () {
        const $btn = $(this).prop('disabled', true);
        const id   = $('#deleteTemplateId').val();
        $('#progressBar').show();

        $.post(ROUTES.delete, { id, _token: CSRF })
            .done(res => {
                if (res.status === 201) {
                    toastr.success(res.success);
                    $('#card' + id).remove();
                    $('#deleteTemplateModal').modal('hide');
                } else {
                    toastr.error(res.error || 'Failed to delete template.');
                }
            })
            .fail(() => toastr.error('Request failed. Please try again.'))
            .always(() => { $btn.prop('disabled', false); $('#progressBar').hide(); });
    });

    // ── FULL PREVIEW ──────────────────────────────────────────────
    $(document).on('click', '.previewBtn', function (e) {
        e.preventDefault();
        $('#previewModalTitle').text($(this).data('name'));
        $('#previewFrame').attr('src', ROUTES.preview + '?id=' + $(this).data('id'));
        $('#previewModal').modal('show');
    });

    $('#previewModal').on('hide.bs.modal', () => {
        $('#previewFrame').attr('src', 'about:blank');
    });

    // ══════════════════════════════════════════════════════════════
    //  CODE EDITOR
    // ══════════════════════════════════════════════════════════════
    let cmEditor           = null;
    let activeTemplateId   = null;
    let activeTemplateName = null;
    let isDirty            = false;
    let debounceTimer      = null;

    // Initialise CodeMirror once (lazy — only when editor is first opened)
    function initialiseCM() {
        if (cmEditor) return;

        cmEditor = CodeMirror.fromTextArea(
            document.getElementById('codeEditorTextarea'),
            {
                mode          : 'htmlmixed',
                theme         : 'dracula',
                lineNumbers   : true,
                matchBrackets : true,
                autoCloseTags : true,
                indentUnit    : 4,
                tabSize       : 4,
                indentWithTabs: false,
                lineWrapping  : false,
                extraKeys     : {
                    'Ctrl-S': saveTemplateCode,
                    'Cmd-S' : saveTemplateCode,
                    'Ctrl-/': cm => cm.execCommand('toggleComment'),
                    'Cmd-/' : cm => cm.execCommand('toggleComment'),
                },
            }
        );

        cmEditor.on('change', () => {
            if (!isDirty) { isDirty = true; setEditorStatus('unsaved', 'Unsaved'); }
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(refreshLivePreview, 1200);
        });

        cmEditor.on('cursorActivity', () => {
            const c = cmEditor.getCursor();
            $('#cursorPos').text(`Ln ${c.line + 1}, Col ${c.ch + 1}`);
        });
    }

    // Open editor modal
    $(document).on('click', '.codeEditorBtn', function (e) {
        e.preventDefault();
        activeTemplateId   = $(this).data('id');
        activeTemplateName = $(this).data('name');

        $('#codeEditorTitle').text('Editing: ' + activeTemplateName);
        $('#editorSubtitle').text('Template #' + activeTemplateId);
        setEditorStatus('loading', 'Loading…');
        $('#editorLoadingOverlay').show();
        isDirty = false;

        $('#codeEditorModal').modal('show');

        // Wait until modal is fully visible so CodeMirror can measure its dimensions
        $('#codeEditorModal').one('shown.bs.modal', function () {
            initialiseCM();
            cmEditor.refresh();

            $.post(ROUTES.getContent, { id: activeTemplateId, _token: CSRF })
                .done(res => {
                    if (res.status === 200) {
                        cmEditor.setValue(res.content || '');
                        cmEditor.clearHistory();
                        cmEditor.setCursor(0, 0);
                        setEditorStatus('saved', 'Saved');
                        refreshLivePreview();
                    } else {
                        toastr.error(res.error || 'Could not load template content.');
                        setEditorStatus('unsaved', 'Error');
                    }
                })
                .fail(() => {
                    toastr.error('Failed to fetch template content.');
                    setEditorStatus('unsaved', 'Error');
                })
                .always(() => {
                    $('#editorLoadingOverlay').hide();
                    isDirty = false; // reset — loading doesn't count as a change
                });
        });
    });

    // Save content to DB
    function saveTemplateCode() {
        if (!cmEditor) return;

        setEditorStatus('saving', 'Saving…');
        $('#saveCodeBtn').prop('disabled', true);

        $.post(ROUTES.saveContent, {
            id      : activeTemplateId,
            content : cmEditor.getValue(),
            _token  : CSRF,
        })
        .done(res => {
            if (res.status === 201) {
                toastr.success(res.success);
                isDirty = false;
                setEditorStatus('saved', 'Saved');
                updateCardMiniPreview(activeTemplateId, cmEditor.getValue());
                refreshLivePreview();
            } else {
                toastr.error(res.error || 'Save failed.');
                setEditorStatus('unsaved', 'Error');
            }
        })
        .fail(() => {
            toastr.error('Save request failed.');
            setEditorStatus('unsaved', 'Error');
        })
        .always(() => $('#saveCodeBtn').prop('disabled', false));
    }

    $('#saveCodeBtn').on('click', saveTemplateCode);

    // Push updated content into the card's mini iframe (no page reload)
    function updateCardMiniPreview(id, content) {
        const $preview = $('#card' + id).find('.card-preview');
        let $iframe    = $preview.find('iframe.card-preview-iframe');

        if ($iframe.length === 0) {
            $preview.html('<iframe class="card-preview-iframe" sandbox="allow-same-origin" loading="lazy"></iframe>');
            $iframe = $preview.find('iframe');
        }
        $iframe[0].srcdoc = content;
    }

    // Render editor content into the right pane via srcdoc (no server round-trip)
    function refreshLivePreview() {
        if (!cmEditor) return;
        document.getElementById('livePreviewFrame').srcdoc = cmEditor.getValue();
    }

    $('#refreshPreviewBtn').on('click', refreshLivePreview);

    // Warn before closing with unsaved changes
    $('#codeEditorModal').on('hide.bs.modal', function (e) {
        if (isDirty && !confirm('You have unsaved changes. Close anyway?')) {
            e.preventDefault();
            return;
        }
        $('#livePreviewFrame').attr('src', 'about:blank');
        isDirty = false;
    });

    // Wrap toggle
    $('#wrapToggleBtn').on('click', function () {
        if (!cmEditor) return;
        const nowWrapped = !cmEditor.getOption('lineWrapping');
        cmEditor.setOption('lineWrapping', nowWrapped);
        $(this).toggleClass('active', nowWrapped);
    });

    // Basic auto-format (re-indent all lines)
    $('#formatBtn').on('click', function () {
        if (!cmEditor) return;
        const total = cmEditor.lineCount();
        for (let i = 0; i < total; i++) cmEditor.indentLine(i, 'smart');
        toastr.info('Code re-indented.');
    });

    // Editor status badge helper
    function setEditorStatus(type, text) {
        $('#editorStatus').attr('class', 'editor-status ' + type).text(text);
    }

});
</script>
@endsection