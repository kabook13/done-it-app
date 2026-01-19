# 🚀 מדריך העלאה - higayonbarie.co.il

## 📋 מבוא

מדריך זה מסביר איך להעלות שינויים לשרת בצורה בטוחה.

---

## ⚠️ לפני העלאה

### Checklist

- [ ] בדקתי את כל השינויים
- [ ] אין שגיאות syntax
- [ ] הכל עובד מקומית
- [ ] יצרתי גיבוי של הקבצים בשרת
- [ ] אני יודע איך לחזור אחורה אם יש בעיה

---

## 📦 תהליך העלאה

### שלב 1: הכנה

1. **צור גיבוי:**
   ```bash
   # גבה את הקבצים הקיימים בשרת
   # שמור ב-backup/
   ```

2. **רשום שינויים:**
   - אילו קבצים שונו
   - מה השינויים
   - תאריך העלאה

### שלב 2: העלאה

#### אפשרות 1: FTP/SFTP

1. **התחבר לשרת:**
   - השתמש ב-FileZilla או כלי אחר
   - התחבר עם פרטי השרת

2. **העלה קבצים:**
   - העלה רק את הקבצים ששונו
   - העלה ל-`wp-content/plugins/` או `wp-content/themes/`

3. **בדוק permissions:**
   - קבצים: 644
   - תיקיות: 755

#### אפשרות 2: cPanel File Manager

1. **פתח File Manager:**
   - התחבר ל-cPanel
   - פתח File Manager

2. **העלה קבצים:**
   - העלה קבצים דרך הממשק
   - העתק למיקום הנכון

#### אפשרות 3: סקריפט (אם יש)

```bash
# אם יש סקריפט ב-scripts/
./scripts/deploy.sh
```

### שלב 3: אימות

1. **בדוק את האתר:**
   - פתח את האתר בדפדפן
   - בדוק שהכל עובד
   - בדוק שאין שגיאות

2. **בדוק שינויים:**
   - בדוק שהשינויים נראים
   - בדוק פונקציונליות
   - בדוק בדפדפנים שונים

3. **בדוק שגיאות:**
   - בדוק error_log
   - בדוק console בדפדפן
   - בדוק שאין warnings

---

## 🔄 חזרה אחורה (Rollback)

אם יש בעיה אחרי העלאה:

1. **החזר גיבוי:**
   ```bash
   # החזר את הקבצים מהגיבוי
   ```

2. **בדוק:**
   - בדוק שהאתר עובד
   - בדוק שאין שגיאות

3. **תקן:**
   - תקן את הבעיה
   - נסה שוב

---

## 📝 תיעוד העלאה

### שמור מידע על כל העלאה:

- **תאריך:** YYYY-MM-DD
- **קבצים שהועלו:** רשימה
- **שינויים:** תיאור קצר
- **מי העלה:** שם
- **הערות:** הערות נוספות

### דוגמה:

```
2025-01-27
- plugins/goodlife-wordle/wordle.php
- themes/hello-theme-child/style.css
שינויים: עדכון מילים ב-Wordle, תיקון עיצוב
מי: [שם]
הערות: הכל עובד תקין
```

---

## ⚠️ בעיות נפוצות

### בעיה: שינויים לא נראים

**פתרונות:**
1. נקה cache (אם יש)
2. בדוק שהקבצים הועלו נכון
3. בדוק permissions

### בעיה: שגיאות PHP

**פתרונות:**
1. בדוק error_log
2. בדוק syntax
3. החזר גיבוי אם צריך

### בעיה: עיצוב שבור

**פתרונות:**
1. נקה cache של דפדפן
2. בדוק CSS
3. בדוק שהקבצים נטענים

---

## 🔒 אבטחה

### לפני העלאה:

1. **בדוק קוד:**
   - אין קוד מסוכן
   - אין backdoors
   - הכל נקי

2. **בדוק permissions:**
   - קבצים: 644
   - תיקיות: 755
   - אין 777

3. **בדוק passwords:**
   - אין passwords בקוד
   - השתמש ב-wp-config.php

---

## 📞 תמיכה

אם יש בעיה:
1. בדוק את התיעוד
2. בדוק error_log
3. צור גיבוי לפני שינויים נוספים

---

**עודכן:** 2025-01-27
















