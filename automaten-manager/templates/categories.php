<?php
// Sicherheit: Direkten Zugriff verhindern
if (!defined('ABSPATH')) {
    exit;
}

// FontAwesome Icons für Icon-Picker
$icon_options = array(
    'fas fa-cube' => 'Würfel',
    'fas fa-glass-water' => 'Getränk',
    'fas fa-cookie-bite' => 'Keks',
    'fas fa-candy-cane' => 'Süßigkeit',
    'fas fa-coffee' => 'Kaffee',
    'fas fa-apple-alt' => 'Apfel',
    'fas fa-bread-slice' => 'Brot',
    'fas fa-cheese' => 'Käse',
    'fas fa-fish' => 'Fisch',
    'fas fa-hamburger' => 'Burger',
    'fas fa-hotdog' => 'Hotdog',
    'fas fa-ice-cream' => 'Eis',
    'fas fa-pepper-hot' => 'Chili',
    'fas fa-pizza-slice' => 'Pizza',
    'fas fa-wine-bottle' => 'Flasche',
    'fas fa-paperclip' => 'Büro',
    'fas fa-pen' => 'Stift',
    'fas fa-calculator' => 'Rechner',
    'fas fa-tools' => 'Werkzeug',
    'fas fa-home' => 'Zuhause',
    'fas fa-car' => 'Auto',
    'fas fa-heart' => 'Herz',
    'fas fa-star' => 'Stern',
    'fas fa-trophy' => 'Pokal',
    'fas fa-gift' => 'Geschenk'
);

// Vordefinierte Farb-Paletten
$color_presets = array(
    '#6366f1' => 'Indigo',
    '#3b82f6' => 'Blau',
    '#10b981' => 'Grün',
    '#f59e0b' => 'Orange',
    '#ef4444' => 'Rot',
    '#ec4899' => 'Pink',
    '#8b5cf6' => 'Lila',
    '#06b6d4' => 'Cyan',
    '#84cc16' => 'Lime',
    '#f97316' => 'Orange',
    '#6b7280' => 'Grau',
    '#1f2937' => 'Dunkelgrau'
);
?>

