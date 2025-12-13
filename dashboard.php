<?php require 'db.php'; ?>
<!DOCTYPE html>
<html>
<head>
    <title>HQ Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
</head>
<body class="bg-gray-900 text-white h-screen flex flex-col">
    <header class="p-4 bg-gray-800 border-b border-gray-700 flex justify-between items-center">
        <h1 class="text-xl font-bold">Aegis Command</h1>
        <div class="text-sm text-gray-400">Live Feed</div>
    </header>
    
    <div class="flex-1 relative z-0" id="map"></div>

    <script>
        // 1. Init Map (Start Zoomed Out)
        const map = L.map('map').setView([20, 0], 2);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap'
        }).addTo(map);

        // 2. Define Custom Icons (CRITICAL FIX)
        const redIcon = new L.Icon({
            iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-red.png',
            shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
            iconSize: [25, 41],
            iconAnchor: [12, 41],
            popupAnchor: [1, -34],
            shadowSize: [41, 41]
        });

        const blueIcon = new L.Icon({
            iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-blue.png',
            shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
            iconSize: [25, 41],
            iconAnchor: [12, 41],
            popupAnchor: [1, -34],
            shadowSize: [41, 41]
        });

        // 3. Auto-Zoom Logic
        let firstLoad = true;

        async function updateMap() {
            try {
                const res = await fetch('fetch.php');
                const data = await res.json();
                
                // Clear old layers
                map.eachLayer((layer) => {
                    if (!!layer.toGeoJSON) {
                        map.removeLayer(layer);
                    }
                });

                // Create a group to hold all markers
                const markers = L.featureGroup();
                let critical = 0;
                
                data.forEach(inc => {
                    if(inc.severity >= 4) critical++;
                    
                    // Choose Icon
                    let iconToUse = (inc.severity >= 4) ? redIcon : blueIcon;
                    
                    // Image Logic
                    let imageHtml = '';
                    if (inc.image_data && inc.image_data.length > 100) {
                        imageHtml = `
                            <div class="mt-2">
                                <img src="${inc.image_data}" style="width:100%; height:auto; border-radius:8px; border:2px solid #ccc;">
                            </div>
                        `;
                    }

                    // Create Marker with Popup
                    const marker = L.marker([inc.latitude, inc.longitude], {icon: iconToUse});
                    
                    marker.bindPopup(`
                        <div class="text-center" style="min-width: 200px">
                            <strong class="text-lg uppercase tracking-wide">${inc.incident_type}</strong><br>
                            <span class="${inc.severity >= 4 ? 'text-red-600 font-bold' : 'text-blue-600'}">
                                Severity Level: ${inc.severity}
                            </span><br>
                            <span class="text-gray-500 text-xs">${inc.reported_at}</span>
                            ${imageHtml} 
                        </div>
                     `);

                    // Add to group (Essential for Auto-Zoom)
                    marker.addTo(markers);
                });

                // Add all markers to map
                markers.addTo(map);

                // 4. Trigger Auto-Zoom (Only once)
                if (firstLoad && data.length > 0) {
                    map.fitBounds(markers.getBounds(), { padding: [50, 50] });
                    firstLoad = false;
                    console.log("Auto-zoomed to " + data.length + " incidents.");
                }

                // Update Stats
                if(document.getElementById('crit-count')) {
                    document.getElementById('crit-count').innerText = critical;
                    document.getElementById('total-count').innerText = data.length;
                }

            } catch(e) { console.log("Map poll error", e); }
        }

        setInterval(updateMap, 3000);
        updateMap();
    </script>
</body>
</html>