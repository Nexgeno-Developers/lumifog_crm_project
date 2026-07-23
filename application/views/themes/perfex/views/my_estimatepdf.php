<?php

defined('BASEPATH') or exit('No direct script access allowed');

$dimensions = $pdf->getPageDimensions();

// Border style
$border_style = 'border:1px solid #000000;';

// Get estimate items
$estimate_items = get_items_table_data($estimate, 'estimate', 'pdf');
$item_counter   = 1;
$total_items    = count($estimate->items);
// If items <= 5, show 5 rows per page, else show 12 rows per page
$items_per_page = ($total_items <= 5) ? 5 : 12;

// Store total items count for footer logic
$pdfId = spl_object_id($pdf);
$GLOBALS['estimate_pdf_total_items_' . $pdfId] = $total_items;

// Start items table immediately after header with a small fixed spacer (~5pt / 2mm)
if (isset($GLOBALS['header_end_y_' . $pdfId])) {
    $headerEndY = (float) $GLOBALS['header_end_y_' . $pdfId];
    // Prefer live cursor when it is still at the header end (relative placement)
    $liveY = $pdf->GetY();
    if ($liveY > 0 && abs($liveY - $headerEndY) < 20) {
        $headerEndY = min($headerEndY, $liveY);
    }
    $itemsStartY = $headerEndY + 2;
    $pdf->SetY($itemsStartY);
    $pdf->SetMargins(
        $pdf->getMargins()['left'],
        $itemsStartY,
        $pdf->getMargins()['right']
    );
}

// Content width
$contentWidth = isset($GLOBALS['content_width_' . $pdfId])
    ? $GLOBALS['content_width_' . $pdfId]
    : ($dimensions['wk'] - ($dimensions['rm'] + $dimensions['lm']));

// Helper: get taxes for estimate item
if (!function_exists('_my_get_estimate_item_taxes')) {
    function _my_get_estimate_item_taxes($item_id)
    {
        if (function_exists('get_estimate_item_taxes')) {
            return get_estimate_item_taxes($item_id);
        }

        if (function_exists('get_invoice_item_taxes')) {
            return get_invoice_item_taxes($item_id);
        }

        return [];
    }
}

/*
|--------------------------------------------------------------------------
| ITEMS TABLE - FIXED ROWS PER PAGE
|--------------------------------------------------------------------------
*/
$item_chunks = array_chunk($estimate->items, $items_per_page);

