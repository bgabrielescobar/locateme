'use strict';

/* =========================================================================
   LocateMe - panel de los padres

   JavaScript "puro": sin frameworks ni compilación, el navegador ejecuta este
   archivo tal cual. Está organizado en secciones:

   1. Constantes y `state`: TODO lo que muestra la página sale del objeto
      `state` (hijos, zonas, hijo seleccionado, recorrido...).
   2. Utilidades: h() para crear elementos HTML, íconos y formato de fechas.
   3. api(): la única función que habla con el servidor (fetch a /api/...).
   4. Estado de cada hijo: statusOf() decide si está en zona, fuera, en SOS...
   5. Renderizado: las funciones render*() redibujan una parte de la página a
      partir de `state`. Regla: primero cambias `state` y luego llamas a render.
   6. Mapa (Leaflet): capas de zonas, recorrido, precisión y marcadores.
   7. Sincronización: refresh() pide datos nuevos cada POLL_MS (15 s).
   8. Acciones: agregar/quitar hijos, vincular teléfonos, crear zonas.
   9. Sesión e inicio: bindEvents() conecta los botones y arranca todo.

   Qué pasa al abrir la página:
     api('GET', 'me') ─ con sesión → showApp() → initMap() → refresh() → schedulePoll()
                      └ sin sesión (401) → showAuth()
   ========================================================================= */

// Cada cuánto se piden datos nuevos al servidor
const POLL_MS = 15000;
// Si no llega una ubicación en este tiempo, el niño aparece «Sin señal»
const STALE_MINUTES = 15;
// Horas de recorrido que muestra el botón «Recorrido»
const HISTORY_HOURS = 24;
// Centro del mapa cuando todavía no hay niños ni zonas (Mexicali)
const DEFAULT_CENTER = [32.6040386, -115.4804487];
// Color de las zonas seguras y nombres sugeridos al crear una
const ZONE_COLOR = '#16a34a';
const ZONE_PRESETS = ['Casa', 'Escuela', 'Casa de los abuelos', 'Parque', 'Deportes'];
// Colores que se pueden elegir para cada hijo: [valor, nombre para lectores de pantalla]
const CHILD_COLORS = [
  ['#6366f1', 'Índigo'], ['#ec4899', 'Rosa'], ['#f59e0b', 'Ámbar'], ['#10b981', 'Verde'],
  ['#0ea5e9', 'Azul cielo'], ['#8b5cf6', 'Violeta'], ['#14b8a6', 'Turquesa'], ['#f97316', 'Naranja'],
];

// Íconos SVG (trazos de 24x24, estilo Lucide). En JS se usan con icon('nombre') y
// en el HTML basta con <span data-icon="nombre"></span> (se rellena al cargar).
const ICONS = {
  pin: '<path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/>',
  map: '<path d="M9 4 3 6v14l6-2 6 2 6-2V4l-6 2-6-2Z"/><path d="M9 4v14M15 6v14"/>',
  shield: '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10Z"/><path d="m9 12 2 2 4-4"/>',
  alert: '<path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><path d="M12 9v4M12 17h.01"/>',
  bell: '<path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/>',
  logout: '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="m16 17 5-5-5-5M21 12H9"/>',
  plus: '<path d="M12 5v14M5 12h14"/>',
  refresh: '<path d="M3 12a9 9 0 0 1 9-9 9.75 9.75 0 0 1 6.74 2.74L21 8"/><path d="M21 3v5h-5"/><path d="M21 12a9 9 0 0 1-9 9 9.75 9.75 0 0 1-6.74-2.74L3 16"/><path d="M8 16H3v5"/>',
  locate: '<circle cx="12" cy="12" r="7"/><circle cx="12" cy="12" r="2.5"/><path d="M12 2v3M12 19v3M2 12h3M19 12h3"/>',
  help: '<circle cx="12" cy="12" r="10"/><path d="M9.1 9a3 3 0 0 1 5.8 1c0 2-3 3-3 3"/><path d="M12 17h.01"/>',
  x: '<path d="M18 6 6 18M6 6l12 12"/>',
  copy: '<rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/>',
  share: '<circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><path d="m8.6 13.5 6.8 4M15.4 6.5l-6.8 4"/>',
  route: '<circle cx="6" cy="19" r="3"/><path d="M9 19h8.5a3.5 3.5 0 0 0 0-7h-11a3.5 3.5 0 0 1 0-7H15"/><circle cx="18" cy="5" r="3"/>',
  phone: '<rect x="5" y="2" width="14" height="20" rx="2"/><path d="M12 18h.01"/>',
  trash: '<path d="M3 6h18M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>',
  battery: '<rect x="2" y="7" width="16" height="10" rx="2"/><path d="M22 11v2"/>',
  clock: '<circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/>',
  target: '<circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="6"/><circle cx="12" cy="12" r="2"/>',
  users: '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>',
  check: '<path d="M20 6 9 17l-5-5"/>',
};

/**
 * Estado de la página. Las funciones render*() sólo leen de aquí.
 *
 *   user        padre con sesión ({ id, name, username }) o null
 *   children    hijos tal como llegan de GET /api/children
 *   zones       zonas seguras de GET /api/zones
 *   selectedId  id del hijo seleccionado en la lista o el mapa
 *   historyId   id del hijo cuyo recorrido se está mostrando
 *   history     puntos de ese recorrido
 *   lastSync    momento (en ms) de la última actualización correcta
 *   syncError   true si la última actualización falló (sin conexión)
 *   prevStatus  estado anterior de cada hijo, para detectar llegadas y salidas
 *   fitted      si ya se ajustó el mapa en la primera carga
 *   placing     true mientras se espera un toque en el mapa para crear una zona
 *   pollTimer   temporizador de la próxima actualización
 */
const state = {
  user: null,
  children: [],
  zones: [],
  selectedId: null,
  historyId: null,
  history: [],
  lastSync: null,
  syncError: false,
  prevStatus: null,
  fitted: false,
  placing: false,
  pollTimer: null,
};

// Objetos de Leaflet (la librería del mapa). `map` se queda en null si Leaflet
// no cargó, por eso las funciones del mapa empiezan con `if (!map) return;`.
//   layers     grupos de capas: zones, history, accuracy y kids
//   draftZone  círculo de la zona segura que se está creando
//   linkChild  hijo del diálogo «Vincular teléfono»
//   markers    id del hijo → { marker, accuracy, html }, para reutilizar los marcadores
let map = null;
let layers = null;
let draftZone = null;
let linkChild = null;
const markers = new Map();

