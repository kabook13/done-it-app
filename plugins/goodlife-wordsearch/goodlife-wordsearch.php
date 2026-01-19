<?php
/**
 * Plugin Name: Goodlife Wordsearch
 * Description: [wordsearch] – תפזורת בקו ישר בלבד, בלי אותיות סופיות על הלוח, ריווח בין מילים, בחירת מס׳ מילים. תומך אלמנטור והדפסה לעמוד אחד.
 * Version: 1.1.8
 * Author: Goodlife
 */

if (!defined('ABSPATH')) exit;

add_action('wp_enqueue_scripts', function () {
  wp_register_script('glws-runtime','',[],false,true);
  wp_enqueue_script('glws-runtime');

  $css = <<<'CSS'
.glws *{box-sizing:border-box}
.glws{direction:rtl;font-family:system-ui,-apple-system,"Segoe UI",Roboto,"Noto Sans Hebrew",Arial,sans-serif;color:#111}
.glws .controls{display:flex;flex-wrap:wrap;gap:.5rem;align-items:center;margin:.5rem 0 1rem}
.glws .controls .group{display:flex;gap:.5rem;align-items:center;flex-wrap:wrap}
.glws .controls label{display:flex;align-items:center;gap:.35rem}
.glws button,.glws select{padding:.55rem .85rem;border:1px solid #bbb;background:#fff;border-radius:.6rem;cursor:pointer}
.glws button:hover{background:#f6f6f6}
.glws .pill{border-radius:999px}
.glws .muted{color:#666;font-size:.9rem}
.glws .spacer{flex:1}
.glws .banner{display:none;margin:.5rem 0;padding:.6rem .8rem;background:#fff7d6;border:1px solid #ffe08a;color:#6b5200;border-radius:.6rem;font-weight:600}
.glws .banner.show{display:block}
.glws-wrap{max-width:1100px;margin:0 auto}
.glws-main{display:grid;grid-template-columns:280px 1fr;gap:22px;align-items:start}
.glws-grid-wrap{width:min(92vmin,720px)}
.glws-grid{--gap:4px;--size:12;display:grid;grid-template-columns:repeat(var(--size),1fr);gap:var(--gap);background:#eee;padding:var(--gap);border-radius:.75rem;user-select:none;touch-action:none}
.glws-cell{aspect-ratio:1/1;background:#fff;border-radius:.35rem;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:calc(20px + (720px / var(--size)) * .05);line-height:1}
.glws-cell.sel{outline:2px solid #3b82f6}
.glws-cell.found{background:#d1fae5;color:#065f46}
.glws-wordlist{list-style:none;padding:0;margin:.5rem 0 0}
.glws-wordlist li{margin:.25rem 0}
.glws-wordlist li.done{text-decoration:line-through;color:#777}
@media (max-width:900px){.glws-main{grid-template-columns:1fr;}}

/* --- הדפסה לעמוד אחד + רשימת מילים בשורה עם פסיקים --- */
@media print{
  .glws .controls{display:none!important}
  .glws .banner{display:none!important}
  .glws-main{grid-template-columns:1fr;gap:10px}
  .glws-grid-wrap{width:100%}
  .glws-grid{--gap:2px;padding:var(--gap);border-radius:0}
  .glws-cell{font-size:18px;border-radius:0}
  /* רשימת מילים בשורה אחת עם פסיקים */
  .glws-wordlist{display:flex;flex-wrap:wrap;gap:.15rem .35rem;margin-top:.35rem}
  .glws-wordlist li{display:inline;margin:0}
  .glws-wordlist li::after{content:", ";}
  .glws-wordlist li:last-child::after{content:"";}
  /* הימנעו משבירת עמוד בין הלוח לרשימה */
  .glws, .glws-grid-wrap, .glws-wordlist{page-break-inside:avoid}
}
CSS;

  wp_register_style('glws-style', false);
  wp_enqueue_style('glws-style');
  wp_add_inline_style('glws-style', $css);

  $js = <<<'JS'
(()=>{
  const rand = a => a[Math.floor(Math.random()*a.length)];
  const shuf = a => { for(let i=a.length-1;i>0;i--){ const j=Math.floor(Math.random()*(i+1)); [a[i],a[j]]=[a[j],a[i]] } return a; };
  const uniq = a => [...new Set(a)];
  const strip = s => (s||"").toLowerCase().replace(/[^\u0590-\u05FF]/g,"").trim();
  const FINAL2BASE = { "ך":"כ", "ם":"מ", "ן":"נ", "ף":"פ", "ץ":"צ" };
  const toBase = s => s.replace(/[ךםןףץ]/g, ch => FINAL2BASE[ch] || ch);
  const HEB_BASE = "אבגדהוזחטיכלמנסעפצקרשת".split("");
  const FALLBACK = ["שלום","עולם","ספר","בית","כיתה","לימוד","משחק","מילה","כדור","חבר","חברה","ישראל","ירושלים","חלון","שולחן","ירוק","כחול","אדום","צהוב","שחור","לבן"].map(strip);

  async function loadWords(){
    let words = [];
    // טעינה של 22 הקבצים
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
        return FALLBACK;
    }
    
    // נקודת התיקון: ניקוי כל המילים מרווחים ותווים שאינם עברית
    return uniq(words.map(strip).filter(Boolean));
  }

  function initWordsearch(root){
    const grid = root.querySelector(".glws-grid");
    const list = root.querySelector(".glws-wordlist");
    const banner = root.querySelector(".banner");
    const sizeSel = root.querySelector("[data-size]");
    const allowH = root.querySelector("[data-dir-h]");
    const allowV = root.querySelector("[data-dir-v]");
    const allowD = root.querySelector("[data-dir-d]");
    const countSel= root.querySelector("[data-count]");

    let placed=[], SIZE=parseInt(sizeSel.value,10)||12, letters=[], COUNT=parseInt(countSel.value,10)||12;

    const inB=(r,c)=> r>=0 && c>=0 && r<SIZE && c<SIZE;

    function empty(){
      grid.style.setProperty("--size", SIZE);
      grid.innerHTML=""; letters = Array.from({length:SIZE},()=>Array.from({length:SIZE},()=> ""));
      for(let i=0;i<SIZE*SIZE;i++){
        const cell=document.createElement("div");
        cell.className="glws-cell"; cell.dataset.index=i;
        grid.appendChild(cell);
      }
    }

    function isClearFor(word,r,c,dr,dc){
      const L = word.length;
      const endR=r+dr*(L-1), endC=c+dc*(L-1);
      if(!inB(endR,endC)) return false;
      for(let i=0;i<L;i++){
        const rr=r+dr*i, cc=c+dc*i;
        if(letters[rr][cc]) return false;
        for(let ar=-1; ar<=1; ar++){
          for(let ac=-1; ac<=1; ac++){
            if(ar===0 && ac===0) continue;
            const nr=rr+ar, nc=cc+ac;
            if(!inB(nr,nc)) continue;
            const onPath = [...Array(L)].some((_,k)=> (r+dr*k)===nr && (c+dc*k)===nc);
            if(!onPath && letters[nr][nc]) return false;
          }
        }
      }
      return true;
    }

    function placeWord(orig){
      // נקודת התיקון: ניקוי המילה מרווחים לפני הפריסה על הלוח
      const word = toBase(strip(orig));
      const dirs=[];
      if(allowH.checked) dirs.push([0,-1],[0,1]);
      if(allowV.checked) dirs.push([1,0],[-1,0]);
      if(allowD.checked) dirs.push([1,1],[1,-1],[-1,1],[-1,-1]);
      shuf(dirs);
      for(const [dr,dc] of dirs){
        for(let t=0;t<160;t++){
          const r=Math.floor(Math.random()*SIZE), c=Math.floor(Math.random()*SIZE);
          if(isClearFor(word,r,c,dr,dc)){
            const cells=[];
            for(let i=0;i<word.length;i++){
              const rr=r+dr*i, cc=c+dc*i;
              letters[rr][cc]=word[i]; cells.push({r:rr,c:cc});
            }
            placed.push({wordBase:word, wordShown:orig, cells, found:false}); 
            return true;
          }
        }
      }
      return false;
    }

    function fillRandom(){
      for(let r=0;r<SIZE;r++) for(let c=0;c<SIZE;c++) if(!letters[r][c]) letters[r][c]=rand(HEB_BASE);
      grid.querySelectorAll(".glws-cell").forEach((cell,i)=>{
        const r=Math.floor(i/SIZE), c=i%SIZE;
        cell.textContent=letters[r][c]; cell.classList.remove("found","sel");
      });
    }

    function renderList(){
      list.innerHTML="";
      placed.forEach(p=>{
        const li=document.createElement("li");
        li.textContent=p.wordShown; p._li=li;
        list.appendChild(li);
      });
    }

    async function rebuild(){
      banner.classList.remove("show"); placed=[]; empty();

      const wordsAll = uniq((await loadWords())
        .filter(w => w.length>=3 && w.length<=Math.max(6, Math.min(10, SIZE-2))));
      const pool = shuf(wordsAll.slice());
      const timeLimit = 400;
      const t0 = performance.now();

      let i=0;
      while(placed.length < COUNT && i < pool.length){
        if(performance.now() - t0 > timeLimit) break;
        const w = pool[i++];
        if(placed.some(p=>p.wordShown===w)) continue;
        placeWord(w);
      }

      fillRandom(); renderList();

      if (placed.length < COUNT) {
        banner.textContent = `הוכנסה כמות ${placed.length} של מילים, אין מקום בתפזורת ליותר`;
        banner.classList.add('show');
      }
    }

    // בחירה בקו ישר
    let selecting=false, startIndex=null;
    const i2rc = i=>({r:Math.floor(i/SIZE), c:i%SIZE});
    const rc2i = (r,c)=> r*SIZE + c;
    const dirs = [[0,1],[0,-1],[1,0],[-1,0],[1,1],[1,-1],[-1,1],[-1,-1]];

    function idxFromClient(x,y){
      const el = document.elementFromPoint(x,y);
      const cell = el && el.closest ? el.closest(".glws-cell") : null;
      return cell ? parseInt(cell.dataset.index,10) : null;
    }

    function snapDir(sr,sc,er,ec){
      const dr=er-sr, dc=ec-sc; if(dr===0&&dc===0) return null;
      const len=Math.hypot(dr,dc), vr=dr/len, vc=dc/len;
      let best=null, bestDot=-1e9;
      for(const [r,c] of dirs){ const dot = vr*r+vc*c; if(dot>bestDot){bestDot=dot; best=[r,c];} }
      return best;
    }

    function lineLocked(aIdx,bIdx){
      const a=i2rc(aIdx), b=i2rc(bIdx);
      const d=snapDir(a.r,a.c,b.r,b.c); if(!d) return [];
      const [dr,dc]=d; const steps=Math.max(Math.abs(b.r-a.r),Math.abs(b.c-a.c));
      const out=[]; let r=a.r,c=a.c;
      for(let k=0;k<=steps;k++){ if(r<0||c<0||r>=SIZE||c>=SIZE) break; out.push(rc2i(r,c)); r+=dr; c+=dc; }
      return out;
    }

    function clearSel(){ grid.querySelectorAll(".glws-cell.sel").forEach(el=>el.classList.remove("sel")); }

    function startAt(x,y){
      const i = idxFromClient(x,y); if(i==null) return;
      selecting=true; startIndex=i; clearSel();
      grid.querySelector(`[data-index="${i}"]`)?.classList.add("sel");
    }
    function moveAt(x,y){
      if(!selecting) return; const i = idxFromClient(x,y); if(i==null) return;
      clearSel(); lineLocked(startIndex,i).forEach(j=>grid.querySelector(`[data-index="${j}"]`)?.classList.add("sel"));
    }
    function endAt(x,y){
      if(!selecting) return; selecting=false; const i = idxFromClient(x,y); if(i==null){clearSel();return;}
      const ln=lineLocked(startIndex,i); if(!ln.length){clearSel();return;}
      const sBase=ln.map(j=>{const r=Math.floor(j/SIZE),c=j%SIZE; return letters[r][c];}).join("");
      const hit=placed.find(p=>!p.found&&(p.wordBase===sBase || p.wordBase===sBase.split("").reverse().join("")));
      if(hit){ hit.found=true; hit.cells.forEach(({r,c})=>grid.querySelector(`[data-index="${rc2i(r,c)}"]`)?.classList.add("found")); hit._li?.classList.add("done");
        if(placed.every(x=>x.found)){ banner.textContent="כל הכבוד! מצאתם את כל המילים"; banner.classList.add("show"); }
      }else{ clearSel(); }
    }

    grid.addEventListener("pointerdown", e=>{grid.setPointerCapture?.(e.pointerId); startAt(e.clientX,e.clientY);});
    grid.addEventListener("pointermove", e=> moveAt(e.clientX,e.clientY));
    grid.addEventListener("pointerup",   e=> endAt  (e.clientX,e.clientY));
    grid.addEventListener("mousedown",  e=> startAt(e.clientX,e.clientY));
    grid.addEventListener("mousemove",  e=> moveAt (e.clientX,e.clientY));
    document.addEventListener("mouseup",e=> endAt   (e.clientX,e.clientY));
    grid.addEventListener("touchstart", e=>{ const t=e.touches[0]; if(t) startAt(t.clientX,t.clientY); }, {passive:true});
    grid.addEventListener("touchmove",  e=>{ const t=e.touches[0]; if(t) moveAt (t.clientX,t.clientY); }, {passive:true});
    grid.addEventListener("touchend",   e=>{ const t=e.changedTouches[0]; if(t) endAt(t.clientX,e.clientY); }, {passive:true});

    root.addEventListener("click", e=>{
      if(e.target.closest("[data-new]")) rebuild();
      if(e.target.closest("[data-show]")){ placed.forEach(p=>{ p.cells.forEach(({r,c})=>grid.querySelector(`[data-index="${r*SIZE+c}"]`)?.classList.add("found")); p._li?.classList.add("done"); p.found=true; }); banner.textContent="הפתרון מוצג"; banner.classList.add("show"); }
      if(e.target.closest("[data-hide]")){ grid.querySelectorAll(".glws-cell.found").forEach(el=>el.classList.remove("found")); placed.forEach(p=>{p.found=false; p._li?.classList.remove("done");}); banner.classList.remove("show"); }
      if(e.target.closest("[data-print]")) window.print();
    });
    sizeSel.addEventListener("change", ()=>{ SIZE=parseInt(sizeSel.value,10)||12; rebuild(); });
    countSel.addEventListener("change", ()=>{ COUNT=parseInt(countSel.value,10)||12; rebuild(); });

    rebuild();
  }

  function boot(){
    const mount = () => {
      const el = document.querySelector(".glws[data-kind='ws']");
      if (el && !el.__glws) { el.__glws = 1; initWordsearch(el); return true; }
      return !!(el && el.__glws);
    };
    if (!mount()) {
      const mo = new MutationObserver(() => { if (mount()) mo.disconnect(); });
      mo.observe(document.documentElement, { childList: true, subtree: true });
      window.addEventListener("load", () => mount() || null, { once: true });
    }
  }
  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", boot);
  } else {
    boot();
  }
})();
JS;

  wp_add_inline_script('glws-runtime', $js);
});

add_shortcode('wordsearch', function () {
  ob_start(); ?>
  <div class="glws glws-wrap" data-kind="ws">
    <div class="controls">
      <div class="group">
        <button class="pill" data-new>לוח חדש</button>
        <button data-show>הצג פתרון</button>
        <button data-hide>הסתר פתרון</button>
        <button data-print>הדפס</button>
      </div>
      <span class="spacer"></span>
      <div class="group">
        <label>גודל לוח
          <select data-size>
            <option value="12">12×12</option>
            <option value="14">14×14</option>
            <option value="16">16×16</option>
          </select>
        </label>
        <label>מס' מילים
          <select data-count>
            <option>8</option><option selected>12</option><option>14</option><option>16</option>
            <option>18</option><option>20</option><option>24</option><option>28</option><option>30</option>
          </select>
        </label>
        <label><input type="checkbox" data-dir-h checked> אופקי</label>
        <label><input type="checkbox" data-dir-v checked> אנכי</label>
        <label><input type="checkbox" data-dir-d checked> אלכסון</label>
      </div>
    </div>

    <div class="glws-main">
      <div>
        <div class="banner"></div>
        <div class="muted">רשימת מילים לחיפוש:</div>
        <ul class="glws-wordlist"></ul>
        <div class="muted" style="margin-top:.75rem">טיפ: גרירה/משיכה בקו ישר כדי לסמן מילה.</div>
      </div>
      <div class="glws-grid-wrap"><div class="glws-grid"></div></div>
    </div>
  </div>
  <?php return ob_get_clean();
});
