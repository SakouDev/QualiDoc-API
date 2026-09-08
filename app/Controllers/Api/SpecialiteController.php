<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\SpecialiteModel;

class SpecialiteController extends BaseController
{
    public function index()
    {
        return $this->respond((new SpecialiteModel())->findAll());
    }
}
