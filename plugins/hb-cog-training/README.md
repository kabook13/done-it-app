# HB Cognitive Training - מערכת אימון מוח (מסלול גיל שלישי)

## 📋 תיאור

פלאגין WordPress למערכת אימון מוח מינימלית המותאמת במיוחד לגיל השלישי. כולל משחק Go/No-Go עם מערכת ציונים משוקללת, סיכום יומי ופרופיל 7 ימים.

**גרסה:** 1.0.0  
**מחבר:** Goodlife

---

## 🚀 התקנה והפעלה

### שלב 1: העלאת הקבצים

1. העלה את התיקייה `hb-cog-training` ל-`/wp-content/plugins/`
2. ודא שהמבנה הבא קיים:
   ```
   wp-content/plugins/hb-cog-training/
   ├── hb-cog-training.php
   └── assets/
       ├── css/
       │   └── hb-cog-training.css
       └── js/
           ├── config_senior.js
           ├── scoring.js
           ├── storage_local.js
           ├── go_nogo_game.js
           └── profile_7days.js
   ```

### שלב 2: הפעלת הפלאגין

1. היכנס ל-WordPress Admin (`/wp-admin`)
2. לך ל-**Plugins** → **Installed Plugins**
3. מצא את **"HB Cognitive Training (Senior Track)"**
4. לחץ על **"Activate"**

### שלב 3: בדיקה ראשונית

1. היכנס לאזור האישי (My Account)
2. בדוק שהטאב **"אימון מוח"** מופיע בתפריט
3. לחץ על הטאב ובדוק שהמשחק והפרופיל נטענים

---

## 📖 שימוש

### א. שימוש באזור האישי (מומלץ)

הפלאגין מוסיף אוטומטית טאב **"אימון מוח"** לאזור האישי (WPUM) למשתמשים מחוברים.

**מיקום:** אזור אישי → טאב "אימון מוח"

הטאב כולל:
- הסבר קצר על אימון מוח
- משחק Go/No-Go
- פרופיל 7 ימים

### ב. שימוש ב-Shortcodes

#### 1. משחק Go/No-Go

```
[hb_cog_game game="go_nogo" track="senior" difficulty="1"]
```

**פרמטרים:**
- `game` (חובה): `"go_nogo"` (כרגע רק זה נתמך)
- `track` (אופציונלי): `"senior"` (ברירת מחדל)
- `difficulty` (אופציונלי): `1-5` (ברירת מחדל: `1`)

**דוגמאות:**
```
[hb_cog_game game="go_nogo"]
[hb_cog_game game="go_nogo" difficulty="3"]
[hb_cog_game game="go_nogo" track="senior" difficulty="2"]
```

#### 2. פרופיל 7 ימים

```
[hb_cog_profile track="senior" days="7"]
```

**פרמטרים:**
- `track` (אופציונלי): `"senior"` (ברירת מחדל)
- `days` (אופציונלי): `1-30` (ברירת מחדל: `7`)

**דוגמאות:**
```
[hb_cog_profile]
[hb_cog_profile days="14"]
[hb_cog_profile track="senior" days="30"]
```

---

## 🎮 משחק Go/No-Go

### כללי המשחק

1. **משך:** 90 שניות
2. **גירויים:**
   - 🟢 **עיגול ירוק** = GO (לחץ/גע)
   - 🔴 **עיגול אדום** = NO-GO (אל תלחץ/תגע)
3. **יחס:** 70% GO, 30% NO-GO
4. **מטרה:** להגיב מהר ככל האפשר ל-GO, ולא להגיב ל-NO-GO

### רמות קושי

| קושי | מרווח בין גירויים | זמן הצגה |
|------|-------------------|----------|
| 1 | 1400-1900ms | 950ms |
| 2 | 1300-1800ms | 900ms |
| 3 | 1200-1700ms | 850ms |
| 4 | 1100-1600ms | 800ms |
| 5 | 1000-1500ms | 750ms |

### קושי אדפטיבי

המערכת מתאימה את הקושי אוטומטית:
- **עלייה:** אם דיוק ≥ 85% ב-2 משחקים רצופים → קושי +1
- **ירידה:** אם דיוק < 60% → קושי -1
- **טווח:** 1-5 (נעול)

### חישוב ציון

הציון מחושב לפי נוסחה משוקללת:

