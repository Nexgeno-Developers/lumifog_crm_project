<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Required app theme head hook
 */
hooks()->add_action('app_customers_head', 'app_theme_head_hook');

/**
 * Default theme menu items
 * In most cases you will want to add this hook because of all the features
 */
hooks()->add_action('clients_init', 'add_default_theme_menu_items');

// Render proposal logo on every PDF page, including automatic page breaks.
hooks()->add_action('pdf_header', 'lumifog_proposal_pdf_header_logo');
// Render invoice header with logo, tax-invoice, and company details on every page
hooks()->add_action('pdf_header', 'lumifog_invoice_pdf_header');
// Render estimate header with same layout as invoice (Pro-Tax-Invoice + estimate number)
hooks()->add_action('pdf_header', 'lumifog_estimate_pdf_header');
// Render proposal footer (same as purchase order).
hooks()->add_action('pdf_footer', 'lumifog_proposal_pdf_footer');
// Render invoice footer with bank details on every page
hooks()->add_action('pdf_footer', 'lumifog_invoice_pdf_footer');
// Render estimate footer mirroring invoice footer logic
hooks()->add_action('pdf_footer', 'lumifog_estimate_pdf_footer');
// Disable default signature processing for invoices (signature is in footer)
hooks()->add_filter('process_pdf_signature_on_close', 'lumifog_disable_invoice_signature');

register_theme_assets_hook('theme_assets');

function lumifog_proposal_pdf_header_logo($data)
{
    if (empty($data['pdf_instance']) || ($data['type'] ?? '') !== 'proposal') {
        return;
    }

    $pdf = $data['pdf_instance'];
    static $marginsAdjusted = [];
    $pdfId = spl_object_id($pdf);
    $margins = $pdf->getMargins();
    $pageWidth = $pdf->getPageWidth();
    $contentWidth = $pageWidth - $margins['left'] - $margins['right'];

    if (!isset($marginsAdjusted[$pdfId])) {
        // Leave extra space for the fixed logo before content starts.
        $topMargin = $margins['top'] + 20;
        $leftMargin = $margins['left'] + 20;
        $pdf->SetMargins($leftMargin, $topMargin, $margins['right']);
        $marginsAdjusted[$pdfId] = true;
    }

    $pdf_logo_url = '
    <div style="background-color:#ffffff; padding:5px; padding-top:15px;">
        <img src="' . base_url('uploads/company/lumifog-logo.webp') . '" height="95">
    </div>';

    // Add margin above the logo by adjusting Y position
    $logoTopMargin = 5; // 10mm margin above logo
    $pdf->SetY($margins['header'] + $logoTopMargin);
    $pdf->SetX($margins['left']);
    $pdf->writeHTMLCell(
        $contentWidth,
        '',
        '',
        '',
        $pdf_logo_url,
        0,
        1,
        false,
        true,
        'L',
        true
    );
    lumifog_proposal_pdf_watermark($pdf);

}

