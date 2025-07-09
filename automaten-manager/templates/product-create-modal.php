<?php
/**
 * Product Creation Modal Template
 */

// Sicherheit
if (!defined('ABSPATH')) {
    exit;
}
?>

<div id="am-product-create-modal" class="am-modal" style="display: none;">
    <div class="am-modal-content">
        <div class="am-modal-header">
            <h2><i class="fas fa-plus-circle"></i> Neues Produkt anlegen</h2>
            <button class="am-modal-close" type="button">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M18 6L6 18M6 6l12 12"/>
                </svg>
            </button>
        </div>
        <form id="am-product-create-form" class="am-form">
            <div class="am-form-steps">
                <div class="am-step active" data-step="1">
                    <div class="am-step-icon"><i class="fas fa-info"></i></div>
                    <div class="am-step-label">Basis Info</div>
                </div>
                <div class="am-step" data-step="2">
                    <div class="am-step-icon"><i class="fas fa-tags"></i></div>
                    <div class="am-step-label">Kategorien</div>
                </div>
                <div class="am-step" data-step="3">
                    <div class="am-step-icon"><i class="fas fa-image"></i></div>
                    <div class="am-step-label">Bild</div>
                </div>
            </div>

            <div class="am-form-step-content active" data-step="1">
                <div class="am-form-group am-animated" style="opacity:0;">
                    <label class="am-label">Barcode</label>
                    <span id="am-new-product-barcode" class="am-input-display">Wird automatisch erkannt</span>
                    <input type="hidden" id="am-new-product-barcode-input" name="barcode">
                </div>
                
                <div class="am-form-group am-animated" style="opacity:0; animation-delay: 0.1s;">
                    <label class="am-label">Produktname *</label>
                    <input type="text" 
                           id="am-new-product-name" 
                           name="name" 
                           class="am-input" 
                           placeholder="z.B. Coca Cola 0.5L" 
                           required>
                </div>
                
                <div class="am-grid am-grid-2">
                    <div class="am-form-group am-animated" style="opacity:0; animation-delay: 0.2s;">
                        <label class="am-label">Preis (€) *</label>
                        <input type="number" 
                               id="am-new-product-price" 
                               name="price" 
                               class="am-input" 
                               step="0.01" 
                               min="0" 
                               placeholder="1.50" 
                               required>
                    </div>
                    
                    <div class="am-form-group am-animated" style="opacity:0; animation-delay: 0.3s;">
                        <label class="am-label">Lagerbestand *</label>
                        <input type="number" 
                               id="am-new-product-stock" 
                               name="stock" 
                               class="am-input" 
                               min="0" 
                               placeholder="50" 
                               required>
                    </div>
                </div>

                <div class="am-modal-footer" style="padding-top: 10px; border-top: none; justify-content: flex-end;">
                    <button type="button" class="am-btn am-btn-primary am-next-step" data-next="2">
                        Weiter <i class="fas fa-arrow-right"></i>
                    </button>
                </div>
            </div>

            <div class="am-form-step-content" data-step="2">
                <div class="am-form-group am-animated" style="opacity:0;">
                    <label class="am-label">Kategorie</label>
                    <input type="hidden" id="am-new-product-category" name="category_id">
                    <div class="am-category-options-grid" id="am-new-category-select">
                        <div class="am-loading" style="grid-column: 1 / -1;">
                            <i class="fas fa-spinner fa-spin"></i>
                            <p>Kategorien werden geladen...</p>
                        </div>
                    </div>
                </div>

                <div class="am-form-group am-animated" style="opacity:0; animation-delay: 0.1s;">
                    <label class="am-label">Beschreibung (Optional)</label>
                    <textarea id="am-new-product-description" name="description" class="am-input" rows="3" placeholder="Zusätzliche Informationen zum Produkt..."></textarea>
                </div>

                <div class="am-form-group am-animated" style="opacity:0; animation-delay: 0.2s;">
                    <label class="am-label">Mindestbestand (Optional)</label>
                    <input type="number" 
                           id="am-new-product-min-stock" 
                           name="min_stock" 
                           class="am-input" 
                           min="0" 
                           placeholder="10 (Standard)">
                </div>

                <div class="am-modal-footer" style="padding-top: 10px; border-top: none; justify-content: space-between;">
                    <button type="button" class="am-btn am-btn-secondary am-prev-step" data-prev="1">
                        <i class="fas fa-arrow-left"></i> Zurück
                    </button>
                    <button type="button" class="am-btn am-btn-primary am-next-step" data-next="3">
                        Weiter <i class="fas fa-arrow-right"></i>
                    </button>
                </div>
            </div>

            <div class="am-form-step-content" data-step="3">
                <div class="am-form-group am-animated" style="opacity:0;">
                    <label class="am-label">Produktbild (Optional)</label>
                    <div class="am-image-upload-options">
                        <button type="button" class="am-btn am-btn-secondary" id="am-capture-photo">
                            <i class="fas fa-camera"></i> Foto aufnehmen
                        </button>
                        <button type="button" class="am-btn am-btn-secondary" id="am-upload-file">
                            <i class="fas fa-upload"></i> Datei hochladen
                            <input type="file" id="am-file-input" accept="image/*" style="display: none;">
                        </button>
                        <button type="button" class="am-btn am-btn-secondary" id="am-url-input">
                            <i class="fas fa-link"></i> Bild-URL
                        </button>
                    </div>
                    
                    <div class="am-url-input-group" style="display: none; margin-top: 15px;">
                        <input type="url" 
                               id="am-new-product-image-url" 
                               name="image_url" 
                               class="am-input" 
                               placeholder="https://deine-domain.de/bild.jpg">
                    </div>

                    <div class="am-image-preview" id="am-new-image-preview">
                        <i class="fas fa-camera"></i>
                        <span>Foto aufnehmen oder URL eingeben</span>
                    </div>
                    <video id="am-photo-video" autoplay playsinline style="display:none; width:100%; max-width: 100%; border-radius: 8px; margin-top: 10px;"></video>
                    <canvas id="am-photo-canvas" style="display:none;"></canvas>
                </div>
                
                <div class="am-modal-footer" style="padding-top: 10px; border-top: none; justify-content: space-between;">
                    <button type="button" class="am-btn am-btn-secondary am-prev-step" data-prev="2">
                        <i class="fas fa-arrow-left"></i> Zurück
                    </button>
                    <button type="button" id="am-save-new-product" class="am-btn am-btn-primary">
                        <i class="fas fa-save"></i>
                        Speichern
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<style>
/* Product Creation Modal Specific Styles */
.am-form-steps {
    display: flex;
    justify-content: space-around;
    margin-bottom: 30px;
    position: relative;
}

