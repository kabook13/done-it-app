<?php
/**
 * Plugin Name: HB Notification Bar
 * Description: Simple, elegant notification bar displayed at bottom after scrolling
 * Version: 1.1.0
 * Author: Higayon Barie
 */
if (!defined('ABSPATH')) exit;

/* ---------------------------------------------------------
 * 1) Enqueue Styles and Scripts
 * --------------------------------------------------------- */
add_action('wp_enqueue_scripts', function() {
  // Only load on home page and crossword pages
  if (!is_front_page() && !is_singular('crossword')) {
    return;
  }
  
  $css = '
  /* HB Notification Bar - Fixed at Bottom */
  .hb-notification-bar {
    position: fixed;
    bottom: -100px;
    right: 0;
    left: 0;
    background: linear-gradient(135deg, #1f2937 0%, #111827 100%);
    color: #fbbf24;
    padding: 20px 60px 20px 20px;
    text-align: center;
    box-shadow: 0 -2px 12px rgba(0, 0, 0, 0.3);
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 12px;
    direction: rtl;
    font-family: "Assistant", system-ui, -apple-system, "Segoe UI", Roboto, "Noto Sans Hebrew", Arial, sans-serif;
    transition: bottom 0.4s cubic-bezier(0.4, 0, 0.2, 1), opacity 0.3s ease-out;
    z-index: 9999;
    margin-bottom: 0;
    border-radius: 0;
  }
  
  .hb-notification-bar.visible {
    bottom: 0;
  }
  
  .hb-notification-bar.hidden {
    display: none;
  }
  
  .hb-notification-bar__content {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    flex-wrap: wrap;
    font-size: 16px;
    font-weight: 500;
    line-height: 1.6;
  }
  
  .hb-notification-bar__text {
    color: #ffffff;
  }
  
  .hb-notification-bar__link {
    color: #fbbf24;
    text-decoration: none;
    font-weight: 700;
    border-bottom: 2px solid #fbbf24;
    transition: all 0.2s ease;
    white-space: nowrap;
    padding-bottom: 2px;
  }
  
  .hb-notification-bar__link:hover {
    color: #fcd34d;
    border-bottom-color: #fcd34d;
  }
  
  .hb-notification-bar__close {
    position: absolute;
    left: 20px;
    top: 50%;
    transform: translateY(-50%);
    background: transparent;
    border: none;
    color: #ffffff;
    font-size: 24px;
    cursor: pointer;
    padding: 0;
    line-height: 1;
    opacity: 0.7;
    transition: opacity 0.2s ease, transform 0.2s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 36px;
    height: 36px;
    border-radius: 50%;
    z-index: 10;
  }
  
  .hb-notification-bar__close:hover {
    opacity: 1;
    background: rgba(255, 255, 255, 0.15);
    transform: translateY(-50%) scale(1.1);
  }
  
  .hb-notification-bar__close:focus {
    outline: 2px solid #fbbf24;
    outline-offset: 2px;
    opacity: 1;
  }
  
  /* Desktop Styling */
  @media (min-width: 769px) {
    .hb-notification-bar {
      padding: 24px 70px 24px 24px;
      font-size: 17px;
    }
    
    .hb-notification-bar__content {
      gap: 12px;
      font-size: 17px;
    }
    
    .hb-notification-bar__link {
      font-size: 17px;
    }
    
    .hb-notification-bar__close {
      width: 40px;
      height: 40px;
      font-size: 26px;
      left: 24px;
    }
  }
  
  /* Responsive */
  @media (max-width: 768px) {
    .hb-notification-bar {
      padding: 16px 50px 16px 16px;
      font-size: 15px;
    }
    
    .hb-notification-bar__content {
      gap: 8px;
      font-size: 15px;
    }
    
    .hb-notification-bar__link {
      font-size: 15px;
    }
    
    .hb-notification-bar__close {
      width: 32px;
      height: 32px;
      font-size: 22px;
      left: 16px;
    }
  }
  
  @media (max-width: 480px) {
    .hb-notification-bar {
      padding: 14px 50px 14px 14px;
      font-size: 14px;
    }
    
    .hb-notification-bar__content {
      flex-direction: column;
      gap: 6px;
      font-size: 14px;
    }
    
    .hb-notification-bar__link {
      font-size: 14px;
    }
  }
  ';
  
  wp_register_style('hb-notification-bar', false);
  wp_add_inline_style('hb-notification-bar', $css);
  wp_enqueue_style('hb-notification-bar');
  
  // Enqueue script
  wp_enqueue_script('hb-notification-bar', false, [], '1.1.0', true);
  wp_add_inline_script('hb-notification-bar', '
  (function() {
    function initNotificationBar() {
      const notificationBar = document.querySelector(".hb-notification-bar");
      if (!notificationBar) return;
      
      const storageKey = "hb_notification_bar_closed";
      const wasClosed = localStorage.getItem(storageKey) === "true";
      
      if (wasClosed) {
        notificationBar.classList.add("hidden");
        return;
      }
      
      // Close button functionality
      const closeButton = notificationBar.querySelector(".hb-notification-bar__close");
      if (closeButton) {
        closeButton.addEventListener("click", function(e) {
          e.preventDefault();
          e.stopPropagation();
          notificationBar.classList.add("hidden");
          localStorage.setItem(storageKey, "true");
        });
      }
      
      // Find the trigger element ("חידת היגיון יומית")
      let triggerElement = null;
      
      // Search for text content in headings and sections
      const allElements = document.querySelectorAll("h1, h2, h3, h4, h5, h6, div, section, article, header");
      for (let i = 0; i < allElements.length; i++) {
        const el = allElements[i];
        const text = el.textContent || el.innerText || "";
        // Look for exact match or partial match
        if (text.includes("חידת היגיון יומית") || 
            (text.includes("חידת היגיון") && text.includes("יומית"))) {
          triggerElement = el;
          break;
        }
      }
      
      // Function to show notification bar
      const showNotificationBar = function() {
        notificationBar.classList.add("visible");
      };
      
      // If trigger element found, use Intersection Observer
      if (triggerElement) {
        const observer = new IntersectionObserver(function(entries) {
          entries.forEach(function(entry) {
            if (entry.isIntersecting && entry.intersectionRatio > 0.05) {
              showNotificationBar();
              observer.disconnect(); // Only show once
            }
          });
        }, {
          threshold: 0.05,
          rootMargin: "50px" // Start showing slightly before element is fully visible
        });
        
        observer.observe(triggerElement);
      } else {
        // Fallback: show after scrolling 400px
        let hasScrolled = false;
        const scrollHandler = function() {
          if (!hasScrolled && window.scrollY > 400) {
            hasScrolled = true;
            showNotificationBar();
            window.removeEventListener("scroll", scrollHandler);
          }
        };
        window.addEventListener("scroll", scrollHandler, { passive: true });
        
        // Also check on load if already scrolled
        if (window.scrollY > 400) {
          showNotificationBar();
        }
      }
    }
    
    // Run on DOM ready
    if (document.readyState === "loading") {
      document.addEventListener("DOMContentLoaded", initNotificationBar);
    } else {
      initNotificationBar();
    }
  })();
  ');
});

/* ---------------------------------------------------------
 * 2) Output Notification Bar HTML
 * --------------------------------------------------------- */
function hb_notification_bar_output() {
  // Only show on home page and crossword pages
  if (!is_front_page() && !is_singular('crossword')) {
    return;
  }
  
  // Direct link to the Human Spark Project page
  $spark_test_url = 'https://higayonbarie.co.il/human-spark-project/';
  
  ?>
  <div class="hb-notification-bar" id="hb-notification-bar">
    <button class="hb-notification-bar__close" aria-label="סגור" type="button">×</button>
    <div class="hb-notification-bar__content">
      <span class="hb-notification-bar__text">איך AI מתמודד עם הגדרות היגיון?</span>
      <a href="<?php echo esc_url($spark_test_url); ?>" class="hb-notification-bar__link">
        כנסו למבחן הניצוץ האנושי
      </a>
    </div>
  </div>
  <?php
}

// Output at footer (not in header)
add_action('wp_footer', 'hb_notification_bar_output', 999);
