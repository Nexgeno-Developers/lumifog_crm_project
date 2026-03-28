<?php

defined('BASEPATH') or exit('No direct script access allowed');

include_once(APPPATH . 'libraries/pdf/App_pdf.php');

class Sale_invoice_pdf extends App_pdf
{
    protected $sale_invoice;

    public function __construct($sale_invoice)
    {
        $sale_invoice                = hooks()->apply_filters('request_html_pdf_data', $sale_invoice);


        parent::__construct();

        $this->sale_invoice = $sale_invoice;

        $this->SetTitle(_l('sale_invoice'));
        # Don't remove these lines - important for the PDF layout
        $this->sale_invoice = $this->fix_editor_html($this->sale_invoice);
    }

    public function prepare()
    {
        $this->set_view_vars('sale_invoice', $this->sale_invoice);

        return $this->build();
    }

    protected function type()
    {
        return 'agent_sale_invoice';
    }

    protected function file_path()
    {
        $customPath = APPPATH . 'views/themes/' . active_clients_theme() . '/views/my_requestpdf.php';
        $actualPath = APP_MODULES_PATH . '/sales_agent/views/portal/sales_invoices/sales_invoicepdf.php';

        if (file_exists($customPath)) {
            $actualPath = $customPath;
        }

        return $actualPath;
    }
}