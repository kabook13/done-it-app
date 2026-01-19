<?php
/**
 * Plugin Name: HB Spark Test
 * Description: Spark Test section with AI success rate gauge, multi-model analysis, and lab results table
 * Version: 3.1.0
 * Author: Higayon Barie
 */
if (!defined('ABSPATH')) exit;

/* ---------------------------------------------------------
 * 1) Register Custom Post Type: Lab Entry
 * --------------------------------------------------------- */
add_action('init', function() {
  register_post_type('lab_entry', [
    'labels' => [
      'name' => 'Lab Entries',
      'singular_name' => 'Lab Entry',
      'add_new' => 'Add New Lab Entry',
      'add_new_item' => 'Add New Lab Entry',
      'edit_item' => 'Edit Lab Entry',
      'new_item' => 'New Lab Entry',
      'view_item' => 'View Lab Entry',
      'search_items' => 'Search Lab Entries',
      'not_found' => 'No lab entries found',
      'not_found_in_trash' => 'No lab entries found in Trash',
    ],
    'public' => false,
    'show_ui' => true,
    'show_in_menu' => true,
    'menu_icon' => 'dashicons-clipboard',
    'supports' => ['title', 'page-attributes'],
    'has_archive' => false,
    'rewrite' => false,
  ]);
});

/* ---------------------------------------------------------
 * 2) Add Custom Fields Metabox
 * --------------------------------------------------------- */
add_action('add_meta_boxes', function() {
  add_meta_box(
    'hb_spark_test_fields',
    'Spark Test Fields',
    'hb_spark_test_render_metabox',
    'lab_entry',
    'normal',
    'high'
  );
});

