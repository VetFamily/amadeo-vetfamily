<?php

namespace App\Http\Controllers\Ajax;

use App\Http\Controllers\Controller;
use App\Model\Categorie;
use App\Model\Categorie_commentaires;
use App\Model\Categorie_espece;
use App\Model\Categorie_produit;
use App\Model\Categorie_produit_objectif;
use App\Model\Objectif;
use App\Repositories\AchatRepository;
use App\Repositories\CategorieRepository;
use App\Repositories\ObjectifRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Session;
use Validator;

class CategorieAjaxController extends Controller
{
  private $achatRepository;

  public function __construct(AchatRepository $achatRepository)
  {
      $this->achatRepository = $achatRepository;
  }

  /**
   * Display a listing of the resource.
   *
   * @return Response
   */
  public function index(CategorieRepository $categorieRepository)
  {
    $categories = $categorieRepository->findAll(Session::get('user_laboratoire_id'));

    return response()->json($categories);
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
  public function store(Request $request, CategorieRepository $categorieRepository)
  {
    $validator = Validator::make($request->all(), [
      'annee' => 'required|max:4',
      'nom' => 'required|max:255'
    ]);

    $input = $request->all();

    if ($validator->passes()) {
      // Création de la catégorie
      $data = ['nom' => $request->nom, 'annee' => $request->annee];

      if ($request->laboratoire != 'Multi-laboratoires') {
        $data['laboratoire_id'] = $request->laboratoire;
      }

      $idCategorie = Categorie::create($data)->id;

      // Insertion des espèces de la catégorie
      $especesToInsert = [];
      if ($request->especes != null) {
        foreach ($request->especes as $espece) {
          $especesToInsert[] = ['categorie_id' => $idCategorie, 'espece_id' => $espece];
        }
        if (sizeof($especesToInsert) > 0) {
          Categorie_espece::insert($especesToInsert);
        }
      }

      // Insertion des produits de la catégorie
      $produitsToInsert = [];
      if ($request->produits != null) {
        foreach ($request->produits as $produit) {
          $produitsToInsert[] = ['categorie_id' => $idCategorie, 'produit_id' => $produit];
        }
        if (sizeof($produitsToInsert) > 0) {
          Categorie_produit::insert($produitsToInsert);
        }
      }

      $categorie = $categorieRepository->findById($idCategorie);

      return response()->json(['success' => 1, 'categorie' => $categorie]);
    }

    return response()->json(['errors' => $validator->errors()]);
  }

  /**
   * Display the specified resource.
   *
   * @param  int  $id
   * @return Response
   */
  public function show($id, CategorieRepository $categorieRepository)
  {
    // Recherche les commentaires d'une catégorie
    $commentaires = $categorieRepository->findListCommentsByCategorieId($id);

    return response()->json($commentaires);
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

  private function updateEspecesCategorie($id, $selectEspeces)
  {
    $oldEspeces = Categorie_espece::where('categorie_id', $id)->pluck('espece_id')->toArray();

    // Suppression de toutes les espèces de $oldEspeces qui ne sont pas dans $selectEspeces
    $especesToDelete = [];
    foreach ($oldEspeces as $espece) {
      if ($selectEspeces == null || !in_array($espece, $selectEspeces)) {
        $especesToDelete[] = $espece;
      }
    }
    if (sizeof($especesToDelete) > 0) {
      Categorie_espece::where('categorie_id', $id)->whereIn('espece_id', $especesToDelete)->delete();
    }

    // Ajout de toutes les espèces de $selectEspeces qui ne sont pas dans $oldEspeces
    $especesToInsert = [];
    if ($selectEspeces != null) {
      foreach ($selectEspeces as $espece) {
        if (!in_array($espece, $oldEspeces)) {
          $especesToInsert[] = ['categorie_id' => $id, 'espece_id' => $espece];
        }
      }
      if (sizeof($especesToInsert) > 0) {
        Categorie_espece::insert($especesToInsert);
      }
    }
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

  private function updateProduitsCategorie($id, $annee, $selectProduits, $objectifRepository)
  {
    $oldProduits = Categorie_produit::where('categorie_id', $id)->pluck('produit_id')->toArray();

    // Suppression de tous les produits de $oldProduits qui ne sont pas dans $selectProduits
    $produitsToDelete = [];
    foreach ($oldProduits as $produit) {
      if ($selectProduits == null) {
        $produitsToDelete[] = $produit;
      } else {
        $existe = false;
        foreach ($selectProduits as $selectProduit) {
          if ($produit == $selectProduit['produit_id']) {
            $existe = true;
          }
        }
        if (!$existe) {
          $produitsToDelete[] = $produit;
        }
      }
    }
    if (sizeof($produitsToDelete) > 0) {
      $categorieProduits = Categorie_produit::where('categorie_id', $id)->whereIn('produit_id', $produitsToDelete)->pluck('id')->toArray();

      // Mise à jour des produits de l'objectif
      Categorie_produit_objectif::whereIn('categorie_produit_id', $categorieProduits)->delete();
      Categorie_produit::whereIn('id', $categorieProduits)->delete();
    }

    // Ajout de tous les produits de $selectProduits qui ne sont pas dans $oldProduits
    $produitsToInsert = [];
    $produitsIds = [];
    if ($selectProduits != null) {
      foreach ($selectProduits as $selectProduit) {
        $existe = false;
        foreach ($oldProduits as $oldProduit) {
          if ($oldProduit == $selectProduit['produit_id']) {
            $existe = true;
          }
        }
        if (!$existe) {
          $produitsToInsert[] = ['categorie_id' => $id, 'produit_id' => $selectProduit['produit_id']];
          $produitsIds[] = $selectProduit['produit_id'];
        }
      }
      if (sizeof($produitsToInsert) > 0) {
        Categorie_produit::insert($produitsToInsert);
        $categorieProduits = Categorie_produit::where('categorie_id', $id)->whereIn('produit_id', $produitsIds)->get();

        $objectifs = Objectif::where('categorie_id', $id)->get();

        $objectifProduits = [];
        foreach ($objectifs as $objectif) {
          foreach ($categorieProduits as $categorieProduit) {
            $objectifProduits[] = ['objectif_id' => $objectif->id, 'categorie_produit_id' => $categorieProduit->id, 'pourcentage_remise' => $objectif->pourcentage_remise, 'pourcentage_remise_source' => $objectif->pourcentage_remise_source];
          }
        }
        Categorie_produit_objectif::insert($objectifProduits);
      }
    }

    // Mise à jour du CA de l'objectif
    if (sizeof($produitsToDelete) > 0 || sizeof($produitsToInsert) > 0) {
      $objectifs = Objectif::where('categorie_id', $id)->get();
      $maxDateAchats = $this->achatRepository->findLastDateOfPurchasesByYear($annee);
      foreach ($objectifs as $objectif) {
        $objectifRepository->updateCAStateAndEcart($objectif, $maxDateAchats);
        // Mise à jour du suivi des objectifs s'il s'agit d'un palier
        if ($objectif->type_objectif_id == 2) {
          $premierPalier = $this->getPalierPrecedent($objectif);
          $this->updateSuiviPalier($premierPalier);
        }
      }
    }
  }

  /**
   * Update the specified resource in storage.
   *
   * @param  \Illuminate\Http\Request  $request
   * @param  int  $id
   * @return Response
   */
  public function update(Request $request, $id, ObjectifRepository $objectifRepository)
  {
    // Mise à jour de la catégorie
    $saveCategorie = Categorie::where('id', $id)->update(['nom' => $request->nom]);

    // Mise à jour des espèces
    $this->updateEspecesCategorie($id, $request->especes);

    // Mise à jour des produits
    $this->updateProduitsCategorie($id, $saveCategorie["annee"], $request->produits, $objectifRepository);

    // Mise à jour des commentaires
    if ($request->commentaires != null) {
      Categorie_commentaires::insert($request->commentaires);
    }

    return response()->json($saveCategorie);
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
    $deleteCategories = Categorie::whereIn('id', $ids)->update(['obsolete' => 1]);

    $categorieProduits = Categorie_produit::whereIn('categorie_id', $ids)->pluck('id')->toArray();

    // Suppression des produits des objectifs liés à la catégorie
    Categorie_produit_objectif::whereIn('categorie_produit_id', $categorieProduits)->delete();

    // Suppression des objectifs liés ??????
    Objectif::whereIn('categorie_id', $ids)->update(['obsolete' => 1]);

    // Suppression des produits liés à la catégorie
    Categorie_produit::whereIn('id', $categorieProduits)->delete();

    return $deleteCategories;
  }
}
