<?php

defined('BASEPATH') or exit('No direct script access allowed');

use chillerlan\QRCode\QRCode;

class Qrplus extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('qrplus_model');
    }

    public function index()
    {

        if (!has_permission('qrplus', '', 'view')) {
            access_denied('qrplus');
        }

        if ($this->input->is_ajax_request()) {
            $this->app->get_table_data(module_views_path('qrplus', 'table'));
        }

        $data['title'] = _l('qrplus');
        $this->load->view('manage', $data);
    }

    public function view_qrcode($id)
    {
        if (!has_permission('qrplus', '', 'view')) {
            access_denied('qrplus');
        }

        $getQrData = $this->qrplus_model->get($id);

        $data['title'] = _l('qrplus');
        $data['qrData'] = $getQrData;
        $data['generatedQr'] = (new QRCode)->render($getQrData->qr_code_content);

        $this->load->view('view', $data);
    }

    public function handle_qrcode_generator()
    {

        if (!has_permission('qrplus', '', 'create')) {
            access_denied('qrplus');
        }

        if ($this->input->post('type')) {

            $generateType = $this->input->post('type');

            switch ($generateType) {

                case 'text':

                    $title = [
                        'title' => $this->input->post('title')
                    ];
                    $data = [
                      'text' =>  $this->input->post('text')
                    ];
                    $qrCodeContent = $data['text'];

                    $id = $this->qrplus_model->add($title + [
                        'user_id' => get_staff_user_id(),
                        'data' => json_encode($data),
                        'qr_code_content' => $qrCodeContent,
                        'created_at' => date('Y-m-d H:i:s')
                        ]);

                    if ($id) {
                        set_alert('success', _l('qrplus_qr_generated_successfully'));
                        redirect(admin_url('qrplus/view_qrcode/' . $id));
                    }
                    redirect(admin_url('qrplus'));
                    die;

                    break;
                case 'wifi':

                    $title = [
                        'title' => $this->input->post('title')
                    ];
                    $data = [
                        'network_name' => $this->input->post('network_name'),
                        'is_hidden' => $this->input->post('is_hidden'),
                        'network_password' => $this->input->post('network_password'),
                        'network_encryption' => $this->input->post('network_encryption')
                    ];

                    $qrCodeContent = 'WIFI:T:'.$data['network_encryption'].';S:'.$data['network_name'].';P:'.$data['network_password'].';H:false;';

                    $id = $this->qrplus_model->add($title + [
                            'user_id' => get_staff_user_id(),
                            'data' => json_encode($data),
                            'qr_code_content' => $qrCodeContent,
                            'created_at' => date('Y-m-d H:i:s')
                        ]);

                    if ($id) {
                        set_alert('success', _l('qrplus_qr_generated_successfully'));
                        redirect(admin_url('qrplus/view_qrcode/' . $id));
                    }
                    redirect(admin_url('qrplus'));
                    die;

                    break;
                case 'phone':

                    $title = [
                        'title' => $this->input->post('title')
                    ];
                    $data = [
                        'phone_number' => $this->input->post('phone_number')
                    ];
                    $qrCodeContent = 'TEL:'.$data['phone_number'];

                    $id = $this->qrplus_model->add($title + [
                            'user_id' => get_staff_user_id(),
                            'data' => json_encode($data),
                            'qr_code_content' => $qrCodeContent,
                            'created_at' => date('Y-m-d H:i:s')
                        ]);

                    if ($id) {
                        set_alert('success', _l('qrplus_qr_generated_successfully'));
                        redirect(admin_url('qrplus/view_qrcode/' . $id));
                    }
                    redirect(admin_url('qrplus'));
                    die;

                    break;
                case 'sms':

                    $title = [
                        'title' => $this->input->post('title')
                    ];
                    $data = [
                        'phone_number' => $this->input->post('phone_number'),
                        'message' => $this->input->post('message')
                    ];
                    $qrCodeContent = 'SMSTO:'.$data['phone_number'].':' . $data['message'];

                    $id = $this->qrplus_model->add($title + [
                            'user_id' => get_staff_user_id(),
                            'data' => json_encode($data),
                            'qr_code_content' => $qrCodeContent,
                            'created_at' => date('Y-m-d H:i:s')
                        ]);

                    if ($id) {
                        set_alert('success', _l('qrplus_qr_generated_successfully'));
                        redirect(admin_url('qrplus/view_qrcode/' . $id));
                    }
                    redirect(admin_url('qrplus'));
                    die;

                    break;
                case 'event':

                    $title = [
                        'title' => $this->input->post('title')
                    ];
                    $data = [
                        'event_title' => $this->input->post('event_title'),
                        'event_location' => $this->input->post('event_location'),
                        'start_date' => $this->input->post('start_date'),
                        'end_date' => $this->input->post('end_date')
                    ];
                    $qrCodeContent = "BEGIN:VEVENT\n";
                    $qrCodeContent .= "SUMMARY:".$data['event_title']."\n";
                    $qrCodeContent .= "DTSTART:".$data['start_date']."\n";
                    $qrCodeContent .= "DTEND:".$data['end_date']."\n";
                    $qrCodeContent .= "END:VEVENT\n";

                    $id = $this->qrplus_model->add($title + [
                            'user_id' => get_staff_user_id(),
                            'data' => json_encode($data),
                            'qr_code_content' => $qrCodeContent,
                            'created_at' => date('Y-m-d H:i:s')
                        ]);

                    if ($id) {
                        set_alert('success', _l('qrplus_qr_generated_successfully'));
                        redirect(admin_url('qrplus/view_qrcode/' . $id));
                    }
                    redirect(admin_url('qrplus'));
                    die;

                    break;
                case 'vcard':

                    $title = [
                        'title' => $this->input->post('title'),
                    ];
                    $data = [
                        'first_name' => $this->input->post('first_name'),
                        'last_name' => $this->input->post('last_name'),
                        'job_title' => $this->input->post('job_title'),
                        'email' => $this->input->post('email'),
                        'company' => $this->input->post('company'),
                        'website_url' => $this->input->post('website_url'),
                        'phone' => $this->input->post('phone'),
                        'mobile' => $this->input->post('mobile'),
                        'street' => $this->input->post('street'),
                        'city' => $this->input->post('city'),
                        'postal_code' => $this->input->post('postal_code'),
                        'state' => $this->input->post('state')
                    ];
                    $qrCodeContent = "BEGIN:VCARD\n";
                    $qrCodeContent .= "VERSION:3.0\n";
                    $qrCodeContent .= "N:".$data['first_name'].";".$data['first_name']."\n";
                    $qrCodeContent .= "FN:".$data['first_name']." ".$data['last_name']."\n";
                    $qrCodeContent .= "TITLE:".$data['job_title']."\n";
                    $qrCodeContent .= "ORG:".$data['company']."\n";
                    $qrCodeContent .= "URL:".$data['website_url']."\n";
                    $qrCodeContent .= "EMAIL;TYPE=INTERNET:".$data['email']."\n";
                    $qrCodeContent .= "TEL;TYPE=voice,work,pref:".$data['phone']."\n";
                    $qrCodeContent .= "TEL;TYPE=voice,cell,pref:".$data['mobile']."\n";
                    $qrCodeContent .= "ADR:;;".$data['street'].", ".$data['city'].", ".$data['postal_code']." ".$data['state']."\n";
                    $qrCodeContent .= "END:VCARD\n";

                    $id = $this->qrplus_model->add($title + [
                            'user_id' => get_staff_user_id(),
                            'data' => json_encode($data),
                            'qr_code_content' => $qrCodeContent,
                            'created_at' => date('Y-m-d H:i:s')
                        ]);

                    if ($id) {
                        set_alert('success', _l('qrplus_qr_generated_successfully'));
                        redirect(admin_url('qrplus/view_qrcode/' . $id));
                    }
                    redirect(admin_url('qrplus'));
                    die;

                    break;
                case 'location':

                    $title = [
                        'title' => $this->input->post('title')
                    ];
                    $data = [
                        'latitude' => $this->input->post('latitude'),
                        'longitude' => $this->input->post('longitude')
                    ];
                    $qrCodeContent = 'geo:'.$data['latitude'].','.$data['longitude'].'';

                    $id = $this->qrplus_model->add($title + [
                            'user_id' => get_staff_user_id(),
                            'data' => json_encode($data),
                            'qr_code_content' => $qrCodeContent,
                            'created_at' => date('Y-m-d H:i:s')
                        ]);

                    if ($id) {
                        set_alert('success', _l('qrplus_qr_generated_successfully'));
                        redirect(admin_url('qrplus/view_qrcode/' . $id));
                    }
                    redirect(admin_url('qrplus'));
                    die;

                    break;
                case 'url':

                    $title = [
                        'title' => $this->input->post('title')
                    ];
                    $data = [
                        'url' => $this->input->post('url')
                    ];
                    $qrCodeContent = $data['url'];

                    $id = $this->qrplus_model->add($title + [
                            'user_id' => get_staff_user_id(),
                            'data' => json_encode($data),
                            'qr_code_content' => $qrCodeContent,
                            'created_at' => date('Y-m-d H:i:s')
                        ]);

                    if ($id) {
                        set_alert('success', _l('qrplus_qr_generated_successfully'));
                        redirect(admin_url('qrplus/view_qrcode/' . $id));
                    }
                    redirect(admin_url('qrplus'));
                    die;

                    break;
                case 'mailto':

                    $title = [
                        'title' => $this->input->post('title')
                    ];
                    $data = [
                        'email' => $this->input->post('email')
                    ];
                    $qrCodeContent = 'MAILTO:'.$data['email'];

                    $id = $this->qrplus_model->add($title + [
                            'user_id' => get_staff_user_id(),
                            'data' => json_encode($data),
                            'qr_code_content' => $qrCodeContent,
                            'created_at' => date('Y-m-d H:i:s')
                        ]);

                    if ($id) {
                        set_alert('success', _l('qrplus_qr_generated_successfully'));
                        redirect(admin_url('qrplus/view_qrcode/' . $id));
                    }
                    redirect(admin_url('qrplus'));
                    die;

                    break;
                case 'mail_message':

                    $title = [
                        'title' => $this->input->post('title')
                    ];
                    $data = [
                        'email' => $this->input->post('email'),
                        'email_subject' => $this->input->post('email_subject'),
                        'email_content' => $this->input->post('email_content'),
                    ];
                    $qrCodeContent = 'MATMSG:TO:'.$data['email'].';SUB:' . $data['email_subject'] . ';BODY:'. $data['email_content'] .';';

                    $id = $this->qrplus_model->add($title + [
                            'user_id' => get_staff_user_id(),
                            'data' => json_encode($data),
                            'qr_code_content' => $qrCodeContent,
                            'created_at' => date('Y-m-d H:i:s')
                        ]);

                    if ($id) {
                        set_alert('success', _l('qrplus_qr_generated_successfully'));
                        redirect(admin_url('qrplus/view_qrcode/' . $id));
                    }
                    redirect(admin_url('qrplus'));
                    die;

                    break;
                case 'google_maps':

                    $title = [
                        'title' => $this->input->post('title')
                    ];
                    $data = [
                        'latitude' => $this->input->post('latitude'),
                        'longitude' => $this->input->post('longitude')
                    ];
                    $qrCodeContent = 'https://maps.google.com/maps?q='.$data['latitude'].','.$data['longitude'];

                    $id = $this->qrplus_model->add($title + [
                            'user_id' => get_staff_user_id(),
                            'data' => json_encode($data),
                            'qr_code_content' => $qrCodeContent,
                            'created_at' => date('Y-m-d H:i:s')
                        ]);

                    if ($id) {
                        set_alert('success', _l('qrplus_qr_generated_successfully'));
                        redirect(admin_url('qrplus/view_qrcode/' . $id));
                    }
                    redirect(admin_url('qrplus'));
                    die;

                    break;
                case 'viber_chat':

                    $title = [
                        'title' => $this->input->post('title')
                    ];
                    $data = [
                        'phone_number' => $this->input->post('phone_number')
                    ];
                    $qrCodeContent = 'viber://chat?number='.$data['phone_number'];

                    $id = $this->qrplus_model->add($title + [
                            'user_id' => get_staff_user_id(),
                            'data' => json_encode($data),
                            'qr_code_content' => $qrCodeContent,
                            'created_at' => date('Y-m-d H:i:s')
                        ]);

                    if ($id) {
                        set_alert('success', _l('qrplus_qr_generated_successfully'));
                        redirect(admin_url('qrplus/view_qrcode/' . $id));
                    }
                    redirect(admin_url('qrplus'));
                    die;

                    break;
                case 'whatsapp_chat':

                    $title = [
                        'title' => $this->input->post('title')
                    ];
                    $data = [
                        'phone_number' => $this->input->post('phone_number')
                    ];
                    $qrCodeContent = 'https://wa.me/'. $data['phone_number'];

                    $id = $this->qrplus_model->add($title + [
                            'user_id' => get_staff_user_id(),
                            'data' => json_encode($data),
                            'qr_code_content' => $qrCodeContent,
                            'created_at' => date('Y-m-d H:i:s')
                        ]);

                    if ($id) {
                        set_alert('success', _l('qrplus_qr_generated_successfully'));
                        redirect(admin_url('qrplus/view_qrcode/' . $id));
                    }
                    redirect(admin_url('qrplus'));
                    die;

                    break;
            }

        }

    }

    public function delete_qrcode($id)
    {
        if (!has_permission('qrplus', '', 'delete')) {
            access_denied('qrplus');
        }

        if (!$id) {
            redirect(admin_url('qrplus'));
        }

        $response = $this->qrplus_model->delete($id);

        if ($response == true) {
            set_alert('success', _l('deleted', _l('qrplus')));
        } else {
            set_alert('warning', _l('problem_deleting', _l('qrplus')));
        }

        redirect(admin_url('qrplus'));
    }

}
