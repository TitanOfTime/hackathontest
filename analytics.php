<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aegis | Disaster Analytics</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster/dist/MarkerCluster.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster/dist/MarkerCluster.Default.css" />
    <script src="https://unpkg.com/leaflet.markercluster/dist/leaflet.markercluster.js"></script>

    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        body { font-family: 'Inter', sans-serif; background: #f3f4f6; }
        .glass-panel {
            background: white;
            border-radius: 1rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            padding: 1.5rem;
            border: 1px solid #e5e7eb;
        }
    </style>
</head>
<body class="p-6">

    <div class="max-w-7xl mx-auto space-y-6">
        
        <!-- HEADER -->
        <div class="flex justify-between items-center mb-8">
            <div>
                <h1 class="text-3xl font-black text-gray-900">Disaster Analytics</h1>
                <p class="text-gray-500 mt-1">Comprehensive breakdown of all incident data (Historical & Live)</p>
            </div>
            <a href="dashboard.php" class="bg-gray-800 text-white px-5 py-2 rounded-lg font-bold hover:bg-gray-700 transition">
                ← Back to Command Center
            </a>
        </div>

        <!-- STATS CARDS -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="glass-panel border-l-4 border-blue-500">
                <p class="text-gray-500 text-xs font-bold uppercase tracking-wider">Total Reports</p>
                <p class="text-4xl font-black text-gray-800 mt-1" id="total-reports">0</p>
                <p class="text-xs text-blue-500 font-medium mt-2">All time data</p>
            </div>
            <div class="glass-panel border-l-4 border-red-500">
                <p class="text-gray-500 text-xs font-bold uppercase tracking-wider">Most Frequent</p>
                <p class="text-xl font-bold text-gray-800 mt-1 truncate" id="top-incident">Analyzing...</p>
                <p class="text-xs text-red-500 font-medium mt-2">Highest occurrence</p>
            </div>
            <div class="glass-panel border-l-4 border-orange-500">
                <p class="text-gray-500 text-xs font-bold uppercase tracking-wider">People Affected</p>
                <p class="text-4xl font-black text-gray-800 mt-1" id="total-pax">0</p>
                <p class="text-xs text-orange-500 font-medium mt-2">Parsed from 'Headcount'</p>
            </div>
            <div class="glass-panel border-l-4 border-green-500">
                <p class="text-gray-500 text-xs font-bold uppercase tracking-wider">Resolution Rate</p>
                <p class="text-4xl font-black text-gray-800 mt-1" id="res-rate">0%</p>
                <p class="text-xs text-green-500 font-medium mt-2">Completed missions</p>
            </div>
        </div>

        <!-- CHARTS ROW 1 -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="glass-panel h-[350px] flex flex-col">
                <h3 class="font-bold text-gray-800 mb-2">Incident Types Overview</h3>
                <div class="flex-1 relative">
                    <canvas id="typeChart"></canvas>
                </div>
            </div>
            <div class="glass-panel h-[350px] flex flex-col">
                <h3 class="font-bold text-gray-800 mb-2">Status Distribution</h3>
                <div class="flex-1 relative flex justify-center items-center">
                    <canvas id="statusChart"></canvas>
                </div>
                <!-- Legend removed from here, ChartJS handles it better -->
            </div>
        </div>

        <!-- MAP ROW -->
        <div class="glass-panel p-0 overflow-hidden h-[500px] relative">
            <div class="absolute top-4 left-4 z-[999] bg-white/90 backdrop-blur px-4 py-2 rounded-lg shadow border border-gray-200">
                <h3 class="font-bold text-gray-800">Effected Areas Map</h3>
                <p class="text-xs text-gray-500">Geospatial distribution of all incidents</p>
            </div>
            <div id="map" class="h-full w-full"></div>
        </div>

    </div>

    <script>
        // --- 1. CONFIG & UTILS ---
        const map = L.map('map', { zoomControl: false }).setView([20, 0], 2);
        L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', { attribution: '© OpenStreetMap' }).addTo(map);
        L.control.zoom({ position: 'bottomright' }).addTo(map);

        async function init() {
            try {
                const res = await fetch('fetch_analytics.php');
                const data = await res.json();
                
                processData(data);
            } catch(e) {
                console.error("Failed to fetch analytics", e);
                alert("Error loading analytics data.");
            }
        }

        function processData(data) {
            // --- VARIABLES ---
            let total = data.length;
            let pax = 0;
            let status = { active: 0, resolved: 0, deleted: 0, in_progress: 0 };
            let types = {};
            let severities = [0,0,0,0,0,0]; // 0-5

            const markers = L.markerClusterGroup();
            
            data.forEach(inc => {
                // 1. Status Count
                let s = inc.status || 'active';
                if (status[s] !== undefined) status[s]++;
                else status.active++; // Default

                // 2. Incident Type Parsing
                // Remove [Tags] and (Pax) logic
                let cleanType = inc.incident_type
                    .replace(/\[.*?\]/, '')
                    .replace(/\(.*?\)/, '')
                    .trim();
                
                types[cleanType] = (types[cleanType] || 0) + 1;

                // 3. Headcount Parsing
                const paxMatch = inc.incident_type.match(/\((\d+) Pax\)/);
                if (paxMatch) {
                    pax += parseInt(paxMatch[1]);
                }

                // 4. Map Markers
                if (inc.latitude && inc.longitude && inc.latitude != 0) {
                    let color = '#3b82f6';
                    if (s === 'resolved') color = '#22c55e';
                    if (s === 'deleted') color = '#ef4444';
                    
                    const marker = L.circleMarker([inc.latitude, inc.longitude], {
                        radius: 8,
                        fillColor: color,
                        color: '#fff',
                        weight: 1,
                        opacity: 1,
                        fillOpacity: 0.8
                    });
                    
                    marker.bindPopup(`
                        <strong>${cleanType}</strong><br>
                        Status: <span class="capitalize font-bold text-${s === 'deleted' ? 'red' : 'blue'}-600">${s}</span><br>
                        Date: ${new Date(inc.reported_at).toLocaleDateString()}
                    `);
                    markers.addLayer(marker);
                }
            });

            // Add markers to map
            map.addLayer(markers);
            if(total > 0) map.fitBounds(markers.getBounds());

            // --- UPDATE DOM ---
            document.getElementById('total-reports').innerText = total;
            document.getElementById('total-pax').innerText = pax;
            
            // Top Incident
            let topType = Object.keys(types).reduce((a, b) => types[a] > types[b] ? a : b, "N/A");
            document.getElementById('top-incident').innerText = topType;
            
            // Resolution Rate
            let completed = status.resolved + status.deleted; // Should deleted count? Maybe just resolved.
            // Let's stick to true resolution rate (Resolved / (Active + InProgress + Resolved)) ignoring deleted in denominator? 
            // Or just Resolved / Total.
            // Let's do (Resolved / Total) * 100
            let rate = total ? Math.round((status.resolved / total) * 100) : 0;
            document.getElementById('res-rate').innerText = rate + "%";

            // --- CHARTS ---
            
            // 1. Types Chart (Bar)
            const typeCtx = document.getElementById('typeChart').getContext('2d');
            new Chart(typeCtx, {
                type: 'bar',
                data: {
                    labels: Object.keys(types),
                    datasets: [{
                        label: '# of Incidents',
                        data: Object.values(types),
                        backgroundColor: 'rgba(59, 130, 246, 0.6)',
                        borderColor: 'rgba(59, 130, 246, 1)',
                        borderWidth: 1,
                        borderRadius: 4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: { y: { beginAtZero: true } }
                }
            });

            // 2. Status Chart (Pie)
            const statusCtx = document.getElementById('statusChart').getContext('2d');
            new Chart(statusCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Active', 'In Progress', 'Resolved', 'Deleted'],
                    datasets: [{
                        data: [status.active, status.in_progress, status.resolved, status.deleted],
                        backgroundColor: [
                            '#3b82f6', // Blue (Active)
                            '#f97316', // Orange (Progress)
                            '#22c55e', // Green (Resolved)
                            '#ef4444'  // Red (Deleted)
                        ],
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false
                }
            });
        }

        init();
    </script>
</body>
</html>
