'use strict';

/* =========================================================================
   LocateMe - página del niño: comparte su ubicación y tiene un botón SOS
   ========================================================================= */

const TOKEN_KEY = 'locateme_device_token';
const SEND_EVERY_MS = 60000;   // envío periódico aunque no se mueva
const MOVE_METERS = 25;        // o antes, si se movió esta distancia...
const MIN_GAP_MS = 5000;       // ...pero nunca más seguido que esto
const CHECK_MS = 5000;
const SOS_HOLD_MS = 1500;

const ICONS = {
  pin: '<path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/>',
  pinOff: '<path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><path d="m3 3 18 18"/>',
  check: '<path d="M20 6 9 17l-5-5"/>',
  alert: '<path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><path d="M12 9v4M12 17h.01"/>',
  wifiOff: '<path d="M12 20h.01M8.5 16.43a5 5 0 0 1 7 0M5 12.86a10 10 0 0 1 5.17-2.69M19 12.86a10 10 0 0 0-2-1.43M2 8.82a15 15 0 0 1 4.18-2.64M22 8.82a15 15 0 0 0-11.29-3.76"/><path d="m2 2 20 20"/>',
  link: '<path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/>',
};

let token = null;
let watchId = null;
let lastPosition = null;
let lastSent = null;
let battery = null;
let sending = false;
let checkTimer = null;

const $ = (selector) => document.querySelector(selector);

function icon(name) {
  const wrap = document.createElement('span');
  wrap.innerHTML = `<svg class="icon" viewBox="0 0 24 24" aria-hidden="true">${ICONS[name]}</svg>`;
  return wrap.firstChild;
}

const sleep = (ms) => new Promise((resolve) => setTimeout(resolve, ms));
const relative = new Intl.RelativeTimeFormat('es', { numeric: 'auto' });

function distanceMeters(a, b) {
  const rad = (deg) => deg * Math.PI / 180;
  const dLat = rad(b.latitude - a.latitude);
  const dLng = rad(b.longitude - a.longitude);
  const x = Math.sin(dLat / 2) ** 2 + Math.cos(rad(a.latitude)) * Math.cos(rad(b.latitude)) * Math.sin(dLng / 2) ** 2;
  return 2 * 6371000 * Math.asin(Math.sqrt(x));
}

/* ---------- Token del enlace ---------- */

