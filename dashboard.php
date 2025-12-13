<?php require 'db.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aegis Command Center</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        body { font-family: 'Inter', sans-serif; }
        /* Hide scrollbar for the feed */
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
        
        /* Glassmorphism effect for panels */
        .glass-panel {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(229, 231, 235, 0.5);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }
    </style>
</head>
<body class="h-screen w-full overflow-hidden bg-gray-100 relative">

    <div id="map" class="absolute inset-0 z-0"></div>

    <div class="absolute top-6 left-6 z-10 flex flex-col gap-4 max-w-xs w-full">
        <div class="glass-panel p-5 rounded-2xl">
            <div class="flex items-center gap-3 mb-1">
                <div class="w-3 h-3 rounded-full bg-red-500 animate-pulse"></div>
                <h1 class="text-xl font-bold text-gray-800">Live Incidents</h1>
            </div>
            <p class="text-xs text-gray-500 font-medium tracking-wide uppercase">Real-time emergency monitoring</p>
        </div>

        <div class="flex gap-3">
            <div class="glass-panel flex-1 p-4 rounded-2xl text-center">
                <p class="text-xs text-gray-500 font-bold uppercase mb-1">Active</p>
                <p class="text-3xl font-bold text-gray-800" id="total-count">0</p>
            </div>
            <div class="glass-panel flex-1 p-4 rounded-2xl text-center border-l-4 border-red-500">
                <p class="text-xs text-gray-500 font-bold uppercase mb-1">High Severity</p>
                <p class="text-3xl font-bold text-red-600" id="crit-count">0</p>
            </div>
        </div>
    </div>

    <div class="absolute top-6 right-6 z-10 flex flex-col gap-3 items-end">
        <a href="app.php" target="_blank" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-xl font-bold shadow-lg flex items-center gap-2 transition-transform transform active:scale-95">
            <span class="text-xl">+</span> New Report
        </a>
        
        <div class="glass-panel p-2 rounded-xl flex flex-col gap-2">
            <button onclick="map.setView([20,0], 2)" class="p-2 hover:bg-gray-100 rounded-lg text-gray-600" title="Global View">
                🌍
            </button>
            <button onclick="location.reload()" class="p-2 hover:bg-gray-100 rounded-lg text-gray-600" title="Refresh Data">
                🔄
            </button>
        </div>
    </div>

    <div class="absolute bottom-6 right-6 z-10 w-80">
        <div class="glass-panel rounded-2xl overflow-hidden flex flex-col max-h-[500px]">
            <div class="p-4 border-b border-gray-100 flex justify-between items-center bg-white">
                <h3 class="font-bold text-gray-800">Recent Reports</h3>
                <span class="bg-green-100 text-green-700 text-[10px] font-bold px-2 py-1 rounded-full uppercase tracking-wide">Live Feed</span>
            </div>
            
            <div id="feed-list" class="overflow-y-auto p-2 space-y-2 no-scrollbar bg-gray-50/50">
                <div class="p-4 text-center text-gray-400 text-sm">Waiting for data...</div>
            </div>
        </div>
    </div>

    <script>
        // 1. Initialize Map (Using "CartoDB Positron" for the clean white look)
        const map = L.map('map', { zoomControl: false }).setView([20, 0], 2);
        
        L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors &copy; <a href="https://carto.com/attributions">CARTO</a>',
            subdomains: 'abcd',
            maxZoom: 20
        }).addTo(map);

        // Add Zoom Control to Bottom Left (out of the way)
        L.control.zoom({ position: 'bottomleft' }).addTo(map);

        // 2. Custom Icons
        const redIcon = new L.Icon({
            iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-red.png',
            shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
            iconSize: [25, 41], iconAnchor: [12, 41], popupAnchor: [1, -34], shadowSize: [41, 41]
        });

        const blueIcon = new L.Icon({
            iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-blue.png',
            shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
            iconSize: [25, 41], iconAnchor: [12, 41], popupAnchor: [1, -34], shadowSize: [41, 41]
        });

        let firstLoad = true;
        let lastUpdateCount = 0;

        // 3. Main Update Loop
        async function updateDashboard() {
            try {
                const res = await fetch('fetch.php');
                const data = await res.json();
                
                // Only update if data changed (optional optimization)
                // For now, we rebuild to ensure sync
                
                // A. Clear Map
                map.eachLayer((layer) => {
                    if (!!layer.toGeoJSON) map.removeLayer(layer);
                });

                const markers = L.featureGroup();
                let critical = 0;
                const feedList = document.getElementById('feed-list');
                let feedHTML = '';

                data.forEach((inc, index) => {
                    // Update Stats
                    if(inc.severity >= 4) critical++;

                    // --- 1. MAP MARKER ---
                    let iconToUse = (inc.severity >= 4) ? redIcon : blueIcon;
                    
                    let imageHtml = '';
                    if (inc.image_data && inc.image_data.length > 100) {
                        imageHtml = `<div class="mt-2"><img src="${inc.image_data}" class="w-full h-32 object-cover rounded-lg border border-gray-200"></div>`;
                    }

                    const marker = L.marker([inc.latitude, inc.longitude], {icon: iconToUse});
                    marker.bindPopup(`
                        <div class="text-center min-w-[200px] font-sans">
                            <strong class="text-sm uppercase tracking-wide text-gray-500">${inc.incident_type}</strong><br>
                            <div class="text-lg font-bold ${inc.severity >= 4 ? 'text-red-600' : 'text-blue-600'}">
                                Severity Level ${inc.severity}
                            </div>
                            <div class="text-xs text-gray-400 mb-2">${inc.reported_at}</div>
                            ${imageHtml}
                        </div>
                    `);
                    marker.addTo(markers);

                    // --- 2. FEED ITEM (Limit to top 20) ---
                    if (index < 20) {
                        feedHTML += `
                            <div class="bg-white p-3 rounded-xl border border-gray-100 shadow-sm hover:shadow-md transition-shadow cursor-pointer" 
                                 onclick="flyTo(${inc.latitude}, ${inc.longitude})">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <p class="font-bold text-gray-800 text-sm">${inc.incident_type}</p>
                                        <p class="text-xs text-gray-500">${inc.reported_at.substring(11, 16)} • ID: #${inc.client_uuid.substring(0,4)}</p>
                                    </div>
                                    <span class="${inc.severity >= 4 ? 'bg-red-100 text-red-700' : 'bg-blue-100 text-blue-700'} text-[10px] font-bold px-2 py-1 rounded">
                                        LVL ${inc.severity}
                                    </span>
                                </div>
                            </div>
                        `;
                    }
                });

                // Render Map Objects
                markers.addTo(map);

                // Render Feed
                if(data.length > 0) feedList.innerHTML = feedHTML;
                else feedList.innerHTML = '<div class="p-4 text-center text-gray-400 text-sm">No active incidents</div>';

                // Render Stats
                document.getElementById('total-count').innerText = data.length;
                document.getElementById('crit-count').innerText = critical;

                // Auto Zoom (First load only)
                if (firstLoad && data.length > 0) {
                    map.fitBounds(markers.getBounds(), { padding: [50, 50] });
                    firstLoad = false;
                }

            } catch(e) { console.error("Poll Error:", e); }
        }

        // Helper: Click feed item to zoom to pin
        function flyTo(lat, lng) {
            map.flyTo([lat, lng], 15, { duration: 1.5 });
        }

        // Start Loop
        setInterval(updateDashboard, 3000);
        updateDashboard();
    </script>
</body>
</html>