<?php
/**
 * Plugin Name: HB Home v2 Shortcode
 * Description: Modern Home v2 page with wizard + action tray. Hybrid mode: boot + section shortcodes for Elementor.
 * Version: 7.0.0
 * Author: Higayon Barie
 */

if (!defined('ABSPATH')) exit;

// Flags to print assets once per page (more reliable than printing inside shortcode content)
$GLOBALS['hb_homev2__used'] = false;
$GLOBALS['hb_homev2__build'] = null;
$GLOBALS['hb_homev2__css_printed'] = false;
$GLOBALS['hb_homev2__js_printed'] = false;

// Alternative: Print CSS/JS in wp_head AND wp_footer if shortcode was used
// This ensures CSS loads even if Elementor filters shortcode output
add_action('wp_head', function() {
  if (!empty($GLOBALS['hb_homev2__build']) && empty($GLOBALS['hb_homev2_assets_printed_in_head'])) {
    $GLOBALS['hb_homev2_assets_printed_in_head'] = true;
    // Always print in wp_head as primary method (more reliable with Elementor)
    // Use priority 99999 to load AFTER ALL theme CSS and plugins
    // Also add to wp_footer as backup with even higher priority
    echo "\n<!-- hb-homev2 CSS (wp_head) -->\n";
    echo '<style id="hb-homev2-styles" type="text/css">';
    echo hb_homev2_get_css($GLOBALS['hb_homev2__build']);
    echo '</style>';
    echo "\n<!-- /hb-homev2 CSS -->\n";
  }
}, 99999);

// Backup: Also print in wp_footer with highest priority to override everything
add_action('wp_footer', function() {
  if (!empty($GLOBALS['hb_homev2__build']) && !empty($GLOBALS['hb_homev2_assets_printed_in_head'])) {
    // Print critical button color overrides in footer as absolute last resort
    echo "\n<!-- hb-homev2 CRITICAL CSS OVERRIDES (wp_footer) -->\n";
    echo '<style id="hb-homev2-critical-overrides" type="text/css">';
    echo '/* CRITICAL: Force white text on buttons - highest priority override */';
    echo 'html body .hb-homev2 a.hb-homev2-btn-primary,';
    echo 'html body .hb-homev2 a.hb-homev2-hero-account-btn,';
    echo 'html body .hb-homev2 a.hb-homev2-btn-primary *,';
    echo 'html body .hb-homev2 a.hb-homev2-hero-account-btn *,';
    echo 'html body .hb-homev2 a.hb-homev2-btn-primary span,';
    echo 'html body .hb-homev2 a.hb-homev2-hero-account-btn span,';
    echo 'html body .hb-homev2 .hb-homev2-hero-account-btn,';
    echo 'html body .hb-homev2 .hb-homev2-hero-account-btn *,';
    echo 'html body .hb-homev2 .hb-homev2-hero-account-btn span,';
    echo 'html body .hb-homev2 .hb-homev2-hero-account-btn span *,';
    echo 'html body .hb-homev2 .hb-homev2-hero-account-icon {';
    echo '  color: #fff !important;';
    echo '}';
    echo 'html body .hb-homev2 a.hb-homev2-btn-primary:hover,';
    echo 'html body .hb-homev2 a.hb-homev2-hero-account-btn:hover,';
    echo 'html body .hb-homev2 a.hb-homev2-btn-primary:hover *,';
    echo 'html body .hb-homev2 a.hb-homev2-hero-account-btn:hover *,';
    echo 'html body .hb-homev2 .hb-homev2-hero-account-btn:hover,';
    echo 'html body .hb-homev2 .hb-homev2-hero-account-btn:hover *,';
    echo 'html body .hb-homev2 .hb-homev2-hero-account-btn:hover span,';
    echo 'html body .hb-homev2 .hb-homev2-hero-account-btn:hover span *,';
    echo 'html body .hb-homev2 .hb-homev2-hero-account-btn:hover .hb-homev2-hero-account-icon {';
    echo '  color: #fff !important;';
    echo '  text-decoration: none !important;';
    echo '}';
    echo '</style>';
    echo "\n<!-- /hb-homev2 CRITICAL CSS OVERRIDES -->\n";
  }
}, 999999);

add_action('wp_footer', function() {
  if (!empty($GLOBALS['hb_homev2__build']) && empty($GLOBALS['hb_homev2_js_printed_in_footer'])) {
    $GLOBALS['hb_homev2_js_printed_in_footer'] = true;
    // Print JS in footer (better performance)
    echo "\n<!-- hb-homev2 JS (wp_footer) -->\n";
    echo hb_homev2_get_js();
    echo "\n<!-- /hb-homev2 JS -->\n";
  }
}, 999);

/* ---------------------------------------------------------
 * 1) Defaults / Atts
 * --------------------------------------------------------- */
function hb_homev2_defaults() {
  return [
    'hero_title' => 'הגיון בריא',
    'hero_subtitle' => 'בית לתשבצי היגיון, משחקי חשיבה ושיפור יכולות קוגניטיביות',

    'samples_url' => 'https://higayonbarie.co.il/%D7%AA%D7%A9%D7%91%D7%A6%D7%99%D7%9D-%D7%9C%D7%93%D7%95%D7%92%D7%9E%D7%94/',
    'weekly_url' => 'https://higayonbarie.co.il/crossword/%D7%AA%D7%A9%D7%91%D7%A5-%D7%A9%D7%91%D7%95%D7%A2%D7%99/',
    'wordle_url' => 'https://higayonbarie.co.il/%D7%9E%D7%99%D7%9C%D7%AA-%D7%94%D7%99%D7%95%D7%9D/',
    'course_url' => 'https://higayonbarie.co.il/%d7%a7%d7%95%d7%a8%d7%a1-%d7%aa%d7%a9%d7%91%d7%a6%d7%99-%d7%94%d7%99%d7%92%d7%99%d7%95%d7%9f-%d7%90%d7%95%d7%a0%d7%9c%d7%99%d7%99%d7%9f/',
    'games_url' => 'https://higayonbarie.co.il/%d7%9e%d7%a9%d7%97%d7%a7%d7%99-%d7%9e%d7%97%d7%a9%d7%91%d7%94/',
    'sub_url' => 'https://higayonbarie.co.il/%d7%9e%d7%a0%d7%95%d7%99-%d7%9c%d7%aa%d7%a9%d7%91%d7%a5-%d7%94%d7%92%d7%99%d7%95%d7%9f-%d7%a9%d7%91%d7%95%d7%a2%d7%99/',
    'human_spark_url' => 'https://higayonbarie.co.il/human-spark-project/',
    'word_finder_url' => 'https://higayonbarie.co.il/%D7%9E%D7%A6%D7%99%D7%90%D7%AA-%D7%9E%D7%99%D7%9C%D7%99%D7%9D/',
    'blog_url' => 'https://higayonbarie.co.il/%d7%91%d7%9c%d7%95%d7%92/',
    'auto_crossword_url' => 'https://higayonbarie.co.il/%D7%AA%D7%A9%D7%91%D7%A5-%D7%90%D7%95%D7%98%D7%95%D7%9E%D7%98%D7%99/',
    'archive_url' => 'https://higayonbarie.co.il/%D7%AA%D7%A9%D7%91%D7%A6%D7%99%D7%9D/',
    'account_url' => 'https://higayonbarie.co.il/user/', // Default user account page
  ];
}

function hb_homev2_parse_atts($atts = []) {
  return shortcode_atts(hb_homev2_defaults(), $atts);
}

/**
 * Check if user has membership access (PMPro) or is logged in
 * This respects PMPro's preview mode (View: My Access)
 */
function hb_homev2_has_access() {
  $is_logged_in = is_user_logged_in();
  
  // If PMPro is active, check membership level
  if (function_exists('pmpro_hasMembershipLevel')) {
    // pmpro_hasMembershipLevel() respects preview mode automatically
    return pmpro_hasMembershipLevel();
  }
  
  // Fallback: just check if logged in
  return $is_logged_in;
}


/* ---------------------------------------------------------
 * 2) Font enqueue
 * --------------------------------------------------------- */
function hb_homev2_enqueue_font_once() {
  static $done = false;
  if ($done) return;
  $done = true;

  wp_enqueue_style(
    'hb-homev2-assistant',
    'https://fonts.googleapis.com/css2?family=Assistant:wght@400;600;700;800&display=swap',
    [],
    null
  );
}

/* ---------------------------------------------------------
 * 3) BOOT shortcode (assets + data for JS)
 * --------------------------------------------------------- */
/**
 * Use inside Elementor container with CSS class "hb-homev2"
 * Example:
 *   [hb_homev2_boot]
 */
