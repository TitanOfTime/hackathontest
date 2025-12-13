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
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
        
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

    <div class="absolute bottom-6 right-6 z-10 w-80 flex flex-col gap-4 max-h-[calc(100vh-50px)] overflow-hidden pointer-events-none">
        
        <div class="glass-panel rounded-2xl overflow-hidden flex flex-col shrink pointer-events-auto max-h-[40vh]">
            <div class="p-4 border-b border-gray-100 flex justify-between items-center bg-white">
                <h3 class="font-bold text-gray-800">Recent Reports</h3>
                <span class="bg-green-100 text-green-700 text-[10px] font-bold px-2 py-1 rounded-full uppercase tracking-wide">Live Feed</span>
            </div>
            <div id="feed-list" class="overflow-y-auto p-2 space-y-2 no-scrollbar bg-gray-50/50">
                <div class="p-4 text-center text-gray-400 text-sm">Waiting for data...</div>
            </div>
        </div>

        <div class="glass-panel rounded-2xl overflow-hidden flex flex-col shrink pointer-events-auto max-h-[30vh]">
            <div class="p-4 border-b border-gray-100 flex justify-between items-center bg-white">
                <h3 class="font-bold text-gray-800">Mission History</h3>
                <span class="bg-gray-100 text-gray-700 text-[10px] font-bold px-2 py-1 rounded-full uppercase tracking-wide">Resolved</span>
            </div>
            <div id="history-list" class="overflow-y-auto p-2 space-y-2 no-scrollbar bg-gray-50/50">
                <div class="p-4 text-center text-gray-400 text-sm">No mission history yet</div>
            </div>
        </div>
    </div>

    <script>
        // 1. Initialize Map
        const map = L.map('map', { zoomControl: false }).setView([20, 0], 2);
        L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
            attribution: '&copy; OpenStreetMap &copy; CARTO',
            subdomains: 'abcd',
            maxZoom: 20
        }).addTo(map);
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

        // Helper: Format Time
        function formatTime(isoString) {
            if (!isoString) return 'Unknown';
            const date = new Date(isoString);
            return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        }

        // --- 3. RESOLVE INCIDENT (Active Function) ---
        async function resolveIncident(id) {
            if(!confirm('Are you sure you want to mark this incident as resolved?')) return;
            
            try {
                // Call the backend script
                const res = await fetch('resolve.php', { 
                    method: 'POST', 
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({id: id}) 
                });
                
                const data = await res.json();
                
                if(data.status === 'success') {
                    // Update Dashboard immediately to remove the pin
                    updateDashboard(); 
                } else {
                    alert("Error: " + (data.message || "Unknown error"));
                }
            } catch(e) {
                console.error(e);
                alert("Network Error: Could not resolve incident.");
            }
        }

        // --- 4. MAIN LOOP ---
        async function updateDashboard() {
            try {
                const res = await fetch('fetch.php');
                const data = await res.json();
                
                // Clear old markers
                map.eachLayer((layer) => { if (!!layer.toGeoJSON) map.removeLayer(layer); });

                const markers = L.featureGroup();
                let critical = 0;
                let activeCount = 0;
                
                let feedHTML = '';
                let historyHTML = '';

                data.forEach((inc) => {
                    // Convert Time
                    const localTime = formatTime(inc.reported_at);
                    const resolvedTime = formatTime(inc.resolved_at);

                    if (inc.status === 'resolved') {
                        // --- RESOLVED ITEM (Add to History Panel) ---
                        historyHTML += `
                            <div class="bg-white p-4 rounded-xl border border-gray-200 shadow-sm opacity-75">
                                <div class="flex justify-between items-start mb-2">
                                    <span class="font-bold text-gray-700 line-through text-sm">${inc.incident_type}</span>
                                    <span class="bg-gray-200 text-gray-600 text-[10px] font-bold px-2 py-1 rounded uppercase">Resolved</span>
                                </div>
                                <p class="text-xs text-gray-400">Rep: ${localTime} • Res: ${resolvedTime}</p>
                            </div>
                        `;
                    } else {
                        // --- ACTIVE ITEM (Add to Map & Feed) ---
                        activeCount++;
                        if(inc.severity >= 4) critical++;

                        let iconToUse = (inc.severity >= 4) ? redIcon : blueIcon;
                        let imageHtml = '';
                        if (inc.image_data && inc.image_data.length > 100) {
                            imageHtml = `<div class="mt-2"><img src="${inc.image_data}" class="w-full h-32 object-cover rounded-lg border border-gray-200"></div>`;
                        }

                        const marker = L.marker([inc.latitude, inc.longitude], {icon: iconToUse});
                        
                        // Map Popup
                        marker.bindPopup(`
                            <div class="text-center min-w-[200px] font-sans">
                                <strong class="text-sm uppercase tracking-wide text-gray-500">${inc.incident_type}</strong><br>
                                <div class="text-lg font-bold ${inc.severity >= 4 ? 'text-red-600' : 'text-blue-600'}">
                                    Severity Level ${inc.severity}
                                </div>
                                <div class="text-xs text-gray-500 font-bold mt-1 mb-2">
                                    🕒 ${localTime}
                                </div>
                                ${imageHtml}
                                <button onclick="resolveIncident(${inc.id})" class="mt-3 w-full bg-green-500 hover:bg-green-600 text-white font-bold py-2 px-4 rounded-lg shadow transition-colors text-sm">
                                    ✅ Mark Resolved
                                </button>
                            </div>
                        `);
                        marker.addTo(markers);

                        // Feed Item
                        if (activeCount <= 20) {
                            feedHTML += `
                                <div class="bg-white p-3 rounded-xl border border-gray-100 shadow-sm hover:shadow-md transition-shadow cursor-pointer" onclick="map.flyTo([${inc.latitude}, ${inc.longitude}], 15)">
                                    <div class="flex justify-between items-start">
                                        <div>
                                            <p class="font-bold text-gray-800 text-sm">${inc.incident_type}</p>
                                            <p class="text-xs text-gray-500">🕒 ${localTime} • ID: #${inc.id}</p>
                                        </div>
                                        <span class="${inc.severity >= 4 ? 'bg-red-100 text-red-700' : 'bg-blue-100 text-blue-700'} text-[10px] font-bold px-2 py-1 rounded">LVL ${inc.severity}</span>
                                    </div>
                                </div>
                            `;
                        }
                    }
                });
                
                // Add Markers to Map
                markers.addTo(map);
                
                // Update Lists
                document.getElementById('feed-list').innerHTML = feedHTML || '<div class="p-4 text-center text-gray-400 text-sm">No active incidents</div>';
                
                const historyEl = document.getElementById('history-list');
                if (historyEl) historyEl.innerHTML = historyHTML || '<div class="p-4 text-center text-gray-400 text-sm">No mission history yet</div>';

                // Update Counts
                document.getElementById('total-count').innerText = activeCount;
                document.getElementById('crit-count').innerText = critical;

                // Auto-Zoom (First load only)
                if (firstLoad && activeCount > 0) {
                    map.fitBounds(markers.getBounds(), { padding: [50, 50] });
                    firstLoad = false;
                }

            } catch(e) { console.error("Poll Error:", e); }
        }

        // Start Loop
        setInterval(updateDashboard, 3000);
        updateDashboard();
    </script>
</body>
</html>