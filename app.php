<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Aegis Responder</title>
    <link rel="manifest" href="manifest.json">
    <link rel="icon" type="image/png" href="icon.png">
    <link rel="apple-touch-icon" href="icon.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; touch-action: manipulation; }
        .severity-btn.active { background-color: #fbbf24; color: black; border-color: #fbbf24; transform: scale(1.1); }
        .help-btn.active { background-color: #2563eb; border-color: #2563eb; color: white; }
        @keyframes fade-in { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        .animate-fade-in { animation: fade-in 0.3s ease-out forwards; }
    </style>
    <script>
        // SECURITY CHECK: Redirect to login if no auth token found
        if (!localStorage.getItem('aegis_auth')) {
            window.location.href = 'index.php';
        } else {
            // Reveal App only if authorized
            document.addEventListener('DOMContentLoaded', () => {
                document.getElementById('app-view').classList.remove('hidden');
                // Set Badge ID
                document.getElementById('display-badge').innerText = localStorage.getItem('aegis_user') || 'Unknown';
            });
        }
    </script>
</head>
<body class="bg-[#0f172a] text-slate-200 min-h-screen pb-20">

    <div id="app-view" class="hidden max-w-lg mx-auto min-h-screen flex flex-col relative">
        
        <div class="bg-gradient-to-br from-slate-900 via-blue-900 to-slate-900 p-8 pb-10 rounded-b-[3rem] shadow-2xl z-10 relative overflow-hidden border-b border-white/10">
            <div class="absolute -top-10 -right-10 w-64 h-64 bg-blue-500/20 rounded-full blur-3xl pointer-events-none"></div>
            <div class="absolute top-10 -left-10 w-40 h-40 bg-indigo-500/20 rounded-full blur-2xl pointer-events-none"></div>

            <div class="flex justify-between items-start relative z-10">
                <div>
                    <h1 class="text-4xl font-black text-white tracking-tighter drop-shadow-sm">Aegis</h1>
                    <p class="text-blue-200 text-sm font-medium mt-1 tracking-wide">Rapid Response Unit</p>
                </div>
                <button onclick="logout()" class="bg-white/10 hover:bg-white/20 text-white p-3 rounded-full backdrop-blur-md transition-all border border-white/10 shadow-lg active:scale-95">
                    <i class="fa-solid fa-power-off text-sm"></i>
                </button>
            </div>
            
            <div class="mt-6 inline-flex items-center gap-3 px-4 py-2 bg-black/30 rounded-full border border-white/10 backdrop-blur-md shadow-inner">
                <span class="relative flex h-3 w-3">
                  <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                  <span class="relative inline-flex rounded-full h-3 w-3 bg-green-500"></span>
                </span>
                <span class="text-xs font-bold text-gray-300 tracking-wider">ID: <span id="display-badge" class="text-white font-mono">...</span></span>
            </div>
        </div>

        <div id="status-bar" class="text-[10px] font-bold text-center py-1 pb-3 pt-12 -mt-10 rounded-b-[3rem] bg-slate-800 text-slate-500 uppercase tracking-widest z-0 transition-all duration-300 shadow-md">
            Checking Connection...
        </div>

        <div class="p-5 -mt-2 space-y-6">
            
            <form id="incident-form" class="space-y-6">
                
                <div class="space-y-2">
                    <label class="text-sm font-semibold text-slate-400 uppercase tracking-wide">Incident Type</label>
                    <div class="relative">
                        <select id="type-select" class="w-full bg-slate-800 text-white p-4 rounded-xl border border-slate-700 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none appearance-none font-medium text-lg transition-all shadow-lg">
                            <option value="Landslide">Landslide</option>
                            <option value="Flood">Flood</option>
                            <option value="Road Blocked">Road Blocked</option>
                            <option value="Power Down">Power Line Down</option>
                            <option value="Fire">Fire / Wildfire</option>
                            <option value="Medical">Medical Emergency</option>
                        </select>
                        <div class="absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none text-slate-400">
                            <i class="fa-solid fa-chevron-down"></i>
                        </div>
                    </div>
                    <input type="hidden" id="type" value="Landslide"> 
                </div>

                <div class="space-y-3">
                    <div class="flex justify-between items-end">
                        <label class="text-sm font-semibold text-slate-400 uppercase tracking-wide">Severity Level</label>
                        <span class="text-xs font-bold text-yellow-500 bg-yellow-500/10 px-2 py-1 rounded border border-yellow-500/20">
                            Level <span id="sev-display">3</span>
                        </span>
                    </div>
                    
                    <div class="flex justify-between items-center bg-slate-800 p-2 rounded-2xl border border-slate-700 shadow-inner">
                        <span class="text-[10px] font-bold text-slate-500 pl-2">MINOR</span>
                        <div class="flex gap-2">
                            <button type="button" class="severity-btn w-10 h-10 rounded-full border border-slate-600 bg-slate-700 text-slate-300 font-bold transition-all flex items-center justify-center" onclick="setSeverity(1)">1</button>
                            <button type="button" class="severity-btn w-10 h-10 rounded-full border border-slate-600 bg-slate-700 text-slate-300 font-bold transition-all flex items-center justify-center" onclick="setSeverity(2)">2</button>
                            <button type="button" class="severity-btn w-10 h-10 rounded-full border-yellow-500/50 bg-yellow-500 text-black font-bold transition-all flex items-center justify-center active scale-110 shadow-[0_0_15px_rgba(234,179,8,0.3)]" onclick="setSeverity(3)">3</button>
                            <button type="button" class="severity-btn w-10 h-10 rounded-full border border-slate-600 bg-slate-700 text-slate-300 font-bold transition-all flex items-center justify-center" onclick="setSeverity(4)">4</button>
                            <button type="button" class="severity-btn w-10 h-10 rounded-full border border-slate-600 bg-slate-700 text-slate-300 font-bold transition-all flex items-center justify-center" onclick="setSeverity(5)">5</button>
                        </div>
                        <span class="text-[10px] font-bold text-red-500 pr-2">CRIT</span>
                    </div>
                    <input type="hidden" id="severity" value="3">
                </div>

                <div class="p-4 bg-slate-800/50 rounded-2xl border border-slate-700/50">
                    <label class="text-sm font-semibold text-slate-400 uppercase tracking-wide mb-3 block">Assistance Needed</label>
                    
                    <div class="grid grid-cols-2 gap-3 mb-4">
                        <button type="button" class="help-btn p-3 rounded-xl bg-slate-800 border border-slate-600 flex flex-col items-center gap-2 transition-all hover:bg-slate-700 active:scale-95 active:bg-slate-600" onclick="toggleHelp(this, 'Medical')">
                            <i class="fa-solid fa-truck-medical text-xl text-red-400"></i>
                            <span class="text-xs font-bold text-slate-300">Medical</span>
                        </button>
                        
                        <button type="button" class="help-btn p-3 rounded-xl bg-slate-800 border border-slate-600 flex flex-col items-center gap-2 transition-all hover:bg-slate-700 active:scale-95 active:bg-slate-600" onclick="toggleHelp(this, 'Trapped')">
                            <i class="fa-solid fa-life-ring text-xl text-pink-500"></i>
                            <span class="text-xs font-bold text-slate-300">Trapped (SOS)</span>
                        </button>
                        
                        <button type="button" class="help-btn p-3 rounded-xl bg-slate-800 border border-slate-600 flex flex-col items-center gap-2 transition-all hover:bg-slate-700 active:scale-95 active:bg-slate-600" onclick="toggleHelp(this, 'Rescue')">
                            <i class="fa-solid fa-helicopter text-xl text-blue-400"></i>
                            <span class="text-xs font-bold text-slate-300">Rescue</span>
                        </button>
                        
                        <button type="button" class="help-btn p-3 rounded-xl bg-slate-800 border border-slate-600 flex flex-col items-center gap-2 transition-all hover:bg-slate-700 active:scale-95 active:bg-slate-600" onclick="toggleHelp(this, 'Supplies')">
                            <i class="fa-solid fa-box-open text-xl text-amber-200"></i>
                            <span class="text-xs font-bold text-slate-300">Supplies</span>
                        </button>
                    </div>

                    <label class="text-xs font-bold text-slate-500 uppercase mb-2 block">Headcount (Approx)</label>
                    <input type="number" id="headcount" value="1" min="1" max="500" step="1" inputmode="numeric" 
                           class="w-full bg-slate-900 border border-slate-700 rounded-lg p-3 text-white focus:border-blue-500 outline-none" placeholder="1-500">
                </div>

                <input type="hidden" id="lat" value="0">
                <input type="hidden" id="lng" value="0">

                <div class="space-y-2">
                    <label class="text-sm font-semibold text-slate-400 uppercase tracking-wide">Additional Details</label>
                    <textarea id="details" rows="2" class="w-full bg-slate-900 border border-slate-700 rounded-xl p-4 text-white focus:border-blue-500 outline-none resize-none placeholder-slate-600" placeholder="Describe the situation..."></textarea>
                </div>

                <div class="space-y-2">
                    <label class="text-sm font-semibold text-slate-400 uppercase tracking-wide">Photo Evidence</label>
                    
                    <input type="file" id="cameraInput" accept="image/*" capture="environment" class="hidden">
                    
                    <div id="camera-trigger" onclick="document.getElementById('cameraInput').click()" 
                         class="border-2 border-dashed border-slate-600 bg-slate-800/50 hover:bg-slate-800 rounded-2xl p-8 flex flex-col items-center justify-center gap-3 cursor-pointer transition-all group">
                        
                        <div class="w-12 h-12 rounded-full bg-slate-700 flex items-center justify-center group-hover:bg-blue-600 transition-colors">
                            <i class="fa-solid fa-camera text-xl text-white"></i>
                        </div>
                        <div class="text-center">
                            <p class="text-sm font-bold text-slate-300">Tap to Capture</p>
                            <p class="text-xs text-slate-500">JPG, PNG up to 5MB</p>
                        </div>
                    </div>

                    <div id="preview-area" class="hidden mt-3 relative rounded-xl overflow-hidden border border-slate-600">
                        <img id="preview-img" class="w-full h-48 object-cover">
                        <div class="absolute bottom-0 left-0 right-0 bg-black/60 p-2 text-center text-xs text-green-400 font-bold backdrop-blur-sm">
                            <i class="fa-solid fa-check-circle"></i> Image Compressed & Attached
                        </div>
                        <button type="button" onclick="clearImage()" class="absolute top-2 right-2 bg-red-600 text-white w-6 h-6 rounded-full text-xs shadow-lg">✕</button>
                    </div>
                </div>

                <button type="submit" class="w-full bg-gradient-to-r from-blue-600 to-blue-500 hover:from-blue-500 hover:to-blue-400 py-4 rounded-xl font-bold text-lg shadow-lg shadow-blue-900/30 flex items-center justify-center gap-2 transition-transform transform active:scale-95 border-t border-blue-400/20">
                    <i class="fa-solid fa-paper-plane"></i> SUBMIT REPORT
                </button>

                <button type="button" onclick="sendSMS()" class="w-full bg-slate-800 hover:bg-slate-700 py-3 rounded-xl font-bold text-sm text-slate-400 shadow-lg flex items-center justify-center gap-2 transition-transform transform active:scale-95 border border-slate-700">
                    <i class="fa-solid fa-comment-sms"></i> NO INTERNET? SEND VIA SMS
                </button>

            </form>

            <div id="status-feed" class="hidden mt-6 bg-slate-800/80 rounded-xl border border-slate-600 p-4 animate-fade-in">
                <h3 class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-3">Live Updates</h3>
                <div id="my-reports-list" class="space-y-2">
                    </div>
            </div>

            <div class="text-center pb-8 opacity-60">
                <p class="text-[10px] uppercase font-bold text-slate-500">Offline Queue</p>
                <p class="text-2xl font-black text-slate-400" id="queue-count">0</p>
            </div>
            
        </div>
    </div>

    <script src="app.js"></script>
    <script>
        // --- 1. GLOBAL LOGIC ---
        let activeHelp = [];

        function logout() {
            if(confirm("End Session?")) {
                localStorage.removeItem('aegis_auth');
                localStorage.removeItem('aegis_user');
                window.location.href = 'index.php';
            }
        }

        // --- 2. DATA PACKER (Update Hidden Input) ---
        function updateHiddenData() {
            const baseType = document.getElementById('type-select').value;
            const count = document.getElementById('headcount').value;
            const details = document.getElementById('details').value;
            
            let finalType = baseType;
            
            // Append Help Tags
            if(activeHelp.length > 0) {
                finalType += " [" + activeHelp.join(", ") + "]";
            }
            
            // Append Headcount
            if(count && count > 0) {
                finalType += " (" + count + " Pax)";
            }

            // Append Details (Optional, adds to string for Admin visibility)
            if(details && details.trim() !== "") {
                finalType += " -- " + details.replace(/[\(\)\[\]]/g, ''); // Sanitize brackets
            }
            
            // UPDATE HIDDEN INPUT
            document.getElementById('type').value = finalType;
        }

        // --- 3. UI INTERACTIONS ---
        
        // Severity Bubbles
        function setSeverity(val) {
            document.getElementById('severity').value = val;
            document.getElementById('sev-display').innerText = val;
            
            const btns = document.querySelectorAll('.severity-btn');
            btns.forEach(btn => {
                btn.className = "severity-btn w-10 h-10 rounded-full border border-slate-600 bg-slate-700 text-slate-300 font-bold transition-all flex items-center justify-center";
                if(parseInt(btn.innerText) === val) {
                    btn.className = "severity-btn w-10 h-10 rounded-full border-yellow-500/50 bg-yellow-500 text-black font-bold transition-all flex items-center justify-center active scale-110 shadow-[0_0_15px_rgba(234,179,8,0.3)]";
                }
            });
        }

        // Assistance Buttons
        function toggleHelp(btn, type) {
            if(activeHelp.includes(type)) {
                activeHelp = activeHelp.filter(i => i !== type);
                btn.classList.remove('active', 'bg-blue-600', 'border-blue-500');
                btn.classList.add('bg-slate-800', 'border-slate-600');
            } else {
                activeHelp.push(type);
                btn.classList.remove('bg-slate-800', 'border-slate-600');
                btn.classList.add('active', 'bg-blue-600', 'border-blue-500');
            }
            updateHiddenData();
        }

        // Inputs triggering updates
        document.getElementById('type-select').addEventListener('change', updateHiddenData);
        document.getElementById('headcount').addEventListener('input', updateHiddenData);
        document.getElementById('details').addEventListener('input', updateHiddenData);

        // --- 4. CAMERA LOGIC ---
        function clearImage() {
            document.getElementById('cameraInput').value = "";
            document.getElementById('preview-area').classList.add('hidden');
        }

        // --- 5. MANUAL SMS TRIGGER ---
        function sendSMS() {
            updateHiddenData(); // Ensure data is fresh
            const type = document.getElementById('type').value;
            const sev = document.getElementById('severity').value;
            const lat = document.getElementById('lat').value;
            const lng = document.getElementById('lng').value;
            
            const body = `SOS REPORT\n${type}\nSev: ${sev}\nLoc: ${lat},${lng}`;
            window.location.href = `sms:0771234567?body=${encodeURIComponent(body)}`;
        }

        // --- 6. LIVE STATUS CHECKER (Feedback Loop) ---
        async function checkMyReports() {
            if (!navigator.onLine) return; 
            
            const user = localStorage.getItem('aegis_user');
            if (!user) return;

            try {
                const res = await fetch(`my_status.php?user=${encodeURIComponent(user)}`);
                const reports = await res.json();
                
                const list = document.getElementById('my-reports-list');
                const feed = document.getElementById('status-feed');
                
                if (reports.length > 0) {
                    feed.classList.remove('hidden');
                    list.innerHTML = reports.map(r => {
                        let type = r.incident_type.replace(/\[.*?\]/, '').replace(/\(.*?\)/, '').trim();
                        // Truncate if too long
                        if(type.length > 20) type = type.substring(0, 20) + '...';

                        if (r.status === 'in_progress') {
                            return `
                                <div class="p-3 rounded-lg border border-orange-500/50 bg-orange-500/10 flex justify-between items-center shadow-lg shadow-orange-900/20">
                                    <span class="text-sm font-bold text-white">${type}</span>
                                    <span class="text-orange-400 font-black text-[10px] uppercase animate-pulse">🚀 Dispatched</span>
                                </div>`;
                        } else {
                            return `
                                <div class="p-3 rounded-lg border border-slate-700 bg-slate-900/50 flex justify-between items-center opacity-70">
                                    <span class="text-sm font-bold text-slate-400">${type}</span>
                                    <span class="text-blue-400 font-bold text-[10px] uppercase">Waiting...</span>
                                </div>`;
                        }
                    }).join('');
                } else {
                    feed.classList.add('hidden');
                }
            } catch (e) { console.log("Status check fail"); }
        }

        // Init
        updateHiddenData();
        setInterval(checkMyReports, 3000);
        checkMyReports();

        // Register SW
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => navigator.serviceWorker.register('/sw.js'));
        }
    </script>
</body>
</html>