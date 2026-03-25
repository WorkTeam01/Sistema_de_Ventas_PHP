<?php

namespace App\Core;

abstract class Middleware
{
    abstract public function handle(): void;
}
