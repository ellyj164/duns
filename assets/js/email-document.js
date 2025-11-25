/**
 * Email Document Feature
 * Client-side functionality for emailing documents
 */

// Create email modal HTML
const createEmailModal = () => {
    const modal = document.createElement('div');
    modal.id = 'emailDocumentModal';
    modal.innerHTML = `
        <div class="modal-overlay" onclick="closeEmailModal()"></div>
        <div class="modal-content">
            <div class="modal-header">
                <h2>📧 Email Document</h2>
                <button class="close-btn" onclick="closeEmailModal()">×</button>
            </div>
            <div class="modal-body">
                <form id="emailDocumentForm">
                    <input type="hidden" id="email_doc_type" name="doc_type">
                    <input type="hidden" id="email_doc_id" name="doc_id">
                    
                    <div class="form-group">
                        <label for="recipient_name">Recipient Name *</label>
                        <input type="text" id="recipient_name" name="recipient_name" required 
                               placeholder="Enter recipient's name">
                    </div>
                    
                    <div class="form-group">
                        <label for="recipient_email">Recipient Email *</label>
                        <input type="email" id="recipient_email" name="recipient_email" required 
                               placeholder="Enter recipient's email address">
                    </div>
                    
                    <div class="form-group">
                        <label for="cc_emails">CC (Optional)</label>
                        <input type="text" id="cc_emails" name="cc_emails" 
                               placeholder="Comma-separated emails (e.g., user1@email.com, user2@email.com)">
                    </div>
                    
                    <div class="form-group">
                        <label for="email_subject">Subject *</label>
                        <input type="text" id="email_subject" name="subject" required 
                               placeholder="Email subject">
                    </div>
                    
                    <div class="form-group">
                        <label for="email_message">Message *</label>
                        <textarea id="email_message" name="message" rows="6" required 
                                  placeholder="Enter your message to the recipient"></textarea>
                    </div>
                    
                    <div class="form-actions">
                        <button type="button" class="btn btn-secondary" onclick="closeEmailModal()">
                            Cancel
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <span class="btn-text">Send Email</span>
                            <span class="btn-loader" style="display: none;">Sending...</span>
                        </button>
                    </div>
                </form>
                
                <div id="emailResult" class="result-message" style="display: none;"></div>
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
    
    // Add event listener for form submission
    document.getElementById('emailDocumentForm').addEventListener('submit', handleEmailSubmit);
};

// Open email modal with document details
const openEmailModal = (docType, docId, clientName, clientEmail) => {
    let modal = document.getElementById('emailDocumentModal');
    
    // Create modal if it doesn't exist
    if (!modal) {
        createEmailModal();
        modal = document.getElementById('emailDocumentModal');
    }
    
    // Set document details
    document.getElementById('email_doc_type').value = docType;
    document.getElementById('email_doc_id').value = docId;
    
    // Pre-fill recipient information if available
    if (clientName) {
        document.getElementById('recipient_name').value = clientName;
    }
    if (clientEmail) {
        document.getElementById('recipient_email').value = clientEmail;
    }
    
    // Set default subject
    const docTypeLabel = docType.charAt(0).toUpperCase() + docType.slice(1);
    document.getElementById('email_subject').value = `${docTypeLabel} #${docId} from Feza Logistics`;
    
    // Set default message
    const defaultMessage = `Dear ${clientName || 'Valued Customer'},

Please find attached your ${docType} (#${docId}) from Feza Logistics.

If you have any questions, please don't hesitate to contact us.

Best regards,
Feza Logistics`;
    document.getElementById('email_message').value = defaultMessage;
    
    // Show modal
    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
};

// Close email modal
const closeEmailModal = () => {
    const modal = document.getElementById('emailDocumentModal');
    if (modal) {
        modal.style.display = 'none';
        document.body.style.overflow = 'auto';
        
        // Reset form
        document.getElementById('emailDocumentForm').reset();
        document.getElementById('emailResult').style.display = 'none';
    }
};

