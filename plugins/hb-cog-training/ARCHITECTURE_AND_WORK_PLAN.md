# ארכיטקטורה ותוכנית עבודה - מערכת אימון קוגניטיבי

## 📐 ארכיטקטורה כללית

### **מבנה הפרויקט:**

```
hb-cog-training/
├── hb-cog-training.php                    # Main plugin file (PHP)
├── includes/                              # PHP classes & functions
│   ├── class-game-registry.php           # ניהול רישום משחקים
│   ├── class-statistics.php              # חישובים סטטיסטיים
│   ├── class-research-references.php     # ניהול סימוכין מחקריים
│   ├── class-api-handlers.php            # AJAX handlers
│   └── class-database.php                # Database operations
│
├── assets/
│   ├── css/
│   │   ├── hb-cog-training.css           # CSS בסיסי (קיים)
│   │   ├── hb-cog-animations.css        # אנימציות GSAP (חדש)
│   │   └── hb-cog-research.css          # עיצוב סימוכין מחקריים (חדש)
│   │
│   ├── js/
│   │   ├── config_senior.js             # קונפיג (קיים)
│   │   ├── hb-cog-runtime.js            # Runtime (קיים)
│   │   ├── scoring.js                   # חישובי ציונים (קיים)
│   │   └── gsap-animations.js           # אנימציות GSAP (חדש)
│   │
│   ├── hb-cog/                           # משחקים Vanilla JS (קיים)
│   │   ├── games/
│   │   │   ├── go_nogo.js               # (קיים - יש לשפר)
│   │   │   ├── stroop.js                # (קיים - יש לשפר)
│   │   │   ├── nback1.js                # (קיים - יש לשפר)
│   │   │   └── visual_search.js         # (קיים - יש לשפר)
│   │   └── hb-cog-core.js               # Core system (קיים)
│   │
│   └── react/                             # משחקים React (חדש)
│       ├── build/                         # Build output
│       ├── src/
│       │   ├── components/
│       │   │   ├── games/
│       │   │   │   ├── DualNBack.js     # משחק חדש
│       │   │   │   ├── MemoryMatrix.js  # משחק חדש
│       │   │   │   ├── TowerOfHanoi.js  # משחק חדש
│       │   │   │   └── ...
│       │   │   ├── shared/
│       │   │   │   ├── Button.js        # Component משותף
│       │   │   │   ├── Timer.js         # Component משותף
│       │   │   │   ├── ScoreDisplay.js # Component משותף
│       │   │   │   ├── ProgressBar.js  # Component משותף
│       │   │   │   └── Feedback.js      # Component משותף
│       │   │   └── research/
│       │   │       └── ResearchReferences.js # סימוכין מחקריים
│       │   ├── hooks/
│       │   │   ├── useGameState.js      # Hook לניהול state
│       │   │   ├── useTimer.js          # Hook לטיימר
│       │   │   └── useScoring.js        # Hook לחישובי ציונים
│       │   ├── utils/
│       │   │   ├── api.js               # API calls
│       │   │   ├── scoring.js           # חישובי ציונים
│       │   │   └── animations.js       # פונקציות אנימציה
│       │   └── App.js                   # React entry point
│       ├── package.json
│       └── webpack.config.js
│
├── data/
│   └── research-references.json          # סימוכין מחקריים (JSON)
│
└── templates/                            # PHP templates (אופציונלי)
    ├── game-page.php
    ├── category-page.php
    └── dashboard.php
```

---

## 🏗️ ארכיטקטורה טכנית

### **1. Backend (PHP/WordPress):**

#### **קובץ ראשי:**
- `hb-cog-training.php` - Main plugin file
  - הגדרות בסיסיות
  - Shortcodes
  - AJAX handlers
  - Database schema
  - Asset loading

#### **Classes (חדש):**
- `class-game-registry.php` - ניהול רישום משחקים
  - רישום משחקים (Vanilla JS + React)
  - מטא-דאטה של משחקים
  - קישורים בין משחקים לתחומים

- `class-statistics.php` - חישובים סטטיסטיים
  - חישובי ציונים משופרים
  - חישובי קטגוריות
  - חישובי דשבורד

- `class-research-references.php` - ניהול סימוכין מחקריים
  - טעינת מחקרים מ-JSON
  - הצגת מחקרים לפי משחק
  - ניהול קישורים

- `class-api-handlers.php` - AJAX handlers
  - שמירת ניסיונות
  - קבלת סטטיסטיקות
  - קבלת מחקרים

- `class-database.php` - Database operations
  - CRUD operations
  - Queries מורכבים
  - אופטימיזציות

---

### **2. Frontend - משחקים קיימים (Vanilla JS):**

