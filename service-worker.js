// Service Worker for Done-It PWA
const CACHE_NAME = 'doneit-v3';
const urlsToCache = [
  './lumina-vault.html',
  './index.html',
  './manifest.json',
  './assets/icon.png',
  'https://cdn.tailwindcss.com',
  'https://fonts.googleapis.com/css2?family=Assistant:wght@300;400;600;800&display=swap'
];

// Install event - cache resources
self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then((cache) => {
        console.log('Opened cache');
        return cache.addAll(urlsToCache);
      })
  );
  self.skipWaiting(); // Activate immediately
});

// Fetch event - serve from cache, fallback to network
self.addEventListener('fetch', (event) => {
  event.respondWith(
    caches.match(event.request)
      .then((response) => {
        // Return cached version or fetch from network
        return response || fetch(event.request);
      })
  );
});

// Activate event - clean up old caches
self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((cacheNames) => {
      return Promise.all(
        cacheNames.map((cacheName) => {
          if (cacheName !== CACHE_NAME) {
            console.log('Deleting old cache:', cacheName);
            return caches.delete(cacheName);
          }
        })
      );
    })
  );
  return self.clients.claim(); // Take control immediately
});

// Background Sync for reminders (works when browser is active)
self.addEventListener('sync', (event) => {
  console.log('[SW] Background Sync event triggered:', event.tag);
  if (event.tag === 'check-reminders') {
    event.waitUntil(checkReminders());
  }
});

// Periodic Background Sync (if supported) - checks reminders periodically even when app is in background
self.addEventListener('periodicsync', (event) => {
  console.log('[SW] Periodic Background Sync event triggered:', event.tag);
  if (event.tag === 'check-reminders') {
    event.waitUntil(checkReminders());
  }
});

// Store reminders in Service Worker memory
let storedReminders = {
  tasks: [],
  notes: []
};

// Message handler for notifications from main app
self.addEventListener('message', (event) => {
  if (event.data && event.data.type === 'SHOW_NOTIFICATION') {
    const { title, body, tag, icon } = event.data;
    self.registration.showNotification(title, {
      body: body,
            icon: icon || './assets/icon.png',
            badge: './assets/icon.png',
      tag: tag,
      requireInteraction: false,
      silent: false,
      vibrate: [200, 100, 200],
      data: {
        url: './lumina-vault.html'
      }
    });
  } else if (event.data && event.data.type === 'UPDATE_REMINDERS') {
    // Store reminders for background checking
    storedReminders.tasks = event.data.tasks || [];
    storedReminders.notes = event.data.notes || [];
    console.log('[SW] Updated reminders:', storedReminders.tasks.length, 'tasks,', storedReminders.notes.length, 'notes');
  } else if (event.data && event.data.type === 'CHECK_REMINDERS') {
    // Check reminders when requested
    checkStoredReminders();
  }
});

// Notification click handler
self.addEventListener('notificationclick', (event) => {
  event.notification.close();
  event.waitUntil(
    clients.matchAll({ type: 'window', includeUncontrolled: true }).then((clientList) => {
      // If app is already open, focus it
      for (let i = 0; i < clientList.length; i++) {
        const client = clientList[i];
        if (client.url.includes('lumina-vault.html') && 'focus' in client) {
          return client.focus();
        }
      }
      // Otherwise open new window
      if (clients.openWindow) {
        return clients.openWindow('./lumina-vault.html');
      }
    })
  );
});

// Check reminders function - checks reminders stored in IndexedDB or via message
async function checkReminders() {
  console.log('[SW] Checking reminders in Service Worker...');
  
  // Check stored reminders
  checkStoredReminders();
  
  // Try to get reminders from clients (main app)
  const clients = await self.clients.matchAll({ includeUncontrolled: true });
  if (clients.length > 0) {
    // Ask main app to check reminders
    clients.forEach(client => {
      client.postMessage({ type: 'CHECK_REMINDERS' });
    });
  }
  
  return Promise.resolve();
}

