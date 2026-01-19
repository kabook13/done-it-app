<?php
/**
 * Plugin Name: Goodlife Connections (Hebrew)
 * Description: [connections mode="daily|random"] – משחק קישוריות בעברית: 4 קבוצות של 4 מילים. רמזים, פתרון, ניקוד ויומי/אקראי. קל-שרת (JS בצד לקוח).
 * Version: 1.0.0
 * Author: Goodlife
 */

if (!defined('ABSPATH')) exit;

/* ---------- בנק קטגוריות קטן להדגמה (אפשר להרחיב/להטעין JSON בהמשך) ---------- */
/* ---------- בנק קטגוריות – טעינה מ-uploads אם קיים ---------- */
function glcx_bank(){
  // פולבק בסיסי (אם אין קובץ חיצוני או אם יש שגיאה)
  $fallback = [
    ['title'=>'פירות הדר', 'words'=>['תפוז','אשכולית','לימון','ליים']],
    ['title'=>'חלקי גוף',  'words'=>['לב','ריאה','כבד','עור']],
    ['title'=>'כלי נגינה', 'words'=>['פסנתר','חליל','גיטרה','תופים']],
    ['title'=>'מדינות באירופה', 'words'=>['צרפת','ספרד','איטליה','יוון']],
    ['title'=>'צבעים', 'words'=>['אדום','כחול','צהוב','ירוק']],
    ['title'=>'ימים בשבוע', 'words'=>['ראשון','שני','שלישי','רביעי']],
    ['title'=>'חיות ים', 'words'=>['דולפין','כריש','תמנון','כלב ים']],
    ['title'=>'ערי בירה', 'words'=>['ירושלים','פריז','מדריד','רומא']],
    ['title'=>'רכיבי מחשב', 'words'=>['מעבד','זיכרון','כרטיס מסך','לוח אם']],
    ['title'=>'משקאות חמים', 'words'=>['קפה','תה','קקאו','סחלב']],
  ];

  // נתיב בטוח ל-uploads
  $up = wp_upload_dir();
  if (empty($up['basedir'])) return $fallback;
  $path = trailingslashit($up['basedir']) . 'connections-bank.json';

  if (!file_exists($path)) {
    // אין קובץ – חזור לפולבק
    return $fallback;
  }

  // קאש לפי זמן שינוי הקובץ (כדי שלא נקרא אותו כל דף)
  $mtime = @filemtime($path) ?: 0;
  $tkey  = 'glcx_bank_' . md5($path . '|' . $mtime);
  $cached = get_transient($tkey);
  if ($cached && is_array($cached)) {
    return $cached;
  }

  // קריאה מהקובץ + ולידציה מינימלית
  $raw = @file_get_contents($path);
  if ($raw === false) return $fallback;

  $data = json_decode($raw, true);
  if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) {
    return $fallback;
  }

  // נירמול/סינון: רק אובייקטים עם title + words[4] מחרוזות לא ריקות
  $clean = [];
  foreach ($data as $row) {
    if (!isset($row['title'], $row['words']) || !is_array($row['words'])) continue;
    $title = trim((string)$row['title']);
    $w = array_values(array_map(function($x){ return trim((string)$x); }, $row['words']));
    $w = array_filter($w, fn($s)=> $s !== '');
    if ($title !== '' && count($w) === 4) {
      $clean[] = ['title' => $title, 'words' => array_values($w)];
    }
  }

  if (empty($clean)) return $fallback;

  // שמור קאש ל-12 שעות (יתבטל אוטומטית אם הקובץ עודכן)
  set_transient($tkey, $clean, 12 * HOUR_IN_SECONDS);
  return $clean;
}


