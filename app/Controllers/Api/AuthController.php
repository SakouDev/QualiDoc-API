<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Libraries\AuthContext;
use App\Libraries\JwtService;
use App\Models\PatientModel;

class AuthController extends BaseController
{
    public function me()
    {
        $patient = (new PatientModel())->find(AuthContext::id());

        return $this->respond([
            'id' => $patient['id'],
            'nom' => $patient['nom'],
            'prenom' => $patient['prenom'],
            'email' => $patient['email'],
            'admin' => (bool) $patient['admin'],
        ]);
    }

    public function register()
    {
        $model = new PatientModel();

        $rules = $model->validationRules;
        $rules['password'] = 'required|min_length[8]';

        if (! $this->validate($rules)) {
            return $this->failValidationErrors($this->validator->getErrors());
        }

        $patientId = $model->insert([
            'nom' => $this->request->getVar('nom'),
            'prenom' => $this->request->getVar('prenom'),
            'email' => $this->request->getVar('email'),
            'password_hash' => password_hash($this->request->getVar('password'), PASSWORD_DEFAULT),
            'admin' => false,
        ]);

        $token = (new JwtService())->generate($patientId, false);

        return $this->respondCreated([
            'token' => $token,
            'patient' => [
                'id' => $patientId,
                'nom' => $this->request->getVar('nom'),
                'prenom' => $this->request->getVar('prenom'),
                'email' => $this->request->getVar('email'),
                'admin' => false,
            ],
        ]);
    }

    public function login()
    {
        if (! $this->validate([
            'email' => 'required|valid_email',
            'password' => 'required',
        ])) {
            return $this->failValidationErrors($this->validator->getErrors());
        }

        $model = new PatientModel();
        $patient = $model->where('email', $this->request->getVar('email'))->first();

        if ($patient === null || ! password_verify($this->request->getVar('password'), $patient['password_hash'])) {
            return $this->failUnauthorized('Identifiants invalides.');
        }

        $token = (new JwtService())->generate($patient['id'], (bool) $patient['admin']);

        return $this->respond([
            'token' => $token,
            'patient' => [
                'id' => $patient['id'],
                'nom' => $patient['nom'],
                'prenom' => $patient['prenom'],
                'email' => $patient['email'],
                'admin' => (bool) $patient['admin'],
            ],
        ]);
    }
}
