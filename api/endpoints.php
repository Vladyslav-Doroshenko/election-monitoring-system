<?php
require_once '../config/config.php';
require_once '../src/controllers/DistrictController.php';
require_once '../src/controllers/CandidateController.php';
require_once '../src/controllers/StationController.php';
require_once '../src/controllers/VoteController.php';
session_start();

// Встановлення заголовків для JSON-відповіді
header('Content-Type: application/json');

// Функція для аутентифікації з логуванням
function authenticate($pdo)
{
  $sessionToken = $_GET['session_token'] ?? getBearerToken();

  if (!$sessionToken) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized access: No session token provided']);
    exit();
  }

  $stmt = $pdo->prepare("SELECT * FROM sessions WHERE session_token = :session_token AND expires_at > NOW()");
  $stmt->execute(['session_token' => $sessionToken]);
  $session = $stmt->fetch(PDO::FETCH_ASSOC);

  if (!$session) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized access: Invalid or expired session token']);
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

function getBearerToken()
{
  $headers = apache_request_headers();
  if (isset($headers['Authorization'])) {
    if (preg_match('/Bearer\s(\S+)/', $headers['Authorization'], $matches)) {
      return $matches[1];
    }
  }
  return null;
}

authenticate($pdo);

$method = $_SERVER['REQUEST_METHOD'];
$entity = $_GET['entity'] ?? null;

$districtController  = new DistrictController($pdo);
$candidateController = new CandidateController($pdo);
$stationController   = new StationController($pdo);
$voteController      = new VoteController($pdo);

switch ($entity) {
  case 'districts':
    handleRequest($method, $districtController);
    break;
  case 'candidates':
    handleRequest($method, $candidateController);
    break;
  case 'stations':
    handleRequest($method, $stationController);
    break;
  case 'votes':
    handleRequest($method, $voteController);
    break;
  case 'results':
    handleResults($method);
    break;
  default:
    http_response_code(400);
    echo json_encode(['error' => 'Invalid entity specified']);
    break;
}

function handleResults($method)
{
  global $pdo;

  if ($method !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed']);
    return;
  }

  try {
    $stmt = $pdo->query("SELECT 
            districts.name AS district_name,
            candidates.name AS candidate_name,
            SUM(votes.votes_received) AS total_votes,
            (SUM(votes.votes_received) / districts.total_voters) * 100 AS voter_turnout_percentage
        FROM votes
        INNER JOIN candidates ON votes.candidate_id = candidates.candidate_id
        INNER JOIN polling_stations ON votes.station_id = polling_stations.station_id
        INNER JOIN districts ON polling_stations.district_id = districts.district_id
        GROUP BY districts.district_id, candidates.candidate_id
        ORDER BY districts.district_id, total_votes DESC");

    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['status' => 'success', 'data' => $results]);

  } catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error fetching election results: ' . $e->getMessage()]);
  }
}

function handleRequest($method, $controller)
{
  $id = $_GET['id'] ?? null;

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
?>
