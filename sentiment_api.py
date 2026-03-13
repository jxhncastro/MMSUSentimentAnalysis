# --- 1. INSTALL REQUIREMENTS ---
import torch
import re
from transformers import AutoTokenizer, AutoModelForSequenceClassification, pipeline
from fastapi import FastAPI
from pydantic import BaseModel

# --- 2. CONFIGURATION ---
MODEL_PATH = "./mmsu_model_optimized_v3"

# Define your specific themes here! You can add or remove any category.
THEMATIC_CATEGORIES = [
    "Service", 
    "Reliability", 
    "Facilities", 
    "Manpower",
    "Academics",
    "Enrollment"
]

# --- 3. LEXICON GUARDS ---

# NEUTRAL: Catches "None", "N/A", "Wala"
neutral_keywords = ['none', 'nothing', 'n/a', 'nil', 'wala', 'awan', 'no suggestion', 'no comment', 'ok', 'okay', 'n.a', 'na']
neutral_regex = re.compile(r'\b(' + '|'.join(map(re.escape, neutral_keywords)) + r')\b', re.IGNORECASE)

# NEGATIVE: Catches bad words
negative_keywords = [
    'rude', 'bad', 'slow', 'nabuntog', 'madi', 'bastos', 'attitude', 'worst',
    'poor', 'pait', 'unprofessional', 'masungit', 'mataray', 'nag-unget', 'nayunget',
    'disappointing', 'terrible', 'awful', 'bagal', 'mabagal',
    'pangit', 'cirig', 'baho', 'dirty', 'messy'
]
negative_regex = re.compile(r'\b(' + '|'.join(map(re.escape, negative_keywords)) + r')\b', re.IGNORECASE)

# POSITIVE SAVERS: Protects context
positive_savers = [
    'not rude', 'not bad', 'not ugly',
    'good', 'great', 'best', 'excellent', 'fast', 'love', 'thank',
    'pintas', 'sayaat', 'napintas', 'salamat', 'mabuhay',
    'haan nga pangit', 'di pangit', 'hindi pangit',
    'haan nga bastos', 'di bastos', 'hindi bastos',
    'haan nga madi', 'di madi', 'hindi madi'
]
saver_regex = re.compile(r'\b(' + '|'.join(map(re.escape, positive_savers)) + r')\b', re.IGNORECASE)

# POSITIVE OVERRIDE: Forces Positive for "No improvement needed" scenarios
positive_override_phrases = [
    'no improvement', 'no suggestion', 'none so far', 'keep it up',
    'good job', 'nothing to improve',
    'does not need improvement', 'no need for improvement',
    'satisfied', 'great service', 'excellent',
    'does not need any improvement', 'no need to improve'
]
positive_override_regex = re.compile(r'\b(' + '|'.join(map(re.escape, positive_override_phrases)) + r')\b', re.IGNORECASE)

# --- 4. LOAD MODELS ---
print("🧠 Loading MMSU Sentiment AI Model... (Please wait)")
device = "cuda" if torch.cuda.is_available() else "cpu"
# For the pipeline, device 0 is GPU, -1 is CPU
pipeline_device = 0 if device == "cuda" else -1 

try:
    # 1. Load your custom Sentiment Model
    tokenizer = AutoTokenizer.from_pretrained(MODEL_PATH)
    model = AutoModelForSequenceClassification.from_pretrained(MODEL_PATH).to(device)
    model.eval()
    
    # 2. Load the Zero-Shot Thematic Model (Downloads ~1.6GB the very first time it runs)
    print("🎯 Loading Thematic Classifier...")
    theme_classifier = pipeline("zero-shot-classification", model="facebook/bart-large-mnli", device=pipeline_device)
    
    print(f"✅ Both Models Loaded Successfully on {device.upper()}")
except Exception as e:
    print(f"❌ Error Loading Model: {e}")
    print("⚠️ Make sure you downloaded the 'mmsu_model_optimized_v3' folder from Google Drive and put it next to this file!")
    raise e

# --- 5. DEFINE API ---
app = FastAPI()

class RequestData(BaseModel):
    text: str
    aspect: str = "general feedback"

def analyze_logic(text, aspect):
    # 1. Normalize
    text = str(text).strip().lower()

    # 2. FIXED CONTRACTION EXPANSION
    contractions = {
        r"\bdoesn'?t\b": "does not", r"\bdon'?t\b": "do not", r"\bwon'?t\b": "will not",
        r"\bcan'?t\b": "cannot", r"\bisn'?t\b": "is not", r"\bwouldn'?t\b": "would not",
        r"\bcouldn'?t\b": "could not", r"\bdidn'?t\b": "did not", r"\bhaven'?t\b": "have not",
        r"\bhasn'?t\b": "has not", r"\bshouldn'?t\b": "should not"
    }

    for pattern, replacement in contractions.items():
        text = re.sub(pattern, replacement, text)

    text_content = text

    # --- THEMATIC ANALYSIS (Runs on every valid text) ---
    # We run this first so we can attach it to the Lexicon overrides too!
    predicted_theme = "General"
    if len(text_content) > 5:  # Only theme it if it's an actual sentence
        theme_result = theme_classifier(text_content, THEMATIC_CATEGORIES)
        # Check if the highest score is confident enough
        if theme_result['scores'][0] > 0.25:
            predicted_theme = theme_result['labels'][0].upper()

    # --- RULE 3: POSITIVE OVERRIDE ---
    if positive_override_regex.search(text_content):
        return {"sentiment": "Positive", "confidence": 100.0, "method": "Lexicon_Positive_Override", "aspect": aspect, "theme": predicted_theme}

    # --- RULE 4: NEGATIVE CHECK ---
    is_saved = saver_regex.search(text_content)
    if negative_regex.search(text_content) and not is_saved:
        return {"sentiment": "Negative", "confidence": 99.9, "method": "Lexicon_Negative", "aspect": aspect, "theme": predicted_theme}

    # --- RULE 5: NEUTRAL CHECK ---
    if neutral_regex.search(text_content) and len(text_content) < 50:
        return {"sentiment": "Neutral", "confidence": 100.0, "method": "Lexicon_Neutral", "aspect": aspect, "theme": "GENERAL"}

    # --- 6. AI PREDICTION ---
    try:
        inputs = tokenizer(f"{aspect} </s> {text_content}", return_tensors="pt", truncation=True, max_length=256).to(device)
        with torch.no_grad():
            outputs = model(**inputs)
            probs = torch.nn.functional.softmax(outputs.logits, dim=-1)

        labels = ["Negative", "Neutral", "Positive"]
        pred_idx = torch.argmax(probs).item()

        return {
            "sentiment": labels[pred_idx],
            "confidence": round(probs[0][pred_idx].item() * 100, 2),
            "method": "MMSU_Transformer_v3",
            "aspect": aspect,
            "theme": predicted_theme # Attached here!
        }
    except Exception as e:
        return {"sentiment": "Neutral", "confidence": 0.0, "error": str(e), "aspect": aspect, "theme": "ERROR"}

@app.post("/predict")
def predict_endpoint(data: RequestData):
    return analyze_logic(data.text, data.aspect)