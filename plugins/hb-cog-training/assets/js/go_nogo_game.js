/**
 * משחק Go/No-Go - מסלול גיל שלישי
 */

// קבלת base URL מה-global או מ-import.meta.url
const getBaseUrl = () => {
  if (typeof hb_cog_vars !== 'undefined' && hb_cog_vars.base_url) {
    return hb_cog_vars.base_url;
  }
  // Fallback - שימוש ב-import.meta.url
  try {
    const currentUrl = new URL(import.meta.url);
    return currentUrl.href.substring(0, currentUrl.href.lastIndexOf('/') + 1);
  } catch(e) {
    // אם גם זה לא עובד, נשתמש ב-relative
    return './';
  }
};

const baseUrl = getBaseUrl();

// Dynamic imports עם base URL
let CONFIG_SENIOR, computeGameScore, computeDomainContrib, getSpeedLabel;
let saveAttempt, updateDailySummary, saveToServer, getDailySummary;

async function loadDependencies() {
  try {
    const configModule = await import(new URL('config_senior.js', baseUrl).href);
    const scoringModule = await import(new URL('scoring.js', baseUrl).href);
    const storageModule = await import(new URL('storage_local.js', baseUrl).href);
    
    CONFIG_SENIOR = configModule.CONFIG_SENIOR;
    computeGameScore = scoringModule.computeGameScore;
    computeDomainContrib = scoringModule.computeDomainContrib;
    getSpeedLabel = scoringModule.getSpeedLabel;
    saveAttempt = storageModule.saveAttempt;
    updateDailySummary = storageModule.updateDailySummary;
    saveToServer = storageModule.saveToServer;
    getDailySummary = storageModule.getDailySummary;
    
    return true;
  } catch(e) {
    console.error('HB Cog Training: Failed to load dependencies:', e);
    return false;
  }
}

document.addEventListener('DOMContentLoaded', async () => {
  console.log('HB Cog Training: DOM loaded, loading dependencies...');
  
  // טעינת dependencies
  const loaded = await loadDependencies();
  if (!loaded) {
    console.error('HB Cog Training: Failed to load dependencies');
    // הצגת הודעת שגיאה ב-containers
    document.querySelectorAll('.hb-cog-game-container').forEach(container => {
      container.innerHTML = '<div class="hb-cog-error" style="padding:20px;color:#c0392b;">שגיאה בטעינת המשחק. נא לרענן את הדף.</div>';
    });
    return;
  }
  
  console.log('HB Cog Training: Dependencies loaded, looking for game containers...');
  
  const containers = document.querySelectorAll('.hb-cog-game-container[data-hb-cog-game="go_nogo"]');
  
  console.log('HB Cog Training: Found', containers.length, 'containers');
  
  if (containers.length === 0) {
    console.warn('HB Cog Training: No game containers found. Check if shortcode is rendered correctly.');
    return;
  }
  
  containers.forEach((container, index) => {
    console.log('HB Cog Training: Initializing container', index + 1);
    initGoNoGoGame(container);
  });
});

