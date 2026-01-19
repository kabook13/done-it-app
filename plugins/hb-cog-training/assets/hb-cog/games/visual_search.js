/*!
 * HB Cognitive Training - Visual Search Game Module
 * User must find and click the target symbol in a grid
 */

(function() {
  'use strict';

  function VisualSearchGame(container, config, core) {
    this.container = container;
    this.config = config || {};
    this.core = core;
    
    // Game state
    this.stimTimeout = null;
    this.nextTimeout = null;
    this.currentGrid = null;
    this.targetSymbol = null;
    this.targetPosition = null;
    this.stimShownAt = 0;
    this.responded = false;
    
    // Metrics
    this.trials = 0;
    this.correct = 0;
    this.wrong = 0;
    this.rts = [];
    this.streak = 0;
    this.maxStreak = 0;
    
    // Symbols
    this.symbols = ['🔺', '🔴', '🔵', '🟢', '🟡', '⚫', '⭐', '💎'];
    this.distractorSymbols = [];
    
    // Config based on difficulty
    this.gridSize = 4; // 4x4 for diff 1
    this.similarity = 0.3; // How similar distractors are to target
    
    // DOM elements
    this.gridEl = null;
    this.gridCells = [];
  }

  VisualSearchGame.prototype.init = function() {
    // Set grid size based on difficulty
    if (this.core.difficulty === 1) {
      this.gridSize = 4;
      this.similarity = 0.3;
    } else if (this.core.difficulty === 2) {
      this.gridSize = 5;
      this.similarity = 0.5;
    } else {
      this.gridSize = 5;
      this.similarity = 0.7; // More similar = harder
    }
    
    // Render HTML
    this.renderHTML();
    
    // Setup DOM references
    this.gridEl = this.container.querySelector('.hb-cog-visual-grid');
    this.setupGrid();
  };

  VisualSearchGame.prototype.renderHTML = function() {
    this.container.innerHTML = [
      '<div class="hb-cog-game-header">',
        '<h3>חיפוש ויזואלי</h3>',
        '<div class="hb-cog-instructions">מצאו את הסמל המטרה ולחצו עליו מהר ככל האפשר.</div>',
        '<div style="display:flex; gap:10px; justify-content:center; flex-wrap:wrap;">',
          '<button class="hb-cog-start-btn" style="font-size:20px;padding:12px 20px;border-radius:10px;border:0;background:#2e7d32;color:#fff;cursor:pointer;">התחל אימון</button>',
          '<button class="hb-cog-stop-btn" style="font-size:18px;padding:10px 18px;border-radius:10px;border:0;background:#444;color:#fff;cursor:pointer;display:none;">סיים מוקדם</button>',
        '</div>',
      '</div>',
      '<div class="hb-cog-game-area" style="display:none;">',
        '<div class="hb-cog-timer">5:00</div>',
        '<div class="hb-cog-visual-area" style="text-align:center;padding:20px;">',
          '<div class="hb-cog-visual-target" style="font-size:36px;margin:20px 0;font-weight:bold;">מטרה: <span id="hb-cog-target-symbol"></span></div>',
          '<div class="hb-cog-visual-grid" style="display:inline-grid;gap:8px;padding:20px;background:#f5f5f5;border-radius:10px;"></div>',
        '</div>',
      '</div>',
      '<div class="hb-cog-results"></div>',
      '<div class="hb-cog-finish-cta"></div>'
    ].join('');
  };

  VisualSearchGame.prototype.setupGrid = function() {
    this.gridEl = this.container.querySelector('.hb-cog-visual-grid');
    if (!this.gridEl) return;
    
    // Set grid template
    this.gridEl.style.gridTemplateColumns = 'repeat(' + this.gridSize + ', 1fr)';
    this.gridEl.style.gridTemplateRows = 'repeat(' + this.gridSize + ', 1fr)';
    
    // Create cells
    var totalCells = this.gridSize * this.gridSize;
    this.gridEl.innerHTML = '';
    this.gridCells = [];
    
    for (var i = 0; i < totalCells; i++) {
      var cell = document.createElement('div');
      cell.className = 'hb-cog-visual-cell';
      cell.style.cssText = 'width:60px;height:60px;display:flex;align-items:center;justify-content:center;font-size:32px;background:#fff;border:2px solid #ddd;border-radius:6px;cursor:pointer;transition:all 0.2s;';
      cell.dataset.index = i;
      
      var self = this;
      cell.addEventListener('click', function() {
        var idx = parseInt(this.dataset.index, 10);
        self.onCellClick(idx);
      });
      
      cell.addEventListener('mouseenter', function() {
        this.style.background = '#e3f2fd';
      });
      cell.addEventListener('mouseleave', function() {
        this.style.background = '#fff';
      });
      
      this.gridEl.appendChild(cell);
      this.gridCells.push(cell);
    }
  };

  VisualSearchGame.prototype.reset = function() {
    this.trials = 0;
    this.correct = 0;
    this.wrong = 0;
    this.rts = [];
    this.streak = 0;
    this.maxStreak = 0;
    this.currentGrid = null;
    this.targetSymbol = null;
    this.targetPosition = null;
    this.responded = false;
    this.clearGrid();
  };

  VisualSearchGame.prototype.start = function() {
    this.scheduleNext();
  };

  VisualSearchGame.prototype.stop = function() {
    if (this.stimTimeout) {
      clearTimeout(this.stimTimeout);
      this.stimTimeout = null;
    }
    if (this.nextTimeout) {
      clearTimeout(this.nextTimeout);
      this.nextTimeout = null;
    }
    this.clearGrid();
  };

  VisualSearchGame.prototype.clearGrid = function() {
    this.gridCells.forEach(function(cell) {
      cell.textContent = '';
      cell.style.background = '#fff';
    });
    this.currentGrid = null;
    this.targetSymbol = null;
    this.targetPosition = null;
    this.responded = false;
  };

  VisualSearchGame.prototype.scheduleNext = function() {
    if (!this.core.running) return;
    
    var gap = this.randInt(1000, 1500);
    var self = this;
    this.nextTimeout = setTimeout(function() {
      if (!self.core.running) return;
      self.showGrid();
    }, gap);
  };

  VisualSearchGame.prototype.showGrid = function() {
    if (!this.core.running) return;
    
    this.clearGrid();
    this.responded = false;
    this.stimShownAt = Date.now();
    this.trials++;
    
    // Choose target symbol
    this.targetSymbol = this.symbols[this.randInt(0, this.symbols.length - 1)];
    
    // Choose target position
    var totalCells = this.gridSize * this.gridSize;
    this.targetPosition = this.randInt(0, totalCells - 1);
    
    // Choose distractor symbols (similar to target based on similarity)
    this.distractorSymbols = [];
    var numDistractors = totalCells - 1;
    
    for (var i = 0; i < numDistractors; i++) {
      var distractor;
      if (Math.random() < this.similarity) {
        // Similar symbol (from same set)
        var similar = this.symbols.filter(function(s) { return s !== this.targetSymbol; }.bind(this));
        distractor = similar[this.randInt(0, similar.length - 1)];
      } else {
        // Different symbol
        var different = this.symbols.filter(function(s) { 
          return s !== this.targetSymbol && this.distractorSymbols.indexOf(s) === -1; 
        }.bind(this));
        if (different.length > 0) {
          distractor = different[this.randInt(0, different.length - 1)];
        } else {
          distractor = this.symbols[this.randInt(0, this.symbols.length - 1)];
        }
      }
      this.distractorSymbols.push(distractor);
    }
    
    // Display target
    var targetEl = document.getElementById('hb-cog-target-symbol');
    if (targetEl) targetEl.textContent = this.targetSymbol;
    
    // Fill grid
    var distractorIdx = 0;
    for (var j = 0; j < totalCells; j++) {
      if (j === this.targetPosition) {
        this.gridCells[j].textContent = this.targetSymbol;
      } else {
        this.gridCells[j].textContent = this.distractorSymbols[distractorIdx];
        distractorIdx++;
      }
    }
    
    // Auto-advance after timeout (reduce to 6 seconds for better pacing)
    var self = this;
    this.stimTimeout = setTimeout(function() {
      if (!self.responded) {
        self.wrong++;
        self.streak = 0;
      }
      self.clearGrid();
      self.scheduleNext();
    }, 6000); // 6 seconds max - better pacing
  };

  VisualSearchGame.prototype.onCellClick = function(cellIndex) {
    if (!this.core.running || this.targetPosition === null || this.responded) return;
    
    this.responded = true;
    var rt = Date.now() - this.stimShownAt;
    
    if (cellIndex === this.targetPosition) {
      this.correct++;
      this.rts.push(rt);
      this.streak++;
      if (this.streak > this.maxStreak) {
        this.maxStreak = this.streak;
      }
      
      // Visual feedback
      if (this.gridCells[cellIndex]) {
        this.gridCells[cellIndex].style.background = '#c8e6c9';
      }
    } else {
      this.wrong++;
      this.streak = 0;
      
      // Visual feedback
      if (this.gridCells[cellIndex]) {
        this.gridCells[cellIndex].style.background = '#ffcdd2';
      }
      if (this.gridCells[this.targetPosition]) {
        this.gridCells[this.targetPosition].style.background = '#c8e6c9';
      }
    }
    
    if (this.stimTimeout) {
      clearTimeout(this.stimTimeout);
      this.stimTimeout = null;
    }
    
    var self = this;
    setTimeout(function() {
      self.clearGrid();
      self.scheduleNext();
    }, 500);
  };

  VisualSearchGame.prototype.getMetrics = function() {
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
      avg_time_to_find_ms: Math.round(mean),
      streak: this.streak,
      max_streak: this.maxStreak
    };
  };

  VisualSearchGame.prototype.getScore = function(metrics) {
    // Scoring: accuracy * speed factor
    var speedFactor = metrics.avg_time_to_find_ms > 0 
      ? Math.max(0, 1 - (metrics.avg_time_to_find_ms / 5000)) 
      : 1;
    var gameScore = Math.round((metrics.accuracy || 0) * speedFactor * 100);
    return { game_score: gameScore };
  };

  VisualSearchGame.prototype.getTips = function(metrics) {
    var tips = [];
    if (metrics.accuracy < 0.7) {
      tips.push('נסו לסרוק את הלוח בצורה שיטתית.');
    }
    if (metrics.avg_time_to_find_ms && metrics.avg_time_to_find_ms > 4000) {
      tips.push('נסו למצוא את המטרה מהר יותר.');
    }
    if (metrics.max_streak >= 5) {
      tips.push('כל הכבוד על הרצף!');
    }
    return tips;
  };

  VisualSearchGame.prototype.renderResults = function(metrics, scoreObj, tips) {
    return '<div class="hb-cog-results-grid">' +
      '<div class="hb-cog-result-card"><div class="hb-cog-result-label">ציון</div><div class="hb-cog-result-value">' + scoreObj.game_score + '</div></div>' +
      '<div class="hb-cog-result-card"><div class="hb-cog-result-label">דיוק</div><div class="hb-cog-result-value">' + Math.round((metrics.accuracy || 0) * 100) + '%</div></div>' +
      '<div class="hb-cog-result-card"><div class="hb-cog-result-label">זמן ממוצע</div><div class="hb-cog-result-value">' + (metrics.avg_time_to_find_ms || 0) + 'ms</div></div>' +
      '<div class="hb-cog-result-card"><div class="hb-cog-result-label">רצף מקסימלי</div><div class="hb-cog-result-value">' + (metrics.max_streak || 0) + '</div></div>' +
      '</div>' +
      '<div class="hb-cog-result-disclaimer">' + (tips.length > 0 ? tips.join('<br>') : 'אחלה עבודה!') + '</div>' +
      '<div style="display:flex;gap:10px;flex-wrap:wrap;justify-content:center;margin-top:16px;">' +
      (this.core.nextUrl ? '<a class="hb-card-button" href="' + this.core.nextUrl + '" style="width:auto;">לאימון הבא</a>' : '') +
      (this.core.backUrl ? '<a class="hb-card-button" href="' + this.core.backUrl + '" style="width:auto;background:#555;">חזרה לקטגוריה</a>' : '') +
      '</div>';
  };

  VisualSearchGame.prototype.randInt = function(min, max) {
    return Math.floor(Math.random() * (max - min + 1)) + min;
  };

  // Register game module
  window.HB_COG_GAMES['visual_search'] = VisualSearchGame;
})();