/* ---------- Utilidades ---------- */

// Atajos: $('#id') devuelve un elemento y $$('.clase') todos los que coincidan
const $ = (selector) => document.querySelector(selector);
const $$ = (selector) => document.querySelectorAll(selector);

/**
 * Crea un elemento HTML. Es una versión mínima de lo que hacen React o Vue:
 *
 *   h('button', { class: 'btn', onclick: guardar }, icon('plus'), 'Agregar')
 *   → <button class="btn">[ícono] Agregar</button>, con el click ya conectado
 *
 * - Las propiedades "on..." se convierten en addEventListener.
 * - null, undefined y false se ignoran (útil para `condicion && h(...)`).
 * - El texto se agrega como texto, NUNCA como HTML: un nombre como "<b>x</b>"
 *   se ve literal y no puede inyectar código (XSS). Por eso en este archivo no
 *   se usa innerHTML con datos que escribió un usuario.
 */
function h(tag, props = {}, ...children) {
  const el = document.createElement(tag);
  for (const [key, value] of Object.entries(props)) {
    if (value == null || value === false) continue;
    if (key === 'class') el.className = value;
    else if (key === 'style') el.style.cssText = value;
    else if (key.startsWith('on')) el.addEventListener(key.slice(2), value);
    else el.setAttribute(key, value === true ? '' : value);
  }
  for (const child of children.flat()) {
    if (child == null || child === false) continue;
    el.append(child instanceof Node ? child : String(child));
  }
  return el;
}

/** Crea un <svg> a partir de su contenido. Sólo para los íconos fijos del código, nunca con datos del usuario. */
function svg(inner) {
  const wrap = document.createElement('span');
  wrap.innerHTML = `<svg class="icon" viewBox="0 0 24 24" aria-hidden="true">${inner}</svg>`;
  return wrap.firstChild;
}

const icon = (name) => svg(ICONS[name]);

/** Ícono de batería con la barra llena según el porcentaje (0-100). */
function batteryIcon(level) {
  const width = Math.max(1, Math.round(12 * level / 100));
  return svg(`${ICONS.battery}<rect x="4" y="9" width="${width}" height="6" rx="1" fill="currentColor" stroke="none"/>`);
}

// Leaflet recibe el contenido de tooltips y marcadores como HTML: todo texto del
// usuario que vaya a Leaflet debe pasar antes por escapeHtml().
const escapeHtml = (text) => String(text).replace(/[&<>"']/g, (c) => (
  { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]
));

// initial('sofía') → 'S' · isMobile() usa el mismo corte que el @media de style.css
// listNames([...]) → "Ana, Luis y Sofía"
const initial = (name) => ([...name.trim()][0] || '?').toUpperCase();
const isMobile = () => matchMedia('(max-width: 900px)').matches;
const listNames = (children) => new Intl.ListFormat('es', { type: 'conjunction' }).format(children.map((c) => c.name));

// Formateador de tiempos relativos en español del navegador
const relative = new Intl.RelativeTimeFormat('es', { numeric: 'auto' });

/** Fecha ISO → "justo ahora", "hace 5 minutos", "ayer"... */
function timeAgo(iso) {
  const seconds = Math.round((Date.parse(iso) - Date.now()) / 1000);
  const abs = Math.abs(seconds);
  if (abs < 45) return 'justo ahora';
  if (abs < 3600) return relative.format(Math.round(seconds / 60), 'minute');
  if (abs < 86400) return relative.format(Math.round(seconds / 3600), 'hour');
  return relative.format(Math.round(seconds / 86400), 'day');
}

// clockTime(iso) → "18:05" · formatDistance(1500) → "1.5 km"
const clockTime = (iso) => new Date(iso).toLocaleTimeString('es', { hour: '2-digit', minute: '2-digit' });
const formatDistance = (m) => (m < 1000 ? `${Math.round(m)} m` : `${(m / 1000).toFixed(1)} km`);

/**
 * Distancia en metros entre dos puntos { latitude, longitude } con la fórmula
 * de haversine (trata a la Tierra como una esfera). Sirve para saber si un
 * niño está dentro del radio de una zona segura.
 */
function distanceMeters(a, b) {
  const rad = (deg) => deg * Math.PI / 180;
  const dLat = rad(b.latitude - a.latitude);
  const dLng = rad(b.longitude - a.longitude);
  const x = Math.sin(dLat / 2) ** 2 + Math.cos(rad(a.latitude)) * Math.cos(rad(b.latitude)) * Math.sin(dLng / 2) ** 2;
  return 2 * 6371000 * Math.asin(Math.sqrt(x));
}

/** Aviso pequeño que aparece unos segundos sobre el mapa. */
function toast(message, isError = false) {
  const el = h('div', { class: 'toast' + (isError ? ' is-error' : ''), role: isError ? 'alert' : 'status' }, message);
  $('#toasts').append(el);
  setTimeout(() => el.remove(), 4500);
}

/* ---------- API ---------- */

/** Error de la API con su código HTTP (status 0 = sin conexión). El mensaje ya viene listo para mostrarse. */
class ApiError extends Error {
  constructor(message, status) {
    super(message);
    this.status = status;
  }
}

/**
 * Llama a la API del servidor y devuelve el JSON de la respuesta.
 *
 *   const { children } = await api('GET', 'children');
 *   await api('POST', 'zones', { name: 'Casa', latitude, longitude, radius });
 *
 * - La ruta es relativa: 'children' → /api/children.
 * - La cookie de sesión viaja sola (credentials: 'same-origin').
 * - Si la respuesta no es 2xx lanza un ApiError con el mensaje del servidor.
 * - Si la sesión expiró (401) vuelve a la pantalla de inicio de sesión.
 */
async function api(method, path, body) {
  let response;
  try {
    response = await fetch('api/' + path, {
      method,
      credentials: 'same-origin',
      headers: {
        Accept: 'application/json',
        ...(body !== undefined ? { 'Content-Type': 'application/json' } : {}),
      },
      body: body !== undefined ? JSON.stringify(body) : undefined,
    });
  } catch {
    throw new ApiError('No hay conexión con el servidor.', 0);
  }

  let data = null;
  try { data = await response.json(); } catch { /* respuesta vacía */ }

  if (response.status === 401 && state.user && path !== 'login' && path !== 'register') {
    showAuth('Tu sesión terminó. Vuelve a iniciar sesión.');
  }
  if (!response.ok) {
    throw new ApiError(data?.error || 'Algo salió mal. Intenta de nuevo.', response.status);
  }
  return data;
}

