<?php
/**
 * Categories Template for AutomatenManager Pro
 * 
 * @package     AutomatenManagerPro
 * @subpackage  Templates
 * @version     1.0.0
 * @since       1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="amp-admin-page">
    <div class="amp-page-header">
        <h1><span class="amp-icon amp-icon-category"></span>Kategorienverwaltung</h1>
        <p>Organisieren Sie Ihre Produkte in übersichtlichen Kategorien</p>
    </div>

    <div class="amp-card">
        <div class="amp-card-header">
            <h2 class="amp-card-title">Kategorien</h2>
            <div>
                <input type="text" class="amp-search" data-table="#categories-table" placeholder="Kategorien suchen..." style="margin-right: 10px;">
                <a href="#" class="amp-btn amp-btn-primary" data-modal="category-modal">
                    <span class="amp-icon amp-icon-plus"></span>Neue Kategorie
                </a>
            </div>
        </div>

        <div class="amp-table">
            <table id="categories-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Beschreibung</th>
                        <th>Reihenfolge</th>
                        <th width="200">Aktionen</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td colspan="4" style="text-align: center; padding: 40px;">
                            <span class="amp-loading"></span> Lade Kategorien...
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Category Modal -->
<div id="category-modal" class="amp-modal">
    <div class="amp-modal-content">
        <div class="amp-modal-header">
            <h2><span class="amp-icon amp-icon-category"></span>Neue Kategorie</h2>
            <button class="amp-modal-close">&times;</button>
        </div>
        <div class="amp-modal-body">
            <form class="amp-form" data-action="amp_create_category">
                <div class="amp-form-group required">
                    <label for="category-name">Kategorie-Name</label>
                    <input type="text" id="category-name" name="name" required>
                </div>

                <div class="amp-form-group">
                    <label for="category-description">Beschreibung</label>
                    <textarea id="category-description" name="description" rows="3" placeholder="Optionale Beschreibung der Kategorie"></textarea>
                </div>

                <div class="amp-form-row">
                    <div class="amp-form-group">
                        <label for="category-color">Farbe</label>
                        <div class="amp-color-picker">
                            <input type="color" id="category-color" name="color" value="#007cba">
                            <span class="color-preview" style="display:inline-block;width:30px;height:30px;border-radius:5px;background:#007cba;border:2px solid #ddd;"></span>
                        </div>
                    </div>
                    <div class="amp-form-group">
                        <label for="category-sort-order">Reihenfolge</label>
                        <input type="number" id="category-sort-order" name="sort_order" min="0" value="0">
                    </div>
                </div>

                <div class="amp-form-group">
                    <label for="category-parent">Übergeordnete Kategorie</label>
                    <select id="category-parent" name="parent_id">
                        <option value="">Keine (Hauptkategorie)</option>
                    </select>
                </div>

                <div class="amp-form-actions">
                    <button type="button" class="amp-btn amp-btn-secondary amp-modal-cancel">Abbrechen</button>
                    <button type="submit" class="amp-btn amp-btn-primary">
                        <span class="amp-icon amp-icon-save"></span>Kategorie Speichern
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Load categories when page loads
    AMP.Categories.loadCategories();
    
    // Update color preview when color changes
    $(document).on("change", "#category-color", function() {
        var color = $(this).val();
        $(".color-preview").css("background-color", color);
    });
    
    // Load parent categories when modal opens
    $(document).on("click", '[data-modal="category-modal"]', function() {
        // Load categories for parent select
        var data = {
            action: "amp_load_categories",
            nonce: ampAdmin.nonce
        };
        
        $.post(ampAdmin.ajaxUrl, data, function(response) {
            if (response.success) {
                var parentSelect = $("#category-parent");
                parentSelect.empty().append('<option value="">Keine (Hauptkategorie)</option>');
                
                response.data.categories.forEach(function(category) {
                    parentSelect.append('<option value="' + category.id + '">' + category.name + '</option>');
                });
            }
        });
    });
    
    // Auto-generate sort order
    $(document).on("focus", "#category-sort-order", function() {
        if (!$(this).val()) {
            // Get highest sort order + 10
            var maxOrder = 0;
            $("#categories-table tbody tr").each(function() {
                var order = parseInt($(this).find("td:eq(2)").text()) || 0;
                if (order > maxOrder) maxOrder = order;
            });
            $(this).val(maxOrder + 10);
        }
    });
});
</script>