/**
 * Automaten Manager Pro - Admin JavaScript
 * Zentrale JavaScript-Funktionen für das Admin-Interface
 */

// Globale Namespace
window.AutomatenManager = window.AutomatenManager || {};

(function($) {
    'use strict';
    
    // Initialisierung
    $(document).ready(function() {
        AutomatenManager.init();
    });
    
    /**
     * Hauptinitialisierung
     */
    AutomatenManager.init = function() {
        console.log('Automaten Manager Pro Admin initialisiert');
        
        // Globale Event-Listener
        this.bindGlobalEvents();
        
        // Tooltips initialisieren
        this.initTooltips();
        
        // Auto-Save für Formulare
        this.initAutoSave();
        
        // Keyboard-Shortcuts
        this.initKeyboardShortcuts();
    };
    
    /**
     * Globale Event-Listener
     */
    AutomatenManager.bindGlobalEvents = function() {
        // Bestätigungs-Dialoge für Lösch-Aktionen
        $(document).on('click', '[data-confirm]', function(e) {
            const message = $(this).data('confirm') || am_ajax.messages.delete_confirm;
            if (!confirm(message)) {
                e.preventDefault();
                return false;
            }
        });
        
        // Auto-Format für Preise
        $(document).on('blur', 'input[type="number"][step="0.01"]', function() {
            const value = parseFloat($(this).val());
            if (!isNaN(value)) {
                $(this).val(value.toFixed(2));
            }
        });
        
        // Auto-Uppercase für Barcodes
        $(document).on('input', 'input[name="barcode"], #productBarcode', function() {
            $(this).val($(this).val().toUpperCase());
        });
        
        // Loading-States für Buttons
        $(document).on('click', '.am-btn[type="submit"]', function() {
            const $btn = $(this);
            const originalText = $btn.html();
            
            $btn.prop('disabled', true)
                .html('<i class="fas fa-spinner fa-spin"></i> Speichern...');
            
            // Reset nach 5 Sekunden falls kein anderes Event
            setTimeout(function() {
                if ($btn.prop('disabled')) {
                    $btn.prop('disabled', false).html(originalText);
                }
            }, 5000);
        });
    };
    
    /**
     * Tooltips initialisieren
     */
    AutomatenManager.initTooltips = function() {
        // Einfache Tooltip-Implementation
        $(document).on('mouseenter', '[title]', function() {
            const $this = $(this);
            const title = $this.attr('title');
            
            if (title && !$this.data('tooltip-created')) {
                $this.data('tooltip-created', true);
                $this.removeAttr('title'); // Verhindert Browser-Tooltip
                
                const $tooltip = $('<div class="am-tooltip">' + title + '</div>');
                $('body').append($tooltip);
                
                const updatePosition = function() {
                    const offset = $this.offset();
                    const tooltipWidth = $tooltip.outerWidth();
                    const elementWidth = $this.outerWidth();
                    
                    $tooltip.css({
                        top: offset.top - $tooltip.outerHeight() - 8,
                        left: offset.left + (elementWidth / 2) - (tooltipWidth / 2),
                        opacity: 1
                    });
                };
                
                updatePosition();
                
                $this.on('mouseleave', function() {
                    $tooltip.remove();
                    $this.data('tooltip-created', false);
                    $this.attr('title', title); // Titel wiederherstellen
                });
            }
        });
    };
    
    /**
     * Auto-Save für Formulare
     */
    AutomatenManager.initAutoSave = function() {
        let autoSaveTimeout;
        
        $(document).on('input', '.am-auto-save input, .am-auto-save textarea, .am-auto-save select', function() {
            const $form = $(this).closest('form');
            
            clearTimeout(autoSaveTimeout);
            autoSaveTimeout = setTimeout(function() {
                if ($form.length && $form.data('auto-save-url')) {
                    AutomatenManager.autoSaveForm($form);
                }
            }, 2000); // 2 Sekunden Verzögerung
        });
    };
    
    /**
     * Formular auto-speichern
     */
    AutomatenManager.autoSaveForm = function($form) {
        const url = $form.data('auto-save-url');
        const data = $form.serialize() + '&auto_save=1';
        
        $.post(url, data)
            .done(function(response) {
                if (response.success) {
                    AutomatenManager.showNotification('Automatisch gespeichert', 'info', 1000);
                }
            })
            .fail(function() {
                console.log('Auto-Save fehlgeschlagen');
            });
    };
    
    /**
     * Keyboard-Shortcuts
     */
    AutomatenManager.initKeyboardShortcuts = function() {
        $(document).on('keydown', function(e) {
            // Ctrl/Cmd + S = Speichern
            if ((e.ctrlKey || e.metaKey) && e.key === 's') {
                e.preventDefault();
                const $submitBtn = $('form:visible').find('button[type="submit"], input[type="submit"]').first();
                if ($submitBtn.length) {
                    $submitBtn.click();
                }
            }
            
            // Escape = Modal schließen
            if (e.key === 'Escape') {
                const $modal = $('.am-modal:visible');
                if ($modal.length) {
                    $modal.find('.am-btn-secondary').first().click();
                }
            }
            
            // Ctrl/Cmd + N = Neues Element
            if ((e.ctrlKey || e.metaKey) && e.key === 'n') {
                e.preventDefault();
                const $addBtn = $('.am-btn:contains("Neu"), .am-btn:contains("Hinzufügen")').first();
                if ($addBtn.length) {
                    $addBtn.click();
                }
            }
        });
    };
    
    /**
     * Notification anzeigen
     */
    AutomatenManager.showNotification = function(message, type = 'info', duration = 3000) {
        // Existierende Notification entfernen
        $('.am-notification').remove();
        
        const $notification = $('<div class="am-notification am-notification-' + type + '">' + message + '</div>');
        $('body').append($notification);
        
        // Animation einblenden
        setTimeout(function() {
            $notification.addClass('show');
        }, 100);
        
        // Auto-remove
        setTimeout(function() {
            $notification.removeClass('show');
            setTimeout(function() {
                $notification.remove();
            }, 300);
        }, duration);
        
        return $notification;
    };
    
    /**
     * Loading-State für Container
     */
    AutomatenManager.setLoading = function($container, loading = true) {
        if (loading) {
            $container.addClass('am-loading');
        } else {
            $container.removeClass('am-loading');
        }
    };
    
    /**
     * AJAX-Helper mit Error-Handling
     */
    AutomatenManager.ajax = function(options) {
        const defaults = {
            type: 'POST',
            dataType: 'json',
            timeout: 30000,
            data: {
                nonce: am_ajax.nonce
            }
        };
        
        const settings = $.extend({}, defaults, options);
        
        // Loading anzeigen falls Container angegeben
        if (settings.loadingContainer) {
            AutomatenManager.setLoading($(settings.loadingContainer), true);
        }
        
        return $.ajax(settings)
            .done(function(response) {
                if (!response.success && response.data && response.data.message) {
                    AutomatenManager.showNotification(response.data.message, 'error');
                }
            })
            .fail(function(xhr, status, error) {
                let message = 'Ein Fehler ist aufgetreten';
                
                if (status === 'timeout') {
                    message = 'Zeitüberschreitung der Anfrage';
                } else if (status === 'parsererror') {
                    message = 'Fehler beim Verarbeiten der Antwort';
                } else if (xhr.status === 403) {
                    message = 'Keine Berechtigung für diese Aktion';
                } else if (xhr.status === 404) {
                    message = 'Aktion nicht gefunden';
                } else if (xhr.status >= 500) {
                    message = 'Serverfehler';
                }
                
                AutomatenManager.showNotification(message, 'error');
                console.error('AJAX Error:', status, error, xhr.responseText);
            })
            .always(function() {
                // Loading ausblenden
                if (settings.loadingContainer) {
                    AutomatenManager.setLoading($(settings.loadingContainer), false);
                }
            });
    };
    
    /**
     * Debounce-Funktion für Search/Filter
     */
    AutomatenManager.debounce = function(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = function() {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    };
    
    /**
     * Format-Helper
     */
    AutomatenManager.formatPrice = function(price) {
        return parseFloat(price).toFixed(2) + '€';
    };
    
    AutomatenManager.formatDate = function(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('de-DE', {
            year: 'numeric',
            month: '2-digit',
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit'
        });
    };
    
    /**
     * Validation-Helper
     */
    AutomatenManager.validateBarcode = function(barcode) {
        // Basis-Validation für Barcodes
        if (!barcode || barcode.length < 8 || barcode.length > 20) {
            return false;
        }
        
        // Nur alphanumerische Zeichen erlaubt
        return /^[A-Z0-9]+$/.test(barcode);
    };
    
    AutomatenManager.validatePrice = function(price) {
        const numPrice = parseFloat(price);
        return !isNaN(numPrice) && numPrice >= 0 && numPrice <= 9999.99;
    };
    
    /**
     * Stock-Status Helper
     */
    AutomatenManager.getStockStatus = function(stock, minStock) {
        if (stock === 0) return 'empty';
        if (stock <= minStock) return 'low';
        return 'good';
    };
    
    AutomatenManager.getStockClass = function(stock, minStock) {
        const status = AutomatenManager.getStockStatus(stock, minStock);
        return 'am-stock-' + status;
    };
    
    /**
     * Search-Highlighting
     */
    AutomatenManager.highlightSearchTerm = function(text, searchTerm) {
        if (!searchTerm) return text;
        
        const regex = new RegExp('(' + searchTerm.replace(/[.*+?^${}()|[\]\\]/g, '\\    /**
     * Search-Highlighting
     */
    AutomatenManager.highlightSearchTerm = function(text, searchTerm) {
        if (!searchTerm) return text;
        
        const regex = new RegExp('(' + searchTerm.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + ')', 'gi');
        return text.replace(regex, '<mark>$1</mark') + ')', 'gi');
        return text.replace(regex, '<mark>$1</mark>');
    };
    
    /**
     * Image-Upload Handler
     */
    AutomatenManager.handleImageUpload = function($fileInput, callback) {
        $fileInput.on('change', function(e) {
            const file = e.target.files[0];
            if (!file) return;
            
            // Validate file type
            if (!file.type.match(/^image\/(jpeg|jpg|png|gif|webp)$/)) {
                AutomatenManager.showNotification('Nur Bilddateien sind erlaubt', 'error');
                return;
            }
            
            // Validate file size (max 5MB)
            if (file.size > 5 * 1024 * 1024) {
                AutomatenManager.showNotification('Datei zu groß (max. 5MB)', 'error');
                return;
            }
            
            const reader = new FileReader();
            reader.onload = function(e) {
                if (callback) callback(e.target.result);
            };
            reader.readAsDataURL(file);
        });
    };
    
    /**
     * Copy-to-Clipboard
     */
    AutomatenManager.copyToClipboard = function(text) {
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(text).then(function() {
                AutomatenManager.showNotification('In Zwischenablage kopiert', 'success', 1500);
            }).catch(function() {
                AutomatenManager.fallbackCopyTextToClipboard(text);
            });
        } else {
            AutomatenManager.fallbackCopyTextToClipboard(text);
        }
    };
    
    AutomatenManager.fallbackCopyTextToClipboard = function(text) {
        const textArea = document.createElement("textarea");
        textArea.value = text;
        textArea.style.top = "0";
        textArea.style.left = "0";
        textArea.style.position = "fixed";
        
        document.body.appendChild(textArea);
        textArea.focus();
        textArea.select();
        
        try {
            document.execCommand('copy');
            AutomatenManager.showNotification('In Zwischenablage kopiert', 'success', 1500);
        } catch (err) {
            AutomatenManager.showNotification('Kopieren fehlgeschlagen', 'error');
        }
        
        document.body.removeChild(textArea);
    };
    
    /**
     * Modal-Helper
     */
    AutomatenManager.openModal = function(modalId, options = {}) {
        const $modal = $('#' + modalId);
        if (!$modal.length) return;
        
        // Modal anzeigen
        $modal.show().addClass('am-modal-active');
        
        // Focus auf erstes Input
        setTimeout(function() {
            $modal.find('input, textarea, select').first().focus();
        }, 100);
        
        // Optional: Callback nach dem Öffnen
        if (options.onOpen) options.onOpen($modal);
        
        return $modal;
    };
    
    AutomatenManager.closeModal = function(modalId) {
        const $modal = modalId ? $('#' + modalId) : $('.am-modal:visible');
        
        $modal.removeClass('am-modal-active');
        setTimeout(function() {
            $modal.hide();
        }, 200);
    };
    
    /**
     * Table-Helper für responsive Tabellen
     */
    AutomatenManager.makeTableResponsive = function($table) {
        if (!$table.length) return;
        
        // Headers für mobile Ansicht hinzufügen
        const $headers = $table.find('thead th');
        $table.find('tbody tr').each(function() {
            $(this).find('td').each(function(index) {
                const headerText = $headers.eq(index).text();
                $(this).attr('data-label', headerText);
            });
        });
        
        $table.addClass('am-responsive-table');
    };
    
    /**
     * Form-Validation
     */
    AutomatenManager.validateForm = function($form) {
        let isValid = true;
        const errors = [];
        
        // Required Fields
        $form.find('[required]').each(function() {
            const $field = $(this);
            const value = $field.val().trim();
            
            if (!value) {
                isValid = false;
                $field.addClass('am-error');
                errors.push($field.prev('label').text() + ' ist erforderlich');
            } else {
                $field.removeClass('am-error');
            }
        });
        
        // Email validation
        $form.find('input[type="email"]').each(function() {
            const $field = $(this);
            const value = $field.val().trim();
            
            if (value && !AutomatenManager.isValidEmail(value)) {
                isValid = false;
                $field.addClass('am-error');
                errors.push('Ungültige E-Mail-Adresse');
            }
        });
        
        // Custom validations
        $form.find('input[name="barcode"]').each(function() {
            const $field = $(this);
            const value = $field.val().trim();
            
            if (value && !AutomatenManager.validateBarcode(value)) {
                isValid = false;
                $field.addClass('am-error');
                errors.push('Ungültiger Barcode');
            }
        });
        
        $form.find('input[type="number"][step="0.01"]').each(function() {
            const $field = $(this);
            const value = parseFloat($field.val());
            
            if ($field.val() && !AutomatenManager.validatePrice(value)) {
                isValid = false;
                $field.addClass('am-error');
                errors.push('Ungültiger Preis');
            }
        });
        
        // Show errors
        if (!isValid) {
            AutomatenManager.showNotification(errors[0], 'error');
        }
        
        return isValid;
    };
    
    AutomatenManager.isValidEmail = function(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    };
    
    /**
     * Data-Export Helper
     */
    AutomatenManager.exportToCSV = function(data, filename) {
        if (!data || !data.length) {
            AutomatenManager.showNotification('Keine Daten zum Exportieren', 'error');
            return;
        }
        
        const csvContent = AutomatenManager.convertToCSV(data);
        const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
        
        if (navigator.msSaveBlob) { // IE 10+
            navigator.msSaveBlob(blob, filename);
        } else {
            const link = document.createElement('a');
            if (link.download !== undefined) {
                const url = URL.createObjectURL(blob);
                link.setAttribute('href', url);
                link.setAttribute('download', filename);
                link.style.visibility = 'hidden';
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            }
        }
        
        AutomatenManager.showNotification('Export erfolgreich', 'success');
    };
    
    AutomatenManager.convertToCSV = function(data) {
        if (!data.length) return '';
        
        const headers = Object.keys(data[0]);
        const csvRows = [];
        
        // Header row
        csvRows.push(headers.join(','));
        
        // Data rows
        for (const row of data) {
            const values = headers.map(header => {
                const value = row[header];
                return typeof value === 'string' ? `"${value.replace(/"/g, '""')}"` : value;
            });
            csvRows.push(values.join(','));
        }
        
        return csvRows.join('\n');
    };
    
    /**
     * Local Storage Helper
     */
    AutomatenManager.storage = {
        set: function(key, value) {
            try {
                localStorage.setItem('am_' + key, JSON.stringify(value));
            } catch (e) {
                console.warn('LocalStorage nicht verfügbar:', e);
            }
        },
        
        get: function(key, defaultValue = null) {
            try {
                const item = localStorage.getItem('am_' + key);
                return item ? JSON.parse(item) : defaultValue;
            } catch (e) {
                console.warn('LocalStorage nicht verfügbar:', e);
                return defaultValue;
            }
        },
        
        remove: function(key) {
            try {
                localStorage.removeItem('am_' + key);
            } catch (e) {
                console.warn('LocalStorage nicht verfügbar:', e);
            }
        }
    };
    
    /**
     * Performance Helper
     */
    AutomatenManager.performance = {
        marks: {},
        
        mark: function(name) {
            this.marks[name] = performance.now();
        },
        
        measure: function(name, startMark) {
            const endTime = performance.now();
            const startTime = this.marks[startMark] || 0;
            const duration = endTime - startTime;
            
            console.log(`Performance [${name}]: ${duration.toFixed(2)}ms`);
            return duration;
        }
    };
    
    /**
     * Browser-Detection für Feature-Checks
     */
    AutomatenManager.browser = {
        isIE: function() {
            return /MSIE|Trident/.test(navigator.userAgent);
        },
        
        isSafari: function() {
            return /Safari/.test(navigator.userAgent) && !/Chrome/.test(navigator.userAgent);
        },
        
        isMobile: function() {
            return /Android|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
        },
        
        supportsWebP: function() {
            const canvas = document.createElement('canvas');
            canvas.width = 1;
            canvas.height = 1;
            return canvas.toDataURL('image/webp').indexOf('data:image/webp') === 0;
        }
    };
    
    /**
     * Accessibility Helper
     */
    AutomatenManager.a11y = {
        announceToScreenReader: function(message) {
            const announcement = document.createElement('div');
            announcement.setAttribute('aria-live', 'polite');
            announcement.setAttribute('aria-atomic', 'true');
            announcement.className = 'sr-only';
            announcement.textContent = message;
            
            document.body.appendChild(announcement);
            
            setTimeout(function() {
                document.body.removeChild(announcement);
            }, 1000);
        },
        
        trapFocus: function($container) {
            const focusableElements = $container.find('button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])');
            const firstElement = focusableElements.first();
            const lastElement = focusableElements.last();
            
            $container.on('keydown.focus-trap', function(e) {
                if (e.key === 'Tab') {
                    if (e.shiftKey && e.target === firstElement[0]) {
                        e.preventDefault();
                        lastElement.focus();
                    } else if (!e.shiftKey && e.target === lastElement[0]) {
                        e.preventDefault();
                        firstElement.focus();
                    }
                }
            });
            
            firstElement.focus();
        },
        
        removeFocusTrap: function($container) {
            $container.off('keydown.focus-trap');
        }
    };
    
})(jQuery);

// CSS für zusätzliche Utility-Klassen
const additionalCSS = `
<style>
/* Screen Reader Only */
.sr-only {
    position: absolute;
    width: 1px;
    height: 1px;
    padding: 0;
    margin: -1px;
    overflow: hidden;
    clip: rect(0, 0, 0, 0);
    white-space: nowrap;
    border: 0;
}

/* Error States */
.am-error {
    border-color: var(--am-error) !important;
    box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1) !important;
}

/* Responsive Table */
.am-responsive-table {
    width: 100%;
    border-collapse: collapse;
}

@media (max-width: 768px) {
    .am-responsive-table,
    .am-responsive-table thead,
    .am-responsive-table tbody,
    .am-responsive-table th,
    .am-responsive-table td,
    .am-responsive-table tr {
        display: block;
    }
    
    .am-responsive-table thead tr {
        position: absolute;
        top: -9999px;
        left: -9999px;
    }
    
    .am-responsive-table tr {
        border: 1px solid var(--am-gray-200);
        margin-bottom: var(--am-space-4);
        border-radius: var(--am-radius);
        overflow: hidden;
    }
    
    .am-responsive-table td {
        border: none !important;
        position: relative;
        padding: var(--am-space-3) var(--am-space-4) var(--am-space-3) 35% !important;
        border-bottom: 1px solid var(--am-gray-100);
    }
    
    .am-responsive-table td:before {
        content: attr(data-label) ": ";
        position: absolute;
        left: var(--am-space-3);
        width: 30%;
        padding-right: var(--am-space-2);
        white-space: nowrap;
        font-weight: 600;
        color: var(--am-gray-700);
    }
}

/* Tooltip */
.am-tooltip {
    position: absolute;
    background: var(--am-gray-800);
    color: white;
    padding: var(--am-space-2) var(--am-space-3);
    border-radius: var(--am-radius);
    font-size: 0.875rem;
    z-index: 10000;
    opacity: 0;
    transition: opacity 0.2s;
    pointer-events: none;
    max-width: 200px;
}

.am-tooltip:after {
    content: '';
    position: absolute;
    top: 100%;
    left: 50%;
    margin-left: -5px;
    border-width: 5px;
    border-style: solid;
    border-color: var(--am-gray-800) transparent transparent transparent;
}

/* Search Highlighting */
mark {
    background: var(--am-neon-green);
    color: var(--am-gray-900);
    padding: 1px 2px;
    border-radius: 2px;
}

/* Modal Animation */
.am-modal {
    opacity: 0;
    transform: scale(0.7);
    transition: all 0.2s ease-out;
}

.am-modal.am-modal-active {
    opacity: 1;
    transform: scale(1);
}

/* Print Styles */
@media print {
    .am-btn,
    .am-modal,
    .am-notification {
        display: none !important;
    }
    
    .am-card {
        break-inside: avoid;
        box-shadow: none;
        border: 1px solid #000;
    }
}
</style>
`;

// CSS zum Head hinzufügen
if (document.head) {
    document.head.insertAdjacentHTML('beforeend', additionalCSS);
}