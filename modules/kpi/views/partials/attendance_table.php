<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
// If summary exists (monthly and no staff selected) show summary table
if (!empty($summary)) { ?>
  <table class="table table-hover">
    <thead>
      <tr>
        <th>#</th>
        <th><?php echo _l('staff'); ?></th>
        <th><?php echo _l('shift_start'); ?></th>
        <th><?php echo 'Avg Points'; ?></th>
      </tr>
    </thead>
    <tbody>
      <?php $i=1; foreach ($summary as $s) { ?>
        <tr>
          <td><?php echo $i++; ?></td>
          <td><?php echo $s['staff_name']; ?></td>
          <td><?php echo $s['shift_start']; ?></td>
          <td style="color: <?php echo $s['color']; ?>; font-weight:700;"><?php echo $s['avg_points']; ?></td>
        </tr>
      <?php } ?>
    </tbody>
  </table>
<?php return; } ?>

<?php
// Otherwise show records table.
// Determine if we are in monthly view for a specific employee
$monthly_for_employee = (isset($view) && $view=='monthly' && !empty($staff_selected));
if ($monthly_for_employee) {
  // show daily rows for the specific employee (date present)
  ?>
  <table class="table table-hover">
    <thead>
      <tr>
        <th>#</th>
        <th><?php echo _l('date'); ?></th>
        <th><?php echo _l('check_in'); ?></th>
        <th><?php echo _l('shift_start'); ?></th>
        <th><?php echo _l('points'); ?></th>
      </tr>
    </thead>
    <tbody>
      <?php $i=1; foreach ($records as $r) { ?>
        <tr>
          <td><?php echo $i++; ?></td>
          <td><?php echo _d($r['date']); ?></td>
          <td style="<?php echo ($r['color']!='gray') ? 'color: '.$r['color'].'; font-weight:600;' : 'color:#666;'; ?>"><?php echo $r['check_in'] ? $r['check_in'] : '-'; ?></td>
          <td><?php echo $r['shift_start']; ?></td>
          <td style="color: <?php echo ($r['color']!='gray') ? $r['color'] : '#666'; ?>; font-weight:700;"><?php echo ($r['color']!='gray') ? $r['points'] : '-'; ?></td>
        </tr>
      <?php } ?>
    </tbody>
  </table>
  <?php
  return;
}

// Default daily (one row per employee for date range) show columns: Employee, Date, Check-in, Shift, Points
?>
<table class="table table-hover">
  <thead>
    <tr>
      <th>#</th>
      <th><?php echo _l('staff'); ?></th>
      <th><?php echo _l('date'); ?></th>
      <th><?php echo _l('check_in'); ?></th>
      <th><?php echo _l('shift_start'); ?></th>
      <th><?php echo _l('points'); ?></th>
    </tr>
  </thead>
  <tbody>
    <?php $i=1; foreach ($records as $r) { ?>
      <tr>
        <td><?php echo $i++; ?></td>
        <td><?php echo $r['staff_name']; ?></td>
        <td><?php echo _d($r['date']); ?></td>
        <td style="<?php echo ($r['color']!='gray') ? 'color: '.$r['color'].'; font-weight:600;' : 'color:#666;'; ?>"><?php echo $r['check_in'] ? $r['check_in'] : '-'; ?></td>
        <td><?php echo $r['shift_start']; ?></td>
        <td style="color: <?php echo ($r['color']!='gray') ? $r['color'] : '#666'; ?>; font-weight:700;"><?php echo ($r['color']!='gray') ? $r['points'] : '-'; ?></td>
      </tr>
    <?php } ?>
  </tbody>
</table>
