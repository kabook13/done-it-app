<?php
/**
 * Plugin Name: HB Video Tutor (+ Admin Builder)
 * Description: וידאו עם עצירות מוגדרות + אוברליי ("רוצה לנסות לפתור" / "המשך בסרטון") שפותח תרגול תואם. כולל דף אדמין לבניית שורטקוד ללא קוד. שימוש ידני: [hb_tutor src="..." cues="0:45@ex1,2:10@ex2"] ... [hb_ex id="ex1" title="..." clue="ההגדרה..."] תוכן ... [/hb_ex] ...
 * Version: 1.1.0
 * Author: Higayon Barie
 */
if (!defined('ABSPATH')) exit;

/* =========================
   Helpers
========================= */
function hbvt_to_embed_url($src){
  $src = trim($src);
  if (preg_match('~\.(mp4|webm|ogg)(\?.*)?$~i', $src)) return $src; // local files

  $id = '';
  if (preg_match('~https?://(?:www\.)?youtu\.be/([A-Za-z0-9_-]{6,})~', $src, $m)) { $id = $m[1]; }
  elseif (preg_match('~https?://(?:www\.)?youtube\.com/watch\?[^#]*v=([A-Za-z0-9_-]{6,})~', $src, $m)) { $id = $m[1]; }
  elseif (preg_match('~https?://(?:www\.)?youtube\.com/shorts/([A-Za-z0-9_-]{6,})~', $src, $m)) { $id = $m[1]; }

  if ($id){
    $params = ['enablejsapi'=>'1','playsinline'=>'1','rel'=>'0','modestbranding'=>'1'];
    return 'https://www.youtube.com/embed/'.$id.'?'.http_build_query($params);
  }
  return $src;
}

