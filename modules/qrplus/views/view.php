<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="text-center">

                <h4 class="tw-mt-0 tw-font-semibold tw-text-lg tw-text-neutral-700">
                    <?php echo $qrData->title ?: ''; ?>
                </h4>

                <div class="panel_s text-center">
                    <div class="panel-body panel-table-full">
                        <img src="<?php echo $generatedQr; ?>" alt="QR Code" />
                        <br>
                        <a href="<?php echo $generatedQr; ?>" class="btn btn-primary" download><?php echo _l('download'); ?></a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php init_tail(); ?>
</body>

</html>