<?php

defined('BASEPATH') or exit('No direct script access allowed');

$dimensions = $pdf->getPageDimensions();

// Border style for tables
$border_style = 'border:1px solid #000000;';
$cell_padding = 'padding:4px;';

// Get invoice items
$invoice_items = get_items_table_data($invoice, 'invoice', 'pdf');
$item_counter = 1;
$total_items = count($invoice->items);
// If items <= 5, show 5 rows per page, else show 12 rows per page
$items_per_page = ($total_items <= 5) ? 5 : 12;

// Store total items count for footer logic
$pdfId = spl_object_id($pdf);
$GLOBALS['invoice_pdf_total_items_' . $pdfId] = $total_items;

// Get Y position after header (stored by header hook)
if (isset($GLOBALS['header_end_y_' . $pdfId])) {
    // Set Y position to start items table immediately after header (no gap)
    $pdf->SetY($GLOBALS['header_end_y_' . $pdfId]);
    $pdf->SetMargins($pdf->getMargins()['left'], $GLOBALS['header_end_y_' . $pdfId], $pdf->getMargins()['right']);
}

// Get content width for consistent table widths (same as header)
$contentWidth = isset($GLOBALS['content_width_' . $pdfId]) ? $GLOBALS['content_width_' . $pdfId] : ($dimensions['wk'] - ($dimensions['rm'] + $dimensions['lm']));

// Split items into chunks based on items_per_page (5 if <=5 items, 12 if >5 items)
$item_chunks = array_chunk($invoice->items, $items_per_page);

