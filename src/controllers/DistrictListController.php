<?php

class DistrictListController
{
  private $pdo;

  public function __construct($pdo)
  {
    $this->pdo = $pdo;
  }

  // Отримання списку всіх округів
  public function getDistricts()
  {
    $stmt = $this->pdo->query("SELECT district_id, name FROM districts ORDER BY name ASC");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }
}