.am-form-steps::before {
    content: '';
    position: absolute;
    top: 50%;
    left: 10%;
    right: 10%;
    height: 2px;
    background: rgba(255, 255, 255, 0.1);
    transform: translateY(-50%);
    z-index: 0;
}

.am-step {
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    position: relative;
    z-index: 1;
    cursor: pointer;
    transition: all 0.3s ease;
}

.am-step-icon {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.1);
    border: 2px solid rgba(255, 255, 255, 0.2);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
    color: rgba(255, 255, 255, 0.5);
    margin-bottom: 8px;
    transition: all 0.3s ease;
}

.am-step-icon:hover {
    background: rgba(255, 255, 255, 0.15); /* Slightly lighter on hover */
}

.am-step-label {
    color: rgba(255, 255, 255, 0.7);
    font-size: 14px;
    font-weight: 500;
}

.am-step.active .am-step-icon {
    background: #00ff88;
    border-color: #00ff88;
    color: #003d1f;
    box-shadow: 0 0 0 5px rgba(0, 255, 136, 0.2);
}

.am-step.active .am-step-label {
    color: white;
}

.am-step.completed .am-step-icon {
    background: rgba(0, 255, 136, 0.3);
    border-color: rgba(0, 255, 136, 0.5);
    color: #00ff88;
}

.am-step.completed .am-step-label {
    color: rgba(0, 255, 136, 0.8);
}

.am-form-step-content {
    display: none;
}

.am-form-step-content.active {
    display: block;
}

.am-input-display {
    display: block;
    width: 100%;
    padding: 16px 20px;
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 12px;
    color: rgba(255, 255, 255, 0.7);
    font-size: 16px;
    margin-bottom: 24px;
    font-family: monospace;
}

.am-category-options-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
    gap: 12px;
    margin-top: 10px;
    max-height: 200px; /* Limit height for scrollability */
    overflow-y: auto;
    padding-right: 5px; /* For scrollbar */
}

