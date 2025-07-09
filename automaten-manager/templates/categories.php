<?php
/**
 * Categories Template
 */

// Sicherheit
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="am-admin-wrapper" id="am-categories-page">
    <div class="am-container">
        <!-- Header -->
        <div class="am-header">
            <div>
                <h1 class="am-title">Kategorien</h1>
                <p class="am-subtitle">Organisiere deine Produkte in Kategorien</p>
            </div>
            <div class="am-header-actions">
                <button class="am-btn am-btn-primary" id="am-add-category">
                    <i class="fas fa-plus"></i>
                    Neue Kategorie
                </button>
            </div>
        </div>
        
        <!-- Categories Grid -->
        <div class="am-grid am-grid-3" id="am-categories-container">
            <!-- Kategorien werden hier via JavaScript geladen -->
        </div>
    </div>
    
    <!-- Category Modal -->
    <div id="am-category-modal" class="am-modal">
        <div class="am-modal-content">
            <div class="am-modal-header">
                <h2>Kategorie bearbeiten</h2>
                <span class="am-modal-close">&times;</span>
            </div>
            <form id="am-category-form" class="am-form">
                <input type="hidden" id="am-category-id" name="id">
                
                <div class="am-form-group">
                    <label class="am-label">Kategoriename</label>
                    <input type="text" 
                           id="am-category-name" 
                           name="name" 
                           class="am-input" 
                           placeholder="z.B. Getränke" 
                           required>
                </div>
                
                <div class="am-form-group">
                    <label class="am-label">Farbe</label>
                    <div class="am-color-picker">
                        <input type="color" 
                               id="am-category-color" 
                               name="color" 
                               class="am-color-input" 
                               value="#3498db">
                        <div class="am-color-presets">
                            <button type="button" class="am-color-preset" style="background: #3498db" data-color="#3498db"></button>
                            <button type="button" class="am-color-preset" style="background: #e74c3c" data-color="#e74c3c"></button>
                            <button type="button" class="am-color-preset" style="background: #f39c12" data-color="#f39c12"></button>
                            <button type="button" class="am-color-preset" style="background: #27ae60" data-color="#27ae60"></button>
                            <button type="button" class="am-color-preset" style="background: #9b59b6" data-color="#9b59b6"></button>
                            <button type="button" class="am-color-preset" style="background: #34495e" data-color="#34495e"></button>
                        </div>
                    </div>
                </div>
                
                <div class="am-form-group">
                    <label class="am-label">Icon</label>
                    <div class="am-icon-picker">
                        <div class="am-icon-preview">
                            <i class="fas fa-box" id="am-category-icon-preview"></i>
                        </div>
                        <select id="am-category-icon" name="icon" class="am-select">
                            <option value="box">📦 Box (Standard)</option>
                            <option value="coffee">☕ Kaffee</option>
                            <option value="cookie">🍪 Cookie</option>
                            <option value="candy-cane">🍬 Süßigkeit</option>
                            <option value="apple-alt">🍎 Apfel</option>
                            <option value="bottle-water">💧 Wasser</option>
                            <option value="beer">🍺 Bier</option>
                            <option value="pizza-slice">🍕 Pizza</option>
                            <option value="hamburger">🍔 Burger</option>
                            <option value="ice-cream">🍦 Eis</option>
                        </select>
                    </div>
                </div>
                
                <div class="am-modal-footer">
                    <button type="button" class="am-btn am-btn-secondary" onclick="$('#am-category-modal').fadeOut(300);">
                        Abbrechen
                    </button>
                    <button type="button" id="am-save-category" class="am-btn am-btn-primary">
                        <i class="fas fa-save"></i>
                        Speichern
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
/* Categories Page Specific Styles */
.am-category-card {
    text-align: center;
    cursor: pointer;
    transition: all var(--transition-normal);
    position: relative;
    overflow: hidden;
}

.am-category-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 5px;
    background: var(--category-color, #3498db);
}

.am-category-icon {
    width: 80px;
    height: 80px;
    margin: 2rem auto 1rem;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    font-size: 2.5rem;
    background: var(--category-color, #3498db);
    color: white;
    position: relative;
}

.am-category-icon::after {
    content: '';
    position: absolute;
    width: 100%;
    height: 100%;
    border-radius: 50%;
    background: inherit;
    opacity: 0.3;
    animation: pulse 2s infinite;
}

.am-category-name {
    font-size: 1.25rem;
    font-weight: 600;
    margin: 0.5rem 0;
}

.am-category-count {
    color: var(--gray-500);
    font-size: 0.875rem;
}

.am-category-actions {
    display: flex;
    gap: 0.5rem;
    justify-content: center;
    margin-top: 1rem;
}

/* Color Picker */
.am-color-picker {
    display: flex;
    gap: 1rem;
    align-items: center;
}

.am-color-input {
    width: 60px;
    height: 40px;
    border: 2px solid var(--gray-200);
    border-radius: 0.5rem;
    cursor: pointer;
}

.am-color-presets {
    display: flex;
    gap: 0.5rem;
}

.am-color-preset {
    width: 30px;
    height: 30px;
    border-radius: 50%;
    border: 2px solid var(--gray-200);
    cursor: pointer;
    transition: all var(--transition-fast);
}

.am-color-preset:hover {
    transform: scale(1.2);
    border-color: var(--gray-400);
}

/* Icon Picker */
.am-icon-picker {
    display: flex;
    gap: 1rem;
    align-items: center;
}

.am-icon-preview {
    width: 60px;
    height: 60px;
    border: 2px solid var(--gray-200);
    border-radius: 0.75rem;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    background: var(--gray-50);
}

/* Empty State */
.am-categories-empty {
    text-align: center;
    padding: 4rem 2rem;
    grid-column: 1 / -1;
}

.am-categories-empty i {
    color: var(--gray-300);
    margin-bottom: 1rem;
}

.am-categories-empty h3 {
    color: var(--gray-600);
    margin: 1rem 0 0.5rem 0;
}

.am-categories-empty p {
    color: var(--gray-500);
}
</style>

<script>
jQuery(document).ready(function($) {
    // Icon Preview Update
    $('#am-category-icon').on('change', function() {
        const icon = $(this).val();
        $('#am-category-icon-preview').attr('class', 'fas fa-' + icon);
    });
    
    // Color Preset Click
    $('.am-color-preset').on('click', function() {
        const color = $(this).data('color');
        $('#am-category-color').val(color);
    });
    
    // Load Categories on Init
    if (typeof AutomatenManager !== 'undefined' && typeof AutomatenManager.loadCategoriesTable === 'function') {
        AutomatenManager.loadCategoriesTable();
    }
});
</script>