// Process each chunk (page) of items
foreach ($item_chunks as $chunk_index => $items_chunk) {
    // Start new table for each chunk - no top margin to connect with header
    $items_table = '<table width="100%" cellspacing="0" cellpadding="4" border="1" style="' . $border_style . ' margin-top:0 !important;">
    <thead>
        <tr style="background-color:#f0f0f0;">
            <th width="6%" style="' . $border_style . '"><strong>Sr No.</strong></th>
            <th width="30%" style="' . $border_style . '"><strong>Description of Services</strong></th>
            <th width="10%" style="' . $border_style . '"><strong>HSN/SAC</strong></th>
            <th width="10%" style="' . $border_style . '"><strong>GST Rate</strong></th>
            <th width="12%" style="' . $border_style . '"><strong>Quantity</strong></th>
            <th width="10%" style="' . $border_style . '"><strong>Rate</strong></th>
            <th width="10%" style="' . $border_style . '"><strong>per</strong></th>
            <th width="12%" style="' . $border_style . '"><strong>Amount</strong></th>
        </tr>
    </thead>
    <tbody>';

    // Add actual items from current chunk
    foreach ($items_chunk as $item) {
        // Determine GST rate for this item dynamically (from item taxes / tbltaxes)
        $item_gst_rate_total   = 0;
        $item_gst_rate_display = '0%';

        if (function_exists('get_invoice_item_taxes') && isset($item['id'])) {
            $item_taxes = get_invoice_item_taxes($item['id']); // returns rows with taxname "NAME|RATE"
            if (is_array($item_taxes) && count($item_taxes) > 0) {
                foreach ($item_taxes as $t) {
                    $tax_name_only = '';
                    $tax_rate      = 0;

                    if (isset($t['taxname'])) {
                        $tmp          = explode('|', $t['taxname']);
                        $tax_name_only = trim($tmp[0] ?? '');
                        $tax_rate      = (float) ($tmp[1] ?? 0);
                    } elseif (isset($t['taxrate'])) {
                        $tax_rate = (float) $t['taxrate'];
                    }

                    // Prefer current rate from tbltaxes when possible
                    if ($tax_name_only !== '' && function_exists('get_tax_by_name')) {
                        $taxObj = get_tax_by_name($tax_name_only);
                        if ($taxObj && isset($taxObj->taxrate)) {
                            $tax_rate = (float) $taxObj->taxrate;
                        }
                    }

                    $item_gst_rate_total += $tax_rate;
                }

                $item_gst_rate_display = app_format_number($item_gst_rate_total, true) . '%';
            }
        }

        $items_table .= '<tr style="height:15mm;">
            <td width="6%" style="' . $border_style . ' text-align:center; vertical-align:middle; height:15mm;">' . $item_counter . '</td>
            <td width="30%" style="' . $border_style . ' vertical-align:middle; height:15mm;"><strong>' . $item['description'] . '</strong><br/>' . $item['long_description'] . '</td>
            <td width="10%" style="' . $border_style . ' text-align:center; vertical-align:middle; height:15mm;">' . (isset($item['hsn_sac']) && $item['hsn_sac'] !== '' ? htmlspecialchars($item['hsn_sac']) : '') . '</td>
            <td width="10%" style="' . $border_style . ' text-align:center; vertical-align:middle; height:15mm;">' . $item_gst_rate_display . '</td>
            <td width="12%" style="' . $border_style . ' text-align:center; vertical-align:middle; height:15mm;">' . $item['qty'] . ' Nos</td>
            <td width="10%" style="' . $border_style . ' text-align:right; vertical-align:middle; height:15mm;">' . app_format_number($item['rate']) . '</td>
            <td width="10%" style="' . $border_style . ' text-align:center; vertical-align:middle; height:15mm;">Nos</td>
            <td width="12%" style="' . $border_style . ' text-align:right; vertical-align:middle; height:15mm;">' . app_format_money($item['qty'] * $item['rate'], $invoice->currency_name) . '</td>
        </tr>';
        $item_counter++;
    }

    // Fill remaining rows to make exactly $items_per_page rows per page
    $rows_in_chunk = count($items_chunk);
    for ($i = $rows_in_chunk; $i < $items_per_page; $i++) {
        $items_table .= '<tr style="height:15mm;">
            <td style="' . $border_style . ' height:15mm; vertical-align:middle;">&nbsp;</td>
            <td style="' . $border_style . ' height:15mm; vertical-align:middle;">&nbsp;</td>
            <td style="' . $border_style . ' height:15mm; vertical-align:middle;">&nbsp;</td>
            <td style="' . $border_style . ' height:15mm; vertical-align:middle;">&nbsp;</td>
            <td style="' . $border_style . ' height:15mm; vertical-align:middle;">&nbsp;</td>
            <td style="' . $border_style . ' height:15mm; vertical-align:middle;">&nbsp;</td>
            <td style="' . $border_style . ' height:15mm; vertical-align:middle;">&nbsp;</td>
            <td style="' . $border_style . ' height:15mm; vertical-align:middle;">&nbsp;</td>
        </tr>';
    }

    $items_table .= '</tbody>
    </table>';

    // Write the table for this chunk with consistent width and alignment
    $pdf->SetX($pdf->getMargins()['left']);
    $pdf->writeHTMLCell(
        $contentWidth,
        '',
        '',
        '',
        $items_table,
        0,
        1,
        false,
        true,
        'L',
        true
    );
    $pdf->Ln(0);
    
    // Add page break if there are more chunks to process
    if ($chunk_index < count($item_chunks) - 1) {
        $pdf->AddPage();
    }
}

// Calculate taxes (CGST/SGST pair vs IGST alone)
$cgst_amount = 0;
$sgst_amount = 0;
$igst_amount = 0;

// Store tax rates if available
$cgst_rate = 0;
$sgst_rate = 0;
$igst_rate = 0;

$taxes    = $invoice_items->taxes();
$has_igst = false;
$has_cgst = false;
$has_sgst = false;

foreach ($taxes as $tax) {
    $tax_rate = isset($tax['taxrate']) ? (float) $tax['taxrate'] : 0;

    if (stripos($tax['taxname'], 'IGST') !== false) {
        // IGST – single tax
        $has_igst    = true;
        $igst_amount += $tax['total_tax'];
        if ($tax_rate > 0) {
            $igst_rate = $tax_rate;
        }
    } elseif (stripos($tax['taxname'], 'CGST') !== false) {
        $has_cgst    = true;
        $cgst_amount += $tax['total_tax'];
        if ($tax_rate > 0) {
            $cgst_rate = $tax_rate;
        }
    } elseif (stripos($tax['taxname'], 'SGST') !== false) {
        $has_sgst    = true;
        $sgst_amount += $tax['total_tax'];
        if ($tax_rate > 0) {
            $sgst_rate = $tax_rate;
        }
    }
}

