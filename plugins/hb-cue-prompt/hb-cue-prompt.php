<?php
/**
 * Plugin Name: HB Cue Prompt
 * Description: Overlay prompt per paused video with two options: “רוצה לנסות לפתור” / “המשך בסרטון”. Detects pause by visible [hb_step]. Supports multiple videos on a page.
 * Version: 1.1.1
 * Author: Higayon Barie
 */
if (!defined('ABSPATH')) exit;

add_action('wp_enqueue_scripts', function () {
  wp_register_style('hb-cue-prompt', false);
  wp_add_inline_style('hb-cue-prompt', '
    .hbcp-player-wrap{position:relative;}
    .hbcp-overlay{
      position:absolute; inset:0; display:none;
      align-items:center; justify-content:center;
      background:linear-gradient(180deg, rgba(0,0,0,.38), rgba(0,0,0,.46));
      z-index:50;
    }
    .hbcp-card{
      width:min(92%,560px); background:#fff; color:#111; border-radius:18px; padding:22px;
      box-shadow:0 20px 60px rgba(0,0,0,.25); text-align:center; border:1px solid #ececf1;
      animation:hbcp-pop .18s ease-out;
    }
    @keyframes hbcp-pop{from{transform:scale(.98);opacity:0}to{transform:scale(1);opacity:1}}
    .hbcp-card h3{margin:0 0 8px; font-size:1.35rem; font-weight:800}
    .hbcp-card p{margin:0 0 16px; font-size:1.05rem; opacity:.9}
    .hbcp-actions{display:flex; gap:12px; justify-content:center; flex-wrap:wrap}
    .hbcp-btn{min-width:200px; padding:12px 18px; border-radius:12px; cursor:pointer; font-weight:800;
      border:1px solid #d0d5dd; box-shadow:0 2px 6px rgba(16,24,40,.12); transition:filter .15s, transform .06s, background .2s, color .2s}
    .hbcp-btn:active{transform:translateY(1px)}
    .hbcp-primary{background:#111827; color:#fff}
    .hbcp-primary:hover{filter:brightness(1.06)}
    .hbcp-ghost{background:#fff; color:#111}
    .hbcp-ghost:hover{background:#f3f4f6}
    .hbcp-hiding-continue .hb-cue-continue button,
    .hbcp-hiding-continue .hb-cue-continue .btn,
    .hbcp-hiding-continue button.hb-cue-continue{visibility:hidden}
    @media (max-width:600px){
      .hbcp-card{padding:18px}
      .hbcp-btn{min-width:180px; padding:11px 16px}
      .hbcp-card h3{font-size:1.25rem}
      .hbcp-card p{font-size:1rem}
    }
  ');
  wp_enqueue_style('hb-cue-prompt');

  wp_register_script('hb-cue-prompt', false, [], null, true);
  wp_add_inline_script('hb-cue-prompt', '
  (function(){
    /* ===== Helpers ===== */
    function isVisible(el){
      if(!el) return false;
      const cs = getComputedStyle(el);
      return cs.display !== "none" && cs.visibility !== "hidden" && el.offsetParent !== null;
    }
    function smoothScrollTo(el){
      try{ el.scrollIntoView({behavior:"smooth", block:"center"}); }
      catch(_){ el.scrollIntoView(true); }
    }
    function findPlayerForStep(step){
      // עולה למעלה עד שמוצא iframe/video קרוב
      let p = step;
      while(p && p !== document.body){
        const iframe = p.querySelector && p.querySelector("iframe");
        const video  = p.querySelector && p.querySelector("video");
        if(iframe || video){ return (iframe || video); }
        p = p.parentElement;
      }
      // fallback: חפש אח לחיפוש
      const iframe = document.querySelector("iframe");
      const video  = document.querySelector("video");
      return (iframe || video) || null;
    }
    function cueContainerFrom(el){
      // מנסה לזהות קונטיינר של HB Cue כדי למצוא כפתור "המשך"
      let p = el;
      while(p && p !== document.body){
        if(p.classList?.contains("hb-cue") || p.classList?.contains("hb_cue") || p.hasAttribute?.("data-hb-cue")) return p;
        p = p.parentElement;
      }
      return el.closest?.(".elementor-widget-video, .elementor-section, .elementor-widget-container") || el.parentElement || null;
    }

    const map = new WeakMap(); // playerParent -> { overlay, stepScope }

    function ensureOverlay(playerEl, stepScope){
      if(!playerEl) return null;
      const parent = playerEl.parentElement;
      if(!parent) return null;
      if(!parent.classList.contains("hbcp-player-wrap")){
        parent.classList.add("hbcp-player-wrap");
        if(getComputedStyle(parent).position === "static"){ parent.style.position = "relative"; }
      }
      if(map.has(parent)) return map.get(parent);

      const ov = document.createElement("div");
      ov.className = "hbcp-overlay";
      ov.innerHTML = `
        <div class="hbcp-card" role="dialog" aria-modal="true" aria-label="עצירה">
          <h3>עצרנו כאן</h3>
          <p>מה תרצו לעשות?</p>
          <div class="hbcp-actions">
            <button class="hbcp-btn hbcp-primary" data-action="solve">רוצה לנסות לפתור</button>
            <button class="hbcp-btn hbcp-ghost" data-action="continue">המשך בסרטון</button>
          </div>
        </div>`;
      parent.appendChild(ov);

      // אירועים
      ov.addEventListener("click", function(e){
        const btn = e.target.closest("button[data-action]");
        if(!btn) return;
        const act = btn.getAttribute("data-action");
        const entry = map.get(parent);
        const cont  = cueContainerFrom(parent);
        const contBtn = cont && cont.querySelector(".hb-cue-continue button, .hb-cue-continue .btn, button.hb-cue-continue");

        if(act === "solve"){
          const visibleStep = entry && entry.stepScope && isVisible(entry.stepScope) ? entry.stepScope : (cont && cont.querySelector(".hb-step"));
          if(visibleStep){ smoothScrollTo(visibleStep); }
          else { smoothScrollTo(cont || parent); }
          hideOverlay(parent);
        }

        if(act === "continue"){
          if(contBtn){ contBtn.click(); }
          else {
            // נגן יוטיוב
            if(playerEl.tagName === "IFRAME" && playerEl.contentWindow){
              try{ playerEl.contentWindow.postMessage(JSON.stringify({event:"command", func:"playVideo", args:[]}), "*"); }catch(_){}
            }
            // נגן וידאו מקומי
            if(playerEl.tagName === "VIDEO"){
              try{ playerEl.play(); }catch(_){}
            }
          }
          hideOverlay(parent);
        }
      });

      const entry = { overlay: ov, stepScope: stepScope || null };
      map.set(parent, entry);
      return entry;
    }

    function showOverlayFor(playerEl, stepEl){
      const entry = ensureOverlay(playerEl, stepEl);
      if(!entry) return;
      entry.stepScope = stepEl || entry.stepScope;
      entry.overlay.style.display = "flex";
      const cont = cueContainerFrom(playerEl);
      if(cont) cont.classList.add("hbcp-hiding-continue");
    }
    function hideOverlay(parentEl){
      const entry = map.get(parentEl);
      if(!entry) return;
      entry.overlay.style.display = "none";
      const cont = cueContainerFrom(parentEl);
      if(cont) cont.classList.remove("hbcp-hiding-continue");
    }

    /* ===== זיהוי עצירה דרך [hb_step] גלוי ===== */
    const seen = new WeakSet();

    function scanSteps(){
      const steps = document.querySelectorAll(".hb-step");
      steps.forEach(step=>{
        const vis = isVisible(step);
        if(vis){
          // מצא את הנגן שקשור ל-step זה
          const player = findPlayerForStep(step);
          if(player){
            const parent = player.parentElement;
            showOverlayFor(player, step);
            seen.add(step);
          }
        } else {
          // אם step זה נסגר – נסה להסתיר אוברליי של הנגן שלו
          if(seen.has(step)){
            const player = findPlayerForStep(step);
            if(player){
              hideOverlay(player.parentElement);
            }
            seen.delete(step);
          }
        }
      });
    }

    // צופה בשינויים בדף
    const obs = new MutationObserver(()=> scanSteps());
    obs.observe(document.documentElement, {childList:true, subtree:true, attributes:true, attributeFilter:["style","class"]});
    document.addEventListener("DOMContentLoaded", scanSteps);
    window.addEventListener("load", scanSteps);
  })();
  ');
  wp_enqueue_script('hb-cue-prompt');
});

