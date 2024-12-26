<?php

class ReportController {
  private $pdo;

  public function __construct($pdo) {
    $this->pdo = $pdo;
  }

  // Отримання підсумкових результатів по округах
  public function getSummaryReport() {
    $stmt = $this->pdo->query("
            SELECT 
                districts.district_id,
                districts.name AS district_name,
                districts.total_voters,
                COALESCE(SUM(ballots.ballots_issued), 0) AS total_ballots_issued,
                COALESCE(SUM(votes.votes_received), 0) AS total_votes,
                candidates.name AS leading_candidate,
                MAX(votes.votes_received) AS max_votes,
                CASE 
                    WHEN districts.total_voters > 0 THEN (COALESCE(SUM(ballots.ballots_issued), 0) / districts.total_voters) * 100
                    ELSE 0
                END AS voter_turnout
            FROM districts
            LEFT JOIN polling_stations ON districts.district_id = polling_stations.district_id
            LEFT JOIN ballots ON polling_stations.station_id = ballots.station_id
            LEFT JOIN votes ON polling_stations.station_id = votes.station_id
            LEFT JOIN candidates ON votes.candidate_id = candidates.candidate_id
            GROUP BY districts.district_id, candidates.candidate_id
            ORDER BY districts.district_id
        ");

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }
}
