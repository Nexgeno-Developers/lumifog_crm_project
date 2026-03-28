<?php

defined('BASEPATH') or exit('No direct script access allowed');

function render_fixed_footer($pdf)
{
    $FOOTER_HEIGHT = 55;

// IMPORTANT: reserve bottom space BEFORE content
// $pdf->SetAutoPageBreak(true, $FOOTER_HEIGHT + 1);
$pdf->SetFooterMargin($FOOTER_HEIGHT);

    // Use Unicode-safe font
    // $pdf->SetFont('dejavusans', '', 12);

    $footer_html = '
    <table width="100%" cellpadding="4" cellspacing="0" style="border-top:1px solid #ccc; font-size:14px;">
        <tr>
            <td width="33%" align="left">
                +91 9372348997
            </td>
            <td width="34%" align="center">
                nfo@lumifog.com
            </td>
            <td width="33%" align="right">
                www.lumifog.com
            </td>
        </tr>
        <tr>
            <td colspan="3" align="center" style="font-size:14px; padding-top:10px">
                Unit No. F-48, First Floor Kohinoor City Mall, Kurla (W), Mumbai – 400070 |
                GSTIN: 27AAFCL7466D1ZC
            </td>
        </tr>
    </table>
    ';

    // Render footer without affecting page flow
    $pdf->writeHTMLCell(
        0,                 // full width
        $footerHeight,     // fixed height
        '',                // X
        '',                // Y (already set)
        $footer_html,
        0,
        0,
        false,
        true,
        'C',
        true
    );
}

// Set left margin/padding for all pages
$pdf->SetLeftMargin(20); // 20mm left margin

/* =================================================
   PAGE 1 : PROPOSAL LETTER
================================================= */

$proposal_date = _l('proposal_date') . ': ' . _d($proposal->date);
$open_till = !empty($proposal->open_till)
    ? _l('proposal_open_till') . ': ' . _d($proposal->open_till)
    : '';

// Proposal custom field: Kind Attn (slug: proposal_kind_attn)
$kind_attn = trim((string) get_custom_field_value($proposal->id, 'proposal_kind_attn', 'proposal'));

// Get assigned staff member information
$assigned_staff_name = '';
$assigned_staff_phone = '';
if (!empty($proposal->assigned) && $proposal->assigned != 0) {
    $assigned_staff_name = get_staff_full_name($proposal->assigned);
    
    // Get staff phone number
    $CI = &get_instance();
    $CI->db->select('phonenumber');
    $CI->db->where('staffid', $proposal->assigned);
    $staff = $CI->db->get(db_prefix() . 'staff')->row();
    if ($staff && !empty($staff->phonenumber)) {
        $assigned_staff_phone = $staff->phonenumber;
    }
}

// Fallback to default values if no staff assigned
if (empty($assigned_staff_name)) {
    $assigned_staff_name = 'S. Ansari';
}
if (empty($assigned_staff_phone)) {
    $assigned_staff_phone = '+91-7897899919';
}

$page1_html = '<div style="font-size:16px; color:#000;"><div style="line-height:1.7;"><strong>Ref:</strong> ' . $number . '<br><strong>Date:</strong> ' . _d($proposal->date) . '</div>
         
<div style="line-height:1.7;"><br><strong>To,</strong><br>' . nl2br(format_proposal_info($proposal, 'pdf')) . '</div>
    
<div style="line-height:1.9;">' . ($kind_attn !== '' ? 'Kind Attn: <strong>' . $kind_attn . ' </strong><br>' : '') . 'Subject: <strong>' . $proposal->subject . '</strong>
</div>
    <br><br><strong>Dear Sir,</strong><br><br>We thank you for the kind courtesy extended to us. With reference to your drawings received and our visit, we are pleased to submit our proposal.

<br>
    <ul style="line-height:14px;">
       <li>Annexure I : Priced BOQ for Subject Work<br></li>
       <li>Annexure II : Terms & Conditions<br></li>
       <li>Annexure III : Bank Details</li>
    </ul> 

    We hope you shall find our offer in line with your requirement. However,
    in case you require any clarification, please feel free to contact us.

    <br><br>We look forward to receiving your prestigious order and continuing our
    excellent relationship with you.

    <br><br>Thanking you & assuring you of our best at all times.
<div style="line-height:1.7;">
    <br>Very Truly Yours,<br><strong>' . get_option('companyname') . '</strong><br><br><strong>' . $assigned_staff_name . '</strong> <br><strong>Mobile No. ' . $assigned_staff_phone . '</strong>
</div>
</div>
';

$pdf->writeHTML($page1_html, true, false, true, false, '');
// render_fixed_footer($pdf);

/* ITEMS TABLE */
$pdf->AddPage(); // Annexure I - items/BOQ
$pdf->SetLeftMargin(20);

