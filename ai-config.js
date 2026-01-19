/**
 * AI Configuration for Done-It Dual-Mode Platform
 * 
 * This file contains system prompts and AI configuration for:
 * - Business Mode: Current Done-It functionality
 * - Pleasure Mode: Danit's Digital Journal
 */

// ============================================
// PLEASURE MODE - Danit's Digital Journal
// ============================================

/**
 * System Prompt for Danit's Journal AI
 * 
 * This prompt ensures the AI maintains:
 * - First-person narrative (Danit's voice)
 * - Contextual continuity across entries
 * - Elegant, luxury aesthetic in writing style
 * - Hebrew (RTL) language support
 * - Cohesive story flow over time
 */
export const DANIT_JOURNAL_SYSTEM_PROMPT = `
את דנית, ואת כותבת יומן דיגיטלי אישי ואינטימי.

סגנון הכתיבה שלך:
- כתיבה בגוף ראשון (אני, שלי, לי)
- אלגנטי, מינימליסטי, יוקרתי
- רגשי ומעמיק אבל לא דרמטי מדי
- טבעי וזורם, כמו שיחה עם עצמך
- בעברית, עם כיוון RTL

המשימה שלך:
- ליצור נרטיב רציף ומתפתח מחיי דנית
- כל תמונה או סרטון שמוספים צריך להשתלב בסיפור הקיים
- לשמור על המשכיות סגנונית ותוכניתית
- לזכור את הסגנון והערכים הקודמים כדי ליצור סיפור זורם לאורך זמן
- ליצור חיבור בין כניסות שונות, גם אם הן מתרחשות בימים שונים

כשמוסיפים מדיה חדשה:
- תסתכלי על התמונה/סרטון ותביני מה קורה
- תשלחי את זה להקשר של הכניסות הקודמות ביומן
- תכתבי טקסט שמתחבר לסיפור הקיים אבל גם מוסיף משהו חדש
- תשמרי על קול אחיד ועקבי של דנית

זכרי: זה לא רק גלריה - זה סיפור חיים מתמשך ומתפתח.
`;

/**
 * Configuration for Journal AI behavior
 */
export const DANIT_JOURNAL_CONFIG = {
    // Language settings
    language: 'he',
    direction: 'rtl',
    
    // Writing style parameters
    style: {
        tone: 'elegant', // elegant, intimate, reflective
        perspective: 'first-person', // Always first person
        formality: 'casual-intimate', // casual-intimate, formal, poetic
    },
    
    // Continuity settings
    continuity: {
        rememberPreviousEntries: true,
        maintainStyleConsistency: true,
        createNarrativeFlow: true,
        maxContextEntries: 50, // How many previous entries to consider
    },
    
    // Media handling
    media: {
        analyzeImages: true,
        analyzeVideos: true,
        generateDescriptions: true,
        createNarrativeConnections: true,
    }
};

// ============================================
// BUSINESS MODE - Done-It Functionality
// ============================================

/**
 * System Prompt for Business Mode AI (if needed in future)
 */
export const BUSINESS_MODE_SYSTEM_PROMPT = `
את עוזר AI לאפליקציית Done-It - מערכת ניהול אישית.

תפקידך:
- לעזור למשתמשים לנהל משימות, פתקים ואפליקציות
- לספק תובנות ועצות פרודוקטיביות
- לשמור על טון מקצועי אבל ידידותי
- בעברית, עם כיוון RTL
`;

/**
 * Configuration for Business Mode AI behavior
 */
export const BUSINESS_MODE_CONFIG = {
    language: 'he',
    direction: 'rtl',
    tone: 'professional-friendly',
};

// ============================================
// GENERAL AI CONFIGURATION
// ============================================

/**
 * General AI settings
 */
export const AI_CONFIG = {
    // API Settings (loaded from .env)
    apiKey: process.env.OPENAI_API_KEY || '',
    
    // Model selection
    defaultModel: 'gpt-4o-mini', // Cost-effective for most tasks
    journalModel: 'gpt-4o', // Better for creative/narrative tasks
    visionModel: 'gpt-4o', // For image analysis
    
    // Temperature settings (creativity vs consistency)
    temperature: {
        journal: 0.8, // More creative for journal entries
        business: 0.3, // More consistent for business tasks
    },
    
    // Max tokens per response
    maxTokens: {
        journal: 1000, // Longer responses for journal
        business: 500, // Shorter for business
    },
    
    // Retry settings
    retryAttempts: 3,
    retryDelay: 1000, // milliseconds
};

// ============================================
// EXPORTS
// ============================================

export default {
    DANIT_JOURNAL_SYSTEM_PROMPT,
    DANIT_JOURNAL_CONFIG,
    BUSINESS_MODE_SYSTEM_PROMPT,
    BUSINESS_MODE_CONFIG,
    AI_CONFIG,
};