function lumifog_invoice_pdf_header($data)
{
    if (empty($data['pdf_instance']) || ($data['type'] ?? '') !== 'invoice') {
        return;
    }

    $pdf = $data['pdf_instance'];
    
    // Get invoice data from global variable
    if (!isset($GLOBALS['invoice_pdf'])) {
        return;
    }
    
    $invoice = $GLOBALS['invoice_pdf'];
    
    // Get custom field values for IRN and E-invoice Number
    $irn = trim((string) get_custom_field_value($invoice->id, 'invoice_irn_code', 'invoice'));
    $e_invoice_number = trim((string) get_custom_field_value($invoice->id, 'invoice_e_invoice_no', 'invoice'));
    
    // Get invoice type from global variable
    $invoice_type = isset($GLOBALS['invoice_pdf_type']) ? $GLOBALS['invoice_pdf_type'] : 'tax';
    
    // Determine invoice heading based on invoice_type
    $invoice_heading = 'TAX-Invoice'; // Default
    if ($invoice_type === 'pro_tax') {
        $invoice_heading = 'Pro-Tax-Invoice';
    }
    
    static $marginsAdjusted = [];
    $pdfId = spl_object_id($pdf);
    $margins = $pdf->getMargins();
    $pageWidth = $pdf->getPageWidth();
    $contentWidth = $pageWidth - $margins['left'] - $margins['right'];
    
    // Border style for tables
    $border_style = 'border:1px solid #000000;';
    
    // Don't adjust margins initially - header will be written at top, content starts after
    if (!isset($marginsAdjusted[$pdfId])) {
        // Keep original margins - we'll adjust after header is written
        $marginsAdjusted[$pdfId] = true;
    }
    
    // Start at top of page
    $pdf->SetY(5);
    $pdf->SetX($margins['left']);
    
    // Combined header with logo, tax-invoice, and company details table
    $header_html = '
    <div style="background-color:#ffffff; margin:0; padding:0; margin-bottom:0 !important;">
        <!-- Logo and Tax-Invoice Row -->
        <table width="100%" cellspacing="0" cellpadding="0" border="0" style="margin-bottom:5px;">
            <tr>
                <td width="30%" valign="middle" style="vertical-align:middle;">
                    <img src="' . base_url('uploads/company/lumifog-logo.webp') . '" height="80">
                </td>
                <td width="40%" valign="middle" style="text-align:center; vertical-align:middle;">
                    <div style="font-size:12px; font-weight:bold;"><strong>' . $invoice_heading . '</strong></div>
                </td>
                <td width="30%" valign="middle" style="vertical-align:middle;">
                </td>
            </tr>
        </table>
        
        <!-- Header Table with Company and Consignee Details -->
        <table width="100%" cellspacing="0" cellpadding="3" border="1" style="' . $border_style . ' border-top:2px solid #000000; margin-bottom:0 !important; margin-top:0;">
            <!-- ROW 1 -->
            <tr>
                <!-- LEFT BLOCK -->
                <td width="60%" rowspan="10" valign="top" style="' . $border_style . ' border-top:2px solid #000000; font-size:12px;"><strong style="font-size:12px;">' . get_option('invoice_company_name') . '</strong><br/>
                    <span style="font-size:12px;">' . 
                    (get_option('invoice_company_address') ? get_option('invoice_company_address') . '<br/>' : '') .
                    (get_option('invoice_company_city') ? get_option('invoice_company_city') . ', ' : '') .
                    (get_option('company_state') ? get_option('company_state') . ' - ' : '') .
                    (get_option('company_zip_code') ? get_option('company_zip_code') : '') . 
                    '</span><br/>

                    <span style="font-size:12px;"><strong>GSTIN/UIN:</strong> ' . (get_option('company_vat') ?: '27AAFCL7466D1ZC') . '<br/>
                    <strong>State Name:</strong> Maharashtra, Code: 27<br/>
                    <strong>Contact:</strong> ' . get_option('company_phonenumber') . '<br/>
                    <strong>E-Mail:</strong> ' . get_option('company_email') . '</span>

                    <hr style="border:0;border-top:2px solid #000;margin:4px 0;"/>

                    <table width="100%" cellspacing="0" cellpadding="3" border="0" style="font-size:12px;">
                        <tr>
                            <td width="50%" valign="top" style="border-right:1px solid #000;"><strong style="font-size:12px;">Buyer (Bill to)</strong><br/>
                                <strong style="font-size:12px;">' . $invoice->client->company . '</strong><br/>
                                <span style="font-size:12px;">' . $invoice->billing_street . '<br/>
                                ' . ($invoice->billing_city ? $invoice->billing_city . ', ' : '') .
                                ($invoice->billing_state ? $invoice->billing_state . ' - ' : '') .
                                $invoice->billing_zip . ', India<br/>
                                <strong>GSTIN/UIN:</strong> ' . ($invoice->client->vat ?: '') . '<br/>
                                <strong>State Name:</strong> ' . ($invoice->billing_state ?: '') . '</span><br/>
                            </td>
                            <td width="50%" valign="top"><strong style="font-size:12px;">Consignee (Ship to)</strong><br/>
                                <strong style="font-size:12px;">' . $invoice->client->company . '</strong><br/>
                                <span style="font-size:12px;">' . $invoice->shipping_street . '<br/>
                                ' . ($invoice->shipping_city ? $invoice->shipping_city . ', ' : '') .
                                ($invoice->shipping_state ? $invoice->shipping_state . ' - ' : '') .
                                $invoice->shipping_zip . ', India<br/>
                                <strong>GSTIN/UIN:</strong> ' . ($invoice->client->vat ?: '') . '<br/>
                                <strong>State Name:</strong> ' . ($invoice->shipping_state ?: '') . '</span><br/>
                            </td>
                        </tr>
                    </table>

                </td>

                <!-- RIGHT BLOCK -->
                <td width="20%" style="border-right:1px solid #000; border-bottom:1px solid #000; border-top:2px solid #000000; font-size:12px;"><strong style="font-size:12px;">Invoice No.</strong></td>
                <td width="20%" style="border-top:2px solid #000000; border-left:1px solid #000000; border-right:1px solid #000000; border-bottom:1px solid #000000; font-size:12px;">' . format_invoice_number($invoice->id) . '</td>
            </tr>

            <tr>
                <td style="border-right:1px solid #000; border-bottom:1px solid #000; font-size:12px;"><strong style="font-size:12px;">Dated</strong></td>
                <td style="font-size:12px;">' . _d($invoice->date) . '</td>
            </tr>

            <tr>
                <td style="border-right:1px solid #000; border-bottom:1px solid #000; font-size:12px;"><strong style="font-size:12px;">E-way Bill No.</strong></td>
                <td style="font-size:12px;">' . (!empty($invoice->eway_bill_no) ? $invoice->eway_bill_no : '&nbsp;') . '</td>
            </tr>

            <tr>
                <td style="border-right:1px solid #000; border-bottom:1px solid #000; font-size:12px;"><strong style="font-size:12px;">Buyer\'s Order No.</strong></td>
                <td style="font-size:12px;">' . (!empty($invoice->buyers_order_no) ? $invoice->buyers_order_no : '&nbsp;') . '</td>
            </tr>

            <tr>
                <td style="border-right:1px solid #000; border-bottom:1px solid #000; font-size:12px;"><strong style="font-size:12px;">Dated</strong></td>
                <td style="font-size:12px;">' . (!empty($invoice->buyers_order_date) ? _d($invoice->buyers_order_date) : '&nbsp;') . '</td>
            </tr>

            <tr>
                <td style="border-right:1px solid #000; border-bottom:1px solid #000; font-size:12px;"><strong style="font-size:12px;">Dispatch Doc No.</strong></td>
                <td style="font-size:12px;">' . (!empty($invoice->dispatch_doc_no) ? $invoice->dispatch_doc_no : '&nbsp;') . '</td>
            </tr>

            <tr>
                <td style="border-right:1px solid #000; border-bottom:1px solid #000; font-size:12px;"><strong style="font-size:12px;">Dispatched Through</strong></td>
                <td style="font-size:12px;">' . (!empty($invoice->dispatched_through) ? $invoice->dispatched_through : '&nbsp;') . '</td>
            </tr>

            <tr>
                <td style="border-right:1px solid #000; border-bottom:1px solid #000; font-size:12px;"><strong style="font-size:12px;">Payment Terms</strong></td>
                <td style="font-size:12px;">' . (!empty($invoice->payment_terms) ? $invoice->payment_terms : '&nbsp;') . '</td>
            </tr>

            <tr>
                <td style="border-right:1px solid #000; border-bottom:1px solid #000; font-size:12px;"><strong style="font-size:12px;">E-invoice Number</strong></td>
                <td style="font-size:12px;">' . ($e_invoice_number !== '' ? htmlspecialchars($e_invoice_number) : '&nbsp;') . '</td>
            </tr>

            <tr>
                <td style="border-right:1px solid #000; border-bottom:1px solid #000; font-size:12px;"><strong style="font-size:12px;">IRN</strong></td>
                <td style="font-size:12px;">' . ($irn !== '' ? htmlspecialchars($irn) : '&nbsp;') . '</td>
            </tr>

        </table>
    </div>';
    
    // Ensure consistent X position before writing header table
    $pdf->SetX($margins['left']);
    
    $pdf->writeHTMLCell(
        $contentWidth,
        '',
        '',
        '',
        $header_html,
        0,
        1,
        false,
        true,
        'L',
        true
    );
    
    // Store Y position after header for content to start immediately after
    $currentY = $pdf->GetY();
    // Reduce Y position slightly to eliminate any gap
    $adjustedY = max($currentY - 2, 5); // Move up 2mm, but not less than 5mm from top
    $GLOBALS['header_end_y_' . $pdfId] = $adjustedY;
    
    // Store content width for consistent table widths
    $GLOBALS['content_width_' . $pdfId] = $contentWidth;
    
    // Set margins so content starts right after header (no extra spacing)
    $pdf->SetMargins($margins['left'], $adjustedY, $margins['right']);
}

