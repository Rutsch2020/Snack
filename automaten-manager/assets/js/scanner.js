jQuery(document).ready(function($) {
    let currentBarcode = '';
    let captureStream = null;
    let scannerInitialized = false;
    let isFlashOn = false;
    let selectedCameraId = null;
    let availableCameras = [];
    let quaggaRunning = false;
    let isMobileDevice = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);

    const scannerWrapper = $('.am-scanner-wrapper');
    const scannerVideo = $('#am-scanner-video')[0];
    const scannerCanvas = $('#am-scanner-canvas')[0];
    const scannerStatus = $('#am-scanner-status');
    const scannerModal = $('#am-scanner-modal');
    const productFoundSection = $('#am-product-found');
    const productNotFoundSection = $('#am-product-not-found');
    const quantitySelectorSection = $('#am-quantity-selector');

    // Audio elements
    const scanSound = $('#am-scan-sound')[0];
    const successSound = $('#am-success-sound')[0];

    // Function to play sounds
    window.playBeep = function(type) {
        try {
            if (type === 'scan' && scanSound) {
                scanSound.play().catch(e => console.warn("Scan sound play failed:", e));
            } else if (type === 'success' && successSound) {
                successSound.play().catch(e => console.warn("Success sound play failed:", e));
            }
        } catch(e) {
            console.warn("Sound play error:", e);
        }
    };

    // Notification system (global)
    window.showNotification = function(message, type = 'info') {
        $('.am-notification').remove();

        const notification = $('<div class="am-notification"></div>');
        let icon = '';
        
        if (type === 'success') {
            icon = '<i class="fas fa-check-circle"></i>';
            notification.addClass('am-notification-success');
        } else if (type === 'error') {
            icon = '<i class="fas fa-exclamation-circle"></i>';
            notification.addClass('am-notification-error');
        } else if (type === 'warning') {
            icon = '<i class="fas fa-exclamation-triangle"></i>';
            notification.addClass('am-notification-warning');
        } else {
            icon = '<i class="fas fa-info-circle"></i>';
            notification.addClass('am-notification-info');
        }
        
        notification.html(`${icon}<span>${message}</span>`);
        $('body').append(notification);

        setTimeout(() => {
            notification.addClass('am-notification-show');
        }, 10);

        setTimeout(() => {
            notification.removeClass('am-notification-show');
            notification.fadeOut(300, () => notification.remove());
        }, 4000);
    };

    // Global handler for barcode detection
    window.handleBarcodeDetected = function(barcode, isManual = false) {
        if (!isManual) {
            playBeep('scan');
        }
        
        // Stop Quagga to prevent multiple detections
        if (quaggaRunning) {
            Quagga.stop();
            quaggaRunning = false;
        }
        
        showScannerStatus('Barcode erkannt: ' + barcode);
        currentBarcode = barcode;
        
        // Close any open product creation modal first
        $('#am-product-create-modal').fadeOut(100);
        
        checkProduct(barcode);
    };

    // Initialize QuaggaJS scanner
    window.initScanner = function() {
        console.log('initScanner called, mobile:', isMobileDevice);
        
        if (!scannerVideo || !scannerCanvas) {
            showNotification('Scanner-Elemente nicht gefunden.', 'error');
            console.error('Scanner video or canvas element is missing from the DOM.');
            return;
        }

        // Close any open modals first
        $('.am-modal').fadeOut(100);
        scannerModal.removeClass('active');
        
        scannerWrapper.addClass('active');
        showScannerStatus('Kamera wird gestartet...');
        
        if (quaggaRunning) {
            showScannerStatus('Scanner bereits aktiv.', 'info');
            return;
        }

        // Check browser support
        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            showScannerStatus('Dein Browser unterstützt keine Kamera.', 'error');
            showNotification('Kamera nicht unterstützt. Bitte verwende einen modernen Browser.', 'error');
            scannerWrapper.removeClass('active');
            return;
        }

        // Enumerate devices
        navigator.mediaDevices.enumerateDevices()
            .then(function(devices) {
                availableCameras = devices.filter(device => device.kind === 'videoinput');
                console.log('Available cameras:', availableCameras);
                
                if (availableCameras.length === 0) {
                    showScannerStatus('Keine Kamera gefunden!', 'error');
                    showNotification('Keine Kamera gefunden! Bitte Kamerazugriff erlauben.', 'error');
                    scannerWrapper.removeClass('active');
                    return;
                }

                // Select camera - prefer back/environment camera
                let chosenCamera = availableCameras.find(device => 
                    device.label.toLowerCase().includes('back') || 
                    device.label.toLowerCase().includes('environment') ||
                    device.label.toLowerCase().includes('rück') ||
                    device.label.toLowerCase().includes('hinten')
                );

                if (!chosenCamera && availableCameras.length > 1) {
                    // On mobile, the last camera is often the back camera
                    chosenCamera = availableCameras[availableCameras.length - 1];
                } else if (!chosenCamera) {
                    chosenCamera = availableCameras[0];
                }

                selectedCameraId = chosenCamera ? chosenCamera.deviceId : null;
                console.log('Chosen camera:', chosenCamera);

                // Quagga configuration optimized for mobile
                const quaggaConfig = {
                    inputStream: {
                        name: "Live",
                        type: "LiveStream",
                        target: scannerVideo,
                        constraints: {
                            video: {
                                deviceId: selectedCameraId ? { exact: selectedCameraId } : undefined,
                                facingMode: isMobileDevice ? { ideal: "environment" } : "user",
                                width: { min: 640, ideal: 1280, max: 1920 },
                                height: { min: 480, ideal: 720, max: 1080 }
                            }
                        },
                        area: isMobileDevice ? {
                            top: "20%",
                            right: "10%",
                            left: "10%",
                            bottom: "20%"
                        } : {
                            top: "30%",
                            right: "20%",
                            left: "20%",
                            bottom: "30%"
                        }
                    },
                    locator: {
                        patchSize: isMobileDevice ? "large" : "medium",
                        halfSample: true
                    },
                    decoder: {
                        readers: [
                            "ean_reader",
                            "ean_8_reader",
                            "code_128_reader",
                            "code_39_reader",
                            "upc_reader",
                            "upc_e_reader"
                        ],
                        multiple: false
                    },
                    locate: true,
                    frequency: 10
                };

                Quagga.init(quaggaConfig, function(err) {
                    if (err) {
                        console.error("Quagga Init Error:", err);
                        handleCameraError(err);
                        return;
                    }
                    
                    console.log("Quagga initialization finished. Ready to start.");
                    Quagga.start();
                    quaggaRunning = true;
                    scannerInitialized = true;
                    showScannerStatus('Scanner bereit!', 'success');
                    
                    // Check for flash support
                    setTimeout(() => {
                        checkFlashSupport();
                    }, 500);
                });
            })
            .catch(function(err) {
                console.error("Error enumerating devices:", err);
                handleCameraError(err);
            });

        // Set up Quagga event handlers
        Quagga.onDetected(function(result) {
            if (result && result.codeResult && result.codeResult.code) {
                const barcode = result.codeResult.code;
                console.log('Barcode detected:', barcode);
                
                // Prevent multiple rapid detections
                if (!window.lastDetectionTime || Date.now() - window.lastDetectionTime > 1000) {
                    window.lastDetectionTime = Date.now();
                    handleBarcodeDetected(barcode);
                }
            }
        });

        Quagga.onProcessed(function(result) {
            const drawingCtx = Quagga.canvas.ctx.overlay;
            const drawingCanvas = Quagga.canvas.dom.overlay;

            if (result) {
                drawingCtx.clearRect(0, 0, parseInt(drawingCanvas.width), parseInt(drawingCanvas.height));
                
                if (result.boxes) {
                    result.boxes.filter(function(box) {
                        return box !== result.box;
                    }).forEach(function(box) {
                        Quagga.ImageDebug.drawPath(box, { x: 0, y: 1 }, drawingCtx, { 
                            color: "green", 
                            lineWidth: 2 
                        });
                    });
                }
                
                if (result.box) {
                    Quagga.ImageDebug.drawPath(result.box, { x: 0, y: 1 }, drawingCtx, { 
                        color: "#00ff88", 
                        lineWidth: 2 
                    });
                }
                
                if (result.codeResult && result.codeResult.code) {
                    Quagga.ImageDebug.drawPath(result.line, { x: 'x', y: 'y' }, drawingCtx, { 
                        color: "red", 
                        lineWidth: 3 
                    });
                }
            }
        });
    };

    // Handle camera errors
    function handleCameraError(err) {
        showScannerStatus('Fehler beim Starten der Kamera', 'error');
        
        let errorMessage = 'Kamera konnte nicht gestartet werden. ';
        
        if (err.name === 'NotAllowedError' || err.name === 'PermissionDeniedError') {
            errorMessage += 'Bitte erlaube den Kamerazugriff in deinen Browsereinstellungen.';
        } else if (err.name === 'NotFoundError') {
            errorMessage += 'Keine Kamera gefunden.';
        } else if (err.name === 'NotReadableError') {
            errorMessage += 'Kamera wird bereits verwendet.';
        } else if (err.name === 'OverconstrainedError') {
            errorMessage += 'Kamera-Einstellungen nicht unterstützt.';
        } else {
            errorMessage += err.message || 'Unbekannter Fehler.';
        }
        
        showNotification(errorMessage, 'error');
        scannerWrapper.removeClass('active');
    }

    // Check flash support
    function checkFlashSupport() {
        if (!scannerVideo || !scannerVideo.srcObject) return;
        
        const track = scannerVideo.srcObject.getVideoTracks()[0];
        if (track) {
            const capabilities = track.getCapabilities ? track.getCapabilities() : {};
            if (capabilities.torch) {
                $('#am-toggle-flash').show();
                console.log('Flash support detected');
            } else {
                $('#am-toggle-flash').hide();
            }
        }
    }

    // Show scanner status message
    function showScannerStatus(message, type = 'info') {
        scannerStatus.text(message);
        scannerStatus.removeClass('am-error-state am-success-state am-warning-state');
        
        if (type === 'error') {
            scannerStatus.addClass('am-error-state');
        } else if (type === 'success') {
            scannerStatus.addClass('am-success-state');
        } else if (type === 'warning') {
            scannerStatus.addClass('am-warning-state');
        }
        
        scannerStatus.fadeIn(200).delay(3000).fadeOut(1000);
    }

    // Check product via AJAX
    function checkProduct(barcode) {
        showScannerStatus('Suche Produkt...');
        
        $.ajax({
            url: automatenManager.ajaxUrl,
            type: 'POST',
            data: {
                action: 'am_check_product',
                barcode: barcode,
                nonce: automatenManager.nonce
            },
            success: function(response) {
                // Make sure scanner modal is shown
                scannerModal.addClass('active');
                $('#am-barcode-result').text(barcode);
                
                if (response.success && response.data.product) {
                    showScannerStatus('Produkt gefunden!');
                    productFoundSection.show();
                    productNotFoundSection.hide();
                    quantitySelectorSection.hide();
                    
                    const product = response.data.product;
                    const defaultImage = automatenManager.defaultImage || '';
                    $('#am-product-image').attr('src', product.image_url || defaultImage);
                    $('#am-product-name').text(product.name);
                    $('#am-product-price').text(parseFloat(product.price).toFixed(2) + ' €');
                    $('#am-product-stock').text(product.stock);

                    // Show stock warnings
                    if (product.stock <= (product.min_stock || 10) && product.stock > 0) {
                        showNotification(`Niedriger Bestand: ${product.name} (${product.stock})`, 'warning');
                    } else if (product.stock === 0) {
                        showNotification(`${product.name} ist ausverkauft!`, 'error');
                    }
                } else {
                    showScannerStatus('Produkt nicht gefunden.', 'warning');
                    productFoundSection.hide();
                    productNotFoundSection.show();
                    quantitySelectorSection.hide();
                    showNotification('Produkt nicht gefunden. Neues Produkt anlegen?', 'info');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', error);
                showScannerStatus('Fehler beim Laden.', 'error');
                scannerModal.addClass('active');
                $('#am-barcode-result').text(barcode);
                productFoundSection.hide();
                productNotFoundSection.show();
                quantitySelectorSection.hide();
                showNotification('Fehler beim Laden der Produktdaten.', 'error');
            }
        });
    }

    // Process scan action (sell/restock)
    function processScanAction(actionType) {
        const quantity = parseInt($('#am-quantity').val());
        if (isNaN(quantity) || quantity <= 0) {
            showNotification('Bitte gültige Menge eingeben.', 'error');
            return;
        }

        showScannerStatus('Verarbeite Aktion...');
        
        $.ajax({
            url: automatenManager.ajaxUrl,
            type: 'POST',
            data: {
                action: 'am_process_scan',
                barcode: currentBarcode,
                scan_action: actionType,
                quantity: quantity,
                nonce: automatenManager.nonce
            },
            success: function(response) {
                if (response.success) {
                    playBeep('success');
                    showNotification(response.data.message, 'success');
                    scannerModal.removeClass('active');
                    
                    // Restart scanner after a short delay
                    setTimeout(() => {
                        if (scannerInitialized && !quaggaRunning && scannerWrapper.hasClass('active')) {
                            Quagga.start();
                            quaggaRunning = true;
                        }
                    }, 500);
                    
                    // Reload products if function exists
                    if (typeof loadProducts === 'function') {
                        loadProducts();
                    }
                } else {
                    showNotification(response.data?.message || 'Aktion fehlgeschlagen.', 'error');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', error);
                showNotification('Netzwerkfehler bei der Aktion.', 'error');
            }
        });
    }

    // Event Listeners

    // Open scanner - remove duplicate listeners
    $(document).off('click', '#am-open-scanner, #am-dashboard-scanner, #am-inventory-scan');
    $(document).on('click', '#am-open-scanner, #am-dashboard-scanner, #am-inventory-scan', function(e) {
        e.preventDefault();
        e.stopPropagation();
        initScanner();
    });

    // Close scanner
    $('#am-scanner-close').off('click').on('click', function() {
        scannerWrapper.removeClass('active');
        if (quaggaRunning) {
            Quagga.stop();
            quaggaRunning = false;
        }
        scannerStatus.hide();
        scannerModal.removeClass('active');
        resetModalState();
        isFlashOn = false;
        $('#am-toggle-flash').removeClass('active');
    });

    // Close result modal
    $('#am-modal-close').off('click').on('click', function() {
        scannerModal.removeClass('active');
        resetModalState();
        
        // Restart scanner after short delay
        setTimeout(() => {
            if (scannerInitialized && !quaggaRunning && scannerWrapper.hasClass('active')) {
                Quagga.start();
                quaggaRunning = true;
            }
        }, 300);
    });

    // Manual barcode submit
    $('#am-manual-submit').off('click').on('click', function() {
        const barcode = $('#am-manual-barcode').val().trim();
        if (barcode) {
            handleBarcodeDetected(barcode, true);
        } else {
            showNotification('Bitte Barcode eingeben.', 'error');
        }
    });

    $('#am-manual-barcode').off('keypress').on('keypress', function(e) {
        if (e.which === 13) {
            e.preventDefault();
            $('#am-manual-submit').click();
        }
    });

    // Sell button
    $('#am-btn-sell').off('click').on('click', function() {
        $('#am-quantity').val(1);
        productFoundSection.hide();
        quantitySelectorSection.show();
        $('#am-confirm-action').data('action', 'sell').html('<i class="fas fa-shopping-cart"></i> Verkaufen');
    });

    // Restock button
    $('#am-btn-restock').off('click').on('click', function() {
        $('#am-quantity').val(1);
        productFoundSection.hide();
        quantitySelectorSection.show();
        $('#am-confirm-action').data('action', 'restock').html('<i class="fas fa-box-open"></i> Auffüllen');
    });

    // Quantity controls
    $('#am-qty-minus').off('click').on('click', function() {
        let qty = parseInt($('#am-quantity').val());
        if (qty > 1) {
            $('#am-quantity').val(qty - 1);
        }
    });

    $('#am-qty-plus').off('click').on('click', function() {
        let qty = parseInt($('#am-quantity').val());
        $('#am-quantity').val(qty + 1);
    });

    // Confirm action
    $('#am-confirm-action').off('click').on('click', function() {
        const actionType = $(this).data('action');
        if (currentBarcode && actionType) {
            processScanAction(actionType);
        }
    });

    // Toggle Flashlight
    $('#am-toggle-flash').off('click').on('click', function() {
        if (!scannerVideo || !scannerVideo.srcObject) {
            showNotification('Kamera nicht bereit.', 'info');
            return;
        }

        const track = scannerVideo.srcObject.getVideoTracks()[0];
        if (track) {
            const capabilities = track.getCapabilities ? track.getCapabilities() : {};
            
            if (capabilities.torch) {
                track.applyConstraints({
                    advanced: [{ torch: !isFlashOn }]
                }).then(() => {
                    isFlashOn = !isFlashOn;
                    $(this).toggleClass('active', isFlashOn);
                    showNotification(`Taschenlampe ${isFlashOn ? 'AN' : 'AUS'}`, 'info');
                }).catch(e => {
                    console.error("Failed to toggle torch:", e);
                    showNotification('Taschenlampe konnte nicht umgeschaltet werden.', 'error');
                });
            } else {
                showNotification('Taschenlampe nicht unterstützt.', 'warning');
            }
        }
    });

    // Create new product button
    $(document).off('click', '#am-btn-create');
    $(document).on('click', '#am-btn-create', function() {
        scannerModal.removeClass('active');
        
        if (quaggaRunning) {
            Quagga.stop();
            quaggaRunning = false;
        }
        
        // Small delay to ensure modal is closed
        setTimeout(() => {
            if (typeof window.initProductModal === 'function') {
                window.initProductModal(currentBarcode);
            } else {
                console.error('initProductModal function not found.');
                showNotification('Produktanlage nicht verfügbar.', 'error');
                window.location.href = automatenManager.adminUrl + 'admin.php?page=am-products&action=new&barcode=' + currentBarcode;
            }
        }, 100);
    });

    // Helper function to reset modal state
    function resetModalState() {
        productFoundSection.hide();
        productNotFoundSection.hide();
        quantitySelectorSection.hide();
        $('#am-manual-barcode').val('');
        $('#am-quantity').val(1);
    }

    // Handle orientation changes on mobile
    if (isMobileDevice) {
        window.addEventListener('orientationchange', function() {
            if (quaggaRunning) {
                setTimeout(() => {
                    Quagga.stop();
                    quaggaRunning = false;
                    initScanner();
                }, 500);
            }
        });
    }
});