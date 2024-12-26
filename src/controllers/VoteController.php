<?php

class VoteController {
  private $pdo;

  public function __construct($pdo) {
    $this->pdo = $pdo;
  }

  // Додавання голосів для кандидата на виборчій дільниці
  public function addVote($stationId, $candidateId, $votesReceived) {
    try {
      $stmt = $this->pdo->prepare("INSERT INTO votes (station_id, candidate_id, votes_received) VALUES (:station_id, :candidate_id, :votes_received)");
      $stmt->execute([
        'station_id'     => $stationId,
        'candidate_id'   => $candidateId,
        'votes_received' => $votesReceived
      ]);
    } catch (PDOException $e) {
      die("Помилка додавання голосу: " . $e->getMessage());
    }
  }

  // Оновлення інформації про голоси, включаючи дільницю та кандидата
  public function updateVote($voteId, $stationId, $candidateId, $votesReceived) {
    try {
      $stmt = $this->pdo->prepare("UPDATE votes SET station_id = :station_id, candidate_id = :candidate_id, votes_received = :votes_received WHERE vote_id = :vote_id");
      $stmt->execute([
        'station_id'     => $stationId,
        'candidate_id'   => $candidateId,
        'votes_received' => $votesReceived,
        'vote_id'        => $voteId
      ]);
    } catch (PDOException $e) {
      die("Помилка оновлення голосу: " . $e->getMessage());
    }
  }

  // Видалення голосів
  public function deleteVote($voteId) {
    try {
      $stmt = $this->pdo->prepare("DELETE FROM votes WHERE vote_id = :vote_id");
      $stmt->execute(['vote_id' => $voteId]);
    } catch (PDOException $e) {
      die("Помилка видалення голосу: " . $e->getMessage());
    }
  }

  // Отримання конкретного голосу за ID
  public function getVoteById($voteId) {
    try {
      $stmt = $this->pdo->prepare("SELECT votes.vote_id, votes.station_id, votes.candidate_id, votes.votes_received, candidates.name AS candidate_name, polling_stations.name AS station_name
              FROM votes
              JOIN candidates ON votes.candidate_id = candidates.candidate_id
              JOIN polling_stations ON votes.station_id = polling_stations.station_id
              WHERE votes.vote_id = :vote_id");
      $stmt->execute(['vote_id' => $voteId]);
      return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
      die("Помилка отримання голосу: " . $e->getMessage());
    }
  }

  // Отримання всіх голосів із приєднанням інформації про кандидата та дільницю
  public function getAllVotes() {
    try {
      $stmt = $this->pdo->query("SELECT votes.vote_id, votes.votes_received, candidates.name AS candidate_name, polling_stations.name AS station_name
              FROM votes
              JOIN candidates ON votes.candidate_id = candidates.candidate_id
              JOIN polling_stations ON votes.station_id = polling_stations.station_id");
      return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
      die("Помилка отримання голосів: " . $e->getMessage());
    }
  }

  // Отримання списку всіх виборчих дільниць
  public function getStations() {
    try {
      $stmt = $this->pdo->query("SELECT station_id, name FROM polling_stations");
      return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
      die("Помилка отримання виборчих дільниць: " . $e->getMessage());
    }
  }

  // Отримання списку всіх кандидатів
  public function getCandidates() {
    try {
      $stmt = $this->pdo->query("SELECT candidate_id, name FROM candidates");
      return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
      die("Помилка отримання кандидатів: " . $e->getMessage());
    }
  }
}
