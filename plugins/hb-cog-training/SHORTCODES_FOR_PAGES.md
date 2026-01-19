# שורטקודים מדויקים לכל עמוד משחק

## 📋 רשימת עמודים ושורטקודים

### 1. Go/No-Go (כבר קיים)
**שם עמוד:** `אימון קוגניטיבי-Go/No-Go`  
**URL (Permalink):** `אימון-קוגניטיבי-go-nogo`  
**Shortcode:**
```
[hb_cog_game_page game="go_nogo"]
```

**אם רוצה להוסיף פרופיל 7 ימים:**
```
[hb_cog_game_page game="go_nogo"]

[hb_cog_profile track="senior" days="7"]
```

---

### 2. N-Back 1
**שם עמוד:** `אימון קוגניטיבי-N-Back 1`  
**URL (Permalink):** `אימון-קוגניטיבי-n-back-1`  
**Shortcode:**
```
[hb_cog_game_page game="nback1"]
```

**אם רוצה להוסיף פרופיל 7 ימים:**
```
[hb_cog_game_page game="nback1"]

[hb_cog_profile track="senior" days="7"]
```

---

### 3. מבחן Stroop
**שם עמוד:** `אימון קוגניטיבי-מבחן Stroop`  
**URL (Permalink):** `אימון-קוגניטיבי-מבחן-stroop`  
**Shortcode:**
```
[hb_cog_game_page game="stroop"]
```

**אם רוצה להוסיף פרופיל 7 ימים:**
```
[hb_cog_game_page game="stroop"]

[hb_cog_profile track="senior" days="7"]
```

---

### 4. חיפוש ויזואלי
**שם עמוד:** `אימון קוגניטיבי-חיפוש ויזואלי`  
**URL (Permalink):** `אימון-קוגניטיבי-חיפוש-ויזואלי`  
**Shortcode:**
```
[hb_cog_game_page game="visual_search"]
```

**אם רוצה להוסיף פרופיל 7 ימים:**
```
[hb_cog_game_page game="visual_search"]

[hb_cog_profile track="senior" days="7"]
```

---

## 📝 הערות חשובות

### איפה נמצאים הקודים של המשחקים?

**הקודים נמצאים ב:**
- `plugins/hb-cog-training/assets/hb-cog/games/go_nogo.js`
- `plugins/hb-cog-training/assets/hb-cog/games/nback1.js`
- `plugins/hb-cog-training/assets/hb-cog/games/stroop.js`
- `plugins/hb-cog-training/assets/hb-cog/games/visual_search.js`

**הקובץ הראשי (`hb-cog-training.php`) טוען אותם אוטומטית** - אתה לא צריך לעשות כלום!

---

## ✅ איך ליצור את העמודים

### שלב 1: יצירת עמוד
1. WordPress Admin → **עמודים** → **עמוד חדש**
2. הזן שם: `אימון קוגניטיבי-N-Back 1` (או שם אחר מהרשימה)
3. בגוף העמוד, העתק את ה-Shortcode המתאים מהרשימה למעלה
4. לחץ **"פרסם"**

### שלב 2: הגדרת URL
1. אחרי הפרסום, לחץ על **"ערוך Permalink"** (מתחת לשם העמוד)
2. שנה ל-URL מהרשימה למעלה (למשל: `אימון-קוגניטיבי-n-back-1`)
3. לחץ **"עדכן"**

### שלב 3: בדיקה
1. נסה לגשת לעמוד
2. בדוק שהמשחק נטען
3. בדוק שהפרופיל מוצג (אם הוספת)

---

## 🎯 סיכום מהיר

| משחק | שם עמוד | Shortcode |
|------|---------|-----------|
| Go/No-Go | `אימון קוגניטיבי-Go/No-Go` | `[hb_cog_game_page game="go_nogo"]` |
| N-Back 1 | `אימון קוגניטיבי-N-Back 1` | `[hb_cog_game_page game="nback1"]` |
| מבחן Stroop | `אימון קוגניטיבי-מבחן Stroop` | `[hb_cog_game_page game="stroop"]` |
| חיפוש ויזואלי | `אימון קוגניטיבי-חיפוש ויזואלי` | `[hb_cog_game_page game="visual_search"]` |

---

## 💡 טיפים

- **אם תרצה לשנות difficulty:** `[hb_cog_game_page game="nback1" difficulty="2"]`
- **אם תרצה לשנות track:** `[hb_cog_game_page game="nback1" track="senior"]`
- **אם תרצה רק פרופיל:** `[hb_cog_profile track="senior" days="7"]`



