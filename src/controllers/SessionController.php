<?php

class SessionController
{
  private $pdo;

  public function __construct($pdo)
  {
    $this->pdo = $pdo;
  }

  // Отримання всіх активних сесій
  public function getAllSessions()
  {
    $stmt = $this->pdo->query("
            SELECT sessions.session_id, sessions.user_id, sessions.session_token, sessions.created_at, sessions.expires_at, users.username 
            FROM sessions 
            LEFT JOIN users ON sessions.user_id = users.user_id
        ");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  // Видалення сесії за її ID
  public function deleteSession($sessionId)
  {
    $stmt = $this->pdo->prepare("DELETE FROM sessions WHERE session_id = :session_id");
    $stmt->execute(['session_id' => $sessionId]);
  }

  // Завершення всіх сесій, крім поточної
  public function deleteAllSessionsExceptCurrent($currentSessionToken)
  {
    $stmt = $this->pdo->prepare("DELETE FROM sessions WHERE session_token != :session_token");
    $stmt->execute(['session_token' => $currentSessionToken]);
  }
}
