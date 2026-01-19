<?php
/**
 * Plugin Name: HB Cognitive Training (Senior Track)
 * Description: מערכת אימון מוח מינימלית - מסלול גיל שלישי. משחק Go/No-Go עם ציונים, סיכום יומי ופרופיל 7 ימים.
 * Version: 1.0.0
 * Author: Goodlife
 */

if (!defined('ABSPATH')) exit;

/* ---------------------------------------------------------
 * 1) הגדרות בסיסיות
 * --------------------------------------------------------- */
define('HB_COG_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('HB_COG_PLUGIN_URL', plugin_dir_url(__FILE__));

/* ---------------------------------------------------------
 * 0.5) קונפיג משקלים למשחקים ותחומים
 * --------------------------------------------------------- */
// משקלים למשחקים (לחישוב ציון יומי משוקלל)
define('HB_COG_GAME_WEIGHTS', [
  'go_nogo' => 1.0,
  'nback1' => 1.0,
  'stroop' => 1.0,
  'visual_search' => 1.0,
]);

// מפת תוויות תחומים
define('HB_COG_DOMAIN_LABELS', [
  'attention' => 'קשב',
  'reasoning_flexibility' => 'גמישות מחשבתית',
  'processing_speed' => 'מהירות עיבוד',
  'working_memory' => 'זיכרון עבודה',
  'visual_perception' => 'תפיסה חזותית',
  'inhibition' => 'עכבה',
]);

define('HB_COG_CATEGORY_PAGES', [
  'attention' => [
    'title' => 'קשב',
    'url' => '/אימון-קשב/'
  ],
  'inhibition' => [
    'title' => 'עכבה',
    'url' => '/אימון-עכבה/'
  ],
  'processing_speed' => [
    'title' => 'מהירות עיבוד',
    'url' => '/אימון-מהירות-עיבוד/'
  ],
  'working_memory' => [
    'title' => 'זיכרון עבודה',
    'url' => '/אימון-זיכרון-עבודה/'
  ],
  'reasoning_flexibility' => [
    'title' => 'גמישות מחשבתית',
    'url' => '/אימון-גמישות-מחשבתית/'
  ],
]);

define('HB_COG_GAMES_BY_DOMAIN', [
  'attention' => [
    ['game_id' => 'go_nogo', 'title' => 'Go/No-Go', 'difficulty_min' => 1],
    ['game_id' => 'visual_search', 'title' => 'חיפוש ויזואלי', 'difficulty_min' => 1],
  ],
  'inhibition' => [
    ['game_id' => 'go_nogo', 'title' => 'Go/No-Go', 'difficulty_min' => 1],
    ['game_id' => 'stroop', 'title' => 'מבחן Stroop', 'difficulty_min' => 1],
  ],
  'processing_speed' => [
    ['game_id' => 'go_nogo', 'title' => 'Go/No-Go', 'difficulty_min' => 1],
    ['game_id' => 'stroop', 'title' => 'מבחן Stroop', 'difficulty_min' => 1],
    ['game_id' => 'visual_search', 'title' => 'חיפוש ויזואלי', 'difficulty_min' => 1],
  ],
  'working_memory' => [
    ['game_id' => 'nback1', 'title' => 'N-Back 1', 'difficulty_min' => 1],
  ],
  'reasoning_flexibility' => [
    ['game_id' => 'stroop', 'title' => 'מבחן Stroop', 'difficulty_min' => 1],
  ],
]);

define('HB_COG_GAME_PAGES', [
  'go_nogo' => '/אימון-קוגניטיבי/',
  'stroop' => '/אימון-קוגניטיבי/',
  'nback1' => '/אימון-קוגניטיבי/',
  'visual_search' => '/אימון-קוגניטיבי/',
]);

// Game registry: game_id => title
define('HB_COG_GAME_REGISTRY', [
  'go_nogo' => 'Go/No-Go',
  'stroop' => 'מבחן Stroop',
  'nback1' => 'N-Back 1',
  'visual_search' => 'חיפוש ויזואלי',
]);

/* ---------------------------------------------------------
 * 0) יצירת טבלאות DB
 * --------------------------------------------------------- */
register_activation_hook(__FILE__, 'hb_cog_create_tables');

// ===== DB versioning / upgrade =====
add_action('plugins_loaded', 'hb_cog_maybe_upgrade_db', 5);

function hb_cog_maybe_upgrade_db() {
  $ver = (int) get_option('hb_cog_db_version', 1);

  // גרסה 2: נוספו attempts_count + games ל-hb_cog_daily
  if ($ver < 2) {
    hb_cog_create_tables(); // dbDelta יעדכן טבלאות קיימות
    update_option('hb_cog_db_version', 2, true);
  }
}

function hb_cog_create_tables() {
  global $wpdb;

  $charset_collate = $wpdb->get_charset_collate();
  $table_attempts = $wpdb->prefix . 'hb_cog_attempts';
  $table_daily = $wpdb->prefix . 'hb_cog_daily';

  $sql_attempts = "CREATE TABLE $table_attempts (
    id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id bigint(20) UNSIGNED NOT NULL,
    track varchar(50) NOT NULL DEFAULT 'senior',
    game_id varchar(50) NOT NULL,
    difficulty int(11) NOT NULL DEFAULT 1,
    attempt_no int(11) NOT NULL DEFAULT 1,
    started_at datetime NOT NULL,
    ended_at datetime NOT NULL,
    date_iso date NOT NULL,
    metrics longtext,
    scores longtext,
    domain_contrib longtext,
    created_at datetime DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY user_id (user_id),
    KEY date_iso (date_iso),
    KEY game_id (game_id)
  ) $charset_collate;";

  // ✅ נוספו: attempts_count, games
  $sql_daily = "CREATE TABLE $table_daily (
    id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id bigint(20) UNSIGNED NOT NULL,
    date_iso date NOT NULL,
    track varchar(50) NOT NULL DEFAULT 'senior',
    daily_score int(11) NOT NULL DEFAULT 0,
    attempts_count int(11) NOT NULL DEFAULT 0,
    domains longtext,
    games longtext,
    updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY user_date (user_id, date_iso),
    KEY date_iso (date_iso)
  ) $charset_collate;";

  require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
  dbDelta($sql_attempts);
  dbDelta($sql_daily);
}

/* ---------------------------------------------------------
 * 2) טעינת נכסים (CSS/JS) - הכל inline בקובץ אחד
 * --------------------------------------------------------- */
function hb_cog_page_has_shortcodes() {
  if (!is_singular()) return false;

  $post_id = get_queried_object_id();
  if (!$post_id) return false;

  // Fallback: אם זה עמוד "user" (אזור אישי), תמיד לטעון נכסים
  $post_slug = get_post_field('post_name', $post_id);
  if ($post_slug === 'user' || $post_slug === 'אזור-אישי-חדש') {
    return true;
  }

  $content = (string) get_post_field('post_content', $post_id);

  // Elementor: גם builder content וגם ה-JSON של _elementor_data (הכי אמין לזיהוי shortcodes)
  $elementor_data = get_post_meta($post_id, '_elementor_data', true);
  if (!empty($elementor_data)) {
    $content .= ' ' . $elementor_data;
  }

  if (did_action('elementor/loaded') && class_exists('\Elementor\Plugin')) {
    try {
      if (\Elementor\Plugin::$instance->db->is_built_with_elementor($post_id)) {
        $content .= ' ' . \Elementor\Plugin::$instance->frontend->get_builder_content_for_display($post_id);
      }
    } catch (\Throwable $e) {}
  }

  // כלל זהב: אם יש hb_cog_ בכל מקום – נטען נכסים (כולל shortcodes חדשים: game_page/category וכו')
  if (strpos($content, 'hb_cog_') !== false) {
    return true;
  }

  // fallback ישן (למקרה קצה)
  $need_assets =
    (strpos($content, '[hb_cog_game') !== false) ||
    (strpos($content, '[hb_cog_profile') !== false) ||
    (strpos($content, '[hb_cog_dashboard') !== false) ||
    (strpos($content, '[hb_cog_summary') !== false) ||
    (strpos($content, '[hb_account_dashboard') !== false);

  return $need_assets;
}

