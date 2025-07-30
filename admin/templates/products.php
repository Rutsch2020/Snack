<?php
/**
 * Products Template for AutomatenManager Pro
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
        <h1><span class="amp-icon amp-icon-product"></span>Produktverwaltung</h1>
        <p>Verwalten Sie Ihre Produkte, Barcodes und Lagerbestände</p>
    </div>

    <div class="amp-card">
        <div class="amp-card-header">
            <h2 class="amp-card-title">Produkte</h2>
            <div>
                <input type="text" class="amp-search" data-table="#products-table" placeholder="Produkte suchen..." style="margin-right: 10px;">
                <a href="#" class="amp-btn amp-btn-primary" data-modal="product-modal">
                    <span class="amp-icon amp-icon-plus"></span>Neues Produkt
                </a>
            </div>
        </div>

        <div class="amp-table">
            <table id="products-table">
                <thead>
                    <tr>
                        <th>Produktname</th>
                        <th>Barcode</th>
                        <th>Kategorie</th>
                        <th>Verkaufspreis</th>
                        <th>Lager</th>
                        <th width="200">Aktionen</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td colspan="6" style="text-align: center; padding: 40px;">
                            <span class="amp-loading"></span> Lade Produkte...
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Product Modal -->
<div id="product-modal" class="amp-modal">
    <div class="amp-modal-content">
        <div class="amp-modal-header">
            <h2><span class="amp-icon amp-icon-product"></span>Neues Produkt</h2>
            <button class="amp-modal-close">&times;</button>
        </div>
        <div class="amp-modal-body">
            <form class="amp-form" data-action="amp_create_product">
                <div class="amp-form-row">
                    <div class="amp-form-group required">
                        <label for="product-name">Produktname</label>
                        <input type="text" id="product-name" name="name" required>
                    </div>
                    <div class="amp-form-group">
                        <label for="product-category">Kategorie</label>
                        <select id="product-category" name="category_id">
                            <option value="">Keine Kategorie</option>
                        </select>
                    </div>
                </div>

                <div class="amp-form-row">
                    <div class="amp-form-group required">
                        <label for="product-barcode">Barcode</label>
                        <div class="amp-barcode-field">
                            <input type="text" id="product-barcode" name="barcode" required pattern="[0-9]{8,13}">
                            <button type="button" class="amp-btn amp-btn-sm amp-generate-barcode">
                                <span class="amp-icon amp-icon-search"></span>Generieren
                            </button>
                        </div>
                    </div>
                </div>

                <div class="amp-form-group">
                    <label for="product-description">Beschreibung</label>
                    <textarea id="product-description" name="description" rows="3"></textarea>
                </div>

                <div class="amp-form-row">
                    <div class="amp-form-group required">
                        <label for="product-buy-price">Einkaufspreis (€)</label>
                        <input type="number" id="product-buy-price" name="buy_price" step="0.01" min="0">
                    </div>
                    <div class="amp-form-group required">
                        <label for="product-sell-price">Verkaufspreis (€)</label>
                        <input type="number" id="product-sell-price" name="sell_price" step="0.01" min="0" required>
                    </div>
                </div>

                <div class="amp-form-row">
                    <div class="amp-form-group">
                        <label for="product-vat-rate">MwSt-Satz (%)</label>
                        <select id="product-vat-rate" name="vat_rate">
                            <option value="19.00">19% (Standard)</option>
                            <option value="7.00">7% (Ermäßigt)</option>
                            <option value="0.00">0% (Befreit)</option>
                        </select>
                    </div>
                    <div class="amp-form-group">
                        <label for="product-deposit">Pfand (€)</label>
                        <input type="number" id="product-deposit" name="deposit" step="0.01" min="0" value="0">
                    </div>
                </div>

                <div class="amp-form-row">
                    <div class="amp-form-group">
                        <label for="product-current-stock">Aktueller Bestand</label>
                        <input type="number" id="product-current-stock" name="current_stock" min="0" value="0">
                    </div>
                    <div class="amp-form-group">
                        <label for="product-min-stock">Mindestbestand</label>
                        <input type="number" id="product-min-stock" name="min_stock" min="0" value="0">
                    </div>
                </div>

                <div class="amp-form-group">
                    <label for="product-expiry-date">Haltbarkeitsdatum</label>
                    <input type="date" id="product-expiry-date" name="expiry_date">
                </div>

                <div class="amp-form-actions">
                    <button type="button" class="amp-btn amp-btn-secondary amp-modal-cancel">Abbrechen</button>
                    <button type="submit" class="amp-btn amp-btn-primary">
                        <span class="amp-icon amp-icon-save"></span>Produkt Speichern
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Load products when page loads
    AMP.Products.loadProducts();
    
    // Load categories for select
    AMP.Categories.loadCategories();
    
    // Auto-calculate buy price based on sell price (simple margin)
    $(document).on("blur", "#product-sell-price", function() {
        var sellPrice = parseFloat($(this).val());
        var buyPriceField = $("#product-buy-price");
        
        if (sellPrice > 0 && !buyPriceField.val()) {
            // Assume 30% margin
            var buyPrice = (sellPrice * 0.7).toFixed(2);
            buyPriceField.val(buyPrice);
        }
    });
    
    // Validate barcode format
    $(document).on("blur", "#product-barcode", function() {
        var barcode = $(this).val();
        if (barcode && !AMP.validateBarcode(barcode)) {
            $(this).addClass("error");
            AMP.showAlert("error", "Barcode muss 8-13 Ziffern enthalten!");
        } else {
            $(this).removeClass("error");
        }
    });
});
</script>