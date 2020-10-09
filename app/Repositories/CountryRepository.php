<?php

namespace App\Repositories;

use App\Model\Country;
use Carbon\Carbon;
use DB;

class CountryRepository implements CountryRepositoryInterface
{

    protected $country;

	public function __construct(Country $country)
	{
		$this->country = $country;
	}

	public function findAllForSelect()
	{
        return $this->country->select(['ctry_id as id', 'ctry_name as name'])->orderBy('ctry_name')->get()->toJson();
	}

	public function findAll()
	{
        return $this->country->orderBy('ctry_name')->get();
	}

}