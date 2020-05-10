<?php

namespace App\Repositories;

use App\Model\Espece;
use DB;

class EspeceRepository implements EspeceRepositoryInterface
{

    protected $espece;

	public function __construct(Espece $espece)
	{
		$this->espece = $espece;
	}

	public function findAll()
	{
        return $this->espece->where('obsolete', '=', '0')->orderBy('nom')->get();
	}

}