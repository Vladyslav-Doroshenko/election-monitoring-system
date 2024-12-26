<?php
require_once '../config/config.php';
require_once '../src/controllers/DistrictController.php';
require_once '../src/controllers/CandidateController.php';
require_once '../src/controllers/StationController.php';
require_once '../src/controllers/VoteController.php';
session_start();

// Функція для маршрутизації запитів
function route($method, $uri)
{
  global $pdo;

  // Розбираємо URI на частини
  $uri      = trim($uri, '/');
  $segments = explode('/', $uri);

  // Перевірка автентифікації для захищених ендпоінтів
  if ($segments[0] !== 'auth') {
    authenticate($pdo);
  }

  // Визначаємо контролер та метод залежно від URI
  switch ($segments[0]) {
    case 'districts':
      $controller = new DistrictController($pdo);
      handleRequest($method, $controller, $segments[1] ?? null);
      break;
    case 'candidates':
      $controller = new CandidateController($pdo);
      handleRequest($method, $controller, $segments[1] ?? null);
      break;
    case 'stations':
      $controller = new StationController($pdo);
      handleRequest($method, $controller, $segments[1] ?? null);
      break;
    case 'votes':
      $controller = new VoteController($pdo);
      handleRequest($method, $controller, $segments[1] ?? null);
      break;
    case 'auth':
      handleAuthRequest($method, $segments[1] ?? null);
      break;
    default:
      http_response_code(404);
      echo json_encode(['error' => 'Route not found']);
  }
}

// Обробка CRUD-запитів для контролерів
function handleRequest($method, $controller, $id = null)
{
  switch ($method) {
    case 'GET':
      if ($id) {
        echo json_encode($controller->getById($id));
      } else {
        echo json_encode($controller->getAll());
      }
      break;
    case 'POST':
      $data = json_decode(file_get_contents('php://input'), true);
      $controller->create($data);
      echo json_encode(['status' => 'Created successfully']);
      break;
    case 'PUT':
      if ($id) {
        $data = json_decode(file_get_contents('php://input'), true);
        $controller->update($id, $data);
        echo json_encode(['status' => 'Updated successfully']);
      } else {
        http_response_code(400);
        echo json_encode(['error' => 'ID is required for update']);
      }
      break;
    case 'DELETE':
      if ($id) {
        $controller->delete($id);
        echo json_encode(['status' => 'Deleted successfully']);
      } else {
        http_response_code(400);
        echo json_encode(['error' => 'ID is required for delete']);
      }
      break;
    default:
      http_response_code(405);
      echo json_encode(['error' => 'Method Not Allowed']);
  }
}

// Обробка запитів для автентифікації
function handleAuthRequest($method, $action = null)
{
  require_once '../src/controllers/AuthController.php';
  $authController = new AuthController();

  switch ($action) {
    case 'login':
      if ($method === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        echo json_encode($authController->login($data));
      } else {
        http_response_code(405);
        echo json_encode(['error' => 'Method Not Allowed']);
      }
      break;
    case 'logout':
      if ($method === 'POST') {
        echo json_encode($authController->logout());
      } else {
        http_response_code(405);
        echo json_encode(['error' => 'Method Not Allowed']);
      }
      break;
    default:
      http_response_code(400);
      echo json_encode(['error' => 'Invalid action specified']);
  }
}

// Аутентифікація сесії для захищених ендпоінтів
function authenticate($pdo)
{
  $sessionToken = $_GET['session_token'] ?? null;

  if (!$sessionToken) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized access']);
    exit();
  }

  $stmt = $pdo->prepare("SELECT * FROM sessions WHERE session_token = :session_token AND expires_at > NOW()");
  $stmt->execute(['session_token' => $sessionToken]);
  $session = $stmt->fetch(PDO::FETCH_ASSOC);

  if (!$session) {
    http_response_code(403);
    echo json_encode(['error' => 'Invalid or expired session']);
    exit();
  }

  $_SESSION['user_id'] = $session['user_id'];
  $_SESSION['role_id'] = getUserRole($pdo, $session['user_id']);
}

function getUserRole($pdo, $userId)
{
  $stmt = $pdo->prepare("SELECT role_id FROM users WHERE user_id = :user_id");
  $stmt->execute(['user_id' => $userId]);
  return $stmt->fetchColumn();
}

// Виклик маршрутизації на основі запиту
route($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
