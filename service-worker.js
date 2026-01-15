// Service Worker for Done-It PWA
const CACHE_NAME = 'doneit-v1';
const urlsToCache = [
  '/done-it-app/lumina-vault.html',
  '/done-it-app/index.html',
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

// Background Sync for reminders
self.addEventListener('sync', (event) => {
  if (event.tag === 'check-reminders') {
    event.waitUntil(checkReminders());
  }
});

// Periodic Background Sync (if supported)
self.addEventListener('periodicsync', (event) => {
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
      icon: icon || '/done-it-app/icon-192.png',
      badge: '/done-it-app/icon-192.png',
      tag: tag,
      requireInteraction: false,
      silent: false,
      vibrate: [200, 100, 200],
      data: {
        url: '/done-it-app/lumina-vault.html'
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
        return clients.openWindow('/done-it-app/lumina-vault.html');
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
  
  // Check task reminders
  storedReminders.tasks.forEach(task => {
    if (task.reminder && task.reminder.enabled && task.reminder.datetime) {
      const reminderTime = new Date(task.reminder.datetime);
      const timeDiff = now.getTime() - reminderTime.getTime();
      
      if (reminderTime <= now && timeDiff < 3600000) {
        // Show notification
        const title = '⏰ תזכורת משימה: ' + task.title;
        const body = `זמן לטפל במשימה: ${task.title}`;
        const tag = `task-reminder-${task.id}`;
        
        self.registration.showNotification(title, {
          body: body,
          icon: '/done-it-app/icon-192.png',
          badge: '/done-it-app/icon-192.png',
          tag: tag,
          requireInteraction: false,
          silent: false,
          vibrate: [200, 100, 200],
          data: {
            url: '/done-it-app/lumina-vault.html',
            taskId: task.id
          }
        });
        console.log('[SW] Showed notification for task:', task.title);
      }
    }
  });
  
  // Check note reminders
  storedReminders.notes.forEach(note => {
    if (note.reminder && note.reminder.enabled && note.reminder.datetime) {
      const reminderTime = new Date(note.reminder.datetime);
      const timeDiff = now.getTime() - reminderTime.getTime();
      
      if (reminderTime <= now && timeDiff < 3600000) {
        // Show notification
        const title = '⏰ תזכורת: ' + (note.title || 'פתק');
        const body = note.body ? note.body.substring(0, 100) + (note.body.length > 100 ? '...' : '') : 'יש לך תזכורת';
        const tag = `note-reminder-${note.space}-${note.index}`;
        
        self.registration.showNotification(title, {
          body: body,
          icon: '/done-it-app/icon-192.png',
          badge: '/done-it-app/icon-192.png',
          tag: tag,
          requireInteraction: false,
          silent: false,
          vibrate: [200, 100, 200],
          data: {
            url: '/done-it-app/lumina-vault.html',
            space: note.space,
            noteIndex: note.index
          }
        });
        console.log('[SW] Showed notification for note:', note.title || 'פתק');
      }
    }
  });
}

// Periodic check for reminders (every 30 seconds when possible)
setInterval(() => {
  checkReminders();
}, 30000); // Check every 30 seconds
