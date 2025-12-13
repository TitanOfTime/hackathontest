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
        async function updateMap() {
            const res = await fetch('fetch.php');
            const data = await res.json();
            
            // Clear existing markers (simple way)
            // Ideally use a layerGroup but this works for MVP
            
            data.forEach(inc => {
                let color = inc.severity >= 4 ? 'red' : 'blue';
                // Simple marker for now
                L.marker([inc.latitude, inc.longitude])
                 .addTo(map)
                 .bindPopup(`<b>${inc.incident_type}</b><br>Sev: ${inc.severity}<br>${inc.reported_at}`);
            });
        }
        setInterval(updateMap, 3000);
        updateMap();
    </script>
</body>
</html>