// Handle email form submission
const handleEmailSubmit = async (e) => {
    e.preventDefault();
    
    const form = e.target;
    const formData = new FormData(form);
    const submitBtn = form.querySelector('button[type="submit"]');
    const btnText = submitBtn.querySelector('.btn-text');
    const btnLoader = submitBtn.querySelector('.btn-loader');
    const resultDiv = document.getElementById('emailResult');
    
    // Show loading state
    submitBtn.disabled = true;
    btnText.style.display = 'none';
    btnLoader.style.display = 'inline';
    resultDiv.style.display = 'none';
    
    try {
        const response = await fetch('email_document.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        // Show result
        resultDiv.style.display = 'block';
        resultDiv.className = 'result-message ' + (result.success ? 'success' : 'error');
        resultDiv.textContent = result.message;
        
        if (result.success) {
            // Close modal after 2 seconds on success
            setTimeout(() => {
                closeEmailModal();
            }, 2000);
        }
    } catch (error) {
        resultDiv.style.display = 'block';
        resultDiv.className = 'result-message error';
        resultDiv.textContent = 'An error occurred while sending the email. Please try again.';
        console.error('Email error:', error);
    } finally {
        // Reset button state
        submitBtn.disabled = false;
        btnText.style.display = 'inline';
        btnLoader.style.display = 'none';
    }
};

// Add CSS styles for the modal
const addEmailModalStyles = () => {
    if (document.getElementById('emailModalStyles')) return;
    
    const style = document.createElement('style');
    style.id = 'emailModalStyles';
    style.textContent = `
        #emailDocumentModal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 10000;
            align-items: center;
            justify-content: center;
        }
        
        #emailDocumentModal .modal-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(4px);
        }
        
        #emailDocumentModal .modal-content {
            position: relative;
            background: white;
            border-radius: 12px;
            width: 90%;
            max-width: 600px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            animation: modalSlideIn 0.3s ease-out;
        }
        
        @keyframes modalSlideIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        #emailDocumentModal .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 30px;
            border-bottom: 1px solid #e5e7eb;
        }
        
        #emailDocumentModal .modal-header h2 {
            margin: 0;
            font-size: 1.5rem;
            color: #1f2937;
        }
        
        #emailDocumentModal .close-btn {
            background: none;
            border: none;
            font-size: 2rem;
            color: #6b7280;
            cursor: pointer;
            padding: 0;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 6px;
            transition: all 0.2s;
        }
        
        #emailDocumentModal .close-btn:hover {
            background: #f3f4f6;
            color: #1f2937;
        }
        
        #emailDocumentModal .modal-body {
            padding: 30px;
        }
        
        #emailDocumentModal .form-group {
            margin-bottom: 20px;
        }
        
        #emailDocumentModal .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #374151;
            font-size: 0.875rem;
        }
        
        #emailDocumentModal .form-group input,
        #emailDocumentModal .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 1rem;
            font-family: inherit;
            transition: border-color 0.2s;
        }
        
        #emailDocumentModal .form-group input:focus,
        #emailDocumentModal .form-group textarea:focus {
            outline: none;
            border-color: #3b82f6;
        }
        
        #emailDocumentModal .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }
        
        #emailDocumentModal .form-actions {
            display: flex;
            gap: 12px;
            justify-content: flex-end;
            margin-top: 30px;
        }
        
        #emailDocumentModal .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        #emailDocumentModal .btn-secondary {
            background: #f3f4f6;
            color: #374151;
        }
        
        #emailDocumentModal .btn-secondary:hover {
            background: #e5e7eb;
        }
        
        #emailDocumentModal .btn-primary {
            background: #3b82f6;
            color: white;
        }
        
        #emailDocumentModal .btn-primary:hover {
            background: #2563eb;
        }
        
        #emailDocumentModal .btn-primary:disabled {
            background: #9ca3af;
            cursor: not-allowed;
        }
        
        #emailDocumentModal .result-message {
            padding: 15px;
            border-radius: 8px;
            margin-top: 20px;
            font-weight: 500;
        }
        
        #emailDocumentModal .result-message.success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #10b981;
        }
        
        #emailDocumentModal .result-message.error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #ef4444;
        }
        
        /* Email button styles for integration */
        .email-doc-btn {
            background: #10b981;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.875rem;
            font-weight: 500;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        
        .email-doc-btn:hover {
            background: #059669;
            transform: translateY(-1px);
        }
    `;
    
    document.head.appendChild(style);
};

// Initialize on page load
document.addEventListener('DOMContentLoaded', () => {
    addEmailModalStyles();
});

// Export functions for global use
window.openEmailModal = openEmailModal;
window.closeEmailModal = closeEmailModal;
