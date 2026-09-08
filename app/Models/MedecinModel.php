<?php

namespace App\Models;

use CodeIgniter\Model;

class MedecinModel extends Model
{
    protected $table = 'medecins';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = ['nom', 'prenom', 'specialite_id'];

    protected $validationRules = [
        'nom' => 'required|max_length[100]',
        'prenom' => 'required|max_length[100]',
        'specialite_id' => 'required|is_natural_no_zero|is_not_unique[specialites.id]',
    ];

    /**
     * Recherche "un seul champ" façon Doctolib : q est comparé en préfixe
     * (LIKE 'terme%', donc utilisable par un index classique) sur nom,
     * prénom et spécialité.
     */
    public function search(?string $q): array
    {
        $builder = $this->select('medecins.*, specialites.nom AS specialite_nom')
            ->join('specialites', 'specialites.id = medecins.specialite_id');

        if ($q !== null && trim($q) !== '') {
            $words = preg_split('/\s+/', mb_strtolower(trim($q), 'UTF-8'), -1, PREG_SPLIT_NO_EMPTY);

            // chaque mot doit matcher au moins une des 3 colonnes (OR),
            // et tous les mots doivent matcher (AND entre les groupes) :
            // "jean dur" -> (nom|prenom|specialite ~ jean%) AND (nom|prenom|specialite ~ dur%)
            foreach ($words as $word) {
                // échappe les caractères spéciaux de LIKE (%, _) : sinon
                // q="%" ou q="_" matche n'importe quoi au lieu du caractère
                // littéral tapé par l'utilisateur.
                $safeWord = $this->db->escapeLikeString($word);

                $builder->groupStart()
                    ->like('medecins.nom', $safeWord, 'after')
                    ->orLike('medecins.prenom', $safeWord, 'after')
                    ->orLike('specialites.nom', $safeWord, 'after')
                    ->groupEnd();
            }
        }

        return $builder->orderBy('medecins.nom')->orderBy('medecins.prenom')->findAll();
    }
}