/* ---------- Estado de cada hijo ---------- */

/** Zona segura que contiene la ubicación (si se enciman, la de centro más cercano), o null. */
function zoneAt(location) {
  let best = null;
  for (const zone of state.zones) {
    const distance = distanceMeters(location, zone);
    if (distance <= zone.radius && (!best || distance < best.distance)) best = { zone, distance };
  }
  return best ? best.zone : null;
}

/**
 * Estado de un hijo, revisado en este orden de prioridad:
 *
 *   key        tone     cuándo
 *   'sos'      danger   tiene una alerta SOS sin atender
 *   'none'     idle     su teléfono nunca ha enviado una ubicación
 *   'stale'    idle     la última ubicación tiene más de STALE_MINUTES
 *   'zone:ID'  ok       está dentro de la zona segura con ese id
 *   'away'     warn     hay zonas creadas y no está en ninguna
 *   'active'   brand    no hay zonas creadas; sólo está compartiendo
 *
 * `tone` elige el color (clases .tone-* de style.css) y `label` el texto. Se
 * calcula en el navegador cada vez; el servidor no guarda este estado.
 */
function statusOf(child) {
  const loc = child.last_location;
  if (child.sos_at) return { key: 'sos', tone: 'danger', label: '¡Pidió ayuda!' };
  if (!loc) return { key: 'none', tone: 'idle', label: 'Esperando su teléfono' };

  const zone = zoneAt(loc);
  const minutes = (Date.now() - Date.parse(loc.recorded_at)) / 60000;
  if (minutes > STALE_MINUTES) {
    return { key: 'stale', tone: 'idle', label: zone ? `Sin señal · última vez en ${zone.name}` : 'Sin señal reciente', zone };
  }
  if (zone) return { key: 'zone:' + zone.id, tone: 'ok', label: 'En ' + zone.name, zone };
  if (state.zones.length) return { key: 'away', tone: 'warn', label: 'Fuera de zonas seguras' };
  return { key: 'active', tone: 'brand', label: 'Compartiendo ubicación' };
}

/**
 * Compara el estado de cada hijo con el de la actualización anterior y avisa si
 * pidió ayuda, llegó a una zona o salió de ella. En la primera carga
 * (prevStatus = null) sólo guarda los estados, para no avisar de todo al abrir.
 */
function detectChanges() {
  const next = new Map(state.children.map((child) => [child.id, statusOf(child)]));

  if (state.prevStatus) {
    for (const child of state.children) {
      const now = next.get(child.id);
      const before = state.prevStatus.get(child.id);
      if (!before || before.key === now.key) continue;

      if (now.key === 'sos') {
        notify(`¡${child.name} pidió ayuda!`, 'Abre LocateMe para ver dónde está.', true);
      } else if (now.key.startsWith('zone:') && (before.key === 'away' || before.key.startsWith('zone:'))) {
        notify(`${child.name} llegó a ${now.zone.name}`);
      } else if (now.key === 'away' && before.key.startsWith('zone:')) {
        notify(`${child.name} salió de ${before.zone.name}`);
      }
    }
  }

  state.prevStatus = next;
}

// Tras cambiar las zonas no hay que avisar de "llegadas" o "salidas" falsas
function resetBaseline() {
  state.prevStatus = new Map(state.children.map((child) => [child.id, statusOf(child)]));
}

/**
 * Avisa al padre: siempre con un toast y, si dio permiso, con una notificación
 * del sistema (cuando la pestaña no está a la vista o si es urgente). Urgente
 * es un SOS: la notificación se queda hasta que la cierre y el teléfono vibra.
 */
function notify(title, body = '', urgent = false) {
  toast(title, urgent);
  if (urgent && navigator.vibrate) navigator.vibrate([300, 150, 300]);

  if ('Notification' in window && Notification.permission === 'granted' && (urgent || document.hidden)) {
    try {
      new Notification(title, { body, tag: title, requireInteraction: urgent });
    } catch { /* algunos navegadores móviles sólo permiten avisos desde un service worker */ }
  }
}

/* ---------- Renderizado ---------- */

/** Redibuja toda la página a partir de `state`. */
function render() {
  renderSummary();
  renderAlerts();
  renderChildren();
  renderZones();
  renderMarkers();
  renderHistory();
  renderSync();

  const sosCount = state.children.filter((c) => c.sos_at).length;
  document.title = sosCount ? `(${sosCount}) ¡Alerta SOS! · LocateMe` : 'LocateMe · Mi familia';
}

/**
 * Recuadro de resumen arriba de la lista ("Todos están en zonas seguras",
 * "Luis está fuera de las zonas seguras"...). Muestra el caso más grave.
 */
function renderSummary() {
  const el = $('#summary');
  if (!state.children.length) {
    el.hidden = true;
    return;
  }

  const withStatus = state.children.map((child) => ({ child, status: statusOf(child) }));
  const pick = (test) => withStatus.filter(({ status }) => test(status.key)).map(({ child }) => child);
  const sos = pick((k) => k === 'sos');
  const away = pick((k) => k === 'away');
  const quiet = pick((k) => k === 'stale' || k === 'none');

  let tone = 'brand', iconName = 'check', text = 'Todos están compartiendo su ubicación';
  if (sos.length) {
    [tone, iconName, text] = ['danger', 'alert', `${listNames(sos)} ${sos.length > 1 ? 'pidieron' : 'pidió'} ayuda`];
  } else if (away.length) {
    [tone, iconName, text] = ['warn', 'alert', `${listNames(away)} ${away.length > 1 ? 'están' : 'está'} fuera de las zonas seguras`];
  } else if (quiet.length === withStatus.length) {
    [tone, iconName, text] = ['idle', 'clock', 'Aún no hay ubicaciones recientes'];
  } else if (quiet.length) {
    [tone, iconName, text] = ['idle', 'clock', `${listNames(quiet)} sin señal reciente`];
  } else if (state.zones.length) {
    [tone, iconName, text] = ['ok', 'shield', withStatus.length > 1 ? 'Todos están en zonas seguras' : `${withStatus[0].child.name} está en una zona segura`];
  }

  el.className = 'summary tone-' + tone;
  el.replaceChildren(icon(iconName), h('span', {}, text));
  el.hidden = false;
}

