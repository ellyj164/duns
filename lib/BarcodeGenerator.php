<?php
/**
 * Barcode Generator Library
 * Simple Code 39 and Code 128 barcode generation for document verification
 */

class BarcodeGenerator
{
    /**
     * Generate Code 39 barcode SVG
     * @param string $data The data to encode
     * @param int $width Width of each bar in pixels
     * @param int $height Height of the barcode in pixels
     * @return string SVG code for the barcode
     */
    public static function generateCode39SVG($data, $width = 2, $height = 50)
    {
        // Code 39 character set
        $code39 = [
            '0' => '101001101101', '1' => '110100101011', '2' => '101100101011',
            '3' => '110110010101', '4' => '101001101011', '5' => '110100110101',
            '6' => '101100110101', '7' => '101001011011', '8' => '110100101101',
            '9' => '101100101101', 'A' => '110101001011', 'B' => '101101001011',
            'C' => '110110100101', 'D' => '101011001011', 'E' => '110101100101',
            'F' => '101101100101', 'G' => '101010011011', 'H' => '110101001101',
            'I' => '101101001101', 'J' => '101011001101', 'K' => '110101010011',
            'L' => '101101010011', 'M' => '110110101001', 'N' => '101011010011',
            'O' => '110101101001', 'P' => '101101101001', 'Q' => '101010110011',
            'R' => '110101011001', 'S' => '101101011001', 'T' => '101011011001',
            'U' => '110010101011', 'V' => '100110101011', 'W' => '110011010101',
            'X' => '100101101011', 'Y' => '110010110101', 'Z' => '100110110101',
            '-' => '100101011011', '.' => '110010101101', ' ' => '100110101101',
            '*' => '100101101101' // Start/Stop character
        ];
        
        $data = strtoupper($data);
        $barcode = '*' . $data . '*'; // Add start/stop characters
        
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="' . (strlen($barcode) * 13 * $width) . '" height="' . ($height + 20) . '">';
        $svg .= '<rect width="100%" height="100%" fill="white"/>';
        
        $x = 0;
        for ($i = 0; $i < strlen($barcode); $i++) {
            $char = $barcode[$i];
            if (isset($code39[$char])) {
                $pattern = $code39[$char];
                for ($j = 0; $j < strlen($pattern); $j++) {
                    if ($pattern[$j] === '1') {
                        $svg .= '<rect x="' . $x . '" y="0" width="' . $width . '" height="' . $height . '" fill="black"/>';
                    }
                    $x += $width;
                }
                $x += $width; // Inter-character gap
            }
        }
        
        // Add text below barcode
        $textX = (strlen($barcode) * 13 * $width) / 2;
        $svg .= '<text x="' . $textX . '" y="' . ($height + 15) . '" font-family="monospace" font-size="12" text-anchor="middle" fill="black">' . htmlspecialchars($data) . '</text>';
        $svg .= '</svg>';
        
        return $svg;
    }
    
    /**
     * Generate Code 39 barcode as PNG using Google Charts API
     * @param string $data The data to encode
     * @param int $width Width in pixels (default 400)
     * @param int $height Height in pixels (default 100)
     * @return string URL to the barcode image
     */
    public static function generateCode39URL($data, $width = 400, $height = 100)
    {
        $data = strtoupper(str_replace(' ', '', $data)); // Remove spaces for barcode
        $encodedData = urlencode($data);
        return "https://bwipjs-api.metafloor.com/?bcid=code39&text={$encodedData}&scale=3&includetext";
    }
    
    /**
     * Generate barcode as base64 image data for embedding in PDFs
     * @param string $data The data to encode
     * @return string|false Base64 encoded image data or false on failure
     */
    public static function generateBarcodeBase64($data)
    {
        $url = self::generateCode39URL($data);
        
        $context = stream_context_create([
            'http' => [
                'timeout' => 10,
                'user_agent' => 'Mozilla/5.0 (compatible; PHP Barcode Generator)'
            ]
        ]);
        
        $imageData = @file_get_contents($url, false, $context);
        
        if ($imageData !== false) {
            return base64_encode($imageData);
        }
        
        return false;
    }
    
    /**
     * Generate document barcode ID
     * @param string $docType Type of document
     * @param int $docId Document ID
     * @param string $date Date string
     * @return string Barcode ID
     */
    public static function generateDocumentBarcodeID($docType, $docId, $date = null)
    {
        $prefix = strtoupper(substr($docType, 0, 3));
        $dateStr = $date ? date('Ymd', strtotime($date)) : date('Ymd');
        return sprintf("%s%s%06d", $prefix, $dateStr, $docId);
    }
    
    /**
     * Save barcode to file
     * @param string $data The data to encode
     * @param string $filepath Path where to save the barcode
     * @return bool Success status
     */
    public static function generateBarcodeFile($data, $filepath)
    {
        $url = self::generateCode39URL($data);
        
        $context = stream_context_create([
            'http' => [
                'timeout' => 10,
                'user_agent' => 'Mozilla/5.0 (compatible; PHP Barcode Generator)'
            ]
        ]);
        
        $imageData = @file_get_contents($url, false, $context);
        
        if ($imageData !== false) {
            return file_put_contents($filepath, $imageData) !== false;
        }
        
        return false;
    }
}
