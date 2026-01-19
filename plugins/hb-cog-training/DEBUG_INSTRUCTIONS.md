# הוראות דיבוג - בעיית dynamic_asset

## מה עשינו עד כה

1. ✅ הוספנו attributes להחרגה מאופטימייזרים:
   - `data-noptimize="1"` (Autoptimize)
   - `data-no-optimize="1"` (LiteSpeed/אחרים)
   - `data-skip-moving="true"` (מניעת העברה)
   - `data-hb-cog-skip-processing="1"` (החרגה כללית)

2. ✅ הוספנו פילטרים ל-`script_loader_tag` ו-`style_loader_tag`

3. ✅ הוספנו scripts ישירות ב-`wp_footer` עם כל ה-attributes

## מה לבדוק עכשיו

### 1. בדיקת Response Headers

פתח DevTools → Network → לחץ על אחד מה-404 (dynamic_asset) → Response Headers

חפש:
- `x-litespeed-cache` → LiteSpeed Cache
- `x-wp-rocket` → WP Rocket
- `x-sg-optimizer` → SiteGround Optimizer
- `server` → יכול להצביע על הוסטינג

### 2. בדיקת פלאגינים פעילים

ב-WordPress Admin → Plugins → Active Plugins

חפש:
- Autoptimize
- LiteSpeed Cache
- WP Rocket
- W3 Total Cache
- WP Super Cache
- SiteGround Optimizer
- Cloudflare (plugin)

### 3. בדיקת MU-Plugins

אם יש תיקייה `wp-content/mu-plugins/` - זה יכול להיות המקור.

### 4. בדיקת ה-HTML בפועל

View Page Source → חפש `hb-cog` → בדוק אם ה-attributes קיימים

אם ה-attributes **לא** קיימים → משהו מסיר אותם אחרי `wp_footer`

אם ה-attributes **כן** קיימים אבל עדיין יש `dynamic_asset` → האופטימייזר לא מכבד אותם

## פתרונות אפשריים

### אם זה Autoptimize:
Settings → Autoptimize → JavaScript Options → Exclude scripts → הוסף:
```
hb-cog-training
```

### אם זה LiteSpeed Cache:
LiteSpeed Cache → Page Optimization → JS Settings → Exclude → הוסף:
```
hb-cog-training
```

### אם זה WP Rocket:
WP Rocket → File Optimization → Exclude JavaScript files → הוסף:
```
/wp-content/plugins/hb-cog-training/
```

### אם זה משהו מהוסטינג:
צור קובץ `.htaccess` בתיקיית הפלאגין:
```apache
<FilesMatch "\.(js|css)$">
  Header set Cache-Control "no-transform"
</FilesMatch>
```

## אם כלום לא עובד

נצטרך לעבור ל-bundling - לבנות קובץ JS אחד במקום modules.