// CSS inline (head)
add_action('wp_head', function () {
  if (!hb_cog_page_has_shortcodes()) return;

  echo "\n<!-- HB_COG: style injected -->\n";

  $css = <<<'CSS'
.hb-cog-game-container,
.hb-cog-profile-container,
.hb-cog-game-stats-container {
  direction: rtl;
  font-family: system-ui, -apple-system, "Segoe UI", Roboto, "Noto Sans Hebrew", Arial, sans-serif;
  max-width: 800px;
  margin: 20px auto;
  padding: 20px;
}

.hb-cog-game-stats-container {
  margin-top: 40px;
  border-top: 2px solid #e0e0e0;
  padding-top: 30px;
}

.hb-cog-game-stats-header {
  margin-bottom: 20px;
}

.hb-cog-game-stats-header h3 {
  font-size: 24px;
  color: #2e7d32;
  margin-bottom: 15px;
}

.hb-cog-game-stats-date-selector {
  display: flex;
  gap: 10px;
  align-items: center;
  flex-wrap: wrap;
  margin-bottom: 20px;
}

.hb-cog-game-stats-date-selector label {
  font-weight: 600;
}

.hb-cog-stats-date-input {
  padding: 8px 12px;
  border: 1px solid #ddd;
  border-radius: 4px;
  font-size: 16px;
}

.hb-cog-stats-load-btn {
  padding: 8px 16px;
  background: #2e7d32;
  color: #fff;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  font-size: 16px;
}

.hb-cog-stats-load-btn:hover {
  background: #1b5e20;
}

.hb-cog-game-stats-loading {
  text-align: center;
  padding: 20px;
  color: #666;
}

.hb-cog-game-stats-content {
  margin-top: 20px;
}

.hb-cog-stats-section {
  margin-bottom: 30px;
}

.hb-cog-stats-section h4 {
  font-size: 20px;
  color: #333;
  margin-bottom: 15px;
}

.hb-cog-stats-table {
  overflow-x: auto;
}

.hb-cog-stats-table table {
  width: 100%;
  border-collapse: collapse;
  background: #fff;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.hb-cog-stats-table th {
  background: #2e7d32;
  color: #fff;
  padding: 12px;
  text-align: right;
  font-weight: 600;
}

.hb-cog-stats-table td {
  padding: 10px 12px;
  border-bottom: 1px solid #e0e0e0;
  text-align: right;
}

.hb-cog-stats-table tr:hover {
  background: #f5f5f5;
}

.hb-cog-stats-no-data,
.hb-cog-stats-error {
  text-align: center;
  padding: 30px;
  color: #666;
  font-size: 18px;
}

.hb-cog-stats-error {
  color: #c62828;
}

.hb-cog-game-loading,
.hb-cog-profile-loading {
  text-align: center;
  padding: 40px;
  color: #666;
  font-size: 18px;
}

.hb-cog-game-header {
  text-align: center;
  margin-bottom: 30px;
}

.hb-cog-game-header h3 {
  font-size: 32px;
  font-weight: 700;
  color: #2e7d32;
  margin: 0 0 12px 0;
}

.hb-cog-instructions {
  font-size: 20px;
  color: #333;
  margin-bottom: 20px;
  line-height: 1.6;
}

.hb-cog-game-area {
  display: block;
  text-align: center;
  padding: 40px 20px;
  background: #f9f9f9;
  border-radius: 12px;
  margin: 20px 0;
}

.hb-cog-timer {
  font-size: 48px;
  font-weight: 800;
  color: #2e7d32;
  margin: 20px 0;
}

.hb-cog-stimulus-area {
  margin: 40px 0;
  min-height: 200px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.hb-cog-stimulus {
  display: none;
  opacity: 0;
  transition: opacity 0.2s;
  width: 180px;
  height: 180px;
  border-radius: 50%;
  cursor: pointer;
  border: 4px solid #333;
}

.hb-cog-stimulus.is-visible {
  display: block;
  opacity: 1;
}

.hb-cog-stimulus-go {
  background: #4caf50;
  border-color: #2e7d32;
}

.hb-cog-stimulus-nogo {
  background: #f44336;
  border-color: #c62828;
}

.hb-cog-results {
  display: none;
  margin-top: 30px;
  padding: 30px;
  background: #fff;
  border-radius: 12px;
  box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.hb-cog-results.show {
  display: block;
}

.hb-cog-results-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 20px;
  margin-bottom: 30px;
}

.hb-cog-result-card {
  background: linear-gradient(135deg, #f5f7fa 0%, #ffffff 100%);
  padding: 20px;
  border-radius: 12px;
  text-align: center;
  border: 2px solid #e8ecef;
  transition: transform 0.3s, box-shadow 0.3s;
}

.hb-cog-result-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 8px 20px rgba(0,0,0,0.1);
}

.hb-cog-result-label {
  font-size: 14px;
  color: #666;
  margin-bottom: 8px;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.hb-cog-result-value {
  font-size: 36px;
  font-weight: 800;
  color: #2e7d32;
  margin: 0;
}

.hb-cog-result-disclaimer {
  margin-top: 30px;
  padding: 20px;
  background: #fff3cd;
  border-radius: 8px;
  border-right: 4px solid #ffc107;
  font-size: 14px;
  color: #856404;
  text-align: right;
  line-height: 1.6;
}

.hb-cog-profile-container {
  direction: rtl;
  font-family: system-ui, -apple-system, "Segoe UI", Roboto, "Noto Sans Hebrew", Arial, sans-serif;
  max-width: 800px;
  margin: 20px auto;
  padding: 20px;
}

.hb-cog-profile-header {
  text-align: center;
  margin-bottom: 30px;
}

.hb-cog-profile-header h3 {
  font-size: 28px;
  font-weight: 700;
  color: #2e7d32;
  margin: 0 0 12px 0;
}

.hb-cog-profile-stats {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
  gap: 16px;
  margin-bottom: 20px;
}

.hb-cog-stat-item {
  background: #f5f5f5;
  padding: 16px;
  border-radius: 8px;
  text-align: center;
}

.hb-cog-stat-value {
  font-size: 32px;
  font-weight: 800;
  color: #2e7d32;
  margin-bottom: 8px;
}

.hb-cog-stat-label {
  font-size: 14px;
  color: #666;
  font-weight: 600;
}

.hb-cog-profile-table {
  width: 100%;
  border-collapse: collapse;
  background: #fff;
  border-radius: 8px;
  overflow: hidden;
  box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.hb-cog-profile-table th {
  background: #2e7d32;
  color: #fff;
  padding: 12px;
  text-align: right;
  font-weight: 700;
  font-size: 16px;
}

.hb-cog-profile-table td {
  padding: 12px;
  text-align: right;
  border-bottom: 1px solid #e0e0e0;
  font-size: 16px;
}

.hb-cog-profile-table tr:last-child td {
  border-bottom: none;
}

.hb-cog-profile-table tr:hover {
  background: #f5f5f5;
}

.hb-cog-profile-error,
.hb-cog-error {
  text-align: center;
  padding: 20px;
  color: #c0392b;
  background: #ffebee;
  border-radius: 8px;
}

/* ===== Account Dashboard - עיצוב בסגנון עמוד הבית ===== */
.hb-account-dashboard {
  /* Design Tokens 2026 - זהה לעמוד הבית */
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

  direction: rtl;
  font-family: var(--hb-font);
  max-width: 1280px;
  margin: 0 auto;
  padding: clamp(20px, 3vw, 32px) clamp(16px, 4vw, 32px);
  color: var(--hb-text);
  line-height: 1.6;
  background: transparent;
  -webkit-font-smoothing: antialiased;
  -moz-osx-font-smoothing: grayscale;
}

.hb-dashboard-header {
  text-align: center;
  margin-bottom: clamp(20px, 3vw, 28px);
  padding-bottom: 0;
}

.hb-dashboard-header p {
  margin: 0;
  font-size: clamp(17px, 2.2vw, 22px);
  color: var(--hb-text-secondary);
  font-weight: 500;
  line-height: 1.6;
}

.hb-dashboard-header-logo {
  max-width: 100%;
  height: auto;
  max-height: 100px;
  margin: 0 auto 20px;
  display: block;
  width: auto;
}

@media (max-width: 768px) {
  .hb-dashboard-header {
    margin-bottom: clamp(16px, 2.5vw, 24px);
  }
  
  .hb-dashboard-header-logo {
    max-height: 70px;
    margin-bottom: 16px;
  }
  
  .hb-card-header-image {
    max-height: 40px;
    margin-bottom: 10px;
  }
  
  .hb-card-header-image img {
    max-height: 40px;
  }
}

.hb-dashboard-header h2 {
  font-size: clamp(32px, 5vw, 48px);
  font-weight: 800;
  background: linear-gradient(135deg, var(--hb-text) 0%, var(--hb-primary) 50%, #3b82f6 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  color: var(--hb-text); /* Fallback */
  margin: 0 0 16px 0;
  letter-spacing: -0.03em;
  line-height: 1.15;
  position: relative;
}

.hb-dashboard-header h2::after {
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

.hb-dashboard-cards {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(min(100%, 300px), 1fr));
  gap: var(--hb-gap);
  padding: var(--hb-gap) 0;
}

.hb-dashboard-card {
  background: linear-gradient(135deg, var(--hb-bg) 0%, var(--hb-bg-subtle) 100%);
  border: 2px solid var(--hb-border);
  border-radius: var(--hb-radius-lg);
  padding: clamp(28px, 5vw, 36px) clamp(24px, 4vw, 28px);
  display: flex;
  flex-direction: column;
  transition: var(--hb-transition);
  box-shadow: var(--hb-shadow-md);
  min-height: 100%;
  text-align: center;
  align-items: center;
  position: relative;
  overflow: hidden;
}

.hb-card-header-image {
  width: 100%;
  margin-bottom: 12px;
  max-height: 50px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.hb-card-header-image img {
  max-width: 100%;
  height: auto;
  max-height: 50px;
  object-fit: contain;
  display: block;
}

.hb-dashboard-card-inactive {
  opacity: 0.7;
  filter: grayscale(0.3);
}

.hb-card-status-badge {
  display: inline-block;
  padding: 4px 12px;
  background: var(--hb-muted);
  color: #fff;
  border-radius: 999px;
  font-size: 12px;
  font-weight: 600;
  margin-bottom: 12px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.hb-card-button-disabled {
  opacity: 0.5;
  cursor: not-allowed !important;
  pointer-events: none;
}

.hb-dashboard-card::before {
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

.hb-dashboard-card:hover::before {
  opacity: 0.8;
}

.hb-dashboard-card:hover {
  box-shadow: var(--hb-shadow-hover);
  transform: translateY(-4px);
  border-color: var(--hb-primary);
}

.hb-card-icon {
  width: 64px;
  height: 64px;
  background: linear-gradient(135deg, var(--hb-primary) 0%, var(--hb-primary-hover) 100%);
  border-radius: var(--hb-radius);
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 20px;
  color: #ffffff;
  transition: var(--hb-transition);
  flex-shrink: 0;
  box-shadow: 0 4px 12px rgba(219, 138, 22, 0.25);
}

.hb-dashboard-card:hover .hb-card-icon {
  transform: scale(1.05);
  box-shadow: 0 6px 16px rgba(219, 138, 22, 0.35);
}

.hb-dashboard-card h3 {
  font-size: clamp(20px, 3vw, 24px);
  font-weight: 700;
  color: var(--hb-text) !important;
  margin: 0 0 12px 0;
  letter-spacing: -0.3px;
  line-height: 1.5;
  display: block;
}

.hb-dashboard-card p {
  font-size: clamp(14px, 1.8vw, 16px);
  color: var(--hb-text-secondary) !important;
  line-height: 1.6;
  margin: 0 0 20px 0;
  flex-grow: 1;
  font-weight: 400;
}

.hb-card-button {
  display: inline-block;
  background: linear-gradient(135deg, var(--hb-primary) 0%, var(--hb-primary-hover) 100%);
  color: #ffffff !important;
  padding: 12px 24px;
  border-radius: 999px;
  text-decoration: none;
  font-weight: 700;
  font-size: 15px;
  transition: var(--hb-transition);
  border: 1px solid rgba(0,0,0,.06);
  cursor: pointer;
  width: 100%;
  text-align: center;
  white-space: nowrap;
  margin-top: auto;
  box-shadow: 0 10px 24px rgba(219, 138, 22, 0.25), 0 0 20px rgba(219, 138, 22, 0.1);
  position: relative;
  overflow: hidden;
}

.hb-card-button::before {
  content: "";
  position: absolute;
  top: 0;
  left: -100%;
  width: 100%;
  height: 100%;
  background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
  transition: left 0.5s ease;
}

.hb-card-button:hover::before {
  left: 100%;
}

.hb-card-button:hover {
  transform: translateY(-2px);
  box-shadow: var(--hb-shadow-hover);
  background: linear-gradient(135deg, var(--hb-primary-hover) 0%, var(--hb-primary) 100%);
  color: #ffffff !important;
}

.hb-card-button:active {
  transform: translateY(0px);
  box-shadow: 0 8px 18px rgba(219, 138, 22, 0.25);
}

.hb-card-button:visited,
.hb-card-button:active,
.hb-card-button:focus {
  color: #ffffff !important;
}

.hb-account-dashboard-not-logged-in {
  direction: rtl;
  font-family: var(--hb-font);
  text-align: center;
  padding: clamp(40px, 6vw, 60px) clamp(20px, 4vw, 40px);
  background: linear-gradient(135deg, var(--hb-bg) 0%, var(--hb-bg-subtle) 100%);
  border: 2px solid var(--hb-border);
  border-radius: var(--hb-radius-lg);
  box-shadow: var(--hb-shadow-md);
  max-width: 600px;
  margin: 40px auto;
}

.hb-account-dashboard-not-logged-in h3 {
  font-size: clamp(24px, 4vw, 32px);
  font-weight: 700;
  color: var(--hb-text) !important;
  margin-bottom: 16px;
  background: linear-gradient(135deg, var(--hb-text) 0%, var(--hb-primary) 50%, #3b82f6 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

.hb-account-dashboard-not-logged-in p {
  font-size: clamp(15px, 2vw, 18px);
  color: var(--hb-text-secondary) !important;
  line-height: 1.6;
}

/* Responsive Design */
@media (max-width: 768px) {
  .hb-dashboard-cards {
    grid-template-columns: 1fr;
    gap: 20px;
  }
  
  .hb-dashboard-card {
    padding: 24px 20px;
  }
  
  .hb-card-icon {
    width: 56px;
    height: 56px;
    margin-bottom: 16px;
  }
  
  .hb-card-button {
    padding: 10px 20px;
    font-size: 14px;
  }
}

/* Dashboard Summary */
.hb-cog-summary {
  direction: rtl;
  font-family: system-ui, -apple-system, "Segoe UI", Roboto, "Noto Sans Hebrew", Arial, sans-serif;
  padding: 20px;
  background: #fff;
  border-radius: 8px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.hb-cog-summary-item {
  display: flex;
  justify-content: space-between;
  padding: 12px 0;
  border-bottom: 1px solid #e0e0e0;
}

.hb-cog-summary-item:last-child {
  border-bottom: none;
}

.hb-cog-summary-label {
  font-weight: 600;
  color: #333;
}

.hb-cog-summary-value {
  color: #2e7d32;
  font-weight: 700;
}

.hb-cog-summary-empty,
.hb-cog-summary-not-logged-in {
  text-align: center;
  padding: 40px;
  color: #666;
}

/* Dashboard Stats */
.hb-cog-dashboard {
  direction: rtl;
  font-family: system-ui, -apple-system, "Segoe UI", Roboto, "Noto Sans Hebrew", Arial, sans-serif;
  padding: 20px;
}

.hb-cog-dashboard-loading {
  text-align: center;
  padding: 40px;
  color: #666;
  font-size: 18px;
}

.hb-cog-dashboard-stats {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 20px;
}

.hb-cog-dashboard-stat {
  background: #fff;
  padding: 20px;
  border-radius: 8px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.1);
  text-align: center;
}

.hb-cog-dashboard-stat-label {
  font-size: 14px;
  color: #666;
  margin-bottom: 8px;
  font-weight: 600;
}

.hb-cog-dashboard-stat-value {
  font-size: 32px;
  font-weight: 800;
  color: #2e7d32;
}

@media (max-width: 600px) {
  .hb-cog-game-container,
  .hb-cog-profile-container {
    padding: 12px;
  }
  
  .hb-cog-game-header h3 {
    font-size: 24px;
  }
  
  .hb-cog-instructions {
    font-size: 18px;
  }
  
  .hb-cog-stimulus {
    width: 150px;
    height: 150px;
  }
  
  .hb-cog-timer {
    font-size: 36px;
  }
  
  .hb-cog-profile-stats {
    grid-template-columns: repeat(2, 1fr);
  }
  
  .hb-cog-profile-table {
    font-size: 14px;
  }
  
  .hb-cog-profile-table th,
  .hb-cog-profile-table td {
    padding: 8px;
  }
  
  .hb-dashboard-cards {
    grid-template-columns: 1fr;
    gap: 24px;
  }
  
  .hb-dashboard-header h2 {
    font-size: 36px;
  }
  
  .hb-dashboard-header p {
    font-size: 18px;
  }
  
  .hb-dashboard-card {
    padding: 32px 24px;
  }
  
  .hb-dashboard-card h3 {
    font-size: 24px;
  }
  
  .hb-dashboard-card p {
    font-size: 16px;
  }
  
  .hb-card-icon {
    width: 70px;
    height: 70px;
  }
  
  .hb-card-button {
    padding: 14px 32px;
    font-size: 16px;
  }
}

.hb-cog-dashboard-reco {
  margin-top: 18px;
  padding: 16px 18px;
  background: #fff3cd;
  border-radius: 10px;
  border-right: 5px solid #ffc107;
  color: #856404;
  font-size: 16px;
  line-height: 1.6;
}

.hb-cog-dashboard-cats {
  margin-top: 22px;
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 16px;
}

.hb-cog-cat-card {
  background: #fff;
  border-radius: 12px;
  padding: 16px;
  box-shadow: 0 2px 10px rgba(0,0,0,0.08);
  border: 1px solid #eee;
}

.hb-cog-cat-title {
  font-size: 18px;
  font-weight: 800;
  color: #2e7d32;
  margin-bottom: 10px;
}

.hb-cog-cat-status {
  font-size: 14px;
  color: #444;
  margin-bottom: 14px;
}

.hb-cog-cat-status-label {
  font-weight: 700;
  margin-left: 6px;
}

.hb-cog-cat-btn {
  display: inline-block;
  width: 100%;
  text-align: center;
  padding: 10px 12px;
  border-radius: 10px;
  background: #2e7d32;
  color: #fff !important;
  text-decoration: none;
  font-weight: 700;
}

.hb-cog-cat-btn:hover {
  background: #256528;
}

.hb-cog-category {
  direction: rtl;
  max-width: 1100px;
  margin: 0 auto;
  padding: 10px 0;
}
.hb-cog-category-header h2 {
  font-size: 34px;
  font-weight: 900;
  color: #000;
  margin: 0 0 10px 0;
}
.hb-cog-category-header p {
  margin: 0 0 16px 0;
  color: #444;
  font-size: 16px;
}
.hb-cog-category-games h3 {
  margin-top: 22px;
  margin-bottom: 12px;
  font-size: 20px;
  font-weight: 800;
}
CSS;

  echo "<style id='hb-cog-style'>\n" . $css . "\n</style>";
}, 99);

// JS inline (footer) — בלי שום src
add_action('wp_footer', function () {
  if (!hb_cog_page_has_shortcodes()) return;

  echo "\n<!-- HB_COG: script injected -->\n";

  $ajax_nonce = wp_create_nonce('hb_cog_nonce');
  $vars = [
    'ajaxurl' => admin_url('admin-ajax.php'),
    'nonce' => $ajax_nonce,
    'user_id' => get_current_user_id()
  ];

  $dur = isset($_GET['dur']) ? intval($_GET['dur']) : 0;
  if ($dur >= 2 && $dur <= 10) {
    $session_duration_ms = $dur * 60000;
  } else {
    $session_duration_ms = 300000; // 5 דקות ברירת מחדל
  }

  $config = [
    'session_duration_ms' => $session_duration_ms,
    'stimulus_interval_ms' => [1200, 1800],
    'stimulus_duration_ms' => 900,
    'go_ratio' => 0.7,
    'nogo_ratio' => 0.3,
    'difficulty_levels' => [
      1 => ['interval' => [1400, 1900], 'duration' => 950],
      2 => ['interval' => [1300, 1800], 'duration' => 900],
      3 => ['interval' => [1200, 1700], 'duration' => 850],
      4 => ['interval' => [1100, 1600], 'duration' => 800],
      5 => ['interval' => [1000, 1500], 'duration' => 750],
    ],
    'scoring' => [
      'weights' => ['accuracy' => 0.55, 'speed' => 0.30, 'stability' => 0.15],
      'speed' => ['min_rt_ms' => 300, 'max_rt_ms' => 1100],
      'stability' => ['cv_target' => 0.45],
    ],
    'domains' => [
      'go_nogo' => [
        'attention' => 0.6,
        'inhibition' => 0.3,
        'processing_speed' => 0.1,
        'working_memory' => 0.0,
        'reasoning_flexibility' => 0.0,
        'visual_perception' => 0.0,
      ],
      'nback1' => [
        'attention' => 0.2,
        'inhibition' => 0.0,
        'processing_speed' => 0.1,
        'working_memory' => 0.7,
        'reasoning_flexibility' => 0.0,
        'visual_perception' => 0.0,
      ],
      'stroop' => [
        'attention' => 0.2,
        'inhibition' => 0.5,
        'processing_speed' => 0.2,
        'working_memory' => 0.0,
        'reasoning_flexibility' => 0.1,
        'visual_perception' => 0.0,
      ],
      'visual_search' => [
        'attention' => 0.5,
        'inhibition' => 0.0,
        'processing_speed' => 0.3,
        'working_memory' => 0.0,
        'reasoning_flexibility' => 0.0,
        'visual_perception' => 0.2,
      ],
    ],
  ];

  $js_bundle = <<<'JS'
/* HB_COG RUNTIME (inline) */
console.log('[HB_COG] runtime inline loaded');

(function () {
  function ready(fn) {
    if (document.readyState !== 'loading') fn();
    else document.addEventListener('DOMContentLoaded', fn);
  }

  function qsAll(sel, root) {
    return Array.prototype.slice.call((root || document).querySelectorAll(sel));
  }

  function enc(obj) {
    var s = [];
    for (var k in obj) {
      if (!Object.prototype.hasOwnProperty.call(obj, k)) continue;
      s.push(encodeURIComponent(k) + '=' + encodeURIComponent(obj[k]));
    }
    return s.join('&');
  }

  function postAjax(params) {
    var url = (window.hb_cog_vars && window.hb_cog_vars.ajaxurl) ? window.hb_cog_vars.ajaxurl : '/wp-admin/admin-ajax.php';
    return fetch(url, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
      credentials: 'same-origin',
      body: params
    }).then(function (r) { return r.json(); });
  }

  function dateISO() {
    var d = new Date();
    var y = d.getFullYear();
    var m = String(d.getMonth() + 1).padStart(2, '0');
    var day = String(d.getDate()).padStart(2, '0');
    return y + '-' + m + '-' + day;
  }

  function saveAttemptToServer(metrics, scoreObj, gameId, difficulty, track, startedAt) {
    var nonce = window.hb_cog_vars && window.hb_cog_vars.nonce;
    var userId = window.hb_cog_vars && window.hb_cog_vars.user_id;
    if (!userId || !nonce) {
      console.warn('HB_COG: Cannot save attempt - missing user_id or nonce');
      return Promise.resolve(null);
    }
    
    var cfg = window.CONFIG_SENIOR || {};
    var attempt = {
      track: track || 'senior',
      game_id: gameId,
      difficulty: difficulty || 1,
      attempt_no: 1,
      started_at: new Date(startedAt || Date.now()).toISOString(),
      ended_at: new Date().toISOString(),
      date_iso: dateISO(),
      metrics: metrics,
      scores: { game_score: scoreObj.game_score || 0 },
      domain_contrib: (cfg.domains && cfg.domains[gameId]) ? cfg.domains[gameId] : {}
    };
    
    return postAjax(enc({
      action: 'hb_cog_save_attempt',
      _ajax_nonce: nonce,
      attempt: JSON.stringify(attempt)
    })).then(function (res) {
      if (res && res.success) {
        document.dispatchEvent(new Event('hb_cog_profile_refresh'));
        console.log('HB_COG: Attempt saved successfully');
      } else {
        console.error('HB_COG: Failed to save attempt', res);
      }
      return res;
    }).catch(function(err) {
      console.error('HB_COG: Error saving attempt', err);
      return null;
    });
  }

  function clamp(n, a, b) { return Math.max(a, Math.min(b, n)); }
  function dateISO() {
    var d = new Date();
    var y = d.getFullYear();
    var m = String(d.getMonth() + 1).padStart(2, '0');
    var day = String(d.getDate()).padStart(2, '0');
    return y + '-' + m + '-' + day;
  }
  function randInt(min, max) { return Math.floor(Math.random() * (max - min + 1)) + min; }

  function scoreGoNoGo(metrics, cfg) {
    var weights = (cfg && cfg.scoring && cfg.scoring.weights) || { accuracy: 0.55, speed: 0.30, stability: 0.15 };
    var speedCfg = (cfg && cfg.scoring && cfg.scoring.speed) || { min_rt_ms: 300, max_rt_ms: 1100 };
    var stabCfg  = (cfg && cfg.scoring && cfg.scoring.stability) || { cv_target: 0.45 };

    var acc = metrics.accuracy || 0; // 0..1
    var meanRT = metrics.mean_rt_ms || 0;
    var cv = metrics.rt_cv || 1;

    var speed01;
    if (!meanRT || meanRT <= 0) speed01 = 0;
    else {
      var t = (speedCfg.max_rt_ms - meanRT) / (speedCfg.max_rt_ms - speedCfg.min_rt_ms);
      speed01 = clamp(t, 0, 1);
    }

    var stab01 = clamp(1 - (cv / (stabCfg.cv_target || 0.45)), 0, 1);

    var total01 = acc * weights.accuracy + speed01 * weights.speed + stab01 * weights.stability;
    var gameScore = Math.round(clamp(total01, 0, 1) * 100);

    return { game_score: gameScore };
  }

  function mountGame(container) {
    console.log('HB_COG bundle: NEW');
    if (container.dataset.hbCogInited === '1') return;
    container.dataset.hbCogInited = '1';

    var gameId = container.getAttribute('data-hb-cog-game') || 'go_nogo';
    console.log('HB_COG: Mounting game', gameId, 'on container:', container);
    console.log('HB_COG: Container data-hb-cog-game attribute:', container.getAttribute('data-hb-cog-game'));
    console.log('HB_COG: Container innerHTML (first 500 chars):', container.innerHTML.substring(0, 500));
    
    // Use new core system if available
    if (window.HB_COG_Core && window.HB_COG_GAMES && window.HB_COG_GAMES[gameId]) {
      try {
        var cfg = window.CONFIG_SENIOR || {};
        var core = new window.HB_COG_Core(container, gameId, cfg);
        core.init();
        return;
      } catch (e) {
        console.error('HB_COG: Error initializing core for game', gameId, e);
      }
    }
    
    // Fallback: Try to use game module directly if available
    if (window.HB_COG_GAMES && window.HB_COG_GAMES[gameId]) {
      console.log('HB_COG: Using fallback system for game', gameId);
      try {
        var cfg = window.CONFIG_SENIOR || {};
        var GameModule = window.HB_COG_GAMES[gameId];
        // Create a minimal core-like object for compatibility
        var minimalCore = {
          container: container,
          gameId: gameId,
          config: cfg,
          track: container.getAttribute('data-hb-cog-track') || 'senior',
          difficulty: parseInt(container.getAttribute('data-hb-cog-difficulty') || '1', 10),
          running: false,
          nextUrl: '',
          backUrl: ''
        };
        var pageWrap = container.closest('.hb-cog-game-page');
        if (pageWrap) {
          minimalCore.nextUrl = pageWrap.getAttribute('data-next-url') || '';
          minimalCore.backUrl = pageWrap.getAttribute('data-back-url') || '';
        }
        var gameInstance = new GameModule(container, cfg, minimalCore);
        gameInstance.init();
        
        // Get DOM elements immediately after init() (which calls renderHTML())
        var startBtn = container.querySelector('.hb-cog-start-btn');
        var stopBtn = container.querySelector('.hb-cog-stop-btn');
        var gameArea = container.querySelector('.hb-cog-game-area');
        var timerEl = container.querySelector('.hb-cog-timer');
        
        if (!startBtn) {
          console.error('HB_COG: Start button not found for game', gameId);
          console.error('HB_COG: Container HTML:', container.innerHTML.substring(0, 500));
          return;
        }
        
        // Add timer functionality
        var running = false;
        var t0 = 0;
        var sessionEnd = 0;
        var tickInterval = null;
        var sessionMs = cfg.session_duration_ms || 300000;
        
        function formatTime(ms) {
          var s = Math.max(0, Math.ceil(ms / 1000));
          var m = Math.floor(s / 60);
          var ss = String(s % 60).padStart(2, '0');
          return m + ':' + ss;
        }
        
        function startGame() {
          if (running) return;
          
          // Update t0 and minimalCore FIRST
          t0 = Date.now();
          minimalCore.t0 = t0;
          running = true;
          
          // IMPORTANT: Update minimalCore.running so game modules can check it
          minimalCore.running = true;
          
          if (gameArea) gameArea.style.display = 'block';
          if (stopBtn) stopBtn.style.display = 'inline-block';
          if (startBtn) startBtn.style.display = 'none';
          
          // Reset game if needed
          if (gameInstance.reset) {
            gameInstance.reset();
          }
          
          // Start timer
          sessionEnd = t0 + sessionMs;
          if (timerEl) timerEl.textContent = formatTime(sessionMs);
          
          tickInterval = setInterval(function() {
            var left = sessionEnd - Date.now();
            if (timerEl) timerEl.textContent = formatTime(left);
            if (left <= 0) {
              finishGame();
            }
          }, 250);
          
          // Start game module AFTER setting running = true
          if (gameInstance.start) {
            gameInstance.start();
          }
        }
        
        function finishGame() {
          if (!running && t0 === 0) return; // Don't finish if never started
          
          // IMPORTANT: Update running state FIRST so game modules stop
          running = false;
          minimalCore.running = false;
          
          if (tickInterval) {
            clearInterval(tickInterval);
            tickInterval = null;
          }
          
          // Stop game module first
          if (gameInstance.stop) {
            gameInstance.stop();
          }
          
          // Show results
          if (gameInstance.getMetrics && gameInstance.getScore) {
            var metrics = gameInstance.getMetrics();
            var scoreObj = gameInstance.getScore(metrics);
            var tips = gameInstance.getTips ? gameInstance.getTips(metrics) : [];
            
            if (!tips || tips.length === 0) {
              tips = ['אחלה עבודה. כדי לשמור על התנופה, מומלץ להוסיף עוד אימון קצר או לעבור לאימון הבא.'];
            }
            
            // Render results
            var results = container.querySelector('.hb-cog-results');
            if (results) {
              results.classList.add('show');
              if (gameInstance.renderResults) {
                results.innerHTML = gameInstance.renderResults(metrics, scoreObj, tips);
              } else {
                // Fallback rendering
                results.innerHTML = '<div class="hb-cog-results-grid">' +
                  '<div class="hb-cog-result-card"><div class="hb-cog-result-label">ציון</div><div class="hb-cog-result-value">' + (scoreObj.game_score || 0) + '</div></div>' +
                  '<div class="hb-cog-result-card"><div class="hb-cog-result-label">דיוק</div><div class="hb-cog-result-value">' + Math.round((metrics.accuracy || 0) * 100) + '%</div></div>' +
                  '</div>' +
                  '<div class="hb-cog-result-disclaimer">' + tips.join('<br>') + '</div>';
              }
            }
            
            // Save attempt (only if game was actually started)
            if (t0 > 0) {
              saveAttemptToServer(metrics, scoreObj, gameId, minimalCore.difficulty, minimalCore.track, t0).then(function(res) {
                if (res && res.success) {
                  console.log('HB_COG: Attempt saved successfully');
                }
              });
            }
          }
          
          // Hide game area and show start button
          if (gameArea) gameArea.style.display = 'none';
          if (stopBtn) stopBtn.style.display = 'none';
          if (startBtn) startBtn.style.display = 'inline-block';
        }
        
        // Setup event listeners immediately after init() (which renders the buttons)
        startBtn.addEventListener('click', function(e) {
          e.preventDefault();
          e.stopPropagation();
          startGame();
        });
        
        if (stopBtn) {
          stopBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            finishGame();
          });
        }
        
        function finishGame() {
          if (!running && t0 === 0) return; // Don't finish if never started
          
          // IMPORTANT: Update running state FIRST so game modules stop
          running = false;
          minimalCore.running = false;
          
          if (tickInterval) {
            clearInterval(tickInterval);
            tickInterval = null;
          }
          
          // Stop game module first
          if (gameInstance.stop) {
            gameInstance.stop();
          }
          
          // Show results
          if (gameInstance.getMetrics && gameInstance.getScore) {
            var metrics = gameInstance.getMetrics();
            var scoreObj = gameInstance.getScore(metrics);
            var tips = gameInstance.getTips ? gameInstance.getTips(metrics) : [];
            
            if (!tips || tips.length === 0) {
              tips = ['אחלה עבודה. כדי לשמור על התנופה, מומלץ להוסיף עוד אימון קצר או לעבור לאימון הבא.'];
            }
            
            // Render results
            var results = container.querySelector('.hb-cog-results');
            if (results) {
              results.classList.add('show');
              if (gameInstance.renderResults) {
                results.innerHTML = gameInstance.renderResults(metrics, scoreObj, tips);
              } else {
                // Fallback rendering
                results.innerHTML = '<div class="hb-cog-results-grid">' +
                  '<div class="hb-cog-result-card"><div class="hb-cog-result-label">ציון</div><div class="hb-cog-result-value">' + (scoreObj.game_score || 0) + '</div></div>' +
                  '<div class="hb-cog-result-card"><div class="hb-cog-result-label">דיוק</div><div class="hb-cog-result-value">' + Math.round((metrics.accuracy || 0) * 100) + '%</div></div>' +
                  '</div>' +
                  '<div class="hb-cog-result-disclaimer">' + tips.join('<br>') + '</div>';
              }
            }
            
            // Save attempt (only if game was actually started)
            if (t0 > 0) {
              saveAttemptToServer(metrics, scoreObj, gameId, minimalCore.difficulty, minimalCore.track, t0).then(function(res) {
                if (res && res.success) {
                  console.log('HB_COG: Attempt saved successfully');
                }
              });
            }
          }
          
          // Hide game area and show start button
          if (gameArea) gameArea.style.display = 'none';
          if (stopBtn) stopBtn.style.display = 'none';
          if (startBtn) startBtn.style.display = 'inline-block';
        }
        
        // Event listeners are set up in the setTimeout above
        
        // Store functions in minimalCore for game module to use
        minimalCore.start = startGame;
        minimalCore.stop = function() {
          running = false;
          if (tickInterval) {
            clearInterval(tickInterval);
            tickInterval = null;
          }
          if (gameInstance.stop) gameInstance.stop();
        };
        minimalCore.finishNow = finishGame;
        minimalCore.finalizeAndShowResults = finishGame;
        minimalCore.t0 = 0;
        minimalCore.dateISO = dateISO;
        minimalCore.postAjax = postAjax;
        minimalCore.enc = enc;
        minimalCore.saveAttempt = function(metrics, scoreObj) {
          return saveAttemptToServer(metrics, scoreObj, gameId, minimalCore.difficulty, minimalCore.track, minimalCore.t0 || Date.now());
        };
        
        // Make running property reactive
        Object.defineProperty(minimalCore, 'running', {
          get: function() { return running; },
          set: function(val) {
            running = val;
            // Prevent infinite recursion
            if (gameInstance && gameInstance.core && gameInstance.core !== minimalCore) {
              try {
                gameInstance.core.running = val;
              } catch (e) {}
            }
          }
        });
        
        return;
      } catch (e) {
        console.error('HB_COG: Error initializing game module', gameId, e);
      }
    }
    
    // Fallback to old system (for backward compatibility - only for go_nogo)
    if (gameId !== 'go_nogo') {
      container.innerHTML = '<div class="hb-cog-error">משחק לא נמצא: ' + gameId + '. נא לרענן את הדף.</div>';
      return;
    }
    
    var gameId = container.getAttribute('data-hb-cog-game') || 'go_nogo';
    var track  = container.getAttribute('data-hb-cog-track') || 'senior';
    var difficulty = parseInt(container.getAttribute('data-hb-cog-difficulty') || '1', 10);

    var cfg = window.CONFIG_SENIOR || {};
    var dcfg = (cfg.difficulty_levels && cfg.difficulty_levels[difficulty]) ? cfg.difficulty_levels[difficulty] : null;
    var interval = dcfg ? dcfg.interval : (cfg.stimulus_interval_ms || [1200, 1800]);
    var stimDuration = dcfg ? dcfg.duration : (cfg.stimulus_duration_ms || 900);
    var sessionMs = cfg.session_duration_ms || 300000;
    var goRatio = cfg.go_ratio != null ? cfg.go_ratio : 0.7;

    container.innerHTML = [
      '<div class="hb-cog-game-header">',
        '<h3>Go / No-Go</h3>',
        '<div class="hb-cog-instructions">לחצו על העיגול <b>הירוק</b>. אל תלחצו על העיגול <b>האדום</b>.</div>',
        '<div style="display:flex; gap:10px; justify-content:center; flex-wrap:wrap;">',
          '<button class="hb-cog-start-btn" style="font-size:20px;padding:12px 20px;border-radius:10px;border:0;background:#2e7d32;color:#fff;cursor:pointer;">התחל אימון</button>',
          '<button class="hb-cog-stop-btn" style="font-size:18px;padding:10px 18px;border-radius:10px;border:0;background:#444;color:#fff;cursor:pointer;display:none;">סיים מוקדם</button>',
        '</div>',
      '</div>',
      '<div class="hb-cog-game-area" style="display:none;">',
        '<div class="hb-cog-timer">5:00</div>',
        '<div class="hb-cog-stimulus-area">',
          '<div class="hb-cog-stimulus hb-cog-stimulus-go" role="button" aria-label="GO"></div>',
          '<div class="hb-cog-stimulus hb-cog-stimulus-nogo" role="button" aria-label="NO-GO"></div>',
        '</div>',
      '</div>',
      '<div class="hb-cog-results"></div>' +
      '<div class="hb-cog-finish-cta"></div>'
    ].join('');

    var startBtn = container.querySelector('.hb-cog-start-btn');
    var gameArea = container.querySelector('.hb-cog-game-area');
    var timerEl  = container.querySelector('.hb-cog-timer');
    var stimGo   = container.querySelector('.hb-cog-stimulus-go');
    var stimNoGo = container.querySelector('.hb-cog-stimulus-nogo');
    var results  = container.querySelector('.hb-cog-results');
    var stopBtn = container.querySelector('.hb-cog-stop-btn');

    var pageWrap = container.closest('.hb-cog-game-page');
    var nextUrl = pageWrap ? (pageWrap.getAttribute('data-next-url') || '') : '';
    var backUrl = pageWrap ? (pageWrap.getAttribute('data-back-url') || '') : '';

    var durBtns = container.querySelectorAll('.hb-cog-dur-btn');

    function getParam(name) {
      try { return new URL(window.location.href).searchParams.get(name); }
      catch(e) { return null; }
    }

    function setParamAndReload(name, value) {
      var url = new URL(window.location.href);
      url.searchParams.set(name, String(value));
      window.location.href = url.toString();
    }

    var currentDur = parseInt(getParam('dur') || '5', 10);
    durBtns.forEach(function(btn){
      var v = parseInt(btn.getAttribute('data-dur'), 10);
      if (v === currentDur) btn.style.opacity = '0.75';
      btn.addEventListener('click', function(){
        setParamAndReload('dur', v);
      });
    });


    var running = false;
    var t0 = 0;
    var sessionEnd = 0;
    var tickInterval = null;
    var stimTimeout = null;
    var nextTimeout = null;

    var currentStim = null; // 'go' | 'nogo'
    var stimShownAt = 0;
    var responded = false;

    var trials = 0, goTrials = 0, nogoTrials = 0;
    var hits = 0, misses = 0, correctReject = 0, falseAlarms = 0;
    var rts = [];

    function formatTime(ms) {
      var s = Math.max(0, Math.ceil(ms / 1000));
      var m = Math.floor(s / 60);
      var ss = String(s % 60).padStart(2, '0');
      return m + ':' + ss;
    }

    function hideStim() {
      stimGo.classList.remove('is-visible');
      stimNoGo.classList.remove('is-visible');
      currentStim = null;
      responded = false;
    }

    function scheduleNext() {
      if (!running) return;
      var gap = randInt(interval[0], interval[1]);
      nextTimeout = setTimeout(function () {
        if (!running) return;
        var kind = (Math.random() < goRatio) ? 'go' : 'nogo';
        showStim(kind);
      }, gap);
    }

    function showStim(kind) {
      hideStim();
      currentStim = kind;
      responded = false;
      stimShownAt = Date.now();
      trials++;
      if (kind === 'go') goTrials++; else nogoTrials++;

      (kind === 'go' ? stimGo : stimNoGo).classList.add('is-visible');

      stimTimeout = setTimeout(function () {
        if (!responded) {
          if (currentStim === 'go') misses++;
          else correctReject++;
        }
        hideStim();
        scheduleNext();
      }, stimDuration);
    }

    function onRespond() {
      if (!running || !currentStim || responded) return;
      responded = true;
      var rt = Date.now() - stimShownAt;

      if (currentStim === 'go') { hits++; rts.push(rt); }
      else { falseAlarms++; }

      clearTimeout(stimTimeout);
      hideStim();
      scheduleNext();
    }

    function computeMetrics() {
      var totalCorrect = hits + correctReject;
      var total = trials || 1;
      var accuracy = totalCorrect / total;

      var mean = 0;
      for (var i = 0; i < rts.length; i++) mean += rts[i];
      mean = rts.length ? (mean / rts.length) : 0;

      var sd = 0;
      for (var j = 0; j < rts.length; j++) sd += Math.pow(rts[j] - mean, 2);
      sd = rts.length ? Math.sqrt(sd / rts.length) : 0;

      var cv = (mean > 0) ? (sd / mean) : 1;

      return {
        trials: trials,
        accuracy: accuracy,
        mean_rt_ms: Math.round(mean),
        rt_cv: Number(cv.toFixed(3)),
        false_alarms: falseAlarms
      };
    }

    function saveAttempt(metrics, scoreObj) {
      var nonce = window.hb_cog_vars && window.hb_cog_vars.nonce;
      var userId = window.hb_cog_vars && window.hb_cog_vars.user_id;
      if (!userId || !nonce) return Promise.resolve(null);

      var attempt = {
        track: track,
        game_id: gameId,
        difficulty: difficulty,
        attempt_no: 1,
        started_at: new Date(t0).toISOString(),
        ended_at: new Date().toISOString(),
        date_iso: dateISO(),
        metrics: metrics,
        scores: { game_score: scoreObj.game_score },
        domain_contrib: (cfg.domains && cfg.domains[gameId]) ? cfg.domains[gameId] : {}
      };

      return postAjax(enc({
        action: 'hb_cog_save_attempt',
        _ajax_nonce: nonce,
        attempt: JSON.stringify(attempt)
      })).then(function (res) {
        if (res && res.success) document.dispatchEvent(new Event('hb_cog_profile_refresh'));
        return res;
      });
    }

    function hbCogBuildNextUrlFallback() {
      const root = document.querySelector('[data-hb-cog-root]') || document.body;

      const get = (k, d = "") => {
        const v = (root.dataset && root.dataset[k]) ? root.dataset[k] : "";
        return v !== "" ? v : d;
      };

      // בסיס: URL נוכחי
      const url = new URL(window.location.href);

      // פרמטרים קיימים (או ברירות מחדל)
      const dur  = url.searchParams.get("dur")  || get("dur", "5");
      const diff = parseInt(url.searchParams.get("diff") || get("diff", "1"), 10) || 1;

      // אם יש רק משחק אחד כרגע, "אימון נוסף" = אותו משחק עם diff+1
      const nextDiff = diff + 1;

      url.searchParams.set("dur", String(dur));
      url.searchParams.set("diff", String(nextDiff));

      // אם יש לכם גם i/order במערכת, נשמור אותם אם קיימים
      if (url.searchParams.has("i")) url.searchParams.set("i", url.searchParams.get("i"));
      if (url.searchParams.has("order")) url.searchParams.set("order", url.searchParams.get("order"));

      return url.toString();
    }

    function finalizeAndShowResults(){
      stop();

      var metrics = computeMetrics();
      var scoreObj = scoreGoNoGo(metrics, cfg);

      // טקסט הכוונה קצר
      var tips = [];
      if (metrics.false_alarms >= 3) tips.push('שמנו לב שלחצת גם כשלא צריך — זה קשור ליכולת עכבה. מחר נתרגל שוב בקצב נוח.');
      if (metrics.mean_rt_ms && metrics.mean_rt_ms > 900) tips.push('זמן התגובה מעט איטי — אפשר לחזק מהירות עיבוד עם אימון קצר נוסף.');
      if (metrics.rt_cv && metrics.rt_cv > 0.6) tips.push('התגובות לא יציבות — אימון קצר נוסף יעזור לייצב קשב וקצב.');
      if (!tips.length) tips.push('אחלה עבודה. כדי לשמור על התנופה, מומלץ להוסיף עוד אימון קצר או לעבור לאימון הבא.');

      results.classList.add('show');
      results.innerHTML =
        '<div class="hb-cog-results-grid">' +
          '<div class="hb-cog-result-card"><div class="hb-cog-result-label">ציון</div><div class="hb-cog-result-value">'+scoreObj.game_score+'</div></div>' +
          '<div class="hb-cog-result-card"><div class="hb-cog-result-label">דיוק</div><div class="hb-cog-result-value">'+Math.round((metrics.accuracy||0)*100)+'%</div></div>' +
          '<div class="hb-cog-result-card"><div class="hb-cog-result-label">זמן תגובה</div><div class="hb-cog-result-value">'+(metrics.mean_rt_ms||0)+'</div></div>' +
          '<div class="hb-cog-result-card"><div class="hb-cog-result-label">טעויות No-Go</div><div class="hb-cog-result-value">'+(metrics.false_alarms||0)+'</div></div>' +
        '</div>' +
        '<div class="hb-cog-result-disclaimer">'+tips.join('<br>')+'</div>' +
        '<div style="display:flex;gap:10px;flex-wrap:wrap;justify-content:center;margin-top:16px;">' +
          (nextUrl ? '<a class="hb-card-button" href="'+nextUrl+'" style="width:auto;">לאימון הבא</a>' : '') +
          (backUrl ? '<a class="hb-card-button" href="'+backUrl+'" style="width:auto;background:#555;">חזרה לקטגוריה</a>' : '') +
        '</div>';

      // CTA container - תמיד להציג כפתור "אימון נוסף"
      var cta = container.querySelector(".hb-cog-finish-cta");
      if (cta) {
        // נסה מקור "רשמי" אם כבר יש לכם
        var root = document.querySelector('[data-hb-cog-root]');
        var serverNext = (root && root.dataset && root.dataset.nextUrl) ? root.dataset.nextUrl : (window.HB_COG_NEXT_URL || "");
        
        // נסה גם מה-wrapper
        if (!serverNext || serverNext.length <= 5) {
          var pageWrap = container.closest('.hb-cog-game-page');
          if (pageWrap) {
            serverNext = pageWrap.getAttribute('data-next-url') || '';
          }
        }
        
        // נסה גם מהמשתנה המקומי
        if (!serverNext || serverNext.length <= 5) {
          serverNext = nextUrl || '';
        }

        var finalNextUrl = (serverNext && serverNext.length > 5)
          ? serverNext
          : hbCogBuildNextUrlFallback();

        cta.innerHTML = '<a class="hb-card-button" href="' + finalNextUrl + '" style="width:auto;background:#777;">אימון נוסף</a>';
      }

      saveAttempt(metrics, scoreObj);
    }

    function stop() {
      running = false;
      clearInterval(tickInterval);
      clearTimeout(stimTimeout);
      clearTimeout(nextTimeout);
      hideStim();
      gameArea.style.display = 'none';
      if (stopBtn) stopBtn.style.display = 'none';
      if (startBtn) startBtn.style.display = 'inline-block';
    }

    function finishNow() {
      if (!running) return;
      finalizeAndShowResults();
    }

    function start() {
      if (running) return;
      running = true;
      results.classList.remove('show');
      results.innerHTML = '';
      gameArea.style.display = 'block';
      if (stopBtn) stopBtn.style.display = 'inline-block';
      if (startBtn) startBtn.style.display = 'none';

      trials = goTrials = nogoTrials = hits = misses = correctReject = falseAlarms = 0;
      rts = [];

      t0 = Date.now();
      sessionEnd = t0 + sessionMs;
      timerEl.textContent = formatTime(sessionMs);

      tickInterval = setInterval(function () {
        var left = sessionEnd - Date.now();
        timerEl.textContent = formatTime(left);
        if (left <= 0) finalizeAndShowResults();
      }, 250);

      scheduleNext();
    }

    startBtn.addEventListener('click', start);
    if (stopBtn) stopBtn.addEventListener('click', function(){
      if (!running) return;
      finalizeAndShowResults();
    });
    stimGo.addEventListener('click', onRespond);
    stimNoGo.addEventListener('click', onRespond);
    document.addEventListener('keydown', function (e) {
      if (e.key === ' ' || e.key === 'Enter') onRespond();
    });
  }

  function mountProfile(container) {
    if (container.dataset.hbCogInited === '1') return;
    container.dataset.hbCogInited = '1';

    var track = container.getAttribute('data-hb-cog-track') || 'senior';
    var days = parseInt(container.getAttribute('data-hb-cog-days') || '7', 10);

    var nonce = window.hb_cog_vars && window.hb_cog_vars.nonce;
    var userId = window.hb_cog_vars && window.hb_cog_vars.user_id;

    if (!userId) {
      container.innerHTML = '<div class="hb-cog-profile-error">נא להתחבר כדי לצפות בפרופיל.</div>';
      return;
    }
    if (!nonce) {
      container.innerHTML = '<div class="hb-cog-profile-error">שגיאת nonce. רעננו את העמוד.</div>';
      return;
    }

    container.innerHTML = '<div class="hb-cog-profile-loading">טוען פרופיל...</div>';

    postAjax(enc({
      action: 'hb_cog_get_profile',
      _ajax_nonce: nonce,
      track: track,
      days: String(days)
    })).then(function (res) {
      if (!res || !res.success) {
        container.innerHTML = '<div class="hb-cog-profile-error">שגיאה בטעינת פרופיל</div>';
        return;
      }

      var data = (res.data && res.data.profile_data) ? res.data.profile_data : [];
      var html = [];
      html.push('<div class="hb-cog-profile-header"><h3>פרופיל ' + days + ' ימים</h3></div>');
      html.push('<table class="hb-cog-profile-table"><thead><tr><th>תאריך</th><th>ציון</th></tr></thead><tbody>');
      for (var i = 0; i < data.length; i++) {
        html.push('<tr><td>' + data[i].date_iso + '</td><td>' + (data[i].daily_score || 0) + '</td></tr>');
      }
      html.push('</tbody></table>');
      container.innerHTML = html.join('');
    });
  }

  function initAll() {
    var games = qsAll('.hb-cog-game-container');
    var profiles = qsAll('.hb-cog-profile-container');
    console.log('[HB_COG] initAll', { games: games.length, profiles: profiles.length });
    games.forEach(mountGame);
    profiles.forEach(mountProfile);
  }

  window.HB_COG = window.HB_COG || {};
  window.HB_COG.init = initAll;

  ready(initAll);

  document.addEventListener('hb_cog_runtime_ready', initAll);
  document.addEventListener('hb_cog_profile_refresh', function () {
    qsAll('.hb-cog-profile-container').forEach(function (c) {
      c.dataset.hbCogInited = '0';
      mountProfile(c);
    });
  });

  document.addEventListener('elementor/frontend/init', initAll);
})();
JS;

  // Load core + game modules
  $game_id = isset($_GET['game']) ? sanitize_text_field($_GET['game']) : 'go_nogo';
  if (!isset(HB_COG_GAME_REGISTRY[$game_id])) {
    $game_id = 'go_nogo'; // Fallback
  }
  
  // Load core
  $core_file = HB_COG_PLUGIN_DIR . 'assets/hb-cog/hb-cog-core.js';
  $core_js = '';
  if (file_exists($core_file)) {
    $core_js = file_get_contents($core_file);
    if (empty($core_js)) {
      error_log('HB_COG: Core file exists but is empty: ' . $core_file);
    } else {
      // Expose HB_COG_Core to window
      $core_js .= "\n;try {\n";
      $core_js .= "  if (typeof HB_COG_Core !== 'undefined' && !window.HB_COG_Core) {\n";
      $core_js .= "    window.HB_COG_Core = HB_COG_Core;\n";
      $core_js .= "  }\n";
      $core_js .= "} catch(e) {}\n";
    }
  } else {
    error_log('HB_COG: Core file not found: ' . $core_file);
  }
  
  // Load all game modules (not just the current one)
  // This ensures all games are available for the Core Engine
  $game_js = '';
  $games_to_load = array_keys(HB_COG_GAME_REGISTRY);
  foreach ($games_to_load as $gid) {
    $game_file = HB_COG_PLUGIN_DIR . 'assets/hb-cog/games/' . $gid . '.js';
    if (file_exists($game_file)) {
      $game_js .= file_get_contents($game_file) . "\n";
    }
  }
  
  // Set game pages in JS
  $game_pages_js = "window.HB_COG_GAME_PAGES = " . wp_json_encode(HB_COG_GAME_PAGES, JSON_UNESCAPED_UNICODE) . ";\n";
  
  // Initialize HB_COG_GAMES registry BEFORE loading games
  $init_registry = "window.HB_COG_GAMES = window.HB_COG_GAMES || {};\n";
  
  // Debug: Log which games are being loaded
  $games_list = implode(', ', $games_to_load);
  $debug_info = "console.log('HB_COG: Loading games:', ['" . implode("', '", $games_to_load) . "']);\n";
  
  $payload =
    "window.hb_cog_vars=" . wp_json_encode($vars, JSON_UNESCAPED_UNICODE) . ";\n" .
    "window.CONFIG_SENIOR=" . wp_json_encode($config, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . ";\n" .
    $game_pages_js .
    $init_registry .
    $debug_info .
    "console.log('HB_COG inline loaded');\n" .
    $core_js . "\n" .
    $game_js . "\n" .
    "console.log('HB_COG: Games registered:', Object.keys(window.HB_COG_GAMES || {}));\n" .
    "console.log('HB_COG: Core available:', typeof window.HB_COG_Core !== 'undefined');\n" .
    $js_bundle;

  $payload .= "\n(function(){\n" .
  "function ready(fn){ if(document.readyState!=='loading'){ fn(); } else { document.addEventListener('DOMContentLoaded', fn); } }\n" .
  "ready(function(){\n" .
  "  var g = document.querySelectorAll('.hb-cog-game-container').length;\n" .
  "  var p = document.querySelectorAll('.hb-cog-profile-container').length;\n" .
  "  var d = document.querySelectorAll('.hb-cog-dashboard').length;\n" .
  "  console.log('HB_COG containers:', {games:g, profiles:p, dashboards:d});\n" .
  "});\n" .
  "})();\n";

  echo "<script id='hb-cog-inline-js'>\n" . $payload . "\n</script>";
}, 99);

function hb_cog_strip_esm($js) {
  // הסרת import statements
  $js = preg_replace('/^import\s+.*?from\s+[\'"][^\'"]+[\'"];?\s*$/m', '', $js);
  $js = preg_replace('/^import\s*\([^)]+\)\s*;?\s*$/m', '', $js);
  
  // הסרת export statements
  $js = preg_replace('/^export\s+(default\s+)?/m', '', $js);
  $js = preg_replace('/^export\s+\{[^}]+\}\s*;?\s*$/m', '', $js);
  
  // החלפת import.meta.url
  $js = str_replace('import.meta.url', '""', $js);
  
  return $js;
}

/* ---------------------------------------------------------
 * 3) Shortcode: [hb_cog_game]
 * --------------------------------------------------------- */
add_shortcode('hb_cog_game', function($atts) {
  $atts = shortcode_atts([
    'game' => 'go_nogo',
    'track' => 'senior',
    'difficulty' => '1',
  ], $atts);
  
  // CRITICAL: URL parameter ALWAYS wins, even in nested shortcode
  // This ensures that ?game=stroop in URL overrides any shortcode attribute
  if (isset($_GET['game']) && $_GET['game'] !== '') {
    $raw_game = wp_unslash($_GET['game']);
    $candidate = sanitize_key($raw_game);
    if (defined('HB_COG_GAME_REGISTRY') && isset(HB_COG_GAME_REGISTRY[$candidate])) {
      $game = $candidate;
      error_log('HB_COG: hb_cog_game shortcode: URL param overrides atts, using game: ' . $game);
    } else {
      $game = !empty($atts['game']) ? sanitize_text_field($atts['game']) : 'go_nogo';
      error_log('HB_COG: hb_cog_game shortcode: URL param invalid (' . $candidate . '), using atts: ' . $game);
    }
  } else {
    $game = !empty($atts['game']) ? sanitize_text_field($atts['game']) : 'go_nogo';
    error_log('HB_COG: hb_cog_game shortcode: No URL param, using atts: ' . $game);
  }
  
  $track = sanitize_text_field($atts['track']);
  $difficulty = intval($atts['difficulty']);
  
  // Debug: Log what game we're using (BEFORE creating container)
  error_log('HB_COG: hb_cog_game shortcode FINAL game: ' . $game . ' (from atts: ' . (isset($atts['game']) ? $atts['game'] : 'none') . ')');
  
  // duration via ?dur=
  $dur = isset($_GET['dur']) ? intval($_GET['dur']) : 5;
  if ($dur < 2) $dur = 2;
  if ($dur > 10) $dur = 10;

  // flow params
  $flow  = isset($_GET['flow']) ? sanitize_text_field($_GET['flow']) : '';
  $order = '';
  if (isset($_GET['order'])) {
    $order_raw = wp_unslash($_GET['order']);
    // keep safe chars including '@' and ','
    $order = preg_replace('/[^a-zA-Z0-9_\-,@]/', '', $order_raw);
  }
  $i     = isset($_GET['i']) ? intval($_GET['i']) : 0;
  $domain = isset($_GET['domain']) ? sanitize_text_field($_GET['domain']) : '';

  $order_arr = [];
  if (!empty($order)) {
    $order_arr = array_values(array_filter(array_map('trim', explode(',', $order))));
  }

  // base url for duration links (keep all params, replace dur only)
  $base_url = (is_ssl() ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
  $base_url = remove_query_arg(['dur'], $base_url);
  $dur_url = function($m) use ($base_url) {
    return add_query_arg(['dur' => $m], $base_url);
  };

  // next url
  $next_url = '';
  if (!empty($order_arr) && isset($order_arr[$i + 1])) {
    $next_game = $order_arr[$i + 1];
    $next_base = HB_COG_GAME_PAGES[$next_game] ?? '/אימון-קוגניטיבי/';
    $next_url = add_query_arg([
      'game'   => $next_game,
      'flow'   => $flow,
      'order'  => implode(',', $order_arr),
      'i'      => $i + 1,
      'dur'    => $dur,
      'domain' => $domain,
    ], home_url($next_base));
  }

  // back url
  $back_url = '';
  if (!empty($domain) && isset(HB_COG_CATEGORY_PAGES[$domain]['url'])) {
    $back_url = HB_COG_CATEGORY_PAGES[$domain]['url'];
  }
  
  ob_start(); ?>
  <div class="hb-cog-game-page"
       data-next-url="<?php echo esc_attr($next_url); ?>"
       data-back-url="<?php echo esc_attr($back_url); ?>">

    <div style="display:flex; gap:10px; flex-wrap:wrap; justify-content:center; margin: 0 0 14px 0;">
      <a class="hb-card-button" href="<?php echo esc_url($dur_url(3)); ?>" style="width:auto; padding:10px 16px; <?php echo ($dur===3?'opacity:0.9;':'' ); ?>">3 דקות</a>
      <a class="hb-card-button" href="<?php echo esc_url($dur_url(5)); ?>" style="width:auto; padding:10px 16px; <?php echo ($dur===5?'opacity:0.9;':'' ); ?>">5 דקות</a>
      <a class="hb-card-button" href="<?php echo esc_url($dur_url(7)); ?>" style="width:auto; padding:10px 16px; <?php echo ($dur===7?'opacity:0.9;':'' ); ?>">7 דקות</a>
    </div>

    <div class="hb-cog-game-container" 
         data-hb-cog-game="<?php echo esc_attr($game); ?>"
         data-hb-cog-track="<?php echo esc_attr($track); ?>"
         data-hb-cog-difficulty="<?php echo esc_attr($difficulty); ?>">
      <div class="hb-cog-game-loading">טוען משחק...</div>
    </div>
  </div>
  <?php
  return ob_get_clean();
});

/* ---------------------------------------------------------
 * 4) Shortcode: [hb_cog_profile]
 * --------------------------------------------------------- */
add_shortcode('hb_cog_profile', function($atts) {
  $atts = shortcode_atts([
    'track' => 'senior',
    'days' => '7',
  ], $atts);
  
  $track = sanitize_text_field($atts['track']);
  $days = intval($atts['days']);
  
  ob_start(); ?>
  <div class="hb-cog-profile-container" 
       data-hb-cog-track="<?php echo esc_attr($track); ?>"
       data-hb-cog-days="<?php echo esc_attr($days); ?>">
    <div class="hb-cog-profile-loading">טוען פרופיל...</div>
  </div>
  <?php
  return ob_get_clean();
});

/* ---------------------------------------------------------
 * 5) AJAX: שמירת ניסיון
 * --------------------------------------------------------- */
add_action('wp_ajax_hb_cog_save_attempt', 'hb_cog_handle_save_attempt');
add_action('wp_ajax_nopriv_hb_cog_save_attempt', 'hb_cog_handle_save_attempt');

function hb_cog_handle_save_attempt() {
  $nonce_check = check_ajax_referer('hb_cog_nonce', false, false);
  if (!$nonce_check) {
    wp_send_json_error('שגיאת אבטחה.');
    return;
  }
  
  $user_id = get_current_user_id();
  if (!$user_id) {
    wp_send_json_error('משתמש לא מחובר.');
    return;
  }
  
  $attempt_json = isset($_POST['attempt']) ? $_POST['attempt'] : '';
  if (empty($attempt_json)) {
    wp_send_json_error('נתונים חסרים.');
    return;
  }
  
  $attempt = json_decode(stripslashes($attempt_json), true);
  if (!is_array($attempt)) {
    wp_send_json_error('פורמט נתונים שגוי.');
    return;
  }
  
  global $wpdb;
  $table_attempts = $wpdb->prefix . 'hb_cog_attempts';
  $table_daily = $wpdb->prefix . 'hb_cog_daily';
  
  // בדיקה: סטטיסטיקה רק אם לפחות דקה (60 שניות) של אימון
  $started_at = isset($attempt['started_at']) ? strtotime($attempt['started_at']) : time();
  $ended_at = isset($attempt['ended_at']) ? strtotime($attempt['ended_at']) : time();
  $duration_seconds = $ended_at - $started_at;
  
  if ($duration_seconds < 60) {
    // פחות מדקה - לא נשמור סטטיסטיקה, רק נחזיר הודעה
    wp_send_json_success([
      'message' => 'אימון קצר מדי (פחות מדקה) - לא נשמר בסטטיסטיקה',
      'skipped' => true,
      'duration_seconds' => $duration_seconds
    ]);
    return;
  }
  
  // שמירת ניסיון ל-DB (רק אם לפחות דקה)
  $wpdb->insert(
    $table_attempts,
    [
      'user_id' => $user_id,
      'track' => $attempt['track'] ?? 'senior',
      'game_id' => $attempt['game_id'] ?? 'unknown',
      'difficulty' => $attempt['difficulty'] ?? 1,
      'attempt_no' => $attempt['attempt_no'] ?? 1,
      'started_at' => date('Y-m-d H:i:s', $started_at),
      'ended_at' => date('Y-m-d H:i:s', $ended_at),
      'date_iso' => $attempt['date_iso'] ?? date('Y-m-d'),
      'metrics' => json_encode($attempt['metrics'] ?? []),
      'scores' => json_encode($attempt['scores'] ?? []),
      'domain_contrib' => json_encode($attempt['domain_contrib'] ?? []),
    ],
    ['%d', '%s', '%s', '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s']
  );
  
  // עדכון סיכום יומי ב-DB
  if (isset($attempt['date_iso'])) {
    $date_iso = $attempt['date_iso'];
    
    // איסוף כל הניסיונות ליום זה
    $attempts_for_day = hb_cog_get_daily_summary_from_db($user_id, $date_iso);
    $daily_data = hb_cog_compute_weighted_daily_score($attempts_for_day);
    
    $attempts_count = is_array($attempts_for_day) ? count($attempts_for_day) : 0;
    $domains_json = json_encode($daily_data['domains_breakdown'] ?? []);
    $games_json   = json_encode($daily_data['games_breakdown'] ?? []);
    
    // עדכון או יצירת רשומה יומית
    $existing = $wpdb->get_var($wpdb->prepare(
      "SELECT id FROM $table_daily WHERE user_id = %d AND date_iso = %s",
      $user_id,
      $date_iso
    ));
    
    if ($existing) {
      $wpdb->update(
        $table_daily,
        [
          'daily_score' => $daily_data['daily_score'],
          'attempts_count' => $attempts_count,
          'domains' => $domains_json,
          'games' => $games_json,
        ],
        ['id' => $existing],
        ['%d', '%d', '%s', '%s'],
        ['%d']
      );
    } else {
      $wpdb->insert(
        $table_daily,
        [
          'user_id' => $user_id,
          'date_iso' => $date_iso,
          'track' => $attempt['track'] ?? 'senior',
          'daily_score' => $daily_data['daily_score'],
          'attempts_count' => $attempts_count,
          'domains' => $domains_json,
          'games' => $games_json,
        ],
        ['%d', '%s', '%s', '%d', '%d', '%s', '%s']
      );
    }
  }
  
  wp_send_json_success(['message' => 'ניסיון נשמר בהצלחה']);
}

/* ---------------------------------------------------------
 * 6) AJAX: קבלת פרופיל
 * --------------------------------------------------------- */
add_action('wp_ajax_hb_cog_get_profile', 'hb_cog_handle_get_profile');
add_action('wp_ajax_nopriv_hb_cog_get_profile', 'hb_cog_handle_get_profile');

function hb_cog_handle_get_profile() {
  $nonce_check = check_ajax_referer('hb_cog_nonce', false, false);
  if (!$nonce_check) {
    wp_send_json_error('שגיאת אבטחה.');
    return;
  }
  
  $user_id = get_current_user_id();
  if (!$user_id) {
    wp_send_json_error('משתמש לא מחובר.');
    return;
  }
  
  $track = isset($_POST['track']) ? sanitize_text_field($_POST['track']) : 'senior';
  $days = isset($_POST['days']) ? max(1, min(30, intval($_POST['days']))) : 7;
  
  $profile_data = [];
  $today = new DateTime();
  global $wpdb;
  $table_daily = $wpdb->prefix . 'hb_cog_daily';
  
  for ($i = 0; $i < $days; $i++) {
    $date_obj = clone $today;
    $date_obj->modify("-{$i} days");
    $date = $date_obj->format('Y-m-d');
    
    // קריאה מ-DB במקום user_meta
    $daily_row = $wpdb->get_row($wpdb->prepare(
      "SELECT daily_score, domains FROM $table_daily WHERE user_id = %d AND date_iso = %s",
      $user_id,
      $date
    ), ARRAY_A);
    
    if ($daily_row) {
      $profile_data[] = [
        'date_iso' => $date,
        'daily_score' => intval($daily_row['daily_score'] ?? 0),
        'domains' => json_decode($daily_row['domains'] ?? '[]', true)
      ];
    } else {
      $profile_data[] = [
        'date_iso' => $date,
        'daily_score' => 0,
        'domains' => []
      ];
    }
  }
  
  wp_send_json_success([
    'profile_data' => $profile_data,
    'track' => $track,
    'days' => $days
  ]);
}

/* ---------------------------------------------------------
 * 6.5) פונקציות חישוב Summary: ציון יומי משוקלל, טרנד, רצף
 * --------------------------------------------------------- */

/**
 * אוסף את כל הניסיונות של משתמש ליום מסוים מ-DB
 * 
 * @param int $user_id ID המשתמש
 * @param string $date_iso תאריך בפורמט Y-m-d
 * @return array מערך ניסיונות
 */
function hb_cog_get_daily_summary($user_id, $date_iso) {
  return hb_cog_get_daily_summary_from_db($user_id, $date_iso);
}

/**
 * אוסף את כל הניסיונות של משתמש ליום מסוים מ-DB
 * 
 * @param int $user_id ID המשתמש
 * @param string $date_iso תאריך בפורמט Y-m-d
 * @return array מערך ניסיונות בפורמט המקורי
 */
function hb_cog_get_daily_summary_from_db($user_id, $date_iso) {
  global $wpdb;
  $table_attempts = $wpdb->prefix . 'hb_cog_attempts';
  
  $results = $wpdb->get_results($wpdb->prepare(
    "SELECT * FROM $table_attempts WHERE user_id = %d AND date_iso = %s ORDER BY created_at ASC",
    $user_id,
    $date_iso
  ), ARRAY_A);
  
  if (empty($results)) {
    return [];
  }
  
  // המרה לפורמט המקורי
  $attempts = [];
  foreach ($results as $row) {
    $attempts[] = [
      'track' => $row['track'],
      'game_id' => $row['game_id'],
      'difficulty' => intval($row['difficulty']),
      'attempt_no' => intval($row['attempt_no']),
      'started_at' => $row['started_at'],
      'ended_at' => $row['ended_at'],
      'date_iso' => $row['date_iso'],
      'metrics' => json_decode($row['metrics'], true) ?? [],
      'scores' => json_decode($row['scores'], true) ?? [],
      'domain_contrib' => json_decode($row['domain_contrib'], true) ?? [],
    ];
  }
  
  return $attempts;
}

/**
 * מחשב ציון יומי משוקלל מניסיונות היום
 * 
 * @param array $attempts_for_day מערך ניסיונות ליום
 * @return array ['daily_score' => int, 'games_breakdown' => array, 'domains_breakdown' => array]
 */
function hb_cog_compute_weighted_daily_score($attempts_for_day) {
  if (empty($attempts_for_day)) {
    return [
      'daily_score' => 0,
      'games_breakdown' => [],
      'domains_breakdown' => [],
    ];
  }
  
  $game_weights = HB_COG_GAME_WEIGHTS;
  $total_weighted_score = 0;
  $total_weight = 0;
  $games_breakdown = [];
  $domains_breakdown = [];
  
  foreach ($attempts_for_day as $attempt) {
    $game_id = $attempt['game_id'] ?? 'unknown';
    $weight = $game_weights[$game_id] ?? 1.0;
    
    if (isset($attempt['scores']['game_score'])) {
      $game_score = floatval($attempt['scores']['game_score']);
      $total_weighted_score += $game_score * $weight;
      $total_weight += $weight;
      
      // Breakdown לפי משחק
      if (!isset($games_breakdown[$game_id])) {
        $games_breakdown[$game_id] = [
          'score' => 0,
          'weight' => $weight,
          'count' => 0,
        ];
      }
      $games_breakdown[$game_id]['score'] += $game_score;
      $games_breakdown[$game_id]['count']++;
    }
    
    // Breakdown לפי תחומים
    if (isset($attempt['domain_contrib']) && is_array($attempt['domain_contrib'])) {
      foreach ($attempt['domain_contrib'] as $domain => $value) {
        if (!isset($domains_breakdown[$domain])) {
          $domains_breakdown[$domain] = 0;
        }
        $domains_breakdown[$domain] += floatval($value) * $weight;
      }
    }
  }
  
  // חישוב ממוצע משוקלל
  $daily_score = $total_weight > 0 ? round($total_weighted_score / $total_weight) : 0;
  $daily_score = max(0, min(100, $daily_score)); // Clamp 0-100
  
  // ממוצע לכל משחק
  foreach ($games_breakdown as $game_id => &$data) {
    if ($data['count'] > 0) {
      $data['score'] = round($data['score'] / $data['count']);
    }
  }
  
  return [
    'daily_score' => intval($daily_score),
    'games_breakdown' => $games_breakdown,
    'domains_breakdown' => $domains_breakdown,
  ];
}

/**
 * מחשב רצף ימים עם אימון (daily_score >= min_score)
 * 
 * @param int $user_id ID המשתמש
 * @param int $min_score ציון מינימלי (ברירת מחדל: 1 = כל אימון נחשב)
 * @param int $max_days מספר ימים מקסימלי לבדיקה (ברירת מחדל: 30)
 * @return int מספר ימים רצופים
 */
function hb_cog_get_streak_days($user_id, $min_score = 1, $max_days = 30) {
  $today = new DateTime();
  $streak = 0;
  
  for ($i = 0; $i < $max_days; $i++) {
    $date_obj = clone $today;
    $date_obj->modify("-{$i} days");
    $date = $date_obj->format('Y-m-d');
    
    $attempts = hb_cog_get_daily_summary($user_id, $date);
    if (empty($attempts)) {
      break; // אין אימון ביום זה - סוף הרצף
    }
    
    $daily_data = hb_cog_compute_weighted_daily_score($attempts);
    if ($daily_data['daily_score'] >= $min_score) {
      $streak++;
    } else {
      break; // ציון נמוך מדי - סוף הרצף
    }
  }
  
  return $streak;
}

/**
 * מחשב מגמה פשוטה: השוואה בין 3 ימים אחרונים ל-3 ימים קודמים
 * 
 * @param int $user_id ID המשתמש
 * @param int $days מספר ימים לבדיקה (ברירת מחדל: 7)
 * @return string 'up' / 'stable' / 'down'
 */
function hb_cog_get_trend($user_id, $days = 7) {
  $today = new DateTime();
  $scores = [];
  
  // איסוף ציונים לימים האחרונים
  for ($i = 0; $i < $days; $i++) {
    $date_obj = clone $today;
    $date_obj->modify("-{$i} days");
    $date = $date_obj->format('Y-m-d');
    
    $attempts = hb_cog_get_daily_summary($user_id, $date);
    if (!empty($attempts)) {
      $daily_data = hb_cog_compute_weighted_daily_score($attempts);
      $scores[] = $daily_data['daily_score'];
    } else {
      $scores[] = 0;
    }
  }
  
  if (count($scores) < 4) {
    return 'stable'; // לא מספיק נתונים
  }
  
  // חלוקה לשני חצאים
  $half = intval(count($scores) / 2);
  $first_half = array_slice($scores, $half);
  $second_half = array_slice($scores, 0, $half);
  
  $avg_first = array_sum($first_half) / count($first_half);
  $avg_second = array_sum($second_half) / count($second_half);
  
  $diff = $avg_first - $avg_second;
  
  if ($diff > 5) {
    return 'up';
  } elseif ($diff < -5) {
    return 'down';
  } else {
    return 'stable';
  }
}

/**
 * מחזיר את התחום המוביל מתוך domains breakdown
 * 
 * @param array $domains_breakdown מערך תחומים עם ערכים
 * @return string|null שם התחום המוביל או null
 */
function hb_cog_get_top_domain($domains_breakdown) {
  if (empty($domains_breakdown) || !is_array($domains_breakdown)) {
    return null;
  }
  
  $top_domain = null;
  $max_value = 0;
  
  foreach ($domains_breakdown as $domain => $value) {
    if (floatval($value) > $max_value) {
      $max_value = floatval($value);
      $top_domain = $domain;
    }
  }
  
  return $top_domain;
}

/* ---------------------------------------------------------
 * 7) Shortcode: [hb_account_dashboard]
 * --------------------------------------------------------- */
add_shortcode('hb_account_dashboard', function($atts) {
  // בדיקה אם המשתמש מחובר
  if (!is_user_logged_in()) {
    return '<div class="hb-account-dashboard-not-logged-in" style="text-align: center; padding: 40px; background: #fff; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
      <h3 style="color: #333; margin-bottom: 16px;">נא להיכנס לאזור האישי</h3>
      <p style="color: #666; font-size: 16px;">כדי לצפות בתוכן, נא להתחבר לחשבון שלך.</p>
    </div>';
  }
  
  $user_id = get_current_user_id();
  
  // בדיקה אם יש קורס (PMPro)
  $has_course = false;
  $course_url = '';
  $course_title = 'הקורס שלי';
  $course_description = 'אם כבר רכשת, היכנס כאן. אחרת לחץ להירשם';
  
  // URLs קבועים
  $course_registration_url = 'https://higayonbarie.co.il/%d7%a7%d7%95%d7%a8%d7%a1-%d7%aa%d7%a9%d7%91%d7%a6%d7%99-%d7%94%d7%99%d7%92%d7%99%d7%95%d7%9f-%d7%90%d7%95%d7%a0%d7%9c%d7%99%d7%99%d7%9f/';
  $course_lessons_url = 'https://higayonbarie.co.il/%d7%a9%d7%99%d7%a2%d7%95%d7%a8%d7%99%d7%9d/';
  
  if (function_exists('pmpro_hasMembershipLevel')) {
    // בדיקה אם יש membership level כלשהו
    $has_course = pmpro_hasMembershipLevel();
    
    if ($has_course) {
      // אם יש membership, קישור ישיר לעמוד השיעורים
        // ננסה למצוא עמוד עם slug "שיעורים"
        $lessons_page = get_page_by_path('שיעורים');
      if ($lessons_page && get_post_status($lessons_page->ID) === 'publish') {
          $test_url = get_permalink($lessons_page->ID);
          // בדיקה שהקישור לא מחזיר את עמוד הבית
          if ($test_url && $test_url != home_url('/') && $test_url != home_url() && $test_url != home_url() . '/') {
            $course_url = $test_url;
        } else {
            // אם get_permalink מחזיר את עמוד הבית, נשתמש ב-URL ישיר
            $course_url = $course_lessons_url;
          }
        } else {
        // Fallback - URL מקודד: שיעורים
        $course_url = $course_lessons_url;
      }
    } else {
      // אם אין membership, קישור לעמוד הקורס (הרשמה)
      // ננסה למצוא עמוד עם slug "קורס-תשבצי-היגיון-אונליין"
      $course_registration_page = get_page_by_path('קורס-תשבצי-היגיון-אונליין');
      if ($course_registration_page && get_post_status($course_registration_page->ID) === 'publish') {
        $test_url = get_permalink($course_registration_page->ID);
        // בדיקה שהקישור לא מחזיר את עמוד הבית
        if ($test_url && $test_url != home_url('/') && $test_url != home_url() && $test_url != home_url() . '/') {
          $course_url = $test_url;
        } else {
          // אם get_permalink מחזיר את עמוד הבית, נשתמש ב-URL ישיר
          $course_url = $course_registration_url;
        }
      } else {
        // Fallback - URL מקודד
        $course_url = $course_registration_url;
      }
      
      $course_title = 'הרשמה לקורס';
      $course_description = 'הצטרפו לקורס המקיף לפתרון תשבצי היגיון';
    }
  } else {
    // אם PMPro לא פעיל, נבדוק WooCommerce
    if (function_exists('wc_memberships_get_user_memberships')) {
      $memberships = wc_memberships_get_user_memberships($user_id);
      if (!empty($memberships)) {
        $has_course = true;
        $course_url = $course_lessons_url; // אם יש membership, קישור לשיעורים
      } else {
        $course_url = $course_registration_url; // אם אין, קישור להרשמה
        $course_title = 'הרשמה לקורס';
        $course_description = 'הצטרפו לקורס המקיף לפתרון תשבצי היגיון';
      }
    } else {
      // אם אין PMPro ואין WooCommerce, default להרשמה
      $course_url = $course_registration_url;
      $course_title = 'הרשמה לקורס';
      $course_description = 'הצטרפו לקורס המקיף לפתרון תשבצי היגיון';
    }
  }
  
  // קישור לאימון קוגניטיבי - רק למשתמשים רשומים
  $training_url = '';
  $training_title = 'אימון קוגניטיבי';
  $training_description = 'שחקו במשחקי מוח מותאמים לגיל השלישי';
  $training_button_text = 'התחל אימון';
  
  // בדיקה אם יש membership לאימון קוגניטיבי
  $has_training_membership = false;
  if (function_exists('pmpro_hasMembershipLevel')) {
    // בדיקה אם יש membership level ספציפי לאימון קוגניטיבי
    // אפשר לבדוק level ID או שם ספציפי - כרגע נבדוק אם יש membership כלשהו
    // בעתיד אפשר להוסיף: pmpro_hasMembershipLevel('אימון קוגניטיבי') || pmpro_hasMembershipLevel(5); // לדוגמה
    $has_training_membership = pmpro_hasMembershipLevel();
  }
  
  if ($has_training_membership) {
    // אם יש membership, קישור לעמוד האימון
    $training_page_id = get_option('hb_cog_brain_training_page_id');
    if ($training_page_id && get_post_status($training_page_id) === 'publish') {
      $training_url = get_permalink($training_page_id);
    } else {
      $training_page = get_page_by_path('brain-training');
      if (!$training_page) {
        $training_page = get_page_by_path('אימון-קוגניטיבי');
      }
      if ($training_page) {
        $training_url = get_permalink($training_page->ID);
      } else {
        $training_url = home_url('/brain-training');
      }
    }
  } else {
    // אם אין membership, קישור לעמוד הרשמה
    $training_registration_page = get_page_by_path('brain-training-registration');
    if (!$training_registration_page) {
      $training_registration_page = get_page_by_path('הרשמה-לאימון-קוגניטיבי');
    }
    if ($training_registration_page) {
      $training_url = get_permalink($training_registration_page->ID);
    } else {
      $training_url = home_url('/brain-training-registration');
    }
    $training_title = 'הרשמה לאימון קוגניטיבי';
    $training_description = 'הצטרפו למסלול אימון קוגניטיבי מותאם לגיל השלישי';
    $training_button_text = 'הרשמה לאימון';
  }
  
  // קישור לכל התשבצים - URL ישיר לארכיון
  $wordle_url = '';
  // נסה קודם את הארכיון (הכי אמין)
  $archive_url = get_post_type_archive_link('crossword');
  if ($archive_url && $archive_url != home_url('/') && $archive_url != home_url()) {
    $wordle_url = $archive_url;
  } else {
    // נסה למצוא עמוד עם slug "תשבצים"
      $wordle_page = get_page_by_path('תשבצים');
    if ($wordle_page && get_post_status($wordle_page->ID) === 'publish') {
      $test_url = get_permalink($wordle_page->ID);
      if ($test_url && $test_url != home_url('/') && $test_url != home_url()) {
        $wordle_url = $test_url;
      }
    }
    // אם עדיין לא מצאנו, נשתמש ב-URL ישיר
    if (!$wordle_url) {
      $wordle_url = 'https://higayonbarie.co.il/%d7%aa%d7%a9%d7%91%d7%a6%d7%99%d7%9d/';
    }
  }
  
  // קישור לעמוד "התשבצים שלי"
  $my_crosswords_url = '';
  $user_page = get_page_by_path('user');
  $user_page_id = $user_page ? $user_page->ID : 0;
  $user_url = $user_page ? untrailingslashit(get_permalink($user_page->ID)) : '';
  
  // נחפש עמוד עם template "User Crossword" (הכי אמין)
  // אבל נדלג על עמוד ה-/user/ עצמו אם הוא משתמש ב-template הזה
  $pages = get_pages(array(
    'meta_key' => '_wp_page_template',
    'meta_value' => 'template-user_crossword.php',
    'post_status' => 'publish'
  ));
  
  if (!empty($pages)) {
    foreach ($pages as $page) {
      // דלג על עמוד ה-/user/ עצמו
      if ($page->ID == $user_page_id) {
        continue;
      }
      
      $test_url = get_permalink($page->ID);
      $test_url_clean = untrailingslashit($test_url);
      // ודא שה-URL לא מצביע על /user/ או על עמוד הבית
      if ($test_url && 
          $test_url != home_url('/') && 
          $test_url != home_url() &&
          $test_url_clean != $user_url &&
          $test_url_clean != untrailingslashit(home_url('/user'))) {
        $my_crosswords_url = $test_url;
        break;
      }
    }
  }
  
  // אם לא מצאנו, ננסה slug (וגם נדלג על עמוד ה-/user/ אם הוא נמצא)
  if (!$my_crosswords_url) {
    $my_crosswords_page = get_page_by_path('user_crosswords_page');
    if (!$my_crosswords_page) {
      $my_crosswords_page = get_page_by_path('התשבצים-שלי');
    }
    // ודא שזה לא עמוד ה-/user/ עצמו
    if ($my_crosswords_page && $my_crosswords_page->ID != $user_page_id && get_post_status($my_crosswords_page->ID) === 'publish') {
      $test_url = get_permalink($my_crosswords_page->ID);
      $test_url_clean = untrailingslashit($test_url);
      // ודא שה-URL לא מצביע על /user/ או על עמוד הבית
      if ($test_url && 
          $test_url != home_url('/') && 
          $test_url != home_url() &&
          $test_url_clean != $user_url &&
          $test_url_clean != untrailingslashit(home_url('/user'))) {
        $my_crosswords_url = $test_url;
      }
    }
  }
  
  // Fallback - URL ישיר (רק אם לא מצאנו עמוד)
  if (!$my_crosswords_url) {
    $my_crosswords_url = 'https://higayonbarie.co.il/user_crosswords_page/';
  }
  
  // בדיקה אחרונה - ודא שהקישור לא מצביע על /user/
  $my_crosswords_url_clean = untrailingslashit($my_crosswords_url);
  if ($user_url && ($my_crosswords_url_clean === $user_url || $my_crosswords_url_clean === untrailingslashit(home_url('/user')))) {
    // אם הקישור עדיין מצביע על /user/, נשתמש ב-URL ישיר
    $my_crosswords_url = 'https://higayonbarie.co.il/user_crosswords_page/';
  }
  
  // קישור לעריכת פרופיל - URL ספציפי
  $profile_url = 'https://higayonbarie.co.il/membership-account/your-profile/';
  
  // אם זה לא אותו domain, נשתמש ב-relative URL
  if (strpos($profile_url, home_url()) === false) {
    $profile_url = '/membership-account/your-profile/';
  }
  
  // URLs לכותרות מעוצבות
  $header_account_url = 'https://higayonbarie.co.il/wp-content/uploads/2025/04/HB_Headers_Account.svg';
  $header_course_url = 'https://higayonbarie.co.il/wp-content/uploads/2025/04/HB_Headers_course.svg';
  $header_saved_crosswords_url = 'https://higayonbarie.co.il/wp-content/uploads/2025/04/saved-crosswords.svg';
  $header_all_crosswords_url = 'https://higayonbarie.co.il/wp-content/uploads/2025/04/HB_Headers_crosswords-all.svg';
  
  // DEBUG: בדיקה שהקישורים תקינים
  // אם course_url הוא עמוד הבית, נשתמש ב-URL ישיר
  $course_url_clean = untrailingslashit($course_url);
  $home_url_clean = untrailingslashit(home_url());
  if ($course_url_clean === $home_url_clean || 
      $course_url_clean === untrailingslashit(home_url('/')) ||
      $course_url == home_url('/') || 
      $course_url == home_url() || 
      $course_url == home_url() . '/') {
    $course_url = $has_course ? $course_lessons_url : $course_registration_url;
  }
  
  // בדיקה נוספת - אם עדיין עמוד הבית, נשתמש ב-URL ישיר
  $course_url_clean_after = untrailingslashit($course_url);
  if ($course_url_clean_after === $home_url_clean || 
      $course_url_clean_after === untrailingslashit(home_url('/'))) {
    $course_url = $has_course ? $course_lessons_url : $course_registration_url;
  }
  
  
  ob_start(); ?>
  <div class="hb-account-dashboard">
    <div class="hb-dashboard-header">
      <p>ברוכים הבאים לאזור האישי שלכם</p>
    </div>
    
    <div class="hb-dashboard-cards">
      <!-- כרטיס 1: הקורס שלי -->
      <div class="hb-dashboard-card">
        <div class="hb-card-header-image">
          <img src="<?php echo esc_url($header_course_url); ?>" alt="<?php echo esc_attr($course_title); ?>" />
        </div>
        <div class="hb-card-icon">
          <svg width="48" height="48" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M12 2L2 7L12 12L22 7L12 2Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M2 17L12 22L22 17" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M2 12L12 17L22 12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
        </div>
        <h3><?php echo esc_html($course_title); ?></h3>
        <p><?php echo esc_html($course_description); ?></p>
        <a href="<?php echo esc_url($course_url); ?>" class="hb-card-button" data-hb-url="<?php echo esc_attr($course_url); ?>">
          <?php echo esc_html($has_course ? 'היכנס לקורס' : $course_title); ?>
        </a>
      </div>
      
      <!-- כרטיס 2: התשבצים שלי -->
      <div class="hb-dashboard-card">
        <div class="hb-card-header-image">
          <img src="<?php echo esc_url($header_saved_crosswords_url); ?>" alt="התשבצים שלי" />
        </div>
        <div class="hb-card-icon">
          <svg width="48" height="48" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <rect x="3" y="3" width="18" height="18" rx="2" stroke="currentColor" stroke-width="2"/>
            <path d="M3 9H21" stroke="currentColor" stroke-width="2"/>
            <path d="M9 3V21" stroke="currentColor" stroke-width="2"/>
            <circle cx="12" cy="12" r="2" fill="currentColor"/>
          </svg>
        </div>
        <h3>התשבצים שלי</h3>
        <p>צפו בכל התשבצים שהתחלתם, פתרתם או שמרתם</p>
        <a href="<?php echo esc_url($my_crosswords_url); ?>" class="hb-card-button" data-hb-url="<?php echo esc_attr($my_crosswords_url); ?>">הצג את התשבצים שלי</a>
      </div>
      
      <!-- כרטיס 3: כל התשבצים -->
      <div class="hb-dashboard-card">
        <div class="hb-card-header-image">
          <img src="<?php echo esc_url($header_all_crosswords_url); ?>" alt="כל התשבצים" />
        </div>
        <div class="hb-card-icon">
          <svg width="48" height="48" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <rect x="3" y="3" width="18" height="18" rx="2" stroke="currentColor" stroke-width="2"/>
            <path d="M3 9H21" stroke="currentColor" stroke-width="2"/>
            <path d="M9 3V21" stroke="currentColor" stroke-width="2"/>
          </svg>
        </div>
        <h3>כל התשבצים</h3>
        <p>גללו בין כל התשבצים הזמינים באתר</p>
        <a href="<?php echo esc_url($wordle_url); ?>" class="hb-card-button" data-hb-url="<?php echo esc_attr($wordle_url); ?>">צפה בתשבצים</a>
      </div>
      
      <!-- כרטיס 4: עריכת פרופיל -->
      <div class="hb-dashboard-card">
        <div class="hb-card-icon">
          <svg width="48" height="48" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M20 21V19C20 17.9391 19.5786 16.9217 18.8284 16.1716C18.0783 15.4214 17.0609 15 16 15H8C6.93913 15 5.92172 15.4214 5.17157 16.1716C4.42143 16.9217 4 17.9391 4 19V21" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            <circle cx="12" cy="7" r="4" stroke="currentColor" stroke-width="2"/>
          </svg>
        </div>
        <h3>עריכת פרופיל</h3>
        <p>עדכנו את פרטי החשבון וההגדרות האישיות</p>
        <a href="<?php echo esc_url($profile_url); ?>" class="hb-card-button" data-hb-url="<?php echo esc_attr($profile_url); ?>">ערוך פרופיל</a>
      </div>
      
      <!-- כרטיס 5: אימון קוגניטיבי (לא פעיל - בתחתית) -->
      <div class="hb-dashboard-card hb-dashboard-card-inactive">
        <div class="hb-card-icon">
          <svg width="48" height="48" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M12 2L2 7L12 12L22 7L12 2Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2"/>
            <path d="M12 6V12L16 14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
          </svg>
    </div>
        <h3><?php echo esc_html($training_title); ?></h3>
        <p><?php echo esc_html($training_description); ?></p>
        <div class="hb-card-status-badge">בפיתוח - בקרוב</div>
        <a href="<?php echo esc_url($training_url); ?>" class="hb-card-button hb-card-button-disabled" onclick="return false;" aria-disabled="true">
          <?php echo esc_html($training_button_text); ?>
        </a>
  </div>
    </div>
  </div>
  <script>
  (function() {
    // DIAGNOSTIC: בדיקות אבחון
    console.log('=== HB ACCOUNT DASHBOARD DIAGNOSTICS ===');
    console.log('User logged in: <?php echo is_user_logged_in() ? "YES" : "NO"; ?>');
    console.log('Is admin: <?php echo current_user_can("administrator") ? "YES" : "NO"; ?>');
    <?php if (function_exists('pmpro_hasMembershipLevel')): ?>
    console.log('Has membership (PMPro): <?php echo pmpro_hasMembershipLevel() ? "YES" : "NO"; ?>');
    <?php endif; ?>
    
    // בדיקת קישורים ב-DOM
    document.addEventListener('DOMContentLoaded', function() {
      var dashboardButtons = document.querySelectorAll('.hb-account-dashboard .hb-card-button');
      console.log('Found ' + dashboardButtons.length + ' dashboard buttons');
      
      dashboardButtons.forEach(function(button, index) {
        var href = button.getAttribute('href') || button.href;
        var dataUrl = button.getAttribute('data-hb-url');
        console.log('Button ' + index + ':');
        console.log('  - href: ' + href);
        console.log('  - data-hb-url: ' + dataUrl);
        console.log('  - text: ' + button.textContent.trim());
        
        // בדיקה אם ה-URL הוא עמוד הבית
        var homeUrl = '<?php echo esc_js(home_url('/')); ?>';
        if (href === homeUrl || href === homeUrl.replace(/\/$/, '') || href === homeUrl + '/') {
          console.warn('  ⚠️ WARNING: Button ' + index + ' points to homepage!');
        }
      });
      
      // הגנה על קישורים באזור האישי - מונע redirects לא רצויים
      // CRITICAL: Fix hrefs IMMEDIATELY on page load, not just on click
      dashboardButtons.forEach(function(button) {
        var buttonText = button.textContent.trim();
        var currentHref = button.getAttribute('href') || button.href;
        var dataUrl = button.getAttribute('data-hb-url');
        var homeUrl = '<?php echo esc_js(untrailingslashit(home_url())); ?>';
        var homeUrlWithSlash = '<?php echo esc_js(untrailingslashit(home_url('/'))); ?>';
        var userUrl = '<?php 
          $user_page = get_page_by_path("user");
          echo $user_page ? esc_js(untrailingslashit(get_permalink($user_page->ID))) : esc_js(untrailingslashit(home_url("/user")));
        ?>';
        
        // Fix "הקורס שלי" - if href is homepage, use data-hb-url or direct URL
        if (buttonText.includes('הקורס שלי') || buttonText.includes('היכנס לקורס') || buttonText.includes('הרשמה לקורס')) {
          var currentHrefClean = currentHref.replace(/\/$/, '');
          if (currentHrefClean === homeUrl || currentHrefClean === homeUrlWithSlash || 
              currentHref === '<?php echo esc_js(home_url('/')); ?>' || 
              currentHref === '<?php echo esc_js(home_url()); ?>') {
            console.warn('🔧 FIXING: "הקורס שלי" href is homepage, fixing to: ' + (dataUrl || 'direct URL'));
            var correctUrl = dataUrl;
            if (!correctUrl || correctUrl === homeUrl || correctUrl === homeUrlWithSlash) {
              // Use direct URL
              correctUrl = '<?php 
                $has_course = function_exists("pmpro_hasMembershipLevel") ? pmpro_hasMembershipLevel() : false;
                $course_lessons_url = "https://higayonbarie.co.il/%d7%a9%d7%99%d7%a2%d7%95%d7%a8%d7%99%d7%9d/";
                $course_registration_url = "https://higayonbarie.co.il/%d7%a7%d7%95%d7%a8%d7%a1-%d7%aa%d7%a9%d7%91%d7%a6%d7%99-%d7%94%d7%99%d7%92%d7%99%d7%95%d7%9f-%d7%90%d7%95%d7%a0%d7%9c%d7%99%d7%99%d7%9f/";
                echo esc_js($has_course ? $course_lessons_url : $course_registration_url);
              ?>';
            }
            button.setAttribute('href', correctUrl);
            button.href = correctUrl;
            button.setAttribute('data-hb-url', correctUrl);
          }
        }
        
        // Fix "התשבצים שלי" - if href is /user/, use data-hb-url or direct URL
        if (buttonText.includes('התשבצים שלי') || buttonText.includes('הצג את התשבצים שלי')) {
          var currentHrefClean = currentHref.replace(/\/$/, '');
          var userUrlClean = userUrl.replace(/\/$/, '');
          if (currentHrefClean === userUrlClean || (currentHref.includes('/user') && !currentHref.includes('user_crosswords'))) {
            console.warn('🔧 FIXING: "התשבצים שלי" href is /user/, fixing to: ' + (dataUrl || 'direct URL'));
            var correctUrl = dataUrl;
            if (!correctUrl || correctUrl === userUrl || correctUrl === userUrlClean) {
              // Use direct URL
              correctUrl = '<?php echo esc_js(home_url("/user_crosswords_page/")); ?>';
            }
            button.setAttribute('href', correctUrl);
            button.href = correctUrl;
            button.setAttribute('data-hb-url', correctUrl);
          }
        }
        
        // Store original URL in data-hb-url if not already set
        var originalUrl = button.getAttribute('data-hb-url');
        if (!originalUrl) {
          originalUrl = button.getAttribute('href') || button.href;
          button.setAttribute('data-hb-url', originalUrl);
        }
        
        if (!originalUrl || originalUrl === '<?php echo esc_js(home_url('/')); ?>' || originalUrl === '<?php echo esc_js(home_url()); ?>') {
          return; // דלג על קישורים לא תקינים
        }
        
        // ודא שה-href נכון (final check)
        if (button.href !== originalUrl && button.getAttribute('href') !== originalUrl) {
          console.log('Fixing href for button: ' + buttonText + ' from ' + button.href + ' to ' + originalUrl);
          button.setAttribute('href', originalUrl);
          button.href = originalUrl;
        }
        
        // Note: We removed the JavaScript force redirects - the issue should be fixed at the PHP level
        // by allowing access via pmpro_has_membership_access_filter in functions.php
      });
      
      // בדיקת שינויים ב-DOM (MutationObserver)
      var observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
          if (mutation.type === 'attributes' && mutation.attributeName === 'href') {
            var target = mutation.target;
            if (target.classList.contains('hb-card-button')) {
              var newHref = target.getAttribute('href');
              var originalUrl = target.getAttribute('data-hb-url');
              if (newHref !== originalUrl && (newHref === '<?php echo esc_js(home_url('/')); ?>' || newHref === '<?php echo esc_js(home_url()); ?>')) {
                console.warn('⚠️ DETECTED: href changed to homepage! Restoring original URL.');
                target.setAttribute('href', originalUrl);
                target.href = originalUrl;
              }
            }
          }
        });
      });
      
      dashboardButtons.forEach(function(button) {
        observer.observe(button, { attributes: true, attributeFilter: ['href'] });
      });
      
      console.log('==========================================');
    });
  })();
  </script>
  <?php
  return ob_get_clean();
});

/* ---------------------------------------------------------
 * 8) הוספת טאב "אימון מוח" לאזור האישי
 * --------------------------------------------------------- */

// WP User Manager (WPUM) - הוספת טאב "אימון מוח"
add_filter('wpum_get_account_page_tabs', function($tabs) {
  if (!is_user_logged_in()) {
    return $tabs;
  }
  
  // בדיקה אם WPUM פעיל
  if (function_exists('wpum_get_core_page_id')) {
    $account_page_id = wpum_get_core_page_id('account');
    if ($account_page_id) {
      $tabs['brain_training'] = [
        'name' => 'אימון מוח',
        'priority' => 50 // לפני Password (800) ו-View Profile (900)
      ];
    }
  }
  
  return $tabs;
}, 20);

// תוכן הטאב - WPUM
add_action('wpum_account_page_content_brain_training', function() {
  ?>
  <div class="wpum-account-content wpum-account-brain-training">
    <h2>אימון מוח</h2>
    <p style="font-size: 18px; line-height: 1.6; margin-bottom: 30px; color: #555;">
      אימון מוח יומי קצר מסייע לשמירה על חדות מחשבתית. 
      המשחקים מותאמים במיוחד לגיל השלישי - עם טקסטים גדולים, קצב נוח ומשוב מחזק.
    </p>
    
    <div style="margin-bottom: 40px;">
      <h3 style="margin-bottom: 20px;">משחק Go/No-Go</h3>
      <?php echo do_shortcode('[hb_cog_game game="go_nogo" track="senior" difficulty="1"]'); ?>
    </div>
    
    <div>
      <h3 style="margin-bottom: 20px;">פרופיל 7 ימים</h3>
      <?php echo do_shortcode('[hb_cog_profile track="senior" days="7"]'); ?>
    </div>
  </div>
  <?php
}, 10);

// Fallback - אם WPUM לא משתמש ב-action הספציפי
add_action('wpum_account_page_content', function() {
  $current_tab = get_query_var('tab');
  if ($current_tab === 'brain_training') {
    do_action('wpum_account_page_content_brain_training');
  }
}, 20);

// Paid Memberships Pro (PMPro) - הוספת קישור "אימון מוח" בלינקים של החשבון
add_action('pmpro_member_links_bottom', function() {
  if (!is_user_logged_in()) return;
  
  $page_id = (int) get_option('hb_cog_brain_training_page_id');
  if ($page_id && get_post_status($page_id) === 'publish') {
    echo '<li><a href="' . esc_url(get_permalink($page_id)) . '">אימון מוח</a></li>';
  }
}, 20);

/* ---------------------------------------------------------
 * 9) Shortcode: [hb_cog_summary days="7"]
 * --------------------------------------------------------- */
add_shortcode('hb_cog_summary', function($atts) {
  // בדיקה אם המשתמש מחובר
  if (!is_user_logged_in()) {
    return '<div class="hb-cog-summary-not-logged-in">נא להתחבר כדי לצפות בסיכום.</div>';
  }
  
  $atts = shortcode_atts([
    'days' => 7,
  ], $atts);
  
  $user_id = get_current_user_id();
  $today = date('Y-m-d');
  
  // חישוב נתונים ליום הנוכחי
  $attempts_today = hb_cog_get_daily_summary($user_id, $today);
  $daily_data = hb_cog_compute_weighted_daily_score($attempts_today);
  
  // אם אין נתונים בכלל
  if (empty($attempts_today) && $daily_data['daily_score'] === 0) {
    // נבדוק אם יש נתונים בכלל בימים האחרונים
    $has_any_data = false;
    for ($i = 1; $i <= intval($atts['days']); $i++) {
      $date_obj = new DateTime();
      $date_obj->modify("-{$i} days");
      $date = $date_obj->format('Y-m-d');
      $attempts = hb_cog_get_daily_summary($user_id, $date);
      if (!empty($attempts)) {
        $has_any_data = true;
        break;
      }
    }
    
    if (!$has_any_data) {
      return '<div class="hb-cog-summary-empty">אין עדיין נתונים – בואו נעשה אימון ראשון</div>';
    }
  }
  
  // חישוב רצף
  $streak = hb_cog_get_streak_days($user_id, 1, 30);
  
  // חישוב מגמה
  $trend = hb_cog_get_trend($user_id, intval($atts['days']));
  
  // תחום מוביל
  $top_domain = hb_cog_get_top_domain($daily_data['domains_breakdown']);
  $top_domain_label = $top_domain ? (HB_COG_DOMAIN_LABELS[$top_domain] ?? $top_domain) : null;
  
  // תרגום מגמה לעברית
  $trend_labels = [
    'up' => '↑ מגמה חיובית',
    'down' => '↓ מגמה שלילית',
    'stable' => '→ יציב',
  ];
  $trend_label = $trend_labels[$trend] ?? '→ יציב';
  
  ob_start(); ?>
  <div class="hb-cog-summary">
    <div class="hb-cog-summary-item">
      <span class="hb-cog-summary-label">ציון יומי כולל:</span>
      <span class="hb-cog-summary-value"><?php echo esc_html($daily_data['daily_score']); ?></span>
    </div>
    
    <div class="hb-cog-summary-item">
      <span class="hb-cog-summary-label">רצף ימים:</span>
      <span class="hb-cog-summary-value"><?php echo esc_html($streak); ?></span>
    </div>
    
    <div class="hb-cog-summary-item">
      <span class="hb-cog-summary-label">מגמה:</span>
      <span class="hb-cog-summary-value"><?php echo esc_html($trend_label); ?></span>
    </div>
    
    <?php if ($top_domain_label): ?>
    <div class="hb-cog-summary-item">
      <span class="hb-cog-summary-label">תחום מוביל:</span>
      <span class="hb-cog-summary-value"><?php echo esc_html($top_domain_label); ?></span>
    </div>
    <?php endif; ?>
  </div>
  <?php
  return ob_get_clean();
});

/* ---------------------------------------------------------
 * 10) Shortcode: [hb_cog_dashboard]
 * --------------------------------------------------------- */
add_shortcode('hb_cog_dashboard', function($atts) {
  // בדיקה אם המשתמש מחובר
  if (!is_user_logged_in()) {
    return '<div class="hb-cog-dashboard-not-logged-in">נא להתחבר כדי לצפות בדשבורד.</div>';
  }
  
  $user_id = get_current_user_id();
  $nonce = wp_create_nonce('hb_cog_nonce');
  
  $daily_mix_url = hb_cog_build_daily_mix_start_url($user_id);
  
  ob_start(); ?>
  <div class="hb-cog-dashboard" data-nonce="<?php echo esc_attr($nonce); ?>">
    <div class="hb-cog-dashboard-loading">טוען נתונים...</div>
    <div class="hb-cog-dashboard-content" style="display: none;">
      <div class="hb-cog-dashboard-stats">
        <div class="hb-cog-dashboard-stat">
          <div class="hb-cog-dashboard-stat-label">ציון היום</div>
          <div class="hb-cog-dashboard-stat-value" id="hb-cog-stat-today">-</div>
        </div>
        <div class="hb-cog-dashboard-stat">
          <div class="hb-cog-dashboard-stat-label">ממוצע 7 ימים</div>
          <div class="hb-cog-dashboard-stat-value" id="hb-cog-stat-7days">-</div>
        </div>
        <div class="hb-cog-dashboard-stat">
          <div class="hb-cog-dashboard-stat-label">ממוצע 30 ימים</div>
          <div class="hb-cog-dashboard-stat-value" id="hb-cog-stat-30days">-</div>
        </div>
        <div class="hb-cog-dashboard-stat">
          <div class="hb-cog-dashboard-stat-label">אימונים היום</div>
          <div class="hb-cog-dashboard-stat-value" id="hb-cog-stat-attempts">-</div>
        </div>
        <div class="hb-cog-dashboard-stat">
          <div class="hb-cog-dashboard-stat-label">רצף ימים</div>
          <div class="hb-cog-dashboard-stat-value" id="hb-cog-stat-streak">-</div>
        </div>
        <div class="hb-cog-dashboard-stat">
          <div class="hb-cog-dashboard-stat-label">מגמה</div>
          <div class="hb-cog-dashboard-stat-value" id="hb-cog-stat-trend">-</div>
        </div>
      </div>

      <div class="hb-cog-dashboard-reco" id="hb-cog-reco">טוען המלצה...</div>

      <?php if (!empty($daily_mix_url)): ?>
        <div style="margin-top:14px;text-align:center;">
          <a class="hb-card-button" href="<?php echo esc_url($daily_mix_url); ?>" style="max-width:420px;">
            מבחן יומי משולב
          </a>
        </div>
      <?php endif; ?>

      <div class="hb-cog-dashboard-cats">
        <?php foreach (HB_COG_CATEGORY_PAGES as $domain => $cfg): ?>
          <?php
            // Build full URL for category page
            $cat_url = home_url($cfg['url']);
          ?>
          <div class="hb-cog-cat-card" data-domain="<?php echo esc_attr($domain); ?>">
            <div class="hb-cog-cat-title"><?php echo esc_html($cfg['title']); ?></div>
            <div class="hb-cog-cat-status">
              <span class="hb-cog-cat-status-label">סטטוס:</span>
              <span class="hb-cog-cat-status-value" data-role="status">טוען...</span>
            </div>
            <a class="hb-cog-cat-btn" href="<?php echo esc_url($cat_url); ?>">כניסה לאימון</a>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
  <script>
    (function() {
      var dashboard = document.querySelector('.hb-cog-dashboard');
      if (!dashboard) return;
      
      var nonce = dashboard.dataset.nonce;
      
      fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        credentials: 'same-origin',
        body: 'action=hb_cog_get_dashboard&_ajax_nonce=' + encodeURIComponent(nonce)
      })
      .then(function(res) { return res.json(); })
      .then(function(data) {
        if (data.success) {
          document.getElementById('hb-cog-stat-today').textContent = data.data.today_score || '-';
          document.getElementById('hb-cog-stat-7days').textContent = data.data.avg_7days || '-';
          document.getElementById('hb-cog-stat-30days').textContent = data.data.avg_30days || '-';
          document.getElementById('hb-cog-stat-attempts').textContent = data.data.attempts_today ?? '-';

          // המלצה
          var reco = document.getElementById('hb-cog-reco');
          if (reco) reco.textContent = data.data.recommendation_text || '';

          // כרטיסיות קטגוריה
          var avg = data.data.domains_7days_avg || {};
          document.querySelectorAll('.hb-cog-cat-card').forEach(function(card) {
            var domain = card.getAttribute('data-domain');
            var v = avg[domain];
            var statusEl = card.querySelector('[data-role="status"]');
            if (!statusEl) return;

            if (v == null) {
              statusEl.textContent = 'אין מספיק נתונים';
              return;
            }

            var status = 'בינוני';
            if (v >= 60 || v >= 0.6) status = 'חזק';
            else if (v <= 30 || v <= 0.3) status = 'צריך חיזוק';

            statusEl.textContent = status;
          });

          document.getElementById('hb-cog-stat-streak').textContent = data.data.streak || '0';
          document.getElementById('hb-cog-stat-trend').textContent = data.data.trend_label || '-';
          document.querySelector('.hb-cog-dashboard-loading').style.display = 'none';
          document.querySelector('.hb-cog-dashboard-content').style.display = 'block';
        } else {
          console.error('Dashboard error:', data.data);
          document.querySelector('.hb-cog-dashboard-loading').textContent = 'שגיאה בטעינת נתונים';
        }
      })
      .catch(function(err) {
        console.error('Dashboard load error:', err);
        document.querySelector('.hb-cog-dashboard-loading').textContent = 'שגיאה בטעינת נתונים';
      });
    })();
  </script>
  <?php
  return ob_get_clean();
});

/* ---------------------------------------------------------
 * 10.5) Shortcode: Category page (hb_cog_category)
 * --------------------------------------------------------- */
add_shortcode('hb_cog_category', function($atts) {
  if (!is_user_logged_in()) {
    return '<div class="hb-cog-dashboard-not-logged-in">נא להתחבר כדי לצפות באימון.</div>';
  }

  $atts = shortcode_atts([
    'domain' => '',
    'days' => '30',
  ], $atts);

  $domain = sanitize_text_field($atts['domain']);
  $days = max(1, min(60, (int)$atts['days']));

  if (empty($domain)) {
    return '<div class="hb-cog-error">חסר domain ב-shortcode.</div>';
  }

  $title = HB_COG_DOMAIN_LABELS[$domain] ?? $domain;

  $games = HB_COG_GAMES_BY_DOMAIN[$domain] ?? [];

  $nonce = wp_create_nonce('hb_cog_nonce');

  ob_start(); ?>
  <div class="hb-cog-category" data-domain="<?php echo esc_attr($domain); ?>" data-days="<?php echo esc_attr($days); ?>" data-nonce="<?php echo esc_attr($nonce); ?>">
    <div class="hb-cog-category-header">
      <h2><?php echo esc_html($title); ?></h2>
      <p>כאן תראו סטטיסטיקות והמלצה לאימון הבא בתוך הקטגוריה.</p>
      
      <?php
        $order = array_map(function($x){ return $x['game_id']; }, $games);
        $first_game = $order[0] ?? '';
        $start_url = '';
        if ($first_game) {
          $base = HB_COG_GAME_PAGES[$first_game] ?? '/אימון-קוגניטיבי/';
          $start_url = add_query_arg([
            'game' => $first_game,
            'domain' => $domain,
            'flow' => 'category_test',
            'order' => implode(',', $order),
            'i' => 0,
          ], home_url($base));
        }
      ?>
      <?php if ($first_game): ?>
        <div style="margin: 14px 0 22px 0;">
          <a class="hb-card-button" href="<?php echo esc_url($start_url); ?>">התחל מבחן יומי בקטגוריה</a>
        </div>
      <?php endif; ?>
    </div>

    <div class="hb-cog-category-stats">
      <div class="hb-cog-dashboard-loading">טוען נתוני קטגוריה...</div>
      <div class="hb-cog-category-stats-content" style="display:none;">
        <div class="hb-cog-dashboard-stats">
          <div class="hb-cog-dashboard-stat">
            <div class="hb-cog-dashboard-stat-label">ציון היום</div>
            <div class="hb-cog-dashboard-stat-value" id="hb-cat-today">-</div>
          </div>
          <div class="hb-cog-dashboard-stat">
            <div class="hb-cog-dashboard-stat-label">ממוצע 7 ימים</div>
            <div class="hb-cog-dashboard-stat-value" id="hb-cat-7days">-</div>
          </div>
          <div class="hb-cog-dashboard-stat">
            <div class="hb-cog-dashboard-stat-label">ממוצע 30 ימים</div>
            <div class="hb-cog-dashboard-stat-value" id="hb-cat-30days">-</div>
          </div>
          <div class="hb-cog-dashboard-stat">
            <div class="hb-cog-dashboard-stat-label">אימונים היום</div>
            <div class="hb-cog-dashboard-stat-value" id="hb-cat-attempts">-</div>
          </div>
          <div class="hb-cog-dashboard-stat">
            <div class="hb-cog-dashboard-stat-label">רצף ימים</div>
            <div class="hb-cog-dashboard-stat-value" id="hb-cat-streak">-</div>
          </div>
          <div class="hb-cog-dashboard-stat">
            <div class="hb-cog-dashboard-stat-label">מגמה</div>
            <div class="hb-cog-dashboard-stat-value" id="hb-cat-trend">-</div>
          </div>
        </div>
      </div>
    </div>

    <div class="hb-cog-category-games">
      <h3>אימונים בקטגוריה</h3>

      <?php if (empty($games)): ?>
        <div class="hb-cog-error">אין עדיין משחקים בקטגוריה הזו.</div>
      <?php else: ?>
        <?php foreach ($games as $idx => $g): ?>
          <?php
            $game_id = $g['game_id'];
            $base = HB_COG_GAME_PAGES[$game_id] ?? '/אימון-קוגניטיבי/';
            $url = home_url($base);

            // קישור "מסלול" (רצף) - מעביר game_order + index
            $game_order = array_map(function($x){ return $x['game_id']; }, $games);
            $url = add_query_arg([
              'game' => $game_id,
              'domain' => $domain,
              'flow' => 'category',
              'order' => implode(',', $game_order),
              'i' => $idx,
            ], $url);
          ?>

          <div class="hb-cog-cat-card">
            <div class="hb-cog-cat-title"><?php echo esc_html($g['title']); ?></div>
            <div class="hb-cog-cat-status">
              <span class="hb-cog-cat-status-label">המלצה:</span>
              <span class="hb-cog-cat-status-value">אימון קצר 3–5 דקות</span>
            </div>

            <a class="hb-card-button" href="<?php echo esc_url($url); ?>">כניסה לאימון</a>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>

  <script>
  (function(){
    var root = document.querySelector('.hb-cog-category');
    if (!root) return;

    var domain = root.getAttribute('data-domain');
    var days = root.getAttribute('data-days');
    var nonce = root.getAttribute('data-nonce');

    fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
      method: 'POST',
      headers: {'Content-Type': 'application/x-www-form-urlencoded'},
      credentials: 'same-origin',
      body: 'action=hb_cog_get_category_stats&_ajax_nonce=' + encodeURIComponent(nonce)
          + '&domain=' + encodeURIComponent(domain)
          + '&days=' + encodeURIComponent(days)
    })
    .then(function(r){ return r.json(); })
    .then(function(res){
      var loading = root.querySelector('.hb-cog-dashboard-loading');
      var content = root.querySelector('.hb-cog-category-stats-content');

      if (!res || !res.success || !res.data) {
        if (loading) loading.textContent = 'שגיאה בטעינת נתונים';
        return;
      }

      if (!res.data.has_data) {
        if (loading) loading.textContent = 'אין עדיין נתונים בקטגוריה הזו — התחילו אימון ראשון';
        return;
      }

      document.getElementById('hb-cat-today').textContent = res.data.today_score || '-';
      document.getElementById('hb-cat-7days').textContent = res.data.avg_7days || '-';
      document.getElementById('hb-cat-30days').textContent = res.data.avg_30days || '-';
      document.getElementById('hb-cat-attempts').textContent = res.data.attempts_today || '0';
      document.getElementById('hb-cat-streak').textContent = res.data.streak || '0';
      document.getElementById('hb-cat-trend').textContent = res.data.trend_label || '-';

      if (loading) loading.style.display = 'none';
      if (content) content.style.display = 'block';
    })
    .catch(function(){
      var loading = root.querySelector('.hb-cog-dashboard-loading');
      if (loading) loading.textContent = 'שגיאה בטעינת נתונים';
    });
  })();
  </script>
  <?php
  return ob_get_clean();
});

