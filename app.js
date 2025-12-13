// app.js - FINAL INTEGRATED VERSION

// --- GLOBAL VARIABLES ---
let compressedImageString = ""; // Stores base64 image
let activeHelp = []; // Stores assistance tags (e.g. ['Medical', 'Trapped'])

// --- SAFETY & FIRST AID DATA ---
const safetyContent = {
    "Landslide": { title: "Landslide Risk", msg: "Move to high ground immediately. Do not stay near the slide area to take photos. Listen for rumbling sounds." },
    "Flood": { title: "Flash Flood Warning", msg: "Climb to high ground. Do not walk, swim, or drive through flood waters. Turn around, don't drown." },
    "Road Blocked": { title: "Road obstruction", msg: "Do not attempt to clear heavy debris yourself. Warn approaching traffic if safe to do so." },
    "Power Down": { title: "Electrical Hazard", msg: "Stay at least 10 meters away. Do not touch anything touching the wire. Assume it is live." },
    "Fire": { title: "Fire Danger", msg: "Evacuate immediately. Stay low if there is smoke. Do not return for belongings." },
    "Medical": { title: "Medical Emergency", msg: "Keep the patient still and warm. Do not give food or water if surgery might be needed. Apply pressure to bleeding." },
    "Trapped": { title: "SOS Guidance", msg: "Conserve energy. Bang on pipes or walls rather than shouting to avoid inhaling dust. Cover your nose/mouth." },
    "Rescue": { title: "Rescue Team", msg: "Stay visible if possible. Use colorful cloth or light to signal. Stay clear of helicopter landing zones" }
};

// --- 1. AUTH LOGIC ---
const loginView = document.getElementById('login-view');
const appView = document.getElementById('app-view');
const currentUser = localStorage.getItem('aegis_user');

if (currentUser && loginView) {
    showApp(currentUser);
} else if (loginView) {
    loginView.classList.remove('hidden');
}

// Always check for badge display (for app.php)
if (currentUser && document.getElementById('display-badge')) {
    document.getElementById('display-badge').innerText = currentUser;
}

if (document.getElementById('login-form')) {
    document.getElementById('login-form').addEventListener('submit', (e) => {
        e.preventDefault();
        const badge = document.getElementById('badge-id').value;
        if (badge.length > 0) {
            localStorage.setItem('aegis_user', badge);
            showApp(badge);
        }
    });
}

function showApp(badge) {
    if (loginView) loginView.classList.add('hidden');
    if (appView) appView.classList.remove('hidden');
    if (document.getElementById('display-badge')) document.getElementById('display-badge').innerText = badge;
    // Trigger initial data pack
    updateHiddenData();
}

// Make logout global for HTML onclick
window.logout = function () {
    if (confirm("End session?")) {
        localStorage.removeItem('aegis_user');
        localStorage.removeItem('aegis_auth');
        window.location.href = 'index.php'; // Redirect to login
    }
};

// --- 2. UI LOGIC & DATA PACKER (THE FIX) ---
// This combines Dropdown + Buttons + Headcount into one string

function updateHiddenData() {
    const typeSelect = document.getElementById('type-select');
    const headcountInput = document.getElementById('headcount');
    const typeHidden = document.getElementById('type');

    // Safety check: if elements don't exist (e.g. on login screen), stop.
    if (!typeSelect || !typeHidden) return;

    let finalType = typeSelect.value;
    const count = headcountInput ? headcountInput.value : "";

    // Append Tags (e.g. " [Medical, Rescue]")
    if (activeHelp.length > 0) {
        finalType += " [" + activeHelp.join(", ") + "]";
    }

    // Append Details from Textarea if present
    const detailsInput = document.getElementById('details');
    if (detailsInput && detailsInput.value.trim() !== "") {
        finalType += " | Note: " + detailsInput.value.trim();
    }

    // Append Headcount (e.g. " (5 Pax)")
    if (count) {
        // Validation: Force Integer & Range 1-500
        let intCount = Math.floor(Number(count));

        // Strict Clamping
        if (intCount < 1) intCount = 1;
        if (intCount > 500) intCount = 500;

        // Visual Update if changed
        if (headcountInput.value != intCount) {
            headcountInput.value = intCount;
        }

        finalType += " (" + intCount + " Pax)";
    }

    // UPDATE HIDDEN INPUT IMMEDIATELY
    typeHidden.value = finalType;
}