.am-category-options-grid::-webkit-scrollbar {
    width: 6px;
}
.am-category-options-grid::-webkit-scrollbar-track {
    background: rgba(255, 255, 255, 0.05);
    border-radius: 3px;
}
.am-category-options-grid::-webkit-scrollbar-thumb {
    background: rgba(0, 255, 136, 0.3);
    border-radius: 3px;
}

.am-category-option {
    background: rgba(255, 255, 255, 0.05);
    border: 2px solid rgba(255, 255, 255, 0.1);
    border-radius: 12px;
    padding: 15px;
    text-align: center;
    cursor: pointer;
    transition: all 0.2s ease;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 8px;
}

.am-category-option:hover {
    background: rgba(255, 255, 255, 0.1);
    border-color: rgba(255, 255, 255, 0.3);
}

.am-category-option.selected {
    background: rgba(0, 255, 136, 0.2);
    border-color: #00ff88;
    box-shadow: 0 0 0 3px rgba(0, 255, 136, 0.2);
}

.am-category-option i {
    font-size: 28px;
    color: rgba(255, 255, 255, 0.7);
}

.am-category-option.selected i {
    color: #00ff88;
}

.am-category-option span {
    font-size: 14px;
    color: rgba(255, 255, 255, 0.9);
    font-weight: 500;
}

.am-image-upload-options {
    display: flex;
    gap: 10px;
    margin-bottom: 20px;
    flex-wrap: wrap;
}

.am-image-upload-options .am-btn {
    flex: 1;
    min-width: 140px;
    background: rgba(255, 255, 255, 0.05);
    color: rgba(255, 255, 255, 0.8);
    border: 1px solid rgba(255, 255, 255, 0.1);
}
.am-image-upload-options .am-btn:hover {
    background: rgba(255, 255, 255, 0.1);
}

.am-image-preview {
    margin-top: 15px;
    padding: 20px;
    border: 2px dashed rgba(255, 255, 255, 0.2);
    border-radius: 12px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    min-height: 180px;
    text-align: center;
    color: rgba(255, 255, 255, 0.4);
    transition: all 0.3s ease;
    cursor: pointer;
}

.am-image-preview:hover {
    border-color: rgba(255, 255, 255, 0.4);
    background: rgba(255, 255, 255, 0.05);
}

.am-image-preview i {
    font-size: 48px;
    margin-bottom: 10px;
}

.am-image-preview span {
    font-size: 14px;
}

.am-url-input-group {
    display: flex;
    gap: 10px;
}

/* Animations for step content */
.am-form-step-content.active .am-animated {
    opacity: 0;
    animation: slideInUp 0.4s ease-out forwards;
}

@media (max-width: 768px) {
    .am-form-steps {
        flex-wrap: wrap;
        margin-bottom: 20px;
    }
    .am-form-steps::before {
        display: none;
    }
    .am-step {
        width: 33%; /* Approx for 3 steps */
        margin-bottom: 15px;
    }
    .am-category-options-grid {
        grid-template-columns: repeat(auto-fit, minmax(100px, 1fr));
    }
    .am-image-upload-options {
        flex-direction: column;
    }
}
</style>

