<?php
	// output headers so that the file is downloaded rather than displayed
	header('Content-Type: text/csv; charset=utf-8');
	header('Content-Disposition: attachment; filename="' . date('Ymd') . '_' . \Lang::get('amadeo.clinics.download-clinics.filename') . '.csv"'); 

	// create a file pointer connected to the output stream
	$output = fopen('php://output', 'w');
	fprintf($output, "\xEF\xBB\xBF");

    // output the column headings
    $headings = [ \Lang::get("amadeo.clinics.id"), \Lang::get('amadeo.clinics.veterinaries'), \Lang::get('amadeo.clinics.name'), \Lang::get('amadeo.clinics.address'), \Lang::get('amadeo.clinics.zip-code'), \Lang::get('amadeo.clinics.city'), \Lang::get('amadeo.clinics.entry-year') ];
    foreach ($centralPurchasing as $cp) {
        array_push($headings, ucwords(strtolower($cp->nom)));
        array_push($headings, ucwords(strtolower($cp->nom) . ' (Web)'));
    }
    array_push($headings, Lang::get("amadeo.clinics.comments"));
	fputcsv($output, $headings, ';');

	// Parcours des données
	foreach ($clinics as $clinic) {
        $line = [ $clinic->clinique_id, $clinic->veterinaires, $clinic->clinique, $clinic->adresse, '="' . $clinic->code_postal . '"', $clinic->ville, $clinic->date_entree ];
        $listOfCentralPurchasingOffWeb = json_decode($clinic->infos_hors_web);
        $listOfCentralPurchasingWeb = json_decode($clinic->infos_web);
        for ($i=0; $i < sizeof($listOfCentralPurchasingOffWeb); $i++) { 
           $centralOffWeb = $listOfCentralPurchasingOffWeb[$i];
           $centralWeb = $listOfCentralPurchasingWeb[$i];
           array_push($line, '="' . str_replace("|", ", ", $centralOffWeb->identifiant) . '"');
           array_push($line, '="' . str_replace("|", ", ", $centralWeb->identifiant) . '"');
        }
        array_push($line, $clinic->commentaire);
		fputcsv($output, $line, ';');
	}
	fclose($output);
	exit();
?>