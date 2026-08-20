<div class="modal fade" id="adminDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
        <div class="modal-content detail-modal">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" data-detail-title>{{ $strings::DETAILS }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ $strings::CLOSE }}"></button>
            </div>
            <div class="modal-body">
                <div class="detail-hero" data-detail-hero hidden>
                    <img src="" alt="" data-detail-image>
                </div>
                <div class="detail-badges mb-3" data-detail-badges></div>
                <dl class="detail-grid" data-detail-fields></dl>
                <div data-detail-blocks></div>
            </div>
            <div class="modal-footer">
                <a href="#" class="btn btn-brand" data-detail-edit>{{ $strings::EDIT }}</a>
                <button type="button" class="btn btn-outline-secondary rounded-pill" data-bs-dismiss="modal">{{ $strings::CLOSE }}</button>
            </div>
        </div>
    </div>
</div>
