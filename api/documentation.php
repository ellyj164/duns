<?php
/**
 * API Documentation
 * Interactive API documentation for developers
 */

require_once 'header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>API Documentation - Feza Logistics</title>
    <link rel="stylesheet" href="../assets/css/dark-mode.css">
    <style>
        .api-docs-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .api-header {
            text-align: center;
            margin-bottom: 40px;
        }
        
        .api-header h1 {
            color: var(--text-color);
            font-size: 2.5rem;
            margin-bottom: 10px;
        }
        
        .api-header p {
            color: var(--text-secondary);
            font-size: 1.1rem;
        }
        
        .endpoint-section {
            background: var(--white-color);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .endpoint-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 15px;
        }
        
        .method-badge {
            padding: 4px 12px;
            border-radius: 4px;
            font-weight: bold;
            font-size: 0.85rem;
        }
        
        .method-get { background: #dbeafe; color: #1e40af; }
        .method-post { background: #d1fae5; color: #065f46; }
        .method-put { background: #fef3c7; color: #92400e; }
        .method-delete { background: #fee2e2; color: #991b1b; }
        
        .endpoint-path {
            font-family: monospace;
            font-size: 1.1rem;
            color: var(--text-color);
        }
        
        .endpoint-description {
            color: var(--text-secondary);
            margin-bottom: 15px;
        }
        
        .params-table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        
        .params-table th {
            background: var(--secondary-color);
            padding: 10px;
            text-align: left;
            color: var(--text-color);
        }
        
        .params-table td {
            padding: 10px;
            border-bottom: 1px solid var(--border-color);
            color: var(--text-color);
        }
        
        .code-block {
            background: #1e1e1e;
            color: #d4d4d4;
            padding: 15px;
            border-radius: 4px;
            font-family: monospace;
            overflow-x: auto;
            margin: 10px 0;
        }
        
        .section-title {
            color: var(--text-color);
            font-size: 1.5rem;
            margin: 30px 0 20px 0;
            padding-bottom: 10px;
            border-bottom: 2px solid var(--primary-color);
        }
    </style>
</head>
<body>
    <div class="api-docs-container">
        <div class="api-header">
            <h1>📚 API Documentation</h1>
            <p>RESTful API for Feza Logistics Financial Management System</p>
        </div>
        
        <h2 class="section-title">Authentication</h2>
        <div class="endpoint-section">
            <h3>API Token Authentication</h3>
            <p>All API requests require authentication using a Bearer token in the Authorization header:</p>
            <div class="code-block">
Authorization: Bearer YOUR_API_TOKEN
            </div>
            <p>To obtain an API token, contact your system administrator or generate one from your profile settings.</p>
        </div>
        
        <h2 class="section-title">Authentication Endpoints</h2>
        
        <div class="endpoint-section">
            <div class="endpoint-header">
                <span class="method-badge method-post">POST</span>
                <span class="endpoint-path">/api/v1/auth/login</span>
            </div>
            <p class="endpoint-description">Authenticate user and obtain API token</p>
            
            <h4>Request Body</h4>
            <table class="params-table">
                <thead>
                    <tr>
                        <th>Parameter</th>
                        <th>Type</th>
                        <th>Required</th>
                        <th>Description</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>username</td>
                        <td>string</td>
                        <td>Yes</td>
                        <td>User's username</td>
                    </tr>
                    <tr>
                        <td>password</td>
                        <td>string</td>
                        <td>Yes</td>
                        <td>User's password</td>
                    </tr>
                </tbody>
            </table>
            
            <h4>Response (200 OK)</h4>
            <div class="code-block">
{
  "success": true,
  "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
  "user": {
    "id": 1,
    "username": "john_doe",
    "email": "john@example.com"
  }
}
            </div>
        </div>
        
        <h2 class="section-title">Client Endpoints</h2>
        
        <div class="endpoint-section">
            <div class="endpoint-header">
                <span class="method-badge method-get">GET</span>
                <span class="endpoint-path">/api/v1/clients</span>
            </div>
            <p class="endpoint-description">Get list of all clients</p>
            
            <h4>Query Parameters</h4>
            <table class="params-table">
                <thead>
                    <tr>
                        <th>Parameter</th>
                        <th>Type</th>
                        <th>Required</th>
                        <th>Description</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>page</td>
                        <td>integer</td>
                        <td>No</td>
                        <td>Page number (default: 1)</td>
                    </tr>
                    <tr>
                        <td>limit</td>
                        <td>integer</td>
                        <td>No</td>
                        <td>Items per page (default: 50, max: 100)</td>
                    </tr>
                    <tr>
                        <td>search</td>
                        <td>string</td>
                        <td>No</td>
                        <td>Search by name or email</td>
                    </tr>
                </tbody>
            </table>
            
            <h4>Response (200 OK)</h4>
            <div class="code-block">
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "ABC Company",
      "email": "contact@abc.com",
      "phone": "+250788123456",
      "tin": "123456789"
    }
  ],
  "pagination": {
    "current_page": 1,
    "total_pages": 5,
    "total_items": 243
  }
}
            </div>
        </div>
        
        <div class="endpoint-section">
            <div class="endpoint-header">
                <span class="method-badge method-get">GET</span>
                <span class="endpoint-path">/api/v1/clients/{id}</span>
            </div>
            <p class="endpoint-description">Get a specific client by ID</p>
            
            <h4>URL Parameters</h4>
            <table class="params-table">
                <thead>
                    <tr>
                        <th>Parameter</th>
                        <th>Type</th>
                        <th>Description</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>id</td>
                        <td>integer</td>
                        <td>Client ID</td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <div class="endpoint-section">
            <div class="endpoint-header">
                <span class="method-badge method-post">POST</span>
                <span class="endpoint-path">/api/v1/clients</span>
            </div>
            <p class="endpoint-description">Create a new client</p>
            
            <h4>Request Body</h4>
            <div class="code-block">
{
  "name": "New Client Ltd",
  "email": "info@newclient.com",
  "phone": "+250788999999",
  "address": "KG 123 St, Kigali",
  "tin": "987654321"
}
            </div>
        </div>
        
        <h2 class="section-title">Invoice Endpoints</h2>
        
        <div class="endpoint-section">
            <div class="endpoint-header">
                <span class="method-badge method-get">GET</span>
                <span class="endpoint-path">/api/v1/invoices</span>
            </div>
            <p class="endpoint-description">Get list of invoices</p>
        </div>
        
        <div class="endpoint-section">
            <div class="endpoint-header">
                <span class="method-badge method-post">POST</span>
                <span class="endpoint-path">/api/v1/invoices</span>
            </div>
            <p class="endpoint-description">Create a new invoice</p>
        </div>
        
        <h2 class="section-title">Transaction Endpoints</h2>
        
        <div class="endpoint-section">
            <div class="endpoint-header">
                <span class="method-badge method-get">GET</span>
                <span class="endpoint-path">/api/v1/transactions</span>
            </div>
            <p class="endpoint-description">Get list of transactions</p>
        </div>
        
        <h2 class="section-title">Dashboard Endpoints</h2>
        
        <div class="endpoint-section">
            <div class="endpoint-header">
                <span class="method-badge method-get">GET</span>
                <span class="endpoint-path">/api/v1/dashboard/summary</span>
            </div>
            <p class="endpoint-description">Get dashboard summary data</p>
            
            <h4>Response (200 OK)</h4>
            <div class="code-block">
{
  "success": true,
  "data": {
    "total_revenue": 15000000,
    "outstanding_amount": 2500000,
    "total_clients": 243,
    "recent_transactions": [...]
  }
}
            </div>
        </div>
        
        <h2 class="section-title">Error Responses</h2>
        
        <div class="endpoint-section">
            <h4>400 Bad Request</h4>
            <div class="code-block">
{
  "success": false,
  "error": "Invalid request parameters"
}
            </div>
            
            <h4>401 Unauthorized</h4>
            <div class="code-block">
{
  "success": false,
  "error": "Invalid or missing authentication token"
}
            </div>
            
            <h4>404 Not Found</h4>
            <div class="code-block">
{
  "success": false,
  "error": "Resource not found"
}
            </div>
            
            <h4>500 Internal Server Error</h4>
            <div class="code-block">
{
  "success": false,
  "error": "Internal server error"
}
            </div>
        </div>
    </div>
</body>
</html>
