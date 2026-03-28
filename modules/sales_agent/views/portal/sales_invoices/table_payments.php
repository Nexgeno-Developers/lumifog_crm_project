<?php

defined('BASEPATH') or exit('No direct script access allowed');

$aColumns = [
    db_prefix().'sa_sale_invoice_payment.id',
    'sale_invoice',
    'paymentmode',
    'transactionid',
    db_prefix().'sa_sale_invoices.clientid', 
    'amount', 
    db_prefix().'sa_sale_invoice_payment.date',
    'note',
    ];
$sIndexColumn = 'id';
$sTable       = db_prefix().'sa_sale_invoice_payment';
$join         = [ 
    'LEFT JOIN '.db_prefix().'sa_sale_invoices ON '.db_prefix().'sa_sale_invoices.id = '.db_prefix().'sa_sale_invoice_payment.sale_invoice',
    'LEFT JOIN ' . db_prefix() . 'payment_modes ON ' . db_prefix() . 'payment_modes.id = ' . db_prefix() . 'sa_sale_invoice_payment.paymentmode',
    'LEFT JOIN ' . db_prefix() . 'sa_clients ON ' . db_prefix() . 'sa_clients.id = ' . db_prefix() . 'sa_sale_invoices.clientid',
];

$where = [];

$agent_id = get_sale_agent_user_id();

array_push($where, 'AND '.db_prefix().'sa_sale_invoices.agent_id = '.$agent_id);


$result = data_tables_init($aColumns, $sIndexColumn, $sTable, $join, $where, ['currency', 'inv_number', db_prefix() . 'payment_modes.name as payment_mode_name', db_prefix().'sa_clients.name' ]);

$output  = $result['output'];
$rResult = $result['rResult'];

foreach ($rResult as $aRow) {
    $row = [];

    for ($i = 0; $i < count($aColumns); $i++) {

        $_data = $aRow[$aColumns[$i]];

        $base_currency = get_base_currency();
        if($aRow['currency'] != 0){
            $base_currency = sa_get_currency_by_id($aRow['currency']);
        }

        if($aColumns[$i] == 'sale_invoice'){
            $_data = '<a href="'.site_url('sales_agent/portal/sale_invoice_detail/'.$aRow['sale_invoice']).'">'.$aRow['inv_number'].'</a>';
        }else if($aColumns[$i] == 'paymentmode'){
            $_data = $aRow['payment_mode_name'];
        }else if($aColumns[$i] == db_prefix().'sa_sale_invoices.clientid'){
            $_data = $aRow['name'];
        }else if($aColumns[$i] == 'amount'){
            $_data = app_format_money($aRow['amount'], $base_currency);
        }else if($aColumns[$i] == 'note'){
            $option = '';

            $option .= '<div class="btn-group mright5">
                            <a href="#" class="btn btn-default dropdown-toggle" data-toggle="dropdown"
                                aria-haspopup="true" aria-expanded="false" ><i class="fa-regular fa-file-pdf"></i><span class="caret"></span></a>
                            <ul class="dropdown-menu dropdown-menu-right">
                                <li class="hidden-xs"><a
                                        href="'.site_url('sales_agent/portal/sale_invoice_payment_pdf/' . $aRow[db_prefix().'sa_sale_invoice_payment.id'] . '?output_type=I').'">'. _l('view_pdf').'</a>
                                </li>
                                <li class="hidden-xs"><a
                                        href="'.site_url('sales_agent/portal/sale_invoice_payment_pdf/' . $aRow[db_prefix().'sa_sale_invoice_payment.id'] . '?output_type=I').'"
                                        target="_blank">'. _l('view_pdf_in_new_window').'</a></li>
                                <li><a
                                        href="'.site_url('sales_agent/portal/sale_invoice_payment_pdf/' . $aRow[db_prefix().'sa_sale_invoice_payment.id']).'">'._l('download').'</a>
                                </li>
                                <li>
                                    <a href="'.site_url('sales_agent/portal/sale_invoice_payment_pdf/' . $aRow[db_prefix().'sa_sale_invoice_payment.id'] . '?print=true').'"
                                        target="_blank">
                                       '. _l('print').'
                                    </a>
                                </li>
                            </ul>
                        </div>';

            $option .= '<a href="'. site_url('sales_agent/portal/delete_payment_sale_invoice/'.$aRow[db_prefix().'sa_sale_invoice_payment.id'].'/'.$aRow['sale_invoice']).'" class="btn btn-danger btn-icon _delete" data-toggle="tooltip" data-placement="top" title="'._l('delete').'" ><i class="fa fa-remove"></i></a>';


            $_data = $option;
        }
        
        $row[] = $_data;
    }
    $output['aaData'][] = $row;

}
