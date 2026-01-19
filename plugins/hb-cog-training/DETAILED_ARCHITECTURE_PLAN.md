# תוכנית מפורטת וארכיטקטורה - מערכת אימון קוגניטיבי

## 📐 ארכיטקטורה מלאה

### **מבנה הפרויקט:**

```
hb-cog-training/
├── hb-cog-training.php                    # Main plugin file (PHP)
│
├── includes/                              # PHP Classes (חדש)
│   ├── class-game-registry.php           # ניהול רישום משחקים
│   ├── class-statistics.php              # חישובים סטטיסטיים משופרים
│   ├── class-adaptive-training.php       # תוכניות אימון אדפטיביות
│   ├── class-research-references.php     # ניהול סימוכין מחקריים
│   ├── class-api-handlers.php            # AJAX handlers מרכזי
│   ├── class-database.php                # Database operations
│   └── class-asset-loader.php            # ניהול טעינת assets
│
├── assets/
│   ├── css/
│   │   ├── hb-cog-training.css           # CSS בסיסי (קיים - יש לשפר)
│   │   ├── hb-cog-animations.css         # אנימציות GSAP (חדש)
│   │   ├── hb-cog-dashboard.css          # דשבורד (חדש)
│   │   ├── hb-cog-research.css           # סימוכין מחקריים (חדש)
│   │   └── hb-cog-components.css         # Components משותפים (חדש)
│   │
│   ├── js/
│   │   ├── config_senior.js              # קונפיג (קיים)
│   │   ├── hb-cog-runtime.js             # Runtime (קיים)
│   │   ├── scoring.js                    # חישובי ציונים (קיים - יש לשפר)
│   │   ├── gsap-animations.js            # אנימציות GSAP (חדש)
│   │   └── dashboard.js                  # דשבורד JS (חדש)
│   │
│   ├── hb-cog/                            # משחקים קיימים (Vanilla JS)
│   │   ├── games/
│   │   │   ├── go_nogo.js                # (קיים - יש לשפר)
│   │   │   ├── stroop.js                 # (קיים - יש לשפר)
│   │   │   ├── nback1.js                 # (קיים - יש לשפר)
│   │   │   └── visual_search.js          # (קיים - יש לשפר)
│   │   └── hb-cog-core.js                # Core system (קיים)
│   │
│   └── react/                             # משחקים חדשים (React)
│       ├── build/                         # Build output (לא ב-git)
│       ├── src/
│       │   ├── components/
│       │   │   ├── games/
│       │   │   │   ├── DualNBack/
│       │   │   │   │   ├── DualNBack.js
│       │   │   │   │   ├── DualNBack.css
│       │   │   │   │   └── index.js
│       │   │   │   ├── MemoryMatrix/
│       │   │   │   │   ├── MemoryMatrix.js
│       │   │   │   │   ├── MemoryMatrix.css
│       │   │   │   │   └── index.js
│       │   │   │   └── ... (משחקים נוספים)
│       │   │   │
│       │   │   ├── shared/
│       │   │   │   ├── Button/
│       │   │   │   │   ├── Button.js
│       │   │   │   │   ├── Button.css
│       │   │   │   │   └── index.js
│       │   │   │   ├── Timer/
│       │   │   │   │   ├── Timer.js
│       │   │   │   │   ├── Timer.css
│       │   │   │   │   └── index.js
│       │   │   │   ├── ScoreDisplay/
│       │   │   │   ├── ProgressBar/
│       │   │   │   ├── Feedback/
│       │   │   │   └── GameContainer/
│       │   │   │
│       │   │   ├── dashboard/
│       │   │   │   ├── Dashboard.js
│       │   │   │   ├── Dashboard.css
│       │   │   │   ├── StatsCard/
│       │   │   │   ├── Chart/
│       │   │   │   └── TrendIndicator/
│       │   │   │
│       │   │   ├── training-plans/
│       │   │   │   ├── TrainingPlan.js
│       │   │   │   ├── TrainingPlan.css
│       │   │   │   ├── Recommendation/
│       │   │   │   └── ProgressTracker/
│       │   │   │
│       │   │   └── research/
│       │   │       ├── ResearchReferences.js
│       │   │       ├── ResearchReferences.css
│       │   │       └── ResearchCard/
│       │   │
│       │   ├── hooks/
│       │   │   ├── useGameState.js       # ניהול state של משחק
│       │   │   ├── useTimer.js           # טיימר hook
│       │   │   ├── useScoring.js         # חישובי ציונים
│       │   │   ├── useStatistics.js      # סטטיסטיקות
│       │   │   └── useAdaptiveTraining.js # תוכניות אימון
│       │   │
│       │   ├── utils/
│       │   │   ├── api.js                # API calls ל-WordPress
│       │   │   ├── scoring.js            # חישובי ציונים
│       │   │   ├── statistics.js         # חישובים סטטיסטיים
│       │   │   ├── animations.js         # פונקציות אנימציה
│       │   │   └── constants.js          # קבועים
│       │   │
│       │   ├── store/                    # State management (אופציונלי)
│       │   │   └── gameStore.js
│       │   │
│       │   └── App.js                    # React entry point
│       │
│       ├── public/
│       │   └── index.html
│       │
│       ├── package.json
│       ├── webpack.config.js
│       └── .babelrc
│
├── data/
│   └── research-references.json          # סימוכין מחקריים (JSON)
│
└── templates/                            # PHP Templates (אופציונלי)
    ├── game-page.php
    ├── category-page.php
    ├── dashboard-page.php
    └── training-plan-page.php
```

