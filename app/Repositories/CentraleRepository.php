<?php

namespace App\Repositories;

use App\Model\Centrale;
use DB;

class CentraleRepository implements CentraleRepositoryInterface
{

    protected $centrale;

	public function __construct(Centrale $centrale)
	{
		$this->centrale = $centrale;
	}

	public function findAll()
	{
        return $this->centrale->where('obsolete', '=', '0')->orderBy('nom')->get();
	}

}