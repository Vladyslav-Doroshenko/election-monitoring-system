<?php

class UserController
{
  private $pdo;

  public function __construct($pdo)
  {
    $this->pdo = $pdo;
  }

  // Створення нового користувача
  public function createUser($data)
  {
    $username = trim($data['username']);
    $email    = trim($data['email']);
    $password = password_hash($data['password'], PASSWORD_DEFAULT);
    $role_id  = (int)$data['role_id'];

    if (empty($username) || empty($email) || empty($data['password']) || empty($role_id)) {
      throw new Exception('Будь ласка, заповніть усі обов\'язкові поля');
    }

    $stmt = $this->pdo->prepare("INSERT INTO users (username, email, password, role_id) VALUES (:username, :email, :password, :role_id)");
    $stmt->execute([
      'username' => $username,
      'email'    => $email,
      'password' => $password,
      'role_id'  => $role_id
    ]);
  }

  // Оновлення інформації про користувача
  public function updateUser($userId, $data)
  {
    $username = trim($data['username']);
    $email    = trim($data['email']);
    $role_id  = (int)$data['role_id'];
    $password = !empty($data['password']) ? password_hash($data['password'], PASSWORD_DEFAULT) : null;

    if (empty($username) || empty($email) || empty($role_id)) {
      throw new Exception('Будь ласка, заповніть усі обов\'язкові поля');
    }

    if ($password) {
      $stmt = $this->pdo->prepare("UPDATE users SET username = :username, email = :email, password = :password, role_id = :role_id WHERE user_id = :user_id");
      $stmt->execute([
        'username' => $username,
        'email'    => $email,
        'password' => $password,
        'role_id'  => $role_id,
        'user_id'  => $userId
      ]);
    } else {
      $stmt = $this->pdo->prepare("UPDATE users SET username = :username, email = :email, role_id = :role_id WHERE user_id = :user_id");
      $stmt->execute([
        'username' => $username,
        'email'    => $email,
        'role_id'  => $role_id,
        'user_id'  => $userId
      ]);
    }
  }

  // Видалення користувача
  public function deleteUser($userId)
  {
    $stmt = $this->pdo->prepare("DELETE FROM users WHERE user_id = :user_id");
    $stmt->execute(['user_id' => $userId]);
  }

  // Отримання списку всіх користувачів із ролями
  public function getAllUsers()
  {
    $stmt = $this->pdo->query("SELECT users.user_id, users.username, users.email, roles.role_name FROM users LEFT JOIN roles ON users.role_id = roles.role_id");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  // Отримання інформації про користувача за ID
  public function getUserById($userId)
  {
    $stmt = $this->pdo->prepare("SELECT * FROM users WHERE user_id = :user_id");
    $stmt->execute(['user_id' => $userId]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
  }

  // Отримання списку всіх ролей для вибору
  public function getRolesOptions($selectedId = null)
  {
    $stmt  = $this->pdo->query("SELECT role_id, role_name FROM roles");
    $roles = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $options = '';
    foreach ($roles as $role) {
      $selected = $selectedId == $role['role_id'] ? 'selected' : '';
      $options  .= "<option value=\"{$role['role_id']}\" $selected>" . htmlspecialchars($role['role_name']) . "</option>";
    }

    return $options;
  }
}
