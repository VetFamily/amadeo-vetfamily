<?php

namespace App\Http\Controllers\Ajax;

use App\Http\Controllers\Controller;
use App\Model\Categorie_produit;
use App\Model\Categorie_produit_objectif;
use App\Model\Objectif;
use App\Model\Objectif_commentaires;
use App\Repositories\AchatRepository;
use App\Repositories\CliniqueRepository;
use App\Repositories\ObjectifRepository;
use App\Repositories\ProduitRepository;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Session;
use Validator;

class ObjectifAjaxController extends Controller
{
  private $objectifRepository;
  private $achatRepository;

  public function __construct(ObjectifRepository $objectifRepository, AchatRepository $achatRepository)
  {
      $this->objectifRepository = $objectifRepository;
      $this->achatRepository = $achatRepository;
  }

  /**
   * Display a listing of the resource.
   *
   * @return Response
   */
  public function index(Request $request, ObjectifRepository $objectifRepository)
  {
    $objectifs = $objectifRepository->findByLaboratoireIdAndMoisFin(Session::get('user_laboratoire_id'), $request->mois_fin);

    return response()->json($objectifs);
  }

  /**
   * Show the form for creating a new resource.
   *
   * @return Response
   */
  public function create()
  {
  }

  /**
   * Store a newly created resource in storage.
   *
   * @return Response
   */
  public function store(Request $request, ProduitRepository $produitRepository, ObjectifRepository $objectifRepository, CliniqueRepository $cliniqueRepository)
  {
    if ($request->isCopy) {
      $validator = Validator::make($request->all(), [
        'ancien_objectif' => 'required',
        'nom' => 'required|max:255'
      ]);

      $input = $request->all();

      if ($validator->passes()) {
        // Récupération de l'objectif d'origine
        $objectif = Objectif::find($request->ancien_objectif);
        $newObjectif = $objectif->replicate();
        $newObjectif->nom = $request->nom;
        $newObjectif->objectif_precedent_id = NULL;
        $newObjectif->save();

        // Insertion des produits associés à l'objectif
        $produitsObjectifToInsert = [];
        $objectifProduits = Categorie_produit_objectif::where('objectif_id', $request->ancien_objectif)->get();
        foreach ($objectifProduits as $objectifProduit) {
          $produitsObjectifToInsert[] = ['objectif_id' => $newObjectif->id, 'categorie_produit_id' => $objectifProduit->categorie_produit_id, 'pourcentage_remise' => $objectifProduit->pourcentage_remise, 'pourcentage_remise_source' => $objectifProduit->pourcentage_remise_source];
        }
        if (sizeof($produitsObjectifToInsert) > 0) {
          Categorie_produit_objectif::insert($produitsObjectifToInsert);
        }

        $saveObjectif = $objectifRepository->findById($newObjectif->id);

        return response()->json(['success' => 1, 'obj' => $saveObjectif]);
      }
    } else {
      $validator = Validator::make($request->all(), [
        'categorie' => 'required',
        'nom' => 'required|max:255'
      ]);

      $input = $request->all();

      if ($validator->passes()) {
        // Création de l'objectif
        $data = [
          'nom' => $request->nom, 
          'categorie_id' => $request->categorie, 
          'type_objectif_id' => 1,
          'type_valorisation_objectif_id' => 1, 
          'valorisation_laboratoire' => 'Valorisation en euros', 
          'valorisation_remise' => 'Valorisation en euros', 
          'suivi' => 1
        ];
        $idObjectif = Objectif::create($data)->id;

        // Création des produits de l'objectif
        $insertProduits = [];
        $categorieProduits = Categorie_produit::where('categorie_id', $request->categorie)->get();
        foreach ($categorieProduits as $categorieProduit) {
          $insertProduits[] = ['objectif_id' => $idObjectif, 'categorie_produit_id' => $categorieProduit->id];
        }
        Categorie_produit_objectif::insert($insertProduits);

        $maxDateAchats = $this->achatRepository->findLastDateOfPurchasesByYear($request->annee);
        $objectif = $objectifRepository->findCAById($idObjectif, 3, date("m",strtotime($maxDateAchats)));

        return response()->json(['success' => 1, 'objectif' => $objectif]);
      }
    }

    return response()->json(['errors' => $validator->errors()]);
  }

  /**
   * Display the specified resource.
   *
   * @param  int  $id
   * @return Response
   */
  public function show($id, Request $request, ObjectifRepository $targetRepository)
  {
    if ($request->comment)
    {
      // Recherche les commentaires d'un objectif
      $commentaires = $targetRepository->findListCommentsByObjectifId($id);
  
      return response()->json($commentaires);
    } else 
    {
      $target = $targetRepository->findDetailById($id);
    
      return response()->json(['target' => $target]);
    }
  }

