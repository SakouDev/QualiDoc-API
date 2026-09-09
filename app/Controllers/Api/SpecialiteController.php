<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\MedecinModel;
use App\Models\SpecialiteModel;

class SpecialiteController extends BaseController
{
    public function index()
    {
        return $this->respond((new SpecialiteModel())->findAll());
    }

    public function create()
    {
        $model = new SpecialiteModel();

        $rules = $model->validationRules;
        $rules['nom'] .= '|is_unique[specialites.nom]';

        if (! $this->validate($rules)) {
            return $this->failValidationErrors($this->validator->getErrors());
        }

        $id = $model->insert(['nom' => $this->request->getVar('nom')]);

        return $this->respondCreated($model->find($id));
    }

    public function update($id)
    {
        $model = new SpecialiteModel();

        if ($model->find($id) === null) {
            return $this->failNotFound('Spécialité introuvable.');
        }

        $data = $this->presentFields(['nom']);

        if ($data === []) {
            return $this->fail('Aucun champ à mettre à jour.', 400);
        }

        $rules = $model->validationRules;
        $rules['nom'] .= "|is_unique[specialites.nom,id,{$id}]";
        $rules = array_intersect_key($rules, $data);

        if (! $this->validateData($data, $rules)) {
            return $this->failValidationErrors($this->validator->getErrors());
        }

        $model->update($id, $data);

        return $this->respond($model->find($id));
    }

    public function delete($id)
    {
        $model = new SpecialiteModel();

        if ($model->find($id) === null) {
            return $this->failNotFound('Spécialité introuvable.');
        }

        if ((new MedecinModel())->where('specialite_id', $id)->countAllResults() > 0) {
            return $this->fail('Impossible de supprimer : des médecins ont encore cette spécialité.', 409);
        }

        $model->delete($id);

        return $this->respondDeleted(['id' => (int) $id]);
    }
}