/* ---------- נכסים ---------- */
add_action('wp_enqueue_scripts', function () {
  // CSS
  $css = <<<'CSS'
.glcx{direction:rtl;font-family:system-ui,-apple-system,"Segoe UI",Roboto,"Noto Sans Hebrew",Arial,sans-serif;max-width:840px;margin:0 auto;background:#fff;border:1px solid #eee;border-radius:14px;padding:14px}
.glcx-head{display:flex;justify-content:space-between;gap:8px;align-items:center;flex-wrap:wrap}
.glcx-title{font-size:18px;font-weight:800;margin:0}
.glcx-hud{font-size:14px;color:#555;display:flex;gap:12px;align-items:center}
.glcx-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:8px;margin:12px 0}
.glcx-card{user-select:none;padding:14px;text-align:center;border:1px solid #e6e6e6;border-radius:12px;background:#f7f7f7;cursor:pointer;font-weight:700}
.glcx-card.sel{outline:2px solid #3b82f6; background:#eef5ff}
.glcx-row{padding:10px;border:1px solid #d9ead3;background:#ecf8ea;border-radius:12px;margin:6px 0;font-weight:800}
.glcx-actions{display:flex;gap:8px;flex-wrap:wrap}
.glcx-btn{padding:10px 14px;border-radius:10px;border:1px solid #e6e6e6;background:#f7f7f7;cursor:pointer;font-weight:700}
.glcx-btn:disabled{opacity:.45;cursor:not-allowed}
.glcx-msg{min-height:24px;font-weight:700;text-align:center;margin-top:6px;color:#2e7d32}
.glcx-solution{display:none;background:#fafafa;border:1px solid #eee;border-radius:10px;padding:10px;margin-top:8px}
.glcx-solution.show{display:block}
@media(max-width:560px){ .glcx-grid{grid-template-columns:repeat(2,1fr)} }
CSS;
  wp_register_style('glcx-style', false, [], null);
  wp_enqueue_style('glcx-style');
  wp_add_inline_style('glcx-style', $css);

  // JS
  $js = <<<'JS'
(()=>{

  // ===== Utils =====
  const shuffle = arr => arr.map(v=>[Math.random(),v]).sort((a,b)=>a[0]-b[0]).map(x=>x[1]);
  const pickN = (arr, n)=>{ const a=[...arr]; let out=[]; for(let i=0;i<n && a.length;i++){ const j=Math.floor(Math.random()*a.length); out.push(a.splice(j,1)[0]); } return out; };

  // ===== Progress (נקודות/שלב) =====
  const STORE='glcx_progress_v1';
  const loadP = ()=>{ try{return JSON.parse(localStorage.getItem(STORE)||'{}');}catch(e){return{};} };
  const saveP = p => localStorage.setItem(STORE, JSON.stringify(p));

  // ===== Generator (Client) =====
  // בוחר 4 קטגוריות שונות מהבנק ומערבב 16 מילים לרשת. daily/random
  function generatePuzzle(bank, mode){
    // בוחרים 4 קטגוריות
    const cats = pickN(bank, 4);
    // מרכיבים רשימת מילים + מיפוי לקטגוריה
    const rows = cats.map(c=>({ title: c.title, words: [...c.words] }));
    const words = shuffle(rows.flatMap((r,i)=> r.words.map(w=>({w, i}))));
    return { rows, words, solved:[], mode };
  }

  // ===== Boot per instance =====
  function bootOne(root){
    const mode  = root.dataset.mode || 'daily';
    const bank  = JSON.parse(root.dataset.bank || '[]');

    const msg   = root.querySelector('.glcx-msg');
    const grid  = root.querySelector('.glcx-grid');
    const rowsC = root.querySelector('.glcx-rows');
    const solveB= root.querySelector('[data-act="submit"]');
    const hintB = root.querySelector('[data-act="hint"]');
    const solB  = root.querySelector('[data-act="solution"]');
    const newB  = root.querySelector('[data-act="new"]');
    const solBx = root.querySelector('.glcx-solution');

    const ptsEl = root.querySelector('[data-points]');
    const lvlEl = root.querySelector('[data-level]');

    const prog = loadP(); prog.points??=0; prog.level??=1; let usedHint=false;
    const HINT_COST = 30; const BASE = 100; // נקודות לבסיס, כל קבוצה = 100; 4 קבוצות → 400

    function hud(){ ptsEl.textContent=prog.points; lvlEl.textContent=prog.level; }

    let game = generatePuzzle(bank, mode);

    function renderBoard(){
      // גריד של 16 קלפים (רק אלה שעוד לא שויכו לקבוצה)
      const leftover = game.words.filter(x=> !game.solved.some(s=> s.words.includes(x.w)));
      grid.innerHTML = leftover.map((x,idx)=> `<div class="glcx-card" data-i="${x.i}" data-w="${x.w}">${x.w}</div>`).join('');
      // שורות שכבר נפתרו
      rowsC.innerHTML = game.solved.map(s=> `<div class="glcx-row">${s.title}: ${s.words.join(' · ')}</div>`).join('');
      msg.textContent = game.solved.length ? `כל הכבוד! מצאת ${game.solved.length}/4 קבוצות` : '';
      // רענון בחירות
      root._sel = [];
      grid.querySelectorAll('.glcx-card').forEach(card=>{
        card.onclick = ()=>{
          card.classList.toggle('sel');
          const k = card.getAttribute('data-w');
          if (card.classList.contains('sel')) root._sel.push(k);
          else root._sel = root._sel.filter(v=> v!==k);
        };
      });
      // כפתור שליחה פעיל רק אם יש 4 בחירות
      solveB.disabled = false;
    }

    function award(points){
      prog.points += points;
      if (game.solved.length===4) prog.level = Math.min(5, prog.level + 1);
      saveP(prog); hud();
    }

    function submitSelection(){
      const sel = (root._sel||[]).slice(0,4);
      if (sel.length !== 4){ msg.textContent='בחר/י 4 מילים'; msg.style.color='#c0392b'; return; }
      // האם כולן מקטגוריה אחת?
      const catIndex = game.rows.findIndex(r => sel.every(w => r.words.includes(w)));
      if (catIndex !== -1){
        // הצלחה – מוסיפים לשורות, מורידים מהגריד, נקודות
        const row = game.rows[catIndex];
        game.solved.push({ title: row.title, words: sel });
        award(BASE);
        msg.textContent = `מצוין! קבוצת "${row.title}" הושלמה (+${BASE} נק׳)`;
        msg.style.color = '#2e7d32';
        root._sel = [];
        renderBoard();
        // ניצחון כולל
        if (game.solved.length === 4){
          msg.textContent = `כל הכבוד! פתרת את כל 4 הקבוצות (+${BASE} נק׳ בונוס)`;
          award(BASE);
        }
      } else {
        msg.textContent = 'לא קבוצה נכונה. נסה/י שוב.';
        msg.style.color = '#c0392b';
        // נקה בחירות וסטייל
        grid.querySelectorAll('.glcx-card.sel').forEach(c=> c.classList.remove('sel'));
        root._sel = [];
      }
    }

    // אירועים
    solveB.onclick = submitSelection;

    hintB.onclick = ()=>{
      // רמז: מציג כותרת של קטגוריה *שעדיין לא נפתרה*
      const remainingRows = game.rows.filter(r => !game.solved.some(s=> s.title===r.title));
      if (!remainingRows.length){ msg.textContent='אין רמזים – הכול נפתר 🙂'; return; }
      const hintRow = remainingRows[ Math.floor(Math.random()*remainingRows.length) ];
      // הורדת נקודות מיידית
      if (!usedHint){ usedHint = true; prog.points = Math.max(0, (prog.points||0) - HINT_COST); saveP(prog); hud(); }
      msg.textContent = `רמז: חפשו מילים שקשורות ל-"${hintRow.title}" (−${HINT_COST} נק׳)`;
      msg.style.color = '#2e7d32';
    };

    solB.onclick = ()=>{
      // מציג פתרון מסודר: כל 4 הקבוצות עם המילים שלהן
      solBx.innerHTML = '<h4>פתרון</h4>' + game.rows.map(r=> `<div class="glcx-row">${r.title}: ${r.words.join(' · ')}</div>`).join('');
      solBx.classList.add('show');
      msg.textContent='';
    };

    newB.onclick = ()=>{
      usedHint=false;
      solBx.classList.remove('show');
      game = generatePuzzle(bank, 'random'); // תמיד חדשה
      renderBoard(); msg.textContent='חידה חדשה נטענה';
      msg.style.color='#2e7d32';
    };

    // Init
    hud();
    renderBoard();
  }

  document.addEventListener('DOMContentLoaded', ()=>{
    document.querySelectorAll('.glcx').forEach(root=> bootOne(root));
  });

})();
JS;
  wp_register_script('glcx-script','',[],false,true);
  wp_enqueue_script('glcx-script');
  wp_add_inline_script('glcx-script', $js);
});

/* ---------- Shortcode ---------- */
add_shortcode('connections', function($atts){
  $a = shortcode_atts([
    'mode' => 'daily', // daily | random
  ], $atts, 'connections');

  $bank = glcx_bank();
  $json = esc_attr(wp_json_encode($bank));

  ob_start(); ?>
  <div class="glcx" data-mode="<?php echo esc_attr($a['mode']); ?>" data-bank="<?php echo $json; ?>">
    <div class="glcx-head">
      <h3 class="glcx-title">קישוריות</h3>
      <div class="glcx-hud">שלב: <b data-level>1</b> · נקודות: <b data-points>0</b></div>
    </div>

    <div class="glcx-rows"></div>
    <div class="glcx-grid"></div>

    <div class="glcx-actions">
      <button type="button" class="glcx-btn" data-act="submit">אשר בחירה</button>
      <button type="button" class="glcx-btn" data-act="hint">רמז</button>
      <button type="button" class="glcx-btn" data-act="solution">פתרון</button>
      <button type="button" class="glcx-btn" data-act="new">חידה חדשה</button>
    </div>

    <div class="glcx-msg" aria-live="polite"></div>
    <div class="glcx-solution"></div>
  </div>
  <?php
  return ob_get_clean();
});
