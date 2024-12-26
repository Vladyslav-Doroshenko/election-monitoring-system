<?php

class BallotController
{
  private $pdo;

  public function __construct($pdo)
  {
    $this->pdo = $pdo;
  }

  // Додавання даних про бюлетені
  public function addBallot($stationId, $ballotsReceived, $ballotsIssued, $ballotsSpoiled, $ballotsUnused)
  {
    $stmt = $this->pdo->prepare("INSERT INTO ballots (station_id, ballots_received, ballots_issued, ballots_spoiled, ballots_unused) VALUES (:station_id, :ballots_received, :ballots_issued, :ballots_spoiled, :ballots_unused)");
    $stmt->execute([
      'station_id'       => $stationId,
      'ballots_received' => $ballotsReceived,
      'ballots_issued'   => $ballotsIssued,
      'ballots_spoiled'  => $ballotsSpoiled,
      'ballots_unused'   => $ballotsUnused
    ]);
  }

  // Оновлення даних про бюлетені
  public function updateBallot($ballotId, $ballotsReceived, $ballotsIssued, $ballotsSpoiled, $ballotsUnused)
  {
    $stmt = $this->pdo->prepare("UPDATE ballots SET ballots_received = :ballots_received, ballots_issued = :ballots_issued, ballots_spoiled = :ballots_spoiled, ballots_unused = :ballots_unused WHERE ballot_id = :ballot_id");
    $stmt->execute([
      'ballots_received' => $ballotsReceived,
      'ballots_issued'   => $ballotsIssued,
      'ballots_spoiled'  => $ballotsSpoiled,
      'ballots_unused'   => $ballotsUnused,
      'ballot_id'        => $ballotId
    ]);
  }

  // Видалення даних про бюлетені
  public function deleteBallot($ballotId)
  {
    $stmt = $this->pdo->prepare("DELETE FROM ballots WHERE ballot_id = :ballot_id");
    $stmt->execute(['ballot_id' => $ballotId]);
  }

  // Отримання конкретного запису про бюлетень для редагування
  public function getBallotById($ballotId)
  {
    $stmt = $this->pdo->prepare("SELECT * FROM ballots WHERE ballot_id = :ballot_id");
    $stmt->execute(['ballot_id' => $ballotId]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
  }

  // Отримання всіх даних про бюлетені із приєднанням інформації про виборчу дільницю
  public function getAllBallots()
  {
    $stmt = $this->pdo->query("SELECT ballots.ballot_id, ballots.ballots_received, ballots.ballots_issued, ballots.ballots_spoiled, ballots.ballots_unused, polling_stations.name AS station_name
            FROM ballots
            JOIN polling_stations ON ballots.station_id = polling_stations.station_id");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  // Отримання списку всіх виборчих дільниць
  public function getStations()
  {
    $stmt = $this->pdo->query("SELECT station_id, name FROM polling_stations");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }
}