/* =========================
   Assets (CSS + JS Frontend)
========================= */
add_action('wp_enqueue_scripts', function(){
  // CSS – נקי ויציב
  wp_register_style('hb-video-tutor', false, [], null);
  wp_add_inline_style('hb-video-tutor', '
    .hb-tutor{max-width:1100px;margin:0 auto}
    .hbvt-video-wrap{position:relative;width:100%;background:#000;border-radius:16px;overflow:hidden;box-shadow:0 10px 40px rgba(0,0,0,.12)}
    .hbvt-aspect{position:relative;padding-top:56.25%;height:0}
    .hbvt-aspect iframe,.hbvt-aspect video{position:absolute;inset:0;width:100%;height:100%;border:0;display:block}

    /* Overlay */
    .hbvt-overlay{position:absolute;inset:0;display:none;align-items:center;justify-content:center;background:linear-gradient(180deg,rgba(0,0,0,.36),rgba(0,0,0,.48));z-index:5}
    .hbvt-card{width:min(92%,560px);background:#fff;color:#111;border-radius:18px;padding:22px;text-align:center;border:1px solid #ececf1;box-shadow:0 20px 60px rgba(0,0,0,.25)}
    .hbvt-card h3{margin:0 0 8px;font-size:1.35rem;font-weight:800}
    .hbvt-card p{margin:0 0 16px;font-size:1.05rem;opacity:.92}
    .hbvt-actions{display:flex;gap:12px;justify-content:center;flex-wrap:wrap}
    .hbvt-btn{min-width:200px;padding:12px 18px;border-radius:12px;cursor:pointer;font-weight:800;border:1px solid #d0d5dd;box-shadow:0 2px 6px rgba(16,24,40,.12)}
    .hbvt-primary{background:#111827;color:#fff}
    .hbvt-ghost{background:#fff;color:#111}

    /* Exercises */
    .hbvt-exercises{margin:22px 0 0}
    .hbvt-ex{display:none;margin:18px 0;position:relative}
    .hbvt-ex.active{display:block}
    .hbvt-ex-title{text-align:center;margin:8px 0 6px;font-weight:800}
    .hbvt-ex-body{position:relative}

    /* CLUE big (משתמש ע"י attribute clue=...) */
    .hbvt-clue-wrap{ text-align:center; margin:4px 0 12px }
    .hbvt-clue{ display:inline-block; font-size:clamp(22px,3.2vw,32px); font-weight:800; line-height:1.35; padding:10px 18px; border-radius:14px; background:#fff; border:1px dashed #e5e7eb }

    /* Continue button inside exercise */
    .hbvt-ex{position:relative}
    .hbvt-ex-footer{position:relative;min-height:0}
    .hbvt-fab{position:absolute;left:16px;bottom:16px;display:none;z-index:5}
    .hbvt-fab button{
      padding:10px 14px;border-radius:12px;background:#f3f4f6;color:#111827;
      border:1px solid #d1d5db;box-shadow:0 4px 12px rgba(0,0,0,.08);font-weight:700;cursor:pointer
    }
    /* Desktop offset: ~1cm right, ~2cm up */
    @media (min-width:1024px){
      .hbvt-ex .hbvt-fab{ left:38px; bottom:76px }
    }

    @media (max-width:768px){
      .hbvt-card{padding:18px}
      .hbvt-btn{min-width:180px;padding:11px 16px}
      .hbvt-card h3{font-size:1.25rem}
      .hbvt-card p{font-size:1rem}
      .hbvt-fab button{padding:10px 13px;border-radius:10px}
    }
  ');
  wp_enqueue_style('hb-video-tutor');

  // JS – מינימלי ויציב
  wp_register_script('hb-video-tutor', false, [], null, true);
  wp_add_inline_script('hb-video-tutor', '
  (function(){
    function parseTime(t){
      if(!t) return null;
      if(/^\d+$/.test(t)) return parseInt(t,10);
      const p=t.split(":").map(n=>parseInt(n,10));
      if(p.some(isNaN)) return null;
      if(p.length===2) return p[0]*60+p[1];
      if(p.length===3) return p[0]*3600+p[1]*60+p[2];
      return null;
    }
    function smoothScrollTo(el){
      try{ el.scrollIntoView({behavior:"smooth", block:"center"}); }
      catch(_){ el.scrollIntoView(true); }
    }

    const tutors=document.querySelectorAll(".hb-tutor[data-cues]");
    let needYT=false;
    tutors.forEach(t=>{ const ifr=t.querySelector("iframe"); if(ifr && /youtube\.com|youtu\.be/.test(ifr.src)) needYT=true; });
    if(needYT && !(window.YT && window.YT.Player)){
      const s=document.createElement("script");
      s.src="https://www.youtube.com/iframe_api";
      document.head.appendChild(s);
    }

    const ST=new WeakMap();

    function setup(tutor){
      const vid=tutor.querySelector("iframe, video");
      const overlay=tutor.querySelector(".hbvt-overlay");
      const btnSolve=tutor.querySelector(".hbvt-btn[data-action=\'solve\']");
      const btnCont=tutor.querySelector(".hbvt-btn[data-action=\'continue\']");
      const fab=tutor.querySelector(".hbvt-fab");
      const exWrap=tutor.querySelector(".hbvt-exercises");

      const cues=(tutor.dataset.cues||"").split(",").map(s=>s.trim()).filter(Boolean).map(s=>{
        const parts=s.split("@"); return {t:parseTime(parts[0]), id:(parts[1]||"").trim()};
      }).filter(x=>x.t!==null).sort((a,b)=>a.t-b.t);

      function showOverlay(){ overlay.style.display="flex"; }
      function hideOverlay(){ overlay.style.display="none"; }
      function hideFab(){ if(fab) fab.style.display="none"; }

      function openExercise(id){
        if(!exWrap) return;
        exWrap.querySelectorAll(".hbvt-ex").forEach(x=>x.classList.remove("active"));
        const trg=exWrap.querySelector(".hbvt-ex[data-ex-id=\'"+CSS.escape(id)+"\']");
        if(!trg) return;
        trg.classList.add("active");

        // continue button inside section footer
        const footer = trg.querySelector(".hbvt-ex-footer") || trg;
        if(fab){ footer.appendChild(fab); fab.style.display="block"; }

        smoothScrollTo(trg);
      }

      function resumeVideoAndScroll(){
        hideOverlay(); hideFab();
        const P=ST.get(tutor);
        const videoWrap=tutor.querySelector(".hbvt-video-wrap");
        if(P){
          if(P.type==="yt" && P.player && P.player.playVideo) try{ P.player.playVideo(); }catch(_){}
          if(P.type==="html5" && P.video) try{ P.video.play(); }catch(_){}
        }
        if(videoWrap) smoothScrollTo(videoWrap);
      }

      btnSolve && btnSolve.addEventListener("click", ()=>{
        const cur=ST.get(tutor)?.currentCueId;
        hideOverlay();
        if(cur) openExercise(cur);
      });
      btnCont && btnCont.addEventListener("click", resumeVideoAndScroll);
      fab && fab.addEventListener("click", resumeVideoAndScroll);

      // HTML5 video
      function attachHTML5(videoEl){
        const state={type:"html5", video:videoEl, i:0, currentCueId:null};
        ST.set(tutor,state);
        function tick(){
          if(!cues.length) return;
          const ct=videoEl.currentTime||0;
          while(state.i<cues.length && ct>=cues[state.i].t-0.12){
            videoEl.pause();
            state.currentCueId=cues[state.i].id;
            showOverlay();
            state.i++;
            break;
          }
          if(!videoEl.paused) requestAnimationFrame(tick);
        }
        videoEl.addEventListener("play", ()=> requestAnimationFrame(tick));
      }

      // YouTube
      function attachYT(iframeEl){
        function init(){
          const player=new YT.Player(iframeEl,{
            events:{
              "onStateChange":function(e){
                const st=e.data;
                const state=ST.get(tutor)||{type:"yt", player:player, i:0, currentCueId:null};
                state.type="yt"; state.player=player; ST.set(tutor,state);
                if(st===YT.PlayerState.PLAYING){
                  loop();
                }
                function loop(){
                  let ct=0; try{ ct=player.getCurrentTime()||0; }catch(_){}
                  while(state.i<cues.length && ct>=cues[state.i].t-0.12){
                    try{ player.pauseVideo(); }catch(_){}
                    state.currentCueId=cues[state.i].id;
                    showOverlay();
                    state.i++;
                    break;
                  }
                  if(player.getPlayerState && player.getPlayerState()===YT.PlayerState.PLAYING) requestAnimationFrame(loop);
                }
              }
            }
          });
        }
        if(window.YT && window.YT.Player) init();
        else {
          const prev=window.onYouTubeIframeAPIReady;
          window.onYouTubeIframeAPIReady=function(){ if(typeof prev==="function") try{prev();}catch(_){ } init(); };
        }
      }

      if(!vid) return;
      if(vid.tagName==="VIDEO"){ attachHTML5(vid); }
      else if(vid.tagName==="IFRAME"){ attachYT(vid); }
      ST.set(tutor, ST.get(tutor) || {type:null, i:0, currentCueId:null});
    }

    document.querySelectorAll(".hb-tutor").forEach(function(t){
      // נשמור currentCueId לכל עצירה
      const cues=(t.dataset.cues||"").split(",").map(s=>s.trim()).filter(Boolean).map(function(s){
        const parts=s.split("@"); return {t:parseTime(parts[0]), id:(parts[1]||"").trim()};
      }).filter(function(x){return x.t!==null;}).sort(function(a,b){return a.t-b.t;});
      t.__hbvtCues = cues;
      setup(t);
    });

    // מסמן currentCueId בכל עצירה (לשימוש כפתור "רוצה לפתור")
    function markCueOnTick(tutor, time){
      const cues=tutor.__hbvtCues||[];
      const st= (tutor.__hbvtState = tutor.__hbvtState || {i:0, currentCueId:null});
      for(; st.i<cues.length; st.i++){
        if(time>=cues[st.i].t-0.12){
          st.currentCueId = cues[st.i].id;
          break;
        }else{ break; }
      }
    }
    // השמטנו קריאה כי כבר מטופל בלולאות למעלה—שומרים את המימוש הזה אם נרצה עתידית.
  })();
  ');
  wp_enqueue_script('hb-video-tutor');
});

/* =========================
   Shortcodes
========================= */

/*
שימוש ידני:
[hb_tutor src="https://www.youtube.com/watch?v=XXXX" cues="0:45@ex1,2:10@ex2"]
  [hb_ex id="ex1" title="תרגול 1 – חלוקה" clue="ציפור מעורבבת עוף (4)"]
    [hb_guide clue="ציפור מעורבבת עוף (4)" split="2" p1="ציפור" p2="מעורבבת עוף" plain="1" answer="תשבץ"]
  [/hb_ex]
  [hb_ex id="ex2" title="תרגול 2 – פתרון" clue="מורה מחביא רמז (3)"]
    [hb_guide clue="מורה מחביא רמז (3)" split="2" p1="מורה" p2="מחביא רמז" answer="..."]
  [/hb_ex]
[/hb_tutor]
*/
add_shortcode('hb_tutor', function($atts, $content=null){
  $a = shortcode_atts([
    'src'  => '',
    'cues' => '',
  ], $atts, 'hb_tutor');

  $raw = trim($a['src']);
  $src = hbvt_to_embed_url($raw);
  $is_mp4 = preg_match('~\.(mp4|webm|ogg)(\?.*)?$~i', $src);

  ob_start(); ?>
  <div class="hb-tutor" data-cues="<?php echo esc_attr($a['cues']); ?>">
    <div class="hbvt-video-wrap">
      <div class="hbvt-aspect">
        <?php if ($is_mp4): ?>
          <video controls preload="metadata" playsinline src="<?php echo esc_url($src); ?>"></video>
        <?php else: ?>
          <iframe src="<?php echo esc_url($src); ?>" allow="autoplay; encrypted-media; picture-in-picture" referrerpolicy="strict-origin-when-cross-origin" allowfullscreen></iframe>
        <?php endif; ?>
      </div>
      <div class="hbvt-overlay">
        <div class="hbvt-card" role="dialog" aria-modal="true" aria-label="עצירה">
          <h3>עצרנו כאן</h3>
          <p>מה תרצו לעשות?</p>
          <div class="hbvt-actions">
            <button class="hbvt-btn hbvt-primary" data-action="solve">רוצה לנסות לפתור</button>
            <button class="hbvt-btn hbvt-ghost" data-action="continue">המשך בסרטון</button>
          </div>
        </div>
      </div>
    </div>

    <!-- כפתור המשך פנימי – ממוקם בסקשן הפעיל -->
    <div class="hbvt-fab"><button type="button" title="המשך בסרטון">המשך בסרטון</button></div>

    <div class="hbvt-exercises">
      <?php echo do_shortcode($content); ?>
    </div>
  </div>
  <?php
  return ob_get_clean();
});

/* [hb_ex id="..." title="..." clue="טקסט ההגדרה"] ... [/hb_ex] */
add_shortcode('hb_ex', function($atts, $content=null){
  $a = shortcode_atts([
    'id' => '',
    'title' => '',
    'clue' => '', // << חדש: מציג אוטומטית כותרת ההגדרה גדולה
  ], $atts, 'hb_ex');

  $id = sanitize_html_class($a['id']);
  $clue = trim($a['clue']);

  ob_start(); ?>
  <section class="hbvt-ex" data-ex-id="<?php echo esc_attr($id); ?>" id="<?php echo esc_attr($id ?: ''); ?>">
    <?php if ($a['title']): ?>
      <h3 class="hbvt-ex-title"><?php echo esc_html($a['title']); ?></h3>
    <?php endif; ?>

    <?php if ($clue !== ''): ?>
      <div class="hbvt-clue-wrap"><span class="hbvt-clue"><?php echo esc_html($clue); ?></span></div>
    <?php endif; ?>

    <div class="hbvt-ex-body">
      <?php echo do_shortcode($content); ?>
    </div>
    <div class="hbvt-ex-footer"></div>
  </section>
  <?php
  return ob_get_clean();
});

/* =========================
   Admin – “HB Tutor” (Builder)
========================= */
add_action('admin_menu', function(){
  add_menu_page(
    'HB Tutor', 'HB Tutor', 'edit_posts', 'hb-tutor-builder', 'hbvt_admin_builder_page',
    'dashicons-controls-play', 58
  );
});

function hbvt_admin_builder_page(){
  if (!current_user_can('edit_posts')) return;
  ?>
  <div class="wrap" dir="rtl">
    <h1>HB Tutor – בניית שורטקוד בלי קוד</h1>
    <p>מלאו את הפרטים ולחצו “יצירת קוד”. הדביקו את השורטקוד בעמוד אלמנטור/פוסט.</p>

    <style>
      .hbvt-admin .row{display:grid;grid-template-columns:180px 1fr;gap:10px;align-items:center;margin:8px 0}
      .hbvt-admin textarea,.hbvt-admin input[type=text]{width:100%}
      .hbvt-admin .box{background:#fff;border:1px solid #e5e7eb;border-radius:10px;padding:14px;margin:14px 0}
      .hbvt-admin .muted{opacity:.8}
      .hbvt-admin pre{white-space:pre-wrap;background:#0b1020;color:#e6edf3;padding:14px;border-radius:8px;direction:ltr}
      .hbvt-admin .ex-list .ex{border:1px dashed #d8dee4;padding:10px;border-radius:8px;margin:10px 0}
      .hbvt-admin button.button-primary{height:auto;padding:8px 14px;border-radius:8px}
      .hbvt-admin small code{background:#f3f4f6;padding:0 4px;border-radius:4px}
    </style>

    <div class="hbvt-admin">
      <div class="box">
        <div class="row">
          <label>קישור וידאו (YouTube/MP4):</label>
          <input type="text" id="hbvt-src" placeholder="https://www.youtube.com/watch?v=XXXXX">
        </div>
        <div class="row">
          <label>עצירות (cues):</label>
          <input type="text" id="hbvt-cues" placeholder='לדוגמה: 0:45@ex1, 2:10@ex2'>
        </div>
        <p class="muted">פורמט: <code>דקה:שניה@מזהה-תרגיל</code> מופרד בפסיקים. לדוגמה <code>45@ex1</code> או <code>1:30@ex2</code>.</p>
      </div>

      <div class="box">
        <h2 style="margin-top:0">תרגילים (Exercises)</h2>
        <div id="hbvt-ex-list" class="ex-list"></div>
        <button type="button" class="button" id="hbvt-add-ex">+ הוסף תרגיל</button>
        <p class="muted">לתואם, דאגו שמזהה התרגיל (ID) זהה למה שמופיע בעמודת ה-<em>cues</em> למעלה.</p>
      </div>

      <p>
        <button type="button" class="button button-primary" id="hbvt-build">יצירת קוד</button>
      </p>

      <div class="box">
        <h2 style="margin-top:0">תוצאה</h2>
        <pre id="hbvt-output" dir="ltr"></pre>
      </div>
    </div>

    <script>
    (function(){
      const exList = document.getElementById('hbvt-ex-list');
      const addBtn = document.getElementById('hbvt-add-ex');
      const buildBtn = document.getElementById('hbvt-build');
      const out = document.getElementById('hbvt-output');

      function exItem(data){
        const wrap=document.createElement('div');
        wrap.className='ex';
        wrap.innerHTML = `
          <div class="row">
            <label>מזהה תרגיל (ID):</label>
            <input type="text" class="ex-id" placeholder="ex1" value="${data?.id||''}">
          </div>
          <div class="row">
            <label>כותרת תרגיל:</label>
            <input type="text" class="ex-title" placeholder="תרגול 1 – ..." value="${data?.title||''}">
          </div>
          <div class="row">
            <label>ההגדרה (clue):</label>
            <input type="text" class="ex-clue" placeholder="ציפור מעורבבת עוף (4)" value="${data?.clue||''}">
          </div>
          <div class="row">
            <label>תוכן בתוך התרגיל:</label>
            <textarea class="ex-content" rows="5" placeholder='למשל:
[hb_guide clue="ציפור מעורבבת עוף (4)" split="2" p1="ציפור" p2="מעורבבת עוף" answer="..."]'>${data?.content||''}</textarea>
          </div>
          <div class="row">
            <label></label>
            <button type="button" class="button hbvt-del">מחק</button>
          </div>
        `;
        wrap.querySelector('.hbvt-del').addEventListener('click', ()=> wrap.remove());
        return wrap;
      }

      addBtn.addEventListener('click', ()=> exList.appendChild(exItem({})));

      buildBtn.addEventListener('click', ()=>{
        const src = document.getElementById('hbvt-src').value.trim();
        const cues = document.getElementById('hbvt-cues').value.trim();
        let code = `[hb_tutor src="${src}" cues="${cues}"]\n`;

        exList.querySelectorAll('.ex').forEach(ex=>{
          const id = ex.querySelector('.ex-id').value.trim();
          const title = ex.querySelector('.ex-title').value.trim();
          const clue = ex.querySelector('.ex-clue').value.trim();
          const content = ex.querySelector('.ex-content').value.trim();
          code += `  [hb_ex id="${id}" title="${title.replace(/"/g,'&quot;')}" clue="${clue.replace(/"/g,'&quot;')}"]\n`;
          code += content.split('\n').map(l=>'    '+l).join('\n') + '\n';
          code += `  [/hb_ex]\n`;
        });

        code += `[/hb_tutor]`;
        out.textContent = code;
      });

      // התחלה עם פריט אחד לדוגמה
      if(!exList.children.length) exList.appendChild(exItem({id:'ex1', title:'תרגול 1 – חלוקת ההגדרה', clue:'ציפור מעורבבת עוף (4)', content:'[hb_guide clue="ציפור מעורבבת עוף (4)" split="2" p1="ציפור" p2="מעורבבת עוף" answer="תשבץ"]'}));
    })();
    </script>
  </div>
  <?php
}