// Fail-safe: mixed GST types on same invoice (some items IGST, others CGST/SGST)
$is_mixed_gst = $has_igst && ($has_cgst || $has_sgst);

// If no specific CGST/SGST and no IGST, split the tax equally
if (!$has_igst && $cgst_amount == 0 && $sgst_amount == 0 && count($taxes) > 0) {
    $total_tax = 0;
    foreach ($taxes as $tax) {
        $total_tax += $tax['total_tax'];
    }
    $cgst_amount = $total_tax / 2;
    $sgst_amount = $total_tax / 2;

    // If rate not found, assume 9% + 9% (=18%) as before
    if ($cgst_rate == 0) {
        $cgst_rate = 9;
    }
    if ($sgst_rate == 0) {
        $sgst_rate = 9;
    }
}

// Add tax summary and total on the last page only
$invoice_total_in_words = strtoupper($CI->numberword->convert($invoice->total, $invoice->currency_name));
$tax_summary_table = '<table width="100%" cellspacing="0" cellpadding="4" border="1" style="' . $border_style . '">
<tbody>';

// Tax summary rows
if ($is_mixed_gst) {
    // Show all taxes that exist to avoid incorrect breakup
    if ($cgst_amount != 0) {
        $tax_summary_table .= '<tr>
    <td colspan="7" style="' . $border_style . ' text-align:right;"><strong>CGST</strong></td>
    <td style="' . $border_style . ' text-align:right;">' . app_format_money($cgst_amount, $invoice->currency_name) . '</td>
</tr>';
    }
    if ($sgst_amount != 0) {
        $tax_summary_table .= '<tr>
    <td colspan="7" style="' . $border_style . ' text-align:right;"><strong>SGST</strong></td>
    <td style="' . $border_style . ' text-align:right;">' . app_format_money($sgst_amount, $invoice->currency_name) . '</td>
</tr>';
    }
    if ($igst_amount != 0) {
        $tax_summary_table .= '<tr>
    <td colspan="7" style="' . $border_style . ' text-align:right;"><strong>IGST</strong></td>
    <td style="' . $border_style . ' text-align:right;">' . app_format_money($igst_amount, $invoice->currency_name) . '</td>
</tr>';
    }
    $tax_summary_table .= '<tr>
    <td colspan="4" style="' . $border_style . '"><strong>Total</strong></td>
    <td style="' . $border_style . ' text-align:center;"><strong>' . array_sum(array_column($invoice->items, 'qty')) . ' Nos</strong></td>
    <td colspan="2" style="' . $border_style . '">&nbsp;</td>
    <td style="' . $border_style . ' text-align:right;"><strong>' . app_format_money($invoice->total, $invoice->currency_name) . '</strong></td>
</tr>
<tr>
    <td colspan="8" style="' . $border_style . '"><strong>Amount (in words):</strong> ' . $invoice_total_in_words . '</td>
</tr>';
} elseif ($has_igst) {
    // IGST only (no separate CGST/SGST rows)
    $tax_summary_table .= '<tr>
    <td colspan="7" style="' . $border_style . ' text-align:right;"><strong>IGST</strong></td>
    <td style="' . $border_style . ' text-align:right;">' . app_format_money($igst_amount, $invoice->currency_name) . '</td>
</tr>
<tr>
    <td colspan="4" style="' . $border_style . '"><strong>Total</strong></td>
    <td style="' . $border_style . ' text-align:center;"><strong>' . array_sum(array_column($invoice->items, 'qty')) . ' Nos</strong></td>
    <td colspan="2" style="' . $border_style . '">&nbsp;</td>
    <td style="' . $border_style . ' text-align:right;"><strong>' . app_format_money($invoice->total, $invoice->currency_name) . '</strong></td>
</tr>
<tr>
    <td colspan="8" style="' . $border_style . '"><strong>Amount (in words):</strong> ' . $invoice_total_in_words . '</td>
</tr>';
} else {
    // CGST + SGST pair
    $tax_summary_table .= '<tr>
    <td colspan="7" style="' . $border_style . ' text-align:right;"><strong>CGST</strong></td>
    <td style="' . $border_style . ' text-align:right;">' . app_format_money($cgst_amount, $invoice->currency_name) . '</td>
</tr>
<tr>
    <td colspan="7" style="' . $border_style . ' text-align:right;"><strong>SGST</strong></td>
    <td style="' . $border_style . ' text-align:right;">' . app_format_money($sgst_amount, $invoice->currency_name) . '</td>
</tr>
<tr>
    <td colspan="4" style="' . $border_style . '"><strong>Total</strong></td>
    <td style="' . $border_style . ' text-align:center;"><strong>' . array_sum(array_column($invoice->items, 'qty')) . ' Nos</strong></td>
    <td colspan="2" style="' . $border_style . '">&nbsp;</td>
    <td style="' . $border_style . ' text-align:right;"><strong>' . app_format_money($invoice->total, $invoice->currency_name) . '</strong></td>
</tr>
<tr>
    <td colspan="8" style="' . $border_style . '"><strong>Total Amount (in words):</strong> ' . $invoice_total_in_words . '</td>
</tr>';
}
$tax_summary_table .= '</tbody>
</table>';

