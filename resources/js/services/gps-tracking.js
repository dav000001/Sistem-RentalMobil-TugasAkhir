/**
 * GPS Tracking Service
 * Handles live GPS tracking, validation, and emergency reporting
 */

class GPSTrackingService {
    constructor() {
        this.watchId = null;
        this.isTracking = false;
        this.lastKnownPosition = null;
        this.trackingInterval = null;
        this.bookingId = null;
        this.config = {
            trackingIntervalMs: 60000, // 1 minute
            highAccuracyTimeout: 15000, // 15 seconds
            maximumAge: 30000, // 30 seconds
            enableHighAccuracy: true
        };
        this.callbacks = {
            onPositionUpdate: null,
            onTrackingError: null,
            onTrackingStatusChange: null
        };
    }

    /**
     * Initialize GPS tracking for a booking
     */
    async initializeTracking(bookingId, options = {}) {
        this.bookingId = bookingId;
        this.config = { ...this.config, ...options };

        // Check if geolocation is supported
        if (!navigator.geolocation) {
            throw new Error('Geolocation is not supported by this browser');
        }

        // Check if tracking is enabled for this booking
        try {
            const response = await fetch(`/api/v1/bookings/${bookingId}/tracking`, {
                headers: {
                    'Authorization': `Bearer ${this.getAuthToken()}`,
                    'Accept': 'application/json'
                }
            });

            const data = await response.json();
            if (!data.success) {
                throw new Error(data.error || 'Failed to check tracking status');
            }

            return data.data.is_being_tracked;
        } catch (error) {
            console.error('Failed to initialize GPS tracking:', error);
            throw error;
        }
    }

    /**
     * Start GPS tracking
     */
    async startTracking() {
        if (this.isTracking) {
            console.warn('GPS tracking is already active');
            return;
        }

        try {
            // Request initial position to test GPS availability
            await this.getCurrentPosition();

            // Start watch position
            this.watchId = navigator.geolocation.watchPosition(
                (position) => this.handlePositionUpdate(position),
                (error) => this.handlePositionError(error),
                {
                    enableHighAccuracy: this.config.enableHighAccuracy,
                    timeout: this.config.highAccuracyTimeout,
                    maximumAge: this.config.maximumAge
                }
            );

            // Set up periodic tracking
            this.trackingInterval = setInterval(() => {
                if (this.lastKnownPosition) {
                    this.sendTrackingPoint(this.lastKnownPosition);
                }
            }, this.config.trackingIntervalMs);

            this.isTracking = true;
            this.notifyTrackingStatusChange(true);

            console.log('GPS tracking started successfully');
            return true;

        } catch (error) {
            console.error('Failed to start GPS tracking:', error);
            this.handleTrackingError(error);
            throw error;
        }
    }

    /**
     * Stop GPS tracking
     */
    stopTracking() {
        if (!this.isTracking) {
            console.warn('GPS tracking is not active');
            return;
        }

        // Clear watch position
        if (this.watchId) {
            navigator.geolocation.clearWatch(this.watchId);
            this.watchId = null;
        }

        // Clear tracking interval
        if (this.trackingInterval) {
            clearInterval(this.trackingInterval);
            this.trackingInterval = null;
        }

        this.isTracking = false;
        this.lastKnownPosition = null;
        this.notifyTrackingStatusChange(false);

        console.log('GPS tracking stopped');
    }