/* ---------------------------------------------------------
 * Helper functions for daily mix
 * --------------------------------------------------------- */
function hb_cog_get_daily_mix_order_tokens($user_id) {
  $today = current_time('Y-m-d');
  $key = 'hb_cog_daily_mix_' . $user_id . '_' . $today;

  $cached = get_transient($key);
  if (is_array($cached) && !empty($cached)) {
    return $cached;
  }

  // רשימת משחקים יומית קבועה: go_nogo → stroop → visual_search
  $tokens = ['go_nogo@1', 'stroop@1', 'visual_search@1'];

  set_transient($key, $tokens, DAY_IN_SECONDS);
  return $tokens;
}

function hb_cog_build_daily_mix_start_url($user_id) {
  $tokens = hb_cog_get_daily_mix_order_tokens($user_id);
  if (empty($tokens)) return '';

  $first = $tokens[0];
  $game = 'go_nogo';
  $diff = 1;

  if (strpos($first, '@') !== false) {
    list($g, $d) = array_map('trim', explode('@', $first, 2));
    if (!empty($g)) $game = $g;
    $d = intval($d);
    if ($d > 0) $diff = $d;
  } else {
    $game = $first;
  }

  $back_url = get_permalink(get_queried_object_id());
  if (!$back_url) $back_url = home_url('/');

  $base = HB_COG_GAME_PAGES[$game] ?? '/אימון-קוגניטיבי/';

  return add_query_arg([
    'game' => $game,
    'diff' => $diff,
    'flow' => 'daily_mix',
    'order' => implode(',', $tokens),
    'i' => 0,
    'dur' => 5,
    'back' => urlencode($back_url),
  ], home_url($base));
}