// Add page break before tax calculations only if items exceed 5
if ($total_items > 5) {
    $pdf->AddPage();
}

$pdf->SetX($pdf->getMargins()['left']);
$pdf->writeHTMLCell(
    $contentWidth,
    '',
    '',
    '',
    $tax_summary_table,
    0,
    1,
    false,
    true,
    'L',
    true
);
$pdf->Ln(0);

// Item-wise HSN/SAC GST Summary Table
$tax_table = '<table width="100%" cellspacing="0" cellpadding="4" border="1" style="' . $border_style . '">
<thead>
    <tr style="background-color:#f0f0f0;">
        <th rowspan="2" style="' . $border_style . '"><strong>HSN/SAC</strong></th>
        <th rowspan="2" style="' . $border_style . '"><strong>Taxable<br/>Value</strong></th>';

if ($has_igst && !$is_mixed_gst) {
    // IGST-only layout
    $tax_table .= '<th colspan="2" style="' . $border_style . '"><strong>IGST</strong></th>';
} else {
    // CGST/SGST layout (including mixed GST)
    $tax_table .= '<th colspan="2" style="' . $border_style . '"><strong>CGST</strong></th>
        <th colspan="2" style="' . $border_style . '"><strong>SGST/UTGST</strong></th>';
    if ($is_mixed_gst) {
        $tax_table .= '<th colspan="2" style="' . $border_style . '"><strong>IGST</strong></th>';
    }
}

$tax_table .= '<th rowspan="2" style="' . $border_style . '"><strong>Total<br/>Tax Amount</strong></th>
    </tr>
    <tr style="background-color:#f0f0f0;">';

if ($has_igst && !$is_mixed_gst) {
    // IGST-only layout
    $tax_table .= '<th style="' . $border_style . '"><strong>Rate</strong></th>
        <th style="' . $border_style . '"><strong>Amount</strong></th>';
} else {
    // CGST/SGST layout
    $tax_table .= '<th style="' . $border_style . '"><strong>Rate</strong></th>
        <th style="' . $border_style . '"><strong>Amount</strong></th>
        <th style="' . $border_style . '"><strong>Rate</strong></th>
        <th style="' . $border_style . '"><strong>Amount</strong></th>';
    if ($is_mixed_gst) {
        $tax_table .= '<th style="' . $border_style . '"><strong>Rate</strong></th>
            <th style="' . $border_style . '"><strong>Amount</strong></th>';
    }
}

$tax_table .= '</tr>
</thead>
<tbody>';

// Group items by HSN code and aggregate values (only items with both HSN and GST)
$hsn_groups = [];
$item_total_cgst = 0;
$item_total_sgst = 0;
$item_total_igst = 0;
$item_total_taxable = 0;
$item_total_tax = 0;

