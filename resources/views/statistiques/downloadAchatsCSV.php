<?php
	// output headers so that the file is downloaded rather than displayed
	header('Content-Type: text/csv; charset=utf-8');
	header('Content-Disposition: attachment; filename="' . date('Ymd') . '_' . \Lang::get('amadeo.purchases.download-purchases.filename') . ' ' . $year . '.csv"'); 

	// create a file pointer connected to the output stream
	$output = fopen('php://output', 'w');
	fprintf($output, "\xEF\xBB\xBF");

	// output the column headings
	fputcsv($output, [\Lang::get("amadeo.clinics.id"), \Lang::get('amadeo.clinics.veterinaries'), \Lang::get('amadeo.clinics.name'), \Lang::get('amadeo.clinics.entry-year'), \Lang::get('amadeo.clinics.date-left'), \Lang::get('amadeo.products.seller'), \Lang::get('amadeo.products.id'), \Lang::get('amadeo.purchases.product'), \Lang::get('amadeo.products.gtin'), \Lang::get('amadeo.products.types'), \Lang::get('amadeo.products.species'), \Lang::get('amadeo.products.therapeutic-classes.level1-code'), \Lang::get('amadeo.products.therapeutic-classes.level1-name'), \Lang::get('amadeo.products.therapeutic-classes.level2-code'), \Lang::get('amadeo.products.therapeutic-classes.level2-name'), \Lang::get('amadeo.products.therapeutic-classes.level3-code'), \Lang::get('amadeo.products.therapeutic-classes.level3-name'), \Lang::get('amadeo.purchases.source'), \Lang::get('amadeo.purchases.date'), \Lang::get('amadeo.purchases.quantity-paid'), \Lang::get('amadeo.purchases.quantity-free'), \Lang::get('amadeo.purchases.gross'), \Lang::get('amadeo.purchases.net'), \Lang::get('amadeo.purchases.currency'), \Lang::get('amadeo.purchases.criteria-valorization'), \Lang::get('amadeo.purchases.clinic-rebate-percent'), \Lang::get('amadeo.purchases.clinic-rebate-amadeo'), \Lang::get('amadeo.purchases.clinic-rebate'), \Lang::get('amadeo.purchases.central-rebate-percent'), \Lang::get('amadeo.purchases.central-rebate-amadeo'), \Lang::get('amadeo.purchases.central-rebate'), \Lang::get('amadeo.purchases.double-net'), \Lang::get('amadeo.purchases.categories')], ';');

	// Parcours des données
	foreach ($purchases as $purchase) {
		fputcsv($output, [$purchase->clinic_id, $purchase->veterinaires, $purchase->clinic_name, $purchase->entry_date, $purchase->date_left, $purchase->supplier, $purchase->product_id, $purchase->product_nom, $purchase->product_gtin, $purchase->types, $purchase->species, $purchase->classe1_code, $purchase->classe1_nom, $purchase->classe2_code, $purchase->classe2_nom, $purchase->classe3_code, $purchase->classe3_nom, ucwords(strtolower($purchase->centrale)), $purchase->purc_date, $purchase->purc_paid_unit, $purchase->purc_free_unit, str_replace(".", ",", $purchase->purc_gross), str_replace(".", ",", $purchase->purc_net), $purchase->purc_currency, $purchase->purc_valorization, str_replace(".", ",", $purchase->purc_clinic_rebate_percent), $purchase->purc_clinic_rebate_amadeo, str_replace(".", ",", $purchase->purc_clinic_rebate), str_replace(".", ",", $purchase->purc_central_rebate_percent), $purchase->purc_central_rebate_amadeo, str_replace(".", ",", $purchase->purc_central_rebate), str_replace(".", ",", $purchase->purc_double_net), $purchase->categories], ';');
	}
	fclose($output);
	exit();
?>