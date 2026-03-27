<?php

namespace App\Models;

use App\Core\Model;

class Category extends Model
{
    protected string $table      = 'tb_categorias';
    protected string $primaryKey = 'id_categoria';
}