/** Banners rojos sobre el mapa, uno por cada alerta SOS sin atender. */
function renderAlerts() {
  const sos = state.children.filter((c) => c.sos_at);
  $('#alerts').replaceChildren(...sos.map((child) => h('div', { class: 'banner banner-sos', role: 'alert' },
    icon('alert'),
    h('span', { class: 'banner-text' },
      `¡${child.name} pidió ayuda!`,
      h('small', {}, `${timeAgo(child.sos_at)}${child.last_location ? '' : ' · sin ubicación disponible'}`)),
    h('span', { class: 'banner-actions' },
      child.last_location && h('button', { class: 'btn btn-sm btn-ghost', onclick: () => selectChild(child.id, true) }, 'Ver'),
      h('button', { class: 'btn btn-sm', onclick: () => acknowledgeSos(child) }, 'Atendida')),
  )));
}

/** Lista de hijos, o la guía de primeros pasos si todavía no hay ninguno. */
function renderChildren() {
  const list = $('#children-list');

  if (!state.children.length) {
    list.replaceChildren(h('li', { class: 'empty' },
      h('div', { class: 'empty-icon' }, icon('users')),
      h('h3', {}, 'Agrega a tu primer hijo'),
      h('ol', { class: 'steps' },
        h('li', {}, 'Escribe su nombre y elige un color.'),
        h('li', {}, 'Abre el enlace o escanea el código QR en su teléfono.'),
        h('li', {}, '¡Listo! Verás su ubicación en el mapa.')),
      h('button', { class: 'btn', onclick: openChildDialog }, icon('plus'), 'Agregar hijo'),
    ));
    return;
  }

  list.replaceChildren(...state.children.map(childCard));
}

/** Tarjeta de un hijo: avatar, estado, hora, batería, precisión del GPS y botones. */
function childCard(child) {
  const status = statusOf(child);
  const loc = child.last_location;
  const meta = [];

  if (loc) {
    meta.push(h('span', { title: new Date(loc.recorded_at).toLocaleString('es') }, icon('clock'), timeAgo(loc.recorded_at)));
    if (loc.battery != null) {
      meta.push(h('span', { class: loc.battery <= 20 ? 'is-low' : null, title: 'Batería de su teléfono' }, batteryIcon(loc.battery), `${loc.battery}%`));
    }
    if (loc.accuracy != null) {
      meta.push(h('span', { title: 'Margen de error del GPS' }, icon('target'), '±' + formatDistance(loc.accuracy)));
    }
  } else {
    meta.push(h('span', {}, 'Vincula su teléfono para empezar'));
  }

  return h('li', { class: 'child-card' + (child.id === state.selectedId ? ' is-selected' : ''), style: `--c:${child.color}` },
    child.sos_at && h('div', { class: 'sos-box', role: 'alert' },
      h('span', {}, `¡${child.name} pidió ayuda!`, h('small', {}, timeAgo(child.sos_at))),
      h('button', { class: 'btn btn-sm', onclick: () => acknowledgeSos(child) }, 'Marcar atendida')),
    h('button', { class: 'child-main', onclick: () => selectChild(child.id, true), 'aria-pressed': String(child.id === state.selectedId) },
      h('span', { class: 'avatar', 'data-tone': status.tone, 'aria-hidden': 'true' }, initial(child.name)),
      h('span', { class: 'child-info' },
        h('span', { class: 'child-name' }, child.name),
        h('span', { class: 'pill tone-' + status.tone }, status.label),
        h('span', { class: 'child-meta' }, meta))),
    h('div', { class: 'child-actions' },
      h('button', {
        class: 'chip-btn',
        'aria-pressed': String(state.historyId === child.id),
        disabled: !loc,
        title: `Recorrido de las últimas ${HISTORY_HOURS} horas`,
        onclick: () => toggleHistory(child),
      }, icon('route'), 'Recorrido'),
      h('button', { class: 'chip-btn', title: 'Vincular su teléfono', onclick: () => openLinkDialog(child) }, icon('phone'), 'Vincular'),
      h('button', { class: 'chip-btn danger', title: `Quitar a ${child.name}`, 'aria-label': `Quitar a ${child.name}`, onclick: () => removeChild(child) }, icon('trash'))),
  );
}

/** Lista de zonas seguras del panel y sus círculos en el mapa. */
function renderZones() {
  const list = $('#zones-list');

  if (!state.zones.length) {
    list.replaceChildren(h('li', {}, h('p', { class: 'zones-empty' },
      'Crea zonas como «Casa» o «Escuela» y te avisaremos cuando tus hijos lleguen o salgan de ellas.')));
  } else {
    list.replaceChildren(...state.zones.map((zone) => {
      const inside = state.children.filter((c) => {
        const status = statusOf(c);
        return status.zone && status.zone.id === zone.id && status.key !== 'stale';
      });
      const who = inside.length ? `${listNames(inside)} ${inside.length > 1 ? 'están' : 'está'} aquí` : 'Nadie aquí ahora';

      return h('li', { class: 'zone-item' },
        h('button', { class: 'zone-main', onclick: () => focusZone(zone) },
          h('span', { class: 'zone-icon' }, icon('shield')),
          h('span', { style: 'min-width:0' },
            h('span', { class: 'zone-name' }, zone.name),
            h('span', { class: 'zone-meta' }, `${formatDistance(zone.radius)} · ${who}`))),
        h('button', { class: 'icon-btn', title: 'Borrar zona', 'aria-label': `Borrar la zona ${zone.name}`, onclick: () => removeZone(zone) }, icon('trash')));
    }));
  }

  if (!map) return;
  layers.zones.clearLayers();
  for (const zone of state.zones) {
    L.circle([zone.latitude, zone.longitude], {
      radius: zone.radius, color: ZONE_COLOR, weight: 2, fillColor: ZONE_COLOR, fillOpacity: 0.12, interactive: false,
    }).addTo(layers.zones);
    // Nombre sobre el borde superior del círculo, para no tapar a los niños que estén dentro
    L.tooltip({ direction: 'center', className: 'zone-tooltip' })
      .setLatLng([zone.latitude + zone.radius / 111320, zone.longitude])
      .setContent(escapeHtml(zone.name))
      .addTo(layers.zones);
  }
}