```
ציון סופי = (דיוק × 55%) + (מהירות × 30%) + (יציבות × 15%)
```

**רכיבי הציון:**
- **דיוק (55%):** אחוז תשובות נכונות (GO + NO-GO משוקלל)
- **מהירות (30%):** זמן תגובה ממוצע (300ms = 100, 1100ms = 0)
- **יציבות (15%):** פיזור זמני תגובה (CV - Coefficient of Variation)

**תוויות מהירות:**
- 0-33: "רגוע"
- 34-66: "טוב"
- 67-100: "מהיר"

---

## 📊 פרופיל 7 ימים

הפרופיל מציג:

1. **סטטיסטיקות כוללות:**
   - רצף ימים (streak) - ימים רצופים עם ציון ≥ 68
   - ציון ממוצע

2. **טבלה יומית:**
   - תאריך
   - ציון יומי
   - תחום מוביל (אם קיים)

3. **מקור נתונים:**
   - אם משתמש מחובר: טוען מ-WordPress DB
   - אחרת: טוען מ-localStorage
   - Fallback אוטומטי אם שרת לא זמין

---

## 💾 שמירת נתונים

### localStorage

**מפתחות:**
- `hb_cog_attempts_v1` - כל הניסיונות (עד 200 אחרונים)
- `hb_cog_daily_v1` - סיכומים יומיים

**מבנה נתונים:**
```javascript
// ניסיון
{
  user_id: null/123,
  track: "senior",
  game_id: "go_nogo",
  difficulty: 1,
  started_at: 1234567890,
  ended_at: 1234568790,
  date_iso: "2024-12-14",
  metrics: {...},
  scores: {...},
  domain_contrib: {...}
}

// סיכום יומי
{
  date_iso: "2024-12-14",
  track: "senior",
  daily_score: 85,
  domains: {...},
  updated_at: 1234567890
}
```

### WordPress DB (משתמשים מחוברים)

**user_meta:**
- `hb_cog_attempts` - מערך ניסיונות (עד 200)
- `hb_cog_daily_YYYY-MM-DD` - סיכום יומי לפי תאריך

---

## 🔌 AJAX Endpoints

### 1. שמירת ניסיון

**Action:** `hb_cog_save_attempt`

**פרמטרים:**
- `attempt` (JSON) - נתוני הניסיון
- `_ajax_nonce` - nonce לאבטחה

**תשובה:**
```json
{
  "success": true,
  "data": {
    "message": "ניסיון נשמר בהצלחה"
  }
}
```

### 2. קבלת פרופיל

**Action:** `hb_cog_get_profile`

**פרמטרים:**
- `track` - מסלול (default: "senior")
- `days` - מספר ימים (default: 7)
- `_ajax_nonce` - nonce לאבטחה

**תשובה:**
```json
{
  "success": true,
  "data": {
    "profile_data": [...],
    "track": "senior",
    "days": 7
  }
}
```

---

## 🎯 תחומי יכולת (Domains)

למשחק Go/No-Go:

| תחום | משקל |
|------|------|
| קשב (attention) | 60% |
| מהירות עיבוד (processing_speed) | 20% |
| עכבה (inhibition) | 20% |

**סה"כ:** 100%

---

## 🛠️ מבנה הקבצים

```
hb-cog-training/
├── hb-cog-training.php          # קובץ ראשי - shortcodes, AJAX, hooks
├── README.md                     # קובץ זה
└── assets/
    ├── css/
    │   └── hb-cog-training.css   # עיצוב מותאם גיל שלישי
    └── js/
        ├── config_senior.js      # הגדרות מסלול גיל שלישי
        ├── scoring.js            # מערכת ציונים משוקללת
        ├── storage_local.js      # שמירה מקומית + שרת
        ├── go_nogo_game.js       # משחק Go/No-Go מלא
        └── profile_7days.js      # פרופיל 7 ימים
```

---

## 🔧 תכונות טכניות

### תמיכה ב-ES Modules

כל קבצי ה-JS משתמשים ב-`type="module"`:
- `import/export` מודרני
- תמיכה בדפדפנים מודרניים
- טעינה רק כשצריך (conditional enqueue)

### Conditional Loading

הפלאגין טוען נכסים רק כשצריך:
- בדיקה אם יש shortcode בעמוד
- טעינת JS רק למשחק/פרופיל הרלוונטי

