<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\DisponibiliteModel;
use App\Models\MedecinModel;
use DateTime;

class MedecinController extends BaseController
{
    public function search()
    {
        return $this->respond(
            (new MedecinModel())->search($this->request->getVar('q'))
        );
    }

    public function creneaux($id)
    {
        $date = $this->request->getVar('date');

        if ($date === null || DateTime::createFromFormat('Y-m-d', $date) === false) {
            return $this->fail('Le paramètre date (format Y-m-d) est requis.', 400);
        }

        if ((new MedecinModel())->find($id) === null) {
            return $this->failNotFound('Médecin introuvable.');
        }

        return $this->respond((new DisponibiliteModel())->creneauxPour((int) $id, $date));
    }

    public function create()
    {
        $model = new MedecinModel();

        if (! $this->validate($model->validationRules)) {
            return $this->failValidationErrors($this->validator->getErrors());
        }

        $id = $model->insert($this->payload());

        // Planning de base auto-assigné (l'admin peut ensuite l'ajuster
        // via /admin/disponibilites) — cf. DisponibiliteModel::seedDefault().
        (new DisponibiliteModel())->seedDefault($id);

        return $this->respondCreated($model->find($id));
    }

    public function update($id)
    {
        $model = new MedecinModel();

        if ($model->find($id) === null) {
            return $this->failNotFound('Médecin introuvable.');
        }

        // PATCH : seuls les champs envoyés sont mis à jour (contrairement à
        // create(), pas besoin que les 3 champs soient tous présents).
        $data = $this->presentFields(['nom', 'prenom', 'specialite_id']);

        if ($data === []) {
            return $this->fail('Aucun champ à mettre à jour.', 400);
        }

        if (isset($data['specialite_id'])) {
            $data['specialite_id'] = (int) $data['specialite_id'];
        }

        $rules = array_intersect_key($model->validationRules, $data);

        if (! $this->validateData($data, $rules)) {
            return $this->failValidationErrors($this->validator->getErrors());
        }

        $model->update($id, $data);

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

        if ($hasRdv) {
            return $this->fail('Impossible de supprimer : ce médecin a des rendez-vous enregistrés.', 409);
        }

        // Les disponibilités ne sont qu'un planning, pas un historique à
        // protéger — elles disparaissent avec le médecin plutôt que de
        // bloquer sa suppression (pas de contrainte ON DELETE CASCADE en
        // base, donc on les supprime explicitement avant).
        (new DisponibiliteModel())->where('medecin_id', $id)->delete();

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
