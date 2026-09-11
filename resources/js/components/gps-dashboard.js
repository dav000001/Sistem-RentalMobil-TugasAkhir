/**
 * GPS Dashboard Component
 * Displays GPS tracking status, late return predictions, and routing information
 */

class GPSDashboard {
    constructor(containerId, options = {}) {
        this.container = document.getElementById(containerId);
        if (!this.container) {
            throw new Error(`Container element with ID '${containerId}' not found`);
        }

        this.options = {
            bookingId: null,
            showMap: true,
            showRouting: true,
            autoRefresh: true,
            refreshInterval: 30000, // 30 seconds
            ...options
        };

        this.gpsTracker = window.gpsTracker || new GPSTrackingService();
        this.refreshTimer = null;
        this.isTracking = false;
        this.lastPosition = null;
        this.trackingStats = null;

        this.init();
    }

    /**
     * Initialize the dashboard
     */
    init() {
        this.render();
        this.attachEventListeners();
        this.setupGPSCallbacks();

        if (this.options.autoRefresh) {
            this.startAutoRefresh();
        }

        // Initial data load
        this.loadTrackingData();
    }

    /**
     * Render the dashboard HTML
     */
    render() {
        this.container.innerHTML = `
            <div class="gps-dashboard">
                <!-- Header -->
                <div class="gps-dashboard-header">
                    <h3>📍 GPS Tracking & Navigation</h3>
                    <div class="gps-controls">
                        <button id="toggle-tracking" class="btn btn-toggle" disabled>
                            <span class="btn-icon">📍</span>
                            <span class="btn-text">Enable Tracking</span>
                            <span class="btn-loading" style="display: none;">
                                <span class="spinner"></span>
                            </span>
                        </button>
                        <button id="refresh-data" class="btn btn-secondary">
                            <span class="btn-icon">🔄</span>
                            Refresh
                        </button>
                    </div>
                </div>

                <!-- Status Cards -->
                <div class="status-cards">
                    <div class="status-card" id="tracking-status-card">
                        <div class="status-icon">📍</div>
                        <div class="status-content">
                            <div class="status-title">Tracking Status</div>
                            <div class="status-value" id="tracking-status">Checking...</div>
                        </div>
                    </div>
                    
                    <div class="status-card" id="gps-accuracy-card">
                        <div class="status-icon">🎯</div>
                        <div class="status-content">
                            <div class="status-title">GPS Accuracy</div>
                            <div class="status-value" id="gps-accuracy">--</div>
                        </div>
                    </div>
                    
                    <div class="status-card" id="late-prediction-card">
                        <div class="status-icon">⏰</div>
                        <div class="status-content">
                            <div class="status-title">Late Prediction</div>
                            <div class="status-value" id="late-prediction">Analyzing...</div>
                        </div>
                    </div>
                </div>

                <!-- Current Location -->
                <div class="location-section">
                    <h4>📍 Current Location</h4>
                    <div class="location-info" id="location-info">
                        <p class="location-placeholder">Enable GPS tracking to see your current location</p>
                    </div>
                </div>`
        );
    }
                < !--Route Recommendations-- >
                <div class="routing-section" id="routing-section" style="display: none;">
                    <h4>🗺️ Route Recommendations</h4>
                    <div class="route-recommendations" id="route-recommendations">
                        <p class="route-placeholder">Getting route recommendations...</p>
                    </div>
                </div>

                <!--Tracking Statistics-- >
                <div class="stats-section" id="stats-section" style="display: none;">
                    <h4>📊 Tracking Statistics</h4>
                    <div class="tracking-stats" id="tracking-stats">
                        <div class="stats-grid">
                            <div class="stat-item">
                                <div class="stat-label">Duration</div>
                                <div class="stat-value" id="stat-duration">--</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-label">Distance</div>
                                <div class="stat-value" id="stat-distance">--</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-label">Points</div>
                                <div class="stat-value" id="stat-points">--</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-label">Last Update</div>
                                <div class="stat-value" id="stat-last-update">--</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!--Insights -->
    <div class="insights-section" id="insights-section" style="display: none;">
        <h4>💡 Insights & Recommendations</h4>
        <div class="insights-list" id="insights-list">
            <p class="insights-placeholder">No insights available</p>
        </div>
    </div>
            </div >
    `;

        this.addStyles();
    }

    /**
     * Add CSS styles for the dashboard
     */
    addStyles() {
        if (document.getElementById('gps-dashboard-styles')) return;

        const styles = document.createElement('style');
        styles.id = 'gps-dashboard-styles';
        styles.textContent = `
        .gps - dashboard {
    max - width: 800px;
    margin: 0 auto;
    padding: 20px;
    font - family: -apple - system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans - serif;
}
            
            .gps - dashboard - header {
    display: flex;
    align - items: center;
    justify - content: space - between;
    margin - bottom: 24px;
    padding - bottom: 16px;
    border - bottom: 1px solid #e5e7eb;
}
            
            .gps - dashboard - header h3 {
    margin: 0;
    color: #374151;
    font - size: 20px;
    font - weight: 600;
}
            
            .gps - controls {
    display: flex;
    gap: 8px;
}
            
            .btn {
    display: flex;
    align - items: center;
    gap: 6px;
    padding: 8px 16px;
    border: none;
    border - radius: 6px;
    cursor: pointer;
    font - size: 14px;
    font - weight: 500;
    transition: all 0.2s ease;
    text - decoration: none;
}
            
            .btn - toggle {
    background: #10b981;
    color: white;
}
            
            .btn - toggle: hover: not(: disabled) {
    background: #059669;
}
            
            .btn - toggle:disabled {
    background: #9ca3af;
    cursor: not - allowed;
}
            
            .btn - toggle.tracking {
    background: #dc2626;
}
            
            .btn - toggle.tracking:hover {
    background: #b91c1c;
}
            
            .btn - secondary {
    background: #f3f4f6;
    color: #374151;
}
            
            .btn - secondary:hover {
    background: #e5e7eb;
}
            
            .status - cards {
    display: grid;
    grid - template - columns: repeat(auto - fit, minmax(200px, 1fr));
    gap: 16px;
    margin - bottom: 24px;
} `
        );
        
        document.head.appendChild(styles);
    }
            
            .status-card {
                background: white;
                border: 1px solid #e5e7eb;
                border-radius: 8px;
                padding: 16px;
                display: flex;
                align-items: center;
                gap: 12px;
                box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
                transition: box-shadow 0.2s ease;
            }
            
            .status-card:hover {
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            }
            
            .status-icon {
                font-size: 24px;
                width: 40px;
                height: 40px;
                display: flex;
                align-items: center;
                justify-content: center;
                border-radius: 50%;
                background: #f3f4f6;
            }
            
            .status-content {
                flex: 1;
            }
            
            .status-title {
                font-size: 12px;
                font-weight: 500;
                color: #6b7280;
                text-transform: uppercase;
                margin-bottom: 4px;
            }
            
            .status-value {
                font-size: 16px;
                font-weight: 600;
                color: #374151;
            }
            
            .location-section,
            .routing-section,
            .stats-section,
            .insights-section {
                background: white;
                border: 1px solid #e5e7eb;
                border-radius: 8px;
                padding: 20px;
                margin-bottom: 16px;
            }
            
            .location-section h4,
            .routing-section h4,
            .stats-section h4,
            .insights-section h4 {
                margin: 0 0 16px 0;
                color: #374151;
                font-size: 16px;
                font-weight: 600;
            }
            
            .location-info {
                background: #f9fafb;
                border: 1px dashed #d1d5db;
                border-radius: 6px;
                padding: 16px;
                text-align: center;
            }
            
            .location-placeholder,
            .route-placeholder,
            .insights-placeholder {
                color: #6b7280;
                font-style: italic;
                margin: 0;
            }
            
            .location-details {
                text-align: left;
            }
            
            .location-address {
                font-weight: 600;
                color: #374151;
                margin-bottom: 8px;
            }
            
            .location-coordinates {
                font-size: 14px;
                color: #6b7280;
                margin-bottom: 8px;
            }
            
            .location-accuracy {
                font-size: 12px;
                color: #059669;
            }
            
            .stats-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
                gap: 16px;
            }
            
            .stat-item {
                text-align: center;
                padding: 12px;
                background: #f9fafb;
                border-radius: 6px;
            }
            
            .stat-label {
                font-size: 12px;
                font-weight: 500;
                color: #6b7280;
                text-transform: uppercase;
                margin-bottom: 4px;
            }
            
            .stat-value {
                font-size: 18px;
                font-weight: 600;
                color: #374151;
            }`
        );

document.head.appendChild(styles);
    }

/**
 * Attach event listeners
 */
attachEventListeners() {
    const toggleBtn = this.container.querySelector('#toggle-tracking');
    const refreshBtn = this.container.querySelector('#refresh-data');

    toggleBtn.addEventListener('click', () => {
        this.handleToggleTracking();
    });

    refreshBtn.addEventListener('click', () => {
        this.loadTrackingData();
    });
}