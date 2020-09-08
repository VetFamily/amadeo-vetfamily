<?php
	// output headers so that the file is downloaded rather than displayed
	header('Content-Type: text/csv; charset=utf-8');
	header('Content-Disposition: attachment; filename="' . date('Ymd') . '_' . \Lang::get('amadeo.purchases.download-purchases.filename') . ' ' . date('Y') . ' ' . \Lang::get('amadeo.purchases.download-purchases-filtered.filename') . '.csv"'); 

	// create a file pointer connected to the output stream
	$output = fopen('php://output', 'w');
	fprintf($output, "\xEF\xBB\xBF");

	/**
	* Summary of criteria
	**/
	// Period
	$startPeriod = ($purchasesCriteria["startMonth"] < 10 ? '0' . $purchasesCriteria["startMonth"] : $purchasesCriteria["startMonth"]) . '/' . $purchasesCriteria["startYear"];
	$endPeriod = ($purchasesCriteria["endMonth"] < 10 ? '0' . $purchasesCriteria["endMonth"] : $purchasesCriteria["endMonth"]) . '/' . $purchasesCriteria["endYear"];
	fputcsv($output, [\Lang::get('amadeo.purchases.criteria-period') . ' : ' . $startPeriod . ' ' . \Lang::get('amadeo.purchases.criteria-period-end') . ' ' . $endPeriod], ';');
	// Clinics
	if ($purchasesCriteria["clinics"] != null)
	{
		$clinics = App\Model\Clinique::whereIn('id', $purchasesCriteria["clinics"])->orderBy('nom')->pluck('nom')->toArray();
	}
	fputcsv($output, [\Lang::get('amadeo.purchases.clinics') . ' : ' . ($purchasesCriteria["clinics"] == null ? \Lang::get('amadeo.all-f') : implode(", ", $clinics))], ';');
	// Years of entry
	fputcsv($output, [\Lang::get('amadeo.purchases.criteria-clinics-step1-entry-date') . ' : ' . (($purchasesClinicsCriteria["clinicYears"] == null) ? \Lang::get('amadeo.all-f') : implode(", ", $purchasesClinicsCriteria["clinicYears"]))], ';');
	// Laboratories
	if ($purchasesProductsCriteria["laboratories"] != null)
	{
		$laboratories = App\Model\Laboratoire::whereIn('id', $purchasesProductsCriteria["laboratories"])->orderBy('nom')->pluck('nom')->toArray();
	}
	fputcsv($output, [\Lang::get('amadeo.purchases.criteria-products-step1-seller') . ' : ' . (($purchasesProductsCriteria["laboratories"] == null) ? \Lang::get('amadeo.all-m') : implode(", ", $laboratories))], ';');
	// Products
	$products = $purchasesCriteria["products"] == null ? \Lang::get('amadeo.all-m') : sizeof($purchasesCriteria["products"]);
	fputcsv($output, [\Lang::get('amadeo.purchases.products') . ' : ' . $products], ';');
	// Valorization
	switch ($purchasesCriteria["valorization"]) {
		case 1:
			$valorization = "Centrale";
			break;
		case 2:
			$valorization = "Laboratoire";
			break;
		case 3:
			$valorization = "Catalogue centrale";
			break;
		default:
			$valorization = "Centrale";
			break;
	}
	fputcsv($output, [\Lang::get('amadeo.purchases.criteria-valorization') . ' : ' . $valorization], ';');
	// Products types
	if ($purchasesProductsCriteria["productTypes"] != null)
	{
		$productTypes = App\Model\Type::whereIn('id', $purchasesProductsCriteria["productTypes"])->orderBy('nom')->pluck('nom')->toArray();
	}
	fputcsv($output, [\Lang::get('amadeo.purchases.criteria-products-step1-type') . ' : ' . (($purchasesProductsCriteria["productTypes"] == null) ? \Lang::get('amadeo.all-m') : implode(", ", $productTypes))], ';');
	// Products species
	if ($purchasesProductsCriteria["productSpecies"] != null)
	{
		$productSpecies = App\Model\Espece::whereIn('id', $purchasesProductsCriteria["productSpecies"])->orderBy('nom')->pluck('nom')->toArray();
	}
	fputcsv($output, [\Lang::get('amadeo.purchases.criteria-products-step1-specie') . ' : ' . (($purchasesProductsCriteria["productSpecies"] == null) ? \Lang::get('amadeo.all-f') : implode(", ", $productSpecies))], ';');
	// Central purchasing
	$centralPurchasing = App\Model\Centrale::whereIn('id', $purchasesCriteria["centralPurchasing"])->orderBy('nom')->pluck('nom')->toArray();
	fputcsv($output, [\Lang::get('amadeo.purchases.criteria-sources') . ' : ' . ucwords(strtolower(implode(", ", $centralPurchasing)))], ';');

	fputcsv($output, [], ';');

	switch ($purchasesCriteria["displayType"][0]) {
		case 'product':
			// output the column headings
			fputcsv($output, [\Lang::get('amadeo.products.seller'), \Lang::get('amadeo.purchases.product'), \Lang::get('amadeo.products.gtin'), \Lang::get('amadeo.products.types'), \Lang::get('amadeo.products.species'), \Lang::get('amadeo.purchases.amount') . ' N', \Lang::get('amadeo.purchases.quantity' . ' N'), \Lang::get('amadeo.purchases.amount') . ' N-1', \Lang::get('amadeo.purchases.quantity' . ' N-1'), \Lang::get('amadeo.purchases.evolution')], ';');

			// Parcours des données
			foreach ($purchases as $purchase) {
				$evol = $purchase->ca_periode_prec != 0 ? round((($purchase->ca_periode*100/$purchase->ca_periode_prec)-100)/100,2) : "-";
				fputcsv($output, [ $purchase->laboratoire, $purchase->denomination . " : " . $purchase->conditionnement, $purchase->code_gtin, $purchase->types, $purchase->especes, str_replace(".", ",", $purchase->ca_periode), $purchase->qte_periode, str_replace(".", ",", $purchase->ca_periode_prec), $purchase->qte_periode_prec, str_replace(".", ",", $evol) ], ';');
			}
			break;

		case 'laboratory':
			// output the column headings
			fputcsv($output, [\Lang::get('amadeo.products.seller'), \Lang::get('amadeo.purchases.amount') . ' N', \Lang::get('amadeo.purchases.amount') . ' N-1', \Lang::get('amadeo.purchases.evolution')], ';');

			// Parcours des données
			foreach ($purchases as $purchase) {
				$evol = $purchase->ca_periode_prec != 0 ? round((($purchase->ca_periode*100/$purchase->ca_periode_prec)-100)/100,2) : "-";
				fputcsv($output, [ $purchase->laboratoire, str_replace(".", ",", $purchase->ca_periode), str_replace(".", ",", $purchase->ca_periode_prec), str_replace(".", ",", $evol) ], ';');
			}
			break;

		case 'clinic':
		// output the column headings
		fputcsv($output, [\Lang::get('amadeo.clinics.veterinaries'), \Lang::get('amadeo.clinics.name'), \Lang::get('amadeo.purchases.amount') . ' N', \Lang::get('amadeo.purchases.amount') . ' N-1', \Lang::get('amadeo.purchases.evolution')], ';');

		// Parcours des données
		foreach ($purchases as $purchase) {
			$evol = $purchase->ca_periode_prec != 0 ? round((($purchase->ca_periode*100/$purchase->ca_periode_prec)-100)/100,2) : "-";
			fputcsv($output, [ $purchase->veterinaires, $purchase->clinique, str_replace(".", ",", $purchase->ca_periode), str_replace(".", ",", $purchase->ca_periode_prec), str_replace(".", ",", $evol) ], ';');
		}
			break;

        case 'category':
        // output the column headings
        fputcsv($output, [\Lang::get('amadeo.categories.year'), \Lang::get('amadeo.categories.specie'), \Lang::get('amadeo.categories.seller'), \Lang::get('amadeo.categories.name'), \Lang::get('amadeo.purchases.amount') . ' N', \Lang::get('amadeo.purchases.amount') . ' N-1', \Lang::get('amadeo.purchases.evolution')], ';');

        // Parcours des données
        foreach ($purchases as $purchase) {
            $evol = $purchase->ca_periode_prec != 0 ? round((($purchase->ca_periode*100/$purchase->ca_periode_prec)-100)/100,2) : "-";
            fputcsv($output, [ $purchase->annee, $purchase->especes, $purchase->laboratoire, $purchase->categorie, str_replace(".", ",", $purchase->ca_periode), str_replace(".", ",", $purchase->ca_periode_prec), str_replace(".", ",", $evol) ], ';');
        }
            break;
		
		default:
			$purchases = null;
			break;
	}
	
	fclose($output);
	exit();
?>