/* ---------------------------------------------------------
 * 10.6) Shortcode: Game page with flow support (hb_cog_game_page)
 * --------------------------------------------------------- */
add_shortcode('hb_cog_game_page', function($atts){
  $atts = shortcode_atts([
    'track' => 'senior',
    'difficulty' => '1',
    'game' => '', // Allow game to be specified in shortcode
  ], $atts);

  $track = sanitize_text_field($atts['track']);
  $difficulty = intval($atts['difficulty']);

  // Priority: URL parameter ALWAYS wins (prevents Elementor / shortcode override)
  $game = '';
  
  // URL param ALWAYS wins (prevents Elementor / shortcode override)
  // Check this FIRST before any other logic
  if (isset($_GET['game']) && $_GET['game'] !== '') {
    $raw_game = wp_unslash($_GET['game']);
    $candidate = sanitize_key($raw_game);
    
    // Debug: Log what we got from URL
    error_log('HB_COG: Raw game from URL: ' . $raw_game . ', sanitized: ' . $candidate);
    
    if (defined('HB_COG_GAME_REGISTRY') && isset(HB_COG_GAME_REGISTRY[$candidate])) {
      $game = $candidate;
      error_log('HB_COG: Game validated: ' . $game);
    } else {
      error_log('HB_COG: Invalid game from URL: ' . $candidate . ' (raw: ' . $raw_game . '), available: ' . implode(', ', array_keys(HB_COG_GAME_REGISTRY)));
      $game = 'go_nogo';
    }
  }
  // Fallback to shortcode attribute if no URL param
  elseif (!empty($atts['game'])) {
    $game = sanitize_text_field($atts['game']);
  }

  // allow overriding difficulty via ?diff=2
  $diff_from_qs = isset($_GET['diff']) ? intval($_GET['diff']) : 0;
  if ($diff_from_qs > 0) {
    $difficulty = $diff_from_qs;
  }

  // duration (minutes) via ?dur=3/5/7 (ברירת מחדל: 5)
  $dur = isset($_GET['dur']) ? intval($_GET['dur']) : 5;
  if ($dur < 2) $dur = 2;
  if ($dur > 10) $dur = 10;

  // flow (next)
  $flow  = isset($_GET['flow']) ? sanitize_text_field($_GET['flow']) : '';
  $order = '';
  if (isset($_GET['order'])) {
    $order_raw = wp_unslash($_GET['order']);
    // keep safe chars including '@' and ','
    $order = preg_replace('/[^a-zA-Z0-9_\-,@]/', '', $order_raw);
  }
  $i     = isset($_GET['i']) ? intval($_GET['i']) : 0;
  $domain = isset($_GET['domain']) ? sanitize_text_field($_GET['domain']) : '';

  $order_arr = [];
  if (!empty($order)) {
    $order_arr = array_values(array_filter(array_map('trim', explode(',', $order))));
  }

  // If game not set yet (no URL param and no shortcode), try to get from order array
  // BUT: Only if game was NOT already set from URL (which should always win)
  if (empty($game) && !empty($order_arr) && isset($order_arr[$i])) {
    $token = $order_arr[$i];

    if (strpos($token, '@') !== false) {
      list($g, $d) = array_map('trim', explode('@', $token, 2));
      if (!empty($g)) $game = sanitize_text_field($g);
      $d = intval($d);
      if ($d > 0) $difficulty = $d;
    } else {
      $game = sanitize_text_field($token);
    }
  }
  
  // Final fallback
  if (empty($game)) {
    $game = 'go_nogo';
  }
  
  // Validate game exists in registry
  if (!isset(HB_COG_GAME_REGISTRY[$game])) {
    error_log('HB_COG: Game not in registry: ' . $game . ', available: ' . implode(', ', array_keys(HB_COG_GAME_REGISTRY)));
    $game = 'go_nogo'; // Fallback
  }
  
  // Debug: Log the game that will be used (FINAL value before passing to shortcode)
  error_log('HB_COG: Game page FINAL game value: ' . $game . ' (from URL: ' . (isset($_GET['game']) ? $_GET['game'] : 'none') . ', from shortcode atts: ' . (isset($atts['game']) ? $atts['game'] : 'none') . ', from order: ' . (isset($order_arr[$i]) ? $order_arr[$i] : 'none') . ')');

  // back url (encoded)
  $back_url = '';
  if (isset($_GET['back']) && $_GET['back'] !== '') {
    $back_url = esc_url_raw(urldecode($_GET['back']));
  }

  // base url of current game page
  $current_game_base = HB_COG_GAME_PAGES[$game] ?? '/אימון-קוגניטיבי/';
  $current_game_url  = home_url($current_game_base);

  // Next URL
  $next_url = '';
  if (!empty($order_arr) && isset($order_arr[$i + 1])) {
    $next_token = $order_arr[$i + 1];

    $next_game = $next_token;
    $next_diff = 0;

    if (strpos($next_token, '@') !== false) {
      list($ng, $nd) = array_map('trim', explode('@', $next_token, 2));
      $next_game = $ng;
      $next_diff = intval($nd);
    }

    $next_base = HB_COG_GAME_PAGES[$next_game] ?? '/אימון-קוגניטיבי/';

    $next_args = [
      'game'   => $next_game,
      'flow'   => $flow,
      'order'  => implode(',', $order_arr),
      'i'      => $i + 1,
      'dur'    => $dur,
      'domain' => isset($_GET['domain']) ? sanitize_text_field($_GET['domain']) : '',
    ];

    if ($next_diff > 0) {
      $next_args['diff'] = $next_diff;
    }

    if (!empty($back_url)) {
      $next_args['back'] = urlencode($back_url);
    }

    $next_url = add_query_arg($next_args, home_url($next_base));
  }

  // Back to category URL (if exists and not already set from URL)
  if (empty($back_url) && !empty($domain) && isset(HB_COG_CATEGORY_PAGES[$domain]['url'])) {
    $back_url = HB_COG_CATEGORY_PAGES[$domain]['url'];
  }

  // duration links (keep all params, replace dur only)
  $base_url = (is_ssl() ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
  $base_url = remove_query_arg(['dur'], $base_url);

  $dur_url = function($m) use ($base_url) {
    return add_query_arg(['dur' => $m], $base_url);
  };

  ob_start(); ?>
    <div
      data-hb-cog-root
      data-dur="<?php echo esc_attr($dur ?? '5'); ?>"
      data-diff="<?php echo esc_attr($difficulty ?? '1'); ?>"
      data-next-url="<?php echo esc_attr($next_url ?? ''); ?>"
    >
      <div class="hb-cog-game-page"
           data-next-url="<?php echo esc_attr($next_url); ?>"
           data-back-url="<?php echo esc_attr($back_url); ?>">

      <?php 
      // Debug: Log what we're passing to hb_cog_game
      error_log('HB_COG: Passing to hb_cog_game shortcode: game=' . $game . ', track=' . $track . ', difficulty=' . $difficulty);
      echo do_shortcode('[hb_cog_game game="'.esc_attr($game).'" track="'.esc_attr($track).'" difficulty="'.esc_attr($difficulty).'"]'); 
      ?>

      <!-- כפתורי ניווט אחרי סיום/עצירה יוצגו ע"י JS (לא כאן) -->

      </div>
      
      <!-- סטטיסטיקה של המשחק הספציפי -->
      <?php echo do_shortcode('[hb_cog_game_stats game="'.esc_attr($game).'"]'); ?>
      
    </div>
  <?php
  return ob_get_clean();
});

/* ---------------------------------------------------------
 * 10.5) Shortcode: [hb_cog_game_stats] - סטטיסטיקה של משחק ספציפי
 * --------------------------------------------------------- */
add_shortcode('hb_cog_game_stats', function($atts) {
  $atts = shortcode_atts([
    'game' => '',
  ], $atts);
  
  // URL parameter wins
  $game = isset($_GET['game']) ? sanitize_key($_GET['game']) : sanitize_text_field($atts['game']);
  if (empty($game) || !isset(HB_COG_GAME_REGISTRY[$game])) {
    return '<div class="hb-cog-game-stats-error">משחק לא תקין</div>';
  }
  
  $game_name = HB_COG_GAME_REGISTRY[$game];
  $nonce = wp_create_nonce('hb_cog_nonce');
  
  ob_start(); ?>
  <div class="hb-cog-game-stats-container" 
       data-game="<?php echo esc_attr($game); ?>"
       data-nonce="<?php echo esc_attr($nonce); ?>">
    <div class="hb-cog-game-stats-header">
      <h3>סטטיסטיקה: <?php echo esc_html($game_name); ?></h3>
      <div class="hb-cog-game-stats-date-selector">
        <label>בחר תאריך:</label>
        <input type="date" class="hb-cog-stats-date-input" value="<?php echo esc_attr(date('Y-m-d')); ?>">
        <button class="hb-cog-stats-load-btn">טען נתונים</button>
      </div>
    </div>
    <div class="hb-cog-game-stats-loading" style="display:none;">טוען נתונים...</div>
    <div class="hb-cog-game-stats-content" style="display:none;">
      <!-- נתונים יוטענו כאן דרך AJAX -->
    </div>
  </div>
  <script>
  (function() {
    var container = document.querySelector('.hb-cog-game-stats-container');
    if (!container) return;
    
    var game = container.getAttribute('data-game');
    var nonce = container.getAttribute('data-nonce');
    var dateInput = container.querySelector('.hb-cog-stats-date-input');
    var loadBtn = container.querySelector('.hb-cog-stats-load-btn');
    var loading = container.querySelector('.hb-cog-game-stats-loading');
    var content = container.querySelector('.hb-cog-game-stats-content');
    
    function loadStats(date) {
      if (!date) date = dateInput.value;
      loading.style.display = 'block';
      content.style.display = 'none';
      
      fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        credentials: 'same-origin',
        body: 'action=hb_cog_get_game_stats&_ajax_nonce=' + encodeURIComponent(nonce)
            + '&game=' + encodeURIComponent(game)
            + '&date=' + encodeURIComponent(date)
      })
      .then(function(r) { return r.json(); })
      .then(function(res) {
        loading.style.display = 'none';
        if (res.success && res.data) {
          content.style.display = 'block';
          renderStats(res.data);
        } else {
          content.innerHTML = '<div class="hb-cog-stats-error">' + (res.data?.message || 'שגיאה בטעינת נתונים') + '</div>';
          content.style.display = 'block';
        }
      })
      .catch(function(err) {
        loading.style.display = 'none';
        content.innerHTML = '<div class="hb-cog-stats-error">שגיאה בטעינת נתונים</div>';
        content.style.display = 'block';
      });
    }
    
    function renderStats(data) {
      var html = '<div class="hb-cog-game-stats-grid">';
      
      if (data.attempts && data.attempts.length > 0) {
        html += '<div class="hb-cog-stats-section"><h4>אימונים בתאריך ' + data.date + '</h4>';
        html += '<div class="hb-cog-stats-table">';
        html += '<table><thead><tr><th>שעה</th><th>ציון</th><th>דיוק</th><th>משך</th></tr></thead><tbody>';
        
        data.attempts.forEach(function(attempt) {
          var metrics = attempt.metrics || {};
          var scores = attempt.scores || {};
          var started = new Date(attempt.started_at);
          var ended = new Date(attempt.ended_at);
          var duration = Math.round((ended - started) / 1000 / 60); // דקות
          
          html += '<tr>';
          html += '<td>' + started.toLocaleTimeString('he-IL', {hour: '2-digit', minute: '2-digit'}) + '</td>';
          html += '<td>' + (scores.game_score || 0) + '</td>';
          html += '<td>' + Math.round((metrics.accuracy || 0) * 100) + '%</td>';
          html += '<td>' + duration + ' דק\'</td>';
          html += '</tr>';
        });
        
        html += '</tbody></table></div></div>';
      } else {
        html += '<div class="hb-cog-stats-no-data">אין נתונים לתאריך זה</div>';
      }
      
      html += '</div>';
      content.innerHTML = html;
    }
    
    if (loadBtn) loadBtn.addEventListener('click', function() { loadStats(); });
    if (dateInput) {
      dateInput.addEventListener('change', function() { loadStats(); });
    }
    
    // טען נתונים ראשוניים
    loadStats();
  })();
  </script>
  <?php
  return ob_get_clean();
});

