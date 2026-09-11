/**
 * Emergency Button Component
 * Provides panic button functionality with emergency type selection
 */

class EmergencyButton {
    constructor(containerId, options = {}) {
        this.container = document.getElementById(containerId);
        if (!this.container) {
            throw new Error(`Container element with ID '${containerId}' not found`);
        }

        this.options = {
            bookingId: null,
            size: 'large', // small, medium, large
            showText: true,
            autoHide: false, // Hide after emergency is created
            ...options
        };

        this.gpsTracker = window.gpsTracker || new GPSTrackingService();
        this.isCreatingEmergency = false;

        this.emergencyTypes = [
            { value: 'breakdown', label: '🔧 Vehicle Breakdown', description: 'Car not working properly' },
            { value: 'accident', label: '💥 Accident', description: 'Vehicle collision or damage' },
            { value: 'theft', label: '🚨 Theft/Security', description: 'Theft attempt or security concern' },
            { value: 'harassment', label: '⚠️ Harassment', description: 'Personal safety concern' },
            { value: 'medical', label: '🏥 Medical Emergency', description: 'Health emergency' },
            { value: 'other', label: '📞 Other Emergency', description: 'Other urgent situation' }
        ];

        this.init();
    }

    /**
     * Initialize the emergency button
     */
    init() {
        this.render();
        this.attachEventListeners();

        // Request notification permission
        this.gpsTracker.requestNotificationPermission().catch(err => {
            console.warn('Notification permission denied:', err);
        });
    }

    /**
     * Render the emergency button HTML
     */
    render() {
        const buttonSize = this.getSizeClass();
        const buttonText = this.options.showText ? 'EMERGENCY' : '';

        this.container.innerHTML = `
            <div class="emergency-button-container">
                <button 
                    id="emergency-btn" 
                    class="emergency-button ${buttonSize}" 
                    title="Click for emergency assistance"
                    aria-label="Emergency assistance button"
                >
                    <span class="emergency-icon">🆘</span>
                    ${buttonText ? `<span class="emergency-text">${buttonText}</span>` : ''}
                </button>
                
                <!-- Emergency Modal -->
                <div id="emergency-modal" class="emergency-modal" style="display: none;">
                    <div class="emergency-modal-overlay"></div>
                    <div class="emergency-modal-content">
                        <div class="emergency-modal-header">
                            <h3>🆘 Emergency Assistance</h3>
                            <button id="emergency-modal-close" class="emergency-modal-close">&times;</button>
                        </div>
                        
                        <div class="emergency-modal-body">
                            <p class="emergency-warning">
                                ⚠️ This will immediately notify the vendor and emergency services if needed.
                                Only use for genuine emergencies.
                            </p>
                            
                            <div class="emergency-types">
                                <h4>Select Emergency Type:</h4>
                                <div class="emergency-type-grid">
                                    ${this.emergencyTypes.map(type => `
                                        <label class="emergency-type-option">
                                            <input 
                                                type="radio" 
                                                name="emergency-type" 
                                                value="${type.value}"
                                                class="emergency-type-radio"
                                            >
                                            <div class="emergency-type-card">
                                                <div class="emergency-type-label">${type.label}</div>
                                                <div class="emergency-type-desc">${type.description}</div>
                                            </div>
                                        </label>
                                    `).join('')}
                                </div>
                            </div>
                            
                            <div class="emergency-description">
                                <label for="emergency-desc-input">
                                    <h4>Description (Optional):</h4>
                                </label>
                                <textarea 
                                    id="emergency-desc-input" 
                                    placeholder="Briefly describe the emergency situation..."
                                    maxlength="500"
                                    rows="3"
                                ></textarea>
                                <div class="char-counter">
                                    <span id="char-count">0</span>/500
                                </div>
                            </div>
                        </div>
                        
                        <div class="emergency-modal-footer">
                            <button id="emergency-cancel" class="btn btn-secondary">
                                Cancel
                            </button>
                            <button id="emergency-submit" class="btn btn-danger" disabled>
                                <span class="btn-text">🆘 Send Emergency Alert</span>
                                <span class="btn-loading" style="display: none;">
                                    <span class="spinner"></span> Sending...
                                </span>
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Success Message -->
                <div id="emergency-success" class="emergency-success-message" style="display: none;">
                    <div class="success-content">
                        <div class="success-icon">✅</div>
                        <h4>Emergency Alert Sent!</h4>
                        <p>Emergency Code: <strong id="emergency-code"></strong></p>
                        <p>Help is on the way. Stay calm and follow the instructions you receive.</p>
                        <button id="emergency-success-close" class="btn btn-primary">OK</button>
                    </div>
                </div>
            </div>
        `;

        this.addStyles();
    }

