# מדריך מעשי: איך לעבור ל-React

## שלב 1: הכנה

### 1.1 התקנת Node.js
```bash
# הורד מ: https://nodejs.org/
# התקן את הגרסה LTS
```

### 1.2 יצירת פרויקט React
```bash
# בתיקיית הפרויקט
npx create-react-app hb-cog-react
cd hb-cog-react
```

### 1.3 התקנת תלויות נוספות
```bash
npm install react-router-dom
npm install framer-motion  # לאנימציות
```

---

## שלב 2: מבנה הפרויקט

### 2.1 מבנה תיקיות
```
hb-cog-react/
├── src/
│   ├── components/
│   │   ├── games/
│   │   │   ├── GoNoGo.js
│   │   │   ├── Stroop.js
│   │   │   ├── NBack1.js
│   │   │   └── VisualSearch.js
│   │   ├── shared/
│   │   │   ├── Button.js
│   │   │   ├── Timer.js
│   │   │   └── ScoreDisplay.js
│   │   └── layout/
│   │       ├── GameContainer.js
│   │       └── Results.js
│   ├── hooks/
│   │   ├── useGameState.js
│   │   └── useTimer.js
│   ├── utils/
│   │   ├── scoring.js
│   │   └── api.js
│   └── App.js
├── public/
└── package.json
```

---

## שלב 3: המרת משחק Go/No-Go

### 3.1 הקוד הנוכחי (Vanilla JS)
```javascript
// go_nogo.js (נוכחי)
class GoNoGoGame {
  constructor(container, config, core) {
    this.container = container;
    this.core = core;
    this.running = false;
    this.score = 0;
  }
  
  renderHTML() {
    this.container.innerHTML = `
      <button class="start-btn">Start</button>
      <div class="score">${this.score}</div>
    `;
  }
  
  start() {
    this.running = true;
  }
}
```

### 3.2 הקוד החדש (React)
```javascript
// src/components/games/GoNoGo.js
import React, { useState, useEffect, useRef } from 'react';
import { Button } from '../shared/Button';
import { Timer } from '../shared/Timer';
import { ScoreDisplay } from '../shared/ScoreDisplay';

function GoNoGo({ config, core }) {
  const [running, setRunning] = useState(false);
  const [score, setScore] = useState(0);
  const [timeLeft, setTimeLeft] = useState(300); // 5 דקות
  const [currentStim, setCurrentStim] = useState(null);
  
  useEffect(() => {
    if (running && timeLeft > 0) {
      const timer = setInterval(() => {
        setTimeLeft(prev => prev - 1);
      }, 1000);
      return () => clearInterval(timer);
    }
  }, [running, timeLeft]);
  
  const handleStart = () => {
    setRunning(true);
    setTimeLeft(300);
  };
  
  const handleStop = () => {
    setRunning(false);
  };
  
  return (
    <div className="go-no-go-game">
      {!running && (
        <Button onClick={handleStart}>
          התחל אימון
        </Button>
      )}
      
      {running && (
        <>
          <Timer timeLeft={timeLeft} />
          <div className="stimulus-area">
            {currentStim === 'go' && (
              <div className="stimulus go" onClick={handleGoClick}>
                GO
              </div>
            )}
            {currentStim === 'nogo' && (
              <div className="stimulus nogo">
                NO-GO
              </div>
            )}
          </div>
          <Button onClick={handleStop}>
            סיים מוקדם
          </Button>
        </>
      )}
      
      <ScoreDisplay score={score} />
    </div>
  );
}

export default GoNoGo;
```

---

## שלב 4: Components משותפים

### 4.1 Button Component
```javascript
// src/components/shared/Button.js
import React from 'react';
import './Button.css';

export function Button({ children, onClick, variant = 'primary' }) {
  return (
    <button 
      className={`btn btn-${variant}`}
      onClick={onClick}
    >
      {children}
    </button>
  );
}
```

### 4.2 Timer Component
```javascript
// src/components/shared/Timer.js
import React from 'react';
import './Timer.css';

export function Timer({ timeLeft }) {
  const minutes = Math.floor(timeLeft / 60);
  const seconds = timeLeft % 60;
  
  return (
    <div className="timer">
      {minutes}:{seconds.toString().padStart(2, '0')}
    </div>
  );
}
```

---

## שלב 5: State Management

### 5.1 Custom Hook לניהול State
```javascript
// src/hooks/useGameState.js
import { useState, useEffect } from 'react';

export function useGameState(initialState) {
  const [state, setState] = useState(initialState);
  
  const updateState = (updates) => {
    setState(prev => ({ ...prev, ...updates }));
  };
  
  return [state, updateState];
}
```

### 5.2 שימוש ב-Hook
```javascript
// במשחק
const [gameState, updateGameState] = useGameState({
  running: false,
  score: 0,
  timeLeft: 300
});

// עדכון state
updateGameState({ running: true });
```

---

