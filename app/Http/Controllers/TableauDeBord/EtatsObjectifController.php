<?php

namespace App\Http\Controllers\TableauDeBord;

use App\Http\Controllers\Controller;
use App\Model\EtatsObjectif;
use App\Repositories\EtatsObjectifRepository;
use Illuminate\Http\Response;

class EtatsObjectifController extends Controller
{
    private $etatsObjectifRepository;

    public function __construct(EtatsObjectifRepository $etatsObjectifRepository)
    {
        $this->etatsObjectifRepository = $etatsObjectifRepository;
    }

    public function index() {
        $data = $this->etatsObjectifRepository->findAll();
        return response()->json($data, Response::HTTP_OK);
    }

    public function findByYear($year)
    {
        $data = $this->etatsObjectifRepository->findByYear($year);
        return response()->json($data, Response::HTTP_OK);
    }

    public function findByYearAndState($year, $stateId)
    {
        $data = $this->etatsObjectifRepository->findByYearAndState($year, $stateId);
        return response()->json($data, Response::HTTP_OK);
    }

    public function findMonthlySesonalityByObjectifId($id) {
        $data = $this->etatsObjectifRepository->findMonthlySesonalityByObjectifId($id);
        return response()->json($data, Response::HTTP_OK);
    }

    public function findEvolutionAndParticipationRatesProducts($id) {
        $data = $this->etatsObjectifRepository->findEvolutionAndParticipationRatesProducts($id);
        return response()->json($data, Response::HTTP_OK);
    }

    public function findEvolutionAndParticipationRatesClinics($id) {
        $data = $this->etatsObjectifRepository->findEvolutionAndParticipationRatesClinics($id);
        return response()->json($data, Response::HTTP_OK);
    }
}
