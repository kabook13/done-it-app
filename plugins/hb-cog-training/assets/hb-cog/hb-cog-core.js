/*!
 * HB Cognitive Training - Core Engine
 * Handles timer, start/stop, end screen, metrics API
 */

(function() {
  'use strict';

  // Registry for game modules
  window.HB_COG_GAMES = window.HB_COG_GAMES || {};

  /**
   * Core Game Engine
   * Provides common functionality for all games
   */
  function HB_COG_Core(container, gameId, config) {
    this.container = container;
    this.gameId = gameId;
    this.config = config || {};
    this.track = container.getAttribute('data-hb-cog-track') || 'senior';
    this.difficulty = parseInt(container.getAttribute('data-hb-cog-difficulty') || '1', 10);
    
    // State
    this.running = false;
    this.t0 = 0;
    this.sessionEnd = 0;
    this.tickInterval = null;
    
    // DOM elements (will be set by init)
    this.startBtn = null;
    this.stopBtn = null;
    this.gameArea = null;
    this.timerEl = null;
    this.results = null;
    this.cta = null;
    
    // Navigation URLs
    this.nextUrl = '';
    this.backUrl = '';
    
    // Game module instance
    this.gameModule = null;
  }

  HB_COG_Core.prototype.init = function() {
    // Get navigation URLs
    var pageWrap = this.container.closest('.hb-cog-game-page');
    this.nextUrl = pageWrap ? (pageWrap.getAttribute('data-next-url') || '') : '';
    this.backUrl = pageWrap ? (pageWrap.getAttribute('data-back-url') || '') : '';
    
    // Load game module
    var GameModule = window.HB_COG_GAMES[this.gameId];
    if (!GameModule) {
      console.error('HB_COG: Game module not found:', this.gameId);
      this.container.innerHTML = '<div class="hb-cog-error">משחק לא נמצא: ' + this.gameId + '</div>';
      return;
    }
    
    this.gameModule = new GameModule(this.container, this.config, this);
    this.gameModule.init();
    
    // Setup DOM references
    this.startBtn = this.container.querySelector('.hb-cog-start-btn');
    this.stopBtn = this.container.querySelector('.hb-cog-stop-btn');
    this.gameArea = this.container.querySelector('.hb-cog-game-area');
    this.timerEl = this.container.querySelector('.hb-cog-timer');
    this.results = this.container.querySelector('.hb-cog-results');
    this.cta = this.container.querySelector('.hb-cog-finish-cta');
    
    // Setup event listeners
    if (this.startBtn) {
      this.startBtn.addEventListener('click', this.start.bind(this));
    }
    if (this.stopBtn) {
      this.stopBtn.addEventListener('click', this.finishNow.bind(this));
    }
  };

  HB_COG_Core.prototype.formatTime = function(ms) {
    var s = Math.max(0, Math.ceil(ms / 1000));
    var m = Math.floor(s / 60);
    var ss = String(s % 60).padStart(2, '0');
    return m + ':' + ss;
  };

  HB_COG_Core.prototype.start = function() {
    if (this.running) return;
    
    this.running = true;
    if (this.results) {
      this.results.classList.remove('show');
      this.results.innerHTML = '';
    }
    if (this.gameArea) this.gameArea.style.display = 'block';
    if (this.stopBtn) this.stopBtn.style.display = 'inline-block';
    if (this.startBtn) this.startBtn.style.display = 'none';
    
    // Reset game module
    if (this.gameModule && this.gameModule.reset) {
      this.gameModule.reset();
    }
    
    // Start timer
    var sessionMs = this.config.session_duration_ms || 300000;
    this.t0 = Date.now();
    this.sessionEnd = this.t0 + sessionMs;
    if (this.timerEl) this.timerEl.textContent = this.formatTime(sessionMs);
    
    var self = this;
    this.tickInterval = setInterval(function() {
      var left = self.sessionEnd - Date.now();
      if (self.timerEl) self.timerEl.textContent = self.formatTime(left);
      if (left <= 0) {
        self.finalizeAndShowResults();
      }
    }, 250);
    
    // Start game module
    if (this.gameModule && this.gameModule.start) {
      this.gameModule.start();
    }
  };

  HB_COG_Core.prototype.stop = function() {
    this.running = false;
    if (this.tickInterval) {
      clearInterval(this.tickInterval);
      this.tickInterval = null;
    }
    if (this.gameArea) this.gameArea.style.display = 'none';
    if (this.stopBtn) this.stopBtn.style.display = 'none';
    if (this.startBtn) this.startBtn.style.display = 'inline-block';
    
    // Stop game module
    if (this.gameModule && this.gameModule.stop) {
      this.gameModule.stop();
    }
  };

  HB_COG_Core.prototype.finishNow = function() {
    if (!this.running) return;
    this.finalizeAndShowResults();
  };

  HB_COG_Core.prototype.buildNextUrlFallback = function() {
    var root = document.querySelector('[data-hb-cog-root]') || document.body;
    
    var get = function(k, d) {
      var v = (root.dataset && root.dataset[k]) ? root.dataset[k] : '';
      return v !== '' ? v : d;
    };
    
    var url = new URL(window.location.href);
    var dur = url.searchParams.get('dur') || get('dur', '5');
    var diff = parseInt(url.searchParams.get('diff') || get('diff', '1'), 10) || 1;
    var order = url.searchParams.get('order') || '';
    var i = parseInt(url.searchParams.get('i') || '0', 10);
    
    // If we have order + i, try to advance to next game
    if (order && order.length > 0) {
      var orderArr = order.split(',').map(function(s) { return s.trim(); });
      if (i + 1 < orderArr.length) {
        // Next game in sequence
        var nextToken = orderArr[i + 1];
        var nextGame = nextToken;
        var nextDiff = diff;
        
        if (nextToken.indexOf('@') !== -1) {
          var parts = nextToken.split('@');
          nextGame = parts[0].trim();
          nextDiff = parseInt(parts[1] || String(diff), 10);
        }
        
        var base = window.HB_COG_GAME_PAGES && window.HB_COG_GAME_PAGES[nextGame] 
          ? window.HB_COG_GAME_PAGES[nextGame] 
          : '/אימון-קוגניטיבי/';
        
        url = new URL(window.location.origin + base);
        url.searchParams.set('game', nextGame);
        url.searchParams.set('diff', String(nextDiff));
        url.searchParams.set('order', order);
        url.searchParams.set('i', String(i + 1));
        url.searchParams.set('dur', dur);
        
        return url.toString();
      } else {
        // End of sequence - show completion screen
        return null; // Will be handled by showing completion message
      }
    }
    
    // Fallback: same game, diff+1
    var nextDiff = diff + 1;
    url.searchParams.set('dur', String(dur));
    url.searchParams.set('diff', String(nextDiff));
    if (order) url.searchParams.set('order', order);
    if (i > 0) url.searchParams.set('i', String(i));
    
    return url.toString();
  };

  HB_COG_Core.prototype.finalizeAndShowResults = function() {
    this.stop();
    
    if (!this.gameModule || !this.gameModule.getMetrics) {
      console.error('HB_COG: Game module missing getMetrics');
      return;
    }
    
    var metrics = this.gameModule.getMetrics();
    var scoreObj = this.gameModule.getScore ? this.gameModule.getScore(metrics) : { game_score: 0 };
    var tips = this.gameModule.getTips ? this.gameModule.getTips(metrics) : [];
    
    if (!tips || tips.length === 0) {
      tips = ['אחלה עבודה. כדי לשמור על התנופה, מומלץ להוסיף עוד אימון קצר או לעבור לאימון הבא.'];
    }
    
    // Render results
    if (this.results) {
      this.results.classList.add('show');
      var resultsHTML = this.gameModule.renderResults ? 
        this.gameModule.renderResults(metrics, scoreObj, tips) :
        this.defaultRenderResults(metrics, scoreObj, tips);
      this.results.innerHTML = resultsHTML;
    }
    
    // Render CTA
    if (this.cta) {
      var root = document.querySelector('[data-hb-cog-root]');
      var serverNext = (root && root.dataset && root.dataset.nextUrl) ? root.dataset.nextUrl : '';
      
      if (!serverNext || serverNext.length <= 5) {
        var pageWrap = this.container.closest('.hb-cog-game-page');
        if (pageWrap) {
          serverNext = pageWrap.getAttribute('data-next-url') || '';
        }
      }
      
      if (!serverNext || serverNext.length <= 5) {
        serverNext = this.nextUrl || '';
      }
      
      var finalNextUrl = (serverNext && serverNext.length > 5) 
        ? serverNext 
        : this.buildNextUrlFallback();
      
      if (finalNextUrl) {
        this.cta.innerHTML = '<a class="hb-card-button" href="' + finalNextUrl + '" style="width:auto;background:#777;">אימון נוסף</a>';
      } else {
        // End of sequence
        this.cta.innerHTML = '<div style="text-align:center;padding:20px;background:#e8f5e9;border-radius:8px;margin-top:16px;"><strong>סיימת את האימון היומי!</strong><br>כל הכבוד על ההתמדה.</div>';
      }
    }
    
    // Save attempt
    this.saveAttempt(metrics, scoreObj);
  };

  HB_COG_Core.prototype.defaultRenderResults = function(metrics, scoreObj, tips) {
    return '<div class="hb-cog-results-grid">' +
      '<div class="hb-cog-result-card"><div class="hb-cog-result-label">ציון</div><div class="hb-cog-result-value">' + scoreObj.game_score + '</div></div>' +
      '<div class="hb-cog-result-card"><div class="hb-cog-result-label">דיוק</div><div class="hb-cog-result-value">' + Math.round((metrics.accuracy || 0) * 100) + '%</div></div>' +
      '</div>' +
      '<div class="hb-cog-result-disclaimer">' + tips.join('<br>') + '</div>' +
      '<div style="display:flex;gap:10px;flex-wrap:wrap;justify-content:center;margin-top:16px;">' +
      (this.nextUrl ? '<a class="hb-card-button" href="' + this.nextUrl + '" style="width:auto;">לאימון הבא</a>' : '') +
      (this.backUrl ? '<a class="hb-card-button" href="' + this.backUrl + '" style="width:auto;background:#555;">חזרה לקטגוריה</a>' : '') +
      '</div>';
  };

  HB_COG_Core.prototype.saveAttempt = function(metrics, scoreObj) {
    var nonce = window.hb_cog_vars && window.hb_cog_vars.nonce;
    var userId = window.hb_cog_vars && window.hb_cog_vars.user_id;
    if (!userId || !nonce) return Promise.resolve(null);
    
    var cfg = window.CONFIG_SENIOR || {};
    var attempt = {
      track: this.track,
      game_id: this.gameId,
      difficulty: this.difficulty,
      attempt_no: 1,
      started_at: new Date(this.t0).toISOString(),
      ended_at: new Date().toISOString(),
      date_iso: this.dateISO(),
      metrics: metrics,
      scores: { game_score: scoreObj.game_score },
      domain_contrib: (cfg.domains && cfg.domains[this.gameId]) ? cfg.domains[this.gameId] : {}
    };
    
    return this.postAjax(this.enc({
      action: 'hb_cog_save_attempt',
      _ajax_nonce: nonce,
      attempt: JSON.stringify(attempt)
    })).then(function(res) {
      if (res && res.success) {
        document.dispatchEvent(new Event('hb_cog_profile_refresh'));
      }
      return res;
    });
  };

  HB_COG_Core.prototype.dateISO = function() {
    var d = new Date();
    var y = d.getFullYear();
    var m = String(d.getMonth() + 1).padStart(2, '0');
    var day = String(d.getDate()).padStart(2, '0');
    return y + '-' + m + '-' + day;
  };

  HB_COG_Core.prototype.enc = function(obj) {
    var s = [];
    for (var k in obj) {
      if (!Object.prototype.hasOwnProperty.call(obj, k)) continue;
      s.push(encodeURIComponent(k) + '=' + encodeURIComponent(obj[k]));
    }
    return s.join('&');
  };

  HB_COG_Core.prototype.postAjax = function(params) {
    var url = (window.hb_cog_vars && window.hb_cog_vars.ajaxurl) ? window.hb_cog_vars.ajaxurl : '/wp-admin/admin-ajax.php';
    return fetch(url, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
      credentials: 'same-origin',
      body: params
    }).then(function(r) { return r.json(); });
  };

  // Export
  window.HB_COG_Core = HB_COG_Core;
})();






