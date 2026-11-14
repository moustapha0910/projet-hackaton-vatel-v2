<?php
// === Réglages SaveurIA ===

// --- Sommelier IA (chat) ---
// Fournisseur : 'auto' (Gemini si une clé Gemini est renseignée, sinon Claude), 'gemini' ou 'anthropic'.
// Aucune clé renseignée = le chat répond avec des mots-clés (gratuit, sans Internet).
const AI_PROVIDER = 'auto';

// Gemini (niveau gratuit) : créez une clé dans Google AI Studio (aistudio.google.com, bouton « Get API key »).
const GEMINI_KEY   = '';
const GEMINI_MODEL = 'gemini-flash-latest';   // à changer si Google retire ce nom (voir la liste des modèles dans AI Studio)

// Claude (payant) : clé créée dans la Claude Console.
const ANTHROPIC_KEY   = '';
const ANTHROPIC_MODEL = 'claude-haiku-5-5';

// Si XAMPP affiche une erreur de certificat SSL, passez à false (ou installez le bundle de certificats cURL).
const VERIFY_SSL = true;

// Code PIN pour l'espace cuisine (laisser vide = accès libre, pratique pour la démo).
const KITCHEN_PIN = '';

// --- Administration (admin.php) ---
// Mot de passe de l'espace admin : CHANGEZ-LE avant la démo.
const ADMIN_PASSWORD = 'admin123';
// Adresse publique utilisée dans les QR codes (ex. 'http://192.168.1.20/saveuria'). Vide = adresse actuelle du navigateur.
// Pour que les téléphones puissent scanner, utilisez l'IP de votre PC, pas localhost.
const PUBLIC_URL = '';
