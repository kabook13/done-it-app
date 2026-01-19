# פתרון בעיות - העיצוב לא מופיע

## הבעיה: העיצוב לא תואם לעיצוב החדש

אם אתה רואה את התוכן אבל העיצוב לא נכון, זה אומר שה-CSS לא נטען או לא מוחל.

---

## פתרון מהיר (נסה קודם):

### 1. ודא שהשורטקוד נכון

**אם אתה משתמש בשורטקוד אחד:**
```
[hb_home_v2]
```

**אם אתה משתמש בשורטקודים נפרדים:**
```
[hb_homev2_boot]
[hb_homev2_start]

[hb_homev2_hero]

[hb_homev2_weekly_teaser]

[hb_homev2_wizard]

[hb_homev2_daily]

[hb_homev2_weekly]

[hb_homev2_faq]

[hb_homev2_testimonials]

[hb_homev2_end]
```

**חשוב:** השורטקוד `[hb_homev2_boot]` **חובה** - הוא טוען את ה-CSS וה-JS!

---

### 2. נקה Cache

1. **Elementor:**
   - Elementor → Tools → Regenerate CSS
   - Elementor → Tools → Regenerate Files

2. **דפדפן:**
   - Ctrl+F5 (או Cmd+Shift+R ב-Mac)
   - או פתח ב-Incognito/Private mode

3. **WordPress Cache (אם יש):**
   - אם יש plugin cache (WP Rocket, W3 Total Cache וכו') - נקה אותו

---

### 3. בדוק אם ה-CSS נטען

1. **פתח את העמוד בדפדפן**
2. **לחץ F12** (Developer Tools)
3. **עבור לטאב "Elements"**
4. **לחץ Ctrl+F** וחפש: `hb-homev2-css` או `hb_homev2 build`
5. **אם אתה רואה את ה-CSS** - הוא נטען ✅
6. **אם לא** - הוא לא נטען ❌

---

## פתרון מתקדם:

### אם ה-CSS לא נטען:

#### פתרון 1: הוסף את ה-CSS ידנית

1. **פתח את העמוד באלמנטור**
2. **הוסף HTML Widget** (או Code Widget)
3. **הדבק את הקוד הבא:**

```html
<style>
/* העתק את כל ה-CSS מהקובץ preview-home-page-full.html */
/* או בקש ממני את ה-CSS המלא */
</style>
```

**זה לא מומלץ** כי זה יוצר כפילות, אבל זה יעבוד.

---

#### פתרון 2: בדוק אם Elementor חוסם inline styles

1. **Elementor → Settings → Advanced**
2. **בדוק את "Disable Default Colors" ו-"Disable Default Fonts"**
3. **אם הם מסומנים** - בטל את הסימון

---

#### פתרון 3: הוסף CSS דרך Theme Customizer

1. **WordPress → Appearance → Customize**
2. **Additional CSS**
3. **הדבק את ה-CSS המלא**

**זה לא מומלץ** כי זה לא יעדכן אוטומטית, אבל זה יעבוד.

---

## בדיקה מהירה ב-Console:

פתח Console (F12 → Console) והדבק:

```javascript
// בדוק אם ה-CSS קיים
const css = document.querySelector('#hb-homev2-css') || 
            document.querySelector('style[id*="hb-homev2"]') ||
            Array.from(document.querySelectorAll('style')).find(s => 
              s.textContent.includes('hb-homev2')
            );

if (css) {
  console.log('✅ CSS נטען!', css);
} else {
  console.log('❌ CSS לא נטען!');
}

// בדוק אם ה-HTML קיים
const homev2 = document.querySelector('.hb-homev2');
if (homev2) {
  console.log('✅ HTML קיים!', homev2);
} else {
  console.log('❌ HTML לא קיים!');
}

// בדוק אם יש שגיאות
console.log('שגיאות:', window.onerror);
```

---

## אם כלום לא עובד:

1. **צילום מסך** של העמוד
2. **צילום מסך** של Developer Tools (Console + Elements)
3. **רשימת השורטקודים** שאתה משתמש בהם
4. **שלח לי** ואני אבדוק

---

## טיפים נוספים:

1. **ודא שהפלאגין פעיל:**
   - WordPress → Plugins
   - ודא ש-"HB Home v2 Shortcode" פעיל

2. **בדוק אם יש שגיאות PHP:**
   - WordPress → Tools → Site Health
   - בדוק אם יש שגיאות

3. **נסה ב-Incognito mode:**
   - לפעמים extensions של הדפדפן חוסמים CSS

4. **בדוק אם יש CSS אחר שדורס:**
   - Developer Tools → Elements
   - בחר אלמנט
   - בדוק ב-"Computed" אם ה-CSS שלנו מוחל
