/**
 * שמירה מקומית (localStorage)
 */
const STORAGE_KEYS = {
  attempts: 'hb_cog_attempts_v1',
  daily: 'hb_cog_daily_v1'
};

/**
 * שמירת ניסיון
 * @param {Object} attempt - נתוני הניסיון
 */
export function saveAttempt(attempt) {
  try {
    let attempts = [];
    const stored = localStorage.getItem(STORAGE_KEYS.attempts);
    if (stored) {
      attempts = JSON.parse(stored);
      if (!Array.isArray(attempts)) attempts = [];
    }
    
    attempts.push(attempt);
    
    // שמירת רק 200 ניסיונות אחרונים
    if (attempts.length > 200) {
      attempts = attempts.slice(-200);
    }
    
    localStorage.setItem(STORAGE_KEYS.attempts, JSON.stringify(attempts));
    return true;
  } catch(e) {
    console.warn('Failed to save attempt to localStorage:', e);
    return false;
  }
}

/**
 * עדכון סיכום יומי
 * @param {string} dateIso - תאריך YYYY-MM-DD
 * @param {Object} dailyData - נתוני יום
 */
export function updateDailySummary(dateIso, dailyData) {
  try {
    let dailySummaries = {};
    const stored = localStorage.getItem(STORAGE_KEYS.daily);
    if (stored) {
      dailySummaries = JSON.parse(stored);
      if (typeof dailySummaries !== 'object') dailySummaries = {};
    }
    
    dailySummaries[dateIso] = {
      ...dailyData,
      updated_at: Date.now()
    };
    
    localStorage.setItem(STORAGE_KEYS.daily, JSON.stringify(dailySummaries));
    return true;
  } catch(e) {
    console.warn('Failed to update daily summary:', e);
    return false;
  }
}

/**
 * קבלת סיכום יומי
 * @param {string} dateIso - תאריך YYYY-MM-DD
 * @returns {Object|null}
 */
export function getDailySummary(dateIso) {
  try {
    const stored = localStorage.getItem(STORAGE_KEYS.daily);
    if (!stored) return null;
    
    const dailySummaries = JSON.parse(stored);
    return dailySummaries[dateIso] || null;
  } catch(e) {
    console.warn('Failed to get daily summary:', e);
    return null;
  }
}

/**
 * קבלת כל הסיכומים היומיים
 * @returns {Object}
 */
export function getAllDailySummaries() {
  try {
    const stored = localStorage.getItem(STORAGE_KEYS.daily);
    if (!stored) return {};
    
    return JSON.parse(stored);
  } catch(e) {
    console.warn('Failed to get daily summaries:', e);
    return {};
  }
}

/**
 * שמירה לשרת (אם משתמש מחובר)
 * @param {Object} attempt - נתוני הניסיון
 * @returns {Promise}
 */
export function saveToServer(attempt) {
  if (typeof hb_cog_vars === 'undefined' || !hb_cog_vars.ajaxurl || !hb_cog_vars.user_id) {
    return Promise.resolve(false);
  }
  
  const data = new URLSearchParams();
  data.set('action', 'hb_cog_save_attempt');
  data.set('attempt', JSON.stringify(attempt));
  data.set('_ajax_nonce', hb_cog_vars.nonce || '');
  
  return fetch(hb_cog_vars.ajaxurl, {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'},
    body: data.toString(),
    credentials: 'same-origin'
  })
  .then(r => r.json())
  .then(res => {
    if (res.success) {
      return true;
    } else {
      console.warn('Server save failed:', res.data);
      return false;
    }
  })
  .catch(err => {
    console.warn('Failed to save to server:', err);
    return false;
  });
}


