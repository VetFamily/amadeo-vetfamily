<?php
	// output headers so that the file is downloaded rather than displayed
	header('Content-Type: text/csv; charset=utf-8');
	header('Content-Disposition: attachment; filename="' . date('Ymd') . '_' . \Lang::get('amadeo.purchases.download-purchases.filename') . ' ' . $year . '.csv"'); 

	// create a file pointer connected to the output stream
	$output = fopen('php://output', 'w');
	fprintf($output, "\xEF\xBB\xBF");

	// output the column headings
	fputcsv($output, [\Lang::get("amadeo.clinics.id"), \Lang::get('amadeo.clinics.veterinaries'), \Lang::get('amadeo.clinics.name'), \Lang::get('amadeo.clinics.entry-year'), \Lang::get('amadeo.products.seller'), \Lang::get('amadeo.products.id'), \Lang::get('amadeo.purchases.product'), \Lang::get('amadeo.products.gtin'), \Lang::get('amadeo.products.types'), \Lang::get('amadeo.products.species'), \Lang::get('amadeo.products.therapeutic-classes.level1-code'), \Lang::get('amadeo.products.therapeutic-classes.level1-name'), \Lang::get('amadeo.products.therapeutic-classes.level2-code'), \Lang::get('amadeo.products.therapeutic-classes.level2-name'), \Lang::get('amadeo.products.therapeutic-classes.level3-code'), \Lang::get('amadeo.products.therapeutic-classes.level3-name'), \Lang::get('amadeo.purchases.source'), \Lang::get('amadeo.purchases.date'), \Lang::get('amadeo.purchases.quantity-paid'), \Lang::get('amadeo.purchases.quantity-free'), \Lang::get('amadeo.purchases.amount'), \Lang::get('amadeo.purchases.categories'), \Lang::get('amadeo.purchases.total-rebate'), \Lang::get('amadeo.purchases.clinic-rebate'), \Lang::get('amadeo.purchases.central-rebate')], ';');

	// Parcours des données
	foreach ($purchases as $purchase) {
		fputcsv($output, [$purchase->clinique_id, $purchase->veterinaires, $purchase->clinique, $purchase->annee, $purchase->laboratoire, $purchase->produit_id, $purchase->produit_nom, $purchase->produit_gtin, $purchase->types, $purchase->especes, $purchase->classe1_code, $purchase->classe1_nom, $purchase->classe2_code, $purchase->classe2_nom, $purchase->classe3_code, $purchase->classe3_nom, ucwords(strtolower($purchase->centrale)), $purchase->date, $purchase->qte_payante, $purchase->qte_gratuite, str_replace(".", ",", $purchase->ca), $purchase->categories, str_replace(".", ",", $purchase->total_rebate), str_replace(".", ",", $purchase->clinic_rebate), str_replace(".", ",", $purchase->central_rebate)], ';');
	}
	fclose($output);
	exit();
?>