#### **מבנה נוכחי:**
- `hb-cog-core.js` - Core system
- `games/*.js` - משחקים בודדים

#### **שיפורים:**
- הוספת GSAP לאנימציות
- שיפור עיצוב CSS
- פידבק חזותי משופר

---

### **3. Frontend - משחקים חדשים (React):**

#### **מבנה:**
- `src/components/games/` - משחקים
- `src/components/shared/` - Components משותפים
- `src/components/research/` - סימוכין מחקריים
- `src/hooks/` - Custom hooks
- `src/utils/` - Utilities

#### **Build:**
- Webpack/Vite build
- Output ל-`build/`
- WordPress integration

---

### **4. Database:**

#### **טבלאות קיימות:**
- `hb_cog_attempts` - ניסיונות משחקים
- `hb_cog_daily` - סיכום יומי

#### **שיפורים עתידיים:**
- אינדקסים נוספים
- אופטימיזציות queries
- Caching

---

## 📋 תוכנית עבודה מפורטת

### **פאזה 1: שיפור עיצוב המשחקים הקיימים (3-5 ימים)**

#### **יום 1-2: שיפור CSS בסיסי**

**משימות:**
- [ ] שיפור `hb-cog-training.css`:
  - גרדיאנטים מודרניים
  - צללים עמוקים
  - טיפוגרפיה משופרת
  - צבעים עשירים
- [ ] יצירת `hb-cog-animations.css`:
  - Keyframes לאנימציות
  - Transitions חלקים
  - Hover effects

**קבצים:**
- `assets/css/hb-cog-training.css` (עריכה)
- `assets/css/hb-cog-animations.css` (חדש)

**תלויות:** אין

---

#### **יום 3-4: הוספת GSAP**

**משימות:**
- [ ] התקנת GSAP (CDN או npm)
- [ ] יצירת `gsap-animations.js`:
  - פונקציות אנימציה משותפות
  - פידבק חזותי (נכון/שגוי)
  - אנימציות טיימר
  - אנימציות ציון
- [ ] אינטגרציה עם משחקים קיימים:
  - Go/No-Go
  - Stroop
  - N-Back 1
  - Visual Search

**קבצים:**
- `assets/js/gsap-animations.js` (חדש)
- `assets/hb-cog/games/*.js` (עריכה)

**תלויות:** יום 1-2

---

#### **יום 5: שיפורים מתקדמים**

**משימות:**
- [ ] חלקיקים (particles) לפידבק
- [ ] אנימציות מורכבות
- [ ] אופטימיזציות ביצועים
- [ ] בדיקות cross-browser

**קבצים:**
- `assets/js/gsap-animations.js` (עריכה)
- `assets/hb-cog/games/*.js` (עריכה)

**תלויות:** יום 3-4

---

### **פאזה 2: הוספת סימוכין מחקריים (2-3 ימים)**

#### **יום 1: מחקר ואיסוף**

**משימות (שלך):**
- [ ] איסוף מחקרים רלוונטיים לכל משחק
- [ ] סיכום קצר של כל מחקר
- [ ] קישורים למחקרים המלאים

**קבצים:**
- `data/research-references.json` (חדש - אתה מכין)

**תלויות:** אין

---

#### **יום 2: פיתוח UI**

**משימות:**
- [ ] יצירת `class-research-references.php`
- [ ] יצירת `ResearchReferences.js` (React component)
- [ ] עיצוב `hb-cog-research.css`
- [ ] אינטגרציה עם משחקים

**קבצים:**
- `includes/class-research-references.php` (חדש)
- `assets/react/src/components/research/ResearchReferences.js` (חדש)
- `assets/css/hb-cog-research.css` (חדש)

**תלויות:** יום 1 (מחקרים)

---

#### **יום 3: שיפורים ואינטגרציה**

**משימות:**
- [ ] שיפורים בעיצוב
- [ ] אינטגרציה מלאה
- [ ] בדיקות
- [ ] אופטימיזציות

**קבצים:**
- כל הקבצים מהשלב הקודם (עריכה)

**תלויות:** יום 2

---

### **פאזה 3: שיפור חישובים סטטיסטיים (3-4 ימים)**

#### **יום 1-2: מחקר ופיתוח נוסחאות**

**משימות:**
- [ ] מחקר נוסחאות (עם ChatGPT)
- [ ] פיתוח נוסחאות משופרות
- [ ] בדיקות מתמטיות

**קבצים:**
- `includes/class-statistics.php` (חדש/עריכה)

**תלויות:** אין

---

#### **יום 3-4: יישום**

**משימות:**
- [ ] יישום נוסחאות חדשות
- [ ] עדכון משחקים קיימים
- [ ] בדיקות
- [ ] אופטימיזציות

