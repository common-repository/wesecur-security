<div class="modal fade" id="changeAdminModal" tabindex="-1" role="dialog" aria-labelledby="changeAdminModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <form method="POST" action="?page={$page_url}">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="notificationModalLabel">{$modal_admin_hardening_title}</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="username" class="col-form-label">{$modal_admin_hardening_username}:</label>
                        <input name="username"
                               id="username"
                               class="form-control"
                               type="text"
                               autocomplete="off"
                               placeholder="{$modal_admin_hardening_placeholder}"
                               value=""
                               required/>
                    </div>
                    <div class="form-group">
                        <input type="checkbox"
                               autocomplete="off"
                               required>{$modal_admin_hardening_disclaimer}
                    </div>
                </div>
                <div class="modal-footer">
                    <input type="hidden" name="action" value="changeAdminUsername">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">{$modal_admin_hardening_btn_close_text}</button>
                    <input type="submit" class="btn btn-primary" value="{$modal_admin_hardening_btn_apply_text}"/>
                </div>
            </div>
            {$nonce_fields}
        </form>
    </div>
</div>