foreach ($invoice->items as $item) {
    $item_id = $item['id'];
    $item_hsn = isset($item['hsn_sac']) && $item['hsn_sac'] !== '' ? trim($item['hsn_sac']) : '';
    
    // Skip items without HSN code
    if (empty($item_hsn)) {
        continue;
    }
    
    $item_qty = $item['qty'];
    $item_rate = $item['rate'];
    $item_taxable_value = $item_qty * $item_rate;

    // Get this item's specific taxes
    $item_taxes = get_invoice_item_taxes($item_id);
    $has_gst = false;
    $item_cgst_rate = 0;
    $item_cgst_amount = 0;
    $item_sgst_rate = 0;
    $item_sgst_amount = 0;
    $item_igst_rate = 0;
    $item_igst_amount = 0;

    foreach ($item_taxes as $tax) {
        $tax_parts = explode('|', $tax['taxname']);
        $tax_name_only = $tax_parts[0];
        $tax_rate = isset($tax_parts[1]) ? (float) $tax_parts[1] : 0;

        if (stripos($tax_name_only, 'CGST') !== false) {
            $has_gst = true;
            $item_cgst_rate = $tax_rate;
            $item_cgst_amount = ($item_taxable_value * $tax_rate) / 100;
        } elseif (stripos($tax_name_only, 'SGST') !== false) {
            $has_gst = true;
            $item_sgst_rate = $tax_rate;
            $item_sgst_amount = ($item_taxable_value * $tax_rate) / 100;
        } elseif (stripos($tax_name_only, 'IGST') !== false) {
            $has_gst = true;
            $item_igst_rate = $tax_rate;
            $item_igst_amount = ($item_taxable_value * $tax_rate) / 100;
        }
    }
    
    // Skip items without GST
    if (!$has_gst) {
        continue;
    }
    
    // Group by HSN code (aggregate if HSN already exists)
    if (!isset($hsn_groups[$item_hsn])) {
        $hsn_groups[$item_hsn] = [
            'hsn' => htmlspecialchars($item_hsn),
            'taxable_value' => 0,
            'cgst_rate' => $item_cgst_rate,
            'cgst_amount' => 0,
            'sgst_rate' => $item_sgst_rate,
            'sgst_amount' => 0,
            'igst_rate' => $item_igst_rate,
            'igst_amount' => 0,
            'total_tax' => 0
        ];
    }
    
    // Aggregate values for this HSN code
    $hsn_groups[$item_hsn]['taxable_value'] += $item_taxable_value;
    $hsn_groups[$item_hsn]['cgst_amount'] += $item_cgst_amount;
    $hsn_groups[$item_hsn]['sgst_amount'] += $item_sgst_amount;
    $hsn_groups[$item_hsn]['igst_amount'] += $item_igst_amount;
    $hsn_groups[$item_hsn]['total_tax'] += ($item_cgst_amount + $item_sgst_amount + $item_igst_amount);
    
    // Update totals
    $item_total_taxable += $item_taxable_value;
    $item_total_cgst += $item_cgst_amount;
    $item_total_sgst += $item_sgst_amount;
    $item_total_igst += $item_igst_amount;
    $item_total_tax += ($item_cgst_amount + $item_sgst_amount + $item_igst_amount);
}

// Display grouped HSN codes (each HSN appears only once)
foreach ($hsn_groups as $hsn_data) {
    // Add item row to table
    $tax_table .= '<tr>
        <td style="' . $border_style . ' text-align:center;">' . $hsn_data['hsn'] . '</td>
        <td style="' . $border_style . ' text-align:right;">' . app_format_money($hsn_data['taxable_value'], $invoice->currency_name) . '</td>';

    if ($has_igst && !$is_mixed_gst) {
        // IGST-only layout
        $tax_table .= '<td style="' . $border_style . ' text-align:center;">' . ($hsn_data['igst_rate'] > 0 ? app_format_number($hsn_data['igst_rate'], false) . '%' : '&nbsp;') . '</td>
            <td style="' . $border_style . ' text-align:right;">' . ($hsn_data['igst_amount'] > 0 ? app_format_money($hsn_data['igst_amount'], $invoice->currency_name) : '&nbsp;') . '</td>';
    } else {
        // CGST/SGST layout
        $tax_table .= '<td style="' . $border_style . ' text-align:center;">' . ($hsn_data['cgst_rate'] > 0 ? app_format_number($hsn_data['cgst_rate'], false) . '%' : '&nbsp;') . '</td>
            <td style="' . $border_style . ' text-align:right;">' . ($hsn_data['cgst_amount'] > 0 ? app_format_money($hsn_data['cgst_amount'], $invoice->currency_name) : '&nbsp;') . '</td>
            <td style="' . $border_style . ' text-align:center;">' . ($hsn_data['sgst_rate'] > 0 ? app_format_number($hsn_data['sgst_rate'], false) . '%' : '&nbsp;') . '</td>
            <td style="' . $border_style . ' text-align:right;">' . ($hsn_data['sgst_amount'] > 0 ? app_format_money($hsn_data['sgst_amount'], $invoice->currency_name) : '&nbsp;') . '</td>';
        if ($is_mixed_gst) {
            $tax_table .= '<td style="' . $border_style . ' text-align:center;">' . ($hsn_data['igst_rate'] > 0 ? app_format_number($hsn_data['igst_rate'], false) . '%' : '&nbsp;') . '</td>
                <td style="' . $border_style . ' text-align:right;">' . ($hsn_data['igst_amount'] > 0 ? app_format_money($hsn_data['igst_amount'], $invoice->currency_name) : '&nbsp;') . '</td>';
        }
    }

    $tax_table .= '<td style="' . $border_style . ' text-align:right;">' . app_format_money($hsn_data['total_tax'], $invoice->currency_name) . '</td>
    </tr>';
}

