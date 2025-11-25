<?php
require_once 'header.php';
require_once 'petty_cash_rbac.php';

// Check permission
if (!canPerformPettyCashAction($_SESSION['user_id'], 'manage_categories')) {
    header('Location: petty_cash.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Petty Cash Categories - Feza Logistics</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet" />
    <style>
        :root {
            --primary: #4f46e5;
            --success: #10b981;
            --danger: #ef4444;
            --bg: #f8f9fc;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Inter', sans-serif; background: var(--bg); }
        .container { max-width: 1200px; margin: 0 auto; padding: 20px; }
        .header { background: linear-gradient(135deg, #111827, #374151); color: white; padding: 30px 20px; margin-bottom: 30px; }
        .header h1 { font-size: 28px; }
        .card { background: white; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); padding: 24px; margin-bottom: 20px; }
        .btn { padding: 10px 20px; border-radius: 6px; border: none; cursor: pointer; font-weight: 500; }
        .btn-primary { background: var(--primary); color: white; }
        .btn-success { background: var(--success); color: white; }
        .btn-danger { background: var(--danger); color: white; }
        .btn:hover { opacity: 0.9; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #e5e7eb; }
        th { background: #f9fafb; font-weight: 600; }
        .category-icon { font-size: 24px; }
        .category-color { width: 30px; height: 30px; border-radius: 50%; display: inline-block; }
        .badge { padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: 500; }
        .badge-active { background: #d1fae5; color: #065f46; }
        .badge-inactive { background: #fee2e2; color: #991b1b; }
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; }
        .modal-content { background: white; max-width: 500px; margin: 50px auto; padding: 30px; border-radius: 12px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: 500; }
        .form-group input, .form-group select { width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 6px; }
    </style>
</head>
<body>
    <div class="header">
        <div class="container">
            <h1>💼 Petty Cash Categories</h1>
            <p>Manage expense categories and spending rules</p>
        </div>
    </div>

    <div class="container">
        <div class="card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h2>Categories</h2>
                <button class="btn btn-primary" onclick="openAddModal()">+ Add Category</button>
            </div>
            
            <table id="categoriesTable">
                <thead>
                    <tr>
                        <th>Icon</th>
                        <th>Name</th>
                        <th>Description</th>
                        <th>Max Amount</th>
                        <th>Color</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="categoriesList">
                    <tr><td colspan="7" style="text-align: center;">Loading...</td></tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Add/Edit Modal -->
    <div id="categoryModal" class="modal">
        <div class="modal-content">
            <h2 id="modalTitle">Add Category</h2>
            <form id="categoryForm" onsubmit="saveCategory(event)">
                <input type="hidden" id="categoryId" />
                
                <div class="form-group">
                    <label>Name *</label>
                    <input type="text" id="categoryName" required />
                </div>
                
                <div class="form-group">
                    <label>Description</label>
                    <input type="text" id="categoryDescription" />
                </div>
                
                <div class="form-group">
                    <label>Max Amount (optional)</label>
                    <input type="number" step="0.01" id="categoryMaxAmount" />
                </div>
                
                <div class="form-group">
                    <label>Icon (emoji)</label>
                    <input type="text" id="categoryIcon" placeholder="🔧" maxlength="2" />
                </div>
                
                <div class="form-group">
                    <label>Color</label>
                    <input type="color" id="categoryColor" value="#6b7280" />
                </div>
                
                <div style="display: flex; gap: 10px; margin-top: 20px;">
                    <button type="submit" class="btn btn-success">Save</button>
                    <button type="button" class="btn" onclick="closeModal()">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        let categories = [];

        async function loadCategories() {
            try {
                const response = await fetch('api_petty_cash_categories.php');
                const data = await response.json();
                if (data.success) {
                    categories = data.data;
                    renderCategories();
                }
            } catch (error) {
                console.error('Error loading categories:', error);
            }
        }

        function renderCategories() {
            const tbody = document.getElementById('categoriesList');
            if (categories.length === 0) {
                tbody.innerHTML = '<tr><td colspan="7" style="text-align: center;">No categories found</td></tr>';
                return;
            }

            tbody.innerHTML = categories.map(cat => `
                <tr>
                    <td><span class="category-icon">${cat.icon || '📦'}</span></td>
                    <td><strong>${cat.name}</strong></td>
                    <td>${cat.description || '-'}</td>
                    <td>${cat.max_amount ? parseFloat(cat.max_amount).toLocaleString() : 'No limit'}</td>
                    <td><span class="category-color" style="background: ${cat.color}"></span></td>
                    <td><span class="badge badge-${cat.is_active == 1 ? 'active' : 'inactive'}">
                        ${cat.is_active == 1 ? 'Active' : 'Inactive'}
                    </span></td>
                    <td>
                        <button class="btn btn-primary" style="padding: 6px 12px; font-size: 12px;" onclick="editCategory(${cat.id})">Edit</button>
                        <button class="btn ${cat.is_active == 1 ? 'btn-danger' : 'btn-success'}" 
                                style="padding: 6px 12px; font-size: 12px;" 
                                onclick="toggleCategory(${cat.id})">
                            ${cat.is_active == 1 ? 'Deactivate' : 'Activate'}
                        </button>
                    </td>
                </tr>
            `).join('');
        }

        function openAddModal() {
            document.getElementById('modalTitle').textContent = 'Add Category';
            document.getElementById('categoryForm').reset();
            document.getElementById('categoryModal').style.display = 'block';
        }

        function editCategory(id) {
            const category = categories.find(c => c.id == id);
            if (!category) return;

            document.getElementById('modalTitle').textContent = 'Edit Category';
            document.getElementById('categoryId').value = category.id;
            document.getElementById('categoryName').value = category.name;
            document.getElementById('categoryDescription').value = category.description || '';
            document.getElementById('categoryMaxAmount').value = category.max_amount || '';
            document.getElementById('categoryIcon').value = category.icon || '';
            document.getElementById('categoryColor').value = category.color || '#6b7280';
            document.getElementById('categoryModal').style.display = 'block';
        }

        function closeModal() {
            document.getElementById('categoryModal').style.display = 'none';
        }

        async function saveCategory(event) {
            event.preventDefault();

            const id = document.getElementById('categoryId').value;
            const data = {
                action: id ? 'update' : 'create',
                name: document.getElementById('categoryName').value,
                description: document.getElementById('categoryDescription').value,
                max_amount: document.getElementById('categoryMaxAmount').value || null,
                icon: document.getElementById('categoryIcon').value,
                color: document.getElementById('categoryColor').value
            };

            if (id) data.id = id;

            try {
                const response = await fetch('api_petty_cash_categories.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });

                const result = await response.json();
                if (result.success) {
                    alert(result.message);
                    closeModal();
                    loadCategories();
                } else {
                    alert('Error: ' + result.error);
                }
            } catch (error) {
                console.error('Error saving category:', error);
                alert('Failed to save category');
            }
        }

        async function toggleCategory(id) {
            if (!confirm('Are you sure you want to change this category status?')) return;

            try {
                const response = await fetch('api_petty_cash_categories.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'toggle', id: id })
                });

                const result = await response.json();
                if (result.success) {
                    loadCategories();
                } else {
                    alert('Error: ' + result.error);
                }
            } catch (error) {
                console.error('Error toggling category:', error);
            }
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('categoryModal');
            if (event.target == modal) {
                closeModal();
            }
        }

        // Load categories on page load
        loadCategories();
    </script>
</body>
</html>
