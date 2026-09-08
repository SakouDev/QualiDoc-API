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
}
