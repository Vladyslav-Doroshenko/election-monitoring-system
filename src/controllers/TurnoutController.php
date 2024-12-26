<?php

class TurnoutController
{
  private $pdo;

  public function __construct($pdo)
  {
    $this->pdo = $pdo;
  }

  // Обчислення явки виборців для кожної виборчої дільниці
  public function calculateTurnout()
  {
    // Отримання списку дільниць із загальною кількістю виборців
    $stations = $this->pdo->query("SELECT station_id, total_voters FROM polling_stations")->fetchAll(PDO::FETCH_ASSOC);

    foreach ($stations as $station) {
      $stationId   = $station['station_id'];
      $totalVoters = $station['total_voters'];

      // Отримання виданих бюлетенів для дільниці
      $stmt = $this->pdo->prepare("SELECT ballots_issued FROM ballots WHERE station_id = :station_id");
      $stmt->execute(['station_id' => $stationId]);
      $ballotData = $stmt->fetch(PDO::FETCH_ASSOC);

      $ballotsIssued = $ballotData['ballots_issued'] ?? 0;

      // Обчислення явки
      $turnout = ($totalVoters > 0) ? ($ballotsIssued / $totalVoters) * 100 : 0;

      // Збереження явки в таблиці `polling_stations`
      $stmt = $this->pdo->prepare("UPDATE polling_stations SET voter_turnout = :turnout WHERE station_id = :station_id");
      $stmt->execute([
        'turnout'    => $turnout,
        'station_id' => $stationId
      ]);
    }
  }

  // Отримання даних для відображення результатів явки
  public function getTurnout()
  {
    $stmt = $this->pdo->query("
        SELECT 
            polling_stations.station_id,
            polling_stations.name AS station_name,
            polling_stations.total_voters,
            COALESCE(ballots.ballots_issued, 0) AS ballots_issued,
            CASE
                WHEN polling_stations.total_voters > 0 THEN (COALESCE(ballots.ballots_issued, 0) / polling_stations.total_voters) * 100
                ELSE 0
            END AS voter_turnout
        FROM polling_stations
        LEFT JOIN ballots ON polling_stations.station_id = ballots.station_id
    ");

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }
}