    /**
     * Add CSS styles for the component
     */
    addStyles() {
        if (document.getElementById('emergency-button-styles')) return;

        const styles = document.createElement('style');
        styles.id = 'emergency-button-styles';
        styles.textContent = `
            .emergency-button-container {
                position: relative;
                z-index: 1000;
            }
            
            .emergency-button {
                background: linear-gradient(45deg, #ff4444, #cc0000);
                border: 3px solid #ffffff;
                border-radius: 50%;
                color: white;
                cursor: pointer;
                display: flex;
                align-items: center;
                justify-content: center;
                flex-direction: column;
                font-weight: bold;
                box-shadow: 0 4px 20px rgba(255, 68, 68, 0.4);
                transition: all 0.3s ease;
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                position: relative;
                overflow: hidden;
            }
            
            .emergency-button:hover {
                background: linear-gradient(45deg, #ff6666, #dd0000);
                box-shadow: 0 6px 25px rgba(255, 68, 68, 0.6);
                transform: translateY(-2px);
            }
            
            .emergency-button:active {
                transform: translateY(0);
                box-shadow: 0 4px 15px rgba(255, 68, 68, 0.4);
            }
            
            .emergency-button::before {
                content: '';
                position: absolute;
                top: 50%;
                left: 50%;
                width: 0;
                height: 0;
                background: rgba(255, 255, 255, 0.3);
                border-radius: 50%;
                transform: translate(-50%, -50%);
                transition: width 0.6s, height 0.6s;
            }
            
            .emergency-button:hover::before {
                width: 100%;
                height: 100%;
            }
            
            .emergency-button.size-small {
                width: 60px;
                height: 60px;
                font-size: 24px;
            }
            
            .emergency-button.size-medium {
                width: 80px;
                height: 80px;
                font-size: 28px;
            }
            
            .emergency-button.size-large {
                width: 100px;
                height: 100px;
                font-size: 32px;
            }
            
            .emergency-icon {
                font-size: 1.2em;
                z-index: 1;
                position: relative;
            }
            
            .emergency-text {
                font-size: 0.3em;
                margin-top: 2px;
                z-index: 1;
                position: relative;
                text-align: center;
                line-height: 1;
            }
            
            .emergency-modal {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                z-index: 10000;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            
            .emergency-modal-overlay {
                position: absolute;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.7);
                backdrop-filter: blur(4px);
            }
            
            .emergency-modal-content {
                background: white;
                border-radius: 12px;
                box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
                max-width: 500px;
                width: 90%;
                max-height: 90vh;
                overflow-y: auto;
                position: relative;
                z-index: 1;
            }
            
            .emergency-modal-header {
                padding: 20px 24px 16px;
                border-bottom: 1px solid #e5e7eb;
                display: flex;
                align-items: center;
                justify-content: space-between;
            }
            
            .emergency-modal-header h3 {
                margin: 0;
                color: #dc2626;
                font-size: 18px;
                font-weight: 600;
            }
            
            .emergency-modal-close {
                background: none;
                border: none;
                font-size: 24px;
                cursor: pointer;
                color: #6b7280;
                padding: 0;
                width: 32px;
                height: 32px;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            
            .emergency-modal-close:hover {
                background: #f3f4f6;
                color: #374151;
            }
            
            .emergency-modal-body {
                padding: 20px 24px;
            }
            
            .emergency-warning {
                background: #fef2f2;
                border: 1px solid #fecaca;
                border-radius: 8px;
                padding: 12px;
                margin-bottom: 20px;
                color: #dc2626;
                font-size: 14px;
            }
            
            .emergency-types h4 {
                margin: 0 0 12px 0;
                color: #374151;
                font-size: 16px;
                font-weight: 600;
            }
            
            .emergency-type-grid {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 8px;
                margin-bottom: 20px;
            }
            
            .emergency-type-option {
                cursor: pointer;
            }
            
            .emergency-type-radio {
                display: none;
            }
            
            .emergency-type-card {
                border: 2px solid #e5e7eb;
                border-radius: 8px;
                padding: 12px;
                transition: all 0.2s ease;
                background: white;
            }
            
            .emergency-type-radio:checked + .emergency-type-card {
                border-color: #dc2626;
                background: #fef2f2;
            }
            
            .emergency-type-card:hover {
                border-color: #dc2626;
                background: #fefefe;
            }
            
            .emergency-type-label {
                font-weight: 600;
                font-size: 14px;
                margin-bottom: 4px;
                color: #374151;
            }
            
            .emergency-type-desc {
                font-size: 12px;
                color: #6b7280;
                line-height: 1.3;
            }
            
            .emergency-description h4 {
                margin: 0 0 8px 0;
                color: #374151;
                font-size: 16px;
                font-weight: 600;
            }
            
            .emergency-description textarea {
                width: 100%;
                border: 1px solid #d1d5db;
                border-radius: 6px;
                padding: 8px 12px;
                font-size: 14px;
                resize: vertical;
                font-family: inherit;
            }
            
            .emergency-description textarea:focus {
                outline: none;
                border-color: #dc2626;
                box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.1);
            }
            
            .char-counter {
                text-align: right;
                font-size: 12px;
                color: #6b7280;
                margin-top: 4px;
            }
            
            .emergency-modal-footer {
                padding: 16px 24px 20px;
                border-top: 1px solid #e5e7eb;
                display: flex;
                gap: 12px;
                justify-content: flex-end;
            }
            
            .btn {
                padding: 8px 16px;
                border-radius: 6px;
                border: none;
                cursor: pointer;
                font-size: 14px;
                font-weight: 500;
                transition: all 0.2s ease;
                display: flex;
                align-items: center;
                gap: 6px;
            }
            
            .btn-secondary {
                background: #f3f4f6;
                color: #374151;
            }
            
            .btn-secondary:hover {
                background: #e5e7eb;
            }
            
            .btn-danger {
                background: #dc2626;
                color: white;
            }
            
            .btn-danger:hover:not(:disabled) {
                background: #b91c1c;
            }
            
            .btn-danger:disabled {
                background: #9ca3af;
                cursor: not-allowed;
            }
            
            .btn-primary {
                background: #2563eb;
                color: white;
            }
            
            .btn-primary:hover {
                background: #1d4ed8;
            }
            
            .spinner {
                width: 12px;
                height: 12px;
                border: 2px solid transparent;
                border-top: 2px solid currentColor;
                border-radius: 50%;
                animation: spin 0.8s linear infinite;
            }
            
            @keyframes spin {
                to { transform: rotate(360deg); }
            }
            
            .emergency-success-message {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                z-index: 10001;
                display: flex;
                align-items: center;
                justify-content: center;
                background: rgba(0, 0, 0, 0.7);
                backdrop-filter: blur(4px);
            }
            
            .success-content {
                background: white;
                border-radius: 12px;
                padding: 32px 24px;
                text-align: center;
                max-width: 400px;
                width: 90%;
                box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            }
            
            .success-icon {
                font-size: 48px;
                margin-bottom: 16px;
            }
            
            .success-content h4 {
                margin: 0 0 16px 0;
                color: #059669;
                font-size: 20px;
                font-weight: 600;
            }
            
            .success-content p {
                margin: 8px 0;
                color: #374151;
                line-height: 1.5;
            }
            
            @media (max-width: 480px) {
                .emergency-type-grid {
                    grid-template-columns: 1fr;
                }
                
                .emergency-modal-content {
                    margin: 20px;
                    width: calc(100% - 40px);
                }
            }
        `;

        document.head.appendChild(styles);
    }

