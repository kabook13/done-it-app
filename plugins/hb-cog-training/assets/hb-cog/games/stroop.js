/*!
 * HB Cognitive Training - Stroop Game Module
 * User must select the ink color, not the word meaning
 */

(function() {
  'use strict';

  function StroopGame(container, config, core) {
    this.container = container;
    this.config = config || {};
    this.core = core;
    
    // Game state
    this.stimTimeout = null;
    this.nextTimeout = null;
    this.currentStim = null;
    this.stimShownAt = 0;
    this.responded = false;
    
    // Metrics
    this.trials = 0;
    this.correct = 0;
    this.wrong = 0;
    this.rts = [];
    
    // Colors
    this.colors = ['אדום', 'כחול', 'ירוק', 'צהוב'];
    this.colorValues = {
      'אדום': '#c62828',
      'כחול': '#1976d2',
      'ירוק': '#2e7d32',
      'צהוב': '#f9a825'
    };
    
    // Config based on difficulty
    this.matchRatio = 0.7; // 70% match (word == ink) for diff 1
    
    // DOM elements
    this.stimEl = null;
    this.colorButtons = [];
  }

  StroopGame.prototype.init = function() {
    // Set match ratio based on difficulty
    if (this.core.difficulty === 1) this.matchRatio = 0.7;
    else if (this.core.difficulty === 2) this.matchRatio = 0.5;
    else this.matchRatio = 0.3; // More conflict for diff 3+
    
    // Render HTML
    this.renderHTML();
    
    // Setup DOM references
    this.stimEl = this.container.querySelector('.hb-cog-stroop-stimulus');
    this.colorButtons = Array.prototype.slice.call(
      this.container.querySelectorAll('.hb-cog-stroop-color-btn')
    );
    
    // Setup event listeners
    var self = this;
    this.colorButtons.forEach(function(btn, idx) {
      btn.addEventListener('click', function() {
        self.onRespond(self.colors[idx]);
      });
    });
  };

  StroopGame.prototype.renderHTML = function() {
    var colorButtonsHTML = this.colors.map(function(color, idx) {
      return '<button class="hb-cog-stroop-color-btn" data-color="' + color + '" style="padding:12px 20px;margin:5px;border-radius:8px;border:2px solid #ddd;background:#fff;font-size:18px;cursor:pointer;min-width:100px;">' + color + '</button>';
    }).join('');
    
    this.container.innerHTML = [
      '<div class="hb-cog-game-header">',
        '<h3>מבחן Stroop</h3>',
        '<div class="hb-cog-instructions">בחרו את <b>צבע הדיו</b> של המילה (לא את משמעות המילה).</div>',
        '<div style="display:flex; gap:10px; justify-content:center; flex-wrap:wrap;">',
          '<button class="hb-cog-start-btn" style="font-size:20px;padding:12px 20px;border-radius:10px;border:0;background:#2e7d32;color:#fff;cursor:pointer;">התחל אימון</button>',
          '<button class="hb-cog-stop-btn" style="font-size:18px;padding:10px 18px;border-radius:10px;border:0;background:#444;color:#fff;cursor:pointer;display:none;">סיים מוקדם</button>',
        '</div>',
      '</div>',
      '<div class="hb-cog-game-area" style="display:none;">',
        '<div class="hb-cog-timer">5:00</div>',
        '<div class="hb-cog-stroop-area" style="text-align:center;padding:40px 20px;">',
          '<div class="hb-cog-stroop-stimulus" style="font-size:48px;font-weight:bold;margin:20px 0;"></div>',
          '<div class="hb-cog-stroop-buttons" style="display:flex;justify-content:center;flex-wrap:wrap;margin-top:30px;">',
            colorButtonsHTML,
          '</div>',
        '</div>',
      '</div>',
      '<div class="hb-cog-results"></div>',
      '<div class="hb-cog-finish-cta"></div>'
    ].join('');
  };

  StroopGame.prototype.reset = function() {
    this.trials = 0;
    this.correct = 0;
    this.wrong = 0;
    this.rts = [];
    this.currentStim = null;
    this.responded = false;
    this.hideStim();
  };

  StroopGame.prototype.start = function() {
    this.scheduleNext();
  };

  StroopGame.prototype.stop = function() {
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

  StroopGame.prototype.hideStim = function() {
    if (this.stimEl) {
      this.stimEl.textContent = '';
      this.stimEl.style.color = 'transparent';
    }
    this.currentStim = null;
    this.responded = false;
  };

  StroopGame.prototype.scheduleNext = function() {
    if (!this.core.running) return;
    
    var gap = this.randInt(1500, 2500);
    var self = this;
    this.nextTimeout = setTimeout(function() {
      if (!self.core.running) return;
      self.showStim();
    }, gap);
  };

  StroopGame.prototype.showStim = function() {
    if (!this.core.running) return;
    
    this.hideStim();
    this.responded = false;
    this.stimShownAt = Date.now();
    this.trials++;
    
    // Choose word and ink color
    var word = this.colors[this.randInt(0, this.colors.length - 1)];
    var inkColor;
    
    if (Math.random() < this.matchRatio) {
      // Match: word == ink
      inkColor = word;
    } else {
      // Mismatch: different color
      var otherColors = this.colors.filter(function(c) { return c !== word; });
      inkColor = otherColors[this.randInt(0, otherColors.length - 1)];
    }
    
    this.currentStim = {
      word: word,
      inkColor: inkColor,
      correctAnswer: inkColor
    };
    
    // Display
    if (this.stimEl) {
      this.stimEl.textContent = word;
      this.stimEl.style.color = this.colorValues[inkColor];
    }
    
    // Auto-advance after timeout
    var self = this;
    this.stimTimeout = setTimeout(function() {
      if (!self.responded) {
        self.wrong++;
      }
      self.hideStim();
      self.scheduleNext();
    }, 3000);
  };

  StroopGame.prototype.onRespond = function(selectedColor) {
    if (!this.core.running || !this.currentStim || this.responded) return;
    
    this.responded = true;
    var rt = Date.now() - this.stimShownAt;
    
    if (selectedColor === this.currentStim.correctAnswer) {
      this.correct++;
      this.rts.push(rt);
    } else {
      this.wrong++;
    }
    
    if (this.stimTimeout) {
      clearTimeout(this.stimTimeout);
      this.stimTimeout = null;
    }
    this.hideStim();
    this.scheduleNext();
  };

  StroopGame.prototype.getMetrics = function() {
    var total = this.trials || 1;
    var accuracy = this.correct / total;
    
    var mean = 0;
    for (var i = 0; i < this.rts.length; i++) mean += this.rts[i];
    mean = this.rts.length ? (mean / this.rts.length) : 0;
    
    return {
      trials: this.trials,
      accuracy: accuracy,
      correct: this.correct,
      wrong: this.wrong,
      avg_reaction_ms: Math.round(mean)
    };
  };

  StroopGame.prototype.getScore = function(metrics) {
    // Simple scoring: accuracy * 100
    var gameScore = Math.round((metrics.accuracy || 0) * 100);
    return { game_score: gameScore };
  };

  StroopGame.prototype.getTips = function(metrics) {
    var tips = [];
    if (metrics.accuracy < 0.7) {
      tips.push('נסו להתמקד בצבע הדיו ולא במשמעות המילה.');
    }
    if (metrics.avg_reaction_ms && metrics.avg_reaction_ms > 2000) {
      tips.push('נסו להגיב מהר יותר — זה יעזור לחזק את יכולת העכבה.');
    }
    return tips;
  };

  StroopGame.prototype.renderResults = function(metrics, scoreObj, tips) {
    return '<div class="hb-cog-results-grid">' +
      '<div class="hb-cog-result-card"><div class="hb-cog-result-label">ציון</div><div class="hb-cog-result-value">' + scoreObj.game_score + '</div></div>' +
      '<div class="hb-cog-result-card"><div class="hb-cog-result-label">דיוק</div><div class="hb-cog-result-value">' + Math.round((metrics.accuracy || 0) * 100) + '%</div></div>' +
      '<div class="hb-cog-result-card"><div class="hb-cog-result-label">זמן תגובה ממוצע</div><div class="hb-cog-result-value">' + (metrics.avg_reaction_ms || 0) + 'ms</div></div>' +
      '<div class="hb-cog-result-card"><div class="hb-cog-result-label">תשובות נכונות</div><div class="hb-cog-result-value">' + (metrics.correct || 0) + '</div></div>' +
      '</div>' +
      '<div class="hb-cog-result-disclaimer">' + (tips.length > 0 ? tips.join('<br>') : 'אחלה עבודה!') + '</div>' +
      '<div style="display:flex;gap:10px;flex-wrap:wrap;justify-content:center;margin-top:16px;">' +
      (this.core.nextUrl ? '<a class="hb-card-button" href="' + this.core.nextUrl + '" style="width:auto;">לאימון הבא</a>' : '') +
      (this.core.backUrl ? '<a class="hb-card-button" href="' + this.core.backUrl + '" style="width:auto;background:#555;">חזרה לקטגוריה</a>' : '') +
      '</div>';
  };

  StroopGame.prototype.randInt = function(min, max) {
    return Math.floor(Math.random() * (max - min + 1)) + min;
  };

  // Register game module
  window.HB_COG_GAMES['stroop'] = StroopGame;
})();