function initGoNoGoGame(container) {
  if (!container) {
    console.error('HB Cog Training: Container is null');
    return;
  }
  
  console.log('HB Cog Training: Initializing Go/No-Go game', container);
  
  const game = container.dataset.hbCogGame;
  const track = container.dataset.hbCogTrack || 'senior';
  const difficulty = parseInt(container.dataset.hbCogDifficulty || '1', 10);
  
  console.log('HB Cog Training: Game config', { game, track, difficulty });
  
  // CONFIG_SENIOR כבר נטען ב-loadDependencies
  if (!CONFIG_SENIOR || !CONFIG_SENIOR.go_nogo) {
    console.error('HB Cog Training: CONFIG_SENIOR not available');
    container.innerHTML = '<div class="hb-cog-error" style="padding:20px;color:#c0392b;">שגיאה בטעינת המשחק. נא לרענן את הדף.</div>';
    return;
  }
  
  const config = CONFIG_SENIOR.go_nogo;
  const diffConfig = config.difficulty[difficulty] || config.difficulty[1];
  
  console.log('HB Cog Training: Config loaded successfully');
  
  let gameState = {
    started: false,
    startTime: null,
    endTime: null,
    trials: [],
    currentTrial: null,
    timeoutId: null,
    sessionTimeoutId: null
  };
  
  // יצירת UI
  renderGameUI(container);
  
  const startBtn = container.querySelector('.hb-cog-start-btn');
  const gameArea = container.querySelector('.hb-cog-game-area');
  const stimulusEl = container.querySelector('.hb-cog-stimulus');
  const timerEl = container.querySelector('.hb-cog-timer');
  const resultsEl = container.querySelector('.hb-cog-results');
  
  startBtn.addEventListener('click', () => {
    startGame();
  });
  
  function renderGameUI(container) {
    container.innerHTML = `
      <div class="hb-cog-game-wrapper">
        <div class="hb-cog-game-header">
          <h3>משחק Go/No-Go</h3>
          <p class="hb-cog-instructions">
            לחץ על העיגול הירוק, אל תלחץ על העיגול האדום
          </p>
        </div>
        
        <div class="hb-cog-game-area" style="display:none;">
          <div class="hb-cog-stimulus"></div>
          <div class="hb-cog-timer">90</div>
        </div>
        
        <div class="hb-cog-start-screen">
          <button class="hb-cog-start-btn">התחל משחק</button>
        </div>
        
        <div class="hb-cog-results" style="display:none;"></div>
      </div>
    `;
  }
  
  function startGame() {
    gameState.started = true;
    gameState.startTime = Date.now();
    gameState.trials = [];
    
    container.querySelector('.hb-cog-start-screen').style.display = 'none';
    gameArea.style.display = 'block';
    
    // טיימר סשן
    let timeLeft = config.session_duration_ms / 1000;
    timerEl.textContent = Math.ceil(timeLeft);
    
    const timerInterval = setInterval(() => {
      timeLeft -= 1;
      if (timeLeft <= 0) {
        clearInterval(timerInterval);
        endGame().catch(err => console.error('Error ending game:', err));
        return;
      }
      timerEl.textContent = Math.ceil(timeLeft);
    }, 1000);
    
    gameState.sessionTimeoutId = setTimeout(() => {
      clearInterval(timerInterval);
      endGame().catch(err => console.error('Error ending game:', err));
    }, config.session_duration_ms);
    
    // התחלת ניסיונות
    scheduleNextTrial();
    
    // טיפול בלחיצות
    stimulusEl.addEventListener('click', handleStimulusClick);
    stimulusEl.addEventListener('touchend', handleStimulusClick);
  }
  
  function scheduleNextTrial() {
    if (!gameState.started) return;
    
    const interval = diffConfig.interval_min_ms + 
                     Math.random() * (diffConfig.interval_max_ms - diffConfig.interval_min_ms);
    
    gameState.timeoutId = setTimeout(() => {
      showStimulus();
    }, interval);
  }
  
  function showStimulus() {
    if (!gameState.started) return;
    
    // החלטה: GO או NO-GO
    const isGo = Math.random() < config.go_ratio;
    const stimulusType = isGo ? 'go' : 'nogo';
    
    gameState.currentTrial = {
      type: stimulusType,
      shownAt: Date.now(),
      clicked: false,
      clickTime: null,
      rt: null
    };
    
    // הצגת גירוי
    stimulusEl.className = `hb-cog-stimulus hb-cog-${stimulusType}`;
    stimulusEl.style.display = 'block';
    
    // הסתרה אחרי זמן מסוים
    setTimeout(() => {
      if (gameState.currentTrial && !gameState.currentTrial.clicked) {
        // ניסיון שלא נלחץ
        gameState.currentTrial.clicked = false;
        recordTrial();
      }
      stimulusEl.style.display = 'none';
      scheduleNextTrial();
    }, diffConfig.stimulus_duration_ms);
  }
  
  function handleStimulusClick(e) {
    e.preventDefault();
    if (!gameState.currentTrial || gameState.currentTrial.clicked) return;
    
    const clickTime = Date.now();
    gameState.currentTrial.clicked = true;
    gameState.currentTrial.clickTime = clickTime;
    gameState.currentTrial.rt = clickTime - gameState.currentTrial.shownAt;
    
    stimulusEl.style.display = 'none';
    recordTrial();
    scheduleNextTrial();
  }
  
  function recordTrial() {
    if (!gameState.currentTrial) return;
    
    const trial = gameState.currentTrial;
    gameState.trials.push(trial);
    gameState.currentTrial = null;
  }
  
  async function endGame() {
    gameState.started = false;
    gameState.endTime = Date.now();
    
    if (gameState.timeoutId) {
      clearTimeout(gameState.timeoutId);
    }
    if (gameState.sessionTimeoutId) {
      clearTimeout(gameState.sessionTimeoutId);
    }
    
    stimulusEl.style.display = 'none';
    gameArea.style.display = 'none';
    
    // חישוב מדדים
    const metrics = calculateMetrics();
    const scores = await computeGameScore(metrics, diffConfig);
    const domainWeights = CONFIG_SENIOR.domain_mapping.go_nogo;
    const domainContrib = await computeDomainContrib(scores.game_score, domainWeights);
    
    // בניית ניסיון לשמירה
    const dateIso = new Date().toISOString().split('T')[0];
    const attempt = {
      user_id: typeof hb_cog_vars !== 'undefined' ? hb_cog_vars.user_id : null,
      track: track,
      game_id: 'go_nogo',
      difficulty: difficulty,
      attempt_no: 1, // TODO: לחשב מהניסיונות הקודמים
      started_at: gameState.startTime,
      ended_at: gameState.endTime,
      date_iso: dateIso,
      metrics: metrics,
      scores: scores,
      domain_contrib: domainContrib
    };
    
    // שמירה
    saveAttempt(attempt);
    updateDailySummary(dateIso, {
      date_iso: dateIso,
      track: track,
      daily_score: scores.game_score,
      domains: domainContrib
    });
    saveToServer(attempt);
    
    // הצגת תוצאות
    showResults(metrics, scores, domainContrib).catch(err => console.error('Error showing results:', err));
  }
  
  function calculateMetrics() {
    const trials = gameState.trials;
    if (trials.length === 0) {
      return {
        accuracy: 0,
        mean_rt_ms: 0,
        stability01: 0
      };
    }
    
    // הפרדת GO ו-NO-GO
    const goTrials = trials.filter(t => t.type === 'go');
    const nogoTrials = trials.filter(t => t.type === 'nogo');
    
    // דיוק GO: אחוז לחיצות נכונות
    const goHits = goTrials.filter(t => t.clicked).length;
    const goHitRate = goTrials.length > 0 ? goHits / goTrials.length : 0;
    
    // דיוק NO-GO: אחוז אי-לחיצות נכונות
    const nogoCorrect = nogoTrials.filter(t => !t.clicked).length;
    const nogoCorrectRate = nogoTrials.length > 0 ? nogoCorrect / nogoTrials.length : 0;
    
    // דיוק כולל משוקלל
    const overallAccuracy = goHitRate * config.go_ratio + nogoCorrectRate * config.no_go_ratio;
    
    // זמן תגובה ממוצע (רק GO נכונים)
    const correctGoTrials = goTrials.filter(t => t.clicked && t.rt);
    let meanRt = 0;
    if (correctGoTrials.length > 0) {
      const rts = correctGoTrials.map(t => t.rt);
      meanRt = rts.reduce((a, b) => a + b, 0) / rts.length;
      
      // חישוב יציבות (CV)
      const mean = meanRt;
      const variance = rts.reduce((sum, rt) => sum + Math.pow(rt - mean, 2), 0) / rts.length;
      const stdDev = Math.sqrt(variance);
      const cv = mean > 0 ? stdDev / mean : 0;
      
      const cvTarget = config.stability.cv_target;
      const stability01 = Math.max(0, Math.min(1, 1 - (cv / cvTarget)));
      
      return {
        accuracy: overallAccuracy,
        mean_rt_ms: meanRt,
        stability01: stability01
      };
    }
    
    return {
      accuracy: overallAccuracy,
      mean_rt_ms: 0,
      stability01: 0
    };
  }
  
  async function showResults(metrics, scores, domainContrib) {
    const speedLabel = await getSpeedLabel(scores.speedScore);
    const dateIso = new Date().toISOString().split('T')[0];
    const dailySummary = getDailySummary(dateIso);
    
    let dailyScoreHTML = '';
    if (dailySummary && dailySummary.daily_score) {
      dailyScoreHTML = `
        <div class="hb-cog-result-item">
          <span class="hb-cog-result-label">ציון יומי:</span>
          <span class="hb-cog-result-value">${dailySummary.daily_score}/100</span>
        </div>
      `;
    }
    
    resultsEl.innerHTML = `
      <div class="hb-cog-results-box">
        <h3>תוצאות המשחק</h3>
        <div class="hb-cog-results-content">
          <div class="hb-cog-result-item">
            <span class="hb-cog-result-label">ציון משחק:</span>
            <span class="hb-cog-result-value">${scores.game_score}/100</span>
          </div>
          ${dailyScoreHTML}
          <div class="hb-cog-result-item">
            <span class="hb-cog-result-label">דיוק:</span>
            <span class="hb-cog-result-value">${Math.round(metrics.accuracy * 100)}%</span>
          </div>
          <div class="hb-cog-result-item">
            <span class="hb-cog-result-label">זמן תגובה ממוצע:</span>
            <span class="hb-cog-result-value">${Math.round(metrics.mean_rt_ms)}ms</span>
          </div>
          <div class="hb-cog-result-item">
            <span class="hb-cog-result-label">קצב:</span>
            <span class="hb-cog-result-value">${speedLabel}</span>
          </div>
          <div class="hb-cog-result-item">
            <span class="hb-cog-result-label">יציבות:</span>
            <span class="hb-cog-result-value">${scores.stabilityScore}/100</span>
          </div>
        </div>
        <div class="hb-cog-disclaimer">
          <small>הערה: משחק זה אינו כלי אבחון רפואי. הוא מיועד לאימון קוגניטיבי בלבד.</small>
        </div>
        <button class="hb-cog-play-again-btn" onclick="location.reload()">שחק שוב</button>
      </div>
    `;
    
    resultsEl.style.display = 'block';
  }
}

