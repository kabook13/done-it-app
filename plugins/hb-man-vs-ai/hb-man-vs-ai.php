<?php
/**
 * Plugin Name: HB Man vs AI
 * Description: מעבדת הניצוץ: אדם נגד מכונה - Spark Lab Interface
 * Version: 2.1.0
 * Author: Higayon Barie
 */

if (!defined('ABSPATH')) exit;

add_shortcode('hb_man_vs_ai', function($atts = []) {
  $hb_build = gmdate('Ymd-His');
  
  wp_enqueue_style(
    'hb-manvsai-assistant',
    'https://fonts.googleapis.com/css2?family=Assistant:wght@400;600;700;800&family=Heebo:wght@400;600;700;800&display=swap',
    [],
    null
  );
  
  ob_start();
  ?>
  <main class="hb-manvsai-lab" data-build="<?php echo esc_attr($hb_build); ?>">
    <?php echo hb_manvsai_get_css($hb_build); ?>
    <?php echo hb_manvsai_get_html(); ?>
    <?php echo hb_manvsai_get_js(); ?>
  </main>
  <?php
  return ob_get_clean();
});

function hb_manvsai_get_css($hb_build) {
  ob_start();
  ?>
  <style>
  /* hb_manvsai lab build: <?php echo esc_html($hb_build); ?> */
  
  .hb-manvsai-lab {
    --hb-bg: #ffffff;
    --hb-text: #1a1a1a;
    --hb-text-muted: #4a4a4a;
    --hb-accent: #FFBF00;
    --hb-border: #e5e5e5;
    --hb-border-light: #f5f5f5;
    --hb-radius: 8px;
    --hb-font: "Assistant", "Heebo", system-ui, -apple-system, "Segoe UI", Roboto, "Noto Sans Hebrew", Arial, sans-serif;
    
    font-family: var(--hb-font);
    direction: rtl;
    width: 100%;
    max-width: 900px;
    margin: 0 auto;
    padding: 0 20px;
    background: var(--hb-bg);
    color: var(--hb-text);
    line-height: 1.7;
  }
  
  /* Header */
  .hb-manvsai-lab-header {
    padding: clamp(60px, 10vw, 100px) 0 clamp(40px, 6vw, 60px);
    text-align: center;
  }
  
  .hb-manvsai-lab-title {
    font-size: clamp(32px, 5vw, 42px);
    font-weight: 700;
    color: var(--hb-text);
    margin: 0 0 24px 0;
    letter-spacing: -0.5px;
    line-height: 1.5;
  }
  
  .hb-manvsai-lab-title-underline {
    display: block;
    width: 120px;
    height: 2px;
    background: var(--hb-accent);
    margin: 0 auto;
  }
  
  .hb-title-break {
    display: inline;
  }
  
  /* Model Selection */
  .hb-manvsai-models {
    padding: 0 0 clamp(48px, 6vw, 64px);
  }
  
  .hb-manvsai-models-title {
    font-size: 16px;
    font-weight: 600;
    color: var(--hb-text);
    margin: 0 0 20px 0;
    text-align: right;
  }
  
  .hb-manvsai-models-list {
    display: flex;
    flex-direction: column;
    gap: 16px;
    max-width: 400px;
    margin: 0 auto;
  }
  
  .hb-manvsai-model-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 16px 20px;
    background: var(--hb-bg);
    border: 1px solid var(--hb-border);
    border-radius: var(--hb-radius);
    cursor: pointer;
    transition: all 0.2s ease;
  }
  
  .hb-manvsai-model-item:hover {
    border-color: var(--hb-accent);
    background: var(--hb-border-light);
  }
  
  .hb-manvsai-model-checkbox {
    width: 20px;
    height: 20px;
    border: 2px solid var(--hb-border);
    border-radius: 4px;
    position: relative;
    flex-shrink: 0;
    cursor: pointer;
    transition: all 0.2s ease;
  }
  
  .hb-manvsai-model-checkbox input {
    position: absolute;
    opacity: 0;
    cursor: pointer;
    width: 100%;
    height: 100%;
    margin: 0;
  }
  
  .hb-manvsai-model-checkbox input:checked + .hb-manvsai-checkbox-mark {
    display: block;
  }
  
  .hb-manvsai-model-checkbox input:checked {
    border-color: var(--hb-accent);
    background: var(--hb-accent);
  }
  
  .hb-manvsai-checkbox-mark {
    display: none;
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 12px;
    height: 12px;
    background: var(--hb-bg);
    border-radius: 2px;
  }
  
  .hb-manvsai-model-checkbox:has(input:checked) {
    border-color: var(--hb-accent);
    background: var(--hb-accent);
  }
  
  .hb-manvsai-model-label {
    font-size: 15px;
    font-weight: 500;
    color: var(--hb-text);
    cursor: pointer;
    flex: 1;
  }
  
  /* Form Section */
  .hb-manvsai-form-section {
    padding: 0 0 clamp(48px, 6vw, 64px);
  }
  
  .hb-manvsai-form {
    display: grid;
    gap: 28px;
    max-width: 600px;
    margin: 0 auto;
  }
  
  .hb-manvsai-form-group {
    display: flex;
    flex-direction: column;
    gap: 10px;
  }
  
  .hb-manvsai-form-label {
    font-size: 14px;
    font-weight: 600;
    color: var(--hb-text);
    text-align: right;
  }
  
  .hb-manvsai-form-input {
    padding: 14px 18px;
    border: 1px solid var(--hb-border);
    border-radius: var(--hb-radius);
    font-family: var(--hb-font);
    font-size: 15px;
    background: var(--hb-bg);
    color: var(--hb-text);
    transition: all 0.2s ease;
    width: 100%;
    box-sizing: border-box;
  }
  
  .hb-manvsai-form-input:focus {
    outline: none;
    border-color: var(--hb-accent);
    box-shadow: 0 0 0 3px rgba(255,191,0,0.1);
  }
  
  .hb-manvsai-form-input::placeholder {
    color: var(--hb-text-muted);
    opacity: 0.6;
  }
  
  .hb-manvsai-form-actions {
    display: flex;
    justify-content: center;
    margin-top: 8px;
  }
  
  .hb-manvsai-btn {
    padding: 16px 40px;
    border: none;
    border-radius: var(--hb-radius);
    font-family: var(--hb-font);
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
    background: var(--hb-accent);
    color: var(--hb-text);
    min-width: 200px;
  }
  
  .hb-manvsai-btn:hover:not(:disabled) {
    background: #e6a800;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(255,191,0,0.2);
  }
  
  .hb-manvsai-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
  }
  
  /* User Status */
  .hb-manvsai-user-status {
    padding: clamp(32px, 5vw, 48px) 0;
    text-align: center;
    border-top: 1px solid var(--hb-border-light);
  }
  
  .hb-manvsai-status-text {
    font-size: 13px;
    color: var(--hb-text-muted);
    margin: 0 0 12px 0;
  }
  
  .hb-manvsai-status-link {
    font-size: 14px;
    color: var(--hb-accent);
    text-decoration: none;
    font-weight: 600;
    transition: color 0.2s ease;
  }
  
  .hb-manvsai-status-link:hover {
    color: #e6a800;
    text-decoration: underline;
  }
  
  /* Processing */
  .hb-manvsai-processing {
    display: none;
    text-align: center;
    padding: 24px;
    color: var(--hb-text-muted);
    font-size: 14px;
  }
  
  .hb-manvsai-processing.active {
    display: block;
  }
  
  .hb-manvsai-spinner {
    display: inline-block;
    width: 16px;
    height: 16px;
    border: 2px solid var(--hb-border);
    border-radius: 50%;
    border-top-color: var(--hb-accent);
    animation: spin 0.8s linear infinite;
    margin-left: 8px;
    vertical-align: middle;
  }
  
  @keyframes spin {
    to { transform: rotate(360deg); }
  }
  
  /* Responsive */
  @media (max-width: 768px) {
    .hb-manvsai-lab {
      padding: 0 16px;
    }
    
    .hb-manvsai-lab-title {
      line-height: 1.8;
      margin: 0 0 20px 0;
      padding: 0 10px;
      display: block;
    }
    
    .hb-title-break {
      display: block;
      margin-top: 16px;
      padding-top: 4px;
      line-height: 1.5;
    }
    
    .hb-manvsai-models-list {
      max-width: 100%;
    }
    
    .hb-manvsai-form {
      max-width: 100%;
    }
    
    .hb-manvsai-btn {
      width: 100%;
      min-width: auto;
    }
  }
  
  </style>
  <?php
  return ob_get_clean();
}

