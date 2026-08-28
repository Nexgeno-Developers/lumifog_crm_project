<?php

defined('BASEPATH') or exit('No direct script access allowed');

// Admin routes for KPI Management module (Rules controller)
$route['kpi_management/rules']              = 'rules/index';
$route['kpi_management/rules/table']        = 'rules/table';
$route['kpi_management/rules/get/(:num)']   = 'rules/get/$1';
$route['kpi_management/rules/save']         = 'rules/save';
$route['kpi_management/rules/delete/(:num)'] = 'rules/delete/$1';

