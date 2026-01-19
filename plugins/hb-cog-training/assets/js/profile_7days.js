/**
 * פרופיל 7 ימים
 */

// Dynamic import
let getAllDailySummaries = null;

async function loadStorageModule() {
  if (getAllDailySummaries) return;
  
  try {
    const baseUrl = typeof hb_cog_vars !== 'undefined' && hb_cog_vars.base_url 
      ? hb_cog_vars.base_url 
      : new URL('.', import.meta.url).href;
    
    const storageModule = await import(new URL('storage_local.js', baseUrl).href);
    getAllDailySummaries = storageModule.getAllDailySummaries;
  } catch(e) {
    console.error('HB Cog Training: Failed to load storage module:', e);
  }
}

document.addEventListener('DOMContentLoaded', async () => {
  console.log('HB Cog Training: Profile script loaded');
  
  await loadStorageModule();
  
  if (!getAllDailySummaries) {
    console.error('HB Cog Training: Storage module not loaded');
    document.querySelectorAll('.hb-cog-profile-container').forEach(container => {
      container.innerHTML = '<div class="hb-cog-profile-error">שגיאה בטעינת הפרופיל. נא לרענן את הדף.</div>';
    });
    return;
  }
  
  const containers = document.querySelectorAll('.hb-cog-profile-container');
  
  console.log('HB Cog Training: Found', containers.length, 'profile containers');
  
  containers.forEach((container, index) => {
    console.log('HB Cog Training: Initializing profile container', index + 1);
    initProfile(container);
  });
});

function initProfile(container) {
  const track = container.dataset.hbCogTrack || 'senior';
  const days = parseInt(container.dataset.hbCogDays || '7', 10);
  
  // ניסיון לטעון מהשרת (אם משתמש מחובר)
  if (typeof hb_cog_vars !== 'undefined' && hb_cog_vars.ajaxurl && hb_cog_vars.user_id) {
    loadFromServer(track, days, container);
  } else {
    // נפילה חזרה ל-localStorage
    loadFromLocalStorage(track, days, container);
  }
}

function loadFromServer(track, days, container) {
  const data = new URLSearchParams();
  data.set('action', 'hb_cog_get_profile');
  data.set('track', track);
  data.set('days', days);
  data.set('_ajax_nonce', hb_cog_vars.nonce || '');
  
  fetch(hb_cog_vars.ajaxurl, {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'},
    body: data.toString(),
    credentials: 'same-origin'
  })
  .then(r => r.json())
  .then(res => {
    if (res.success && res.data && res.data.profile_data) {
      renderProfile(res.data.profile_data, container);
    } else {
      // נפילה חזרה ל-localStorage
      loadFromLocalStorage(track, days, container);
    }
  })
  .catch(err => {
    console.warn('Failed to load profile from server:', err);
    loadFromLocalStorage(track, days, container);
  });
}

function loadFromLocalStorage(track, days, container) {
  try {
    const allSummaries = getAllDailySummaries();
    const profileData = [];
    const today = new Date();
    
    for (let i = 0; i < days; i++) {
      const date = new Date(today);
      date.setDate(date.getDate() - i);
      const dateIso = date.toISOString().split('T')[0];
      
      const summary = allSummaries[dateIso];
      if (summary && summary.track === track) {
        profileData.push({
          date_iso: dateIso,
          daily_score: summary.daily_score || 0,
          domains: summary.domains || {}
        });
      } else {
        profileData.push({
          date_iso: dateIso,
          daily_score: 0,
          domains: {}
        });
      }
    }
    
    renderProfile(profileData, container);
  } catch(e) {
    console.error('Failed to load from localStorage:', e);
    showError(container, 'שגיאה בטעינת הנתונים');
  }
}

function renderProfile(profileData, container) {
  const loadingEl = container.querySelector('.hb-cog-profile-loading');
  if (loadingEl) loadingEl.style.display = 'none';
  
  // חישוב streak
  let streak = 0;
  for (const day of profileData) {
    if (day.daily_score >= 68) {
      streak++;
    } else {
      break;
    }
  }
  
  // חישוב ממוצע
  const scores = profileData.map(d => d.daily_score).filter(s => s > 0);
  const avgScore = scores.length > 0 
    ? Math.round(scores.reduce((a, b) => a + b, 0) / scores.length)
    : 0;
  
  // מציאת תחום מוביל (אם יש)
  let topDomain = null;
  if (profileData.length > 0 && profileData[0].domains) {
    const domains = profileData[0].domains;
    const entries = Object.entries(domains);
    if (entries.length > 0) {
      entries.sort((a, b) => b[1] - a[1]);
      topDomain = entries[0];
    }
  }
  
  let tableHTML = `
    <div class="hb-cog-profile-header">
      <h3>פרופיל ${profileData.length} ימים</h3>
      <div class="hb-cog-profile-stats">
        <div class="hb-cog-stat-item">
          <div class="hb-cog-stat-value">${streak}</div>
          <div class="hb-cog-stat-label">רצף ימים</div>
        </div>
        <div class="hb-cog-stat-item">
          <div class="hb-cog-stat-value">${avgScore}</div>
          <div class="hb-cog-stat-label">ציון ממוצע</div>
        </div>
      </div>
    </div>
    <table class="hb-cog-profile-table">
      <thead>
        <tr>
          <th>תאריך</th>
          <th>ציון יומי</th>
          ${topDomain ? `<th>${getDomainLabel(topDomain[0])}</th>` : ''}
        </tr>
      </thead>
      <tbody>
  `;
  
  profileData.forEach(day => {
    const dateObj = new Date(day.date_iso);
    const dateStr = dateObj.toLocaleDateString('he-IL', {day: '2-digit', month: '2-digit'});
    const domainValue = topDomain ? (day.domains[topDomain[0]] || 0) : null;
    
    tableHTML += `
      <tr>
        <td>${dateStr}</td>
        <td>${day.daily_score > 0 ? day.daily_score : '-'}</td>
        ${domainValue !== null ? `<td>${domainValue}</td>` : ''}
      </tr>
    `;
  });
  
  tableHTML += `
      </tbody>
    </table>
  `;
  
  container.innerHTML = tableHTML;
}

function getDomainLabel(domain) {
  const labels = {
    attention: 'קשב',
    reasoning_flexibility: 'גמישות מחשבתית',
    processing_speed: 'מהירות עיבוד',
    working_memory: 'זיכרון עבודה',
    visual_perception: 'תפיסה חזותית',
    inhibition: 'עכבה'
  };
  return labels[domain] || domain;
}

function showError(container, message) {
  const loadingEl = container.querySelector('.hb-cog-profile-loading');
  if (loadingEl) loadingEl.style.display = 'none';
  
  container.innerHTML = `<div class="hb-cog-profile-error">${message}</div>`;
}


