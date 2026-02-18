@extends('master.dashboard')
@section('content')
<style>
/* === BLUE BAR === */
.card-header {
    padding: 0.5rem 1.5rem !important;
    background: linear-gradient(to right, #4B5EBD, #576CC0);
    color: #fff;
    border-radius: 10px 10px 0 0;
}
.card-header .btn-light {
    height: 28px;
    padding: 0 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    line-height: 1;
    font-size: 1rem;
}
.card-header .btn-light:hover {
    background-color: #f8f9fa;
}
.card-header h4 {
    color: #fff;
    font-weight: 600;
    margin: 0;
    font-size: 1.1rem;
}

/* === CONTROLS === */
.controls-section {
    background: #ffffff;
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
    background: #f8f9fa url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='%236c757d' viewBox='0 0 16 16'%3E%3Cpath d='M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001c.03.04.062.078.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1.007 1.007 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0z'/%3E%3C/svg%3E") no-repeat 0.75rem center;
    background-size: 16px;
    transition: all 0.2s;
}
#searchInput:focus {
    background-color: #fff;
    box-shadow: 0 0 0 0.2rem rgba(75, 94, 189, 0.25);
}

/* === PROFESSIONAL CARD DESIGN === */
#templateCards {
    margin-top: 0.8rem !important;
}
.template-card {
    width: 300px;
    height: 430px;
    background: #fff;
    border: none;
    border-radius: 16px;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
    transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
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
    transform: translateY(-12px);
    box-shadow: 0 16px 32px rgba(0, 0, 0, 0.18);
}

/* Header */
.card-header-section {
    padding: 1.25rem 1.25rem 0.75rem;
    text-align: center;
}
.card-title {
    font-size: 1.25rem;
    font-weight: 700;
    color: #2c3e50;
    margin: 0 0 0.5rem;
    line-height: 1.3;
}
.badge-default {
    font-size: 0.7rem;
    font-weight: 600;
    padding: 0.35em 0.85em;
    border-radius: 50px;
    background: #28a745;
    color: #fff;
    text-transform: uppercase;
    letter-spacing: 0.7px;
    box-shadow: 0 2px 4px rgba(40, 167, 69, 0.3);
}

/* CLEAN MINI PDF PREVIEW - NO TOOLBARS */
.card-preview {
    flex: 1;
    padding: 0.75rem;
    background: #f8f9fb;
}
.card-preview iframe {
    width: 100%;
    height: 100%;
    border: 1px solid #e0e0e0;
    border-radius: 12px;
    background: #fff;
    box-shadow: inset 0 2px 6px rgba(0,0,0,0.06);
}

