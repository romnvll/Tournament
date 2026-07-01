// ─── Synchronisation horloge client/serveur ───────────────────────────────
let _serverOffset = 0; // ms entre Date.now() client et heure serveur

async function syncServerTime() {
    try {
        const t0 = Date.now();
        const r  = await fetch('./serverTime.php?_=' + t0);
        const t1 = Date.now();
        const { serverTime } = await r.json();

        // Latence aller-retour divisée par 2 = latence estimée
        const latence = Math.round((t1 - t0) / 2);
        _serverOffset = (serverTime + latence) - t1;

        console.log(`[syncTime] offset=${_serverOffset}ms latence=${latence}ms`);
    } catch (e) {
        console.warn('[syncTime] échec, offset=0', e);
    }
}

// Retourne Date.now() corrigé par l'offset serveur
function serverNow() {
    return Date.now() + _serverOffset;
}

// Synchroniser au chargement, puis toutes les 5 minutes
syncServerTime();
setInterval(syncServerTime, 5 * 60 * 1000);
// ─────────────────────────────────────────────────────────────────────────────