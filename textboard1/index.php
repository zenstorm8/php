<?php
declare(strict_types=1);

// — CONFIGURATION VARIABLES —
define('BOARD_TITLE',             'Text Board');  // change the board title here
define('MAX_POSTS',                        30);  // how many messages to keep
define('MAX_MESSAGE_LENGTH',           2000);  // max characters per message
define('RATE_LIMIT_SECONDS',              10);  // seconds between posts per user
define('ENABLE_HEARTBEAT_LOG',         true);  // toggle the “Page loaded” heartbeat log
define('CLEAR_ERROR_LOG_ON_START',    false);  // if true, wipes error.txt on each load

// — ERROR HANDLING & ENVIRONMENT CHECK —
// Ensure error.txt exists (create if missing) but do not clear it unless configured
if (CLEAR_ERROR_LOG_ON_START) {
    @file_put_contents(__DIR__ . '/error.txt', '', LOCK_EX);
} else {
    if (!file_exists(__DIR__ . '/error.txt')) {
        @file_put_contents(__DIR__ . '/error.txt', '', LOCK_EX);
    }
}

// Configure PHP to log everything to error.txt, never show them in the browser
ini_set('display_errors', '0');
ini_set('log_errors',     '1');
ini_set('error_log',      __DIR__ . '/error.txt');
error_reporting(E_ALL);

// Optional heartbeat to verify logging (appends)
if (ENABLE_HEARTBEAT_LOG) {
    error_log('[' . date('Y-m-d H:i:s') . "] Page loaded\n", 3, __DIR__ . '/error.txt');
}

// Enforce PHP 8.4.8+
if (PHP_VERSION_ID < 80408) {
    error_log('✖ PHP 8.4.8+ required (running ' . PHP_VERSION . ")\n", 3, __DIR__ . '/error.txt');
    exit;
}

session_start();

// — CSRF TOKEN SETUP —
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// — DATA FILE SETUP —
define('POST_FILE', __DIR__ . '/posts.json');
if (!file_exists(POST_FILE)) {
    file_put_contents(POST_FILE, json_encode([], JSON_PRETTY_PRINT), LOCK_EX);
}

// — TEXT BOARD CLASS —
class TextBoard
{
    public function __construct(private readonly int $maxPosts = MAX_POSTS) {}

    public function addPost(string $text): void
    {
        $fp = fopen(POST_FILE, 'c+');
        if (!$fp) {
            error_log("Failed to open posts file\n", 3, __DIR__ . '/error.txt');
            return;
        }

        flock($fp, LOCK_EX);
        $raw   = stream_get_contents($fp) ?: '';
        $posts = json_decode(json: $raw, associative: true) ?: [];

        array_unshift($posts, ['text' => $text]);
        if (count($posts) > $this->maxPosts) {
            $posts = array_slice($posts, 0, $this->maxPosts);
        }

        ftruncate($fp, 0);
        rewind($fp);
        fwrite($fp, json_encode($posts, JSON_PRETTY_PRINT));
        fflush($fp);
        flock($fp, LOCK_UN);
        fclose($fp);
    }

    public function getPosts(): array
    {
        $raw   = @file_get_contents(POST_FILE) ?: '';
        $posts = json_decode(json: $raw, associative: true);
        return is_array($posts) ? $posts : [];
    }
}

// — HANDLE NEW POSTS —
$board = new TextBoard();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF check
    if (hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        // Rate-limit
        $now  = time();
        $last = $_SESSION['last_post_time'] ?? 0;
        if ($now - $last >= RATE_LIMIT_SECONDS) {
            $text = trim((string)($_POST['text'] ?? ''));
            if ($text !== '' && mb_strlen($text) <= MAX_MESSAGE_LENGTH) {
                $board->addPost($text);
                $_SESSION['last_post_time'] = $now;
            }
        }
    } else {
        error_log("CSRF token mismatch\n", 3, __DIR__ . '/error.txt');
    }
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// — SECURITY HEADERS —
// Allow inline styles, block all scripts
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');
header("Content-Security-Policy: default-src 'self'; script-src 'none'; style-src 'self' 'unsafe-inline';");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title><?= htmlspecialchars(BOARD_TITLE, ENT_QUOTES) ?></title>
  <style>
    body {
      background-color: #222;
      color: #ddd;
      font-family: sans-serif;
      max-width: 600px;
      margin: 2em auto;
      padding: 0 1em;
    }
    h1 { margin-bottom: 0.5em; }
    form { margin-bottom: 1em; }
    textarea {
      width: 100%;
      height: 80px;
      background: #333;
      color: #ddd;
      border: 1px solid #555;
      padding: 0.5em;
      font-family: inherit;
      font-size: 1em;
      resize: vertical;
    }
    input[type="submit"] {
      margin-top: 0.5em;
      padding: 0.5em 1em;
      background: #444;
      color: #ddd;
      border: 1px solid #555;
      cursor: pointer;
      font-family: inherit;
      font-size: 1em;
    }
    .post {
      border-bottom: 1px solid #555;
      padding: 0.5em 0;
    }
    .post p {
      margin: 0;
      white-space: pre-wrap;
    }
  </style>
</head>
<body>
  <h1><?= htmlspecialchars(BOARD_TITLE, ENT_QUOTES) ?></h1>

  <form method="post" action="">
    <textarea
      name="text"
      placeholder="Write your message here…"
      required
      maxlength="<?= MAX_MESSAGE_LENGTH ?>"
    ></textarea><br>
    <input type="hidden" name="csrf_token"
           value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES) ?>">
    <input type="submit" value="Send">
  </form>

  <?php $posts = $board->getPosts(); ?>
  <?php if (empty($posts)): ?>
    <p>No posts yet. Be the first!</p>
  <?php else: ?>
    <?php foreach ($posts as $post): ?>
      <div class="post">
        <p><?= nl2br(htmlspecialchars($post['text'])) ?></p>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>
</body>
</html>
