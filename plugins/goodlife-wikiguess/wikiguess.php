<?php
/**
 * Plugin Name: Goodlife – WikiGuess (Clean Text + Topic/Difficulty + Top Solution Title)
 * Description: [wikiguess mode="random|daily|specific" page="Title" lang="he" topic="" difficulty="easy|medium|hard"] – חידת ויקי: טקסט נקי (כותרות+פסקאות) מטושטש; ניחושי מילים חושפים הופעות; ניחוש שם הערך פותח הכול. בחירת שפה/נושא/קושי וכפתור "מעבר לערך". מציג את שם הערך (הפתרון) בראש הדף עם חשיפה.
 * Version: 1.5.0
 * Author: Goodlife
 */

if (!defined('ABSPATH')) exit;

/* ------------------------------------------
 * Normalization helpers
 * ------------------------------------------ */
function glwg_norm($s){
  $s = trim((string)$s);
  $s = preg_replace('/[\x{0591}-\x{05C7}]/u', '', $s); // הסרת ניקוד
  $map = ['ך'=>'כ','ם'=>'מ','ן'=>'נ','ף'=>'פ','ץ'=>'צ']; // סופיות → בסיס
  $s = strtr($s, $map);
  if (function_exists('mb_strtolower')) $s = mb_strtolower($s, 'UTF-8'); else $s = strtolower($s);
  return $s;
}

/* ------------------------------------------
 * Clean article HTML (keep only headings & paragraphs; strip media/tables/lists etc.)
 * ------------------------------------------ */
function glwg_clean_article_html($html){
  if (!$html) return '';

  // remove scripts/styles/meta/link
  $html = preg_replace('~<(script|style|meta|link)[^>]*>.*?</\1>~isu', '', $html);

  // remove media/boxes
  $remove_selectors = [
    'table','figure','img','video','audio','svg','math','aside','footer','nav','iframe',
    'div.mw-references-wrap','ol.references','sup.reference','div.infobox','table.infobox',
    'div.navbox','table.navbox','div.vertical-navbox','div.thumb','div.gallery','ul.gallery'
  ];
  foreach ($remove_selectors as $sel){
    if (strpos($sel,'.')!==false){
      list($tag,$cls)=explode('.',$sel,2);
      $html = preg_replace('~<'.$tag.'[^>]*class="[^"]*\b'.preg_quote($cls,'~').'\b[^"]*"[^>]*>.*?</'.$tag.'>~isu','',$html);
    } else {
      $html = preg_replace('~<'.$sel.'[^>]*>.*?</'.$sel.'>~isu','',$html);
    }
  }

  // list items -> paragraphs; remove list wrappers
  $html = preg_replace('~<li[^>]*>(.*?)</li>~isu','<p>$1</p>',$html);
  $html = preg_replace('~</?(ul|ol)[^>]*>~isu','',$html);

  // drop H1 (title) if present
  $html = preg_replace('~<h1[^>]*>.*?</h1>~isu','',$html);

  // keep only h2–h6 + p; strip other tags (leave text)
  $html = preg_replace_callback('~<(/?)(\w+)([^>]*)>~isu', function($m){
    $tag = strtolower($m[2]);
    $keep = in_array($tag, ['h2','h3','h4','h5','h6','p'], true);
    return $keep ? $m[0] : '';
  }, $html);

  // tidy whitespace & add line breaks for readability
  $html = preg_replace('~\s+~u',' ',$html);
  $html = preg_replace('~</(h2|h3|h4|h5|h6)>~iu', "</$1>\n", $html);
  $html = preg_replace('~</p>~iu', "</p>\n", $html);

  return trim($html);
}

/* ------------------------------------------
 * Assets (CSS/JS)
 * ------------------------------------------ */
