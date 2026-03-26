<?php

namespace App\Models;

use App\Core\Model;

class Role extends Model
{
    protected string $table      = 'tb_roles';
    protected string $primaryKey = 'id_rol';
}