---

## 🏗️ ארכיטקטורה טכנית מפורטת

### **1. Backend (PHP/WordPress)**

#### **1.1 Main Plugin File: `hb-cog-training.php`**

**תפקידים:**
- הגדרות בסיסיות (constants, paths)
- רישום plugin
- טעינת classes
- Shortcodes
- AJAX handlers (legacy - יעברו ל-class)
- Database schema
- Asset loading

**שיפורים:**
- הפרדה ל-classes
- ניהול טוב יותר של assets
- אופטימיזציות

---

#### **1.2 Class: `class-game-registry.php`**

**תפקידים:**
- ניהול רישום משחקים
- מטא-דאטה של משחקים
- קישורים בין משחקים לתחומים
- ניהול difficulty levels
- ניהול game weights

**Methods:**
```php
class HB_COG_Game_Registry {
    public static function register_game($game_id, $config);
    public static function get_game($game_id);
    public static function get_games_by_domain($domain);
    public static function get_all_games();
    public static function is_game_valid($game_id);
}
```

---

#### **1.3 Class: `class-statistics.php`**

**תפקידים:**
- חישובי ציונים משופרים
- נירמול בין משחקים
- חישובי קטגוריות
- חישובי דשבורד
- חישובי מגמות

**Methods:**
```php
class HB_COG_Statistics {
    public static function calculate_game_score($metrics, $game_id);
    public static function normalize_score($score, $game_id);
    public static function calculate_category_score($domain, $date);
    public static function calculate_daily_score($user_id, $date);
    public static function calculate_trend($scores);
    public static function get_statistics($user_id, $game_id, $date);
}
```

---

#### **1.4 Class: `class-adaptive-training.php`**

**תפקידים:**
- ניתוח ביצועים
- זיהוי חוזקות/חולשות
- המלצות מותאמות
- תוכניות אימון
- מעקב אחרי שיפור

**Methods:**
```php
class HB_COG_Adaptive_Training {
    public static function analyze_performance($user_id);
    public static function identify_strengths_weaknesses($user_id);
    public static function generate_recommendations($user_id);
    public static function create_training_plan($user_id, $goals);
    public static function update_difficulty($user_id, $game_id);
    public static function track_progress($user_id);
}
```

---

#### **1.5 Class: `class-research-references.php`**

**תפקידים:**
- טעינת מחקרים מ-JSON
- הצגת מחקרים לפי משחק
- ניהול קישורים
- caching

**Methods:**
```php
class HB_COG_Research_References {
    public static function load_research($game_id);
    public static function get_research_for_game($game_id);
    public static function format_research($research);
}
```

---

#### **1.6 Class: `class-api-handlers.php`**

**תפקידים:**
- AJAX handlers מרכזי
- שמירת ניסיונות
- קבלת סטטיסטיקות
- קבלת מחקרים
- ניהול תוכניות אימון

**Methods:**
```php
class HB_COG_API_Handlers {
    public static function save_attempt();
    public static function get_statistics();
    public static function get_dashboard();
    public static function get_category_stats();
    public static function get_game_stats();
    public static function get_training_plan();
    public static function update_training_plan();
}
```

---

#### **1.7 Class: `class-database.php`**

**תפקידים:**
- CRUD operations
- Queries מורכבים
- אופטימיזציות
- Caching

