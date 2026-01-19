/**
 * Node.js API Proxy for OpenAI + File Storage
 * 
 * Installation:
 * npm install express dotenv cors openai
 * 
 * Usage:
 * node api-proxy.js
 * 
 * The server will run on http://localhost:3000
 */

const express = require('express');
const cors = require('cors');
const dotenv = require('dotenv');
const OpenAI = require('openai');
const fs = require('fs').promises;
const path = require('path');

// Load environment variables from SETUP-AI-BACKEND file
require('dotenv').config({ path: './SETUP-AI-BACKEND' });

const app = express();
const PORT = 3000;

// Initialize OpenAI client
const openai = new OpenAI({
    apiKey: process.env.OPENAI_API_KEY
});

// Verify API key is loaded
if (!process.env.OPENAI_API_KEY) {
    console.error('❌ ERROR: OPENAI_API_KEY not found in SETUP-AI-BACKEND file');
    console.error('Please ensure SETUP-AI-BACKEND file exists and contains: OPENAI_API_KEY=your-key-here');
} else {
    console.log('✅ OpenAI API Key loaded successfully from SETUP-AI-BACKEND');
}

// Increase body size limit to handle large files
app.use(cors());
app.use(express.json({ limit: '50mb' }));
app.use(express.urlencoded({ limit: '50mb', extended: true }));

// Serve static files from assets/journal directory
const assetsDir = path.join(__dirname, 'assets', 'journal');
app.use('/assets/journal', express.static(assetsDir));

// Ensure assets/journal directory exists
async function ensureAssetsDir() {
    try {
        await fs.mkdir(assetsDir, { recursive: true });
        console.log('✅ Assets directory ready:', assetsDir);
    } catch (error) {
        console.error('❌ Error creating assets directory:', error);
    }
}

ensureAssetsDir();

// Helper function to save Base64 image/video to file
async function saveMediaFile(base64Data, mediaType) {
    try {
        // Extract base64 data (remove data:image/jpeg;base64, prefix)
        const base64Match = base64Data.match(/^data:([^;]+);base64,(.+)$/);
        if (!base64Match) {
            throw new Error('Invalid base64 data format');
        }
        
        const mimeType = base64Match[1];
        const base64Content = base64Match[2];
        
        // Determine file extension
        let extension = 'jpg';
        if (mimeType.includes('png')) extension = 'png';
        else if (mimeType.includes('gif')) extension = 'gif';
        else if (mimeType.includes('webp')) extension = 'webp';
        else if (mimeType.includes('video/mp4')) extension = 'mp4';
        else if (mimeType.includes('video/webm')) extension = 'webm';
        else if (mimeType.includes('video/quicktime')) extension = 'mov';
        
        // Generate unique filename
        const filename = `${Date.now()}-${Math.random().toString(36).substring(7)}.${extension}`;
        const filePath = path.join(assetsDir, filename);
        
        // Convert base64 to buffer and save
        const buffer = Buffer.from(base64Content, 'base64');
        await fs.writeFile(filePath, buffer);
        
        // Return relative path for frontend
        return `/assets/journal/${filename}`;
    } catch (error) {
        console.error('❌ Error saving media file:', error);
        throw error;
    }
}

// API endpoint for uploading media files
app.post('/api/upload-media', async (req, res) => {
    try {
        const { base64Data, mediaType } = req.body;
        
        if (!base64Data) {
            return res.status(400).json({ error: 'No base64 data provided' });
        }
        
        const filePath = await saveMediaFile(base64Data, mediaType);
        
        console.log('✅ Media file saved:', filePath);
        res.json({ filePath: filePath });
    } catch (error) {
        console.error('❌ Error uploading media:', error);
        res.status(500).json({ error: error.message });
    }
});