## שלב 6: אינטגרציה עם WordPress

### 6.1 Build הפרויקט
```bash
npm run build
```

### 6.2 העתקת הקבצים
```bash
# העתק את הקבצים מ-build/ ל:
wp-content/plugins/hb-cog-training/assets/react/
```

### 6.3 טעינה ב-WordPress
```php
// hb-cog-training.php
function hb_cog_enqueue_react() {
  wp_enqueue_script(
    'hb-cog-react',
    plugin_dir_url(__FILE__) . 'assets/react/static/js/main.js',
    [],
    '1.0.0',
    true
  );
  
  wp_enqueue_style(
    'hb-cog-react-css',
    plugin_dir_url(__FILE__) . 'assets/react/static/css/main.css',
    [],
    '1.0.0'
  );
}
add_action('wp_enqueue_scripts', 'hb_cog_enqueue_react');
```

### 6.4 Shortcode עם React
```php
// hb-cog-training.php
add_shortcode('hb_cog_game_react', function($atts) {
  $game = $atts['game'] ?? 'go_nogo';
  
  return '<div id="hb-cog-react-root" data-game="' . esc_attr($game) . '"></div>';
});
```

### 6.5 Mount React ב-WordPress
```javascript
// src/index.js
import React from 'react';
import ReactDOM from 'react-dom/client';
import App from './App';

const rootElement = document.getElementById('hb-cog-react-root');
if (rootElement) {
  const game = rootElement.dataset.game;
  const root = ReactDOM.createRoot(rootElement);
  root.render(<App game={game} />);
}
```

---

## שלב 7: API Calls

### 7.1 API Utility
```javascript
// src/utils/api.js
export async function saveAttempt(attemptData) {
  const response = await fetch('/wp-admin/admin-ajax.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/x-www-form-urlencoded',
    },
    body: new URLSearchParams({
      action: 'hb_cog_save_attempt',
      attempt: JSON.stringify(attemptData),
    }),
  });
  
  return response.json();
}
```

### 7.2 שימוש ב-API
```javascript
// במשחק
import { saveAttempt } from '../utils/api';

const handleFinish = async () => {
  const result = await saveAttempt({
    game_id: 'go_nogo',
    score: score,
    // ...
  });
  
  if (result.success) {
    // הצג תוצאות
  }
};
```

---

## שלב 8: אנימציות

### 8.1 התקנת Framer Motion
```bash
npm install framer-motion
```

### 8.2 שימוש באנימציות
```javascript
import { motion } from 'framer-motion';

function GoNoGo() {
  return (
    <motion.div
      initial={{ opacity: 0, y: 20 }}
      animate={{ opacity: 1, y: 0 }}
      transition={{ duration: 0.5 }}
    >
      <motion.button
        whileHover={{ scale: 1.05 }}
        whileTap={{ scale: 0.95 }}
        onClick={handleStart}
      >
        התחל אימון
      </motion.button>
    </motion.div>
  );
}
```

---

## שלב 9: בדיקות

### 9.1 בדיקת Build
```bash
npm run build
```

### 9.2 בדיקת Development
```bash
npm start
```

### 9.3 בדיקת WordPress
1. העתק קבצים מ-`build/` ל-WordPress
2. בדוק שהמשחקים עובדים
3. בדוק API calls

---

## שלב 10: פריסה

### 10.1 Build Script
```json
// package.json
{
  "scripts": {
    "build": "react-scripts build",
    "build:wordpress": "react-scripts build && cp -r build/* ../wp-content/plugins/hb-cog-training/assets/react/"
  }
}
```

### 10.2 אוטומציה
```bash
npm run build:wordpress
```

---

## סיכום

### **השלבים:**
1. ✅ התקן Node.js
2. ✅ צור פרויקט React
3. ✅ המר משחקים ל-Components
4. ✅ צור Components משותפים
5. ✅ הוסף State Management
6. ✅ אינטגר עם WordPress
7. ✅ הוסף API Calls
8. ✅ הוסף אנימציות
9. ✅ בדוק הכל
10. ✅ פרוס

### **זמן משוער:**
- **הכנה**: 30 דקות
- **המרת משחק אחד**: 2-3 שעות
- **אינטגרציה**: 1-2 שעות
- **סה"כ**: 1-2 ימים עבודה

### **הערות:**
- זה תהליך הדרגתי - אפשר להמיר משחק אחד בכל פעם
- אפשר לשמור על Vanilla JS למשחקים קיימים
- אפשר לערבב - חלק React, חלק Vanilla JS

---

## המלצה

**לפרויקט שלך**: אני ממליץ **לא** לעבור ל-React עכשיו, אלא:
1. לשפר את מה שיש (Vanilla JS)
2. להוסיף אנימציות ועיצוב
3. לשקול React רק אם הפרויקט יגדל משמעותית

**אבל אם אתה רוצה**: המדריך הזה מראה איך לעשות את זה בפועל.
