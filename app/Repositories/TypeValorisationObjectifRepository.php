<?php

namespace App\Repositories;

use App\Model\TypeValorisationObjectif;
use DB;

class TypeValorisationObjectifRepository implements TypeValorisationObjectifRepositoryInterface
{

    protected $type;

	public function __construct(TypeValorisationObjectif $type)
	{
		$this->type = $type;
	}

	public function findAll()
	{
        return $this->type->where('obsolete', '=', '0')->orderBy('id')->get();
	}

}