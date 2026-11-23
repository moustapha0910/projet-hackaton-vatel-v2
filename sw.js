/* Service worker : reçoit les notifications « push » du serveur et affiche « Votre commande est prête ».
   Le push ne contient pas de texte : on va chercher le message en attente sur api.php?a=pushmsg (repli : message générique). */
self.addEventListener('install', () => self.skipWaiting());
self.addEventListener('activate', e => e.waitUntil(self.clients.claim()));

async function pending() {
  try {
    const sub = await self.registration.pushManager.getSubscription();
    if (!sub) return null;
    const r = await fetch('api.php?a=pushmsg', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ ep: sub.endpoint }) });
    const m = await r.json();
    return m && m.title ? m : null;
  } catch (e) { return null; }
}

self.addEventListener('push', e => e.waitUntil((async () => {
  const m = (await pending()) || { title: 'Votre commande est prête !', body: 'Elle arrive à votre table.', url: 'menu.php', tag: 'ready' };
  return self.registration.showNotification(m.title, {
    body: m.body, tag: m.tag || 'ready', icon: 'img/icon-192.png', badge: 'img/icon-192.png',
    vibrate: [250, 120, 250, 120, 500], requireInteraction: true, data: { url: m.url || 'menu.php' }
  });
})()));

self.addEventListener('notificationclick', e => {
  e.notification.close();
  const url = new URL((e.notification.data && e.notification.data.url) || 'menu.php', self.registration.scope).href;
  e.waitUntil(self.clients.matchAll({ type: 'window', includeUncontrolled: true }).then(list => {
    for (const c of list) { if ('focus' in c) { if (c.navigate) c.navigate(url); return c.focus(); } }
    return self.clients.openWindow(url);
  }));
});