function lumifog_estimate_pdf_header($data)
{
    if (empty($data['pdf_instance']) || ($data['type'] ?? '') !== 'estimate') {
        return;
    }

    $pdf = $data['pdf_instance'];

    // Get estimate data from global variable
    if (!isset($GLOBALS['estimate_pdf'])) {
        return;
    }

    $estimate = $GLOBALS['estimate_pdf'];

    // Custom fields for IRN and E-invoice Number on estimates
    $irn              = trim((string) get_custom_field_value($estimate->id, 'estimate_irn_code', 'estimate'));
    $e_invoice_number = trim((string) get_custom_field_value($estimate->id, 'estimate_e_invoice_no', 'estimate'));

    // Heading fixed to Pro-Tax-Invoice for estimates
    $estimate_heading = 'Proforma-Invoice';

    static $marginsAdjusted = [];
    $pdfId       = spl_object_id($pdf);
    $margins     = $pdf->getMargins();
    $pageWidth   = $pdf->getPageWidth();
    $contentWidth = $pageWidth - $margins['left'] - $margins['right'];

    // Border style for tables
    $border_style = 'border:1px solid #000000;';

    if (!isset($marginsAdjusted[$pdfId])) {
        $marginsAdjusted[$pdfId] = true;
    }

    // Start at top of page
    $pdf->SetY(5);
    $pdf->SetX($margins['left']);

    // Header HTML adapted to match lumifog_invoice_pdf_header structure
    $header_html = '
    <div style="background-color:#ffffff; margin:0; padding:0; margin-bottom:0 !important;">
        <!-- Logo and Pro-Tax-Invoice Row -->
        <table width="100%" cellspacing="0" cellpadding="0" border="0" style="margin-bottom:0;">
            <tr>
                <td width="30%" valign="middle" style="vertical-align:middle;">
                    <img src="' . base_url('uploads/company/lumifog-logo.webp') . '" height="80">
                </td>
                <td width="40%" valign="middle" style="text-align:center; vertical-align:middle;">
                    <div style="font: size 20px; font-weight:bold;"><strong>' . $estimate_heading . '</strong></div>
                </td>
                <td width="30%"></td>
            </tr>
        </table>

        <!-- Header Table with Company and Consignee Details -->
        <table width="100%" cellspacing="0" cellpadding="3" border="1" style="' . $border_style . ' border-top:2px solid #000000; margin-bottom:0 !important; margin-top:0;">
            <!-- ROW 1 -->
            <tr>
                <!-- LEFT BLOCK -->
                <td width="60%" rowspan="10" valign="top" style="' . $border_style . ' border-top:2px solid #000000; font-size:12px;"><strong style="font-size:12px;">' . get_option('invoice_company_name') . '</strong><br/>
                    <span style="font-size:12px;">' .
                    (get_option('invoice_company_address') ? get_option('invoice_company_address') . '<br/>' : '') .
                    (get_option('invoice_company_city') ? get_option('invoice_company_city') . ', ' : '') .
                    (get_option('company_state') ? get_option('company_state') . ' - ' : '') .
                    (get_option('company_zip_code') ? get_option('company_zip_code') : '') .
                    '</span><br/>

                    <span style="font-size:12px;"><strong>GSTIN/UIN:</strong> ' . (get_option('company_vat') ?: '27AAFCL7466D1ZC') . '<br/>
                    <strong>State Name:</strong> Maharashtra, Code: 27<br/>
                    <strong>Contact:</strong> ' . get_option('company_phonenumber') . '<br/>
                    <strong>E-Mail:</strong> ' . get_option('company_email') . '</span>

                    <hr style="border:0;border-top:2px solid #000;margin:4px 0;"/>

                    <table width="100%" cellspacing="0" cellpadding="3" border="0" style="font-size:12px;">
                        <tr>
                            <td width="50%" valign="top" style="border-right:1px solid #000;"><strong style="font-size:12px;">Buyer (Bill to)</strong><br/>
                                <strong style="font-size:12px;">' . $estimate->client->company . '</strong><br/>
                                <span style="font-size:12px;">' . $estimate->billing_street . '<br/>
                                ' . ($estimate->billing_city ? $estimate->billing_city . ', ' : '') .
                                ($estimate->billing_state ? $estimate->billing_state . ' - ' : '') .
                                $estimate->billing_zip . ', India<br/>
                                <strong>GSTIN/UIN:</strong> ' . ($estimate->client->vat ?: '') . '<br/>
                                <strong>State Name:</strong> ' . ($estimate->billing_state ?: '') . '</span><br/>
                            </td>
                            <td width="50%" valign="top"><strong style="font-size:12px;">Consignee (Ship to)</strong><br/>
                                <strong style="font-size:12px;">' . $estimate->client->company . '</strong><br/>
                                <span style="font-size:12px;">' . $estimate->shipping_street . '<br/>
                                ' . ($estimate->shipping_city ? $estimate->shipping_city . ', ' : '') .
                                ($estimate->shipping_state ? $estimate->shipping_state . ' - ' : '') .
                                $estimate->shipping_zip . ', India<br/>
                                <strong>GSTIN/UIN:</strong> ' . ($estimate->client->vat ?: '') . '<br/>
                                <strong>State Name:</strong> ' . ($estimate->shipping_state ?: '') . '</span><br/>
                            </td>
                        </tr>
                    </table>

                </td>

                <!-- RIGHT BLOCK -->
                <td width="20%" style="border-right:1px solid #000; border-bottom:1px solid #000; border-top:2px solid #000000; font-size:12px;"><strong style="font-size:12px;">Estimate No.</strong></td>
                <td width="20%" style="border-top:2px solid #000000; border-left:1px solid #000000; border-right:1px solid #000000; border-bottom:1px solid #000000; font-size:12px;">' . format_estimate_number($estimate->id) . '</td>
            </tr>

            <tr>
                <td style="border-right:1px solid #000; border-bottom:1px solid #000; font-size:12px;"><strong style="font-size:12px;">Dated</strong></td>
                <td style="font-size:12px;">' . _d($estimate->date) . '</td>
            </tr>

            <tr>
                <td style="border-right:1px solid #000; border-bottom:1px solid #000; font-size:12px;"><strong style="font-size:12px;">E-way Bill No.</strong></td>
                <td style="font-size:12px;">' . (!empty($estimate->eway_bill_no) ? $estimate->eway_bill_no : '&nbsp;') . '</td>
            </tr>

            <tr>
                <td style="border-right:1px solid #000; border-bottom:1px solid #000; font-size:12px;"><strong style="font-size:12px;">Buyer\'s Order No.</strong></td>
                <td style="font-size:12px;">' . (!empty($estimate->buyers_order_no) ? $estimate->buyers_order_no : '&nbsp;') . '</td>
            </tr>

            <tr>
                <td style="border-right:1px solid #000; border-bottom:1px solid #000; font-size:12px;"><strong style="font-size:12px;">Dated</strong></td>
                <td style="font-size:12px;">' . (!empty($estimate->buyers_order_date) ? _d($estimate->buyers_order_date) : '&nbsp;') . '</td>
            </tr>

            <tr>
                <td style="border-right:1px solid #000; border-bottom:1px solid #000; font-size:12px;"><strong style="font-size:12px;">Dispatch Doc No.</strong></td>
                <td style="font-size:12px;">' . (!empty($estimate->dispatch_doc_no) ? $estimate->dispatch_doc_no : '&nbsp;') . '</td>
            </tr>

            <tr>
                <td style="border-right:1px solid #000; border-bottom:1px solid #000; font-size:12px;"><strong style="font-size:12px;">Dispatched Through</strong></td>
                <td style="font-size:12px;">' . (!empty($estimate->dispatched_through) ? $estimate->dispatched_through : '&nbsp;') . '</td>
            </tr>

            <tr>
                <td style="border-right:1px solid #000; border-bottom:1px solid #000; font-size:12px;"><strong style="font-size:12px;">Payment Terms</strong></td>
                <td style="font-size:12px;">' . (!empty($estimate->payment_terms) ? $estimate->payment_terms : '&nbsp;') . '</td>
            </tr>

            <tr>
                <td style="border-right:1px solid #000; border-bottom:1px solid #000; font-size:12px;"><strong style="font-size:12px;">E-invoice Number</strong></td>
                <td style="font-size:12px;">' . ($e_invoice_number !== '' ? htmlspecialchars($e_invoice_number) : '&nbsp;') . '</td>
            </tr>

            <tr>
                <td style="border-right:1px solid #000; border-bottom:1px solid #000; font-size:12px;"><strong style="font-size:12px;">IRN</strong></td>
                <td style="font-size:12px;">' . ($irn !== '' ? htmlspecialchars($irn) : '&nbsp;') . '</td>
            </tr>

        </table>
    </div>';

    // Ensure consistent X position before writing header table
    $pdf->SetX($margins['left']);

    // ln=2 places cursor below the cell; TCPDF HTML still reports Y past the visible border.
    // Pull up so items sit with only a small spacer under the header table.
    $pdf->writeHTMLCell(
        $contentWidth,
        '',
        '',
        '',
        $header_html,
        0,
        2,
        false,
        true,
        'L',
        true
    );

    $adjustedY = max($pdf->GetY() - 10, 5);
    $GLOBALS['header_end_y_' . $pdfId] = $adjustedY;

    // Store content width for consistent table widths
    $GLOBALS['content_width_' . $pdfId] = $contentWidth;

    // Set margins so content starts right after header (no extra spacing)
    $pdf->SetMargins($margins['left'], $adjustedY, $margins['right']);
}

