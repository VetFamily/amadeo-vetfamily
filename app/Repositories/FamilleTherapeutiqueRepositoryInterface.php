<?php

namespace App\Repositories;

interface FamilleTherapeutiqueRepositoryInterface
{
	public function findAll();

	public function findAllByParams($laboratories, $productTypes, $productSpecies);
}