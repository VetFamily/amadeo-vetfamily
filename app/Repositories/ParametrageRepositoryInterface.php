<?php

namespace App\Repositories;

interface ParametrageRepositoryInterface
{
	public function findPurchagesLastUpdateDate();
	
	public function findCommitmentsClosingDate();
}