function lumifog_invoice_pdf_footer($data)
{
    if (empty($data['pdf_instance']) || ($data['type'] ?? '') !== 'invoice') {
        return;
    }

    $pdf = $data['pdf_instance'];
    $pdfId = spl_object_id($pdf);
    
    // Get total items count
    $total_items = isset($GLOBALS['invoice_pdf_total_items_' . $pdfId]) ? $GLOBALS['invoice_pdf_total_items_' . $pdfId] : 0;
    
    // Only show footer on the last page
    // Check if all content has been written and we're on the last page
    $contentComplete = isset($GLOBALS['invoice_pdf_content_complete_' . $pdfId]) && $GLOBALS['invoice_pdf_content_complete_' . $pdfId];
    $lastPage = isset($GLOBALS['invoice_pdf_last_page_' . $pdfId]) ? $GLOBALS['invoice_pdf_last_page_' . $pdfId] : 0;
    $currentPage = $pdf->getPage();
    
    // If items <= 5, footer should show on same page as tax (which is on same page as items)
    // If items > 5, footer should show only on last page (after tax calculations)
    if (!$contentComplete) {
        return;
    }
    
    // If items > 5, only show footer on the last page
    if ($total_items > 5 && ($lastPage > 0 && $currentPage < $lastPage)) {
        return;
    }
    
    // Get invoice data from global variable
    if (!isset($GLOBALS['invoice_pdf'])) {
        return;
    }
    
    $invoice = $GLOBALS['invoice_pdf'];
    $pdfId = spl_object_id($pdf);
    
    // Get content width for consistent table width
    $contentWidth = isset($GLOBALS['content_width_' . $pdfId]) ? $GLOBALS['content_width_' . $pdfId] : ($pdf->getPageWidth() - $pdf->getMargins()['left'] - $pdf->getMargins()['right']);
    
    // Border style for tables
    $border_style = 'border:1px solid #000000;';
    
    // Get signature image if available
    $signatureImage = get_option('signature_image');
    $signatureHtml = '';
    if ($signatureImage != '') {
        $signaturePath = FCPATH . 'uploads/company/' . $signatureImage;
        if (file_exists($signaturePath)) {
            $imageData = base64_encode(file_get_contents($signaturePath));
            $signatureHtml = '<br/><img src="@' . $imageData . '" height="80" style="max-height:80px;" />';
        }
    }
    
    // Bank Details Footer
    $bank_details = '<table width="100%" cellspacing="0" cellpadding="2" border="1" style="' . $border_style . ' margin:0 !important; padding:0 !important;">
    <tr>
        <td width="60%" style="' . $border_style . ' font-size:12px; padding:3px; margin:0;">
            <strong style="font-size:12px;">Company\'s Bank Details</strong><br/>
            <span style="font-size:12px;"><strong>A/c Holder\'s Name:</strong> ' . get_option('invoice_company_name') . '<br/>
            <strong>Bank Name:</strong> HDFC BANK LTD<br/>
            <strong>A/c No.:</strong> 50200096015459<br/>
            <strong>Branch & IFS Code:</strong> HDFC0000592</span>
        </td>
        <td width="40%" style="' . $border_style . ' text-align:right; vertical-align:bottom; font-size:12px; padding:3px; padding-bottom:5px; margin:0;">
            <strong style="font-size:12px;">for ' . get_option('invoice_company_name') . '</strong><br/>
            ' . $signatureHtml . '
            <strong style="font-size:12px;">Authorised Signatory</strong>
        </td>
    </tr>
    <tr>
        <td width="100%" colspan="2" style="' . $border_style . ' font-size:12px; padding:3px; margin:0;">
            <strong style="font-size:12px;">Declaration</strong><br/>
            <span style="font-size:12px;">We declare that this invoice shows the actual price of the goods described and that all particulars are true and correct.</span>
        </td>
    </tr>
    </table>';
    
    // Position footer at bottom of page
    $margins = $pdf->getMargins();
    $pageHeight = $pdf->getPageHeight();
    $footerHeight = 35; // Approximate footer height in mm
    
    // Position footer at the bottom with minimal spacing
    $footerY = $pageHeight - $margins['bottom'] - $footerHeight;
    $pdf->SetY($footerY);
    $pdf->SetX($margins['left']);
    
    $pdf->writeHTMLCell(
        $contentWidth,
        '',
        '',
        '',
        $bank_details,
        0,
        1,
        false,
        true,
        'L',
        true
    );
    
    // Set footer margin to reserve space for footer (if not already set)
    if ($pdf->getFooterMargin() < $footerHeight + 5) {
        $pdf->SetFooterMargin($footerHeight + 5);
    }
}

