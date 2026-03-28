<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div id="history_check_in_out" class="hide reports_fr">
   <table class="table table-history_check_in_out_report scroll-responsive">
    <thead>
      <tr>
         <th><?php echo _l('staffid'); ?></th>
         <th><?php echo _l('time'); ?></th>
         <th ><?php echo _l('type'); ?></th>
         <th><?php echo _l('shift'); ?></th>
         <th><?php echo _l('workplace'); ?></th>
         <th><?php echo _l('route'); ?></th>
         <th><?php echo _l('Location'); ?></th> <!-- zaid -->
      </tr>
   </thead>
   <tbody></tbody>
   <tfoot>
      <td></td>
      <td></td>
      <td></td>
      <td></td>
      <td></td>
      <td></td>
      <td></td> <!-- zaid -->
   </tfoot>
</table>
</div>

<!-- Modal Structure -->
<div id="mapModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.8); justify-content:center; align-items:center;">
    <div style="position:relative; width:80%; max-width:600px; background:#fff; padding:20px; border-radius:8px;">
        <button onclick="closeModal()" style="position:absolute; top:10px; right:10px;">Close</button>
        <iframe id="mapIframe" width="100%" height="400px" style="border:0;" allowfullscreen></iframe>
    </div>
</div>
<!-- zaid -->
<script>
    function view_map(latitude, longitude) {
        // Build the Google Maps Embed API URL
        const mapUrl = `https://www.google.com/maps?q=${latitude},${longitude}&hl=es;z=14&output=embed`;
        
        // Set the iframe's source to the map URL
        document.getElementById('mapIframe').src = mapUrl;
    
        // Show the modal
        document.getElementById('mapModal').style.display = 'flex';
    }
    
    function closeModal() {
        // Hide the modal
        document.getElementById('mapModal').style.display = 'none';
        
        // Clear the iframe source to stop the map from loading when the modal is closed
        document.getElementById('mapIframe').src = '';
    }
</script>
<!-- zaid -->