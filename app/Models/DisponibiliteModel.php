<?php

namespace App\Models;

use CodeIgniter\Model;

class DisponibiliteModel extends Model
{
    protected $table = 'disponibilites';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = ['medecin_id', 'date_dispo', 'heure_debut', 'heure_fin', 'duree_creneau'];

    protected $validationRules = [
        'medecin_id' => 'required|is_natural_no_zero|is_not_unique[medecins.id]',
        'date_dispo' => 'required|valid_date[Y-m-d]',
        'heure_debut' => 'required|valid_date[H:i:s]',
        'heure_fin' => 'required|valid_date[H:i:s]',
        'duree_creneau' => 'required|is_natural_no_zero',
    ];

    /**
     * Planning "de base" attribué automatiquement à tout nouveau médecin :
     * Lun-Ven, 9h-12h + 14h-18h, créneaux de 20min, sur les $weeks
     * prochaines semaines. L'admin peut ensuite ajuster via le CRUD
     * (ajouter/supprimer des plages) — c'est un point de départ, pas figé.
     */
    public function seedDefault(int $medecinId, int $weeks = 2): void
    {
        $rows = [];
        $date = new \DateTime('tomorrow');
        $end = (clone $date)->modify("+{$weeks} weeks");

        while ($date < $end) {
            if ((int) $date->format('N') <= 5) {
                $jour = $date->format('Y-m-d');

                $rows[] = ['medecin_id' => $medecinId, 'date_dispo' => $jour, 'heure_debut' => '09:00:00', 'heure_fin' => '12:00:00', 'duree_creneau' => 20];
                $rows[] = ['medecin_id' => $medecinId, 'date_dispo' => $jour, 'heure_debut' => '14:00:00', 'heure_fin' => '18:00:00', 'duree_creneau' => 20];
            }

            $date->modify('+1 day');
        }

        $this->insertBatch($rows);
    }

    public function forMedecin(int $medecinId): array
    {
        return $this->where('medecin_id', $medecinId)
            ->orderBy('date_dispo')
            ->orderBy('heure_debut')
            ->findAll();
    }

    /**
     * Découpe les plages [heure_debut, heure_fin] déclarées pour ce
     * médecin à cette date en créneaux de `duree_creneau` minutes, et
     * marque chacun disponible/pris en croisant avec les RDV confirmés.
     */
    public function creneauxPour(int $medecinId, string $date): array
    {
        $blocks = $this->where('medecin_id', $medecinId)->where('date_dispo', $date)->findAll();

        $prisRows = (new RendezVousModel())
            ->select('date_heure')
            ->where('medecin_id', $medecinId)
            ->where('statut', 'confirme')
            ->where('date_heure >=', $date . ' 00:00:00')
            ->where('date_heure <=', $date . ' 23:59:59')
            ->findAll();

        $pris = array_flip(array_column($prisRows, 'date_heure'));

        $creneaux = [];

        foreach ($blocks as $block) {
            $start = strtotime($date . ' ' . $block['heure_debut']);
            $end = strtotime($date . ' ' . $block['heure_fin']);
            $step = (int) $block['duree_creneau'] * 60;

            for ($t = $start; $t < $end; $t += $step) {
                $dateHeure = date('Y-m-d H:i:s', $t);

                $creneaux[] = [
                    'date_heure' => $dateHeure,
                    'disponible' => ! isset($pris[$dateHeure]),
                ];
            }
        }

        return $creneaux;
    }
}