/* ---------------------------------------------------------
 * 10.6) AJAX: Game stats (hb_cog_get_game_stats)
 * --------------------------------------------------------- */
add_action('wp_ajax_hb_cog_get_game_stats', 'hb_cog_handle_get_game_stats');

function hb_cog_handle_get_game_stats() {
  $nonce = isset($_POST['_ajax_nonce']) ? sanitize_text_field($_POST['_ajax_nonce']) : '';
  if (!wp_verify_nonce($nonce, 'hb_cog_nonce')) {
    wp_send_json_error(['message' => 'שגיאת אבטחה (nonce).']);
  }

  $user_id = get_current_user_id();
  if (!$user_id) {
    wp_send_json_error(['message' => 'משתמש לא מחובר.']);
  }

  $game = isset($_POST['game']) ? sanitize_key($_POST['game']) : '';
  if (empty($game) || !isset(HB_COG_GAME_REGISTRY[$game])) {
    wp_send_json_error(['message' => 'משחק לא תקין.']);
  }

  $date = isset($_POST['date']) ? sanitize_text_field($_POST['date']) : date('Y-m-d');
  if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    $date = date('Y-m-d');
  }

  global $wpdb;
  $table_attempts = $wpdb->prefix . 'hb_cog_attempts';

  // מביא את כל הניסיונות של המשחק הזה בתאריך הזה
  // רק ניסיונות של לפחות דקה (60 שניות)
  $attempts = $wpdb->get_results($wpdb->prepare(
    "SELECT id, started_at, ended_at, metrics, scores, difficulty
     FROM $table_attempts
     WHERE user_id = %d 
       AND game_id = %s 
       AND date_iso = %s
       AND TIMESTAMPDIFF(SECOND, started_at, ended_at) >= 60
     ORDER BY started_at DESC",
    $user_id,
    $game,
    $date
  ), ARRAY_A);

  $formatted_attempts = [];
  foreach ($attempts as $attempt) {
    $formatted_attempts[] = [
      'id' => $attempt['id'],
      'started_at' => $attempt['started_at'],
      'ended_at' => $attempt['ended_at'],
      'metrics' => json_decode($attempt['metrics'] ?: '{}', true),
      'scores' => json_decode($attempt['scores'] ?: '{}', true),
      'difficulty' => intval($attempt['difficulty']),
    ];
  }

  wp_send_json_success([
    'game' => $game,
    'date' => $date,
    'attempts' => $formatted_attempts,
    'count' => count($formatted_attempts),
  ]);
}

