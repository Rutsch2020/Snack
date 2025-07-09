/**
 * Automaten Manager - Modern Admin JavaScript
 * Version: 1.0.0
 */

(function($) {
    'use strict';

    // Wait for DOM ready
    $(document).ready(function() {
        console.log('Automaten Manager Admin Script loaded');

        // Initialize based on current page
        if ($('#am-products-page').length) {
            initProductsPage();
        }
        if ($('#am-categories-page').length) {
            initCategoriesPage();
        }

        // Initialize global features
        initDarkMode();
        initAnimations();
    });

    // Dark Mode Toggle
    function initDarkMode() {
        const $toggle = $('#am-dark-mode-toggle');
        const $body = $('body');
        
        // Check saved preference
        if (localStorage.getItem('am-dark-mode') === 'true') {
            $body.addClass('am-dark-mode');
            $toggle.prop('checked', true);
        }
        
        $toggle.on('change', function() {
            if ($(this).is(':checked')) {
                $body.addClass('am-dark-mode');
                localStorage.setItem('am-dark-mode', 'true');
            } else {
                $body.removeClass('am-dark-mode');
                localStorage.setItem('am-dark-mode', 'false');
            }
        });
    }

    // Initialize Animations
    function initAnimations() {
        // Animate elements on load
        setTimeout(function() {
            $('.am-animate').each(function(index) {
                setTimeout(() => {
                    $(this).addClass('am-animated');
                }, index * 100);
            });
        }, 100);
    }

    // PRODUCTS PAGE
    function initProductsPage() {
        console.log('Initializing Products Page');
        
        // Load initial data
        loadCategories();
        loadProducts();
        
        // Add Product Button
        $('#am-add-product').on('click', function(e) {
            e.preventDefault();
            console.log('Add product clicked');
            openProductModal();
        });
        
        // Save Product Button
        $('#am-save-product').on('click', function(e) {
            e.preventDefault();
            console.log('Save product clicked');
            saveProduct();
        });
        
        // Close Modal
        $('.am-modal-close').on('click', function() {
            $(this).closest('.am-modal').fadeOut(300);
        });
        
        // Close modal on background click
        $('.am-modal').on('click', function(e) {
            if (e.target === this) {
                $(this).fadeOut(300);
            }
        });
        
        // Search functionality
        let searchTimeout;
        $('#am-search-products').on('keyup', function() {
            clearTimeout(searchTimeout);
            const searchTerm = $(this).val().toLowerCase();
            
            searchTimeout = setTimeout(function() {
                $('.am-product-card').each(function() {
                    const $card = $(this);
                    const name = $card.find('.am-product-name').text().toLowerCase();
                    const barcode = $card.find('.am-product-barcode').text().toLowerCase();
                    
                    if (name.includes(searchTerm) || barcode.includes(searchTerm)) {
                        $card.fadeIn(200);
                    } else {
                        $card.fadeOut(200);
                    }
                });
            }, 300);
        });
        
        // Category filter
        $('#am-filter-category').on('change', function() {
            const categoryId = $(this).val();
            
            $('.am-product-card').each(function() {
                const $card = $(this);
                const cardCategory = $card.data('category');
                
                if (!categoryId || cardCategory == categoryId) {
                    $card.fadeIn(200);
                } else {
                    $card.fadeOut(200);
                }
            });
        });
        
        // Sort functionality
        $('#am-sort-products').on('change', function() {
            const sortBy = $(this).val();
            sortProducts(sortBy);
        });
    }

    // Load Products
    function loadProducts() {
        console.log('Loading products...');
        const $container = $('#am-products-container');
        
        // Show loading state
        $container.html('<div class="am-loading"><i class="fas fa-spinner fa-spin fa-3x"></i><p>Lade Produkte...</p></div>');
        
        $.ajax({
            url: am_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'am_get_products',
                nonce: am_ajax.nonce
            },
            success: function(response) {
                console.log('Products response:', response);
                
                if (response.success && response.data && response.data.length > 0) {
                    displayProducts(response.data);
                } else {
                    $container.html(`
                        <div class="am-empty-state">
                            <i class="fas fa-box-open fa-4x"></i>
                            <h3>Keine Produkte vorhanden</h3>
                            <p>Füge dein erstes Produkt hinzu!</p>
                            <button class="am-btn am-btn-primary" onclick="$('#am-add-product').click()">
                                <i class="fas fa-plus"></i> Erstes Produkt anlegen
                            </button>
                        </div>
                    `);
                }
            },
            error: function(xhr, status, error) {
                console.error('Load products error:', error);
                $container.html(`
                    <div class="am-error-state">
                        <i class="fas fa-exclamation-triangle fa-3x"></i>
                        <h3>Fehler beim Laden</h3>
                        <p>Die Produkte konnten nicht geladen werden.</p>
                        <button class="am-btn am-btn-secondary" onclick="loadProducts()">
                            <i class="fas fa-redo"></i> Erneut versuchen
                        </button>
                    </div>
                `);
            }
        });
    }

    // Display Products
    function displayProducts(products) {
        console.log('Displaying products:', products);
        const $container = $('#am-products-container');
        $container.empty();
        
        products.forEach(function(product) {
            const stockClass = product.stock < 10 ? 'am-low-stock' : '';
            const categoryColor = product.category_color || '#6b7280';
            const imageHtml = product.image_url 
                ? `<img src="${product.image_url}" alt="${product.name}" style="width: 100%; height: 200px; object-fit: cover;">` 
                : '<i class="fas fa-image fa-3x"></i>';
            
            const cardHtml = `
                <div class="am-card am-product-card" data-id="${product.id}" data-category="${product.category_id}">
                    <div class="am-product-image-placeholder">
                        ${imageHtml}
                    </div>
                    <div class="am-product-badge" style="background-color: ${categoryColor}20; color: ${categoryColor}">
                        ${product.category_name || 'Keine Kategorie'}
                    </div>
                    <h3 class="am-product-name">${product.name}</h3>
                    <p class="am-product-barcode">
                        <i class="fas fa-barcode"></i> ${product.barcode}
                    </p>
                    <div class="am-product-info">
                        <span class="am-product-price">€${parseFloat(product.price).toFixed(2)}</span>
                        <span class="am-product-stock ${stockClass}">
                            <i class="fas fa-box"></i> ${product.stock}
                        </span>
                    </div>
                    <div class="am-product-actions">
                        <button class="am-btn am-btn-sm am-btn-secondary am-edit-product" 
                                data-product='${JSON.stringify(product).replace(/'/g, "&apos;")}'>
                            <i class="fas fa-edit"></i> Bearbeiten
                        </button>
                        <button class="am-btn am-btn-sm am-btn-danger am-delete-product" 
                                data-id="${product.id}">
                            <i class="fas fa-trash"></i> Löschen
                        </button>
                    </div>
                </div>
            `;
            
            $container.append(cardHtml);
        });
        
        // Attach event handlers
        attachProductEventHandlers();
    }

    // Attach Product Event Handlers
    function attachProductEventHandlers() {
        // Edit Product
        $('.am-edit-product').off('click').on('click', function(e) {
            e.preventDefault();
            const product = $(this).data('product');
            console.log('Edit product:', product);
            openProductModal(product);
        });
        
        // Delete Product
        $('.am-delete-product').off('click').on('click', function(e) {
            e.preventDefault();
            const productId = $(this).data('id');
            deleteProduct(productId);
        });
    }

    // Open Product Modal
    function openProductModal(product = null) {
        console.log('Opening product modal:', product);
        
        // Reset form
        $('#am-product-form')[0].reset();
        
        if (product) {
            // Fill form with product data
            $('#am-product-id').val(product.id);
            $('#am-product-barcode').val(product.barcode);
            $('#am-product-name').val(product.name);
            $('#am-product-price').val(product.price);
            $('#am-product-stock').val(product.stock);
            $('#am-product-category').val(product.category_id);
            $('#am-product-image').val(product.image_url);
            
            // Update modal title
            $('#am-product-modal .am-modal-header h2').text('Produkt bearbeiten');
        } else {
            // Clear ID for new product
            $('#am-product-id').val('');
            
            // Update modal title
            $('#am-product-modal .am-modal-header h2').text('Neues Produkt');
            
            // Check for barcode parameter (from scanner)
            const urlParams = new URLSearchParams(window.location.search);
            const barcodeParam = urlParams.get('barcode');
            if (barcodeParam) {
                $('#am-product-barcode').val(barcodeParam);
            }
        }
        
        // Show modal
        $('#am-product-modal').fadeIn(300);
    }

    // Save Product
    function saveProduct() {
        console.log('Saving product...');
        
        // Get form data
        const formData = {
            action: 'am_save_product',
            nonce: am_ajax.nonce,
            id: $('#am-product-id').val(),
            barcode: $('#am-product-barcode').val().trim(),
            name: $('#am-product-name').val().trim(),
            price: $('#am-product-price').val(),
            stock: $('#am-product-stock').val(),
            category_id: $('#am-product-category').val(),
            image_url: $('#am-product-image').val().trim()
        };
        
        console.log('Form data:', formData);
        
        // Validate
        if (!formData.barcode) {
            showNotification('Bitte Barcode eingeben!', 'error');
            $('#am-product-barcode').focus();
            return;
        }
        
        if (!formData.name) {
            showNotification('Bitte Produktname eingeben!', 'error');
            $('#am-product-name').focus();
            return;
        }
        
        if (!formData.price || formData.price <= 0) {
            showNotification('Bitte gültigen Preis eingeben!', 'error');
            $('#am-product-price').focus();
            return;
        }
        
        if (formData.stock === '' || formData.stock < 0) {
            showNotification('Bitte gültigen Lagerbestand eingeben!', 'error');
            $('#am-product-stock').focus();
            return;
        }
        
        // Show loading state
        const $saveBtn = $('#am-save-product');
        const originalText = $saveBtn.html();
        $saveBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Speichern...');
        
        // Send AJAX request
        $.ajax({
            url: am_ajax.ajax_url,
            type: 'POST',
            data: formData,
            success: function(response) {
                console.log('Save response:', response);
                
                if (response.success) {
                    showNotification('Produkt erfolgreich gespeichert!', 'success');
                    $('#am-product-modal').fadeOut(300);
                    loadProducts(); // Reload products
                } else {
                    showNotification(response.data?.message || 'Fehler beim Speichern', 'error');
                }
            },
            error: function(xhr, status, error) {
                console.error('Save error:', error);
                showNotification('Netzwerkfehler beim Speichern', 'error');
            },
            complete: function() {
                // Reset button
                $saveBtn.prop('disabled', false).html(originalText);
            }
        });
    }

    // Delete Product
    function deleteProduct(productId) {
        console.log('Delete product ID:', productId);
        
        if (!confirm('Möchtest du dieses Produkt wirklich löschen?')) {
            return;
        }
        
        $.ajax({
            url: am_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'am_delete_product',
                id: productId,
                nonce: am_ajax.nonce
            },
            success: function(response) {
                console.log('Delete response:', response);
                
                if (response.success) {
                    showNotification('Produkt gelöscht!', 'success');
                    $(`.am-product-card[data-id="${productId}"]`).fadeOut(300, function() {
                        $(this).remove();
                        // Check if empty
                        if ($('.am-product-card').length === 0) {
                            loadProducts();
                        }
                    });
                } else {
                    showNotification(response.data?.message || 'Fehler beim Löschen', 'error');
                }
            },
            error: function(xhr, status, error) {
                console.error('Delete error:', error);
                showNotification('Netzwerkfehler beim Löschen', 'error');
            }
        });
    }

    // Load Categories
    function loadCategories() {
        console.log('Loading categories...');
        
        $.ajax({
            url: am_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'am_get_categories',
                nonce: am_ajax.nonce
            },
            success: function(response) {
                console.log('Categories response:', response);
                
                if (response.success && response.data) {
                    // Update dropdowns
                    const $productCategory = $('#am-product-category');
                    const $filterCategory = $('#am-filter-category');
                    
                    // Clear existing options
                    $productCategory.find('option:not(:first)').remove();
                    $filterCategory.find('option:not(:first)').remove();
                    
                    // Add categories
                    response.data.forEach(function(category) {
                        const option = `<option value="${category.id}">${category.name}</option>`;
                        $productCategory.append(option);
                        $filterCategory.append(option);
                    });
                }
            },
            error: function(xhr, status, error) {
                console.error('Load categories error:', error);
            }
        });
    }

    // Sort Products
    function sortProducts(sortBy) {
        const $container = $('#am-products-container');
        const $cards = $container.find('.am-product-card').toArray();
        
        $cards.sort(function(a, b) {
            const $a = $(a);
            const $b = $(b);
            
            switch(sortBy) {
                case 'name':
                    return $a.find('.am-product-name').text().localeCompare($b.find('.am-product-name').text());
                case 'price':
                    const priceA = parseFloat($a.find('.am-product-price').text().replace('€', ''));
                    const priceB = parseFloat($b.find('.am-product-price').text().replace('€', ''));
                    return priceA - priceB;
                case 'stock':
                    const stockA = parseInt($a.find('.am-product-stock').text());
                    const stockB = parseInt($b.find('.am-product-stock').text());
                    return stockA - stockB;
                case 'date':
                default:
                    return parseInt($b.data('id')) - parseInt($a.data('id'));
            }
        });
        
        $container.html($cards);
    }

    // CATEGORIES PAGE
    function initCategoriesPage() {
        console.log('Initializing Categories Page');
        loadCategoriesTable();
        
        // Add Category Button
        $('#am-add-category').on('click', function(e) {
            e.preventDefault();
            openCategoryModal();
        });
        
        // Save Category Button
        $('#am-save-category').on('click', function(e) {
            e.preventDefault();
            saveCategory();
        });
    }

    // Load Categories for Table
    function loadCategoriesTable() {
        const $container = $('#am-categories-container');
        
        $container.html('<div class="am-loading"><i class="fas fa-spinner fa-spin fa-3x"></i><p>Lade Kategorien...</p></div>');
        
        $.ajax({
            url: am_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'am_get_categories',
                nonce: am_ajax.nonce
            },
            success: function(response) {
                if (response.success && response.data && response.data.length > 0) {
                    displayCategories(response.data);
                } else {
                    $container.html(`
                        <div class="am-categories-empty">
                            <i class="fas fa-tags fa-4x"></i>
                            <h3>Keine Kategorien vorhanden</h3>
                            <p>Erstelle deine erste Kategorie!</p>
                            <button class="am-btn am-btn-primary" onclick="$('#am-add-category').click()">
                                <i class="fas fa-plus"></i> Erste Kategorie anlegen
                            </button>
                        </div>
                    `);
                }
            },
            error: function(xhr, status, error) {
                console.error('Load categories error:', error);
                $container.html(`
                    <div class="am-error-state">
                        <i class="fas fa-exclamation-triangle fa-3x"></i>
                        <h3>Fehler beim Laden</h3>
                        <p>Die Kategorien konnten nicht geladen werden.</p>
                    </div>
                `);
            }
        });
    }

    // Display Categories
    function displayCategories(categories) {
        const $container = $('#am-categories-container');
        $container.empty();
        
        categories.forEach(function(category) {
            const cardHtml = `
                <div class="am-card am-category-card" data-id="${category.id}" style="--category-color: ${category.color}">
                    <div class="am-category-icon" style="background-color: ${category.color}">
                        <i class="fas fa-${category.icon}"></i>
                    </div>
                    <h3 class="am-category-name">${category.name}</h3>
                    <p class="am-category-count">0 Produkte</p>
                    <div class="am-category-actions">
                        <button class="am-btn am-btn-sm am-btn-secondary am-edit-category" 
                                data-category='${JSON.stringify(category).replace(/'/g, "&apos;")}'>
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="am-btn am-btn-sm am-btn-danger am-delete-category" 
                                data-id="${category.id}">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            `;
            
            $container.append(cardHtml);
        });
        
        // Attach event handlers
        attachCategoryEventHandlers();
    }

    // Attach Category Event Handlers
    function attachCategoryEventHandlers() {
        $('.am-edit-category').off('click').on('click', function(e) {
            e.preventDefault();
            const category = $(this).data('category');
            openCategoryModal(category);
        });
        
        $('.am-delete-category').off('click').on('click', function(e) {
            e.preventDefault();
            const categoryId = $(this).data('id');
            deleteCategory(categoryId);
        });
    }

    // Open Category Modal
    function openCategoryModal(category = null) {
        // Reset form
        $('#am-category-form')[0].reset();
        
        if (category) {
            $('#am-category-id').val(category.id);
            $('#am-category-name').val(category.name);
            $('#am-category-color').val(category.color);
            $('#am-category-icon').val(category.icon);
            $('#am-category-icon-preview').attr('class', 'fas fa-' + category.icon);
            $('#am-category-modal h2').text('Kategorie bearbeiten');
        } else {
            $('#am-category-id').val('');
            $('#am-category-modal h2').text('Neue Kategorie');
        }
        
        $('#am-category-modal').fadeIn(300);
    }

    // Save Category
    function saveCategory() {
        const formData = {
            action: 'am_save_category',
            nonce: am_ajax.nonce,
            id: $('#am-category-id').val(),
            name: $('#am-category-name').val().trim(),
            color: $('#am-category-color').val(),
            icon: $('#am-category-icon').val()
        };
        
        if (!formData.name) {
            showNotification('Bitte Kategoriename eingeben!', 'error');
            return;
        }
        
        const $saveBtn = $('#am-save-category');
        const originalText = $saveBtn.html();
        $saveBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Speichern...');
        
        $.ajax({
            url: am_ajax.ajax_url,
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    showNotification('Kategorie erfolgreich gespeichert!', 'success');
                    $('#am-category-modal').fadeOut(300);
                    loadCategoriesTable();
                } else {
                    showNotification(response.data?.message || 'Fehler beim Speichern', 'error');
                }
            },
            error: function(xhr, status, error) {
                showNotification('Netzwerkfehler beim Speichern', 'error');
            },
            complete: function() {
                $saveBtn.prop('disabled', false).html(originalText);
            }
        });
    }

    // Delete Category
    function deleteCategory(categoryId) {
        if (!confirm('Möchtest du diese Kategorie wirklich löschen?')) {
            return;
        }
        
        $.ajax({
            url: am_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'am_delete_category',
                id: categoryId,
                nonce: am_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    showNotification('Kategorie gelöscht!', 'success');
                    loadCategoriesTable();
                } else {
                    showNotification(response.data?.message || 'Fehler beim Löschen', 'error');
                }
            },
            error: function(xhr, status, error) {
                showNotification('Netzwerkfehler beim Löschen', 'error');
            }
        });
    }

    // Make functions globally available
    window.loadProducts = loadProducts;
    window.loadCategoriesTable = loadCategoriesTable;

})(jQuery);