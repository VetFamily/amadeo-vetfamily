<?php

namespace App\Repositories;

use App\Model\Type;
use DB;

class TypeRepository implements TypeRepositoryInterface
{

    protected $type;

	public function __construct(Type $type)
	{
		$this->type = $type;
	}

	public function findAll()
	{
        return $this->type->where('obsolete', '=', '0')->orderBy('nom')->get();
	}

}