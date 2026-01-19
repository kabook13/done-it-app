<?php
/**
 * Plugin Name: Goodlife Wordle (Hebrew)
 * Description: [wordle letters="5" tries="6" mode="daily|random"] – וורדעל עברית עם התאמת סופיות, "מילת יום" יציבה ומקלדת עברית ללא סופיות. כולל תיקוני מובייל וזהירות מקריסות.
 * Version: 1.3.1
 * Author: Goodlife
 */

if (!defined('ABSPATH')) exit;

/* ---------------------------------------------------------
 * 0) עזרי mb_str_split לתאימות
 * --------------------------------------------------------- */
if (!function_exists('gl_mb_str_split')) {
  function gl_mb_str_split($string, $length = 1, $encoding = 'UTF-8') {
    $result = [];
    $strlen = function_exists('mb_strlen') ? mb_strlen($string, $encoding) : strlen($string);
    for ($i = 0; $i < $strlen; $i += $length) {
      $result[] = function_exists('mb_substr') ? mb_substr($string, $i, $length, $encoding) : substr($string, $i, $length);
    }
    return $result;
  }
}

/* ---------------------------------------------------------
 * 1) כללי: מיפוי סופיות ↔ בסיס + נורמליזציה
 * --------------------------------------------------------- */
function gl_wordle_final_to_base_map() {
  return [ 'ך'=>'כ', 'ם'=>'מ', 'ן'=>'נ', 'ף'=>'פ', 'ץ'=>'צ' ];
}
function gl_wordle_to_base($s) {
  $map = gl_wordle_final_to_base_map();
  return preg_replace_callback('/[ךםןףץ]/u', function($m) use ($map) {
    return isset($map[$m[0]]) ? $map[$m[0]] : $m[0];
  }, $s);
}

/* ---------------------------------------------------------
 * 2) טעינת נכסים (CSS/JS) + nonce ל-AJAX
 * --------------------------------------------------------- */
