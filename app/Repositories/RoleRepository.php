<?php

namespace App\Repositories;

use App\Model\Role;
use DB;

class RoleRepository implements RoleRepositoryInterface
{

    protected $role;

	public function __construct(Role $role)
	{
		$this->role = $role;
	}

	public function findListByUserId($userId)
	{
        return $this->role
        			->join('role_user', 'role_user.role_id', '=', 'roles.id')
        			->where('role_user.user_id', '=', $userId)
        			->pluck('roles.nom')
        			->toArray();
	}

	public function findCliniqueIdByUserId($userId)
	{
		return $this->role
        			->join('role_user', 'role_user.role_id', '=', 'roles.id')
        			->where('role_user.user_id', '=', $userId)
        			->where('roles.nom', '=', 'Vétérinaire')
        			->pluck('role_user.clinique_id')
        			->first();
	}

	public function findLaboratoireIdByUserId($userId)
	{
		return $this->role
        			->join('role_user', 'role_user.role_id', '=', 'roles.id')
        			->where('role_user.user_id', '=', $userId)
        			->where('roles.nom', '=', 'Laboratoire')
        			->pluck('role_user.laboratoire_id')
        			->first();
	}

    public function isUserSuperAdmin($userId)
    {
        return $this->role
                    ->join('role_user', 'role_user.role_id', '=', 'roles.id')
                    ->where('role_user.user_id', '=', $userId)
                    ->where('roles.nom', '=', 'Administrateur')
                    ->pluck('role_user.super')
                    ->first();
    }

}