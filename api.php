<?php
// Stockage partagé : commandes, carte et avis dans data/state.json
header('Content-Type: application/json; charset=utf-8');
$dir = __DIR__ . '/data'; $file = $dir . '/state.json';
if (!is_dir($dir)) mkdir($dir, 0777, true);
$a = $_GET['a'] ?? 'load';
if ($a === 'aitest') {
  require_once __DIR__ . '/inc/ai.php'; $p = ai_provider();
  if ($p === 'gemini' && isset($_GET['list'])) { $e = null; echo json_encode(['models' => gemini_models($e), 'error' => $e], JSON_UNESCAPED_UNICODE); exit; }
  if (!$p) { echo json_encode(['provider' => null, 'info' => 'Aucune clé renseignée dans config.php : le chat utilise les mots-clés.']); exit; }
  $err = null; $t = ai_call($p, 'Réponds par un objet JSON {"reply":"ok"}', [['role' => 'user', 'content' => 'test']], $err, 40);
  echo json_encode(['provider' => $p, 'ok' => $t !== '', 'text' => mb_substr($t, 0, 200), 'error' => $err], JSON_UNESCAPED_UNICODE); exit;
}
if ($a === 'chat') {
  require_once __DIR__ . '/inc/ai.php';
  $in = json_decode(file_get_contents('php://input'), true) ?: [];
  $ex = $in['al'] ?? [];
  $safe = array_values(array_filter($in['menu'] ?? [], fn($d) => !array_intersect($d['a'] ?? [], $ex)));
  $msgs = [];
  foreach (($in['messages'] ?? []) as $m) {
    $r = (($m['role'] ?? '') === 'assistant') ? 'assistant' : 'user'; $c = trim((string)($m['content'] ?? ''));
    if ($c === '') continue; $c = mb_substr($c, 0, 800);
    if ($msgs && $msgs[count($msgs) - 1]['role'] === $r) $msgs[count($msgs) - 1]['content'] .= "\n" . $c; else $msgs[] = ['role' => $r, 'content' => $c];
  }
  while ($msgs && $msgs[0]['role'] !== 'user') array_shift($msgs);
  $prov = ai_provider();
  if ($prov === '' || !$msgs || !function_exists('curl_init')) { echo '{"reply":null}'; exit; }
  $cart = json_encode($in['cart'] ?? [], JSON_UNESCAPED_UNICODE);
  $sys = "Tu es le sommelier IA de SaveurIA, restaurant à Abidjan. Tu discutes avec un client à table : vouvoiement, ton chaleureux de bon serveur, 3 phrases maximum. "
       . "Tu réponds à ses questions (ingrédients, allergènes, temps de préparation, prix, accompagnements) UNIQUEMENT d'après la carte ci-dessous ; si l'information n'y figure pas, dis-le et invite-le à demander au personnel. "
       . "Tu recommandes 1 à 4 plats de la carte, jamais d'autres, avec le vrai prix ; si un budget est donné, le total ne doit pas le dépasser ; adapte les quantités q au nombre de personnes. Si la demande est floue, pose une seule question. "
       . "Dans tes messages précédents, les plats proposés apparaissent entre crochets sous la forme [plats proposés : id×quantité]. "
       . "Quand le client accepte tes propositions (je prends, ajoute, oui, d'accord...), mets \"action\":\"add\" et reprends exactement ces plats dans items. "
       . "Quand il veut valider, envoyer ou terminer sa commande, mets \"action\":\"checkout\" (items vides) et dis-lui de vérifier le récapitulatif puis de valider. Sinon \"action\":\"none\". Tu ne peux jamais envoyer la commande toi-même : le client valide avec un bouton. "
       . "Réponds toujours par un objet JSON {\"reply\":\"texte\",\"items\":[{\"id\":nombre,\"q\":nombre,\"why\":\"raison de 15 mots maximum\"}],\"action\":\"none|add|checkout\"} sans aucun texte autour. "
       . "Carte disponible ce soir (les plats contenant les allergènes du client sont déjà retirés) : " . json_encode($safe, JSON_UNESCAPED_UNICODE)
       . ". Allergènes à éviter : " . implode(', ', $ex ?: ['aucun']) . ". Panier actuel du client (id, quantité) : " . $cart . ".";
  $txt = ai_call($prov, $sys, $msgs);
  preg_match('/\{.*\}/s', $txt, $mm); $j = json_decode($mm[0] ?? '', true);
  $reply = is_array($j) ? trim((string)($j['reply'] ?? '')) : trim($txt);
  $ids = array_column($safe, 'id'); $out = [];
  foreach ((is_array($j) ? ($j['items'] ?? []) : []) as $x) {
    if (in_array($x['id'] ?? 0, $ids) && count($out) < 4) $out[] = ['id' => $x['id'], 'q' => max(1, min(10, (int)($x['q'] ?? 1))), 'why' => mb_substr((string)($x['why'] ?? ''), 0, 160)];
  }
  $act = (is_array($j) && in_array($j['action'] ?? '', ['add', 'checkout'], true)) ? $j['action'] : null;
  echo json_encode(['reply' => $reply !== '' ? mb_substr($reply, 0, 700) : null, 'items' => $out, 'action' => $act], JSON_UNESCAPED_UNICODE); exit;
}
if ($a === 'tables') {
  require_once __DIR__ . '/inc/store.php'; $o = [];
  foreach (jload('tables', []) as $n => $t) if (!empty($t['on'])) $o[$n] = ['label' => $t['label'] ?? ''];
  echo json_encode((object)$o, JSON_UNESCAPED_UNICODE); exit;
}
if ($a === 'save' && $_SERVER['REQUEST_METHOD'] === 'POST') {
  $raw = file_get_contents('php://input');
  $j = json_decode($raw, true);
  if (!is_array($j) || !isset($j['D'], $j['orders'])) { http_response_code(400); echo '{"ok":false}'; exit; }
  file_put_contents($file, $raw, LOCK_EX);
  echo '{"ok":true}';
} elseif ($a === 'reset') {
  @unlink($file); echo '{"ok":true}';
} else {
  echo is_file($file) ? file_get_contents($file) : 'null';
}