function lumifog_disable_invoice_signature($process)
{
    // Disable signature processing for invoices and estimates since signature is in custom footers
    if (isset($GLOBALS['invoice_pdf']) || isset($GLOBALS['estimate_pdf'])) {
        return false;
    }
    return $process;
}

function lumifog_estimate_pdf_footer($data)
{
    if (empty($data['pdf_instance']) || ($data['type'] ?? '') !== 'estimate') {
        return;
    }

    $pdf   = $data['pdf_instance'];
    $pdfId = spl_object_id($pdf);

    // Get total items count
    $total_items = isset($GLOBALS['estimate_pdf_total_items_' . $pdfId]) ? $GLOBALS['estimate_pdf_total_items_' . $pdfId] : 0;

    // Only show footer on the last page
    $contentComplete = isset($GLOBALS['estimate_pdf_content_complete_' . $pdfId]) && $GLOBALS['estimate_pdf_content_complete_' . $pdfId];
    $lastPage        = isset($GLOBALS['estimate_pdf_last_page_' . $pdfId]) ? $GLOBALS['estimate_pdf_last_page_' . $pdfId] : 0;
    $currentPage     = $pdf->getPage();

    if (!$contentComplete) {
        return;
    }

    if ($total_items > 5 && ($lastPage > 0 && $currentPage < $lastPage)) {
        return;
    }

    if (!isset($GLOBALS['estimate_pdf'])) {
        return;
    }

    $estimate = $GLOBALS['estimate_pdf'];

    // Get content width for consistent table width
    $contentWidth = isset($GLOBALS['content_width_' . $pdfId]) ? $GLOBALS['content_width_' . $pdfId] : ($pdf->getPageWidth() - $pdf->getMargins()['left'] - $pdf->getMargins()['right']);

    // Border style for tables
    $border_style = 'border:1px solid #000000;';

    // Get signature image if available
    $signatureImage = get_option('signature_image');
    $signatureHtml  = '';
    if ($signatureImage != '') {
        $signaturePath = FCPATH . 'uploads/company/' . $signatureImage;
        if (file_exists($signaturePath)) {
            $imageData    = base64_encode(file_get_contents($signaturePath));
            $signatureHtml = '<br/><img src="@' . $imageData . '" height="80" style="max-height:80px;" />';
        }
    }

    // Bank Details Footer (same as invoice footer, but for estimates)
    $bank_details = '<table width="100%" cellspacing="0" cellpadding="2" border="1" style="' . $border_style . ' margin:0 !important; padding:0 !important;">
    <tr>
        <td width="60%" style="' . $border_style . ' font-size:12px; padding:3px; margin:0;">
            <strong style="font-size:12px;">Company\'s Bank Details</strong><br/>
            <span style="font-size:12px;"><strong>A/c Holder\'s Name:</strong> ' . get_option('invoice_company_name') . '<br/>
            <strong>Bank Name:</strong> HDFC BANK LTD<br/>
            <strong>A/c No.:</strong> 50200096015459<br/>
            <strong>Branch & IFS Code:</strong> HDFC0000592</span>
        </td>
        <td width="40%" style="' . $border_style . ' text-align:right; vertical-align:bottom; font-size:12px; padding:3px; padding-bottom:5px; margin:0;">
            <strong style="font-size:12px;">for ' . get_option('invoice_company_name') . '</strong><br/>
            ' . $signatureHtml . '
            <strong style="font-size:12px;">Authorised Signatory</strong>
        </td>
    </tr>
    <tr>
        <td width="100%" colspan="2" style="' . $border_style . ' font-size:12px; padding:3px; margin:0;">
            <strong style="font-size:12px;">Declaration</strong><br/>
            <span style="font-size:12px;">We declare that this invoice shows the actual price of the goods described and that all particulars are true and correct.</span>
        </td>
    </tr>
    </table>';

    // Position footer relative to tax/HSN block (not bottom-anchored — that overlapped tax-in-words)
    $margins      = $pdf->getMargins();
    $pageHeight   = $pdf->getPageHeight();
    // Approx height of bank-details + signature + declaration (slightly tight so spacer can use leftover room)
    $footerHeight = 30;
    // App_pdf::Footer() already SetY(-15); use stored content end Y from my_estimatepdf.php
    $contentEndY  = isset($GLOBALS['estimate_pdf_content_end_y_' . $pdfId])
        ? (float) $GLOBALS['estimate_pdf_content_end_y_' . $pdfId]
        : $pdf->GetY();

    // Use near-page-bottom (not full bottom margin) so free whitespace can pull the footer down
    $bottomLimit = $pageHeight - 8;
    $remaining   = $bottomLimit - $contentEndY - $footerHeight;
    // Small gap after tax-in-words; if free space remains, use ~half so footer sits lower
    $spacer = 5;
    if ($remaining > $spacer) {
        $spacer = $spacer + (($remaining - $spacer) / 2);
    }

    $pdf->SetY($contentEndY + $spacer);
    $pdf->SetX($margins['left']);

    $pdf->writeHTMLCell(
        $contentWidth,
        '',
        '',
        '',
        $bank_details,
        0,
        1,
        false,
        true,
        'L',
        true
    );

    if ($pdf->getFooterMargin() < $footerHeight + 5) {
        $pdf->SetFooterMargin($footerHeight + 5);
    }
}

