// Banyira service worker: caches built static assets only (JS, CSS, icons) so
// pages load quickly on slow connections. Pages and data always come from
// the network, so no financial information is stored or shown from cache.
const CACHE = 'banyira-static-v1'

self.addEventListener('install', () => self.skipWaiting())
self.addEventListener('activate', (e) => e.waitUntil(
  caches.keys().then((keys) => Promise.all(keys.filter((k) => k !== CACHE).map((k) => caches.delete(k)))).then(() => self.clients.claim()),
))

self.addEventListener('fetch', (e) => {
  const url = new URL(e.request.url)
  const isStatic = e.request.method === 'GET' && url.origin === location.origin && (url.pathname.startsWith('/build/') || url.pathname === '/favicon.svg')
  if (!isStatic) return
  e.respondWith(caches.open(CACHE).then(async (cache) => {
    const hit = await cache.match(e.request)
    if (hit) return hit
    const res = await fetch(e.request)
    if (res.ok) cache.put(e.request, res.clone())
    return res
  }))
})
