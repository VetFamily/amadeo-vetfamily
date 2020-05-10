<?php

namespace App\Repositories;

interface CliniqueRepositoryInterface
{

    public function findAllForSelect($userId);

    public function findAll($year, $userClinicId);

    public function findById($id);

    public function findDetailById($id);

    public function findAllForExportCSV($clinicIds);

    public function findCAByObjectifId($objectifId, $annee);

    public function findCodesCentralesById($id);

    /*
	* Search clinics based on selected settings
	*/
    public function findAllByParams($clinicYears, $selectedClinics, $userCliniqueId);
    
    public function findCountByParams($clinicYears, $userCliniqueId);
}