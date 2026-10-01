<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<script>
    var leadCustomerLinkerConfig = {
        searchUrl: '<?php echo admin_url("lead_customer_linker/search_clients"); ?>',
        convertExistingUrl: '<?php echo admin_url("lead_customer_linker/convert_to_existing"); ?>',
        csrfTokenName: '<?php echo $this->security->get_csrf_token_name(); ?>',
        csrfHash: '<?php echo $this->security->get_csrf_hash(); ?>'
    };
</script>