    /**
     * Toggle tracking on/off
     */
    async toggleTracking(enabled) {
        try {
            const response = await fetch(`/api/v1/bookings/${this.bookingId}/tracking/toggle`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${this.getAuthToken()}`,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ enabled })
            });

            const data = await response.json();
            if (!data.success) {
                throw new Error(data.error || 'Failed to toggle tracking');
            }

            if (enabled) {
                await this.startTracking();
            } else {
                this.stopTracking();
            }

            return data.data;

        } catch (error) {
            console.error('Failed to toggle tracking:', error);
            throw error;
        }
    }

    /**
     * Get current position (one-time)
     */
    getCurrentPosition() {
        return new Promise((resolve, reject) => {
            navigator.geolocation.getCurrentPosition(
                (position) => resolve(position),
                (error) => reject(error),
                {
                    enableHighAccuracy: this.config.enableHighAccuracy,
                    timeout: this.config.highAccuracyTimeout,
                    maximumAge: this.config.maximumAge
                }
            );
        });
    }

    /**
     * Handle position updates from GPS
     */
    async handlePositionUpdate(position) {
        this.lastKnownPosition = position;

        // Prepare GPS data
        const gpsData = {
            latitude: position.coords.latitude,
            longitude: position.coords.longitude,
            accuracy: position.coords.accuracy,
            heading: position.coords.heading,
            speed: position.coords.speed ? position.coords.speed * 3.6 : null, // Convert m/s to km/h
            altitude: position.coords.altitude
        };

        // Try to get address (optional)
        try {
            gpsData.address = await this.reverseGeocode(gpsData.latitude, gpsData.longitude);
        } catch (error) {
            console.warn('Failed to get address for GPS point:', error);
        }

        // Send to server
        try {
            await this.sendTrackingPoint(gpsData);
        } catch (error) {
            console.error('Failed to send tracking point:', error);
            this.handleTrackingError(error);
        }

        // Notify callback
        if (this.callbacks.onPositionUpdate) {
            this.callbacks.onPositionUpdate(gpsData);
        }
    }

    /**
     * Send GPS tracking point to server
     */
    async sendTrackingPoint(gpsData) {
        if (!this.bookingId) {
            throw new Error('No booking ID set for tracking');
        }

        const response = await fetch(`/api/v1/bookings/${this.bookingId}/tracking/record`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${this.getAuthToken()}`,
                'Accept': 'application/json'
            },
            body: JSON.stringify(gpsData)
        });

        const data = await response.json();
        if (!data.success) {
            throw new Error(data.error || 'Failed to record tracking point');
        }

