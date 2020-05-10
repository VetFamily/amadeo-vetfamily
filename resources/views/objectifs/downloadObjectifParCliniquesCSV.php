<?php
	// output headers so that the file is downloaded rather than displayed
	header('Content-Type: text/csv; charset=utf-8');
	$objectif = App\Model\Objectif::where('id', $objectifId)->first();
	$name = preg_replace("/[éè&<>:,!;\"\/\\\|\?\*]/", '', $objectif->nom);
	
	header('Content-Disposition: attachment; filename="' . date('Ymd') . '_' . \Lang::get('amadeo.targets.download-clinics.filename', ['name' => (strlen($name) > 30 ? substr_replace($name, "...", 30) : $name)]) . '.csv"'); 

	// create a file pointer connected to the output stream
	$output = fopen('php://output', 'w');
	fprintf($output, "\xEF\xBB\xBF");

	// output the column headings
	$headings = [\Lang::get("amadeo.clinics.veterinaries")];
	for ($i=1 ; $i<13 ; $i++)
    {
        $headings[] = ($i < 10 ? "0" : "") . $i . "-" . ($annee-1);
    }
	for ($i=1 ; $i<($annee == date('Y') ? (date('n')+1) : 13) ; $i++)
    {
        $headings[] = ($i < 10 ? "0" : "") . $i . "-" . $annee;
    }
	fputcsv($output, $headings, ';');


	// Parcours des données
	for($i=0 ; $i<sizeof($cliniques) ; $i++) {
		$clinique = $cliniques[$i];
		$row = [$clinique->clinique];
		for ($j=0 ; $j<(12+($annee == date('Y') ? date('n') : 12)) ; $j++)
	    {
	        $row[] = str_replace(".", ",", $clinique->{"ca_complet_m".$j});
	    }

		fputcsv($output, $row, ';');
	}

	fclose($output);
	exit();
?>