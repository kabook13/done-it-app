<?php
/**
 * Plugin Name: HB Logic Lab
 * Description: Interactive exercises for cryptic crosswords: tag-the-parts, pick manipulation, assemble answer. Includes [hb_ex] wrapper for builders.
 * Version: 1.1.0
 * Author: Higayon Barie
 */
if (!defined('ABSPATH')) exit;

// enqueue minimal CSS once
add_action('wp_enqueue_scripts', function(){
  $css = '.hb-ex{border:1px solid #eee;padding:12px;border-radius:12px;margin:14px 0;background:#fff}
  .hb-ex[hidden]{display:none!important}
  .hb-ex .hb-head{font-weight:700;margin-bottom:6px}
  .hb-resume{margin-top:10px;padding:8px 14px;border:1px solid #ddd;border-radius:10px;background:#f6f6f6;cursor:pointer}
  .hb-clue-card{border:1px solid #eee;padding:12px;border-radius:12px;background:#fff}
  .hb-clue-text mark.hb-definition{background:#cfe8ff;padding:0 2px}
  .hb-clue-text mark.hb-indicator{background:#ffd79a;padding:0 2px}
  .hb-clue-text mark.hb-fodder{background:#c7f7c9;padding:0 2px}
  .hb-actions button{padding:6px 10px;border:1px solid #ddd;border-radius:8px;background:#f7f7f7;cursor:pointer}
  .hb-letters{display:flex;flex-wrap:wrap;gap:6px;margin:8px 0}
  .hb-letter{padding:6px 10px;border:1px solid #ddd;border-radius:8px;background:#fff;cursor:pointer}
  @media (max-width:640px){.hb-resume{width:100%}}';
  wp_register_style('hb-logic-lab', false);
  wp_add_inline_style('hb-logic-lab', $css);
  wp_enqueue_style('hb-logic-lab');
});

// 1) Tag-the-parts: [hb_clue clue="..." solution="..." type="anagram" def="..." ind="..." fod="..."]
add_shortcode('hb_clue', function($atts){
  $a = shortcode_atts([
    'clue' => '',
    'solution' => '',
    'type' => '',
    'def' => '',
    'ind' => '',
    'fod' => ''
  ], $atts);
  ob_start(); ?>
  <div class="hb-clue-card" data-solution="<?php echo esc_attr($a['solution']); ?>" data-type="<?php echo esc_attr($a['type']); ?>" data-def="<?php echo esc_attr($a['def']); ?>" data-ind="<?php echo esc_attr($a['ind']); ?>" data-fod="<?php echo esc_attr($a['fod']); ?>">
    <div class="hb-clue-text" contenteditable="false"><?php echo esc_html($a['clue']); ?></div>
    <div class="hb-actions" style="margin-top:8px;display:flex;gap:6px;flex-wrap:wrap">
      <button class="hb-mark" data-mark="definition">סמן הגדרה</button>
      <button class="hb-mark" data-mark="indicator">סמן אינדיקטור</button>
      <button class="hb-mark" data-mark="fodder">סמן חומר גלם</button>
      <button class="hb-clear">נקה סימון</button>
      <button class="hb-check">בדוק</button>
    </div>
    <div class="hb-feedback" hidden style="margin-top:8px;"></div>
  </div>
  <script>
  (function(){
    if(window.hbClueInit) return; window.hbClueInit = true;
    function wrapSelection(cls){
      const sel = window.getSelection(); if(!sel.rangeCount) return;
      const r = sel.getRangeAt(0);
      if(!r || r.collapsed) return;
      const mk = document.createElement('mark'); mk.className = 'hb-'+cls;
      try{ r.surroundContents(mk); }catch(e){}
      sel.removeAllRanges();
    }
    document.addEventListener('click', function(e){
      const btn = e.target.closest('.hb-mark');
      if(btn){
        wrapSelection(btn.dataset.mark);
        return;
      }
      if(e.target.classList.contains('hb-clear')){
        const card = e.target.closest('.hb-clue-card');
        card.querySelectorAll('mark').forEach(m=>{
          const p=m.parentNode; while(m.firstChild) p.insertBefore(m.firstChild, m);
          p.removeChild(m);
        });
      }
      if(e.target.classList.contains('hb-check')){
        const card = e.target.closest('.hb-clue-card');
        const fb = card.querySelector('.hb-feedback'); fb.hidden=false;
        let ok = true;
        const def = (card.dataset.def||'').trim();
        const ind = (card.dataset.ind||'').trim();
        const fod = (card.dataset.fod||'').trim();
        function textOf(cls){ return [...card.querySelectorAll('.hb-'+cls)].map(n=>n.textContent.trim()).join(' '); }
        if(def && textOf('definition')!==def) ok = false;
        if(ind && textOf('indicator')!==ind) ok = false;
        if(fod && textOf('fodder')!==fod) ok = false;
        fb.textContent = ok ? 'בול! סימנת נכון.' : 'כדאי לבדוק שוב את הסימון.';
      }
    });
  })();
  </script>
  <?php return ob_get_clean();
});

// 2) Pick manipulation(s): [hb_picker clue="..." options="anagram,hidden,homophone" answer="hidden"]
add_shortcode('hb_picker', function($atts){
  $a = shortcode_atts(['clue'=>'','options'=>'','answer'=>''], $atts);
  $opts = array_filter(array_map('trim', explode(',', $a['options'])));
  ob_start(); ?>
  <div class="hb-picker" data-answer="<?php echo esc_attr($a['answer']); ?>">
    <div class="hb-q" style="margin-bottom:6px"><?php echo esc_html($a['clue']); ?></div>
    <div class="hb-ops" style="display:flex;gap:10px;flex-wrap:wrap">
      <?php foreach($opts as $op): ?>
      <label style="display:flex;gap:6px;align-items:center">
        <input type="checkbox" value="<?php echo esc_attr($op); ?>"> <?php echo esc_html($op); ?>
      </label>
      <?php endforeach; ?>
    </div>
    <button class="hb-verify" style="margin-top:8px;padding:6px 10px;border:1px solid #ddd;border-radius:8px;background:#f7f7f7;cursor:pointer">בדוק</button>
    <div class="hb-out" hidden style="margin-top:6px"></div>
  </div>
  <script>
  (function(){
    if(window.hbPickerInit) return; window.hbPickerInit = true;
    document.addEventListener('click', function(e){
      if(!e.target.classList.contains('hb-verify')) return;
      const box=e.target.closest('.hb-picker'); const ans=(box.dataset.answer||'').split('|').sort().join(',');
      const sel=[...box.querySelectorAll('input:checked')].map(i=>i.value).sort().join(',');
      const out=box.querySelector('.hb-out'); out.hidden=false;
      out.textContent = sel===ans ? 'נכון!' : 'עדיין לא — נסו שוב.';
    });
  })();
  </script>
  <?php return ob_get_clean();
});

// 3) Assemble (type-and-build): [hb_assemble clue="..." letters="א,ב,ג,ד" answer="..."]
add_shortcode('hb_assemble', function($atts){
  $a = shortcode_atts(['clue'=>'','letters'=>'','answer'=>''], $atts);
  $letters = array_filter(array_map('trim', explode(',', $a['letters'])));
  ob_start(); ?>
  <div class="hb-assemble" data-answer="<?php echo esc_attr($a['answer']); ?>">
    <div class="hb-q" style="margin-bottom:6px"><?php echo esc_html($a['clue']); ?></div>
    <div class="hb-letters">
      <?php foreach($letters as $L): ?>
        <button type="button" class="hb-letter"><?php echo esc_html($L); ?></button>
      <?php endforeach; ?>
    </div>
    <input class="hb-input" placeholder="הקלד/י או בנה/י מהאותיות" style="padding:8px;border:1px solid #ddd;border-radius:8px;width:100%;max-width:420px">
    <button class="hb-verify2" style="margin-top:8px;padding:6px 10px;border:1px solid #ddd;border-radius:8px;background:#f7f7f7;cursor:pointer">בדוק</button>
    <div class="hb-out" hidden style="margin-top:6px"></div>
  </div>
  <script>
  (function(){
    if(window.hbAssembleInit) return; window.hbAssembleInit = true;
    document.addEventListener('click', function(e){
      if(e.target.classList.contains('hb-letter')){
        const w=e.target.closest('.hb-assemble'); w.querySelector('.hb-input').value += e.target.textContent;
      }
      if(e.target.classList.contains('hb-verify2')){
        const w=e.target.closest('.hb-assemble'); const a=(w.dataset.answer||'').replace(/\s/g,'');
        const v=w.querySelector('.hb-input').value.replace(/\s/g,''); const out=w.querySelector('.hb-out');
        out.hidden=false; out.textContent = v===a ? 'בול!' : 'עוד ניסיון :)';
      }
    });
  })();
  </script>
  <?php return ob_get_clean();
});

// 4) Wrapper: [hb_ex id="ex1" title="תרגול 1"] ...shortcodes... [/hb_ex]
add_shortcode('hb_ex', function($atts, $content=''){
  $a = shortcode_atts(['id'=> '', 'title'=> ''], $atts);
  $id = preg_replace('/[^A-Za-z0-9_-]/','', $a['id']);
  if(!$id) $id = 'ex-'.wp_generate_uuid4();
  ob_start(); ?>
  <div class="hb-ex" data-ex="<?php echo esc_attr($id); ?>" hidden>
    <?php if(!empty($a['title'])): ?>
      <div class="hb-head"><?php echo esc_html($a['title']); ?></div>
    <?php endif; ?>
    <?php echo do_shortcode($content); ?>
    <button class="hb-resume" data-resume>המשך וידאו</button>
  </div>
  <?php return ob_get_clean();
});