        return data.data;
    }

    /**
     * Handle GPS position errors
     */
    handlePositionError(error) {
        let errorMessage = 'GPS error occurred';

        switch (error.code) {
            case error.PERMISSION_DENIED:
                errorMessage = 'GPS permission denied by user';
                break;
            case error.POSITION_UNAVAILABLE:
                errorMessage = 'GPS position information unavailable';
                break;
            case error.TIMEOUT:
                errorMessage = 'GPS position request timed out';
                break;
        }

        console.error('GPS Position Error:', errorMessage, error);
        this.handleTrackingError(new Error(errorMessage));
    }

    /**
     * Handle tracking errors
     */
    handleTrackingError(error) {
        if (this.callbacks.onTrackingError) {
            this.callbacks.onTrackingError(error);
        }

        // Show user-friendly error message
        this.showNotification('GPS Tracking Error', error.message, 'error');
    }

    /**
     * Notify tracking status change
     */
    notifyTrackingStatusChange(isActive) {
        if (this.callbacks.onTrackingStatusChange) {
            this.callbacks.onTrackingStatusChange(isActive);
        }
    }

    /**
     * Create emergency report
     */
    async createEmergencyReport(emergencyType, description = '') {
        try {
            // Get current position
            const position = await this.getCurrentPosition();

            const emergencyData = {
                type: emergencyType,
                latitude: position.coords.latitude,
                longitude: position.coords.longitude,
                description: description,
                urgency_level: this.getUrgencyLevel(emergencyType)
            };

            const response = await fetch(`/api/v1/bookings/${this.bookingId}/emergency`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${this.getAuthToken()}`,
                    'Accept': 'application/json'
                },
                body: JSON.stringify(emergencyData)
            });

            const data = await response.json();
            if (!data.success) {
                throw new Error(data.error || 'Failed to create emergency report');
            }

            // Show success message with emergency code
            this.showNotification(
                'Emergency Report Created',
                `Emergency code: ${data.data.emergency_code}. Help is on the way!`,
                'success'
            );

            return data.data;

        } catch (error) {
            console.error('Failed to create emergency report:', error);
            this.showNotification('Emergency Report Failed', error.message, 'error');
            throw error;
        }
    }

    /**
     * Get urgency level for emergency type
     */
    getUrgencyLevel(emergencyType) {
        switch (emergencyType) {
            case 'medical':
            case 'accident':
                return 'critical';
            case 'theft':
            case 'harassment':
                return 'high';
            case 'breakdown':
                return 'medium';
            default:
                return 'low';
        }
    }

    /**
     * Get route recommendations
     */
    async getRouteRecommendations() {
        try {
            const position = await this.getCurrentPosition();

            const response = await fetch(`/api/v1/bookings/${this.bookingId}/routing/recommendations`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${this.getAuthToken()}`,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    current_latitude: position.coords.latitude,
                    current_longitude: position.coords.longitude
                })
            });

            const data = await response.json();
            if (!data.success) {
                throw new Error(data.error || 'Failed to get route recommendations');
            }

            return data.data;

        } catch (error) {
            console.error('Failed to get route recommendations:', error);
            throw error;
        }
    }

    /**
     * Reverse geocode coordinates to address
     */
    async reverseGeocode(latitude, longitude) {
        try {
            // Using OpenStreetMap Nominatim (free alternative to Google)
            const response = await fetch(
                `https://nominatim.openstreetmap.org/reverse?format=json&lat=${latitude}&lon=${longitude}&zoom=18&addressdetails=1&accept-language=id,en`,
                {
                    headers: {
                        'User-Agent': 'RentalMobil-GPS-Service/1.0'
                    }
                }
            );

            if (!response.ok) {
                throw new Error('Geocoding service unavailable');
            }

            const data = await response.json();
            return data.display_name || 'Address not found';

        } catch (error) {
            console.warn('Reverse geocoding failed:', error);
            return null;
        }
    }

    /**
     * Set event callbacks
     */
    setCallbacks(callbacks) {
        this.callbacks = { ...this.callbacks, ...callbacks };
    }

    /**
     * Get authentication token from localStorage or meta tag
     */
    getAuthToken() {
        // Try to get from localStorage first
        const token = localStorage.getItem('auth_token') ||
            sessionStorage.getItem('auth_token');

        if (token) return token;

        // Fall back to meta tag (for Laravel Sanctum SPA)
        const metaToken = document.querySelector('meta[name="csrf-token"]');
        return metaToken ? metaToken.getAttribute('content') : '';
    }

    /**
     * Show notification to user
     */
    showNotification(title, message, type = 'info') {
        // Check if browser supports notifications
        if ('Notification' in window && Notification.permission === 'granted') {
            new Notification(title, {
                body: message,
                icon: '/favicon.ico'
            });
        }

        // Also log to console and trigger custom event for UI handling
        console.log(`${type.toUpperCase()}: ${title} - ${message}`);

        // Dispatch custom event for UI components to handle
        window.dispatchEvent(new CustomEvent('gps-notification', {
            detail: { title, message, type }
        }));
    }

    /**
     * Request notification permissions
     */
    async requestNotificationPermission() {
        if ('Notification' in window && Notification.permission === 'default') {
            const permission = await Notification.requestPermission();
            return permission === 'granted';
        }
        return Notification.permission === 'granted';
    }

    /**
     * Get tracking statistics
     */
    async getTrackingStats() {
        try {
            const response = await fetch(`/api/v1/bookings/${this.bookingId}/tracking`, {
                headers: {
                    'Authorization': `Bearer ${this.getAuthToken()}`,
                    'Accept': 'application/json'
                }
            });

            const data = await response.json();
            if (!data.success) {
                throw new Error(data.error || 'Failed to get tracking stats');
            }

            return data.data;

        } catch (error) {
            console.error('Failed to get tracking stats:', error);
            throw error;
        }
    }

    /**
     * Clean up resources
     */
    destroy() {
        this.stopTracking();
        this.callbacks = {};
        this.bookingId = null;
        this.lastKnownPosition = null;
    }
}

// Export for use in other files
window.GPSTrackingService = GPSTrackingService;

// Auto-initialize if booking data is available
document.addEventListener('DOMContentLoaded', () => {
    const bookingElement = document.querySelector('[data-booking-id]');
    if (bookingElement) {
        const bookingId = bookingElement.getAttribute('data-booking-id');
        window.gpsTracker = new GPSTrackingService();

        // Initialize but don't start tracking automatically
        window.gpsTracker.initializeTracking(bookingId).catch(error => {
            console.warn('GPS tracking initialization failed:', error);
        });
    }
});