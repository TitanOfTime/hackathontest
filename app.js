// app.js - FINAL INTEGRATED VERSION

// --- GLOBAL VARIABLES ---
let compressedImageString = ""; // Stores base64 image
let activeHelp = []; // Stores assistance tags (e.g. ['Medical', 'Trapped'])

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
    loginView.classList.add('hidden');
    appView.classList.remove('hidden');
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
    console.log("Data Ready to Send:", finalType);
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

    if (typeSelect) typeSelect.addEventListener('change', updateHiddenData);
    if (headcountInput) headcountInput.addEventListener('input', updateHiddenData);
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

        if (res.ok) {
            localStorage.setItem('aegis_queue', "[]");
            updateQueueUI();
            updateStatus();
        }
    } catch (e) {
        console.log("Sync failed.");
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