function lumifog_proposal_pdf_watermark($pdf)
{
    $watermarkLogo = FCPATH . 'uploads/company/water-mark.webp';

    if (!file_exists($watermarkLogo)) {
        return;
    }

    $pageWidth = $pdf->getPageWidth();
    $pageHeight = $pdf->getPageHeight();

    $margins = $pdf->getMargins();
    $leftMargin = $margins['left'];
    $rightMargin = $margins['right'];
    $topMargin = $margins['top'];
    $bottomMargin = $margins['bottom'];

    $usableWidth = $pageWidth - $leftMargin - $rightMargin;
    $usableHeight = $pageHeight - $topMargin - $bottomMargin;

    $desiredHeightMM = 52.92; // 200px at 96 DPI

    $imageInfo = @getimagesize($watermarkLogo);
    if ($imageInfo) {
        $imgWidth = $imageInfo[0];
        $imgHeight = $imageInfo[1];
        $aspectRatio = $imgWidth / $imgHeight;
        $desiredWidthMM = $desiredHeightMM * $aspectRatio;
    } else {
        $desiredWidthMM = $desiredHeightMM * 1.5;
    }

    if ($desiredWidthMM > $usableWidth) {
        $scale = $usableWidth / $desiredWidthMM;
        $desiredWidthMM = $usableWidth;
        $desiredHeightMM = $desiredHeightMM * $scale;
    }

    if ($desiredHeightMM > $usableHeight) {
        $scale = $usableHeight / $desiredHeightMM;
        $desiredHeightMM = $usableHeight;
        $desiredWidthMM = $desiredWidthMM * $scale;
    }

    $x = $leftMargin + ($usableWidth - $desiredWidthMM) / 2;
    $y = $topMargin + ($usableHeight - $desiredHeightMM) / 2;

    $autoPageBreak = $pdf->getAutoPageBreak();
    $breakMargin = $pdf->getBreakMargin();
    $pdf->SetAutoPageBreak(false);

    $pdf->SetAlpha(1.0);

    $pdf->Image(
        $watermarkLogo,
        $x,
        $y,
        $desiredWidthMM,
        $desiredHeightMM,
        '',
        '',
        '',
        false,
        96,
        '',
        false,
        false,
        0,
        false,
        false,
        true
    );

    $pdf->SetAlpha(1);
    $pdf->SetAutoPageBreak($autoPageBreak, $breakMargin);
}

