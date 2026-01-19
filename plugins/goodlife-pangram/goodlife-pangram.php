<?php
/**
 * Plugin Name: Goodlife Beehive (כוורת מילים)
 * Description: [beehive] – כוורת אותיות עם גרירה/קליקים/הקלדה, תבניות מילים בדמות כוכביות, מעבר שלב רק כשכל התבניות התמלאו, נרמול סופיות וחיווי פנגרמה. שליטה קשיחה באורכי המילים.
 * Version: 1.5.0
 * Author: Goodlife
 */

if (!defined('ABSPATH')) exit;

add_action('wp_enqueue_scripts', function () {
  wp_register_script('glbh-runtime','',[],false,true);
  wp_enqueue_script('glbh-runtime');

  $css = <<<'CSS'
.glbh *{box-sizing:border-box}
.glbh{direction:rtl;font-family:system-ui,-apple-system,"Segoe UI",Roboto,"Noto Sans Hebrew",Arial,sans-serif;color:#111}
.glbh .controls{display:flex;flex-wrap:wrap;gap:.5rem;align-items:center;margin:.5rem 0 1rem}
.glbh button,.glbh input[type="text"]{padding:.55rem .85rem;border:1px solid #bbb;background:#fff;border-radius:.6rem}
.glbh button{cursor:pointer}
.glbh .pill{border-radius:999px}
.glbh .muted{color:#666;font-size:.9rem}
.glbh .spacer{flex:1}
.glbh-wrap{max-width:920px;margin:0 auto}

.glbh-hive{width:min(80vmin,520px);margin:0 auto;position:relative}
.glbh-hex{width:70px;aspect-ratio:1/1;clip-path:polygon(25% 6%,75% 6%,100% 50%,75% 94%,25% 94%,0% 50%);background:#f2a23a;border:2px solid #b87416;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:26px;cursor:pointer;user-select:none;touch-action:none;color:#111}
.glbh-hex.center{background:#fff6cc;border-color:#f1c62f}
.glbh-hive-grid{display:grid;grid-template-columns:repeat(5,70px);gap:8px;justify-content:center;margin:.5rem auto 1rem}
.glbh-hive-row1{grid-column:2 / span 3;display:flex;gap:8px;justify-content:center}
.glbh-hive-row2{grid-column:1 / span 5;display:flex;gap:8px;justify-content:center}
.glbh-hive-row3{grid-column:2 / span 3;display:flex;gap:8px;justify-content:center}
.glbh-line{position:absolute;left:0;top:0;width:100%;height:100%;pointer-events:none}
.glbh-line polyline{fill:none;stroke:#1f2a7a;stroke-width:4;stroke-linecap:round;stroke-linejoin:round;opacity:.75}

.glbh-input{display:flex;align-items:center;gap:.5rem;margin:.5rem auto;max-width:520px}
.glbh-input input{flex:1;background:#fbfbfb}

.glbh-goal{background:#eef7ff;border:1px solid #cfe8ff;color:#0b3a7a;padding:.6rem .85rem;border-radius:.6rem;margin:.5rem 0}
.glbh-small{font-size:.9rem;color:#555}
.glbh-good{color:#0b7a27}
.glbh-bad{color:#8a0b0b}

.glbh-words{max-height:260px;overflow:auto;border:1px solid #eee;border-radius:.6rem;padding:.5rem .75rem;background:#fafafa}
.glbh-words ul{list-style:none;padding:0;margin:0;display:grid;grid-template-columns:repeat(auto-fill,minmax(120px,1fr));gap:.35rem .75rem}
.glbh-tag{display:inline-block;background:#eef2ff;color:#1f2a7a;padding:.25rem .5rem;border-radius:.5rem;font-weight:600}

.glbh-templates{margin:.6rem 0 0}
.glbh-templates .row{display:flex;flex-wrap:wrap;gap:.5rem}
.glbh-slot{display:inline-flex;align-items:center;gap:.45rem;border:1px dashed #bbb;border-radius:.5rem;padding:.25rem .6rem;background:#fff}
.glbh-slot.filled{border-style:solid;background:#e6ffea;border-color:#b6f2c0}
.glbh-slot .dots{letter-spacing:.2em;opacity:.8}
.glbh-slot .len{font-size:.85em;color:#666}

.glbh-toast{position:fixed;inset:auto 0 20px 0;display:flex;justify-content:center;pointer-events:none;z-index:999999}
.glbh-toast .card{pointer-events:auto;background:#111;color:#fff;padding:.75rem 1rem;border-radius:.75rem;box-shadow:0 8px 28px rgba(0,0,0,.25);font-weight:700}
CSS;

  wp_register_style('glbh-style', false);
  wp_enqueue_style('glbh-style');
  wp_add_inline_style('glbh-style', $css);

  $js = <<<'JS'
(()=>{
  // ===== עוזרים =====
  const rand=a=>a[Math.floor(Math.random()*a.length)];
  const shuf=a=>{for(let i=a.length-1;i>0;i--){const j=Math.floor(Math.random()*(i+1));[a[i],a[j]]=[a[j],a[i]]}return a};
  const uniq=a=>[...new Set(a)];

  // נרמול עברית: מסיר לא-עברית וממיר סופיות
  const FINAL={'\u05DA':'\u05DB','\u05DD':'\u05DE','\u05DF':'\u05E0','\u05E3':'\u05E4','\u05E5':'\u05E6'};
  const norm=s=>(s||"").toLowerCase().replace(/[^\u0590-\u05FF]/g,"").split("").map(c=>FINAL[c]||c).join("").trim();

  const FALLBACK = ["שלום","שלמה","שלומית","שלמות","למשול","משלים","משולם","משלום"].map(norm);

  async function loadWords(){
    let words = [];
    for(let i=1;i<=23;i++){
      const u = `${location.origin}/wp-content/uploads/output_${i}.json`;
      try{
        const r = await fetch(u, {cache:"no-store"});
        if(r.ok){
          const d = await r.json();
          if(Array.isArray(d)) words.push(...d);
        }
      }catch(e){}
    }
    if(!words.length) return FALLBACK;
    return uniq(words.map(w=>norm(String(w))).filter(Boolean));
  }

  // ===== שליטה קשיחה באורכי המילים =====
  // תקרה: עד שלב 20 – 5; אח"כ נפתח לאט (6,7…) ורק מאוחר מאוד.
  const lengthCap = lvl => (lvl<=20?5 : lvl<=35?6 : lvl<=60?7 : 8);

  // תבניות (רשימת אורכים) קשיחות לשלבים מוקדמים; משלב 6 – דפוס עדין אך בתוך התקרה
  function fixedLens(level){
    if(level===1) return [3,3];
    if(level===2) return [3,3,3];
    if(level===3) return [3,3,3];
    if(level===4) return [3,3,3,4];
    if(level===5) return [3,3,3,4,4];
    return null;
  }

  function dynamicLens(level){
    const cap = lengthCap(level);
    // מספר תבניות עולה לאט מאוד
    const count = Math.min(14, 5 + Math.floor((level-5)/2)); // מתחיל ב-5 בערך; מוסיף ~כל 2 רמות
    const lens=[];
    while(lens.length<count){
      // העדפה חזקה ל-3 ו-4, קצת 5, 6+ נדיר מאוד ורק אם cap מאפשר
      let pick = 3;
      const r = Math.random();
      if(r<0.55) pick=3;
      else if(r<0.9) pick=4;
      else pick=5;
      if(cap>=6 && level>35 && Math.random()<0.06) pick=6;
      if(cap>=7 && level>55 && Math.random()<0.02) pick=7;
      if(cap>=8 && level>80 && Math.random()<0.01) pick=8;
      lens.push(Math.min(pick, cap));
    }
    return lens;
  }

  // ===== משחק =====
  function initBeehive(root){
    const hive=root.querySelector(".glbh-hive");
    const input=root.querySelector(".glbh-input input");
    const wordsBox=root.querySelector(".glbh-words ul");
    const scoreEl=root.querySelector("[data-score]");
    const totalEl=root.querySelector("[data-total]");
    const levelEl=root.querySelector("[data-level]");
    const goalEl=root.querySelector("[data-goal]");
    const msg=root.querySelector("[data-msg]");
    const lock=root.querySelector("[data-lock]");
    const swipeToggle=root.querySelector("[data-swipe]");
    const tmplWrap=root.querySelector(".glbh-templates .row");

    let toast=document.querySelector(".glbh-toast");
    if(!toast){toast=document.createElement("div");toast.className="glbh-toast";document.body.appendChild(toast);}
    const showToast=(text,ms=1300)=>{toast.innerHTML=`<div class="card">${text}</div>`;setTimeout(()=>toast.innerHTML="",ms);};

    let letters=[], center="", allowed=new Set(), valid=[], found=new Set();
    let score=0;
    let level=parseInt(localStorage.getItem("bh_level")||"1",10);
    let total=parseInt(localStorage.getItem("bh_total")||"0",10);
    let slots=[]; // [{len, word:null|w}]
    let swiping=false, seenHex=[], poly, stageDone=false;

    function save(){ localStorage.setItem("bh_level",String(level)); localStorage.setItem("bh_total",String(total)); }

    // ציור הכוורת + קו הגרירה
    function drawHive(){
      hive.innerHTML = `
        <svg class="glbh-line" viewBox="0 0 100 100" preserveAspectRatio="none"><polyline points=""></polyline></svg>
        <div class="glbh-hive-grid">
          <div class="glbh-hive-row1">
            <div class="glbh-hex" data-ch="${letters[0]}">${letters[0]}</div>
            <div class="glbh-hex" data-ch="${letters[1]}">${letters[1]}</div>
          </div>
          <div class="glbh-hive-row2">
            <div class="glbh-hex" data-ch="${letters[2]}">${letters[2]}</div>
            <div class="glbh-hex center" data-ch="${center}">${center}</div>
            <div class="glbh-hex" data-ch="${letters[3]}">${letters[3]}</div>
          </div>
          <div class="glbh-hive-row3">
            <div class="glbh-hex" data-ch="${letters[4]}">${letters[4]}</div>
            <div class="glbh-hex" data-ch="${letters[5]}">${letters[5]}</div>
          </div>
        </div>
      `;
      poly = hive.querySelector(".glbh-line polyline");

      const addCh = (ch)=>{ if(lock.checked) return; input.value += (ch||""); };

      // קליקים
      hive.querySelectorAll(".glbh-hex").forEach(hex=>{
        hex.addEventListener("click", ()=> addCh(hex.dataset.ch||""));
      });

      // גרירה עם קו
      const hiveRect = ()=> hive.getBoundingClientRect();
      const hexCenter = (hex)=>{
        const r = hex.getBoundingClientRect(), hs = hiveRect();
        const x = ((r.left + r.width/2) - hs.left) / hs.width  * 100;
        const y = ((r.top  + r.height/2) - hs.top ) / hs.height * 100;
        return [x,y];
      };
      const pushHex = (hex)=>{
        if(!hex) return;
        const tag = hex.dataset.ch + "@" + hex.offsetLeft + "x" + hex.offsetTop;
        if(seenHex.find(h=>h.tag===tag)) return; // לא לחזור על אותו משושה
        seenHex.push({tag,hex});
        addCh(hex.dataset.ch||"");
        const pts = seenHex.map(h=>hexCenter(h.hex)).map(([x,y])=>`${x},${y}`).join(" ");
        poly.setAttribute("points", pts);
      };
      const startSwipe = (clientX,clientY)=>{
        if(!swipeToggle.checked) return;
        swiping=true; seenHex=[]; poly.setAttribute("points",""); input.value=""; stepSwipe(clientX,clientY);
      };
      const stepSwipe  = (clientX,clientY)=>{
        if(!swiping) return;
        const el = document.elementFromPoint(clientX, clientY);
        const hex = el && el.closest && el.closest(".glbh-hex");
        pushHex(hex);
      };
      const endSwipe   = ()=>{ swiping=false; };

      hive.addEventListener("pointerdown", e=>{ startSwipe(e.clientX,e.clientY); });
      hive.addEventListener("pointermove", e=>{ stepSwipe(e.clientX,e.clientY); });
      document.addEventListener("pointerup", ()=> endSwipe());

      hive.addEventListener("touchstart", e=>{ const t=e.touches[0]; if(t) startSwipe(t.clientX,t.clientY); }, {passive:true});
      hive.addEventListener("touchmove",  e=>{ const t=e.touches[0]; if(t) stepSwipe(t.clientX,t.clientY); }, {passive:true});
      hive.addEventListener("touchend",   ()=> endSwipe());
    }

    const isPangram = (w)=>{ const u=new Set(w.split("")); return letters.every(l=>u.has(l)) && u.has(center); };
    const scoreOf   = (w)=> Math.max(1, w.length) + (isPangram(w)?7:0);

    function renderFound(){
      wordsBox.innerHTML = "";
      [...found].sort((a,b)=> a.length===b.length ? a.localeCompare(b) : a.length-b.length)
        .forEach(w=>{
          const li=document.createElement("li");
          li.innerHTML=`<span class="glbh-tag">${w}</span>`;
          wordsBox.appendChild(li);
        });
    }

    function renderTemplates(){
      tmplWrap.innerHTML = "";
      slots.forEach((s,idx)=>{
        const el = document.createElement("div");
        el.className = "glbh-slot"+(s.word?" filled":"");
        el.dataset.index = String(idx);
        el.innerHTML = s.word
          ? `<span class="word">${s.word}</span>`
          : `<span class="dots">${"•".repeat(s.len)}</span><span class="len">(${s.len})</span>`;
        tmplWrap.appendChild(el);
      });
    }

    function setGoalText(){
      const lensTxt = slots.map(s=>s.len).join(', ');
      goalEl.textContent = `יעד לשלב ${level}: מצאו ${slots.length} מילים (${lensTxt} אותיות)`;
    }

    // מילוי תבנית לפי אורך – כל תבנית נספרת בנפרד (לא "אורך פעם אחת")
    function fillMatchingSlot(word){
      const i = slots.findIndex(s=>!s.word && s.len===word.length);
      if(i>=0){ slots[i].word = word; renderTemplates(); }
    }

    // מעבר לשלב הבא רק כשהכל התמלא
    function maybeAdvance(){
      const done = slots.filter(s=>!!s.word).length;
      if(!stageDone && done>=slots.length){
        stageDone = true;
        total += score; save();
        totalEl.textContent = String(total);
        msg.className="glbh-good";
        msg.textContent=`שלב ${level} הושלם! +${score} נק׳ (מצטבר: ${total})`;
        showToast(`🎉 שלב ${level} הושלם! עוברים לשלב ${level+1}…`, 1000);
        setTimeout(()=>{ level += 1; save(); rebuild(true); }, 1000);
      }
    }

    function submitWord(){
      const raw = input.value;
      const w = norm(raw);
      input.value=""; msg.textContent="";
      if(!w || w.length<3){ msg.className="glbh-bad"; msg.textContent="מינימום 3 אותיות"; return; }
      if(!w.includes(center)){ msg.className="glbh-bad"; msg.textContent="חובה לכלול את האות המרכזית"; return; }
      if(!w.split("").every(ch=>allowed.has(ch))){ msg.className="glbh-bad"; msg.textContent="מותר להשתמש רק באותיות הנתונות"; return; }
      if(!valid.includes(w)){ msg.className="glbh-bad"; msg.textContent="לא ברשימת המילים"; return; }
      if(found.has(w)){ msg.className="glbh-bad"; msg.textContent="כבר מצאתם את המילה הזו"; return; }

      found.add(w); renderFound();
      fillMatchingSlot(w);

      const add=scoreOf(w);
      score += add; scoreEl.textContent = String(score);

      if(isPangram(w)){ showToast("🎉 מצאת פנגרמה!"); msg.className="glbh-good"; msg.textContent=`🎉 פנגרמה! +${add} נק׳`; }
      else{ msg.className="glbh-good"; msg.textContent=`+${add} נק׳`; }

      maybeAdvance();
    }

    // בחירת 7 אותיות ולסנן מילים בהתאם + תקרת אורך
    function randomLetters(words){
      const pool = uniq(words.join("").split("").filter(ch=>/[\u0590-\u05FF]/.test(ch)));
      for(let t=0; t<160; t++){
        const c = rand(pool);
        const others = uniq(shuf(pool.filter(l=>l!==c))).slice(0,6);
        if(others.length<6) continue;

        const L = uniq([c, ...others]);
        const set = new Set(L);

        const cand = words.filter(w=>{
          if(w.length<3) return false;
          if(!w.includes(c)) return false;
          for(const ch of w) if(!set.has(ch)) return false;
          return true;
        });

        if(cand.length >= 10 || (t>60 && cand.length>=8) || (t>110 && cand.length>=6)){
          const hasPan = cand.some(w=>{ const u=new Set(w.split("")); return L.every(ch=>u.has(ch)); });
          if(!hasPan && t<120) continue;
          return {letters:L, center:c, list: uniq(cand)};
        }
      }
      return null;
    }

    // בונה את תבניות היעד לרמה הנוכחית (קשיח בשלבים ראשונים)
    function targetLensFor(level){
      const fixed = fixedLens(level);
      if(fixed) return fixed;
      return dynamicLens(level);
    }

    async function rebuild(keepTotals=false){
      found.clear(); score=0; stageDone=false; msg.textContent=""; input.value="";

      const dictAll = await loadWords();

      const cap = lengthCap(level);
      const wanted = targetLensFor(level).map(L=>Math.min(L,cap)); // לא חורגים מהתקרה
      let ok=false, attempt, L, c, list;

      for(let tries=0; tries<45 && !ok; tries++){
        attempt = randomLetters(dictAll);
        if(!attempt) break;

        // הכנה
        L = attempt.letters.map(norm);
        c = norm(attempt.center);
        const allowedSet = new Set(L);

        // לקסיקון חוקי לסט + תקרה
        list = uniq(attempt.list.map(norm).filter(w=>{
          const len=w.length;
          if(len<3 || len>cap) return false;
          if(!w.includes(c)) return false;
          for(const ch of w) if(!allowedSet.has(ch)) return false;
          return true;
        })).sort();

        // בדיקת זמינות מדויקת לפי תבניות (כמות לכל אורך)
        const byLen={}; list.forEach(w=>byLen[w.length]=(byLen[w.length]||0)+1);
        const needCount={}; wanted.forEach(Ln=>needCount[Ln]=(needCount[Ln]||0)+1);
        ok = Object.keys(needCount).every(k => (byLen[+k]||0) >= needCount[k]);

        if(ok){
          letters = L.filter(x=>x!==c); shuf(letters);
          center = c; allowed = new Set([center, ...letters]);
          valid = list;
          break;
        }
      }

      // אם לא מצאנו סט תומך – לא מקשיחים כלפי מעלה; מרככים כלפי מטה (ממירים לאורכים 3/4)
      if(!ok){
        attempt = attempt || randomLetters(dictAll) || {letters:["ש","ל","ו","מ","כ","ת","ר"], center:"מ", list:dictAll};
        L = attempt.letters.map(norm);
        c = norm(attempt.center);
        const allowedSet = new Set(L);
        list = uniq(attempt.list.map(norm).filter(w=>{
          const len=w.length;
          if(len<3 || len>cap) return false;
          if(!w.includes(c)) return false;
          for(const ch of w) if(!allowedSet.has(ch)) return false;
          return true;
        })).sort();
        letters = L.filter(x=>x!==c); shuf(letters);
        center = c; allowed = new Set([center, ...letters]);
        valid = list;
      }

      // בונים Slots בפועל – כל תבנית מיוצגת בנפרד
      slots = wanted.map(Ln=>({len:Ln, word:null}));

      drawHive();
      scoreEl.textContent = "0";
      totalEl.textContent = String(total);
      levelEl.textContent = String(level);

      renderTemplates();
      setGoalText();
      renderFound();
    }

    // אירועים
    root.addEventListener("click", e=>{
      if(e.target.closest("[data-shuffle]")){ shuf(letters); drawHive(); }
      if(e.target.closest("[data-back]")){ input.value = input.value.slice(0,-1); }
      if(e.target.closest("[data-clear]")){ input.value=""; msg.textContent=""; }
      if(e.target.closest("[data-submit]")){ submitWord(); }
      if(e.target.closest("[data-new]")){ showToast("לוח חדש נטען"); rebuild(true); }
    });
    input.addEventListener("keydown", e=>{ if(e.key==="Enter") submitWord(); });
    input.readOnly=false;

    rebuild(true);
  }

  function boot(){
    const mount=()=>document.querySelectorAll(".glbh[data-kind='bh']").forEach(el=>{ if(!el.__glbh){ el.__glbh=1; initBeehive(el); }});
    mount();
    const mo=new MutationObserver(ms=>{ for(const m of ms){ m.addedNodes.forEach(n=>{ if(!(n instanceof Element)) return;
      if(n.matches?.(".glbh[data-kind='bh']") && !n.__glbh){ n.__glbh=1; initBeehive(n); }
      n.querySelectorAll?.(".glbh[data-kind='bh']").forEach(el=>{ if(!el.__glbh){ el.__glbh=1; initBeehive(el); }});
    }); }});
    mo.observe(document.documentElement,{childList:true,subtree:true});
  }
  if(document.readyState==="loading") document.addEventListener("DOMContentLoaded",boot); else boot();

})();
JS;

  wp_add_inline_script('glbh-runtime', $js);
});

// ===== Shortcode =====
add_shortcode('beehive', function () {
  ob_start(); ?>
  <div class="glbh glbh-wrap" data-kind="bh">
    <div class="controls">
      <button class="pill" data-new>לוח חדש</button>
      <button data-shuffle>ערבב אותיות</button>
      <label class="muted"><input type="checkbox" data-lock> נעילת הקלדה</label>
      <label class="muted"><input type="checkbox" data-swipe checked> גרירה לבחירה (עם קו)</label>
      <span class="spacer"></span>
      <span class="muted">
        שלב: <b data-level>1</b> |
        נק׳ שלב: <b data-score>0</b> |
        נק׳ מצטבר: <b data-total>0</b>
      </span>
    </div>

    <div class="glbh-hive"></div>

    <div class="glbh-input">
      <input type="text" placeholder="הרכיבו מילה (האות המרכזית חובה) – גרירה/לחיצה/הקלדה" />
      <div style="display:flex;gap:.5rem;">
        <button data-back title="מחק תו אחרון">⌫</button>
        <button data-clear>נקה</button>
        <button data-submit>אשר</button>
      </div>
    </div>

    <div class="glbh-goal"><span data-goal>יעד נטען…</span></div>
    <div class="glbh-small" data-msg></div>

    <div class="glbh-templates">
      <div class="muted">תבניות המילים לשלב זה (מתמלאות כשאתם מוצאים):</div>
      <div class="row"></div>
    </div>

    <div class="glbh-words" style="margin-top:.6rem">
      <div class="muted">מילים שמצאתם:</div>
      <ul></ul>
    </div>

    <div class="muted" style="margin-top:.6rem">
      חוקים: מינימום 3 אותיות, חובה לכלול את האות המרכזית, מותר להשתמש רק באותיות הנתונות.
      האותיות ך,ם,ן,ף,ץ מנורמלות לאותיות כ,מ,נ,פ,צ.
    </div>
  </div>
  <?php return ob_get_clean();
});

