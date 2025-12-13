// app.js

// 1. GPS Tracking
navigator.geolocation.getCurrentPosition(
    pos => { 
        document.getElementById('lat').value = pos.coords.latitude;
        document.getElementById('lng').value = pos.coords.longitude;
    },
    err => console.log("GPS Fail", err),
    { enableHighAccuracy: true }
);

// 2. Offline Detection
function updateStatus() {
    const bar = document.getElementById('status-bar');
    if (navigator.onLine) {
        bar.textContent = "ONLINE - SYNCING...";
        bar.className = "p-3 text-center font-bold text-sm bg-green-600";
        trySync();
    } else {
        bar.textContent = "OFFLINE - SAVING LOCALLY";
        bar.className = "p-3 text-center font-bold text-sm bg-red-600";
    }
}
window.addEventListener('online', updateStatus);
window.addEventListener('offline', updateStatus);

// 3. Save to LocalStorage
document.getElementById('incident-form').addEventListener('submit', (e) => {
    e.preventDefault();
    
    const report = {
        uuid: crypto.randomUUID(),
        type: document.getElementById('type').value,
        severity: document.getElementById('severity').value,
        lat: document.getElementById('lat').value,
        lng: document.getElementById('lng').value,
        timestamp: new Date().toISOString()
    };

    let queue = JSON.parse(localStorage.getItem('aegis_queue') || "[]");
    queue.push(report);
    localStorage.setItem('aegis_queue', JSON.stringify(queue));
    
    alert("Saved!");
    updateQueueUI();
    if(navigator.onLine) trySync();
});

// 4. Sync to Server
async function trySync() {
    let queue = JSON.parse(localStorage.getItem('aegis_queue') || "[]");
    if (queue.length === 0) return;

    try {
        let res = await fetch('sync.php', {
            method: 'POST',
            body: JSON.stringify(queue)
        });
        if (res.ok) {
            localStorage.setItem('aegis_queue', "[]");
            updateQueueUI();
            console.log("Synced!");
        }
    } catch (e) { console.log("Sync failed, retrying later."); }
}

function updateQueueUI() {
    let queue = JSON.parse(localStorage.getItem('aegis_queue') || "[]");
    document.getElementById('queue-count').innerText = queue.length;
}

// Init
updateStatus();
updateQueueUI();