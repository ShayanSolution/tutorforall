<?php

namespace App\Repositories\Contracts;

interface SearchLocationInterface
{
    public function searchLocation($request);
    public function locations($id);
}
