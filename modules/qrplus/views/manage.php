<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">

            <div class="col-md-12">
                <h4 class="tw-mt-0 tw-font-semibold tw-text-lg tw-text-neutral-700">
                    <?php echo $title; ?>
                </h4>
                <?php
                if (has_permission('qrplus', '', 'create')) {
                    ?>
                    <div class="panel_s">
                        <div class="panel-body">
                            <div class="horizontal-scrollable-tabs panel-full-width-tabs">
                                <div class="scroller arrow-left" style="display: none;"><i class="fa fa-angle-left"></i></div>
                                <div class="scroller arrow-right" style="display: none;"><i class="fa fa-angle-right"></i></div>
                                <div class="horizontal-tabs">
                                    <ul class="nav nav-tabs nav-tabs-horizontal" role="tablist">
                                        <?php
                                        foreach (qrplus_supported_qr_codes() as $type => $text){
                                            $active = '';
                                            if ($type == 'text') {
                                                $active = 'active';
                                            }
                                            ?>
                                            <li role="presentation" class="<?php echo $active; ?>">
                                                <a href="#<?php echo $type; ?>" aria-controls="<?php echo $type; ?>" role="tab" data-toggle="tab" aria-expanded="true"><?php echo $text; ?></a>
                                            </li>
                                            <?php
                                        }
                                        ?>
                                    </ul>
                                </div>
                            </div>

                            <div class="tab-content mtop15">

                                <div role="tabpanel" class="tab-pane active" id="text">
                                    <?php echo form_open(admin_url('qrplus/handle_qrcode_generator')) ; ?>

                                    <?php echo form_hidden('type', 'text') ?>

                                    <div class="col-md-12">
                                        <?php echo render_input('title', 'qrplus_qr_code_title', '', 'text', ['required'=>'true']) ?>
                                    </div>

                                    <div class="col-md-12">
                                        <?php echo render_textarea('text', 'qrplus_text_title'); ?>
                                    </div>

                                    <div class="col-md-12">
                                        <button type="submit" class="btn btn-primary pull-right"><?php echo _l('qrplus_generate_qr'); ?></button>
                                    </div>

                                    <?php echo form_close(); ?>
                                </div>

                                <div role="tabpanel" class="tab-pane" id="wifi">
                                    <?php echo form_open(admin_url('qrplus/handle_qrcode_generator')) ; ?>

                                    <?php echo form_hidden('type', 'wifi') ?>

                                    <div class="col-md-12">
                                        <?php echo render_input('title', 'qrplus_qr_code_title', '', 'text', ['required'=>'true']) ?>
                                    </div>

                                    <div class="col-md-6">
                                        <?php echo render_input('network_name', 'qrplus_network_name'); ?>
                                    </div>

                                    <div class="col-md-6">
                                        <?php echo render_yes_no_option('is_hidden', 'qrplus_network_hidden'); ?>
                                    </div>

                                    <div class="col-md-12">
                                        <?php echo render_input('network_password', 'qrplus_network_password'); ?>
                                    </div>

                                    <div class="col-md-12">
                                        <?php echo render_select('network_encryption', qrplus_supported_wifi_encryptions(), ['value', 'name'], 'qrplus_network_encryption'); ?>
                                    </div>

                                    <div class="col-md-12">
                                        <button type="submit" class="btn btn-primary pull-right"><?php echo _l('qrplus_generate_qr'); ?></button>
                                    </div>

                                    <?php echo form_close(); ?>
                                </div>

                                <div role="tabpanel" class="tab-pane" id="phone">
                                    <?php echo form_open(admin_url('qrplus/handle_qrcode_generator')) ; ?>

                                    <?php echo form_hidden('type', 'phone') ?>

                                    <div class="col-md-6">
                                        <?php echo render_input('title', 'qrplus_qr_code_title', '', 'text', ['required'=>'true']) ?>
                                    </div>

                                    <div class="col-md-6">
                                        <?php echo render_input('phone_number', 'qrplus_phone_number'); ?>
                                    </div>

                                    <div class="col-md-12">
                                        <button type="submit" class="btn btn-primary pull-right"><?php echo _l('qrplus_generate_qr'); ?></button>
                                    </div>

                                    <?php echo form_close(); ?>
                                </div>

                                <div role="tabpanel" class="tab-pane" id="sms">
                                    <?php echo form_open(admin_url('qrplus/handle_qrcode_generator')) ; ?>

                                    <?php echo form_hidden('type', 'sms') ?>

                                    <div class="col-md-12">
                                        <?php echo render_input('title', 'qrplus_qr_code_title', '', 'text', ['required'=>'true']) ?>
                                    </div>

                                    <div class="col-md-6">
                                        <?php echo render_input('phone_number', 'qrplus_phone_number'); ?>
                                    </div>

                                    <div class="col-md-6">
                                        <?php echo render_input('message', 'qrplus_sms_message'); ?>
                                    </div>

                                    <div class="col-md-12">
                                        <button type="submit" class="btn btn-primary pull-right"><?php echo _l('qrplus_generate_qr'); ?></button>
                                    </div>

                                    <?php echo form_close(); ?>
                                </div>

                                <div role="tabpanel" class="tab-pane" id="event">
                                    <?php echo form_open(admin_url('qrplus/handle_qrcode_generator')) ; ?>

                                    <?php echo form_hidden('type', 'event') ?>

                                    <div class="col-md-12">
                                        <?php echo render_input('title', 'qrplus_qr_code_title', '', 'text', ['required'=>'true']) ?>
                                    </div>

                                    <div class="col-md-12">
                                        <?php echo render_input('event_title', 'qrplus_event_title'); ?>
                                    </div>

                                    <div class="col-md-4">
                                        <?php echo render_input('event_location', 'qrplus_event_location'); ?>
                                    </div>

                                    <div class="col-md-4">
                                        <?php echo render_datetime_input('start_date', 'qrplus_event_start_date'); ?>
                                    </div>

                                    <div class="col-md-4">
                                        <?php echo render_datetime_input('end_date', 'qrplus_event_end_date'); ?>
                                    </div>

                                    <div class="col-md-12">
                                        <button type="submit" class="btn btn-primary pull-right"><?php echo _l('qrplus_generate_qr'); ?></button>
                                    </div>

                                    <?php echo form_close(); ?>
                                </div>

                                <div role="tabpanel" class="tab-pane" id="vcard">
                                    <?php echo form_open(admin_url('qrplus/handle_qrcode_generator')) ; ?>

                                    <?php echo form_hidden('type', 'vcard') ?>

                                    <div class="col-md-12">
                                        <?php echo render_input('title', 'qrplus_qr_code_title', '', 'text', ['required'=>'true']) ?>
                                    </div>

                                    <div class="col-md-4">
                                        <?php echo render_input('first_name', 'qrplus_firstname'); ?>
                                    </div>

                                    <div class="col-md-4">
                                        <?php echo render_input('last_name', 'qrplus_last_name'); ?>
                                    </div>

                                    <div class="col-md-4">
                                        <?php echo render_input('job_title', 'qrplus_title'); ?>
                                    </div>

                                    <div class="col-md-4">
                                        <?php echo render_input('email', 'qrplus_email'); ?>
                                    </div>

                                    <div class="col-md-4">
                                        <?php echo render_input('company', 'qrplus_company'); ?>
                                    </div>

                                    <div class="col-md-4">
                                        <?php echo render_input('website_url', 'qrplus_website_url'); ?>
                                    </div>

                                    <div class="col-md-6">
                                        <?php echo render_input('phone', 'qrplus_phone'); ?>
                                    </div>

                                    <div class="col-md-6">
                                        <?php echo render_input('mobile', 'qrplus_mobile'); ?>
                                    </div>

                                    <div class="col-md-3">
                                        <?php echo render_input('street', 'qrplus_street'); ?>
                                    </div>

                                    <div class="col-md-3">
                                        <?php echo render_input('city', 'qrplus_city'); ?>
                                    </div>

                                    <div class="col-md-3">
                                        <?php echo render_input('postal_code', 'qrplus_postal_code'); ?>
                                    </div>

                                    <div class="col-md-3">
                                        <?php echo render_input('state', 'qrplus_state'); ?>
                                    </div>

                                    <div class="col-md-12">
                                        <button type="submit" class="btn btn-primary pull-right"><?php echo _l('qrplus_generate_qr'); ?></button>
                                    </div>

                                    <?php echo form_close(); ?>
                                </div>

                                <div role="tabpanel" class="tab-pane" id="location">
                                    <?php echo form_open(admin_url('qrplus/handle_qrcode_generator')) ; ?>

                                    <?php echo form_hidden('type', 'location') ?>

                                    <div class="col-md-12">
                                        <?php echo render_input('title', 'qrplus_qr_code_title', '', 'text', ['required'=>'true']) ?>
                                    </div>

                                    <div class="col-md-6">
                                        <?php echo render_input('latitude', 'qrplus_latitude'); ?>
                                    </div>

                                    <div class="col-md-6">
                                        <?php echo render_input('longitude', 'qrplus_longitude'); ?>
                                    </div>

                                    <div class="col-md-12">
                                        <button type="submit" class="btn btn-primary pull-right"><?php echo _l('qrplus_generate_qr'); ?></button>
                                    </div>

                                    <?php echo form_close(); ?>
                                </div>

                                <div role="tabpanel" class="tab-pane" id="url">
                                    <?php echo form_open(admin_url('qrplus/handle_qrcode_generator')) ; ?>

                                    <?php echo form_hidden('type', 'url') ?>

                                    <div class="col-md-12">
                                        <?php echo render_input('title', 'qrplus_qr_code_title', '', 'text', ['required'=>'true']) ?>
                                    </div>

                                    <div class="col-md-12">
                                        <?php echo render_input('url', 'qrplus_generate_url') ?>
                                    </div>

                                    <div class="col-md-12">
                                        <button type="submit" class="btn btn-primary pull-right"><?php echo _l('qrplus_generate_qr'); ?></button>
                                    </div>

                                    <?php echo form_close(); ?>
                                </div>

                                <div role="tabpanel" class="tab-pane" id="mailto">
                                    <?php echo form_open(admin_url('qrplus/handle_qrcode_generator')) ; ?>

                                    <div class="col-md-12">
                                        <?php echo render_input('title', 'qrplus_qr_code_title', '', 'text', ['required'=>'true']) ?>
                                    </div>

                                    <?php echo form_hidden('type', 'mailto') ?>

                                    <div class="col-md-12">
                                        <?php echo render_input('email', 'qrplus_email') ?>
                                    </div>

                                    <div class="col-md-12">
                                        <button type="submit" class="btn btn-primary pull-right"><?php echo _l('qrplus_generate_qr'); ?></button>
                                    </div>

                                    <?php echo form_close(); ?>
                                </div>

                                <div role="tabpanel" class="tab-pane" id="mail_message">
                                    <?php echo form_open(admin_url('qrplus/handle_qrcode_generator')) ; ?>

                                    <?php echo form_hidden('type', 'mail_message') ?>

                                    <div class="col-md-12">
                                        <?php echo render_input('title', 'qrplus_qr_code_title', '', 'text', ['required'=>'true']) ?>
                                    </div>

                                    <div class="col-md-6">
                                        <?php echo render_input('email', 'qrplus_email'); ?>
                                    </div>

                                    <div class="col-md-6">
                                        <?php echo render_input('email_subject', 'qrplus_email_subject'); ?>
                                    </div>

                                    <div class="col-md-12">
                                        <?php echo render_textarea('email_content', 'qrplus_email_content'); ?>
                                    </div>

                                    <div class="col-md-12">
                                        <button type="submit" class="btn btn-primary pull-right"><?php echo _l('qrplus_generate_qr'); ?></button>
                                    </div>

                                    <?php echo form_close(); ?>
                                </div>

                                <div role="tabpanel" class="tab-pane" id="google_maps">
                                    <?php echo form_open(admin_url('qrplus/handle_qrcode_generator')) ; ?>

                                    <?php echo form_hidden('type', 'google_maps') ?>

                                    <div class="col-md-12">
                                        <?php echo render_input('title', 'qrplus_qr_code_title', '', 'text', ['required'=>'true']) ?>
                                    </div>

                                    <div class="col-md-6">
                                        <?php echo render_input('latitude', 'qrplus_latitude'); ?>
                                    </div>

                                    <div class="col-md-6">
                                        <?php echo render_input('longitude', 'qrplus_longitude'); ?>
                                    </div>

                                    <div class="col-md-12">
                                        <button type="submit" class="btn btn-primary pull-right"><?php echo _l('qrplus_generate_qr'); ?></button>
                                    </div>

                                    <?php echo form_close(); ?>
                                </div>

                                <div role="tabpanel" class="tab-pane" id="viber_chat">
                                    <?php echo form_open(admin_url('qrplus/handle_qrcode_generator')) ; ?>

                                    <?php echo form_hidden('type', 'viber_chat') ?>

                                    <div class="col-md-6">
                                        <?php echo render_input('title', 'qrplus_qr_code_title', '', 'text', ['required'=>'true']) ?>
                                    </div>

                                    <div class="col-md-6">
                                        <?php echo render_input('phone_number', 'qrplus_phone_number'); ?>
                                    </div>

                                    <div class="col-md-12">
                                        <button type="submit" class="btn btn-primary pull-right"><?php echo _l('qrplus_generate_qr'); ?></button>
                                    </div>

                                    <?php echo form_close(); ?>
                                </div>

                                <div role="tabpanel" class="tab-pane" id="whatsapp_chat">
                                    <?php echo form_open(admin_url('qrplus/handle_qrcode_generator')) ; ?>

                                    <?php echo form_hidden('type', 'whatsapp_chat') ?>

                                    <div class="col-md-6">
                                        <?php echo render_input('title', 'qrplus_qr_code_title', '', 'text', ['required'=>'true']) ?>
                                    </div>

                                    <div class="col-md-6">
                                        <?php echo render_input('phone_number', 'qrplus_phone_number'); ?>
                                    </div>

                                    <div class="col-md-12">
                                        <button type="submit" class="btn btn-primary pull-right"><?php echo _l('qrplus_generate_qr'); ?></button>
                                    </div>

                                    <?php echo form_close(); ?>
                                </div>

                            </div>

                        </div>
                    </div>
                    <?php
                }
                ?>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <h4 class="tw-mt-0 tw-font-semibold tw-text-lg tw-text-neutral-700">
                    <?php echo _l('qrplus_generated_qr_codes'); ?>
                </h4>
                <div class="panel_s">
                    <div class="col-md-12 panel-body">

                        <div class="panel-body panel-table-full">
                            <?php render_datatable([
                                _l('title'),
                                _l('created_at'),
                                _l('options'),
                            ], 'qrplus-codes'); ?>
                        </div>

                    </div>
                </div>
            </div>
        </div>

        <div class="btn-bottom-pusher"></div>
    </div>
</div>
<?php init_tail(); ?>
<script>
    $(function() {
        initDataTable('.table-qrplus-codes', window.location.href, [1], [1], [], [1, 'desc']);
    });
</script>
</body>
</html>
