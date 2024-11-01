<script>
    var integrity_data = {$integrity_files};
    var table_title_status = "{$integrity_title_status}";
    var table_title_size = "{$integrity_title_size}";
    var table_title_modified = "{$integrity_title_modified}";
    var table_title_file = "{$integrity_title_file}";
    var labels = ["{$external_malware_urls_scanned}","{$external_malware_urls_malware}","{$external_malware_javascript_scanned}"];
    var externalChartData = [{$external_malware_num_urls},{$external_malware_files|@count},{$external_malware_javascript_files|@count}];
    {literal}


    jQuery(function () {

        jQuery('#notificationModal').on('show.bs.modal', function (event) {
            var button = jQuery(event.relatedTarget) // Button that triggered the modal
            var action = button.data('action') // Extract info from data-* attributes
            var selectedFiles = $table.bootstrapTable('getSelections');

            var modal = jQuery(this)
            modal.find('input[name="action"]').val(action);
            modal.find('input[name="selectedFiles"]').val(JSON.stringify(selectedFiles));
        })

        initIntegrityTable(integrity_data, tableLocale);
    });

    function initIntegrityTable(integrity_data, tableLocale) {

        $table = jQuery('#table');
        $remove = jQuery('#remove');

        var height = 615;
        if (integrity_data.length <= 0) {
            height = 615;
        }

        $table.bootstrapTable({
            data: integrity_data,
            height: height,
            locale: tableLocale,
            columns: [
                {
                    field: 'state',
                    checkbox: true,
                    align: 'center',
                    valign: 'middle',
                    width: '25'
                },
                {
                    title: table_title_status,
                    field: 'status',
                    align: 'center',
                    valign: 'middle',
                    width: '25'
                },
                {
                    title: table_title_size,
                    field: 'size',
                    align: 'center',
                    valign: 'middle',
                    width: '50',
                    formatter: sizeFormatter,
                    sortable: true
                },
                {
                    title: table_title_modified,
                    field: 'modified_at',
                    align: 'center',
                    width: '50',
                    formatter: dateTimeFormattter,
                    sortable: true
                },
                {
                    field: 'file',
                    title: table_title_file,
                    width: '600',
                    sortable: true
                },
                {
                    field: 'fixable',
                    align: 'center',
                    valign: 'middle',
                    width: '25',
                    visible: false
                }

            ]
        });

        $remove.click(() => {
            const ids = getIdSelections();
            alert(ids);
            console.log(ids);
            $table.bootstrapTable('remove', {
                field: 'file',
                values: ids
            });
            $remove.prop('disabled', true);
        });


        var ctx = document.getElementById("externalMalwareChart");
        ctx.height = 300;

        var externalMalwareChart = new Chart(ctx, {
            type: 'horizontalBar',
            data: {
                labels: labels,
                datasets: [
                    {
                        data: externalChartData,
                        backgroundColor: [
                            '#67b7dc',
                            '#FF0F00',
                            '#67b7dc'
                        ]
                    }
                ]
            },
            options: {
                legend: {
                    display: false
                },
                scales: {
                    yAxes: [{
                        gridLines: {
                            display: false
                        },
                    }]
                }
            }
        });


    }
</script>
{/literal}

