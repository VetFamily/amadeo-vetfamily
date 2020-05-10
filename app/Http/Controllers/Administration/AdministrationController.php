<?php

namespace App\Http\Controllers\Administration;

/* composer require phpoffice/phpspreadsheet */
use App\Http\Controllers\Controller;
use App\Jobs\ExportBilansRFA;
use App\Model\Centrale_clinique;
use App\Model\Clinique;
use App\Model\Engagement;
use App\Model\Laboratoire;
use App\Model\Objectif;
use App\Repositories\CliniqueRepository;
use App\Repositories\LaboratoireRepository;
use App\Repositories\ObjectifRepository;
use App\Repositories\ProduitRepository;
use DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Session;

class AdministrationController extends Controller
{

	public function searchCodesCentralesByClinique(CliniqueRepository $cliniqueRepository)
	{
		// Entreprise
		$entreprise = null;
		if (isset($_POST["clinique"]))
		{
			$entreprise = $_POST["clinique"];
		}
		
	    $codesCentrales = $cliniqueRepository->findCodesCentralesById($entreprise);

        echo $codesCentrales;
	}

	public function exportEstimationRFAExcel($detail, $moisDebut, $anneeDebut, $moisFin, $anneeFin, $entrepriseId, $entrepriseCodesCentrales, $anneeObj, ProduitRepository $produitRepository, LaboratoireRepository $laboratoireRepository, ObjectifRepository $objectifRepository)
	{
		// Entreprise
		$entreprise = Clinique::select('id', 'veterinaires')->where('id', $entrepriseId)->first();
		$codesEntreprises = explode(',', $entrepriseCodesCentrales);
		$nomEntreprise = $entreprise->veterinaires;

		if ($codesEntreprises != null && !in_array('0', $codesEntreprises))
		{
			$identifiants = Centrale_clinique::select('identifiant')->whereIn('id', $codesEntreprises)->get();
			$nomEntreprise .= ' (';
			$firstLoop = true;
			foreach ($identifiants as $identifiant) {
				if ($firstLoop)
				{
					$firstLoop = false;
				} else
				{
					$nomEntreprise .= ', ';
				}

				$nomEntreprise .= $identifiant->identifiant;
			}
			$nomEntreprise .= ')';
			
		}

		// Fichier
		$classeur = new Spreadsheet();
		$classeur->getDefaultStyle()->getFont()->getColor()->setRGB('212629');
		$classeur->getDefaultStyle()->getFont()->setSize(9);
		$classeur->getDefaultStyle()->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
		$classeur->getDefaultStyle()->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
		$classeur->getActiveSheet()->getPageSetup()->setPaperSize(PageSetup::PAPERSIZE_A4);
		
		$syntheseLaboratoires = $laboratoireRepository->findEstimationsRFAForExcel($moisDebut, $anneeDebut, $moisFin, $anneeFin, $entrepriseId, $codesEntreprises, $anneeObj);

		if ($detail == '1')
		{
			// Création des feuilles "Laboratoires"
			$cptFeuilles = 1;
			foreach ($syntheseLaboratoires as $laboratoire) {
				$feuilleLaboratoire = new Worksheet($classeur, $laboratoire->nom);
				$classeur->addSheet($feuilleLaboratoire, $cptFeuilles);
				// Recherche des produits du laboratoire
				$produitsLaboratoire = $produitRepository->findEstimationsRFAForExcel($moisDebut, $anneeDebut, $moisFin, $anneeFin, $entrepriseId, $laboratoire->id, $codesEntreprises, $anneeObj);
				// Recherche des objectifs du laboratoire
				$objectifsLaboratoire = $objectifRepository->findEstimationsRFAForExcel($moisDebut, $anneeDebut, $moisFin, $anneeFin, $entrepriseId, $laboratoire->id, $codesEntreprises, $anneeObj);
				$laboratoire = $this->generateEstimationRFALaboratoire($feuilleLaboratoire, $moisDebut, $anneeDebut, $moisFin, $anneeFin, $nomEntreprise, $laboratoire, $produitsLaboratoire, $objectifsLaboratoire);
				$cptFeuilles++;
			}
		}

		// Création de la feuille de synthèse
		$classeur->setActiveSheetIndex(0);
		$feuilleSynthese = $classeur->getActiveSheet()->setTitle('Synthèse');
		$this->generateEstimationRFASynthese($feuilleSynthese, $moisDebut, $anneeDebut, $moisFin, $anneeFin, $nomEntreprise, $syntheseLaboratoires);

		header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
		$nom = 'Content-Disposition: attachment;filename="' . date('Ymd') . '_Estimation_RFA_' . str_replace('/', '-', $entreprise->veterinaires);
		if ($detail == '1')
		{
			 $nom .= '_detail';
		}
		$nom .= '.xlsx"';
		header($nom);
	    $writer = IOFactory::createWriter($classeur, 'Xlsx');
		$writer->save('php://output');
		exit;
	}

