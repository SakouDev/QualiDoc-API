<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Libraries\AuthContext;
use App\Models\RendezVousModel;
use DateTime;

class RendezVousController extends BaseController
{
    public function create()
    {
        $model = new RendezVousModel();

        if (! $this->validate($model->validationRules)) {
            return $this->failValidationErrors($this->validator->getErrors());
        }

        $dateHeure = $this->request->getVar('date_heure');
        $medecinId = (int) $this->request->getVar('medecin_id');

        if (strtotime($dateHeure) <= time()) {
            return $this->fail('La date du rendez-vous doit être dans le futur.', 400);
        }

        if ($model->isSlotTaken($medecinId, $dateHeure)) {
            return $this->fail('Ce créneau n\'est plus disponible pour ce médecin.', 409);
        }

        $id = $model->insert([
            'patient_id' => AuthContext::id(),
            'medecin_id' => $medecinId,
            'date_heure' => $dateHeure,
            'statut' => 'confirme',
        ]);

        return $this->respondCreated($model->withMedecin($id));
    }

    public function index()
    {
        return $this->respond((new RendezVousModel())->forPatient(AuthContext::id()));
    }

    public function delete($id)
    {
        $model = new RendezVousModel();
        $rdv = $model->find($id);

        // 404 (pas 403) pour ne pas révéler qu'un rendez-vous appartenant
        // à quelqu'un d'autre existe.
        if ($rdv === null || (int) $rdv['patient_id'] !== AuthContext::id()) {
            return $this->failNotFound('Rendez-vous introuvable.');
        }

        if ($rdv['statut'] !== 'confirme') {
            return $this->fail('Ce rendez-vous ne peut plus être annulé.', 409);
        }

        $model->update($id, ['statut' => 'annule']);

        return $this->respond($model->withMedecin($id));
    }

    public function adminIndex()
    {
        $model = new RendezVousModel();
        $medecinId = $this->request->getVar('medecin_id');
        $patientId = $this->request->getVar('patient_id');
        $dateFrom = $this->request->getVar('date_from');
        $dateTo = $this->request->getVar('date_to');

        foreach (['date_from' => $dateFrom, 'date_to' => $dateTo] as $field => $value) {
            if ($value !== null && DateTime::createFromFormat('Y-m-d H:i:s', $value) === false) {
                return $this->fail("Le champ {$field} doit être au format Y-m-d H:i:s.", 400);
            }
        }

        return $this->respond($model->adminSearch(
            $medecinId !== null ? (int) $medecinId : null,
            $patientId !== null ? (int) $patientId : null,
            $this->request->getVar('statut'),
            $dateFrom,
            $dateTo,
        ));
    }
}
