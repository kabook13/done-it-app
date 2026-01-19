/*!
 * HB Cognitive Training - Go/No-Go Game Module
 */

(function() {
  'use strict';

  function GoNoGoGame(container, config, core) {
    this.container = container;
    this.config = config || {};
    this.core = core;
    
    // Game state
    this.stimTimeout = null;
    this.nextTimeout = null;
    this.currentStim = null; // 'go' | 'nogo'
    this.stimShownAt = 0;
    this.responded = false;
    
    // Metrics
    this.trials = 0;
    this.goTrials = 0;
    this.nogoTrials = 0;
    this.hits = 0;
    this.misses = 0;
    this.correctReject = 0;
    this.falseAlarms = 0;
    this.rts = [];
    
    // Config
    this.interval = [1200, 1800];
    this.stimDuration = 900;
    this.goRatio = 0.7;
    
    // DOM elements
    this.stimGo = null;
    this.stimNoGo = null;
  }

  GoNoGoGame.prototype.init = function() {
    // Get config
    var cfg = window.CONFIG_SENIOR || {};
    var dcfg = cfg.difficulty_levels && cfg.difficulty_levels[this.core.difficulty] 
      ? cfg.difficulty_levels[this.core.difficulty] 
      : null;
    
    this.interval = dcfg ? dcfg.interval : (cfg.stimulus_interval_ms || [1200, 1800]);
    this.stimDuration = dcfg ? dcfg.duration : (cfg.stimulus_duration_ms || 900);
    this.goRatio = cfg.go_ratio != null ? cfg.go_ratio : 0.7;
    
    // Render HTML
    this.renderHTML();
    
    // Setup DOM references
    this.stimGo = this.container.querySelector('.hb-cog-stimulus-go');
    this.stimNoGo = this.container.querySelector('.hb-cog-stimulus-nogo');
    
    // Setup event listeners
    if (this.stimGo) {
      this.stimGo.addEventListener('click', this.onRespond.bind(this));
    }
    if (this.stimNoGo) {
      this.stimNoGo.addEventListener('click', this.onRespond.bind(this));
    }
    
    document.addEventListener('keydown', function(e) {
      if (e.key === ' ' || e.key === 'Enter') {
        this.onRespond();
      }
    }.bind(this));
  };

  GoNoGoGame.prototype.renderHTML = function() {
    this.container.innerHTML = [
      '<div class="hb-cog-game-header">',
        '<h3>Go / No-Go</h3>',
        '<div class="hb-cog-instructions">לחצו על העיגול <b>הירוק</b>. אל תלחצו על העיגול <b>האדום</b>.</div>',
        '<div style="display:flex; gap:10px; justify-content:center; flex-wrap:wrap;">',
          '<button class="hb-cog-start-btn" style="font-size:20px;padding:12px 20px;border-radius:10px;border:0;background:#2e7d32;color:#fff;cursor:pointer;">התחל אימון</button>',
          '<button class="hb-cog-stop-btn" style="font-size:18px;padding:10px 18px;border-radius:10px;border:0;background:#444;color:#fff;cursor:pointer;display:none;">סיים מוקדם</button>',
        '</div>',
      '</div>',
      '<div class="hb-cog-game-area" style="display:none;">',
        '<div class="hb-cog-timer">5:00</div>',
        '<div class="hb-cog-stimulus-area">',
          '<div class="hb-cog-stimulus hb-cog-stimulus-go" role="button" aria-label="GO"></div>',
          '<div class="hb-cog-stimulus hb-cog-stimulus-nogo" role="button" aria-label="NO-GO"></div>',
        '</div>',
      '</div>',
      '<div class="hb-cog-results"></div>',
      '<div class="hb-cog-finish-cta"></div>'
    ].join('');
  };

  GoNoGoGame.prototype.reset = function() {
    this.trials = 0;
    this.goTrials = 0;
    this.nogoTrials = 0;
    this.hits = 0;
    this.misses = 0;
    this.correctReject = 0;
    this.falseAlarms = 0;
    this.rts = [];
    this.currentStim = null;
    this.responded = false;
    this.hideStim();
  };

  GoNoGoGame.prototype.start = function() {
    this.scheduleNext();
  };

  GoNoGoGame.prototype.stop = function() {
    if (this.stimTimeout) {
      clearTimeout(this.stimTimeout);
      this.stimTimeout = null;
    }
    if (this.nextTimeout) {
      clearTimeout(this.nextTimeout);
      this.nextTimeout = null;
    }
    this.hideStim();
  };

  GoNoGoGame.prototype.hideStim = function() {
    if (this.stimGo) this.stimGo.classList.remove('is-visible');
    if (this.stimNoGo) this.stimNoGo.classList.remove('is-visible');
    this.currentStim = null;
    this.responded = false;
  };

  GoNoGoGame.prototype.scheduleNext = function() {
    if (!this.core.running) return;
    
    var gap = this.randInt(this.interval[0], this.interval[1]);
    var self = this;
    this.nextTimeout = setTimeout(function() {
      if (!self.core.running) return;
      var kind = (Math.random() < self.goRatio) ? 'go' : 'nogo';
      self.showStim(kind);
    }, gap);
  };

  GoNoGoGame.prototype.showStim = function(kind) {
    if (!this.core.running) return;
    
    this.hideStim();
    this.currentStim = kind;
    this.responded = false;
    this.stimShownAt = Date.now();
    this.trials++;
    if (kind === 'go') this.goTrials++; else this.nogoTrials++;
    
    var stimEl = (kind === 'go') ? this.stimGo : this.stimNoGo;
    if (stimEl) stimEl.classList.add('is-visible');
    
    var self = this;
    this.stimTimeout = setTimeout(function() {
      if (!self.responded) {
        if (self.currentStim === 'go') self.misses++;
        else self.correctReject++;
      }
      self.hideStim();
      self.scheduleNext();
    }, this.stimDuration);
  };

  GoNoGoGame.prototype.onRespond = function() {
    if (!this.core.running || !this.currentStim || this.responded) return;
    
    this.responded = true;
    var rt = Date.now() - this.stimShownAt;
    
    if (this.currentStim === 'go') {
      this.hits++;
      this.rts.push(rt);
    } else {
      this.falseAlarms++;
    }
    
    if (this.stimTimeout) {
      clearTimeout(this.stimTimeout);
      this.stimTimeout = null;
    }
    this.hideStim();
    this.scheduleNext();
  };

  GoNoGoGame.prototype.getMetrics = function() {
    var totalCorrect = this.hits + this.correctReject;
    var total = this.trials || 1;
    var accuracy = totalCorrect / total;
    
    var mean = 0;
    for (var i = 0; i < this.rts.length; i++) mean += this.rts[i];
    mean = this.rts.length ? (mean / this.rts.length) : 0;
    
    var sd = 0;
    for (var j = 0; j < this.rts.length; j++) {
      sd += Math.pow(this.rts[j] - mean, 2);
    }
    sd = this.rts.length ? Math.sqrt(sd / this.rts.length) : 0;
    
    var cv = (mean > 0) ? (sd / mean) : 1;
    
    return {
      trials: this.trials,
      accuracy: accuracy,
      mean_rt_ms: Math.round(mean),
      rt_cv: Number(cv.toFixed(3)),
      false_alarms: this.falseAlarms
    };
  };

  GoNoGoGame.prototype.getScore = function(metrics) {
    var cfg = window.CONFIG_SENIOR || {};
    var weights = (cfg.scoring && cfg.scoring.weights) || { accuracy: 0.55, speed: 0.30, stability: 0.15 };
    var speedCfg = (cfg.scoring && cfg.scoring.speed) || { min_rt_ms: 300, max_rt_ms: 1100 };
    var stabCfg = (cfg.scoring && cfg.scoring.stability) || { cv_target: 0.45 };
    
    var acc = metrics.accuracy || 0;
    var meanRT = metrics.mean_rt_ms || 0;
    var cv = metrics.rt_cv || 1;
    
    var speed01 = 0;
    if (meanRT && meanRT > 0) {
      var t = (speedCfg.max_rt_ms - meanRT) / (speedCfg.max_rt_ms - speedCfg.min_rt_ms);
      speed01 = this.clamp(t, 0, 1);
    }
    
    var stab01 = this.clamp(1 - (cv / (stabCfg.cv_target || 0.45)), 0, 1);
    var total01 = acc * weights.accuracy + speed01 * weights.speed + stab01 * weights.stability;
    var gameScore = Math.round(this.clamp(total01, 0, 1) * 100);
    
    return { game_score: gameScore };
  };

  GoNoGoGame.prototype.getTips = function(metrics) {
    var tips = [];
    if (metrics.false_alarms >= 3) {
      tips.push('שמנו לב שלחצת גם כשלא צריך — זה קשור ליכולת עכבה. מחר נתרגל שוב בקצב נוח.');
    }
    if (metrics.mean_rt_ms && metrics.mean_rt_ms > 900) {
      tips.push('זמן התגובה מעט איטי — אפשר לחזק מהירות עיבוד עם אימון קצר נוסף.');
    }
    if (metrics.rt_cv && metrics.rt_cv > 0.6) {
      tips.push('התגובות לא יציבות — אימון קצר נוסף יעזור לייצב קשב וקצב.');
    }
    return tips;
  };

  GoNoGoGame.prototype.renderResults = function(metrics, scoreObj, tips) {
    return '<div class="hb-cog-results-grid">' +
      '<div class="hb-cog-result-card"><div class="hb-cog-result-label">ציון</div><div class="hb-cog-result-value">' + scoreObj.game_score + '</div></div>' +
      '<div class="hb-cog-result-card"><div class="hb-cog-result-label">דיוק</div><div class="hb-cog-result-value">' + Math.round((metrics.accuracy || 0) * 100) + '%</div></div>' +
      '<div class="hb-cog-result-card"><div class="hb-cog-result-label">זמן תגובה</div><div class="hb-cog-result-value">' + (metrics.mean_rt_ms || 0) + '</div></div>' +
      '<div class="hb-cog-result-card"><div class="hb-cog-result-label">טעויות No-Go</div><div class="hb-cog-result-value">' + (metrics.false_alarms || 0) + '</div></div>' +
      '</div>' +
      '<div class="hb-cog-result-disclaimer">' + tips.join('<br>') + '</div>' +
      '<div style="display:flex;gap:10px;flex-wrap:wrap;justify-content:center;margin-top:16px;">' +
      (this.core.nextUrl ? '<a class="hb-card-button" href="' + this.core.nextUrl + '" style="width:auto;">לאימון הבא</a>' : '') +
      (this.core.backUrl ? '<a class="hb-card-button" href="' + this.core.backUrl + '" style="width:auto;background:#555;">חזרה לקטגוריה</a>' : '') +
      '</div>';
  };

  GoNoGoGame.prototype.randInt = function(min, max) {
    return Math.floor(Math.random() * (max - min + 1)) + min;
  };

  GoNoGoGame.prototype.clamp = function(n, a, b) {
    return Math.max(a, Math.min(b, n));
  };

  // Register game module
  window.HB_COG_GAMES['go_nogo'] = GoNoGoGame;
})();




