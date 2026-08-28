<?php

defined('BASEPATH') or exit('No direct script access allowed');

if (!function_exists('get_boq_item_description')) {
    function get_boq_item_description($boq_item_id)
    {
        $CI = &get_instance();
        if (!isset($CI->project_boq_model)) {
            $CI->load->model('boq/Boq_model', 'project_boq_model');
        }

        $boq = $CI->project_boq_model->get((int) $boq_item_id);

        return $boq ? $boq->boq_description : '';
    }
}
