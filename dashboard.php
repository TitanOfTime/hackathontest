<?php 
session_start();
require 'db.php'; 

// --- ADMIN LOGOUT ---
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: dashboard.php");
    exit;
}

// --- ADMIN LOGIN (Database Check) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['username'])) {
    $u = $_POST['username'];
    $p = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ? AND role = 'admin'");
    $stmt->execute([$u]);
    $admin = $stmt->fetch();

    if ($admin && password_verify($p, $admin['password'])) {
        $_SESSION['admin_auth'] = true;
        header("Location: dashboard.php");
        exit;
    } else {
        $error = "Access Denied: Admin Level Only";
    }
}

if (!isset($_SESSION['admin_auth'])): 
?>
<!DOCTYPE html>
<body class="bg-gray-900 text-white min-h-screen flex items-center justify-center p-4 font-sans">
    <div class="max-w-md w-full bg-gray-800 p-8 rounded-2xl border border-gray-700 shadow-2xl text-center">
        <h1 class="text-3xl font-black mb-2">AEGIS HQ</h1>
        <p class="text-gray-400 text-xs uppercase tracking-widest mb-6">Classified Access</p>
        <?php if(isset($error)) echo "<p class='text-red-400 mb-4 text-sm'>$error</p>"; ?>
        <form method="POST" class="space-y-4">
            <input type="text" name="username" class="w-full bg-gray-900 border border-gray-700 text-white p-4 rounded-xl text-center text-lg" placeholder="Username" autofocus>
            <input type="password" name="password" class="w-full bg-gray-900 border border-gray-700 text-white p-4 rounded-xl text-center text-lg" placeholder="••••••••">
            <button class="w-full bg-blue-600 hover:bg-blue-500 font-bold py-4 rounded-xl shadow-lg">UNLOCK SYSTEM</button>
        </form>
    </div>
    <script src="https://cdn.tailwindcss.com"></script>
