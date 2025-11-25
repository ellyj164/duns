<?php
/**
 * Template Manager
 * Manages document templates and rendering
 */

class TemplateManager
{
    private $pdo;
    private $templatesPath;
    
    public function __construct($pdo)
    {
        $this->pdo = $pdo;
        $this->templatesPath = __DIR__ . '/../templates/pdf';
    }
    
    /**
     * Get template for document type
     * 
     * @param string $docType Document type (invoice, receipt, quotation)
     * @param int $companyId Company ID for preferences (optional)
     * @return array Template configuration
     */
    public function getTemplate($docType, $companyId = null)
    {
        try {
            // First, try to get company-specific preference
            if ($companyId) {
                $stmt = $this->pdo->prepare("
                    SELECT dt.* FROM document_templates dt
                    JOIN company_template_preferences ctp ON dt.id = ctp.template_id
                    WHERE ctp.company_id = :company_id AND dt.doc_type = :doc_type
                    AND dt.is_active = 1
                    LIMIT 1
                ");
                $stmt->execute([
                    ':company_id' => $companyId,
                    ':doc_type' => $docType
                ]);
                $template = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($template) {
                    return $this->prepareTemplate($template);
                }
            }
            
            // Fall back to default template for document type
            $stmt = $this->pdo->prepare("
                SELECT * FROM document_templates
                WHERE doc_type = :doc_type AND is_default = 1 AND is_active = 1
                LIMIT 1
            ");
            $stmt->execute([':doc_type' => $docType]);
            $template = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($template) {
                return $this->prepareTemplate($template);
            }
            
            // If no template found, return classic default
            return $this->getDefaultTemplate($docType);
            
        } catch (PDOException $e) {
            // Return default template on error
            return $this->getDefaultTemplate($docType);
        }
    }
    
    /**
     * Prepare template configuration
     * 
     * @param array $template Template from database
     * @return array Prepared template configuration
     */
    private function prepareTemplate($template)
    {
        $settings = [];
        if (!empty($template['settings_json'])) {
            $settings = json_decode($template['settings_json'], true) ?? [];
        }
        
        return array_merge($template, [
            'settings' => $settings,
            'colors' => $this->getTemplateColors($template['template_code']),
            'fonts' => $this->getTemplateFonts($template['template_code'])
        ]);
    }
    
    /**
     * Get default template configuration
     * 
     * @param string $docType Document type
     * @return array Default template configuration
     */
    private function getDefaultTemplate($docType)
    {
        return [
            'id' => 0,
            'template_name' => 'Classic Professional',
            'template_code' => 'classic',
            'doc_type' => $docType,
            'is_default' => true,
            'colors' => $this->getTemplateColors('classic'),
            'fonts' => $this->getTemplateFonts('classic'),
            'settings' => []
        ];
    }
    
    /**
     * Get color scheme for template
     * 
     * @param string $templateCode Template code
     * @return array Color configuration
     */
    private function getTemplateColors($templateCode)
    {
        $colorSchemes = [
            'classic' => [
                'primary' => [0, 113, 206],      // Blue #0071ce
                'secondary' => [73, 80, 87],     // Dark Gray
                'border' => [222, 226, 230],     // Light Gray
                'accent' => [16, 185, 129],      // Green
                'text' => [31, 41, 55]           // Dark Text
            ],
            'modern' => [
                'primary' => [99, 102, 241],     // Indigo #6366f1
                'secondary' => [55, 65, 81],     // Slate Gray
                'border' => [229, 231, 235],     // Very Light Gray
                'accent' => [236, 72, 153],      // Pink
                'text' => [17, 24, 39]           // Almost Black
            ],
            'elegant' => [
                'primary' => [79, 70, 229],      // Deep Purple #4f46e5
                'secondary' => [75, 85, 99],     // Cool Gray
                'border' => [209, 213, 219],     // Gray
                'accent' => [168, 85, 247],      // Purple
                'text' => [31, 41, 55]           // Dark
            ],
            'bold' => [
                'primary' => [239, 68, 68],      // Red #ef4444
                'secondary' => [55, 65, 81],     // Dark Gray
                'border' => [243, 244, 246],     // Light Gray
                'accent' => [251, 191, 36],      // Amber
                'text' => [17, 24, 39]           // Almost Black
            ],
            'simple' => [
                'primary' => [51, 51, 51],       // Dark Gray #333
                'secondary' => [102, 102, 102],  // Medium Gray
                'border' => [224, 224, 224],     // Light Gray
                'accent' => [0, 0, 0],           // Black
                'text' => [51, 51, 51]           // Dark Gray
            ]
        ];
        
        return $colorSchemes[$templateCode] ?? $colorSchemes['classic'];
    }
    
    /**
     * Get font configuration for template
     * 
     * @param string $templateCode Template code
     * @return array Font configuration
     */
    private function getTemplateFonts($templateCode)
    {
        $fontConfigs = [
            'classic' => [
                'main' => 'Arial',
                'heading' => 'Arial',
                'heading_size' => 18,
                'body_size' => 10,
                'small_size' => 8
            ],
            'modern' => [
                'main' => 'Helvetica',
                'heading' => 'Helvetica',
                'heading_size' => 20,
                'body_size' => 10,
                'small_size' => 8
            ],
            'elegant' => [
                'main' => 'Times',
                'heading' => 'Times',
                'heading_size' => 19,
                'body_size' => 11,
                'small_size' => 9
            ],
            'bold' => [
                'main' => 'Arial',
                'heading' => 'Arial',
                'heading_size' => 22,
                'body_size' => 11,
                'small_size' => 9
            ],
            'simple' => [
                'main' => 'Courier',
                'heading' => 'Courier',
                'heading_size' => 16,
                'body_size' => 10,
                'small_size' => 8
            ]
        ];
        
        return $fontConfigs[$templateCode] ?? $fontConfigs['classic'];
    }
    
    /**
     * Get all available templates for a document type
     * 
     * @param string $docType Document type
     * @return array List of templates
     */
    public function getAvailableTemplates($docType)
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT * FROM document_templates
                WHERE doc_type = :doc_type AND is_active = 1
                ORDER BY is_default DESC, template_name ASC
            ");
            $stmt->execute([':doc_type' => $docType]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            return [];
        }
    }
    
    /**
     * Set company template preference
     * 
     * @param int $companyId Company ID
     * @param string $docType Document type
     * @param int $templateId Template ID
     * @return bool Success status
     */
    public function setCompanyPreference($companyId, $docType, $templateId)
    {
        try {
            // Check if preference exists
            $checkStmt = $this->pdo->prepare("
                SELECT id FROM company_template_preferences
                WHERE company_id = :company_id AND doc_type = :doc_type
            ");
            $checkStmt->execute([
                ':company_id' => $companyId,
                ':doc_type' => $docType
            ]);
            
            if ($checkStmt->fetch()) {
                // Update existing
                $stmt = $this->pdo->prepare("
                    UPDATE company_template_preferences
                    SET template_id = :template_id, updated_at = NOW()
                    WHERE company_id = :company_id AND doc_type = :doc_type
                ");
            } else {
                // Insert new
                $stmt = $this->pdo->prepare("
                    INSERT INTO company_template_preferences
                    (company_id, doc_type, template_id)
                    VALUES (:company_id, :doc_type, :template_id)
                ");
            }
            
            $stmt->execute([
                ':company_id' => $companyId,
                ':doc_type' => $docType,
                ':template_id' => $templateId
            ]);
            
            return true;
            
        } catch (PDOException $e) {
            error_log("Template preference error: " . $e->getMessage());
            return false;
        }
    }
}
