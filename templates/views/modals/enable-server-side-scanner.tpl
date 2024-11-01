<div class="modal fade" id="enableServerSideScanner" tabindex="-1" role="dialog" aria-labelledby="enableServerSideScannerLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <form method="POST" id="enableServerSideScannerForm" name="enableServerSideScannerForm" action="?page={$page_url}">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="notificationModalLabel">{$modal_serverside_title}</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    {$modal_serverside_text}
                </div>
                <div class="modal-footer">
                    <a href="{$modal_serverside_url}" target="_blank" class="btn btn-primary">{$modal_serverside_btn_text}</a>
                </div>
            </div>
            {$nonce_fields}
        </form>
    </div>
</div>