/**
 * הגדרות מסלול גיל שלישי
 */
export const CONFIG_SENIOR = {
  track: 'senior',
  
  // הגדרות קושי למשחק Go/No-Go
  go_nogo: {
    difficulty: {
      1: {
        interval_min_ms: 1400,
        interval_max_ms: 1900,
        stimulus_duration_ms: 950
      },
      2: {
        interval_min_ms: 1300,
        interval_max_ms: 1800,
        stimulus_duration_ms: 900
      },
      3: {
        interval_min_ms: 1200,
        interval_max_ms: 1700,
        stimulus_duration_ms: 850
      },
      4: {
        interval_min_ms: 1100,
        interval_max_ms: 1600,
        stimulus_duration_ms: 800
      },
      5: {
        interval_min_ms: 1000,
        interval_max_ms: 1500,
        stimulus_duration_ms: 750
      }
    },
    
    // פרמטרים כלליים
    session_duration_ms: 90000, // 90 שניות
    go_ratio: 0.7, // 70% GO
    no_go_ratio: 0.3, // 30% NO-GO
    
    // ספי קושי אדפטיבי
    adaptive: {
      accuracy_threshold_up: 0.85, // אם דיוק >= 0.85 ב-2 משחקים רצופים -> קושי +1
      accuracy_threshold_down: 0.60, // אם דיוק < 0.60 -> קושי -1
      consecutive_sessions_up: 2
    },
    
    // ספי מהירות לנרמול
    speed_bounds: {
      min_rt_ms: 300, // הכי מהיר
      max_rt_ms: 1100 // הכי איטי
    },
    
    // סף יציבות
    stability: {
      cv_target: 0.45 // coefficient of variation יעד
    }
  },
  
  // משקולות ציון למסלול גיל שלישי
  scoring_weights: {
    accuracy: 0.55,
    speed: 0.30,
    stability: 0.15
  },
  
  // מיפוי תחומי יכולת למשחקים
  domain_mapping: {
    go_nogo: {
      attention: 0.6,
      reasoning_flexibility: 0.0,
      processing_speed: 0.2,
      working_memory: 0.0,
      visual_perception: 0.0,
      inhibition: 0.2 // אם לא קיים, נשתמש ב-attention
    }
  },
  
  // תוויות מהירות
  speed_labels: {
    slow: { min: 0, max: 33, label: 'רגוע' },
    good: { min: 34, max: 66, label: 'טוב' },
    fast: { min: 67, max: 100, label: 'מהיר' }
  }
};


