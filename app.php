<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Aegis Responder</title>
    <link rel="manifest" href="manifest.json">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; touch-action: manipulation; }
        .severity-btn.active { background-color: #fbbf24; color: black; border-color: #fbbf24; transform: scale(1.1); }
        .help-btn.active { background-color: #2563eb; border-color: #2563eb; color: white; }
    </style>
    <script>
        // SECURITY CHECK: Redirect to login if no auth token found
        if (!localStorage.getItem('aegis_auth')) {
            window.location.href = 'index.php';
        }
    </script>
</head>
<body class="bg-[#0f172a] text-slate-200 min-h-screen pb-20">

    <div id="app-view" class="max-w-lg mx-auto min-h-screen flex flex-col relative">
        
        <div class="bg-blue-600 p-6 pt-8 rounded-b-3xl shadow-2xl z-10 relative overflow-hidden">
            <div class="absolute top-0 right-0 p-4 opacity-10">
                <i class="fa-solid fa-shield-halved text-9xl"></i>
            </div>
            <div class="flex justify-between items-start relative z-10">
                <div>
                    <h1 class="text-3xl font-extrabold text-white tracking-tight">Report Incident</h1>
                    <p class="text-blue-100 text-sm mt-1 opacity-90">Help us respond faster.</p>
                </div>
                <button onclick="logout()" class="bg-blue-700/50 hover:bg-blue-700 text-white p-2 rounded-lg backdrop-blur-sm transition">
                    <i class="fa-solid fa-power-off"></i>
                </button>
            </div>
            
            <div class="mt-4 flex items-center gap-2 text-xs font-bold text-blue-200 bg-blue-800/40 w-fit px-3 py-1 rounded-full border border-blue-400/30 backdrop-blur-md">
                <div class="w-2 h-2 rounded-full bg-green-400 animate-pulse"></div>
                ID: <span id="display-badge">Loading...</span>
            </div>
        </div>

        <div id="status-bar" class="text-[10px] font-bold text-center py-1 bg-slate-800 text-slate-500 uppercase tracking-widest">
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
                        <button type="button" class="help-btn p-3 rounded-xl bg-slate-800 border border-slate-600 flex flex-col items-center gap-2 transition-all hover:bg-slate-700" onclick="toggleHelp(this, 'Medical')">
                            <i class="fa-solid fa-truck-medical text-xl text-red-400"></i>
                            <span class="text-xs font-bold text-slate-300">Medical</span>
                        </button>
                        
                        <button type="button" class="help-btn p-3 rounded-xl bg-slate-800 border border-slate-600 flex flex-col items-center gap-2 transition-all hover:bg-slate-700" onclick="toggleHelp(this, 'Trapped')">
                            <i class="fa-solid fa-life-ring text-xl text-pink-500"></i>
                            <span class="text-xs font-bold text-slate-300">Trapped (SOS)</span>
                        </button>
                        
                        <button type="button" class="help-btn p-3 rounded-xl bg-slate-800 border border-slate-600 flex flex-col items-center gap-2 transition-all hover:bg-slate-700" onclick="toggleHelp(this, 'Rescue')">
                            <i class="fa-solid fa-helicopter text-xl text-blue-400"></i>
                            <span class="text-xs font-bold text-slate-300">Rescue</span>
                        </button>
                        
                        <button type="button" class="help-btn p-3 rounded-xl bg-slate-800 border border-slate-600 flex flex-col items-center gap-2 transition-all hover:bg-slate-700" onclick="toggleHelp(this, 'Supplies')">
                            <i class="fa-solid fa-box-open text-xl text-amber-200"></i>
                            <span class="text-xs font-bold text-slate-300">Supplies</span>
                        </button>
                    </div>

                    <label class="text-xs font-bold text-slate-500 uppercase mb-2 block">Headcount (Approx)</label>
                    <input type="number" id="headcount" class="w-full bg-slate-900 border border-slate-700 rounded-lg p-3 text-white focus:border-blue-500 outline-none" placeholder="0">
                </div>

                <input type="hidden" id="lat" value="0">
                <input type="hidden" id="lng" value="0">

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

            </form>

            <div class="text-center pb-8 opacity-60">
                <p class="text-[10px] uppercase font-bold text-slate-500">Offline Queue</p>
                <p class="text-2xl font-black text-slate-400" id="queue-count">0</p>
            </div>
            
        </div>
    </div>

    <script src="app.js"></script>
    <script>
        // --- INLINE UI LOGIC ---

        // 1. FIX: Display ID on Load
        document.addEventListener('DOMContentLoaded', () => {
            const user = localStorage.getItem('aegis_user');
            if(document.getElementById('display-badge')) {
                document.getElementById('display-badge').innerText = user || 'Unknown';
            }
        });

        // 2. FIX: Logout Redirect
        function logout() {
            if(confirm("End Session?")) {
                localStorage.removeItem('aegis_auth');
                localStorage.removeItem('aegis_user');
                // Force Redirect to Index
                window.location.href = 'index.php';
            }
        }
        
        // --- REAL-TIME DATA PACKER ---
        let activeHelp = []; 

        function updateHiddenData() {
            const baseType = document.getElementById('type-select').value;
            const count = document.getElementById('headcount').value;
            
            let finalType = baseType;
            
            // Append Tags
            if(activeHelp.length > 0) {
                finalType += " [" + activeHelp.join(", ") + "]";
            }
            
            // Append Headcount
            if(count && count > 0) {
                finalType += " (" + count + " Pax)";
            }
            
            // UPDATE HIDDEN INPUT
            document.getElementById('type').value = finalType;
        }

        // --- EVENTS ---
        document.getElementById('type-select').addEventListener('change', updateHiddenData);
        document.getElementById('headcount').addEventListener('input', updateHiddenData);

        // Severity
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

        // Assistance
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

        // Image
        function clearImage() {
            document.getElementById('cameraInput').value = "";
            document.getElementById('preview-area').classList.add('hidden');
        }

        // Init
        updateHiddenData();
        // Register SW
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => navigator.serviceWorker.register('/sw.js'));
        }
    </script>
</body>
</html>