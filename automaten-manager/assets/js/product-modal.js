// Enhanced Product Creation Modal JavaScript
(function($) {
    'use strict';
    
    let currentStep = 1;
    let captureStream = null;
    let photoCanvas = null;
    let photoContext = null;
    let videoElement = null;
    let isMobileDevice = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);

    // Initialize Product Creation Modal (make it globally available)
    window.initProductModal = function(barcode) {
        console.log('Opening product creation modal with barcode:', barcode);
        
        // Close scanner modal if open
        $('#am-scanner-modal').removeClass('active');
        
        // Set barcode
        $('#am-new-product-barcode').text(barcode || 'Wird automatisch erkannt');
        $('#am-new-product-barcode-input').val(barcode || '');
        
        // Reset form
        $('#am-product-create-form')[0].reset();
        currentStep = 1;
        updateSteps();
        
        // Load categories
        loadCategoriesForModal();
        
        // Reset image preview and camera state
        resetImagePreview();
        
        // Show modal with slight delay to ensure scanner modal is closed
        setTimeout(() => {
            $('#am-product-create-modal').fadeIn(300);
        }, 100);
    };
    
    // Reset image preview
    function resetImagePreview() {
        $('#am-new-image-preview').html('<i class="fas fa-camera"></i><span>Foto aufnehmen oder URL eingeben</span>');
        $('#am-new-product-image-url').val('');
        $('.am-url-input-group').hide();
        
        // Stop camera if active
        if (captureStream) {
            captureStream.getTracks().forEach(track => track.stop());
            captureStream = null;
        }
        
        if (videoElement) {
            videoElement.style.display = 'none';
            videoElement.srcObject = null;
        }
    }
    
    // Update form steps
    function updateSteps() {
        $('.am-step').removeClass('active completed');
        $('.am-form-step-content').removeClass('active');
        
        // Mark completed steps
        for (let i = 1; i < currentStep; i++) {
            $(`.am-step[data-step="${i}"]`).addClass('completed');
        }
        
        // Mark current step
        $(`.am-step[data-step="${currentStep}"]`).addClass('active');
        $(`.am-form-step-content[data-step="${currentStep}"]`).addClass('active');
        
        // Animate form groups
        $(`.am-form-step-content[data-step="${currentStep}"] .am-animated`).each(function(index) {
            const $this = $(this);
            $this.css({ 'opacity': '0', 'transform': 'translateY(20px)' });
            setTimeout(() => {
                $this.css({ 'opacity': '1', 'transform': 'translateY(0)' });
            }, index * 100);
        });
    }
    
    // Load categories for modal
    function loadCategoriesForModal() {
        const $container = $('#am-new-category-select');
        $container.html(`
            <div class="am-loading" style="grid-column: 1 / -1; color: white;">
                <i class="fas fa-spinner fa-spin"></i>
                <p>Kategorien werden geladen...</p>
            </div>
        `);
        
        $.ajax({
            url: am_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'am_get_categories',
                nonce: am_ajax.nonce
            },
            success: function(response) {
                if (response.success && response.data) {
                    $container.empty();
                    
                    // Add "No Category" option
                    $container.append(`
                        <div class="am-category-option selected" data-id="">
                            <i class="fas fa-times-circle" style="color: #6b7280;"></i>
                            <span>Keine</span>
                        </div>
                    `);
                    
                    // Add categories
                    response.data.forEach(function(category) {
                        const icon = category.icon || 'box';
                        const color = category.color || '#6b7280';
                        
                        $container.append(`
                            <div class="am-category-option" data-id="${category.id}" style="border-color: ${color}20;">
                                <i class="fas fa-${icon}" style="color: ${color};"></i>
                                <span>${category.name}</span>
                            </div>
                        `);
                    });
                } else {
                    showNotification('Kategorien konnten nicht geladen werden.', 'error');
                    $container.html(`<div class="am-empty-state" style="grid-column: 1 / -1; color: rgba(255,255,255,0.5);">
                                        <i class="fas fa-box-open"></i><p>Keine Kategorien gefunden.</p>
                                    </div>`);
                }
            },
            error: function() {
                showNotification('Netzwerkfehler beim Laden der Kategorien.', 'error');
                $container.html(`<div class="am-error-state" style="grid-column: 1 / -1; color: var(--danger);">
                                    <i class="fas fa-exclamation-triangle"></i><p>Fehler beim Laden.</p>
                                </div>`);
            }
        });
    }
    
    // Camera capture functionality
    function initCamera() {
        resetImagePreview();
        
        $('#am-new-image-preview').html(`
            <div class="am-camera-preview">
                <video id="am-photo-video" autoplay playsinline muted style="width:100%; max-width: 100%; border-radius: 8px;"></video>
                <canvas id="am-photo-canvas" style="display:none;"></canvas>
                <button type="button" class="am-btn am-btn-primary" id="am-capture-button" style="margin-top: 10px;">
                    <i class="fas fa-camera"></i> Foto aufnehmen
                </button>
                <button type="button" class="am-btn am-btn-secondary" id="am-cancel-camera" style="margin-top: 10px;">
                    <i class="fas fa-times"></i> Abbrechen
                </button>
            </div>
        `);
        
        videoElement = document.getElementById('am-photo-video');
        photoCanvas = document.getElementById('am-photo-canvas');
        
        if (!videoElement || !photoCanvas) {
            console.error('Video or Canvas element not found');
            showNotification('Kamera-Elemente nicht gefunden.', 'error');
            return;
        }
        
        photoContext = photoCanvas.getContext('2d');
        
        const constraints = {
            video: {
                facingMode: isMobileDevice ? { ideal: "environment" } : "user",
                width: { ideal: 1280 },
                height: { ideal: 720 }
            }
        };
        
        navigator.mediaDevices.getUserMedia(constraints)
            .then(function(stream) {
                captureStream = stream;
                videoElement.srcObject = stream;
                videoElement.play();
                showNotification('Kamera bereit.', 'info');
            })
            .catch(function(err) {
                console.error('Camera error:', err);
                handleCameraError(err);
            });
    }
    
    // Handle camera errors
    function handleCameraError(err) {
        let errorMessage = 'Kamera konnte nicht gestartet werden. ';
        
        if (err.name === 'NotAllowedError' || err.name === 'PermissionDeniedError') {
            errorMessage += 'Bitte erlaube den Kamerazugriff.';
        } else if (err.name === 'NotFoundError') {
            errorMessage += 'Keine Kamera gefunden.';
        } else {
            errorMessage += err.message || 'Unbekannter Fehler.';
        }
        
        showNotification(errorMessage, 'error');
        
        $('#am-new-image-preview').html(`
            <div style="text-align: center; padding: 20px; color: var(--danger);">
                <i class="fas fa-exclamation-circle fa-2x"></i>
                <p>${errorMessage}</p>
                <button type="button" class="am-btn am-btn-secondary" id="am-retry-camera" style="margin-top: 10px;">
                    <i class="fas fa-redo"></i> Erneut versuchen
                </button>
            </div>
        `);
    }
    
    // Capture photo from camera
    function capturePhoto() {
        if (!videoElement || !photoCanvas || !photoContext || !captureStream) {
            showNotification('Kamera ist nicht aktiv.', 'error');
            return;
        }

        photoCanvas.width = videoElement.videoWidth;
        photoCanvas.height = videoElement.videoHeight;
        photoContext.drawImage(videoElement, 0, 0);
        
        const dataUrl = photoCanvas.toDataURL('image/jpeg', 0.8);
        
        $('#am-new-image-preview').html(`
            <img src="${dataUrl}" style="max-width: 100%; max-height: 300px; border-radius: 8px;">
            <button type="button" class="am-btn am-btn-secondary am-btn-sm" id="am-retake-photo" style="margin-top: 10px;">
                <i class="fas fa-redo"></i> Neu aufnehmen
            </button>
        `);
        
        // Stop camera
        if (captureStream) {
            captureStream.getTracks().forEach(track => track.stop());
            captureStream = null;
        }
        
        $('#am-new-product-image-url').val(dataUrl);
        showNotification('Foto aufgenommen!', 'success');
    }
    
    // Validate form step
    function validateStep(step) {
        let isValid = true;
        
        switch(step) {
            case 1:
                const name = $('#am-new-product-name').val().trim();
                const price = $('#am-new-product-price').val();
                const stock = $('#am-new-product-stock').val();
                const barcode = $('#am-new-product-barcode-input').val().trim();

                if (!barcode) {
                    showNotification('Barcode erforderlich.', 'error');
                    isValid = false;
                } else if (!name) {
                    showNotification('Produktname erforderlich.', 'error');
                    $('#am-new-product-name').focus();
                    isValid = false;
                } else if (!price || parseFloat(price) <= 0) {
                    showNotification('Gültiger Preis erforderlich.', 'error');
                    $('#am-new-product-price').focus();
                    isValid = false;
                } else if (stock === '' || parseInt(stock) < 0) {
                    showNotification('Gültiger Bestand erforderlich.', 'error');
                    $('#am-new-product-stock').focus();
                    isValid = false;
                }
                break;
        }
        
        return isValid;
    }
    
    // Save new product
    function saveNewProduct() {
        const $saveBtn = $('#am-save-new-product');
        const originalText = $saveBtn.html();
        
        $saveBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Speichern...');
        
        const formData = {
            action: 'am_save_product',
            nonce: am_ajax.nonce,
            barcode: $('#am-new-product-barcode-input').val(),
            name: $('#am-new-product-name').val().trim(),
            price: $('#am-new-product-price').val(),
            stock: $('#am-new-product-stock').val(),
            category_id: $('#am-new-product-category').val() || '',
            image_url: $('#am-new-product-image-url').val() || '',
            description: $('#am-new-product-description').val() || '',
            min_stock: $('#am-new-product-min-stock').val() || 10
        };
        
        $.ajax({
            url: am_ajax.ajax_url,
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    if (typeof playBeep === 'function') {
                        playBeep('success');
                    }
                    showNotification('Produkt erfolgreich angelegt!', 'success');
                    
                    // Show success animation
                    $('#am-product-create-modal .am-modal-content').html(`
                        <div style="text-align: center; padding: 40px 20px;">
                            <div style="font-size: 60px; color: #10b981; margin-bottom: 20px;">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <h2 style="color: white; margin-bottom: 15px;">Erfolgreich angelegt!</h2>
                            <p style="color: rgba(255,255,255,0.7); margin-bottom: 25px;">
                                "${formData.name}" wurde gespeichert.
                            </p>
                            <div style="display: flex; gap: 10px; justify-content: center; flex-wrap: wrap;">
                                <button class="am-btn am-btn-primary" onclick="window.location.href='${automatenManager.adminUrl}admin.php?page=am-products'">
                                    <i class="fas fa-list"></i> Produktliste
                                </button>
                                <button class="am-btn am-btn-secondary" id="am-create-another">
                                    <i class="fas fa-plus"></i> Weiteres anlegen
                                </button>
                                <button class="am-btn am-btn-success" id="am-continue-scanning">
                                    <i class="fas fa-barcode"></i> Weiterscannen
                                </button>
                            </div>
                        </div>
                    `);

                    // Reload products if function exists
                    if (typeof loadProducts === 'function') {
                        loadProducts();
                    }
                } else {
                    showNotification(response.data?.message || 'Fehler beim Speichern.', 'error');
                    $saveBtn.prop('disabled', false).html(originalText);
                }
            },
            error: function(xhr, status, error) {
                console.error('Save error:', error);
                showNotification('Netzwerkfehler beim Speichern.', 'error');
                $saveBtn.prop('disabled', false).html(originalText);
            }
        });
    }
    
    // Initialize event handlers
    $(document).ready(function() {
        // Remove any existing handlers first to prevent duplicates
        $(document).off('.productModal');
        
        // Step navigation
        $(document).on('click.productModal', '.am-next-step', function() {
            const nextStep = $(this).data('next');
            if (validateStep(currentStep)) {
                currentStep = nextStep;
                updateSteps();
            }
        });
        
        $(document).on('click.productModal', '.am-prev-step', function() {
            const prevStep = $(this).data('prev');
            currentStep = prevStep;
            updateSteps();
        });
        
        // Step click navigation
        $(document).on('click.productModal', '.am-step.completed', function() {
            const clickedStep = parseInt($(this).data('step'));
            if (clickedStep < currentStep) {
                currentStep = clickedStep;
                updateSteps();
            }
        });
        
        // Category selection
        $(document).on('click.productModal', '.am-category-option', function() {
            $('.am-category-option').removeClass('selected');
            $(this).addClass('selected');
            $('#am-new-product-category').val($(this).data('id'));
        });
        
        // Image upload options
        $(document).on('click.productModal', '#am-capture-photo', function() {
            initCamera();
        });
        
        $(document).on('click.productModal', '#am-upload-file', function() {
            $('#am-file-input').click();
        });
        
        $(document).on('click.productModal', '#am-url-input', function() {
            $('.am-url-input-group').slideToggle(200);
        });
        
        // File input change
        $(document).on('change.productModal', '#am-file-input', function(e) {
            const file = e.target.files[0];
            if (file && file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    $('#am-new-image-preview').html(`
                        <img src="${e.target.result}" style="max-width: 100%; max-height: 300px; border-radius: 8px;">
                    `);
                    $('#am-new-product-image-url').val(e.target.result);
                    showNotification('Bild geladen!', 'success');
                };
                reader.readAsDataURL(file);
            } else if (file) {
                showNotification('Bitte wähle eine Bilddatei.', 'error');
            }
        });
        
        // URL input change
        $(document).on('input.productModal', '#am-new-product-image-url', function() {
            const url = $(this).val();
            if (url && !url.startsWith('data:')) {
                $('#am-new-image-preview').html(`
                    <img src="${url}" style="max-width: 100%; max-height: 300px; border-radius: 8px;" 
                         onerror="this.onerror=null; this.parentElement.innerHTML='<i class=\\'fas fa-exclamation-triangle\\'></i><span>Bild konnte nicht geladen werden</span>';">
                `);
            }
        });
        
        // Camera actions
        $(document).on('click.productModal', '#am-capture-button', function() {
            capturePhoto();
        });
        
        $(document).on('click.productModal', '#am-retake-photo, #am-retry-camera', function() {
            initCamera();
        });
        
        $(document).on('click.productModal', '#am-cancel-camera', function() {
            resetImagePreview();
        });
        
        // Save product
        $(document).on('click.productModal', '#am-save-new-product', function() {
            if (validateStep(3)) {
                saveNewProduct();
            }
        });
        
        // Success actions
        $(document).on('click.productModal', '#am-create-another', function() {
            $('#am-product-create-modal').fadeOut(300, function() {
                window.initProductModal('');
            });
        });
        
        $(document).on('click.productModal', '#am-continue-scanning', function() {
            $('#am-product-create-modal').fadeOut(300, function() {
                if (typeof initScanner === 'function') {
                    initScanner();
                } else {
                    window.location.href = automatenManager.adminUrl + 'admin.php?page=am-scanner';
                }
            });
        });
        
        // Close modal
        $(document).on('click.productModal', '#am-product-create-modal .am-modal-close', function() {
            $('#am-product-create-modal').fadeOut(300);
            
            // Stop camera if active
            if (captureStream) {
                captureStream.getTracks().forEach(track => track.stop());
                captureStream = null;
            }
            
            // Restart scanner if it was active
            if ($('.am-scanner-wrapper').hasClass('active') && typeof Quagga !== 'undefined') {
                setTimeout(() => {
                    Quagga.start();
                }, 500);
            }
        });
        
        // Prevent modal close on content click
        $(document).on('click.productModal', '#am-product-create-modal', function(e) {
            if (e.target === this) {
                $(this).find('.am-modal-close').click();
            }
        });
    });
    
})(jQuery);