# תיעוד מלא - מערכת אימון קוגניטיבי (HB Cognitive Training)

## סקירה כללית

מערכת אימון קוגניטיבי מבוססת WordPress המאפשרת למשתמשים רשומים לבצע אימוני מוח דומים ל-Effectivate/Excellent Brain. המערכת כוללת משחקים קוגניטיביים פשוטים, מדורגים, עם מעקב אחר ביצועים וסטטיסטיקות.

## ארכיטקטורה

### מבנה הקבצים

```
plugins/hb-cog-training/
├── hb-cog-training.php          # קובץ הפלאגין הראשי (כל הלוגיקה)
├── assets/hb-cog/
│   ├── hb-cog-core.js           # מנוע המשחקים המרכזי (HB_COG_Core)
│   └── games/
│       ├── go_nogo.js           # משחק Go/No-Go
│       ├── stroop.js            # משחק Stroop
│       ├── nback1.js            # משחק N-Back 1
│       └── visual_search.js     # משחק חיפוש ויזואלי
```

### עקרונות עיצוב

1. **כל הקוד ב-PHP אחד**: כל הלוגיקה (PHP, CSS, JS) נמצאת בקובץ `hb-cog-training.php` בגלל בעיות עבר עם הפרדת קבצים.

2. **מערכת Core Engine**: 
   - `HB_COG_Core` - מנוע מרכזי שמנהל את כל המשחקים
   - כל משחק הוא מודול נפרד שיוצר instance של `HB_COG_Core`
   - המשחקים נרשמים ב-`window.HB_COG_GAMES` registry

3. **Fallback System**: אם `HB_COG_Core` לא זמין, המערכת משתמשת ב-"minimal core" שמספק פונקציונליות בסיסית.

## מבנה הנתונים

### טבלאות מסד הנתונים

1. **`wp_hb_cog_attempts`**: ניסיונות משחקים
   - `id`, `user_id`, `track`, `game_id`, `difficulty`, `attempt_no`
   - `started_at`, `ended_at`, `date_iso`
   - `metrics` (JSON), `scores` (JSON), `domain_contrib` (JSON)

2. **`wp_hb_cog_daily`**: סיכום יומי (cache)
   - `user_id`, `date_iso`, `daily_score`, `domains` (JSON)

### קטגוריות קוגניטיביות (Domains)

```php
define('HB_COG_DOMAIN_LABELS', [
  'attention' => 'קשב',
  'inhibition' => 'עכבה',
  'processing_speed' => 'מהירות עיבוד',
  'working_memory' => 'זיכרון עבודה',
  'reasoning_flexibility' => 'גמישות מחשבתית',
  'visual_perception' => 'תפיסה ויזואלית'
]);
```

### רישום משחקים

```php
define('HB_COG_GAME_REGISTRY', [
  'go_nogo' => ['title' => 'Go/No-Go', 'domains' => ['attention', 'inhibition']],
  'stroop' => ['title' => 'Stroop', 'domains' => ['inhibition', 'processing_speed', 'reasoning_flexibility']],
  'nback1' => ['title' => 'N-Back 1', 'domains' => ['working_memory']],
  'visual_search' => ['title' => 'חיפוש ויזואלי', 'domains' => ['attention', 'processing_speed']]
]);
```

## זרימת העבודה

### 1. טעינת משחק

1. **PHP**: `hb_cog_game_page` shortcode קורא את ה-`game` מה-URL:
   ```php
   $game = isset($_GET['game']) ? sanitize_text_field($_GET['game']) : 'go_nogo';
   ```

2. **PHP**: מעביר ל-`hb_cog_game` shortcode:
   ```php
   echo do_shortcode('[hb_cog_game game="'.$game.'" ...]');
   ```

3. **PHP**: יוצר container עם `data-hb-cog-game` attribute:
   ```php
   <div class="hb-cog-game-container" data-hb-cog-game="<?php echo $game; ?>">
   ```

4. **JavaScript**: `mountGame()` נקרא על ה-container:
   ```javascript
   var gameId = container.getAttribute('data-hb-cog-game') || 'go_nogo';
   ```

5. **JavaScript**: מנסה לטעון דרך `HB_COG_Core`, אם לא זמין - משתמש ב-fallback.

### 2. הפעלת משחק

1. המשתמש לוחץ על "התחל אימון"
2. `startGame()` נקרא:
   - מעדכן `minimalCore.running = true`
   - מציג את אזור המשחק
   - מתחיל טיימר
   - קורא ל-`gameInstance.start()`

3. המשחק מתחיל להציג גירויים

### 3. סיום משחק

