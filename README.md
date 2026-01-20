# Done-It - מערכת ניהול אישית

אפליקציית PWA סטטית לניהול משימות, עולמות, כספת ומתנות.

## 🚀 התחלה מהירה

1. **העלאה ל-GitHub Pages:**
   - Push את הקבצים ל-GitHub
   - הפעל GitHub Pages ב-Settings → Pages
   - קבל כתובת: `https://YOUR_USERNAME.github.io/done-it-app/`

2. **התקנה על הטלפון:**
   - פתח את הכתובת בדפדפן
   - Android: Chrome → תפריט → "הוסף למסך הבית"
   - iPhone: Safari → Share → "הוסף למסך הבית"

📖 **להדרכה מפורטת:** ראה `DEPLOYMENT.md`

## 📁 קבצים נחוצים

- `lumina-vault.html` - האפליקציה הראשית
- `manifest.json` - PWA manifest
- `service-worker.js` - Service Worker לעבודה offline
- `index.html` - redirect פשוט
- `assets/icon.png` - האייקון של האפליקציה
- `icon-generator.html` - כלי עזר ליצירת אייקונים

## ✨ תכונות

- ✅ ניהול משימות עם עדיפויות ותזכורות
- 🌍 עולמות (עבורי, הבית ודורי, פרויקטים, משאלות)
- 🔐 כספת מאובטחת עם סיסמה
- 💝 מתנות ממני - הודעות ומתנות דיגיטליות
- 📱 PWA מלא - עובד offline
- 🌙 מצב כהה/בהיר
- 🚀 אפליקציות מהירות

## 💾 אחסון נתונים

כל הנתונים נשמרים ב-localStorage במכשיר. אין שרת, אין סנכרון - 100% פרטיות.

## 🔄 עדכונים

לעדכן את האפליקציה:
1. ערוך את הקבצים המקומיים
2. `git add . && git commit -m "Update" && git push`
3. GitHub Pages יעדכן אוטומטית תוך 1-2 דקות