$items_heading_html = '
<h3 style="text-align:center; ">
ANNEXURE – I : PRICED BOQ FOR SUBJECT WORK<br><br>
</h3>
';
$pdf->writeHTML($items_heading_html, true, false, true, false, '');

$items = get_items_table_data($proposal, 'proposal', 'pdf')
    ->set_headings('estimate');

// Wrap items table with larger font size
$items_html = '<div style="font-size:' . ($font_size + 6) . 'px;">';
$items_html .= $items->table();
$items_html .= '</div>';
$items_html .= '<br><br>';

$items_html .= '<table cellpadding="6" style="font-size:' . ($font_size + 6) . 'px;">';

$items_html .= '
<tr>
    <td width="85%" align="right"><strong>' . _l('estimate_subtotal') . '</strong></td>
    <td width="15%" align="right">' . app_format_money($proposal->subtotal, $proposal->currency_name) . '</td>
</tr>';

if (is_sale_discount_applied($proposal)) {
    $items_html .= '
    <tr>
        <td align="right"><strong>' . _l('estimate_discount') . '</strong></td>
        <td align="right">-' . app_format_money($proposal->discount_total, $proposal->currency_name) . '</td>
    </tr>';
}

foreach ($items->taxes() as $tax) {
    $items_html .= '
    <tr>
        <td align="right"><strong>' . $tax['taxname'] . ' (' . app_format_number($tax['taxrate']) . '%)</strong></td>
        <td align="right">' . app_format_money($tax['total_tax'], $proposal->currency_name) . '</td>
    </tr>';
}

if ((int)$proposal->adjustment != 0) {
    $items_html .= '
    <tr>
        <td align="right"><strong>' . _l('estimate_adjustment') . '</strong></td>
        <td align="right">' . app_format_money($proposal->adjustment, $proposal->currency_name) . '</td>
    </tr>';
}

$items_html .= '
<tr style="background-color:#f0f0f0;">
    <td align="right"><strong>' . _l('estimate_total') . '</strong></td>
    <td align="right">' . app_format_money($proposal->total, $proposal->currency_name) . '</td>
</tr>';

$items_html .= '</table>';

if (get_option('total_to_words_enabled') == 1) {
    $items_html .= '<br><br><div style="font-size:' . ($font_size + 6) . 'px;"><strong>' .
        _l('num_word') . ': ' .
        $CI->numberword->convert($proposal->total, $proposal->currency_name) .
        '</strong></div>';
}

$pdf->writeHTML($items_html, true, false, true, false, '');

// Custom field: Payment Terms (slug: proposal_payment_terms)
$payment_terms = trim((string) get_custom_field_value($proposal->id, 'proposal_payment_terms', 'proposal'));
if ($payment_terms !== '') {
    $payment_terms_html = '
    <h3 style="margin-top:10px; font-size:18px;">Payment Terms</h3>
    <div style="font-size:14px; line-height:1.6;">' . nl2br($payment_terms) . '</div>
    ';
    $pdf->writeHTML($payment_terms_html, true, false, true, false, '');
}

/* =================================================
   PAGE 3 : TERMS & CONDITIONS + BANK DETAILS
================================================= */
$pdf->AddPage();
$pdf->SetLeftMargin(20);

$page3_html = '
<h3 style="text-align:center; ">
ANNEXURE – II : TERMS & CONDITIONS<br> <br>
</h3> 
<div style="font-size:16px; color:#000;">
<p style="line-height:0px"><strong>1. SCOPE OF WORKS & BOQ MODEL:</strong></p>
<ul style="margin-bottom:20px; line-height:24px;">
<li>The scope of works shall be as described in the schedule of quantities mentioned in the BOQ &
mutually agreed terms & conditions. We have quoted our prices as per BOQ attached.</li>
<li>Quantities mentioned in the BOQ are based on measurable items and our contract value shall be
based on the final measurement of individual BOQ items.</li>
<li>Our scope of work is limited to items mentioned in BOQ only, any other additional work shall be
carried out on further chargeable basis.</li><br> <br>
</ul>

<p style="line-height:0px"><strong>2. TAXES:</strong></p>
<ul style="line-height:24px;">
<li>GST @ 18% shall be applicable on the supply of unit only and GST @ 18% on installation works.</li>
<li>Any statutory variation in taxes shall be applicable accordingly.</li><br> <br>
</ul>


<p style="line-height:0px"><strong>3. ORDERING PROCEDURE:</strong></p>
<ul style="line-height:24px;">
<li>SITC order shall be placed on Lumifog Private Limited.</li><br> <br>
</ul>

<p style="line-height:0px"><strong>4. PAYMENT TERMS:</strong></p>
<ul style="line-height:24px;">
    <li>We hereby request for the following Payment Terms:</li>
    <li><u>Supply of high side equipment’s:</u>
        <ul>
            <li>100% Advance along with the purchase order</li>
        </ul>
    </li>
    <li><u>Low side works:</u>
        <ul>
            <li>70% against before work Start</li>
            <li>25% against pro-rata installation</li>
            <li>5% against Testing and Commissioning within one week</li>
        </ul>
    </li>
    <br><br>