// Global Functions for HTML onclick events
window.setSeverity = function (val) {
    const sevInput = document.getElementById('severity');
    const sevDisplay = document.getElementById('sev-display');

    if (sevInput) sevInput.value = val;
    if (sevDisplay) sevDisplay.innerText = val;

    // Visual Update for Bubbles
    const btns = document.querySelectorAll('.severity-btn');
    btns.forEach(btn => {
        btn.className = "severity-btn w-10 h-10 rounded-full border border-slate-600 bg-slate-700 text-slate-300 font-bold transition-all flex items-center justify-center";
        if (parseInt(btn.innerText) === val) {
            btn.className = "severity-btn w-10 h-10 rounded-full border-yellow-500/50 bg-yellow-500 text-black font-bold transition-all flex items-center justify-center active scale-110 shadow-[0_0_15px_rgba(234,179,8,0.3)]";
        }
    });
};

window.toggleHelp = function (btn, type) {
    if (activeHelp.includes(type)) {
        activeHelp = activeHelp.filter(i => i !== type);
        btn.classList.remove('active', 'bg-blue-600', 'border-blue-500');
        btn.classList.add('bg-slate-800', 'border-slate-600');
    } else {
        activeHelp.push(type);
        btn.classList.remove('bg-slate-800', 'border-slate-600');
        btn.classList.add('active', 'bg-blue-600', 'border-blue-500');
    }
    updateHiddenData(); // Update hidden input immediately
    updateSafetyCard(); // Update Safety Card based on active tags
};

// Update Safety Card Logic
function updateSafetyCard() {
    const typeSelect = document.getElementById('type-select');
    const safetyAlert = document.getElementById('safety-alert');
    if (!typeSelect || !safetyAlert) return;

    const currentType = typeSelect.value;

    // Determine which message to show
    // Priority: Medical/Trapped (Life Safety) -> Incident Type Default
    let content = null;

    if (activeHelp.includes('Medical')) content = safetyContent['Medical'];
    else if (activeHelp.includes('Trapped')) content = safetyContent['Trapped'];
    else if (activeHelp.includes('Rescue')) content = safetyContent['Rescue'];
    else if (safetyContent[currentType]) content = safetyContent[currentType];

    // Update UI
    if (content) {
        document.getElementById('safety-title').innerText = content.title;
        document.getElementById('safety-msg').innerText = content.msg;
        safetyAlert.classList.remove('hidden');
    } else {
        safetyAlert.classList.add('hidden');
    }
}

// --- SMS FALLBACK ---
window.sendSMS = function () {
    // 1. Gather Data
    const type = document.getElementById('type-select').value;
    const severity = document.getElementById('severity').value;
    const details = document.getElementById('details').value;
    const lat = document.getElementById('lat').value;
    const lng = document.getElementById('lng').value;
    const badge = localStorage.getItem('aegis_user') || "Unknown";

    // 2. Construct Message
    let msg = `SOS REPORT\nID: ${badge}\nType: ${type}`;

    if (activeHelp.length > 0) msg += `\nNeed: ${activeHelp.join(', ')}`;
    msg += `\nSev: ${severity}`;
    if (details) msg += `\nNote: ${details}`;

    // location
    if (lat != 0 && lng != 0) {
        msg += `\nLoc: ${lat},${lng}`;
        msg += `\nMaps: http://maps.google.com/?q=${lat},${lng}`;
    } else {
        msg += `\nLoc: No GPS Signal`;
    }

    // 3. Open SMS App (Universal Link)
    // Replace 119 or local urgency number
    const phone = "0771234567"; // Change this to Admin Number

    // Detect iOS vs Android for separator
    const separator = navigator.userAgent.match(/iPhone|iPad|iPod/i) ? '&' : '?';

    window.location.href = `sms:${phone}${separator}body=${encodeURIComponent(msg)}`;
};