/* Action Buttons */
.card-actions {
    padding: 1rem;
    display: flex;
    justify-content: center;
    gap: 14px;
    background: #f9f9f9;
    border-top: 1px solid #eee;
}
.btn-icon {
    width: 48px;
    height: 48px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    font-size: 1.3rem;
    transition: all 0.3s ease;
    box-shadow: 0 4px 10px rgba(0,0,0,0.15);
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
    transform: translate(-50%, -50%);
    transition: width 0.4s, height 0.4s;
}
.btn-icon:active::after {
    width: 200px;
    height: 200px;
}
.btn-preview { background: linear-gradient(135deg, #ffebee, #ffcdd2); color: #c62828; }
.btn-preview:hover { background: linear-gradient(135deg, #ffcdd2, #ef9a9a); transform: scale(1.18); box-shadow: 0 6px 16px rgba(198,40,40,0.4); }
.btn-edit { background: linear-gradient(135deg, #e8f5e8, #c8e6c9); color: #2e7d32; }
.btn-edit:hover { background: linear-gradient(135deg, #c8e6c9, #a5d6a7); transform: scale(1.18); box-shadow: 0 6px 16px rgba(46,125,50,0.4); }
.btn-delete { background: linear-gradient(135deg, #ffebee, #ffcdd2); color: #c62828; }
.btn-delete:hover { background: linear-gradient(135deg, #ffcdd2, #ef9a9a); transform: scale(1.18); box-shadow: 0 6px 16px rgba(198,40,40,0.4); }

/* Responsive */
@media (max-width: 768px) {
    .controls-row { flex-direction: column; align-items: stretch; }
    #searchInput { max-width: 100%; }
}

/* === FULL PREVIEW MODAL === */
#previewModal .modal-dialog {
    max-width: 900px;
    margin: 1.75rem auto;
}
#previewModal .modal-body {
    padding: 0;
    position: relative;
}
#previewModal .preview-scroll {
    overflow: auto;
    -webkit-overflow-scrolling: touch;
    max-height: 80vh;
}
#previewModal .preview-scroll iframe {
    width: 100%;
    height: 800px;
    border: none;
    display: block;
}
#pdfFallback {
    position: absolute;
    top: 50%; left: 50%;
    transform: translate(-50%, -50%);
    text-align: center;
    background: rgba(255,255,255,0.98);
    padding: 2.5rem;
    border-radius: 14px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.18);
    z-index: 10;
    width: 80%;
    max-width: 440px;
}
</style>

<div class="progress" id="progressBar" style="height:8px; display:none">
    <div class="progress-bar progress-bar-striped progress-bar-animated" style="width:100%"></div>
</div>

<div class="content-page">
<div class="content">
<div class="container-fluid">

<div class="row mb-3"></div>

<!-- BLUE BAR WITH ALL ICONS -->
<div class="card">
<div class="card-header d-flex justify-content-between align-items-center">
    <h4 class="header-title mb-0">
        <i class="ri-file-list-3-line"></i> Invoice Templates
    </h4>
    <div class="d-flex align-items-center">
        <a href="#" class="btn btn-light text-primary fs-16 mx-1" id="infoBtn" title="Info">
            <i class="ri-information-line"></i>
        </a>
    </div>
</div>

<!-- CONTROLS WITH ICON -->
<div class="controls-section">
    <div class="controls-row">
        <a href="#" class="btn btn-primary" id="newDataBtn">
            <i class="ri-add-circle-line"></i> Add New Template
        </a>
        <input type="text" class="form-control" id="searchInput" placeholder="Search templates...">
    </div>
</div>
</div>

<!-- PROFESSIONAL CARDS WITH CLEAN MINI PDF -->
<div class="row g-3" id="templateCards">
    <?php $templates = DB::table('invoice_templates')->get(); ?>
    @foreach($templates as $tpl)
        @php
            $filename = \Str::slug($tpl->name, '_') . '.blade.php';
            $pdfUrl = route('preview.invoice.pdf', $filename);
            $cleanPreviewUrl = $pdfUrl . '#toolbar=0&navpanes=0&scrollbar=0';
        @endphp
        <div class="col-md-4 col-lg-3 d-flex justify-content-center template-card-wrapper" id="card{{ $tpl->id }}">
            <div class="template-card">
                <!-- Header: Name + Badge -->
                <div class="card-header-section">
                    <h5 class="card-title">{{ $tpl->name }}</h5>
                    @if($tpl->is_default)
                        <span class="badge-default">Default</span>
                    @endif
                </div>

                <!-- CLEAN MINI PREVIEW: NO TOOLBAR, NO HEADERS -->
                <div class="card-preview">
                    <iframe src="{{ $cleanPreviewUrl }}" loading="lazy"></iframe>
                </div>

                <!-- Action Buttons WITH ICONS -->
                <div class="card-actions">
                    <a href="#" class="btn btn-icon btn-preview previewBtn"
                       previewName="{{ $tpl->name }}"
                       previewUrl="{{ $pdfUrl }}"
                       title="Preview PDF">
                       <i class="ri-file-pdf-line"></i>
                    </a>
                    <a href="#" class="btn btn-icon btn-edit editDataBtn"
                       editId="{{ $tpl->id }}"
                       editName="{{ $tpl->name }}"
                       editIsDefault="{{ $tpl->is_default ? '1' : '0' }}" title="Edit">
                       <i class="ri-edit-box-line"></i>
                    </a>
                    <a href="#" class="btn btn-icon btn-delete deleteDataBtn"
                       deleteLabel="{{ $tpl->name }}"
                       deleteId="{{ $tpl->id }}" title="Delete">
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

<!-- INFO MODAL -->
<section>
<div class="modal fade" id="infoModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Invoice Templates Management</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                Enter a name to create empty <code>.blade.php</code> file in <code>resources/views/common/invoices/</code>
            </div>
        </div>
    </div>
</div>
</section>

<!-- ADD MODAL -->
<section>
<div class="modal fade" id="newDataModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Template</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="newDataForm">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Template Name <span class="text-danger">*</span></label>
                        <input class="form-control" name="name" required placeholder="Enter template name">
                    </div>
                    <div class="form-check mb-3" style="display:none">
                        <input class="form-check-input" type="checkbox" name="is_default" value="1" id="is_default_new">
                        <label class="form-check-label" for="is_default_new">Set as default</label>
                    </div>
                    <button type="button" class="btn btn-primary float-end mt-3 mb-2" id="submitDataBtn">Submit</button>
                    <button type="button" class="btn btn-secondary float-end mt-3 mb-2 mx-2" id="cancelDataBtn">Clear</button>
                </form>
            </div>
        </div>
    </div>
</div>
</section>

<!-- DELETE MODAL -->
<section>
<div class="modal fade" id="singleDeleteDataModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1">
    <div class="modal-dialog" style="max-width:350px; margin:1.75rem auto;">
        <div class="modal-content">
            <div class="modal-body text-center pb-4">
                <i class="ri-error-warning-line text-danger" style="font-size:70px"></i>
                <form id="singleDeleteDataForm">
                    @csrf
                    <h4>Delete <span id="singleDisplayDeleteLabel"></span>?</h4>
                    <h5>You won't be able to revert this!</h5>
                    <input type="hidden" id="singleDeleteId" name="id">
                    <div class="mt-3">
                        <button type="button" class="btn btn-danger" id="submitSingleDeleteDataBtn">Yes, Delete</button>
                        <button type="button" class="btn btn-info" id="keepSingleDataBtn">No, Keep</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
</section>

<!-- EDIT MODAL -->
<section>
<div class="modal fade" id="editDataModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Update Template</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="editDataForm">
                    @csrf
                    <input type="hidden" name="id" id="editId">
                    <div class="mb-3">
                        <label class="form-label">Template Name <span class="text-danger">*</span></label>
                        <input class="form-control" name="name" id="editName" required>
                    </div>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" name="is_default" value="1" id="editIsDefault">
                        <label class="form-check-label" for="editIsDefault">Set as default</label>
                    </div>
                    <button type="button" class="btn btn-primary float-end mt-3 mb-2" id="submitUpdateDataBtn">Submit</button>
                    <button type="button" class="btn btn-secondary float-end mt-3 mb-2 mx-2" id="cancelEditDataBtn">Clear</button>
                </form>
            </div>
        </div>
    </div>
</div>
</section>

<!-- FULL PDF PREVIEW MODAL -->
<section>
<div class="modal fade" id="previewModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Preview: <span id="previewTitle"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="preview-scroll">
                    <iframe id="previewFrame" style="border:none;"></iframe>
                    <div id="pdfFallback" style="display:none;">
                        <p class="text-muted mb-3">Your browser cannot display PDF inline.</p>
                        <a href="" id="pdfDownloadLink" target="_blank" class="btn btn-sm btn-primary">
                            <i class="ri-download-line"></i> Download PDF
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</section>
@endsection

@section('scripts')
<script>
$(document).ready(function () {
    toastr.options = { closeButton: true, progressBar: true, timeOut: 5000 };

    // Helper: Generate clean filename
    const getFilename = name => name.replace(/[^a-zA-Z0-9_-]+/g, '_').replace(/^_+|_+$/g, '') + '.blade.php';
    const getPdfUrl = name => `{{ route('preview.invoice.pdf', ':filename') }}`.replace(':filename', encodeURIComponent(getFilename(name)));

    // SEARCH
    const searchInput = $('#searchInput');
    const filterCards = () => {
        const query = searchInput.val().toLowerCase().trim();
        $('.template-card-wrapper').each(function () {
            const $wrapper = $(this);
            const name = $wrapper.find('.card-title').text().toLowerCase();
            const isDefault = $wrapper.find('.badge-default').length > 0;
            const matches = name.includes(query) || (query.includes('default') && isDefault);
            $wrapper.toggle(matches);
        });
    };
    searchInput.on('input', filterCards);

    // MODALS
    $('#newDataBtn').click(e => { e.preventDefault(); $('#newDataForm')[0].reset(); $('#newDataModal').modal('show'); });
    $('#infoBtn').click(e => { e.preventDefault(); $('#infoModal').modal('show'); });

    // ADD NEW TEMPLATE
    $('#submitDataBtn').click(function (e) {
        e.preventDefault();
        $(this).prop('disabled', true);
        $('#progressBar').show();

        $.post('{{ route("master.invoice.template.insert") }}', $('#newDataForm').serialize())
            .done(data => {
                if (data.status === 201) {
                    toastr.success(data.success);
                    const t = data.template;
                    const pdfUrl = getPdfUrl(t.name);
                    const cleanPreviewUrl = pdfUrl + '#toolbar=0&navpanes=0&scrollbar=0';

                    $('.badge-default').remove();

                    const badge = t.is_default ? '<span class="badge-default">Default</span>' : '';
                    const cardHtml = `
                        <div class="col-md-4 col-lg-3 d-flex justify-content-center template-card-wrapper" id="card${t.id}">
                            <div class="template-card">
                                <div class="card-header-section">
                                    <h5 class="card-title">${t.name}</h5>
                                    ${badge}
                                </div>
                                <div class="card-preview">
                                    <iframe src="${cleanPreviewUrl}" loading="lazy"></iframe>
                                </div>
                                <div class="card-actions">
                                    <a href="#" class="btn btn-icon btn-preview previewBtn" previewName="${t.name}" previewUrl="${pdfUrl}" title="Preview PDF">
                                        <i class="ri-file-pdf-line"></i>
                                    </a>
                                    <a href="#" class="btn btn-icon btn-edit editDataBtn"
                                       editId="${t.id}" editName="${t.name}" editIsDefault="${t.is_default ? '1' : '0'}" title="Edit">
                                        <i class="ri-edit-box-line"></i>
                                    </a>
                                    <a href="#" class="btn btn-icon btn-delete deleteDataBtn"
                                       deleteLabel="${t.name}" deleteId="${t.id}" title="Delete">
                                        <i class="ri-delete-bin-line"></i>
                                    </a>
                                </div>
                            </div>
                        </div>`;
                    $('#templateCards').append(cardHtml);
                    $('#newDataModal').modal('hide');
                    filterCards();
                }
            })
            .always(() => {
                $(this).prop('disabled', false);
                $('#progressBar').hide();
            });
    });

    $('#cancelDataBtn').click(e => { e.preventDefault(); $('#newDataForm')[0].reset(); $('#newDataModal').modal('hide'); });

    // DELETE
    $(document).on('click', '.deleteDataBtn', function () {
        $('#singleDisplayDeleteLabel').html($(this).attr('deleteLabel'));
        $('#singleDeleteId').val($(this).attr('deleteId'));
        $('#singleDeleteDataModal').modal('show');
    });

    $('#submitSingleDeleteDataBtn').click(function () {
        $(this).prop('disabled', true);
        $('#progressBar').show();
        const id = $('#singleDeleteId').val();
        $.post('{{ route("master.invoice.template.delete") }}', { id, _token: '{{ csrf_token() }}' })
            .done(data => {
                if (data.status === 201) {
                    toastr.success(data.success);
                    $('#card' + id).remove();
                    $('#singleDeleteDataModal').modal('hide');
                    filterCards();
                }
            })
            .always(() => {
                $(this).prop('disabled', false);
                $('#progressBar').hide();
            });
    });

    $('#keepSingleDataBtn').click(() => $('#singleDeleteDataModal').modal('hide'));

    // EDIT
    $(document).on('click', '.editDataBtn', function () {
        $('#editId').val($(this).attr('editId'));
        $('#editName').val($(this).attr('editName'));
        $('#editIsDefault').prop('checked', $(this).attr('editIsDefault') === '1');
        $('#editDataModal').modal('show');
    });

    $('#submitUpdateDataBtn').click(function () {
        $(this).prop('disabled', true);
        $('#progressBar').show();

        $.post('{{ route("master.invoice.template.update") }}', $('#editDataForm').serialize())
            .done(data => {
                if (data.status === 201) {
                    toastr.success(data.success);
                    const t = data.template;
                    const pdfUrl = getPdfUrl(t.name);
                    const cleanPreviewUrl = pdfUrl + '#toolbar=0&navpanes=0&scrollbar=0';

                    $('.badge-default').remove();

                    const badge = t.is_default ? '<span class="badge-default">Default</span>' : '';
                    const cardHtml = `
                        <div class="col-md-4 col-lg-3 d-flex justify-content-center template-card-wrapper" id="card${t.id}">
                            <div class="template-card">
                                <div class="card-header-section">
                                    <h5 class="card-title">${t.name}</h5>
                                    ${badge}
                                </div>
                                <div class="card-preview">
                                    <iframe src="${cleanPreviewUrl}" loading="lazy"></iframe>
                                </div>
                                <div class="card-actions">
                                    <a href="#" class="btn btn-icon btn-preview previewBtn" previewName="${t.name}" previewUrl="${pdfUrl}" title="Preview PDF">
                                        <i class="ri-file-pdf-line"></i>
                                    </a>
                                    <a href="#" class="btn btn-icon btn-edit editDataBtn"
                                       editId="${t.id}" editName="${t.name}" editIsDefault="${t.is_default ? '1' : '0'}" title="Edit">
                                        <i class="ri-edit-box-line"></i>
                                    </a>
                                    <a href="#" class="btn btn-icon btn-delete deleteDataBtn"
                                       deleteLabel="${t.name}" deleteId="${t.id}" title="Delete">
                                        <i class="ri-delete-bin-line"></i>
                                    </a>
                                </div>
                            </div>
                        </div>`;
                    $('#card' + t.id).replaceWith(cardHtml);
                    $('#editDataModal').modal('hide');
                    filterCards();
                }
            })
            .always(() => {
                $(this).prop('disabled', false);
                $('#progressBar').hide();
            });
    });

    $('#cancelEditDataBtn').click(e => { e.preventDefault(); $('#editDataForm')[0].reset(); $('#editDataModal').modal('hide'); });

    // FULL PDF PREVIEW (WITH TOOLBAR)
    $(document).on('click', '.previewBtn', function (e) {
        e.preventDefault();
        const name = $(this).attr('previewName');
        const url = $(this).attr('previewUrl'); // Full URL
        $('#previewTitle').text(name);
        const $iframe = $('#previewFrame');
        const $fallback = $('#pdfFallback');
        const $downloadLink = $('#pdfDownloadLink');

        $iframe.attr('src', url);
        $fallback.hide();

        $iframe.on('load', function () {
            setTimeout(() => {
                try {
                    const contents = $iframe.contents();
                    if (contents.find('body').length === 0 || contents.text().includes('404')) {
                        $fallback.show();
                        $downloadLink.attr('href', url);
                    }
                } catch (e) {
                    $fallback.show();
                    $downloadLink.attr('href', url);
                }
            }, 800);
        });

        $('#previewModal').modal('show');
    });
});
</script>
@endsection