    /**
     * Get CSS class for button size
     */
    getSizeClass() {
        return `size-${this.options.size}`;
    }

    /**
     * Attach event listeners
     */
    attachEventListeners() {
        const emergencyBtn = this.container.querySelector('#emergency-btn');
        const modal = this.container.querySelector('#emergency-modal');
        const modalClose = this.container.querySelector('#emergency-modal-close');
        const cancelBtn = this.container.querySelector('#emergency-cancel');
        const submitBtn = this.container.querySelector('#emergency-submit');
        const overlay = this.container.querySelector('.emergency-modal-overlay');
        const descInput = this.container.querySelector('#emergency-desc-input');
        const charCount = this.container.querySelector('#char-count');
        const successModal = this.container.querySelector('#emergency-success');
        const successClose = this.container.querySelector('#emergency-success-close');

        // Open modal
        emergencyBtn.addEventListener('click', () => {
            this.openModal();
        });

        // Close modal handlers
        [modalClose, cancelBtn, overlay].forEach(element => {
            element.addEventListener('click', () => {
                this.closeModal();
            });
        });

        // Emergency type selection
        const typeRadios = this.container.querySelectorAll('.emergency-type-radio');
        typeRadios.forEach(radio => {
            radio.addEventListener('change', () => {
                this.updateSubmitButton();
            });
        });

        // Description character counter
        descInput.addEventListener('input', () => {
            const count = descInput.value.length;
            charCount.textContent = count;

            if (count > 450) {
                charCount.style.color = '#dc2626';
            } else {
                charCount.style.color = '#6b7280';
            }
        });

        // Submit emergency
        submitBtn.addEventListener('click', () => {
            this.handleEmergencySubmit();
        });

        // Success modal close
        successClose.addEventListener('click', () => {
            this.closeSuccessModal();
        });

        // Keyboard shortcuts
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                this.closeModal();
                this.closeSuccessModal();
            }
        });
    }

    /**
     * Open emergency modal
     */
    openModal() {
        const modal = this.container.querySelector('#emergency-modal');
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';

        // Focus first radio button
        const firstRadio = this.container.querySelector('.emergency-type-radio');
        if (firstRadio) {
            firstRadio.focus();
        }
    }

    /**
     * Close emergency modal
     */
    closeModal() {
        const modal = this.container.querySelector('#emergency-modal');
        modal.style.display = 'none';
        document.body.style.overflow = '';

        // Reset form
        this.resetForm();
    }

    /**
     * Close success modal
     */
    closeSuccessModal() {
        const successModal = this.container.querySelector('#emergency-success');
        successModal.style.display = 'none';
        document.body.style.overflow = '';

        if (this.options.autoHide) {
            this.container.style.display = 'none';
        }
    }

    /**
     * Reset form to initial state
     */
    resetForm() {
        const typeRadios = this.container.querySelectorAll('.emergency-type-radio');
        typeRadios.forEach(radio => {
            radio.checked = false;
        });

        const descInput = this.container.querySelector('#emergency-desc-input');
        descInput.value = '';

        const charCount = this.container.querySelector('#char-count');
        charCount.textContent = '0';
        charCount.style.color = '#6b7280';

        this.updateSubmitButton();
    }

    /**
     * Update submit button state
     */
    updateSubmitButton() {
        const submitBtn = this.container.querySelector('#emergency-submit');
        const selectedType = this.container.querySelector('.emergency-type-radio:checked');

        submitBtn.disabled = !selectedType || this.isCreatingEmergency;
    }

    /**
     * Handle emergency form submission
     */
    async handleEmergencySubmit() {
        if (this.isCreatingEmergency) return;

        const selectedType = this.container.querySelector('.emergency-type-radio:checked');
        if (!selectedType) return;

        const description = this.container.querySelector('#emergency-desc-input').value.trim();
        const submitBtn = this.container.querySelector('#emergency-submit');
        const btnText = submitBtn.querySelector('.btn-text');
        const btnLoading = submitBtn.querySelector('.btn-loading');

        try {
            this.isCreatingEmergency = true;

            // Update button state
            submitBtn.disabled = true;
            btnText.style.display = 'none';
            btnLoading.style.display = 'flex';

            // Create emergency report
            const result = await this.gpsTracker.createEmergencyReport(
                selectedType.value,
                description
            );

            // Show success message
            this.showSuccessMessage(result.emergency_code);
            this.closeModal();

        } catch (error) {
            console.error('Emergency submission failed:', error);
            alert(`Failed to send emergency alert: ${error.message}`);

        } finally {
            this.isCreatingEmergency = false;

            // Reset button state
            submitBtn.disabled = false;
            btnText.style.display = 'flex';
            btnLoading.style.display = 'none';
        }
    }

    /**
     * Show success message with emergency code
     */
    showSuccessMessage(emergencyCode) {
        const successModal = this.container.querySelector('#emergency-success');
        const codeElement = this.container.querySelector('#emergency-code');

        codeElement.textContent = emergencyCode;
        successModal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }

    /**
     * Set booking ID for emergency reports
     */
    setBookingId(bookingId) {
        this.options.bookingId = bookingId;
        if (this.gpsTracker) {
            this.gpsTracker.bookingId = bookingId;
        }
    }

    /**
     * Destroy the component and clean up
     */
    destroy() {
        this.container.innerHTML = '';
        document.body.style.overflow = '';

        const styles = document.getElementById('emergency-button-styles');
        if (styles) {
            styles.remove();
        }
    }
}

// Export for use in other files
window.EmergencyButton = EmergencyButton;