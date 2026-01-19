<?php
/**
 * Plugin Name: HB Cue
 * Description: Single, self-contained YouTube cue + timed-steps system. Shortcodes: [hb_cue src="" cues="45:ex1,130:ex2"] ... [hb_step id="ex1"]content[/hb_step].
 * Version: 1.0.0
 * Author: Higayon Barie
 */
if (!defined('ABSPATH')) exit;

add_shortcode('hb_cue', function($atts, $content=''){
  $a = shortcode_atts([ 'src'=>'', 'cues'=>'', 'maxwidth'=>'980px', 'ratio'=>'56.25' ], $atts, 'hb_cue');

  $src = trim($a['src']); $video_id='';
  if (preg_match('~(?:youtu\.be/|youtube\.com/(?:watch\?v=|embed/|shorts/))([A-Za-z0-9_-]{6,})~', $src, $m)) $video_id = $m[1];
  else $video_id = preg_replace('~[^A-Za-z0-9_-]~','',$src);

  $pairs = array_filter(array_map('trim', explode(',', $a['cues']))); $cues=[];
  foreach ($pairs as $p){ if (strpos($p,':')!==false){ list($t,$id)=array_map('trim',explode(':',$p,2)); if (is_numeric($t) && $id) $cues[]=['t'=>(float)$t,'id'=>$id]; } }

  $uid = 'hbcue-'.wp_generate_uuid4();
  ob_start(); ?>
  <div class="hbcue-wrap" id="<?php echo esc_attr($uid); ?>" style="max-width:<?php echo esc_attr($a['maxwidth']); ?>;margin:0 auto;">
    <div class="hbcue-embed" style="position:relative;width:100%;padding-top:<?php echo esc_attr($a['ratio']); ?>%;background:#000;border-radius:12px;overflow:hidden;">
      <div id="<?php echo esc_attr($uid); ?>-player" style="position:absolute;inset:0;"></div>
    </div>
    <div class="hbcue-steps"><?php echo do_shortcode($content); ?></div>
  </div>
  <style>
    .hbcue-step[hidden]{display:none!important}
    .hbcue-step{border:1px solid #eee;border-radius:12px;padding:12px;background:#fff;margin:14px 0}
    .hbcue-title{font-weight:700;margin-bottom:6px}
    .hbcue-btn{margin-top:10px;padding:8px 14px;border:1px solid #ddd;border-radius:10px;background:#f6f6f6;cursor:pointer}
  </style>
  <script>
  (function(){
    const WRAP   = document.getElementById('<?php echo esc_js($uid); ?>');
    const VIDEO  = '<?php echo esc_js($video_id); ?>';
    const CUES   = <?php echo wp_json_encode($cues); ?>;
    let player=null, fired={}, timer=null;

    if(!window.HB_YT_LOADED){
      const tag = document.createElement('script');
      tag.src = "https://www.youtube.com/iframe_api";
      document.head.appendChild(tag);
      window.HB_YT_LOADED = true;
    }

    function revealStep(stepId){
      WRAP.querySelectorAll('.hbcue-step').forEach(s=>s.hidden=true);
      const box = WRAP.querySelector('[data-ex="'+stepId+'"]');
      if(!box){ console.warn('HB_CUE: step not found', stepId); return; }
      box.hidden = false;
      try{ box.scrollIntoView({behavior:'smooth', block:'center'});}catch(e){}
      const resume = box.querySelector('[data-resume]');
      if(resume){
        const once = function(){ try{ player && player.playVideo(); }catch(e){} resume.removeEventListener('click', once); };
        resume.addEventListener('click', once);
      }
    }

    function onReady(){
      WRAP.querySelectorAll('.hbcue-step').forEach(s=>s.hidden=true);
      setInterval(function(){
        if(!player || typeof player.getCurrentTime!=='function') return;
        const t = Math.floor(player.getCurrentTime());
        CUES.forEach(c=>{
          if(!fired[c.t] && t>=c.t){
            fired[c.t]=true; try{ player.pauseVideo(); }catch(e){}
            revealStep(c.id);
          }
        });
      }, 300);
    }

    function createPlayer(){
      try{
        player = new YT.Player('<?php echo esc_js($uid); ?>-player', {
          videoId: VIDEO,
          playerVars: {rel:0, modestbranding:1, controls:1, playsinline:1, enablejsapi:1},
          events: { onReady: onReady }
        });
      }catch(e){ console.error('HB_CUE init error', e); }
    }

    if(typeof YT!=='undefined' && YT.Player){ createPlayer(); }
    else { window.onYouTubeIframeAPIReady = (function(prev){ return function(){ if(typeof prev==='function') prev(); createPlayer(); }; })(window.onYouTubeIframeAPIReady); }
  })();
  </script>
  <?php return ob_get_clean();
});

add_shortcode('hb_step', function($atts, $content=''){
  $a = shortcode_atts(['id'=>'' ], $atts, 'hb_step');
  $id = preg_replace('/[^A-Za-z0-9_-]/','', $a['id']); if(!$id) $id = 'ex-'.wp_generate_uuid4();
  ob_start(); ?>
  <div class="hbcue-step" data-ex="<?php echo esc_attr($id); ?>" hidden>
    <?php echo do_shortcode($content); ?>
    <button class="hbcue-btn" data-resume>המשך וידאו</button>
  </div>
  <?php return ob_get_clean();
});
