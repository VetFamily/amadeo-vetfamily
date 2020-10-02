<?php

namespace App\Repositories;

interface CountryRepositoryInterface
{

    public function findAllForSelect();

    public function findAll();

}