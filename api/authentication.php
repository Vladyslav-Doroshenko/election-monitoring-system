<?php
require_once '../config/config.php';
session_start();

// Встановлення заголовків для JSON-відповіді
header('Content-Type: application/json');

try {
  // Визначення методу запиту
  $action = $_GET['action'] ?? null;

  switch ($action) {
    case 'login':
      handleLogin();
      break;
    case 'check_session':
      handleCheckSession();
      break;
    case 'logout':
      handleLogout();
      break;
    default:
      http_response_code(400);
      echo json_encode(['error' => 'Invalid action specified']);
      break;
  }
} catch (Exception $e) {
  http_response_code(500);
  echo json_encode(['error' => 'Unexpected server error: ' . $e->getMessage()]);
}

// Обробка запиту на вхід
function handleLogin() {
  global $pdo;

  $data = json_decode(file_get_contents("php://input"), true);
  $username = trim($data['username'] ?? '');
  $password = trim($data['password'] ?? '');

  if (empty($username) || empty($password)) {
    http_response_code(400);
    echo json_encode(['error' => 'Username and password are required']);
    return;
  }

  try {
    $stmt = $pdo->prepare("SELECT user_id, username, password, role_id FROM users WHERE username = :username");
    $stmt->execute(['username' => $username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
      $sessionToken = bin2hex(random_bytes(32));

      $stmt = $pdo->prepare("INSERT INTO sessions (user_id, session_token, expires_at) VALUES (:user_id, :session_token, DATE_ADD(NOW(), INTERVAL 1 DAY))");
      $stmt->execute([
        'user_id' => $user['user_id'],
        'session_token' => $sessionToken
      ]);

      echo json_encode([
        'status' => 'Login successful',
        'session_token' => $sessionToken,
        'user_id' => $user['user_id'],
        'role_id' => $user['role_id']
      ]);
    } else {
      http_response_code(401);
      echo json_encode(['error' => 'Invalid username or password']);
    }
  } catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
  }
}

// Перевірка активності сесії
function handleCheckSession() {
  global $pdo;

  $sessionToken = $_GET['session_token'] ?? null;
  if (!$sessionToken) {
    http_response_code(400);
    echo json_encode(['error' => 'Session token is required']);
    return;
  }

  try {
    $stmt = $pdo->prepare("SELECT * FROM sessions WHERE session_token = :session_token AND expires_at > NOW()");
    $stmt->execute(['session_token' => $sessionToken]);
    $session = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($session) {
      echo json_encode(['status' => 'Session is active', 'user_id' => $session['user_id']]);
    } else {
      http_response_code(401);
      echo json_encode(['error' => 'Session is invalid or expired']);
    }
  } catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
  }
}

// Вихід з системи (видалення сесії)
function handleLogout() {
  global $pdo;

  $sessionToken = $_GET['session_token'] ?? null;
  if (!$sessionToken) {
    http_response_code(400);
    echo json_encode(['error' => 'Session token is required']);
    return;
  }

  try {
    $stmt = $pdo->prepare("DELETE FROM sessions WHERE session_token = :session_token");
    $stmt->execute(['session_token' => $sessionToken]);

    echo json_encode(['status' => 'Logged out successfully']);
  } catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
  }
}
?>
