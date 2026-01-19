<?php
/**
 * Plugin Name: Goodlife Cipher (צופן מילים)
 * Description: [cipher difficulty="easy|mid|hard" daily="0|1" timer="60|0" timer_toggle="1|0" symbolset="numbers|letters"] — צופן תחליף עם כפתור שפה, סימנים מספריים (ברירת מחדל), שלבים מדורגים.
 * Version: 1.2.0
 * Author: Goodlife
 */

if (!defined('ABSPATH')) exit;

add_shortcode('cipher', function ($atts = []) {
  $a = shortcode_atts([
    'difficulty'   => 'easy',     // easy|mid|hard (קל=מילים/קצרים)
    'daily'        => '0',        // 0|1
    'timer'        => '0',        // seconds or 0 (off)
    'timer_toggle' => '1',        // show toggle button
    'symbolset'    => 'numbers',  // numbers|letters (ברירת מחדל: מספרים)
  ], $atts, 'cipher');

  $difficulty   = in_array($a['difficulty'], ['easy','mid','hard'], true) ? $a['difficulty'] : 'easy';
  $daily        = ($a['daily'] === '1');
  $timer        = max(0, intval($a['timer']));
  $timer_toggle = ($a['timer_toggle'] === '1');
  $symbolset    = ($a['symbolset'] === 'letters') ? 'letters' : 'numbers';

  // Load JSON from uploads
  $uploads   = wp_upload_dir();
  $json_path = trailingslashit($uploads['basedir']) . 'cipher_texts.json';
  $data = null;
  if (is_readable($json_path)) {
    $raw = file_get_contents($json_path);
    $decoded = json_decode($raw, true);
    if (is_array($decoded)) $data = $decoded;
  }
  if (!$data) {
    $data = [
      'he' => [
        'easy' => ['שלום','תודה','בוקר טוב','בית','מים','ספר','לחם','חלב'],
        'mid'  => ['אין חדש תחת השמש','טובים השניים מן האחד'],
        'hard' => ['כשיש ספק אין ספק ועוצרים לבירור לפני פעולה'],
      ],
      'en' => [
        'easy' => ['hello','thanks','good morning','door','water','book','bread','milk'],
        'mid'  => ['practice makes perfect','time is money'],
        'hard' => ['the road to hell is paved with good intentions'],
      ],
    ];
  }

  // Build pools for both languages at chosen difficulty
  $pool_he = isset($data['he'][$difficulty]) ? (array)$data['he'][$difficulty] : [];
  $pool_en = isset($data['en'][$difficulty]) ? (array)$data['en'][$difficulty] : [];
  if (!$pool_he) $pool_he = ['טקסט חסר'];
  if (!$pool_en) $pool_en = ['missing text'];

  // Deterministic index by date if daily, per language
  $seed = intval(current_time('Ymd'));

  // Unique DOM id
  $uid = 'glcipher_' . wp_generate_uuid4();

  ob_start(); ?>
  <div class="gl-cipher" id="<?php echo esc_attr($uid); ?>" dir="rtl">
    <style>
      .gl-cipher{direction:rtl;font-family:system-ui,-apple-system,"Segoe UI",Roboto,Arial;max-width:720px;margin:16px auto;padding:12px;border:1px solid #ddd;border-radius:14px}
      .gl-cipher *{box-sizing:border-box}
      .glc-row{display:flex;flex-wrap:wrap;gap:8px;align-items:center}
      .glc-top{justify-content:space-between;margin-bottom:10px}
      .glc-btn{border:1px solid #bbb;border-radius:10px;padding:8px 12px;cursor:pointer;background:#fff;font-size:15px}
      .glc-btn:disabled{opacity:.6;cursor:not-allowed}
      .glc-btn:active{transform:scale(0.98)}
      .glc-seg{display:flex;gap:6px;align-items:center}
      .glc-badge{font-size:13px;opacity:.85}
      .glc-grid{display:flex;flex-wrap:wrap;gap:6px;align-items:flex-start;margin:10px 0}
      .glc-cell{min-width:30px;min-height:44px;padding:6px 8px;border:1px solid #ddd;border-radius:10px;text-align:center}
      .glc-cell .ct{font-size:18px;font-weight:600}
      .glc-cell .pt{font-size:14px;opacity:.7;margin-top:2px}
      .glc-map{width:100%;border:1px dashed #ddd;border-radius:12px;padding:8px;margin:8px 0}
      .glc-map h4{margin:0 0 6px 0;font-size:14px}
      .glc-map-table{display:grid;grid-template-columns:70px 1fr;gap:6px}
      .glc-map .sym{border:1px solid #eee;border-radius:8px;padding:6px;text-align:center;font-weight:600}
      .glc-map input{width:100%;padding:6px;border:1px solid #ddd;border-radius:10px;text-align:center;font-size:15px}
      .glc-legend{font-size:12px;opacity:.8;margin-top:6px}
      .glc-msg{margin-top:8px;font-size:14px}
      .glc-timer{font-variant-numeric:tabular-nums}
      .glc-score{font-weight:600}
      .glc-foot{display:flex;gap:8px;flex-wrap:wrap;margin-top:8px}
      @media (max-width:480px){
        .glc-cell{min-width:26px;min-height:40px}
        .glc-map-table{grid-template-columns:60px 1fr}
      }
    </style>

    <div class="glc-row glc-top">
      <div class="glc-seg">
        <button class="glc-btn" data-act="lang-he">עברית</button>
        <button class="glc-btn" data-act="lang-en">English</button>
        <span class="glc-badge" data-role="meta"></span>
      </div>
      <div class="glc-seg">
        <?php if ($timer_toggle): ?>
          <button class="glc-btn" data-act="toggle-timer">שעון</button>
        <?php endif; ?>
        <button class="glc-btn" data-act="hint">רמז (2)</button>
        <button class="glc-btn" data-act="reveal">פתרון</button>
        <button class="glc-btn" data-act="new">חידה חדשה</button>
      </div>
    </div>

    <div class="glc-grid" data-role="grid"></div>

    <div class="glc-map">
      <h4>מפת סימנים → הנחת אות</h4>
      <div class="glc-map-table" data-role="map"></div>
      <div class="glc-legend">הקלידו אות עברית/אנגלית בהתאם לשפה שנבחרה. סימני פיסוק ורווחים אינם מוצפנים.</div>
    </div>

    <div class="glc-row glc-foot">
      <div class="glc-score" data-role="score"></div>
      <div class="glc-msg" data-role="msg"></div>
      <div class="glc-timer" data-seconds="<?php echo esc_attr($timer); ?>" hidden>00:00</div>
    </div>

    <script>
    (function(){
      const $root = document.getElementById('<?php echo esc_js($uid); ?>');
      if (!$root) return;

      // ---- Data from PHP ----
      const poolHE = <?php echo json_encode(array_values($pool_he), JSON_UNESCAPED_UNICODE); ?>;
      const poolEN = <?php echo json_encode(array_values($pool_en), JSON_UNESCAPED_UNICODE); ?>;
      const DIFF   = <?php echo json_encode($difficulty); ?>;
      const DAILY  = <?php echo $daily ? 'true' : 'false'; ?>;
      const SEED   = <?php echo (int)$seed; ?>;
      const SYMBOLSET = <?php echo json_encode($symbolset); ?>; // 'numbers' | 'letters'
      const INIT_TIMER = <?php echo (int)$timer; ?>;
      const TIMER_TOGGLE = <?php echo $timer_toggle ? 'true' : 'false'; ?>;

      // ---- State ----
      let lang = 'he'; // default UI language
      let plaintext = '';
      let ciphertextChars = [];
      let plaintextChars  = [];
      let c2p = new Map(); // cipher symbol -> true plain letter
      let guess = new Map();
      let usedSymbols = []; // list of cipher symbols as strings ("01","02",... or letters)

      // ---- Elements ----
      const gridEl  = $root.querySelector('[data-role="grid"]');
      const mapWrap = $root.querySelector('[data-role="map"]');
      const msgEl   = $root.querySelector('[data-role="msg"]');
      const scoreEl = $root.querySelector('[data-role="score"]');
      const timerEl = $root.querySelector('.glc-timer');
      const metaEl  = $root.querySelector('[data-role="meta"]');
      const btnHe   = $root.querySelector('[data-act="lang-he"]');
      const btnEn   = $root.querySelector('[data-act="lang-en"]');
      const btnHint = $root.querySelector('[data-act="hint"]');
      const btnReveal = $root.querySelector('[data-act="reveal"]');
      const btnNew  = $root.querySelector('[data-act="new"]');
      const btnToggle = $root.querySelector('[data-act="toggle-timer"]');

      // ---- Alphabets & helpers ----
      const heAlphabet = 'אבגדהוזחטיכלמנסעפצקרשתםןףץך'.split('');
      const enAlphabet = 'abcdefghijklmnopqrstuvwxyz'.split('');
      const isLetter = (ch) => /^[\p{L}]$/u.test(ch);

      function pickFromPool(arr, seedOffset=0){
        if (!arr.length) return '';
        if (DAILY){
          const idx = (SEED + seedOffset) % arr.length;
          return arr[idx];
        }
        return arr[Math.floor(Math.random()*arr.length)];
      }

      function makeSymbols(alpha){
        // Return list of display symbols for cipher mapping
        if (SYMBOLSET === 'letters'){
          return alpha.slice(); // same set size as alphabet
        }
        // numbers: generate "01","02","03",... length = alpha.length
        const out = [];
        for (let i=1;i<=alpha.length;i++){
          out.push(String(i).padStart(2,'0'));
        }
        return out;
      }

      function buildCipherForLanguage(currentLang){
        const alpha = (currentLang==='he') ? heAlphabet : enAlphabet;
        const symbols = makeSymbols(alpha);
        // Deterministic shuffle for daily else random
        function shuffled(arr){
          const a = arr.slice();
          for (let i=a.length-1;i>0;i--){
            const j = Math.floor(Math.random()*(i+1));
            [a[i],a[j]] = [a[j],a[i]];
          }
          return a;
        }
        function dailyShuffle(arr, seedStr){
          const a = arr.slice();
          let seed = 0;
          for (let i=0;i<seedStr.length;i++) seed = (seed*131 + seedStr.charCodeAt(i))>>>0;
          for (let i=a.length-1;i>0;i--){
            seed = (seed*1664525 + 1013904223)>>>0;
            const j = seed % (i+1);
            [a[i],a[j]] = [a[j],a[i]];
          }
          return a;
        }
        const seedStr = (DAILY ? (SEED + '|' + currentLang + '|' + DIFF) : (Math.random()+''));
        const perm = DAILY ? dailyShuffle(symbols, seedStr) : shuffled(symbols);

        // Map: plain alpha[i] -> cipher perm[i]
        const p2c = new Map(); c2p = new Map();
        for (let i=0;i<alpha.length;i++){
          p2c.set(alpha[i], perm[i]);
          c2p.set(perm[i], alpha[i]);
        }
        usedSymbols = []; // will be derived from ciphertext in use
        return {alpha, p2c, c2p};
      }

      function encryptText(p2c, text, currentLang){
        const arr = Array.from(text);
        const out = [];
        const usedSet = new Set();
        for (const ch of arr){
          const low = ch.toLocaleLowerCase();
          if (!isLetter(low)){ out.push(ch); continue; }
          const mapped = p2c.get(low);
          if (!mapped){ out.push(ch); continue; }
          // Preserve case for English letters only when symbolset=letters
          if (SYMBOLSET==='letters' && currentLang==='en' && ch!==low){
            out.push(String(mapped).toUpperCase());
          } else {
            out.push(String(mapped));
          }
          usedSet.add(String(mapped).toLowerCase());
        }
        usedSymbols = Array.from(usedSet);
        // Sort for stable map order: numbers numeric, letters by alpha order
        if (SYMBOLSET==='numbers'){
          usedSymbols.sort((a,b)=>parseInt(a,10)-parseInt(b,10));
        } else {
          const alpha = (currentLang==='he') ? heAlphabet : enAlphabet;
          usedSymbols.sort((a,b)=>alpha.indexOf(a)-alpha.indexOf(b));
        }
        return out;
      }

      // --- Score/Timer ---
      let finished=false, hintsUsed=0, baseScore=1000;
      let timerEnabled=false, timeLeft=0, timeSpent=0, timerHandle=null;
      function fmt(sec){ const m=Math.floor(sec/60),s=sec%60; return (m<10?'0':'')+m+':'+(s<10?'0':'')+s; }
      function paintTimer(){
        if (!timerEl) return;
        timerEl.hidden = !timerEnabled;
        if (timerEnabled) timerEl.textContent = fmt(Math.max(0,timeLeft));
      }
      function startTimer(){
        if (!timerEl || finished || !timerEnabled) return;
        paintTimer();
        if (timerHandle) clearInterval(timerHandle);
        timerHandle = setInterval(()=>{
          if (finished || !timerEnabled){ clearInterval(timerHandle); return; }
          timeLeft--; timeSpent++;
          if (timeLeft<=0){
            stopTimer();
            msgEl.textContent = (lang==='he'?'הזמן הסתיים. אפשר לחשוף פתרון או להמשיך.':'Time up. Reveal or keep playing.');
          }
          paintTimer();
          if (!finished) updateScore();
        },1000);
      }
      function stopTimer(){ if (timerHandle){ clearInterval(timerHandle); timerHandle=null; } }
      function toggleTimer(){
        if (finished) return;
        timerEnabled = !timerEnabled;
        if (timerEnabled){
          if (timeLeft<=0) timeLeft = (INIT_TIMER>0?INIT_TIMER:60);
          startTimer();
        } else stopTimer();
        paintTimer(); updateScore();
      }

      function wrongCount(){
        let w=0;
        for (const s of usedSymbols){
          const g = (guess.get(s)||'').toLocaleLowerCase();
          const t = (c2p.get(s)||'').toLocaleLowerCase();
          if (g && g!==t) w++;
        }
        return w;
      }
      function computeScore(forceZero=false){
        if (forceZero) return 0;
        let score = baseScore - wrongCount()*2 - hintsUsed*20;
        if (timerEnabled && timeSpent>0){
          const bonus = Math.max(0, 100 - Math.floor(timeSpent/2));
          score += bonus;
        }
        return Math.max(0,score);
      }
      function updateScore(forceZero=false){
        const sc = computeScore(forceZero);
        scoreEl.textContent = (lang==='he'?'ניקוד: ':'Score: ') + sc + (finished ? (lang==='he'?' (סופי)':' (final)') : '');
        return sc;
      }

      function checkWin(){
        for (const s of usedSymbols){
          const g = (guess.get(s)||'').toLocaleLowerCase();
          if (g !== (c2p.get(s)||'')) return false;
        }
        endGame(false);
        return true;
      }
      function lockUI(){
        // disable inputs
        mapWrap.querySelectorAll('input').forEach(i=>i.disabled=true);
        if (btnHint) btnHint.disabled=true;
        if (btnReveal) btnReveal.disabled=true;
        if (btnToggle) btnToggle.disabled=true;
      }
      function endGame(forceReveal){
        if (finished) return;
        finished = true;
        stopTimer();
        const sc = updateScore(forceReveal);
        msgEl.textContent = (lang==='he'
          ? (forceReveal ? 'הפתרון נחשף. ציון סופי: '+sc : 'כל הכבוד! פוענח. ציון סופי: '+sc)
          : (forceReveal ? 'Revealed. Final score: '+sc : 'Great! Decoded. Final score: '+sc));
        lockUI();
      }

      // --- Build Map UI ---
      function buildMapUI(){
        mapWrap.innerHTML = '';
        const table = document.createElement('div');
        table.className = 'glc-map-table';
        mapWrap.appendChild(table);
        for (const sym of usedSymbols){
          const symBox = document.createElement('div');
          symBox.className = 'sym';
          symBox.textContent = sym;
          const inp = document.createElement('input');
          inp.maxLength = 1;
          inp.placeholder = (lang==='he'?'אות':'A');
          inp.addEventListener('input',(e)=>{
            const v = (e.target.value||'').trim();
            const ch = v ? Array.from(v)[0] : '';
            guess.set(sym, ch);
            renderGrid();
            updateScore();
            checkWin();
          });
          table.appendChild(symBox);
          table.appendChild(inp);
        }
      }

      // --- Render Grid ---
      function renderGrid(){
        gridEl.innerHTML = '';
        for (let i=0;i<ciphertextChars.length;i++){
          const c = ciphertextChars[i];
          const p = plaintextChars[i];
          const cell = document.createElement('div');
          cell.className = 'glc-cell';
          const top = document.createElement('div');
          top.className = 'ct';
          top.textContent = c;
          const bottom = document.createElement('div');
          bottom.className = 'pt';
          if (/^[0-9A-Za-z\u0590-\u05FF]{1,2}$/.test(String(c))){ // symbol token
            const g = (guess.get(String(c).toLowerCase())||'');
            bottom.textContent = g || (lang==='he'?'?':'?');
            const t = (c2p.get(String(c).toLowerCase())||'');
            cell.style.borderColor = (g && g.toLocaleLowerCase()===t) ? '#8bc34a' : '#ddd';
          } else {
            bottom.textContent = p; cell.style.opacity = .75;
          }
          cell.appendChild(top); cell.appendChild(bottom); gridEl.appendChild(cell);
        }
      }

      function giveHint(n=2){
        if (finished) return;
        const cand = usedSymbols.filter(s=>{
          const g=(guess.get(s)||'').toLocaleLowerCase(); const t=(c2p.get(s)||'').toLocaleLowerCase();
          return !(g && g===t);
        });
        if (!cand.length) return;
        for (let i=0;i<Math.min(n,cand.length);i++){
          const k = cand[Math.floor(Math.random()*cand.length)];
          guess.set(k, (c2p.get(k)||''));
        }
        hintsUsed++;
        buildMapUI(); renderGrid(); updateScore(); checkWin();
      }
      function revealAll(){
        if (finished) return;
        usedSymbols.forEach(s=> guess.set(s, (c2p.get(s)||'')) );
        buildMapUI(); renderGrid();
        endGame(true); // score forced to 0
      }

      function showMeta(){
        metaEl.textContent = (lang==='he' ? ('קושי: '+DIFF) : ('Difficulty: '+DIFF));
        // toggle dir
        $root.setAttribute('dir', lang==='he'?'rtl':'ltr');
      }

      function newPuzzle(){
        // pick plaintext per lang and encrypt
        guess = new Map(); hintsUsed=0; timeSpent=0;
        const text = (lang==='he') ? pickFromPool(poolHE, 0) : pickFromPool(poolEN, 1);
        plaintext = text;
        const {alpha, p2c, c2p:mapC2P} = buildCipherForLanguage(lang);
        c2p = mapC2P;
        plaintextChars = Array.from(plaintext);
        ciphertextChars = encryptText(p2c, plaintext, lang);
        buildMapUI();
        renderGrid();
        msgEl.textContent = '';
        finished=false;
        updateScore();
        if (timerEl && INIT_TIMER>0 && timerEnabled){
          timeLeft = INIT_TIMER; startTimer();
        }
        showMeta();
      }

      // Wire buttons
      btnHe.addEventListener('click', ()=>{ lang='he'; newPuzzle(); });
      btnEn.addEventListener('click', ()=>{ lang='en'; newPuzzle(); });
      btnHint.addEventListener('click', ()=> giveHint(2));
      btnReveal.addEventListener('click', ()=> revealAll());
      btnNew.addEventListener('click', ()=> newPuzzle());
      if (btnToggle && <?php echo $timer_toggle ? 'true' : 'false'; ?>){
        btnToggle.addEventListener('click', ()=> toggleTimer());
      }

      // Init timer state
      timerEnabled = (INIT_TIMER>0);
      timeLeft = INIT_TIMER>0 ? INIT_TIMER : 0;
      paintTimer();

      // First run
      newPuzzle();
    })();
    </script>
  </div>
  <?php
  return ob_get_clean();
});

