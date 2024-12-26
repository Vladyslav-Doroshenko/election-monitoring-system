<?php

class DistrictController
{
  private $pdo;

  public function __construct($pdo)
  {
    $this->pdo = $pdo;
  }

  // Створення нового округу
  public function createDistrict($data)
  {
    $name         = trim($data['name']);
    $region_id    = (int)$data['region_id'];
    $total_voters = (int)$data['total_voters'];

    if (empty($name) || empty($region_id) || empty($total_voters)) {
      throw new Exception('Будь ласка, заповніть усі обов\'язкові поля');
    }

    $stmt = $this->pdo->prepare("INSERT INTO districts (name, region_id, total_voters) VALUES (:name, :region_id, :total_voters)");
    $stmt->execute([
      'name'         => $name,
      'region_id'    => $region_id,
      'total_voters' => $total_voters
    ]);
  }

  // Оновлення інформації про округ
  public function updateDistrict($districtId, $data)
  {
    $name         = trim($data['name']);
    $region_id    = (int)$data['region_id'];
    $total_voters = (int)$data['total_voters'];

    if (empty($name) || empty($region_id) || empty($total_voters)) {
      throw new Exception('Будь ласка, заповніть усі обов\'язкові поля');
    }

    $stmt = $this->pdo->prepare("UPDATE districts SET name = :name, region_id = :region_id, total_voters = :total_voters WHERE district_id = :district_id");
    $stmt->execute([
      'name'         => $name,
      'region_id'    => $region_id,
      'total_voters' => $total_voters,
      'district_id'  => $districtId
    ]);
  }

  // Видалення округу
  public function deleteDistrict($districtId)
  {
    $stmt = $this->pdo->prepare("DELETE FROM districts WHERE district_id = :district_id");
    $stmt->execute(['district_id' => $districtId]);
  }

  // Отримання списку всіх округів із приєднанням інформації про регіон
  public function getAllDistricts()
  {
    $stmt = $this->pdo->query("SELECT districts.district_id, districts.name, districts.total_voters, regions.name AS region_name FROM districts LEFT JOIN regions ON districts.region_id = regions.region_id");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  // Отримання округу за ID
  public function getDistrictById($districtId)
  {
    $stmt = $this->pdo->prepare("SELECT * FROM districts WHERE district_id = :district_id");
    $stmt->execute(['district_id' => $districtId]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
  }

  // Отримання списку всіх регіонів для випадаючого списку
  public function getRegionsOptions($selectedId = null)
  {
    $stmt    = $this->pdo->query("SELECT region_id, name FROM regions");
    $regions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $options = '';
    foreach ($regions as $region) {
      $selected = $selectedId == $region['region_id'] ? 'selected' : '';
      $options  .= "<option value=\"{$region['region_id']}\" $selected>" . htmlspecialchars($region['name']) . "</option>";
    }

    return $options;
  }
}