// Check stored reminders and show notifications
async function checkStoredReminders() {
  const now = new Date();
  console.log('[SW] 🔍 Checking stored reminders at', now.toISOString());
  console.log('[SW] Stored tasks:', storedReminders.tasks.length, 'Stored notes:', storedReminders.notes.length);
  
  if (storedReminders.tasks.length === 0 && storedReminders.notes.length === 0) {
    console.log('[SW] ⚠️ No reminders stored - make sure reminders are sent to Service Worker');
    return;
  }
  
  // Track which reminders we've already shown (to avoid duplicates)
  const shownReminders = new Set();
  let notificationsShown = 0;
  
  // Check task reminders
  storedReminders.tasks.forEach(task => {
    if (task.reminder && task.reminder.enabled && task.reminder.datetime) {
      const reminderTime = new Date(task.reminder.datetime);
      const timeDiff = now.getTime() - reminderTime.getTime();
      const reminderKey = `task-${task.id}-${task.reminder.datetime}`;
      
      // Show notification if reminder time passed (within 24 hours window)
      const shouldShow = reminderTime <= now && timeDiff < 86400000; // 24 hours window
      
      console.log('[SW] 🔍 Checking task reminder:', task.title, {
        reminderTime: reminderTime.toISOString(),
        now: now.toISOString(),
        timeDiff: timeDiff,
        minutesAgo: Math.floor(timeDiff / 60000),
        shouldShow: shouldShow
      });
      
      if (shouldShow && !shownReminders.has(reminderKey)) {
        // Show notification
        const title = '⏰ תזכורת משימה: ' + task.title;
        const body = `זמן לטפל במשימה: ${task.title}`;
        const tag = `task-reminder-${task.id}`;
        
        try {
          await self.registration.showNotification(title, {
            body: body,
            icon: '/assets/icon.png',
            badge: '/assets/icon.png',
            tag: tag,
            requireInteraction: false,
            silent: false,
            vibrate: [200, 100, 200],
            data: {
              url: './lumina-vault.html',
              taskId: task.id
            }
          });
          console.log('[SW] ✅✅✅ Showed notification for task:', task.title);
          shownReminders.add(reminderKey);
          notificationsShown++;
        } catch (e) {
          console.error('[SW] ❌ Failed to show notification for task:', task.title, e);
        }
      }
    }
  });
  
  // Check note reminders
  storedReminders.notes.forEach(note => {
    if (note.reminder && note.reminder.enabled && note.reminder.datetime) {
      const reminderTime = new Date(note.reminder.datetime);
      const timeDiff = now.getTime() - reminderTime.getTime();
      const reminderKey = `note-${note.space}-${note.index}-${note.reminder.datetime}`;
      
      // Show notification if reminder time passed (within 24 hours window)
      const shouldShow = reminderTime <= now && timeDiff < 86400000; // 24 hours window
      
      console.log('[SW] 🔍 Checking note reminder:', note.title || 'פתק', {
        reminderTime: reminderTime.toISOString(),
        now: now.toISOString(),
        timeDiff: timeDiff,
        minutesAgo: Math.floor(timeDiff / 60000),
        shouldShow: shouldShow
      });
      
      if (shouldShow && !shownReminders.has(reminderKey)) {
        // Show notification
        const title = '⏰ תזכורת: ' + (note.title || 'פתק');
        const body = note.body ? note.body.substring(0, 100) + (note.body.length > 100 ? '...' : '') : 'יש לך תזכורת';
        const tag = `note-reminder-${note.space}-${note.index}`;
        
        try {
          await self.registration.showNotification(title, {
            body: body,
            icon: '/assets/icon.png',
            badge: '/assets/icon.png',
            tag: tag,
            requireInteraction: false,
            silent: false,
            vibrate: [200, 100, 200],
            data: {
              url: './lumina-vault.html',
              space: note.space,
              noteIndex: note.index
            }
          });
          console.log('[SW] ✅✅✅ Showed notification for note:', note.title || 'פתק');
          shownReminders.add(reminderKey);
          notificationsShown++;
        } catch (e) {
          console.error('[SW] ❌ Failed to show notification for note:', note.title || 'פתק', e);
        }
      }
    }
  });
  
  console.log('[SW] 📊 Check complete. Notifications shown:', notificationsShown);
}

// NOTE: setInterval doesn't work reliably in Service Workers!
// Instead, we rely on:
// 1. The main app checking reminders every 3 seconds when open
// 2. Background Sync events (if supported by browser)
// 3. Immediate check when app opens (via message from main app)