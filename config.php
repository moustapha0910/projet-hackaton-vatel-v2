<?php
// === RÃƒÂ©glages SaveurIA ===

// --- Sommelier IA (chat) ---
// Fournisseur : 'auto' (Groq gratuit en prioritÃƒÂ©, sinon Gemini, sinon Claude), 'groq', 'gemini' ou 'anthropic'.
// Aucune clÃƒÂ© renseignÃƒÂ©e = le chat rÃƒÂ©pond avec des mots-clÃƒÂ©s (gratuit, sans Internet).
const AI_PROVIDER = 'auto';

// Groq (GRATUIT, modÃƒÂ¨les open-source) : crÃƒÂ©ez une clÃƒÂ© gratuite sur console.groq.com (Ã‚Â« API Keys Ã‚Â»).
const GROQ_KEY   = '';
const GROQ_MODEL = 'openai/gpt-oss-20b';   // aussi : openai/gpt-oss-120b, qwen/qwen3.8-27b, allam-2-7b

// Gemini (niveau gratuit) : crÃƒÂ©ez une clÃƒÂ© dans Google AI Studio (aistudio.google.com, bouton Ã‚Â« Get API key Ã‚Â»).
const GEMINI_KEY   = '';
const GEMINI_MODEL = 'gemini-flash-latest';   // ÃƒÂ  changer si Google retire ce nom (voir la liste des modÃƒÂ¨les dans AI Studio)

// Claude (payant) : clÃƒÂ© crÃƒÂ©ÃƒÂ©e dans la Claude Console.
const ANTHROPIC_KEY   = '';
const ANTHROPIC_MODEL = 'claude-haiku-5-5';

// Si XAMPP affiche une erreur de certificat SSL, passez ÃƒÂ  false (ou installez le bundle de certificats cURL).
const VERIFY_SSL = true;

// Code PIN pour l'espace cuisine (laisser vide = accÃƒÂ¨s libre, pratique pour la dÃƒÂ©mo).
const KITCHEN_PIN = '';

// --- Administration (admin.php) ---
// Mot de passe de l'espace admin : CHANGEZ-LE avant la dÃƒÂ©mo.
const ADMIN_PASSWORD = 'admin123';
// Adresse publique utilisÃƒÂ©e dans les QR codes (ex. 'http://192.168.1.20/saveuria'). Vide = adresse actuelle du navigateur.
// Pour que les tÃƒÂ©lÃƒÂ©phones puissent scanner, utilisez l'IP de votre PC, pas localhost.
const PUBLIC_URL = '';