add_shortcode('hb_homev2_boot', function($atts = []) {
  $atts = hb_homev2_parse_atts($atts);
  hb_homev2_enqueue_font_once();

  $has_access = hb_homev2_has_access();
  
  // Check if user is logged in (regardless of membership)
  // IMPORTANT: Force check - don't rely on cached values
  $is_logged_in = is_user_logged_in();
  
  // Debug: Log if user is logged in but data-logged-in might be wrong
  if ($is_logged_in && !isset($GLOBALS['hb_homev2_logged_in_debug'])) {
    $GLOBALS['hb_homev2_logged_in_debug'] = true;
    error_log('HB HomeV2: User is logged in - ID: ' . get_current_user_id() . ', Login check: ' . ($is_logged_in ? 'YES' : 'NO'));
  }
  
  // Check if user has weekly membership (Level 2 or 8)
  $has_weekly_membership = false;
  if (function_exists('pmpro_hasMembershipLevel')) {
    $has_weekly_membership = pmpro_hasMembershipLevel([2, 8]);
  }
  
  $hb_build = gmdate('Ymd-His');
  
  $GLOBALS['hb_homev2__used'] = true;
  $GLOBALS['hb_homev2__build'] = $hb_build;

  // Always use the specific user page URL
  $account_url = 'https://higayonbarie.co.il/user/';
  
  // Get subscription page URL (for redirects when not logged in)
  $sub_page = get_page_by_path('מנוי-לתשבץ-היגיון-שבועי');
  $sub_url = $sub_page ? get_permalink($sub_page->ID) : $atts['sub_url'];

  // Print CSS/JS inside the shortcode output (more reliable with Elementor)
  // Use global to ensure it works across all shortcode calls
  if (empty($GLOBALS['hb_homev2_assets_printed'])) {
    $GLOBALS['hb_homev2_assets_printed'] = true;
    // Store CSS/JS to print inside shortcode output
    $GLOBALS['hb_homev2_css'] = hb_homev2_get_css($hb_build);
    $GLOBALS['hb_homev2_js'] = hb_homev2_get_js();
  }

  ob_start();
  
  // Print CSS/JS as part of shortcode output
  if (!empty($GLOBALS['hb_homev2_css'])) {
    echo "\n<!-- hb-homev2 CSS/JS START -->\n";
    echo $GLOBALS['hb_homev2_css'];
    echo $GLOBALS['hb_homev2_js'];
    echo "\n<!-- hb-homev2 CSS/JS END -->\n";
    // Clear after printing
    unset($GLOBALS['hb_homev2_css']);
    unset($GLOBALS['hb_homev2_js']);
  }
  
  // Hidden boot node holding data attributes (Elementor container can't easily hold data-* attrs)
  // CRITICAL: Always output correct login status, even if cached
  $force_logged_in = is_user_logged_in(); // Force fresh check
  ?>
  <div class="hb-homev2-boot"
    data-logged-in="<?php echo $force_logged_in ? '1' : '0'; ?>"
    data-has-weekly-membership="<?php echo $has_weekly_membership ? '1' : '0'; ?>"
    data-user-id="<?php echo get_current_user_id(); ?>"
    data-check-time="<?php echo time(); ?>"
    data-build="<?php echo esc_attr($hb_build); ?>"
    data-samples-url="<?php echo esc_attr($atts['samples_url']); ?>"
    data-wordle-url="<?php echo esc_attr($atts['wordle_url']); ?>"
    data-course-url="<?php echo esc_attr($atts['course_url']); ?>"
    data-games-url="<?php echo esc_attr($atts['games_url']); ?>"
    data-sub-url="<?php echo esc_attr($sub_url); ?>"
    data-human-spark-url="<?php echo esc_attr($atts['human_spark_url']); ?>"
    data-word-finder-url="<?php echo esc_attr($atts['word_finder_url']); ?>"
    data-blog-url="<?php echo esc_attr($atts['blog_url']); ?>"
    data-auto-crossword-url="<?php echo esc_attr($atts['auto_crossword_url']); ?>"
    data-archive-url="<?php echo esc_attr($atts['archive_url']); ?>"
    data-account-url="<?php echo esc_attr($sub_url); ?>"
    data-weekly-url="<?php echo esc_attr($has_weekly_membership ? $atts['weekly_url'] : $sub_url); ?>"
  ></div>
  <?php

  return ob_get_clean();
});

// CSS/JS are now printed directly in hb_homev2_boot shortcode
// No need for separate hooks

/* ---------------------------------------------------------
 * 4) SECTION shortcodes (HTML only)
 * --------------------------------------------------------- */
add_shortcode('hb_homev2_hero', function($atts = []) {
  $atts = hb_homev2_parse_atts($atts);
  $has_access = hb_homev2_has_access();
  return hb_homev2_section_hero($atts, $has_access);
});

add_shortcode('hb_homev2_weekly_teaser', function() {
  $has_access = hb_homev2_has_access();
  return hb_homev2_section_weekly_teaser($has_access);
});

add_shortcode('hb_homev2_wizard', function() {
  return hb_homev2_section_wizard();
});

add_shortcode('hb_homev2_daily', function() {
  $has_access = hb_homev2_has_access();
  return hb_homev2_section_daily($has_access);
});

add_shortcode('hb_homev2_weekly', function() {
  $has_access = hb_homev2_has_access();
  return hb_homev2_section_weekly($has_access);
});

add_shortcode('hb_homev2_testimonials', function() {
  return hb_homev2_section_testimonials();
});

add_shortcode('hb_homev2_faq', function() {
  return hb_homev2_section_faq();
});

add_shortcode('hb_homev2_start', function() {
  // מרחיב יציבות: גם אם שכחת BOOT, לפחות העטיפה קיימת
  return '<div class="hb-homev2">';
});

add_shortcode('hb_homev2_end', function() {
  return '</div>';
});

/* ---------------------------------------------------------
 * 5) Legacy full-page shortcode (still works)
 * --------------------------------------------------------- */
/**
 * Shortcode: [hb_home_v2]
 * Old behavior: outputs everything in one shot (boot + all sections).
 */
add_shortcode('hb_home_v2', function($atts = []) {
  // Ensure build is set for wp_head/wp_footer hooks
  if (empty($GLOBALS['hb_homev2__build'])) {
    $GLOBALS['hb_homev2__build'] = gmdate('Ymd-His');
  }
  
  $has_access = hb_homev2_has_access();
  $out  = '<div class="hb-homev2">';
  $out .= do_shortcode('[hb_homev2_boot]');
  $out .= do_shortcode('[hb_homev2_hero]');
  $out .= do_shortcode('[hb_homev2_wizard]');
  $out .= hb_homev2_section_weekly_teaser($has_access);
  $out .= do_shortcode('[hb_homev2_daily]');
  $out .= do_shortcode('[hb_homev2_weekly]');
  $out .= do_shortcode('[hb_homev2_faq]');
  $out .= do_shortcode('[hb_homev2_testimonials]');
  $out .= '</div>';
  return $out;
});

add_shortcode('hb_testimonials_home', function() {
  ob_start();
  ?>
  <section class="hb-homev2-testimonials">
    <h2 class="hb-homev2-section-title">מה אומרים עלינו</h2>
    <div class="hb-homev2-testimonials-content">
      <div class="hb-homev2-faq-list">
        <div class="hb-homev2-faq-item" style="padding:18px 20px;">
          <strong>ח.ג.</strong><br>
          רק אחרי הקורס הדיגיטלי ב״הגיון בריא״ הבנתי את צורת החשיבה שיש בתשבצי הגיון ונתפסתי. אני התמכרתי!
        </div>
        <div class="hb-homev2-faq-item" style="padding:18px 20px;">
          <strong>סבתא רונית פלג</strong><br>
          מאז, בדיוק ובנאמנות, אני מתחדשת מידי יום שישי בתשבץ חדש ומאתגר שעדי מפרסם ושאני פותרת בשקיקה ובהנאה רבה.
        </div>
        <div class="hb-homev2-faq-item" style="padding:18px 20px;">
          <strong>ורד</strong><br>
          הגעתי לקורס בלי שום ידע על תשבצי הגיון... בסיומו של הקורס יכולתי כבר להבין את הפתרונות של רוב התשבצים.
        </div>
      </div>
    </div>
  </section>
  <?php
  return ob_get_clean();
});

add_shortcode('hb_testimonials', function() {
  $items = [
    [
      'name' => 'ורד',
      'highlight' => 'הגעתי בלי ידע — ובסוף הקורס כבר הבנתי את רוב התשבצים.',
      'full' => 'הגעתי לקורס בלי שום ידע על תשבצי הגיון. גם כשראיתי תשבץ פתור לא הבנתי איך הפתרון קשור לחידה. ואז הגעתי לקורס שהעביר עדי בצורה ברורה, מדויקת ובליווי שפע דוגמאות. בסיומו של הקורס יכולתי כבר להבין את הפתרונות של רוב התשבצים. קרוב לסיום הקורס נפתח החוג השבועי, אליו הצטרפתי בשמחה. משבוע לשבוע גדל החלק שפתרתי בתשבץ. היום, לאחר כ-4 שנים שהחוג מתקיים, יכולה להעיד שמצפה מדי שבוע ליום ו (פרסום תשבץ חדש) וליום ד, שעת המפגש בה אנו פותרים יחדיו, בניהולו של עדי ובאווירה טובה.'
    ],
    [
      'name' => 'רונית פלג',
      'highlight' => 'אני מתחדשת בכל יום שישי בתשבץ חדש — וזה הפך להרגל קבוע.',
      'full' => 'אני סבתא שפותרת תשבצים רגילים על בסיס יומיומי. אבל, בכל פעם שניסיתי לפתור תשבץ הגיון, נתקלתי בעולם זר, מוזר, בלתי ניתן לפענוח, ומאוד מסקרן…לפעמים ניסיתי והתאכזבתי בכל פעם מחדש. והנה, נקרתה בפני הזדמנות להשתתף בקורס בן ארבעה מפגשים שהתקיים בזום. נרשמתי… בקורס השתתפו מבוגרים כמוני ולרובנו לא היה מושג כיצד ניגשים לנושא. המדריך היה עדי עופר, שבענווה, בשלווה, בתבונה, ברגישות ובסבלנות אינסופית ריכז קבוצה גדולה מאוד של אנשים שהמטירו שאלות בלי סוף. (לא מבינה איך הצליח…קוסם!) באומנות ובצעדים קטנים, אך מאוד נחושים, בדרך מאוד מאורגנת וברורה, פרש עדי לפנינו את סודות תשבצי ההגיון ולאט לאט פתח בפנינו את השער לעולם הקסום והנפלא הזה. התהליך היה מובנה, ברור, מאורגן להפליא ומרתק. מאז, בדיוק ובנאמנות, אני מתחדשת מידי יום שישי בתשבץ חדש ומאתגר שעדי מפרסם ושאני פותרת בשקיקה ובהנאה רבה. בכל תשבץ אני פוגשת הגדרות שנונות, מאתגרות, לעיתים מצחיקות ותמיד, תמיד מהנות. ממליצה בחום רב,'
    ],
    [
      'name' => 'ח.ג.',
      'highlight' => 'רק אחרי הקורס הדיגיטלי הבנתי את צורת החשיבה — ונתפסתי.',
      'full' => 'היי עדי היקר, תודה על הקורס לתשבצי הגיון שפתח לי עולם חדש מאתגר. אני חובבת תשבצים. אבל שניסיתי לפתור תשבצי הגיון נתקעתי וחשתי תסכול. רק אחרי הקורס הדיגיטלי בהגיון בריא הבנתי את צורת החשיבה שיש בתשבצי הגיון ונתפסתי. הקורס מלמד בצורה ידידותית וקלה עם הרבה דוגמאות והסברים. עדיין לא קל לי לפתור תשבצי הגיון אבל מאתגר ומהנה, וההמשכיות איתך של תשבץ הגיון שבועי עם הסברים להבנת ההגיון מהווה תירגול מצויין לידע הרב שאתה מקנה. ממליצה בחום לכל מי שרוצה להפעיל את הראש, לחשוב גם בצורה אחרת בדרך מהנה ומאתגרת. אני התמכרתי!'
    ],
  ];

  ob_start(); ?>
  <section class="hb-homev2-testimonials">
    <h2 class="hb-homev2-section-title">מה אומרים עלינו</h2>

    <div class="hb-homev2-testimonials-grid">
      <?php foreach ($items as $t): ?>
        <details class="hb-homev2-testimonial">
          <summary>
            <strong><?php echo esc_html($t['name']); ?></strong>
            <span><?php echo esc_html($t['highlight']); ?></span>
          </summary>
          <div class="hb-homev2-testimonial-body">
            <?php echo esc_html($t['full']); ?>
          </div>
        </details>
      <?php endforeach; ?>
    </div>
  </section>
  <?php
  return ob_get_clean();
});

