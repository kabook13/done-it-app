<?php
/**
 * Plugin Name: HB Logic Sheet (גיליון הגדרות)
 * Description: שורטקוד ליצירת גיליון הגדרות. מזינים לכל שיעור בתיבת מטה: "הגדרה | פתרון | הסבר". שימוש: [hb_logic_sheet title="שם השיעור"].
 * Version: 1.1.0
 * Author: Higayon Bari
 */

if (!defined('ABSPATH')) exit;

/* ---------- 1) תיבת מטה להזנת הגדרות לכל שיעור ---------- */
add_action('add_meta_boxes', function(){
  add_meta_box(
    'hb_ls_box',
    'HB – הגדרות לשיעור (שורה לכל הגדרה)',
    function($post){
      $val = get_post_meta($post->ID, '_hb_ls_items', true);
      echo '<p style="margin:6px 0 10px">הזינו בכל שורה: <code>הגדרה | פתרון | הסבר</code>. ההסבר לא חובה.</p>';
      echo '<textarea name="hb_ls_items" style="width:100%;min-height:220px;direction:rtl" placeholder="דוגמה:
בלתי אפשרי כשאת לא פה (ש) (4) | אינך | בלתי אפשרי=אין איך (שמיעה) ⇒ אינך; כשאת לא פה=אינך
מה שאמור לקרות עם כיסוי (5) | מצופה | מה שאמור לקרות=מצופה; עם כיסוי=מצופה
סופר ושר (ש&quot;מ) (3) | ורן | ז׳ול ורן; נדרש שם משפחה (ש&quot;מ)">
'.esc_textarea($val).'</textarea>';
    },
    null, 'normal', 'default'
  );
});
add_action('save_post', function($post_id){
  if (isset($_POST['hb_ls_items'])){
    $txt = wp_kses_post(str_replace(["\r\n","\r"], "\n", (string)$_POST['hb_ls_items']));
    update_post_meta($post_id, '_hb_ls_items', $txt);
  }
});

/* ---------- 2) עזר: ניקוי תוכן ופרסור "צינורות" ---------- */
function hb_ls_clean_json($raw){
  $raw = trim((string)$raw);
  $raw = preg_replace('~<\s*br\s*/?\s*>~i', "\n", $raw);
  $raw = preg_replace('~</?p[^>]*>~i', "\n", $raw);
  $raw = html_entity_decode($raw, ENT_QUOTES|ENT_HTML5, 'UTF-8');
  $raw = str_replace(["\xE2\x80\x9C","\xE2\x80\x9D","\xE2\x80\x98","\xE2\x80\x99"], ['"','"',"'", "'"], $raw);
  $raw = str_replace("\xC2\xA0", ' ', $raw);
  return trim($raw);
}
function hb_ls_parse_pipes($text){
  $lines = array_filter(array_map('trim', preg_split('~\R+~', (string)$text)));
  $out = [];
  foreach ($lines as $ln){
    if ($ln==='') continue;
    $parts = array_map('trim', explode('|', $ln));
    $item = [
      'clue'    => $parts[0] ?? '',
      'answer'  => $parts[1] ?? '',
      // נשמור הסבר 1 תמיד ב-explain (מחרוזת)
      'explain' => $parts[2] ?? '',
    ];
    // אם קיימת עמודה רביעית – נשמור אותה ב-explain2
    if (isset($parts[3]) && $parts[3] !== '') {
      $item['explain2'] = $parts[3];
    }
    $out[] = $item;
  }
  return $out;
}


