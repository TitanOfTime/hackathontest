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
        // Init Map
        const map = L.map('map').setView([6.6828, 80.3992], 12);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);

        // Polling Loop
        // 3. Polling Loop (UPDATED FOR IMAGES)
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

                // Update Counts
                let critical = 0;
                
                data.forEach(inc => {
                    if(inc.severity >= 4) critical++;
                    
                    // Choose Icon
                    let iconToUse = (inc.severity >= 4) ? redIcon : blueIcon;
                    
                    // --- NEW IMAGE LOGIC STARTS HERE ---
                    let imageHtml = '';
                    // Check if image data exists and is long enough to be a real image
                    if (inc.image_data && inc.image_data.length > 100) {
                        imageHtml = `
                            <div class="mt-2">
                                <img src="${inc.image_data}" style="width:100%; height:auto; border-radius:8px; border:2px solid #ccc;">
                            </div>
                        `;
                    }
                    // --- NEW IMAGE LOGIC ENDS HERE ---

                    // Create Marker with Popup
                    L.marker([inc.latitude, inc.longitude], {icon: iconToUse})
                     .addTo(map)
                     .bindPopup(`
                        <div class="text-center" style="min-width: 200px">
                            <strong class="text-lg uppercase tracking-wide">${inc.incident_type}</strong><br>
                            <span class="${inc.severity >= 4 ? 'text-red-600 font-bold' : 'text-blue-600'}">
                                Severity Level: ${inc.severity}
                            </span><br>
                            <span class="text-gray-500 text-xs">${inc.reported_at}</span>
                            ${imageHtml} </div>
                     `);
                });

                // Update Header Stats (if elements exist)
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