/* ---------------------------------------------------------
 * 6) Sections renderers
 * --------------------------------------------------------- */
function hb_homev2_section_hero($atts, $has_access) {
  // Always use the specific user page URL
  $account_url = 'https://higayonbarie.co.il/user/';

  ob_start(); ?>
  <section class="hb-homev2-hero">
    <?php if ($has_access): ?>
    <div class="hb-homev2-hero-account-link">
      <a href="<?php echo esc_url($account_url); ?>" class="hb-homev2-hero-account-btn" style="color: #fff !important;">
        <span class="hb-homev2-hero-account-icon" style="color: #fff !important;">👤</span>
        <span style="color: #fff !important;">אזור אישי</span>
      </a>
    </div>
    <?php endif; ?>
    <div class="hb-homev2-hero-content">
      <h1 class="hb-homev2-hero-title" style="color: #0f172a;"><?php echo esc_html($atts['hero_title']); ?></h1>
      <p class="hb-homev2-hero-subtitle"><?php echo esc_html($atts['hero_subtitle']); ?></p>
      <p class="hb-homev2-hero-intro">
        ברוכים הבאים לאתר של עדי עפר — בית לתשבצי היגיון מקוריים, קורס היגיון שמלמד את שיטת החשיבה, משחקי חשיבה וכלים לשיפור יכולות קוגניטיביות.
        <br>כל התכנים כאן הם פרי יצירה שלי, מתוך מחויבות לאיכות ולחשיבה מעמיקה. בחרו רמה (מתחילים / מתקדמים / מומחים) כדי לקבל סט כלים מותאם.
      </p>
      <?php if ($has_access): ?>
      <div class="hb-homev2-hero-account-link-mobile">
        <a href="<?php echo esc_url($account_url); ?>" class="hb-homev2-hero-account-btn" style="color: #fff !important;">
          <span class="hb-homev2-hero-account-icon" style="color: #fff !important;">👤</span>
          <span style="color: #fff !important;">אזור אישי</span>
        </a>
      </div>
      <?php endif; ?>
    </div>
  </section>
  <?php
  return ob_get_clean();
}

function hb_homev2_section_weekly_teaser($has_access) {
  $defaults = hb_homev2_defaults();
  $weekly_url = $has_access ? $defaults['weekly_url'] : wp_login_url($defaults['weekly_url']);
  
  ob_start(); ?>
  <section class="hb-homev2-weekly-teaser">
    <div class="hb-homev2-loginbar">
      <div class="hb-homev2-loginbar-content">
        <div class="hb-homev2-loginbar-icon">🗓️</div>
        <div class="hb-homev2-loginbar-text">
          <?php if ($has_access): ?>
          <strong>תשבץ היגיון שבועי:</strong> מדי שבוע עולה תשבץ היגיון חדש ומאתגר. כחבר מנוי, יש לך גישה לתשבץ השבועי, לארכיון המלא עם כל התשבצים הקודמים, ולשמירת התקדמות אישית.
          <?php else: ?>
          <strong>תשבץ היגיון שבועי:</strong> מדי שבוע עולה תשבץ היגיון חדש ומאתגר. מנויים מקבלים גישה לכלל הכלים והתכנים באתר: התשבץ השבועי, ארכיון מלא עם כל התשבצים הקודמים, שמירת התקדמות אישית, ומגוון כלים נוספים.
          <?php endif; ?>
        </div>
      </div>
      <div class="hb-homev2-loginbar-actions">
        <a class="hb-homev2-btn-primary hb-homev2-btn-small" href="<?php echo esc_url($weekly_url); ?>" style="color: #fff !important;">
          <span style="color: #fff !important;"><?php echo $has_access ? 'מעבר לתשבץ השבועי' : 'התחברות ומעבר לתשבץ'; ?></span>
        </a>
      </div>
    </div>
  </section>
  <?php
  return ob_get_clean();
}

function hb_homev2_section_wizard() {
  ob_start(); ?>
  <section class="hb-homev2-wizard">
    <button class="hb-homev2-wizard-card" data-route="beginner" type="button">
      <h3>מתחילים</h3>
      <p>מסלול מושלם למי שזה עתה מתחיל</p>
    </button>

    <button class="hb-homev2-wizard-card" data-route="intermediate" type="button">
      <h3>מתקדמים</h3>
      <p>למשתמשים עם ניסיון</p>
    </button>

    <button class="hb-homev2-wizard-card" data-route="expert" type="button">
      <h3>מומחים</h3>
      <p>למשתמשים מנוסים</p>
    </button>
  </section>
  <?php
  return ob_get_clean();
}

function hb_homev2_section_daily($has_access) {
  ob_start(); ?>
  <section class="hb-homev2-daily-puzzle" id="daily-puzzle">
    <h2 class="hb-homev2-section-title">חידה יומית</h2>

    <div class="hb-homev2-centerbox">
      <p class="hb-homev2-lead">רוצים דקה של חשיבה? פתחו את חידת ההיגיון היומית.</p>

      <div class="hb-homev2-btn-row">
        <button type="button" class="hb-homev2-btn-primary hb-homev2-daily-toggle">פתח/סגור חידה</button>
      </div>

      <div class="hb-homev2-daily-wrap" hidden>
        <?php echo do_shortcode('[hb_daily_puzzle]'); ?>
      </div>
    </div>
  </section>
  <?php
  return ob_get_clean();
}

function hb_homev2_section_weekly($has_access) {
  $weekly_url = 'https://higayonbarie.co.il/crossword/%D7%AA%D7%A9%D7%91%D7%A5-%D7%A9%D7%91%D7%95%D7%A2%D7%99/';
  $sub_url    = 'https://higayonbarie.co.il/%d7%9e%d7%a0%d7%95%d7%99-%d7%9c%d7%aa%d7%a9%d7%91%d7%a5-%d7%94%d7%92%d7%99%d7%95%d7%9f-%d7%a9%d7%91%d7%95%d7%a2%d7%99/';

  ob_start(); ?>
  <section class="hb-homev2-weekly-club hb-homev2-section-collapsible" id="weekly-club">
    <h2 class="hb-homev2-section-title">תשבץ היגיון שבועי</h2>

    <div class="hb-homev2-weekly-content hb-homev2-centerbox">
      <p class="hb-homev2-lead">
        <strong>תשבץ חדש מדי שבוע!</strong> מנויים מקבלים גישה לכלל הכלים והתכנים באתר: התשבץ השבועי, ארכיון מלא עם כל התשבצים הקודמים, שמירת התקדמות אישית, ומגוון כלים נוספים. כל תשבץ כולל הסברים מפורטים שמלמדים את דרך החשיבה.
      </p>

      <?php if ($has_access): ?>
        <a class="hb-homev2-btn-primary" href="<?php echo esc_url($weekly_url); ?>" style="color: #fff !important;">
          <span style="color: #fff !important;">פתח את התשבץ השבועי</span>
        </a>
      <?php else: ?>
        <?php 
          $sub_page = get_page_by_path('מנוי-לתשבץ-היגיון-שבועי');
          $sub_url = $sub_page ? get_permalink($sub_page->ID) : home_url('/מנוי-לתשבץ-היגיון-שבועי/');
        ?>
        <div class="hb-homev2-btn-row">
          <a class="hb-homev2-btn-primary" href="<?php echo esc_url($sub_url); ?>" style="color: #fff !important;">
            <span style="color: #fff !important;">התחברות ומעבר לתשבץ</span>
          </a>
          <a class="hb-homev2-btn-secondary" href="<?php echo esc_url($sub_url); ?>">
            מה כולל המנוי?
          </a>
        </div>
      <?php endif; ?>
    </div>
  </section>
  <?php
  return ob_get_clean();
}

function hb_homev2_section_testimonials() {
  return do_shortcode('[hb_testimonials]');
}

