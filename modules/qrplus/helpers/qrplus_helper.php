<?php

function qrplus_supported_qr_codes()
{
    return [
        'text' => _l('qrplus_generate_text'),
        'wifi' => _l('qrplus_generate_wifi'),
        'phone' => _l('qrplus_generate_tel'),
        'sms' => _l('qrplus_generate_sms'),
        'event' => _l('qrplus_generate_event'),
        'vcard' => _l('qrplus_generate_vcard'),
        'location' => _l('qrplus_generate_location'),
        'url' => _l('qrplus_generate_url'),
        'mailto' => _l('qrplus_generate_mailto'),
        'mail_message' => _l('qrplus_generate_mailmessage'),
        'google_maps' => _l('qrplus_generate_google_maps'),
        'viber_chat' => _l('qrplus_generate_viber_chat'),
        'whatsapp_chat' => _l('qrplus_generate_whatsapp_chat')
    ];
}

function qrplus_supported_wifi_encryptions()
{

    return [
        [
            'value' => 'nopass',
            'name' => 'None'
        ],
        [
            'value' => 'WPA',
            'name' => 'WPA'
        ],
        [
            'value' => 'WEP',
            'name' => 'WEP'
        ]
    ];
}