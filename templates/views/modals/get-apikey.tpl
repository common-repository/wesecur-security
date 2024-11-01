<div class="modal fade" id="getApiKey" tabindex="-1" role="dialog" aria-labelledby="getApiKeyLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <form method="POST" id="getApiKeyForm" name="getApiKeyForm" action="?page={$page_url}">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="notificationModalLabel">{$settings_modal_apikey_title}</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    {$settings_modal_apikey_text}
                </div>
                <div class="modal-footer">
                    <a href="{$settings_apikey_premium_url}" target="_blank" class="btn btn-primary">{$settings_modal_apikey_btn_text}</a>
                </div>
            </div>
            {$nonce_fields}
        </form>
    </div>
</div>