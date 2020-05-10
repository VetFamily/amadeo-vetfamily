<?php

namespace App\Repositories;

use App\Model\TypeObjectif;
use DB;

class TypeObjectifRepository implements TypeObjectifRepositoryInterface
{

    protected $type;

	public function __construct(TypeObjectif $type)
	{
		$this->type = $type;
	}

	public function findAll()
	{
        return $this->type->where('obsolete', '=', '0')->orderBy('id')->get();
	}

}