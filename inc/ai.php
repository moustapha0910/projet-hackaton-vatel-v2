<?php
require_once __DIR__ . '/../config.php';
function ai_provider() {
  $p = defined('AI_PROVIDER') ? AI_PROVIDER : 'auto';
  if ($p === 'groq') return GROQ_KEY !== '' ? 'groq' : '';
  if ($p === 'gemini') return GEMINI_KEY !== '' ? 'gemini' : '';
  if ($p === 'anthropic') return ANTHROPIC_KEY !== '' ? 'anthropic' : '';
  if (GROQ_KEY !== '') return 'groq';
  if (GEMINI_KEY !== '') return 'gemini';
  return ANTHROPIC_KEY !== '' ? 'anthropic' : '';
}
function gemini_model() { return (($_GET['a'] ?? '') === 'aitest' && !empty($_GET['model'])) ? preg_replace('/[^A-Za-z0-9._-]/', '', $_GET['model']) : GEMINI_MODEL; }
function gemini_models(&$err = null) {
  $ch = curl_init('https://generativelanguage.googleapis.com/v1beta/models?pageSize=200');
  curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => 1, CURLOPT_TIMEOUT => 30, CURLOPT_CONNECTTIMEOUT => 8, CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4, CURLOPT_SSL_VERIFYPEER => VERIFY_SSL, CURLOPT_HTTPHEADER => ['x-goog-api-key: ' . GEMINI_KEY]]);
  $raw = curl_exec($ch); $code = curl_getinfo($ch, CURLINFO_HTTP_CODE); $ce = curl_error($ch); curl_close($ch);
  $r = json_decode((string)$raw, true); $out = [];
  foreach (($r['models'] ?? []) as $m) if (in_array('generateContent', $m['supportedGenerationMethods'] ?? [])) $out[] = preg_replace('#^models/#', '', $m['name']);
  if (!$out) $err = 'HTTP ' . $code . ' ' . ($ce ?: mb_substr((string)$raw, 0, 300));
  return $out;
}
function ai_call($prov, $sys, $msgs, &$err = null, $timeout = 15) {
  if ($prov === 'groq') {
    $url = 'https://api.groq.com/openai/v1/chat/completions';
    $body = ['model' => GROQ_MODEL, 'messages' => array_merge([['role' => 'system', 'content' => $sys]], $msgs), 'max_tokens' => 600, 'temperature' => 0.6];
    $headers = ['content-type: application/json', 'authorization: Bearer ' . GROQ_KEY];
  } elseif ($prov === 'gemini') {
    $url = 'https://generativelanguage.googleapis.com/v1beta/models/' . rawurlencode(gemini_model()) . ':generateContent';
    $contents = array_map(fn($m) => ['role' => $m['role'] === 'assistant' ? 'model' : 'user', 'parts' => [['text' => $m['content']]]], $msgs);
    $body = ['systemInstruction' => ['parts' => [['text' => $sys]]], 'contents' => $contents,
             'generationConfig' => ['maxOutputTokens' => 2000, 'temperature' => 0.6, 'responseMimeType' => 'application/json']];
    $headers = ['content-type: application/json', 'x-goog-api-key: ' . GEMINI_KEY];
  } else {
    $url = 'https://api.anthropic.com/v1/messages';
    $body = ['model' => ANTHROPIC_MODEL, 'max_tokens' => 600, 'system' => $sys, 'messages' => $msgs];
    $headers = ['content-type: application/json', 'x-api-key: ' . ANTHROPIC_KEY, 'anthropic-version: 2023-06-01'];
  }
  $ch = curl_init($url);
  curl_setopt_array($ch, [CURLOPT_POST => 1, CURLOPT_POSTFIELDS => json_encode($body, JSON_UNESCAPED_UNICODE), CURLOPT_RETURNTRANSFER => 1,
    CURLOPT_TIMEOUT => $timeout, CURLOPT_CONNECTTIMEOUT => 8, CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4, CURLOPT_SSL_VERIFYPEER => VERIFY_SSL, CURLOPT_HTTPHEADER => $headers]);
  $raw = curl_exec($ch); $code = curl_getinfo($ch, CURLINFO_HTTP_CODE); $ce = curl_error($ch); curl_close($ch);
  $r = json_decode((string)$raw, true);
  $txt = $prov === 'groq' ? (string)($r['choices'][0]['message']['content'] ?? '')
       : ($prov === 'gemini' ? (string)($r['candidates'][0]['content']['parts'][0]['text'] ?? '')
       : (string)($r['content'][0]['text'] ?? ''));
  if ($txt === '') $err = 'HTTP ' . $code . ' ' . ($ce ?: mb_substr(json_encode($r['error'] ?? $raw, JSON_UNESCAPED_UNICODE), 0, 300));
  return $txt;
}
