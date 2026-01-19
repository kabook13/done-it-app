# בעיית dynamic_asset - תקציר למען ייעוץ

## הבעיה

WordPress plugin (hb-cog-training) משתמש ב-ES6 modules (`type="module"`), אבל כל ה-URLs של קבצי JS/CSS משתנים ל-`dynamic_asset` URLs שגורמים ל-404.

**דוגמה:**
- URL מקורי: `https://site.com/wp-content/plugins/hb-cog-training/assets/js/config_senior.js?ver=1.0.0`
- URL בפועל: `https://site.com/index.php?dynamic_asset=%2Fwp-content%2Fplugins%2Fhb-cog-training%2Fassets%2Fjs%2Fconfig_senior.js%3Fver%3D1.0.0`
- תוצאה: **404 Not Found**

**הערה חשובה:** העמוד בטיוטה (draft) וצפייה ב-preview mode - זה יכול להשפיע!

## מה ניסיתי

1. **פילטרים `script_loader_src` ו-`script_loader_tag`** עם priority גבוה מאוד (999999) - לא עובד
2. **הוספת scripts ישירות ב-`wp_footer`** - עדיין נטענים דרך `dynamic_asset`
3. **הוספת scripts ישירות ב-shortcode output** - עדיין נטען דרך `dynamic_asset` (משהו מעבד את ה-HTML אחרי)

## מבנה הקבצים

```
/wp-content/plugins/hb-cog-training/
├── hb-cog-training.php (קובץ ראשי)
└── assets/
    ├── css/hb-cog-training.css
    └── js/
        ├── config_senior.js (export CONFIG_SENIOR)
        ├── scoring.js (imports מ-config_senior.js)
        ├── storage_local.js
        ├── go_nogo_game.js (imports מ-3 הקבצים למעלה)
        └── profile_7days.js (imports מ-storage_local.js)
```

## דרישות

- ES6 modules חייבים (יש imports/exports)
- הקבצים צריכים להיטען ישירות (לא דרך `dynamic_asset`)
- תאימות ל-WordPress enqueue system
- עובד גם ב-preview mode

## קוד נוכחי (רלוונטי)

```php
// Shortcode - הוספת scripts ישירות
$base_url = HB_COG_PLUGIN_URL . 'assets/js/';
echo "<script type='module' src='" . esc_url($base_url . 'config_senior.js') . "?ver=1.0.0'></script>";
```

אבל עדיין נטען דרך `dynamic_asset` - משהו מעבד את ה-HTML אחרי שהשורטקוד רץ.

## שאלות קריטיות

1. **מה יוצר את ה-`dynamic_asset` URLs?** 
   - פלאגין אופטימיזציה? (WP Rocket, Autoptimize, וכו')
   - משהו ב-WordPress core?
   - פילטר ב-theme?

2. **איך לעקוף את זה?**
   - האם יש דרך להכריח WordPress לטעון קבצים ישירות?
   - האם צריך להשתמש ב-`wp_print_scripts` במקום?
   - האם צריך להשתמש ב-inline scripts במקום external?

3. **האם preview mode משפיע?**
   - האם יש פילטרים שרצים רק ב-preview?
   - האם צריך לבדוק `is_preview()`?

## מידע נוסף

- WordPress version: לא ידוע (אבל מודרני)
- יש פלאגינים נוספים שעובדים עם JS modules
- ה-CSS עובד (302 redirect) אבל JS לא
- העמוד בטיוטה (draft) - preview mode
- ה-scripts נטענים פעמיים (רואים 2 ניסיונות לכל קובץ)

## ניסיון אחרון

עכשיו הוספתי את ה-scripts ישירות ב-shortcode output (לא דרך wp_enqueue), אבל עדיין משהו מעבד את ה-HTML ומשנה את ה-URLs ל-dynamic_asset.

