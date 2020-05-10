<?php 

namespace App\Http\Controllers\Ajax;

use App\Http\Controllers\Controller;
use App\Repositories\ProduitRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Session;

class CategorieProduitAjaxController extends Controller 
{

  /**
   * Display a listing of the resource.
   *
   * @return Response
   */
  public function index(Request $request, ProduitRepository $produitRepository)
  {
    $produits = $produitRepository->findListByCategorieId($request->categorie);

    return response()->json($produits);
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
  public function store()
  {
    
  }

  /**
   * Display the specified resource.
   *
   * @param  int  $id
   * @return Response
   */
  public function show($id)
  {

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

  /**
   * Update the specified resource in storage.
   *
   * @param  \Illuminate\Http\Request  $request
   * @param  int  $id
   * @return Response
   */
  public function update(Request $request, $id)
  {
    
  }

  /**
   * Remove the specified resource from storage.
   *
   * @param  int  $id
   * @return Response
   */
  public function destroy($id)
  {
    
  }
  
  /**
   * Display the specified resource.
   *
   * @param  int  $id
   * @return Response
   */
  public function showListOfProducts(Request $request, ProduitRepository $produitRepository)
  {
    // Recherche des produits candidats
    $produits = $produitRepository->findListCandidatsByLaboratoireAndCategorie($request->laboratoire, $request->produits);
    
    return response()->json($produits);
  }
  
}

?>