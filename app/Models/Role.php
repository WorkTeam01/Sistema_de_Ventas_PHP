<?php

namespace App\Models;

use App\Core\Model;

/**
 * Modelo para la tabla tb_roles.
 *
 * Hereda all(), find(), create(), update(), delete() y count() de Model.
 */
class Role extends Model
{
    protected string $table      = 'tb_roles';
    protected string $primaryKey = 'id_rol';
}