/** HTML del pin de un niño en el mapa (los estilos están en .kid-marker de style.css). */
function markerHtml(child, status) {
  const classes = ['kid-marker'];
  if (status.key === 'sos') classes.push('is-sos');
  if (status.key === 'stale') classes.push('is-stale');
  if (child.id === state.selectedId) classes.push('is-selected');

  return `<div class="${classes.join(' ')}" style="--c:${escapeHtml(child.color)}">`
    + '<span class="kid-pulse"></span>'
    + `<div class="kid-pin"><span>${escapeHtml(initial(child.name))}</span></div></div>`;
}

// Ícono del pin para Leaflet. El pin es un cuadrado girado 45° (ver .kid-pin en
// style.css) y su punta queda 56 px debajo del borde superior; por eso
// iconAnchor es [23, 56]: centro horizontal y la punta sobre la coordenada real.
const kidIcon = (html) => L.divIcon({ className: 'kid-icon', html, iconSize: [46, 46], iconAnchor: [23, 56], tooltipAnchor: [0, -64] });

/**
 * Crea o actualiza el pin y el círculo de precisión de cada niño.
 *
 * Los marcadores se reutilizan (Map `markers`) en vez de borrarlos y crearlos en
 * cada actualización: así no parpadean y un tooltip abierto no se cierra solo.
 * Al final se quitan los de los niños que ya no existen.
 */
function renderMarkers() {
  if (!map) return;
  const seen = new Set();

  for (const child of state.children) {
    const loc = child.last_location;
    if (!loc) continue;
    seen.add(child.id);

    const status = statusOf(child);
    const latlng = [loc.latitude, loc.longitude];
    const html = markerHtml(child, status);
    let entry = markers.get(child.id);

    if (!entry) {
      const marker = L.marker(latlng, { icon: kidIcon(html), riseOnHover: true, title: child.name, alt: child.name })
        .on('click', () => selectChild(child.id, false))
        .bindTooltip('', { direction: 'top', className: 'kid-tooltip' });
      const accuracy = L.circle(latlng, { radius: 0, weight: 1, opacity: 0.35, fillOpacity: 0.1, interactive: false });
      layers.kids.addLayer(marker);
      layers.accuracy.addLayer(accuracy);
      entry = { marker, accuracy, html };
      markers.set(child.id, entry);
    }

    entry.marker.setLatLng(latlng);
    if (entry.html !== html) {
      entry.marker.setIcon(kidIcon(html));
      entry.html = html;
    }
    entry.marker.setZIndexOffset(status.key === 'sos' ? 2000 : child.id === state.selectedId ? 1000 : 0);
    entry.marker.setTooltipContent(escapeHtml(`${child.name} · ${status.label} · ${timeAgo(loc.recorded_at)}`));
    entry.accuracy.setLatLng(latlng).setRadius(loc.accuracy || 0).setStyle({ color: child.color, fillColor: child.color });
  }

  for (const [id, entry] of markers) {
    if (seen.has(id)) continue;
    layers.kids.removeLayer(entry.marker);
    layers.accuracy.removeLayer(entry.accuracy);
    markers.delete(id);
  }
}

/**
 * Dibuja el recorrido del hijo `state.historyId`: una línea punteada y un punto
 * por ubicación (rojo si fue un SOS). Al pasar el mouse se ve la hora.
 */
function renderHistory() {
  if (!map) return;
  layers.history.clearLayers();

  const child = state.children.find((c) => c.id === state.historyId);
  if (!child || !state.history.length) return;

  L.polyline(state.history.map((p) => [p.latitude, p.longitude]), {
    color: child.color, weight: 4, opacity: 0.8, dashArray: '1 9', lineCap: 'round', interactive: false,
  }).addTo(layers.history);

  state.history.forEach((point, index) => {
    const first = index === 0;
    const label = `${clockTime(point.recorded_at)}${point.is_sos ? ' · SOS' : ''}${first ? ' · inicio' : ''}`;
    L.circleMarker([point.latitude, point.longitude], {
      radius: point.is_sos ? 7 : first ? 6 : 4,
      color: '#fff', weight: 2,
      fillColor: point.is_sos ? '#dc2626' : child.color, fillOpacity: 1,
    }).bindTooltip(label, { direction: 'top', className: 'kid-tooltip' }).addTo(layers.history);
  });
}

/** Texto «Actualizado hace...» del pie del panel. */
function renderSync() {
  const el = $('#sync-status');
  el.classList.toggle('is-error', state.syncError);
  el.textContent = state.syncError
    ? 'Sin conexión, reintentando…'
    : state.lastSync ? `Actualizado ${timeAgo(new Date(state.lastSync).toISOString())}` : 'Conectando…';
}

/* ---------- Mapa ---------- */

/**
 * Crea el mapa de Leaflet (una sola vez). Las imágenes del mapa vienen de
 * OpenStreetMap; para usar otro proveedor cambia la URL de L.tileLayer y agrega
 * su dominio a img-src en la CSP (src/Router/Models/Pages/Methods/GET.php).
 *
 * Capas, de abajo hacia arriba: zonas → recorrido → precisión → niños.
 */
function initMap() {
  if (map) return;
  if (typeof L === 'undefined') {
    $('#map').append(h('div', { class: 'empty', style: 'margin:24px;background:var(--surface)' },
      h('h3', {}, 'No se pudo cargar el mapa'), h('p', {}, 'Revisa tu conexión a internet y recarga la página.')));
    return;
  }

  map = L.map('map', { zoomControl: false, preferCanvas: true }).setView(DEFAULT_CENTER, 13);
  L.control.zoom({ position: 'bottomright', zoomInTitle: 'Acercar', zoomOutTitle: 'Alejar' }).addTo(map);
  L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 19,
    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
  }).addTo(map);

  layers = {
    zones: L.layerGroup().addTo(map),
    history: L.layerGroup().addTo(map),
    accuracy: L.layerGroup().addTo(map),
    kids: L.layerGroup().addTo(map),
  };

  map.on('click', (event) => {
    if (state.placing || draftZone) placeDraftZone(event.latlng);
  });
}

/** Ajusta el zoom para ver a todos los niños y zonas (botón de la mira). */
function fitAll(animate = true) {
  if (!map) return;
  const bounds = L.latLngBounds([]);
  for (const child of state.children) {
    if (child.last_location) bounds.extend([child.last_location.latitude, child.last_location.longitude]);
  }
  for (const zone of state.zones) {
    bounds.extend(L.latLng(zone.latitude, zone.longitude).toBounds(zone.radius * 2));
  }

  if (bounds.isValid()) map.fitBounds(bounds, { padding: [70, 70], maxZoom: 16, animate });
  else map.setView(DEFAULT_CENTER, 13, { animate });
}

