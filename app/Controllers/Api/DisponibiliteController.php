<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\DisponibiliteModel;

class DisponibiliteController extends BaseController
{
    public function index()
    {
        $medecinId = $this->request->getVar('medecin_id');

        if ($medecinId === null) {
            return $this->fail('Le paramètre medecin_id est requis.', 400);
        }

        return $this->respond((new DisponibiliteModel())->forMedecin((int) $medecinId));
    }

    public function create()
    {
        $model = new DisponibiliteModel();

        if (! $this->validate($model->validationRules)) {
            return $this->failValidationErrors($this->validator->getErrors());
        }

        $heureDebut = $this->request->getVar('heure_debut');
        $heureFin = $this->request->getVar('heure_fin');

        if ($heureFin <= $heureDebut) {
            return $this->fail('heure_fin doit être après heure_debut.', 400);
        }

        $id = $model->insert([
            'medecin_id' => (int) $this->request->getVar('medecin_id'),
            'date_dispo' => $this->request->getVar('date_dispo'),
            'heure_debut' => $heureDebut,
            'heure_fin' => $heureFin,
            'duree_creneau' => (int) $this->request->getVar('duree_creneau'),
        ]);

        return $this->respondCreated($model->find($id));
    }

    public function delete($id)
    {
        $model = new DisponibiliteModel();

        if ($model->find($id) === null) {
            return $this->failNotFound('Disponibilité introuvable.');
        }

        $model->delete($id);

        return $this->respondDeleted(['id' => (int) $id]);
    }
}
