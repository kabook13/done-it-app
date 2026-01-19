# 🧩 higayonbarie.co.il - פרויקט אתר מסודר

## 📖 אודות

פרויקט מסודר ומקצועי לאתר **higayonbarie.co.il** - אתר תשבצי היגיון ומשחקי חשיבה.

פרויקט זה מאורגן עם תיעוד מפורט, מבנה מסודר וכלים לעבודה יעילה.

---

## 📂 מבנה הפרויקט

```
higayonbarie-site/
├── 📂 plugins/              # פלאגינים מותאמים
│   ├── goodlife-*          # פלאגיני משחקים
│   └── hb-*                # פלאגיני היגיון
│
├── 📂 themes/               # Themes מותאמים
│   ├── hello-theme-child/   # Theme ראשי
│   └── nbsp/                # Theme נוסף
│
├── 📂 backup/               # גיבויים
│   ├── temp/                # גיבויים זמניים
│   └── database.sql         # גיבוי מסד נתונים
│
├── 📂 config/               # קבצי הגדרה
│   └── environment.json     # הגדרות סביבה
│
├── 📂 scripts/              # סקריפטים שימושיים
│
├── 📂 docs/                 # תיעוד מפורט
│   ├── DEVELOPMENT.md       # מדריך פיתוח
│   └── DEPLOYMENT.md        # מדריך העלאה
│
├── 📂 assets/               # קבצים סטטיים
│
├── 📄 README.md             # קובץ זה
├── 📄 WORKFLOW.md           # תהליך עבודה
├── 📄 PROJECT_STRUCTURE.md  # מבנה מפורט
├── 📄 CHANGELOG.md          # יומן שינויים
└── 📄 .gitignore            # הגדרות Git
```

---

## 🚀 התחלה מהירה

### 🎯 רוצה ליצור פיצ'ר חדש?

**→ התחל כאן:** [START_HERE.md](START_HERE.md)

זה המדריך המהיר ליצירת פיצ'רים חדשים (כמו מבחן IQ או סיקוונס משחקי בחינת מוח).

---

### 1. פתיחת הפרויקט

```bash
# פתח את הפרויקט ב-Cursor
File → Open Folder → F:\cursor\files\higayonbarie-site
```

### 2. הכרת המבנה

- **פלאגינים:** `plugins/` - כל הפלאגינים המותאמים
- **Themes:** `themes/` - כל ה-Themes המותאמים
- **תיעוד:** `docs/` - מדריכים מפורטים

### 3. התחלת עבודה

- **יצירת פיצ'ר חדש:** [START_HERE.md](START_HERE.md)
- **תהליך עבודה כללי:** [WORKFLOW.md](WORKFLOW.md)

---

## 📋 מה יש באתר?

### משחקי חשיבה:
- **Wordle** - משחק ניחוש מילים בעברית
- **Cipher** - משחק צופן
- **Matchsticks** - משחק גפרורים
- **Pangram** - משחק פנגרם
- **Wikiguess** - משחק ניחוש מויקיפדיה
- **Wordhole** - משחק חור מילים
- **Wordsearch** - משחק חיפוש מילים
- **Connections** - משחק קשרים

### פלאגינים מותאמים:

#### פלאגיני Goodlife (משחקים):
- `goodlife-wordle` - Wordle בעברית
- `goodlife-cipher` - צופן
- `goodlife-matchsticks` - גפרורים
- `goodlife-pangram` - פנגרם
- `goodlife-wikiguess` - ניחוש מויקיפדיה
- `goodlife-wordhole` - חור מילים
- `goodlife-wordsearch` - חיפוש מילים
- `Goodlife - Connections` - קשרים

#### פלאגיני HB (היגיון):
- `hb-cue` - רמזים
- `hb-cue-guide` - מדריך רמזים
- `hb-cue-prompt` - הנחיות רמזים
- `hb-logic-lab` - מעבדת היגיון
- `hb-logic-lab-flow` - זרימת מעבדת היגיון
- `hb-logic-sheet` - דף היגיון
- `hb-video-cues-youtube` - רמזי וידאו
- `hb-video-tutor` - מורה וידאו

### Themes:
- `hello-theme-child` - Theme ראשי (Child של Hello Elementor)
- `nbsp` - Theme נוסף

---

## 📚 תיעוד

### מדריכים עיקריים:

1. **[START_HERE.md](START_HERE.md)** ⭐ - התחלה מהירה
   - איך ליצור פיצ'ר חדש
   - מפות עבודה
   - צעדים מעשיים

2. **[docs/WORKING_WITH_AI.md](docs/WORKING_WITH_AI.md)** 🤖 - עבודה עם AI
   - איך לפתוח שיחה חדשה
   - איך להציג קונטקסט
   - דוגמאות הודעות
   - תהליך עבודה מומלץ

2. **[WORKFLOW.md](WORKFLOW.md)** - תהליך עבודה מפורט
   - איך לעבוד עם הפרויקט
   - כללי עבודה
   - תהליך העלאה

3. **[docs/CREATING_NEW_FEATURES.md](docs/CREATING_NEW_FEATURES.md)** - יצירת פיצ'רים
   - תהליך עבודה שלב אחר שלב
   - תבניות קוד
   - דוגמאות מהפרויקט

4. **[docs/FEATURE_ROADMAP.md](docs/FEATURE_ROADMAP.md)** - מפת עבודה
   - תכנון פיצ'רים חדשים
   - השוואות והמלצות
   - תכנון מפורט

5. **[PROJECT_STRUCTURE.md](PROJECT_STRUCTURE.md)** - מבנה הפרויקט
   - הסבר על כל תיקייה
   - מבנה פלאגינים
   - מבנה themes

6. **[docs/DEVELOPMENT.md](docs/DEVELOPMENT.md)** - מדריך פיתוח
   - איך לפתח פלאגינים
   - איך לפתח themes
   - כלים וטיפים

7. **[docs/DEPLOYMENT.md](docs/DEPLOYMENT.md)** - מדריך העלאה
   - איך להעלות לשרת
   - checklist לפני העלאה
   - פתרון בעיות

### קבצים נוספים:

- **[CHANGELOG.md](CHANGELOG.md)** - יומן שינויים
- **[config/environment.json](config/environment.json)** - הגדרות סביבה
- **[.gitignore](.gitignore)** - הגדרות Git

---

## 🛠️ כלים שימושיים

### Cursor
- עורך קוד מומלץ
- תמיכה ב-PHP, JavaScript, CSS
- Linter מובנה

### Git (מומלץ)
- ניהול גרסאות
- מעקב אחרי שינויים
- גיבוי קוד

---

## 🔄 תהליך עבודה

### עבודה על פלאגין:
1. פתח את הפלאגין ב-`plugins/your-plugin/`
2. ערוך את הקבצים
3. בדוק את השינויים
4. שמור

### עבודה על Theme:
1. פתח את ה-Theme ב-`themes/hello-theme-child/`
2. ערוך קבצים (functions.php, style.css, וכו')
3. בדוק את השינויים
4. שמור

לקריאה מפורטת, עיין ב-[WORKFLOW.md](WORKFLOW.md).

---

## 📞 תמיכה

- **תיעוד:** קרא את הקבצים ב-`docs/`
- **מבנה:** עיין ב-[PROJECT_STRUCTURE.md](PROJECT_STRUCTURE.md)
- **עבודה:** עיין ב-[WORKFLOW.md](WORKFLOW.md)

---

## 📝 רישיון

פרויקט זה הוא פרטי.

---

**עודכן:** 2025-01-27  
**גרסה:** 1.0.0  
**מצב:** ✅ מוכן לעבודה