**קבצים:**
- `includes/class-statistics.php` (עריכה)
- `assets/hb-cog/games/*.js` (עריכה)

**תלויות:** יום 1-2

---

### **פאזה 4: פיתוח משחקים חדשים (5-7 ימים למשחק)**

#### **משחק 1: Dual N-Back (5-7 ימים)**

**יום 1-2: תכנון ועיצוב**
- [ ] תכנון המשחק
- [ ] עיצוב UI/UX
- [ ] הכנת assets

**יום 3-5: פיתוח**
- [ ] פיתוח ב-React
- [ ] עיצוב מקצועי
- [ ] אנימציות
- [ ] אינטגרציה עם המערכת

**יום 6-7: שיפורים**
- [ ] שיפורים
- [ ] אופטימיזציות
- [ ] בדיקות

**קבצים:**
- `assets/react/src/components/games/DualNBack.js` (חדש)
- `includes/class-game-registry.php` (עריכה)

---

#### **משחק 2: Memory Matrix (5-7 ימים)**

**אותו מבנה כמו Dual N-Back**

**קבצים:**
- `assets/react/src/components/games/MemoryMatrix.js` (חדש)

---

#### **משחק 3: Tower of Hanoi (5-7 ימים)**

**אותו מבנה כמו Dual N-Back**

**קבצים:**
- `assets/react/src/components/games/TowerOfHanoi.js` (חדש)

---

### **פאזה 5: Components משותפים (2-3 ימים)**

#### **יום 1-2: פיתוח Components**

**משימות:**
- [ ] Button component
- [ ] Timer component
- [ ] ScoreDisplay component
- [ ] ProgressBar component
- [ ] Feedback component

**קבצים:**
- `assets/react/src/components/shared/*.js` (חדש)

**תלויות:** אין (אבל משמש את המשחקים החדשים)

---

#### **יום 3: שיפורים**

**משימות:**
- [ ] שיפורים
- [ ] אופטימיזציות
- [ ] בדיקות

---

## 📊 תלויות בין פאזות

```
פאזה 1 (עיצוב) ──┐
                  ├──> פאזה 2 (מחקרים) ──┐
פאזה 3 (סטטיסטיקות) ──┘                  │
                                          ├──> פאזה 4 (משחקים חדשים)
פאזה 5 (Components) ──────────────────────┘
```

---

## 🎯 סדר עדיפויות

### **עדיפות גבוהה (מיד):**
1. ✅ פאזה 1: שיפור עיצוב (3-5 ימים)
2. ✅ פאזה 2: סימוכין מחקריים (2-3 ימים)
3. ✅ פאזה 5: Components משותפים (2-3 ימים)

### **עדיפות בינונית:**
4. ⚠️ פאזה 3: שיפור סטטיסטיקות (3-4 ימים)
5. ⚠️ פאזה 4: משחקים חדשים (5-7 ימים למשחק)

---

## 📅 לוח זמנים משוער

### **חודש 1:**
- שבוע 1-2: פאזה 1 (עיצוב)
- שבוע 3: פאזה 2 (מחקרים)
- שבוע 4: פאזה 5 (Components)

### **חודש 2:**
- שבוע 1: פאזה 3 (סטטיסטיקות)
- שבוע 2-3: משחק 1 (Dual N-Back)
- שבוע 4: משחק 2 (Memory Matrix)

### **חודש 3:**
- שבוע 1-2: משחק 3 (Tower of Hanoi)
- שבוע 3-4: שיפורים ואופטימיזציות

---

## 🔧 כלים וטכנולוגיות

### **קיימים:**
- WordPress (PHP)
- Vanilla JavaScript
- CSS

### **חדשים:**
- React
- Framer Motion
- GSAP
- Webpack/Vite
- Node.js

---

## 📝 הערות חשובות

### **1. תאימות:**
- משחקים קיימים (Vanilla JS) ימשיכו לעבוד
- משחקים חדשים (React) יעבדו במקביל
- אין צורך להמיר הכל בבת אחת

### **2. Build Process:**
- React games ייבנו עם Webpack/Vite
- Output יועתק ל-`assets/react/build/`
- WordPress יטען את הקבצים המובנים

### **3. Database:**
- אין שינויים מבניים נדרשים
- רק שיפורים בחישובים

---

## ✅ Checklist התחלה

### **לפני שמתחילים:**
- [ ] התקן Node.js
- [ ] צור פרויקט React
- [ ] התקן GSAP
- [ ] אסוף מחקרים (או תן לי לעזור)

### **במהלך העבודה:**
- [ ] תן משוב מהיר
- [ ] תן אישורים מהירים
- [ ] בדוק מהר

---

**מוכן להתחיל?** 🚀