**Methods:**
```php
class HB_COG_Database {
    public static function save_attempt($data);
    public static function get_attempts($user_id, $filters);
    public static function get_daily_summary($user_id, $date);
    public static function get_statistics($user_id, $game_id, $date);
    public static function optimize_queries();
}
```

---

#### **1.8 Class: `class-asset-loader.php`**

**תפקידים:**
- ניהול טעינת CSS/JS
- Conditional loading
- Versioning
- Minification

**Methods:**
```php
class HB_COG_Asset_Loader {
    public static function enqueue_styles();
    public static function enqueue_scripts();
    public static function load_game_assets($game_id);
    public static function load_react_assets();
}
```

---

### **2. Frontend - משחקים קיימים (Vanilla JS)**

#### **2.1 Core System: `hb-cog-core.js`**

**תפקידים:**
- ניהול משחקים
- State management
- Timer management
- Event handling

**שיפורים:**
- הוספת GSAP
- שיפור state management
- אופטימיזציות

---

#### **2.2 Games: `games/*.js`**

**משחקים:**
- `go_nogo.js` - Go/No-Go
- `stroop.js` - Stroop
- `nback1.js` - N-Back 1
- `visual_search.js` - Visual Search

**שיפורים לכל משחק:**
- הוספת GSAP לאנימציות
- שיפור פידבק חזותי
- חלקיקים ואפקטים
- אנימציות בזמן אמת

---

#### **2.3 Animations: `gsap-animations.js`**

**תפקידים:**
- פונקציות אנימציה משותפות
- פידבק חזותי (נכון/שגוי)
- אנימציות טיימר
- אנימציות ציון
- חלקיקים

**Functions:**
```javascript
// פידבק חזותי
function showCorrectFeedback(element, position);
function showWrongFeedback(element, position);

// אנימציות ציון
function animateScore(element, from, to);
function animateProgress(element, from, to);

// חלקיקים
function createParticles(x, y, isCorrect);

// אנימציות טיימר
function animateTimer(element, timeLeft);
```

---

### **3. Frontend - משחקים חדשים (React)**

#### **3.1 Components Structure**

**Games:**
- `DualNBack/` - Dual N-Back game
- `MemoryMatrix/` - Memory Matrix game
- `TowerOfHanoi/` - Tower of Hanoi game
- `DividedAttention/` - Divided Attention game
- `NumberSeries/` - Number Series game
- `PlanningGame/` - Planning Game

**Shared Components:**
- `Button/` - כפתור משותף
- `Timer/` - טיימר
- `ScoreDisplay/` - הצגת ציון
- `ProgressBar/` - progress bar
- `Feedback/` - פידבק חזותי
- `GameContainer/` - container למשחק

**Dashboard:**
- `Dashboard/` - דשבורד ראשי
- `StatsCard/` - כרטיס סטטיסטיקה
- `Chart/` - גרף
- `TrendIndicator/` - אינדיקטור מגמה

**Training Plans:**
- `TrainingPlan/` - תוכנית אימון
- `Recommendation/` - המלצה
- `ProgressTracker/` - מעקב התקדמות

**Research:**
- `ResearchReferences/` - סימוכין מחקריים
- `ResearchCard/` - כרטיס מחקר

---

#### **3.2 Hooks**

**Custom Hooks:**
- `useGameState.js` - ניהול state של משחק
- `useTimer.js` - טיימר hook
- `useScoring.js` - חישובי ציונים
- `useStatistics.js` - סטטיסטיקות
- `useAdaptiveTraining.js` - תוכניות אימון

---

#### **3.3 Utils**

**Utilities:**
- `api.js` - API calls ל-WordPress
- `scoring.js` - חישובי ציונים
- `statistics.js` - חישובים סטטיסטיים
- `animations.js` - פונקציות אנימציה
- `constants.js` - קבועים

---

### **4. Database Schema**

#### **4.1 טבלאות קיימות:**

**`hb_cog_attempts`:**
```sql
- id (bigint, primary key)
- user_id (bigint)
- track (varchar)
- game_id (varchar)
- difficulty (int)
- attempt_no (int)
- started_at (datetime)
- ended_at (datetime)
- date_iso (date)
- metrics (longtext, JSON)
- scores (longtext, JSON)
- domain_contrib (longtext, JSON)
- created_at (datetime)
```

