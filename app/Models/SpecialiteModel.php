<?php

namespace App\Models;

use CodeIgniter\Model;

class SpecialiteModel extends Model
{
    protected $table = 'specialites';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = ['nom'];
}