function hb_homev2_section_faq() {
  $faq = [
    [
      'q' => 'איך פותרים את התשבצים באתר?',
      'a' => 'אפשר לפתור אונליין במחשב ובנייד. בנוסף אפשר להוריד ולהדפיס כ-PDF. תשבצי דוגמה פתוחים לכולם, ולמנויים יש גישה רחבה יותר.'
    ],
    [
      'q' => 'מה כולל קורס תשבצי ההיגיון?',
      'a' => 'שיעורים מצולמים עם גישה מיידית לתוכן. לכל שיעור יש גם תרגול: תשבץ תואם, סט חידות נפרדות, ואפשרות להדפסה או פתרון אונליין.'
    ],
    [
      'q' => 'איך מתבצע התשלום?',
      'a' => 'התשלום בכרטיס אשראי דרך סליקה מאובטחת. אם צריך דרך אחרת — אפשר ליצור קשר ונעזור.'
    ],
    [
      'q' => 'אילו סוגי מנויים קיימים?',
      'a' => 'מנוי חודשי או שנתי: גישה לתשבץ השבועי + ארכיון מלא, ושמירת תשבצים. ללא מנוי אפשר לפתור דוגמאות, לשחק במשחקי מחשבה ולהשתמש בכלי עזר.'
    ],
    [
      'q' => 'האם צריך להירשם כדי לפתור תשבצים?',
      'a' => 'לא חובה. אפשר לפתור מספר תשבצי דוגמה כאורח. משתמשים רשומים/מנויים נהנים משמירת התקדמות וגישה לארכיון.'
    ],
    [
      'q' => 'יש לי שאלה שלא הופיעה כאן — איך פונים אליכם?',
      'a' => 'אפשר לפנות בטלפון 052-470-5144 או במייל [email protected] או דרך טופס יצירת קשר באתר.'
    ],
  ];

  ob_start(); ?>
  <section class="hb-homev2-faq">
    <h2 class="hb-homev2-section-title">שאלות נפוצות</h2>
    <div class="hb-homev2-faq-list">
      <?php foreach ($faq as $item): ?>
        <details class="hb-homev2-faq-item">
          <summary class="hb-homev2-faq-question"><?php echo esc_html($item['q']); ?></summary>
          <div class="hb-homev2-faq-answer"><?php echo esc_html($item['a']); ?></div>
        </details>
      <?php endforeach; ?>
    </div>
  </section>
  <?php
  return ob_get_clean();
}

/* ---------------------------------------------------------
 * 7) CSS (same as yours)
 * --------------------------------------------------------- */