<script>
// Enhanced Product Creation Modal JavaScript
(function($) {
    'use strict';
    
    let currentStep = 1;
    let captureStream = null;
    let photoCanvas = null;
    let photoContext = null;
    let videoElement = null; // Reference to the video element for camera

    // Initialize Product Creation Modal
    function initProductModal(barcode) {
        // Set barcode
        $('#am-new-product-barcode').text(barcode || 'Wird automatisch erkannt');
        $('#am-new-product-barcode-input').val(barcode || '');
        
        // Reset form
        $('#am-product-create-form')[0].reset();
        currentStep = 1;
        updateSteps();
        
        // Load categories
        loadCategoriesForModal();
        
        // Show modal
        $('#am-product-create-modal').fadeIn(300);

        // Reset image preview and camera state
        $('#am-new-image-preview').html('<i class="fas fa-camera"></i><span>Foto aufnehmen oder URL eingeben</span>');
        $('#am-new-product-image-url').val('');
        $('.am-url-input-group').hide(); // Hide URL input by default

        // Stop camera if it was active
        if (captureStream) {
            captureStream.getTracks().forEach(track => track.stop());
            captureStream = null;
        }
        // Ensure the video element in the preview area is properly hidden and reset
        videoElement = document.getElementById('am-photo-video');
        if (videoElement) {
            videoElement.style.display = 'none'; // Hide video element
            videoElement.srcObject = null;
        }
    }
    
    // Update form steps
    function updateSteps() {
        // Update step indicators
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
            $this.css({ 'opacity': '0', 'transform': 'translateY(20px)' }); // Reset for re-animation
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
        `); // Show loading spinner
        
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
                    showNotification('Kategorien konnten nicht geladen werden.', 'error'); // Use global notification
                    $container.html(`<div class="am-empty-state" style="grid-column: 1 / -1; color: rgba(255,255,255,0.5);">
                                        <i class="fas fa-box-open"></i><p>Keine Kategorien gefunden.</p>
                                    </div>`);
                }
            },
            error: function() {
                showNotification('Netzwerkfehler beim Laden der Kategorien.', 'error'); // Use global notification
                $container.html(`<div class="am-error-state" style="grid-column: 1 / -1; color: var(--danger);">
                                    <i class="fas fa-exclamation-triangle"></i><p>Fehler beim Laden der Kategorien.</p>
                                </div>`);
            }
        });
    }
    
    // Camera capture functionality
    function initCamera() {
        // Ensure videoElement and photoCanvas are correctly referenced
        videoElement = document.getElementById('am-photo-video');
        photoCanvas = document.getElementById('am-photo-canvas');

        if (!videoElement || !photoCanvas) {
            console.error('Video or Canvas element not found for product image capture.');
            showNotification('Kamera-Vorschau-Elemente nicht gefunden.', 'error');
            return;
        }

        photoContext = photoCanvas.getContext('2d');
        
        // Always reset preview area before starting camera
        $('#am-new-image-preview').html(`
            <video id="am-photo-video" autoplay playsinline style="width:100%; max-width: 100%; border-radius: 8px; margin-top: 10px;"></video>
            <canvas id="am-photo-canvas" style="display:none;"></canvas>
            <button type="button" class="am-btn am-btn-primary" id="am-capture-button" style="margin-top: 10px;">
                <i class="fas fa-camera"></i> Foto aufnehmen
            </button>
        `);
        // Re-assign videoElement and photoCanvas after HTML update as their references might change
        videoElement = document.getElementById('am-photo-video');
        photoCanvas = document.getElementById('am-photo-canvas');
        photoContext = photoCanvas.getContext('2d');

        const constraints = {
            video: {
                facingMode: 'environment', // Prefer back camera for product photos
                width: { ideal: 1280 },
                height: { ideal: 720 }
            }
        };
        
        navigator.mediaDevices.getUserMedia(constraints)
            .then(function(stream) {
                captureStream = stream;
                videoElement.srcObject = stream;
                videoElement.style.display = 'block';
                videoElement.play();
                showNotification('Kamera gestartet.', 'info');
            })
            .catch(function(err) {
                console.error('Camera error in product-create-modal:', err);
                let errorMessage = 'Kamera konnte nicht gestartet werden.';
                if (err.name === 'NotAllowedError' || err.name === 'PermissionDeniedError') {
                    errorMessage += ' Bitte erlaube den Zugriff in deinen Browsereinstellungen.';
                } else if (err.name === 'NotFoundError') {
                    errorMessage += ' Keine passende Kamera gefunden.';
                } else {
                    errorMessage += ` Fehlerdetails: ${err.message || err}`;
                }
                showNotification(errorMessage, 'error');
                
                $('#am-new-image-preview').html(`
                    <div style="text-align: center; padding: 20px; color: var(--danger);">
                        <i class="fas fa-exclamation-circle fa-2x"></i>
                        <p>${errorMessage}</p>
                        <button type="button" class="am-btn am-btn-secondary" id="am-capture-photo" style="margin-top: 10px;">
                            <i class="fas fa-redo"></i> Erneut versuchen
                        </button>
                    </div>
                `);
                // Ensure video element is hidden if camera fails
                if (videoElement) {
                    videoElement.style.display = 'none';
                }
            });
    }
    
    // Capture photo from camera
    function capturePhoto() {
        if (!videoElement || !photoCanvas || !photoContext || !captureStream) {
            showNotification('Kamera ist nicht aktiv. Kann kein Foto aufnehmen.', 'error');
            return;
        }

        // Set canvas size to video size
        photoCanvas.width = videoElement.videoWidth;
        photoCanvas.height = videoElement.videoHeight;
        
        // Draw video frame to canvas
        photoContext.drawImage(videoElement, 0, 0, photoCanvas.width, photoCanvas.height);
        
        // Convert to data URL
        const dataUrl = photoCanvas.toDataURL('image/jpeg', 0.8);
        
        // Show preview
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
        if (videoElement) {
            videoElement.style.display = 'none'; // Hide video element
            videoElement.srcObject = null;
        }
        
        // Store image data
        $('#am-new-product-image-url').val(dataUrl);
        showNotification('Foto aufgenommen!', 'success');
    }
    
    // Initialize event handlers
    $(document).ready(function() {
        // Make initProductModal globally available
        window.initProductModal = initProductModal;
        
        // Step navigation
        $(document).on('click', '.am-next-step', function() {
            const nextStep = $(this).data('next');
            
            // Validate current step
            if (validateStep(currentStep)) {
                currentStep = nextStep;
                updateSteps();
            }
        });
        
        $(document).on('click', '.am-prev-step', function() {
            const prevStep = $(this).data('prev');
            currentStep = prevStep;
            updateSteps();
        });
        
        // Step click navigation (only for completed steps)
        $(document).on('click', '.am-step.completed', function() {
            const clickedStep = parseInt($(this).data('step'));
            // Only allow navigation to previous completed steps
            if (clickedStep < currentStep) {
                currentStep = clickedStep;
                updateSteps();
            }
        });
        
        // Category selection
        $(document).on('click', '.am-category-option', function() {
            $('.am-category-option').removeClass('selected');
            $(this).addClass('selected');
            $('#am-new-product-category').val($(this).data('id'));
        });
        
        // Image upload options
        $(document).on('click', '#am-capture-photo', function() {
            initCamera();
        });
        
        $(document).on('click', '#am-upload-file', function() {
            $('#am-file-input').click();
        });
        
        $(document).on('click', '#am-url-input', function() {
            $('.am-url-input-group').slideToggle(200);
        });
        
        // File input change
        $(document).on('change', '#am-file-input', function(e) {
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
                showNotification('Bitte wähle eine gültige Bilddatei aus.', 'error');
            }
        });
        
        // URL input change
        $(document).on('input', '#am-new-product-image-url', function() {
            const url = $(this).val();
            if (url) {
                $('#am-new-image-preview').html(`
                    <img src="${url}" style="max-width: 100%; max-height: 300px; border-radius: 8px;" 
                         onerror="this.onerror=null; this.parentElement.innerHTML='<i class=\\'fas fa-exclamation-triangle\\'></i><span>Bild konnte nicht geladen werden. Ungültige URL oder Serverproblem.</span>'; this.parentElement.style.color='var(--danger)';">
                `);
            } else {
                $('#am-new-image-preview').html('<i class="fas fa-camera"></i><span>Foto aufnehmen oder URL eingeben</span>');
            }
        });
        
        // Capture button (dynamic)
        $(document).on('click', '#am-capture-button', function() {
            capturePhoto();
        });
        
        // Retake photo (dynamic)
        $(document).on('click', '#am-retake-photo', function() {
            initCamera();
        });
        
        // Save product
        $(document).on('click', '#am-save-new-product', function() {
            if (validateStep(3)) { // Validate last step before saving
                saveNewProduct();
            }
        });
        
        // Close modal
        $(document).on('click', '#am-product-create-modal .am-modal-close, #am-product-create-modal .am-btn-secondary', function() {
            $('#am-product-create-modal').fadeOut(300);
            
            // Stop camera if active
            if (captureStream) {
                captureStream.getTracks().forEach(track => track.stop());
                captureStream = null;
            }
            if (videoElement) {
                videoElement.style.display = 'none';
                videoElement.srcObject = null;
            }

            // If scanner was open, restart it
            if ($('.am-scanner-wrapper').hasClass('active') && typeof Quagga !== 'undefined') {
                Quagga.start();
            }
        });
    });
    
    // Validate form step
    function validateStep(step) {
        let isValid = true;
        
        switch(step) {
            case 1:
                // Validate basic info
                const name = $('#am-new-product-name').val().trim();
                const price = $('#am-new-product-price').val();
                const stock = $('#am-new-product-stock').val();
                const barcode = $('#am-new-product-barcode-input').val().trim(); // Barcode is now required

                if (!barcode) {
                    showNotification('Barcode ist erforderlich. Bitte scannen oder manuell eingeben.', 'error');
                    // Maybe go back to scanner or indicate on the field
                    isValid = false;
                } else if (!name) {
                    showNotification('Bitte Produktname eingeben.', 'error');
                    $('#am-new-product-name').focus();
                    isValid = false;
                } else if (!price || parseFloat(price) <= 0) {
                    showNotification('Bitte gültigen Preis eingeben.', 'error');
                    $('#am-new-product-price').focus();
                    isValid = false;
                } else if (stock === '' || parseInt(stock) < 0) {
                    showNotification('Bitte gültigen Bestand eingeben.', 'error');
                    $('#am-new-product-stock').focus();
                    isValid = false;
                }
                break;
                
            case 2:
                // Details are optional, always valid for now
                // Future: add validation for min_stock if desired
                break;
                
            case 3:
                // Image is optional, always valid for now
                break;
        }
        
        return isValid;
    }
    
    // Save new product
    function saveNewProduct() {
        const $saveBtn = $('#am-save-new-product');
        const originalText = $saveBtn.html();
        
        // Show loading
        $saveBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Speichern...');
        
        // Collect form data
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
        
        // Send AJAX request
        $.ajax({
            url: am_ajax.ajax_url,
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    playBeep('success'); // Play success sound
                    
                    showNotification('Produkt erfolgreich angelegt!', 'success'); // Use global notification
                    
                    // Show success animation in modal
                    $('#am-product-create-modal .am-modal-content').html(`
                        <div style="text-align: center; padding: 60px;">
                            <div style="font-size: 80px; color: #10b981; margin-bottom: 30px;">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <h2 style="color: white; margin-bottom: 20px;">Produkt erfolgreich angelegt!</h2>
                            <p style="color: rgba(255,255,255,0.7); margin-bottom: 30px;">
                                "${formData.name}" wurde mit dem Barcode "${formData.barcode}" gespeichert.
                            </p>
                            <div style="display: flex; gap: 12px; justify-content: center; flex-wrap: wrap;">
                                <button class="am-btn am-btn-primary" onclick="window.location.href='${automatenManager.adminUrl}admin.php?page=am-products'">
                                    <i class="fas fa-list"></i> Zur Produktliste
                                </button>
                                <button class="am-btn am-btn-secondary" id="am-create-another-product">
                                    <i class="fas fa-plus"></i> Weiteres Produkt anlegen
                                </button>
                                <button class="am-btn am-btn-success" id="am-continue-scanning">
                                    <i class="fas fa-barcode"></i> Weiterscannen
                                </button>
                            </div>
                        </div>
                    `);

                    // Add event listeners for the new buttons in the success view
                    $('#am-create-another-product').on('click', function() {
                        $('#am-product-create-modal').fadeOut(300, function() {
                            initProductModal(''); // Open new product modal for empty barcode
                        });
                    });

                    $('#am-continue-scanning').on('click', function() {
                        $('#am-product-create-modal').fadeOut(300, function() {
                            if ($('.am-scanner-wrapper').length > 0 && typeof initScanner === 'function') {
                                // If scanner wrapper exists and initScanner is available, restart scanner
                                initScanner(); // Call the global scanner initialization function
                            } else {
                                // Fallback if scanner isn't the primary flow or not loaded
                                window.location.href = automatenManager.adminUrl + 'admin.php?page=am-scanner';
                            }
                        });
                    });

                    // Reload products if on products page and loadProducts is defined
                    if (typeof loadProducts === 'function') {
                        loadProducts();
                    }
                } else {
                    showNotification(response.data?.message || 'Fehler beim Speichern.', 'error'); // Use global notification
                    $saveBtn.prop('disabled', false).html(originalText);
                }
            },
            error: function(xhr, status, error) {
                console.error('Save error:', error);
                showNotification('Netzwerkfehler beim Speichern.', 'error'); // Use global notification
                $saveBtn.prop('disabled', false).html(originalText);
            }
        });
    }
    
    // showNotification function is now global in scanner.js
    // We removed its local definition here.
    
})(jQuery);