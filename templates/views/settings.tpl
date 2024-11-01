<div id="apikey-required" class="bootstrap-wrapper wesecur-notifications-box row" style="display:none">
    <div class="col-md-12">
        <div class="alert alert-dismissible fade show alert-danger" role="alert">
            <strong>WeSecur Security</strong> - {$settings_apikey_notfound_text}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">×</span>
            </button>
        </div>
    </div>
</div>
<div id="ajax-error" class="bootstrap-wrapper wesecur-notifications-box row" style="display:none">
    <div class="col-md-12">
        <div class="alert alert-dismissible fade show alert-danger" role="alert">
            <strong>WeSecur Security</strong> - {$settings_timeout_error_text}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">×</span>
            </button>
        </div>
    </div>
</div>
<script>
    var _nonce = "{$settings_ftp_ajax_nonce}";
    var endpoint_ajax = "{$endpoint_ajax}";
    var ftp_error_invalid_folder = "{$settings_ftp_error_installation_folder_invalid}";
    var ftp_error_host_required = "{$settings_ftp_error_host_required}";
    var ftp_error_invalid_host = "{$settings_ftp_error_invalid_host}";
    var ftp_error_port_required = "{$settings_ftp_error_port_required}";
    var ftp_error_username_required = "{$settings_ftp_error_username_required}";
    var ftp_error_invalid_username = "{$settings_ftp_error_invalid_username_password}";
    var ftp_error_password_required = "{$settings_ftp_error_password_required}";
    var ftp_error_folder_required = "{$settings_ftp_error_folder_required}";
    var ftp_error_invalid_ftp_type = "{$settings_ftp_error_invalid_type}";
    var loading_url = "{$loading_image}";
    var loading_text = "{$loading_text}";
{literal}

    function parseAjaxErrors(result, formValidator) {
        if (result.data.message == "Invalid host or port") {
            jQuery('form#ftpForm [name="settings_ftp_host"]').closest('.control-group').removeClass('success').addClass('error');
            jQuery('form#ftpForm [name="settings_ftp_port"]').closest('.control-group').removeClass('success').addClass('error');
        }

        if (result.data.message == "Invalid username or password") {
            jQuery('form#ftpForm [name="settings_ftp_username"]').closest('.control-group').removeClass('success').addClass('error');
            var errors = { settings_ftp_username: ftp_error_invalid_username };
            formValidator.showErrors(errors);
        }

        if (result.data.message == "Invalid ftp connection type") {
            jQuery('form#ftpForm [name="settings_ftp_type"]').closest('.control-group').removeClass('success').addClass('error');
            var errors = { settings_ftp_type: ftp_error_invalid_ftp_type };
            formValidator.showErrors(errors);
        }

        if (result.data.message == "API key not found. Setup your API key before configure the FTP settings.") {
            jQuery('#apikey-required').show();
            jQuery('html, body').animate({
                scrollTop: jQuery("#apikey-required").offset().top
            }, 400);
        }
    }

    function printFtpDirectory(dataToPrint, ftpParams, _nonce, loaderParams) {
        jQuery('#tree').treeview({data: dataToPrint});
        jQuery('#tree').on('nodeSelected', function(event, folder) {
            jQuery('form#ftpFolders').loader(loaderParams);
            if (ftpParams["path"] == undefined ||  ftpParams["path"] == "/") {
                ftpParams["path"] = "/" + folder.text;
            }else{
                ftpParams["path"] = ftpParams["path"] + "/" + folder.text;
            }

            var data = {
                action: "wesecur_security_ajax_get_ftp_folders",
                payload: ftpParams,
                _nonce: _nonce
            };

            jQuery.ajax({url: endpoint_ajax,
                type: 'POST',
                dataType: 'json',
                data: data,
                timeout: 66000,
                success: function(result) {
                    jQuery.loader.close();
                    if (result.success || result.success == undefined) {
                        if (ftpParams["path"].indexOf("/..") >= 0) {
                            var path = ftpParams["path"].split('/');
                            path.pop();
                            path.pop();
                            ftpParams["path"] = path.join('/');
                            if (ftpParams["path"] == "") {
                                ftpParams["path"] = "/";
                            }
                        }
                        jQuery("#selectedFolder").val(ftpParams["path"]);
                        jQuery("#selectedFolder").trigger("change");
                        printFtpDirectory(result.data.message, ftpParams, _nonce, loaderParams);
                    }else{
                        jQuery('#apikey-required').show();
                        jQuery('html, body').animate({
                            scrollTop: jQuery("#apikey-required").offset().top
                        }, 400);
                    }
                },
                error: function(result) {
                    jQuery.loader.close();
                }
            });
        });
    }

    jQuery(function () {
        var loaderParams = {
            autoCheck: true,
            size: 32,
            bgColor: "#FFF",
            bgOpacity: 0.9,
            fontColor: "#000",
            title: loading_text,
            isOnly: true,
            imgUrl: loading_url
        };

        var formFtpFolderValidator = jQuery('form#ftpFolders').validate({
            rules: {
                selectedFolder: {
                    required: true
                }
            },
            messages: {
                selectedFolder: {
                    required: ftp_error_invalid_folder
                }
            },
            highlight: function (element) {
                jQuery(element).closest('.control-group').removeClass('success').addClass('error');
            },
            success: function (element) {
                element.addClass('valid').closest('.control-group').removeClass('error').addClass('success');
            }
        });

        var formValidator = jQuery('form#ftpForm').validate({
            rules: {
                settings_ftp_host: {
                    minlength: 5,
                    required: true
                },
                settings_ftp_port: {
                    required: true,
                    number: true
                },
                settings_ftp_username: {
                    required: true
                },
                settings_ftp_password: {
                    required: true
                },
                settings_ftp_path: {
                    required: true
                },
                settings_ftp_type: {
                    required: true
                }
            },
        messages: {
                settings_ftp_host: {
                    required: ftp_error_host_required,
                    minlength: ftp_error_invalid_host
                },
                settings_ftp_port: {
                    required: ftp_error_port_required
                },
                settings_ftp_username: {
                    required: ftp_error_username_required
                },
                settings_ftp_password: {
                    required: ftp_error_password_required
                },
                settings_ftp_path: {
                    required: ftp_error_invalid_ftp_type
                }
            },
            highlight: function (element) {
                jQuery(element).closest('.control-group').removeClass('success').addClass('error');
            },
            success: function (element) {
                element.addClass('valid').closest('.control-group').removeClass('error').addClass('success');
            }
        });

        jQuery("form#ftpFolders [name=\"selectedFolder\"]").change(function() {
            formFtpFolderValidator.resetForm();
            jQuery(this).closest('.control-group').removeClass('error');
        });

        jQuery('#settings_btn_bf_enabled').on('click', function() {
            var enabled = (jQuery(this).attr('aria-pressed')=='true')?false:true;
            jQuery('#settings_waf_bf_enabled').val(enabled);
        });

        jQuery('#ftpPath').on("click", function (event) {
            var ftpParams = {};
            jQuery('form#ftpForm [name^="settings_ftp"]').each(function() {
                ftpParams[this.name.replace("settings_ftp_", "")] = jQuery(this).val();
            });
            ftpParams["tls"] = (ftpParams['type'] == 'ftps');
            ftpParams['path'] = "/";

            var data = {
                action: "wesecur_security_ajax_test_credentials",
                payload: ftpParams,
                _nonce: _nonce
            };

            if (ftpParams['username'] !== "" && ftpParams['password'] !== "" && ftpParams['port'] !== "" && ftpParams['host'] !== "") {
                jQuery('#ftpBox').loader(loaderParams);
                jQuery.ajax({url: endpoint_ajax,
                             type: 'POST',
                             dataType: 'json',
                             data: data,
                             timeout: 66000,
                    success: function(result) {

                        jQuery.loader.close();
                        if (result.success || result.success == undefined) {
                            jQuery('#ftpFolderModal').modal('toggle');
                            jQuery("#selectedFolder").val("/");

                            printFtpDirectory(result.data.message, ftpParams, _nonce, loaderParams);

                            jQuery('#testConnection').on('click', function() {
                                jQuery('form#ftpFolders').loader(loaderParams);
                                var data = {
                                    action: "wesecur_security_ajax_test_connection",
                                    payload: ftpParams,
                                    _nonce: _nonce
                                };
                                jQuery.ajax({url: endpoint_ajax,
                                    type: 'POST',
                                    dataType: 'json',
                                    data: data,
                                    success: function(result) {
                                        jQuery.loader.close();
                                        if (result.success || result.success == undefined) {
                                            jQuery('form#ftpForm [name="settings_ftp_path"]').val(ftpParams["path"]);
                                            jQuery("#selectedFolder").trigger("change");
                                            jQuery('#ftpFolderModal').modal('toggle');
                                        }else{
                                            jQuery('form#ftpFolders [name="selectedFolder"]').closest('.control-group').removeClass('success').addClass('error');
                                            var errors = { selectedFolder: ftp_error_invalid_folder };
                                            formFtpFolderValidator.showErrors(errors);
                                        }
                                    },
                                    error: function(result) {
                                        jQuery.loader.close();
                                        if (result.responseText.indexOf("503 Service Unavailable") > -1) {
                                            jQuery('#ajax-error').show();
                                            jQuery('html, body').animate({
                                                scrollTop: jQuery("#ajax-error").offset().top
                                            }, 400);
                                        }
                                    },
                                    timeout: 66000
                                });
                            });
                        }else {
                            parseAjaxErrors(result, formValidator);
                        }
                    },
                    error: function(result) {
                        jQuery.loader.close();
                    }
                });
            }
        });
    });
