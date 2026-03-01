let map;
let markersLayer; // Holds the pins
let heatLayer;    // Holds the heatmap
let isHeatmapActive = false;
let currentLang = 'en';

document.addEventListener("DOMContentLoaded", () => {
    initMap();
    loadReports();
});

function initMap() {
    map = L.map('map').setView([-1.9441, 30.0619], 13);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '© OpenStreetMap' }).addTo(map);
    
    markersLayer = L.layerGroup().addTo(map); // Add markers layer by default
}

function startReport() {
    document.getElementById('reportModal').style.display = 'flex';
    const center = map.getCenter();
    document.getElementById('lat').value = center.lat;
    document.getElementById('lng').value = center.lng;
}

function selectCat(cat, element) {
    document.getElementById('category').value = cat;
    document.querySelectorAll('.cat-option').forEach(el => el.classList.remove('selected'));
    element.classList.add('selected');
}

document.getElementById('reportForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    if (!document.getElementById('category').value) {
        alert(currentLang === 'en' ? "⚠️ Please select a Category!" : "⚠️ Hitamo ubwoko bw'ikibazo!");
        return; 
    }

    const formData = new FormData(e.target);
    const btn = document.getElementById('btn-submit-form');
    btn.innerText = currentLang === 'en' ? "Uploading..." : "Birimo...";
    btn.disabled = true;

    try {
        const res = await fetch('api/reports.php', { method: 'POST', body: formData });
        const data = await res.json();
        if (data.success) {
            alert(currentLang === 'en' ? "Report Submitted! 🚀" : "Byoherejwe neza! 🚀");
            closeModal('reportModal');
            loadReports(); 
            e.target.reset(); 
            document.querySelectorAll('.cat-option').forEach(el => el.classList.remove('selected'));
        }
    } catch (err) { alert("Error connecting to server."); }
    
    btn.innerText = currentLang === 'en' ? "SUBMIT REPORT" : "OHEREZA IKIBAZO";
    btn.disabled = false;
});

async function loadReports() {
    const res = await fetch('api/reports.php');
    const reports = await res.json();

    // Clear old data
    markersLayer.clearLayers();
    if (heatLayer) map.removeLayer(heatLayer);

    const heatData = []; // Array for heatmap points

    reports.forEach(report => {
        // 1. Setup Heatmap Data (Intensity based on upvotes)
        if (report.status === 'open') {
            const intensity = 0.5 + (report.upvotes * 0.1); // More votes = hotter!
            heatData.push([report.lat, report.lng, intensity]);
        }

        // 2. Setup Pins
        let iconLabel = '⚠️'; 
        if (report.category === 'pothole') iconLabel = '🚧';
        if (report.category === 'trash') iconLabel = '🗑️';
        if (report.category === 'streetlight') iconLabel = '💡';
        if (report.category === 'accident') iconLabel = '🚑';
        if (report.category === 'water') iconLabel = '💧';
        if (report.category === 'vandalism') iconLabel = '🎨';
        if (report.category === 'parking') iconLabel = '🚗';
        if (report.category === 'tree') iconLabel = '🌳';
        if (report.status === 'resolved') iconLabel = '✅';

        const customIcon = L.divIcon({
            className: 'custom-map-icon', 
            html: `<div style="font-size: 28px; text-shadow: 0 2px 5px rgba(0,0,0,0.3);">${iconLabel}</div>`,
            iconSize: [30, 30], iconAnchor: [15, 15] 
        });

        const marker = L.marker([report.lat, report.lng], { icon: customIcon });
        
        // Popup Content (Includes Upvote Button!)
        const catText = report.category ? report.category.toUpperCase() : 'UNKNOWN';
        let popupContent = `<div style="font-family: 'Roboto', sans-serif;"><b>${catText}</b><br><span style="color:#64748b;">${report.description}</span>`;
        if (report.image_path) popupContent += `<br><img src="${report.image_path}" class="popup-img">`;
        
        let statusColor = report.status === 'resolved' ? '#10b981' : '#ef4444';
        popupContent += `<br><div style="margin-top:5px; color:${statusColor}; font-weight:bold; font-size:0.8rem;">STATUS: ${report.status.toUpperCase()}</div>`;
        
        // UPVOTE BUTTON (Only if still open)
        if (report.status === 'open') {
            popupContent += `<button class="upvote-btn" onclick="upvoteIssue(${report.id})">👍 Me Too! (${report.upvotes} Votes)</button>`;
        }
        
        popupContent += `</div>`;
        marker.bindPopup(popupContent);
        markersLayer.addLayer(marker);
    });

    // Create Heatmap Layer
    heatLayer = L.heatLayer(heatData, { radius: 25, blur: 15, maxZoom: 15 });
    
    // Show correct layer based on toggle
    if (isHeatmapActive) {
        map.removeLayer(markersLayer);
        heatLayer.addTo(map);
    }
}

// UPVOTE FUNCTION
async function upvoteIssue(id) {
    await fetch('api/reports.php', {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'upvote', id: id })
    });
    loadReports(); // Refresh map to show new vote count!
}

// TOGGLE HEATMAP
function toggleHeatmap() {
    const btn = document.getElementById('btn-heatmap');
    isHeatmapActive = !isHeatmapActive;
    
    if (isHeatmapActive) {
        map.removeLayer(markersLayer);
        heatLayer.addTo(map);
        btn.innerText = "📍 Show Pins";
        btn.classList.add('active');
    } else {
        map.removeLayer(heatLayer);
        markersLayer.addTo(map);
        btn.innerText = "🔥 Heatmap Off";
        btn.classList.remove('active');
    }
}

// TOGGLE LANGUAGE (English / Kinyarwanda)
function toggleLanguage() {
    currentLang = currentLang === 'en' ? 'rw' : 'en';
    const btn = document.getElementById('btn-lang');
    
    if (currentLang === 'rw') {
        btn.innerText = "🇬🇧 English";
        document.getElementById('txt-live').innerText = "IKURIKIRANA";
        document.getElementById('btn-main-report').innerText = "+ TANGA IKIBAZO";
        document.getElementById('txt-modal-title').innerText = "Tanga Ikibazo Gishya";
        document.getElementById('txt-label-type').innerText = "Ubwoko bw'Ikibazo";
        document.getElementById('txt-label-desc').innerText = "Sobanura Ikibazo";
        document.getElementById('txt-label-email').innerText = "Imeli (Kugirango umenye niba cyakemutse)";
        document.getElementById('txt-label-photo').innerText = "Ifoto (Ikimenyetso)";
        document.getElementById('btn-submit-form').innerText = "OHEREZA IKIBAZO";
    } else {
        btn.innerText = "🇷🇼 Kinyarwanda";
        document.getElementById('txt-live').innerText = "LIVE MONITORING";
        document.getElementById('btn-main-report').innerText = "+ REPORT ISSUE";
        document.getElementById('txt-modal-title').innerText = "New Incident Report";
        document.getElementById('txt-label-type').innerText = "Incident Type";
        document.getElementById('txt-label-desc').innerText = "Description";
        document.getElementById('txt-label-email').innerText = "Email (To get updates when fixed)";
        document.getElementById('txt-label-photo').innerText = "Evidence (Photo)";
        document.getElementById('btn-submit-form').innerText = "SUBMIT REPORT";
    }

    // Update Category Grid Text
    document.querySelectorAll('.cat-txt').forEach(span => {
        span.innerText = span.getAttribute(`data-${currentLang}`);
    });
}

function closeModal(id) { document.getElementById(id).style.display = 'none'; }