// Add tax breakdown totals row
$tax_table .= '<tr>
    <td style="' . $border_style . '"><strong>Total</strong></td>
    <td style="' . $border_style . ' text-align:right;"><strong>' . app_format_money($item_total_taxable, $invoice->currency_name) . '</strong></td>';

if ($has_igst && !$is_mixed_gst) {
    // IGST-only layout
    $tax_table .= '<td style="' . $border_style . '">&nbsp;</td>
        <td style="' . $border_style . ' text-align:right;"><strong>' . ($item_total_igst > 0 ? app_format_money($item_total_igst, $invoice->currency_name) : '&nbsp;') . '</strong></td>';
} else {
    // CGST/SGST layout
    $tax_table .= '<td style="' . $border_style . '">&nbsp;</td>
        <td style="' . $border_style . ' text-align:right;"><strong>' . ($item_total_cgst > 0 ? app_format_money($item_total_cgst, $invoice->currency_name) : '&nbsp;') . '</strong></td>
        <td style="' . $border_style . '">&nbsp;</td>
        <td style="' . $border_style . ' text-align:right;"><strong>' . ($item_total_sgst > 0 ? app_format_money($item_total_sgst, $invoice->currency_name) : '&nbsp;') . '</strong></td>';
    if ($is_mixed_gst) {
        $tax_table .= '<td style="' . $border_style . '">&nbsp;</td>
            <td style="' . $border_style . ' text-align:right;"><strong>' . ($item_total_igst > 0 ? app_format_money($item_total_igst, $invoice->currency_name) : '&nbsp;') . '</strong></td>';
    }
}

$tax_table .= '<td style="' . $border_style . ' text-align:right;"><strong>' . app_format_money($item_total_tax, $invoice->currency_name) . '</strong></td>
</tr>
<tr>
<td colspan="' . (($has_igst && !$is_mixed_gst) ? '5' : ($is_mixed_gst ? '9' : '7')) . '" style="' . $border_style . '"><strong>Tax Amount (in words):</strong> ' .
strtoupper($CI->numberword->convert($item_total_tax, $invoice->currency_name)) . '</td>
</tr>
</tbody>
</table>';

$pdf->SetX($pdf->getMargins()['left']);
$pdf->writeHTMLCell(
    $contentWidth,
    '',
    '',
    '',
    $tax_table,
    0,
    1,
    false,
    true,
    'L',
    true
);
$pdf->Ln(0);

// Mark that all content has been written and store the last page number
$GLOBALS['invoice_pdf_content_complete_' . $pdfId] = true;
$GLOBALS['invoice_pdf_last_page_' . $pdfId] = $pdf->getPage();

// Amount in words (second instance)
// $amount_words2 = '<table width="100%" cellspacing="0" cellpadding="4" border="1" style="' . $border_style . '">
// <tr>
//     <td style="' . $border_style . '"><strong>Tax Amount (in words):</strong> ' . 
//     strtoupper($CI->numberword->convert($cgst_amount + $sgst_amount, $invoice->currency_name)) . '</td>
// </tr>
// </table>';

// Bank details are now in footer hook - removed from here

// Footer
// $pdf->SetFont($font_name, 'I', $font_size - 2);
// $pdf->Cell(0, 0, 'This is a Computer Generated Invoice', 0, 1, 'C', 0, '', 0);
