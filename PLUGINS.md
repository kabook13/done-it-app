# 🔌 רשימת פלאגינים - higayonbarie.co.il

## 📦 פלאגינים מותאמים

### פלאגיני Goodlife (משחקים)

#### goodlife-wordle
**תיאור:** משחק Wordle בעברית  
**מיקום:** `plugins/goodlife-wordle/`  
**קבצים עיקריים:**
- `wordle.php` - קובץ ראשי
- `hebrew-words.json` - רשימת מילים בעברית
- `target_words_*.json` - מילים יעד

#### goodlife-cipher
**תיאור:** משחק צופן  
**מיקום:** `plugins/goodlife-cipher/`  
**קבצים עיקריים:**
- `goodlife-cipher.php` - קובץ ראשי

#### goodlife-matchsticks
**תיאור:** משחק גפרורים  
**מיקום:** `plugins/goodlife-matchsticks/`  
**קבצים עיקריים:**
- `goodlife-matchsticks.php` - קובץ ראשי

#### goodlife-pangram
**תיאור:** משחק פנגרם  
**מיקום:** `plugins/goodlife-pangram/`  
**קבצים עיקריים:**
- `goodlife-pangram.php` - קובץ ראשי

#### goodlife-wikiguess
**תיאור:** משחק ניחוש מויקיפדיה  
**מיקום:** `plugins/goodlife-wikiguess/`  
**קבצים עיקריים:**
- `wikiguess.php` - קובץ ראשי

#### goodlife-wordhole
**תיאור:** משחק חור מילים  
**מיקום:** `plugins/goodlife-wordhole/`  
**קבצים עיקריים:**
- `goodlife-wordhole.php` - קובץ ראשי

#### goodlife-wordsearch
**תיאור:** משחק חיפוש מילים  
**מיקום:** `plugins/goodlife-wordsearch/`  
**קבצים עיקריים:**
- `goodlife-wordsearch.php` - קובץ ראשי

#### Goodlife - Connections
**תיאור:** משחק קשרים  
**מיקום:** `plugins/Goodlife - Connections/`  
**קבצים עיקריים:**
- `goodlife-connections.php` - קובץ ראשי

---

### פלאגיני HB (היגיון)

#### hb-cue
**תיאור:** רמזים  
**מיקום:** `plugins/hb-cue/`  
**קבצים עיקריים:**
- `hb-cue.php` - קובץ ראשי

#### hb-cue-guide
**תיאור:** מדריך רמזים  
**מיקום:** `plugins/hb-cue-guide/`  
**קבצים עיקריים:**
- `hb-cue-guide.php` - קובץ ראשי

#### hb-cue-prompt
**תיאור:** הנחיות רמזים  
**מיקום:** `plugins/hb-cue-prompt/`  
**קבצים עיקריים:**
- `hb-cue-prompt.php` - קובץ ראשי

#### hb-logic-lab
**תיאור:** מעבדת היגיון  
**מיקום:** `plugins/hb-logic-lab/`  
**קבצים עיקריים:**
- `hb-logic-lab.php` - קובץ ראשי

#### hb-logic-lab-flow
**תיאור:** זרימת מעבדת היגיון  
**מיקום:** `plugins/hb-logic-lab-flow/`  
**קבצים עיקריים:**
- `hb-logic-lab-flow.php` - קובץ ראשי

#### hb-logic-sheet
**תיאור:** דף היגיון  
**מיקום:** `plugins/hb-logic-sheet/`  
**קבצים עיקריים:**
- `hb-logic-sheet.php` - קובץ ראשי

#### hb-video-cues-youtube
**תיאור:** רמזי וידאו מ-YouTube  
**מיקום:** `plugins/hb-video-cues-youtube/`  
**קבצים עיקריים:**
- `hb-video-cues-youtube.php` - קובץ ראשי

#### hb-video-tutor
**תיאור:** מורה וידאו  
**מיקום:** `plugins/hb-video-tutor/`  
**קבצים עיקריים:**
- `hb-video-tutor.php` - קובץ ראשי

#### hb-testimonials
**תיאור:** Shortcodes להצגת המלצות לקוחות באתר  
**מיקום:** `plugins/hb-testimonials/`  
**קבצים עיקריים:**
- `hb-testimonials.php` - קובץ ראשי (כולל הכל - אין קבצי CSS/JS נפרדים)

**Shortcodes זמינים:**
- `[hb_testimonials_full]` - הצגת כל ההמלצות במלואן (עמוד ייעודי)
- `[hb_testimonials_course]` - המלצות מלאות עם כותרת לקורס
- `[hb_testimonials_home]` - ציטוטים קצרים לעמוד הבית
- `[hb_testimonials_subscription]` - ציטוטים קצרים לעמוד המנוי/חוג

**הערות:**
- התוסף משתמש ב-inline styles בלבד (לא קבצי CSS נפרדים)
- העיצוב מינימלי - רק margin, padding, border-radius, background
- הטיפוגרפיה נשארת לתבנית/אלמנטור

---

## 🔧 עבודה עם פלאגינים

### עריכת פלאגין

1. **פתח את הפלאגין:**
   ```
   plugins/your-plugin/your-plugin.php
   ```

2. **ערוך את הקוד:**
   - הוסף פונקציונליות
   - תקן באגים
   - שפר ביצועים

3. **בדוק:**
   - בדוק שהפלאגין עובד
   - בדוק שאין שגיאות
   - בדוק תאימות

### יצירת פלאגין חדש

1. **צור תיקייה:**
   ```
   plugins/your-plugin-name/
   ```

2. **צור קובץ ראשי:**
   ```php
   <?php
   /**
    * Plugin Name: Your Plugin Name
    * Description: תיאור הפלאגין
    * Version: 1.0.0
    */
   ```

3. **הוסף פונקציונליות:**
   - הוסף hooks
   - הוסף shortcodes
   - הוסף admin pages

לקריאה מפורטת, עיין ב-[docs/DEVELOPMENT.md](docs/DEVELOPMENT.md).

---

## 📝 הערות

- כל הפלאגינים נמצאים ב-`plugins/`
- כל פלאגין הוא תיקייה נפרדת
- הקובץ הראשי הוא בדרך כלל `plugin-name.php`

---

**עודכן:** 2025-01-27