### תאימות לאחור

- שמירה ב-localStorage תמיד
- שמירה ב-WordPress DB רק למשתמשים מחוברים
- Fallback אוטומטי אם שרת לא זמין

---

## ⚠️ פתרון בעיות

### הטאב "אימון מוח" לא מופיע

**פתרון:**
1. ודא שהפלאגין מופעל
2. ודא שאתה מחובר כמשתמש
3. בדוק שהאתר משתמש ב-WP User Manager (WPUM)
4. נקה cache (אם יש)

### המשחק לא נטען

**פתרון:**
1. פתח Console בדפדפן (F12)
2. בדוק אם יש שגיאות JavaScript
3. ודא שקבצי ה-JS נטענים (Network tab)
4. בדוק ש-`type="module"` נתמך בדפדפן

### נתונים לא נשמרים

**פתרון:**
1. בדוק אם localStorage פעיל (לא במצב incognito)
2. אם משתמש מחובר - בדוק Console לשגיאות AJAX
3. בדוק nonce - רענן את הדף

### פרופיל לא מציג נתונים

**פתרון:**
1. ודא שיש נתונים ב-localStorage (DevTools → Application → Local Storage)
2. אם משתמש מחובר - בדוק user_meta ב-WordPress
3. בדוק Console לשגיאות

---

## 📝 דוגמאות שימוש

### דוגמה 1: עמוד אימון מוח ייעודי

צור עמוד חדש ב-WordPress והוסף:

```
<h2>אימון מוח יומי</h2>
<p>שחק משחק קצר כל יום לשמירה על חדות מחשבתית.</p>

[hb_cog_game game="go_nogo" track="senior" difficulty="1"]

<h3>התקדמות שבועית</h3>
[hb_cog_profile track="senior" days="7"]
```

### דוגמה 2: שילוב באזור אישי מותאם

הפלאגין מוסיף אוטומטית לאזור האישי, אבל אפשר גם ליצור עמוד מותאם עם shortcodes.

---

## 🔐 אבטחה

- **Nonce verification** בכל AJAX request
- **Sanitization** של כל הקלטים
- **Authorization** - רק משתמשים מחוברים יכולים לשמור בשרת
- **XSS protection** - כל הפלטים מסוננים

---

## 🎨 התאמה אישית

### שינוי עיצוב

ערוך את `assets/css/hb-cog-training.css`:

```css
/* שינוי צבע ראשי */
.hb-cog-game-header h3 {
  color: #your-color;
}

/* שינוי גודל גירוי */
.hb-cog-stimulus {
  width: 250px;
  height: 250px;
}
```

### שינוי הגדרות קושי

ערוך את `assets/js/config_senior.js`:

```javascript
go_nogo: {
  difficulty: {
    1: {
      interval_min_ms: 1500, // שינוי מרווח
      interval_max_ms: 2000,
      stimulus_duration_ms: 1000
    }
  }
}
```

### שינוי משקולות ציון

ערוך את `assets/js/config_senior.js`:

```javascript
scoring_weights: {
  accuracy: 0.60,  // שינוי משקל
  speed: 0.25,
  stability: 0.15
}
```

---

## 📚 הרחבות עתידיות

- [ ] משחקים נוספים (זיכרון רצפים, איתור דפוסים)
- [ ] גרפים מתקדמים (התקדמות שבועית/חודשית)
- [ ] מערכת הודעות (חיזוקים, הנחיות)
- [ ] קושי אדפטיבי מתקדם (מעקב בין משחקים)
- [ ] תמיכה במסלולים נוספים (כללי, מתקדם)

---

## 📞 תמיכה

לשאלות או בעיות:
1. בדוק את ה-Console בדפדפן (F12)
2. בדוק את ה-logs של WordPress
3. ודא שכל הקבצים קיימים ומופעלים

---

## 📄 רישיון

פלאגין זה פותח עבור Goodlife.

---

## 🔄 עדכונים

**גרסה 1.0.0** (2024-12-14)
- שחרור ראשוני
- משחק Go/No-Go
- מערכת ציונים משוקללת
- פרופיל 7 ימים
- אינטגרציה עם WPUM

---

**נבנה עם ❤️ עבור גיל שלישי**