	private function generateEstimationRFALaboratoire($feuille, $moisDebut, $anneeDebut, $moisFin, $anneeFin, $nomEntreprise, $laboratoire, $produitsLaboratoire, $objectifsLaboratoire) 
	{
		
		$feuille->getSheetView()->setZoomScale(130);

		// Tailles des colonnes et des lignes
		$feuille->getDefaultColumnDimension()->setAutoSize(false);
		$feuille->getColumnDimension('A')->setWidth(0.83203125);
		$feuille->getColumnDimension('B')->setWidth(1.6640625);
		$feuille->getColumnDimension('C')->setWidth(56.59765625);
		$feuille->getColumnDimension('D')->setWidth(20);
		$feuille->getColumnDimension('E')->setWidth(20);
		$feuille->getColumnDimension('F')->setWidth(1.6640625);
		$feuille->getColumnDimension('G')->setWidth(0.83203125);
		$feuille->getColumnDimension('H')->setWidth(0.83203125);
		$feuille->getColumnDimension('I')->setWidth(1.6640625);
		$feuille->getColumnDimension('J')->setWidth(25);
		$feuille->getColumnDimension('K')->setWidth(25.5);
		$feuille->getColumnDimension('L')->setWidth(16);
		$feuille->getColumnDimension('M')->setWidth(14.4);
		$feuille->getColumnDimension('N')->setWidth(16);
		$feuille->getColumnDimension('O')->setWidth(1.6640625);
		$feuille->getColumnDimension('P')->setWidth(0.83203125);

		/************************
		 * Entête de la feuille *
		 ************************/

		$feuille->getRowDimension('1')->setRowHeight(42);
		$feuille->getStyle('A1:P1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('212629');
		$feuille->setCellValue('B1', 'Amadeo');
		$feuille->getStyle('B1')->getFont()->getColor()->setRGB('FFFFFF');
		$feuille->getStyle('B1')->getFont()->setSize(22);
		$feuille->getStyle('B1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
		$feuille->setCellValue('E1', $nomEntreprise);
		$feuille->getStyle('E1')->getFont()->getColor()->setRGB('9B9B9B');
		$feuille->getStyle('E1')->getFont()->setSize(12);
		$feuille->getStyle('E1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
		$feuille->setCellValue('I1', 'Amadeo');
		$feuille->getStyle('I1')->getFont()->getColor()->setRGB('FFFFFF');
		$feuille->getStyle('I1')->getFont()->setSize(22);
		$feuille->getStyle('I1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
		$feuille->setCellValue('N1', $nomEntreprise);
		$feuille->getStyle('N1')->getFont()->getColor()->setRGB('9B9B9B');
		$feuille->getStyle('N1')->getFont()->setSize(12);
		$feuille->getStyle('N1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

		/***********************
		 * Titre de la feuille *
		 ***********************/

		// Estimation des remises par objectif
		$feuille->getRowDimension('2')->setRowHeight(42);
		$feuille->getStyle('B2:F2')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('01B4BC');
		$feuille->getStyle('B2:F2')->getFont()->getColor()->setRGB('E8E8E8');
		$feuille->getStyle('C2')->getFont()->setSize(14);
		$feuille->setCellValue('C2', $laboratoire->nom . ' - Estimation des remises / objectif');
		$feuille->getStyle('C2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
		setlocale(LC_ALL, 'fr_FR.UTF-8');
		$feuille->setCellValue('E2', 'Période : de ' . strftime("%B",strtotime($anneeDebut . "-" . $moisDebut . "-01")) . ' ' . $anneeDebut . ' à ' . strftime("%B",strtotime($anneeFin . "-" . $moisFin . "-01")) . ' ' . $anneeFin);
		$feuille->getStyle('E2')->getFont()->setSize(11);
		$feuille->getStyle('E2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

		// Estimation des remises par produit
		$feuille->getStyle('I2:O2')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('01B4BC');
		$feuille->getStyle('I2:O2')->getFont()->getColor()->setRGB('E8E8E8');
		$feuille->getStyle('J2')->getFont()->setSize(14);
		$feuille->setCellValue('J2', $laboratoire->nom . ' - Estimation des remises / produit');
		$feuille->getStyle('J2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
		$feuille->setCellValue('N2', 'Période : de ' . strftime("%B",strtotime($anneeDebut . "-" . $moisDebut . "-01")) . ' ' . $anneeDebut . ' à ' . strftime("%B",strtotime($anneeFin . "-" . $moisFin . "-01")) . ' ' . $anneeFin);
		$feuille->getStyle('N2')->getFont()->setSize(11);
		$feuille->getStyle('N2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

		/*********************
		 * Entête du tableau *
		 *********************/

		// Estimation des remises / objectif
		$feuille->getRowDimension('3')->setRowHeight(62);
		$feuille->getStyle('B3:F3')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('E8E8E8');
		$feuille->getStyle('B3:F3')->getFont()->getColor()->setRGB('01B4BC');
		$feuille->getStyle('B3:F3')->getAlignment()->setWrapText(true);
		$feuille->setCellValue('C3', 'Nom de l\'objectif');
		$feuille->setCellValue('D3', 'Achats  sur la période (€ au tarif de référence)');
		$feuille->setCellValue('E3', 'Remise financière estimée sur la période (€)*');
		
		// Estimation des remises / produit
		$feuille->getStyle('I3:O3')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('E8E8E8');
		$feuille->getStyle('I3:O3')->getFont()->getColor()->setRGB('01B4BC');
		$feuille->getStyle('I3:O3')->getAlignment()->setWrapText(true);
		$feuille->setCellValue('J3', 'Dénomination du produit');
		$feuille->setCellValue('K3', 'Conditionnement du produit');
		$feuille->setCellValue('L3', 'Achats  sur la période (€ au tarif de référence)');
		$feuille->setCellValue('M3', '% de remise');
		$feuille->setCellValue('N3', 'Remise financière estimée sur la période (€)*');
		
		/********************************
		 * Lignes du tableau / objectif *
		 ********************************/

		$cptObjectif = 3;
		foreach ($objectifsLaboratoire as $objectif) 
		{
			$cptObjectif++;
			$feuille->getRowDimension($cptObjectif)->setRowHeight(30);
			
			// Estimation des remises
			$feuille->getStyle('B' . $cptObjectif . ':F' . $cptObjectif)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FFFFFF');
			$feuille->getStyle('B' . $cptObjectif . ':F' . $cptObjectif)->getBorders()->getTop()->setBorderStyle(Border::BORDER_THICK);
			$feuille->getStyle('B' . $cptObjectif . ':F' . $cptObjectif)->getBorders()->getTop()->getColor()->setRGB('E8E8E8');
			$feuille->setCellValue('C' . $cptObjectif, $objectif->nom);
			$feuille->getStyle('C' . $cptObjectif)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
			$feuille->getStyle('C' . $cptObjectif)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
			$feuille->getStyle('C' . $cptObjectif)->getAlignment()->setWrapText(true);
			$feuille->setCellValue('D' . $cptObjectif, $objectif->ca_periode);
			$feuille->getStyle('D' . $cptObjectif)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_CURRENCY_EUR_SIMPLE);
			$feuille->getStyle('D' . $cptObjectif)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
			$feuille->setCellValue('E' . $cptObjectif, $objectif->remise_periode);
			$feuille->getStyle('E' . $cptObjectif)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_CURRENCY_EUR_SIMPLE);
			$feuille->getStyle('E' . $cptObjectif)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
		}

		/*******************************
		 * Lignes du tableau / produit *
		 *******************************/

		$cptProduit = 3;
		foreach ($produitsLaboratoire as $produit) 
		{
			$cptProduit++;
			$feuille->getRowDimension($cptProduit)->setRowHeight(30);
			
			// Estimation des remises
			$feuille->getStyle('I' . $cptProduit . ':O' . $cptProduit)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FFFFFF');
			$feuille->getStyle('I' . $cptProduit . ':O' . $cptProduit)->getBorders()->getTop()->setBorderStyle(Border::BORDER_THICK);
			$feuille->getStyle('I' . $cptProduit . ':O' . $cptProduit)->getBorders()->getTop()->getColor()->setRGB('E8E8E8');
			$feuille->setCellValue('J' . $cptProduit, $produit->denomination);
			$feuille->getStyle('J' . $cptProduit)->getFont()->setSize(8);
			$feuille->getStyle('J' . $cptProduit)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
			$feuille->getStyle('J' . $cptProduit)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
			$feuille->getStyle('J' . $cptProduit)->getAlignment()->setWrapText(true);
			$feuille->setCellValue('K' . $cptProduit, $produit->conditionnement);
			$feuille->getStyle('K' . $cptProduit)->getFont()->setSize(8);
			$feuille->getStyle('K' . $cptProduit)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
			$feuille->getStyle('K' . $cptProduit)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
			$feuille->getStyle('K' . $cptProduit)->getAlignment()->setWrapText(true);
			$feuille->setCellValue('L' . $cptProduit, $produit->ca_periode);
			$feuille->getStyle('L' . $cptProduit)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_CURRENCY_EUR_SIMPLE);
			$feuille->getStyle('L' . $cptProduit)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
			$feuille->setCellValue('M' . $cptProduit, $produit->pourcentage_remise);
			$feuille->getStyle('M' . $cptProduit)->getNumberFormat()->setFormatCode('# ##0.00_-"%"');
			$feuille->getStyle('M' . $cptProduit)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
			$commentPourcentageRemise = $feuille->getComment('M' . $cptProduit)->getText()->createTextRun('Objectif rattaché :');
			$commentPourcentageRemise->getFont()->setBold(true);
			$feuille->getComment('M' . $cptProduit)->getText()->createTextRun("\r\n");
			$feuille->getComment('M' . $cptProduit)->getText()->createTextRun($produit->objectif);
			$feuille->setCellValue('N' . $cptProduit, '=L' . $cptProduit . '*M' . $cptProduit . '/100');
			$feuille->getStyle('N' . $cptProduit)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_CURRENCY_EUR_SIMPLE);
			$feuille->getStyle('N' . $cptProduit)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
		}

		/********************
		 * Total du tableau *
		 ********************/

		$feuille->getRowDimension($cptProduit+1)->setRowHeight(26);
		
		// Estimation des remises / objectif
		$feuille->getStyle('B' . ($cptObjectif+1) . ':F' . ($cptObjectif+1))->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FFFFFF');
		$feuille->getStyle('B' . ($cptObjectif+1) . ':F' . ($cptObjectif+1))->getBorders()->getTop()->setBorderStyle(Border::BORDER_MEDIUM);
		$feuille->getStyle('B' . ($cptObjectif+1) . ':F' . ($cptObjectif+1))->getBorders()->getTop()->getColor()->setRGB('01B4BC');
		$feuille->getStyle('B' . ($cptObjectif+1) . ':F' . ($cptObjectif+1))->getBorders()->getBottom()->setBorderStyle(Border::BORDER_MEDIUM);
		$feuille->getStyle('B' . ($cptObjectif+1) . ':F' . ($cptObjectif+1))->getBorders()->getBottom()->getColor()->setRGB('01B4BC');
		$feuille->getStyle('B' . ($cptObjectif+1) . ':F' . ($cptObjectif+1))->getBorders()->getLeft()->setBorderStyle(Border::BORDER_MEDIUM);
		$feuille->getStyle('B' . ($cptObjectif+1) . ':F' . ($cptObjectif+1))->getBorders()->getLeft()->getColor()->setRGB('01B4BC');
		$feuille->getStyle('B' . ($cptObjectif+1) . ':F' . ($cptObjectif+1))->getBorders()->getRight()->setBorderStyle(Border::BORDER_MEDIUM);
		$feuille->getStyle('B' . ($cptObjectif+1) . ':F' . ($cptObjectif+1))->getBorders()->getRight()->getColor()->setRGB('01B4BC');
		$feuille->setCellValue('C' . ($cptObjectif+1), 'Total');
		/*$feuille->setCellValue('D' . ($cptObjectif+1), '=SUM(D4:D' . $cptObjectif . ')');
		$feuille->getStyle('D' . ($cptObjectif+1))->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_CURRENCY_EUR_SIMPLE);
		$feuille->getStyle('D' . ($cptObjectif+1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);*/
		$feuille->setCellValue('E' . ($cptObjectif+1), '=SUM(E4:E' . $cptObjectif . ')');
		$feuille->getStyle('E' . ($cptObjectif+1))->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_CURRENCY_EUR_SIMPLE);
		$feuille->getStyle('E' . ($cptObjectif+1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

		// Estimation des remises / produit
		$feuille->getStyle('I' . ($cptProduit+1) . ':O' . ($cptProduit+1))->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FFFFFF');
		$feuille->getStyle('I' . ($cptProduit+1) . ':O' . ($cptProduit+1))->getBorders()->getTop()->setBorderStyle(Border::BORDER_MEDIUM);
		$feuille->getStyle('I' . ($cptProduit+1) . ':O' . ($cptProduit+1))->getBorders()->getTop()->getColor()->setRGB('01B4BC');
		$feuille->getStyle('I' . ($cptProduit+1) . ':O' . ($cptProduit+1))->getBorders()->getBottom()->setBorderStyle(Border::BORDER_MEDIUM);
		$feuille->getStyle('I' . ($cptProduit+1) . ':O' . ($cptProduit+1))->getBorders()->getBottom()->getColor()->setRGB('01B4BC');
		$feuille->getStyle('I' . ($cptProduit+1) . ':O' . ($cptProduit+1))->getBorders()->getLeft()->setBorderStyle(Border::BORDER_MEDIUM);
		$feuille->getStyle('I' . ($cptProduit+1) . ':O' . ($cptProduit+1))->getBorders()->getLeft()->getColor()->setRGB('01B4BC');
		$feuille->getStyle('I' . ($cptProduit+1) . ':O' . ($cptProduit+1))->getBorders()->getRight()->setBorderStyle(Border::BORDER_MEDIUM);
		$feuille->getStyle('I' . ($cptProduit+1) . ':O' . ($cptProduit+1))->getBorders()->getRight()->getColor()->setRGB('01B4BC');
		$feuille->setCellValue('J' . ($cptProduit+1), 'Total');
		$feuille->setCellValue('N' . ($cptProduit+1), '=SUM(N4:N' . $cptProduit . ')');
		$feuille->getStyle('N' . ($cptProduit+1))->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_CURRENCY_EUR_SIMPLE);
		$feuille->getStyle('N' . ($cptProduit+1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
			
		/*******************
		 * Pied du tableau *
		 *******************/

		$feuille->getRowDimension($cptProduit+2)->setRowHeight(26);
		
		// Estimation des remises / objectif
		$feuille->getStyle('B' . ($cptObjectif+2) . ':F' . ($cptObjectif+2))->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('E8E8E8');
		$feuille->setCellValue('C' . ($cptObjectif+2), '* Les calculs de remises ne prennent pas en compte les conditions particulières à l\'échelle de la clinique');
		$feuille->getStyle('C' . ($cptObjectif+2))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
		$feuille->getStyle('C' . ($cptObjectif+2))->getFont()->setSize(8);

		// Estimation des remises / produit
		$feuille->getStyle('I' . ($cptProduit+2) . ':O' . ($cptProduit+2))->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('E8E8E8');
		$feuille->setCellValue('J' . ($cptProduit+2), '* Les calculs de remises ne prennent pas en compte les conditions particulières à l\'échelle de la clinique');
		$feuille->getStyle('J' . ($cptProduit+2))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
		$feuille->getStyle('J' . ($cptProduit+2))->getFont()->setSize(8);

		/**********************
		 * Filtres du tableau *
		 **********************/

		$feuille->setAutoFilter('C3:N' . max($cptProduit, $cptObjectif));

		/**********************
		 * Contour du tableau *
		 **********************/

		$feuille->getRowDimension(max($cptProduit+3, $cptObjectif+3))->setRowHeight(5);
		$feuille->getStyle('A1:A' . ($cptObjectif+3))->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('212629');
		$feuille->getStyle('G1:G' . ($cptObjectif+3))->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('212629');
		$feuille->getStyle('H1:H' . ($cptProduit+3))->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('212629');
		$feuille->getStyle('P1:P' . ($cptProduit+3))->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('212629');
		$feuille->getStyle('A' . ($cptObjectif+3) . ':G' . ($cptObjectif+3))->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('212629');
		$feuille->getStyle('H' . ($cptProduit+3) . ':P' . ($cptProduit+3))->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('212629');
	}

	private function generateEstimationRFASynthese($feuille, $moisDebut, $anneeDebut, $moisFin, $anneeFin, $nomEntreprise, $syntheseLaboratoires)
	{
		$feuille->getSheetView()->setZoomScale(130);

		// Tailles des colonnes et des lignes
		$feuille->getDefaultColumnDimension()->setAutoSize(false);
		$feuille->getDefaultRowDimension()->setRowHeight(26);
		$feuille->getColumnDimension('A')->setWidth(0.83203125);
		$feuille->getColumnDimension('B')->setWidth(2.33203125);
		$feuille->getColumnDimension('C')->setWidth(21);
		$feuille->getColumnDimension('D')->setWidth(18);
		$feuille->getColumnDimension('E')->setWidth(18);
		$feuille->getColumnDimension('F')->setWidth(18);
		$feuille->getColumnDimension('G')->setWidth(18);
		$feuille->getColumnDimension('H')->setWidth(1.6640625);
		$feuille->getColumnDimension('I')->setWidth(0.83203125);

		/************************
		 * Entête de la feuille *
		 ************************/

		$feuille->getRowDimension('1')->setRowHeight(42);
		$feuille->getStyle('A1:I1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('212629');
		$feuille->setCellValue('B1', 'Amadeo');
		$feuille->getStyle('B1')->getFont()->getColor()->setRGB('FFFFFF');
		$feuille->getStyle('B1')->getFont()->setSize(22);
		$feuille->getStyle('B1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
		$feuille->setCellValue('G1', $nomEntreprise);
		$feuille->getStyle('G1')->getFont()->getColor()->setRGB('9B9B9B');
		$feuille->getStyle('G1')->getFont()->setSize(12);
		$feuille->getStyle('G1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

		/***********************
		 * Titre de la feuille *
		 ***********************/

		$feuille->getRowDimension('2')->setRowHeight(42);
		$feuille->getStyle('B2:H2')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('01B4BC');
		$feuille->getStyle('B2:H2')->getFont()->getColor()->setRGB('E8E8E8');
		$feuille->getStyle('C2')->getFont()->setSize(14);
		$feuille->setCellValue('C2', 'Synthèse');
		$feuille->getStyle('C2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
		setlocale(LC_ALL, 'fr_FR.UTF-8');
		$feuille->setCellValue('G2', 'Période : de ' . strftime("%B",strtotime($anneeDebut . "-" . $moisDebut . "-01")) . ' ' . $anneeDebut . ' à ' . strftime("%B",strtotime($anneeFin . "-" . $moisFin . "-01")) . ' ' . $anneeFin);
		$feuille->getStyle('G2')->getFont()->setSize(11);
		$feuille->getStyle('G2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

		/*********************
		 * Entête du tableau *
		 *********************/

		$feuille->getRowDimension('3')->setRowHeight(62);
		$feuille->getStyle('B3:H3')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('E8E8E8');
		$feuille->getStyle('B3:H3')->getFont()->getColor()->setRGB('01B4BC');
		$feuille->getStyle('B3:H3')->getAlignment()->setWrapText(true);
		//$feuille->mergeCells('C3:F3');
		$feuille->setCellValue('C3', 'Laboratoire');
		$feuille->setCellValue('G3', 'Remise financière estimée pour l\'entreprise sur la période (€)*');
		
		/*********************
		 * Lignes du tableau *
		 *********************/

		$cpt = 3;
		foreach ($syntheseLaboratoires as $laboratoire) 
		{
			$cpt++;
			$feuille->getRowDimension($cpt)->setRowHeight(26);
			
			// Estimation des remises
			$feuille->getStyle('B' . $cpt . ':H' . $cpt)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FFFFFF');
			$feuille->getStyle('B' . $cpt . ':H' . $cpt)->getBorders()->getTop()->setBorderStyle(Border::BORDER_THICK);
			$feuille->getStyle('B' . $cpt . ':H' . $cpt)->getBorders()->getTop()->getColor()->setRGB('E8E8E8');
			$feuille->mergeCells('C' . $cpt . ':F' . $cpt);
			$feuille->setCellValue('C' . $cpt, $laboratoire->nom);
			$feuille->getStyle('C' . $cpt)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
			$feuille->getStyle('C' . $cpt)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
			$feuille->getStyle('C' . $cpt)->getAlignment()->setWrapText(true);
			$feuille->setCellValue('G' . $cpt, $laboratoire->total_remises);
			$feuille->getStyle('G' . $cpt)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_CURRENCY_EUR_SIMPLE);
			$feuille->getStyle('G' . $cpt)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
		}

		/********************
		 * Total du tableau *
		 ********************/

		$feuille->getRowDimension($cpt+1)->setRowHeight(10);
		$feuille->getStyle('B' . ($cpt+1) . ':H' . ($cpt+1))->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('E8E8E8');
		$feuille->getRowDimension($cpt+2)->setRowHeight(26);
		$feuille->getStyle('B' . ($cpt+2) . ':H' . ($cpt+2))->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FFFFFF');
		$feuille->getStyle('B' . ($cpt+2) . ':H' . ($cpt+2))->getBorders()->getTop()->setBorderStyle(Border::BORDER_MEDIUM);
		$feuille->getStyle('B' . ($cpt+2) . ':H' . ($cpt+2))->getBorders()->getTop()->getColor()->setRGB('01B4BC');
		$feuille->getStyle('B' . ($cpt+2) . ':H' . ($cpt+2))->getBorders()->getBottom()->setBorderStyle(Border::BORDER_MEDIUM);
		$feuille->getStyle('B' . ($cpt+2) . ':H' . ($cpt+2))->getBorders()->getBottom()->getColor()->setRGB('01B4BC');
		$feuille->getStyle('B' . ($cpt+2) . ':H' . ($cpt+2))->getBorders()->getLeft()->setBorderStyle(Border::BORDER_MEDIUM);
		$feuille->getStyle('B' . ($cpt+2) . ':H' . ($cpt+2))->getBorders()->getLeft()->getColor()->setRGB('01B4BC');
		$feuille->getStyle('B' . ($cpt+2) . ':H' . ($cpt+2))->getBorders()->getRight()->setBorderStyle(Border::BORDER_MEDIUM);
		$feuille->getStyle('B' . ($cpt+2) . ':H' . ($cpt+2))->getBorders()->getRight()->getColor()->setRGB('01B4BC');
		$feuille->setCellValue('C' . ($cpt+2), 'Totaux');
		$feuille->setCellValue('G' . ($cpt+2), '=SUM(G4:G' . $cpt . ')');
		$feuille->getStyle('D' . ($cpt+2) . ':G' . ($cpt+2))->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_CURRENCY_EUR_SIMPLE);
		$feuille->getStyle('D' . ($cpt+2) . ':G' . ($cpt+2))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

		/*******************
		 * Pied du tableau *
		 *******************/

		$feuille->getRowDimension($cpt+3)->setRowHeight(14);
		$feuille->getStyle('B' . ($cpt+3) . ':H' . ($cpt+3))->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('E8E8E8');
		$feuille->setCellValue('C' . ($cpt+3), '* Les calculs de remises ne prennent pas en compte les conditions particulières à l\'échelle de la clinique');
		$feuille->getStyle('C' . ($cpt+3))->getFont()->setSize(8);
		$feuille->getStyle('C' . ($cpt+3))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

		/**********************
		 * Filtres du tableau *
		 **********************/

		$feuille->setAutoFilter('C3:G' . $cpt);

		/**********************
		 * Contour du tableau *
		 **********************/

		$feuille->getRowDimension($cpt+4)->setRowHeight(5);
		$feuille->getStyle('A1:A' . ($cpt+4))->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('212629');
		$feuille->getStyle('I1:I' . ($cpt+4))->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('212629');
		$feuille->getStyle('A' . ($cpt+4) . ':I' . ($cpt+4))->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('212629');
	}

	/*
    * Recherche les produits pour le calcul des prix nets pour téléchargement d'un Excel.
    */
	public function exportExtractionPrixNetsExcel($annee, $remise, ProduitRepository $produitRepository)
	{
		$produits = $produitRepository->findExtractionPrixNets($annee, $remise);

		// Fichier
		$classeur = new Spreadsheet();
		$classeur->getDefaultStyle()->getFont()->getColor()->setRGB('212629');
		$classeur->getActiveSheet()->getPageSetup()->setPaperSize(PageSetup::PAPERSIZE_A4);
		
		$classeur->setActiveSheetIndex(0);
		$feuille = $classeur->getActiveSheet()->setTitle('Prix nets');
		$feuille->getSheetView()->setZoomScale(100);
		$feuille->getDefaultColumnDimension()->setAutoSize(true);
		
		/*********************
		 * Entête du tableau *
		 *********************/

		$feuille->setCellValue('A1', 'Laboratoire');
		$feuille->setCellValue('B1', 'Dénomination');
		$feuille->setCellValue('C1', 'Conditionnement');
		$feuille->setCellValue('D1', 'Code GTIN');
		$feuille->setCellValue('E1', 'Code classe 1');
		$feuille->setCellValue('F1', 'Nom classe 1');
		$feuille->setCellValue('G1', 'Code classe 2');
		$feuille->setCellValue('H1', 'Nom classe 2');
		$feuille->setCellValue('I1', 'Code classe 3');
		$feuille->setCellValue('J1', 'Nom classe 3');
		$feuille->setCellValue('K1', 'Tarif Alcyon');
		$feuille->setCellValue('L1', 'Remise Alcyon');
		$feuille->setCellValue('M1', 'Tarif laboratoire');
		$feuille->setCellValue('N1', 'Remise laboratoire');
		$feuille->setCellValue('O1', 'Total qté achetée ' . $annee);
		$feuille->setCellValue('P1', 'Total CA acheté ' . $annee);
		$feuille->setCellValue('Q1', 'Total qté achetée ' . ($annee-1));
		$feuille->setCellValue('R1', 'Total CA acheté ' . ($annee-1));
		$feuille->setCellValue('S1', 'Prix net');
		
		/*********************
		 * Lignes du tableau *
		 *********************/

		$cpt = 1;
		foreach ($produits as $produit) 
		{
			$cpt++;

			$feuille->setCellValue('A' . $cpt, $produit->lab_nom);
			$feuille->setCellValue('B' . $cpt, $produit->denomination);
			$feuille->setCellValue('C' . $cpt, $produit->conditionnement);
			$feuille->setCellValue('D' . $cpt, $produit->code_gtin);
			$feuille->setCellValue('E' . $cpt, $produit->classe1_code);
			$feuille->setCellValue('F' . $cpt, $produit->classe1_nom);
			$feuille->setCellValue('G' . $cpt, $produit->classe2_code);
			$feuille->setCellValue('H' . $cpt, $produit->classe2_nom);
			$feuille->setCellValue('I' . $cpt, $produit->classe3_code);
			$feuille->setCellValue('J' . $cpt, $produit->classe3_nom);
			$feuille->setCellValue('K' . $cpt, $produit->tarif_centrale);
			$feuille->getStyle('K' . $cpt)->getNumberFormat()->setFormatCode('# ##0.00_-"€"');
			$feuille->getStyle('K' . $cpt)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
			$feuille->setCellValue('L' . $cpt, $remise);
			$feuille->getStyle('L' . $cpt)->getNumberFormat()->setFormatCode('# ##0.00_-"%"');
			$feuille->getStyle('Q' . $cpt)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
			$feuille->setCellValue('M' . $cpt, $produit->tarif_laboratoire);
			$feuille->getStyle('M' . $cpt)->getNumberFormat()->setFormatCode('# ##0.00_-"€"');
			$feuille->getStyle('M' . $cpt)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
			$feuille->setCellValue('N' . $cpt, $produit->cumul_remises);
			$feuille->getStyle('N' . $cpt)->getNumberFormat()->setFormatCode('# ##0.00_-"%"');
			$feuille->getStyle('N' . $cpt)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
			$feuille->setCellValue('O' . $cpt, $produit->qte_periode);
			$feuille->setCellValue('P' . $cpt, $produit->ca_periode);
			$feuille->getStyle('P' . $cpt)->getNumberFormat()->setFormatCode('# ##0.00_-"€"');
			$feuille->getStyle('P' . $cpt)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
			$feuille->setCellValue('Q' . $cpt, $produit->qte_periode_prec);
			$feuille->setCellValue('R' . $cpt, $produit->ca_periode_prec);
			$feuille->getStyle('R' . $cpt)->getNumberFormat()->setFormatCode('# ##0.00_-"€"');
			$feuille->getStyle('R' . $cpt)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
			$feuille->setCellValue('S' . $cpt, '=(K' . $cpt . '*(1-L' . $cpt . '/100)) - (M' . $cpt . '*N' . $cpt . '/100)');
			$feuille->getStyle('S' . $cpt)->getNumberFormat()->setFormatCode('# ##0.00_-"€"');
			$feuille->getStyle('S' . $cpt)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
		}

        /**********************
         * Filtres du tableau *
         **********************/

        $feuille->setAutoFilter('A1:S1');

		header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
		header('Content-Disposition: attachment;filename="' . date('Ymd') . '_Extraction_prix_nets_' . $annee . '_' . $remise . '.xlsx"');
	    $writer = IOFactory::createWriter($classeur, 'Xlsx');
	    $writer->save('php://output');
		exit;
	}

    /*
    * Exporte les bilans de fin d'année au format Excel.
    */
	public function exportBilanRFAExcel($detail, $annee)
	{
		$this->dispatch(new ExportBilansRFA(Auth::user()->name, $detail, $annee));
	}

}