window.clearImage = function () {
    document.getElementById('cameraInput').value = "";
    document.getElementById('preview-area').classList.add('hidden');
    document.getElementById('camera-trigger').classList.remove('hidden'); // <--- ACTION: SHOW TRIGGER
    compressedImageString = "";
};

// Add Event Listeners for Real-time typing
document.addEventListener('DOMContentLoaded', () => {
    const typeSelect = document.getElementById('type-select');
    const headcountInput = document.getElementById('headcount');

    if (typeSelect) {
        typeSelect.addEventListener('change', () => {
            updateHiddenData();
            updateSafetyCard();
        });
    }
    if (headcountInput) headcountInput.addEventListener('input', updateHiddenData);

    // Details Listener
    const detailsInput = document.getElementById('details');
    if (detailsInput) detailsInput.addEventListener('input', updateHiddenData);

    // Initial Safety Card Load
    updateSafetyCard();
});

// --- 3. CAMERA & COMPRESSION ---
if (document.getElementById('cameraInput')) {
    document.getElementById('cameraInput').addEventListener('change', function (e) {
        const file = e.target.files[0];
        if (!file) return;

        const reader = new FileReader();
        reader.readAsDataURL(file);

        reader.onload = function (event) {
            const img = new Image();
            img.src = event.target.result;

            img.onload = function () {
                const canvas = document.createElement('canvas');
                const ctx = canvas.getContext('2d');

                // FORCE RESIZE (Max 500px)
                const MAX_WIDTH = 500;
                const scaleSize = MAX_WIDTH / img.width;
                canvas.width = MAX_WIDTH;
                canvas.height = img.height * scaleSize;

                ctx.drawImage(img, 0, 0, canvas.width, canvas.height);

                // COMPRESS to JPEG 50%
                compressedImageString = canvas.toDataURL('image/jpeg', 0.5);

                // Show Preview & Hide Trigger
                document.getElementById('preview-img').src = compressedImageString;
                document.getElementById('preview-area').classList.remove('hidden');
                document.getElementById('camera-trigger').classList.add('hidden'); // <--- ACTION: HIDE TRIGGER
            }
        }
    });
}

// --- 4. FORM SUBMISSION ---
const form = document.getElementById('incident-form');
if (form) {
    form.addEventListener('submit', (e) => {
        e.preventDefault();

        // Final data sync before sending (Just in case)
        updateHiddenData();

        const report = {
            uuid: crypto.randomUUID(),
            username: localStorage.getItem('aegis_user') || 'Unknown', // <--- FIXED: SENDS USERNAME NOW
            type: document.getElementById('type').value, // This now contains the packed data
            severity: document.getElementById('severity').value,
            lat: document.getElementById('lat').value,
            lng: document.getElementById('lng').value,
            timestamp: new Date().toISOString(),
            image: compressedImageString || "" // Send empty string if no image
        };

        let queue = JSON.parse(localStorage.getItem('aegis_queue') || "[]");
        queue.push(report);
        localStorage.setItem('aegis_queue', JSON.stringify(queue));

        alert("Report Saved!");

        // Reset UI
        form.reset();
        document.getElementById('preview-area').classList.add('hidden');
        document.getElementById('camera-trigger').classList.remove('hidden'); // <--- ACTION: SHOW TRIGGER
        compressedImageString = "";
        activeHelp = []; // Clear help array
        // Reset Help Buttons Visuals
        document.querySelectorAll('.help-btn').forEach(btn => {
            btn.classList.remove('active', 'bg-blue-600', 'border-blue-500');
            btn.classList.add('bg-slate-800', 'border-slate-600');
        });
        // Reset Severity Visuals
        window.setSeverity(3);

        updateQueueUI();
        if (navigator.onLine) trySync();
    });
}

// --- 5. GPS & SYNC ---
navigator.geolocation.getCurrentPosition(
    pos => {
        if (document.getElementById('lat')) {
            document.getElementById('lat').value = pos.coords.latitude;
            document.getElementById('lng').value = pos.coords.longitude;
        }
    },
    err => console.log("GPS Fail", err),
    { enableHighAccuracy: true }
);