</script>
{/literal}

<div id="wesecur-loading"></div>
<div id="bootstrap-wrapper-settings" class="bootstrap-wrapper">
    <div class="row col-md-12">
        <div class="col-xl-5 col-lg-7 col-md-8 col-sm-12">
            <div class="wesecursecurity-container">
                <div class="wesecursecurity-box">
                    <div class="wesecursecurity-box-title">
                        <h1>{$settings_waf_title}</h1>
                        <i id="tooltip-test"
                           class="fa fa-info-circle red-tooltip"
                           data-toggle="tooltip"
                           data-placement="top"
                           data-container="#bootstrap-wrapper-settings"
                           title="{$settings_waf_description}"
                           aria-hidden="true">
                        </i>
                    </div>
                    <form method="POST" action="?page={$page_url}">
                        <div class="form-group row">
                            <label for="exampleInputEmail1" class="col-sm-6 col-form-label">
                                {$settings_waf_bf_text}
                                <i id="tooltip-test"
                                   class="fa fa-info-circle red-tooltip"
                                   data-toggle="tooltip"
                                   data-placement="top"
                                   data-container="#bootstrap-wrapper-settings"
                                   data-offset="50"
                                   title="{$settings_waf_bf_description}"
                                   aria-hidden="true">
                                </i>
                            </label>
                            <div class="col-sm-4">
                                <button type="button"
                                        class="btn btn-sm btn-toggle {if $settings_waf_bf_enabled == "true"}active{/if}"
                                        data-toggle="button"
                                        aria-pressed="{$settings_waf_bf_enabled}"
                                        autocomplete="off"
                                        id="settings_btn_bf_enabled">
                                    <div class="handle"></div>
                                </button>
                                <input type="hidden"
                                       id="settings_waf_bf_enabled"
                                       name="settings_waf_bf_enabled"
                                       value="{$settings_waf_bf_enabled}">
                            </div>
                        </div>
                        <div class="form-group row">
                            <label for="allowedLogins" class="col-sm-6 col-form-label">
                                {$settings_waf_bf_attempts_text}
                            </label>
                            <div class="col-sm-5">
                                <input type="number"
                                       class="form-control col-xl-6 col-lg-12 col-md-12 col-sm-12"
                                       id="allowedLogins"
                                       aria-describedby="allowedLogins"
                                       name="settings_waf_bf_attempts"
                                       min="1"
                                       value="{$settings_waf_bf_attempts}">
                            </div>
                        </div>
                        <div class="form-group row">
                            <label for="loginsInterval" class="col-sm-6 col-form-label">
                                {$settings_waf_bf_interval_text}
                            </label>
                            <div class="col-sm-5">
                                <input type="number"
                                       class="form-control col-xl-6 col-lg-12 col-md-12 col-sm-12"
                                       id="loginsInterval"
                                       aria-describedby="loginsInterval"
                                       name="settings_waf_bf_interval"
                                       min="1"
                                       value="{$settings_waf_bf_interval}">
                            </div>
                        </div>
                        <div class="form-group row">
                            <label for="blockDuration" class="col-sm-6 col-form-label">
                                {$settings_waf_bf_ban_text}
                                <i id="tooltip-test"
                                   class="fa fa-info-circle red-tooltip"
                                   data-toggle="tooltip"
                                   data-placement="top"
                                   data-container="#bootstrap-wrapper-settings"
                                   data-offset="50"
                                   title="{$settings_waf_bf_ban_description}"
                                   aria-hidden="true">
                                </i>
                            </label>
                            <div class="col-sm-5">
                                <input type="number"
                                       class="form-control col-xl-6 col-lg-12 col-md-12 col-sm-12"
                                       id="blockDuration"
                                       aria-describedby="blockDuration"
                                       placeholder=""
                                       name="settings_waf_bf_ban_time"
                                       min="1"
                                       value="{$settings_waf_bf_ban_time}">
                                <small id="blockDuration" class="text-muted">
                                    {$settings_waf_bf_minutes_text}
                                </small>
                            </div>
                        </div>
                        <input type="submit" class="btn btn-primary" value="{$settings_waf_bruteforce_btn_text}">
                        <input type="hidden" name="action" value="saveBruteforceOptions">
                        {$nonce_fields}
                    </form>
                </div>
            </div>
        </div>
        <div class="col-xl-5 col-lg-7 col-md-8 col-sm-12">
            <div class="wesecursecurity-container">
                <div class="wesecursecurity-box">
                    <div class="wesecursecurity-box-title">
                        <h1>{$settings_hardening_title}</h1>
                        <i id="tooltip-test"
                           class="fa fa-info-circle red-tooltip"
                           data-toggle="tooltip"
                           data-placement="top"
                           data-container="#bootstrap-wrapper-settings"
                           title="{$settings_hardening_description}"
                           aria-hidden="true">
                        </i>
                    </div>
                    <form method="POST" action="?page={$page_url}">
                        <div class="form-group row">
                            <label class="col-sm-8 col-form-label">
                                {$settings_hardening_default_user_text}
                                <i id="tooltip-test"
                                   class="fa fa-info-circle"
                                   data-toggle="tooltip"
                                   data-placement="top"
                                   data-container="#bootstrap-wrapper-settings"
                                   data-offset="200"
                                   title="{$settings_hardening_default_user_description}"
                                   aria-hidden="true">
                                </i>
                            </label>
                            <div class="col-sm-4">
                                <input type="button"
                                       data-toggle="modal"
                                       data-target="#changeAdminModal"
                                       class="btn {if !$settings_hardening_exist_admin_user}btn-primary{else}btn-warning{/if} btn-sm" {if !$settings_hardening_exist_admin_user}disabled{/if} value="{if !$settings_hardening_exist_admin_user}{$settings_hardening_revert_hardening}{else}{$settings_hardening_apply_hardening}{/if}">
                            </div>
                        </div>
                        <input type="hidden" name="action" value="changeAdminUsername">
                        {$nonce_fields}
                    </form>
                    <form method="POST" action="?page={$page_url}">
                        <div class="form-group row">
                            <label class="col-sm-8 col-form-label">
                                {$settings_hardening_hide_version_text}
                                <i id="tooltip-test"
                                   class="fa fa-info-circle"
                                   data-toggle="tooltip"
                                   data-placement="top"
                                   data-container="#bootstrap-wrapper-settings"
                                   data-offset="430"
                                   title="{$settings_hardening_hide_version_description}"
                                   aria-hidden="true">
                                </i>
                            </label>
                            <div class="col-sm-4">
                                <input type="submit" class="btn {if $settings_hardening_hide_version}btn-primary{else}btn-warning{/if} btn-sm" value="{if $settings_hardening_hide_version}{$settings_hardening_revert_hardening}{else}{$settings_hardening_apply_hardening}{/if}">
                            </div>
                        </div>
                        <input type="hidden" name="action" value="{if $settings_hardening_hide_version}showWordPressVersion{else}hideWordPressVersion{/if}">
                        {$nonce_fields}
                    </form>
                    <form method="POST" action="?page={$page_url}">
                        <div class="form-group row">
                            <label class="col-sm-8 col-form-label">
                               {$settings_hardening_default_theme_text}
                                <i id="tooltip-test"
                                   class="fa fa-info-circle"
                                   data-toggle="tooltip"
                                   data-placement="top"
                                   data-container="#bootstrap-wrapper-settings"
                                   data-offset="200"
                                   title="{$settings_hardening_default_theme_description}"
                                   aria-hidden="true">
                                </i>
                            </label>
                            <div class="col-sm-4">
                                <input type="submit" class="btn {if $settings_hardening_editor_disabled}btn-primary{else}btn-warning{/if} btn-sm" value="{if $settings_hardening_editor_disabled}{$settings_hardening_revert_hardening}{else}{$settings_hardening_apply_hardening}{/if}">
                            </div>
                        </div>
                        <input type="hidden" name="action" value="{if $settings_hardening_editor_disabled}enableThemePluginEditor{else}disableThemePluginEditor{/if}">
                        {$nonce_fields}
                    </form>
                    <form method="POST" action="?page={$page_url}">
                        <div class="form-group row">
                            <label class="col-sm-8 col-form-label">
                                {$settings_hardening_xmlrpc_text}
                                <i id="tooltip-test"
                                   class="fa fa-info-circle"
                                   data-toggle="tooltip"
                                   data-placement="top"
                                   data-container="#bootstrap-wrapper-settings"
                                   data-offset="200"
                                   title="{$settings_hardening_xmlrpc_description}"
                                   aria-hidden="true">
                                </i>
                            </label>
                            <div class="col-sm-4">
                                <input type="submit" class="btn {if $settings_hardening_xmlrpc_disabled}btn-primary{else}btn-warning{/if} btn-sm" value="{if $settings_hardening_xmlrpc_disabled}{$settings_hardening_revert_hardening}{else}{$settings_hardening_apply_hardening}{/if}">
                            </div>
                        </div>
                        <input type="hidden" name="action" value="{if $settings_hardening_xmlrpc_disabled}enableXmlrpc{else}disableXmlrpc{/if}">
                        {$nonce_fields}
                    </form>
                    <form method="POST" action="?page={$page_url}">
                        <div class="form-group row">
                            <label class="col-sm-8 col-form-label">
                                {$settings_hardening_php_execution_text}
                                <i id="tooltip-test"
                                   class="fa fa-info-circle"
                                   data-toggle="tooltip"
                                   data-placement="top"
                                   data-container="#bootstrap-wrapper-settings"
                                   data-offset="200"
                                   title="{$settings_hardening_php_execution_description}"
                                   aria-hidden="true">
                                </i>
                            </label>
                            <div class="col-sm-4">
                                <input type="submit" class="btn {if $settings_hardening_php_execution_disabled}btn-primary{else}btn-warning{/if} btn-sm" value="{if $settings_hardening_php_execution_disabled}{$settings_hardening_revert_hardening}{else}{$settings_hardening_apply_hardening}{/if}">
                            </div>
                        </div>
                        <input type="hidden" name="action" value="{if $settings_hardening_php_execution_disabled}enablePhpExecution{else}disablePhpExecution{/if}">
                        {$nonce_fields}
                    </form>
                    {include file="$admin_hardening"}
                    {include file="$ftp_folder"}
                </div>
            </div>
        </div>
        <div class="col-xl-5 col-lg-7 col-md-8 col-sm-12">
            <div class="wesecursecurity-container"
                 {if $settings_api_required}
                 data-toggle="tooltip"
                 data-placement="top"
                 data-container="#bootstrap-wrapper-settings"
                 title="{$settings_api_required_description}"
                 {/if}
                 id="ftpBox">
                <div class="wesecursecurity-box {if $settings_api_required}wesecursecurity-disable-section{/if}">
                    <div class="wesecursecurity-box-title">
                        <h1>{$settings_ftp_title}</h1>
                        <i id="tooltip-test"
                           class="fa fa-info-circle red-tooltip"
                           data-toggle="tooltip"
                           data-placement="top"
                           data-container="#bootstrap-wrapper-settings"
                           title="{$settings_ftp_description}"
                           aria-hidden="true">
                        </i>
                    </div>
                    <form method="POST" name="ftpForm" id="ftpForm" action="?page={$page_url}">
                        <div class="form-group row">
                            <label for="ftpType" class="col-sm-6 col-form-label">
                                {$settings_ftp_type_text}
                            </label>
                            <div class="col-sm-4">
                                <select id="ftpType" name="settings_ftp_type">
                                    <option value="ftp" {if $settings_ftp_type == 'ftp'}selected{/if}>{$settings_ftp_type_ftp_text}</option>
                                    <option value="ftps" {if $settings_ftp_type == 'ftps'}selected{/if}>{$settings_ftp_type_ftps_text}</option>
                                    <option value="sftp" {if $settings_ftp_type == 'sftp'}selected{/if}>{$settings_ftp_type_sftp_text}</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-group control-group row">
                            <label for="ftpHost" class="col-sm-6 col-form-label">
                                {$settings_ftp_host_text}
                            </label>
                            <div class="col-sm-6">
                                <input type="text"
                                       class="form-control col-xl-8 col-lg-12 col-md-12 col-sm-12"
                                       id="ftpHost"
                                       aria-describedby="ftpHost"
                                       name="settings_ftp_host"
                                       value="{$settings_ftp_host}">
                            </div>
                        </div>
                        <div class="form-group control-group row">
                            <label for="ftpPort" class="col-sm-6 col-form-label">
                                {$settings_ftp_port_text}
                            </label>
                            <div class="col-sm-4">
                                <input type="text"
                                       class="form-control col-xl-6 col-lg-12 col-md-12 col-sm-12"
                                       id="ftpPort"
                                       aria-describedby="ftpPort"
                                       name="settings_ftp_port"
                                       value="{$settings_ftp_port}">
                            </div>
                        </div>
                        <div class="form-group row">
                            <label for="ftpUsername" class="col-sm-6 col-form-label">
                                {$settings_ftp_username_text}
                            </label>
                            <div class="col-sm-6">
                                <input type="text"
                                       class="form-control col-xl-8 col-lg-12 col-md-12 col-sm-12"
                                       id="ftpUsername"
                                       aria-describedby="ftpUsername"
                                       name="settings_ftp_username"
                                       value="{$settings_ftp_username}"
                                       required>
                            </div>
                        </div>
                        <div class="form-group row">
                            <label for="ftpPassword" class="col-sm-6 col-form-label">
                                {$settings_ftp_password_text}
                            </label>
                            <div class="col-sm-6">
                                <input type="password"
                                       class="form-control col-xl-8 col-lg-12 col-md-12 col-sm-12"
                                       id="ftpPassword"
                                       aria-describedby="ftpPassword"
                                       name="settings_ftp_password"
                                       value="{$settings_ftp_password}"
                                       required>
                            </div>
                        </div>
                        <div class="form-group row">
                            <label for="ftpPath" class="col-sm-6 col-form-label">
                                {$settings_ftp_folder_text}
                                <i id="tooltip-ftp-type"
                                   class="fa fa-info-circle red-tooltip"
                                   data-toggle="tooltip"
                                   data-placement="top"
                                   data-container="#bootstrap-wrapper-settings"
                                   data-offset="20"
                                   title="{$settings_ftp_folder_description}"
                                   aria-hidden="true">
                                </i>
                            </label>

                            <div class="col-sm-6">
                                <input type="text"
                                       class="form-control col-xl-8 col-lg-12 col-md-12 col-sm-12"
                                       id="ftpPath"
                                       aria-describedby="ftpPath"
                                       name="settings_ftp_path"
                                       value="{$settings_ftp_folder}"
                                       required
                                       readonly>
                            </div>
                        </div>
                        <input type="submit" class="btn btn-primary" value="{$settings_ftp_save_btn_text}">
                        <input type="hidden" name="action" value="saveFtpData">
                        {$nonce_fields}
                    </form>
                </div>
            </div>
        </div>
        <div class="col-xl-6 col-lg-8 col-md-12 col-sm-12">
            <div class="wesecursecurity-container">
                <div class="wesecursecurity-box">
                    <div class="wesecursecurity-box-title">
                        <h1>{$settings_apikey_title}</h1>
                        <i id="tooltip-test"
                           class="fa fa-info-circle red-tooltip"
                           data-toggle="tooltip"
                           data-placement="top"
                           data-container="#bootstrap-wrapper-settings"
                           data-offset="710"
                           title="{$settings_apikey_description}"
                           aria-hidden="true">
                        </i>
                    </div>
                    <form method="POST" action="?page={$page_url}">
                        <div class="form-group row">
                            <label class="col-sm-8 col-form-label">
                                {$settings_apikey_default_text}
                            </label>
                            <div class="col-sm-12">
                                <textarea class="form-control col-lg-12"
                                          rows="2"
                                          id="settings_apikey"
                                          aria-describedby="settings_apikey"
                                          name="settings_apikey">{$settings_apikey_value}</textarea>
                            </div>
                        </div>
                        <input type="hidden" name="action" value="saveApiKey">
                        <input type="submit" class="btn btn-primary" value="{$settings_apikey_btn_text}">
                        {if $settings_api_required}
                            <input type="button" style="float:right" class="btn btn-warning" data-toggle="modal" data-target="#getApiKey" value="{$settings_apikey_premium_text}">
                            {include file="$settings_apikey_modal"}
                        {/if}
                        {$nonce_fields}
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