<div id="bootstrap-wrapper-integrity" class="bootstrap-wrapper">
    <div class="row col-md-12">
        <div class="col-lx-9 col-lg-9 col-md-12 col-sm-12">
            <div class="wesecursecurity-container">
                <div class="wesecursecurity-box">
                    <div class="wesecursecurity-box-title">
                        <h1>{$integrity_title}</h1>
                        <i class="fa fa-info-circle"
                           data-toggle="tooltip"
                           data-placement="top"
                           data-container="#bootstrap-wrapper-integrity"
                           data-offset="210"
                           title="{$integrity_description}"
                           aria-hidden="true">
                        </i>
                    </div>
                    {if $integrity_alert}
                        <div class="alert alert-danger" role="alert">{$integrity_danger_description}</div>
                    {else}
                        <div class="alert alert-success" role="alert">{$integrity_ok_description}</div>
                    {/if}
                    <form method="POST" action="?page={$page_url}">
                        <input type="submit" class="btn btn-primary" value="{$integrity_start_button_title}">
                        <input type="hidden" name="action" value="checkIntegrity">
                        {$nonce_fields}
                        <div id="toolbar" class="wesecursecurity-toolbar">
                            <div class="wesecursecurity-left">
                                <div class="dropdown">
                                    <button class="btn btn-secondary dropdown-toggle" type="button" id="dropdownMenuActionsButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                        {$antivirus_action_dropdown_title}
                                    </button>
                                    <div class="dropdown-menu" aria-labelledby="dropdownMenuActionsButton">
                                        <a class="dropdown-item default-action" data-toggle="modal" data-target="#notificationModal" data-action="fixIntegrity" href="#">{$integrity_fix_file}</a>
                                        <a class="dropdown-item" data-toggle="modal" data-target="#notificationModal" data-action="ignoreIntegrity" href="#">{$integrity_ignore_file}</a>
                                        <a class="dropdown-item" data-toggle="modal" data-target="#notificationModal" data-action="deleteIntegrity" href="#">{$integrity_delete_file}</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <table id="table"
                               name="table"
                               data-search="true"
                               data-id-field="file"
                               data-page-list="[10, 25, 50, 100, 200, ALL]"
                               data-pagination="true">
                        </table>
                    </form>

                    <div class="modal fade" id="notificationModal" tabindex="-1" role="dialog" aria-labelledby="notificationModalLabel" aria-hidden="true">
                        <div class="modal-dialog" role="document">
                            <form method="POST" formaction="?page={$page_url}">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="notificationModalLabel">{$integrity_modal_title}</h5>
                                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                            <span aria-hidden="true">&times;</span>
                                        </button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="form-group">
                                            <input type="checkbox" checked autocomplete="off">{$integrity_disclaimer}
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <input type="hidden" name="selectedFiles" value="">
                                        <input type="hidden" name="action" value="">
                                        <button type="button" class="btn btn-secondary" data-dismiss="modal">{$integrity_close_action}</button>
                                        <input type="submit" class="btn btn-primary" value="{$integrity_apply_action}"/>
                                        {$nonce_fields}
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lx-3 col-lg-3 col-md-12 col-sm-12">
            <div class="wesecursecurity-container">
                <div class="external-malware-box wesecursecurity-box">
                    <div class="wesecursecurity-box-title">
                        <h1>{$external_malware_title}</h1>
                        <i id="tooltip-test"
                           class="fa fa-info-circle red-tooltip"
                           data-toggle="tooltip"
                           data-placement="top"
                           data-container="#bootstrap-wrapper-integrity"
                           title="{$external_malware_description}"
                           aria-hidden="true">
                        </i>
                    </div>
                    {if !empty($external_malware_files)}
                        <div class="alert alert-danger" role="alert">{$external_malware_danger_description}</div>
                    {else}
                        <div class="alert alert-success" role="alert">{$external_malware_ok_description}</div>
                    {/if}
                    <form method="POST" action="?page={$page_url}">
                        <input type="submit" class="btn btn-primary" value="{$external_malware_start_button_title}">
                        <input type="hidden" name="action" value="checkRemoteMalware">
                        {$nonce_fields}
                    </form>
                    <canvas id="externalMalwareChart" width="900" height="220"></canvas>
                    <a href="#"
                       data-toggle="modal"
                       data-target="#remoteScanModal">{$external_malware_more_details}</a>
                </div>
                {include file="$remote_scan_modal"}
            </div>
            <div class="wesecursecurity-container">
                <div class="blacklist-box wesecursecurity-box">
                    <div class="wesecursecurity-box-title">
                        <h1>{$blacklists_title}</h1>
                        <i id="tooltip-test"
                           class="fa fa-info-circle"
                           data-toggle="tooltip"
                           data-placement="top"
                           data-container="#bootstrap-wrapper-integrity"
                           title="{$blacklists_description}"
                           aria-hidden="true">
                        </i>
                    </div>
                    {if empty($blacklist->urls)}
                        <div class="alert alert-success" role="alert">{$blacklists_ok_description}</div>
                    {/if}
                    <ul class="list-group">
                        {foreach from=$blacklists item=blacklist}
                            <li class="list-group-item {if !empty($blacklist->urls)}list-group-item-danger{else}list-group-item-success{/if}">
                                {$blacklist->name}
                                {if !empty($blacklist->urls)}
                                    <i class="fa fa-exclamation-triangle" aria-hidden="true"></i>
                                {/if}
                            </li>
                        {/foreach}
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>