# 📁 מבנה הפרויקט - higayonbarie.co.il

## 🗂️ מבנה התיקיות

```
higayonbarie-site/
├── 📂 plugins/                    # פלאגינים מותאמים
│   ├── goodlife-*                 # פלאגיני משחקים (wordle, cipher, etc.)
│   └── hb-*                       # פלאגיני היגיון (cue, logic-lab, etc.)
│
├── 📂 themes/                     # Themes מותאמים
│   ├── hello-theme-child/         # Theme ראשי (Child של Hello Elementor)
│   └── nbsp/                      # Theme נוסף (אם נדרש)
│
├── 📂 backup/                     # גיבויים
│   ├── temp/                      # גיבויים זמניים (לא לשנות)
│   └── database.sql               # גיבוי מסד נתונים
│
├── 📂 config/                     # קבצי הגדרה
│   ├── wp-config.example.php      # דוגמה ל-wp-config
│   └── environment.json           # הגדרות סביבה
│
├── 📂 scripts/                    # סקריפטים שימושיים
│   ├── deploy.sh                  # סקריפט העלאה
│   └── backup.sh                  # סקריפט גיבוי
│
├── 📂 docs/                       # תיעוד
│   ├── development/               # תיעוד פיתוח
│   ├── deployment/                # תיעוד העלאה
│   └── README.md                  # תיעוד ראשי
│
├── 📂 assets/                     # קבצים סטטיים
│   ├── images/                    # תמונות
│   ├── css/                       # CSS מותאם
│   └── js/                        # JavaScript מותאם
│
└── 📂 wordpress/                  # קבצי WordPress (אם נדרש)
```

---

## 📦 פלאגינים מותאמים

### פלאגיני Goodlife (משחקים)
- **goodlife-wordle** - משחק Wordle בעברית
- **goodlife-cipher** - משחק צופן
- **goodlife-matchsticks** - משחק גפרורים
- **goodlife-pangram** - משחק פנגרם
- **goodlife-wikiguess** - משחק ניחוש מויקיפדיה
- **goodlife-wordhole** - משחק חור מילים
- **goodlife-wordsearch** - משחק חיפוש מילים
- **Goodlife - Connections** - משחק קשרים

### פלאגיני HB (היגיון)
- **hb-cue** - רמזים
- **hb-cue-guide** - מדריך רמזים
- **hb-cue-prompt** - הנחיות רמזים
- **hb-logic-lab** - מעבדת היגיון
- **hb-logic-lab-flow** - זרימת מעבדת היגיון
- **hb-logic-sheet** - דף היגיון
- **hb-video-cues-youtube** - רמזי וידאו מ-YouTube
- **hb-video-tutor** - מורה וידאו

---

## 🎨 Themes

### hello-theme-child
Theme ראשי של האתר - Child Theme של Hello Elementor.

**מבנה:**
- `functions.php` - פונקציות מותאמות
- `style.css` - עיצוב מותאם
- `js/` - סקריפטים מותאמים
- `paid-memberships-pro/` - תבניות PMPro
- `ultimate-member/` - תבניות Ultimate Member
- `template-user_crossword/` - תבנית תשבץ משתמש

### nbsp
Theme נוסף (אם נדרש).

---

## 🔧 קבצי הגדרה

### .gitignore
קובץ שמגדיר אילו קבצים לא להעלות ל-Git.

### config/
תיקייה לקבצי הגדרה:
- `wp-config.example.php` - דוגמה ל-wp-config
- `environment.json` - הגדרות סביבה

---

## 📝 תיעוד

### docs/
תיקיית תיעוד:
- `README.md` - תיעוד ראשי
- `development/` - תיעוד פיתוח
- `deployment/` - תיעוד העלאה

---

## 🚀 עבודה עם הפרויקט

1. **פיתוח מקומי:**
   - פתח את התיקייה ב-Cursor
   - ערוך קבצים ב-`plugins/` או `themes/`
   - בדוק שינויים

2. **העלאה לשרת:**
   - השתמש בסקריפטים ב-`scripts/`
   - עקוב אחרי התיעוד ב-`docs/deployment/`

3. **גיבוי:**
   - שמור גיבויים ב-`backup/`
   - אל תשנה את `backup/temp/`

---

**עודכן:** 2025-01-27
