/* ---------------------------------------------------------
 * 11) AJAX: Dashboard data (hb_cog_get_dashboard)
 * --------------------------------------------------------- */
add_action('wp_ajax_hb_cog_get_dashboard', 'hb_cog_handle_get_dashboard');
// אין nopriv – הדשבורד מיועד למשתמש מחובר

function hb_cog_handle_get_dashboard() {
  // קבל nonce גם בשם nonce וגם בשם _ajax_nonce
  $nonce = isset($_POST['nonce']) ? sanitize_text_field($_POST['nonce']) : '';
  if (empty($nonce) && isset($_POST['_ajax_nonce'])) {
    $nonce = sanitize_text_field($_POST['_ajax_nonce']);
  }

  if (!wp_verify_nonce($nonce, 'hb_cog_nonce')) {
    wp_send_json_error(['message' => 'שגיאת אבטחה (nonce).']);
  }

  $user_id = get_current_user_id();
  if (!$user_id) {
    wp_send_json_error(['message' => 'משתמש לא מחובר.']);
  }

  global $wpdb;
  $table_daily = $wpdb->prefix . 'hb_cog_daily';

  $today = current_time('Y-m-d');

  // היום
  $today_score = (int) $wpdb->get_var($wpdb->prepare(
    "SELECT daily_score FROM $table_daily WHERE user_id = %d AND date_iso = %s",
    $user_id,
    $today
  ));

  // 7 ימים אחרונים
  $rows_7 = $wpdb->get_col($wpdb->prepare(
    "SELECT daily_score FROM $table_daily
     WHERE user_id = %d AND date_iso >= DATE_SUB(%s, INTERVAL 6 DAY)
     ORDER BY date_iso DESC",
    $user_id,
    $today
  ));
  $avg_7 = hb_cog_safe_avg($rows_7);

  // 30 ימים אחרונים
  $rows_30 = $wpdb->get_col($wpdb->prepare(
    "SELECT daily_score FROM $table_daily
     WHERE user_id = %d AND date_iso >= DATE_SUB(%s, INTERVAL 29 DAY)
     ORDER BY date_iso DESC",
    $user_id,
    $today
  ));
  $avg_30 = hb_cog_safe_avg($rows_30);

  // streak (כמה ימים רצוף יש ציון > 0; אם תרצה סף אחר—נשנה)
  $streak = 0;
  for ($i = 0; $i < 30; $i++) {
    $d = new DateTime($today);
    $d->modify("-{$i} day");
    $date = $d->format('Y-m-d');
    $s = (int) $wpdb->get_var($wpdb->prepare(
      "SELECT daily_score FROM $table_daily WHERE user_id = %d AND date_iso = %s",
      $user_id,
      $date
    ));
    if ($s > 0) $streak++;
    else break;
  }

  // trend – השוואה בין 7 ימים אחרונים ל־7 ימים שקדמו להם
  $rows_14 = $wpdb->get_col($wpdb->prepare(
    "SELECT daily_score FROM $table_daily
     WHERE user_id = %d AND date_iso >= DATE_SUB(%s, INTERVAL 13 DAY)
     ORDER BY date_iso DESC",
    $user_id,
    $today
  ));
  $first7 = array_slice($rows_14, 0, 7);
  $prev7  = array_slice($rows_14, 7, 7);
  $a1 = hb_cog_safe_avg($first7);
  $a2 = hb_cog_safe_avg($prev7);

  $trend = 'stable';
  if ($a2 > 0) {
    if ($a1 >= $a2 + 3) $trend = 'up';
    elseif ($a1 <= $a2 - 3) $trend = 'down';
  } elseif ($a1 > 0) {
    $trend = 'up';
  }

  $trend_labels = [
    'up' => '↑ מגמה חיובית',
    'down' => '↓ מגמה שלילית',
    'stable' => '→ יציב',
  ];

  // דומיינים 7 ימים
  $domains7 = hb_cog_get_domains_last_days($user_id, 7);
  $avg_domains_7 = $domains7['avg'];

  $top_domain = hb_cog_pick_top_domain($avg_domains_7);
  $weak_domain = hb_cog_pick_weak_domain($avg_domains_7);

  $top_label = $top_domain ? (HB_COG_DOMAIN_LABELS[$top_domain] ?? $top_domain) : null;
  $weak_label = $weak_domain ? (HB_COG_DOMAIN_LABELS[$weak_domain] ?? $weak_domain) : null;

  // אימונים היום (fallback לספירה מה-attempts)
  $attempts_today = (int) $wpdb->get_var($wpdb->prepare(
    "SELECT attempts_count FROM $table_daily WHERE user_id = %d AND date_iso = %s",
    $user_id,
    $today
  ));
  
  // fallback: אם אין ב-daily, ספור מה-attempts
  if ($attempts_today <= 0) {
    $table_attempts = $wpdb->prefix . 'hb_cog_attempts';
    $attempts_today = (int) $wpdb->get_var($wpdb->prepare(
      "SELECT COUNT(*) FROM $table_attempts WHERE user_id = %d AND date_iso = %s",
      $user_id,
      $today
    ));
  }

  // המלצה קצרה
  $recommendation_text = '';
  if ($attempts_today <= 0) {
    $recommendation_text = $weak_label
      ? "מומלץ להתחיל היום באימון קצר שמחזק את תחום: {$weak_label}."
      : "מומלץ להתחיל היום באימון קצר של 3–5 דקות.";
  } else {
    if ($trend === 'down') {
      $recommendation_text = $weak_label
        ? "היום כדאי לחזור על אימון שמחזק את תחום: {$weak_label}, בקצב נוח."
        : "היום כדאי לבצע אימון נוסף קצר בקצב נוח.";
    } else {
      $recommendation_text = $weak_label
        ? "כדי לשמור על איזון, מומלץ להוסיף אימון שמחזק את תחום: {$weak_label}."
        : "כדי לשמור על איזון, מומלץ להוסיף אימון משלים קצר.";
    }
  }

  wp_send_json_success([
    'today_score' => $today_score > 0 ? $today_score : null,
    'avg_7days' => $avg_7 > 0 ? $avg_7 : null,
    'avg_30days' => $avg_30 > 0 ? $avg_30 : null,
    'attempts_today' => $attempts_today,
    'streak' => $streak,
    'trend' => $trend,
    'trend_label' => $trend_labels[$trend] ?? '→ יציב',
    'domains_7days_avg' => $avg_domains_7,
    'top_domain' => $top_domain,
    'top_domain_label' => $top_label,
    'weak_domain' => $weak_domain,
    'weak_domain_label' => $weak_label,
    'recommendation_text' => $recommendation_text,
  ]);
}

