/**
 * Leaflet Map Helper Functions
 * Common map initialization and marker utilities
 */

/**
 * Initialize a basic map
 * @param {string} containerId - ID of the container element
 * @param {number} lat - Initial latitude
 * @param {number} lng - Initial longitude
 * @param {number} zoom - Initial zoom level
 * @returns {L.Map} Leaflet map instance
 */
function initMap(containerId, lat = 42.6026, lng = 20.9030, zoom = 13) {
    const map = L.map(containerId).setView([lat, lng], zoom);
    
    L.tileLayer('https://{s}.tile.openstreetmap.org/{s}/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors',
        maxZoom: 19
    }).addTo(map);
    
    return map;
}

/**
 * Create a colored marker based on status
 * @param {string} status - Report status
 * @returns {L.Icon} Leaflet icon
 */
function getStatusIcon(status) {
    let color = 'gray';
    
    switch(status) {
        case 'Pending':
            color = 'orange';
            break;
        case 'In-Progress':
            color = 'blue';
            break;
        case 'Fixed':
            color = 'green';
            break;
        case 'Rejected':
            color = 'red';
            break;
        default:
            color = 'gray';
    }
    
    return L.divIcon({
        className: 'custom-marker',
        html: `<div style="background-color: ${color}; width: 20px; height: 20px; border-radius: 50%; border: 2px solid white; box-shadow: 0 0 0 2px rgba(0,0,0,0.2);"></div>`,
        iconSize: [20, 20],
        iconAnchor: [10, 10]
    });
}

/**
 * Create a marker with priority indicator
 * @param {number} priority - AI priority (1-5)
 * @param {string} status - Report status
 * @returns {L.Icon} Leaflet icon
 */
function getPriorityIcon(priority, status) {
    let color = 'gray';
    
    switch(status) {
        case 'Pending':
            color = 'orange';
            break;
        case 'In-Progress':
            color = 'blue';
            break;
        case 'Fixed':
            color = 'green';
            break;
        default:
            color = 'gray';
    }
    
    // Add red border for high priority (4-5)
    const borderColor = priority >= 4 ? 'red' : 'transparent';
    const borderWidth = priority >= 4 ? '3px' : '2px';
    
    return L.divIcon({
        className: 'custom-marker',
        html: `<div style="background-color: ${color}; width: 24px; height: 24px; border-radius: 50%; border: ${borderWidth} solid white; box-shadow: 0 0 0 2px ${borderColor};"></div>`,
        iconSize: [24, 24],
        iconAnchor: [12, 12]
    });
}

/**
 * Format popup content for a report
 * @param {Object} report - Report data object
 * @returns {string} HTML content for popup
 */
function formatReportPopup(report) {
    const imageTag = report.image ? 
        `<img src="data:image/jpeg;base64,${report.image}" style="max-width: 200px; max-height: 150px;" class="img-thumbnail mb-2">` : '';
    
    return `
        <div style="min-width: 250px;">
            <h6>${escapeHtml(report.title)}</h6>
            ${imageTag}
            <p class="mb-1"><small>${escapeHtml(report.description?.substring(0, 100) || '')}...</small></p>
            <p class="mb-1"><strong>Status:</strong> ${escapeHtml(report.status_name || 'Unknown')}</p>
            ${report.priority ? `<p class="mb-1"><strong>Priority:</strong> ${report.priority}/5</p>` : ''}
            <p class="mb-1"><small>Reporter: ${escapeHtml(report.username || 'Anonymous')}</small></p>
            <p class="mb-2"><small>${new Date(report.created_at).toLocaleString()}</small></p>
            <a href="/municipality/report_view.php?id=${report.report_id}" class="btn btn-sm btn-primary">View Details</a>
        </div>
    `;
}

/**
 * Simple HTML escape function
 */
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

/**
 * Calculate bounds from array of coordinates
 * @param {Array} coordinates - Array of [lat, lng] pairs
 * @returns {L.LatLngBounds} Leaflet bounds object
 */
function calculateBounds(coordinates) {
    if (coordinates.length === 0) return null;
    
    let minLat = coordinates[0][0];
    let maxLat = coordinates[0][0];
    let minLng = coordinates[0][1];
    let maxLng = coordinates[0][1];
    
    coordinates.forEach(([lat, lng]) => {
        minLat = Math.min(minLat, lat);
        maxLat = Math.max(maxLat, lat);
        minLng = Math.min(minLng, lng);
        maxLng = Math.max(maxLng, lng);
    });
    
    return L.latLngBounds([[minLat, minLng], [maxLat, maxLng]]);
}