function hb_manvsai_get_html() {
  ob_start();
  ?>
  <!-- Header -->
  <section class="hb-manvsai-lab-header">
    <h1 class="hb-manvsai-lab-title">מעבדת הניצוץ:<span class="hb-title-break"> אדם נגד מכונה</span></h1>
    <span class="hb-manvsai-lab-title-underline"></span>
  </section>
  
  <!-- Model Selection -->
  <section class="hb-manvsai-models">
    <h2 class="hb-manvsai-models-title">בחר מודלים לבדיקה</h2>
    <div class="hb-manvsai-models-list">
      <label class="hb-manvsai-model-item">
        <div class="hb-manvsai-model-checkbox">
          <input type="checkbox" name="models[]" value="chatgpt" checked>
          <span class="hb-manvsai-checkbox-mark"></span>
        </div>
        <span class="hb-manvsai-model-label">ChatGPT</span>
      </label>
      <label class="hb-manvsai-model-item">
        <div class="hb-manvsai-model-checkbox">
          <input type="checkbox" name="models[]" value="gemini" checked>
          <span class="hb-manvsai-checkbox-mark"></span>
        </div>
        <span class="hb-manvsai-model-label">Gemini</span>
      </label>
      <label class="hb-manvsai-model-item">
        <div class="hb-manvsai-model-checkbox">
          <input type="checkbox" name="models[]" value="claude" checked>
          <span class="hb-manvsai-checkbox-mark"></span>
        </div>
        <span class="hb-manvsai-model-label">Claude</span>
      </label>
    </div>
  </section>
  
  <!-- Form Section -->
  <section class="hb-manvsai-form-section">
    <form class="hb-manvsai-form" id="hb-manvsai-lab-form">
      <div class="hb-manvsai-form-group">
        <label class="hb-manvsai-form-label" for="clue-text">הגדרה</label>
        <input 
          type="text" 
          id="clue-text" 
          name="clue_text" 
          class="hb-manvsai-form-input" 
          placeholder="הזן את טקסט ההגדרה"
          required
        />
      </div>
      
      <div class="hb-manvsai-form-group">
        <label class="hb-manvsai-form-label" for="num-letters">מספר אותיות</label>
        <input 
          type="number" 
          id="num-letters" 
          name="num_letters" 
          class="hb-manvsai-form-input" 
          placeholder="מספר האותיות בפתרון"
          min="1"
          required
        />
      </div>
      
      <div class="hb-manvsai-form-group">
        <label class="hb-manvsai-form-label" for="solution">פתרון</label>
        <input 
          type="text" 
          id="solution" 
          name="solution" 
          class="hb-manvsai-form-input" 
          placeholder="הזן את הפתרון (לצורך השוואה)"
          required
        />
      </div>
      
      <div class="hb-manvsai-form-actions">
        <button type="submit" class="hb-manvsai-btn" id="run-test-btn">
          הרץ בדיקת מעבדה
        </button>
      </div>
      
      <div class="hb-manvsai-processing" id="processing-message">
        <span class="hb-manvsai-spinner"></span>
        <span>מעבד...</span>
      </div>
    </form>
  </section>
  
  <!-- User Status -->
  <section class="hb-manvsai-user-status">
    <p class="hb-manvsai-status-text">סטטוס: אורח (נותרו 3 בדיקות חינם)</p>
    <a href="<?php echo esc_url(wp_login_url()); ?>" class="hb-manvsai-status-link">
      להיסטוריית הבדיקות שלך - התחבר
    </a>
  </section>
  
  <?php
  return ob_get_clean();
}

