# 🚀 יצירת פיצ'רים חדשים - מדריך מעשי

## 📋 מבוא

מדריך זה מסביר איך ליצור פיצ'ר חדש בפרויקט, שלב אחר שלב.

---

## 🎯 תהליך עבודה - 5 שלבים

### שלב 1: תכנון (Planning)

**לפני שתכתוב קוד, תכנן:**

1. **מה הפיצ'ר עושה?**
   - כתוב תיאור קצר
   - מה המטרה?
   - מי המשתמשים?

2. **איך זה יעבוד?**
   - מה המשתמש רואה?
   - מה המשתמש עושה?
   - מה קורה מאחורי הקלעים?

3. **מה צריך?**
   - פלאגין חדש?
   - שינוי ב-Theme?
   - קבצים נוספים? (JSON, CSS, JS)

**דוגמה:**
```
פיצ'ר: מבחן IQ
תיאור: מבחן IQ אונליין עם שאלות לוגיקה, מתמטיקה, מרחב
איך: משתמש עונה על שאלות, מקבל ציון בסוף
צריך: פלאגין חדש + קבצי שאלות (JSON)
```

### שלב 2: יצירת מבנה (Structure)

**צור את המבנה הבסיסי:**

1. **צור תיקיית פלאגין:**
   ```
   plugins/your-feature-name/
   ```

2. **צור קובץ ראשי:**
   ```
   plugins/your-feature-name/your-feature-name.php
   ```

3. **צור קבצים נוספים (אם צריך):**
   - `data.json` - נתונים
   - `style.css` - עיצוב (או inline)
   - `script.js` - JavaScript (או inline)

### שלב 3: כתיבת קוד (Coding)

**השתמש בתבנית בסיסית:**

```php
<?php
/**
 * Plugin Name: Your Feature Name
 * Description: תיאור קצר של הפיצ'ר
 * Version: 1.0.0
 * Author: Your Name
 */

if (!defined('ABSPATH')) exit;

// 1. טעינת נכסים (CSS/JS)
add_action('wp_enqueue_scripts', function () {
  // CSS/JS כאן
});

// 2. Shortcode
add_shortcode('your_shortcode', function ($atts = []) {
  // קוד הפיצ'ר כאן
  return $output;
});

// 3. AJAX handlers (אם צריך)
add_action('wp_ajax_your_action', 'your_handler_function');
```

### שלב 4: בדיקה (Testing)

**בדוק את הפיצ'ר:**

1. **בדוק syntax:**
   - אין שגיאות PHP
   - אין שגיאות JavaScript
   - אין שגיאות CSS

2. **בדוק פונקציונליות:**
   - הכל עובד?
   - אין באגים?
   - נראה טוב?

3. **בדוק תאימות:**
   - עובד בדפדפנים שונים?
   - עובד במובייל?
   - עובד עם פלאגינים אחרים?

### שלב 5: תיעוד (Documentation)

**תעד את הפיצ'ר:**

1. **הוסף הערות בקוד:**
   ```php
   // מה הפונקציה עושה
   function your_function() {
     // איך זה עובד
   }
   ```

2. **עדכן PLUGINS.md:**
   - הוסף את הפלאגין החדש לרשימה

3. **עדכן CHANGELOG.md:**
   - כתוב מה נוסף

---

## 📝 תבנית פלאגין מלאה

### תבנית בסיסית

```php
<?php
/**
 * Plugin Name: Your Feature Name
 * Description: תיאור קצר
 * Version: 1.0.0
 * Author: Your Name
 */

if (!defined('ABSPATH')) exit;

// ===== 1. טעינת נכסים =====
add_action('wp_enqueue_scripts', function () {
  // CSS
  $css = '/* CSS שלך כאן */';
  wp_register_style('your-feature-style', false);
  wp_add_inline_style('your-feature-style', $css);
  wp_enqueue_style('your-feature-style');
  
  // JavaScript
  wp_register_script('your-feature-script', '', [], false, true);
  wp_enqueue_script('your-feature-script');
  
  // AJAX
  wp_localize_script('your-feature-script', 'your_feature_vars', [
    'ajaxurl' => admin_url('admin-ajax.php'),
    'nonce' => wp_create_nonce('your_feature_nonce')
  ]);
});

// ===== 2. Shortcode =====
add_shortcode('your_shortcode', function ($atts = []) {
  $a = shortcode_atts([
    'param1' => 'default1',
    'param2' => 'default2',
  ], $atts, 'your_shortcode');
  
  ob_start();
  ?>
  <div class="your-feature-container">
    <!-- HTML שלך כאן -->
  </div>
  <script>
    // JavaScript שלך כאן
  </script>
  <?php
  return ob_get_clean();
});

// ===== 3. AJAX Handlers (אם צריך) =====
add_action('wp_ajax_your_action', function () {
  check_ajax_referer('your_feature_nonce', 'nonce');
  // קוד AJAX כאן
  wp_send_json_success(['data' => 'result']);
});
```

---

## 🎨 דוגמאות מהפרויקט

### דוגמה 1: פלאגין פשוט (goodlife-cipher)

```php
// מבנה פשוט עם shortcode
add_shortcode('cipher', function ($atts = []) {
  // טעינת נתונים מ-JSON
  // הצגת המשחק
  // JavaScript למשחק
});
```

### דוגמה 2: פלאגין עם AJAX (goodlife-wordle)

```php
// Shortcode + AJAX handlers
add_shortcode('wordle', ...);
add_action('wp_ajax_check_word', ...);
```

### דוגמה 3: פלאגין עם Meta Boxes (hb-logic-sheet)

```php
// Meta box להזנת נתונים
add_action('add_meta_boxes', ...);
add_action('save_post', ...);
```

---

## 🔧 כלים שימושיים

### 1. טעינת נתונים מ-JSON

```php
$uploads = wp_upload_dir();
$json_path = trailingslashit($uploads['basedir']) . 'your-data.json';
if (file_exists($json_path)) {
  $data = json_decode(file_get_contents($json_path), true);
}
```

### 2. Shortcode עם פרמטרים

```php
add_shortcode('your_shortcode', function ($atts = []) {
  $a = shortcode_atts([
    'mode' => 'daily',
    'difficulty' => 'easy',
  ], $atts);
  
  // השתמש ב-$a['mode'], $a['difficulty']
});
```

### 3. AJAX Handler

```php
add_action('wp_ajax_your_action', function () {
  check_ajax_referer('your_nonce', 'nonce');
  
  $result = do_something();
  
  wp_send_json_success(['data' => $result]);
});
```

### 4. CSS Inline

```php
$css = <<<'CSS'
.your-class {
  /* CSS כאן */
}
CSS;
wp_add_inline_style('your-style', $css);
```

---

## ✅ Checklist לפני סיום

- [ ] הפיצ'ר עובד
- [ ] אין שגיאות syntax
- [ ] עובד בדפדפנים שונים
- [ ] עובד במובייל
- [ ] יש הערות בקוד
- [ ] עודכן PLUGINS.md
- [ ] עודכן CHANGELOG.md

---

## 📚 משאבים

- [WordPress Shortcode API](https://developer.wordpress.org/reference/functions/add_shortcode/)
- [WordPress AJAX](https://developer.wordpress.org/plugins/javascript/ajax/)
- [WordPress Enqueue Scripts](https://developer.wordpress.org/reference/functions/wp_enqueue_script/)

---

**עודכן:** 2025-01-27
















