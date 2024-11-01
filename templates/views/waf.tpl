<script>

    var chartData = {$waf_requests};
    var updateMsg = "{$waf_premium_text}";
    var yAxesLabel = "{$waf_requests_yaxes_label}";
    var chartData = [];
    var chartLabels = [];

    {literal}

    jQuery(function () {

        Chart.plugins.register({
            afterDraw: function(chart) {

                if (chart.data.datasets[0].data.length < 2) {
                    // No data is present
                    var ctx = chart.chart.ctx;
                    var width = chart.chart.width;
                    var height = chart.chart.height;
                    var dateNow = new Date();

                    /*chart.data.datasets[0].label = 'Requests';
                    chart.data.datasets[0].data = [{
                        x: dateNow.toISOString(),
                        y: 0
                    }];*/

                    chart.update();

                    ctx.textAlign = 'center';
                    ctx.textBaseline = 'middle';
                    ctx.font = "16px bold 'Helvetica Nueue'";
                    ctx.fillText(updateMsg, width / 2, height / 2);
                    ctx.restore();
                }
            }
        });

        var ctx = document.getElementById("line-chart");
        var lineChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: chartLabels,
                datasets: [{
                    data: chartData,
                    label: yAxesLabel,
                    borderColor: "#67b7dc",
                    fill: false
                }
                ]
            },
            options: {
                maintainAspectRatio: false,
                legend: {
                    display: false
                },
                scales: {
                    xAxes: [{
                        type: 'time',
                        time: {
                            unit: 'day',
                            unitStepSize: 1,
                            displayFormats: {
                                'day': 'MMM DD'
                            }
                        },
                        display: true,
                        scaleLabel: {
                            display: false,
                            labelString: 'Date'
                        },
                        ticks: {
                            major: {
                                fontStyle: 'bold',
                                fontColor: '#FF0000'
                            }
                        }
                    }],
                    yAxes: [{
                        display: true,
                        scaleLabel: {
                            display: true,
                            labelString: yAxesLabel
                        },
                        ticks: {
                            min: 0,
                            max: 100,
                            stepSize: 10
                        }
                    }]
                }
            }
        });
    });



    {/literal}

</script>

<div id="bootstrap-wrapper-waf" class="bootstrap-wrapper">
    <div class="row col-md-12">
        <div class="col-md-12">
            <div class="wesecursecurity-container">
                <div class="wesecursecurity-box">
                    <div class="wesecursecurity-box-title">
                        <h1>{$waf_title}</h1>
                        <i id="tooltip-test"
                           class="fa fa-info-circle red-tooltip"
                           data-toggle="tooltip"
                           data-placement="top"
                           data-container="#bootstrap-wrapper-waf"
                           data-offset="100"
                           title="{$waf_description}"
                           aria-hidden="true">
                        </i>
                    </div>
                    <div class="alert alert-warning" role="alert">{$waf_api_required_description}</div>
                    {include file="$premium_modal"}
                    <div id="wesecur-line-chart">
                        <canvas id="line-chart" width="600" height="450"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="row col-md-12">
        <div class="col-md-3">
            <div class="wesecursecurity-container">
                <div class="wesecursecurity-box">
                    <div class="wesecursecurity-box-title">
                        <h1>{$waf_banned_title}</h1>
                        <i id="tooltip-test"
                           class="fa fa-info-circle red-tooltip"
                           data-toggle="tooltip"
                           data-placement="top"
                           data-container="#bootstrap-wrapper-waf"
                           data-offset="200"
                           title="{$waf_banned_description}"
                           aria-hidden="true">
                        </i>
                    </div>

                    <table data-toggle="table"
                           id="table-banned-ip"
                           name="table-banned-ip"
                           data-search="true"
                           data-page-list="[10, 25, 50, 100, 200, ALL]"
                           data-pagination="true">
                        <thead>
                        <tr>
                            <th data-field="time">{$waf_banned_time}</th>
                            <th data-field="ip">{$waf_banned_ip}</th>
                        </tr>
                        </thead>
                        <tbody>
                        {foreach from=$waf_banned_ips key=bannedIp item=hour}
                            <tr>
                                <td>{$hour|date_format:"%Y-%m-%e %H:%M:%S"}</td>
                                <td>{$bannedIp}</td>
                            </tr>
                        {/foreach}
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>