async function trySync() {
    let queue = JSON.parse(localStorage.getItem('aegis_queue') || "[]");
    if (queue.length === 0) return;

    const bar = document.getElementById('status-bar');
    if (bar) bar.textContent = "UPLOADING...";

    try {
        let res = await fetch('sync.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(queue)
        });

        // PARSE THE JSON
        const data = await res.json();

        if (data.status === 'success') {
            localStorage.setItem('aegis_queue', "[]");
            updateQueueUI();
            updateStatus();
        } else {
            console.error("Server Error:", data);
            alert("⚠️ Upload Failed: " + (data.message || "Unknown Server Error"));
        }

    } catch (e) {
        console.log("Sync failed.", e);
        updateStatus();
    }
}

function updateStatus() {
    const bar = document.getElementById('status-bar');
    if (!bar) return;
    if (navigator.onLine) {
        bar.textContent = "ONLINE - SYNC READY";
        bar.className = "text-[10px] font-bold text-center py-1 bg-green-900 text-green-400 uppercase tracking-widest rounded-b-[3rem] pb-3 pt-12 -mt-10 shadow-md transition-all duration-300 z-0";
        trySync();
    } else {
        bar.textContent = "OFFLINE - SAVING LOCALLY";
        bar.className = "text-[10px] font-bold text-center py-1 bg-red-900 text-red-400 uppercase tracking-widest rounded-b-[3rem] pb-3 pt-12 -mt-10 shadow-md transition-all duration-300 z-0";
    }
}

function updateQueueUI() {
    let queue = JSON.parse(localStorage.getItem('aegis_queue') || "[]");
    if (document.getElementById('queue-count')) {
        document.getElementById('queue-count').innerText = queue.length;
    }
}

window.addEventListener('online', updateStatus);
window.addEventListener('offline', updateStatus);
window.addEventListener('load', () => { updateStatus(); updateQueueUI(); });

// --- 6. LIVE STATUS CHECKER (The Feedback Loop) ---
async function checkMyReports() {
    // 1. Basic Checks: Online? User logged in?
    if (!navigator.onLine) return;
    const user = localStorage.getItem('aegis_user');
    if (!user) return;

    try {
        // 2. Ask Server: "What is the status of my reports?"
        const res = await fetch(`my_status.php?user=${encodeURIComponent(user)}`);
        const reports = await res.json();

        const list = document.getElementById('my-reports-list');
        const feed = document.getElementById('status-feed');

        if (!list || !feed) return; // Safety check if elements are missing

        if (reports.length > 0) {
            feed.classList.remove('hidden'); // Show the box
            
            list.innerHTML = reports.map(r => {
                // Clean up the text (remove tags like [Medical] for cleaner display)
                let type = r.incident_type.replace(/\[.*?\]/, '').replace(/\(.*?\)/, '').trim();
                if(type.length > 20) type = type.substring(0, 20) + '...';

                // 3. DECIDE STATUS DISPLAY
                if (r.status === 'in_progress') {
                    // CASE A: Admin clicked "Dispatch" -> Show Orange Rocket
                    return `
                        <div class="p-3 rounded-lg border border-orange-500/50 bg-orange-500/10 flex justify-between items-center shadow-lg shadow-orange-900/20 transition-all duration-500">
                            <span class="text-sm font-bold text-white">${type}</span>
                            <span class="text-orange-400 font-black text-[10px] uppercase animate-pulse">🚀 Team Dispatched</span>
                        </div>`;
                } else {
                    // CASE B: Just submitted -> Show "Received"
                    return `
                        <div class="p-3 rounded-lg border border-slate-700 bg-slate-900/50 flex justify-between items-center opacity-70">
                            <span class="text-sm font-bold text-slate-400">${type}</span>
                            <span class="text-blue-400 font-bold text-[10px] uppercase">✔ Received</span>
                        </div>`;
                }
            }).join('');
        } else {
            feed.classList.add('hidden'); // Hide box if no active reports
        }
    } catch (e) { 
        console.log("Status check fail - retrying in 3s"); 
    }
}

// 4. Start the Loop
setInterval(checkMyReports, 3000); // Check every 3 seconds
checkMyReports(); // Check immediately on load