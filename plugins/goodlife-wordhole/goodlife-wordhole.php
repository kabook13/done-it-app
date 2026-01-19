<?php
/**
 * Plugin Name: Goodlife Wordhole (חיפוש מילים עם חור)
 * Description: [wordhole] – חיפוש לפי תבנית עם חורים. התו היחיד לחור: ? (אות עברית אחת). תומך בביטויים מרובי-מילים; סימני גרש/ניקוד/כיווניות אינם נספרים.
 * Version: 1.3.2
 * Author: Goodlife
 */

if (!defined('ABSPATH')) exit;

add_action('wp_enqueue_scripts', function () {
  // סקריפט/סגנון Inline כדי למנוע תלות בקבצים חיצוניים
  wp_register_script('glwh-runtime','',[],false,true);
  wp_enqueue_script('glwh-runtime');

  $css = <<<'CSS'
.glwh *{box-sizing:border-box}
.glwh{direction:rtl;font-family:system-ui,-apple-system,"Segoe UI",Roboto,"Noto Sans Hebrew",Arial,sans-serif;color:#111}
.glwh .wrap{max-width:900px;margin:0 auto}
.glwh .controls{display:flex;flex-wrap:wrap;gap:.5rem;align-items:center;margin:0 0 1rem}
.glwh .controls input[type="text"]{flex:1;min-width:260px;padding:.6rem .8rem;border:1px solid #bbb;border-radius:.6rem;background:#fff}
.glwh .controls button{padding:.6rem .9rem;border:1px solid #bbb;border-radius:.6rem;background:#fff;cursor:pointer}
.glwh .controls button[disabled]{opacity:.55;cursor:not-allowed}
.glwh .controls button:hover:not([disabled]){background:#f6f6f6}
.glwh .count{margin:.35rem 0 .5rem;font-weight:600}
.glwh .results{display:grid;grid-template-columns:repeat(auto-fill,minmax(120px,1fr));gap:.35rem .6rem}
.glwh .chip{display:inline-flex;align-items:center;justify-content:center;padding:.35rem .55rem;border:1px solid #e5e5e5;border-radius:.5rem;background:#fafafa;font-weight:600}
.glwh .chip .hole{background:#fff0b3;border-bottom:2px solid #f3c300;border-radius:.25rem;padding:0 .1em;margin:0 .02em}
@media print{
  .glwh .controls{display:none!important}
  .glwh .results{grid-template-columns:repeat(auto-fill,minmax(90px,1fr));gap:.2rem .45rem}
}
CSS;

  wp_register_style('glwh-style', false);
  wp_enqueue_style('glwh-style');
  wp_add_inline_style('glwh-style', $css);

  $js = <<<'JS'
(()=>{
  const uniq = a => [...new Set(a)];
  const debounce = (fn, ms=200) => { let t; return (...args)=>{ clearTimeout(t); t=setTimeout(()=>fn(...args), ms); }; };

  // א"ב – בדיקות ותיקוני אותיות סופיות
  const FINAL2BASE = { "ך":"כ", "ם":"מ", "ן":"נ", "ף":"פ", "ץ":"צ" };
  const toBase = s => s.replace(/[ךםןףץ]/g, ch => FINAL2BASE[ch] || ch);
  const isHeb = ch => ch >= '\u05D0' && ch <= '\u05EA';

  // ניקוד עברי (טעמים וניקוד)
  const stripHebrewDiacritics = s => s.replace(/[\u0591-\u05BD\u05BF\u05C1-\u05C2\u05C4-\u05C5\u05C7]/g, "");

  // הסרת כל תווי ה־Format (Cf) אם הדפדפן תומך ב-Unicode Property Escapes
  const stripCf = s => {
    try { return s.replace(/\p{Cf}/gu, ""); }
    catch { return s.replace(/[\u200B-\u200F\u202A-\u202E\u2066-\u2069]/g, ""); }
  };

  // נרמול קשוח: שומר אך ורק על א–ת, רווח, ?
  function normForMatch(raw){
    let s = (raw||"").normalize('NFKC').toLowerCase();

    // ההמרות לפני סינון:
    s = s.replace(/\u061F/g,"?")               // סימן שאלה ערבי -> ?
         .replace(/\uFEFF/g,"");               // BOM
    s = toBase(s);
    s = stripHebrewDiacritics(s);
    s = stripCf(s)
          .replace(/['\u05F3\u05F4]/g,"")      // ' ׳ ״
          .replace(/\u00A0/g," ")              // NBSP
          .replace(/[\u0000-\u001F]/g,"");     // control

    // השארת תווים מותרים בלבד
    s = [...s].filter(ch => ch === ' ' || ch === '?' || isHeb(ch)).join('');
    return s.replace(/\s+/g," ").trim();
  }

  // התאמה ידנית תו-לתו – pattern & word כבר מנורמלים
  function matches(pattern, word){
    if (pattern.length !== word.length) return false;
    for (let i=0; i<pattern.length; i++){
      const p = pattern[i], w = word[i];
      if (p === '?'){ if (!isHeb(w)) return false; } else if (p !== w) { return false; }
    }
    return true;
  }

  // הדגשה ויזואלית של ה"חורים" – מציגים את המילה המקורית, אבל מקדמים את התבנית רק על אותיות
  function highlight(word, patternRaw){
    const pat = normForMatch(patternRaw);
    let iPat = 0, out = "";
    for (const ch of word){
      const p = pat[iPat] || "";
      if (p === '?'){
        out += `<span class="hole">${ch}</span>`;
        if (isHeb(ch)) iPat++;
      } else if (p === ' '){
        if (ch === ' ') iPat++;
        out += ch;
      } else {
        out += ch;
        if (isHeb(ch)) iPat++;
      }
    }
    return out;
  }

  // טעינת מאגר מילים
  async function loadWords(){
    let words = [];
    // קריאה של 22 הקבצים
    for (let i = 1; i <= 23; i++) {
        const u = location.origin + `/wp-content/uploads/output_${i}.json`;
        try {
            const r = await fetch(u, {cache:"no-store"});
            if (r.ok) {
                const d = await r.json();
                if (Array.isArray(d) && d.length) {
                    words = words.concat(d);
                }
            }
        } catch(e) {
            console.error(`Failed to load file ${u}:`, e);
        }
    }
    
    // Fallback אם שום קובץ לא נטען
    if (words.length === 0) {
        return [
          "שלום","עלמה","ספרים","מילים","אבן","אומה","אתר","הרים","הדג","יחס","ידית","ינשוף",
          "נורה","נתב","תיבה","תפוח","אוכל","אמת","הורה","היכל","הילה","יניב","יניקה","ניתוב","ניצב","נתיב","תיקון","תייר","תיבה",
          "בית ספר","מדינת ישראל","קו ירוק","ספר פתוח"
        ];
    }
    
    return uniq(words.map(String).map(s=>s.trim()).filter(Boolean));
  }
  
  function init(root){
    const input = root.querySelector("[data-pattern]");
    const btn   = root.querySelector("[data-search]");
    const cnt   = root.querySelector("[data-count]");
    const list  = root.querySelector("[data-results]");

    let wordsRaw=[], wordsNorm=[], ready=false;

    async function boot(){
      btn.disabled = true; btn.textContent = "טוען…";
      const raw = uniq((await loadWords()).map(String).map(s=>s.trim()).filter(Boolean));
      wordsRaw  = raw;
      wordsNorm = raw.map(normForMatch);
      ready = true; btn.disabled = false; btn.textContent = "חפש";

      const last = localStorage.getItem("glwh-last");
      if(last){ input.value = last; search(); }
    }

    function render(hits, patRaw){
      cnt.textContent = String(hits.length);
      const frag = document.createDocumentFragment();
      hits.forEach(w=>{
        const div = document.createElement("div");
        div.className="chip";
        div.innerHTML = highlight(w, patRaw);
        frag.appendChild(div);
      });
      list.replaceChildren(frag);
    }

    function search(){
      if(!ready) return;
      const patRaw = input.value || "";
      localStorage.setItem("glwh-last", patRaw);

      const pat = normForMatch(patRaw);
      list.innerHTML = "";
      if(!pat){ cnt.textContent = "—"; return; }

      const hits=[];
      for(let i=0;i<wordsNorm.length;i++){
        if(matches(pat, wordsNorm[i])) hits.push(wordsRaw[i]);
      }
      render(hits, patRaw);
    }

    const searchDebounced = debounce(search, 160);
    root.addEventListener("click", e=>{ if(e.target.closest("[data-search]")) search(); });
    input.addEventListener("keydown", e=>{ if(e.key==="Enter") search(); });
    input.addEventListener("input", searchDebounced);

    boot();
  }

  // אתחול פעם-אחת (גם בבוני דפים)
  function mountOnce(){
    const el = document.querySelector(".glwh[data-kind='wh']");
    if(el && !el.__glwh){ el.__glwh=1; init(el); return true; }
    return !!(el && el.__glwh);
  }
  function boot(){
    if(!mountOnce()){
      const mo=new MutationObserver(()=>{ if(mountOnce()) mo.disconnect(); });
      mo.observe(document.documentElement,{childList:true,subtree:true});
      window.addEventListener("load", ()=> mountOnce() || null, {once:true});
    }
  }
  if(document.readyState==="loading") document.addEventListener("DOMContentLoaded",boot); else boot();
})();
JS;

  wp_add_inline_script('glwh-runtime', $js);
});

add_shortcode('wordhole', function () {
  ob_start(); ?>
  <div class="glwh wrap" data-kind="wh">
    <div class="controls">
      <input type="text" data-pattern placeholder="הקלידו תבנית עם חורים ( ? )" inputmode="text" />
      <button data-search>חפש</button>
    </div>
    <div class="count">נמצאו <span data-count>—</span> תוצאות</div>
    <div class="results" data-results></div>
  </div>
  <?php return ob_get_clean();
});