function lumifog_proposal_pdf_footer($data)
{
    if (empty($data['pdf_instance']) || ($data['type'] ?? '') !== 'proposal') {
        return;
    }

    $pdf = $data['pdf_instance'];
    $pageWidth = $pdf->getPageWidth();
    $margin = $pdf->getMargins()['left'];

    $phoneIcon = FCPATH . 'uploads/company/telephone.png';
    $emailIcon = FCPATH . 'uploads/company/email.png';
    $webIcon   = FCPATH . 'uploads/company/web.png';

    $pdf->SetY(-23);
    $pdf->SetFont('helvetica', '', 8);

    $colWidth = ($pageWidth - $margin * 2) / 3;

    $pdf->Image($phoneIcon, $margin + 2, $pdf->GetY() + 1, 4);
    $pdf->SetXY($margin + 7, $pdf->GetY());
    $pdf->Cell($colWidth - 8, 5, '9372348997', 0, 0, 'L');

    $centerX = $margin + $colWidth;
    $pdf->Image($emailIcon, $centerX + ($colWidth / 2) - 18, $pdf->GetY() + 1, 4);
    $pdf->SetXY($centerX + ($colWidth / 2) - 13, $pdf->GetY());
    $pdf->Cell(22, 5, 'info@lumifog.com', 0, 0, 'C');

    $rightX = $margin + $colWidth * 2;
    $pdf->Image($webIcon, $rightX + $colWidth - 28, $pdf->GetY() + 1, 4);
    $pdf->SetXY($rightX + $colWidth - 22, $pdf->GetY());
    $pdf->Cell(26, 5, 'www.lumifog.com', 0, 1, 'L');

    $pdf->Ln(1);
    $pdf->Line($margin, $pdf->GetY(), $pageWidth - $margin, $pdf->GetY());

    $pdf->Ln(2);
    $pdf->SetFont('helvetica', '', 7.5);
    $pdf->Cell(0, 4, 'Unit No. F-48, First Floor kohinoor City Mall, Opp Holly Cross School, Kurla (W) Mumbai, Maharashtra - 400070.', 0, 1, 'C');
    $pdf->Cell(0, 4, 'GSTIN: 27AAFCL7466D1ZC', 0, 1, 'C');

    // Add page number - placed at the very bottom right
    $pdf->SetY(-10);
    $pdf->SetX(-20);
    $pdf->SetFont('helvetica', '', 8);
    $pdf->Cell(0, 5, 'Page ' . $pdf->getAliasNumPage() . '/' . $pdf->getAliasNbPages(), 0, 0, 'R');
}

