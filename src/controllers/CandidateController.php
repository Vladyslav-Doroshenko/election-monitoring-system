<?php

class CandidateController
{
  private $pdo;

  public function __construct($pdo)
  {
    $this->pdo = $pdo;
  }

  public function createCandidate($data)
  {
    $stmt = $this->pdo->prepare("INSERT INTO candidates 
            (name, full_name, date_of_birth, party, party_affiliation, district_id, bio, previous_experience, education, campaign_promises, contact_info, social_media_links)
            VALUES (:name, :full_name, :date_of_birth, :party, :party_affiliation, :district_id, :bio, :previous_experience, :education, :campaign_promises, :contact_info, :social_media_links)");

    $stmt->execute([
      'name'                => $data['name'],
      'full_name'           => $data['full_name'],
      'date_of_birth'       => $data['date_of_birth'],
      'party'               => $data['party'],
      'party_affiliation'   => $data['party_affiliation'],
      'district_id'         => (int)$data['district_id'],
      'bio'                 => $data['bio'],
      'previous_experience' => $data['previous_experience'],
      'education'           => $data['education'],
      'campaign_promises'   => $data['campaign_promises'],
      'contact_info'        => $data['contact_info'],
      'social_media_links'  => $data['social_media_links']
    ]);
  }

  public function updateCandidate($candidateId, $data)
  {
    $stmt = $this->pdo->prepare("UPDATE candidates SET 
            name = :name,
            full_name = :full_name,
            date_of_birth = :date_of_birth,
            party = :party,
            party_affiliation = :party_affiliation,
            district_id = :district_id,
            bio = :bio,
            previous_experience = :previous_experience,
            education = :education,
            campaign_promises = :campaign_promises,
            contact_info = :contact_info,
            social_media_links = :social_media_links
            WHERE candidate_id = :candidate_id");

    $stmt->execute([
      'name'                => $data['name'],
      'full_name'           => $data['full_name'],
      'date_of_birth'       => $data['date_of_birth'],
      'party'               => $data['party'],
      'party_affiliation'   => $data['party_affiliation'],
      'district_id'         => (int)$data['district_id'],
      'bio'                 => $data['bio'],
      'previous_experience' => $data['previous_experience'],
      'education'           => $data['education'],
      'campaign_promises'   => $data['campaign_promises'],
      'contact_info'        => $data['contact_info'],
      'social_media_links'  => $data['social_media_links'],
      'candidate_id'        => $candidateId
    ]);
  }

  // Видалення кандидата
  public function deleteCandidate($candidateId)
  {
    $stmt = $this->pdo->prepare("DELETE FROM candidates WHERE candidate_id = :candidate_id");
    $stmt->execute(['candidate_id' => $candidateId]);
  }

  // Отримання списку всіх кандидатів із приєднанням інформації про округ
  public function getAllCandidates()
  {
    $stmt = $this->pdo->query("SELECT candidates.candidate_id, candidates.name, candidates.full_name, candidates.party, candidates.party_affiliation, districts.name AS district_name, candidates.photo_url FROM candidates LEFT JOIN districts ON candidates.district_id = districts.district_id");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  // Отримання кандидата за ID
  public function getCandidateById($candidateId)
  {
    $stmt = $this->pdo->prepare("SELECT * FROM candidates WHERE candidate_id = :candidate_id");
    $stmt->execute(['candidate_id' => $candidateId]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
  }

  // Отримання списку всіх округів для вибору
  public function getDistrictsOptions($selectedId = null)
  {
    $stmt      = $this->pdo->query("SELECT district_id, name FROM districts");
    $districts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $options = '';
    foreach ($districts as $district) {
      $selected = $selectedId == $district['district_id'] ? 'selected' : '';
      $options  .= "<option value=\"{$district['district_id']}\" $selected>" . htmlspecialchars($district['name']) . "</option>";
    }

    return $options;
  }
}