/* ---------------------------------------------------------
 * 11.5) AJAX: Category stats (hb_cog_get_category_stats)
 * --------------------------------------------------------- */
add_action('wp_ajax_hb_cog_get_category_stats', 'hb_cog_handle_get_category_stats');

function hb_cog_handle_get_category_stats() {
  $nonce = isset($_POST['_ajax_nonce']) ? sanitize_text_field($_POST['_ajax_nonce']) : '';
  if (!wp_verify_nonce($nonce, 'hb_cog_nonce')) {
    wp_send_json_error(['message' => 'שגיאת אבטחה (nonce).']);
  }

  $user_id = get_current_user_id();
  if (!$user_id) {
    wp_send_json_error(['message' => 'משתמש לא מחובר.']);
  }

  $domain = isset($_POST['domain']) ? sanitize_text_field($_POST['domain']) : '';
  if (empty($domain)) {
    wp_send_json_error(['message' => 'חסר domain.']);
  }

  $days = isset($_POST['days']) ? max(1, min(60, intval($_POST['days']))) : 30;

  global $wpdb;
  $table_daily = $wpdb->prefix . 'hb_cog_daily';
  $table_attempts = $wpdb->prefix . 'hb_cog_attempts';
  $today = current_time('Y-m-d');

  // משחקים בקטגוריה הזו
  $games_in_domain = HB_COG_GAMES_BY_DOMAIN[$domain] ?? [];
  $game_ids = array_map(function($g) { return $g['game_id']; }, $games_in_domain);
  
  if (empty($game_ids)) {
    wp_send_json_success([
      'domain' => $domain,
      'days' => $days,
      'has_data' => false,
    ]);
  }

  // מביא domains json לימים אחרונים (רק של הקטגוריה)
  $rows = $wpdb->get_col($wpdb->prepare(
    "SELECT domains FROM $table_daily
     WHERE user_id = %d AND date_iso >= DATE_SUB(%s, INTERVAL %d DAY)
     ORDER BY date_iso DESC",
    $user_id,
    $today,
    $days - 1
  ));

  $values = [];
  foreach ($rows as $json) {
    $obj = json_decode($json ?: '[]', true);
    if (!is_array($obj)) continue;
    if (!array_key_exists($domain, $obj)) continue;
    $values[] = (float)$obj[$domain];
  }

  // היום - רק של המשחקים בקטגוריה
  $today_value = 0;
  $today_row = $wpdb->get_var($wpdb->prepare(
    "SELECT domains FROM $table_daily WHERE user_id = %d AND date_iso = %s",
    $user_id,
    $today
  ));
  if ($today_row) {
    $today_obj = json_decode($today_row, true);
    if (is_array($today_obj) && array_key_exists($domain, $today_obj)) {
      $today_value = (float)$today_obj[$domain];
    }
  }

  // ממוצע 7 ימים
  $rows_7 = $wpdb->get_col($wpdb->prepare(
    "SELECT domains FROM $table_daily
     WHERE user_id = %d AND date_iso >= DATE_SUB(%s, INTERVAL 6 DAY)
     ORDER BY date_iso DESC",
    $user_id,
    $today
  ));
  $values_7 = [];
  foreach ($rows_7 as $json) {
    $obj = json_decode($json ?: '[]', true);
    if (is_array($obj) && array_key_exists($domain, $obj)) {
      $values_7[] = (float)$obj[$domain];
    }
  }
  $avg_7 = !empty($values_7) ? (array_sum($values_7) / count($values_7)) : 0;

  // ממוצע 30 ימים
  $rows_30 = $wpdb->get_col($wpdb->prepare(
    "SELECT domains FROM $table_daily
     WHERE user_id = %d AND date_iso >= DATE_SUB(%s, INTERVAL 29 DAY)
     ORDER BY date_iso DESC",
    $user_id,
    $today
  ));
  $values_30 = [];
  foreach ($rows_30 as $json) {
    $obj = json_decode($json ?: '[]', true);
    if (is_array($obj) && array_key_exists($domain, $obj)) {
      $values_30[] = (float)$obj[$domain];
    }
  }
  $avg_30 = !empty($values_30) ? (array_sum($values_30) / count($values_30)) : 0;

  // אימונים היום - רק של המשחקים בקטגוריה
  $attempts_today = 0;
  if (!empty($game_ids)) {
    $placeholders = implode(',', array_fill(0, count($game_ids), '%s'));
    $attempts_today = (int) $wpdb->get_var($wpdb->prepare(
      "SELECT COUNT(*) FROM $table_attempts 
       WHERE user_id = %d AND date_iso = %s 
       AND game_id IN ($placeholders)
       AND TIMESTAMPDIFF(SECOND, started_at, ended_at) >= 60",
      array_merge([$user_id, $today], $game_ids)
    ));
  }

  // רצף ימים - רק של הקטגוריה
  $streak = 0;
  for ($i = 0; $i < 30; $i++) {
    $d = new DateTime($today);
    $d->modify("-{$i} day");
    $date = $d->format('Y-m-d');
    $row = $wpdb->get_var($wpdb->prepare(
      "SELECT domains FROM $table_daily WHERE user_id = %d AND date_iso = %s",
      $user_id,
      $date
    ));
    if ($row) {
      $obj = json_decode($row, true);
      if (is_array($obj) && array_key_exists($domain, $obj) && (float)$obj[$domain] > 0) {
        $streak++;
      } else {
        break;
      }
    } else {
      break;
    }
  }

  // מגמה - השוואה בין 7 ימים אחרונים ל-7 ימים שקדמו להם
  $rows_14 = $wpdb->get_col($wpdb->prepare(
    "SELECT domains FROM $table_daily
     WHERE user_id = %d AND date_iso >= DATE_SUB(%s, INTERVAL 13 DAY)
     ORDER BY date_iso DESC",
    $user_id,
    $today
  ));
  $first7 = [];
  $prev7 = [];
  foreach (array_slice($rows_14, 0, 7) as $json) {
    $obj = json_decode($json ?: '[]', true);
    if (is_array($obj) && array_key_exists($domain, $obj)) {
      $first7[] = (float)$obj[$domain];
    }
  }
  foreach (array_slice($rows_14, 7, 7) as $json) {
    $obj = json_decode($json ?: '[]', true);
    if (is_array($obj) && array_key_exists($domain, $obj)) {
      $prev7[] = (float)$obj[$domain];
    }
  }
  $a1 = !empty($first7) ? (array_sum($first7) / count($first7)) : 0;
  $a2 = !empty($prev7) ? (array_sum($prev7) / count($prev7)) : 0;
  
  $trend = 'stable';
  if ($a2 > 0) {
    if ($a1 >= $a2 + 0.08) $trend = 'up';
    elseif ($a1 <= $a2 - 0.08) $trend = 'down';
  } elseif ($a1 > 0) {
    $trend = 'up';
  }

  $trend_labels = [
    'up' => '↑ מגמה חיובית',
    'down' => '↓ מגמה שלילית',
    'stable' => '→ יציב',
  ];

  if (empty($values)) {
    wp_send_json_success([
      'domain' => $domain,
      'days' => $days,
      'has_data' => false,
    ]);
  }

  $avg = array_sum($values) / count($values);

  wp_send_json_success([
    'domain' => $domain,
    'days' => $days,
    'has_data' => true,
    'today_score' => $today_value > 0 ? round($today_value * 100) : null,
    'avg_7days' => $avg_7 > 0 ? round($avg_7 * 100) : null,
    'avg_30days' => $avg_30 > 0 ? round($avg_30 * 100) : null,
    'attempts_today' => $attempts_today,
    'streak' => $streak,
    'trend' => $trend,
    'trend_label' => $trend_labels[$trend] ?? '→ יציב',
    'avg' => $avg,
    'days_with_data' => count($values),
  ]);
}

