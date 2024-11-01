<script>
    var audit_logs_length = {$dashboard_auditlogs|@count};
    var dashboardStrengthPoints = {$dashboard_strength_points};
    var securityLabel = "{$dashboard_strength_security_label}";
    {literal}
    function dateTimeFormattter(value, row, index, field) {
        var date = new Date(value*1000);
        return date.toLocaleDateString() + " " + date.toLocaleTimeString();
    }

    jQuery(function () {
        initAuditLogsTable(audit_logs_length, tableLocale);

        var chart = c3.generate({
            data: {
                columns: [
                    [securityLabel, dashboardStrengthPoints]
                ],
                type: 'gauge',
                onclick: function (d, i) { console.log("onclick", d, i); },
                onmouseover: function (d, i) { console.log("onmouseover", d, i); },
                onmouseout: function (d, i) { console.log("onmouseout", d, i); }
            },
            gauge: {
            },
            color: {
                pattern: ['#D32200', '#d35e20', '#d0d337', '#0080ff', '#66d31b'], // the three color levels for the percentage values.
                threshold: {
                    values: [16, 30, 50, 80, 100]
                }
            },
            size: { height: 240 } // size of the chart
        });

        chart.load({
            columns: [[securityLabel, dashboardStrengthPoints]]
        });
    });

    function initAuditLogsTable(audit_logs_length, tableLocale) {

        $table = jQuery('#table');

        var height = 615;
        if (audit_logs_length <= 0) {
            height = 200;
        }
    }
</script>
{/literal}