function hb_manvsai_get_js() {
  ob_start();
  ?>
  <script>
  (function() {
    'use strict';
    
    const form = document.getElementById('hb-manvsai-lab-form');
    const runBtn = document.getElementById('run-test-btn');
    const processingMsg = document.getElementById('processing-message');
    
    if (form && runBtn && processingMsg) {
      form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Get selected models
        const selectedModels = Array.from(form.querySelectorAll('input[name="models[]"]:checked'))
          .map(cb => cb.value);
        
        if (selectedModels.length === 0) {
          alert('אנא בחר לפחות מודל אחד לבדיקה');
          return;
        }
        
        // Show processing
        runBtn.disabled = true;
        processingMsg.classList.add('active');
        
        // Simulate processing (replace with actual API call later)
        setTimeout(function() {
          processingMsg.classList.remove('active');
          runBtn.disabled = false;
          alert('פונקציונליות הבדיקה תתווסף בקרוב. כרגע זהו ממשק ויזואלי בלבד.');
        }, 2000);
      });
    }
    
    // Update checkbox visual state
    const checkboxes = document.querySelectorAll('.hb-manvsai-model-checkbox input');
    checkboxes.forEach(function(cb) {
      cb.addEventListener('change', function() {
        const checkbox = this.closest('.hb-manvsai-model-checkbox');
        if (this.checked) {
          checkbox.style.borderColor = 'var(--hb-accent)';
          checkbox.style.background = 'var(--hb-accent)';
        } else {
          checkbox.style.borderColor = 'var(--hb-border)';
          checkbox.style.background = 'transparent';
        }
      });
    });
    
  })();
  </script>
  <?php
  return ob_get_clean();
}

