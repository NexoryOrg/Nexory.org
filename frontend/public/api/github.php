<?php
header("Content-Type: application/json; charset=utf-8");

function read_env_value(string $key): string {
  $candidates = [];

  $v = getenv($key);
  if ($v !== false && $v !== null) $candidates[] = $v;

  if (isset($_SERVER[$key])) $candidates[] = $_SERVER[$key];
  if (isset($_ENV[$key])) $candidates[] = $_ENV[$key];

  foreach ($candidates as $candidate) {
    $candidate = trim((string)$candidate);
    $candidate = trim($candidate, "\"'");
    if ($candidate !== "") return $candidate;
  }

  return "";
}

function read_dotenv_value(string $dotenvPath, string $key): string {
  if (!is_readable($dotenvPath)) return "";

  $lines = file($dotenvPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
  if ($lines === false) return "";

  foreach ($lines as $line) {
    $line = trim($line);
    if ($line === "" || strpos($line, "#") === 0) continue;

    $parts = explode("=", $line, 2);
    if (count($parts) !== 2) continue;

    $k = trim($parts[0]);
    $v = trim($parts[1]);
    $v = trim($v, "\"'");

    if ($k === $key && $v !== "") return $v;
  }

  return "";
}

$token = read_env_value("GITHUB_TOKEN");

if ($token === "") {
  $dotenvCandidates = [
    dirname(__DIR__, 2) . "/.env",
    __DIR__ . "/.env"
  ];

  foreach ($dotenvCandidates as $dotenvPath) {
    $dotenvToken = read_dotenv_value($dotenvPath, "GITHUB_TOKEN");
    if ($dotenvToken !== "") {
      $token = $dotenvToken;
      break;
    }
  }
}

if ($token === "") {
  http_response_code(500);
  echo json_encode([
    "error" => "Missing GITHUB_TOKEN",
    "hint" => "Set GITHUB_TOKEN in env or .env"
  ]);
  exit;
}

$endpoint = $_GET["endpoint"] ?? "";
$org = "NexoryDev";

if ($endpoint === "repos") {
  $perPage = isset($_GET["per_page"]) ? (int)$_GET["per_page"] : 100;
  if ($perPage < 1) $perPage = 1;
  if ($perPage > 100) $perPage = 100;
  $sort = $_GET["sort"] ?? "updated";
  $allowedSort = ["created", "updated", "pushed", "full_name"];
  if (!in_array($sort, $allowedSort, true)) $sort = "updated";
  $url = "https://api.github.com/orgs/$org/repos?type=public&per_page=$perPage&sort=$sort";
} elseif ($endpoint === "members") {
  $url = "https://api.github.com/orgs/$org/members?per_page=100";
} elseif ($endpoint === "contributors") {
  $repo = $_GET["repo"] ?? "";
  if ($repo === "") {
    http_response_code(400);
    echo json_encode(["error" => "Missing repo"]);
    exit;
  }
  $url = "https://api.github.com/repos/$org/" . rawurlencode($repo) . "/contributors?per_page=100";
} elseif ($endpoint === "collaborators") {
  $repo = $_GET["repo"] ?? "";
  if ($repo === "") {
    http_response_code(400);
    echo json_encode(["error" => "Missing repo"]);
    exit;
  }
  $url = "https://api.github.com/repos/$org/" . rawurlencode($repo) . "/collaborators?per_page=100";
} else {
  http_response_code(400);
  echo json_encode(["error" => "Invalid endpoint"]);
  exit;
}

$ch = curl_init($url);
curl_setopt_array($ch, [
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_HTTPHEADER => [
    "Accept: application/vnd.github+json",
    "Authorization: Bearer " . $token,
    "User-Agent: nexory-dev-frontend"
  ],
  CURLOPT_TIMEOUT => 20
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlErr = curl_error($ch);
curl_close($ch);

if ($curlErr) {
  http_response_code(502);
  echo json_encode(["error" => "Upstream curl error"]);
  exit;
}

if ($endpoint === "repos") {
  $decoded = json_decode($response, true);
  if (is_array($decoded)) {
    $decoded = array_values(array_filter($decoded, static function ($repo) {
      return is_array($repo) && (!isset($repo["private"]) || $repo["private"] !== true);
    }));
    http_response_code($httpCode > 0 ? $httpCode : 500);
    echo json_encode($decoded);
    exit;
  }
}

http_response_code($httpCode > 0 ? $httpCode : 500);
echo $response ?: "[]";