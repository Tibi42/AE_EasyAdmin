<?php
/**
 * Récepteur de webhook GitHub
 * Déclenche automatiquement deploy.sh à chaque push sur main
 *
 * URL : https://ton-domaine.com/webhook.php
 */

define('DEPLOY_SCRIPT', '/var/www/ae_easyadmin/deploy.sh');
define('LOG_FILE',      '/var/www/ae_easyadmin/var/log/deploy.log');
define('ALLOWED_BRANCH', 'refs/heads/main');

// --- Vérification méthode ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method Not Allowed');
}

// --- Lecture du payload ---
$payload = file_get_contents('php://input');
if (empty($payload)) {
    http_response_code(400);
    exit('Empty payload');
}

// --- Vérification signature GitHub (HMAC SHA-256) ---
$secret    = getenv('GITHUB_WEBHOOK_SECRET');
$signature = $_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? '';

if (empty($secret) || empty($signature)) {
    http_response_code(403);
    exit('Missing signature or secret');
}

$expected = 'sha256=' . hash_hmac('sha256', $payload, $secret);
if (!hash_equals($expected, $signature)) {
    http_response_code(403);
    logDeploy('ERREUR : Signature invalide — tentative non autorisée');
    exit('Invalid signature');
}

// --- Vérification événement ---
$event = $_SERVER['HTTP_X_GITHUB_EVENT'] ?? '';
if ($event !== 'push') {
    http_response_code(200);
    exit('Event ignored: ' . $event);
}

// --- Vérification branche ---
$data   = json_decode($payload, true);
$branch = $data['ref'] ?? '';

if ($branch !== ALLOWED_BRANCH) {
    http_response_code(200);
    logDeploy("Push ignoré sur la branche : $branch");
    exit('Branch ignored');
}

// --- Déclenchement du déploiement en arrière-plan ---
$commit  = $data['after'] ?? 'unknown';
$pusher  = $data['pusher']['name'] ?? 'unknown';
$message = $data['head_commit']['message'] ?? '';

logDeploy("Déploiement déclenché par $pusher — commit $commit — \"$message\"");

// Lance deploy.sh en arrière-plan, redirige la sortie vers le log
$cmd = 'bash ' . escapeshellarg(DEPLOY_SCRIPT) . ' >> ' . escapeshellarg(LOG_FILE) . ' 2>&1 &';
exec($cmd);

http_response_code(200);
echo json_encode(['status' => 'deployment triggered', 'commit' => $commit]);

// --- Helpers ---
function logDeploy(string $message): void
{
    $dir = dirname(LOG_FILE);
    if (!is_dir($dir)) {
        mkdir($dir, 0775, true);
    }
    $line = '[' . date('d/m/Y H:i:s') . '] ' . $message . PHP_EOL;
    file_put_contents(LOG_FILE, $line, FILE_APPEND | LOCK_EX);
}
