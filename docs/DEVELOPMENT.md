# 💻 מדריך פיתוח - higayonbarie.co.il

## 🎯 מבוא

מדריך זה מסביר איך לפתח ולשנות את האתר higayonbarie.co.il.

---

## 🛠️ סביבת פיתוח

### דרישות

- **Cursor** - עורך קוד
- **PHP 7.4+** - לבדיקות מקומיות (אופציונלי)
- **WordPress** - להבנת המבנה
- **Git** - לניהול גרסאות (מומלץ)

### הכנה

1. פתח את הפרויקט ב-Cursor
2. ודא שיש לך גישה לקבצים
3. צור סביבת בדיקה (אם צריך)

---

## 📦 פיתוח פלאגינים

### מבנה פלאגין

כל פלאגין ב-`plugins/` צריך:

```
your-plugin/
├── your-plugin.php    # קובץ ראשי
├── readme.txt         # תיעוד (מומלץ)
└── ...                # קבצים נוספים
```

### יצירת פלאגין חדש

1. **צור תיקייה:**
   ```
   plugins/your-plugin-name/
   ```

2. **צור קובץ ראשי:**
   ```php
   <?php
   /**
    * Plugin Name: Your Plugin Name
    * Description: תיאור הפלאגין
    * Version: 1.0.0
    */
   
   // קוד הפלאגין כאן
   ```

3. **הוסף פונקציונליות:**
   - הוסף hooks של WordPress
   - הוסף shortcodes אם צריך
   - הוסף admin pages אם צריך

### עריכת פלאגין קיים

**דוגמה: goodlife-wordle**

```
plugins/goodlife-wordle/
├── wordle.php              ← ערוך כאן
├── hebrew-words.json       ← עדכן מילים כאן
└── target_words_*.json     ← עדכן מילים יעד כאן
```

**טיפים:**
- שמור את המבנה הקיים
- בדוק תאימות עם WordPress
- בדוק תאימות עם פלאגינים אחרים

---

## 🎨 פיתוח Themes

### מבנה Theme

```
themes/hello-theme-child/
├── style.css           # עיצוב
├── functions.php       # פונקציות
├── js/                 # JavaScript
└── templates/          # תבניות
```

### עריכת Theme

**style.css:**
```css
/* ערוך כאן עיצוב מותאם */
```

**functions.php:**
```php
<?php
// הוסף פונקציות מותאמות כאן
```

**js/:**
```javascript
// הוסף סקריפטים מותאמים כאן
```

### יצירת תבנית חדשה

1. צור קובץ ב-`templates/`
2. השתמש ב-WordPress Template Hierarchy
3. בדוק שהתבנית עובדת

---

## 🔧 כלים שימושיים

### Linter

השתמש ב-linter של Cursor לבדיקת שגיאות:
- PHP syntax errors
- JavaScript errors
- CSS errors

### Debug

**WordPress Debug:**
```php
// ב-wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

### Testing

1. בדוק בדפדפן
2. בדוק במכשירים שונים
3. בדוק תאימות

---

## 📝 סטנדרטי קוד

### PHP

- השתמש ב-WordPress Coding Standards
- כתוב הערות ברורות
- השתמש בשמות משתנים ברורים

### JavaScript

- השתמש ב-ES6+ אם אפשר
- כתוב קוד נקי
- הוסף הערות במקומות מורכבים

### CSS

- השתמש ב-BEM או מתודולוגיה אחרת
- כתוב CSS מסודר
- הוסף הערות לסקציות

---

## 🚀 בדיקות

### לפני העלאה

1. **בדוק syntax:**
   - PHP syntax
   - JavaScript syntax
   - CSS syntax

2. **בדוק פונקציונליות:**
   - כל הפיצ'רים עובדים
   - אין שגיאות
   - הכל נראה טוב

3. **בדוק תאימות:**
   - דפדפנים שונים
   - מכשירים שונים
   - פלאגינים אחרים

---

## 📚 משאבים

### WordPress
- [WordPress Codex](https://codex.wordpress.org/)
- [WordPress Developer Handbook](https://developer.wordpress.org/)
- [WordPress Hooks](https://developer.wordpress.org/reference/hooks/)

### PHP
- [PHP Manual](https://www.php.net/manual/en/)

### JavaScript
- [MDN Web Docs](https://developer.mozilla.org/)

---

**עודכן:** 2025-01-27
















