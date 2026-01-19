/**
 * מערכת ציונים משוקללת
 */

// Dynamic import - עובד טוב יותר עם WordPress
let CONFIG_SENIOR = null;

async function loadConfig() {
  if (CONFIG_SENIOR) return CONFIG_SENIOR;
  
  try {
    const baseUrl = typeof hb_cog_vars !== 'undefined' && hb_cog_vars.base_url 
      ? hb_cog_vars.base_url 
      : new URL('.', import.meta.url).href;
    
    const configModule = await import(new URL('config_senior.js', baseUrl).href);
    CONFIG_SENIOR = configModule.CONFIG_SENIOR;
    return CONFIG_SENIOR;
  } catch(e) {
    console.error('Failed to load CONFIG_SENIOR:', e);
    return null;
  }
}

// טעינה מיידית
loadConfig();

/**
 * חישוב ציון משחק
 * @param {Object} metrics - מדדים: {accuracy, mean_rt_ms, stability01}
 * @param {Object} diffConfig - הגדרות קושי
 * @returns {Object} {game_score, accuracyScore, speedScore, stabilityScore}
 */
export async function computeGameScore(metrics, diffConfig) {
  const config = await loadConfig();
  if (!config) {
    console.error('CONFIG_SENIOR not loaded');
    return { game_score: 0, accuracyScore: 0, speedScore: 0, stabilityScore: 0 };
  }
  const weights = config.scoring_weights;
  
  // ציון דיוק (0-100)
  const accuracyScore = Math.round(metrics.accuracy * 100);
  
  // ציון מהירות (0-100) - נרמול בין min/max
  const speedBounds = config.go_nogo.speed_bounds;
  let speedScore = 0;
  if (metrics.mean_rt_ms && metrics.mean_rt_ms > 0) {
    const normalized = (speedBounds.max_rt_ms - metrics.mean_rt_ms) / 
                       (speedBounds.max_rt_ms - speedBounds.min_rt_ms);
    speedScore = Math.max(0, Math.min(100, Math.round(normalized * 100)));
  }
  
  // ציון יציבות (0-100)
  const stabilityScore = Math.round((metrics.stability01 || 0) * 100);
  
  // ציון סופי משוקלל
  const gameScore = Math.round(
    accuracyScore * weights.accuracy +
    speedScore * weights.speed +
    stabilityScore * weights.stability
  );
  
  return {
    game_score: Math.max(0, Math.min(100, gameScore)),
    accuracyScore,
    speedScore,
    stabilityScore
  };
}

/**
 * חישוב תרומה לתחומי יכולת
 * @param {number} gameScore - ציון המשחק
 * @param {Object} domainWeights - משקולות תחומים (מ-CONFIG_SENIOR.domain_mapping)
 * @returns {Object} תרומה לכל תחום
 */
export async function computeDomainContrib(gameScore, domainWeights) {
  const contrib = {};
  
  for (const [domain, weight] of Object.entries(domainWeights)) {
    contrib[domain] = Math.round(gameScore * weight);
  }
  
  return contrib;
}

/**
 * קבלת תווית מהירות לפי ציון
 * @param {number} speedScore - ציון מהירות 0-100
 * @returns {string} תווית
 */
export async function getSpeedLabel(speedScore) {
  const config = await loadConfig();
  if (!config) return 'טוב';
  const labels = config.speed_labels;
  
  if (speedScore >= labels.fast.min) {
    return labels.fast.label;
  } else if (speedScore >= labels.good.min) {
    return labels.good.label;
  } else {
    return labels.slow.label;
  }
}


