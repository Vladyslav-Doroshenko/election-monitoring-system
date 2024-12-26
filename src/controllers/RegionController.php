<?php

class RegionController
{
  private $pdo;

  public function __construct($pdo)
  {
    $this->pdo = $pdo;
  }

  // Отримання всіх регіонів
  public function getAllRegions()
  {
    $stmt = $this->pdo->query("SELECT * FROM regions");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  // Отримання регіону за ID
  public function getRegionById($regionId)
  {
    $stmt = $this->pdo->prepare("SELECT * FROM regions WHERE region_id = :region_id");
    $stmt->execute(['region_id' => $regionId]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
  }

  // Додавання нового регіону
  public function createRegion($data)
  {
    $stmt = $this->pdo->prepare("INSERT INTO regions (name, description) VALUES (:name, :description)");
    $stmt->execute([
      'name'        => $data['name'],
      'description' => $data['description']
    ]);
  }

  // Оновлення існуючого регіону
  public function updateRegion($regionId, $data)
  {
    $stmt = $this->pdo->prepare("UPDATE regions SET name = :name, description = :description WHERE region_id = :region_id");
    $stmt->execute([
      'name'        => $data['name'],
      'description' => $data['description'],
      'region_id'   => $regionId
    ]);
  }

  // Видалення регіону
  public function deleteRegion($regionId)
  {
    $stmt = $this->pdo->prepare("DELETE FROM regions WHERE region_id = :region_id");
    $stmt->execute(['region_id' => $regionId]);
  }

  // Оновлення регіонів з API, якщо таблиця порожня
  public function populateRegionsFromAPI()
  {
    $apiUrl = "https://decentralization.ua/graphql";

    // Формування GraphQL-запиту
    $query = <<<GQL
    {
        areas {
            title
            id
            square
            population
            local_community_count
            percent_communities_from_area
            sum_communities_square
        }
    }
    GQL;

    // Виконання запиту до API за допомогою cURL
    $ch = curl_init($apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
      'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['query' => $query]));

    $response = curl_exec($ch);
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpcode !== 200 || !$response) {
      throw new Exception("Помилка отримання даних з API.");
    }

    // Декодуємо JSON у PHP-масив
    $data = json_decode($response, true);
    if (!isset($data['data']['areas'])) {
      throw new Exception("Неправильний формат відповіді API.");
    }

    // Підготовка запиту для вставки і перевірки регіонів
    $insertStmt = $this->pdo->prepare("INSERT INTO regions (name, description) VALUES (:name, :description)");
    $checkStmt  = $this->pdo->prepare("SELECT COUNT(*) FROM regions WHERE name = :name");

    // Проходимося по кожному регіону з API
    foreach ($data['data']['areas'] as $area) {
      // Витягуємо необхідні дані
      $name        = $area['title'];
      $description = "Площа: " . $area['square'] . " км², Населення: " . $area['population'];

      // Перевіряємо, чи існує такий регіон у базі
      $checkStmt->execute(['name' => $name]);
      $exists = $checkStmt->fetchColumn();

      // Якщо запис не існує, додаємо його в базу
      if (!$exists) {
        $insertStmt->execute([
          'name'        => $name,
          'description' => $description
        ]);
      }
    }
  }
}
