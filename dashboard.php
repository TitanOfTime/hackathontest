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
    </div>

        <!-- MOVED PANELS TO LEFT COLUMN -->
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
        <a href="?logout=true" class="bg-red-900/80 hover:bg-red-800 text-red-200 px-4 py-2 rounded-lg font-bold text-xs backdrop-blur border border-red-700/50 mb-2">🔒 LOGOUT</a>
        
        <a href="app.php" target="_blank" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-xl font-bold shadow-lg flex items-center gap-2 transition-transform transform active:scale-95">
            <span class="text-xl">+</span> New Report
        </a>
        
        <div class="glass-panel p-2 rounded-xl flex flex-col gap-2">
            <button onclick="map.setView([20,0], 2)" class="p-2 hover:bg-gray-100 rounded-lg text-gray-600">🌍</button>
            <button onclick="location.reload()" class="p-2 hover:bg-gray-100 rounded-lg text-gray-600">🔄</button>
        </div>
    </div>

    <!-- REMOVED RIGHT SIDEBAR CONTAINER -->

    <script>
        // SANITIZE INPUT: Prevents hackers from injecting HTML/JS code
        function safe(str) {
            if (!str) return '';
            const div = document.createElement('div');
            div.textContent = str; // converting HTML to plain text
            return div.innerHTML;
        }

        // 1. Init Map
        const map = L.map('map', { zoomControl: false }).setView([20, 0], 2);
        L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', { attribution: '© OpenStreetMap', maxZoom: 20 }).addTo(map);
        L.control.zoom({ position: 'bottomleft' }).addTo(map);

        // 2. Icons
        const redIcon = new L.Icon({ iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-red.png', shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png', iconSize: [25, 41], iconAnchor: [12, 41], popupAnchor: [1, -34], shadowSize: [41, 41] });
        const blueIcon = new L.Icon({ iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-blue.png', shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png', iconSize: [25, 41], iconAnchor: [12, 41], popupAnchor: [1, -34], shadowSize: [41, 41] });

        let firstLoad = true;
        function formatTime(isoString) { if (!isoString) return 'Unknown'; const date = new Date(isoString); return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }); }

        // --- ACTION: RESOLVE ---
        async function resolveIncident(id) {
            if(!confirm('Mark resolved?')) return;
            try {
                const res = await fetch('resolve.php', { method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify({id: id}) });
                const data = await res.json();
                if(data.status === 'success') updateDashboard();
            } catch(e) {}
        }

        // --- NEW ACTION: DELETE ---
        async function deleteIncident(id) {
            if(!confirm('⚠️ PERMANENTLY DELETE this record? This cannot be undone.')) return;
            try {
                const res = await fetch('delete_incident.php', { method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify({id: id}) });
                const data = await res.json();
                if(data.status === 'success') updateDashboard();
            } catch(e) { console.error(e); }
        }

        // --- THE UNPACKER LOGIC ---
        function parseIncidentData(rawType) {
            let type = rawType;
            let tags = [];
            let count = null;

            // Extract [...]
            const tagMatch = type.match(/\[(.*?)\]/);
            if(tagMatch) {
                tags = tagMatch[1].split(',').map(s => s.trim());
                type = type.replace(/\[.*?\]/, '');
            }

            // Extract (...)
            const countMatch = type.match(/\((.*?) Pax\)/);
            if(countMatch) {
                count = countMatch[1];
                type = type.replace(/\(.*?\)/, '');
            }

            return { type: type.trim(), tags, count };
        }

        async function updateDashboard() {
            try {
                const res = await fetch('fetch.php');
                const data = await res.json();
                
                // Get current search term
                const searchEl = document.getElementById('search-input');
                const filterTerm = searchEl ? searchEl.value.toLowerCase().trim() : '';

                map.eachLayer((layer) => { if (!!layer.toGeoJSON) map.removeLayer(layer); });
                const markers = L.featureGroup();
                let critical = 0, activeCount = 0, sidebarCount = 0, feedHTML = '', historyHTML = '';

                data.forEach((inc) => {
                    const localTime = formatTime(inc.reported_at);
                    
                    // PARSE DATA
                    const info = parseIncidentData(inc.incident_type);
                    
                    // --- SECURITY: SANITIZE EVERYTHING ---
                    const safeType = safe(info.type);
                    const safeId = safe(inc.id);
                    const safeSev = safe(inc.severity);
                    const safeTags = info.tags.map(t => safe(t)); // Clean the array
                    const safeCount = safe(info.count);
                    // -------------------------------------

                    // Generate Badge HTML (Using Clean Data)
                    let badgesHtml = safeTags.map(t => `<span class="bg-blue-100 text-blue-700 px-1 rounded text-[10px] font-bold border border-blue-200 mr-1">${t}</span>`).join('');
                    if(safeCount) badgesHtml += `<span class="bg-gray-800 text-white px-1 rounded text-[10px] font-bold border border-gray-600 mr-1"><i class="fa-solid fa-user"></i> ${safeCount}</span>`;

                    if (inc.status === 'resolved') {
                        // RESOLVED
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
                        // ACTIVE
                        activeCount++;
                        if(inc.severity >= 4) critical++;
                        let iconToUse = (inc.severity >= 4) ? redIcon : blueIcon;
                        
                        // HEATMAP / GHOST EFFECT (Opacity 0.8)
                        const marker = L.marker([inc.latitude, inc.longitude], {icon: iconToUse, opacity: 0.8});
                        
                        let imageHtml = (inc.image_data && inc.image_data.length > 100) ? `<div class="mt-2"><img src="${inc.image_data}" class="w-full h-32 object-cover rounded-lg border border-gray-200"></div>` : '';
                        
                        // POPUP (Using Safe Data)
                        marker.bindPopup(`
                            <div class="text-center min-w-[200px] font-sans">
                                <strong class="text-sm uppercase tracking-wide text-gray-500">${safeType}</strong><br>
                                <div class="text-lg font-bold ${inc.severity >= 4 ? 'text-red-600' : 'text-blue-600'}">Severity Level ${safeSev}</div>
                                <div class="my-2">${badgesHtml}</div>
                                <div class="text-xs text-gray-500 font-bold mb-2">🕒 ${localTime}</div>
                                ${imageHtml}
                                <button onclick="resolveIncident(${safeId})" class="mt-3 w-full bg-green-500 hover:bg-green-600 text-white font-bold py-2 px-4 rounded-lg shadow text-sm">✅ Mark Resolved</button>
                            </div>
                        `);
                        marker.addTo(markers);

                        // --- FILTER LOGIC FOR SIDEBAR (UPDATED) ---
                        // Rule 1: Severity > 3 (4 or 5) OR
                        // Rule 2: Assistance in [Medical, Rescue, Trapped (SOS)]
                        const sev = parseInt(inc.severity);
                        const fullType = inc.incident_type; // Check FULL string (including tags)
                        
                        const isUrgentType = fullType.includes('Medical') || fullType.includes('Rescue') || fullType.includes('Trapped');
                        const isSupplies = fullType.includes('Supplies');
                        
                        let showInSidebar = false;

                        // Show if High Severity OR Urgent Needs (regardless of severity)
                        if (sev >= 4 || isUrgentType) {
                            showInSidebar = true;
                        }
                        
                        // --- NEW: TEXT SEARCH FILTER ---
                        // Combine all searchable fields into one string
                        const searchableText = `${fullType} level ${sev} lvl ${sev}`.toLowerCase();
                        
                        if (filterTerm && !searchableText.includes(filterTerm)) {
                            showInSidebar = false;
                        }

                        if (showInSidebar && sidebarCount < 20) {
                            sidebarCount++;
                            // FEED ITEM (Using Safe Data)
                            feedHTML += `
                                <div class="bg-white p-3 rounded-xl border border-gray-100 shadow-sm hover:shadow-md transition-shadow cursor-pointer" onclick="map.flyTo([${inc.latitude}, ${inc.longitude}], 15)">
                                    <div class="flex justify-between items-start">
                                        <div>
                                            <p class="font-bold text-gray-800 text-sm">${safeType}</p>
                                            <div class="mt-1">${badgesHtml}</div>
                                            <p class="text-xs text-gray-500 mt-1">🕒 ${localTime} • ID: #${safeId}</p>
                                        </div>
                                        <span class="${inc.severity >= 4 ? 'bg-red-100 text-red-700' : 'bg-blue-100 text-blue-700'} text-[10px] font-bold px-2 py-1 rounded">LVL ${safeSev}</span>
                                    </div>
                                </div>
                            `;
                        }
                    }
                });
                
                markers.addTo(map);
                document.getElementById('feed-list').innerHTML = feedHTML || '<div class="p-4 text-center text-gray-400 text-sm">No active incidents</div>';
                document.getElementById('history-list').innerHTML = historyHTML || '<div class="p-4 text-center text-gray-400 text-sm">No mission history yet</div>';
                document.getElementById('total-count').innerText = activeCount;
                document.getElementById('crit-count').innerText = critical;

                if (firstLoad && activeCount > 0) {
                    map.fitBounds(markers.getBounds(), { padding: [50, 50] });
                    firstLoad = false;
                }
            } catch(e) { console.error(e); }
        }

        setInterval(updateDashboard, 3000);
        updateDashboard();
    </script>
</body>
</html>