</ul>

<p style="line-height:0px"><strong>5. PROJECT COMPLETION:</strong></p>
<ul style="line-height:24px;">
    <li>As per the mutually agreed project schedule.</li><br><br>
</ul>

<p style="line-height:0px"><strong>6. PRICE VALIDITY:</strong></p>
<ul style="line-height:24px;">
<li>Quoted prices are valid for 10 days from the date of submission of this offer.</li><br> <br> <br>
</ul>

<p style="line-height:0px"><strong>7. PROJECT HANDOVER:</strong></p>
<ul style="line-height:24px;">
<li>As per agreed schedule.</li><br> <br>
</ul>

<p style="line-height:0px"><strong>8. DEFECTS LIABILITY PERIOD:</strong></p>
<ul style="line-height:24px;">
<li>12 months from the date of commissioning or 15 months from the date of supply,
whichever occurs earlier.</li><br> <br>
</ul>

<p style="line-height:0px"><strong>9. SITE OFFICE & STORAGE SPACE:</strong></p>
<ul style="line-height:24px;">
<li>Covered site office space complete with electrical/telephonic facilities shall be provided
free of cost.</li>
<li>A separate covered storage space shall be provided for stacking/storage of equipment
and low side material free of cost. Space requirement will be indicated later.</li><br> <br>
</ul>


<p style="line-height:0px"><strong>10. WATER & ELECTRICITY:</strong></p>
<ul style="line-height:24px;">
<li>Water & electricity for installation works shall be provided at one point on each apartment
on FOC basis.</li>
<li>For testing & commissioning, 3-phase power supply shall be provided on FOC basis.</li> <br> <br>
</ul>



<p style="line-height:0px"><strong>11. EXCLUSIONS FOR INSTALLATION JOBS:</strong></p>
<ul style="line-height:24px;">
<li>All masonry/civil works such as supports for condensing units, RCC/PCC cutting,
trenches, lifting, shifting and handling of material.</li>
<li>Supply and installation of main power supply (3-phase / 415V / 50Hz with neutral),
isolating MCB/MCCB & ELCB at outdoor units and power supply near indoor units,
including cabling and terminations.</li>
<li>Drain points arrangement by civil contractor. Scaffolding for height above 3.5 meters
shall be charged extra.</li>
<li>False ceiling works, boxing work, wooden framework, carpentry work.</li>
<li>Under-deck or over-deck insulation, thermal or acoustic insulation works.</li>
<li>Stable and uninterrupted power supply for erection, testing & commissioning.</li>
<li>Any dismantling work or any item not specifically mentioned in this offer.</li><br>
</ul>


<p style="line-height:0px"><strong>NOTE:</strong></p>
<ul style="line-height:24px;">
<li>3-phase power supply is mandatory for outdoor units. In case of phase reversal or phase
unavailability, the unit shall not function.</li>
<li>Phase sequencer installation is recommended in case of phase reversal issues.</li>
<li>Power supply to indoor & outdoor units shall remain always ON. ON/OFF operation
shall be through remote controller only.</li>
<li>Commissioning will be done only on permanent and stable power supply.</li>
</ul>
</div>
';

$pdf->writeHTML($page3_html, true, false, true, false, '');

// Bank Details section - always start on new page
$pdf->AddPage();
$pdf->SetLeftMargin(20);

$bank_details_html = '
<h3 style="text-align:center; border-bottom:0px solid #000;">
<br>ANNEXURE – III: <> BANK DETAILS
</h3>

<table border="1" cellpadding="6" width="100%" style="font-size:16px;">
<tr>
    <td width="35%"><b>Name of Firm</b></td>
    <td width="65%">LUMIFOG PRIVATE LIMITED</td>
</tr>
<tr>
    <td><b>Bank Name</b></td>
    <td>HDFC BANK LTD</td>
</tr>
<tr>
    <td><b>Bank Address</b></td>
    <td>Unit No.1 Town Centre, Andheri Kurla Rd, Near Kobe Sizzlers,
    Andheri East, Mumbai – 400059, Maharashtra</td>
</tr>
<tr>
    <td><b>Branch</b></td>
    <td>ANDHERI EAST</td>
</tr>
<tr>
    <td><b>Account No</b></td>
    <td>50200096015459</td>
</tr>
<tr>
    <td><b>IFSC Code</b></td>
    <td>HDFC0000592</td>
</tr>
<tr>
    <td><b>GST No</b></td>
    <td>27AAFCL7466D1ZC</td>
</tr>
</table>
';

$pdf->writeHTML($bank_details_html, true, false, true, false, '');
// render_fixed_footer($pdf);
