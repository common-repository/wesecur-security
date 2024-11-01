<div class="modal fade" id="remoteScanModal" tabindex="-1" role="dialog" aria-labelledby="remoteScanModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="remoteScanModalLabel">{$external_malware_modal_title}</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <div class="section">
                        <div data-target="0" class="container__header">{$external_malware_info_text}</div>
                        <div class="result__row" data-target="110">
                            <div class="result__icon-wrapper">
                                <i class="fa fa-globe" class="result__icon--warning" alt="warning" aria-hidden="true"></i>
                            </div>
                            <div class="result__title">{$external_malware_website_text}</div>
                            <div class="code--warning result__content">
                                <div class="statusHtml">
                                    {$external_malware_domain}
                                </div>
                            </div>
                        </div>
                     </div>
                    <div class="result__row" data-target="110">
                        <div class="result__icon-wrapper">
                            <i class="fa fa-server" class="result__icon--warning" alt="warning" aria-hidden="true"></i>
                        </div>
                        <div class="result__title">{$external_malware_modal_server_text}</div>
                        <div class="code--warning result__content">
                            <div class="statusHtml">
                                {$external_malware_server_type}
                            </div>
                        </div>
                    </div>
                    <div class="result__row" data-target="110">
                        <div class="result__icon-wrapper">
                            <i class="fa fa-cog" class="result__icon--warning" alt="warning" aria-hidden="true"></i>
                        </div>
                        <div class="result__title">{$external_malware_modal_technology_text}</div>
                        <div class="code--warning result__content">
                            <div class="statusHtml">
                                {$external_malware_server_tech}
                            </div>
                        </div>
                    </div>

                    <div class="section">
                        <div class="container__header">{$external_malware_modal_scan_text}</div>
                        <div class="result__row row-no-bottom">
                            <div class="result__icon-wrapper">
                                <i class="fa fa-bug" class="result__icon--warning" aria-hidden="true"></i>
                            </div>
                            <div class="result__title"><a href="">{$external_malware_modal_urls_text}</a></div>
                        </div>
                        <div class="result__row">
                            <div class="expanded-content">
                                {foreach from=$external_malware_files item=malwareFile}
                                    <p>{$malwareFile->url}</p>
                                {/foreach}
                            </div>
                        </div>
                        <div class="result__row row-no-bottom">
                            <div class="result__icon-wrapper">
                                <i class="fa fa-file-code-o" class="result__icon--warning" aria-hidden="true"></i>
                            </div>
                            <div class="result__title"><a href="">{$external_malware_modal_javascript_text}</a></div>
                        </div>
                        <div class="result__row">
                            <div class="expanded-content">
                                {foreach from=$external_malware_javascript_files item=javascriptFile}
                                    <p>{$javascriptFile}</p>
                                {/foreach}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">{$external_malware_modal_btn_close_text}</button>
            </div>
        </div>
    </div>
</div>