/*!
 * HB Cognitive Training - N-Back 1 Game Module
 * User clicks "Yes" if current item matches previous item
 */

(function() {
  'use strict';

  function NBack1Game(container, config, core) {
    this.container = container;
    this.config = config || {};
    this.core = core;
    
    // Game state
    this.stimTimeout = null;
    this.nextTimeout = null;
    this.currentStim = null;
    this.previousStim = null;
    this.stimShownAt = 0;
    this.responded = false;
    
    // Metrics
    this.trials = 0;
    this.hits = 0;
    this.misses = 0;
    this.falseAlarms = 0;
    this.correctRejections = 0;
    this.rts = [];
    
    // Stimuli
    this.stimuli = ['א', 'ב', 'ג', 'ד', 'ה', 'ו', 'ז', 'ח'];
    this.stimulusSetSize = 8;
    
    // Config based on difficulty
    this.stimulusInterval = 1400; // ms between stimuli
    this.matchRatio = 0.3; // 30% matches
    
    // DOM elements
    this.stimEl = null;
    this.yesBtn = null;
    this.noBtn = null;
  }

  NBack1Game.prototype.init = function() {
    // Set interval based on difficulty
    if (this.core.difficulty === 1) this.stimulusInterval = 1400;
    else if (this.core.difficulty === 2) this.stimulusInterval = 1100;
    else this.stimulusInterval = 900; // Faster for diff 3+
    
    // Render HTML
    this.renderHTML();
    
    // Setup DOM references
    this.stimEl = this.container.querySelector('.hb-cog-nback-stimulus');
    this.yesBtn = this.container.querySelector('.hb-cog-nback-yes');
    this.noBtn = this.container.querySelector('.hb-cog-nback-no');
    
    // Setup event listeners
    if (this.yesBtn) {
      this.yesBtn.addEventListener('click', function() {
        this.onRespond(true);
      }.bind(this));
    }
    if (this.noBtn) {
      this.noBtn.addEventListener('click', function() {
        this.onRespond(false);
      }.bind(this));
    }
    
    // Keyboard support
    document.addEventListener('keydown', function(e) {
      if (e.key === '1' || e.key === 'ArrowLeft') {
        this.onRespond(true);
      } else if (e.key === '2' || e.key === 'ArrowRight') {
        this.onRespond(false);
      }
    }.bind(this));
  };

  NBack1Game.prototype.renderHTML = function() {
    this.container.innerHTML = [
      '<div class="hb-cog-game-header">',
        '<h3>N-Back 1</h3>',
        '<div class="hb-cog-instructions">לחצו <b>כן</b> אם האות הנוכחית זהה לקודמת, אחרת לחצו <b>לא</b>.</div>',
        '<div style="display:flex; gap:10px; justify-content:center; flex-wrap:wrap;">',
          '<button class="hb-cog-start-btn" style="font-size:20px;padding:12px 20px;border-radius:10px;border:0;background:#2e7d32;color:#fff;cursor:pointer;">התחל אימון</button>',
          '<button class="hb-cog-stop-btn" style="font-size:18px;padding:10px 18px;border-radius:10px;border:0;background:#444;color:#fff;cursor:pointer;display:none;">סיים מוקדם</button>',
        '</div>',
      '</div>',
      '<div class="hb-cog-game-area" style="display:none;">',
        '<div class="hb-cog-timer">5:00</div>',
        '<div class="hb-cog-nback-area" style="text-align:center;padding:40px 20px;">',
          '<div class="hb-cog-nback-stimulus" style="font-size:72px;font-weight:bold;margin:40px 0;min-height:100px;display:flex;align-items:center;justify-content:center;"></div>',
          '<div class="hb-cog-nback-buttons" style="display:flex;justify-content:center;gap:20px;margin-top:30px;">',
            '<button class="hb-cog-nback-yes" style="padding:15px 30px;border-radius:10px;border:0;background:#2e7d32;color:#fff;font-size:20px;cursor:pointer;min-width:120px;">כן (1)</button>',
            '<button class="hb-cog-nback-no" style="padding:15px 30px;border-radius:10px;border:0;background:#c62828;color:#fff;font-size:20px;cursor:pointer;min-width:120px;">לא (2)</button>',
          '</div>',
        '</div>',
      '</div>',
      '<div class="hb-cog-results"></div>',
      '<div class="hb-cog-finish-cta"></div>'
    ].join('');
  };

  NBack1Game.prototype.reset = function() {
    this.trials = 0;
    this.hits = 0;
    this.misses = 0;
    this.falseAlarms = 0;
    this.correctRejections = 0;
    this.rts = [];
    this.currentStim = null;
    this.previousStim = null;
    this.responded = false;
    this.hideStim();
  };

  NBack1Game.prototype.start = function() {
    this.previousStim = null; // First stimulus has no previous
    this.scheduleNext();
  };

  NBack1Game.prototype.stop = function() {
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

  NBack1Game.prototype.hideStim = function() {
    if (this.stimEl) {
      this.stimEl.textContent = '';
    }
    this.currentStim = null;
    this.responded = false;
  };

  NBack1Game.prototype.scheduleNext = function() {
    if (!this.core.running) return;
    
    var self = this;
    this.nextTimeout = setTimeout(function() {
      if (!self.core.running) return;
      self.showStim();
    }, this.stimulusInterval);
  };

  NBack1Game.prototype.showStim = function() {
    if (!this.core.running) return;
    
    this.hideStim();
    this.responded = false;
    this.stimShownAt = Date.now();
    this.trials++;
    
    // Determine if this should match previous
    var shouldMatch = (this.previousStim !== null && Math.random() < this.matchRatio);
    var stim;
    
    if (shouldMatch) {
      // Match: use same as previous
      stim = this.previousStim;
    } else {
      // No match: choose different
      var otherStimuli = this.stimuli.filter(function(s) { return s !== this.previousStim; }.bind(this));
      stim = otherStimuli[this.randInt(0, otherStimuli.length - 1)];
    }
    
    this.currentStim = {
      value: stim,
      isMatch: (this.previousStim === stim)
    };
    
    // Display
    if (this.stimEl) {
      this.stimEl.textContent = stim;
    }
    
    // Auto-advance after timeout
    var self = this;
    this.stimTimeout = setTimeout(function() {
      if (!self.responded) {
        // Timeout = wrong answer
        if (self.currentStim.isMatch) {
          self.misses++;
        } else {
          self.correctRejections++;
        }
      }
      self.previousStim = self.currentStim.value;
      self.hideStim();
      self.scheduleNext();
    }, this.stimulusInterval - 200); // Show for most of interval
  };

  NBack1Game.prototype.onRespond = function(isYes) {
    if (!this.core.running || !this.currentStim || this.responded) return;
    
    this.responded = true;
    var rt = Date.now() - this.stimShownAt;
    
    var correct = (isYes === this.currentStim.isMatch);
    
    if (correct) {
      if (isYes) {
        this.hits++;
      } else {
        this.correctRejections++;
      }
      this.rts.push(rt);
    } else {
      if (isYes) {
        this.falseAlarms++;
      } else {
        this.misses++;
      }
    }
    
    if (this.stimTimeout) {
      clearTimeout(this.stimTimeout);
      this.stimTimeout = null;
    }
    
    this.previousStim = this.currentStim.value;
    this.hideStim();
    this.scheduleNext();
  };

  NBack1Game.prototype.getMetrics = function() {
    var total = this.trials || 1;
    var accuracy = (this.hits + this.correctRejections) / total;
    
    var mean = 0;
    for (var i = 0; i < this.rts.length; i++) mean += this.rts[i];
    mean = this.rts.length ? (mean / this.rts.length) : 0;
    
    return {
      trials: this.trials,
      accuracy: accuracy,
      hits: this.hits,
      misses: this.misses,
      false_alarms: this.falseAlarms,
      correct_rejections: this.correctRejections,
      avg_reaction_ms: Math.round(mean)
    };
  };

  NBack1Game.prototype.getScore = function(metrics) {
    // Scoring: accuracy * 100
    var gameScore = Math.round((metrics.accuracy || 0) * 100);
    return { game_score: gameScore };
  };

  NBack1Game.prototype.getTips = function(metrics) {
    var tips = [];
    if (metrics.accuracy < 0.7) {
      tips.push('נסו לזכור את האות הקודמת ולהשוות אותה לנוכחית.');
    }
    if (metrics.false_alarms > metrics.hits) {
      tips.push('נסו להיות זהירים יותר — לחצו "כן" רק כשאתם בטוחים.');
    }
    return tips;
  };

  NBack1Game.prototype.renderResults = function(metrics, scoreObj, tips) {
    return '<div class="hb-cog-results-grid">' +
      '<div class="hb-cog-result-card"><div class="hb-cog-result-label">ציון</div><div class="hb-cog-result-value">' + scoreObj.game_score + '</div></div>' +
      '<div class="hb-cog-result-card"><div class="hb-cog-result-label">דיוק</div><div class="hb-cog-result-value">' + Math.round((metrics.accuracy || 0) * 100) + '%</div></div>' +
      '<div class="hb-cog-result-card"><div class="hb-cog-result-label">זמן תגובה ממוצע</div><div class="hb-cog-result-value">' + (metrics.avg_reaction_ms || 0) + 'ms</div></div>' +
      '<div class="hb-cog-result-card"><div class="hb-cog-result-label">זיהויים נכונים</div><div class="hb-cog-result-value">' + (metrics.hits || 0) + '</div></div>' +
      '</div>' +
      '<div class="hb-cog-result-disclaimer">' + (tips.length > 0 ? tips.join('<br>') : 'אחלה עבודה!') + '</div>' +
      '<div style="display:flex;gap:10px;flex-wrap:wrap;justify-content:center;margin-top:16px;">' +
      (this.core.nextUrl ? '<a class="hb-card-button" href="' + this.core.nextUrl + '" style="width:auto;">לאימון הבא</a>' : '') +
      (this.core.backUrl ? '<a class="hb-card-button" href="' + this.core.backUrl + '" style="width:auto;background:#555;">חזרה לקטגוריה</a>' : '') +
      '</div>';
  };

  NBack1Game.prototype.randInt = function(min, max) {
    return Math.floor(Math.random() * (max - min + 1)) + min;
  };

  // Register game module
  window.HB_COG_GAMES['nback1'] = NBack1Game;
})();




