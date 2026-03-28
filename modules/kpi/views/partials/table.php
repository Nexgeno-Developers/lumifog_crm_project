<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<table class="table table-hover">
  <thead>
    <tr>
      <th><?php echo _l('staff'); ?></th>
      <th><?php echo _l('date'); ?></th>
      <th><?php echo _l('check_in'); ?></th>
      <th><?php echo _l('shift_start'); ?></th>
      <th><?php echo _l('status'); ?></th>
      <th><?php echo _l('points'); ?></th>
    </tr>
  </thead>
  <tbody>
    <?php if(empty($records)) { ?>
      <tr><td colspan="6" class="text-center text-muted"><?php echo _l('no_records_found'); ?></td></tr>
    <?php } else { foreach($records as $r) { ?>
      <tr>
        <td><?php echo $r['staff_name']; ?></td>
        <td><?php echo _d($r['date']); ?></td>
        <td style="color: <?php echo $r['color']; ?>; font-weight:600;"><?php echo $r['check_in']; ?></td>
        <td><?php echo $r['shift_start']; ?></td>
        <td><?php echo ucfirst($r['color']); ?></td>
        <td><?php echo $r['points']; ?></td>
      </tr>
    <?php } } ?>
  </tbody>
</table>