/** Centra el mapa en una zona cuando se toca en la lista. */
function focusZone(zone) {
  if (!map) return;
  if (isMobile()) window.scrollTo({ top: 0, behavior: 'smooth' });
  map.flyToBounds(L.latLng(zone.latitude, zone.longitude).toBounds(zone.radius * 2.6), { duration: 0.6 });
}

/** Resalta a un hijo en la lista y en el mapa; con fly=true además mueve el mapa hasta él. */
function selectChild(id, fly) {
  state.selectedId = id;
  renderChildren();
  renderMarkers();

  const child = state.children.find((c) => c.id === id);
  const loc = child && child.last_location;
  if (!map || !loc) return;

  if (fly) {
    if (isMobile()) window.scrollTo({ top: 0, behavior: 'smooth' });
    map.flyTo([loc.latitude, loc.longitude], Math.max(map.getZoom(), 16), { duration: 0.7 });
  }
  const entry = markers.get(id);
  if (entry) entry.marker.openTooltip();
}

/** Muestra u oculta el recorrido de un hijo (sólo uno a la vez). */
async function toggleHistory(child) {
  if (state.historyId === child.id) {
    state.historyId = null;
    state.history = [];
    renderHistory();
    renderChildren();
    return;
  }

  state.historyId = child.id;
  state.history = [];
  state.selectedId = child.id;
  renderChildren();
  renderMarkers();
  await loadHistory(true);
}

/**
 * Pide el recorrido de `state.historyId` y lo dibuja. Con fit=true (al abrirlo)
 * ajusta el mapa para verlo completo. Si mientras llegaba la respuesta el
 * usuario eligió a otro hijo, la respuesta se descarta.
 */
async function loadHistory(fit) {
  const id = state.historyId;
  if (id == null) return;

  try {
    const { locations } = await api('GET', `children/${id}/locations?hours=${HISTORY_HOURS}`);
    if (state.historyId !== id) return;
    state.history = locations;
    renderHistory();

    if (fit && map && locations.length > 1) {
      if (isMobile()) window.scrollTo({ top: 0, behavior: 'smooth' });
      map.fitBounds(locations.map((p) => [p.latitude, p.longitude]), { padding: [90, 90], maxZoom: 17 });
    }
    if (fit && locations.length === 0) toast(`Sin recorrido en las últimas ${HISTORY_HOURS} horas`);
  } catch (error) {
    if (fit) toast(error.message, true);
  }
}

/* ---------- Sincronización ---------- */

/**
 * Pide hijos y zonas al servidor, detecta cambios (para los avisos) y redibuja
 * todo. Se llama al entrar, cada POLL_MS, al volver a la pestaña y con el
 * botón de actualizar.
 */
async function refresh() {
  try {
    const [{ children }, { zones }] = await Promise.all([api('GET', 'children'), api('GET', 'zones')]);
    state.children = children;
    state.zones = zones;
    state.lastSync = Date.now();
    state.syncError = false;

    if (state.selectedId != null && !children.some((c) => c.id === state.selectedId)) state.selectedId = null;
    if (state.historyId != null && !children.some((c) => c.id === state.historyId)) {
      state.historyId = null;
      state.history = [];
    }

    detectChanges();
    render();

    if (!state.fitted) {
      fitAll(false);
      state.fitted = true;
    }
    if (state.historyId != null) loadHistory(false);
  } catch (error) {
    if (error.status === 401) return;
    state.syncError = true;
    renderSync();
  }
}

/**
 * Programa la siguiente actualización. Usa setTimeout y no setInterval para que
 * nunca corran dos refresh() a la vez si el servidor tarda en responder.
 */
function schedulePoll() {
  clearTimeout(state.pollTimer);
  state.pollTimer = setTimeout(async () => {
    await refresh();
    if (state.user) schedulePoll();
  }, POLL_MS);
}

/* ---------- Acciones ---------- */

/** «Marcar atendida»: quita la alerta SOS de un hijo. */
async function acknowledgeSos(child) {
  try {
    await api('POST', `children/${child.id}/sos/ack`);
    toast(`Alerta de ${child.name} marcada como atendida`);
    await refresh();
  } catch (error) {
    toast(error.message, true);
  }
}

/** Quita a un hijo (y su historial) después de confirmar. */
async function removeChild(child) {
  const ok = await confirmDialog({
    title: `¿Quitar a ${child.name}?`,
    text: 'Se borrará todo su historial de ubicaciones y su teléfono dejará de compartir la ubicación.',
    confirm: 'Quitar',
  });
  if (!ok) return;

  try {
    await api('DELETE', `children/${child.id}`);
    toast(`Se quitó a ${child.name}`);
    await refresh();
  } catch (error) {
    toast(error.message, true);
  }
}

/** Borra una zona después de confirmar. */
async function removeZone(zone) {
  const ok = await confirmDialog({
    title: `¿Borrar la zona «${zone.name}»?`,
    text: 'Ya no recibirás avisos cuando tus hijos entren o salgan de ella.',
    confirm: 'Borrar',
  });
  if (!ok) return;

  try {
    await api('DELETE', `zones/${zone.id}`);
    state.zones = state.zones.filter((z) => z.id !== zone.id);
    resetBaseline();
    render();
    toast(`Zona «${zone.name}» borrada`);
  } catch (error) {
    toast(error.message, true);
  }
}

/**
 * Diálogo de confirmación propio (en lugar de window.confirm). Devuelve una
 * promesa: `if (await confirmDialog({...}))`. Es true sólo si se tocó el botón
 * de confirmar; Escape o Cancelar dan false.
 */
function confirmDialog({ title, text, confirm }) {
  const dialog = $('#confirm-dialog');
  $('#confirm-title').textContent = title;
  $('#confirm-text').textContent = text;
  $('#confirm-ok').textContent = confirm;
  dialog.returnValue = '';
  dialog.showModal();

  return new Promise((resolve) => {
    dialog.addEventListener('close', () => resolve(dialog.returnValue === 'ok'), { once: true });
  });
}

/* ---------- Agregar hijo y vincular su teléfono ---------- */

