<div class="modal fade" id="ftpFolderModal" tabindex="-1" role="dialog" aria-labelledby="ftpFolderModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <form method="POST" id="ftpFolders" name="ftpFolders" action="?page={$page_url}">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="notificationModalLabel">{$modal_ftp_folder_title}</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body" id="ftpModalBody">
                    <div class="form-group">
                        <div id="tree"></div>
                    </div>
                    <div class="form-group control-group">
                        <label for="selectedFolder" class="col-sm-4 col-form-label">
                            {$settings_ftp_selected_folder}
                        </label>
                        <input type="text" id="selectedFolder" name="selectedFolder">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">{$modal_admin_hardening_btn_close_text}</button>
                    <button type="button" id="testConnection" name="testConnection" class="btn btn-primary">{$modal_ftp_folder_btn_save_text}</button>
                </div>
            </div>
            {$nonce_fields}
        </form>
    </div>
</div>