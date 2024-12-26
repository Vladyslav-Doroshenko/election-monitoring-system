<?php

class RoleController
{
  private $pdo;
  private $protectedRoleIds = [1, 2, 3, 4];

  public function __construct($pdo)
  {
    $this->pdo = $pdo;
  }

  // Створення нової ролі
  public function createRole($data)
  {
    $role_name = trim($data['role_name']);

    if (empty($role_name)) {
      throw new Exception('Назва ролі є обов\'язковою');
    }

    $stmt = $this->pdo->prepare("INSERT INTO roles (role_name) VALUES (:role_name)");
    $stmt->execute(['role_name' => $role_name]);
  }

  // Оновлення інформації про роль
  public function updateRole($roleId, $data)
  {
    if (in_array($roleId, $this->protectedRoleIds)) {
      throw new Exception('Ця роль захищена від редагування.');
    }

    $role_name = trim($data['role_name']);

    if (empty($role_name)) {
      throw new Exception('Назва ролі є обов\'язковою');
    }

    $stmt = $this->pdo->prepare("UPDATE roles SET role_name = :role_name WHERE role_id = :role_id");
    $stmt->execute([
      'role_name' => $role_name,
      'role_id'   => $roleId
    ]);
  }

  // Видалення ролі
  public function deleteRole($roleId)
  {
    if (in_array($roleId, $this->protectedRoleIds)) {
      throw new Exception('Ця роль захищена від видалення.');
    }

    $stmt = $this->pdo->prepare("DELETE FROM roles WHERE role_id = :role_id");
    $stmt->execute(['role_id' => $roleId]);
  }

  // Отримання списку всіх ролей
  public function getAllRoles()
  {
    $stmt = $this->pdo->query("SELECT * FROM roles");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  // Отримання інформації про роль за ID
  public function getRoleById($roleId)
  {
    $stmt = $this->pdo->prepare("SELECT * FROM roles WHERE role_id = :role_id");
    $stmt->execute(['role_id' => $roleId]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
  }
}
