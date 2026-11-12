/* Project Profit & Loss dashboard (Chart.js 2.x bundled with Perfex) */
(function ($) {
    'use strict';

    var COLORS = {
        revenue: '#22c55e',
        purchase: '#f59e0b',
        expenses: '#ef4444',
        profit: '#2563eb'
    };

    // Format with the project's currency (Perfex's format_money() defaults to "$" here)
    function money(value, currency) {
        var symbol = currency.symbol || '';
        var options = {
            symbol: symbol,
            precision: currency.precision,
            decimal: currency.decimal,
            thousand: currency.thousand,
            format: currency.placement === 'after' ? '%v %s' : '%s%v'
        };

        if (typeof accounting !== 'undefined') {
            return accounting.formatMoney(value, options);
        }

        var number = Number(value).toFixed(currency.precision);
        return currency.placement === 'after' ? number + ' ' + symbol : symbol + number;
    }

    // Compact axis labels in Indian units (K / L / Cr)
    function shortMoney(value, currency) {
        var abs = Math.abs(value);
        var text = value;
        if (abs >= 10000000) {
            text = (value / 10000000).toFixed(1) + ' Cr';
        } else if (abs >= 100000) {
            text = (value / 100000).toFixed(1) + ' L';
        } else if (abs >= 1000) {
            text = (value / 1000).toFixed(1) + ' K';
        }
        var symbol = currency.symbol || '';
        return currency.placement === 'after' ? text + ' ' + symbol : symbol + text;
    }

    function renderMonthlyChart(data) {
        var canvas = document.getElementById('project-pnl-monthly-chart');
        if (!canvas || !data.monthly.labels.length) {
            return;
        }

        var currency = data.currency;

        new Chart(canvas, {
            type: 'bar',
            data: {
                labels: data.monthly.labels,
                datasets: [
                    {
                        type: 'line',
                        label: data.labels.cumulative_profit,
                        data: data.monthly.cumulative_profit,
                        borderColor: COLORS.profit,
                        backgroundColor: 'rgba(37, 99, 235, 0.08)',
                        borderWidth: 2,
                        pointRadius: 3,
                        fill: false,
                        lineTension: 0.2
                    },
                    { label: data.labels.revenue, data: data.monthly.revenue, backgroundColor: COLORS.revenue },
                    { label: data.labels.purchase, data: data.monthly.purchase, backgroundColor: COLORS.purchase },
                    { label: data.labels.expenses, data: data.monthly.expenses, backgroundColor: COLORS.expenses }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                legend: { position: 'bottom' },
                tooltips: {
                    mode: 'index',
                    intersect: false,
                    callbacks: {
                        label: function (item, chart) {
                            return chart.datasets[item.datasetIndex].label + ': ' + money(item.yLabel, currency);
                        }
                    }
                },
                scales: {
                    yAxes: [{
                        ticks: {
                            beginAtZero: true,
                            callback: function (value) {
                                return shortMoney(value, currency);
                            }
                        }
                    }]
                }
            }
        });
    }

    $(function () {
        var $dashboard = $('#project-pnl-dashboard');
        if (!$dashboard.length) {
            return;
        }

        // Rendered inside the overview's right column; show it full width after the overview panel
        var $overviewPanel = $('.project-overview-right').closest('.panel_s');
        if ($overviewPanel.length) {
            $dashboard.insertAfter($overviewPanel);
        }
        $dashboard.removeClass('hide');

        if (typeof Chart === 'undefined' || typeof project_pnl_data === 'undefined') {
            return;
        }

        renderMonthlyChart(project_pnl_data);
    });
})(jQuery);