// API endpoint for generating story
app.post('/api/generate-story', async (req, res) => {
    try {
        const { personalNotes, mediaType, previousEntries, systemPrompt, globalPrompt, albumBasePrompt } = req.body;
        
        if (!process.env.OPENAI_API_KEY) {
            return res.status(500).json({ error: 'API key not configured' });
        }

        // Build context from previous entries (date format: DD/MM/YYYY, no time)
        const context = previousEntries.map(e => {
            const entryDate = new Date(e.date);
            const formattedDate = entryDate.toLocaleDateString('he-IL').replace(/\./g, '/');
            return `תאריך: ${formattedDate}\nהערות: ${e.personalNotes || ''}\nסיפור: ${e.narrative || ''}`;
        }).join('\n\n---\n\n');

        // Combine system prompts: globalPrompt + albumBasePrompt
        let finalSystemPrompt = '';
        
        if (systemPrompt) {
            finalSystemPrompt = systemPrompt;
        } else {
            const basePrompt = globalPrompt || `את דנית, ואת כותבת יומן דיגיטלי אישי ואינטימי.

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

זכרי: זה לא רק גלריה - זה סיפור חיים מתמשך ומתפתח.`;
            
            finalSystemPrompt = basePrompt;
            if (albumBasePrompt && albumBasePrompt.trim()) {
                finalSystemPrompt = basePrompt + '\n\n' + albumBasePrompt.trim();
            }
        }

        // Build user prompt with personal notes
        const userPrompt = personalNotes ? 
            `הערות אישיות: ${personalNotes}\n\n${mediaType === 'image' ? 'תמונה' : mediaType === 'video' ? 'סרטון' : 'טקסט'} חדש נוסף ליומן. ${context ? `\n\nהכניסות הקודמות:\n${context}` : ''}\n\nצרי סיפור יפה בגוף ראשון שמתחבר לכניסות הקודמות.` :
            `${mediaType === 'image' ? 'תמונה' : mediaType === 'video' ? 'סרטון' : 'טקסט'} חדש נוסף ליומן. ${context ? `\n\nהכניסות הקודמות:\n${context}` : ''}\n\nצרי סיפור יפה בגוף ראשון שמתחבר לכניסות הקודמות.`;

        // Log the full prompt for debugging - REQUIRED
        console.log('='.repeat(80));
        console.log('OPENAI PROMPT:');
        console.log('='.repeat(80));
        console.log('\n[SYSTEM PROMPT]:');
        console.log(finalSystemPrompt);
        console.log('\n[USER PROMPT]:');
        console.log(userPrompt);
        console.log('='.repeat(80));

        // Use OpenAI SDK v4+ (openai.chat.completions.create)
        const completion = await openai.chat.completions.create({
            model: 'gpt-4o',
            messages: [
                { role: 'system', content: finalSystemPrompt },
                { role: 'user', content: userPrompt }
            ],
            temperature: 0.8,
            max_tokens: 1000
        });

        const narrative = completion.choices[0].message.content.trim();
        
        // Log response - REQUIRED
        console.log('OPENAI RESPONSE:', narrative);
        console.log('='.repeat(80));
        
        res.json({ narrative: narrative });

    } catch (error) {
        console.error('❌ Error generating story:', error);
        res.status(500).json({ error: error.message });
    }
});

// API endpoint for loading data
app.get('/api/load-data', async (req, res) => {
    try {
        const dataPath = path.join(__dirname, 'data.json');
        
        try {
            const data = await fs.readFile(dataPath, 'utf8');
            const parsed = JSON.parse(data);
            res.json(parsed);
        } catch (error) {
            // If file doesn't exist, return empty structure
            res.json({
                journal: {
                    albums: [],
                    entries: [],
                    settings: {}
                },
                business: {
                    tasks: [],
                    notes: [],
                    apps: []
                }
            });
        }
    } catch (error) {
        console.error('❌ Error loading data:', error);
        res.status(500).json({ error: error.message });
    }
});

// API endpoint for deleting media file
app.delete('/api/delete-media', async (req, res) => {
    try {
        const { filePath } = req.body;
        
        if (!filePath) {
            return res.status(400).json({ error: 'No file path provided' });
        }
        
        // Extract filename from path (e.g., /assets/journal/image.jpg -> image.jpg)
        const filename = path.basename(filePath);
        const filePathToDelete = path.join(assetsDir, filename);
        
        try {
            await fs.unlink(filePathToDelete);
            console.log('✅ Media file deleted:', filePathToDelete);
            res.json({ success: true, message: 'File deleted successfully' });
        } catch (error) {
            // File might not exist, that's okay
            if (error.code === 'ENOENT') {
                console.log('⚠️ File not found (already deleted?):', filePathToDelete);
                res.json({ success: true, message: 'File not found (may already be deleted)' });
            } else {
                throw error;
            }
        }
    } catch (error) {
        console.error('❌ Error deleting media:', error);
        res.status(500).json({ error: error.message });
    }
});

// API endpoint for saving data
app.post('/api/save-data', async (req, res) => {
    try {
        const { albums, entries, settings } = req.body;
        const dataPath = path.join(__dirname, 'data.json');
        
        // Validate data structure
        if (!albums || !entries || !settings) {
            console.error('Missing required data fields:', { albums: !!albums, entries: !!entries, settings: !!settings });
            return res.status(400).json({ error: 'Missing required data fields' });
        }
        
        const data = {
            journal: {
                albums: Array.isArray(albums) ? albums : [],
                entries: Array.isArray(entries) ? entries : [],
                settings: settings || {}
            },
            business: {
                tasks: [],
                notes: [],
                apps: []
            }
        };
        
        // Write file with error handling
        try {
            await fs.writeFile(dataPath, JSON.stringify(data, null, 2), 'utf8');
            console.log('✅ Data saved successfully:', {
                albums: data.journal.albums.length,
                entries: data.journal.entries.length
            });
            res.json({ success: true, message: 'Data saved successfully' });
        } catch (writeError) {
            console.error('❌ File write error:', writeError);
            throw new Error(`Failed to write file: ${writeError.message}`);
        }
    } catch (error) {
        console.error('❌ Error saving data:', error);
        res.status(500).json({ error: error.message || 'Unknown error occurred' });
    }
});

app.listen(PORT, () => {
    console.log(`🚀 API Proxy server running on http://localhost:${PORT}`);
    console.log(`📁 Assets directory: ${assetsDir}`);
    console.log(`🔑 API key loaded from SETUP-AI-BACKEND file`);
});
