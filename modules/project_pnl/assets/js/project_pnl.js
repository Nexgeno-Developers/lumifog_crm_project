/* Project Profit & Loss dashboard (Chart.js 2.x bundled with Perfex) */
(function ($) {
    'use strict';

    var COLORS = {
        revenue: '#22c55e',
        purchase: '#f59e0b',
        expenses: '#ef4444',
        profit: '#2563eb',
        palette: ['#f59e0b', '#ef4444', '#8b5cf6', '#06b6d4', '#ec4899', '#84cc16', '#64748b', '#f97316', '#14b8a6', '#a855f7']
    };

    function money(value) {
        if (typeof format_money === 'function') {
            return format_money(value);
        }
        return Number(value).toLocaleString(undefined, { maximumFractionDigits: 2 });
    }

    function shortMoney(value) {
        var abs = Math.abs(value);
        if (abs >= 10000000) {
            return (value / 10000000).toFixed(1) + ' Cr';
        }
        if (abs >= 100000) {
            return (value / 100000).toFixed(1) + ' L';
        }
        if (abs >= 1000) {
            return (value / 1000).toFixed(1) + ' K';
        }
        return value;
    }

    function renderMonthlyChart(data) {
        var canvas = document.getElementById('project-pnl-monthly-chart');
        if (!canvas || !data.monthly.labels.length) {
            return;
        }

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
                            return chart.datasets[item.datasetIndex].label + ': ' + money(item.yLabel);
                        }
                    }
                },
                scales: {
                    yAxes: [{ ticks: { beginAtZero: true, callback: shortMoney } }]
                }
            }
        });
    }

    function renderCostChart(data) {
        var canvas = document.getElementById('project-pnl-cost-chart');
        if (!canvas || !data.cost_split.values.length) {
            return;
        }

        var colors = data.cost_split.values.map(function (_, i) {
            return COLORS.palette[i % COLORS.palette.length];
        });

        new Chart(canvas, {
            type: 'doughnut',
            data: {
                labels: data.cost_split.labels,
                datasets: [{ data: data.cost_split.values, backgroundColor: colors, borderWidth: 1 }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutoutPercentage: 60,
                legend: { position: 'bottom', labels: { boxWidth: 12 } },
                tooltips: {
                    callbacks: {
                        label: function (item, chart) {
                            var values = chart.datasets[0].data;
                            var total = values.reduce(function (sum, v) { return sum + v; }, 0);
                            var value = values[item.index];
                            var pct = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                            return chart.labels[item.index] + ': ' + money(value) + ' (' + pct + '%)';
                        }
                    }
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
        renderCostChart(project_pnl_data);
    });
})(jQuery);
