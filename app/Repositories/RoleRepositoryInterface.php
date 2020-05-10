<?php

namespace App\Repositories;

interface RoleRepositoryInterface
{

	/**
	*	Recherche la liste des rôles pour l'utilisateur passé en paramètre.
	*/
    public function findListByUserId($userId);

    /**
	*	Recherche l'identifiant de la clinique si l'utilisateur est un vétérinaire.
	*/
    public function findCliniqueIdByUserId($userId);

    /**
	*	Recherche l'identifiant du laboratoire si l'utilisateur est un laboratoire.
	*/
    public function findLaboratoireIdByUserId($userId);

    /**
	*	Recherche si l'utilisateur est un super administrateur.
	*/
    public function isUserSuperAdmin($userId);
}