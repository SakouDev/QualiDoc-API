<?php

namespace App\Models;

use CodeIgniter\Model;

class RendezVousModel extends Model
{
    protected $table = 'rendez_vous';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = ['patient_id', 'medecin_id', 'date_heure', 'statut'];

    /**
     * Format d'échange front -> API pour date_heure : 'Y-m-d H:i:s'
     * (ex: "2026-09-20 14:00:00"), format natif MySQL DATETIME.
     */
    protected $validationRules = [
        'medecin_id' => 'required|is_natural_no_zero|is_not_unique[medecins.id]',
        'date_heure' => 'required|valid_date[Y-m-d H:i:s]',
    ];

    /**
     * Un créneau (médecin + date_heure) ne peut avoir qu'un seul RDV
     * 'confirme' à la fois — un médecin ne peut pas voir 2 patients en
     * même temps. Un créneau annulé libère la place.
     */
    public function isSlotTaken(int $medecinId, string $dateHeure): bool
    {
        return $this->where('medecin_id', $medecinId)
            ->where('date_heure', $dateHeure)
            ->where('statut', 'confirme')
            ->countAllResults() > 0;
    }

    public function forPatient(int $patientId): array
    {
        return $this->withJoins()
            ->where('rendez_vous.patient_id', $patientId)
            ->orderBy('rendez_vous.date_heure', 'DESC')
            ->findAll();
    }

    public function withMedecin(int $id): ?array
    {
        return $this->withJoins()->where('rendez_vous.id', $id)->first();
    }

    public function adminSearch(?int $medecinId, ?int $patientId, ?string $statut, ?string $dateFrom, ?string $dateTo): array
    {
        $builder = $this->withJoins()
            ->select('patients.nom AS patient_nom, patients.prenom AS patient_prenom')
            ->join('patients', 'patients.id = rendez_vous.patient_id');

        if ($medecinId !== null) {
            $builder->where('rendez_vous.medecin_id', $medecinId);
        }

        if ($patientId !== null) {
            $builder->where('rendez_vous.patient_id', $patientId);
        }

        if ($statut !== null) {
            $builder->where('rendez_vous.statut', $statut);
        }

        if ($dateFrom !== null) {
            $builder->where('rendez_vous.date_heure >=', $dateFrom);
        }

        if ($dateTo !== null) {
            $builder->where('rendez_vous.date_heure <=', $dateTo);
        }

        return $builder->orderBy('rendez_vous.date_heure', 'DESC')->findAll();
    }

    private function withJoins()
    {
        // Colonnes listées explicitement plutôt que "rendez_vous.*" pour ne
        // pas exposer slot_confirme (colonne générée, détail d'implémentation
        // de la contrainte anti-double-réservation, pas une donnée métier).
        return $this->select('rendez_vous.id, rendez_vous.patient_id, rendez_vous.medecin_id, rendez_vous.date_heure, rendez_vous.statut, rendez_vous.created_at, medecins.nom AS medecin_nom, medecins.prenom AS medecin_prenom, specialites.nom AS specialite_nom')
            ->join('medecins', 'medecins.id = rendez_vous.medecin_id')
            ->join('specialites', 'specialites.id = medecins.specialite_id');
    }
}
