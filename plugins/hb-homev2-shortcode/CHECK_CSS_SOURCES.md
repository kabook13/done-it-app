# מקורות CSS שעלולים לדרוס את העיצוב

## מקורות שצריך לבדוק ידנית (אין לי גישה אליהם):

### 1. WordPress Customizer CSS
**איפה לבדוק:**
- WordPress Admin → Appearance → Customize → Additional CSS
- או: WordPress Admin → Appearance → Theme Editor → Additional CSS

**מה לחפש:**
- כל CSS שמגדיר `a { color: ... }`
- כל CSS שמגדיר `.button { color: ... }`
- כל CSS שמגדיר `.hb-homev2` או `.hb-homev2-btn-primary`

**איך לתקן:**
- מחק או הערה את ה-CSS שמתנגש
- או הוסף `!important` ל-CSS שלנו (כבר עשיתי)

---

### 2. Elementor Custom CSS
**איפה לבדוק:**
- Elementor → Settings → Advanced → Custom CSS
- או: בכל עמוד שנבנה ב-Elementor → Settings (הגלגל) → Advanced → Custom CSS

**מה לחפש:**
- כל CSS שמגדיר `a { color: ... }`
- כל CSS שמגדיר `.button { color: ... }`
- כל CSS שמגדיר `.hb-homev2` או `.hb-homev2-btn-primary`

**איך לתקן:**
- מחק או הערה את ה-CSS שמתנגש
- או הוסף `!important` ל-CSS שלנו (כבר עשיתי)

---

### 3. Elementor Global CSS
**איפה לבדוק:**
- Elementor → Settings → Style → Global Colors/Fonts
- או: Elementor → Settings → Advanced → Custom CSS

**מה לחפש:**
- הגדרות צבעים גלובליים שעלולים להשפיע על קישורים
- הגדרות Typography שעלולות להשפיע על צבעי טקסט

**איך לתקן:**
- בדוק אם יש הגדרות גלובליות שמשנות את צבעי הקישורים
- שנה אותן או בטל אותן

---

### 4. Plugins שעלולים להוסיף CSS
**Plugins שצריך לבדוק:**
- **WP Accessibility** - אולי מוסיף CSS לקישורים
- **Any accessibility plugin** - אולי משנה צבעים
- **Any caching plugin** - אולי משמר CSS ישן
- **Any optimization plugin** - אולי משנה את סדר הטעינה

**איך לבדוק:**
1. כבה כל ה-plugins זמנית
2. בדוק אם הבעיה נפתרה
3. הפעל plugins אחד אחד עד שתמצא את הבעיה

---

### 5. Browser Extensions
**מה לבדוק:**
- Extensions של הדפדפן שעלולים להוסיף CSS
- Ad blockers שעלולים לחסום CSS
- Dark mode extensions שעלולים לשנות צבעים

**איך לבדוק:**
- פתח את האתר ב-Incognito/Private mode (ללא extensions)
- בדוק אם הבעיה נפתרה

---

## מקורות שכבר בדקתי:

### ✅ קבצי CSS בקוד:
- `themes/hello-theme-child/style.css` - יש שם CSS כללי `a { color: #DB8A16; }` - תיקנתי עם override
- `themes/hello-theme-child/paid-memberships-pro.css` - לא משפיע על hb-homev2
- `plugins/hb-cog-training/assets/css/hb-cog-training.css` - לא משפיע על hb-homev2

### ✅ PHP files:
- `themes/hello-theme-child/functions.php` - בדקתי, אין CSS שמתנגש
- `plugins/hb-homev2-shortcode/hb-homev2-shortcode.php` - ה-CSS שלנו נטען עם priority 99999

---

## מה לעשות עכשיו:

1. **בדוק WordPress Customizer:**
   - WordPress Admin → Appearance → Customize → Additional CSS
   - חפש כל CSS שמתנגש

2. **בדוק Elementor Custom CSS:**
   - Elementor → Settings → Advanced → Custom CSS
   - חפש כל CSS שמתנגש

3. **בדוק Browser Extensions:**
   - פתח ב-Incognito mode
   - בדוק אם הבעיה נפתרה

4. **בדוק Plugins:**
   - כבה plugins זמנית
   - בדוק אם הבעיה נפתרה

5. **בדוק ב-Developer Tools:**
   - F12 → Elements → בחר את הכפתור
   - Computed → בדוק מה ה-`color` בפועל
   - Rules → בדוק איזה CSS דורס את שלנו

---

## אם עדיין לא עובד:

שלח לי:
1. צילום מסך של ה-Computed styles של הכפתור
2. צילום מסך של ה-Rules tab (כל ה-CSS שמוחל על הכפתור)
3. צילום מסך של WordPress Customizer → Additional CSS (אם יש)
4. צילום מסך של Elementor → Settings → Advanced → Custom CSS (אם יש)

זה יעזור לי לזהות בדיוק מה דורס את ה-CSS.