/** Abre «Agregar a un hijo» con un color preseleccionado que ningún hermano use. */
function openChildDialog() {
  const form = $('#child-form');
  form.reset();
  $('#child-error').hidden = true;

  const used = new Set(state.children.map((c) => c.color));
  const selected = (CHILD_COLORS.find(([color]) => !used.has(color)) || CHILD_COLORS[0])[0];

  $('#child-colors').replaceChildren(...CHILD_COLORS.map(([color, name]) => h('label', { class: 'swatch', style: `--c:${color}`, title: name },
    h('input', { type: 'radio', name: 'color', value: color, checked: color === selected, 'aria-label': name }),
    h('span', { 'aria-hidden': 'true' }))));

  $('#child-dialog').showModal();
  $('#child-name').focus();
}

/** Crea al hijo y muestra enseguida el enlace y el QR para su teléfono. */
async function submitChild(event) {
  event.preventDefault();
  const form = event.currentTarget;
  const button = form.querySelector('[type="submit"]');
  const errorBox = $('#child-error');
  button.disabled = true;
  errorBox.hidden = true;

  try {
    const { child, device_token: token } = await api('POST', 'children', {
      name: $('#child-name').value,
      color: new FormData(form).get('color'),
    });
    $('#child-dialog').close();
    await refresh();
    selectChild(child.id, false);
    showLink(child, token);
  } catch (error) {
    errorBox.textContent = error.message;
    errorBox.hidden = false;
  } finally {
    button.disabled = false;
  }
}

/**
 * Abre «Vincular teléfono» para un hijo que ya existe. Primero advierte que el
 * enlace anterior dejará de funcionar; el enlace nuevo se pide en generateLink().
 */
function openLinkDialog(child) {
  linkChild = child;
  $('#link-dialog-title').textContent = `Vincular el teléfono de ${child.name}`;
  $('#link-intro').hidden = false;
  $('#link-result').hidden = true;
  $('#link-dialog').showModal();
}

/** Pide un token nuevo al servidor y muestra el enlace. */
async function generateLink() {
  const button = $('#link-generate');
  button.disabled = true;
  try {
    const { device_token: token } = await api('POST', `children/${linkChild.id}/token`);
    showLink(linkChild, token);
  } catch (error) {
    toast(error.message, true);
  } finally {
    button.disabled = false;
  }
}

/**
 * Muestra el enlace /nino#t=<token> y su código QR. Al cerrar el diálogo el
 * enlace se borra de la página (ver el evento 'close' en bindEvents).
 */
function showLink(child, token) {
  linkChild = child;
  // El token va después de "#" para que nunca llegue a los logs del servidor
  const url = new URL('nino', location.href);
  url.hash = 't=' + token;

  $('#link-dialog-title').textContent = `Vincular el teléfono de ${child.name}`;
  $$('.link-child-name').forEach((el) => { el.textContent = child.name; });
  $('#link-url').value = url.href;
  $('#link-share').hidden = !navigator.share;
  $('#link-qr').replaceChildren();

  if (typeof qrcode === 'function') {
    const qr = qrcode(0, 'M');
    qr.addData(url.href);
    qr.make();
    $('#link-qr').innerHTML = qr.createSvgTag({ cellSize: 4, margin: 2, scalable: true });
  } else {
    $('#link-qr').append(h('p', { class: 'muted', style: 'margin:70px 10px;text-align:center' }, 'Copia el enlace de abajo'));
  }

  $('#link-intro').hidden = true;
  $('#link-result').hidden = false;
  const dialog = $('#link-dialog');
  if (!dialog.open) dialog.showModal();
}

/** Copia el enlace; si el navegador no permite el portapapeles moderno, usa el método antiguo. */
async function copyLink() {
  const input = $('#link-url');
  try {
    await navigator.clipboard.writeText(input.value);
  } catch {
    input.select();
    document.execCommand('copy');
  }
  toast('Enlace copiado');
}

/** Abre el menú de compartir del teléfono (WhatsApp, mensajes...). Sólo existe si hay navigator.share. */
async function shareLink() {
  try {
    await navigator.share({
      title: 'LocateMe',
      text: `Abre este enlace en el teléfono de ${linkChild.name} para compartir su ubicación.`,
      url: $('#link-url').value,
    });
  } catch { /* el usuario canceló */ }
}

/* ---------- Zonas seguras ---------- */

/*
 * Crear una zona segura tiene 3 pasos:
 *   1. startPlacing(): cambia el cursor y pide tocar el mapa.
 *   2. placeDraftZone(): al tocar aparece un círculo de borrador y el editor
 *      (nombre y tamaño). Tocar otra vez el mapa mueve el círculo.
 *   3. submitZone(): la guarda en el servidor. cancelZoneEditor() limpia todo.
 */
function startPlacing() {
  if (!map) return;
  cancelZoneEditor();
  state.placing = true;
  $('#map').classList.add('is-placing');
  $('#placing-hint').hidden = false;
  if (isMobile()) window.scrollTo({ top: 0, behavior: 'smooth' });
}

function stopPlacing() {
  state.placing = false;
  $('#map').classList.remove('is-placing');
  $('#placing-hint').hidden = true;
}

function placeDraftZone(latlng) {
  stopPlacing();
  const radius = Number($('#zone-radius').value);

  if (draftZone) {
    draftZone.setLatLng(latlng);
    return;
  }

  draftZone = L.circle(latlng, {
    radius, color: ZONE_COLOR, weight: 2, dashArray: '6 6', fillColor: ZONE_COLOR, fillOpacity: 0.2, interactive: false,
  }).addTo(map);
  map.flyToBounds(draftZone.getBounds(), { padding: [90, 90], maxZoom: 17, duration: 0.5 });

  $('#zone-editor').hidden = false;
  if (!isMobile()) $('#zone-name').focus();
}

function cancelZoneEditor() {
  stopPlacing();
  if (draftZone) draftZone.remove();
  draftZone = null;
  $('#zone-editor').hidden = true;
  $('#zone-editor').reset();
  $('#zone-radius-out').textContent = '150 m';
}

async function submitZone(event) {
  event.preventDefault();
  if (!draftZone) return;

  const button = event.currentTarget.querySelector('[type="submit"]');
  const center = draftZone.getLatLng();
  button.disabled = true;

  try {
    const { zone } = await api('POST', 'zones', {
      name: $('#zone-name').value,
      latitude: Number(center.lat.toFixed(6)),
      longitude: Number(center.lng.toFixed(6)),
      radius: Number($('#zone-radius').value),
    });
    state.zones.push(zone);
    state.zones.sort((a, b) => a.name.localeCompare(b.name, 'es'));
    cancelZoneEditor();
    resetBaseline();
    render();
    toast(`Zona «${zone.name}» creada`);
  } catch (error) {
    toast(error.message, true);
  } finally {
    button.disabled = false;
  }
}

