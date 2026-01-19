# איך לבדוק למה ה-CSS לא נטען

## הבעיה
השינויים שעשיתי לא מופיעים באתר, למרות שניקית cache.

## מה לבדוק

### 1. בדיקה ראשונית - האם ה-CSS נטען בכלל?

1. פתח את העמוד `https://higayonbarie.co.il/user/` בדפדפן
2. לחץ `F12` כדי לפתוח את Developer Tools
3. לך לטאב **Elements** (או **Inspector**)
4. לחץ `Ctrl+F` (או `Cmd+F` ב-Mac) כדי לחפש
5. חפש: `hb-account-dashboard`
6. אם אתה מוצא את ה-class, זה אומר שה-HTML נטען
7. לחץ על האלמנט עם ה-class `hb-account-dashboard`
8. בצד ימין, תחת **Styles** (או **Computed**), בדוק אם יש CSS rules ל-`.hb-account-dashboard`

### 2. בדיקה - האם ה-CSS מוזרק ל-head?

1. בדפדפן, לחץ `Ctrl+U` (או `Cmd+Option+U` ב-Mac) כדי לראות את קוד המקור
2. לחץ `Ctrl+F` כדי לחפש
3. חפש: `HB_COG: style injected`
4. אם אתה מוצא את זה, זה אומר שה-CSS נטען
5. חפש גם: `Account Dashboard` או `hb-account-dashboard`
6. אם אתה מוצא את זה, זה אומר שה-CSS של האזור האישי נטען

### 3. בדיקה - האם יש CSS שמדרס את העיצוב?

1. בדפדפן, לחץ `F12` כדי לפתוח את Developer Tools
2. לך לטאב **Elements**
3. חפש אלמנט עם class `hb-account-dashboard`
4. לחץ עליו
5. בצד ימין, תחת **Styles**, בדוק:
   - האם יש CSS rules ל-`.hb-account-dashboard`?
   - האם יש CSS rules שמוצגות עם קו חוצה (strikethrough)? זה אומר שיש CSS אחר שמדרס אותן
   - האם יש CSS rules עם `!important` שמדרסות את העיצוב?

### 4. בדיקה - האם הפונקציה מזהה את השורטקוד?

1. פתח את `plugins/hb-cog-training/hb-cog-training.php`
2. מצא את הפונקציה `hb_cog_page_has_shortcodes()` (שורה 167)
3. הוסף debug code זמני:

```php
function hb_cog_page_has_shortcodes() {
  if (!is_singular()) return false;

  $post_id = get_queried_object_id();
  if (!$post_id) return false;

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

  // DEBUG - הוסף את זה זמנית
  if ($post_id && get_post_field('post_name', $post_id) === 'user') {
    error_log('DEBUG: Post ID: ' . $post_id);
    error_log('DEBUG: Content: ' . substr($content, 0, 200));
    error_log('DEBUG: Has hb_account_dashboard: ' . (strpos($content, '[hb_account_dashboard') !== false ? 'YES' : 'NO'));
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
```

4. בדוק את ה-error log של WordPress (בדרך כלל ב-`wp-content/debug.log`)

### 5. פתרון מהיר - להכריח טעינת CSS

אם כלום לא עובד, אפשר להוסיף CSS ישירות ב-`wp_head` עם priority גבוה:

```php
add_action('wp_head', function() {
  // רק לעמוד user
  if (is_page('user') || (is_singular() && get_post_field('post_name', get_queried_object_id()) === 'user')) {
    // הוסף כאן את כל ה-CSS של האזור האישי
  }
}, 99999);
```

## מה לעשות עכשיו

1. **לבדוק בדפדפן** - האם ה-CSS נטען? (לפי ההוראות למעלה)
2. **לבדוק ב-Elementor** - האם יש Custom CSS שמדרס את העיצוב?
3. **לבדוק cache** - האם יש עוד cache plugins שצריך לנקות?

## לגבי עמוד "התשבצים שלי"

**מה שיניתי:**
- ✅ רק את ה-template (`template-user_crossword.php`) - הוספתי כותרת מעוצבת
- ✅ רק את ה-CSS (`style.css`) - עדכנתי את עיצוב הכרטיסים והכפתורים

**מה לא נגעתי:**
- ✅ לא נגעתי בלוגיקה של התשבצים
- ✅ לא נגעתי ב-query של התשבצים
- ✅ לא נגעתי ב-display של התשבצים
- ✅ לא נגעתי ב-JavaScript של התשבצים

**זה בטוח לחלוטין** - רק עיצוב, לא פונקציונליות.
