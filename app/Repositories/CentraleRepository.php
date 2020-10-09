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

	public function findAllForSelect()
	{
        return $this->centrale->select(['id', 'nom as name'])->orderBy('nom')->get()->toJson();
	}

	public function findAll()
	{
        return $this->centrale->where('obsolete', '=', '0')->orderBy('nom')->get();
	}

}