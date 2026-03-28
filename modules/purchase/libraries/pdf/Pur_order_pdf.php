<?php

defined('BASEPATH') or exit('No direct script access allowed');

include_once(APPPATH . 'libraries/pdf/App_pdf.php');

class Pur_order_pdf extends App_pdf
{
    protected $pur_order;

    public function __construct($pur_order)
    {
        $pur_order                = hooks()->apply_filters('request_html_pdf_data', $pur_order);
        $GLOBALS['pur_order_pdf'] = $pur_order;

        parent::__construct();
        
        // Increase top margin to prevent overlap
        $this->SetMargins(15, 40, 15);
        
        $this->setPrintHeader(true);
        $this->setPrintFooter(true);

        $this->pur_order = $pur_order;

        $this->SetTitle(_l('pur_order'));
        # Don't remove these lines - important for the PDF layout
        $this->pur_order = $this->fix_editor_html($this->pur_order);
        
        // Ensure proper page breaks
        $this->SetAutoPageBreak(true, 30);
    }

    public function prepare()
    {
        $this->set_view_vars('pur_order', $this->pur_order);

        return $this->build();
    }

    protected function type()
    {
        return 'pur_order';
    }

    protected function file_path()
    {
        $customPath = APPPATH . 'views/themes/' . active_clients_theme() . '/views/my_requestpdf.php';
        $actualPath = APP_MODULES_PATH . '/purchase/views/purchase_order/pur_orderpdf.php';

        if (file_exists($customPath)) {
            $actualPath = $customPath;
        }

        return $actualPath;
    }
    
    public function Header()
    {
        $logo = FCPATH . 'uploads/company/lumifog-logo.webp';
    
        if (file_exists($logo)) {
            // Logo on left side - 90px height (approximately 23.81mm at 96 DPI)
            // Using 0 for width to maintain aspect ratio automatically
            $this->Image($logo, 15, 10, 0, 23.81);
        }
        
        // Move content down to prevent overlap with logo
        // Adjusted from 45 to 40 for 90px logo
        $this->SetY(40);
        
        // Add watermark on every page
        $this->addWatermark();
    }
    
    protected function addWatermark()
    {
        $watermarkLogo = FCPATH . 'uploads/company/water-mark.webp';
        
        if (file_exists($watermarkLogo)) {
            // Get page dimensions (in mm)
            $pageWidth = $this->getPageWidth();    // Total page width in mm
            $pageHeight = $this->getPageHeight();  // Total page height in mm
            
            // Get margins
            $margins = $this->getMargins();
            $leftMargin = $margins['left'];
            $rightMargin = $margins['right'];
            $topMargin = $margins['top'];
            $bottomMargin = $margins['bottom'];
            
            // Calculate usable area (inside margins)
            $usableWidth = $pageWidth - $leftMargin - $rightMargin;
            $usableHeight = $pageHeight - $topMargin - $bottomMargin;
            
            // Desired watermark size (200px ≈ 52.92mm at 96 DPI)
            $desiredHeightMM = 52.92; // Equivalent to 200px
            
            // Try to get image info to maintain aspect ratio
            $imageInfo = @getimagesize($watermarkLogo);
            if ($imageInfo) {
                $imgWidth = $imageInfo[0];
                $imgHeight = $imageInfo[1];
                $aspectRatio = $imgWidth / $imgHeight;
                $desiredWidthMM = $desiredHeightMM * $aspectRatio;
            } else {
                // Fallback if can't read image
                $desiredWidthMM = $desiredHeightMM * 1.5; // Assume 3:2 aspect ratio
            }
            
            // Ensure watermark doesn't exceed usable area
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
            
            // Calculate center position within usable area
            $x = $leftMargin + ($usableWidth - $desiredWidthMM) / 2;
            $y = $topMargin + ($usableHeight - $desiredHeightMM) / 2;
            
            // Save current auto-page-break status
            $autoPageBreak = $this->AutoPageBreak;
            $this->SetAutoPageBreak(false);
            
            // Set opacity for watermark to 100% (fully visible)
            $this->SetAlpha(1.0); // 100% opacity
            
            // Add watermark image
            $this->Image(
                $watermarkLogo, 
                $x, 
                $y, 
                $desiredWidthMM, 
                $desiredHeightMM,
                '',      // image type (auto-detect)
                '',      // link
                '',      // align
                false,   // resize
                96,      // dpi
                '',      // palette
                false,   // isMask
                false,   // maskImg
                0,       // border
                false,   // fitbox
                false,   // hidden
                true     // fitOnPage
            );
            
            // No need to reset opacity since it's already 1.0, but keep for consistency
            $this->SetAlpha(1);
            
            // Restore auto-page-break status
            $this->SetAutoPageBreak($autoPageBreak, 30);
        }
    }

    public function Footer()
    {
        $pageWidth = $this->getPageWidth();
        $margin = $this->getMargins()['left'];
    
        $phoneIcon = FCPATH . 'uploads/company/telephone.png';
        $emailIcon = FCPATH . 'uploads/company/email.png';
        $webIcon   = FCPATH . 'uploads/company/web.png';
    
        $this->SetY(-23);
        $this->SetFont('helvetica', '', 8);
    
        $colWidth = ($pageWidth - $margin * 2) / 3;
    
        $this->Image($phoneIcon, $margin + 2, $this->GetY() + 1, 4);
        $this->SetXY($margin + 7, $this->GetY());
        $this->Cell($colWidth - 8, 5, '9372348997', 0, 0, 'L');
    
        $centerX = $margin + $colWidth;
        $this->Image($emailIcon, $centerX + ($colWidth/2) - 18, $this->GetY() + 1, 4);
        $this->SetXY($centerX + ($colWidth/2) - 13, $this->GetY());
        $this->Cell(22, 5, 'info@lumifog.com', 0, 0, 'C');
    
        $rightX = $margin + $colWidth * 2;
        $this->Image($webIcon, $rightX + $colWidth - 28, $this->GetY() + 1, 4);
        $this->SetXY($rightX + $colWidth - 22, $this->GetY());
        $this->Cell(26, 5, 'www.lumifog.com', 0, 1, 'L');
    
        $this->Ln(1);
        $this->Line($margin, $this->GetY(), $pageWidth - $margin, $this->GetY());
    
        $this->Ln(2);
        $this->SetFont('helvetica', '', 7.5);
        $this->Cell(0, 4, 'Unit No. F-48, First Floor kohinoor City Mall, Opp Holly Cross School, Kurla (W) Mumbai, Maharashtra - 400070.', 0, 1, 'C');
    
        $this->Cell(0, 4, 'GSTIN: 27AAFCL7466D1ZC', 0, 1, 'C');
        
        // Add page number - placed at the very bottom right
        $this->SetY(-10);
        $this->SetX(-20);
        $this->SetFont('helvetica', '', 8);
        $this->Cell(0, 5, 'Page ' . $this->getAliasNumPage() . '/' . $this->getAliasNbPages(), 0, 0, 'R');
    }
}