add_action('wp_enqueue_scripts', function () {
  // JS ריק שרק משמש כ"עוגן" ל-inline
  wp_register_script('gl-wordle-runtime', '', [], false, true);
  wp_enqueue_script('gl-wordle-runtime');

  $ajax_nonce = wp_create_nonce('gl_wordle_nonce');
  wp_localize_script('gl-wordle-runtime','gl_wordle_vars',[
    'ajaxurl' => admin_url('admin-ajax.php'),
    'nonce'   => $ajax_nonce
  ]);

  // ===== CSS (תיקוני מובייל זהירים) =====
  $css = <<<'CSS'
.glw *{box-sizing:border-box}

/* --- מיכל מרכזי: מנטרל כל הזחה/פלואט/טרנספורם מהתבנית ומונע גלילה אופקית --- */
.glw{
  direction:rtl;
  font-family:system-ui,-apple-system,"Segoe UI",Roboto,"Noto Sans Hebrew",Arial,sans-serif;

  /* מרכוז בטוח */
  display:block;
  width:100%;
  max-width:560px;
  margin-inline:auto !important;   /* מרכז גם ב-RTL */
  clear:both;
  float:none !important;

  /* מבטל כל הזזה שירשנו (position/transform) */
  position:relative !important;
  left:0 !important;
  right:0 !important;
  transform:none !important;

  /* אין חריגה לרוחב (וגם לא הישענות על רוחב של הורה) */
  overflow-x:hidden;
  padding:12px 10px;

  /* מונע הקטנה/הגדלה אוטומטית של iOS */
  -webkit-text-size-adjust:100%;
}

/* לוח */
.glw-board{
  display:grid;
  grid-template-rows:repeat(var(--tries,6),1fr);
  gap:6px;
  width:100%;
}
.glw-row{display:grid; grid-template-columns:repeat(var(--letters,5),1fr); gap:6px}
.glw-cell{
  aspect-ratio:1/1;
  border:2px solid #d0d0d0;
  display:flex; justify-content:center; align-items:center;
  font-size:clamp(20px, 7.2vw, 34px);
  font-weight:700; background:#fff; user-select:none;
  max-width:100%;
}
.glw-cell.correct{background:#6aaa64;border-color:#6aaa64;color:#fff}
.glw-cell.misplaced{background:#c9b458;border-color:#c9b458;color:#fff}
.glw-cell.wrong{background:#787c7e;border-color:#787c7e;color:#fff}
.glw-cell.bounce{animation:glw-bounce .28s ease-out forwards}
@keyframes glw-bounce{0%{transform:scale(.85)}40%{transform:scale(1.08)}100%{transform:scale(1)}}

.glw-msg{min-height:28px;color:#c0392b;font-weight:700;text-align:center}

/* קלט חבוי שלא יוצר גלילה אופקית ב-RTL */
.glw-input{
  position:absolute;
  top:0;
  left:0;          /* לא שולחים ל- -9999px */
  width:1px;
  height:1px;
  opacity:0;
  pointer-events:none;
  overflow:hidden;
  white-space:nowrap;
  clip: rect(0 0 0 0);
  clip-path: inset(50%);
}

/* במובייל - מונעים לחלוטין את היכולת לעשות focus */
@media (max-width:768px), (pointer:coarse) {
  .glw-input{
    display:none !important;
    visibility:hidden !important;
    pointer-events:none !important;
  }
}

/* חלונית “כל הכבוד!” */
.glw-celebrate{
  display:none; position:fixed; inset:0; background:rgba(0,0,0,.25);
  align-items:center; justify-content:center; z-index:9999; padding:2rem
}
.glw-celebrate.show{display:flex}
.glw-celebrate .box{
  background:#ffffff; border-radius:14px; padding:22px 28px; text-align:center;
  box-shadow:0 10px 30px rgba(0,0,0,.25); max-width:90vw
}
.glw-celebrate .title{font-size:28px; font-weight:800; color:#2e7d32; margin-bottom:8px}
.glw-celebrate .sub{font-size:16px; color:#333}

/* מקלדת – רספונסיבי ומרוכז, ללא חריגה */
.glw-keyboard{width:100%; display:grid; gap:6px; margin-top:4px; overflow-x:hidden}
.glw-keyrow{
  display:grid;
  grid-template-columns:repeat(8, minmax(0,1fr));
  gap:6px;
  padding-inline:6px;
  max-width:100%;
}
.glw-keyrow.last{
  grid-template-columns:minmax(0,1fr) repeat(6, minmax(0,1fr)) minmax(0,1fr);
}
.glw-key{
  min-width:0; max-width:100%;
  padding:12px 8px;
  background:#d3d6da; border-radius:6px; cursor:pointer; font-weight:700; text-align:center; user-select:none;
  font-size:clamp(14px, 4.2vw, 18px);
  touch-action:manipulation; -webkit-tap-highlight-color: transparent;
}
.glw-key:active{transform:scale(0.98)}
.glw-key .label{display:inline}
.glw-key.correct{background:#6aaa64;color:#fff}
.glw-key.misplaced{background:#c9b458;color:#fff}
.glw-key.wrong{background:#787c7e;color:#fff}

/* מובייל: פחות רווחים/גובה + אייקונים ל-אישור/מחיקה */
@media (max-width:420px){
  .glw{max-width:96vw; padding:10px 8px}
  .glw-board{gap:5px}
  .glw-row{gap:5px}
  .glw-cell{aspect-ratio:.92}
  .glw-keyboard{gap:5px}
  .glw-keyrow{padding-inline:4px; gap:5px}
  .glw-key{padding:10px 6px}
  .glw-key.enter .label, .glw-key.back .label{display:none}
  .glw-key.enter::before{content:"✔"; font-weight:900}
  .glw-key.back::before{content:"✖"; font-weight:900}
}

/* מסכים ממש קטנים */
@media (max-width:350px){
  .glw{max-width:98vw}
  .glw-cell{aspect-ratio:.88; font-size:clamp(18px, 9vw, 28px)}
  .glw-keyrow{padding-inline:2px; gap:4px}
  .glw-key{padding:9px 5px; font-size:clamp(13px, 4.6vw, 17px)}
}

/* מסך תוצאות */
.glw-results-overlay{
  position:fixed; inset:0; background:rgba(0,0,0,.6);
  display:flex; align-items:center; justify-content:center;
  z-index:10000; padding:1rem; direction:rtl;
}
.glw-results-box{
  background:#fff; border-radius:16px; padding:28px 24px;
  max-width:90vw; width:100%; max-width:480px;
  box-shadow:0 10px 40px rgba(0,0,0,.3);
  text-align:center;
}
.glw-results-title{
  font-size:28px; font-weight:800; color:#2e7d32;
  margin-bottom:20px;
}
.glw-results-content{
  margin-bottom:24px;
}
.glw-result-item{
  display:flex; justify-content:space-between;
  padding:10px 0; border-bottom:1px solid #e0e0e0;
  font-size:16px;
}
.glw-result-item:last-child{border-bottom:none}
.glw-result-label{
  color:#666; font-weight:600;
}
.glw-result-value{
  color:#333; font-weight:700;
}
.glw-daily-summary{
  margin-top:20px; padding-top:20px;
  border-top:2px solid #e0e0e0;
}
.glw-daily-title{
  font-size:18px; font-weight:700; color:#2e7d32;
  margin-bottom:12px;
}
.glw-results-actions{
  display:flex; gap:12px; justify-content:center;
  margin-top:20px;
}
.glw-btn{
  padding:12px 24px; border:none; border-radius:8px;
  font-size:16px; font-weight:700; cursor:pointer;
  transition:all .2s;
}
.glw-btn-primary{
  background:#2e7d32; color:#fff;
}
.glw-btn-primary:hover{background:#1b5e20}
.glw-btn-secondary{
  background:#e0e0e0; color:#333;
}
.glw-btn-secondary:hover{background:#bdbdbd}

/* פירוט ציון */
.glw-score-breakdown{
  margin-top:20px; padding:16px;
  background:#f5f5f5; border-radius:8px;
  border-top:2px solid #e0e0e0;
}
.glw-breakdown-title{
  font-size:16px; font-weight:700; color:#2e7d32;
  margin-bottom:12px;
}
.glw-breakdown-item{
  display:flex; justify-content:space-between;
  padding:6px 0; font-size:14px;
}
.glw-breakdown-label{
  color:#666; font-weight:600;
}
.glw-breakdown-value{
  color:#333; font-weight:700;
}
.glw-breakdown-final{
  margin-top:8px; padding-top:8px;
  border-top:1px solid #ddd;
  font-size:16px;
}
.glw-breakdown-final .glw-breakdown-label,
.glw-breakdown-final .glw-breakdown-value{
  font-weight:800; color:#2e7d32;
}

/* תצוגת המילה הסודית בהפסד */
.glw-secret-word-display{
  margin-bottom:24px; padding:20px;
  background:#fff3cd; border:2px solid #ffc107;
  border-radius:12px; text-align:center;
}
.glw-secret-word-label{
  font-size:16px; font-weight:600; color:#856404;
  margin-bottom:12px;
}
.glw-secret-word-value{
  font-size:36px; font-weight:800; color:#856404;
  letter-spacing:4px; font-family:monospace;
}

CSS;

  wp_register_style('gl-wordle-style', false, [], null);
  wp_enqueue_style('gl-wordle-style');
  wp_add_inline_style('gl-wordle-style', $css);

  // ===== JS =====
  $js = <<<'JS'
document.addEventListener('DOMContentLoaded', () => {
  const FINAL2BASE = { "ך":"כ","ם":"מ","ן":"נ","ף":"פ","ץ":"צ" };
  const isHeb = ch => /^[א-ת]$/u.test(ch);
  const MSG = {
    not_found: "מילה לא קיימת במאגר",
    short: "צריך בדיוק מספר אותיות מתאים",
    win: "כל הכבוד!",
    loss: "נגמרו הניסיונות. המילה הייתה: "
  };

  // זיהוי מובייל - לא לעשות focus כדי שהמקלדת לא תקפוץ
  const isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent) || 
                   (window.matchMedia && window.matchMedia('(max-width: 768px)').matches) ||
                   ('ontouchstart' in window);

  document.querySelectorAll('.glw').forEach(root => {
    const letters = parseInt(root.dataset.letters || '5',10);
    const tries   = parseInt(root.dataset.tries   || '6',10);
    const mode    = (root.dataset.mode || 'daily');

    const board   = root.querySelector('.glw-board');
    const kb      = root.querySelector('.glw-keyboard');
    const msgEl   = root.querySelector('.glw-msg');
    const hidden  = root.querySelector('.glw-input');
    const celebrate = root.querySelector('.glw-celebrate');

    let currentRow = 0;
    let guess = "";
    let gameOver = false;
    
    // ===== מערכת ציונים יומית =====
    let gameStartTime = null;
    let gameEndTime = null;
    let attemptsCount = 0;
    const scoringStorageKey = `gl_wordle_scores_${mode}_${letters}`;
    const dailyStorageKey = `gl_wordle_daily_${mode}_${letters}`;
    
    // שמירת המילה הסודית ב-sessionStorage כדי שהיא תישאר קבועה לכל המשחק
    const storageKey = `gl_wordle_secret_${mode}_${letters}_${root.dataset.tries || '6'}`;
    const today = new Date().toISOString().split('T')[0];
    let secretWord = null;
    
    // התחלת מעקב זמן
    gameStartTime = Date.now();
    
    // טעינת המילה הסודית מ-sessionStorage (אם קיימת והיא מהיום)
    try {
      const stored = sessionStorage.getItem(storageKey);
      if (stored) {
        const parsed = JSON.parse(stored);
        if (parsed.date === today && parsed.word) {
          secretWord = parsed.word;
        }
      }
    } catch(e) {
      console.warn('Failed to load secret word from storage:', e);
    }

    const rows = () => board.querySelectorAll('.glw-row');
    const cellsOf = (i) => rows()[i].querySelectorAll('.glw-cell');

    function renderRow(){
      const cells = cellsOf(currentRow);
      for (let i=0;i<letters;i++) cells[i].textContent = guess[i] || '';
    }
    function showMsg(t, ok=false){
      msgEl.textContent = t;
      msgEl.style.color = ok ? '#2e7d32' : '#c0392b';
      window.setTimeout(()=>{ msgEl.textContent=''; }, 1800);
    }
    function focusInput(){ 
      if (!hidden) return;
      hidden.value=''; 
      // במובייל - לא עושים focus כדי שהמקלדת של הטלפון לא תקפוץ
      if (isMobile) return;
      try {
        hidden.focus({preventScroll:true});
        // fallback - ניסיון נוסף אחרי delay קצר
        setTimeout(() => {
          if (document.activeElement !== hidden && !gameOver) {
            hidden.focus({preventScroll:true});
          }
        }, 50);
      } catch(e) {
        // אם focus נכשל, ננסה שוב
        setTimeout(() => hidden && hidden.focus({preventScroll:true}), 100);
      }
    }

    function showCelebrate(){
      if (!celebrate) return;
      celebrate.classList.add('show');
      window.setTimeout(()=>celebrate.classList.remove('show'), 2200);
    }

    board.addEventListener('click', ()=>{
      if (gameOver) return;
      const rowEls = rows();
      let target = 0;
      for (let r=0;r<tries;r++){
        const text = Array.from(rowEls[r].querySelectorAll('.glw-cell')).map(c=>c.textContent).join('');
        if (text.length < letters){ target = r; break; }
        target = Math.min(r+1, tries-1);
      }
      currentRow = target;
      guess = Array.from(cellsOf(currentRow)).map(c=>c.textContent).join('');
      // רק בדסקטופ - focus על הקלט
      if (!isMobile) focusInput();
    });

    // flag למניעת הקלדה כפולה - כשמקלידים במקלדת פיזית, לא נטפל ב-input
    let isPhysicalKeyboard = false;
    
    // במובייל - נשתמש רק במקלדת הוירטואלית, לא בקלט החבוי
    // בדסקטופ - נשתמש בקלט החבוי למקלדת פיזית
    if (!isMobile) {
      // טיפול באירועי input - רק בדסקטופ
      hidden.addEventListener('input', (e)=>{
        if (gameOver || isPhysicalKeyboard) {
          isPhysicalKeyboard = false; // איפוס הflag
          return;
        }
        const v = (e.target.value || '').replace(/\s+/g,'');
        if (!v) return;
        // לוקחים את התו האחרון שהוקלד
        const ch = v.slice(-1);
        hidden.value = '';
        if (isHeb(ch) && guess.length < letters){
          guess += ch;
          renderRow();
          setTimeout(() => focusInput(), 10);
        }
      });
      
      // טיפול גם ב-composition events (IME)
      hidden.addEventListener('compositionend', (e)=>{
        if (gameOver || isPhysicalKeyboard) {
          isPhysicalKeyboard = false;
          return;
        }
        const v = (e.target.value || '').replace(/\s+/g,'');
        if (!v) return;
        const ch = v.slice(-1);
        hidden.value = '';
        if (isHeb(ch) && guess.length < letters){
          guess += ch;
          renderRow();
          setTimeout(() => focusInput(), 10);
        }
      });
    } else {
      // במובייל - נכבה את הקלט כדי למנוע מהמקלדת לקפוץ
      hidden.setAttribute('readonly', 'readonly');
      hidden.setAttribute('disabled', 'disabled');
      hidden.setAttribute('tabindex', '-1');
      hidden.style.pointerEvents = 'none';
      // מונעים focus בכל דרך אפשרית
      hidden.addEventListener('focus', (e) => {
        e.preventDefault();
        e.stopPropagation();
        hidden.blur();
      }, true);
      hidden.addEventListener('click', (e) => {
        e.preventDefault();
        e.stopPropagation();
        hidden.blur();
      }, true);
    }

    function updateKeyboard(feedback){
      feedback.forEach(item => {
        const key = kb.querySelector(`.glw-key[data-key="${item.letter}"]`);
        if (!key) return;
        if (key.classList.contains('correct')) return;
        if (item.status === 'correct') key.className = 'glw-key correct';
        else if (item.status === 'misplaced' && !key.classList.contains('correct')) key.className = 'glw-key misplaced';
        else if (item.status === 'wrong' && !key.classList.contains('correct') && !key.classList.contains('misplaced')) key.className = 'glw-key wrong';
      });
    }

    // ===== פונקציות מערכת ציונים =====
    
    /**
     * חישוב ציון למשחק Wordle (משוקלל: 70% ניסיונות, 30% זמן)
     * @param {boolean} won - האם ניצח
     * @param {number} attempts - מספר ניחושים
     * @param {number} maxAttempts - מספר ניחושים מקסימלי
     * @param {number} timeSpent - זמן במשחק (מילישניות)
     * @returns {Object} {finalScore, attemptsScore, speedScore}
     */
    function calculateWordleScore(won, attempts, maxAttempts, timeSpent) {
      if (!won) {
        return {
          finalScore: 0,
          attemptsScore: 0,
          speedScore: 0
        };
      }
      
      // ציון לפי מספר ניחושים (70% מהציון הסופי)
      // 1 ניחוש = 100, 2 = 85, 3 = 70, 4 = 55, 5 = 40, 6 = 25
      const attemptsScore = Math.max(0, 100 - (attempts - 1) * 15);
      
      // ציון זמן (30% מהציון הסופי) - טווח 40-240 שניות
      const timeSeconds = timeSpent / 1000;
      const minTime = 40;
      const maxTime = 240;
      let speedScore = 0;
      
      if (timeSeconds <= minTime) {
        speedScore = 100; // זמן מהיר מאוד = 100
      } else if (timeSeconds >= maxTime) {
        speedScore = 0; // זמן איטי = 0
      } else {
        // אינטרפולציה ליניארית: מהיר יותר = ציון גבוה יותר
        speedScore = Math.round(100 * (1 - (timeSeconds - minTime) / (maxTime - minTime)));
      }
      
      // ציון סופי משוקלל: 70% ניסיונות + 30% זמן
      const finalScore = Math.min(100, Math.round(attemptsScore * 0.7 + speedScore * 0.3));
      
      return {
        finalScore: finalScore,
        attemptsScore: attemptsScore,
        speedScore: speedScore
      };
    }
    
    /**
     * שמירת תוצאות משחק
     */
    function saveGameResult(won, attempts, secretWord) {
      if (!gameStartTime || !gameEndTime) {
        gameEndTime = Date.now();
      }
      const timeSpent = gameEndTime - gameStartTime;
      const scoreBreakdown = calculateWordleScore(won, attempts, tries, timeSpent);
      
      const gameResult = {
        date: today,
        timestamp: Date.now(),
        won: won,
        attempts: attempts,
        maxAttempts: tries,
        timeSpent: timeSpent,
        score: scoreBreakdown.finalScore,
        attemptsScore: scoreBreakdown.attemptsScore,
        speedScore: scoreBreakdown.speedScore,
        secretWord: secretWord,
        letters: letters,
        mode: mode
      };
      
      // שמירה ב-localStorage
      try {
        // שמירת ניסיון בודד
        const attemptsKey = `${scoringStorageKey}_attempts`;
        let allAttempts = [];
        try {
          const stored = localStorage.getItem(attemptsKey);
          if (stored) {
            allAttempts = JSON.parse(stored);
            if (!Array.isArray(allAttempts)) allAttempts = [];
          }
        } catch(e) {}
        allAttempts.push(gameResult);
        // שמירת רק 100 ניסיונות אחרונים
        if (allAttempts.length > 100) {
          allAttempts = allAttempts.slice(-100);
        }
        localStorage.setItem(attemptsKey, JSON.stringify(allAttempts));
        
        // עדכון סיכום יומי
        updateDailySummary(gameResult.score, won);
        
        // שליחה לשרת (אם יש משתמש מחובר)
        saveToServer(gameResult);
        
        // הצגת מסך תוצאות
        setTimeout(() => showResultsScreen(gameResult), 1500);
      } catch(e) {
        console.warn('Failed to save game result:', e);
      }
    }
    
    /**
     * עדכון סיכום יומי
     */
    function updateDailySummary(gameScore, won) {
      try {
        let dailyData = {
          date: today,
          gamesPlayed: 0,
          gamesWon: 0,
          totalScore: 0,
          bestScore: 0,
          bestAttempts: null,
          averageScore: 0
        };
        
        const stored = localStorage.getItem(dailyStorageKey);
        if (stored) {
          const parsed = JSON.parse(stored);
          if (parsed.date === today) {
            dailyData = parsed;
          }
        }
        
        dailyData.gamesPlayed++;
        if (won) dailyData.gamesWon++;
        dailyData.totalScore += gameScore;
        if (gameScore > dailyData.bestScore) {
          dailyData.bestScore = gameScore;
        }
        if (won && (dailyData.bestAttempts === null || attemptsCount < dailyData.bestAttempts)) {
          dailyData.bestAttempts = attemptsCount;
        }
        dailyData.averageScore = Math.round(dailyData.totalScore / dailyData.gamesPlayed);
        
        localStorage.setItem(dailyStorageKey, JSON.stringify(dailyData));
      } catch(e) {
        console.warn('Failed to update daily summary:', e);
      }
    }
    
    /**
     * שליחת תוצאות לשרת (WordPress)
     */
    function saveToServer(gameResult) {
      if (typeof gl_wordle_vars === 'undefined' || !gl_wordle_vars.ajaxurl) {
        return;
      }
      
      const data = new URLSearchParams();
      data.set('action', 'gl_wordle_save_score');
      data.set('game_result', JSON.stringify(gameResult));
      data.set('_ajax_nonce', gl_wordle_vars.nonce || '');
      
      // שליחה אסינכרונית (לא מחכים לתשובה)
      fetch(gl_wordle_vars.ajaxurl, {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'},
        body: data.toString(),
        credentials: 'same-origin'
      }).catch(err => {
        console.warn('Failed to save score to server:', err);
      });
    }
    
    /**
     * הצגת מסך תוצאות
     */
    function showResultsScreen(gameResult) {
      const timeSeconds = Math.round(gameResult.timeSpent / 1000);
      
      // במשחק "מילת היום" בהפסד - הצג רק את המילה הסודית ללא מדדים
      if (mode === 'daily' && !gameResult.won && gameResult.secretWord) {
        const resultsHTML = `
          <div class="glw-results-overlay">
            <div class="glw-results-box">
              <div class="glw-results-title">נסה שוב מחר</div>
              <div class="glw-results-content">
                <div class="glw-secret-word-display">
                  <div class="glw-secret-word-label">המילה הייתה:</div>
                  <div class="glw-secret-word-value">${gameResult.secretWord}</div>
                </div>
              </div>
              <div class="glw-results-actions">
                <button class="glw-btn glw-btn-primary" onclick="this.closest('.glw-results-overlay').remove(); location.reload();">שחק שוב</button>
                <button class="glw-btn glw-btn-secondary" onclick="this.closest('.glw-results-overlay').remove();">סגור</button>
              </div>
            </div>
          </div>
        `;
        root.insertAdjacentHTML('beforeend', resultsHTML);
        return;
      }
      
      // במשחק "מילת היום" בניצחון - הצג רק מספר ניחושים וזמן
      if (mode === 'daily' && gameResult.won) {
        const resultsHTML = `
          <div class="glw-results-overlay">
            <div class="glw-results-box">
              <div class="glw-results-title">🎉 כל הכבוד!</div>
              <div class="glw-results-content">
                <div class="glw-result-item">
                  <span class="glw-result-label">מספר ניחושים:</span>
                  <span class="glw-result-value">${gameResult.attempts}/${gameResult.maxAttempts}</span>
                </div>
                <div class="glw-result-item">
                  <span class="glw-result-label">זמן:</span>
                  <span class="glw-result-value">${timeSeconds} שניות</span>
                </div>
              </div>
              <div class="glw-results-actions">
                <button class="glw-btn glw-btn-primary" onclick="this.closest('.glw-results-overlay').remove(); location.reload();">שחק שוב</button>
                <button class="glw-btn glw-btn-secondary" onclick="this.closest('.glw-results-overlay').remove();">סגור</button>
              </div>
            </div>
          </div>
        `;
        root.insertAdjacentHTML('beforeend', resultsHTML);
        return;
      }
      
      // למשחק random - הצג את כל המדדים
      const breakdownHTML = gameResult.won ? `
        <div class="glw-score-breakdown">
          <div class="glw-breakdown-title">פירוט ציון:</div>
          <div class="glw-breakdown-item">
            <span class="glw-breakdown-label">ציון ניסיונות (70%):</span>
            <span class="glw-breakdown-value">${gameResult.attemptsScore || 0}/100</span>
          </div>
          <div class="glw-breakdown-item">
            <span class="glw-breakdown-label">ציון זמן (30%):</span>
            <span class="glw-breakdown-value">${gameResult.speedScore || 0}/100</span>
          </div>
          <div class="glw-breakdown-item glw-breakdown-final">
            <span class="glw-breakdown-label">ציון סופי:</span>
            <span class="glw-breakdown-value">${gameResult.score}/100</span>
          </div>
        </div>
      ` : '';
      
      // הצגת המילה הסודית בהפסד (רק למשחק random)
      const secretWordHTML = !gameResult.won && gameResult.secretWord && mode === 'random' ? `
        <div class="glw-secret-word-display">
          <div class="glw-secret-word-label">המילה הייתה:</div>
          <div class="glw-secret-word-value">${gameResult.secretWord}</div>
        </div>
      ` : '';
      
      // יצירת מסך תוצאות למשחק random
      const resultsHTML = `
        <div class="glw-results-overlay">
          <div class="glw-results-box">
            <div class="glw-results-title">${gameResult.won ? '🎉 כל הכבוד!' : 'נסה שוב מחר'}</div>
            <div class="glw-results-content">
              ${secretWordHTML}
              <div class="glw-result-item">
                <span class="glw-result-label">ציון המשחק:</span>
                <span class="glw-result-value">${gameResult.score}/100</span>
              </div>
              <div class="glw-result-item">
                <span class="glw-result-label">מספר ניחושים:</span>
                <span class="glw-result-value">${gameResult.attempts}/${gameResult.maxAttempts}</span>
              </div>
              <div class="glw-result-item">
                <span class="glw-result-label">זמן:</span>
                <span class="glw-result-value">${timeSeconds} שניות</span>
              </div>
              ${breakdownHTML}
              ${getDailySummaryHTML()}
            </div>
            <div class="glw-results-actions">
              <button class="glw-btn glw-btn-primary" onclick="this.closest('.glw-results-overlay').remove(); location.reload();">שחק שוב</button>
              <button class="glw-btn glw-btn-secondary" onclick="this.closest('.glw-results-overlay').remove();">סגור</button>
            </div>
          </div>
        </div>
      `;
      
      root.insertAdjacentHTML('beforeend', resultsHTML);
    }
    
    /**
     * קבלת HTML של סיכום יומי
     */
    function getDailySummaryHTML() {
      try {
        const stored = localStorage.getItem(dailyStorageKey);
        if (!stored) return '';
        
        const dailyData = JSON.parse(stored);
        if (dailyData.date !== today) return '';
        
        if (dailyData.gamesPlayed <= 1) return '';
        
        return `
          <div class="glw-daily-summary">
            <div class="glw-daily-title">סיכום יומי</div>
            <div class="glw-result-item">
              <span class="glw-result-label">משחקים היום:</span>
              <span class="glw-result-value">${dailyData.gamesPlayed}</span>
            </div>
            <div class="glw-result-item">
              <span class="glw-result-label">ניצחונות:</span>
              <span class="glw-result-value">${dailyData.gamesWon}/${dailyData.gamesPlayed}</span>
            </div>
            <div class="glw-result-item">
              <span class="glw-result-label">ציון ממוצע:</span>
              <span class="glw-result-value">${dailyData.averageScore}/100</span>
            </div>
            ${dailyData.bestScore > 0 ? `
            <div class="glw-result-item">
              <span class="glw-result-label">שיא היום:</span>
              <span class="glw-result-value">${dailyData.bestScore}/100</span>
            </div>
            ` : ''}
          </div>
        `;
      } catch(e) {
        return '';
      }
    }

    function submitGuess(){
      if (gameOver) return;
      if (guess.length !== letters){ showMsg(MSG.short); return; }

      // בדיקה שכל המשתנים הנדרשים קיימים
      if (typeof gl_wordle_vars === 'undefined' || !gl_wordle_vars.ajaxurl) {
        showMsg("שגיאה: הגדרות AJAX חסרות. נא לרענן את הדף.");
        console.error('gl_wordle_vars is undefined or ajaxurl missing');
        return;
      }

      const data = new URLSearchParams();
      data.set('action','gl_wordle_check_guess');
      data.set('guess', guess);
      data.set('letters', letters);
      data.set('mode', mode);
      data.set('_ajax_nonce', gl_wordle_vars.nonce || '');
      // אם יש לנו מילה סודית שמורה, נשלח אותה כדי שהשרת ישתמש בה
      if (secretWord) {
        data.set('secret_word', secretWord);
      }

      // הוספת timeout למובייל (15 שניות)
      const controller = new AbortController();
      const timeoutId = setTimeout(() => controller.abort(), 15000);

      fetch(gl_wordle_vars.ajaxurl, {
        method: 'POST',
        headers: {'Content-Type':'application/x-www-form-urlencoded; charset=UTF-8'},
        body: data.toString(),
        signal: controller.signal,
        credentials: 'same-origin'
      })
      .then(r => {
        clearTimeout(timeoutId);
        // בדיקה אם התשובה היא JSON או טקסט (למקרה של שגיאת nonce)
        const contentType = r.headers.get('content-type');
        if (!r.ok) {
          // אם זה 403, זה כנראה בעיית nonce
          if (r.status === 403) {
            throw new Error('NONCE_ERROR');
          }
          throw new Error('HTTP error: ' + r.status);
        }
        if (contentType && contentType.includes('application/json')) {
          return r.json();
        } else {
          // אם התשובה לא JSON, ננסה לפרסר אותה
          return r.text().then(text => {
            console.error('Unexpected response format:', text);
            throw new Error('Invalid response format');
          });
        }
      })
      .then(res=>{
        if (!res || !('success' in res)){ 
          console.error('Invalid response structure:', res);
          showMsg("שגיאה בתקשורת"); 
          return; 
        }
        if (!res.success){ 
          showMsg(res.data || MSG.not_found); 
          return; 
        }
        const { feedback, correct_word, secret_word } = res.data || {};
        if (!Array.isArray(feedback)){ 
          console.error('Invalid feedback format:', feedback);
          showMsg("שגיאה בנתונים"); 
          return; 
        }

        // שמירת המילה הסודית ב-sessionStorage אם זה הניחוש הראשון
        if (secret_word && !secretWord) {
          secretWord = secret_word;
          try {
            sessionStorage.setItem(storageKey, JSON.stringify({
              date: today,
              word: secret_word
            }));
          } catch(e) {
            console.warn('Failed to save secret word to storage:', e);
          }
        }

        const cells = cellsOf(currentRow);
        feedback.forEach((item, i) => {
          window.setTimeout(()=>{
            if (cells[i]) {
              cells[i].classList.add(item.status);
              cells[i].classList.add('bounce');
            }
          }, i*140);
        });

        updateKeyboard(feedback);

        attemptsCount = currentRow + 1;

        if (correct_word){
          gameEndTime = Date.now();
          showMsg(MSG.win, true);
          showCelebrate();
          gameOver = true;
          // חישוב ושמירת ציון
          saveGameResult(true, attemptsCount, secret_word || secretWord);
          // מחיקת המילה הסודית מה-storage בסוף המשחק
          try {
            sessionStorage.removeItem(storageKey);
          } catch(e) {}
          return;
        }

        if (currentRow === tries-1){
          gameEndTime = Date.now();
          showMsg(MSG.loss + (secret_word || secretWord || ''));
          gameOver = true;
          // חישוב ושמירת ציון (הפסד)
          saveGameResult(false, attemptsCount, secret_word || secretWord);
          // מחיקת המילה הסודית מה-storage בסוף המשחק
          try {
            sessionStorage.removeItem(storageKey);
          } catch(e) {}
          return;
        }

        // מעבר לניחוש הבא - גם במובייל
        currentRow++;
        guess = "";
        renderRow();
        // רק בדסקטופ - focus על הקלט
        if (!isMobile) focusInput();
      })
      .catch(err => {
        clearTimeout(timeoutId);
        console.error('AJAX Error:', err);
        if (err.name === 'AbortError') {
          showMsg("שגיאה: זמן תגובה פג. נא לבדוק את החיבור לאינטרנט.");
        } else if (err.message === 'NONCE_ERROR') {
          showMsg("שגיאת אבטחה. נא לרענן את הדף ולנסות שוב.");
          console.error('Nonce verification failed. Try refreshing the page.');
        } else if (err.message && err.message.includes('HTTP error')) {
          showMsg("שגיאת שרת. נא לנסות שוב.");
        } else {
          showMsg("שגיאת AJAX. נא לנסות שוב או לרענן את הדף.");
        }
      });
    }

    // טיפול משופר באירועי מקלדת - תמיכה במובייל (click + touch)
    function handleKeyPress(key) {
      if (gameOver) return;
      if (key === 'ENTER') {
        submitGuess();
        return;
      }
      if (key === 'BACK'){
        // תמיד אפשר למחוק, גם אם המילה מלאה
        if (guess.length > 0){ 
          guess = guess.slice(0,-1); 
          renderRow(); 
        }
        // רק בדסקטופ - focus על הקלט
        if (!isMobile) focusInput();
        return;
      }
      if (/^[א-ת]$/u.test(key) && guess.length < letters){
        guess += key; 
        renderRow(); 
        // רק בדסקטופ - focus על הקלט
        if (!isMobile) focusInput();
      }
    }

    kb.addEventListener('click', e=>{
      const keyEl = e.target.closest('.glw-key');
      if (!keyEl) return;
      const key = keyEl.dataset.key;
      if (key) handleKeyPress(key);
    });
    
    // תמיכה נוספת ב-touch events למובייל
    kb.addEventListener('touchend', e=>{
      e.preventDefault(); // מונע double-tap zoom
      const keyEl = e.target.closest('.glw-key');
      if (!keyEl) return;
      const key = keyEl.dataset.key;
      if (key) handleKeyPress(key);
    }, {passive: false});

    window.addEventListener('keydown', e=>{
      if (gameOver) return;
      const k = e.key;
      // סימון שזו מקלדת פיזית כדי למנוע טיפול כפול ב-input event
      isPhysicalKeyboard = true;
      if (k === 'Enter') {
        submitGuess();
        return;
      }
      if (k === 'Backspace'){ 
        // תמיד אפשר למחוק, גם אם המילה מלאה
        if (guess.length > 0){ 
          guess = guess.slice(0,-1); 
          renderRow(); 
        } 
        return; 
      }
      if (/^[א-ת]$/u.test(k) && guess.length < letters){ 
        guess += k; 
        renderRow(); 
      }
      // איפוס הflag אחרי זמן קצר
      setTimeout(() => { isPhysicalKeyboard = false; }, 100);
    }, {passive:true});

    // רק בדסקטופ - focus על הקלט בהתחלה
    if (!isMobile) focusInput();
  });
});
JS;

  wp_add_inline_script('gl-wordle-runtime', $js, 'after');
});

/* ---------------------------------------------------------
 * 3) איתור קובץ המילים + קריאה בטוחה (ללא קריסה)
 * --------------------------------------------------------- */
function gl_wordle_find_words_file() {
  $plugin_path  = plugin_dir_path(__FILE__) . 'hebrew-words.json';
  $uploads_path = WP_CONTENT_DIR . '/uploads/hebrew-words.json';
  if (file_exists($plugin_path))  return $plugin_path;
  if (file_exists($uploads_path)) return $uploads_path;
  return false;
}

/**
 * איתור קובץ מילים יעד (רשימה מוגבלת למילות יום)
 */
function gl_wordle_find_target_words_file($letters = 5) {
  $plugin_path = plugin_dir_path(__FILE__) . "target_words_{$letters}_letters_quality.json";
  if (file_exists($plugin_path)) return $plugin_path;
  return false;
}

function gl_wordle_get_all_words() {
  $path = gl_wordle_find_words_file();
  if (!$path) return [];
  $raw = @file_get_contents($path);
  if ($raw === false) return [];
  $arr = json_decode($raw, true);
  if (!is_array($arr)) return [];
  $clean = [];
  foreach ($arr as $w) {
    $w = trim(mb_strtolower($w, 'UTF-8'));
    if ($w === '') continue;
    $clean[] = $w;
  }
  return array_values(array_unique($clean));
}

/**
 * קבלת רשימת מילים יעד למילת יום (אם קיימת)
 */
function gl_wordle_get_target_words($letters = 5) {
  $path = gl_wordle_find_target_words_file($letters);
  if (!$path) return [];
  $raw = @file_get_contents($path);
  if ($raw === false) return [];
  $arr = json_decode($raw, true);
  if (!is_array($arr)) return [];
  $clean = [];
  foreach ($arr as $w) {
    $w = trim(mb_strtolower($w, 'UTF-8'));
    if ($w === '') continue;
    $clean[] = $w;
  }
  return array_values(array_unique($clean));
}

/* ---------------------------------------------------------
 * 4) "מילת יום" : יציבה ודטרמיניסטית (daily)
 * משתמש ברשימת מילים יעד אם קיימת (target_words), אחרת ברשימה הכללית
 * --------------------------------------------------------- */
function gl_wordle_pick_daily_word($words, $letters = 5) {
  // ניסיון להשתמש ברשימת מילים יעד (רשימה מוגבלת)
  $target_words = gl_wordle_get_target_words($letters);
  if (!empty($target_words)) {
    // סינון לפי אורך (אם צריך)
    $cand = array_values(array_filter($target_words, function($w) use ($letters) {
      return mb_strlen(gl_wordle_to_base($w), 'UTF-8') === $letters;
    }));
  } else {
    // נפילה חזרה לרשימה הכללית
    $cand = array_values(array_filter($words, function($w) use ($letters) {
      return mb_strlen(gl_wordle_to_base($w), 'UTF-8') === $letters;
    }));
  }
  
  if (empty($cand)) return '';

  $today = date('Y-m-d');
  $opt_word = get_option('gl_wordle_daily_word');
  $opt_date = get_option('gl_wordle_daily_date');
  if ($opt_word && $opt_date === $today) {
    return $opt_word;
  }

  $idx = abs(crc32($today)) % count($cand);
  $chosen = $cand[$idx];

  update_option('gl_wordle_daily_word', $chosen);
  update_option('gl_wordle_daily_date', $today);
  return $chosen;
}

/* ---------------------------------------------------------
 * 5) AJAX: בדיקת ניחוש (עם הגנות בסיסיות)
 * --------------------------------------------------------- */
add_action('wp_ajax_gl_wordle_check_guess', 'gl_wordle_handle_check');
add_action('wp_ajax_nopriv_gl_wordle_check_guess', 'gl_wordle_handle_check');

function gl_wordle_handle_check() {
  // בדיקת nonce עם טיפול טוב יותר בשגיאות
  $nonce_check = check_ajax_referer('gl_wordle_nonce', false, false);
  if (!$nonce_check) {
    wp_send_json_error('שגיאת אבטחה. נא לרענן את הדף ולנסות שוב.');
    return;
  }

  $letters = isset($_POST['letters']) ? max(3, intval($_POST['letters'])) : 5;
  $guess   = isset($_POST['guess']) ? sanitize_text_field($_POST['guess']) : '';
  $mode    = isset($_POST['mode']) ? sanitize_text_field($_POST['mode']) : 'daily';

  $guess = mb_strtolower($guess, 'UTF-8');
  $guess_base = gl_wordle_to_base($guess);

  if (mb_strlen($guess_base, 'UTF-8') !== $letters) {
    wp_send_json_error('אורך ניחוש שגוי.');
  }

  $all = gl_wordle_get_all_words();
  if (empty($all)) {
    wp_send_json_error('שגיאה: מאגר המילים ריק או לא נמצא.');
  }

  // סט לנעילה מהירה (בצורת בסיס)
  $all_base_set = [];
  foreach ($all as $w) {
    $all_base_set[ gl_wordle_to_base($w) ] = true;
  }
  if (!isset($all_base_set[$guess_base])) {
    wp_send_json_error('מילה לא קיימת במאגר.');
  }

  // בחירת מילה לפי mode - אם יש מילה סודית שנשלחה מהלקוח, נשתמש בה
  $provided_secret = isset($_POST['secret_word']) ? sanitize_text_field($_POST['secret_word']) : '';
  
  if (!empty($provided_secret)) {
    // וידוא שהמילה הסודית שנשלחה תקינה
    $provided_secret = mb_strtolower($provided_secret, 'UTF-8');
    $provided_secret_base = gl_wordle_to_base($provided_secret);
    if (mb_strlen($provided_secret_base, 'UTF-8') === $letters) {
      $secret = $provided_secret;
    } else {
      $provided_secret = ''; // אם לא תקין, נבחר מילה חדשה
    }
  }
  
  if (empty($provided_secret)) {
    // בחירת מילה חדשה רק אם לא נשלחה מילה סודית
    $candidates = array_values(array_filter($all, function($w) use ($letters) {
      return mb_strlen(gl_wordle_to_base($w), 'UTF-8') === $letters;
    }));
    if (empty($candidates)) wp_send_json_error('אין מילים מתאימות לאורך.');

    if ($mode === 'random') {
      $secret = $candidates[array_rand($candidates)];
    } else {
      $secret = gl_wordle_pick_daily_word($all, $letters);
    }
  }
  
  $secret_base  = gl_wordle_to_base($secret);

  // חישוב פידבק (באופן בסיס)
  $g_chars = gl_mb_str_split($guess_base, 1, 'UTF-8');
  $s_chars = gl_mb_str_split($secret_base, 1, 'UTF-8');
  $orig_guess_chars = gl_mb_str_split($guess, 1, 'UTF-8');

  $feedback = [];
  $correct = true;

  // ירוקים
  for ($i=0; $i<$letters; $i++) {
    if ($g_chars[$i] === $s_chars[$i]) {
      $feedback[$i] = ['letter'=>$orig_guess_chars[$i], 'status'=>'correct'];
      $s_chars[$i] = null;
    } else {
      $correct = false;
    }
  }
  // צהובים/אפור
  for ($i=0; $i<$letters; $i++) {
    if (isset($feedback[$i])) continue;
    $pos = array_search($g_chars[$i], $s_chars, true);
    if ($pos !== false) {
      $feedback[$i] = ['letter'=>$orig_guess_chars[$i], 'status'=>'misplaced'];
      $s_chars[$pos] = null;
    } else {
      $feedback[$i] = ['letter'=>$orig_guess_chars[$i], 'status'=>'wrong'];
    }
  }
  ksort($feedback);

  wp_send_json_success([
    'feedback'     => array_values($feedback),
    'correct_word' => $correct,
    'secret_word'  => $secret, // מוצג בהפסד/דיבאג
  ]);
}

/* ---------------------------------------------------------
 * 6) AJAX: שמירת ציון משחק
 * --------------------------------------------------------- */
add_action('wp_ajax_gl_wordle_save_score', 'gl_wordle_handle_save_score');
add_action('wp_ajax_nopriv_gl_wordle_save_score', 'gl_wordle_handle_save_score');

function gl_wordle_handle_save_score() {
  // בדיקת nonce
  $nonce_check = check_ajax_referer('gl_wordle_nonce', false, false);
  if (!$nonce_check) {
    wp_send_json_error('שגיאת אבטחה.');
    return;
  }

  $game_result_json = isset($_POST['game_result']) ? $_POST['game_result'] : '';
  if (empty($game_result_json)) {
    wp_send_json_error('נתונים חסרים.');
    return;
  }

  $game_result = json_decode(stripslashes($game_result_json), true);
  if (!is_array($game_result)) {
    wp_send_json_error('פורמט נתונים שגוי.');
    return;
  }

  // וידוא שכל השדות הנדרשים קיימים
  $required_fields = ['date', 'won', 'attempts', 'score', 'letters', 'mode'];
  foreach ($required_fields as $field) {
    if (!isset($game_result[$field])) {
      wp_send_json_error("שדה חסר: $field");
      return;
    }
  }

  $user_id = get_current_user_id();
  $date = sanitize_text_field($game_result['date']);
  $today = date('Y-m-d');

  // שמירה רק אם התאריך הוא היום
  if ($date !== $today) {
    wp_send_json_success(['message' => 'תוצאה לא נשמרה - תאריך לא תואם']);
    return;
  }

  // שמירה ב-WordPress
  if ($user_id) {
    // שמירה ב-user meta
    $user_scores_key = 'gl_wordle_scores';
    $user_scores = get_user_meta($user_id, $user_scores_key, true);
    if (!is_array($user_scores)) {
      $user_scores = [];
    }
    
    // הוספת התוצאה
    $user_scores[] = [
      'date' => $date,
      'timestamp' => time(),
      'game_result' => $game_result
    ];
    
    // שמירת רק 100 תוצאות אחרונות
    if (count($user_scores) > 100) {
      $user_scores = array_slice($user_scores, -100);
    }
    
    update_user_meta($user_id, $user_scores_key, $user_scores);
    
    // עדכון סיכום יומי
    $daily_summary_key = 'gl_wordle_daily_' . $date;
    $daily_summary = get_user_meta($user_id, $daily_summary_key, true);
    if (!is_array($daily_summary)) {
      $daily_summary = [
        'date' => $date,
        'games_played' => 0,
        'games_won' => 0,
        'total_score' => 0,
        'best_score' => 0,
        'best_attempts' => null,
        'average_score' => 0
      ];
    }
    
    $daily_summary['games_played']++;
    if ($game_result['won']) {
      $daily_summary['games_won']++;
    }
    $daily_summary['total_score'] += intval($game_result['score']);
    if (intval($game_result['score']) > $daily_summary['best_score']) {
      $daily_summary['best_score'] = intval($game_result['score']);
    }
    if ($game_result['won'] && ($daily_summary['best_attempts'] === null || intval($game_result['attempts']) < $daily_summary['best_attempts'])) {
      $daily_summary['best_attempts'] = intval($game_result['attempts']);
    }
    $daily_summary['average_score'] = round($daily_summary['total_score'] / $daily_summary['games_played']);
    
    update_user_meta($user_id, $daily_summary_key, $daily_summary);
  } else {
    // משתמש לא מחובר - שמירה ב-option כללי (אופציונלי)
    // אפשר לדלג על זה או לשמור ב-transient
  }

  wp_send_json_success([
    'message' => 'תוצאה נשמרה בהצלחה',
    'user_id' => $user_id
  ]);
}

/* ---------------------------------------------------------
 * 7) AJAX: קבלת פרופיל משתמש
 * --------------------------------------------------------- */
add_action('wp_ajax_gl_wordle_get_profile', 'gl_wordle_handle_get_profile');
add_action('wp_ajax_nopriv_gl_wordle_get_profile', 'gl_wordle_handle_get_profile');

function gl_wordle_handle_get_profile() {
  // בדיקת nonce
  $nonce_check = check_ajax_referer('gl_wordle_nonce', false, false);
  if (!$nonce_check) {
    wp_send_json_error('שגיאת אבטחה.');
    return;
  }

  $user_id = get_current_user_id();
  if (!$user_id) {
    wp_send_json_error(['message' => 'משתמש לא מחובר']);
    return;
  }

  $mode = isset($_POST['mode']) ? sanitize_text_field($_POST['mode']) : 'daily';
  $letters = isset($_POST['letters']) ? intval($_POST['letters']) : 5;
  $days = isset($_POST['days']) ? intval($_POST['days']) : 7;

  // איסוף נתונים מהימים האחרונים
  $profile_data = [];
  $today = new DateTime();
  
  for ($i = 0; $i < $days; $i++) {
    $date_obj = clone $today;
    $date_obj->modify("-{$i} days");
    $date = $date_obj->format('Y-m-d');
    
    // קבלת סיכום יומי
    $daily_summary_key = 'gl_wordle_daily_' . $date;
    $daily_summary = get_user_meta($user_id, $daily_summary_key, true);
    
    if (is_array($daily_summary) && isset($daily_summary['date']) && $daily_summary['date'] === $date) {
      // סינון לפי mode ו-letters אם רלוונטי
      // (כרגע נשמור את כל הנתונים, אפשר להוסיף סינון בהמשך)
      $profile_data[] = [
        'date' => $date,
        'games_played' => intval($daily_summary['games_played'] ?? 0),
        'games_won' => intval($daily_summary['games_won'] ?? 0),
        'total_score' => intval($daily_summary['total_score'] ?? 0),
        'best_score' => intval($daily_summary['best_score'] ?? 0),
        'best_attempts' => isset($daily_summary['best_attempts']) ? intval($daily_summary['best_attempts']) : null,
        'average_score' => intval($daily_summary['average_score'] ?? 0)
      ];
    } else {
      // יום ללא משחקים
      $profile_data[] = [
        'date' => $date,
        'games_played' => 0,
        'games_won' => 0,
        'total_score' => 0,
        'best_score' => 0,
        'best_attempts' => null,
        'average_score' => 0
      ];
    }
  }

  wp_send_json_success([
    'profile_data' => $profile_data,
    'mode' => $mode,
    'letters' => $letters,
    'days' => $days
  ]);
}

/* ---------------------------------------------------------
 * 8) Shortcode: [wordle_profile mode="daily" letters="5" days="7"]
 * --------------------------------------------------------- */
add_shortcode('wordle_profile', function($atts){
  $a = shortcode_atts([
    'mode' => 'daily',
    'letters' => '5',
    'days' => '7'
  ], $atts, 'wordle_profile');

  $mode = in_array($a['mode'], ['daily','random'], true) ? $a['mode'] : 'daily';
  $letters = max(3, intval($a['letters']));
  $days = max(1, min(30, intval($a['days']))); // בין 1 ל-30 ימים

  ob_start(); ?>
  <div class="glw-profile" 
       data-mode="<?php echo esc_attr($mode); ?>"
       data-letters="<?php echo esc_attr($letters); ?>"
       data-days="<?php echo esc_attr($days); ?>">
    <div class="glw-profile-loading">טוען נתונים...</div>
    <div class="glw-profile-content" style="display:none;">
      <div class="glw-profile-header">
        <h3 class="glw-profile-title">פרופיל Wordle - <?php echo esc_html($days); ?> ימים אחרונים</h3>
      </div>
      <div class="glw-profile-stats"></div>
      <div class="glw-profile-table"></div>
    </div>
    <div class="glw-profile-error" style="display:none;"></div>
  </div>
  
  <script>
  (function() {
    document.addEventListener('DOMContentLoaded', function() {
      const profileEl = document.querySelector('.glw-profile[data-mode="<?php echo esc_js($mode); ?>"][data-letters="<?php echo esc_js($letters); ?>"][data-days="<?php echo esc_js($days); ?>"]');
      if (!profileEl) return;
      
      const mode = profileEl.dataset.mode;
      const letters = parseInt(profileEl.dataset.letters, 10);
      const days = parseInt(profileEl.dataset.days, 10);
      
      // ניסיון לטעון מהשרת (אם משתמש מחובר)
      if (typeof gl_wordle_vars !== 'undefined' && gl_wordle_vars.ajaxurl) {
        const data = new URLSearchParams();
        data.set('action', 'gl_wordle_get_profile');
        data.set('mode', mode);
        data.set('letters', letters);
        data.set('days', days);
        data.set('_ajax_nonce', gl_wordle_vars.nonce || '');
        
        fetch(gl_wordle_vars.ajaxurl, {
          method: 'POST',
          headers: {'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'},
          body: data.toString(),
          credentials: 'same-origin'
        })
        .then(r => r.json())
        .then(res => {
          if (res.success && res.data && res.data.profile_data) {
            renderProfile(res.data.profile_data, profileEl);
          } else {
            // נפילה חזרה ל-localStorage
            loadFromLocalStorage(mode, letters, days, profileEl);
          }
        })
        .catch(err => {
          console.warn('Failed to load profile from server:', err);
          loadFromLocalStorage(mode, letters, days, profileEl);
        });
      } else {
        // נפילה ישירה ל-localStorage
        loadFromLocalStorage(mode, letters, days, profileEl);
      }
      
      function loadFromLocalStorage(mode, letters, days, profileEl) {
        try {
          const storageKey = `gl_wordle_daily_${mode}_${letters}`;
          const today = new Date().toISOString().split('T')[0];
          const profileData = [];
          
          for (let i = 0; i < days; i++) {
            const date = new Date();
            date.setDate(date.getDate() - i);
            const dateStr = date.toISOString().split('T')[0];
            
            const stored = localStorage.getItem(storageKey);
            if (stored) {
              const dailyData = JSON.parse(stored);
              if (dailyData.date === dateStr) {
                profileData.push({
                  date: dateStr,
                  games_played: dailyData.gamesPlayed || 0,
                  games_won: dailyData.gamesWon || 0,
                  total_score: dailyData.totalScore || 0,
                  best_score: dailyData.bestScore || 0,
                  best_attempts: dailyData.bestAttempts || null,
                  average_score: dailyData.averageScore || 0
                });
                continue;
              }
            }
            
            profileData.push({
              date: dateStr,
              games_played: 0,
              games_won: 0,
              total_score: 0,
              best_score: 0,
              best_attempts: null,
              average_score: 0
            });
          }
          
          renderProfile(profileData, profileEl);
        } catch(e) {
          console.error('Failed to load from localStorage:', e);
          showError(profileEl, 'שגיאה בטעינת הנתונים');
        }
      }
      
      function renderProfile(profileData, profileEl) {
        const loadingEl = profileEl.querySelector('.glw-profile-loading');
        const contentEl = profileEl.querySelector('.glw-profile-content');
        const errorEl = profileEl.querySelector('.glw-profile-error');
        
        if (loadingEl) loadingEl.style.display = 'none';
        if (errorEl) errorEl.style.display = 'none';
        if (contentEl) contentEl.style.display = 'block';
        
        // חישוב סטטיסטיקות כוללות
        let totalGames = 0, totalWon = 0, totalScore = 0, bestOverall = 0;
        profileData.forEach(day => {
          totalGames += day.games_played;
          totalWon += day.games_won;
          totalScore += day.total_score;
          if (day.best_score > bestOverall) bestOverall = day.best_score;
        });
        const avgScore = totalGames > 0 ? Math.round(totalScore / totalGames) : 0;
        const winRate = totalGames > 0 ? Math.round((totalWon / totalGames) * 100) : 0;
        
        // הצגת סטטיסטיקות
        const statsEl = profileEl.querySelector('.glw-profile-stats');
        if (statsEl) {
          statsEl.innerHTML = `
            <div class="glw-stat-item">
              <div class="glw-stat-value">${totalGames}</div>
              <div class="glw-stat-label">משחקים</div>
            </div>
            <div class="glw-stat-item">
              <div class="glw-stat-value">${winRate}%</div>
              <div class="glw-stat-label">אחוז ניצחונות</div>
            </div>
            <div class="glw-stat-item">
              <div class="glw-stat-value">${avgScore}</div>
              <div class="glw-stat-label">ציון ממוצע</div>
            </div>
            <div class="glw-stat-item">
              <div class="glw-stat-value">${bestOverall}</div>
              <div class="glw-stat-label">שיא</div>
            </div>
          `;
        }
        
        // הצגת טבלה
        const tableEl = profileEl.querySelector('.glw-profile-table');
        if (tableEl) {
          let tableHTML = '<table class="glw-profile-table-inner"><thead><tr><th>תאריך</th><th>משחקים</th><th>ניצחונות</th><th>ציון ממוצע</th><th>שיא</th></tr></thead><tbody>';
          profileData.forEach(day => {
            const dateObj = new Date(day.date);
            const dateStr = dateObj.toLocaleDateString('he-IL', {day: '2-digit', month: '2-digit'});
            tableHTML += `
              <tr>
                <td>${dateStr}</td>
                <td>${day.games_played}</td>
                <td>${day.games_won}</td>
                <td>${day.average_score}</td>
                <td>${day.best_score > 0 ? day.best_score : '-'}</td>
              </tr>
            `;
          });
          tableHTML += '</tbody></table>';
          tableEl.innerHTML = tableHTML;
        }
      }
      
      function showError(profileEl, message) {
        const loadingEl = profileEl.querySelector('.glw-profile-loading');
        const contentEl = profileEl.querySelector('.glw-profile-content');
        const errorEl = profileEl.querySelector('.glw-profile-error');
        
        if (loadingEl) loadingEl.style.display = 'none';
        if (contentEl) contentEl.style.display = 'none';
        if (errorEl) {
          errorEl.textContent = message;
          errorEl.style.display = 'block';
        }
      }
    });
  })();
  </script>
  
  <style>
  .glw-profile{max-width:800px; margin:20px auto; padding:20px; direction:rtl}
  .glw-profile-loading{text-align:center; padding:40px; color:#666}
  .glw-profile-error{text-align:center; padding:20px; color:#c0392b; background:#ffebee; border-radius:8px}
  .glw-profile-header{margin-bottom:20px}
  .glw-profile-title{font-size:24px; font-weight:700; color:#2e7d32; margin:0}
  .glw-profile-stats{display:grid; grid-template-columns:repeat(auto-fit, minmax(150px, 1fr)); gap:16px; margin-bottom:24px}
  .glw-stat-item{background:#f5f5f5; padding:16px; border-radius:8px; text-align:center}
  .glw-stat-value{font-size:32px; font-weight:800; color:#2e7d32; margin-bottom:8px}
  .glw-stat-label{font-size:14px; color:#666; font-weight:600}
  .glw-profile-table-inner{width:100%; border-collapse:collapse; background:#fff; border-radius:8px; overflow:hidden; box-shadow:0 2px 8px rgba(0,0,0,.1)}
  .glw-profile-table-inner th{background:#2e7d32; color:#fff; padding:12px; text-align:right; font-weight:700}
  .glw-profile-table-inner td{padding:12px; text-align:right; border-bottom:1px solid #e0e0e0}
  .glw-profile-table-inner tr:last-child td{border-bottom:none}
  .glw-profile-table-inner tr:hover{background:#f5f5f5}
  @media (max-width:600px){
    .glw-profile-stats{grid-template-columns:repeat(2, 1fr)}
    .glw-profile-table-inner{font-size:14px}
    .glw-profile-table-inner th,
    .glw-profile-table-inner td{padding:8px}
  }
  </style>
  <?php
  return ob_get_clean();
});

/* ---------------------------------------------------------
 * 9) Shortcode: [wordle letters="5" tries="6" mode="daily|random"]
 * --------------------------------------------------------- */
add_shortcode('wordle', function($atts){
  $a = shortcode_atts([
    'letters' => '5',
    'tries'   => '6',
    'mode'    => 'daily'
  ], $atts, 'wordle');

  $letters = max(3, intval($a['letters']));
  $tries   = max(1, intval($a['tries']));
  $mode    = in_array($a['mode'], ['daily','random'], true) ? $a['mode'] : 'daily';

  // מקלדת: א-ת ללא סופיות
  $row1 = ['א','ב','ג','ד','ה','ו','ז','ח'];
  $row2 = ['ט','י','כ','ל','מ','נ','ס','ע'];
  $row3 = ['פ','צ','ק','ר','ש','ת'];

  ob_start(); ?>
  <div class="glw"
       data-letters="<?php echo esc_attr($letters); ?>"
       data-tries="<?php echo esc_attr($tries); ?>"
       data-mode="<?php echo esc_attr($mode); ?>">

    <!-- שכבת חגיגה -->
    <div class="glw-celebrate" aria-live="polite" aria-atomic="true">
      <div class="box">
        <div class="title">כל הכבוד!</div>
        <div class="sub">פתרת נכון 🎉</div>
      </div>
    </div>

    <input class="glw-input" type="text" inputmode="text" autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false" tabindex="-1" />
    <div class="glw-msg"></div>

    <div class="glw-board" style="--letters:<?php echo intval($letters); ?>;--tries:<?php echo intval($tries); ?>;">
      <?php for($r=0;$r<$tries;$r++): ?>
        <div class="glw-row">
          <?php for($c=0;$c<$letters;$c++): ?>
            <div class="glw-cell"></div>
          <?php endfor; ?>
        </div>
      <?php endfor; ?>
    </div>

    <div class="glw-keyboard">
      <div class="glw-keyrow">
        <?php foreach($row1 as $k): ?>
          <div class="glw-key" data-key="<?php echo esc_attr($k); ?>"><span class="label"><?php echo esc_html($k); ?></span></div>
        <?php endforeach; ?>
      </div>
      <div class="glw-keyrow">
        <?php foreach($row2 as $k): ?>
          <div class="glw-key" data-key="<?php echo esc_attr($k); ?>"><span class="label"><?php echo esc_html($k); ?></span></div>
        <?php endforeach; ?>
      </div>
      <div class="glw-keyrow last">
        <div class="glw-key enter" data-key="ENTER"><span class="label">אישור</span></div>
        <?php foreach($row3 as $k): ?>
          <div class="glw-key" data-key="<?php echo esc_attr($k); ?>"><span class="label"><?php echo esc_html($k); ?></span></div>
        <?php endforeach; ?>
        <div class="glw-key back" data-key="BACK"><span class="label">מחיקה</span></div>
      </div>
    </div>
  </div>
  <?php return ob_get_clean();
});