function hb_homev2_get_css($hb_build) {
  ob_start();
  ?>
  <style id="hb-homev2-css" data-build="<?php echo esc_attr($hb_build); ?>">
  /* hb_homev2 build: <?php echo esc_html($hb_build); ?> */
  
  /* CRITICAL: Override global link styles FIRST - highest specificity */
  /* Use html body for maximum specificity to override ANY theme CSS */
  html body .hb-homev2 a.hb-homev2-btn-primary,
  html body .hb-homev2 a.hb-homev2-hero-account-btn,
  html body .hb-homev2 a.hb-homev2-btn-primary *,
  html body .hb-homev2 a.hb-homev2-hero-account-btn *,
  html body .hb-homev2 a.hb-homev2-btn-primary span,
  html body .hb-homev2 a.hb-homev2-hero-account-btn span,
  html body .hb-homev2 .hb-homev2-hero-account-btn,
  html body .hb-homev2 .hb-homev2-hero-account-btn *,
  html body .hb-homev2 .hb-homev2-hero-account-btn span,
  html body .hb-homev2 .hb-homev2-hero-account-btn span *,
  html body .hb-homev2 .hb-homev2-hero-account-icon {
    color: #fff !important;
  }
  html body .hb-homev2 a.hb-homev2-btn-primary:hover,
  html body .hb-homev2 a.hb-homev2-hero-account-btn:hover,
  html body .hb-homev2 a.hb-homev2-btn-primary:hover *,
  html body .hb-homev2 a.hb-homev2-hero-account-btn:hover *,
  html body .hb-homev2 .hb-homev2-hero-account-btn:hover,
  html body .hb-homev2 .hb-homev2-hero-account-btn:hover *,
  html body .hb-homev2 .hb-homev2-hero-account-btn:hover span,
  html body .hb-homev2 .hb-homev2-hero-account-btn:hover span *,
  html body .hb-homev2 .hb-homev2-hero-account-btn:hover .hb-homev2-hero-account-icon {
    color: #fff !important;
    text-decoration: none !important;
  }

  .hb-homev2 {
    /* Design Tokens 2026 */
    --hb-primary: #DB8A16;
    --hb-primary-hover: #c17813;
    --hb-primary-light: rgba(219, 138, 22, 0.08);
    --hb-text: #0f172a;
    --hb-text-secondary: #475569;
    --hb-muted: #64748b;
    --hb-bg: #ffffff;
    --hb-bg-subtle: #f8fafc;
    --hb-border: #e2e8f0;
    --hb-border-light: #f1f5f9;
    --hb-wash: rgba(219,138,22,.08);
    --hb-wash-strong: rgba(219,138,22,.14);
    --hb-ring: rgba(219,138,22,.25);
    --hb-shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
    --hb-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    --hb-shadow-md: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
    --hb-shadow-lg: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
    --hb-shadow-hover: 0 14px 32px rgba(219,138,22,0.12), var(--hb-shadow);
    --hb-radius: 12px;
    --hb-radius-lg: 16px;
    --hb-radius-xl: 24px;
    --hb-pad: clamp(24px, 5vw, 48px);
    --hb-gap: clamp(20px, 4vw, 32px);
    --hb-font: "Assistant", -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Noto Sans Hebrew", sans-serif;
    --hb-transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);

    font-family: var(--hb-font);
    direction: rtl;
    width: 100%;
    max-width: 1280px;
    margin: 0 auto;
    padding: 0 clamp(16px, 4vw, 32px);
    color: var(--hb-text);
    line-height: 1.6;
    background: transparent;
    -webkit-font-smoothing: antialiased;
    -moz-osx-font-smoothing: grayscale;
  }

  /* boot node hidden */
  .hb-homev2 .hb-homev2-boot { display:none; }

  /* Force reset for button-like cards */
  .hb-homev2 button.hb-homev2-wizard-card {
    -webkit-appearance: none;
    appearance: none;
    font: inherit;
    color: inherit;
  }

  .hb-homev2-section-collapsible {
    max-height: 0;
    overflow: hidden;
    opacity: 0;
    padding: 0;
    margin: 0;
    transition: opacity 0.3s ease, max-height 0.4s ease, padding 0.4s ease;
  }
  .hb-homev2-section-collapsible.hb-homev2-section-visible {
    max-height: 5000px;
    opacity: 1;
    padding: clamp(40px, 6vw, 64px) 0;
    animation: hbFadeUp 0.4s cubic-bezier(0.4, 0, 0.2, 1) both;
  }
  @keyframes hbFadeUp {
    from { 
      opacity: 0; 
      transform: translateY(20px); 
    }
    to { 
      opacity: 1; 
      transform: translateY(0); 
    }
  }

  .hb-homev2-testimonials,
  .hb-homev2-faq { 
    padding: clamp(40px, 6vw, 64px) 0; 
    position: relative; 
  }

  .hb-homev2 .hb-homev2-hero {
    padding: clamp(48px, 8vw, 80px) 0 clamp(40px, 6vw, 64px) !important;
    text-align: center !important;
    background: 
      radial-gradient(circle at 20% 30%, rgba(219, 138, 22, 0.12) 0%, transparent 60%),
      radial-gradient(circle at 80% 70%, rgba(59, 130, 246, 0.08) 0%, transparent 60%),
      linear-gradient(135deg, rgba(255, 255, 255, 1) 0%, rgba(248, 250, 252, 1) 50%, rgba(241, 245, 249, 1) 100%) !important;
    border-radius: var(--hb-radius-xl) !important;
    margin-bottom: clamp(32px, 5vw, 48px) !important;
    position: relative !important;
    overflow: hidden !important;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.08), 0 0 0 1px rgba(0, 0, 0, 0.04) !important;
  }
  .hb-homev2-hero::before {
    content: "";
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, transparent, var(--hb-primary), transparent);
    opacity: 0.6;
  }
  .hb-homev2-hero-content { 
    max-width: 800px; 
    margin: 0 auto;
    position: relative;
    z-index: 1;
  }
  .hb-homev2-hero-account-link {
    position: absolute;
    top: clamp(24px, 4vw, 32px);
    right: clamp(24px, 4vw, 32px);
    z-index: 2;
  }
  .hb-homev2-hero-account-link-mobile {
    display: none;
  }
  @media (max-width: 768px) {
    .hb-homev2-hero-account-link {
      display: none;
    }
    .hb-homev2-hero-account-link-mobile {
      display: block;
      margin: 24px 0 0;
      text-align: center;
    }
  }
  .hb-homev2 .hb-homev2-hero-title {
    font-size: clamp(36px, 7vw, 64px) !important;
    font-weight: 800 !important;
    margin: 0 0 16px 0 !important;
    line-height: 1.15 !important;
    letter-spacing: -0.03em !important;
    background: linear-gradient(135deg, #0f172a 0%, #DB8A16 50%, #3b82f6 100%) !important;
    -webkit-background-clip: text !important;
    -webkit-text-fill-color: transparent !important;
    background-clip: text !important;
    color: #0f172a !important; /* Fallback for browsers that don't support background-clip: text */
  }
  .hb-homev2 .hb-homev2-hero-subtitle {
    font-size: clamp(19px, 2.8vw, 24px) !important;
    color: var(--hb-primary) !important; /* Fallback color */
    margin: 0 0 28px 0 !important;
    font-weight: 600 !important;
    text-align: center !important;
  }
  @supports (-webkit-background-clip: text) or (background-clip: text) {
    .hb-homev2 .hb-homev2-hero-subtitle {
      background: linear-gradient(135deg, var(--hb-primary) 0%, var(--hb-primary-hover) 100%) !important;
      -webkit-background-clip: text !important;
      -webkit-text-fill-color: transparent !important;
      background-clip: text !important;
      color: transparent !important; /* Ensure fallback doesn't show */
    }
  }
  .hb-homev2-hero-account-btn {
    display: inline-flex !important;
    align-items: center !important;
    gap: 8px !important;
    padding: 10px 20px !important;
    background: var(--hb-primary) !important;
    color: #fff !important;
    text-decoration: none !important;
    border-radius: 999px !important;
    font-weight: 600 !important;
    font-size: 15px !important;
    transition: var(--hb-transition) !important;
    box-shadow: 0 2px 8px rgba(219, 138, 22, 0.25) !important;
    white-space: nowrap !important;
  }
  html body .hb-homev2 .hb-homev2-hero-account-btn,
  html body .hb-homev2 .hb-homev2-hero-account-btn *,
  html body .hb-homev2 .hb-homev2-hero-account-btn span,
  html body .hb-homev2 .hb-homev2-hero-account-btn span *,
  html body .hb-homev2 .hb-homev2-hero-account-btn a,
  html body .hb-homev2 .hb-homev2-hero-account-btn a *,
  html body .hb-homev2 .hb-homev2-hero-account-icon {
    color: #fff !important;
  }
  .hb-homev2-hero-account-btn:hover {
    background: var(--hb-primary-hover) !important;
    transform: translateY(-1px) !important;
    box-shadow: 0 4px 12px rgba(219, 138, 22, 0.35) !important;
  }
  html body .hb-homev2 .hb-homev2-hero-account-btn:hover,
  html body .hb-homev2 .hb-homev2-hero-account-btn:hover *,
  html body .hb-homev2 .hb-homev2-hero-account-btn:hover span,
  html body .hb-homev2 .hb-homev2-hero-account-btn:hover span *,
  html body .hb-homev2 .hb-homev2-hero-account-btn:hover .hb-homev2-hero-account-icon {
    color: #fff !important;
  }
  .hb-homev2-hero-account-icon {
    font-size: 18px !important;
    color: #fff !important;
  }
  .hb-homev2-hero-intro {
    font-size: clamp(17px, 2.2vw, 19px);
    color: var(--hb-text-secondary);
    margin: 28px auto 0;
    max-width: 680px;
    line-height: 1.75;
    padding: 0 clamp(16px, 4vw, 0);
  }

  .hb-homev2 .hb-homev2-wizard {
    padding: clamp(40px, 6vw, 64px) 0 !important;
    display: grid !important;
    grid-template-columns: repeat(auto-fit, minmax(min(100%, 300px), 1fr)) !important;
    gap: clamp(20px, 4vw, 32px) !important;
  }

  .hb-homev2-section-title {
    margin: 0 0 clamp(32px, 5vw, 48px) 0;
    font-size: clamp(24px, 4vw, 40px);
    font-weight: 700;
    background: linear-gradient(135deg, #0f172a 0%, var(--hb-primary) 50%, var(--hb-accent-yellow) 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    text-align: center;
    position: relative;
  }
  .hb-homev2-section-title:after {
    content: "";
    display: block;
    width: 64px;
    height: 4px;
    margin: 20px auto 0;
    border-radius: 999px;
    background: linear-gradient(90deg, transparent, var(--hb-primary), transparent);
    opacity: 0.8;
    box-shadow: 0 0 10px rgba(219, 138, 22, 0.3);
  }

  .hb-homev2 .hb-homev2-btn-primary,
  .hb-homev2 .hb-homev2-btn-secondary{
    border-radius: 999px;
    padding: 12px 18px;
    font-weight: 700;
    letter-spacing: .2px;
    transition: transform .18s ease, box-shadow .18s ease, filter .18s ease, background .18s ease;
    will-change: transform;
    text-decoration: none !important;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    font-size: 15px;
  }

  .hb-homev2 .hb-homev2-btn-primary{
    background: linear-gradient(135deg, #DB8A16 0%, #C17813 100%) !important;
    color: #fff !important;
    border: 1px solid rgba(0,0,0,.06) !important;
    box-shadow: 0 10px 24px rgba(219, 138, 22, 0.25), 0 0 20px rgba(219, 138, 22, 0.1) !important;
    position: relative !important;
    overflow: hidden !important;
  }
  /* Override global link color for buttons - use html body for maximum specificity */
  html body .hb-homev2 .hb-homev2-btn-primary,
  html body .hb-homev2 .hb-homev2-btn-primary *,
  html body .hb-homev2 .hb-homev2-btn-primary span,
  html body .hb-homev2 .hb-homev2-btn-primary a,
  html body .hb-homev2 .hb-homev2-btn-primary button,
  html body .hb-homev2 a.hb-homev2-btn-primary,
  html body .hb-homev2 a.hb-homev2-btn-primary *,
  html body .hb-homev2 a.hb-homev2-btn-primary span {
    color: #fff !important;
  }
  html body .hb-homev2 .hb-homev2-btn-primary:hover,
  html body .hb-homev2 .hb-homev2-btn-primary:hover *,
  html body .hb-homev2 .hb-homev2-btn-primary:hover span,
  html body .hb-homev2 a.hb-homev2-btn-primary:hover,
  html body .hb-homev2 a.hb-homev2-btn-primary:hover * {
    color: #fff !important;
  }
  .hb-homev2 .hb-homev2-btn-primary::before {
    content: "";
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
    transition: left 0.5s ease;
  }
  .hb-homev2 .hb-homev2-btn-primary:hover::before {
    left: 100%;
  }
  .hb-homev2 .hb-homev2-btn-primary:hover{
    transform: translateY(-2px);
    box-shadow: 0 14px 32px rgba(219, 138, 22, 0.35), 0 0 30px rgba(219, 138, 22, 0.15);
    filter: saturate(1.1) brightness(1.05);
  }
  .hb-homev2 .hb-homev2-btn-primary:active{
    transform: translateY(0px);
    box-shadow: 0 8px 18px rgba(219, 138, 22, 0.25);
  }

  .hb-homev2 .hb-homev2-btn-secondary{
    background: rgba(255,255,255,.85) !important;
    color: var(--hb-primary) !important;
    border: 1px solid rgba(0,0,0,.10) !important;
    box-shadow: 0 8px 18px rgba(0,0,0,.06);
    backdrop-filter: blur(8px);
  }
  .hb-homev2 .hb-homev2-btn-secondary:hover{
    transform: translateY(-2px);
    box-shadow: 0 12px 26px rgba(0,0,0,.10);
  }

  @media (max-width: 768px){
    .hb-homev2 .hb-homev2-btn-primary,
    .hb-homev2 .hb-homev2-btn-secondary{
      width: 100%;
      max-width: 420px;
    }
  }

  .hb-homev2 .hb-homev2-wizard-card {
    background: linear-gradient(135deg, rgba(255, 255, 255, 0.98) 0%, rgba(248, 250, 252, 0.95) 100%) !important;
    border: 2px solid var(--hb-border) !important;
    border-radius: var(--hb-radius-lg) !important;
    padding: clamp(36px, 6vw, 52px) clamp(28px, 5vw, 36px) !important;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.08), inset 0 1px 0 rgba(255, 255, 255, 0.9) !important;
    transition: var(--hb-transition) !important;
    position: relative !important;
    overflow: hidden !important;
    cursor: pointer !important;
    text-align: center !important;
  }
  .hb-homev2-wizard-card::before {
    content: "";
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: linear-gradient(90deg, transparent, var(--hb-primary), transparent);
    opacity: 0;
    transition: var(--hb-transition);
  }
  .hb-homev2-wizard-card::after {
    content: "";
    position: absolute;
    top: -50%;
    left: -50%;
    width: 200%;
    height: 200%;
    background: radial-gradient(circle, rgba(219, 138, 22, 0.08) 0%, transparent 70%);
    opacity: 0;
    transition: var(--hb-transition);
  }
  .hb-homev2-wizard-card:hover::before {
    opacity: 0.8;
  }
  .hb-homev2-wizard-card:hover::after {
    opacity: 1;
  }
  .hb-homev2-wizard-card:hover {
    box-shadow: 0 12px 40px rgba(219, 138, 22, 0.15), inset 0 1px 0 rgba(255, 255, 255, 0.95);
    transform: translateY(-4px);
  }
  .hb-homev2-wizard-selected {
    border-color: var(--hb-primary) !important;
    background: linear-gradient(180deg, var(--hb-primary-light) 0%, rgba(255, 255, 255, 0.95) 100%) !important;
    box-shadow: var(--hb-shadow-md);
  }
  .hb-homev2-wizard-selected::before {
    opacity: 1 !important;
  }
  .hb-homev2 .hb-homev2-wizard-card h3 {
    font-size: clamp(22px, 3.5vw, 28px) !important;
    font-weight: 700 !important;
    color: var(--hb-text) !important;
    margin: 0 0 12px 0 !important;
  }
  .hb-homev2 .hb-homev2-wizard-card p {
    font-size: clamp(15px, 2vw, 17px) !important;
    color: var(--hb-text-secondary) !important;
    margin: 0 !important;
  }

  /* Action tray grid */
  .hb-homev2-action-tray {
    padding: clamp(28px, 5vw, 56px) 0;
    background: transparent;
    border-top: 1px solid rgba(219,138,22,.12);
  }
  .hb-homev2-action-tray-header { text-align: center; margin: 0 0 22px 0; }
  .hb-homev2-action-tray-title {
    font-size: clamp(20px, 3vw, 24px);
    font-weight: 600;
    margin: 0 0 6px 0;
    letter-spacing: -0.3px;
  }
  .hb-homev2-action-tray-subtitle { color: var(--hb-muted); margin: 0; font-size: 15px; }

  .hb-homev2-action-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(230px, 1fr));
    gap: 16px;
  }
  .hb-homev2-action-card {
    display: block;
    background: #fff;
    border: 1px solid var(--hb-border);
    border-radius: var(--hb-radius-sm);
    padding: 20px;
    transition: all .2s ease;
    text-decoration: none !important;
    color: var(--hb-text) !important;
    position: relative;
    overflow: hidden;
  }
  .hb-homev2-action-card:before {
    display: none;
  }
  .hb-homev2-action-card:hover { border-color: var(--hb-text); background: var(--hb-border-light); }

  .hb-homev2-action-top { display: block; }
  .hb-homev2-action-ico {
    width: 32px; height: 32px;
    border-radius: 2px;
    display: flex; align-items: center; justify-content: center;
    font-size: 11px; font-weight: 600;
    background: transparent;
    border: 1px solid var(--hb-border);
    color: var(--hb-muted);
    flex: 0 0 auto;
    letter-spacing: 0.5px;
    text-transform: uppercase;
  }
  .hb-homev2-action-tag {
    position: absolute;
    top: 12px;
    left: 12px;
    font-size: 10px;
    font-weight: 600;
    padding: 2px 6px;
    border-radius: 2px;
    background: transparent;
    border: 1px solid var(--hb-border);
    color: var(--hb-muted);
    letter-spacing: 0.5px;
    text-transform: uppercase;
    z-index: 1;
    max-width: 80px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }
  @media (max-width: 768px) {
    .hb-homev2-action-tag {
      font-size: 9px;
      padding: 1px 4px;
      max-width: 70px;
    }
  }

  .hb-homev2-paywall {
    text-align: center;
    padding: 48px;
    background: var(--hb-bg);
    border-radius: var(--hb-radius-sm);
    border: 1px solid var(--hb-border);
  }

  .hb-homev2-loginbar {
    display: flex;
    gap: 20px;
    align-items: center;
    justify-content: space-between;
    padding: 20px 24px;
    border: 2px solid var(--hb-ring);
    border-radius: var(--hb-radius-lg);
    background: linear-gradient(135deg, rgba(255, 255, 255, 0.98) 0%, rgba(248, 250, 252, 0.95) 100%);
    margin: 0 auto 40px;
    max-width: 900px;
    box-shadow: 0 12px 32px rgba(219,138,22,0.15), inset 0 1px 0 rgba(255, 255, 255, 0.9);
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
  }
  .hb-homev2-loginbar::before {
    content: "";
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: linear-gradient(90deg, transparent, var(--hb-primary), transparent);
    opacity: 0.6;
  }
  .hb-homev2-loginbar:hover {
    box-shadow: 0 16px 40px rgba(219,138,22,0.25), inset 0 1px 0 rgba(255, 255, 255, 0.95);
    transform: translateY(-2px);
  }
  .hb-homev2-loginbar-content {
    display: flex;
    gap: 16px;
    align-items: flex-start;
    flex: 1;
  }
  .hb-homev2-loginbar-icon {
    font-size: 28px;
    line-height: 1;
    flex-shrink: 0;
    margin-top: 2px;
  }
  .hb-homev2-loginbar-text { 
    color: var(--hb-text); 
    font-size: clamp(15px, 1.9vw, 16px);
    line-height: 1.7;
    text-align: right;
  }
  .hb-homev2-loginbar-text strong {
    color: var(--hb-text);
    font-weight: 700;
  }
  .hb-homev2-loginbar-subtext {
    color: var(--hb-text-secondary);
    font-size: 0.9em;
    display: block;
    margin-top: 4px;
  }
  .hb-homev2-loginbar-actions { 
    display: flex; 
    gap: 12px; 
    flex-wrap: wrap;
    flex-shrink: 0;
  }
  .hb-homev2-btn-small { 
    padding: 12px 24px; 
    font-size: 15px;
    font-weight: 600;
  }

  /* FAQ */
  .hb-homev2-faq-list { max-width: 800px; margin: 0 auto; }
  .hb-homev2-faq-item {
    background: linear-gradient(135deg, rgba(255, 255, 255, 0.98) 0%, rgba(248, 250, 252, 0.95) 100%);
    border: 2px solid var(--hb-border);
    border-radius: var(--hb-radius-lg);
    margin-bottom: 16px;
    overflow: hidden;
    transition: all 0.2s ease;
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.06), inset 0 1px 0 rgba(255, 255, 255, 0.9);
  }
  .hb-homev2-faq-item:hover { 
    border-color: var(--hb-primary); 
    background: linear-gradient(135deg, rgba(255, 255, 255, 1) 0%, rgba(248, 250, 252, 1) 100%);
    box-shadow: 0 8px 24px rgba(219, 138, 22, 0.12), inset 0 1px 0 rgba(255, 255, 255, 0.95);
    transform: translateY(-2px);
  }
  .hb-homev2-faq-question { padding: clamp(20px, 3vw, 24px); font-weight: 600; cursor: pointer; list-style: none; user-select: none; }
  .hb-homev2-faq-question::-webkit-details-marker { display: none; }
  .hb-homev2-faq-answer { padding: 0 clamp(20px, 3vw, 24px) clamp(20px, 3vw, 24px); color: var(--hb-muted); }

  @media (max-width: 768px) {
    .hb-homev2 {
      padding: 0 16px;
    }
    .hb-homev2-wizard { 
      grid-template-columns: 1fr; 
      gap: 20px;
    }
    .hb-homev2-action-grid { 
      grid-template-columns: 1fr; 
      gap: 12px;
    }
    .hb-homev2-loginbar { 
      flex-direction: column; 
      align-items: stretch; 
      gap: 16px;
      padding: 20px;
    }
    .hb-homev2-loginbar-content {
      flex-direction: column;
      text-align: center;
      gap: 12px;
    }
    .hb-homev2-loginbar-icon {
      align-self: center;
    }
    .hb-homev2-loginbar-text {
      text-align: center;
    }
    .hb-homev2-loginbar-actions { 
      justify-content: center;
      width: 100%;
    }
    .hb-homev2-loginbar-actions .hb-homev2-btn-primary,
    .hb-homev2-loginbar-actions .hb-homev2-btn-secondary {
      flex: 1;
      min-width: 0;
    }
  }

  .hb-homev2 { text-align: center; }
  .hb-homev2 * { box-sizing: border-box; }

  .hb-homev2-centerbox { max-width: 820px; margin: 0 auto; }
  .hb-homev2-lead { margin: 0 0 18px; color: var(--hb-muted); font-size: 16px; }


  .hb-homev2-action-tray-header { display:none; } /* מוריד את הכותרת ה"שחורה" של ה-tray */
  .hb-homev2-action-texts h4 { 
    margin: 0 0 6px 0; 
    font-size: 16px; 
    font-weight: 600;
    padding-right: 60px; /* Space for tag */
    word-wrap: break-word;
    overflow-wrap: break-word;
  }
  @media (max-width: 768px) {
    .hb-homev2-action-texts h4 {
      padding-right: 70px; /* More space on mobile */
      min-height: 2.5em; /* Ensure enough height for 2 lines */
    }
  }
  .hb-homev2-action-texts p { margin: 0; color: var(--hb-muted); font-size: 14px; }


  .hb-homev2 a:focus-visible,
  .hb-homev2 button:focus-visible,
  .hb-homev2 summary:focus-visible {
    outline: 2px solid rgba(219,138,22,.45);
    outline-offset: 2px;
    border-radius: 8px;
  }

  /* ===== Layout sanity ===== */
  .hb-homev2 { 
    direction: rtl;
    text-align: center;
    margin: 0 auto;
  }
  .hb-homev2 .hb-homev2-hero-intro,
  .hb-homev2 .hb-homev2-action-card,
  .hb-homev2 .hb-homev2-faq-item,
  .hb-homev2 .hb-homev2-testimonial {
    text-align: right; /* תוכן בתוך כרטיסים נקרא טוב יותר כך */
  }

  /* titles ALWAYS centered */
  .hb-homev2 .hb-homev2-section-title,
  .hb-homev2 .hb-homev2-hero,
  .hb-homev2 .hb-homev2-hero-subtitle {
    text-align: center !important;
  }

  .hb-homev2 .hb-homev2-btn-row{
    display:flex;
    gap:12px;
    justify-content:center;
    align-items:center;
    flex-wrap:wrap;
    margin-top: 14px;
  }
  @media (max-width: 768px){
    .hb-homev2 .hb-homev2-btn-row{
      flex-direction: column;
    }
  }

  /* ===== Action cards (modern) ===== */
  .hb-homev2 .hb-homev2-action-grid{
    max-width: 980px;
    margin: 0 auto;
    gap: 14px;
  }

  .hb-homev2 .hb-homev2-action-card{
    border-radius: var(--hb-radius-lg);
    border: 2px solid var(--hb-border);
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.06), inset 0 1px 0 rgba(255, 255, 255, 0.9);
    padding: clamp(20px, 3vw, 24px);
    background: linear-gradient(135deg, rgba(255, 255, 255, 0.98) 0%, rgba(248, 250, 252, 0.95) 100%);
    position: relative;
    overflow: hidden;
    transition: var(--hb-transition);
  }

  .hb-homev2 .hb-homev2-action-card::before {
    content: "";
    position: absolute;
    top: -50%;
    left: -50%;
    width: 200%;
    height: 200%;
    background: radial-gradient(circle, rgba(219, 138, 22, 0.08) 0%, transparent 70%);
    opacity: 0;
    transition: var(--hb-transition);
  }

  .hb-homev2 .hb-homev2-action-card::after{
    content: "→";
    position: absolute;
    left: 20px;
    top: 50%;
    transform: translateY(-50%);
    font-size: 20px;
    color: var(--hb-muted);
    transition: var(--hb-transition);
    font-weight: 300;
    z-index: 1;
  }

  .hb-homev2 .hb-homev2-action-card:hover{
    transform: translateY(-3px);
    box-shadow: 0 12px 40px rgba(219, 138, 22, 0.15), inset 0 1px 0 rgba(255, 255, 255, 0.95);
    border-color: var(--hb-primary);
    background: linear-gradient(135deg, rgba(255, 255, 255, 1) 0%, rgba(248, 250, 252, 1) 100%);
  }
  .hb-homev2 .hb-homev2-action-card:hover::before {
    opacity: 1;
  }
  .hb-homev2 .hb-homev2-action-card:hover::after{
    transform: translateY(-50%) translateX(-4px);
    color: var(--hb-primary);
  }

  .hb-homev2 .hb-homev2-daily-wrap{
    margin: 24px auto 0;
    max-width: 980px;
    border: 1.5px solid var(--hb-border);
    border-radius: var(--hb-radius-lg);
    padding: clamp(20px, 3vw, 28px);
    box-shadow: var(--hb-shadow);
    background: #fff;
    max-height: 600px;
    overflow: auto;
    scrollbar-width: thin;
    scrollbar-color: var(--hb-border) transparent;
  }
  .hb-homev2 .hb-homev2-daily-wrap::-webkit-scrollbar {
    width: 8px;
  }
  .hb-homev2 .hb-homev2-daily-wrap::-webkit-scrollbar-track {
    background: transparent;
  }
  .hb-homev2 .hb-homev2-daily-wrap::-webkit-scrollbar-thumb {
    background: var(--hb-border);
    border-radius: 4px;
  }
  .hb-homev2 .hb-homev2-lead{
    color: var(--hb-text-secondary);
    margin: 0 0 24px 0;
    font-size: clamp(16px, 2vw, 18px);
    line-height: 1.7;
  }

  /* Daily toggle button - more prominent */
  .hb-homev2 .hb-homev2-daily-toggle {
    min-width: 200px;
    font-size: 16px;
    padding: 14px 28px;
    box-shadow: var(--hb-shadow-md) !important;
  }
  .hb-homev2 .hb-homev2-daily-toggle:hover {
    box-shadow: var(--hb-shadow-lg) !important;
    transform: translateY(-3px);
  }

  /* Weekly teaser uses loginbar styling */
  .hb-homev2-weekly-teaser {
    margin: 0 auto 40px;
    max-width: 900px;
  }

  /* Weekly box improvements */
  .hb-homev2-weekly-content {
    background: linear-gradient(135deg, rgba(255, 255, 255, 0.98) 0%, rgba(248, 250, 252, 0.95) 100%);
    border: 2px solid var(--hb-ring);
    border-radius: var(--hb-radius-lg);
    padding: clamp(28px, 4vw, 36px);
    box-shadow: 0 12px 32px rgba(219,138,22,0.15), inset 0 1px 0 rgba(255, 255, 255, 0.9);
    position: relative;
    overflow: hidden;
    transition: all 0.3s ease;
  }
  .hb-homev2-weekly-content::before {
    content: "";
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: linear-gradient(90deg, transparent, var(--hb-primary), transparent);
    opacity: 0.6;
  }
  .hb-homev2-weekly-content:hover {
    box-shadow: 0 16px 40px rgba(219,138,22,0.25), inset 0 1px 0 rgba(255, 255, 255, 0.95);
    transform: translateY(-2px);
  }
  .hb-homev2-weekly-content .hb-homev2-btn-primary {
    min-width: auto;
    padding: 12px 24px;
    font-size: 15px;
  }
  html body .hb-homev2 .hb-homev2-weekly-content .hb-homev2-btn-primary,
  html body .hb-homev2 .hb-homev2-weekly-content .hb-homev2-btn-primary * {
    color: #fff !important;
  }
  html body .hb-homev2 .hb-homev2-loginbar-actions .hb-homev2-btn-primary,
  html body .hb-homev2 .hb-homev2-loginbar-actions .hb-homev2-btn-primary * {
    color: #fff !important;
  }
  .hb-homev2-weekly-content .hb-homev2-lead {
    margin-bottom: 20px;
    font-size: clamp(15px, 1.9vw, 16px);
    line-height: 1.7;
  }

  /* ===== FAQ modern accordion ===== */
  .hb-homev2 .hb-homev2-faq-list{
    max-width: 900px;
    margin: 0 auto;
    text-align: right;
  }
  .hb-homev2 .hb-homev2-faq-item{
    border-radius: var(--hb-radius-lg);
    border: 1.5px solid var(--hb-border);
    box-shadow: var(--hb-shadow-sm);
    margin-bottom: 16px;
    background: #fff;
    transition: var(--hb-transition);
    overflow: hidden;
  }
  .hb-homev2 .hb-homev2-faq-item:hover {
    border-color: var(--hb-primary);
    box-shadow: var(--hb-shadow);
  }
  .hb-homev2 .hb-homev2-faq-question{
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap: 16px;
    padding: clamp(20px, 3vw, 24px);
    font-weight: 600;
    font-size: clamp(16px, 2vw, 18px);
    color: var(--hb-text);
    cursor: pointer;
    list-style: none;
    user-select: none;
  }
  .hb-homev2 .hb-homev2-faq-question::-webkit-details-marker { display: none; }
  .hb-homev2 .hb-homev2-faq-question::after{
    content:"+";
    font-weight: 300;
    font-size: 24px;
    color: var(--hb-primary);
    transition: var(--hb-transition);
    flex-shrink: 0;
    width: 28px;
    height: 28px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    background: var(--hb-primary-light);
  }
  .hb-homev2 details[open] .hb-homev2-faq-question::after{
    content:"–";
    transform: rotate(180deg);
  }
  .hb-homev2 .hb-homev2-faq-answer { 
    padding: 0 clamp(20px, 3vw, 24px) clamp(20px, 3vw, 24px); 
    color: var(--hb-text-secondary); 
    line-height: 1.7;
    font-size: clamp(15px, 1.8vw, 16px);
  }

  .hb-homev2 .hb-homev2-testimonials{
    text-align:center;
  }
  .hb-homev2 .hb-homev2-testimonials-grid{
    max-width: 1000px;
    margin: 0 auto;
    display:grid;
    gap: 16px;
  }
  @media (min-width: 769px) {
    .hb-homev2 .hb-homev2-testimonials-grid {
      grid-template-columns: repeat(2, 1fr);
      gap: 20px;
    }
  }
  .hb-homev2 .hb-homev2-testimonial{
    border-radius: var(--hb-radius-lg);
    border: 2px solid var(--hb-border);
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.06), inset 0 1px 0 rgba(255, 255, 255, 0.9);
    background: linear-gradient(135deg, rgba(255, 255, 255, 0.98) 0%, rgba(248, 250, 252, 0.95) 100%);
    transition: var(--hb-transition);
    overflow: hidden;
    position: relative;
  }
  .hb-homev2 .hb-homev2-testimonial::before {
    content: "";
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: linear-gradient(90deg, transparent, var(--hb-primary), transparent);
    opacity: 0;
    transition: var(--hb-transition);
  }
  .hb-homev2 .hb-homev2-testimonial:hover {
    border-color: var(--hb-primary);
    box-shadow: 0 8px 24px rgba(219, 138, 22, 0.12), inset 0 1px 0 rgba(255, 255, 255, 0.95);
    transform: translateY(-2px);
  }
  .hb-homev2 .hb-homev2-testimonial:hover::before {
    opacity: 0.8;
  }
  .hb-homev2 .hb-homev2-testimonial summary{
    padding: clamp(20px, 3vw, 24px);
    cursor: pointer;
    list-style: none;
    display: grid;
    gap: 8px;
    position: relative;
    padding-right: 50px;
  }
  .hb-homev2 .hb-homev2-testimonial summary::-webkit-details-marker { display: none; }
  .hb-homev2 .hb-homev2-testimonial summary::after {
    content: "▼";
    position: absolute;
    left: 20px;
    top: 50%;
    transform: translateY(-50%);
    font-size: 12px;
    color: var(--hb-primary);
    transition: var(--hb-transition);
    opacity: 0.6;
  }
  .hb-homev2 .hb-homev2-testimonial[open] summary::after {
    transform: translateY(-50%) rotate(180deg);
    opacity: 1;
  }
  .hb-homev2 .hb-homev2-testimonial summary strong {
    font-size: clamp(16px, 2vw, 18px);
    font-weight: 700;
    color: var(--hb-text);
  }
  .hb-homev2 .hb-homev2-testimonial summary span {
    color: var(--hb-text-secondary);
    font-size: clamp(14px, 1.8vw, 15px);
    line-height: 1.6;
  }
  .hb-homev2 .hb-homev2-testimonial-body{
    padding: 0 clamp(20px, 3vw, 24px) clamp(20px, 3vw, 24px);
    color: var(--hb-text-secondary);
    line-height: 1.7;
    font-size: clamp(15px, 1.8vw, 16px);
    border-top: 1px solid var(--hb-border-light);
    margin-top: 12px;
    padding-top: 16px;
  }
  </style>
  <?php
  return ob_get_clean();
}

