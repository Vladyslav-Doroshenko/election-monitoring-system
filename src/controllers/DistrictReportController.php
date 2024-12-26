<?php

class DistrictReportController {
  private $pdo;

  public function __construct($pdo) {
    $this->pdo = $pdo;
  }

  // Отримання детального звіту по округу
  public function getDistrictReport($districtId) {
    // Отримання інформації про округ
    $stmt = $this->pdo->prepare("
            SELECT 
                districts.name AS district_name,
                districts.total_voters,
                COALESCE(SUM(ballots.ballots_issued), 0) AS total_ballots_issued,
                COALESCE(SUM(votes.votes_received), 0) AS total_votes,
                CASE 
                    WHEN districts.total_voters > 0 THEN (COALESCE(SUM(ballots.ballots_issued), 0) / districts.total_voters) * 100
                    ELSE 0
                END AS voter_turnout
            FROM districts
            LEFT JOIN polling_stations ON districts.district_id = polling_stations.district_id
            LEFT JOIN ballots ON polling_stations.station_id = ballots.station_id
            LEFT JOIN votes ON polling_stations.station_id = votes.station_id
            WHERE districts.district_id = :district_id
            GROUP BY districts.district_id
        ");
    $stmt->execute(['district_id' => $districtId]);
    $districtInfo = $stmt->fetch(PDO::FETCH_ASSOC);

    // Отримання інформації про кандидатів у цьому окрузі
    $stmt = $this->pdo->prepare("
            SELECT 
                candidates.name AS candidate_name,
                COALESCE(SUM(votes.votes_received), 0) AS total_votes
            FROM candidates
            LEFT JOIN votes ON candidates.candidate_id = votes.candidate_id
            WHERE candidates.district_id = :district_id
            GROUP BY candidates.candidate_id
            ORDER BY total_votes DESC
        ");
    $stmt->execute(['district_id' => $districtId]);
    $candidates = $stmt->fetchAll(PDO::FETCH_ASSOC);

    return [
      'district_info' => $districtInfo,
      'candidates' => $candidates
    ];
  }
}