/* ---------- Sesión ---------- */

/** Muestra la pantalla de acceso (con un mensaje de error opcional) y detiene las actualizaciones. */
function showAuth(message) {
  clearTimeout(state.pollTimer);
  state.user = null;
  $('#app-view').hidden = true;
  $('#auth-view').hidden = false;
  document.title = 'LocateMe · Mi familia';

  const errorBox = $('#auth-error');
  errorBox.textContent = message || '';
  errorBox.hidden = !message;
}

/** Muestra el panel del padre, crea el mapa y empieza a actualizar cada POLL_MS. */
async function showApp(user) {
  state.user = user;
  state.fitted = false;
  state.prevStatus = null;
  $('#user-name').textContent = user.name;
  $('#auth-view').hidden = true;
  $('#app-view').hidden = false;
  $('#notify-btn').hidden = !('Notification' in window) || Notification.permission !== 'default';

  initMap();
  if (map) map.invalidateSize();
  await refresh();
  schedulePoll();
}

/** Cambia entre las pestañas «Iniciar sesión» y «Crear cuenta». */
function switchAuthTab(tab) {
  const isLogin = tab === 'login';
  $('#tab-login').setAttribute('aria-selected', String(isLogin));
  $('#tab-register').setAttribute('aria-selected', String(!isLogin));
  $('#login-form').hidden = !isLogin;
  $('#register-form').hidden = isLogin;
  $('#auth-error').hidden = true;
  (isLogin ? $('#login-username') : $('#register-name')).focus();
}

/**
 * Envía el formulario de login o de registro (path = 'login' | 'register').
 * Los atributos name de los <input> son justo los campos que espera la API.
 */
async function submitAuth(event, path) {
  event.preventDefault();
  const form = event.currentTarget;
  const button = form.querySelector('[type="submit"]');
  button.disabled = true;
  $('#auth-error').hidden = true;

  try {
    const { user } = await api('POST', path, Object.fromEntries(new FormData(form)));
    form.reset();
    await showApp(user);
  } catch (error) {
    showAuth(error.message);
  } finally {
    button.disabled = false;
  }
}

/**
 * Cierra la sesión y limpia el estado y el mapa, para que otra persona que use
 * la misma computadora no vea los datos anteriores.
 */
async function logout() {
  try { await api('POST', 'logout'); } catch { /* igual cerramos la sesión local */ }

  state.children = [];
  state.zones = [];
  state.selectedId = null;
  state.historyId = null;
  state.history = [];
  cancelZoneEditor();
  if (map) {
    Object.values(layers).forEach((layer) => layer.clearLayers());
    markers.clear();
  }
  showAuth();
  switchAuthTab('login');
}

/* ---------- Inicio ---------- */

/** Conecta cada botón y formulario del HTML con su función. Se ejecuta una sola vez. */
function bindEvents() {
  // Cada <span data-icon="..."> del HTML recibe su ícono SVG
  $$('[data-icon]').forEach((el) => el.append(icon(el.dataset.icon)));

  $('#tab-login').addEventListener('click', () => switchAuthTab('login'));
  $('#tab-register').addEventListener('click', () => switchAuthTab('register'));
  $('#login-form').addEventListener('submit', (e) => submitAuth(e, 'login'));
  $('#register-form').addEventListener('submit', (e) => submitAuth(e, 'register'));
  $('#logout-btn').addEventListener('click', logout);

  $('#notify-btn').addEventListener('click', async () => {
    const permission = await Notification.requestPermission();
    $('#notify-btn').hidden = permission !== 'default';
    toast(permission === 'granted' ? 'Listo: te avisaremos aunque tengas otra pestaña abierta' : 'Los avisos quedaron desactivados');
  });

  $('#refresh-btn').addEventListener('click', async () => {
    await refresh();
    schedulePoll();
  });
  $('#fit-btn').addEventListener('click', () => fitAll());

  const legend = $('#legend');
  const legendBtn = $('#legend-btn');
  legendBtn.addEventListener('click', (event) => {
    event.stopPropagation();
    legend.hidden = !legend.hidden;
    legendBtn.setAttribute('aria-expanded', String(!legend.hidden));
  });
  document.addEventListener('click', (event) => {
    if (!legend.hidden && !legend.contains(event.target)) {
      legend.hidden = true;
      legendBtn.setAttribute('aria-expanded', 'false');
    }
  });

  $('#add-child-btn').addEventListener('click', openChildDialog);
  $('#child-form').addEventListener('submit', submitChild);

  $('#link-generate').addEventListener('click', generateLink);
  $('#link-copy').addEventListener('click', copyLink);
  $('#link-share').addEventListener('click', shareLink);
  // No dejar el enlace secreto en la página cuando se cierra el diálogo
  $('#link-dialog').addEventListener('close', () => {
    $('#link-url').value = '';
    $('#link-qr').replaceChildren();
  });

  $$('[data-close]').forEach((button) => button.addEventListener('click', () => button.closest('dialog').close()));

  $('#add-zone-btn').addEventListener('click', startPlacing);
  $('#cancel-placing').addEventListener('click', stopPlacing);
  $('#zone-cancel').addEventListener('click', cancelZoneEditor);
  $('#zone-editor').addEventListener('submit', submitZone);
  $('#zone-radius').addEventListener('input', (event) => {
    const radius = Number(event.target.value);
    $('#zone-radius-out').textContent = formatDistance(radius);
    if (draftZone) draftZone.setRadius(radius);
  });
  $('#zone-presets').append(...ZONE_PRESETS.map((name) => h('button', {
    type: 'button',
    class: 'chip-btn',
    onclick: () => { $('#zone-name').value = name; },
  }, name)));

  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && (state.placing || draftZone)) cancelZoneEditor();
  });

  // Al volver a la pestaña se actualiza enseguida: los navegadores frenan los
  // temporizadores de las pestañas que no están a la vista.
  document.addEventListener('visibilitychange', () => {
    if (!document.hidden && state.user) {
      refresh();
      schedulePoll();
    }
  });

  // Mantiene al día el texto «Actualizado hace...» aunque no lleguen datos nuevos
  setInterval(() => { if (state.user) renderSync(); }, 5000);
}

// Arranque: con una cookie de sesión válida se muestra el panel; si no, el acceso.
bindEvents();

api('GET', 'me')
  .then(({ user }) => showApp(user))
  .catch((error) => showAuth(error.status === 401 ? '' : error.message));