</body>
</html>
<?php exit; endif; ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aegis Command Center</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster/dist/MarkerCluster.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster/dist/MarkerCluster.Default.css" />
    <script src="https://unpkg.com/leaflet.markercluster/dist/leaflet.markercluster.js"></script>
    
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        body { font-family: 'Inter', sans-serif; }
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .glass-panel {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(229, 231, 235, 0.5);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        }
        
        /* --- CUSTOM CLUSTER STYLES --- */
        @keyframes blink-cluster {
            0% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.1); opacity: 0.85; box-shadow: 0 0 15px rgba(0,0,0,0.5); }
            100% { transform: scale(1); opacity: 1; }
        }

        .marker-cluster-small, .marker-cluster-medium, .marker-cluster-large {
            background-color: transparent !important; /* Remove default light outer ring */
        }

        .marker-cluster-small div, .marker-cluster-medium div, .marker-cluster-large div {
            width: 36px !important;
            height: 36px !important;
            margin-left: 2px !important;
            margin-top: 2px !important;
            
            display: flex;
            align-items: center;
            justify-content: center;
            
            border-radius: 50%;
            border: 2px solid white;
            color: white !important;
            font-weight: 900 !important;
            font-family: 'Inter', sans-serif;
            text-shadow: 0 1px 2px rgba(0,0,0,0.5);
            
            animation: blink-cluster 2s infinite ease-in-out;
        }

        /* Dark Colours */
        .marker-cluster-small div { background-color: #0f172a !important; } /* Dark Slate */
        .marker-cluster-medium div { background-color: #7c2d12 !important; } /* Dark Orange */
        .marker-cluster-large div { background-color: #7f1d1d !important; } /* Dark Red */
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
                <p class="text-xs text-gray-500 font-bold uppercase mb-1">High Sev</p>
                <p class="text-3xl font-bold text-red-600" id="crit-count">0</p>
            </div>
        </div>

        <div class="glass-panel rounded-2xl overflow-hidden flex flex-col shrink pointer-events-auto max-h-[40vh]">
            <div class="p-4 border-b border-gray-100 bg-white">
                <div class="flex justify-between items-center mb-2">
                    <h3 class="font-bold text-gray-800">Priority Incidents</h3>
                    <span class="bg-green-100 text-green-700 text-[10px] font-bold px-2 py-1 rounded-full uppercase">Live Feed</span>
                </div>
                <input type="text" id="search-input" placeholder="🔍 Search incidents..." class="w-full bg-gray-50 border border-gray-200 rounded-lg px-3 py-2 text-xs focus:outline-none focus:border-blue-500 transition-colors">
            </div>
            <div id="feed-list" class="overflow-y-auto p-2 space-y-2 no-scrollbar bg-gray-50/50">
                <div class="p-4 text-center text-gray-400 text-sm">Waiting for data...</div>
            </div>
        </div>

        <div class="glass-panel rounded-2xl overflow-hidden flex flex-col shrink pointer-events-auto max-h-[30vh]">
            <div class="p-4 border-b border-gray-100 flex justify-between items-center bg-white">
                <h3 class="font-bold text-gray-800">Mission History</h3>
                <span class="bg-gray-100 text-gray-700 text-[10px] font-bold px-2 py-1 rounded-full uppercase">Resolved</span>
            </div>
            <div id="history-list" class="overflow-y-auto p-2 space-y-2 no-scrollbar bg-gray-50/50">
                <div class="p-4 text-center text-gray-400 text-sm">No mission history yet</div>
            </div>
        </div>
    </div>

    <div class="absolute top-6 right-6 z-10 flex flex-col gap-3 items-end">
        <!-- FILTERS -->
        <div class="glass-panel p-2 rounded-xl flex gap-1 shadow-lg mb-2">
            <button onclick="setFilter('All')" data-type="All" class="filter-btn bg-gray-800 text-white px-3 py-1 rounded-lg text-xs font-bold transition-colors">All</button>
            <button onclick="setFilter('Medical')" data-type="Medical" class="filter-btn bg-white text-gray-700 hover:bg-gray-100 px-3 py-1 rounded-lg text-xs font-bold transition-colors">Medical</button>
            <button onclick="setFilter('Rescue')" data-type="Rescue" class="filter-btn bg-white text-gray-700 hover:bg-gray-100 px-3 py-1 rounded-lg text-xs font-bold transition-colors">Rescue</button>
            <button onclick="setFilter('Fire')" data-type="Fire" class="filter-btn bg-white text-gray-700 hover:bg-gray-100 px-3 py-1 rounded-lg text-xs font-bold transition-colors">Fire</button>
            <button onclick="toggleHazardMode()" id="hazard-btn" class="bg-white text-gray-700 hover:bg-red-50 px-3 py-1 rounded-lg text-xs font-bold border-l border-gray-200 ml-2 transition-colors">⭕ Hazard Zone</button>
        </div>

        <a href="?logout=true" class="bg-red-900/80 hover:bg-red-800 text-red-200 px-4 py-2 rounded-lg font-bold text-xs backdrop-blur border border-red-700/50 mb-2">🔒 LOGOUT</a>
        
        <a href="analytics.php" target="_blank" class="bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-3 rounded-xl font-bold shadow-lg flex items-center gap-2 transition-transform transform active:scale-95 mb-2">
            <span class="text-xl">📊</span> Analytics & Stats
        </a>

        <button onclick="sendBroadcast()" class="bg-red-600 hover:bg-red-700 text-white px-6 py-3 rounded-xl font-bold shadow-lg flex items-center gap-2 transition-transform transform active:scale-95 mb-2 animate-pulse">
            <span class="text-xl">📢</span> BROADCAST ALERT
        </button>

        <a href="app.php" target="_blank" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-xl font-bold shadow-lg flex items-center gap-2 transition-transform transform active:scale-95">
            <span class="text-xl">+</span> New Report
        </a>
        
        <script>
            async function sendBroadcast() {
                const msg = prompt("📢 EMERGENCY BROADCAST SYSTEM\n\nEnter alert message to send to ALL active units:", "Heavy Rain expected. Move to higher ground.");
                if(msg) {
                    if(confirm("⚠️ ARE YOU SURE?\n\nThis will trigger a RED ALERT on all user devices immediately.")) {
                        try {
                            const res = await fetch('broadcast_api.php', {
                                method: 'POST',
                                headers: {'Content-Type': 'application/json'},
                                body: JSON.stringify({ message: msg })
                            });
                            const data = await res.json();
                            if(data.status === 'success') {
                                alert("✅ Broadcast Sent Successfully");
                            } else {
                                alert("❌ Error: " + data.msg);
                            }
                        } catch(e) {
                            alert("❌ Network Error");
                        }
                    }
                }
            }
        </script>
        
        <div class="glass-panel p-2 rounded-xl flex flex-col gap-2">
            <button onclick="map.setView([20,0], 2)" class="p-2 hover:bg-gray-100 rounded-lg text-gray-600">🌍</button>
            <button onclick="location.reload()" class="p-2 hover:bg-gray-100 rounded-lg text-gray-600">🔄</button>
        </div>
    </div>

    <script>
        // SANITIZE INPUT: Prevents hackers from injecting HTML/JS code
        function safe(str) {
            if (!str) return '';
            const div = document.createElement('div');
            div.textContent = str; 
            return div.innerHTML;
        }

        // 1. Init Map
        const map = L.map('map', { zoomControl: false }).setView([20, 0], 2);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '© OpenStreetMap contributors', maxZoom: 19 }).addTo(map);
        L.control.zoom({ position: 'bottomleft' }).addTo(map);

        // GLOBAL CLUSTER GROUP (For smoother updates)
        const clusterGroup = L.markerClusterGroup();
        map.addLayer(clusterGroup);
        
        let markers = {}; // Store marker references

        let isPaused = false;
        map.on('popupopen', () => isPaused = true);
        map.on('popupclose', () => { 
            isPaused = false;
            if (window.popupTimer) clearTimeout(window.popupTimer);
            updateDashboard(); 
        });

        // 2. Icons
        const redIcon = new L.Icon({ iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-red.png', shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png', iconSize: [25, 41], iconAnchor: [12, 41], popupAnchor: [1, -34], shadowSize: [41, 41] });
        const orangeIcon = new L.Icon({ iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-orange.png', shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png', iconSize: [25, 41], iconAnchor: [12, 41], popupAnchor: [1, -34], shadowSize: [41, 41] });
        const greenIcon = new L.Icon({ iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-green.png', shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png', iconSize: [25, 41], iconAnchor: [12, 41], popupAnchor: [1, -34], shadowSize: [41, 41] });
        const blueIcon = new L.Icon({ iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-blue.png', shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png', iconSize: [25, 41], iconAnchor: [12, 41], popupAnchor: [1, -34], shadowSize: [41, 41] });

        let firstLoad = true;
        let currentFilter = 'All'; // Filter State
        let hazardMode = false; // Geofence State

        // --- FILTER UI UPDATE ---
        function setFilter(type) {
            currentFilter = type;
            // Update Button Visuals
            document.querySelectorAll('.filter-btn').forEach(btn => {
                if(btn.dataset.type === type) {
                    btn.classList.add('bg-gray-800', 'text-white');
                    btn.classList.remove('bg-white', 'text-gray-700');
                } else {
                    btn.classList.remove('bg-gray-800', 'text-white');
                    btn.classList.add('bg-white', 'text-gray-700');
                }
            });
            updateDashboard();
        }

        // --- HAZARD ZONE TOOL ---
        function toggleHazardMode() {
            hazardMode = !hazardMode;
            const btn = document.getElementById('hazard-btn');
            if (hazardMode) {
                btn.classList.add('bg-red-600', 'text-white', 'animate-pulse');
                btn.classList.remove('bg-white', 'text-gray-700');
                btn.innerHTML = '⚠️ Click Map to Set Zone';
                document.getElementById('map').style.cursor = 'crosshair';
            } else {
                btn.classList.remove('bg-red-600', 'text-white', 'animate-pulse');
                btn.classList.add('bg-white', 'text-gray-700');
                btn.innerHTML = '⭕ Hazard Zone';
                document.getElementById('map').style.cursor = '-webkit-grab';
            }
        }

        // --- ZONE MANAGEMENT & PERSISTENCE ---
        
        function saveZones() {
            const zones = [];
            map.eachLayer(layer => {
                if (layer instanceof L.Circle && layer.severity) {
                    zones.push({
                        lat: layer.getLatLng().lat,
                        lng: layer.getLatLng().lng,
                        radius: layer.getRadius(),
                        color: layer.options.color,
                        severity: layer.severity
                    });
                }
            });
            localStorage.setItem('aegis_zones', JSON.stringify(zones));
        }

        function bindZonePopup(circle) {
            circle.bindPopup(() => {
                const id = circle._leaflet_id;
                const s = circle.severity;
                return `
                    <div class="text-center font-sans">
                        <p class="font-bold text-gray-700 text-xs mb-2 uppercase tracking-wider">Zone Controls</p>
                        
                        <div class="flex justify-center gap-2 mb-3">
                            <button onclick="updateZoneColor(${id}, 'high')" title="High Risk" class="w-6 h-6 rounded-full bg-red-500 border-2 ${s === 'high' ? 'border-gray-900 scale-110' : 'border-transparent'} shadow-sm"></button>
                            <button onclick="updateZoneColor(${id}, 'medium')" title="Medium Risk" class="w-6 h-6 rounded-full bg-amber-400 border-2 ${s === 'medium' ? 'border-gray-900 scale-110' : 'border-transparent'} shadow-sm"></button>
                            <button onclick="updateZoneColor(${id}, 'low')" title="Low Risk" class="w-6 h-6 rounded-full bg-green-500 border-2 ${s === 'low' ? 'border-gray-900 scale-110' : 'border-transparent'} shadow-sm"></button>
                        </div>

                        <div class="flex gap-1 justify-center mb-2">
                            <button onclick="updateZoneRadius(${id})" class="bg-gray-100 hover:bg-gray-200 border border-gray-300 text-gray-700 text-[10px] font-bold px-2 py-1 rounded">
                                R: ${circle.getRadius()}m
                            </button>
                            <button onclick="removeZone(${id})" class="bg-red-50 hover:bg-red-100 border border-red-200 text-red-600 text-[10px] font-bold px-2 py-1 rounded">
                                Delete
                            </button>
                        </div>
                        <p class="text-[9px] text-gray-400">ID: ${id}</p>
                    </div>
                `;
            });
        }

        window.updateZoneColor = function(id, level) {
            const layer = map._layers[id];
            if (!layer) return;
            
            let color = '#ef4444'; // Red (High)
            if (level === 'medium') color = '#fbbf24'; // Orange/Yellow
            if (level === 'low')    color = '#22c55e'; // Green

            layer.setStyle({ color: color, fillColor: color });
            layer.severity = level; // Save state
            
            layer.closePopup();
            layer.openPopup();
            saveZones();
        };

        window.updateZoneRadius = function(id) {
             const layer = map._layers[id];
             if (!layer) return;
             
             const newR = prompt("Enter new radius (meters):", layer.getRadius());
             if(newR && !isNaN(newR)) {
                 layer.setRadius(parseInt(newR));
                 layer.closePopup();
                 layer.openPopup();
                 saveZones();
             }
        };

        window.removeZone = function(id) {
            const layer = map._layers[id];
            if (layer) { 
                map.removeLayer(layer);
                saveZones();
            }
        };

        function loadZones() {
            const stored = localStorage.getItem('aegis_zones');
            if(!stored) return;
            try {
                const zones = JSON.parse(stored);
                zones.forEach(z => {
                    const circle = L.circle([z.lat, z.lng], {
                        color: z.color,
                        fillColor: z.color,
                        fillOpacity: 0.3,
                        radius: z.radius
                    }).addTo(map);
                    circle.severity = z.severity;
                    bindZonePopup(circle);
                });
            } catch(e) { console.error("Error loading zones", e); }
        }

        map.on('click', function(e) {
            if (!hazardMode) return;
            
            // 1. Draw Circle
            const radius = prompt("Enter Hazard Radius (meters):", "500");
            if (!radius) return;

            const circle = L.circle(e.latlng, {
                color: '#ef4444',
                fillColor: '#ef4444',
                fillOpacity: 0.3,
                radius: parseInt(radius)
            }).addTo(map);
            
            circle.severity = 'high'; // Default

            // 2. Bind Interactive Popup
            bindZonePopup(circle);

            // 3. Count impacted users (Simulated logic)
            let impactedCount = 0;
            for (const id in markers) {
                const m = markers[id];
                const d = map.distance(e.latlng, m.getLatLng());
                if (d <= parseInt(radius)) impactedCount++;
            }

            // 4. Send Initial Alert
            if(confirm(`⚠️ ZONE CREATED\n\n${impactedCount} active incidents found in this area.\n\nBroadcast "EVACUATE" push notification to all users in this zone?`)) {
                alert("📡 ALERT SENT: Push notification dispatched to devices in geofence.");
            }

            // Reset Mode
            toggleHazardMode();
            
            // Auto open menu & SAVE
            setTimeout(() => circle.openPopup(), 500);
            saveZones();
        });

        // Load Zones on Startup
        loadZones();

        function formatTime(isoString) { if (!isoString) return 'Unknown'; const date = new Date(isoString); return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }); }

        // --- ACTION: RESOLVE ---
        async function resolveIncident(id) {
            if(!confirm('Mark resolved?')) return;
            try {
                const res = await fetch('resolve.php', { method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify({id: id}) });
                const data = await res.json();
                if(data.status === 'success') {
                    map.closePopup(); // Close popup to unpause and trigger update
                    updateDashboard();
                }
            } catch(e) {}
        }

        // --- ACTION: DISPATCH (IN PROGRESS) ---
        async function markInProgress(id) {
            try {
                // 1. Send command to DB
                const res = await fetch('progress.php', { method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify({id: id}) });
                
                // 2. CLOSE THE POPUP (This unpauses the map and triggers the auto-update)
                map.closePopup(); 
                
            } catch(e) { console.error(e); }
        }

        // --- ACTION: DELETE ---
        async function deleteIncident(id) {
            if(!confirm('⚠️ PERMANENTLY DELETE this record? This cannot be undone.')) return;
            try {
                const res = await fetch('delete_incident.php', { method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify({id: id}) });
                const data = await res.json();
                if(data.status === 'success') updateDashboard();
            } catch(e) { console.error(e); }
        }
        
        // --- ACTION: HIGHLIGHT INCIDENT ---
        function highlightIncident(id) {
            const marker = markers[id];
            if (marker) {
                // LOCK UPDATES immediately to prevent dashboard refresh ensuring marker isn't removed during zoom
                isPaused = true;
                
                if (window.popupTimer) clearTimeout(window.popupTimer);
                
                clusterGroup.zoomToShowLayer(marker, function() {
                    marker.openPopup();
                    // Auto-close after 10 seconds
                    window.popupTimer = setTimeout(() => {
                        marker.closePopup();
                    }, 10000);
                });
            }
        }

        // --- DATA PARSER ---
        function parseIncidentData(rawType) {
            let type = rawType;
            let tags = [];
            let count = null;

            const tagMatch = type.match(/\[(.*?)\]/);
            if(tagMatch) {
                tags = tagMatch[1].split(',').map(s => s.trim());
                type = type.replace(/\[.*?\]/, '');
            }

            const countMatch = type.match(/\((.*?) Pax\)/);
            if(countMatch) {
                count = countMatch[1];
                type = type.replace(/\(.*?\)/, '');
            }

            return { type: type.trim(), tags, count };
        }

        async function updateDashboard() {
            if (isPaused) return;
            try {
                const res = await fetch('fetch.php');
                const data = await res.json();
                
                // CRITICAL FIX: Ensure we didn't pause while fetching
                if (isPaused) return;

                // Get current search term
                const searchEl = document.getElementById('search-input');
                const filterTerm = searchEl ? searchEl.value.toLowerCase().trim() : '';

                // CLEAR CLUSTERS AND MARKERS
                clusterGroup.clearLayers();
                markers = {};

                let critical = 0, activeCount = 0, sidebarCount = 0, feedHTML = '', historyHTML = '';

                data.forEach((inc) => {
                    const localTime = formatTime(inc.reported_at);
                    
                    // --- 0. SKIP DELETED ---
                    if (inc.status === 'deleted') return;

                    const info = parseIncidentData(inc.incident_type);
                    
                    // --- SANITIZE ---
                    const safeType = safe(info.type);
                    const safeId = safe(inc.id);
                    const safeSev = safe(inc.severity);
                    const safeTags = info.tags.map(t => safe(t));
                    const safeCount = safe(info.count);

                    // --- BADGES ---
                    let badgesHtml = safeTags.map(t => `<span class="bg-blue-100 text-blue-700 px-1 rounded text-[10px] font-bold border border-blue-200 mr-1">${t}</span>`).join('');
                    if(safeCount) badgesHtml += `<span class="bg-gray-800 text-white px-1 rounded text-[10px] font-bold border border-gray-600 mr-1"><i class="fa-solid fa-user"></i> ${safeCount}</span>`;

                    // --- FILTER CHECK ---
                    // Applies only to MAP markers, lists are separate logic
                    let showOnMap = true;

                    // HIDE RESOLVED FROM MAP
                    if (inc.status === 'resolved') showOnMap = false;

                    if (currentFilter !== 'All') {
                        // Check if tags or type contains filter
                        const combinedTags = [...info.tags, info.type].join(' ');
                        if (!combinedTags.includes(currentFilter)) showOnMap = false;
                    }

                    // --- MAP LOGIC (ALL STATUSES) ---
                    const isResolved = inc.status === 'resolved';
                    const isInProgress = inc.status === 'in_progress';
                    
                    // Color Logic: Red (Active), Yellow (Progress), Green (Resolved)
                    let iconToUse = redIcon; // Default Active
                    if (isResolved) iconToUse = greenIcon;
                    else if (isInProgress) iconToUse = orangeIcon;

                    if (showOnMap) {
                        // Action Buttons Logic
                        let actionButtons = '';
                        if (!isResolved) {
                            if(!isInProgress) actionButtons += `<button onclick="markInProgress(${safeId})" class="w-full bg-orange-500 hover:bg-orange-600 text-white font-bold py-2 px-4 rounded-lg shadow text-sm mb-2">🚀 Dispatch Team</button>`;
                            actionButtons += `<button onclick="resolveIncident(${safeId})" class="w-full bg-green-500 hover:bg-green-600 text-white font-bold py-2 px-4 rounded-lg shadow text-sm">✅ Mark Resolved</button>`;
                        } else {
                            actionButtons = `<p class="text-green-600 font-bold text-center">✅ RESOLVED</p>`;
                        }
                        
                        let imageHtml = (inc.image_data && inc.image_data.length > 100) ? `<div class="mt-2"><img src="${inc.image_data}" class="w-full h-32 object-cover rounded-lg border border-gray-200"></div>` : '';
                        
                        // Popup
                        const marker = L.marker([inc.latitude, inc.longitude], {icon: iconToUse, opacity: 0.9});
                        marker.bindPopup(`
                            <div class="text-center min-w-[200px] font-sans">
                                <strong class="text-sm uppercase tracking-wide text-gray-500">${safeType}</strong><br>
                                <div class="text-lg font-bold ${inc.severity >= 4 ? 'text-red-600' : 'text-blue-600'}">Severity Level ${safeSev}</div>
                                
                                ${isInProgress ? '<div class="bg-orange-100 text-orange-700 text-xs font-bold px-2 py-1 rounded my-1 border border-orange-200 uppercase">⚠️ Response Team En Route</div>' : ''}
                                
                                <div class="my-2">${badgesHtml}</div>
                                <div class="text-xs text-gray-500 font-bold mb-2">🕒 ${localTime}</div>
                                ${imageHtml}
                                <div class="mt-3">${actionButtons}</div>
                            </div>
                        `);
                        
                        clusterGroup.addLayer(marker);
                        markers[inc.id] = marker; // TRACK MARKER
                    }

                    // --- LIST LOGIC ---
                    if (isResolved) {
                        // RESOLVED LIST
                        historyHTML += `
                            <div class="bg-white p-3 rounded-xl border border-gray-200 shadow-sm opacity-75 group hover:opacity-100 transition-all">
                                <div class="flex justify-between items-start mb-1">
                                    <span class="font-bold text-gray-700 line-through text-xs">${safeType}</span>
                                    <div class="flex gap-1">
                                        <span class="bg-gray-200 text-gray-600 text-[10px] font-bold px-2 py-1 rounded uppercase">Done</span>
                                        <button onclick="deleteIncident(${safeId})" class="bg-red-100 hover:bg-red-500 text-red-500 hover:text-white rounded px-2 py-0.5 transition-colors">
                                            <i class="fa-solid fa-trash text-[10px]"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="mb-1">${badgesHtml}</div>
                                <p class="text-[10px] text-gray-400">Time: ${localTime}</p>
                            </div>
                        `;
                    } else {
                        // PRIORITY FEED (Active + In Progress)
                        activeCount++;
                        if(inc.severity >= 4) critical++;
                        
                        // --- SIDEBAR FILTER LOGIC ---
                        const sev = parseInt(inc.severity);
                        const fullType = inc.incident_type;
                        const isUrgentType = fullType.includes('Medical') || fullType.includes('Rescue') || fullType.includes('Trapped');
                        let showInSidebar = false;

                        // 1. Priority Logic
                        if (sev >= 4 || isUrgentType || isInProgress) { 
                            showInSidebar = true;
                        }
                        
                        // 2. Search Logic (Overrides Default)
                        if (filterTerm) {
                            const searchableText = `${fullType} level ${sev} lvl ${sev}`.toLowerCase();
                            if (searchableText.includes(filterTerm)) showInSidebar = true; 
                            else showInSidebar = false; 
                        }

                        if (showInSidebar && sidebarCount < 20) {
                            sidebarCount++;
                            const borderClass = isInProgress ? 'border-orange-400 bg-orange-50' : 'border-gray-100';
                            const statusText = isInProgress ? '<span class="text-orange-600 font-bold">🚀 TEAM DISPATCHED</span>' : `🕒 ${localTime}`;

                            feedHTML += `
                                <div class="bg-white p-3 rounded-xl border ${borderClass} shadow-sm hover:shadow-md transition-shadow cursor-pointer" onclick="highlightIncident(${safeId})">
                                    <div class="flex justify-between items-start">
                                        <div>
                                            <p class="font-bold text-gray-800 text-sm">${safeType}</p>
                                            <div class="mt-1">${badgesHtml}</div>
                                            <p class="text-xs text-gray-500 mt-1">${statusText} • ID: #${safeId}</p>
                                        </div>
                                        <span class="${inc.severity >= 4 ? 'bg-red-100 text-red-700' : 'bg-blue-100 text-blue-700'} text-[10px] font-bold px-2 py-1 rounded">LVL ${safeSev}</span>
                                    </div>
                                </div>
                            `;
                        }
                    }
                });
                
                document.getElementById('feed-list').innerHTML = feedHTML || '<div class="p-4 text-center text-gray-400 text-sm">No active incidents</div>';
                document.getElementById('history-list').innerHTML = historyHTML || '<div class="p-4 text-center text-gray-400 text-sm">No mission history yet</div>';
                document.getElementById('total-count').innerText = activeCount;
                document.getElementById('crit-count').innerText = critical;

                if (firstLoad && activeCount > 0) {
                    map.fitBounds(clusterGroup.getBounds(), { padding: [50, 50] });
                    firstLoad = false;
                }
            } catch(e) { console.error(e); }
        }

        document.getElementById('search-input').addEventListener('input', updateDashboard);
        setInterval(updateDashboard, 3000);
        updateDashboard();
    </script>
</body>
</html>