<?php
/**
 * Plugin Name: HB Video Cues (YouTube)
 * Description: YouTube player with timed interactive pauses. Use: [hb_yt src="https://www.youtube.com/watch?v=VIDEO_ID" cues="75:ex1, 180:ex2"] ...interactive blocks... [/hb_yt]
 * Version: 1.0.0
 * Author: Higayon Barie
 */
if (!defined('ABSPATH')) exit;

add_shortcode('hb_yt', function($atts, $content=''){
  $a = shortcode_atts([
    'src' => '',
    'cues' => '',
    'width' => '100%',
    'maxwidth' => '980px',
    'ratio' => '56.25', // 16:9
    'rel' => '0',
    'modestbranding' => '1',
    'controls' => '1',
    'playsinline' => '1',
    'cc' => '0' // 1 to force captions
  ], $atts);

  // Extract video ID from URL or accept raw ID
  $src = trim($a['src']);
  $video_id = '';
  if (preg_match('~(?:youtu\.be/|youtube\.com/(?:watch\?v=|embed/|shorts/))([A-Za-z0-9_-]{6,})~', $src, $m)) {
    $video_id = $m[1];
  } else {
    $video_id = preg_replace('~[^A-Za-z0-9_-]~','',$src);
  }

  $id = 'hby-'.wp_generate_uuid4();
  $cues_list = array_filter(array_map('trim', explode(',', $a['cues'])));
  ob_start(); ?>
  <div class="hby-wrap" id="<?php echo esc_attr($id); ?>" style="max-width:<?php echo esc_attr($a['maxwidth']); ?>;margin:0 auto;">
    <div class="hby-embed" style="position:relative;width:100%;padding-top:<?php echo esc_attr($a['ratio']); ?>%;border-radius:12px;overflow:hidden;background:#000">
      <div id="<?php echo esc_attr($id); ?>-player" style="position:absolute;inset:0;"></div>
    </div>
    <div class="hby-content">
      <?php echo do_shortcode($content); ?>
    </div>
  </div>
  <script>
  (function(){
    const ROOT_ID = '<?php echo esc_js($id); ?>';
    const VIDEO_ID = '<?php echo esc_js($video_id); ?>';
    const PARAMS = {
      rel: <?php echo (int)$a['rel']; ?>,
      modestbranding: <?php echo (int)$a['modestbranding']; ?>,
      controls: <?php echo (int)$a['controls']; ?>,
      playsinline: <?php echo (int)$a['playsinline']; ?>,
      cc_load_policy: <?php echo (int)$a['cc']; ?>,
      enablejsapi: 1
    };
    const CUES = <?php
      $out=[]; foreach($cues_list as $c){
        if(strpos($c,':')!==false){
          list($t,$ex)=array_map('trim',explode(':',$c,2));
          if(is_numeric($t) && $ex) $out[]=['t'=>(float)$t,'id'=>$ex];
        }
      }
      echo wp_json_encode($out);
    ?>;

    // Load YouTube IFrame API once
    if(!window.HB_YT_LOADED){
      var tag = document.createElement('script');
      tag.src = "https://www.youtube.com/iframe_api";
      document.head.appendChild(tag);
      window.HB_YT_LOADED = true;
    }

    const root = document.getElementById(ROOT_ID);
    let player = null, timer = null, fired = {};

    function reveal(exId){
      const el = root.querySelector('[data-ex="'+exId+'"]');
      if(!el) return;
      el.hidden = false;
      el.scrollIntoView({behavior:'smooth', block:'center'});
      const btn = el.querySelector('[data-resume]');
      if(btn){
        btn.addEventListener('click', function resumeOnce(){
          if(player) player.playVideo();
          btn.removeEventListener('click', resumeOnce);
        });
      }
    }

    function onPlayerReady(){
      // Poll current time for cues (more reliable across browsers)
      timer = setInterval(function(){
        if(!player || typeof player.getCurrentTime!=='function') return;
        var t = Math.floor(player.getCurrentTime());
        CUES.forEach(function(c){
          if(!fired[c.t] && t>=c.t){
            fired[c.t]=true;
            player.pauseVideo();
            reveal(c.id);
          }
        });
      }, 300);
    }

    function createPlayer(){
      player = new YT.Player(ROOT_ID+'-player', {
        videoId: VIDEO_ID,
        playerVars: PARAMS,
        events: {
          'onReady': onPlayerReady
        }
      });
    }

    // YouTube API callback
    if(typeof YT !== 'undefined' && YT.Player){
      createPlayer();
    } else {
      window.onYouTubeIframeAPIReady = (function(prev){
        return function(){
          if(typeof prev === 'function') prev();
          createPlayer();
        };
      })(window.onYouTubeIframeAPIReady);
    }
  })();
  </script>
  <style>
    .hb-ex{border:1px solid #eee;padding:12px;border-radius:12px;margin:14px 0;background:#fff}
    .hb-ex[hidden]{display:none!important}
    .hb-ex .hb-head{font-weight:700;margin-bottom:6px}
    .hb-resume{margin-top:10px;padding:8px 14px;border:1px solid #ddd;border-radius:10px;background:#f6f6f6;cursor:pointer}
    @media (max-width:640px){
      .hb-ex{padding:10px;border-radius:10px}
      .hb-resume{width:100%}
    }
  </style>
  <?php return ob_get_clean();
});