function hb_cog_safe_avg($arr) {
  if (!is_array($arr) || empty($arr)) return 0;
  $nums = array_map('intval', $arr);
  // פילטר רק ערכים גדולים מ-0
  $filtered = array();
  foreach ($nums as $v) {
    if ($v > 0) {
      $filtered[] = $v;
    }
  }
  if (empty($filtered)) return 0;
  return (int) round(array_sum($filtered) / count($filtered));
}

function hb_cog_get_domains_last_days($user_id, $days = 7) {
  global $wpdb;
  $table_daily = $wpdb->prefix . 'hb_cog_daily';

  $today = current_time('Y-m-d');
  $days = max(1, min(60, (int)$days));

  $rows = $wpdb->get_col($wpdb->prepare(
    "SELECT domains FROM $table_daily
     WHERE user_id = %d AND date_iso >= DATE_SUB(%s, INTERVAL %d DAY)
     ORDER BY date_iso DESC",
    $user_id,
    $today,
    $days - 1
  ));

  $sum = [];
  $count = 0;

  foreach ($rows as $json) {
    $obj = json_decode($json ?: '[]', true);
    if (!is_array($obj) || empty($obj)) continue;

    $count++;
    foreach ($obj as $k => $v) {
      if (!isset($sum[$k])) $sum[$k] = 0;
      $sum[$k] += (float)$v;
    }
  }

  if ($count <= 0) return ['avg' => [], 'days_with_data' => 0];

  $avg = [];
  foreach ($sum as $k => $v) {
    $avg[$k] = $v / $count;
  }

  return ['avg' => $avg, 'days_with_data' => $count];
}

function hb_cog_pick_top_domain($avg) {
  if (!is_array($avg) || empty($avg)) return null;
  $topK = null; $topV = -INF;
  foreach ($avg as $k => $v) {
    if ($v > $topV) { $topV = $v; $topK = $k; }
  }
  return $topK;
}

function hb_cog_pick_weak_domain($avg) {
  if (!is_array($avg) || empty($avg)) return null;
  $weakK = null; $weakV = INF;
  foreach ($avg as $k => $v) {
    if ($v < $weakV) { $weakV = $v; $weakK = $k; }
  }
  return $weakK;
}