1. המשתמש לוחץ על "סיים מוקדם" או הזמן נגמר
2. `finishGame()` נקרא:
   - מעדכן `minimalCore.running = false`
   - עוצר את המשחק
   - אוסף metrics
   - מציג תוצאות
   - שומר לשרת דרך `saveAttemptToServer()`

## בעיות ידועות ופתרונות

### בעיה 1: כל המשחקים מציגים go_nogo

**תסמינים**: 
- ה-URL מכיל `game=stroop` אבל המשחק שמציג הוא go_nogo
- ה-container מקבל `data-hb-cog-game="go_nogo"` במקום `stroop`

**סיבה אפשרית**:
- ה-`game` לא מועבר נכון מה-URL ל-shortcode
- או שה-`game` נדרס על ידי fallback

**פתרון**:
- לבדוק את ה-error log של WordPress:
  ```
  HB_COG: Game page will use game: ...
  HB_COG: Passing to hb_cog_game shortcode: game=...
  HB_COG: hb_cog_game shortcode using game: ...
  ```
- לבדוק את ה-DOM - האם ה-container מכיל את ה-attribute הנכון?

### בעיה 2: כפתור "התחל אימון" לא עובד

**תסמינים**:
- לחיצה על הכפתור לא עושה כלום

**סיבה**:
- ה-event listeners לא מוגדרים בזמן
- ה-init() קורא ל-renderHTML() שיוצר את הכפתורים, אבל אז ה-listeners מוגדרים ב-setTimeout

**פתרון**:
- להגדיר את ה-event listeners ישירות אחרי init(), לא ב-setTimeout
- לוודא שה-init() מסתיים לפני הגדרת ה-listeners

### בעיה 3: נתונים לא נשמרים

**תסמינים**:
- המשחק מסתיים אבל הנתונים לא נשמרים

**פתרון**:
- לוודא ש-`saveAttemptToServer()` נקרא ב-`finishGame()`
- לבדוק את ה-console לשגיאות AJAX

## Shortcodes

### `[hb_cog_dashboard]`
מציג דשבורד ראשי עם סטטיסטיקות וקישורים לקטגוריות.

### `[hb_cog_category domain="attention" days="30"]`
מציג עמוד קטגוריה עם משחקים וסטטיסטיקות.

### `[hb_cog_game_page]`
עמוד משחק דינמי. קורא את ה-`game` מה-URL:
- `?game=stroop` - משחק Stroop
- `?game=nback1` - משחק N-Back
- וכו'

### `[hb_cog_game game="go_nogo" track="senior" difficulty="1"]`
מציג משחק ספציפי. משמש על ידי `hb_cog_game_page`.

## JavaScript API

### `HB_COG_Core`
מנוע מרכזי שמנהל משחקים:
```javascript
var core = new HB_COG_Core(container, gameId, config);
core.init();
```

### `window.HB_COG_GAMES`
Registry של כל המשחקים:
```javascript
window.HB_COG_GAMES['stroop'] = StroopGame;
```

### `mountGame(container)`
פונקציה שמטעינה משחק על container:
```javascript
mountGame(document.querySelector('.hb-cog-game-container'));
```

## קונפיגורציה

### `CONFIG_SENIOR`
קונפיגורציה מרכזית:
```javascript
window.CONFIG_SENIOR = {
  session_duration_ms: 300000, // 5 דקות
  difficulty_levels: { ... },
  domains: { ... }, // תרומה של כל משחק לקטגוריות
  scoring: { ... }
};
```

## דיבוג

### Error Logs (WordPress)
בקובץ `wp-content/debug.log` (אם `WP_DEBUG_LOG` מופעל):
```
HB_COG: Game page will use game: stroop
HB_COG: Passing to hb_cog_game shortcode: game=stroop
HB_COG: hb_cog_game shortcode using game: stroop
```

### Console Logs (Browser)
```javascript
HB_COG: Mounting game stroop
HB_COG: Container data-hb-cog-game attribute: stroop
HB_COG: Setting up buttons for game stroop
```

## בעיות פתוחות

1. **כל המשחקים מציגים go_nogo**: צריך לבדוק למה ה-`game` לא מועבר נכון מה-URL
2. **כפתור "התחל אימון" לא עובד**: תוקן - ה-event listeners מוגדרים ישירות אחרי init()
3. **נתונים לא נשמרים**: צריך לבדוק את `saveAttemptToServer()`

## שלבים עתידיים

1. הוספת עוד 2-3 משחקים לכל קטגוריה
2. יישום לוגיקת התקדמות אוטומטית (difficulty adjustment)
3. סטטיסטיקות מפורטות לכל משחק וקטגוריה
4. תמיכה רב-לשונית



