<?php
/**
 * Plugin Name: HB Cue Guide
 * Description: Interactive 3-step exercise (split on text → pick plain on text → solve). Shortcode: [hb_guide clue="" split="2|3" p1="" p2="" p3="" plain="1|2|3" answer=""].
 * Version: 1.3.1
 * Author: Higayon Barie
 */
if (!defined('ABSPATH')) exit;

add_action('wp_enqueue_scripts', function(){
  wp_register_style('hb-cue-guide', false);
  wp_add_inline_style('hb-cue-guide', '
    .hb-guide{border:1px solid #e9e9ef;border-radius:16px;padding:20px;background:#fff;box-shadow:0 6px 24px rgba(16,24,40,.06)}
    .hb-guide .clue-title{font-size:1.12rem;font-weight:800;margin-bottom:10px;text-align:center;letter-spacing:.2px}
    .hb-guide .clue-wrap{position:relative;display:flex;justify-content:center;margin:12px 0 18px}
    .hb-guide .clue{max-width:940px;font-size:1.7rem;line-height:2.25;text-align:center;border:1px dashed #dfe3ea;border-radius:14px;padding:18px 20px;background:#fbfbff;user-select:text;-webkit-user-select:text}
    .hb-guide .clue[contenteditable="true"]{outline:none}
    .hb-guide mark{background:#fff4b8;border-radius:6px;padding:0 3px}
    .hb-guide mark.part1{background:#dff7e8}
    .hb-guide mark.part2{background:#e7efff}
    .hb-guide mark.part3{background:#fde7ff}
    .hb-guide .toolbar{position:absolute;z-index:5;display:none;gap:6px;background:#111;color:#fff;border-radius:10px;padding:6px 8px;box-shadow:0 8px 24px rgba(0,0,0,.18)}
    .hb-guide .toolbar button{background:transparent;border:0;color:#fff;cursor:pointer;font-weight:700;padding:6px 10px;border-radius:8px}
    .hb-guide .toolbar button:hover{background:rgba(255,255,255,.14)}
    .hb-guide .row{margin:16px 0}
    .hb-guide .hint{opacity:.95;margin-bottom:10px;text-align:center;font-size:1.04rem}
    .hb-guide .muted{opacity:.75}
    .hb-guide .good{color:#0a7a4b;font-weight:700}
    .hb-guide .bad{color:#b00020;font-weight:700}
    .hb-guide .controls{display:flex;gap:10px;flex-wrap:wrap;justify-content:center}
    /* כפתורי בדיקה – גודל אחיד בכל השלבים */
    .hb-guide .btn{min-width:180px;padding:12px 18px;border:1px solid #d0d5dd;border-radius:12px;background:#111827;color:#fff;cursor:pointer;transition:transform .06s ease, box-shadow .2s ease, background .2s;font-weight:700;box-shadow:0 2px 6px rgba(16,24,40,.12);text-align:center}
    .hb-guide .btn:hover{filter:brightness(1.05)}
    .hb-guide .btn.secondary{background:#fff;color:#111;border-color:#d0d5dd}
    .hb-guide .btn.secondary:hover{background:#f3f4f6}
    .hb-guide .msg{margin-top:10px;text-align:center;font-size:1.06rem}

    /* שלב 3 – מודגש, ממורכז, תיבת טקסט מתחת */
    .hb-guide .row3 { margin-top: 22px; border-top: 1px dashed #e5e7eb; padding-top: 16px; }
    .hb-guide .row3 .hint { font-weight: 800; font-size: 1.14rem; text-align: center; margin-bottom: 12px; }
    .hb-guide .field{display:flex;gap:12px;flex-wrap:wrap;align-items:center;justify-content:center;margin-top:10px}
    .hb-guide .row3 .field{justify-content:center;flex-direction:column;gap:10px}
    .hb-guide input[type=text]{padding:12px 16px;border:1px solid #d0d5dd;border-radius:12px;min-width:300px;max-width:560px;width:100%;font-size:1.1rem;text-align:center}

    /* נסיון ליישר כפתור "המשך וידאו" של תוסף ה-Cue אם יש class כללית */
    .hb-guide + .hb-cue-continue .btn,
    .hb-guide + .hb-cue-continue button{min-width:180px;padding:12px 18px;border-radius:12px}

    @media (max-width: 600px){
      .hb-guide .clue{font-size:1.35rem; line-height:2.05; padding:14px 14px}
      .hb-guide .hint{font-size:1rem}
      .hb-guide .btn{min-width:160px;padding:11px 16px}
    }
  ');
  wp_enqueue_style('hb-cue-guide');
});

add_shortcode('hb_guide', function($atts){
  $a = shortcode_atts([
    'clue'  => '',
    'split' => '2',
    'p1'    => '',
    'p2'    => '',
    'p3'    => '',
    'plain' => '1',
    'answer'=> '',
  ], $atts, 'hb_guide');

  $id    = 'hbg-'.wp_generate_uuid4();
  $split = max(2, min(3, intval($a['split'])));

  ob_start(); ?>
  <div class="hb-guide" id="<?php echo esc_attr($id); ?>"
       data-split="<?php echo esc_attr($split); ?>"
       data-plain="<?php echo esc_attr($a['plain']); ?>"
       data-answer="<?php echo esc_attr($a['answer']); ?>"
       data-p1="<?php echo esc_attr($a['p1']); ?>"
       data-p2="<?php echo esc_attr($a['p2']); ?>"
       data-p3="<?php echo esc_attr($a['p3']); ?>">
    <div class="clue-title">ההגדרה</div>

    <div class="clue-wrap">
      <div class="clue" contenteditable="true"><?php echo esc_html($a['clue']); ?></div>
      <div class="toolbar" role="group" aria-label="סימון חלקים">
        <button data-tag="1">חלק 1</button>
        <button data-tag="2">חלק 2</button>
        <?php if ($split===3): ?><button data-tag="3">חלק 3</button><?php endif; ?>
        <button data-clear class="secondary" title="נקה">נקה</button>
      </div>
    </div>

    <!-- שלב 1 -->
    <div class="row row1">
      <div class="hint">
        שלב 1: סמנו על המשפט — גררו עכבר/אצבע על המילים של החלק הרצוי.
        ייפתח בלון: בחרו את מספר החלק.חזרו עבור החלק הנוסף. לסיום לחצו <em>בדוק חלוקה</em>.
      </div>
      <div class="controls">
        <button class="btn check1">בדוק חלוקה</button>
      </div>
      <div class="msg msg1 muted"></div>
    </div>

    <!-- שלב 2 (נפתח אחרי שלב 1) -->
    <div class="row row2" style="display:none;">
      <div class="hint">שלב 2: לחצו על אחד החלקים המסומנים שהוא לדעתכם “כפשוטו”, ואז “בדוק כפשוטו”.</div>
      <div class="controls">
        <button class="btn check2">בדוק כפשוטו</button>
      </div>
      <div class="msg msg2 muted"></div>
    </div>

    <!-- שלב 3: תמיד מוצג -->
    <div class="row row3">
      <div class="hint">שלב 3: מה הפתרון של ההגדרה?</div>
      <div class="field">
        <input type="text" class="sol" placeholder="כתבו את הפתרון">
        <button class="btn check3">בדוק</button>
      </div>
      <div class="msg msg3 muted"></div>
    </div>
  </div>

  <script>
  (function(){
    const root   = document.getElementById('<?php echo esc_js($id); ?>');
    if(!root) return;

    const split  = parseInt(root.dataset.split||'2',10);
    const plain  = String(root.dataset.plain||'1');
    const answer = (root.dataset.answer||'').replace(/\s/g,'');

    const exp = {
      1: (root.dataset.p1||'').replace(/\s+/g,' ').trim(),
      2: (root.dataset.p2||'').replace(/\s+/g,' ').trim(),
      3: (root.dataset.p3||'').replace(/\s+/g,' ').trim()
    };

    const clueEl = root.querySelector('.clue');
    const bar    = root.querySelector('.toolbar');
    const row1   = root.querySelector('.row1');
    const row2   = root.querySelector('.row2');

    /* ---------- Utilities ---------- */
    function clearMarks(){
      const html = clueEl.innerHTML;
      clueEl.innerHTML = html.replace(/<\/?mark[^>]*>/g,'');
    }
    function norm(s){
      return (s||'').toLowerCase()
        .replace(/[^\u0590-\u05FFa-z0-9 ]+/g,'')
        .replace(/\s+/g,' ')
        .trim();
    }
    function getTextOffsetsWithin(node, container){
      // מחזיר start,end ביחס ל-textContent של container
      const sel = window.getSelection(); if(!sel || !sel.rangeCount) return null;
      const r = sel.getRangeAt(0);
      if(!r || r.collapsed) return null;
      if (!container.contains(r.startContainer) || !container.contains(r.endContainer)) return null;

      // חשב אינדקסים יחסיים ע"י מעבר על כל ה-TextNodes
      let start = -1, end = -1, idx = 0;
      const walker = document.createTreeWalker(container, NodeFilter.SHOW_TEXT, null);
      while (walker.nextNode()){
        const tn = walker.currentNode;
        const length = tn.nodeValue.length;

        if (tn === r.startContainer){
          start = idx + r.startOffset;
        }
        if (tn === r.endContainer){
          end = idx + r.endOffset;
          break;
        }
        idx += length;
      }
      if (start < 0 || end < 0 || end <= start) return null;
      return {start, end};
    }
    function setClueWithMarks(parts){
      // parts = [{cls, s, e}, ...] על textContent גולמי
      const raw = clueEl.textContent;
      let out = '';
      let pos = 0;
      parts.sort((a,b)=>a.s-b.s);
      parts.forEach(p=>{
        out += escapeHtml(raw.slice(pos, p.s));
        out += '<mark class="'+p.cls+'">'+ escapeHtml(raw.slice(p.s, p.e)) +'</mark>';
        pos = p.e;
      });
      out += escapeHtml(raw.slice(pos));
      clueEl.innerHTML = out;
    }
    function escapeHtml(s){ return (s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;'); }
    function getMarksText(cls){
      const m = clueEl.querySelector('mark.'+cls);
      return m ? m.textContent.replace(/\s+/g,' ').trim() : '';
    }
    function autoRevealCorrectSplit(exp1, exp2, exp3){
      // מצא הופעה ראשונה של הטקסטים בתוך ה-raw ובנה חלקים
      const raw = clueEl.textContent;
      let parts = [];
      function idxOfChunk(t){ if(!t) return -1; return raw.indexOf(t); }
      const i1 = idxOfChunk(exp1), i2 = idxOfChunk(exp2), i3 = split===3 ? idxOfChunk(exp3) : -1;
      if (i1>=0) parts.push({cls:'part1', s:i1, e:i1+exp1.length});
      if (i2>=0) parts.push({cls:'part2', s:i2, e:i2+exp[2].length});
      if (split===3 && i3>=0) parts.push({cls:'part3', s:i3, e:i3+exp[3].length});
      if (parts.length){ setClueWithMarks(parts); }
    }

    /* ---------- Selection / Toolbar (עכבר + טאצ') ---------- */
    function positionBar(){
      const sel = window.getSelection();
      if(!sel || !sel.rangeCount) return hideBar();
      const r = sel.getRangeAt(0);
      if(!r || r.collapsed || !clueEl.contains(r.startContainer) || !clueEl.contains(r.endContainer)) return hideBar();
      const rect = r.getBoundingClientRect();
      const host = clueEl.getBoundingClientRect();
      bar.style.display = 'flex';
      // מרכז מעל הבחירה
      bar.style.top  = (rect.top - host.top - 44 + clueEl.scrollTop) + 'px';
      bar.style.left = (rect.left - host.left + rect.width/2 - bar.offsetWidth/2 + clueEl.scrollLeft) + 'px';
    }
    function hideBar(){ bar.style.display='none'; }

    clueEl.addEventListener('mouseup', ()=> setTimeout(positionBar, 10));
    clueEl.addEventListener('keyup',  ()=> setTimeout(positionBar, 10));
    clueEl.addEventListener('touchend', ()=> setTimeout(positionBar, 10), {passive:true});
    document.addEventListener('click', (e)=>{ if(!bar.contains(e.target) && !clueEl.contains(e.target)) hideBar(); });

    bar.addEventListener('click', (e)=>{
      const b = e.target.closest('button'); if(!b) return;
      if (b.hasAttribute('data-clear')) { clearMarks(); return; }
      const tag = b.getAttribute('data-tag'); // "1"/"2"/"3"
      const sel = getTextOffsetsWithin(window.getSelection(), clueEl);
      if(!sel){ hideBar(); return; }
      // בנה חלקים קיימים + החלק החדש
      const raw = clueEl.textContent;
      let parts = [];
      // אם קיימים סימונים – שלוף אותם כ-offsets מחדש מה-raw (לשם פשטות נמחק ונבנה)
      const m1 = getMarksText('part1'), m2 = getMarksText('part2'), m3 = getMarksText('part3');
      function pushExisting(txt, cls){
        if(!txt) return;
        const i = raw.indexOf(txt);
        if(i>=0) parts.push({cls, s:i, e:i+txt.length});
      }
      pushExisting(m1,'part1'); pushExisting(m2,'part2'); if (split===3) pushExisting(m3,'part3');
      // הוסף הבחירה החדשה:
      parts = parts.filter(p=>p.cls !== ('part'+tag)); // החלפה אם כבר קיים
      parts.push({cls:'part'+tag, s:sel.start, e:sel.end});
      // בנה מחדש:
      setClueWithMarks(parts);
      hideBar();
    });

    /* ---------- בדיקות שלבים ---------- */
    // שלב 1
    root.querySelector('.check1')?.addEventListener('click', ()=>{
      const m1 = getMarksText('part1'), m2 = getMarksText('part2'), m3 = split===3 ? getMarksText('part3') : '';
      const msg = root.querySelector('.msg1');
      const ok1 = norm(m1)===norm(exp[1]);
      const ok2 = norm(m2)===norm(exp[2]);
      const ok3 = split===3 ? (norm(m3)===norm(exp[3])) : true;

      if (ok1 && ok2 && ok3){
        msg.textContent = "מצוין! החלוקה שסימנת נכונה.";
        msg.className = "msg msg1 good";
      } else {
        autoRevealCorrectSplit(exp[1], exp[2], exp[3]);
        msg.textContent = "הצגנו את החלוקה הנכונה על המשפט. מעבירים לשלב הבא.";
        msg.className = "msg msg1 bad";
      }
      setTimeout(()=>{ if(row1) row1.style.display="none"; if(row2) row2.style.display="block"; }, 450);
    });

    // שלב 2 – בחירת כפשוטו ע"י לחיצה על הסימון עצמו
    let chosenPlain = "";
    clueEl.addEventListener('click', (e)=>{
      const m = e.target.closest('mark'); if(!m) return;
      if(m.classList.contains('part1')) chosenPlain = "1";
      else if(m.classList.contains('part2')) chosenPlain = "2";
      else if(split===3 && m.classList.contains('part3')) chosenPlain = "3";
      m.style.outline="2px solid #111"; setTimeout(()=>{ m.style.outline=""; }, 180);
    });

    root.querySelector('.check2')?.addEventListener('click', ()=>{
      const msg = root.querySelector('.msg2');
      if(!chosenPlain){ msg.textContent="בחר/י חלק ע״י לחיצה על אחד הקטעים המסומנים."; msg.className="msg msg2 bad"; return; }
      if(String(chosenPlain)===plain){
        msg.textContent="נכון — זה החלק שנפתר כפשוטו.";
        msg.className="msg msg2 good";
      } else {
        msg.textContent="לא מדויק. החלק הנכון הוא חלק "+plain+".";
        msg.className="msg msg2 bad";
      }
    });

    // שלב 3 – פתרון
    root.querySelector('.check3')?.addEventListener('click', ()=>{
      const msg = root.querySelector('.msg3');
      const field = root.querySelector('.sol');
      const val = (field ? field.value : "").replace(/\s/g,"");
      if(!field){ msg.textContent="שדה הפתרון לא נמצא."; msg.className="msg msg3 bad"; return; }
      if(answer){
        if(val===answer){ msg.textContent="בול! יפה מאוד. חזרו לסרטון להסבר המלא."; msg.className="msg msg3 good"; }
        else{ msg.textContent="הפתרון הנכון: "+answer+". חזרו להסבר המלא בווידאו."; msg.className="msg msg3 bad"; }
      }else{
        msg.textContent="בדקו את הפתרון בהמשך הווידאו."; msg.className="msg msg3 muted";
      }
    });
  })();
  </script>
  <?php
  return ob_get_clean();
});

