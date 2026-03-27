<?php

namespace App\Models;

use App\Core\Model;

/**
 * Modelo para la tabla tb_categorias.
 *
 * Hereda all(), find(), create(), update(), delete() y count() de Model.
 */
class Category extends Model
{
    protected string $table      = 'tb_categorias';
    protected string $primaryKey = 'id_categoria';
}
