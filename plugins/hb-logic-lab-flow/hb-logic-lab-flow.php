<?php
/**
 * Plugin Name: HB Logic Lab Flow
 * Description: Step-by-step cryptic clue trainer with sequential actions. Shortcode: [hb_flow ...].
 * Version: 1.0.0
 * Author: Higayon Barie
 */
if (!defined('ABSPATH')) exit;

add_action('wp_enqueue_scripts', function(){
  $css = '
  .hb-flow{border:1px solid #eee;border-radius:12px;padding:12px;background:#fff;margin:16px 0}
  .hb-flow h4{margin:0 0 8px 0;font-size:1rem}
  .hb-flow .row{margin:8px 0}
  .hb-flow button{padding:8px 12px;border:1px solid #ddd;border-radius:10px;background:#fafafa;cursor:pointer}
  .hb-flow .btn-primary{border-color:#ccc}
  .hb-flow .hint{font-size:.95rem;opacity:.85;margin:6px 0}
  .hb-flow .clue{padding:8px;border:1px dashed #ddd;border-radius:8px;line-height:1.8}
  .hb-flow mark{padding:0 2px;border-radius:3px}
  .hb-flow mark.part1{background:#e9f3ff}
  .hb-flow mark.part2{background:#fff1d6}
  .hb-flow mark.part3{background:#e6ffe7}
  .hb-flow .muted{opacity:.7}
  .hb-flow .good{color:#066e3f}
  .hb-flow .bad{color:#b00020}
  .hb-flow .field{display:flex;gap:8px;align-items:center;margin-top:6px;flex-wrap:wrap}
  .hb-flow input[type=text]{padding:8px;border:1px solid #ddd;border-radius:8px}
  .hb-flow .sep{height:1px;background:#f0f0f0;margin:12px 0}
  ';
  wp_register_style('hb-logic-lab-flow', false);
  wp_add_inline_style('hb-logic-lab-flow', $css);
  wp_enqueue_style('hb-logic-lab-flow');
});

add_shortcode('hb_flow', function($atts){
  $a = shortcode_atts([
    'clue' => '',
    'split' => '2',
    'p1' => '',
    'p2' => '',
    'p3' => '',
    'plain' => '1',
    'answer' => '',
    'options' => 'anagram,hidden,homophone,reversal,container,charade,clipping',
    'manip' => '',
    'tri_label' => 'חלוקת ההגדרה לשלושה חלקים'
  ], $atts);

  $id = 'hbf-'.wp_generate_uuid4();
  $split = (int)$a['split'];
  $opts = array_filter(array_map('trim', explode(',', $a['options'])));

  ob_start(); ?>
  <div class="hb-flow" id="<?php echo esc_attr($id); ?>"
       data-split="<?php echo esc_attr($split); ?>"
       data-p1="<?php echo esc_attr($a['p1']); ?>"
       data-p2="<?php echo esc_attr($a['p2']); ?>"
       data-p3="<?php echo esc_attr($a['p3']); ?>"
       data-plain="<?php echo esc_attr($a['plain']); ?>"
       data-answer="<?php echo esc_attr($a['answer']); ?>"
       data-manip="<?php echo esc_attr($a['manip']); ?>"
       >
    <h4 class="muted">תרגיל מודרך</h4>

    <div class="row">
      <button class="step1 btn-primary">
        <?php echo $split===3 ? esc_html($a['tri_label']) : 'חלוקת ההגדרה לשני חלקים'; ?>
      </button>
      <div class="panel1" hidden>
        <div class="hint">עליך לסמן בתוך ההגדרה את <?php echo $split===3 ? 'שלושת' : 'שני'; ?> החלקים שלה.</div>
        <div class="clue" contenteditable="true"><?php echo esc_html($a['clue']); ?></div>
        <div class="field">
          <button class="mark" data-for="part1">סמן חלק 1</button>
          <button class="mark" data-for="part2">סמן חלק 2</button>
          <?php if($split===3): ?><button class="mark" data-for="part3">סמן חלק 3</button><?php endif; ?>
          <button class="clear">נקה סימונים</button>
          <button class="check1">בדוק</button>
        </div>
        <div class="msg1 muted"></div>
      </div>
    </div>

    <div class="sep"></div>

    <div class="row">
      <button class="step2 btn-primary" disabled>איזה צד נפתר כפשוטו?</button>
      <div class="panel2" hidden>
        <div class="hint">עליך לסמן בתוך ההגדרה את הצד שנפתר כפשוטו.</div>
        <div class="field choose-plain">
          <button class="plain" data-which="1">חלק 1</button>
          <button class="plain" data-which="2">חלק 2</button>
          <?php if($split===3): ?><button class="plain" data-which="3">חלק 3</button><?php endif; ?>
        </div>
        <div class="msg2 muted"></div>
        <div class="field plain-answer" hidden>
          <span>האם מצאת את התשובה לצד הזה?</span>
          <button class="found-yes">כן</button>
          <button class="found-no">לא</button>
        </div>
        <div class="field try-answer" hidden>
          <input type="text" class="try" placeholder="נסו לכתוב את הפתרון">
          <button class="submit-try">בדוק</button>
          <div class="msg2b muted"></div>
        </div>
      </div>
    </div>

    <div class="sep"></div>

    <div class="row">
      <button class="step3 btn-primary" disabled>איזו מניפולציה יש פה?</button>
      <div class="panel3" hidden>
        <div class="hint">מצאת את המניפולציה שיש בהגדרה?</div>
        <div class="field yn">
          <button class="yn-yes">כן</button>
          <button class="yn-no">לא</button>
        </div>
        <div class="field pick" hidden>
          <div class="muted">בחר/י:</div>
          <?php foreach($opts as $op): ?>
            <label style="display:inline-flex;align-items:center;gap:6px;margin-right:10px">
              <input type="radio" name="<?php echo esc_attr($id); ?>-manip" value="<?php echo esc_attr($op); ?>"> <?php echo esc_html($op); ?>
            </label>
          <?php endforeach; ?>
          <button class="check3">בדוק</button>
        </div>
        <div class="msg3 muted"></div>
      </div>
    </div>

  </div>

  <script>
  (function(){
    const root = document.getElementById('<?php echo esc_js($id); ?>');
    if(!root) return;
    const split = parseInt(root.dataset.split||'2',10);
    const exp = {p1: root.dataset.p1||'', p2: root.dataset.p2||'', p3: root.dataset.p3||''};
    const plain = (root.dataset.plain||'1');
    const answer = (root.dataset.answer||'').replace(/\s/g,'');
    const manip = (root.dataset.manip||'');

    function clearMarks(){
      root.querySelectorAll('mark').forEach(m=>{
        const p=m.parentNode; while(m.firstChild) p.insertBefore(m.firstChild, m);
        p.removeChild(m);
      });
    }
function wrapSelection(cls){
  const sel = window.getSelection(); if(!sel.rangeCount) return;
  const r = sel.getRangeAt(0); if(!r || r.collapsed) return;
  const card = r.commonAncestorContainer;
  const clue = (card.nodeType===1 ? card : card.parentNode).closest('.hb-flow .panel1 .clue');
  if(!clue) return; // רק אם הבחירה בתוך ההגדרה

  // אם הגבולות אינם בתוך ה-.clue, לא נסמן
  if (!clue.contains(r.startContainer) || !clue.contains(r.endContainer)) return;

  const mk = document.createElement('mark'); mk.className = cls;
  try { r.surroundContents(mk); } catch(e){}
  sel.removeAllRanges();
}

    function textOf(cls){
      return [...root.querySelectorAll('mark.'+cls)].map(n=>n.textContent.trim()).join(' ');
    }

    // Step 1
    const step1Btn = root.querySelector('.step1');
    const p1 = root.querySelector('.panel1');
    step1Btn.addEventListener('click', ()=>{ p1.hidden=false; });

    p1.addEventListener('click', (e)=>{
      if(e.target.classList.contains('mark')){
        wrapSelection(e.target.dataset.for);
      }
      if(e.target.classList.contains('clear')){
        clearMarks();
      }
      if(e.target.classList.contains('check1')){
        const got1 = textOf('part1')===(exp.p1||'').trim();
        const got2 = textOf('part2')===(exp.p2||'').trim();
        const got3 = split===3 ? (textOf('part3')===(exp.p3||'').trim()) : true;
        const msg = p1.querySelector('.msg1');
        if(got1 && got2 && got3){
          msg.textContent = 'מצוין! החלוקה שסימנת נכונה.'; msg.className='msg1 good';
          root.querySelector('.step2').disabled=false;
          root.querySelector('.step2').focus();
        }else{
          msg.textContent = 'כדאי לנסות שוב ולוודא שהחלקים מסומנים במלואם.'; msg.className='msg1 bad';
        }
      }
    });

    // Step 2
    const step2Btn = root.querySelector('.step2');
    const p2 = root.querySelector('.panel2');
    step2Btn.addEventListener('click', ()=>{ p2.hidden=false; });

    p2.addEventListener('click', (e)=>{
      if(e.target.classList.contains('plain')){
        const which = e.target.dataset.which;
        const msg = p2.querySelector('.msg2');
        if(which===plain){
          msg.textContent='נכון — זה הצד שנפתר כפשוטו.'; msg.className='msg2 good';
          p2.querySelector('.plain-answer').hidden=false;
        }else{
          msg.textContent='לא מדויק. מסמן את הצד הנכון עבורך.'; msg.className='msg2 bad';
          root.querySelectorAll('mark').forEach(m=> m.style.outline='');
          const corr = root.querySelector('mark.part'+plain);
          if(corr) corr.style.outline='2px solid rgba(0,0,0,.2)';
          p2.querySelector('.plain-answer').hidden=false;
        }
      }
      if(e.target.classList.contains('found-yes')){
        p2.querySelector('.try-answer').hidden=false;
      }
      if(e.target.classList.contains('found-no')){
        root.querySelector('.step3').disabled=false;
        root.querySelector('.step3').focus();
      }
      if(e.target.classList.contains('submit-try')){
        const v = (p2.querySelector('.try').value||'').replace(/\s/g,'');
        const out = p2.querySelector('.msg2b');
        if(answer && v===answer){
          out.textContent='יפה! נמשיך לבדיקה המלאה של הרמז.'; out.className='msg2b good';
        }else{
          out.textContent='נמשיך ונראה אם צדקת.'; out.className='msg2b muted';
        }
        root.querySelector('.step3').disabled=false;
        root.querySelector('.step3').focus();
      }
    });

    // Step 3
    const step3Btn = root.querySelector('.step3');
    const p3 = root.querySelector('.panel3');
    step3Btn.addEventListener('click', ()=>{ p3.hidden=false; });

    p3.addEventListener('click', (e)=>{
      if(e.target.classList.contains('yn-yes')){
        p3.querySelector('.pick').hidden=false;
      }
      if(e.target.classList.contains('yn-no')){
        const msg = p3.querySelector('.msg3');
        msg.textContent = (manip ? ('המניפולציה כאן היא: '+manip+'. צפו בהמשך הווידאו להסבר מלא.') : 'צפו בהמשך הווידאו להסבר המלא.');
        msg.className = 'msg3 muted';
      }
      if(e.target.classList.contains('check3')){
        const sel = root.querySelector('input[name="<?php echo esc_js($id); ?>-manip"]:checked');
        const msg = p3.querySelector('.msg3');
        if(!sel){ msg.textContent='בחר/י אופציה'; msg.className='msg3 bad'; return; }
        if(manip && sel.value===manip){
          msg.textContent='בול! זו המניפולציה. המשיכו בווידאו להסבר מלא.'; msg.className='msg3 good';
        }else{
          msg.textContent='עדיין לא. '+(manip ? ('התשובה הנכונה: '+manip+'. המשיכו בווידאו להסבר.') : 'בדקו בהמשך הווידאו.'); msg.className='msg3 bad';
        }
      }
    });
  })();
  </script>

  <?php
  return ob_get_clean();
});
