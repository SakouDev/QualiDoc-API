<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\MedecinModel;

class MedecinController extends BaseController
{
    public function search()
    {
        return $this->respond(
            (new MedecinModel())->search($this->request->getVar('q'))
        );
    }

    public function create()
    {
        $model = new MedecinModel();

        if (! $this->validate($model->validationRules)) {
            return $this->failValidationErrors($this->validator->getErrors());
        }

        $id = $model->insert($this->payload());

        return $this->respondCreated($model->find($id));
    }

    public function update($id)
    {
        $model = new MedecinModel();

        if ($model->find($id) === null) {
            return $this->failNotFound('Médecin introuvable.');
        }

        if (! $this->validate($model->validationRules)) {
            return $this->failValidationErrors($this->validator->getErrors());
        }

        $model->update($id, $this->payload());

        return $this->respond($model->find($id));
    }

    public function delete($id)
    {
        $model = new MedecinModel();

        if ($model->find($id) === null) {
            return $this->failNotFound('Médecin introuvable.');
        }

        $db = db_connect();
        $hasRdv = $db->table('rendez_vous')->where('medecin_id', $id)->countAllResults() > 0;
        $hasDispo = $db->table('disponibilites')->where('medecin_id', $id)->countAllResults() > 0;

        if ($hasRdv || $hasDispo) {
            return $this->fail('Impossible de supprimer : ce médecin a des rendez-vous ou des disponibilités enregistrés.', 409);
        }

        $model->delete($id);

        return $this->respondDeleted(['id' => (int) $id]);
    }

    private function payload(): array
    {
        return [
            'nom' => $this->request->getVar('nom'),
            'prenom' => $this->request->getVar('prenom'),
            'specialite_id' => (int) $this->request->getVar('specialite_id'),
        ];
    }
}