**`hb_cog_daily`:**
```sql
- id (bigint, primary key)
- user_id (bigint)
- date_iso (date)
- track (varchar)
- daily_score (int)
- attempts_count (int)
- domains (longtext, JSON)
- games (longtext, JSON)
- updated_at (datetime)
```

---

#### **4.2 טבלאות חדשות (אופציונלי):**

**`hb_cog_training_plans`:**
```sql
- id (bigint, primary key)
- user_id (bigint)
- plan_name (varchar)
- goals (longtext, JSON)
- current_difficulty (longtext, JSON)
- recommendations (longtext, JSON)
- created_at (datetime)
- updated_at (datetime)
```

---

### **5. Data Files**

#### **5.1 `research-references.json`**

**מבנה:**
```json
{
  "go_nogo": {
    "title": "Go/No-Go Task",
    "studies": [
      {
        "title": "Inhibitory Control and Working Memory",
        "authors": "Diamond, A. (2013)",
        "journal": "Annual Review of Psychology",
        "year": 2013,
        "url": "https://doi.org/...",
        "summary": "מחקר מראה ש-Go/No-Go משפר עכבה וזיכרון עבודה"
      }
    ]
  }
}
```

---

## 📋 תוכנית עבודה מפורטת - שלב אחר שלב

### **שלב 1: שיפור עיצוב המשחקים הקיימים (1-2 שבועות)**

#### **יום 1-2: שיפור CSS בסיסי**

**משימות:**
- [ ] שיפור `hb-cog-training.css`:
  - גרדיאנטים מודרניים לכפתורים
  - צללים עמוקים (box-shadow)
  - טיפוגרפיה משופרת (גופנים, גדלים)
  - צבעים עשירים
  - אייקונים מקצועיים (Font Awesome או SVG)
- [ ] יצירת `hb-cog-animations.css`:
  - Keyframes לאנימציות
  - Transitions חלקים
  - Hover effects
  - Active states

**קבצים:**
- `assets/css/hb-cog-training.css` (עריכה)
- `assets/css/hb-cog-animations.css` (חדש)

**תוצאה:** משחקים נראים מקצועיים יותר

---

#### **יום 3-4: הוספת GSAP**

**משימות:**
- [ ] הוספת GSAP (CDN או npm)
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

**תוצאה:** אנימציות חלקות ופידבק חזותי

---

#### **יום 5-7: שיפורים מתקדמים**

**משימות:**
- [ ] חלקיקים (particles) לפידבק
- [ ] אנימציות מורכבות
- [ ] אופטימיזציות ביצועים
- [ ] בדיקות cross-browser
- [ ] responsive improvements

**קבצים:**
- `assets/js/gsap-animations.js` (עריכה)
- `assets/hb-cog/games/*.js` (עריכה)

**תוצאה:** משחקים מקצועיים עם אנימציות חלקות

---

### **שלב 2: דשבורד מקצועי (1-2 שבועות)**

#### **יום 1-2: תכנון ועיצוב**

**משימות:**
- [ ] תכנון UI/UX
- [ ] עיצוב mockups
- [ ] בחירת ספריית גרפים (Chart.js או Recharts)
- [ ] תכנון components

**תוצאה:** תכנון מלא

---

#### **יום 3-5: פיתוח Backend**

**משימות:**
- [ ] יצירת `class-statistics.php`
- [ ] פיתוח נוסחאות משופרות
- [ ] יצירת API endpoints
- [ ] בדיקות

**קבצים:**
- `includes/class-statistics.php` (חדש)
- `includes/class-api-handlers.php` (עריכה)

**תוצאה:** Backend מוכן

---

#### **יום 6-8: פיתוח Frontend**

**משימות:**
- [ ] יצירת React Dashboard component
- [ ] יצירת StatsCard components
- [ ] יצירת Chart components
- [ ] אינטגרציה עם backend
- [ ] אנימציות

**קבצים:**
- `assets/react/src/components/dashboard/Dashboard.js` (חדש)
- `assets/react/src/components/dashboard/StatsCard.js` (חדש)
- `assets/react/src/components/dashboard/Chart.js` (חדש)
- `assets/css/hb-cog-dashboard.css` (חדש)

**תוצאה:** דשבורד מקצועי עובד

---

#### **יום 9-10: שיפורים**

**משימות:**
- [ ] שיפורים בעיצוב
- [ ] אופטימיזציות
- [ ] בדיקות
- [ ] responsive improvements

