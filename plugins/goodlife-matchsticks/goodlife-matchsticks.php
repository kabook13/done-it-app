<?php
/**
 * Plugin Name: Goodlife Matchsticks (Interactive)
 * Description: [matchsticks mode="daily|random" maxmoves="2"] – גפרורים אינטראקטיביים (7־סגמנטים), מחולל חידות בצד הלקוח, רמז/פתרון/הדגמה, ניקוד ושמירה מקומית.
 * Version: 1.0.1
 * Author: Goodlife
 */

if (!defined('ABSPATH')) exit;

/* ---------- Assets (CSS + JS inline, ללא AJAX) ---------- */
add_action('wp_enqueue_scripts', function () {
  // CSS
  $css = <<<'CSS'
.glms{direction:rtl;font-family:system-ui,-apple-system,"Segoe UI",Roboto,"Noto Sans Hebrew",Arial,sans-serif;max-width:760px;margin:0 auto;padding:16px;border:1px solid #eaeaea;background:#fff;border-radius:14px}
.glms-head{display:flex;justify-content:space-between;align-items:center;gap:8px;flex-wrap:wrap}
.glms-title{font-size:18px;font-weight:800;margin:0}
.glms-hud{font-size:14px;color:#555;display:flex;gap:12px;align-items:center}
.glms-actions{display:flex;gap:8px;flex-wrap:wrap;margin:8px 0}
.glms-btn{padding:10px 14px;border-radius:10px;border:1px solid #e6e6e6;background:#f7f7f7;cursor:pointer;font-weight:700}
.glms-btn:disabled{opacity:.45;cursor:not-allowed}
.glms-msg{min-height:24px;text-align:center;font-weight:700;margin-top:4px;color:#2e7d32} /* כבר לא נציג "טיפ" ברירת־מחדל */
.glms-svg{width:100%;max-width:760px;border:1px solid #eee;border-radius:12px;background:#fff;margin:8px auto;touch-action:manipulation}
.seg{cursor:pointer;transition:opacity .15s, fill .15s; fill:#111; stroke:transparent; stroke-width:18; stroke-linejoin:round} /* שטח קליק רחב */
.seg.on{fill:#111; opacity:1}
.seg.off{fill:#dadada; opacity:.12; filter:saturate(0.2)}
.seg.demo{outline:2px dashed #2e7d32; outline-offset:2px}
.glms-eqtext{direction:ltr;unicode-bidi:bidi-override;text-align:center;font-variant-numeric:tabular-nums;font-weight:800;font-size:28px;margin:6px 0 0}
.glms-note{font-size:13px;color:#777;text-align:center}
.glms-solution{display:none;background:#fafafa;border:1px solid #eee;border-radius:10px;padding:10px;margin-top:8px}
.glms-solution.show{display:block}
.glms-solution h4{margin:0 0 6px 0;font-size:16px}
.glms-celebrate{display:none;position:fixed;inset:0;background:rgba(0,0,0,.25);z-index:9999;align-items:center;justify-content:center;padding:2rem}
.glms-celebrate.show{display:flex}
.glms-celebrate .box{background:#fff;border-radius:14px;padding:22px 28px;box-shadow:0 10px 30px rgba(0,0,0,.25);text-align:center}
.glms-celebrate .title{font-size:26px;font-weight:800;color:#2e7d32;margin-bottom:8px}

/* מובייל – הגדלה משמעותית + ריווח נוח */
@media(max-width:520px){
  .glms{padding:12px}
  .glms-btn{padding:9px 12px}
  .glms-eqtext{font-size:24px}
  .glms-svg{max-width:100%}
  .seg{stroke-width:24} /* עוד הרחבת אזור קליק במסכים צרים */
}
CSS;
  wp_register_style('glms-style', false, [], null);
  wp_enqueue_style('glms-style');
  wp_add_inline_style('glms-style', $css);

  // JS – מחולל + אינטראקציה (בטוח וקל)
  $js = <<<'JS'
(()=>{
  const IDX = {a:0,b:1,c:2,d:3,e:4,f:5,g:6};
  const KEYS = ["a","b","c","d","e","f","g"];
  const DIGITS = {
    "0":"abcefg","1":"bc","2":"abdeg","3":"abcdg","4":"bcdf",
    "5":"acdfg","6":"acdefg","7":"abc","8":"abcdefg","9":"abcdfg"
  };
  const DM = {}; for(const d in DIGITS){ DM[d] = toMask(DIGITS[d]); }
  function toMask(s){ let m=0; for(const k of KEYS){ if(s.includes(k)) m|=(1<<IDX[k]); } return m; }
  function maskToDigit(mask){ for(const d in DM){ if(DM[d]===mask) return parseInt(d,10); } return null; }

  // מצייר ספרה אחת (7 סגמנטים)
  function digitGroup(x,y,scale,mask,dIndex){
    const pts = {
      a:`10,10 50,10 45,15 15,15`,
      d:`10,50 50,50 45,55 15,55`,
      g:`10,90 50,90 45,95 15,95`,
      b:`50,10 55,15 55,45 50,50 45,45 45,15`,
      c:`50,50 55,55 55,85 50,90 45,85 45,55`,
      f:`10,10 15,15 15,45 10,50 5,45 5,15`,
      e:`10,50 15,55 15,85 10,90 5,85 5,55`
    };
    let segs='';
    for(const k of KEYS){
      const on=(mask & (1<<IDX[k]))!==0;
      segs += `<polygon class="seg ${on?'on':'off'}" data-d="${dIndex}" data-seg="${k}" points="${pts[k]}" transform="translate(${x},${y}) scale(${scale})" />`;
    }
    return segs;
  }

  // רנדר משוואה – סקייל דינמי לפי רוחב הקונטיינר (מובייל גדול יותר)
  function renderEq(root, A, op, B, C){
    const box = root.querySelector('.glms-svg');
    const boxW = Math.max(320, box.clientWidth || 720);
    const isMobile = window.matchMedia('(max-width:520px)').matches;
    const baseCol = 120;
    const padX = isMobile ? 22 : 30;
    const colW = Math.max(baseCol, Math.floor((boxW - padX*2) / 5));
    const scale = (colW / baseCol) * (isMobile ? 1.6 : 1.2); // 👈 הגדלה משמעותית במובייל
    const y = isMobile ? 10 : 20;
    const xBase=i=> padX + i*colW;

    const masks=[ DM[A], DM[B], DM[C] ];
    root._st = { masks:[...masks], base:[...masks], carry:null };

    const parts=[];
    parts.push( digitGroup(xBase(0), y, scale, masks[0], 0) );

    // OP
    if (op==='+'){
      parts.push(`<g class="op" transform="translate(${xBase(1)+Math.round(colW*0.33)},${y+45*scale/1.2}) scale(${scale})">
        <rect x="0" y="15" width="40" height="8" rx="4" ry="4"/>
        <rect x="16" y="0" width="8" height="40" rx="4" ry="4"/>
      </g>`);
    } else {
      parts.push(`<g class="op" transform="translate(${xBase(1)+Math.round(colW*0.33)},${y+45*scale/1.2}) scale(${scale})">
        <rect x="0" y="15" width="40" height="8" rx="4" ry="4"/>
      </g>`);
    }

    parts.push( digitGroup(xBase(2), y, scale, masks[1], 1) );

    // '='
    parts.push(`<g class="op" transform="translate(${xBase(3)+Math.round(colW*0.28)},${y+33*scale/1.2}) scale(${scale})">
      <rect x="0" y="0" width="40" height="8" rx="4" ry="4"/>
      <rect x="0" y="22" width="40" height="8" rx="4" ry="4"/>
    </g>`);

    parts.push( digitGroup(xBase(4), y, scale, masks[2], 2) );

    // גובה ו־viewBox דינמיים כדי לשמור על קליקביליות
    const vbW = Math.max(720, Math.floor(colW*5 + padX*2));
    const vbH = Math.floor(160 * scale);
    box.innerHTML = `<svg viewBox="0 0 ${vbW} ${vbH}" width="100%" height="auto">${parts.join('')}</svg>`;
  }

  function redraw(root){
    const st=root._st, svg=root.querySelector('.glms-svg svg');
    if(!svg) return;
    svg.querySelectorAll('.seg').forEach(seg=>{
      const d=+seg.getAttribute('data-d'); const s=seg.getAttribute('data-seg');
      const bit=1<<IDX[s]; const on=(st.masks[d] & bit)!==0;
      seg.classList.toggle('off', !on); seg.classList.toggle('on', on);
    });
  }

  // שכנים/בדיקות
  function neighbors(state){
    const res=[];
    for(let fromD=0;fromD<3;fromD++){
      for(let toD=0;toD<3;toD++){
        for(const fromSeg of KEYS){
          const bitFrom = 1<<IDX[fromSeg];
          if ((state[fromD] & bitFrom)===0) continue;
          for(const toSeg of KEYS){
            const bitTo = 1<<IDX[toSeg];
            if ((state[toD] & bitTo)!==0) continue;
            const next=[...state];
            next[fromD] &= ~bitFrom;
            next[toD]   |=  bitTo;
            res.push([next,{from:{digit:fromD,seg:fromSeg},to:{digit:toD,seg:toSeg}}]);
          }
        }
      }
    }
    return res;
  }
  function digitsValid(state){ return maskToDigit(state[0])!==null && maskToDigit(state[1])!==null && maskToDigit(state[2])!==null; }
  function evalOK(A,op,B,C){ return op==='+' ? (A+B===C) : (A-B===C); }
  function stateToDigits(st){ return [maskToDigit(st[0]), maskToDigit(st[1]), maskToDigit(st[2])]; }
  const H = st => st.join(':');

  // פתרון יחיד (K<=2)
  function uniqueSolution(start, op, K){
    const q=[[start,[]]], seen=new Set([H(start)]); const sols=[];
    while(q.length){
      const [st,path]=q.shift();
      if (digitsValid(st)){
        const [A,B,C]=stateToDigits(st);
        if (evalOK(A,op,B,C)){ sols.push(path); if (sols.length>1) return null; continue; }
      }
      if (path.length>=K) continue;
      for(const [nxt,step] of neighbors(st)){
        const h=H(nxt); if (seen.has(h)) continue;
        seen.add(h); q.push([nxt,[...path,step]]);
      }
    }
    return (sols.length===1 && sols[0].length===K) ? sols[0] : null;
  }

  // מחולל חידה
  function generatePuzzle(K){
    for(let tries=0; tries<120; tries++){
      const op = (Math.random()<0.5)?'+':'-';
      const A = Math.floor(Math.random()*10);
      const B = Math.floor(Math.random()*10);
      const C = op==='+' ? A+B : A-B;
      if (C<0 || C>9) continue;
      const goal = [DM[A],DM[B],DM[C]];
      let st=[...goal];
      for(let i=0;i<K;i++){
        const ns = neighbors(st);
        if (!ns.length) continue;
        const pick = ns[Math.floor(Math.random()*ns.length)];
        st = pick[0];
      }
      if (!digitsValid(st)) continue;
      const [a,b,c] = stateToDigits(st);
      if (evalOK(a,op,b,c)) continue;
      const sol = uniqueSolution(st, op, K);
      if (!sol) continue;
      return { start: st, op, K, exprText: `${a} ${op} ${b} = ${c}`, solution: sol };
    }
    return null;
  }

  // HUD/Scoring
  const STORE='glms_progress_v3';
  function loadP(){ try{ return JSON.parse(localStorage.getItem(STORE)||'{}'); }catch(e){return{};} }
  function saveP(p){ localStorage.setItem(STORE, JSON.stringify(p)); }

  function bootOne(root){
    const mode = root.dataset.mode || 'daily';
    const maxmoves = parseInt(root.dataset.maxmoves||'2',10);

    const msg   = root.querySelector('.glms-msg');
    const hintB = root.querySelector('[data-act="hint"]');
    const solB  = root.querySelector('[data-act="solution"]');
    const again = root.querySelector('[data-act="again"]');
    const next  = root.querySelector('[data-act="next"]');
    const solBx = root.querySelector('.glms-solution');
    const solCt = solBx.querySelector('.content');
    const eqTxt = root.querySelector('.glms-eqtext');

    const pointsEl = root.querySelector('[data-points]');
    const levelEl  = root.querySelector('[data-level]');

    const prog = loadP(); prog.points??=0; prog.level??=1;
    let usedHint=false, solved=false, puzzle=null;

    function hud(){ pointsEl.textContent=prog.points; levelEl.textContent=prog.level; }
    function setMsg(t,ok=false){ msg.textContent=t; msg.style.color = ok?'#2e7d32':'#c0392b'; }
    function celebrate(){ const c=root.querySelector('.glms-celebrate'); c.classList.add('show'); setTimeout(()=>c.classList.remove('show'),1800); }

    async function buildPuzzle(initDaily=false){
      let K = (prog.level>=3 && maxmoves>=2) ? 2 : 1;
      if (initDaily){
        for (let i=0;i<40 && !puzzle;i++){ puzzle = generatePuzzle(K) || generatePuzzle(1); }
      } else {
        puzzle = generatePuzzle(K) || generatePuzzle(1);
      }
      if (!puzzle){ setMsg('לא נמצאה חידה, נסו שוב.', false); return; }

      const [a,b,c] = stateToDigits(puzzle.start);
      renderEq(root, a, puzzle.op, b, c);
      eqTxt.textContent = `${a} ${puzzle.op} ${b} = ${c}`;
      usedHint=false; solved=false; solBx.classList.remove('show');

      /* לא מציגים טיפ אדום ברירת־מחדל יותר */
      setMsg('');

      next.disabled = true; again.disabled=false; hintB.disabled=false; solB.disabled=false;

      // קליקים
      const svg = root.querySelector('.glms-svg');
      svg.onclick = (e)=>{
        const st=root._st; const el=e.target.closest('.seg'); if(!el) return;
        const d=+el.getAttribute('data-d'), s=el.getAttribute('data-seg');
        const bit=1<<IDX[s]; const on=(st.masks[d] & bit)!==0;
        if(!st.carry){
          if(!on){ setMsg('בחר/י תחילה גפרור דלוק'); return; }
          st.carry={digit:d,seg:s}; st.masks[d] &= ~bit; setMsg('הרמת גפרור – בחר/י יעד', true); redraw(root);
        } else {
          if(on){ setMsg('היעד כבר דלוק'); return; }
          st.masks[d] |= bit; st.carry=null; redraw(root); setMsg('');
          const A=maskToDigit(st.masks[0]), B=maskToDigit(st.masks[1]), C=maskToDigit(st.masks[2]);
          if (A!==null && B!==null && C!==null && evalOK(A,puzzle.op,B,C)){
            solved = true;
            const base = 100 * (puzzle.K);
            prog.points += base;
            if (puzzle.K === 2) prog.level = Math.min(5, prog.level + 1);
            saveP(prog); hud();
            setMsg(`כל הכבוד! (+${base} נק׳)`, true);
            celebrate();
            next.disabled = false;
          }
        }
      };

      hintB.onclick = ()=>{
        const h=puzzle.solution[0];
        setMsg(`רמז: העבר מה-${nameSeg(h.from.seg)} של הספרה ה${nameDigit(h.from.digit)} אל ה-${nameSeg(h.to.seg)} של הספרה ה${nameDigit(h.to.digit)}.`, true);
        usedHint = true;
        const HINT_COST = 30;
        prog.points = Math.max(0, (prog.points||0) - HINT_COST);
        saveP(prog); hud();
        blinkSeg(root,h.from); blinkSeg(root,h.to);
      };

      solB.onclick = ()=>{
        solCt.innerHTML = `<ol class="steps">${
          puzzle.solution.map((s,i)=>
            `<li>צעד ${i+1}: העבר מה-${nameSeg(s.from.seg)} <span>(מהספרה ה${nameDigit(s.from.digit)})</span> אל ה-${nameSeg(s.to.seg)} <span>(מהספרה ה${nameDigit(s.to.digit)})</span>.</li>`
          ).join('')
        }</ol>
        <div style="margin-top:8px"><button type="button" class="glms-btn" data-act="demo">הדגמה</button></div>`;
        solBx.classList.add('show');
        solBx.querySelector('[data-act="demo"]').onclick = ()=> demoSolution(root,puzzle);
      };

      again.onclick = ()=>{
        const [a,b,c] = stateToDigits(puzzle.start);
        renderEq(root, a, puzzle.op, b, c); redraw(root);
        usedHint=false; solved=false; setMsg('התחל/י מחדש', true);
        next.disabled=true;
      };

      next.onclick = ()=>{
        if(!solved){ setMsg('פתרו ואז לחידה הבאה'); return; }
        puzzle=null; buildPuzzle(false);
      };

      if (mode==='daily'){ next.disabled=true; }

      // רענון סקייל/קליקביליות בעת שינוי גודל (ליתר בטחון במובייל)
      const ro = new ResizeObserver(()=>{ const [a2,b2,c2]=stateToDigits(puzzle.start); renderEq(root, a2, puzzle.op, b2, c2); redraw(root); });
      ro.observe(root.querySelector('.glms-svg'));
    }

    function nameSeg(s){ return {a:'עליון',b:'ימני־עליון',c:'ימני־תחתון',d:'אמצעי',e:'שמאלי־תחתון',f:'שמאלי־עליון',g:'תחתון'}[s]; }
    function nameDigit(i){ return ['ראשונה','שנייה','שלישית'][i]; }
    function blinkSeg(root, pos){
      const svg=root.querySelector('.glms-svg svg');
      if(!svg) return;
      svg.querySelectorAll(`.seg[data-d="${pos.digit}"][data-seg="${pos.seg}"]`).forEach(el=>{
        el.classList.add('demo'); setTimeout(()=>el.classList.remove('demo'),1000);
      });
    }
    function demoSolution(root, p){
      const [a,b,c] = stateToDigits(p.start); renderEq(root, a, p.op, b, c);
      let i=0; const st=root._st;
      (function stepper(){
        if(i>=p.solution.length){ redraw(root); return; }
        const s=p.solution[i];
        blinkSeg(root, s.from); blinkSeg(root, s.to);
        const fromBit=1<<IDX[s.from.seg], toBit=1<<IDX[s.to.seg];
        st.masks[s.from.digit] &= ~fromBit;
        st.masks[s.to.digit]   |=  toBit;
        redraw(root);
        i++; setTimeout(stepper, 900);
      })();
    }

    hud();
    buildPuzzle(mode==='daily');
  }

  document.addEventListener('DOMContentLoaded', ()=>{
    document.querySelectorAll('.glms').forEach(root=> bootOne(root));
  });
})();
JS;
  wp_register_script('glms-script', '', [], false, true);
  wp_enqueue_script('glms-script');
  wp_add_inline_script('glms-script', $js);
});

/* ---------- Shortcode ---------- */
add_shortcode('matchsticks', function ($atts) {
  $a = shortcode_atts([
    'mode'     => 'random', // daily | random
    'maxmoves' => '2'       // 1–2
  ], $atts, 'matchsticks');

  ob_start(); ?>
  <div class="glms" data-mode="<?php echo esc_attr($a['mode']); ?>" data-maxmoves="<?php echo (int)$a['maxmoves']; ?>">
    <div class="glms-head">
      <h3 class="glms-title">חידות גפרורים</h3>
      <div class="glms-hud">
        <span>שלב: <b data-level>1</b></span>
        <span>נקודות: <b data-points>0</b></span>
      </div>
    </div>

    <div class="glms-actions">
      <button type="button" class="glms-btn" data-act="hint">רמז</button>
      <button type="button" class="glms-btn" data-act="solution">פתרון</button>
      <button type="button" class="glms-btn" data-act="again">שחק שוב</button>
      <button type="button" class="glms-btn" data-act="next" disabled>חידה חדשה</button>
    </div>

    <div class="glms-msg" aria-live="polite"></div>

    <!-- שכבת חגיגה -->
    <div class="glms-celebrate" aria-hidden="true">
      <div class="box">
        <div class="title">כל הכבוד! 🎉</div>
        <div class="sub">פתרת נכון</div>
      </div>
    </div>

    <div class="glms-svg"></div>
    <div class="glms-eqtext"></div>

    <div class="glms-solution">
      <h4>הסבר הפתרון</h4>
      <div class="content"></div>
    </div>

    <!-- נשארת רק הערת טיפ האפורה (לא הודעת טיפ אדומה) -->
    <div class="glms-note">טיפ: לחץ/י על גפרור דלוק כדי “להרים”, ואז על סגמנט כבוי כדי “להניח”. כל העברה = מהלך 1.</div>
  </div>
  <?php
  return ob_get_clean();
});