/**
 * Gateway Page Shortcode: [hb_spark_gateway]
 */
add_shortcode('hb_spark_gateway', function($atts = []) {
  $atts = shortcode_atts([
    'research_url' => '#',
    'lab_url' => '#',
  ], $atts);
  
  $hb_build = gmdate('Ymd-His');
  
  wp_enqueue_style(
    'hb-spark-gateway-heebo',
    'https://fonts.googleapis.com/css2?family=Heebo:wght@400;600;700;800&display=swap',
    [],
    null
  );
  
  ob_start();
  ?>
  <main class="hb-spark-gateway" data-build="<?php echo esc_attr($hb_build); ?>">
    <?php echo hb_spark_gateway_get_css($hb_build); ?>
    <?php echo hb_spark_gateway_get_html($atts); ?>
  </main>
  <?php
  return ob_get_clean();
});

function hb_spark_gateway_get_css($hb_build) {
  ob_start();
  ?>
  <style>
  /* hb_spark_gateway build: <?php echo esc_html($hb_build); ?> */
  
  .hb-spark-gateway {
    --hb-bg: #ffffff;
    --hb-text: #2c3e50;
    --hb-text-muted: #5a6c7d;
    --hb-accent: #FFBF00;
    --hb-border: #e5e7eb;
    --hb-shadow: 0 2px 8px rgba(0,0,0,.06);
    --hb-shadow-hover: 0 4px 16px rgba(0,0,0,.1);
    --hb-radius: 12px;
    --hb-font: "Heebo", system-ui, -apple-system, "Segoe UI", Roboto, "Noto Sans Hebrew", Arial, sans-serif;
    
    font-family: var(--hb-font);
    direction: rtl;
    width: 100%;
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 20px;
    background: var(--hb-bg);
    color: var(--hb-text);
    line-height: 1.7;
  }
  
  /* Header */
  .hb-spark-gateway-header {
    padding: clamp(80px, 12vw, 120px) 0 clamp(60px, 8vw, 80px);
    text-align: center;
  }
  
  .hb-spark-gateway-header h1 {
    font-size: clamp(36px, 6vw, 52px);
    font-weight: 800;
    color: var(--hb-text);
    margin: 0 0 20px 0;
    line-height: 1.3;
    letter-spacing: -1px;
  }
  
  .hb-spark-gateway-header p {
    font-size: clamp(18px, 2.5vw, 22px);
    color: var(--hb-text-muted);
    margin: 0;
    max-width: 700px;
    margin-left: auto;
    margin-right: auto;
    line-height: 1.6;
  }
  
  /* Two Paths Cards */
  .hb-spark-gateway-paths {
    padding: 0 0 clamp(80px, 10vw, 120px);
    display: flex;
    gap: 32px;
    align-items: stretch;
  }
  
  .hb-spark-gateway-card {
    flex: 1;
    background: var(--hb-bg);
    border: 1px solid var(--hb-border);
    border-radius: var(--hb-radius);
    padding: clamp(40px, 5vw, 56px);
    box-shadow: var(--hb-shadow);
    transition: all 0.3s ease;
    display: flex;
    flex-direction: column;
    position: relative;
    overflow: hidden;
  }
  
  .hb-spark-gateway-card:before {
    content: "";
    position: absolute;
    top: 0;
    right: 0;
    width: 4px;
    height: 100%;
    background: var(--hb-accent);
    opacity: 0;
    transition: opacity 0.3s ease;
  }
  
  .hb-spark-gateway-card:hover {
    transform: translateY(-4px);
    box-shadow: var(--hb-shadow-hover);
    border-color: var(--hb-accent);
  }
  
  .hb-spark-gateway-card:hover:before {
    opacity: 1;
  }
  
  .hb-spark-gateway-card-title {
    font-size: clamp(24px, 3.5vw, 32px);
    font-weight: 700;
    color: var(--hb-text);
    margin: 0 0 16px 0;
    line-height: 1.3;
  }
  
  .hb-spark-gateway-card-content {
    font-size: clamp(16px, 2vw, 18px);
    color: var(--hb-text-muted);
    margin: 0 0 32px 0;
    line-height: 1.7;
    flex: 1;
  }
  
  .hb-spark-gateway-card-btn {
    display: inline-block;
    padding: 14px 32px;
    background: var(--hb-accent);
    color: var(--hb-text);
    border: none;
    border-radius: 8px;
    font-family: var(--hb-font);
    font-size: 16px;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.2s ease;
    align-self: flex-start;
    margin-top: auto;
  }
  
  .hb-spark-gateway-card-btn:hover {
    background: #e6a800;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(255,191,0,0.25);
  }
  
  /* Footer */
  .hb-spark-gateway-footer {
    padding: clamp(40px, 6vw, 60px) 0;
    text-align: center;
    border-top: 1px solid var(--hb-border);
  }
  
  .hb-spark-gateway-footer p {
    font-size: 14px;
    color: var(--hb-text-muted);
    margin: 0;
  }
  
  /* Responsive */
  @media (max-width: 768px) {
    .hb-spark-gateway {
      padding: 0 16px;
    }
    
    .hb-spark-gateway-paths {
      flex-direction: column;
      gap: 24px;
    }
    
    .hb-spark-gateway-card {
      padding: 32px 24px;
    }
    
    .hb-spark-gateway-card-btn {
      width: 100%;
      text-align: center;
    }
  }
  
  </style>
  <?php
  return ob_get_clean();
}