function hb_spark_test_render_metabox($post) {
  wp_nonce_field('hb_spark_test_save_fields', 'hb_spark_test_fields_nonce');
  wp_enqueue_media();
  
  $puzzle_id = get_post_meta($post->ID, '_lab_entry_puzzle_id', true);
  $failure_o1 = get_post_meta($post->ID, '_lab_entry_failure_o1', true);
  $failure_claude = get_post_meta($post->ID, '_lab_entry_failure_claude', true);
  $failure_gemini = get_post_meta($post->ID, '_lab_entry_failure_gemini', true);
  
  // Success checkboxes
  $o1_success = get_post_meta($post->ID, '_o1_success', true) === '1';
  $claude_success = get_post_meta($post->ID, '_claude_success', true) === '1';
  $gemini_success = get_post_meta($post->ID, '_gemini_success', true) === '1';
  
  // 3-Part Modular Spark Solution
  $spark_part_1 = get_post_meta($post->ID, '_lab_entry_spark_part_1', true);
  $spark_part_2 = get_post_meta($post->ID, '_lab_entry_spark_part_2', true);
  $spark_part_3 = get_post_meta($post->ID, '_lab_entry_spark_part_3', true);
  
  // Legacy fields for backward compatibility
  $spark_definition = get_post_meta($post->ID, '_lab_entry_spark_definition', true);
  $spark_indicator = get_post_meta($post->ID, '_lab_entry_spark_indicator', true);
  $spark_final = get_post_meta($post->ID, '_lab_entry_spark_final', true);
  $spark_solution = get_post_meta($post->ID, '_lab_entry_spark_solution', true);
  
  ?>
  <style>
    .hb-spark-test-field-group {
      background: #f9fafb;
      border: 1px solid #e5e7eb;
      border-radius: 8px;
      padding: 20px;
      margin-bottom: 20px;
    }
    .hb-spark-test-field-group h3 {
      margin: 0 0 15px 0;
      font-size: 18px;
      color: #1f2937;
      padding-bottom: 8px;
    }
    .hb-spark-test-field-group.o1 h3 { border-bottom: 2px solid #3b82f6; }
    .hb-spark-test-field-group.claude h3 { border-bottom: 2px solid #d97706; }
    .hb-spark-test-field-group.gemini h3 { border-bottom: 2px solid #059669; }
    .hb-spark-test-success-checkboxes {
      background: #f0fdf4;
      border: 2px solid #86efac;
      border-radius: 8px;
      padding: 15px;
      margin-bottom: 20px;
    }
    .hb-spark-test-success-checkboxes h4 {
      margin: 0 0 12px 0;
      color: #166534;
      font-size: 16px;
    }
    .hb-spark-test-success-checkboxes label {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      margin-left: 20px;
      cursor: pointer;
      font-weight: 500;
    }
    .hb-spark-test-success-checkboxes input[type="checkbox"] {
      width: 18px;
      height: 18px;
      cursor: pointer;
    }
  </style>
  
  <table class="form-table" style="direction: rtl;">
    <tr>
      <th scope="row">
        <label for="lab_entry_puzzle_id">מזהה חידה (Puzzle ID)</label>
      </th>
      <td>
        <input type="text" 
               id="lab_entry_puzzle_id" 
               name="lab_entry_puzzle_id" 
               value="<?php echo esc_attr($puzzle_id); ?>" 
               class="regular-text" 
               placeholder="לדוגמה: puzzle-001" />
        <p class="description">הזן מזהה ייחודי לחידה. כל 32 הרשומות של אותה חידה צריכות את אותו מזהה.</p>
      </td>
    </tr>
  </table>
  
  <!-- Success Checkboxes -->
  <div class="hb-spark-test-success-checkboxes">
    <h4>✅ סמן הצלחות AI</h4>
    <label>
      <input type="checkbox" 
             name="o1_success" 
             value="1" 
             <?php checked($o1_success, true); ?> />
      <span>ChatGPT הצליח</span>
    </label>
    <label>
      <input type="checkbox" 
             name="claude_success" 
             value="1" 
             <?php checked($claude_success, true); ?> />
      <span>Claude הצליח</span>
    </label>
    <label>
      <input type="checkbox" 
             name="gemini_success" 
             value="1" 
             <?php checked($gemini_success, true); ?> />
      <span>Gemini הצליח</span>
    </label>
    <p class="description" style="margin-top: 10px; margin-right: 0;">רמז נחשב כשלון AI רק אם אף אחד מהמודלים לא הצליח (כל התיבות לא מסומנות).</p>
  </div>
  
  <div class="hb-spark-test-field-group o1">
    <h3>🤖 ChatGPT - ניתוח ניסיון</h3>
    <table class="form-table" style="direction: rtl;">
      <tr>
        <td colspan="2">
          <textarea id="lab_entry_failure_o1" 
                    name="lab_entry_failure_o1" 
                    rows="6" 
                    class="large-text" 
                    placeholder="הדבק כאן את ניתוח הניסיון של ChatGPT..."><?php echo esc_textarea($failure_o1); ?></textarea>
          <p class="description">תאר את ניתוח הניסיון של ChatGPT</p>
        </td>
      </tr>
    </table>
  </div>
  
  <div class="hb-spark-test-field-group claude">
    <h3>🧠 Claude 3.5 Sonnet - ניתוח ניסיון</h3>
    <table class="form-table" style="direction: rtl;">
      <tr>
        <td colspan="2">
          <textarea id="lab_entry_failure_claude" 
                    name="lab_entry_failure_claude" 
                    rows="6" 
                    class="large-text" 
                    placeholder="הדבק כאן את ניתוח הניסיון של Claude..."><?php echo esc_textarea($failure_claude); ?></textarea>
          <p class="description">תאר את ניתוח הניסיון של Claude</p>
        </td>
      </tr>
    </table>
  </div>
  
  <div class="hb-spark-test-field-group gemini">
    <h3>⭐ Gemini 1.5 Pro - ניתוח ניסיון</h3>
    <table class="form-table" style="direction: rtl;">
      <tr>
        <td colspan="2">
          <textarea id="lab_entry_failure_gemini" 
                    name="lab_entry_failure_gemini" 
                    rows="6" 
                    class="large-text" 
                    placeholder="הדבק כאן את ניתוח הניסיון של Gemini..."><?php echo esc_textarea($failure_gemini); ?></textarea>
          <p class="description">תאר את ניתוח הניסיון של Gemini</p>
        </td>
      </tr>
    </table>
  </div>
  
  <div class="hb-spark-test-field-group" style="background: #fef3c7; border-color: #fbbf24;">
    <h3 style="border-bottom: 2px solid #f59e0b;">💡 הניצוץ (פתרון אנושי)</h3>
    <p style="margin: 0 0 15px 0; color: #92400e; font-weight: 500;">ניתן להשתמש בשני פורמטים:</p>
    
    <!-- New 3-Part Modular Structure -->
    <div style="margin-bottom: 20px;">
      <h4 style="margin: 0 0 10px 0; color: #78350f;">פורמט מודולרי (מומלץ):</h4>
      <table class="form-table" style="direction: rtl;">
        <tr>
          <th scope="row"><label for="lab_entry_spark_part_1">חלק 1</label></th>
          <td>
            <textarea id="lab_entry_spark_part_1" 
                      name="lab_entry_spark_part_1" 
                      rows="4" 
                      class="large-text" 
                      placeholder="הגדרה/מידע ראשוני..."><?php echo esc_textarea($spark_part_1); ?></textarea>
          </td>
        </tr>
        <tr>
          <th scope="row"><label for="lab_entry_spark_part_2">חלק 2</label></th>
          <td>
            <textarea id="lab_entry_spark_part_2" 
                      name="lab_entry_spark_part_2" 
                      rows="4" 
                      class="large-text" 
                      placeholder="מנגנון/אינדיקטור..."><?php echo esc_textarea($spark_part_2); ?></textarea>
          </td>
        </tr>
        <tr>
          <th scope="row"><label for="lab_entry_spark_part_3">חלק 3 (תשובה סופית)</label></th>
          <td>
            <textarea id="lab_entry_spark_part_3" 
                      name="lab_entry_spark_part_3" 
                      rows="4" 
                      class="large-text" 
                      placeholder="תשובה סופית..."><?php echo esc_textarea($spark_part_3); ?></textarea>
          </td>
        </tr>
      </table>
    </div>
    
    <!-- Legacy Structure (for backward compatibility) -->
    <div style="border-top: 2px dashed #fbbf24; padding-top: 20px;">
      <h4 style="margin: 0 0 10px 0; color: #78350f;">פורמט ישן (תואם לאחור):</h4>
      <table class="form-table" style="direction: rtl;">
        <tr>
          <th scope="row"><label for="lab_entry_spark_definition">הגדרה</label></th>
          <td>
            <textarea id="lab_entry_spark_definition" 
                      name="lab_entry_spark_definition" 
                      rows="3" 
                      class="large-text" 
                      placeholder="הגדרה..."><?php echo esc_textarea($spark_definition); ?></textarea>
          </td>
        </tr>
        <tr>
          <th scope="row"><label for="lab_entry_spark_indicator">מנגנון/אינדיקטור</label></th>
          <td>
            <textarea id="lab_entry_spark_indicator" 
                      name="lab_entry_spark_indicator" 
                      rows="3" 
                      class="large-text" 
                      placeholder="מנגנון/אינדיקטור..."><?php echo esc_textarea($spark_indicator); ?></textarea>
          </td>
        </tr>
        <tr>
          <th scope="row"><label for="lab_entry_spark_final">תשובה סופית</label></th>
          <td>
            <textarea id="lab_entry_spark_final" 
                      name="lab_entry_spark_final" 
                      rows="3" 
                      class="large-text" 
                      placeholder="תשובה סופית..."><?php echo esc_textarea($spark_final); ?></textarea>
          </td>
        </tr>
        <tr>
          <th scope="row"><label for="lab_entry_spark_solution">פתרון מלא (שדה יחיד)</label></th>
          <td>
            <textarea id="lab_entry_spark_solution" 
                      name="lab_entry_spark_solution" 
                      rows="6" 
                      class="large-text" 
                      placeholder="פתרון מלא..."><?php echo esc_textarea($spark_solution); ?></textarea>
          </td>
        </tr>
      </table>
    </div>
  </div>
  
  <?php
}

/* ---------------------------------------------------------
 * 3) Save Custom Fields
 * --------------------------------------------------------- */
add_action('save_post_lab_entry', function($post_id) {
  if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
  if (!isset($_POST['hb_spark_test_fields_nonce']) || 
      !wp_verify_nonce($_POST['hb_spark_test_fields_nonce'], 'hb_spark_test_save_fields')) {
    return;
  }
  if (!current_user_can('edit_post', $post_id)) return;
  
  if (isset($_POST['lab_entry_failure_o1'])) {
    update_post_meta($post_id, '_lab_entry_failure_o1', sanitize_textarea_field($_POST['lab_entry_failure_o1']));
  }
  if (isset($_POST['lab_entry_failure_claude'])) {
    update_post_meta($post_id, '_lab_entry_failure_claude', sanitize_textarea_field($_POST['lab_entry_failure_claude']));
  }
  if (isset($_POST['lab_entry_failure_gemini'])) {
    update_post_meta($post_id, '_lab_entry_failure_gemini', sanitize_textarea_field($_POST['lab_entry_failure_gemini']));
  }
  
  // Save 3-Part Modular Spark Solution
  if (isset($_POST['lab_entry_spark_part_1'])) {
    update_post_meta($post_id, '_lab_entry_spark_part_1', sanitize_textarea_field($_POST['lab_entry_spark_part_1']));
  }
  if (isset($_POST['lab_entry_spark_part_2'])) {
    update_post_meta($post_id, '_lab_entry_spark_part_2', sanitize_textarea_field($_POST['lab_entry_spark_part_2']));
  }
  if (isset($_POST['lab_entry_spark_part_3'])) {
    update_post_meta($post_id, '_lab_entry_spark_part_3', sanitize_textarea_field($_POST['lab_entry_spark_part_3']));
  }
  
  // Save legacy fields
  if (isset($_POST['lab_entry_spark_definition'])) {
    update_post_meta($post_id, '_lab_entry_spark_definition', sanitize_textarea_field($_POST['lab_entry_spark_definition']));
  }
  if (isset($_POST['lab_entry_spark_indicator'])) {
    update_post_meta($post_id, '_lab_entry_spark_indicator', sanitize_textarea_field($_POST['lab_entry_spark_indicator']));
  }
  if (isset($_POST['lab_entry_spark_final'])) {
    update_post_meta($post_id, '_lab_entry_spark_final', sanitize_textarea_field($_POST['lab_entry_spark_final']));
  }
  if (isset($_POST['lab_entry_spark_solution'])) {
    update_post_meta($post_id, '_lab_entry_spark_solution', sanitize_textarea_field($_POST['lab_entry_spark_solution']));
  }
  
  if (isset($_POST['lab_entry_puzzle_id'])) {
    update_post_meta($post_id, '_lab_entry_puzzle_id', sanitize_text_field($_POST['lab_entry_puzzle_id']));
  }
  
  // Save success checkboxes
  update_post_meta($post_id, '_o1_success', isset($_POST['o1_success']) ? '1' : '0');
  update_post_meta($post_id, '_claude_success', isset($_POST['claude_success']) ? '1' : '0');
  update_post_meta($post_id, '_gemini_success', isset($_POST['gemini_success']) ? '1' : '0');
});

/* ---------------------------------------------------------
 * 4) Add Custom Columns to Admin List
 * --------------------------------------------------------- */
add_filter('manage_lab_entry_posts_columns', function($columns) {
  $new_columns = [];
  $new_columns['cb'] = $columns['cb'];
  $new_columns['title'] = 'רמז';
  $new_columns['puzzle_id'] = 'Puzzle ID';
  $new_columns['success'] = 'הצלחות AI';
  $new_columns['date'] = $columns['date'];
  return $new_columns;
});

add_action('manage_lab_entry_posts_custom_column', function($column, $post_id) {
  // Always output something to prevent critical errors
  $output = '—';
  
  try {
    // Basic validation
    if (empty($column) || empty($post_id) || !is_numeric($post_id) || $post_id <= 0) {
      echo $output;
      return;
    }
    
    // Only handle our custom columns
    if ($column !== 'puzzle_id' && $column !== 'success') {
      return;
    }
    
    // Validate post exists and is lab_entry
    $post = get_post($post_id);
    if (!$post || !is_object($post)) {
      echo $output;
      return;
    }
    
    if ($post->post_type !== 'lab_entry') {
      echo $output;
      return;
    }
    
    // Display puzzle_id column
    if ($column === 'puzzle_id') {
      $puzzle_id = get_post_meta($post_id, '_lab_entry_puzzle_id', true);
      if (!empty($puzzle_id) && is_string($puzzle_id)) {
        echo esc_html($puzzle_id);
      } else {
        echo $output;
      }
      return;
    }
    
    // Display success column
    if ($column === 'success') {
      $o1 = get_post_meta($post_id, '_o1_success', true) === '1';
      $claude = get_post_meta($post_id, '_claude_success', true) === '1';
      $gemini = get_post_meta($post_id, '_gemini_success', true) === '1';
      
      $successes = [];
      if ($o1) {
        $successes[] = 'ChatGPT';
      }
      if ($claude) {
        $successes[] = 'Claude';
      }
      if ($gemini) {
        $successes[] = 'Gemini';
      }
      
      if (!empty($successes) && is_array($successes)) {
        echo esc_html(implode(', ', $successes));
      } else {
        echo $output;
      }
      return;
    }
  } catch (Throwable $e) {
    // Always output something to prevent critical errors
    echo $output;
    return;
  } catch (Exception $e) {
    // Fallback for older PHP versions
    echo $output;
    return;
  }
  
  // Fallback - always output something
  echo $output;
}, 10, 2);

/* ---------------------------------------------------------
 * Fix Admin Query to Show All Lab Entries
 * --------------------------------------------------------- */
add_action('pre_get_posts', function($query) {
  // Only in admin
  if (!is_admin()) {
    return;
  }
  
  // Check if this is the main query
  if (!$query->is_main_query()) {
    return;
  }
  
  // Check if we're on the edit screen
  global $pagenow;
  if ($pagenow !== 'edit.php') {
    return;
  }
  
  // Check if this is a lab_entry query
  $post_type = isset($_GET['post_type']) ? sanitize_text_field($_GET['post_type']) : '';
  if ($post_type !== 'lab_entry') {
    return;
  }
  
  // Suppress filters to avoid conflicts with other plugins
  $query->set('suppress_filters', true);
  
  // Ensure post_status includes all statuses
  $post_status = $query->get('post_status');
  if (empty($post_status) || $post_status === 'all' || (is_array($post_status) && in_array('all', $post_status))) {
    $query->set('post_status', 'any');
  }
  
  // Increase posts per page significantly
  $current_ppp = $query->get('posts_per_page');
  if (empty($current_ppp) || $current_ppp < 100) {
    $query->set('posts_per_page', 500);
  }
  
  // Remove any filters that might hide posts
  $query->set('date_query', []);
  $query->set('meta_query', []);
  $query->set('tax_query', []);
  $query->set('post__not_in', []);
  
  // Force post_type to be lab_entry
  $query->set('post_type', 'lab_entry');
}, 999); // High priority to run after other plugins

/* ---------------------------------------------------------
 * 5) Enqueue Styles - Using working CSS as base, will add new features gradually
 * --------------------------------------------------------- */
add_action('wp_enqueue_scripts', function() {
  $css = '
  .spark-test {
    max-width: 1200px;
    margin: 0 auto;
    padding: 60px 20px 40px;
    direction: rtl;
    font-family: system-ui, -apple-system, "Segoe UI", Roboto, "Noto Sans Hebrew", Arial, sans-serif;
  }
  
  .spark-test__header {
    text-align: center;
    margin-bottom: 50px;
  }
  
  .spark-test__main-title {
    font-size: clamp(28px, 5vw, 42px);
    font-weight: 700;
    color: #1f2937;
    margin: 0 0 16px 0;
    line-height: 1.3;
  }
  
  .spark-test__subtitle {
    font-size: 18px;
    color: #6b7280;
    margin: 0;
    line-height: 1.6;
  }
  
  /* Gauge */
  .spark-test__gauge-wrapper {
    display: flex;
    justify-content: center;
    margin-bottom: 0;
  }
  
  .spark-test__gauge-container {
    position: relative;
    width: 200px;
    height: 140px;
    margin: 0 auto;
  }
  
  .spark-test__gauge-svg {
    width: 100%;
    height: 120px;
    display: block;
  }
  
  .spark-test__gauge-track {
    fill: none;
    stroke: #e5e7eb;
    stroke-width: 12;
    stroke-linecap: round;
  }
  
  .spark-test__gauge-fill {
    fill: none;
    stroke: #DB8A16;
    stroke-width: 12;
    stroke-linecap: round;
    stroke-dashoffset: 251.33;
    transition: stroke-dashoffset 1.5s ease-out;
  }
  
  .spark-test__gauge-fill.animated {
    stroke-dashoffset: 0;
  }
  
  .spark-test__gauge-value {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    font-size: 36px;
    font-weight: 700;
    color: #1f2937;
    z-index: 10;
    pointer-events: none;
    margin-top: -10px;
  }
  
  .spark-test__gauge-label {
    position: absolute;
    bottom: 0;
    left: 50%;
    transform: translateX(-50%);
    font-size: 13px;
    color: #6b7280;
    font-weight: 500;
    z-index: 10;
    pointer-events: none;
    white-space: nowrap;
    text-align: center;
    width: 100%;
    padding-top: 8px;
  }
  
  /* Statistics */
  .spark-test__stats-wrapper {
    margin-bottom: 50px;
  }
  
  .spark-test__stats-container {
    display: flex;
    flex-direction: column;
    gap: 30px;
  }
  
  .spark-test__stats-top-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 24px;
    align-items: center;
  }
  
  .spark-test__spark-index-stat {
    background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
    border-radius: 16px;
    padding: 30px;
    display: flex;
    align-items: center;
    gap: 20px;
    box-shadow: 0 4px 12px rgba(251, 191, 36, 0.2);
    margin-bottom: 0;
  }
  
  .spark-test__spark-index-icon {
    font-size: 48px;
    flex-shrink: 0;
  }
  
  .spark-test__spark-index-content {
    flex: 1;
  }
  
  .spark-test__spark-index-label {
    font-size: 16px;
    font-weight: 600;
    color: #92400e;
    margin-bottom: 8px;
  }
  
  .spark-test__spark-index-value {
    font-size: 42px;
    font-weight: 700;
    color: #78350f;
    line-height: 1;
    margin-bottom: 6px;
  }
  
  .spark-test__spark-index-subtext {
    font-size: 14px;
    color: #a16207;
    line-height: 1.4;
  }
  
  .spark-test__leaderboard {
    background: #ffffff;
    border: 2px solid #e5e7eb;
    border-radius: 16px;
    padding: 24px;
    grid-column: 1 / -1;
  }
  
  .spark-test__leaderboard-title {
    font-size: 20px;
    font-weight: 700;
    color: #1f2937;
    margin: 0 0 20px 0;
    text-align: center;
  }
  
  .spark-test__leaderboard-bars {
    display: flex;
    flex-direction: column;
    gap: 18px;
  }
  
  .spark-test__model-bar {
    direction: rtl;
  }
  
  .spark-test__model-bar-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 8px;
  }
  
  .spark-test__model-name {
    font-size: 16px;
    font-weight: 600;
    color: #1f2937;
  }
  
  .spark-test__model-percentage {
    font-size: 18px;
    font-weight: 700;
    color: #1f2937;
  }
  
  .spark-test__model-bar-track {
    height: 32px;
    background: #f3f4f6;
    border-radius: 16px;
    overflow: hidden;
    position: relative;
  }
  
  .spark-test__model-bar-fill {
    height: 100%;
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: flex-end;
    padding: 0 12px;
    font-size: 14px;
    font-weight: 600;
    color: #ffffff;
    transition: width 1s ease-out;
  }
  
  .spark-test__model-bar-fill--o1 {
    background: linear-gradient(90deg, #3b82f6 0%, #2563eb 100%);
  }
  
  .spark-test__model-bar-fill--claude {
    background: linear-gradient(90deg, #d97706 0%, #b45309 100%);
  }
  
  .spark-test__model-bar-fill--gemini {
    background: linear-gradient(90deg, #059669 0%, #047857 100%);
  }
  
  .spark-test__model-bar-stats {
    margin-top: 6px;
    font-size: 13px;
    color: #6b7280;
    text-align: right;
  }
  
  /* Feed */
  .spark-test__feed-wrapper {
    position: relative;
  }
  
  .spark-test__feed {
    display: flex;
    flex-direction: column;
    gap: 30px;
    max-width: 100%;
    margin: 0 auto;
  }
  
  .spark-test__card {
    background: #ffffff !important;
    border: 2px solid #e5e7eb !important;
    border-radius: 20px !important;
    padding: 30px !important;
    margin: 0 0 30px 0 !important;
    transition: all 0.3s ease !important;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05) !important;
    display: block !important;
    position: relative !important;
    width: 100% !important;
    box-sizing: border-box !important;
  }
  
  .spark-test__card:hover {
    border-color: #DB8A16 !important;
    box-shadow: 0 8px 24px rgba(219, 138, 22, 0.15) !important;
    transform: translateY(-2px) !important;
  }
  
  /* Puzzle Accordion */
  .spark-test__puzzle-accordion {
    background: #ffffff !important;
    border: 2px solid #e5e7eb !important;
    border-radius: 20px !important;
    margin: 0 auto 20px !important;
    max-width: 100% !important;
    overflow: hidden !important;
    transition: all 0.3s ease !important;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05) !important;
    display: block !important;
    position: relative !important;
  }
  
  .spark-test__puzzle-accordion:hover {
    border-color: #DB8A16 !important;
    box-shadow: 0 8px 24px rgba(219, 138, 22, 0.15) !important;
  }
  
  .spark-test__puzzle-accordion-button {
    width: 100% !important;
    background: #ffffff !important;
    border: none !important;
    border-bottom: 2px solid #e5e7eb !important;
    padding: 28px 40px !important;
    text-align: center !important;
    cursor: pointer !important;
    display: flex !important;
    justify-content: center !important;
    align-items: center !important;
    gap: 12px !important;
    font-size: 24px !important;
    font-weight: 700 !important;
    color: #1f2937 !important;
    transition: all 0.2s ease !important;
    min-height: 80px !important;
    margin: 0 !important;
    box-shadow: none !important;
    border-radius: 0 !important;
    font-family: system-ui, -apple-system, "Segoe UI", Roboto, "Noto Sans Hebrew", Arial, sans-serif !important;
  }
  
  .spark-test__puzzle-accordion-button:hover {
    background: #f9fafb !important;
    border-bottom-color: #DB8A16 !important;
  }
  
  .spark-test__puzzle-accordion-icon {
    font-size: 16px !important;
    transition: transform 0.3s ease !important;
    color: #6b7280 !important;
    margin: 0 !important;
    padding: 0 !important;
  }
  
  .spark-test__puzzle-accordion-button:hover .spark-test__puzzle-accordion-icon {
    color: #DB8A16 !important;
  }
  
  .spark-test__puzzle-accordion-button[aria-expanded="true"] .spark-test__puzzle-accordion-icon {
    transform: rotate(180deg);
  }
  
  .spark-test__puzzle-accordion-content {
    display: none;
    padding: 0;
  }
  
  .spark-test__puzzle-accordion-content[aria-hidden="false"] {
    display: block;
  }
  
  .spark-test__puzzle-inner {
    padding: 30px;
  }
  
  .spark-test__card--locked {
    filter: blur(4px);
    opacity: 0.6;
    pointer-events: none;
  }
  
  .spark-test__card:not(.spark-test__card--locked) {
    position: relative;
    z-index: 21;
    filter: none;
    opacity: 1;
    pointer-events: auto;
  }
  
  .spark-test__card-clue {
    font-size: 24px !important;
    font-weight: 700 !important;
    color: #1f2937 !important;
    margin: 0 0 30px 0 !important;
    line-height: 1.4 !important;
    text-align: right !important;
    direction: rtl !important;
    background: none !important;
    border: none !important;
    padding: 0 !important;
    font-family: system-ui, -apple-system, "Segoe UI", Roboto, "Noto Sans Hebrew", Arial, sans-serif !important;
  }
  
  /* Accordions */
  .spark-test__spark-accordion {
    margin-bottom: 20px;
  }
  
  .spark-test__spark-accordion-button {
    width: 100% !important;
    background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%) !important;
    border: 2px solid #fbbf24 !important;
    border-radius: 12px !important;
    padding: 18px 24px !important;
    text-align: right !important;
    cursor: pointer !important;
    display: flex !important;
    justify-content: space-between !important;
    align-items: center !important;
    font-size: 16px !important;
    font-weight: 700 !important;
    color: #78350f !important;
    transition: all 0.2s ease !important;
    margin: 0 !important;
    box-shadow: 0 2px 6px rgba(251, 191, 36, 0.15) !important;
    font-family: system-ui, -apple-system, "Segoe UI", Roboto, "Noto Sans Hebrew", Arial, sans-serif !important;
    word-break: break-word !important;
    white-space: normal !important;
  }
  
  .spark-test__spark-accordion-button span:first-child {
    flex: 1 !important;
    min-width: 0 !important;
    word-break: break-word !important;
    white-space: normal !important;
  }
  
  .spark-test__spark-accordion-button:hover {
    background: linear-gradient(135deg, #fde68a 0%, #fcd34d 100%) !important;
    transform: translateY(-2px) !important;
    box-shadow: 0 4px 12px rgba(251, 191, 36, 0.3) !important;
    border-color: #f59e0b !important;
  }
  
  .spark-test__spark-accordion-icon {
    font-size: 14px;
    transition: transform 0.3s ease;
  }
  
  .spark-test__spark-accordion-button[aria-expanded="true"] .spark-test__spark-accordion-icon {
    transform: rotate(180deg);
  }
  
  .spark-test__spark-accordion-content {
    display: none !important;
    padding: 20px !important;
    background: #fef9e7 !important;
    border-radius: 12px !important;
    margin-top: 16px !important;
  }
  
  .spark-test__spark-accordion-content[aria-hidden="false"] {
    display: block !important;
  }
  
  .spark-test__spark-section {
    padding: 20px;
    background: #ffffff;
    border-radius: 8px;
    margin-top: 20px;
  }
  
  .spark-test__spark-chain {
    display: flex;
    flex-direction: column;
    gap: 12px;
    padding-right: 20px;
  }
  
  .spark-test__spark-part {
    background: #ffffff !important;
    border: 2px solid #fbbf24 !important;
    border-radius: 12px !important;
    padding: 12px 16px !important;
    font-size: 16px !important;
    line-height: 1.2 !important;
    color: #78350f !important;
    margin: 0 !important;
    font-family: system-ui, -apple-system, "Segoe UI", Roboto, "Noto Sans Hebrew", Arial, sans-serif !important;
  }
  
  .spark-test__spark-part--3,
  .spark-test__spark-part--final {
    background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
    font-size: 18px;
    font-weight: 600;
  }
  
  .spark-test__spark-connector {
    text-align: center;
    font-size: 28px;
    font-weight: 700;
    color: #f59e0b;
    margin: 4px 0;
  }
  
  .spark-test__spark-text {
    font-size: 17px !important;
    line-height: 1.2 !important;
    color: #78350f !important;
    padding-right: 0 !important;
    margin: 0 !important;
    font-family: system-ui, -apple-system, "Segoe UI", Roboto, "Noto Sans Hebrew", Arial, sans-serif !important;
  }
  
  .spark-test__ai-accordions {
    display: flex;
    flex-direction: column;
    gap: 15px;
    margin-top: 20px;
  }
  
  .spark-test__ai-accordion {
    border-radius: 10px;
    overflow: hidden;
  }
  
  .spark-test__ai-accordion-button {
    width: 100% !important;
    background: #f3f4f6 !important;
    border: none !important;
    padding: 16px 20px !important;
    text-align: right !important;
    cursor: pointer !important;
    display: flex !important;
    justify-content: space-between !important;
    align-items: center !important;
    font-size: 15px !important;
    font-weight: 600 !important;
    color: #1f2937 !important;
    transition: all 0.2s ease !important;
    margin: 0 !important;
    border-radius: 8px !important;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1) !important;
    font-family: system-ui, -apple-system, "Segoe UI", Roboto, "Noto Sans Hebrew", Arial, sans-serif !important;
    position: relative !important;
  }
  
  .spark-test__ai-accordion-button--success {
    background: #ecfdf5 !important;
    border-right: 4px solid #10b981 !important;
  }
  
  .spark-test__ai-accordion-button--success::before {
    content: "✓" !important;
    position: absolute !important;
    right: 20px !important;
    color: #10b981 !important;
    font-weight: 700 !important;
    font-size: 18px !important;
    z-index: 1 !important;
  }
  
  .spark-test__ai-accordion-button--success span:first-child {
    color: #059669 !important;
    font-weight: 700 !important;
  }
  
  .spark-test__ai-accordion-button:hover {
    background: #e5e7eb !important;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.15) !important;
    transform: translateY(-1px) !important;
  }
  
  .spark-test__ai-accordion-button--o1 {
    border-right: 4px solid #3b82f6 !important;
  }
  
  .spark-test__ai-accordion-button--claude {
    border-right: 4px solid #d97706 !important;
  }
  
  .spark-test__ai-accordion-button--gemini {
    border-right: 4px solid #059669 !important;
  }
  
  .spark-test__ai-accordion-icon {
    font-size: 12px;
    transition: transform 0.3s ease;
  }
  
  .spark-test__ai-accordion-button[aria-expanded="true"] .spark-test__ai-accordion-icon {
    transform: rotate(180deg);
  }
  
  .spark-test__ai-accordion-content {
    display: none;
    padding: 12px 20px !important;
    background: #ffffff !important;
    border-top: 1px solid #e5e7eb !important;
    font-size: 15px !important;
    line-height: 1.2 !important;
    color: #4b5563 !important;
  }
  
  .spark-test__ai-accordion-content[aria-hidden="false"] {
    display: block !important;
  }
  
  .spark-test__ai-text {
    margin: 0 !important;
    padding: 0 !important;
    white-space: pre-wrap !important;
    line-height: 1.2 !important;
    font-family: system-ui, -apple-system, "Segoe UI", Roboto, "Noto Sans Hebrew", Arial, sans-serif !important;
  }
  
  /* Paywall Message */
  .spark-test__paywall-message {
    background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%) !important;
    border: 2px solid #fbbf24 !important;
    border-radius: 16px !important;
    padding: 30px !important;
    margin: 30px 0 !important;
    text-align: center !important;
    direction: rtl !important;
    box-shadow: 0 4px 12px rgba(251, 191, 36, 0.2) !important;
  }
  
  .spark-test__paywall-message-icon {
    font-size: 48px !important;
    margin-bottom: 16px !important;
    display: block !important;
  }
  
  .spark-test__paywall-message-title {
    font-size: 24px !important;
    font-weight: 700 !important;
    color: #78350f !important;
    margin: 0 0 12px 0 !important;
    font-family: system-ui, -apple-system, "Segoe UI", Roboto, "Noto Sans Hebrew", Arial, sans-serif !important;
  }
  
  .spark-test__paywall-message-text {
    font-size: 16px !important;
    color: #92400e !important;
    margin: 0 0 24px 0 !important;
    line-height: 1.6 !important;
    font-family: system-ui, -apple-system, "Segoe UI", Roboto, "Noto Sans Hebrew", Arial, sans-serif !important;
  }
  
  .spark-test__paywall-message-button {
    display: inline-block !important;
    background: linear-gradient(135deg, #DB8A16 0%, #f59e0b 100%) !important;
    color: #ffffff !important;
    text-decoration: none !important;
    padding: 16px 40px !important;
    border-radius: 12px !important;
    font-size: 18px !important;
    font-weight: 700 !important;
    transition: all 0.3s ease !important;
    box-shadow: 0 6px 20px rgba(219, 138, 22, 0.35) !important;
    font-family: system-ui, -apple-system, "Segoe UI", Roboto, "Noto Sans Hebrew", Arial, sans-serif !important;
  }
  
  .spark-test__paywall-message-button:hover {
    background: linear-gradient(135deg, #c17813 0%, #d97706 100%) !important;
    transform: translateY(-3px) !important;
    box-shadow: 0 8px 25px rgba(219, 138, 22, 0.45) !important;
    color: #ffffff !important;
  }
  
  /* Paywall Overlay */
  .spark-test__paywall-overlay {
    position: absolute;
    top: 0;
    right: 0;
    left: 0;
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(8px);
    display: none;
    align-items: flex-start;
    justify-content: center;
    z-index: 20;
    border-radius: 24px;
    pointer-events: auto;
    padding-top: 40px;
  }
  
  .spark-test__paywall-content {
    text-align: center;
    padding: 0 40px 50px 40px;
    width: 100%;
  }
  
  .spark-test__paywall-icon {
    font-size: 72px;
    margin-bottom: 24px;
    opacity: 0.8;
  }
  
  .spark-test__paywall-text {
    font-size: 22px;
    font-weight: 700;
    color: #1f2937;
    margin-bottom: 32px;
    line-height: 1.5;
  }
  
  .spark-test__paywall-button {
    display: inline-block;
    background: linear-gradient(135deg, #DB8A16 0%, #f59e0b 100%);
    color: #ffffff !important;
    text-decoration: none;
    padding: 18px 48px;
    border-radius: 12px;
    font-size: 18px;
    font-weight: 700;
    transition: all 0.3s ease;
    box-shadow: 0 6px 20px rgba(219, 138, 22, 0.35);
  }
  
  .spark-test__paywall-button:hover {
    background: linear-gradient(135deg, #c17813 0%, #d97706 100%);
    transform: translateY(-3px);
    box-shadow: 0 8px 25px rgba(219, 138, 22, 0.45);
  }
  
  /* Methodology Footer */
  .spark-test__methodology-footer {
    background: #f9fafb;
    border-top: 1px solid #e5e7eb;
    border-radius: 12px;
    padding: 30px;
    margin-top: 60px;
    direction: rtl;
  }
  
  .spark-test__methodology-footer-header {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 20px;
  }
  
  .spark-test__methodology-footer-icon {
    font-size: 20px;
    opacity: 0.6;
  }
  
  .spark-test__methodology-footer-title {
    font-size: 16px;
    font-weight: 600;
    color: #6b7280;
    margin: 0;
    text-transform: uppercase;
    letter-spacing: 0.5px;
  }
  
  .spark-test__methodology-footer-content {
    color: #6b7280;
    line-height: 1.8;
    font-size: 14px;
  }
  
  .spark-test__methodology-footer-section {
    margin-bottom: 16px;
  }
  
  .spark-test__methodology-footer-section:last-child {
    margin-bottom: 0;
  }
  
  .spark-test__methodology-footer-section-title {
    font-weight: 600;
    color: #4b5563;
    margin-bottom: 6px;
    font-size: 14px;
  }
  
  .spark-test__methodology-footer-section-text {
    color: #6b7280;
    margin: 0;
    font-size: 13px;
  }
  
  /* Methodology Prompt Accordion */
  .spark-test__methodology-prompt-accordion {
    margin-top: 12px;
  }
  
  .spark-test__methodology-prompt-button {
    display: flex !important;
    align-items: center !important;
    justify-content: space-between !important;
    width: 100% !important;
    padding: 12px 16px !important;
    background: #ffffff !important;
    border: 2px solid #e5e7eb !important;
    border-radius: 8px !important;
    font-size: 14px !important;
    font-weight: 600 !important;
    color: #4b5563 !important;
    cursor: pointer !important;
    transition: all 0.2s ease !important;
    font-family: system-ui, -apple-system, "Segoe UI", Roboto, "Noto Sans Hebrew", Arial, sans-serif !important;
    direction: rtl !important;
    text-align: right !important;
  }
  
  .spark-test__methodology-prompt-button:hover {
    background: #f9fafb !important;
    border-color: #d1d5db !important;
  }
  
  .spark-test__methodology-prompt-icon {
    font-size: 12px !important;
    transition: transform 0.2s ease !important;
    margin-right: 8px !important;
  }
  
  .spark-test__methodology-prompt-button[aria-expanded="true"] .spark-test__methodology-prompt-icon {
    transform: rotate(180deg) !important;
  }
  
  .spark-test__methodology-prompt-content {
    display: none;
    margin-top: 12px;
    padding: 16px;
    background: #ffffff;
    border: 2px solid #e5e7eb;
    border-radius: 8px;
    font-size: 13px;
    line-height: 1.8;
    color: #4b5563;
    white-space: pre-wrap;
    word-wrap: break-word;
    direction: rtl;
    text-align: right;
    font-family: system-ui, -apple-system, "Segoe UI", Roboto, "Noto Sans Hebrew", Arial, sans-serif;
  }
  
  .spark-test__methodology-prompt-content[aria-hidden="false"] {
    display: block !important;
  }
  
  /* Responsive */
  @media (max-width: 768px) {
    .spark-test {
      padding: 40px 15px 30px;
    }
    
    .spark-test__main-title {
      font-size: clamp(24px, 6vw, 32px);
      line-height: 1.4 !important;
      margin-bottom: 16px;
    }
    
    .spark-test__subtitle {
      font-size: 16px;
    }
    
    .spark-test__gauge-wrapper {
      margin-bottom: 30px;
    }
    
    .spark-test__gauge-container {
      width: 160px;
      height: 96px;
    }
    
    .spark-test__gauge-value {
      font-size: 28px;
      bottom: 28px;
    }
    
    .spark-test__gauge-label {
      font-size: 12px;
      bottom: 6px;
    }
    
    .spark-test__stats-wrapper {
      margin-bottom: 30px;
    }
    
    .spark-test__stats-container {
      gap: 20px;
    }
    
    .spark-test__stats-top-row {
      grid-template-columns: 1fr;
      gap: 20px;
    }
    
    .spark-test__spark-index-stat {
      flex-direction: column;
      text-align: center;
      padding: 20px 16px;
    }
    
    .spark-test__spark-index-icon {
      font-size: 36px;
    }
    
    .spark-test__spark-index-value {
      font-size: 32px;
    }
    
    .spark-test__spark-index-subtext {
      font-size: 13px;
    }
    
    .spark-test__leaderboard {
      padding: 20px 16px;
    }
    
    .spark-test__leaderboard-title {
      font-size: 18px;
      margin-bottom: 16px;
    }
    
    .spark-test__leaderboard-bars {
      gap: 14px;
    }
    
    .spark-test__model-bar-track {
      height: 26px;
    }
    
    .spark-test__model-bar-fill {
      font-size: 12px;
      padding: 0 10px;
    }
    
    .spark-test__model-name {
      font-size: 15px;
    }
    
    .spark-test__model-percentage {
      font-size: 16px;
    }
    
    .spark-test__model-bar-stats {
      font-size: 12px;
    }
    
    .spark-test__puzzle-accordion {
      margin: 0 auto 16px;
    }
    
    .spark-test__puzzle-accordion-button {
      padding: 24px 20px !important;
      font-size: 20px !important;
      flex-direction: row !important;
      gap: 12px !important;
      min-height: 70px !important;
    }
    
    .spark-test__puzzle-inner {
      padding: 20px;
    }
    
    .spark-test__card {
      padding: 25px 20px;
      border-radius: 16px;
    }
    
    .spark-test__card-clue {
      font-size: 20px !important;
      margin-bottom: 25px !important;
    }
    
    .spark-test__spark-accordion-button {
      padding: 16px 20px !important;
      font-size: 15px !important;
      word-break: break-word !important;
      white-space: normal !important;
    }
    
    .spark-test__spark-accordion-button span:first-child {
      word-break: break-word !important;
      white-space: normal !important;
    }
    
    .spark-test__ai-accordion-button {
      padding: 14px 18px !important;
      font-size: 14px !important;
    }
    
    .spark-test__ai-accordion {
      margin-bottom: 15px;
    }
    
    .spark-test__ai-accordion-button {
      padding: 14px 20px;
      font-size: 14px;
    }
    
    .spark-test__spark-chain {
      padding-right: 20px;
      flex-direction: column;
    }
    
    .spark-test__spark-part {
      padding: 16px 20px;
      font-size: 15px;
      max-width: 100%;
      white-space: pre-wrap;
      word-wrap: break-word;
    }
    
    .spark-test__spark-part--3,
    .spark-test__spark-part--final {
      font-size: 17px;
    }
    
    .spark-test__spark-connector {
      font-size: 24px;
      margin: 6px 0;
    }
    
    .spark-test__spark-accordion-button {
      padding: 14px 20px;
      font-size: 15px;
    }
    
    .spark-test__spark-accordion-content[aria-hidden="false"] {
      padding: 20px;
    }
    
    .spark-test__spark-section {
      padding: 20px;
      margin-top: 20px;
    }
    
    .spark-test__spark-text {
      font-size: 16px !important;
      padding-right: 0 !important;
      line-height: 1.2 !important;
    }
    
    .spark-test__spark-part {
      padding: 10px 14px !important;
      line-height: 1.2 !important;
    }
    
    .spark-test__ai-accordion-content {
      padding: 10px 16px !important;
      line-height: 1.2 !important;
    }
  }
  ';
  
  wp_register_style('hb-spark-test', false);
  wp_add_inline_style('hb-spark-test', $css);
  wp_enqueue_style('hb-spark-test');
});

/* ---------------------------------------------------------
 * 6) Shortcode: [hb_spark_test] - Based on working code, will add new features gradually
 * --------------------------------------------------------- */
add_shortcode('hb_spark_test', function($atts) {
  $atts = shortcode_atts([
    'score' => '42',
    'free_rows' => '3',
    'puzzle_id' => '',
    'id' => '', // Alias for puzzle_id
    'limit' => '-1',
  ], $atts);
  
  $score = intval($atts['score']);
  $limit = intval($atts['limit']);
  // If limit is set and not -1, use it as free_rows
  $free_rows = ($limit > 0) ? $limit : intval($atts['free_rows']);
  // Support both 'puzzle_id' and 'id' parameters
  $puzzle_id = !empty($atts['puzzle_id']) ? sanitize_text_field($atts['puzzle_id']) : sanitize_text_field($atts['id']);
  
  // Check PMPro membership
  $has_membership = false;
  if (function_exists('pmpro_hasMembershipLevel')) {
    $has_membership = pmpro_hasMembershipLevel();
  }
  
  // Query entries - SIMPLIFIED: Get ALL entries, group by puzzle_id
  $query_args = [
    'post_type' => 'lab_entry',
    'posts_per_page' => -1,
    'post_status' => 'publish',
    'orderby' => ['menu_order' => 'ASC', 'ID' => 'ASC'],
  ];
  
  $lab_entries = get_posts($query_args);
  
  if (empty($lab_entries)) {
    return '<div class="spark-test"><p style="text-align: center; padding: 40px; direction: rtl;">לא נמצאו רשומות מעבדה. אנא הוסף Lab Entries דרך תפריט הניהול.</p></div>';
  }
  
  // Group entries by puzzle_id
  $puzzles = [];
  $global_total_successes = 0;
  $global_o1_successes = 0;
  $global_claude_successes = 0;
  $global_gemini_successes = 0;
  $global_total_clues = 0;
  
  foreach ($lab_entries as $entry) {
    $entry_puzzle_id = get_post_meta($entry->ID, '_lab_entry_puzzle_id', true);
    if (empty($entry_puzzle_id)) {
      $entry_puzzle_id = 'unassigned';
    }
    
    // Check success checkboxes
    $o1_success = get_post_meta($entry->ID, '_o1_success', true) === '1';
    $claude_success = get_post_meta($entry->ID, '_claude_success', true) === '1';
    $gemini_success = get_post_meta($entry->ID, '_gemini_success', true) === '1';
    
    // Count global successes
    if ($o1_success) {
      $global_total_successes++;
      $global_o1_successes++;
    }
    if ($claude_success) {
      $global_total_successes++;
      $global_claude_successes++;
    }
    if ($gemini_success) {
      $global_total_successes++;
      $global_gemini_successes++;
    }
    $global_total_clues++;
    
    $failure_o1 = get_post_meta($entry->ID, '_lab_entry_failure_o1', true) ?: '';
    $failure_claude = get_post_meta($entry->ID, '_lab_entry_failure_claude', true) ?: '';
    $failure_gemini = get_post_meta($entry->ID, '_lab_entry_failure_gemini', true) ?: '';
    
    // Get 3-Part Modular Spark Solution
    $spark_part_1 = get_post_meta($entry->ID, '_lab_entry_spark_part_1', true) ?: '';
    $spark_part_2 = get_post_meta($entry->ID, '_lab_entry_spark_part_2', true) ?: '';
    $spark_part_3 = get_post_meta($entry->ID, '_lab_entry_spark_part_3', true) ?: '';
    
    // Legacy fields
    $spark_definition = get_post_meta($entry->ID, '_lab_entry_spark_definition', true) ?: '';
    $spark_indicator = get_post_meta($entry->ID, '_lab_entry_spark_indicator', true) ?: '';
    $spark_final = get_post_meta($entry->ID, '_lab_entry_spark_final', true) ?: '';
    $spark_solution_legacy = get_post_meta($entry->ID, '_lab_entry_spark_solution', true) ?: '';
    
    // Initialize puzzle if not exists
    if (!isset($puzzles[$entry_puzzle_id])) {
      $puzzles[$entry_puzzle_id] = [
        'id' => $entry_puzzle_id,
        'entries' => [],
        'stats' => [
          'total_clues' => 0,
          'o1_successes' => 0,
          'claude_successes' => 0,
          'gemini_successes' => 0,
          'total_successes' => 0,
        ],
      ];
    }
    
    // Add entry to puzzle
    $puzzles[$entry_puzzle_id]['entries'][] = [
      'clue' => get_the_title($entry->ID) ?: $entry->post_content,
      'failure_o1' => $failure_o1,
      'failure_claude' => $failure_claude,
      'failure_gemini' => $failure_gemini,
      'o1_success' => $o1_success,
      'claude_success' => $claude_success,
      'gemini_success' => $gemini_success,
      'spark_part_1' => $spark_part_1,
      'spark_part_2' => $spark_part_2,
      'spark_part_3' => $spark_part_3,
      'spark_definition' => $spark_definition,
      'spark_indicator' => $spark_indicator,
      'spark_final' => $spark_final,
      'spark_solution' => $spark_solution_legacy,
    ];
    
    // Update puzzle statistics
    $puzzles[$entry_puzzle_id]['stats']['total_clues']++;
    if ($o1_success) {
      $puzzles[$entry_puzzle_id]['stats']['o1_successes']++;
      $puzzles[$entry_puzzle_id]['stats']['total_successes']++;
    }
    if ($claude_success) {
      $puzzles[$entry_puzzle_id]['stats']['claude_successes']++;
      $puzzles[$entry_puzzle_id]['stats']['total_successes']++;
    }
    if ($gemini_success) {
      $puzzles[$entry_puzzle_id]['stats']['gemini_successes']++;
      $puzzles[$entry_puzzle_id]['stats']['total_successes']++;
    }
  }
  
  // Calculate GLOBAL statistics (across all puzzles)
  $global_total_attempts = $global_total_clues * 3;
  $global_ai_success_rate = $global_total_attempts > 0 ? round(($global_total_successes / $global_total_attempts) * 100, 2) : 0;
  $global_o1_success_rate = $global_total_clues > 0 ? round(($global_o1_successes / $global_total_clues) * 100, 2) : 0;
  $global_claude_success_rate = $global_total_clues > 0 ? round(($global_claude_successes / $global_total_clues) * 100, 2) : 0;
  $global_gemini_success_rate = $global_total_clues > 0 ? round(($global_gemini_successes / $global_total_clues) * 100, 2) : 0;
  $global_human_spark_index = round(100 - $global_ai_success_rate, 2);
  
  // Calculate statistics for each puzzle
  foreach ($puzzles as $puzzle_id_key => &$puzzle) {
    $puzzle_clues = $puzzle['stats']['total_clues'];
    $puzzle_attempts = $puzzle_clues * 3;
    $total_successes = $puzzle['stats']['total_successes'];
    
    $puzzle['stats']['ai_success_rate'] = $puzzle_attempts > 0 
      ? round(($total_successes / $puzzle_attempts) * 100, 2) 
      : 0;
    
    $puzzle['stats']['o1_success_rate'] = $puzzle_clues > 0 
      ? round(($puzzle['stats']['o1_successes'] / $puzzle_clues) * 100, 2) 
      : 0;
    $puzzle['stats']['claude_success_rate'] = $puzzle_clues > 0 
      ? round(($puzzle['stats']['claude_successes'] / $puzzle_clues) * 100, 2) 
      : 0;
    $puzzle['stats']['gemini_success_rate'] = $puzzle_clues > 0 
      ? round(($puzzle['stats']['gemini_successes'] / $puzzle_clues) * 100, 2) 
      : 0;
    
    $puzzle['stats']['human_spark_index'] = round(100 - $puzzle['stats']['ai_success_rate'], 2);
  }
  unset($puzzle);
  
  // Sort puzzles by ID numerically (descending - newest first)
  uksort($puzzles, function($a, $b) {
    $a_num = is_numeric($a) ? (int)$a : PHP_INT_MAX;
    $b_num = is_numeric($b) ? (int)$b : PHP_INT_MAX;
    if ($a_num !== PHP_INT_MAX || $b_num !== PHP_INT_MAX) {
      return $b_num <=> $a_num;
    }
    return strcmp($b, $a);
  });
  
  ob_start();
  ?>
  
  <div class="spark-test" id="spark-test-<?php echo uniqid('st-'); ?>">
    
    <!-- Header -->
    <div class="spark-test__header">
      <h1 class="spark-test__main-title">איפה האלגוריתם עוצר וההברקה מתחילה</h1>
      <p class="spark-test__subtitle">ניסוי מחשבתי הבוחן את גבולות ה-Reasoning של מודלי שפה מול חשיבה אנושית</p>
    </div>
    
    <!-- GLOBAL Statistics Section -->
    <div class="spark-test__stats-wrapper">
      <div class="spark-test__stats-container">
        <!-- Top Row: Gauge and Human Spark Index -->
        <div class="spark-test__stats-top-row">
          <!-- Human Spark Index (Left) -->
          <div class="spark-test__spark-index-stat">
            <div class="spark-test__spark-index-icon">💡</div>
            <div class="spark-test__spark-index-content">
              <div class="spark-test__spark-index-label">מדד הניצוץ האנושי</div>
              <div class="spark-test__spark-index-value"><?php echo esc_html($global_human_spark_index); ?>%</div>
              <div class="spark-test__spark-index-subtext">ערך מוסף של המוח האנושי מעל הממוצע של ה-AI</div>
            </div>
          </div>
          
          <!-- General AI Success Rate Gauge (Right) -->
          <div class="spark-test__gauge-wrapper">
            <div class="spark-test__gauge-container">
              <svg class="spark-test__gauge-svg" viewBox="0 0 200 120">
                <path class="spark-test__gauge-track" 
                      d="M 20 100 A 80 80 0 0 1 180 100" 
                      stroke-dasharray="251.33" />
                <path class="spark-test__gauge-fill" 
                      data-score="<?php echo esc_attr($global_ai_success_rate); ?>"
                      d="M 20 100 A 80 80 0 0 1 180 100"
                      stroke-dasharray="251.33" />
              </svg>
              <div class="spark-test__gauge-value"><?php echo esc_html($global_ai_success_rate); ?>%</div>
              <div class="spark-test__gauge-label">אחוז הצלחת המכונה (משוכלל)</div>
            </div>
          </div>
        </div>
        
        <!-- Model Leaderboard (Bottom, Full Width) -->
        <div class="spark-test__leaderboard">
          <h3 class="spark-test__leaderboard-title">דירוג המודלים (כללי)</h3>
          <div class="spark-test__leaderboard-bars">
            <div class="spark-test__model-bar">
              <div class="spark-test__model-bar-header">
                <span class="spark-test__model-name">ChatGPT</span>
                <span class="spark-test__model-percentage"><?php echo esc_html($global_o1_success_rate); ?>%</span>
              </div>
              <div class="spark-test__model-bar-track">
                <div class="spark-test__model-bar-fill spark-test__model-bar-fill--o1" 
                     style="width: <?php echo esc_attr($global_o1_success_rate); ?>%"
                     data-percentage="<?php echo esc_attr($global_o1_success_rate); ?>"></div>
              </div>
              <div class="spark-test__model-bar-stats"><?php echo esc_html($global_o1_successes); ?> / <?php echo esc_html($global_total_clues); ?> רמזים</div>
            </div>
            
            <div class="spark-test__model-bar">
              <div class="spark-test__model-bar-header">
                <span class="spark-test__model-name">Claude</span>
                <span class="spark-test__model-percentage"><?php echo esc_html($global_claude_success_rate); ?>%</span>
              </div>
              <div class="spark-test__model-bar-track">
                <div class="spark-test__model-bar-fill spark-test__model-bar-fill--claude" 
                     style="width: <?php echo esc_attr($global_claude_success_rate); ?>%"
                     data-percentage="<?php echo esc_attr($global_claude_success_rate); ?>"></div>
              </div>
              <div class="spark-test__model-bar-stats"><?php echo esc_html($global_claude_successes); ?> / <?php echo esc_html($global_total_clues); ?> רמזים</div>
            </div>
            
            <div class="spark-test__model-bar">
              <div class="spark-test__model-bar-header">
                <span class="spark-test__model-name">Gemini</span>
                <span class="spark-test__model-percentage"><?php echo esc_html($global_gemini_success_rate); ?>%</span>
              </div>
              <div class="spark-test__model-bar-track">
                <div class="spark-test__model-bar-fill spark-test__model-bar-fill--gemini" 
                     style="width: <?php echo esc_attr($global_gemini_success_rate); ?>%"
                     data-percentage="<?php echo esc_attr($global_gemini_success_rate); ?>"></div>
              </div>
              <div class="spark-test__model-bar-stats"><?php echo esc_html($global_gemini_successes); ?> / <?php echo esc_html($global_total_clues); ?> רמזים</div>
            </div>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Puzzles Section with Accordions -->
    <div class="spark-test__feed-wrapper">
      <h2 style="text-align: center; font-size: 28px; font-weight: 700; color: #1f2937; margin-bottom: 30px;">תשבצים</h2>
      
      <div class="spark-test__feed">
        <?php foreach ($puzzles as $puzzle_id_key => $puzzle): ?>
          <?php $puzzle_unique_id = 'puzzle-' . esc_attr($puzzle_id_key); ?>
          <div class="spark-test__puzzle-accordion">
            <button class="spark-test__puzzle-accordion-button" 
                    type="button" 
                    aria-expanded="false"
                    aria-controls="<?php echo $puzzle_unique_id; ?>">
              <span>תשבץ <?php echo esc_html($puzzle_id_key); ?></span>
              <span class="spark-test__puzzle-accordion-icon">▼</span>
            </button>
            <div class="spark-test__puzzle-accordion-content" 
                 id="<?php echo $puzzle_unique_id; ?>" 
                 aria-hidden="true">
              <div class="spark-test__puzzle-inner">
                <!-- Puzzle-specific Statistics -->
                <div style="background: #f9fafb; border-radius: 12px; padding: 20px; margin-bottom: 30px; display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px;">
                  <div>
                    <div style="font-size: 12px; color: #6b7280; margin-bottom: 4px;">אחוז הצלחה משוכלל:</div>
                    <div style="font-size: 20px; font-weight: 700; color: #1f2937;"><?php echo esc_html($puzzle['stats']['ai_success_rate']); ?>%</div>
                  </div>
                  <div>
                    <div style="font-size: 12px; color: #6b7280; margin-bottom: 4px;">ChatGPT:</div>
                    <div style="font-size: 20px; font-weight: 700; color: #1f2937;"><?php echo esc_html($puzzle['stats']['o1_success_rate']); ?>%</div>
                  </div>
                  <div>
                    <div style="font-size: 12px; color: #6b7280; margin-bottom: 4px;">Claude:</div>
                    <div style="font-size: 20px; font-weight: 700; color: #1f2937;"><?php echo esc_html($puzzle['stats']['claude_success_rate']); ?>%</div>
                  </div>
                  <div>
                    <div style="font-size: 12px; color: #6b7280; margin-bottom: 4px;">Gemini:</div>
                    <div style="font-size: 20px; font-weight: 700; color: #1f2937;"><?php echo esc_html($puzzle['stats']['gemini_success_rate']); ?>%</div>
                  </div>
                  <div>
                    <div style="font-size: 12px; color: #6b7280; margin-bottom: 4px;">סה"כ הגדרות:</div>
                    <div style="font-size: 20px; font-weight: 700; color: #1f2937;"><?php echo esc_html($puzzle['stats']['total_clues']); ?></div>
                  </div>
                </div>
                
                <!-- Puzzle Entries -->
                <div class="spark-test__feed">
                  <?php 
                  $puzzle_total_entries = count($puzzle['entries']);
                  $puzzle_has_locked = !$has_membership && $puzzle_total_entries > $free_rows;
                  $membership_url = function_exists('pmpro_url') ? pmpro_url('levels') : home_url('/membership-levels/');
                  ?>
                  
                  <?php foreach ($puzzle['entries'] as $index => $row): ?>
                    <?php 
                    $is_locked = !$has_membership && $index >= $free_rows; 
                    ?>
                    
                    <!-- Paywall Message - Show before first locked entry -->
                    <?php if ($is_locked && $index === $free_rows && $puzzle_has_locked): ?>
                      <div class="spark-test__paywall-message">
                        <span class="spark-test__paywall-message-icon">🔒</span>
                        <h3 class="spark-test__paywall-message-title">הגדרות נוספות זמינות למנויים בלבד</h3>
                        <p class="spark-test__paywall-message-text">כדי לצפות בכל ההגדרות של התשבץ, כולל הפתרונות המלאים של המודלים והסבר הפתרון האנושי, הצטרף למנוי.</p>
                        <a href="<?php echo esc_url($membership_url); ?>" class="spark-test__paywall-message-button">
                          הצטרף למנוי עכשיו
                        </a>
                      </div>
                    <?php endif; ?>
                    
                    <div class="spark-test__card <?php echo $is_locked ? 'spark-test__card--locked' : ''; ?>" data-index="<?php echo $index; ?>" data-locked="<?php echo $is_locked ? 'true' : 'false'; ?>">
                      <h2 class="spark-test__card-clue"><?php echo esc_html($row['clue']); ?></h2>
                  
                  <!-- Primary Accordion: Human Spark -->
                  <?php 
                  $has_new_spark = !empty($row['spark_part_1']) || !empty($row['spark_part_2']) || !empty($row['spark_part_3']);
                  $has_legacy_parts = !empty($row['spark_definition']) || !empty($row['spark_indicator']) || !empty($row['spark_final']);
                  $has_legacy_spark = !empty($row['spark_solution']);
                  $has_any_spark = $has_new_spark || $has_legacy_parts || $has_legacy_spark;
                  $unique_id = $puzzle_id_key . '-' . $index;
                  ?>
                  <?php if ($has_any_spark): ?>
                    <div class="spark-test__spark-accordion">
                      <button class="spark-test__spark-accordion-button" 
                              type="button" 
                              aria-expanded="false"
                              aria-controls="spark-accordion-<?php echo esc_attr($unique_id); ?>">
                        <span>💡 גלה את הניצוץ (הפתרון האנושי)</span>
                        <span class="spark-test__spark-accordion-icon">▼</span>
                      </button>
                      <div class="spark-test__spark-accordion-content" 
                           id="spark-accordion-<?php echo esc_attr($unique_id); ?>" 
                           aria-hidden="true">
                        <div class="spark-test__spark-section">
                          <?php if ($has_new_spark): ?>
                            <div class="spark-test__spark-chain">
                              <?php if (!empty($row['spark_part_1'])): ?>
                                <div class="spark-test__spark-part spark-test__spark-part--1">
                                  <div><?php echo nl2br(esc_html($row['spark_part_1'])); ?></div>
                                </div>
                              <?php endif; ?>
                              <?php if (!empty($row['spark_part_1']) && !empty($row['spark_part_2'])): ?>
                                <div class="spark-test__spark-connector">+</div>
                              <?php endif; ?>
                              <?php if (!empty($row['spark_part_2'])): ?>
                                <div class="spark-test__spark-part spark-test__spark-part--2">
                                  <div><?php echo nl2br(esc_html($row['spark_part_2'])); ?></div>
                                </div>
                              <?php endif; ?>
                              <?php if ((!empty($row['spark_part_1']) || !empty($row['spark_part_2'])) && !empty($row['spark_part_3'])): ?>
                                <div class="spark-test__spark-connector">=</div>
                              <?php endif; ?>
                              <?php if (!empty($row['spark_part_3'])): ?>
                                <div class="spark-test__spark-part spark-test__spark-part--3">
                                  <div><?php echo nl2br(esc_html($row['spark_part_3'])); ?></div>
                                </div>
                              <?php endif; ?>
                            </div>
                          <?php elseif ($has_legacy_parts): ?>
                            <div class="spark-test__spark-chain">
                              <?php if (!empty($row['spark_definition'])): ?>
                                <div class="spark-test__spark-part spark-test__spark-part--definition">
                                  <div style="font-weight: 600; margin-bottom: 8px;">הגדרה</div>
                                  <div><?php echo nl2br(esc_html($row['spark_definition'])); ?></div>
                                </div>
                              <?php endif; ?>
                              <?php if (!empty($row['spark_definition']) && (!empty($row['spark_indicator']) || !empty($row['spark_final']))): ?>
                                <div class="spark-test__spark-connector">+</div>
                              <?php endif; ?>
                              <?php if (!empty($row['spark_indicator'])): ?>
                                <div class="spark-test__spark-part spark-test__spark-part--indicator">
                                  <div style="font-weight: 600; margin-bottom: 8px;">מנגנון/אינדיקטור</div>
                                  <div><?php echo nl2br(esc_html($row['spark_indicator'])); ?></div>
                                </div>
                              <?php endif; ?>
                              <?php if (!empty($row['spark_indicator']) && !empty($row['spark_final'])): ?>
                                <div class="spark-test__spark-connector">=</div>
                              <?php endif; ?>
                              <?php if (!empty($row['spark_final'])): ?>
                                <div class="spark-test__spark-part spark-test__spark-part--final">
                                  <div style="font-weight: 600; margin-bottom: 8px;">תשובה סופית</div>
                                  <div><?php echo nl2br(esc_html($row['spark_final'])); ?></div>
                                </div>
                              <?php endif; ?>
                            </div>
                          <?php elseif ($has_legacy_spark): ?>
                            <p class="spark-test__spark-text"><?php echo nl2br(esc_html($row['spark_solution'])); ?></p>
                          <?php endif; ?>
                        </div>
                      </div>
                    </div>
                  <?php endif; ?>
                  
                  <!-- Secondary Accordions: AI Attempts -->
                  <?php if (!empty($row['failure_o1']) || !empty($row['failure_claude']) || !empty($row['failure_gemini'])): ?>
                    <div class="spark-test__ai-accordions">
                      <?php if (!empty($row['failure_o1'])): ?>
                        <div class="spark-test__ai-accordion">
                          <button class="spark-test__ai-accordion-button spark-test__ai-accordion-button--o1 <?php echo (isset($row['o1_success']) && $row['o1_success'] === true) ? 'spark-test__ai-accordion-button--success' : ''; ?>" 
                                  type="button" 
                                  aria-expanded="false"
                                  aria-controls="accordion-o1-<?php echo esc_attr($unique_id); ?>">
                            <span><?php echo (isset($row['o1_success']) && $row['o1_success'] === true) ? '✓ ' : ''; ?>צפה בניתוח ChatGPT</span>
                            <span class="spark-test__ai-accordion-icon">▼</span>
                          </button>
                          <div class="spark-test__ai-accordion-content" 
                               id="accordion-o1-<?php echo esc_attr($unique_id); ?>" 
                               aria-hidden="true">
                            <p class="spark-test__ai-text"><?php echo nl2br(esc_html($row['failure_o1'])); ?></p>
                          </div>
                        </div>
                      <?php endif; ?>
                      
                      <?php if (!empty($row['failure_claude'])): ?>
                        <div class="spark-test__ai-accordion">
                          <button class="spark-test__ai-accordion-button spark-test__ai-accordion-button--claude <?php echo (isset($row['claude_success']) && $row['claude_success'] === true) ? 'spark-test__ai-accordion-button--success' : ''; ?>" 
                                  type="button" 
                                  aria-expanded="false"
                                  aria-controls="accordion-claude-<?php echo esc_attr($unique_id); ?>">
                            <span><?php echo (isset($row['claude_success']) && $row['claude_success'] === true) ? '✓ ' : ''; ?>צפה בניתוח Claude</span>
                            <span class="spark-test__ai-accordion-icon">▼</span>
                          </button>
                          <div class="spark-test__ai-accordion-content" 
                               id="accordion-claude-<?php echo esc_attr($unique_id); ?>" 
                               aria-hidden="true">
                            <p class="spark-test__ai-text"><?php echo nl2br(esc_html($row['failure_claude'])); ?></p>
                          </div>
                        </div>
                      <?php endif; ?>
                      
                      <?php if (!empty($row['failure_gemini'])): ?>
                        <div class="spark-test__ai-accordion">
                          <button class="spark-test__ai-accordion-button spark-test__ai-accordion-button--gemini <?php echo (isset($row['gemini_success']) && $row['gemini_success'] === true) ? 'spark-test__ai-accordion-button--success' : ''; ?>" 
                                  type="button" 
                                  aria-expanded="false"
                                  aria-controls="accordion-gemini-<?php echo esc_attr($unique_id); ?>">
                            <span><?php echo (isset($row['gemini_success']) && $row['gemini_success'] === true) ? '✓ ' : ''; ?>צפה בניתוח Gemini</span>
                            <span class="spark-test__ai-accordion-icon">▼</span>
                          </button>
                          <div class="spark-test__ai-accordion-content" 
                               id="accordion-gemini-<?php echo esc_attr($unique_id); ?>" 
                               aria-hidden="true">
                            <p class="spark-test__ai-text"><?php echo nl2br(esc_html($row['failure_gemini'])); ?></p>
                          </div>
                        </div>
                      <?php endif; ?>
                    </div>
                  <?php endif; ?>
                    </div>
                  <?php endforeach; ?>
                </div>
                
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
    
    <!-- Methodology Footer -->
    <div class="spark-test__methodology-footer">
      <div class="spark-test__methodology-footer-header">
        <span class="spark-test__methodology-footer-icon">ℹ️</span>
        <h3 class="spark-test__methodology-footer-title">הבהרות ומתודולוגיה</h3>
      </div>
      <div class="spark-test__methodology-footer-content">
        <div class="spark-test__methodology-footer-section">
          <div class="spark-test__methodology-footer-section-title">אופי הבדיקה:</div>
          <p class="spark-test__methodology-footer-section-text">מובהר כי אין מדובר במחקר מדעי פורמלי או אקדמי, אלא בתצפית התנהגותית ובניסוי מחשבתי (Thought Experiment) הבוחן את גבולות ה-Reasoning של מודלי שפה מול חשיבה אנושית לטרלית.</p>
        </div>
        <div class="spark-test__methodology-footer-section">
          <div class="spark-test__methodology-footer-section-title">התאמה לשונית:</div>
          <p class="spark-test__methodology-footer-section-text">הפרומפט הותאם במיוחד למבנה התשבץ העברי, כולל הנחיה ברורה למודל לקרוא את מספר האותיות בסוגריים מימין לשמאל (כך שהספרה הימנית מייצגת את המילה הראשונה).</p>
        </div>
        <div class="spark-test__methodology-footer-section">
          <div class="spark-test__methodology-footer-section-title">סביבה סטרילית:</div>
          <p class="spark-test__methodology-footer-section-text">כל הבדיקות נערכו בחלונות צ'אט נקיים ('Zero-shot'), ללא חשיפה מוקדמת לפתרונות התשבץ או לקובץ התשובות המלא.</p>
        </div>
        <div class="spark-test__methodology-footer-section">
          <div class="spark-test__methodology-footer-section-title">כלי המדידה:</div>
          <p class="spark-test__methodology-footer-section-text">הבדיקה בוצעה מול הגרסאות המתקדמות ביותר (SOTA) של המודלים: ChatGPT, Claude 3.5 Sonnet ו-Gemini 1.5 Pro.</p>
        </div>
        <div class="spark-test__methodology-footer-section">
          <div class="spark-test__methodology-footer-section-title">זה הפרומפט שמשמש לבדיקה:</div>
          <div class="spark-test__methodology-prompt-accordion">
            <button class="spark-test__methodology-prompt-button" 
                    type="button" 
                    aria-expanded="false"
                    aria-controls="methodology-prompt-content">
              <span>לחץ כדי לצפות בפרומפט המלא</span>
              <span class="spark-test__methodology-prompt-icon">▼</span>
            </button>
            <div class="spark-test__methodology-prompt-content" 
                 id="methodology-prompt-content" 
                 aria-hidden="true">
<?php 
$prompt_text = '**"אתה מומחה לפתרון תשבצי היגיון בעברית. 
לפניך הגדרת היגיון אחת מתשבץ מקורי. 
הנחיות קריטיות למבנה הפתרון: המספרים בסוגריים בסוף ההגדרה מציינים את מספר האותיות בכל מילה, אך סדר המילים הוא מימין לשמאל: הספרה הימנית ביותר מייצגת את מספר האותיות במילה הראשונה של הפתרון. הספרה משמאלה (אם קיימת) מייצגת את מספר האותיות במילה השנייה, וכן הלאה. דוגמה: (3,4) פירושו שהמילה הראשונה בת 4 אותיות והשנייה בת 3 אותיות. עליך לפעול לפי השלבים הבאים: ניתוח מילולי: פרק את ההגדרה למרכיביה (מילים, ביטויים, רמיזות). חשיבה לוגית (Chain of Thought): הסבר את מסלולי החשיבה השונים (מילים נרדפות, אנגרמות, היפוכים, כפל משמעות). פתרון סופי: הצג את הפתרון המלא לפי סדר המילים הנכון. ההגדרה לפתרון: {כאן תופיע ההגדרה} : {כאן יופיעו כמות האותיות שנדרשות לפיתרון} חשוב: אל תנחש. אם אינך בטוח, הסבר איפה הלוגיקה שלך נעצרת."**';
echo nl2br(esc_html($prompt_text)); 
?>
            </div>
          </div>
        </div>
        <div class="spark-test__methodology-footer-section">
          <div class="spark-test__methodology-footer-section-title">דינמיות המודלים:</div>
          <p class="spark-test__methodology-footer-section-text">חשוב לציין שמודלי AI משתפרים ומתעדכנים תדיר. התוצאות המוצגות כאן משקפות את ביצועי המודל ברגע הבדיקה הספציפי.</p>
        </div>
      </div>
    </div>
    
  </div>
  
  <script>
  (function() {
    // Puzzle Accordions (Main)
    document.querySelectorAll('.spark-test__puzzle-accordion-button').forEach(function(button) {
      button.addEventListener('click', function() {
        const isExpanded = this.getAttribute('aria-expanded') === 'true';
        const content = document.getElementById(this.getAttribute('aria-controls'));
        
        this.setAttribute('aria-expanded', !isExpanded);
        if (content) {
          content.setAttribute('aria-hidden', isExpanded);
        }
      });
    });
    
    // Primary Accordion: Human Spark
    document.querySelectorAll('.spark-test__spark-accordion-button').forEach(function(button) {
      button.addEventListener('click', function() {
        const isExpanded = this.getAttribute('aria-expanded') === 'true';
        const content = document.getElementById(this.getAttribute('aria-controls'));
        
        this.setAttribute('aria-expanded', !isExpanded);
        if (content) {
          content.setAttribute('aria-hidden', isExpanded);
        }
      });
    });
    
    // Secondary Accordions: AI Attempts
    document.querySelectorAll('.spark-test__ai-accordion-button').forEach(function(button) {
      button.addEventListener('click', function() {
        const isExpanded = this.getAttribute('aria-expanded') === 'true';
        const content = document.getElementById(this.getAttribute('aria-controls'));
        
        const card = this.closest('.spark-test__card');
        if (card) {
          card.querySelectorAll('.spark-test__ai-accordion-button').forEach(function(btn) {
            if (btn !== button && btn.getAttribute('aria-expanded') === 'true') {
              btn.setAttribute('aria-expanded', 'false');
              const btnContent = document.getElementById(btn.getAttribute('aria-controls'));
              if (btnContent) {
                btnContent.setAttribute('aria-hidden', 'true');
              }
            }
          });
        }
        
        this.setAttribute('aria-expanded', !isExpanded);
        if (content) {
          content.setAttribute('aria-hidden', isExpanded);
        }
      });
    });
    
    // Methodology Prompt Accordion
    document.querySelectorAll('.spark-test__methodology-prompt-button').forEach(function(button) {
      button.addEventListener('click', function() {
        const isExpanded = this.getAttribute('aria-expanded') === 'true';
        const content = document.getElementById(this.getAttribute('aria-controls'));
        
        this.setAttribute('aria-expanded', !isExpanded);
        if (content) {
          content.setAttribute('aria-hidden', isExpanded);
        }
      });
    });
    
    // Gauge animation
    const observerOptions = {
      threshold: 0.3,
      rootMargin: '0px'
    };
    
    const observer = new IntersectionObserver(function(entries) {
      entries.forEach(function(entry) {
        if (entry.isIntersecting) {
          const gauge = entry.target.querySelector('.spark-test__gauge-fill');
          if (gauge && !gauge.classList.contains('animated')) {
            const score = parseInt(gauge.getAttribute('data-score')) || 0;
            const circumference = 251.33;
            const offset = circumference - (score / 100) * circumference;
            gauge.style.strokeDashoffset = offset;
            gauge.classList.add('animated');
          }
          
          entry.target.querySelectorAll('.spark-test__model-bar-fill').forEach(function(bar) {
            if (!bar.classList.contains('animated')) {
              const percentage = parseInt(bar.getAttribute('data-percentage')) || 0;
              bar.style.width = percentage + '%';
              bar.classList.add('animated');
            }
          });
        }
      });
    }, observerOptions);
    
    const sparkTest = document.querySelector('.spark-test');
    if (sparkTest) {
      observer.observe(sparkTest);
    }
  })();
  </script>
  
  <?php
  return ob_get_clean();
});