/* ---------- 3) השורטקוד ---------- */
add_shortcode('hb_logic_sheet', function ($atts, $content=null) {
  $atts = shortcode_atts([
    'title' => '',
    'numbering' => '1',       // '1' או 'א'
    'show_reveal_all' => 'yes',
    'show_print' => 'yes',
    'src' => '',              // רשות: URL לקובץ JSON/CSV
  ], $atts, 'hb_logic_sheet');

  $uid = 'hb-logic-'.wp_generate_uuid4();
  $items = null;

  // 1) אם יש תוכן פנימי (JSON) – ננסה לפרסר
  if (!empty($content)){
    $json = hb_ls_clean_json($content);
    $items = json_decode($json, true);
    if (!is_array($items)) $items = hb_ls_parse_pipes($json);
  }

  // 2) אם אין תוכן – ננסה src (מדיה)
  if (!$items && !empty($atts['src'])){
    $resp = wp_remote_get($atts['src'], ['timeout'=>10]);
    if (!is_wp_error($resp) && wp_remote_retrieve_response_code($resp)==200){
      $body = hb_ls_clean_json(wp_remote_retrieve_body($resp));
      // תמיכה ב-JSON או CSV/צינורות
      $try = json_decode($body, true);
      $items = is_array($try) ? $try : hb_ls_parse_pipes($body);
    }
  }

  // 3) אם עדיין אין – נטען מהתיבה של הפוסט
  if (!$items){
    $post_id = get_the_ID();
    $raw = get_post_meta($post_id, '_hb_ls_items', true);
    $items = hb_ls_parse_pipes($raw);
  }

  ob_start();
  ?>
  <section id="<?php echo esc_attr($uid); ?>" class="hb-logic-sheet" dir="rtl">
    <?php if ($atts['title']) : ?>
      <h2 class="hb-title"><?php echo esc_html($atts['title']); ?></h2>
    <?php endif; ?>

    <?php if (!$items || !is_array($items) || !count($items)) : ?>
      <div class="hb-error">לא הוזנו הגדרות. ערוך/י את העמוד ומלא/י בתיבת “HB – הגדרות לשיעור”.</div>
    <?php else: ?>
      <div class="hb-actions">
        <?php if ($atts['show_reveal_all'] === 'yes'): ?>
          <button type="button" class="hb-btn hb-toggle-all" data-open="false">הצג את כל הפתרונות וההסברים</button>
        <?php endif; ?>
        <?php if ($atts['show_print'] === 'yes'): ?>
          <button type="button" class="hb-btn hb-print">גרסת הדפסה / PDF</button>
        <?php endif; ?>
      </div>

      <ol class="hb-list" <?php if ($atts['numbering']==='א') echo 'style="list-style-type: hebrew;"'; ?>>
        <?php foreach ($items as $i => $row): 
          $clue = isset($row['clue']) ? $row['clue'] : ($row[0] ?? '');
          $answer = isset($row['answer']) ? $row['answer'] : ($row[1] ?? '');
          $explain = isset($row['explain']) ? $row['explain'] : ($row[2] ?? '');
          $item_id = $uid.'-item-'.$i;
        ?>
        <li class="hb-item" id="<?php echo esc_attr($item_id); ?>">
          <div class="hb-clue">
            <span class="hb-clue-text"><?php echo esc_html($clue); ?></span>
          </div>
          <div class="hb-solution-row">
            <button type="button" class="hb-btn hb-toggle-one" data-target="<?php echo esc_attr($item_id); ?>">הצג פתרון</button>
          </div>
          <div class="hb-solution" hidden>
            <?php if ($answer): ?>
              <div class="hb-answer"><strong>פתרון:</strong> <?php echo esc_html($answer); ?></div>
            <?php endif; ?>
            <?php
  // איסוף הסברים: תומך ב-explain כמחרוזת או כמערך, וב-explain2 כשדה נפרד
  $explain1 = $explain;   // מהשדה הקיים (יכול להיות ריק)
  $explain2 = '';

  // אם explain הגיע כמערך JSON: "explain": ["...", "..."]
  if (isset($row['explain']) && is_array($row['explain'])) {
    $explain1 = $row['explain'][0] ?? '';
    $explain2 = $row['explain'][1] ?? '';
  }

  // אם קיים שדה מפורש explain2 — הוא גובר כ"הסבר שני"
  if (isset($row['explain2']) && is_string($row['explain2'])) {
    $explain2 = $row['explain2'];
  }

  // הדפסה: מציגים רק אם יש לפחות אחד מההסברים
  if ($explain1 || $explain2): ?>
    <div class="hb-explain">
      <?php if ($explain1): ?>
        <div class="hb-explain-line"><strong>הסבר:</strong> <?php echo wp_kses_post(nl2br(esc_html($explain1))); ?></div>
      <?php endif; ?>
      <?php if ($explain2): ?>
        <div class="hb-explain-line"><?php echo wp_kses_post(nl2br(esc_html($explain2))); ?></div>
      <?php endif; ?>
    </div>
<?php endif; ?>


          </div>
        </li>
        <?php endforeach; ?>
      </ol>
    <?php endif; ?>
  </section>

  <style>
    #<?php echo $uid; ?>.hb-logic-sheet{font-family:system-ui,-apple-system,"Segoe UI",Roboto,"Helvetica Neue",Arial,"Noto Sans Hebrew","Rubik",sans-serif;max-width:900px;margin:0 auto;color:#111}
    #<?php echo $uid; ?> .hb-title{font-size:clamp(22px,3vw,30px);margin:0 0 14px;text-align:right}
    #<?php echo $uid; ?> .hb-actions{display:flex;gap:8px;margin:10px 0 16px}
    #<?php echo $uid; ?> .hb-btn{border:1px solid #ccc;padding:8px 12px;border-radius:10px;background:#fff;cursor:pointer}
    #<?php echo $uid; ?> .hb-btn:hover{background:#f7f7f7}
    #<?php echo $uid; ?> .hb-list{margin:0;padding-inline-start:22px}
    #<?php echo $uid; ?> .hb-item{margin:12px 0 18px}
    #<?php echo $uid; ?> .hb-clue{display:flex;gap:8px;align-items:baseline;flex-wrap:wrap}
    #<?php echo $uid; ?> .hb-clue-text{font-size:18px}
    #<?php echo $uid; ?> .hb-solution-row{margin-top:6px}
    #<?php echo $uid; ?> .hb-solution{margin-top:10px;padding:10px 12px;border-radius:12px;background:#fcfcfc;border:1px dashed #ddd}
    #<?php echo $uid; ?> .hb-answer{margin-bottom:6px}
    @media print{
      #<?php echo $uid; ?> .hb-actions,#<?php echo $uid; ?> .hb-toggle-one{display:none!important}
      #<?php echo $uid; ?> .hb-solution{display:block!important}
      #<?php echo $uid; ?> .hb-solution[hidden]{display:block!important}
    }
  </style>

  <script>
  (function(){
    const root=document.getElementById('<?php echo esc_js($uid); ?>'); if(!root) return;
    const toggleAllBtn=root.querySelector('.hb-toggle-all');
    const itemToggles=root.querySelectorAll('.hb-toggle-one');
    function setAll(open){
      root.querySelectorAll('.hb-solution').forEach(el=>{el.hidden=!open;});
      root.querySelectorAll('.hb-toggle-one').forEach(b=>{b.textContent=open?'הסתר פתרון':'הצג פתרון';});
      if(toggleAllBtn){toggleAllBtn.dataset.open=String(open);toggleAllBtn.textContent=open?'הסתר את כל הפתרונות':'הצג את כל הפתרונות וההסברים';}
    }
    itemToggles.forEach(btn=>{
      btn.addEventListener('click', ()=>{
        const li=btn.closest('.hb-item'); const box=li && li.querySelector('.hb-solution'); if(!box) return;
        const show=box.hidden; box.hidden=!show; btn.textContent=show?'הסתר פתרון':'הצג פתרון';
      });
    });
    if(toggleAllBtn){toggleAllBtn.addEventListener('click', ()=>{const open=toggleAllBtn.dataset.open!=='true'; setAll(open);});}
    const printBtn=root.querySelector('.hb-print'); if(printBtn){printBtn.addEventListener('click', ()=>window.print());}
  })();
  </script>
  <?php
  return ob_get_clean();
});

/* ---------- 4) שורטקוד בדיקה (רשות) ---------- */
add_shortcode('hb_logic_ok', function(){ return '<div style="direction:rtl">HB Logic Sheet: <b>OK</b></div>'; });