  /**
   * Show the form for editing the specified resource.
   *
   * @param  int  $id
   * @return Response
   */
  public function edit($id)
  {
  }

  private function getPalierPrecedent($objectif)
  {
    $objectifPrecedent = Objectif::where('id', $objectif->objectif_precedent_id)->first();

    if ($objectifPrecedent != null) {
      return $this->getPalierPrecedent($objectifPrecedent);
    } else {
      return $objectif;
    }
  }

  private function updateSuiviPalier($objectif)
  {
    if ($objectif->valeur_atteinte) {
      $objectifSuivant = Objectif::where('objectif_precedent_id', $objectif->id)->first();

      if ($objectifSuivant != null) {
        if ($objectif->suivi) {
          Objectif::where('id', $objectif->id)->update(['suivi' => false]);
          Objectif::where('id', $objectifSuivant->id)->update(['suivi' => true]);
        }
        $this->updateSuiviPalier($objectifSuivant);
      }
    }

    return;
  }

  /**
   * Update the specified resource in storage.
   *
   * @param  \Illuminate\Http\Request  $request
   * @param  int  $id
   * @return Response
   */
  public function update(Request $request, $id, CliniqueRepository $cliniqueRepository, ObjectifRepository $objectifRepository)
  {
    if ($request->isSuivi) {
      // Modification du suivi uniquement
      $saveObjectif = Objectif::where('id', $id)->update(['suivi' => $request->suivi]);
    } else {
      $validator = Validator::make($request->all(), [
        'nom' => 'max:255'
      ]);

      if ($validator->passes()) {
        // Mise à jour de l'objectif
        $objectif = Objectif::where('id', $id)->first();
        $objectif = $objectifRepository->updateObjectifFromRequest($objectif, $request);
        // Mise à jour des commentaires
        if ($request->commentaires != null) {
          Objectif_commentaires::insert($request->commentaires);
        }
        // Mise à jour du CA et de l'indicateur 'Atteint' de l'objectif + ( ecart, ecart_unite et etat_objectif_id)
        $maxDateAchats = $this->achatRepository->findLastDateOfPurchasesByYear($request->annee);
        $saveObjectif = $this->objectifRepository->updateCAStateAndEcart($objectif, $maxDateAchats);
        // Mise à jour du suivi des objectifs s'il s'agit d'un palier
        if ($request->typeObjectif == 2) {
          $premierPalier = $this->getPalierPrecedent($objectif);
          $this->updateSuiviPalier($premierPalier);
        }
        // Mise à jour des remises des produits associés à l'objectif
        if ($request->produits != null) {
          foreach ($request->produits as $produit) {
            Categorie_produit_objectif::where('id', $produit["cat_prod_obj_id"])->update(['pourcentage_remise' => $produit["remise"], 'pourcentage_remise_source' => $produit["remise_source"]]);
          }
        }
      }
    }
    return response()->json(['objectif' => $saveObjectif]);
  }

  /**
   * Remove the specified resource from storage.
   *
   * @param  int  $id
   * @return Response
   */
  public function destroy($id)
  {
    $ids = explode(",", $id);
    $deleteObjectifs = Objectif::whereIn('id', $ids)->update(['obsolete' => 1]);

    // Update references of objectifs 
    Objectif::whereIn('objectif_precedent_id', $ids)->update(['objectif_precedent_id' => NULL]);
    Objectif::whereIn('objectif_conditionne_id', $ids)->update(['objectif_conditionne_id' => NULL]);

    return $deleteObjectifs;
  }
  
  public function updateAll($year)
  {
    $objectifs = Objectif::select('objectifs.*')
                            ->join('categories', 'categories.id', '=', 'objectifs.categorie_id')
                            ->where('categories.annee', '=', $year)
                            ->where('objectifs.obsolete', 0)
                            ->get();

    $maxDateAchats = $this->achatRepository->findLastDateOfPurchasesByYear($year);
    foreach ($objectifs as $objectif) {
      $this->objectifRepository->updateCAStateAndEcart($objectif, $maxDateAchats);
    }

    // Mise à jour du suivi des objectifs s'il s'agit d'un palier : MAJ AUTOMATIQUE NON VOULU PAR LE CLIENT
    $objectifsPaliers = Objectif::select('objectifs.*')
                                  ->join('categories', 'categories.id', '=', 'objectifs.categorie_id')
                                  ->where('categories.annee', '=', $year)
                                  ->where('objectifs.type_objectif_id', 2)
                                  ->whereNotNull('objectifs.objectif_precedent_id')
                                  ->where('objectifs.obsolete', 0)
                                  ->get();
    foreach ($objectifsPaliers as $objectif) {
      $this->updateSuiviPalier($objectif);
    }

    return response()->json(['nb_objectifs' => sizeof($objectifs)], Response::HTTP_OK);
  }
}
