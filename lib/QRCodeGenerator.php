<?php
/**
 * QR Code Generator Library
 * Simple QR code generation using various methods
 */

// Load configuration
if (file_exists(__DIR__ . '/../config.php')) {
    require_once __DIR__ . '/../config.php';
}

class QRCodeGenerator
{
    /**
     * Generate QR code using Google Charts API
     * @param string $data The data to encode
     * @param int $size Size of the QR code in pixels (default 300)
     * @return string URL to the QR code image
     */
    public static function generateQRCodeURL($data, $size = 300)
    {
        $encodedData = urlencode($data);
        return "https://chart.googleapis.com/chart?chs={$size}x{$size}&cht=qr&chl={$encodedData}&choe=UTF-8";
    }
    
    /**
     * Generate QR code as base64 image data for embedding in PDFs
     * @param string $data The data to encode
     * @param int $size Size of the QR code in pixels (default 300)
     * @return string|false Base64 encoded image data or false on failure
     */
    public static function generateQRCodeBase64($data, $size = 300)
    {
        $url = self::generateQRCodeURL($data, $size);
        
        // Fetch the QR code image
        $context = stream_context_create([
            'http' => [
                'timeout' => 10,
                'user_agent' => 'Mozilla/5.0 (compatible; PHP QR Generator)'
            ]
        ]);
        
        $imageData = @file_get_contents($url, false, $context);
        
        if ($imageData !== false) {
            return base64_encode($imageData);
        }
        
        return false;
    }
    
    /**
     * Generate QR code and save to file
     * @param string $data The data to encode
     * @param string $filepath Path where to save the QR code
     * @param int $size Size of the QR code in pixels (default 300)
     * @return bool Success status
     */
    public static function generateQRCodeFile($data, $filepath, $size = 300)
    {
        $url = self::generateQRCodeURL($data, $size);
        
        $context = stream_context_create([
            'http' => [
                'timeout' => 10,
                'user_agent' => 'Mozilla/5.0 (compatible; PHP QR Generator)'
            ]
        ]);
        
        $imageData = @file_get_contents($url, false, $context);
        
        if ($imageData !== false) {
            return file_put_contents($filepath, $imageData) !== false;
        }
        
        return false;
    }
    
    /**
     * Generate verification data for a document
     * @param string $docType Type of document (invoice, receipt, etc.)
     * @param int $docId Document ID
     * @param string $amount Amount with currency
     * @param string $date Document date
     * @return string Verification data string
     */
    public static function generateVerificationData($docType, $docId, $amount, $date)
    {
        $baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://{$_SERVER['HTTP_HOST']}";
        $verificationUrl = "{$baseUrl}/verify_document.php?type={$docType}&id={$docId}";
        
        // Create verification hash
        $secret = defined('DOCUMENT_VERIFICATION_SECRET') ? DOCUMENT_VERIFICATION_SECRET : 'DEFAULT_SECRET';
        $hash = hash('sha256', $docType . $docId . $amount . $date . $secret);
        $shortHash = substr($hash, 0, 12);
        
        return $verificationUrl . "&hash={$shortHash}";
    }
    
    /**
     * Generate a simple text-based verification code
     * @param string $docType Type of document
     * @param int $docId Document ID
     * @return string Verification code
     */
    public static function generateVerificationCode($docType, $docId)
    {
        $prefix = strtoupper(substr($docType, 0, 3));
        return sprintf("%s-%06d-%04d", $prefix, $docId, rand(1000, 9999));
    }
}
