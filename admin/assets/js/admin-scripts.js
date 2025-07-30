/* AutomatenManager Pro - Admin Scripts REPARIERT */

jQuery(document).ready(function($) {
    
    // === GLOBAL AJAX SETUP === 
    const AMP = {
        ajaxUrl: ampAjax.ajaxurl,
        nonce: ampAjax.nonce,
        
        // AJAX Helper
        ajax: function(action, data = {}, callback = null) {
            data.action = action;
            data.nonce = this.nonce;
            
            return $.ajax({
                url: this.ajaxUrl,
                type: 'POST',
                data: data,
                success: function(response) {
                    if (callback) callback(response);
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Error:', error);
                    AMP.showNotice('Verbindungsfehler: ' + error, 'error');
                }
            });
        },
        
        // Notification System
        showNotice: function(message, type = 'info') {
            const noticeHtml = `
                <div class="notice notice-${type} is-dismissible">
                    <p>${message}</p>
                    <button type="button" class="notice-dismiss">
                        <span class="screen-reader-text">Hinweis schließen</span>
                    </button>
                </div>
            `;
            
            $('.wrap h1').after(noticeHtml);
            
            // Auto-dismiss nach 5 Sekunden
            setTimeout(() => {
                $('.notice').fadeOut();
            }, 5000);
        }
    };
    
    // === PRODUCT MANAGEMENT ===
    if ($('body').hasClass('admin_page_amp-products')) {
        
        // Product Form Handler
        $('#amp-product-form').on('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const isEdit = formData.get('product_id') ? true : false;
            const action = isEdit ? 'amp_update_product' : 'amp_create_product';
            
            // Convert FormData to regular object
            const data = {};
            formData.forEach((value, key) => {
                data[key] = value;
            });
            
            AMP.ajax(action, data, function(response) {
                if (response.success) {
                    AMP.showNotice(response.data.message, 'success');
                    if (!isEdit) {
                        // Redirect nach erfolgreichem Erstellen
                        window.location.href = 'admin.php?page=amp-products';
                    }
                } else {
                    AMP.showNotice(response.data.message, 'error');
                }
            });
        });
        
        // Product Delete Handler
        $('.delete-product').on('click', function(e) {
            e.preventDefault();
            
            if (!confirm('Sind Sie sicher, dass Sie dieses Produkt löschen möchten?')) {
                return;
            }
            
            const productId = $(this).data('product-id');
            
            AMP.ajax('amp_delete_product', {id: productId}, function(response) {
                if (response.success) {
                    AMP.showNotice(response.data.message, 'success');
                    $('#product-' + productId).fadeOut();
                } else {
                    AMP.showNotice(response.data.message, 'error');
                }
            });
        });
        
        // Product Search
        $('#product-search').on('input', function() {
            const searchTerm = $(this).val();
            
            if (searchTerm.length >= 2) {
                AMP.ajax('amp_search_products', {search: searchTerm}, function(response) {
                    if (response.success) {
                        // Update product list
                        updateProductList(response.data);
                    }
                });
            }
        });
        
        // Image Upload Handler
        let mediaUploader;
        
        $('#upload-product-image').on('click', function(e) {
            e.preventDefault();
            
            if (mediaUploader) {
                mediaUploader.open();
                return;
            }
            
            mediaUploader = wp.media({
                title: 'Produktbild auswählen',
                button: {
                    text: 'Bild verwenden'
                },
                multiple: false
            });
            
            mediaUploader.on('select', function() {
                const attachment = mediaUploader.state().get('selection').first().toJSON();
                $('#product-image-id').val(attachment.id);
                $('#product-image-preview').html(`<img src="${attachment.url}" style="max-width: 150px; height: auto;" />`);
            });
            
            mediaUploader.open();
        });
        
        // Remove Image
        $('#remove-product-image').on('click', function(e) {
            e.preventDefault();
            $('#product-image-id').val('');
            $('#product-image-preview').empty();
        });
    }
    
    // === SCANNER SPECIFIC ===
    if ($('body').hasClass('admin_page_amp-scanner')) {
        
        // Scanner wird im Template selbst initialisiert
        console.log('Scanner page loaded');
        
        // Check if Quagga is loaded
        if (typeof Quagga === 'undefined') {
            AMP.showNotice('Scanner-Library (QuaggaJS) nicht geladen. Scanner funktioniert möglicherweise nicht.', 'warning');
        }
    }
    
    // === SALES MANAGEMENT ===
    if ($('body').hasClass('admin_page_amp-sales')) {
        
        // Sales Session Handler
        $('#start-sales-session').on('click', function() {
            AMP.ajax('amp_start_sales_session', {}, function(response) {
                if (response.success) {
                    AMP.showNotice('Verkaufs-Session gestartet', 'success');
                    updateSalesInterface(response.data);
                }
            });
        });
        
        $('#end-sales-session').on('click', function() {
            if (!confirm('Verkaufs-Session beenden?')) return;
            
            AMP.ajax('amp_end_sales_session', {}, function(response) {
                if (response.success) {
                    AMP.showNotice('Session beendet. E-Mail versendet.', 'success');
                    resetSalesInterface();
                }
            });
        });
    }
    
    // === UTILITY FUNCTIONS ===
    function updateProductList(products) {
        const tbody = $('#products-table tbody');
        tbody.empty();
        
        products.forEach(product => {
            const row = `
                <tr id="product-${product.id}">
                    <td>${product.name}</td>
                    <td>${product.barcode}</td>
                    <td>${product.current_stock}</td>
                    <td>${product.sell_price}€</td>
                    <td>
                        <a href="admin.php?page=amp-products&action=edit&id=${product.id}">Bearbeiten</a> |
                        <a href="#" class="delete-product" data-product-id="${product.id}">Löschen</a>
                    </td>
                </tr>
            `;
            tbody.append(row);
        });
    }
    
    function updateSalesInterface(sessionData) {
        $('#sales-session-info').show();
        $('#start-sales-session').hide();
        $('#end-sales-session').show();
    }
    
    function resetSalesInterface() {
        $('#sales-session-info').hide();
        $('#start-sales-session').show();
        $('#end-sales-session').hide();
    }
    
    // === GLOBAL ENHANCEMENTS ===
    
    // Auto-dismiss notices
    $(document).on('click', '.notice-dismiss', function() {
        $(this).parent().fadeOut();
    });
    
    // Confirm dangerous actions
    $('.amp-danger-action').on('click', function(e) {
        const message = $(this).data('confirm') || 'Sind Sie sicher?';
        if (!confirm(message)) {
            e.preventDefault();
            return false;
        }
    });
    
    // Auto-save for long forms
    $('#amp-product-form input, #amp-product-form textarea, #amp-product-form select').on('change', function() {
        // Auto-save draft könnte hier implementiert werden
        console.log('Form field changed:', $(this).attr('name'));
    });
    
    // Loading states
    $(document).ajaxStart(function() {
        $('.amp-loading').show();
    }).ajaxStop(function() {
        $('.amp-loading').hide();
    });
    
    // Make AMP globally available
    window.AMP = AMP;
});