<div id="bootstrap-wrapper-dashboard" class="bootstrap-wrapper">
    <div class="row col-md-12">
        <div class="col-md-3 col-sm-6">
            <div class="wesecursecurity-container">
                <div class="wesecursecurity-box-small">
                    <div class="wesecursecurity-box wesecur-box-info">
                        <div class="wesecursecurity-box-title">
                            <ol>
                                <li class="fa
                                    {if $dashboard_malware_issues}fa-exclamation-triangle text-danger{else}fa-check-circle text-success{/if}"
                                    data-toggle="tooltip"
                                    data-placement="top"
                                    data-offset="230"
                                    data-container="#bootstrap-wrapper-dashboard"
                                    title="{if $dashboard_malware_issues}
                                                {$dashboard_info_malware_found_text}
                                            {else}
                                                {$dashboard_info_malware_notfound_text}
                                            {/if}
                                    ">
                                </li>
                                <span class="box-info-title">{$dashboard_info_malware_text}</span>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="wesecursecurity-container">
                <div class="wesecursecurity-box-small">
                    <div class="wesecursecurity-box wesecur-box-info">
                        <div class="wesecursecurity-box-title">
                            <ol>
                                <li class="fa
                                    {if $dashboard_blacklist_issues}fa-exclamation-triangle text-danger{else}fa-check-circle text-success{/if}"
                                    data-toggle="tooltip"
                                    data-placement="top"
                                    data-container="#bootstrap-wrapper-dashboard"
                                    title="{if $dashboard_blacklist_issues}
                                                {$dashboard_info_blacklist_found_text}
                                           {else}
                                                {$dashboard_info_blacklist_notfound_text}
                                           {/if}">
                                </li>
                                <span class="box-info-title">{$dashboard_info_blacklists_text}</span>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="wesecursecurity-container">
                <div class="wesecursecurity-box-small">
                    <div class="wesecursecurity-box wesecur-box-info">
                        <div class="wesecursecurity-box-title">
                            <ol>
                                <li class="fa
                                    {if $dashboard_integrity_issues}fa-exclamation-triangle text-danger{else}fa-check-circle text-success{/if}"
                                    data-toggle="tooltip"
                                    data-placement="top"
                                    data-container="#bootstrap-wrapper-dashboard"
                                    title="{if $dashboard_integrity_issues}
                                                {$dashboard_info_integrity_found_text}
                                           {else}
                                                {$dashboard_info_integrity_notfound_text}
                                           {/if}">
                                </li>
                                <span class="box-info-title">{$dashboard_info_integrity_text}</span>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="wesecursecurity-container">
                <div class="wesecursecurity-box-small">
                    <div class="wesecursecurity-box wesecur-box-info">
                        <div class="wesecursecurity-box-title">
                            <ol>
                                <li class="fa
                                    {if $dashboard_vulnerabilities_enabled}
                                        {if $dashboard_vulnerabilities_issues}
                                            fa-exclamation-triangle text-danger
                                        {else}
                                            fa-check-circle text-success
                                        {/if}
                                    {else}
                                        fa-question-circle
                                    {/if}"
                                    data-toggle="tooltip"
                                    data-placement="top"
                                    data-container="#bootstrap-wrapper-dashboard"
                                    data-html="true"
                                    title="{if $dashboard_vulnerabilities_enabled}
                                                {if $dashboard_vulnerabilities_issues}
                                                    Vulnerabilities found!
                                                {else}

                                                {/if}

                                            {else}
                                                {$dashboard_info_vulnerabilities_enable}
                                            {/if}
                                        ">
                                </li>
                                <span class="box-info-title">{$dashboard_info_vulnerabilities_text}</span>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="row col-md-12">
        <div class="col-xl-4 col-lg-6 col-md-12 col-sm-12">
            <div class="wesecursecurity-container">
                <div class="wesecursecurity-box">
                    <div class="wesecursecurity-box-title">
                        <h1>{$dashboard_strength_title}</h1>
                        <i id="tooltip-test"
                           class="fa fa-info-circle red-tooltip"
                           data-toggle="tooltip"
                           data-placement="top"
                           data-container="#bootstrap-wrapper-dashboard"
                           title="{$dashboard_strength_description}"
                           aria-hidden="true">
                        </i>
                    </div>

                    <div id="chart"></div>

                    <strong>{$dashboard_strength_recommendations}</strong>
                    <ul class="list-group">
                        {if !$dashboard_bf_enabled}
                            <li class="list-group-item">{$dashboard_strength_enable_bf}</li>
                        {/if}
                        {if $dashboard_malware_issues}
                            <li class="list-group-item">{$dashboard_strength_fix_malware_error}</li>
                        {/if}
                        {if $dashboard_integrity_issues}
                            <li class="list-group-item">{$dashboard_strength_fix_integrity_error}</li>
                        {/if}
                        {if $dashboard_admin_username}
                            <li class="list-group-item">{$dashboard_strength_fix_admin_username}</li>
                        {/if}
                        {if !$dashboard_editor_disabled}
                            <li class="list-group-item">{$dashboard_strength_fix_editor}</li>
                        {/if}
                        {if !$dashboard_wp_version_hiden}
                            <li class="list-group-item">{$dashboard_strength_fix_wp_version}</li>
                        {/if}
                        {if !$dashboard_xmlrpc_disabled}
                            <li class="list-group-item">{$dashboard_strength_fix_xmlrpc}</li>
                        {/if}
                        {if !$dashboard_phpexecution_disabled}
                            <li class="list-group-item">{$dashboard_strength_fix_php_execution}</li>
                        {/if}
                        {if !$dashboard_vulnerabilities_enabled}
                            <li class="list-group-item">{$dashboard_strength_fix_enable_scanner}</li>
                            {include file="$dashboard_strength_server_side_modal"}
                        {/if}
                        {if !$dashboard_waf_enabled}
                            <li class="list-group-item">{$dashboard_strength_fix_enable_waf}</li>
                            {include file="$dashboard_strength_waf_modal"}
                        {/if}
                    </ul>

                </div>
            </div>
        </div>
        <div class="col-xl-8 col-lg-6 col-md-12 col-sm-12">
            <div class="wesecursecurity-container">
                <div class="wesecursecurity-box">
                    <div class="wesecursecurity-box-title">
                        <h1>{$dashboard_auditlogs_title}</h1>
                        <i id="tooltip-test"
                           class="fa fa-info-circle red-tooltip"
                           data-toggle="tooltip"
                           data-placement="top"
                           data-container="#bootstrap-wrapper-dashboard"
                           title="{$dashboard_info_auditlogs_text}"
                           aria-hidden="true">
                        </i>
                    </div>
                    <table data-toggle="table"
                           id="table-auditlogs"
                           name="table-auditlogs"
                           data-search="true"
                           data-page-list="[10, 25, 50, 100, 200, ALL]"
                           data-pagination="true">
                        <thead>
                        <tr>
                            <th data-field="time">{$dashboard_auditlog_time_text}</th>
                            <th data-field="event">{$dashboard_auditlog_event_text}</th>
                            <th data-field="forks_count">{$dashboard_auditlog_ip_text}</th>
                        </tr>
                        </thead>
                        <tbody>
                        {foreach from=$dashboard_auditlogs item=auditLog}
                            <tr {if $auditLog->type == "bruteforceattempt"}class="table-warning"{/if} data-index="0">
                                <td>{$auditLog->time}</td>
                                {if $auditLog->type == "lastlogin"}
                                    <td>
                                        {$dashboard_auditlog_user_text} <strong>{$auditLog->user_login}</strong> {$dashboard_auditlog_authentication_succeeded_text}
                                    </td>
                                {elseif $auditLog->type == "activatedplugin"}
                                    <td>
                                        {$dashboard_auditlog_user_text} <strong>{$auditLog->user_login}</strong> {$dashboard_auditlog_has_activated_plugin} <strong>{$auditLog->extra->plugin_name}</strong>
                                    </td>
                                {elseif $auditLog->type == "deactivatedplugin"}
                                    <td>
                                        {$dashboard_auditlog_user_text} <strong>{$auditLog->user_login}</strong> {$dashboard_auditlog_has_deactivated_plugin} <strong>{$auditLog->extra->plugin_name}</strong>
                                    </td>
                                {elseif $auditLog->type == "failedlogin"}
                                    <td>
                                        {$dashboard_auditlog_user_text} <strong>{$auditLog->user_login}</strong> {$dashboard_auditlog_has_failed_login}
                                    </td>
                                {elseif $auditLog->type == "bruteforceattempt"}
                                    <td>
                                        {$dashboard_auditlog_bruteforce_attempt} <strong>{$auditLog->user_login}</strong>
                                    </td>
                                {elseif $auditLog->type == "newuser"}
                                    {if ($auditLog->user_login)}
                                        <td>
                                            {$dashboard_auditlog_user_text} <strong>{$auditLog->user_login}</strong> {$dashboard_auditlog_has_created_user} <strong>{$auditLog->extra->new_user_login}</strong> {$dashboard_auditlog_new_account_with_roles_text} <strong>{$auditLog->extra->new_user_roles}</strong>
                                        </td>
                                    {else}
                                        <td>
                                            {$dashboard_auditlog_new_account_text} <strong>{$auditLog->extra->new_user_login}</strong> {$dashboard_auditlog_new_account_roles_text} <strong>{$auditLog->extra->new_user_roles}</strong>
                                        </td>
                                    {/if}
                                {/if}
                                {if is_object($auditLog->remote_addr)}
                                    <td>{$auditLog->remote_addr->ip} <div class="flag flag-{$auditLog->remote_addr->country_code}" title="{$auditLog->remote_addr->country_name}"></div></td>
                                {else}
                                    <td>{$auditLog->remote_addr}</td>
                                {/if}
                            </tr>
                        {/foreach}
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