function theme_assets()
{
    $CI = &get_instance();

    $groupName = $CI->app_scripts->default_theme_group();

    $CI->app_scripts->theme('bootstrap-js', 'assets/plugins/bootstrap/js/bootstrap.min.js');

    add_datatables_js_assets($groupName);
    add_jquery_validation_js_assets($groupName);
    add_bootstrap_select_js_assets($groupName);

    $CI->app_scripts->theme('datetimepicker-js', 'assets/plugins/datetimepicker/jquery.datetimepicker.full.min.js');
    $CI->app_scripts->theme('chart-js', 'assets/plugins/Chart.js/Chart.min.js');
    $CI->app_scripts->theme('colorpicker-js', 'assets/plugins/bootstrap-colorpicker/js/bootstrap-colorpicker.min.js');
    $CI->app_scripts->theme('lightbox-js', 'assets/plugins/lightbox/js/lightbox.min.js');

    if (is_client_logged_in()) {
        $CI->app_scripts->theme('dropzone-js', 'assets/plugins/dropzone/min/dropzone.min.js');
        $CI->app_scripts->theme('circle-progress-js', 'assets/plugins/jquery-circle-progress/circle-progress.min.js');

        $CI->app_scripts->theme('jquery-comments-js', 'assets/plugins/jquery-comments/js/jquery-comments.min.js');
        $CI->app_scripts->theme('frape-gantt-js', 'assets/plugins/frappe/frappe-gantt-es2015.js');
        add_moment_js_assets($groupName);
        add_dropbox_js_assets($groupName);

        $CI->app_css->theme('jquery-comments-css', 'assets/plugins/jquery-comments/css/jquery-comments.css');
        $CI->app_css->theme('frappe-gantt-css', 'assets/plugins/frappe/frappe-gantt.css');
        add_calendar_assets($groupName);

        if (get_option('enable_google_picker') == '1') {
            add_google_api_js_assets($groupName);
        }
    }

    $CI->app_scripts->theme('common-js', 'assets/builds/common.js');

    $CI->app_scripts->theme(
        'theme-global-js',
        base_url($CI->app_scripts->core_file(theme_assets_path() . '/js', 'global.js')) . '?v=' . $CI->app_css->core_version(),
        ['common-js']
    );

    if (is_client_logged_in()) {
        $CI->app_scripts->theme(
            'theme-js',
            base_url($CI->app_scripts->core_file(theme_assets_path() . '/js', 'clients.js')) . '?v=' . $CI->app_css->core_version(),
            ['common-js']
        );
    }

    // CSS
    add_favicon_link_asset($groupName);

    $CI->app_css->theme(
        'reset-css',
        base_url($CI->app_css->core_file('assets/css', 'reset.css')) . '?v=' . $CI->app_css->core_version()
    );

    $CI->app_css->theme('bootstrap-css', 'assets/plugins/bootstrap/css/bootstrap.min.css');
    // $CI->app_css->theme('roboto-css', 'assets/plugins/roboto/roboto.css');
    $CI->app_css->theme('inter-font', 'assets/plugins/inter/inter.css');

    if (is_rtl()) {
        $CI->app_css->theme('bootstrap-rtl-css', 'assets/plugins/bootstrap-arabic/css/bootstrap-arabic.min.css');
    }

    $CI->app_css->theme('datatables-css', 'assets/plugins/datatables/datatables.min.css');

    $CI->app_css->theme('fontawesome-css', 'assets/plugins/font-awesome/css/fontawesome.min.css');
    $CI->app_css->theme('fontawesome-brands', 'assets/plugins/font-awesome/css/brands.min.css');
    $CI->app_css->theme('fontawesome-solid', 'assets/plugins/font-awesome/css/solid.min.css');
    $CI->app_css->theme('fontawesome-regular', 'assets/plugins/font-awesome/css/regular.min.css');

    $CI->app_css->theme('datetimepicker-css', 'assets/plugins/datetimepicker/jquery.datetimepicker.min.css');
    $CI->app_css->theme('bootstrap-select-css', 'assets/plugins/bootstrap-select/css/bootstrap-select.min.css');

    if (is_client_logged_in()) {
        $CI->app_css->theme('dropzone-basic-css', 'assets/plugins/dropzone/min/basic.min.css');
        $CI->app_css->theme('dropzone-css', 'assets/plugins/dropzone/min/dropzone.min.css');
    }

    $CI->app_css->theme('lightbox-css', 'assets/plugins/lightbox/css/lightbox.min.css');
    $CI->app_css->theme('colorpicker-css', 'assets/plugins/bootstrap-colorpicker/css/bootstrap-colorpicker.min.css');

    $CI->app_css->theme('tailwind-css', base_url($CI->app_css->core_file('assets/builds', 'tailwind.css')) . '?v=' . $CI->app_css->core_version(), ['bootstrap-css']);

    $CI->app_css->theme(
        'theme-css',
        base_url($CI->app_scripts->core_file(theme_assets_path() . '/css', 'style.css')) . '?v=' . $CI->app_css->core_version()
    );
}
