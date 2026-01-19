# איך לבדוק את טמפלטי Elementor

## הבעיה
יש שני עמודים עם אותו שורטקוד `[hb_account_dashboard]` אבל תוכן שונה:
- **"אזור אישי" (מפורסם):** 3 כרטיסים
- **"אזור אישי חדש" (טיוטה):** 4 כרטיסים

## מה לבדוק

### שלב 1: לבדוק את "תפריט אזור אישי" בטמפלטים שמורים

1. היכנס ל-WordPress Admin
2. לך ל- **Templates** → **Saved Templates** (או **טמפלטים** → **טמפלטים שמורים**)
3. חפש את **"תפריט אזור אישי"**
4. לחץ עליו כדי לערוך

**מה לבדוק:**
- האם יש שם את השורטקוד `[hb_account_dashboard]`?
- האם יש שם Custom CSS שמסתיר כרטיסים? (לחפש `display: none` או `visibility: hidden`)
- האם יש שם Display Conditions או Visibility Rules?

### שלב 2: לבדוק את התבנית ב-"בונה תבנית"

1. היכנס ל-WordPress Admin
2. לך ל- **Templates** → **Theme Builder** (או **טמפלטים** → **בונה תבנית**)
3. חפש תבנית שקשורה לאזור אישי
4. לחץ עליה כדי לערוך

**מה לבדוק:**
- האם יש שם את השורטקוד `[hb_account_dashboard]`?
- האם יש שם Custom CSS שמסתיר כרטיסים?
- האם יש שם Display Conditions?

### שלב 3: לבדוק את שני העמודים עצמם

#### עמוד "אזור אישי" (המפורסם):
1. לך ל- **Pages** → **All Pages**
2. מצא את **"אזור אישי"**
3. לחץ על **Edit with Elementor**
4. בדוק:
   - האם יש שם את השורטקוד `[hb_account_dashboard]`?
   - האם יש שם Custom CSS ב-Section/Widget שמסתיר כרטיסים?
   - האם יש Display Conditions על ה-Widget של השורטקוד?

#### עמוד "אזור אישי חדש" (טיוטה):
1. לך ל- **Pages** → **All Pages**
2. מצא את **"אזור אישי חדש"**
3. לחץ על **Edit with Elementor**
4. בדוק:
   - האם יש שם את השורטקוד `[hb_account_dashboard]`?
   - האם יש שם Custom CSS ב-Section/Widget שמסתיר כרטיסים?
   - האם יש Display Conditions על ה-Widget של השורטקוד?

### שלב 4: לנקות Cache

1. **Elementor Cache:**
   - לך ל- **Elementor** → **Tools** → **Regenerate CSS & Data**
   - לחץ על **Regenerate Files**

2. **WordPress Cache:**
   - אם יש לך plugin של cache (כמו WP Super Cache, W3 Total Cache, וכו'), נקה אותו

3. **דפדפן:**
   - לחץ `Ctrl + Shift + R` (או `Cmd + Shift + R` ב-Mac) ל-Hard Refresh

## מה לעשות אם מצאת משהו

אם מצאת Custom CSS שמסתיר כרטיסים:
- מחק אותו או העתק אותו אליי כדי שאוכל לבדוק

אם מצאת Display Conditions:
- הסר אותם או ספר לי מה הם

אם לא מצאת כלום:
- זה בסדר, נמשיך עם התאמת העיצוב ונבדוק שוב אחרי

## הערה חשובה

**אין צורך לעשות את כל הבדיקות האלה עכשיו!**

אפשר פשוט להמשיך עם התאמת העיצוב לסגנון עמוד הבית, ואחרי זה לבדוק אם יש עדיין הבדלים.