foreach ($item_chunks as $chunk_index => $items_chunk) {
    $items_table = '<table width="100%" cellspacing="0" cellpadding="4" border="1" style="' . $border_style . ' margin-top:0 !important;">
    <thead>
        <tr style="background-color:#f0f0f0;">
            <th width="6%" style="' . $border_style . ' text-align:center;"><strong>Sr No.</strong></th>
            <th width="30%" style="' . $border_style . '"><strong>Description of Services</strong></th>
            <th width="10%" style="' . $border_style . ' text-align:center;"><strong>HSN/SAC</strong></th>
            <th width="10%" style="' . $border_style . ' text-align:center;"><strong>GST Rate</strong></th>
            <th width="12%" style="' . $border_style . ' text-align:center;"><strong>Quantity</strong></th>
            <th width="10%" style="' . $border_style . ' text-align:right;"><strong>Rate</strong></th>
            <th width="10%" style="' . $border_style . ' text-align:center;"><strong>per</strong></th>
            <th width="12%" style="' . $border_style . ' text-align:right;"><strong>Amount</strong></th>
        </tr>
    </thead>
    <tbody>';

    foreach ($items_chunk as $item) {
        $item_gst_rate_total   = 0;
        $item_gst_rate_display = '0%';

        $item_taxes = [];
        if (isset($item['id'])) {
            $item_taxes = _my_get_estimate_item_taxes($item['id']);
        }

        if (is_array($item_taxes) && count($item_taxes) > 0) {
            foreach ($item_taxes as $t) {
                $tax_name_only = '';
                $tax_rate      = 0;

                if (isset($t['taxname'])) {
                    $tmp           = explode('|', $t['taxname']);
                    $tax_name_only = trim($tmp[0] ?? '');
                    $tax_rate      = (float) ($tmp[1] ?? 0);
                } elseif (isset($t['taxrate'])) {
                    $tax_rate = (float) $t['taxrate'];
                }

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

        $description      = $item['description'] ?? '';
        $long_description = html_entity_decode($item['long_description'] ?? '', ENT_QUOTES, 'UTF-8');

        // Convert saved <br>, <br/>, <br /> into actual new lines first
        $long_description = preg_replace('/<br\s*\/?>/i', "\n", $long_description);

        $description_html = htmlspecialchars($description);

        if (!empty(trim($long_description))) {
            $description_html .= '<br>' . nl2br(htmlspecialchars($long_description));
        }

        $items_table .= '<tr style="height:15mm;">
        <td width="6%" style="' . $border_style . ' text-align:center; vertical-align:middle; height:15mm;">' . $item_counter . '</td>
        <td width="30%" style="' . $border_style . ' vertical-align:middle; height:15mm;">' . $description_html . '</td>
        <td width="10%" style="' . $border_style . ' text-align:center; vertical-align:middle; height:15mm;">' . (!empty($item['hsn_sac']) ? htmlspecialchars($item['hsn_sac']) : '') . '</td>
        <td width="10%" style="' . $border_style . ' text-align:center; vertical-align:middle; height:15mm;">' . $item_gst_rate_display . '</td>
        <td width="12%" style="' . $border_style . ' text-align:center; vertical-align:middle; height:15mm;">' . $item['qty'] . ' Nos</td>
        <td width="10%" style="' . $border_style . ' text-align:right; vertical-align:middle; height:15mm;">' . app_format_number($item['rate']) . '</td>
        <td width="10%" style="' . $border_style . ' text-align:center; vertical-align:middle; height:15mm;">Nos</td>
        <td width="12%" style="' . $border_style . ' text-align:right; vertical-align:middle; height:15mm;">' . app_format_money($item['qty'] * $item['rate'], $estimate->currency_name) . '</td>
    </tr>';

        $item_counter++;
    }

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

    $items_table .= '</tbody></table>';

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

    if ($chunk_index < count($item_chunks) - 1) {
        $pdf->AddPage();
    }
}

/*
|--------------------------------------------------------------------------
| TAX CALCULATIONS
|--------------------------------------------------------------------------
*/
$cgst_amount = 0;
$sgst_amount = 0;
$igst_amount = 0;

$cgst_rate = 0;
$sgst_rate = 0;
$igst_rate = 0;

$taxes    = $estimate_items->taxes();
$has_igst = false;
$has_cgst = false;
$has_sgst = false;

foreach ($taxes as $tax) {
    $tax_rate = isset($tax['taxrate']) ? (float) $tax['taxrate'] : 0;

    if (stripos($tax['taxname'], 'IGST') !== false) {
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

$is_mixed_gst = $has_igst && ($has_cgst || $has_sgst);

if (!$has_igst && $cgst_amount == 0 && $sgst_amount == 0 && count($taxes) > 0) {
    $total_tax = 0;
    foreach ($taxes as $tax) {
        $total_tax += $tax['total_tax'];
    }

    $cgst_amount = $total_tax / 2;
    $sgst_amount = $total_tax / 2;

    if ($cgst_rate == 0) {
        $cgst_rate = 9;
    }
    if ($sgst_rate == 0) {
        $sgst_rate = 9;
    }
}

/*
|--------------------------------------------------------------------------
| TAX SUMMARY TABLE
|--------------------------------------------------------------------------
*/
if ($total_items > 5) {
    $pdf->AddPage();
}

$estimate_total_in_words = strtoupper($CI->numberword->convert($estimate->total, $estimate->currency_name));

// Total of item amounts (taxable value) shown before the tax rows
$items_amount_total = 0;
foreach ($estimate->items as $item) {
    $items_amount_total += $item['qty'] * $item['rate'];
}

$amount_total_row = '<tr>
    <td colspan="7" style="' . $border_style . ' text-align:right;"><strong>Amount</strong></td>
    <td style="' . $border_style . ' text-align:right;"><strong>' . app_format_money($items_amount_total, $estimate->currency_name) . '</strong></td>
</tr>';

$tax_summary_table = '<table width="100%" cellspacing="0" cellpadding="4" border="1" style="' . $border_style . '"><tbody>';
$tax_summary_table .= $amount_total_row;

if ($is_mixed_gst) {
    if ($cgst_amount != 0) {
        $tax_summary_table .= '<tr>
            <td colspan="7" style="' . $border_style . ' text-align:right;"><strong>CGST</strong></td>
            <td style="' . $border_style . ' text-align:right;">' . app_format_money($cgst_amount, $estimate->currency_name) . '</td>
        </tr>';
    }

    if ($sgst_amount != 0) {
        $tax_summary_table .= '<tr>
            <td colspan="7" style="' . $border_style . ' text-align:right;"><strong>SGST</strong></td>
            <td style="' . $border_style . ' text-align:right;">' . app_format_money($sgst_amount, $estimate->currency_name) . '</td>
        </tr>';
    }

    if ($igst_amount != 0) {
        $tax_summary_table .= '<tr>
            <td colspan="7" style="' . $border_style . ' text-align:right;"><strong>IGST</strong></td>
            <td style="' . $border_style . ' text-align:right;">' . app_format_money($igst_amount, $estimate->currency_name) . '</td>
        </tr>';
    }

    $tax_summary_table .= '<tr>
        <td colspan="4" style="' . $border_style . '"><strong>Total</strong></td>
        <td style="' . $border_style . ' text-align:center;"><strong>' . array_sum(array_column($estimate->items, 'qty')) . ' Nos</strong></td>
        <td colspan="2" style="' . $border_style . '">&nbsp;</td>
        <td style="' . $border_style . ' text-align:right;"><strong>' . app_format_money($estimate->total, $estimate->currency_name) . '</strong></td>
    </tr>
    <tr>
        <td colspan="8" style="' . $border_style . '"><strong>Amount (in words):</strong> ' . $estimate_total_in_words . '</td>
    </tr>';
} elseif ($has_igst) {
    $tax_summary_table .= '<tr>
        <td colspan="7" style="' . $border_style . ' text-align:right;"><strong>IGST</strong></td>
        <td style="' . $border_style . ' text-align:right;">' . app_format_money($igst_amount, $estimate->currency_name) . '</td>
    </tr>
    <tr>
        <td colspan="4" style="' . $border_style . '"><strong>Total</strong></td>
        <td style="' . $border_style . ' text-align:center;"><strong>' . array_sum(array_column($estimate->items, 'qty')) . ' Nos</strong></td>
        <td colspan="2" style="' . $border_style . '">&nbsp;</td>
        <td style="' . $border_style . ' text-align:right;"><strong>' . app_format_money($estimate->total, $estimate->currency_name) . '</strong></td>
    </tr>
    <tr>
        <td colspan="8" style="' . $border_style . '"><strong>Amount (in words):</strong> ' . $estimate_total_in_words . '</td>
    </tr>';
} else {
    $tax_summary_table .= '<tr>
        <td colspan="7" style="' . $border_style . ' text-align:right;"><strong>CGST</strong></td>
        <td style="' . $border_style . ' text-align:right;">' . app_format_money($cgst_amount, $estimate->currency_name) . '</td>
    </tr>
    <tr>
        <td colspan="7" style="' . $border_style . ' text-align:right;"><strong>SGST</strong></td>
        <td style="' . $border_style . ' text-align:right;">' . app_format_money($sgst_amount, $estimate->currency_name) . '</td>
    </tr>
    <tr>
        <td colspan="4" style="' . $border_style . '"><strong>Total</strong></td>
        <td style="' . $border_style . ' text-align:center;"><strong>' . array_sum(array_column($estimate->items, 'qty')) . ' Nos</strong></td>
        <td colspan="2" style="' . $border_style . '">&nbsp;</td>
        <td style="' . $border_style . ' text-align:right;"><strong>' . app_format_money($estimate->total, $estimate->currency_name) . '</strong></td>
    </tr>
    <tr>
        <td colspan="8" style="' . $border_style . '"><strong>Total Amount (in words):</strong> ' . $estimate_total_in_words . '</td>
    </tr>';
}

$tax_summary_table .= '</tbody></table>';

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

/*
|--------------------------------------------------------------------------
| ITEM-WISE HSN/SAC GST SUMMARY TABLE
|--------------------------------------------------------------------------
*/
$tax_table = '<table width="100%" cellspacing="0" cellpadding="4" border="1" style="' . $border_style . '">
<thead>
    <tr style="background-color:#f0f0f0;">
        <th rowspan="2" style="' . $border_style . '"><strong>HSN/SAC</strong></th>
        <th rowspan="2" style="' . $border_style . '"><strong>Taxable<br/>Value</strong></th>';

if ($has_igst && !$is_mixed_gst) {
    $tax_table .= '<th colspan="2" style="' . $border_style . '"><strong>IGST</strong></th>';
} else {
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
    $tax_table .= '<th style="' . $border_style . '"><strong>Rate</strong></th>
        <th style="' . $border_style . '"><strong>Amount</strong></th>';
} else {
    $tax_table .= '<th style="' . $border_style . '"><strong>Rate</strong></th>
        <th style="' . $border_style . '"><strong>Amount</strong></th>
        <th style="' . $border_style . '"><strong>Rate</strong></th>
        <th style="' . $border_style . '"><strong>Amount</strong></th>';
    if ($is_mixed_gst) {
        $tax_table .= '<th style="' . $border_style . '"><strong>Rate</strong></th>
            <th style="' . $border_style . '"><strong>Amount</strong></th>';
    }
}

$tax_table .= '</tr></thead><tbody>';

$hsn_groups         = [];
$item_total_cgst    = 0;
$item_total_sgst    = 0;
$item_total_igst    = 0;
$item_total_taxable = 0;
$item_total_tax     = 0;

foreach ($estimate->items as $item) {
    $item_id  = $item['id'];
    $item_hsn = isset($item['hsn_sac']) && $item['hsn_sac'] !== '' ? trim($item['hsn_sac']) : '';

    if (empty($item_hsn)) {
        continue;
    }

    $item_qty           = $item['qty'];
    $item_rate          = $item['rate'];
    $item_taxable_value = $item_qty * $item_rate;

    $item_taxes       = _my_get_estimate_item_taxes($item_id);
    $has_gst          = false;
    $item_cgst_rate   = 0;
    $item_cgst_amount = 0;
    $item_sgst_rate   = 0;
    $item_sgst_amount = 0;
    $item_igst_rate   = 0;
    $item_igst_amount = 0;

    foreach ($item_taxes as $tax) {
        $tax_parts     = explode('|', $tax['taxname']);
        $tax_name_only = $tax_parts[0];
        $tax_rate      = isset($tax_parts[1]) ? (float) $tax_parts[1] : 0;

        if (stripos($tax_name_only, 'CGST') !== false) {
            $has_gst          = true;
            $item_cgst_rate   = $tax_rate;
            $item_cgst_amount = ($item_taxable_value * $tax_rate) / 100;
        } elseif (stripos($tax_name_only, 'SGST') !== false) {
            $has_gst          = true;
            $item_sgst_rate   = $tax_rate;
            $item_sgst_amount = ($item_taxable_value * $tax_rate) / 100;
        } elseif (stripos($tax_name_only, 'IGST') !== false) {
            $has_gst          = true;
            $item_igst_rate   = $tax_rate;
            $item_igst_amount = ($item_taxable_value * $tax_rate) / 100;
        }
    }

    if (!$has_gst) {
        continue;
    }

    if (!isset($hsn_groups[$item_hsn])) {
        $hsn_groups[$item_hsn] = [
            'hsn'           => htmlspecialchars($item_hsn),
            'taxable_value' => 0,
            'cgst_rate'     => $item_cgst_rate,
            'cgst_amount'   => 0,
            'sgst_rate'     => $item_sgst_rate,
            'sgst_amount'   => 0,
            'igst_rate'     => $item_igst_rate,
            'igst_amount'   => 0,
            'total_tax'     => 0,
        ];
    }

    $hsn_groups[$item_hsn]['taxable_value'] += $item_taxable_value;
    $hsn_groups[$item_hsn]['cgst_amount']   += $item_cgst_amount;
    $hsn_groups[$item_hsn]['sgst_amount']   += $item_sgst_amount;
    $hsn_groups[$item_hsn]['igst_amount']   += $item_igst_amount;
    $hsn_groups[$item_hsn]['total_tax']     += ($item_cgst_amount + $item_sgst_amount + $item_igst_amount);

    $item_total_taxable += $item_taxable_value;
    $item_total_cgst    += $item_cgst_amount;
    $item_total_sgst    += $item_sgst_amount;
    $item_total_igst    += $item_igst_amount;
    $item_total_tax     += ($item_cgst_amount + $item_sgst_amount + $item_igst_amount);
}

foreach ($hsn_groups as $hsn_data) {
    $tax_table .= '<tr>
        <td style="' . $border_style . ' text-align:center;">' . $hsn_data['hsn'] . '</td>
        <td style="' . $border_style . ' text-align:right;">' . app_format_money($hsn_data['taxable_value'], $estimate->currency_name) . '</td>';

    if ($has_igst && !$is_mixed_gst) {
        $tax_table .= '<td style="' . $border_style . ' text-align:center;">' . ($hsn_data['igst_rate'] > 0 ? app_format_number($hsn_data['igst_rate'], false) . '%' : '&nbsp;') . '</td>
            <td style="' . $border_style . ' text-align:right;">' . ($hsn_data['igst_amount'] > 0 ? app_format_money($hsn_data['igst_amount'], $estimate->currency_name) : '&nbsp;') . '</td>';
    } else {
        $tax_table .= '<td style="' . $border_style . ' text-align:center;">' . ($hsn_data['cgst_rate'] > 0 ? app_format_number($hsn_data['cgst_rate'], false) . '%' : '&nbsp;') . '</td>
            <td style="' . $border_style . ' text-align:right;">' . ($hsn_data['cgst_amount'] > 0 ? app_format_money($hsn_data['cgst_amount'], $estimate->currency_name) : '&nbsp;') . '</td>
            <td style="' . $border_style . ' text-align:center;">' . ($hsn_data['sgst_rate'] > 0 ? app_format_number($hsn_data['sgst_rate'], false) . '%' : '&nbsp;') . '</td>
            <td style="' . $border_style . ' text-align:right;">' . ($hsn_data['sgst_amount'] > 0 ? app_format_money($hsn_data['sgst_amount'], $estimate->currency_name) : '&nbsp;') . '</td>';

        if ($is_mixed_gst) {
            $tax_table .= '<td style="' . $border_style . ' text-align:center;">' . ($hsn_data['igst_rate'] > 0 ? app_format_number($hsn_data['igst_rate'], false) . '%' : '&nbsp;') . '</td>
                <td style="' . $border_style . ' text-align:right;">' . ($hsn_data['igst_amount'] > 0 ? app_format_money($hsn_data['igst_amount'], $estimate->currency_name) : '&nbsp;') . '</td>';
        }
    }

    $tax_table .= '<td style="' . $border_style . ' text-align:right;">' . app_format_money($hsn_data['total_tax'], $estimate->currency_name) . '</td>
    </tr>';
}

$tax_table .= '<tr>
    <td style="' . $border_style . '"><strong>Total</strong></td>
    <td style="' . $border_style . ' text-align:right;"><strong>' . app_format_money($item_total_taxable, $estimate->currency_name) . '</strong></td>';

if ($has_igst && !$is_mixed_gst) {
    $tax_table .= '<td style="' . $border_style . '">&nbsp;</td>
        <td style="' . $border_style . ' text-align:right;"><strong>' . ($item_total_igst > 0 ? app_format_money($item_total_igst, $estimate->currency_name) : '&nbsp;') . '</strong></td>';
} else {
    $tax_table .= '<td style="' . $border_style . '">&nbsp;</td>
        <td style="' . $border_style . ' text-align:right;"><strong>' . ($item_total_cgst > 0 ? app_format_money($item_total_cgst, $estimate->currency_name) : '&nbsp;') . '</strong></td>
        <td style="' . $border_style . '">&nbsp;</td>
        <td style="' . $border_style . ' text-align:right;"><strong>' . ($item_total_sgst > 0 ? app_format_money($item_total_sgst, $estimate->currency_name) : '&nbsp;') . '</strong></td>';

    if ($is_mixed_gst) {
        $tax_table .= '<td style="' . $border_style . '">&nbsp;</td>
            <td style="' . $border_style . ' text-align:right;"><strong>' . ($item_total_igst > 0 ? app_format_money($item_total_igst, $estimate->currency_name) : '&nbsp;') . '</strong></td>';
    }
}

$tax_table .= '<td style="' . $border_style . ' text-align:right;"><strong>' . app_format_money($item_total_tax, $estimate->currency_name) . '</strong></td>
</tr>
<tr>
    <td colspan="' . (($has_igst && !$is_mixed_gst) ? '5' : ($is_mixed_gst ? '9' : '7')) . '" style="' . $border_style . '">
        <strong>Tax Amount (in words):</strong> ' . strtoupper($CI->numberword->convert($item_total_tax, $estimate->currency_name)) . '
    </td>
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

// Mark completion — store content end Y so footer can sit relative to this block
// (App_pdf::Footer() calls SetY(-15) before the footer hook, wiping GetY())
$GLOBALS['estimate_pdf_content_complete_' . $pdfId] = true;
$GLOBALS['estimate_pdf_last_page_' . $pdfId]        = $pdf->getPage();
$GLOBALS['estimate_pdf_content_end_y_' . $pdfId]    = $pdf->GetY();
