<?php

class StationController {
  private $pdo;

  public function __construct($pdo) {
    $this->pdo = $pdo;
  }

  // Створення нової дільниці
  public function createStation($data) {
    $name = trim($data['name']);
    $address = trim($data['address']);
    $district_id = (int)$data['district_id'];
    $total_voters = (int)$data['total_voters'];

    if (empty($name) || empty($district_id) || empty($total_voters)) {
      throw new Exception('Будь ласка, заповніть усі обов\'язкові поля');
    }

    $stmt = $this->pdo->prepare("INSERT INTO polling_stations (name, address, district_id, total_voters) VALUES (:name, :address, :district_id, :total_voters)");
    $stmt->execute([
      'name' => $name,
      'address' => $address,
      'district_id' => $district_id,
      'total_voters' => $total_voters
    ]);
  }

  // Оновлення інформації про дільницю
  public function updateStation($stationId, $data) {
    $name = trim($data['name']);
    $address = trim($data['address']);
    $district_id = (int)$data['district_id'];
    $total_voters = (int)$data['total_voters'];

    if (empty($name) || empty($district_id) || empty($total_voters)) {
      throw new Exception('Будь ласка, заповніть усі обов\'язкові поля');
    }

    $stmt = $this->pdo->prepare("UPDATE polling_stations SET name = :name, address = :address, district_id = :district_id, total_voters = :total_voters WHERE station_id = :station_id");
    $stmt->execute([
      'name' => $name,
      'address' => $address,
      'district_id' => $district_id,
      'total_voters' => $total_voters,
      'station_id' => $stationId
    ]);
  }

  // Видалення дільниці
  public function deleteStation($stationId) {
    $stmt = $this->pdo->prepare("DELETE FROM polling_stations WHERE station_id = :station_id");
    $stmt->execute(['station_id' => $stationId]);
  }

  // Отримання списку всіх дільниць із інформацією про округ
  public function getAllStations() {
    $stmt = $this->pdo->query("SELECT polling_stations.station_id, polling_stations.name, polling_stations.address, polling_stations.total_voters, districts.name AS district_name FROM polling_stations LEFT JOIN districts ON polling_stations.district_id = districts.district_id");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  // Отримання інформації про дільницю за ID
  public function getStationById($stationId) {
    $stmt = $this->pdo->prepare("SELECT * FROM polling_stations WHERE station_id = :station_id");
    $stmt->execute(['station_id' => $stationId]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
  }

  // Отримання списку всіх округів для вибору
  public function getDistrictsOptions($selectedId = null) {
    $stmt = $this->pdo->query("SELECT district_id, name FROM districts");
    $districts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $options = '';
    foreach ($districts as $district) {
      $selected = $selectedId == $district['district_id'] ? 'selected' : '';
      $options .= "<option value=\"{$district['district_id']}\" $selected>" . htmlspecialchars($district['name']) . "</option>";
    }

    return $options;
  }
}