**תוצאה:** דשבורד מושלם

---

### **שלב 3: שיפור חישובים סטטיסטיים (1 שבוע)**

#### **יום 1-2: מחקר**

**משימות:**
- [ ] מחקר נוסחאות (עם ChatGPT)
- [ ] פיתוח נוסחאות משופרות
- [ ] בדיקות מתמטיות

**תוצאה:** נוסחאות מוכנות

---

#### **יום 3-4: יישום**

**משימות:**
- [ ] יישום נוסחאות ב-`class-statistics.php`
- [ ] עדכון משחקים קיימים
- [ ] נירמול בין משחקים
- [ ] בדיקות

**קבצים:**
- `includes/class-statistics.php` (עריכה)
- `assets/hb-cog/games/*.js` (עריכה)

**תוצאה:** חישובים מדויקים

---

#### **יום 5: בדיקות ואופטימיזציות**

**משימות:**
- [ ] בדיקות מקיפות
- [ ] אופטימיזציות
- [ ] תיקונים

**תוצאה:** סטטיסטיקות מושלמות

---

### **שלב 4: משחק חדש אחד (1-2 שבועות)**

#### **משחק: Dual N-Back**

#### **יום 1-2: תכנון ועיצוב**

**משימות:**
- [ ] תכנון המשחק
- [ ] עיצוב UI/UX
- [ ] הכנת assets
- [ ] תכנון components

**תוצאה:** תכנון מלא

---

#### **יום 3-5: פיתוח**

**משימות:**
- [ ] פיתוח React component
- [ ] עיצוב מקצועי
- [ ] אנימציות (Framer Motion)
- [ ] אינטגרציה עם backend
- [ ] בדיקות

**קבצים:**
- `assets/react/src/components/games/DualNBack/DualNBack.js` (חדש)
- `assets/react/src/components/games/DualNBack/DualNBack.css` (חדש)
- `includes/class-game-registry.php` (עריכה)

**תוצאה:** משחק מקצועי עובד

---

#### **יום 6-7: שיפורים**

**משימות:**
- [ ] שיפורים
- [ ] אופטימיזציות
- [ ] בדיקות
- [ ] responsive improvements

**תוצאה:** משחק מושלם

---

## 🔄 תלויות בין שלבים

```
שלב 1 (עיצוב)
    │
    ├──> שלב 2 (דשבורד) ──┐
    │                      │
    └──> שלב 3 (סטטיסטיקות) ──┘
                              │
                              └──> שלב 4 (משחק חדש)
```

---

## 📅 לוח זמנים משוער

### **MVP מהיר (4-6 שבועות):**

**שבוע 1-2:**
- שלב 1: שיפור עיצוב
- תוצאה: משחקים נראים מקצועיים

**שבוע 3-4:**
- שלב 2: דשבורד
- תוצאה: דשבורד מקצועי

**שבוע 5:**
- שלב 3: סטטיסטיקות
- תוצאה: סטטיסטיקות מדויקות

**שבוע 6:**
- שלב 4: משחק חדש
- תוצאה: משחק מקצועי

---

## 🛠️ טכנולוגיות

### **קיימות:**
- WordPress (PHP 7.4+)
- Vanilla JavaScript (ES6+)
- CSS3

### **חדשות:**
- React 18+
- Framer Motion
- GSAP 3+
- Chart.js / Recharts
- Webpack 5 / Vite
- Node.js 16+

---

## ✅ Checklist התחלה

### **לפני שמתחילים:**
- [ ] התקן Node.js (אני אסביר)
- [ ] צור פרויקט React (אני אסביר)
- [ ] התקן GSAP (CDN או npm)
- [ ] החלט על ספריית גרפים

### **במהלך העבודה:**
- [ ] תן משוב מהיר
- [ ] תן אישורים מהירים
- [ ] בדוק מהר
- [ ] היה גמיש

---

## 🚀 הצעה להתחלה

### **אני ממליץ להתחיל עם:**

**שלב 1: שיפור עיצוב (1-2 שבועות)**

**למה:**
- ✅ תוצאה מיידית
- ✅ קל יחסית
- ✅ שיפור נראה מיד
- ✅ בסיס להמשך

**מה תקבל:**
- ✅ משחקים נראים מקצועיים
- ✅ אנימציות חלקות
- ✅ פידבק חזותי
- ✅ איכות 70-80% מהמתחרים

---

**מוכן להתחיל עם שלב 1?** 🚀
