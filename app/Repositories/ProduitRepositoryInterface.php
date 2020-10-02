<?php

namespace App\Repositories;

interface ProduitRepositoryInterface
{
	/**
	* Recherche la liste de tous les produits.
	*/
	public function findAll($labId);

	/**
	* Search detail for a product.
	*/
	public function findDetailById($id);

	/**
	* Recherche la liste de tous les produits d'une catégorie.
	*/
	public function findListByCategorieId($categorieId);

	/**
	* Recherche la liste de tous les produits d'un objectif.
	*/
	public function findListByObjectifId($objectifId);

	/**
	* Recherche la liste de tous les produits d'un objectif avec les volumes sur une période donnée.
	*/
	public function findListByObjectifIdAndMoisFin($objectifId, $moisFin);

	/**
	* Recherche la liste de tous les produits candidats d'une catégorie.
	*/
	public function findListCandidatsByLaboratoireAndCategorie($countryId, $laboratoireId, $produitsId);

	/**
	* Recherche la liste de tous les produits de tous les laboratoires avec les estimations de RFA pour téléchargement au format Excel.
	*/
	public function findEstimationsRFAForExcel($startMonth, $startYear, $endMonth, $endYear, $clinic, $lab, $clinicCodes, $targetYear);

	public function findBilanRFAForExcel($laboratoireId, $annee, $objectifsId, $cliniqueId);

	/**
	* Recherche la liste de tous les produits associés à des objectifs suivis avec le calcul du prix net pour téléchargement au format Excel.
	* La centrale prise en compte pour le tarif et la remise est Alcyon.
	*/
	public function findExtractionPrixNets($annee, $remiseCentrale);

	/*
	* Search products based on selected settings
	*/
	public function findAllByParams($laboratories, $productTypes, $productSpecies, $therapeuticClasses, $selectedProducts);

	public function findCountByParams($laboratories, $productTypes, $productSpecies, $therapeuticClasses);
}