<div class="wrap">
    <style>
    .automaten-categories {
        max-width: 1400px;
        margin: 0;
        padding: 20px 0;
    }
    
    .automaten-page-header {
        margin-bottom: 30px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 30px;
        border-radius: 12px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 20px;
    }
    
    .automaten-page-title {
        font-size: 2.5rem;
        margin: 0 0 10px 0;
        font-weight: 700;
    }
    
    .automaten-page-subtitle {
        font-size: 1.1rem;
        margin: 0;
        opacity: 0.9;
    }
    
    .automaten-btn {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 12px 24px;
        border: none;
        border-radius: 8px;
        font-weight: 600;
        text-decoration: none;
        cursor: pointer;
        transition: all 0.3s ease;
        font-size: 14px;
    }
    
    .automaten-btn-primary {
        background: rgba(255, 255, 255, 0.2);
        color: white;
        border: 2px solid rgba(255, 255, 255, 0.3);
    }
    
    .automaten-btn-primary:hover {
        background: rgba(255, 255, 255, 0.3);
        transform: translateY(-2px);
        color: white;
        text-decoration: none;
    }
    
    .automaten-btn-secondary {
        background: #6c757d;
        color: white;
    }
    
    .automaten-btn-secondary:hover {
        background: #5a6268;
        color: white;
        text-decoration: none;
    }
    
    .automaten-btn-danger {
        background: #dc3545;
        color: white;
    }
    
    .automaten-btn-danger:hover {
        background: #c82333;
        color: white;
        text-decoration: none;
    }
    
    .automaten-categories-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 20px;
        margin-top: 20px;
    }
    
    .automaten-category-card {
        background: white;
        border-radius: 12px;
        box-shadow: 0 2px 15px rgba(0,0,0,0.1);
        overflow: hidden;
        border: 1px solid #e1e5e9;
        transition: all 0.3s ease;
    }
    
    .automaten-category-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 25px rgba(0,0,0,0.15);
    }
    
    .automaten-category-header {
        padding: 30px;
        text-align: center;
        position: relative;
        overflow: hidden;
    }
    
    .automaten-category-header::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 100%;
        opacity: 0.1;
        background: linear-gradient(135deg, rgba(255,255,255,0.2), transparent);
    }
    
    .automaten-category-icon {
        font-size: 3rem;
        color: white;
        margin-bottom: 15px;
        position: relative;
        z-index: 1;
    }
    
    .automaten-category-name {
        font-size: 1.25rem;
        font-weight: 600;
        color: white;
        margin-bottom: 8px;
        position: relative;
        z-index: 1;
    }
    
    .automaten-category-count {
        font-size: 0.875rem;
        color: rgba(255, 255, 255, 0.9);
        position: relative;
        z-index: 1;
    }
    
    .automaten-category-actions {
        padding: 15px;
        background: #f8f9fa;
        display: flex;
        gap: 10px;
    }
    
    .automaten-category-actions .automaten-btn {
        flex: 1;
        justify-content: center;
        padding: 8px 12px;
        font-size: 13px;
    }
    
    .automaten-text-center {
        text-align: center;
    }
    
    .automaten-loading {
        width: 40px;
        height: 40px;
        border: 4px solid #f3f3f3;
        border-top: 4px solid #667eea;
        border-radius: 50%;
        animation: spin 1s linear infinite;
        margin: 0 auto;
    }
    
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
    
    /* Modal Styles */
    .automaten-modal {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        z-index: 9999;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 20px;
        background: rgba(0, 0, 0, 0.7);
        backdrop-filter: blur(5px);
    }
    
    .automaten-modal-content {
        background: white;
        border-radius: 12px;
        box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        max-width: 600px;
        width: 100%;
        max-height: 90vh;
        overflow: hidden;
        position: relative;
    }
    
    .automaten-modal-header {
        padding: 25px 30px;
        border-bottom: 1px solid #e1e5e9;
        display: flex;
        align-items: center;
        justify-content: space-between;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
    }
    
    .automaten-modal-header h3 {
        margin: 0;
        font-size: 1.5rem;
        font-weight: 700;
    }
    
    .automaten-modal-body {
        padding: 30px;
        max-height: 60vh;
        overflow-y: auto;
    }
    
    .automaten-modal-footer {
        padding: 20px 30px;
        border-top: 1px solid #e1e5e9;
        background: #f8f9fa;
        display: flex;
        gap: 15px;
        justify-content: flex-end;
    }
    
    .automaten-btn-icon-only {
        padding: 8px;
        background: rgba(255, 255, 255, 0.2);
        border: 2px solid rgba(255, 255, 255, 0.3);
    }
    
    .automaten-form-group {
        margin-bottom: 20px;
    }
    
    .automaten-label {
        display: block;
        margin-bottom: 5px;
        font-weight: 600;
        color: #495057;
    }
    
    .automaten-input {
        width: 100%;
        padding: 10px 15px;
        border: 2px solid #e1e5e9;
        border-radius: 8px;
        font-size: 14px;
        transition: border-color 0.3s ease;
    }
    
    .automaten-input:focus {
        outline: none;
        border-color: #667eea;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    }
    
    /* Color Picker */
    .automaten-color-picker {
        display: flex;
        flex-direction: column;
        gap: 15px;
    }
    
    .automaten-color-input {
        width: 60px;
        height: 40px;
        border: 2px solid #e1e5e9;
        border-radius: 8px;
        cursor: pointer;
        background: none;
        padding: 0;
    }
    
    .automaten-color-presets {
        display: grid;
        grid-template-columns: repeat(6, 1fr);
        gap: 10px;
    }
    
    .automaten-color-preset {
        width: 40px;
        height: 40px;
        border: 2px solid #e1e5e9;
        border-radius: 8px;
        cursor: pointer;
        transition: all 0.3s ease;
        position: relative;
    }
    
    .automaten-color-preset:hover {
        transform: scale(1.1);
        border-color: #667eea;
    }
    
    .automaten-color-preset.selected {
        border-color: #667eea;
        box-shadow: 0 0 0 2px rgba(102, 126, 234, 0.2);
    }
    
    .automaten-color-preset.selected::after {
        content: '✓';
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        color: white;
        font-weight: bold;
        text-shadow: 0 0 3px rgba(0,0,0,0.5);
    }
    
    /* Icon Picker */
    .automaten-icon-picker {
        display: flex;
        flex-direction: column;
        gap: 15px;
    }
    
    .automaten-selected-icon {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 80px;
        height: 80px;
        border: 2px solid #e1e5e9;
        border-radius: 12px;
        background: #f8f9fa;
        color: #667eea;
    }
    
    .automaten-icon-grid {
        display: grid;
        grid-template-columns: repeat(8, 1fr);
        gap: 8px;
        max-height: 200px;
        overflow-y: auto;
        padding: 10px;
        border: 1px solid #e1e5e9;
        border-radius: 8px;
    }
    
    .automaten-icon-option {
        width: 40px;
        height: 40px;
        border: 1px solid #e1e5e9;
        border-radius: 8px;
        background: white;
        color: #6c757d;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .automaten-icon-option:hover {
        background: #f8f9fa;
        border-color: #667eea;
        color: #667eea;
    }
    
    .automaten-icon-option.selected {
        background: #667eea;
        border-color: #667eea;
        color: white;
    }
    
    /* Kategorie Vorschau */
    .automaten-category-preview {
        padding: 20px;
        background: #f8f9fa;
        border-radius: 8px;
        text-align: center;
    }
    
    .automaten-preview-badge {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 8px 16px;
        border-radius: 6px;
        color: white;
        font-weight: 500;
        font-size: 0.875rem;
        background-color: #667eea;
        transition: all 0.3s ease;
    }
    
    /* Responsive */
    @media (max-width: 768px) {
        .automaten-page-header {
            flex-direction: column;
            text-align: center;
        }
        
        .automaten-categories-grid {
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        }
        
        .automaten-modal {
            padding: 10px;
        }
        
        .automaten-modal-body {
            padding: 20px;
        }
        
        .automaten-modal-footer {
            flex-direction: column;
        }
        
        .automaten-icon-grid {
            grid-template-columns: repeat(6, 1fr);
        }
        
        .automaten-color-presets {
            grid-template-columns: repeat(4, 1fr);
        }
        
        .automaten-category-actions {
            flex-direction: column;
        }
    }
    </style>

    <div class="automaten-categories">
        <!-- Page Header -->
        <div class="automaten-page-header">
            <div>
                <h1 class="automaten-page-title">
                    <i class="fas fa-tags"></i>
                    Kategorien
                </h1>
                <p class="automaten-page-subtitle">
                    Organisieren Sie Ihre Produkte in Kategorien
                </p>
            </div>
            <button id="addCategoryBtn" class="automaten-btn automaten-btn-primary">
                <i class="fas fa-plus"></i>
                Neue Kategorie
            </button>
        </div>

        <!-- Loading Indicator -->
        <div id="loadingIndicator" class="automaten-text-center" style="display: none; margin: 40px 0;">
            <div class="automaten-loading"></div>
            <p style="margin-top: 20px; color: #6c757d;">Lade Kategorien...</p>
        </div>

        <!-- Empty State -->
        <div id="emptyState" class="automaten-text-center" style="display: none; margin: 40px 0;">
            <i class="fas fa-tags" style="font-size: 4rem; color: #dee2e6; margin-bottom: 20px;"></i>
            <h3 style="color: #6c757d; margin-bottom: 10px;">Keine Kategorien vorhanden</h3>
            <p style="color: #6c757d; margin-bottom: 30px;">Erstellen Sie Ihre erste Kategorie um Produkte zu organisieren.</p>
            <button class="automaten-btn automaten-btn-primary" onclick="document.getElementById('addCategoryBtn').click();">
                <i class="fas fa-plus"></i>
                Erste Kategorie erstellen
            </button>
        </div>

        <!-- Kategorien Grid -->
        <div id="categoriesContainer" class="automaten-categories-grid">
            <!-- Kategorien werden hier dynamisch geladen -->
        </div>

    </div>
</div>

<!-- Kategorie Modal -->
<div id="categoryModal" class="automaten-modal" style="display: none;">
    <div class="automaten-modal-content">
        <div class="automaten-modal-header">
            <h3 id="modalTitle">Neue Kategorie</h3>
            <button id="closeModalBtn" class="automaten-btn automaten-btn-icon-only">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <form id="categoryForm" class="automaten-modal-body">
            <input type="hidden" id="categoryId" value="">
            
            <div class="automaten-form-group">
                <label class="automaten-label" for="categoryName">Name *</label>
                <input type="text" id="categoryName" class="automaten-input" required placeholder="z.B. Getränke, Snacks, Bürobedarf">
            </div>
            
            <div class="automaten-form-group">
                <label class="automaten-label">Farbe *</label>
                <div class="automaten-color-picker">
                    <input type="color" id="categoryColor" class="automaten-color-input" value="#6366f1">
                    <div class="automaten-color-presets">
                        <?php foreach ($color_presets as $color => $name): ?>
                            <button type="button" class="automaten-color-preset" data-color="<?php echo $color; ?>" style="background-color: <?php echo $color; ?>;" title="<?php echo $name; ?>"></button>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            
            <div class="automaten-form-group">
                <label class="automaten-label">Icon *</label>
                <div class="automaten-icon-picker">
                    <div class="automaten-selected-icon">
                        <i id="selectedIcon" class="fas fa-cube" style="font-size: 2rem;"></i>
                        <input type="hidden" id="categoryIcon" value="fas fa-cube">
                    </div>
                    <div class="automaten-icon-grid">
                        <?php foreach ($icon_options as $icon => $label): ?>
                            <button type="button" class="automaten-icon-option" data-icon="<?php echo $icon; ?>" title="<?php echo $label; ?>">
                                <i class="<?php echo $icon; ?>"></i>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            
            <!-- Vorschau -->
            <div class="automaten-form-group">
                <label class="automaten-label">Vorschau</label>
                <div id="categoryPreview" class="automaten-category-preview">
                    <span class="automaten-preview-badge">
                        <i class="fas fa-cube"></i>
                        Beispiel Kategorie
                    </span>
                </div>
            </div>
        </form>
        
        <div class="automaten-modal-footer">
            <button type="button" id="cancelBtn" class="automaten-btn automaten-btn-secondary">
                Abbrechen
            </button>
            <button type="submit" form="categoryForm" id="saveCategoryBtn" class="automaten-btn automaten-btn-primary">
                <i class="fas fa-save"></i>
                Speichern
            </button>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Elemente
    const categoriesContainer = document.getElementById('categoriesContainer');
    const loadingIndicator = document.getElementById('loadingIndicator');
    const emptyState = document.getElementById('emptyState');
    const categoryModal = document.getElementById('categoryModal');
    const categoryForm = document.getElementById('categoryForm');
    const addCategoryBtn = document.getElementById('addCategoryBtn');
    const closeModalBtn = document.getElementById('closeModalBtn');
    const cancelBtn = document.getElementById('cancelBtn');
    
    // Form-Elemente
    const categoryNameInput = document.getElementById('categoryName');
    const categoryColorInput = document.getElementById('categoryColor');
    const categoryIconInput = document.getElementById('categoryIcon');
    const selectedIconElement = document.getElementById('selectedIcon');
    const previewBadge = document.querySelector('.automaten-preview-badge');
    
    let categories = [];
    let currentEditId = null;
    
    // Initial laden
    loadCategories();
    
    // Event Listeners
    addCategoryBtn.addEventListener('click', openAddModal);
    closeModalBtn.addEventListener('click', closeModal);
    cancelBtn.addEventListener('click', closeModal);
    categoryForm.addEventListener('submit', saveCategory);
    
    // Color Picker Events
    categoryColorInput.addEventListener('change', updatePreview);
    categoryNameInput.addEventListener('input', updatePreview);
    
    // Color Presets
    document.querySelectorAll('.automaten-color-preset').forEach(preset => {
        preset.addEventListener('click', function() {
            const color = this.dataset.color;
            categoryColorInput.value = color;
            updateColorPresetSelection(color);
            updatePreview();
        });
    });
    
    // Icon Picker
    document.querySelectorAll('.automaten-icon-option').forEach(option => {
        option.addEventListener('click', function() {
            const icon = this.dataset.icon;
            categoryIconInput.value = icon;
            selectedIconElement.className = icon;
            updateIconSelection(icon);
            updatePreview();
        });
    });
    
    // Modal schließen
    categoryModal.addEventListener('click', function(e) {
        if (e.target === categoryModal) {
            closeModal();
        }
    });
    
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && categoryModal.style.display !== 'none') {
            closeModal();
        }
    });
    
    /**
     * Kategorien laden
     */
    function loadCategories() {
        showLoading();
        
        fetch(automatenAjax.ajaxurl + '?action=automaten_get_categories&nonce=' + automatenAjax.nonce)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    categories = data.data || [];
                    displayCategories(categories);
                } else {
                    showNotification('Fehler beim Laden der Kategorien', 'error');
                    displayCategories([]);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Netzwerkfehler beim Laden', 'error');
                displayCategories([]);
            })
            .finally(() => {
                hideLoading();
            });
    }
    
    /**
     * Kategorien anzeigen
     */
    function displayCategories(categoriesToShow) {
        if (categoriesToShow.length === 0) {
            categoriesContainer.style.display = 'none';
            emptyState.style.display = 'block';
            return;
        }
        
        categoriesContainer.style.display = 'grid';
        emptyState.style.display = 'none';
        
        categoriesContainer.innerHTML = categoriesToShow.map(category => {
            return `
                <div class="automaten-category-card" data-category-id="${category.id}">
                    <div class="automaten-category-header" style="background: linear-gradient(135deg, ${category.color}, ${adjustColor(category.color, -20)});">
                        <div class="automaten-category-icon">
                            <i class="${category.icon}"></i>
                        </div>
                        <h4 class="automaten-category-name">${category.name}</h4>
                        <div class="automaten-category-count">Kategorie erstellt</div>
                    </div>
                    <div class="automaten-category-actions">
                        <button class="automaten-btn automaten-btn-secondary edit-category" data-id="${category.id}">
                            <i class="fas fa-edit"></i>
                            Bearbeiten
                        </button>
                        <button class="automaten-btn automaten-btn-danger delete-category" data-id="${category.id}">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            `;
        }).join('');
        
        // Event Listeners für Aktions-Buttons
        document.querySelectorAll('.edit-category').forEach(btn => {
            btn.addEventListener('click', (e) => editCategory(e.target.dataset.id));
        });
        
        document.querySelectorAll('.delete-category').forEach(btn => {
            btn.addEventListener('click', (e) => deleteCategory(e.target.dataset.id));
        });
    }
    
    /**
     * Farbe anpassen (heller/dunkler)
     */
    function adjustColor(color, percent) {
        const num = parseInt(color.replace("#",""), 16);
        const amt = Math.round(2.55 * percent);
        const R = (num >> 16) + amt;
        const G = (num >> 8 & 0x00FF) + amt;
        const B = (num & 0x0000FF) + amt;
        return "#" + (0x1000000 + (R < 255 ? R < 1 ? 0 : R : 255) * 0x10000 +
            (G < 255 ? G < 1 ? 0 : G : 255) * 0x100 +
            (B < 255 ? B < 1 ? 0 : B : 255)).toString(16).slice(1);
    }
    
    /**
     * Modal öffnen (Hinzufügen)
     */
    function openAddModal() {
        currentEditId = null;
        document.getElementById('modalTitle').textContent = 'Neue Kategorie';
        categoryForm.reset();
        document.getElementById('categoryId').value = '';
        
        // Standard-Werte setzen
        categoryColorInput.value = '#6366f1';
        categoryIconInput.value = 'fas fa-cube';
        selectedIconElement.className = 'fas fa-cube';
        
        updateColorPresetSelection('#6366f1');
        updateIconSelection('fas fa-cube');
        updatePreview();
        
        categoryModal.style.display = 'flex';
        categoryNameInput.focus();
    }
    
    /**
     * Modal öffnen (Bearbeiten)
     */
    function editCategory(categoryId) {
        const category = categories.find(c => c.id == categoryId);
        if (!category) return;
        
        currentEditId = categoryId;
        document.getElementById('modalTitle').textContent = 'Kategorie bearbeiten';
        
        // Formular füllen
        document.getElementById('categoryId').value = category.id;
        categoryNameInput.value = category.name;
        categoryColorInput.value = category.color;
        categoryIconInput.value = category.icon;
        selectedIconElement.className = category.icon;
        
        updateColorPresetSelection(category.color);
        updateIconSelection(category.icon);
        updatePreview();
        
        categoryModal.style.display = 'flex';
        categoryNameInput.focus();
    }
    
    /**
     * Color Preset Selection aktualisieren
     */
    function updateColorPresetSelection(selectedColor) {
        document.querySelectorAll('.automaten-color-preset').forEach(preset => {
            if (preset.dataset.color === selectedColor) {
                preset.classList.add('selected');
            } else {
                preset.classList.remove('selected');
            }
        });
    }
    
    /**
     * Icon Selection aktualisieren
     */
    function updateIconSelection(selectedIcon) {
        document.querySelectorAll('.automaten-icon-option').forEach(option => {
            if (option.dataset.icon === selectedIcon) {
                option.classList.add('selected');
            } else {
                option.classList.remove('selected');
            }
        });
    }
    
    /**
     * Vorschau aktualisieren
     */
    function updatePreview() {
        const name = categoryNameInput.value || 'Beispiel Kategorie';
        const color = categoryColorInput.value;
        const icon = categoryIconInput.value;
        
        previewBadge.style.backgroundColor = color;
        previewBadge.innerHTML = `<i class="${icon}"></i> ${name}`;
    }
    
    /**
     * Modal schließen
     */
    function closeModal() {
        categoryModal.style.display = 'none';
        currentEditId = null;
        categoryForm.reset();
    }
    
    /**
     * Kategorie speichern
     */
    function saveCategory(e) {
        e.preventDefault();
        
        const formData = new FormData();
        formData.append('action', 'automaten_save_category');
        formData.append('nonce', automatenAjax.nonce);
        formData.append('category_id', document.getElementById('categoryId').value);
        formData.append('name', categoryNameInput.value);
        formData.append('color', categoryColorInput.value);
        formData.append('icon', categoryIconInput.value);
        
        // Button deaktivieren
        const saveBtn = document.getElementById('saveCategoryBtn');
        saveBtn.disabled = true;
        saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Speichern...';
        
        fetch(automatenAjax.ajaxurl, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('Kategorie erfolgreich gespeichert!', 'success');
                closeModal();
                loadCategories(); // Neu laden
            } else {
                showNotification(data.data.message || 'Fehler beim Speichern', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('Netzwerkfehler beim Speichern', 'error');
        })
        .finally(() => {
            // Button wieder aktivieren
            saveBtn.disabled = false;
            saveBtn.innerHTML = '<i class="fas fa-save"></i> Speichern';
        });
    }
    
    /**
     * Kategorie löschen
     */
    function deleteCategory(categoryId) {
        if (!confirm(automatenAjax.messages.delete_confirm)) return;
        
        const formData = new FormData();
        formData.append('action', 'automaten_delete_category');
        formData.append('nonce', automatenAjax.nonce);
        formData.append('category_id', categoryId);
        
        fetch(automatenAjax.ajaxurl, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('Kategorie gelöscht!', 'success');
                loadCategories(); // Neu laden
            } else {
                showNotification(data.data.message || 'Fehler beim Löschen', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('Netzwerkfehler beim Löschen', 'error');
        });
    }
    
    /**
     * Loading anzeigen
     */
    function showLoading() {
        loadingIndicator.style.display = 'block';
        categoriesContainer.style.display = 'none';
        emptyState.style.display = 'none';
    }
    
    /**
     * Loading ausblenden
     */
    function hideLoading() {
        loadingIndicator.style.display = 'none';
    }
    
    /**
     * Notification anzeigen
     */
    function showNotification(message, type = 'info') {
        // Einfache Alert-Box (kann später durch Toast ersetzt werden)
        if (type === 'success') {
            alert('✅ ' + message);
        } else if (type === 'error') {
            alert('❌ ' + message);
        } else {
            alert('ℹ️ ' + message);
        }
    }
});
</script>