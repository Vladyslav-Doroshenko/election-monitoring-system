<?php

class ResultController
{
  private $pdo;

  public function __construct($pdo)
  {
    $this->pdo = $pdo;
  }

  // Метод для перевірки, чи вже були розраховані результати
  public function checkIfResultsCalculated()
  {
    $stmt = $this->pdo->query("SELECT COUNT(*) FROM results_summary");
    return $stmt->fetchColumn() > 0;
  }

  // Обчислення загальної кількості голосів і результатів для кожного округу
  public function calculateTotals()
  {
    // Отримання списку округів із загальною кількістю виборців
    $districts = $this->pdo->query("SELECT district_id, total_voters FROM districts")->fetchAll(PDO::FETCH_ASSOC);

    foreach ($districts as $district) {
      $districtId  = $district['district_id'];
      $totalVoters = $district['total_voters'];

      // Підрахунок голосів у виборчій дільниці
      $stmt = $this->pdo->prepare("SELECT candidate_id, SUM(votes_received) AS total_votes FROM votes WHERE station_id IN (SELECT station_id FROM polling_stations WHERE district_id = :district_id) GROUP BY candidate_id");
      $stmt->execute(['district_id' => $districtId]);
      $candidateVotes = $stmt->fetchAll(PDO::FETCH_ASSOC);

      // Підрахунок загальної кількості голосів та визначення провідного кандидата
      $totalVotes       = 0;
      $leadingCandidate = null;
      $maxVotes         = 0;

      foreach ($candidateVotes as $candidateVote) {
        $votes      = $candidateVote['total_votes'];
        $totalVotes += $votes;

        // Визначення кандидата з найбільшою кількістю голосів
        if ($votes > $maxVotes) {
          $maxVotes         = $votes;
          $leadingCandidate = $candidateVote['candidate_id'];
        }
      }

      // Обчислення явки виборців
      $voterTurnout = ($totalVoters > 0) ? ($totalVotes / $totalVoters) * 100 : 0;

      // Збереження результатів у таблиці results_summary
      $stmt = $this->pdo->prepare("REPLACE INTO results_summary (district_id, total_voters, total_votes, voter_turnout, leading_candidate) VALUES (:district_id, :total_voters, :total_votes, :voter_turnout, :leading_candidate)");
      $stmt->execute([
        'district_id'       => $districtId,
        'total_voters'      => $totalVoters,
        'total_votes'       => $totalVotes,
        'voter_turnout'     => $voterTurnout,
        'leading_candidate' => $leadingCandidate
      ]);
    }
  }

  // Отримання результатів для відображення
  public function getResults()
  {
    $stmt = $this->pdo->query("SELECT results_summary.district_id, districts.name AS district_name, results_summary.total_voters, results_summary.total_votes, results_summary.voter_turnout, candidates.name AS leading_candidate
          FROM results_summary
          LEFT JOIN districts ON results_summary.district_id = districts.district_id
          LEFT JOIN candidates ON results_summary.leading_candidate = candidates.candidate_id");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  public function deleteDistrict($districtId)
  {
    // Видалення пов'язаних записів у таблиці results_summary
    $stmt = $this->pdo->prepare("DELETE FROM results_summary WHERE district_id = :district_id");
    $stmt->execute(['district_id' => $districtId]);

    // Видалення округу
    $stmt = $this->pdo->prepare("DELETE FROM districts WHERE district_id = :district_id");
    $stmt->execute(['district_id' => $districtId]);
  }
}