function readToken() {
  const match = location.hash.match(/(?:^#|&)t=([A-Za-z0-9_-]{20,100})/);
  if (match) {
    try {
      localStorage.setItem(TOKEN_KEY, match[1]);
      // Quitar el token de la barra de direcciones una vez guardado
      history.replaceState(null, '', location.pathname + location.search);
    } catch { /* sin almacenamiento: el token queda sólo en la URL */ }
    return match[1];
  }
  try {
    return localStorage.getItem(TOKEN_KEY);
  } catch {
    return null;
  }
}

function forgetToken() {
  token = null;
  try { localStorage.removeItem(TOKEN_KEY); } catch { /* nada que borrar */ }
}

async function api(method, path, body) {
  const response = await fetch('api/' + path, {
    method,
    cache: 'no-store',
    headers: {
      Accept: 'application/json',
      Authorization: 'Bearer ' + token,
      ...(body ? { 'Content-Type': 'application/json' } : {}),
    },
    body: body ? JSON.stringify(body) : undefined,
  });
  if (!response.ok) {
    const error = new Error('HTTP ' + response.status);
    error.status = response.status;
    throw error;
  }
  return response.json();
}

/* ---------- Pantalla ---------- */

function setStatus({ tone, title, text = '', iconName, live = false }) {
  const radar = $('#radar');
  radar.dataset.tone = tone;
  radar.classList.toggle('is-live', live);
  $('#radar-icon').replaceChildren(icon(iconName));
  $('#status-title').textContent = title;
  $('#status-text').textContent = text;
  $('#status-text').hidden = !text;
}

function renderLast() {
  const el = $('#status-last');
  if (!lastSent) {
    el.hidden = true;
    return;
  }
  const seconds = Math.round((lastSent.at - Date.now()) / 1000);
  el.textContent = 'Último envío: ' + (seconds > -45 ? 'justo ahora' : relative.format(Math.round(seconds / 60), 'minute'));
  el.hidden = false;
}

function greet(child) {
  $('#kid-page').style.setProperty('--kid', child.color);
  $('#kid-avatar').textContent = ([...child.name.trim()][0] || '·').toUpperCase();
  $('#kid-title').textContent = `¡Hola, ${child.name}!`;
}

function showUnlinked(title, text) {
  stopWatching();
  $('#sos-btn').hidden = true;
  $('#start-btn').hidden = true;
  $('#kid-tip').hidden = true;
  $('#status-last').hidden = true;
  setStatus({ tone: 'off', title, text, iconName: 'link' });
}

function showSharing() {
  setStatus({ tone: 'ok', title: 'Tu familia puede ver dónde estás', iconName: 'check', live: true });
  renderLast();
}

/* ---------- Ubicación ---------- */

function startWatching() {
  if (watchId !== null) return;
  $('#start-btn').hidden = true;
  $('#kid-tip').hidden = false;
  setStatus({ tone: 'waiting', title: 'Buscando tu ubicación…', text: 'Esto puede tardar unos segundos.', iconName: 'pin', live: true });

  watchId = navigator.geolocation.watchPosition(onPosition, onPositionError, {
    enableHighAccuracy: true,
    maximumAge: 10000,
    timeout: 30000,
  });
  checkTimer = setInterval(sendIfNeeded, CHECK_MS);
}

function stopWatching() {
  if (watchId !== null) navigator.geolocation.clearWatch(watchId);
  clearInterval(checkTimer);
  watchId = null;
}

function onPosition(position) {
  lastPosition = position;
  sendIfNeeded();
}

// Se revisa con cada lectura del GPS y también cada pocos segundos, para que un
// movimiento que llegó justo después de un envío no se quede sin mandar
function sendIfNeeded() {
  renderLast();
  if (!lastPosition) return;
  if (!lastSent) {
    send();
    return;
  }

  const elapsed = Date.now() - lastSent.at;
  if (elapsed >= SEND_EVERY_MS || (elapsed >= MIN_GAP_MS && distanceMeters(lastSent, lastPosition.coords) >= MOVE_METERS)) {
    send();
  }
}

function onPositionError(error) {
  if (error.code === error.PERMISSION_DENIED) {
    stopWatching();
    setStatus({
      tone: 'error',
      title: 'La ubicación está bloqueada',
      text: 'Abre los ajustes del navegador, busca «Ubicación», permítela para esta página y vuelve a cargarla.',
      iconName: 'pinOff',
    });
    return;
  }
  // Sin señal o tardó demasiado: watchPosition sigue intentando por su cuenta
  if (!lastSent) {
    setStatus({
      tone: 'waiting',
      title: 'Buscando señal GPS…',
      text: 'Si estás dentro de un edificio, acércate a una ventana.',
      iconName: 'pin',
      live: true,
    });
  }
}

async function send(sos = false) {
  if (!token || (sending && !sos)) return false;
  sending = true;

  const coords = lastPosition && lastPosition.coords;
  const body = {};
  if (sos) body.sos = true;
  if (coords) {
    body.latitude = Number(coords.latitude.toFixed(6));
    body.longitude = Number(coords.longitude.toFixed(6));
    body.accuracy = Math.round(coords.accuracy);
  }
  if (battery) body.battery = Math.round(battery.level * 100);

  try {
    await api('POST', 'locations', body);
    if (coords) lastSent = { at: Date.now(), latitude: coords.latitude, longitude: coords.longitude };
    if (coords) showSharing();
    return true;
  } catch (error) {
    if (error.status === 401) {
      forgetToken();
      showUnlinked('Este enlace ya no funciona', 'Pide a tu papá o mamá un enlace nuevo desde «Vincular».');
    } else {
      setStatus({ tone: 'error', title: 'Sin internet', text: 'Tu ubicación se enviará en cuanto vuelva la conexión.', iconName: 'wifiOff' });
    }
    return false;
  } finally {
    sending = false;
  }
}

/* ---------- Botón SOS (mantener presionado para evitar toques accidentales) ---------- */

function setupSos() {
  const button = $('#sos-btn');
  const help = $('#sos-help');
  const title = button.querySelector('strong');
  let timer = null;
  let busy = false;

  button.style.setProperty('--hold-ms', SOS_HOLD_MS + 'ms');

  const begin = (event) => {
    event.preventDefault();
    if (busy) return;
    button.classList.add('is-holding');
    if (navigator.vibrate) navigator.vibrate(30);
    timer = setTimeout(fire, SOS_HOLD_MS);
  };
  const end = () => {
    clearTimeout(timer);
    button.classList.remove('is-holding');
  };

  async function fire() {
    busy = true;
    button.classList.remove('is-holding');
    if (navigator.vibrate) navigator.vibrate([200, 100, 200]);
    help.textContent = 'Enviando alerta…';

    // Si todavía no compartía su ubicación, empezar ahora para que llegue con la alerta
    if (watchId === null) startWatching();

    while (!(await send(true))) {
      if (!token) return;
      help.textContent = 'Sin internet. Reintentando…';
      await sleep(5000);
    }

    button.classList.add('is-sent');
    title.textContent = '✓';
    help.textContent = 'Avisamos a tu familia. Si puedes, quédate en un lugar seguro.';

    setTimeout(() => {
      button.classList.remove('is-sent');
      title.textContent = 'SOS';
      help.textContent = 'Mantén presionado si necesitas ayuda';
      busy = false;
    }, 60000);
  }

  button.addEventListener('pointerdown', begin);
  ['pointerup', 'pointerleave', 'pointercancel'].forEach((type) => button.addEventListener(type, end));
  button.addEventListener('contextmenu', (event) => event.preventDefault());
  button.addEventListener('keydown', (event) => {
    if ((event.key === 'Enter' || event.key === ' ') && !event.repeat) begin(event);
  });
  button.addEventListener('keyup', (event) => {
    if (event.key === 'Enter' || event.key === ' ') end();
  });
}

/* ---------- Inicio ---------- */

async function start() {
  $('#kid-avatar').replaceChildren(icon('pin'));
  token = readToken();
  if (!token) {
    showUnlinked('Este teléfono aún no está vinculado', 'Pide a tu papá o mamá que abra LocateMe y te muestre el código de «Vincular».');
    return;
  }

  try {
    const { child } = await api('GET', 'device');
    greet(child);
  } catch (error) {
    if (error.status === 401) {
      forgetToken();
      showUnlinked('Este enlace ya no funciona', 'Pide a tu papá o mamá un enlace nuevo desde «Vincular».');
      return;
    }
    // Sin conexión: seguimos, los envíos se reintentan solos
  }

  if (!('geolocation' in navigator) || !window.isSecureContext) {
    setStatus({
      tone: 'error',
      title: 'No se puede compartir la ubicación',
      text: window.isSecureContext ? 'Abre este enlace en Chrome o Safari.' : 'La página debe abrirse con https://',
      iconName: 'alert',
    });
    return;
  }

  $('#sos-btn').hidden = false;
  setupSos();
  if (navigator.getBattery) navigator.getBattery().then((b) => { battery = b; }).catch(() => {});

  let permission = null;
  try {
    permission = await navigator.permissions.query({ name: 'geolocation' });
  } catch { /* Safari antiguo no tiene la API de permisos */ }

  if (permission && permission.state === 'granted') {
    startWatching();
  } else {
    setStatus({
      tone: 'waiting',
      title: 'Comparte tu ubicación',
      text: 'Toca el botón y luego «Permitir» para que tu familia sepa dónde estás.',
      iconName: 'pin',
    });
    $('#start-btn').hidden = false;
  }

  document.addEventListener('visibilitychange', () => {
    if (!document.hidden && lastPosition) send();
  });
}

$('#start-btn').addEventListener('click', startWatching);
start();
