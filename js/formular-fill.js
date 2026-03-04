/**
 * Formular Fill - JavaScript
 * Handles form validation, signature pad, AJAX submission
 */

(function($) {
    'use strict';

    // Signature Pad implementation
    var SignaturePad = function(canvas) {
        this.canvas = canvas;
        this.ctx = canvas.getContext('2d');
        this.isDrawing = false;
        this.points = [];
        this.hasSignature = false;
        
        this.init();
    };

    SignaturePad.prototype.init = function() {
        var self = this;
        
        // Set canvas size based on container
        this.resize();
        
        // Mouse events
        this.canvas.addEventListener('mousedown', function(e) {
            self.startDrawing(e);
        });
        this.canvas.addEventListener('mousemove', function(e) {
            self.draw(e);
        });
        this.canvas.addEventListener('mouseup', function(e) {
            self.stopDrawing(e);
        });
        this.canvas.addEventListener('mouseout', function(e) {
            self.stopDrawing(e);
        });
        
        // Touch events
        this.canvas.addEventListener('touchstart', function(e) {
            e.preventDefault();
            self.startDrawing(e.touches[0]);
        });
        this.canvas.addEventListener('touchmove', function(e) {
            e.preventDefault();
            self.draw(e.touches[0]);
        });
        this.canvas.addEventListener('touchend', function(e) {
            e.preventDefault();
            self.stopDrawing(e);
        });
        
        // Handle window resize
        $(window).on('resize', function() {
            self.resize();
        });
    };

    SignaturePad.prototype.resize = function() {
        var ratio = Math.max(window.devicePixelRatio || 1, 1);
        var rect = this.canvas.getBoundingClientRect();
        
        this.canvas.width = rect.width * ratio;
        this.canvas.height = rect.height * ratio;
        this.ctx.scale(ratio, ratio);
        
        // Clear canvas after resize
        this.ctx.fillStyle = '#fff';
        this.ctx.fillRect(0, 0, rect.width, rect.height);
    };

    SignaturePad.prototype.getCoordinates = function(e) {
        var rect = this.canvas.getBoundingClientRect();
        return {
            x: e.clientX - rect.left,
            y: e.clientY - rect.top
        };
    };

    SignaturePad.prototype.startDrawing = function(e) {
        this.isDrawing = true;
        this.points = [];
        var coords = this.getCoordinates(e);
        this.points.push(coords);
        this.ctx.beginPath();
        this.ctx.moveTo(coords.x, coords.y);
    };

    SignaturePad.prototype.draw = function(e) {
        if (!this.isDrawing) return;
        
        var coords = this.getCoordinates(e);
        this.points.push(coords);
        
        this.ctx.lineTo(coords.x, coords.y);
        this.ctx.strokeStyle = '#000';
        this.ctx.lineWidth = 2;
        this.ctx.lineCap = 'round';
        this.ctx.lineJoin = 'round';
        this.ctx.stroke();
    };

    SignaturePad.prototype.stopDrawing = function(e) {
        if (this.isDrawing) {
            this.isDrawing = false;
            this.hasSignature = this.points.length > 10;
        }
    };

    SignaturePad.prototype.clear = function() {
        var rect = this.canvas.getBoundingClientRect();
        this.ctx.fillStyle = '#fff';
        this.ctx.fillRect(0, 0, rect.width, rect.height);
        this.points = [];
        this.hasSignature = false;
    };

    SignaturePad.prototype.getDataURL = function() {
        if (!this.hasSignature) return null;
        return this.canvas.toDataURL('image/png');
    };

    // Main FormularFill class
    var FormularFill = {
        init: function() {
            this.bindEvents();
        },
        
        bindEvents: function() {
            var self = this;
            
            // Form submission
            $(document).on('submit', '.formular-fill-form', function(e) {
                e.preventDefault();
                self.handleSubmit($(this));
            });
            
            // County change - load localities
            $(document).on('change', 'select[name="judet"]', function() {
                self.loadLocalities($(this));
            });
            
            // Signature clear button
            $(document).on('click', '.wer-sign-clear-but', function() {
                var signContainer = $(this).closest('.wer-sign');
                var canvas = signContainer.find('canvas');
                var signaturePad = canvas.data('signaturePad');
                if (signaturePad) {
                    signaturePad.clear();
                    signContainer.find('input[name="semnatura"]').val('');
                }
            });
            
            // Initialize signature pads
            $(document).on('wer-sign-init', function() {
                self.initSignaturePads();
            });
            
            // Trigger signature pad init
            setTimeout(function() {
                $(document).trigger('wer-sign-init');
            }, 100);
        },
        
        initSignaturePads: function() {
            var self = this;
            
            $('.wer-sign').each(function() {
                var $container = $(this);
                var $canvas = $container.find('canvas');
                
                if (!$canvas.data('initialized')) {
                    var signaturePad = new SignaturePad($canvas[0]);
                    $canvas.data('signaturePad', signaturePad);
                    $canvas.data('initialized', true);
                    
                    // Store signature data on hidden input when signing
                    $canvas.on('mouseup touchend', function() {
                        var dataUrl = signaturePad.getDataURL();
                        if (dataUrl) {
                            $container.find('input[name="semnatura"]').val(dataUrl);
                        }
                    });
                }
            });
        },
        
        loadLocalities: function($countySelect) {
            var $form = $countySelect.closest('form');
            var $localitySelect = $form.find('select[name="localitate"]');
            var countyId = $countySelect.val();
            
            if (!countyId) {
                $localitySelect.prop('disabled', true);
                $localitySelect.html('<option value="">Localitate* (alege)</option>');
                return;
            }
            
            // Show loading
            $localitySelect.prop('disabled', true);
            $localitySelect.html('<option value="">Se încarcă...</option>');
            
            $.ajax({
                url: formularFill.ajax_url,
                type: 'POST',
                data: {
                    action: 'formular_fill_get_localities',
                    nonce: formularFill.nonce,
                    county_id: countyId
                },
                success: function(response) {
                    if (response.success && response.data) {
                        var options = '<option value="">Localitate* (alege)</option>';
                        $.each(response.data, function(id, name) {
                            options += '<option value="' + id + '">' + name + '</option>';
                        });
                        $localitySelect.html(options);
                        $localitySelect.prop('disabled', false);
                    } else {
                        $localitySelect.html('<option value="">Localitate* (alege)</option>');
                    }
                },
                error: function() {
                    $localitySelect.html('<option value="">Eroare la încărcare</option>');
                }
            });
        },
        
        validateForm: function($form) {
            var self = this;
            var isValid = true;
            var errors = [];
            
            // Check required fields
            $form.find('[data-r="1"]').each(function() {
                var $field = $(this);
                var name = $field.attr('name');
                var value = $field.val();
                var $msgContainer = $field.siblings('.wer-i-msg');
                
                // Skip hidden checkboxes that aren't checked
                if ($field.attr('type') === 'checkbox' && !$field.is(':checked')) {
                    $field.addClass('error');
                    $msgContainer.text('Câmp obligatoriu');
                    isValid = false;
                    return;
                }
                
                if (!value || $.trim(value) === '') {
                    $field.addClass('error');
                    if ($msgContainer.length && $msgContainer.text() === '') {
                        $msgContainer.text('Câmp obligatoriu');
                    }
                    isValid = false;
                } else {
                    $field.removeClass('error');
                    $msgContainer.text('');
                }
            });
            
            // Validate CNP/NIF - only if filled in
            var $cnpField = $form.find('input[name="cnp"]');
            if ($cnpField.length && $cnpField.val()) {
                if (!self.validateCNP($cnpField.val())) {
                    $cnpField.addClass('error');
                    $cnpField.siblings('.wer-i-msg').text('CNP/NIF incorect!');
                    isValid = false;
                } else {
                    $cnpField.removeClass('error');
                    $cnpField.siblings('.wer-i-msg').text('');
                }
            } else {
                // Clear any previous error
                $cnpField.removeClass('error');
                $cnpField.siblings('.wer-i-msg').text('');
            }
            
            // Validate Email - only if filled in
            var $mailField = $form.find('input[name="mail"]');
            if ($mailField.length && $mailField.val()) {
                if (!self.validateEmail($mailField.val())) {
                    $mailField.addClass('error');
                    $mailField.siblings('.wer-i-msg').text('Email incorect');
                    isValid = false;
                } else {
                    $mailField.removeClass('error');
                    $mailField.siblings('.wer-i-msg').text('');
                }
            } else {
                // Clear any previous error
                $mailField.removeClass('error');
                $mailField.siblings('.wer-i-msg').text('');
            }
            
            // Check signature
            var $signatureInput = $form.find('input[name="semnatura"]');
            if ($signatureInput.length && !$signatureInput.val()) {
                var $signContainer = $signatureInput.closest('.wer-sign');
                $signContainer.find('.wer-i-msg, .wer-sign-controls .wer-i-msg').text('Semnați în chenar');
                isValid = false;
            }
            
            return isValid;
        },
        
        validateCNP: function(cnp) {
            // Basic CNP validation (Romanian CNP is 13 digits)
            // This is a simplified check - you may want to implement full validation
            cnp = cnp.replace(/\s/g, '');
            if (cnp.length < 13) return false;
            if (!/^\d+$/.test(cnp)) return false;
            return true;
        },
        
        validateEmail: function(email) {
            // More robust email validation
            var re = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
            return re.test(email);
        },
        
        handleSubmit: function($form) {
            var self = this;
            
            // Validate form
            if (!self.validateForm($form)) {
                self.showStatus($form, 'Vă rugăm să completați toate câmpurile obligatorii corect.', 'error');
                return;
            }
            
            // Show loading
            self.showStatus($form, 'Se generează PDF...', 'loading');
            $('#submit-btn-' + $form.find('input[name="form_id"]').val()).prop('disabled', true);
            
            // Collect form data
            var formData = {};
            $form.serializeArray().forEach(function(item) {
                formData[item.name] = item.value;
            });
            
            // Add signature
            var $signatureInput = $form.find('input[name="semnatura"]');
            if ($signatureInput.length && $signatureInput.val()) {
                formData['semnatura'] = $signatureInput.val();
            }
            
            // Add radio button values (perioada - single selection)
            $form.find('input[type="radio"]').each(function() {
                var name = $(this).attr('name');
                if (name && $(this).is(':checked')) {
                    formData[name] = $(this).val();
                }
            });
            
            // Add checkbox values
            $form.find('input[type="checkbox"]').each(function() {
                var name = $(this).attr('name');
                if (name) {
                    formData[name] = $(this).is(':checked') ? '1' : '0';
                }
            });
            
            // Submit via AJAX
            $.ajax({
                url: formularFill.ajax_url,
                type: 'POST',
                data: {
                    action: 'formular_fill_submit',
                    nonce: formularFill.nonce,
                    form_id: $form.find('input[name="form_id"]').val(),
                    form_data: formData
                },
                success: function(response) {
                    if (response.success) {
                        self.showStatus($form, 'PDF generat cu succes! Se descarcă...', 'success');
                        
                        // Trigger download
                        if (response.data.pdf_url) {
                            var downloadUrl = response.data.pdf_url;
                            // Open in new tab or download
                            window.open(downloadUrl, '_blank');
                        }
                        
                        setTimeout(function() {
                            $('#submit-btn-' + $form.find('input[name="form_id"]').val()).prop('disabled', false);
                        }, 2000);
                    } else {
                        self.showStatus($form, response.data.message || 'Eroare la generarea PDF-ului', 'error');
                        $('#submit-btn-' + $form.find('input[name="form_id"]').val()).prop('disabled', false);
                    }
                },
                error: function(xhr, status, error) {
                    self.showStatus($form, 'Eroare de conexiune. Încercați din nou.', 'error');
                    $('#submit-btn-' + $form.find('input[name="form_id"]').val()).prop('disabled', false);
                }
            });
        },
        
        showStatus: function($form, message, type) {
            var $status = $form.find('.wer-status');
            $status.removeClass('success error loading');
            $status.addClass(type);
            $status.text(message);
        }
    };

    // Initialize on document ready
    $(document).ready(function() {
        FormularFill.init();
    });

})(jQuery);
