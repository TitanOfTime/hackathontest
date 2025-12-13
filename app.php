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
    </style>
    <script>
        // SECURITY CHECK: Redirect to login if no auth token found
        if (!localStorage.getItem('aegis_auth')) {
            window.location.href = 'index.php';
        } else {
            // Reveal App only if authorized
            document.addEventListener('DOMContentLoaded', () => {
                document.getElementById('app-view').classList.remove('hidden');
            });
        }
    </script>
</head>
<body class="bg-[#0f172a] text-slate-200 min-h-screen pb-20">

    <div id="app-view" class="hidden max-w-lg mx-auto min-h-screen flex flex-col relative">
        
        <div class="bg-gradient-to-br from-slate-900 via-blue-900 to-slate-900 p-8 pb-10 rounded-b-[3rem] shadow-2xl z-10 relative overflow-hidden border-b border-white/10">
            <!-- decorative bg -->
            <div class="absolute -top-10 -right-10 w-64 h-64 bg-blue-500/20 rounded-full blur-3xl pointer-events-none"></div>
            <div class="absolute top-10 -left-10 w-40 h-40 bg-indigo-500/20 rounded-full blur-2xl pointer-events-none"></div>

            <div class="flex justify-between items-start relative z-10">
                <div>
                    <h1 class="text-4xl font-black text-white tracking-tighter drop-shadow-sm">Incidents</h1>
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
                    <!-- Dynamic Safety Card -->
                    <div id="safety-alert" class="hidden animate-fade-in mt-2">
                        <div class="bg-red-500/10 border border-red-500/20 rounded-xl p-4 flex gap-3 text-red-100">
                            <div class="pt-1"><i class="fa-solid fa-triangle-exclamation text-red-500"></i></div>
                            <div class="text-sm">
                                <p class="font-bold text-red-400 mb-1" id="safety-title">Safety Warning</p>
                                <p id="safety-msg" class="opacity-90 leading-relaxed"></p>
                            </div>
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
                    <input type="number" id="headcount" value="1" min="1" max="500" step="1" inputmode="numeric" pattern="[0-9]*" 
                           onkeydown="if(['.', 'e', 'E', '-'].includes(event.key)) event.preventDefault()"
                           oninput="this.value = this.value.replace(/[^0-9]/g, ''); if(this.value > 500) this.value = 500; if(this.value && this.value < 1) this.value = 1;" 
                           class="w-full bg-slate-900 border border-slate-700 rounded-lg p-3 text-white focus:border-blue-500 outline-none" placeholder="1-500">
                </div>

                <input type="hidden" id="lat" value="0">
                <input type="hidden" id="lng" value="0">

                <div class="space-y-2">
                    <label class="text-sm font-semibold text-slate-400 uppercase tracking-wide">Additional Details</label>
                    <textarea id="details" rows="3" class="w-full bg-slate-900 border border-slate-700 rounded-xl p-4 text-white focus:border-blue-500 outline-none resize-none placeholder-slate-600" placeholder="Describe the situation, blocked paths, or specific injuries..."></textarea>
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

            <div class="text-center pb-8 opacity-60">
                <p class="text-[10px] uppercase font-bold text-slate-500">Offline Queue</p>
                <p class="text-2xl font-black text-slate-400" id="queue-count">0</p>
            </div>
            
        </div>
    </div>

    <script src="app.js"></script>
    <script>
        // Register Service Worker
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => navigator.serviceWorker.register('/sw.js'));
        }
    </script>
</body>
</html>