add_action('wp_enqueue_scripts', function(){
  // CSS
  $css = <<<'CSS'
.glwg{direction:rtl;font-family:system-ui,-apple-system,"Segoe UI",Roboto,"Noto Sans Hebrew",Arial,sans-serif;max-width:740px;width:100%;margin:0 auto;padding:14px;border-radius:14px;border:1px solid #eee;background:#fff;box-sizing:border-box}
.glwg *{box-sizing:border-box}
.glwg-head{display:flex;gap:10px;flex-wrap:wrap;align-items:center;justify-content:space-between}
.glwg-title{margin:0;font-size:18px;font-weight:800}
.glwg-hud{font-size:14px;color:#555;display:flex;gap:14px;align-items:center}
.glwg-controls{display:flex;gap:8px;flex-wrap:wrap;margin:10px 0}
.glwg select,.glwg input[type="text"]{padding:10px 12px;border:1px solid #e5e7eb;border-radius:10px;max-width:100%}
.glwg input[type="text"]{min-width:180px}
.glwg-btn{padding:10px 14px;border:1px solid #e5e7eb;border-radius:10px;background:#f7f7f7;cursor:pointer;font-weight:700}
.glwg-btn.primary{background:#2563eb;color:#fff;border-color:#2563eb}
.glwg-btn.link{background:#111827;color:#fff;border-color:#111827}
.glwg-btn:disabled{opacity:.45;cursor:not-allowed}
.glwg-msg{min-height:24px;margin-top:6px;font-weight:700;text-align:center;color:#2e7d32}

/* NEW: top solution title */
.glwg-solution-title{display:none;margin:12px 0 6px;font-weight:800;font-size:1.25rem;line-height:1.3;color:#111827}
.glwg-solution-title.show{display:block}

.glwg-article{line-height:1.9;font-size:18px;border-top:1px dashed #e5e7eb;padding-top:12px;margin-top:8px;overflow-wrap:anywhere;word-break:break-word;white-space:pre-wrap}
.glwg-article h2,.glwg-article h3,.glwg-article h4,.glwg-article h5,.glwg-article h6{margin:18px 0 6px;font-weight:800;font-size:1.1em;filter:blur(7px)}
.glwg-article p{margin:10px 0;filter:blur(7px)}
/* מילים/מספרים עטופים; רווחים/פיסוק נשמרים כטקסט רגיל */
.glwg-article .tok{display:inline;filter:blur(7px);transition:filter .2s ease, background-color .2s ease}
.glwg-article .tok.revealed{filter:none;background:rgba(255,255,0,.25)}
.glwg-article .punct{filter:none}

.glwg-solution{display:none;margin-top:12px}
.glwg-solution.show{display:block}
.glwg-foot{display:flex;justify-content:space-between;align-items:center;margin-top:10px;color:#6b7280;font-size:13px}
@media(max-width:560px){.glwg input[type="text"]{min-width:140px}}
CSS;

  wp_register_style('glwg-style', false, [], null);
  wp_enqueue_style('glwg-style');
  wp_add_inline_style('glwg-style', $css);

  // JS
  $js = <<<'JS'
(()=>{

  // ===== helpers =====
  const FINAL2BASE = { "ך":"כ", "ם":"מ", "ן":"נ", "ף":"פ", "ץ":"צ" };
  const norm = s => (s||'')
    .normalize('NFC')
    .replace(/[\u0591-\u05C7]/g,'') // ניקוד
    .replace(/[ךםןףץ]/g, ch => FINAL2BASE[ch] || ch)
    .toLowerCase();

  // Tokenize: wrap only words/numbers in spans; keep spaces/punct as text (so spacing looks natural)
  function tokenizeDOM(root){
    const walker = document.createTreeWalker(root, NodeFilter.SHOW_TEXT, {
      acceptNode: (n)=> n.nodeValue ? NodeFilter.FILTER_ACCEPT : NodeFilter.FILTER_REJECT
    });
    const textNodes = [];
    while (walker.nextNode()) textNodes.push(walker.currentNode);

    const wordRe = /([\p{L}\p{N}׳״"']+)/gu; // capture words/numbers
    textNodes.forEach(node=>{
      const text = node.nodeValue;
      if (!text.trim()) return;
      const parts = text.split(wordRe); // preserves separators (spaces/punct)
      if (parts.length<=1) return;

      const frag = document.createDocumentFragment();
      for (let i=0;i<parts.length;i++){
        const part = parts[i];
        if (!part) continue;
        if (wordRe.test(part)){ // word/number
          const span = document.createElement('span');
          span.className = 'tok';
          span.dataset.base = norm(part);
          span.textContent = part;
          frag.appendChild(span);
        } else {
          frag.appendChild(document.createTextNode(part)); // keep spaces/punct as-is
        }
        wordRe.lastIndex = 0;
      }
      node.parentNode.replaceChild(frag, node);
    });
  }

  function revealAllWords(root){
    root.querySelectorAll('.tok').forEach(el=> el.classList.add('revealed'));
    root.querySelectorAll('h2,h3,h4,h5,h6,p').forEach(el=> el.style.filter='none');
  }

  // ===== Progress store =====
  const STORE='glwg_progress_v5';
  const loadP = ()=>{ try{return JSON.parse(localStorage.getItem(STORE)||'{}');}catch(e){return{};} };
  const saveP = p => localStorage.setItem(STORE, JSON.stringify(p));

  // ===== Boot per instance =====
  function boot(root){
    const initLang = root.dataset.lang || 'he';
    const mode = root.dataset.mode || 'random';
    const page = root.dataset.page || '';
    const initTopic = root.dataset.topic || '';
    const initDiff  = root.dataset.difficulty || 'easy';

    // UI refs
    const langSel   = root.querySelector('[data-lang]');
    const topicSel  = root.querySelector('[data-topic]');
    const diffSel   = root.querySelector('[data-diff]');
    const wordInput = root.querySelector('[data-guess]');
    const wordBtn   = root.querySelector('[data-act="guess"]');
    const artBtn    = root.querySelector('[data-act="guess-article"]');
    const artInput  = root.querySelector('[data-guess-article]');
    const hintBtn   = root.querySelector('[data-act="hint"]');
    const solveBtn  = root.querySelector('[data-act="solution"]');
    const newBtn    = root.querySelector('[data-act="new"]');
    const msg       = root.querySelector('.glwg-msg');
    const solTitle  = root.querySelector('.glwg-solution-title'); // NEW: top solution title
    const art       = root.querySelector('.glwg-article');
    const ptsEl     = root.querySelector('[data-points]');
    const triesEl   = root.querySelector('[data-tries]');
    const solBx     = root.querySelector('.glwg-solution');

    langSel.value  = initLang;
    diffSel.value  = initDiff;
    if (initTopic) topicSel.value = initTopic;

    const prog = loadP(); prog.points??=0; prog.games??=0; saveP(prog);
    const cfg  = { HINT_COST: 20, HIT_BASE: 3, MINLEN: 1 }; // word/number – even single char

    let session = { titleBase:'', titleRaw:'', url:'', tries:0 };

    function hud(){ ptsEl.textContent=prog.points; triesEl.textContent=session.tries; }
    function award(n){ prog.points += n; saveP(prog); hud(); }

    function doGuessWord(){
      const g = norm((wordInput.value||'').trim());
      wordInput.value='';
      if (!g || g.length < cfg.MINLEN){ msg.textContent='הקלד/י מילה/מספר (לפחות '+cfg.MINLEN+')'; msg.style.color='#c0392b'; return; }
      session.tries++; hud();

      let hits = 0;
      art.querySelectorAll('.tok:not(.revealed)').forEach(el=>{
        if (el.dataset.base === g){ el.classList.add('revealed'); hits++; }
      });
      if (hits>0){
        art.querySelectorAll('p,h2,h3,h4,h5,h6').forEach(block=>{
          if (block.querySelector('.tok.revealed')) block.style.filter='none';
        });
        msg.textContent = `יפה! "${g}" הופיעה ${hits}× (+${cfg.HIT_BASE*hits} נק׳)`; msg.style.color='#2e7d32';
        award(cfg.HIT_BASE * hits);
      } else {
        msg.textContent = 'לא נמצאה התאמה. נסו מילה אחרת.'; msg.style.color='#c0392b';
      }
    }

    function doGuessArticle(){
      const g = norm((artInput.value||'').trim());
      if (!g){ msg.textContent='הקלד/י ניחוש לשם הערך'; msg.style.color='#c0392b'; return; }
      session.tries++; hud();
      if (g === session.titleBase){
        msg.textContent = `בול! ניחשתם את שם הערך (+50 נק׳)`; msg.style.color='#2e7d32';
        award(50); prog.games++; saveP(prog);
        revealAllWords(art);
        // NEW: show solution title on top
        solTitle.textContent = session.titleRaw || '(?)';
        solTitle.classList.add('show');
        // link button
        solBx.innerHTML = `<a class="glwg-btn link" href="${session.url}" target="_blank" rel="noopener">מעבר לערך</a>`;
        solBx.classList.add('show');
      } else {
        msg.textContent = 'עדיין לא. נסו שוב.'; msg.style.color='#c0392b';
      }
    }

    function doHint(){
      const pool = Array.from(art.querySelectorAll('.tok:not(.revealed)'));
      if (!pool.length){ msg.textContent='אין יותר מה לחשוף 🙂'; msg.style.color='#2e7d32'; return; }
      const freq = {};
      pool.forEach(el=>{ const b=el.dataset.base; freq[b]=(freq[b]||0)+1; });
      let best=null, max=-1;
      Object.entries(freq).forEach(([w,c])=>{ if(c>max){max=c;best=w;} });
      prog.points = Math.max(0, prog.points - cfg.HINT_COST); saveP(prog); hud();
      pool.forEach(el=>{ if(el.dataset.base===best) el.classList.add('revealed'); });
      art.querySelectorAll('p,h2,h3,h4,h5,h6').forEach(block=>{
        if (block.querySelector('.tok.revealed')) block.style.filter='none';
      });
      msg.textContent = `רמז: חשפנו את "${best}" (−${cfg.HINT_COST} נק׳)`; msg.style.color='#2e7d32';
    }

    function showSolution(){
      revealAllWords(art);
      msg.textContent='כל הטקסט נחשף. נסו לנחש גם את שם הערך 🙂'; msg.style.color='#2e7d32';
      // NEW: show top solution title (use original title casing)
      solTitle.textContent = session.titleRaw || '(?)';
      solTitle.classList.add('show');
      solBx.innerHTML = `<a class="glwg-btn link" href="${session.url}" target="_blank" rel="noopener">מעבר לערך</a>`;
      solBx.classList.add('show');
    }

    function newGame(kind, lang, page, topic, diff){
      msg.textContent='טוען…'; msg.style.color='#6b7280';
      solBx.classList.remove('show'); solBx.innerHTML='';
      solTitle.classList.remove('show'); solTitle.textContent=''; // NEW: clear top title
      art.innerHTML='';
      const form = new URLSearchParams();
      form.append('action','glwg_fetch_full');
      form.append('mode', kind);
      form.append('lang', lang);
      form.append('page', page||'');
      form.append('topic', topic||'');
      form.append('difficulty', diff||'easy');

      fetch(glwg_vars.ajaxurl, {method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:form.toString()})
      .then(r => r.json()).then(j=>{
        if(!j.success){ throw new Error(j.data||'fetch failed'); }
        const data = j.data;
        session = { titleBase: norm(data.title), titleRaw: data.title, url: data.url, tries: 0 };
        art.innerHTML = data.html;
        tokenizeDOM(art); // wrap tokens; keep spacing/punct as-is
        msg.textContent='התחל/י לנחש מילים…'; msg.style.color='#6b7280';
        hud();
      }).catch(e=>{
        console.error(e);
        msg.textContent='שגיאה בטעינת הערך. נסו שוב.'; msg.style.color='#c0392b';
      });
    }

    // events
    wordBtn.onclick = doGuessWord;
    wordInput.addEventListener('keydown', e=>{ if(e.key==='Enter') doGuessWord(); });
    artBtn.onclick = doGuessArticle;
    artInput.addEventListener('keydown', e=>{ if(e.key==='Enter') doGuessArticle(); });
    hintBtn.onclick = doHint;
    solveBtn.onclick = showSolution;
    newBtn.onclick   = ()=> newGame('random', langSel.value, '', topicSel.value, diffSel.value);
    langSel.onchange = ()=> newGame('random', langSel.value, '', topicSel.value, diffSel.value);
    topicSel.onchange= ()=> newGame('random', langSel.value, '', topicSel.value, diffSel.value);
    diffSel.onchange = ()=> newGame('random', langSel.value, '', topicSel.value, diffSel.value);

    // init
    ptsEl.textContent = prog.points;
    newGame(mode, initLang, page, initTopic, initDiff);
  }

  document.addEventListener('DOMContentLoaded', ()=>{
    document.querySelectorAll('.glwg').forEach(boot);
  });

})();
JS;

  wp_register_script('glwg-script', '', [], false, true);
  wp_enqueue_script('glwg-script');
  wp_add_inline_script('glwg-script', $js);
});

/* ------------------------------------------
 * AJAX – fetch article, clean to text-only, apply topic/difficulty
 * ------------------------------------------ */
add_action('wp_ajax_glwg_fetch_full', 'glwg_fetch_full');
add_action('wp_ajax_nopriv_glwg_fetch_full', 'glwg_fetch_full');

function glwg_fetch_full(){
  $mode = isset($_POST['mode']) ? sanitize_text_field($_POST['mode']) : 'random';
  $lang = isset($_POST['lang']) ? sanitize_text_field($_POST['lang']) : 'he';
  $page = isset($_POST['page']) ? sanitize_text_field($_POST['page']) : '';
  $topic= isset($_POST['topic']) ? sanitize_text_field($_POST['topic']) : '';
  $diff = isset($_POST['difficulty']) ? sanitize_text_field($_POST['difficulty']) : 'easy';

  if ($mode === 'daily') {
    $d = date('Y-m-d');
    $saved_d = get_option('glwg_full_daily_date');
    $saved_p = get_option('glwg_full_daily_payload');
    if ($saved_d === $d && !empty($saved_p)) {
      wp_send_json_success($saved_p);
    }
  }

  $payload = glwg_fetch_full_payload($lang, $page, $mode, $topic, $diff);
  if (!$payload){
    wp_send_json_error('failed to fetch wiki');
  }

  if ($mode === 'daily') {
    update_option('glwg_full_daily_date', date('Y-m-d'));
    update_option('glwg_full_daily_payload', $payload);
  }

  wp_send_json_success($payload);
}

function glwg_fetch_full_payload($lang='he', $page='', $mode='random', $topic='', $difficulty='easy'){
  $lang = preg_replace('~[^a-z\-]~i','',$lang) ?: 'he';
  $difficulty = in_array($difficulty, ['easy','medium','hard'], true) ? $difficulty : 'easy';

  // choose title
  if ($mode === 'specific' && $page){
    $title = $page;
  } else {
    if ($topic){
      $cands = glwg_wiki_search_titles($lang, $topic, 30);
      if (!$cands) $cands = glwg_wiki_search_titles($lang, $topic, 10);
      if (!$cands) $cands = [ glwg_wiki_random_title($lang) ];
      $title = glwg_pick_by_difficulty($cands, $difficulty);
    } else {
      if ($difficulty === 'easy'){
        $top = glwg_pageviews_top($lang, 200);
        $title = $top ? $top[array_rand($top)] : glwg_wiki_random_title($lang);
      } elseif ($difficulty === 'medium'){
        $seed = ($lang==='he') ? 'ישראל OR מדע OR היסטוריה' : 'the OR science OR history';
        $cands = glwg_wiki_search_titles($lang, $seed, 30);
        $title = $cands ? glwg_pick_by_difficulty($cands, 'medium') : glwg_wiki_random_title($lang);
      } else {
        $title = glwg_wiki_random_title($lang);
      }
    }
    if (!$title) return null;
  }

  // fetch full HTML (Parsoid) -> fallback to Mobile -> fallback to Summary
  $html = glwg_wiki_full_html($lang, $title);
  if (!$html) $html = glwg_wiki_mobile_html($lang, $title);
  if (!$html){
    $sum = glwg_wiki_summary($lang, $title);
    if (!$sum) return null;
    $html = '<p>'.esc_html($sum['extract']).'</p>';
  }

  // clean to text-only (headings+paragraphs)
  $clean = glwg_clean_article_html($html);
  $url   = "https://$lang.wikipedia.org/wiki/" . rawurlencode($title);
  return ['title'=>$title, 'url'=>$url, 'html'=>$clean];
}

/* ---------- pick candidate by difficulty ---------- */
function glwg_pick_by_difficulty($cands, $difficulty){
  $cands = array_values(array_filter(array_unique($cands)));
  $n = count($cands);
  if ($n===0) return null;
  if ($n<=3) return $cands[array_rand($cands)];
  if ($difficulty==='easy'){
    $slice = array_slice($cands, 0, max(3, (int)floor($n*0.25)));
  } elseif ($difficulty==='medium'){
    $slice = array_slice($cands, (int)floor($n*0.25), max(3, (int)floor($n*0.4)));
  } else {
    $slice = array_slice($cands, (int)floor($n*0.6));
  }
  return $slice[array_rand($slice)];
}

/* ---------- search by topic ---------- */
function glwg_wiki_search_titles($lang='he', $query='', $limit=20){
  $query = trim($query);
  if (!$query) return [];
  $url = add_query_arg([
    'action' => 'query',
    'list'   => 'search',
    'format' => 'json',
    'srlimit'=> $limit,
    'srprop' => '',
    'srsearch' => $query
  ], "https://$lang.wikipedia.org/w/api.php");
  $r = wp_remote_get($url, ['timeout'=>12, 'redirection'=>3, 'user-agent'=>'Goodlife-WikiGuess/1.5']);
  if (is_wp_error($r) || wp_remote_retrieve_response_code($r)!==200) return [];
  $data = json_decode(wp_remote_retrieve_body($r), true);
  if (!isset($data['query']['search'])) return [];
  $titles = [];
  foreach($data['query']['search'] as $row){
    if (!empty($row['title'])) $titles[] = $row['title'];
  }
  return $titles;
}

/* ---------- top viewed (yesterday) ---------- */
function glwg_pageviews_top($lang='he', $limit=200){
  $project = ($lang==='en') ? 'en.wikipedia' : $lang.'.wikipedia';
  $y = gmdate('Y'); $m = gmdate('m'); $d = gmdate('d', strtotime('-1 day'));
  $url = "https://wikimedia.org/api/rest_v1/metrics/pageviews/top/$project.org/all-access/$y/$m/$d";
  $r = wp_remote_get($url, ['timeout'=>12, 'redirection'=>3, 'user-agent'=>'Goodlife-WikiGuess/1.5']);
  if (is_wp_error($r) || wp_remote_retrieve_response_code($r)!==200) return [];
  $data = json_decode(wp_remote_retrieve_body($r), true);
  if (empty($data['items'][0]['articles'])) return [];
  $out = [];
  foreach($data['items'][0]['articles'] as $a){
    $t = $a['article'] ?? '';
    if (!$t) continue;
    if (preg_match('~^(Main_Page|עמוד_ראשי)$~u', $t)) continue;
    $out[] = urldecode(str_replace('_', ' ', $t));
    if (count($out) >= $limit) break;
  }
  return $out;
}

/* ---------- REST helpers ---------- */
function glwg_wiki_random_title($lang='he'){
  $url = "https://$lang.wikipedia.org/api/rest_v1/page/random/summary";
  $r = wp_remote_get($url, ['timeout'=>14, 'redirection'=>3, 'user-agent'=>'Goodlife-WikiGuess/1.5']);
  if (is_wp_error($r)) return null;
  if (wp_remote_retrieve_response_code($r) !== 200) return null;
  $data = json_decode(wp_remote_retrieve_body($r), true);
  if (!is_array($data) || empty($data['title'])) return null;
  return $data['title'];
}
function glwg_wiki_summary($lang='he', $title=''){
  $enc = rawurlencode($title);
  $url = "https://$lang.wikipedia.org/api/rest_v1/page/summary/$enc";
  $r = wp_remote_get($url, ['timeout'=>14, 'redirection'=>3, 'user-agent'=>'Goodlife-WikiGuess/1.5']);
  if (is_wp_error($r)) return null;
  if (wp_remote_retrieve_response_code($r) !== 200) return null;
  $data = json_decode(wp_remote_retrieve_body($r), true);
  if (!is_array($data) || empty($data['extract'])) return null;
  return $data;
}
// Parsoid HTML
function glwg_wiki_full_html($lang='he', $title=''){
  $enc = rawurlencode($title);
  $url = "https://$lang.wikipedia.org/api/rest_v1/page/html/$enc";
  $r = wp_remote_get($url, [
    'timeout'=>14, 'redirection'=>3,
    'user-agent'=>'Goodlife-WikiGuess/1.5',
    'headers'=>['Accept'=>'text/html']
  ]);
  if (is_wp_error($r)) return null;
  if (wp_remote_retrieve_response_code($r) !== 200) return null;
  $html = wp_remote_retrieve_body($r);
  if (!$html) return null;
  if (preg_match('~<body[^>]*>(.*?)</body>~isu', $html, $m)){
    $html = $m[1];
  }
  return $html;
}
// Mobile HTML fallback
function glwg_wiki_mobile_html($lang='he', $title=''){
  $enc = rawurlencode($title);
  $url = "https://$lang.wikipedia.org/api/rest_v1/page/mobile-html/$enc";
  $r = wp_remote_get($url, [
    'timeout'=>14, 'redirection'=>3,
    'user-agent'=>'Goodlife-WikiGuess/1.5',
    'headers'=>['Accept'=>'text/html']
  ]);
  if (is_wp_error($r)) return null;
  if (wp_remote_retrieve_response_code($r) !== 200) return null;
  $html = wp_remote_retrieve_body($r);
  if (!$html) return null;
  if (preg_match('~<body[^>]*>(.*?)</body>~isu', $html, $m)){
    $html = $m[1];
  }
  return $html;
}

/* ------------------------------------------
 * Shortcode
 * ------------------------------------------ */
add_shortcode('wikiguess', function($atts){
  $a = shortcode_atts([
    'mode' => 'random',       // random | daily | specific
    'page' => '',             // כש-mode="specific"
    'lang' => 'he',           // he | en | ...
    'topic'=> '',             // נושא התחלתי (אופציונלי)
    'difficulty' => 'easy',   // easy | medium | hard
  ], $atts, 'wikiguess');

  // ensure assets
  if (wp_style_is('glwg-style','registered'))  wp_enqueue_style('glwg-style');
  if (wp_script_is('glwg-script','registered')) wp_enqueue_script('glwg-script');

  wp_localize_script('glwg-script', 'glwg_vars', ['ajaxurl' => admin_url('admin-ajax.php')]);

  // topics (example set; adjust as needed)
  $topics = [
    ''=>'— ללא נושא —','ישראל'=>'ישראל','היסטוריה'=>'היסטוריה','מדע'=>'מדע','גיאוגרפיה'=>'גיאוגרפיה','תרבות'=>'תרבות',
    'ספורט'=>'ספורט','ביוגרפיה'=>'ביוגרפיה','טכנולוגיה'=>'טכנולוגיה','קולנוע'=>'קולנוע','מוזיקה'=>'מוזיקה'
  ];
  if ($a['lang']==='en'){
    $topics = [
      ''=>'— No topic —','Israel'=>'Israel','History'=>'History','Science'=>'Science','Geography'=>'Geography','Culture'=>'Culture',
      'Sports'=>'Sports','Biography'=>'Biography','Technology'=>'Technology','Cinema'=>'Cinema','Music'=>'Music'
    ];
  }

  ob_start(); ?>
  <div class="glwg"
       data-mode="<?php echo esc_attr($a['mode']); ?>"
       data-page="<?php echo esc_attr($a['page']); ?>"
       data-lang="<?php echo esc_attr($a['lang']); ?>"
       data-topic="<?php echo esc_attr($a['topic']); ?>"
       data-difficulty="<?php echo esc_attr($a['difficulty']); ?>">

    <div class="glwg-head">
      <h3 class="glwg-title">WikiGuess</h3>
      <div class="glwg-hud">
        <span>ניקוד: <b data-points>0</b></span>
        <span>ניסיונות: <b data-tries>0</b></span>
      </div>
    </div>

    <div class="glwg-controls">
      <label>שפה:
        <select data-lang>
          <option value="he" <?php selected($a['lang'], 'he'); ?>>עברית</option>
          <option value="en" <?php selected($a['lang'], 'en'); ?>>English</option>
        </select>
      </label>
      <label>נושא:
        <select data-topic>
          <?php foreach($topics as $val=>$lab): ?>
            <option value="<?php echo esc_attr($val); ?>" <?php selected($a['topic'], $val); ?>>
              <?php echo esc_html($lab); ?>
            </option>
          <?php endforeach; ?>
        </select>
      </label>
      <label>דרגת קושי:
        <select data-diff>
          <option value="easy" <?php selected($a['difficulty'],'easy'); ?>>קל</option>
          <option value="medium" <?php selected($a['difficulty'],'medium'); ?>>בינוני</option>
          <option value="hard" <?php selected($a['difficulty'],'hard'); ?>>קשה</option>
        </select>
      </label>

      <input type="text" placeholder="ניחוש מילה / מספר…" data-guess />
      <button type="button" class="glwg-btn" data-act="guess">נחש מילה</button>

      <input type="text" placeholder="ניחוש: שם הערך" data-guess-article />
      <button type="button" class="glwg-btn primary" data-act="guess-article">נחש ערך</button>

      <button type="button" class="glwg-btn" data-act="hint">רמז (−20)</button>
      <button type="button" class="glwg-btn" data-act="solution">פתרון</button>
      <button type="button" class="glwg-btn" data-act="new">חידה חדשה</button>
    </div>

    <div class="glwg-msg" aria-live="polite"></div>
    <!-- NEW: top solution title (appears when solved or when "solution" pressed) -->
    <div class="glwg-solution-title"></div>

    <div class="glwg-article"><!-- cleaned article (headings+paragraphs) will be injected here --></div>
    <div class="glwg-solution"></div>

    <div class="glwg-foot">
      <div>מקור: ויקיפדיה</div>
    </div>
  </div>
  <?php
  return ob_get_clean();
});