/* ---------------------------------------------------------
 * 8) JS (reads from boot node)
 * --------------------------------------------------------- */
function hb_homev2_get_js() {
  ob_start();
  ?>
  <script>
  (function() {
    'use strict';

    function initHBHomeV2(root) {
      root = root || document;

      // find boot anywhere (Elementor sometimes changes nesting)
      const boot = root.querySelector('.hb-homev2-boot') || document.querySelector('.hb-homev2-boot');
      if (!boot) return;

      // scope: nearest .hb-homev2 if exists, otherwise document
      const scope = boot.closest('.hb-homev2') || document;

      // prevent double-binding
      if (scope.__hbHomeV2Bound) return;
      scope.__hbHomeV2Bound = true;

      // Check login status dynamically (in case of cache issues)
      function checkLoggedInStatus() {
        // Method 1: Check from boot attribute (PHP-set, most reliable - set in real-time)
        const bootLoggedIn = boot.getAttribute('data-logged-in') === '1';
        
        // Method 2: Check body class (WordPress sets this, but may be cached)
        const bodyHasLoggedIn = document.body.classList.contains('logged-in');
        
        // Method 3: Check if user menu/astronaut icon exists
        const hasUserMenu = document.querySelector('.usermenu-icon, .elementor-menu-item:has(a[href*="/user/"])');
        
        // Method 4: Check for other logged-in indicators
        const hasLoggedInIndicator = document.querySelector('body.logged-in') ||
                                     document.querySelector('.wpum-account-page') ||
                                     document.querySelector('[data-hb-url*="/user/"]');
        
        // Method 5: Check if there's a logout link (indicates logged in)
        const hasLogoutLink = document.querySelector('a[href*="logout"], a[href*="התנתקות"]');
        
        // Priority: boot attribute is most reliable (set by PHP in real-time)
        // Fallback to body class and other indicators
        const isLoggedIn = bootLoggedIn || bodyHasLoggedIn || !!hasUserMenu || !!hasLoggedInIndicator || !!hasLogoutLink;
        
        // Update boot attribute if other indicators say logged in but boot doesn't
        if (!bootLoggedIn && (bodyHasLoggedIn || hasUserMenu || hasLogoutLink)) {
          boot.setAttribute('data-logged-in', '1');
        }
        
        return isLoggedIn;
      }
      
      let isLoggedIn = checkLoggedInStatus();
      
      // Update login status periodically (in case of dynamic changes)
      function updateLoginStatus() {
        const newStatus = checkLoggedInStatus();
        if (newStatus !== isLoggedIn) {
          console.log('🔄 Login status changed: ' + isLoggedIn + ' -> ' + newStatus);
          isLoggedIn = newStatus;
          // Update boot attribute
          boot.setAttribute('data-logged-in', isLoggedIn ? '1' : '0');
          // Re-render action tray if it exists (to update links based on new login status)
          // Only update if action tray is already visible (has selected card)
          const selectedCard = scope.querySelector('.hb-homev2-wizard-card.hb-homev2-wizard-selected');
          if (selectedCard && actionTray) {
            const routeId = selectedCard.getAttribute('data-route');
            if (routeId && routes[routeId]) {
              try {
                actionTray.innerHTML = createActionTray(routes[routeId]);
              } catch (e) {
                console.error('Error updating action tray:', e);
              }
            }
          }
        }
      }
      
      // Check login status on page load and periodically
      setTimeout(updateLoginStatus, 100);
      setTimeout(updateLoginStatus, 500);
      setTimeout(updateLoginStatus, 1000);
      
      // Also check on DOM mutations (in case menu is loaded dynamically)
      // But throttle to avoid excessive updates
      let mutationTimeout;
      if (typeof MutationObserver !== 'undefined') {
        const observer = new MutationObserver(function() {
          clearTimeout(mutationTimeout);
          mutationTimeout = setTimeout(function() {
            updateLoginStatus();
          }, 500); // Throttle to max once per 500ms
        });
        observer.observe(document.body, { childList: true, subtree: true });
      }
      
      const getUrl = function(key) {
        return boot.getAttribute('data-' + key.replace(/_/g, '-')) || '#';
      };

      const routes = {
        beginner: {
          title: "מתחילים",
          subtitle: "סט כלים עדין להתחלה טובה",
          actions: [
            { text: "חידה יומית", desc: "דקה של חשיבה כל יום", href: "#daily-puzzle" },
            { text: "תשבצי היגיון לדוגמה", desc: "טעימה לפני שמעמיקים", dataAttr: "samples-url" },
            { text: "מילת היום", desc: "אתגר יומי קצר", dataAttr: "wordle-url" },
            { text: "קורס תשבצי היגיון", desc: "לומדים את השיטה", dataAttr: "course-url" },
            { text: "משחקי חשיבה", desc: "גיוון קצר בין תשבצים", dataAttr: "games-url" }
          ]
        },
        intermediate: {
          title: "מתקדמים",
          subtitle: "למי שכבר פתר ורוצה עקביות",
          actions: [
            { text: "התשבץ השבועי", desc: "מנויים + ארכיון", dataAttr: "weekly-url" },
            { text: "ארכיון תשבצי ההיגיון המלא", desc: "גישה לכל התשבצים הקודמים", dataAttr: "archive-url" },
            { text: "מידע / הצטרפות למנוי", desc: "כל הפרטים במקום אחד", dataAttr: "sub-url" },
            { text: "פרוייקט הניצוץ האנושי", desc: "איך AI מתמודד עם הגדרות היגיון?", dataAttr: "human-spark-url" },
            { text: "מציאת מילים", desc: "עוזר כשנתקעים", dataAttr: "word-finder-url" },
            { text: "בלוג", desc: "טיפים, מחשבה ושיטה", dataAttr: "blog-url" },
            { text: "תשבץ אוטומטי", desc: "תשבץ רגיל (לא תשבץ היגיון)", dataAttr: "auto-crossword-url" }
          ]
        },
        expert: {
          title: "מומחים",
          subtitle: "למי שמכוון לרמה גבוהה",
          actions: [
            { text: "התשבץ השבועי", desc: "אתגר קבוע + ארכיון", dataAttr: "weekly-url" },
            { text: "ארכיון תשבצי ההיגיון המלא", desc: "גישה לכל התשבצים הקודמים", dataAttr: "archive-url" },
            { text: "פרוייקט הניצוץ האנושי", desc: "איך AI מתמודד עם הגדרות היגיון?", dataAttr: "human-spark-url" },
            { text: "קורס תשבצי היגיון", desc: "דקויות, טכניקות, תבניות", dataAttr: "course-url" },
            { text: "מציאת מילים", desc: "כלי עבודה לפתרון", dataAttr: "word-finder-url" }
          ]
        }
      };

      let actionTray = scope.querySelector('.hb-homev2-action-tray');

      function createActionTray(routeConfig) {
        if (!routeConfig || !routeConfig.actions) return '';

        let html = `
          <div class="hb-homev2-action-tray-header">
            <h3 class="hb-homev2-action-tray-title">${routeConfig.title}</h3>
            <p class="hb-homev2-action-tray-subtitle">${routeConfig.subtitle || ''}</p>
          </div>
          <div class="hb-homev2-action-grid">
        `;

        // Check login status and membership once for all actions
        const currentLoggedIn = checkLoggedInStatus();
        const hasWeeklyMembership = boot.getAttribute('data-has-weekly-membership') === '1';

        routeConfig.actions.forEach(function(action) {
          // Skip members-only actions if user is not logged in
          if (action.membersOnly && !currentLoggedIn) {
            return;
          }

          let href = action.href || '';
          if (action.dataAttr && !href) href = getUrl(action.dataAttr);

          // Redirect weekly-url and archive-url based on membership status
          if (action.dataAttr === 'weekly-url') {
            href = hasWeeklyMembership ? getUrl('weekly-url') : getUrl('sub-url');
          }
          
          if (action.dataAttr === 'archive-url') {
            href = hasWeeklyMembership ? getUrl('archive-url') : getUrl('sub-url');
          }
          
          if (!href || href === '#') return;

          const tag = action.tag ? `<span class="hb-homev2-action-tag">${action.tag}</span>` : '';
          const desc = action.desc || '';

          html += `
            <a class="hb-homev2-action-card" href="${href}">
              ${tag}
              <div class="hb-homev2-action-top">
                <div class="hb-homev2-action-texts">
                  <h4>${action.text}</h4>
                  <p>${desc}</p>
                </div>
              </div>
            </a>
          `;
        });

        html += '</div>';
        return html;
      }

      function setVisible(selector, on) {
        const el = scope.querySelector(selector) || document.querySelector(selector);
        if (!el) return;
        if (on) el.classList.add('hb-homev2-section-visible');
        else el.classList.remove('hb-homev2-section-visible');
      }

      function ensureTray() {
        if (actionTray) return actionTray;

        const wizard = scope.querySelector('.hb-homev2-wizard') || document.querySelector('.hb-homev2-wizard');
        actionTray = document.createElement('section');
        actionTray.className = 'hb-homev2-action-tray hb-homev2-section-collapsible hb-homev2-section-visible';

        if (wizard && wizard.parentNode) {
          wizard.insertAdjacentElement('afterend', actionTray);
        } else {
          (scope.querySelector('.hb-homev2') || document.body).appendChild(actionTray);
        }
        return actionTray;
      }

      // attach clicks (robust: event delegation)
      scope.addEventListener('click', function(e) {
        const btn = e.target.closest('.hb-homev2-wizard-card');
        if (!btn) return;

        const routeId = btn.getAttribute('data-route');
        if (!routeId || !routes[routeId]) return;

        // Update login status before creating tray
        updateLoginStatus();

        // selected style
        const all = scope.querySelectorAll('.hb-homev2-wizard-card');
        all.forEach(c => c.classList.remove('hb-homev2-wizard-selected'));
        btn.classList.add('hb-homev2-wizard-selected');

        // show/hide collapsibles (but don't auto-open daily puzzle)
        setVisible('#daily-puzzle', routeId === 'beginner');
        setVisible('#weekly-club', routeId === 'intermediate' || routeId === 'expert');

        // Create and show action tray immediately
        const tray = ensureTray();
        try {
          tray.innerHTML = createActionTray(routes[routeId]);
          tray.classList.add('hb-homev2-section-visible');
        } catch (e) {
          console.error('Error creating action tray for route:', routeId, e);
          // Fallback: show error message
          tray.innerHTML = '<div class="hb-homev2-action-tray-header"><h3>שגיאה בטעינת התוכן</h3><p>אנא רענן את הדף</p></div>';
          tray.classList.add('hb-homev2-section-visible');
        }

        // Scroll to action tray (not daily puzzle)
        setTimeout(function() {
          tray.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }, 60);
      });
      
      // Fix action card links on click (ensure correct URLs based on login status)
      scope.addEventListener('click', function(e) {
        const actionCard = e.target.closest('.hb-homev2-action-card');
        if (!actionCard) return;
        
        // Update login status before navigation
        updateLoginStatus();
        const currentLoggedIn = checkLoggedInStatus();
        const hasWeeklyMembership = boot.getAttribute('data-has-weekly-membership') === '1';
        
        const href = actionCard.getAttribute('href');
        if (!href || href === '#') return;
        
        // Fix weekly-url links - redirect based on membership status
        if (href.includes('weekly') || actionCard.textContent.includes('תשבץ השבועי')) {
          const correctUrl = hasWeeklyMembership ? getUrl('weekly-url') : getUrl('sub-url');
          if (href !== correctUrl) {
            actionCard.setAttribute('href', correctUrl);
            // Allow navigation to proceed
            return;
          }
        }
        
        // Fix archive-url links - redirect based on membership status
        if (href.includes('archive') || href.includes('תשבצים') || actionCard.textContent.includes('ארכיון')) {
          const correctUrl = hasWeeklyMembership ? getUrl('archive-url') : getUrl('sub-url');
          if (href !== correctUrl) {
            actionCard.setAttribute('href', correctUrl);
            // Allow navigation to proceed
            return;
          }
        }
      });

      // Daily toggle (compact)
      scope.addEventListener('click', function(e){
        const t = e.target.closest('.hb-homev2-daily-toggle');
        if (!t) return;
        const wrap = scope.querySelector('.hb-homev2-daily-wrap');
        if (!wrap) return;
        wrap.hidden = !wrap.hidden;
        if (!wrap.hidden) wrap.scrollIntoView({behavior:'smooth', block:'start'});
      });
    }

    // Run on DOM ready
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', function() { initHBHomeV2(document); });
    } else {
      initHBHomeV2(document);
    }

    // Elementor frontend hook (if exists)
    if (window.elementorFrontend && window.elementorFrontend.hooks) {
      try {
        window.elementorFrontend.hooks.addAction('frontend/element_ready/global', function($scope) {
          initHBHomeV2($scope && $scope[0] ? $scope[0] : document);
        });
      } catch (e) {}
    }

  })();
  </script>
  <?php
  return ob_get_clean();
}
