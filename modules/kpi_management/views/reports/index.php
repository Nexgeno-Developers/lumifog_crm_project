<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>

<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="panel_s">
                    <div class="panel-body">
                        <h4 class="no-margin">KPI Management - Reports</h4>
                        <p class="text-muted">Select a report to view KPI performance results.</p>
                        <hr class="hr-panel-heading" />

                        <div class="row">
                            <div class="col-md-4">
                                <a class="btn btn-default btn-block" href="<?php echo admin_url('kpi_management/reports/top_performers'); ?>">
                                    <i class="fa-solid fa-trophy" aria-hidden="true"></i> Top performer of the month
                                </a>
                            </div>
                            <div class="col-md-8">
                                <div class="well well-sm well-light">
                                    <div class="text-muted">
                                        The top performer report is ranked by maximum points to lowest points.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<?php init_tail(); ?>
</body>
</html>

