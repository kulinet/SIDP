<?php

namespace App\Libraries;

use Dompdf\Dompdf;
use Dompdf\Options;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

class MonevPdfGenerator
{
    /**
     * Generate a PDF from HTML and save to disk
     *
     * @param string $html The HTML content
     * @param string $savePath Absolute path to save the PDF
     * @return bool True if saved successfully
     */
    public function generateAndSave(string $html, string $savePath): bool
    {
        $options = new Options();
        $options->set('isRemoteEnabled', true); // Allow loading external CSS/images if needed
        $options->set('defaultFont', 'Helvetica');
        
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        
        // Setup paper size and orientation
        $dompdf->setPaper('A4', 'portrait');
        
        // Render the HTML as PDF
        $dompdf->render();
        
        // Save to file
        $output = $dompdf->output();
        return file_put_contents($savePath, $output) !== false;
    }
    
    /**
     * Generate a QR Code as SVG string
     *
     * @param string $data Data to encode in QR
     * @return string SVG string of the QR code
     */
    public function generateQrCodeSvg(string $data): string
    {
        $renderer = new ImageRenderer(
            new RendererStyle(150),
            new SvgImageBackEnd()
        );
        $writer = new Writer($renderer);
        return $writer->writeString($data);
    }
    
    /**
     * Generate a deterministic Hash signature for the document
     *
     * @param array $periodData
     * @return string SHA256 Hash
     */
    public function generateDocumentHash(array $periodData): string
    {
        $salt = "MONEV_INFOAJIP_V2_";
        // Create a unique fingerprint based on crucial data that shouldn't change
        $fingerprint = $salt . 
                       $periodData['id'] . '_' .
                       $periodData['proyek_id'] . '_' .
                       $periodData['status'] . '_' .
                       ($periodData['skor_kinerja_total'] ?? '0') . '_' .
                       date('YmdHis');
                       
        return hash('sha256', $fingerprint);
    }
}