function hb_spark_gateway_get_html($atts) {
  ob_start();
  ?>
  <!-- Header -->
  <section class="hb-spark-gateway-header">
    <h1>פרויקט הניצוץ: המדד האנושי בעידן ה-AI</h1>
    <p>חוקרים ומאתגרים את גבולות הבינה המלאכותית דרך עולם תשבצי ההיגיון.</p>
  </section>
  
  <!-- Two Paths -->
  <section class="hb-spark-gateway-paths">
    <!-- Card 1: The Research -->
    <div class="hb-spark-gateway-card">
      <h2 class="hb-spark-gateway-card-title">המעבדה האנליטית</h2>
      <p class="hb-spark-gateway-card-content">איך מודלי השפה הגדולים בעולם התמודדו עם תשבץ היגיון מלא? צלילה לנתונים של תשבץ 183.</p>
      <a href="<?php echo esc_url($atts['research_url']); ?>" class="hb-spark-gateway-card-btn">לצפייה במחקר</a>
    </div>
    
    <!-- Card 2: The Tool -->
    <div class="hb-spark-gateway-card">
      <h2 class="hb-spark-gateway-card-title">בדיקת חסינות AI</h2>
      <p class="hb-spark-gateway-card-content">כתבתם הגדרה מבריקה? בואו לבדוק אם ChatGPT או Gemini מסוגלים לפצח אותה.</p>
      <a href="<?php echo esc_url($atts['lab_url']); ?>" class="hb-spark-gateway-card-btn">למעבדת הבדיקה</a>
    </div>
  </section>
  
  <!-- Footer -->
  <footer class="hb-spark-gateway-footer">
    <p>בקרוב: מדד תשבצי העיתונים וכלים למחברים מקצועיים.</p>
  </footer>
  
  <?php
  return ob_get_clean();
}
