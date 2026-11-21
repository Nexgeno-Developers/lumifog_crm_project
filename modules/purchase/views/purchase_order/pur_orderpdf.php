<?php defined('BASEPATH') or exit('No direct script access allowed');

$terms_marker = '<!--PUR_TERMS_PAGE-->';

if (strpos($pur_order, $terms_marker) !== false && method_exists($pdf, 'pur_start_terms_page')) {
    // Write the PO, then the Terms & Conditions on their own page. pur_start_terms_page()
    // avoids a blank page when the PO body ends exactly at the bottom of a page.
    [$po_body, $po_terms] = explode($terms_marker, $pur_order, 2);

    // Stylesheet <link>s sit at the end of the HTML; give both halves the same styles.
    $po_styles = preg_match_all('/<link\b[^>]*>/i', $pur_order, $m) ? implode('', $m[0]) : '';

    $pdf->writeHTML('<div class="div_pdf">' . $po_styles . $po_body . '</div>', false, false, true, false, '');

    $pdf->pur_start_terms_page();

    $pdf->writeHTML('<div class="div_pdf">' . $po_styles . $po_terms . '</div>', true, false, true, false, '');
} else {
// Theese lines should aways at the end of the document left side. Dont indent these lines
$html = <<<EOF
<div class="div_pdf">
$pur_order
</div>
EOF;

$pdf->writeHTML($html, true, false, true, false, '');
}
