<?php

namespace App\Jobs;

use App\Jobs\Job;
use App\Model\Objectif;
use App\Repositories\CliniqueRepository;
use App\Repositories\LaboratoireRepository;
use App\Repositories\ObjectifRepository;
use App\Repositories\ProduitRepository;
use Illuminate\Support\Facades\Log;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use ZipArchive;

class ExportBilansRFA extends Job implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $detail;
    protected $annee;
    protected $userName;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($userName, $detail, $annee)
    {
        $this->detail = $detail;
        $this->annee = $annee;
        $this->userName = $userName;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(CliniqueRepository $cliniqueRepository, LaboratoireRepository $laboratoireRepository, ObjectifRepository $objectifRepository, ProduitRepository $produitRepository)
    {
        // Création du dossier d'extraction
        $chemin = storage_path('app/bilansFinAnnee');
        $date = date('YmdHis');
        $repertoire = $chemin . "/" . $date . "/";
        mkdir($repertoire, 0777, true);
        // Création de l'archive
        $zip = new ZipArchive;
        $zipFilename = $repertoire . $date . '_BilansRFA_VetFamily (' . $this->userName . ').zip';
        $res = $zip->open($zipFilename, ZipArchive::CREATE);
        if ($res !== TRUE)
        {
            die ("Could not create archive");
        }

        // 1ère passe : Récupérations des objectifs suivis atteints et non atteints
        $listeObjectifsAtteints = $objectifRepository->findObjectifsAtteints($this->annee, 12);
        $listeObjectifsNonAtteints = $objectifRepository->findObjectifsNonAtteints($this->annee, 12);

        // 2e passe : Mise à jour des objectifs paliers suivis non atteints
        foreach ($listeObjectifsNonAtteints as $objectif) {
            // S'il s'agit d'un palier
            if ($objectif->type_objectif_id == 2)
            {
                $listeObjectifsAtteints = $this->addPalierPrecedentAtteint($objectif, $listeObjectifsAtteints, $this->annee, $objectifRepository);
            }
        }

        // 3e passe : Vérification des objectifs conditionnés pour les objectifs indiqués comme atteints
        $objectifsToDelete = [];
        foreach ($listeObjectifsAtteints as $key => $objectif) {
            if ($objectif->objectif_conditionne_id != null)
            {
                // Si l'objectif conditionné n'est pas atteint
                if (!$listeObjectifsAtteints->contains('id', $objectif->objectif_conditionne_id) && !in_array($key, $objectifsToDelete))
                {
                    // Ajout de l'objectif à la liste des atteints à supprimer
                    array_push($objectifsToDelete, $key);
                    // Ajout de l'objectif à la liste des non atteints
                    $listeObjectifsNonAtteints->push($objectif);
                } 
            }

            // S'il s'agit d'un palier
            if($objectif->type_objectif_id == 2)
            {
                // Supprimer les éventuels paliers
                $objectifsToDelete = $this->searchPalierPrecedent($objectif, $listeObjectifsAtteints, $objectifsToDelete);
            }
        }

        foreach ($objectifsToDelete as $key => $value) {
            $listeObjectifsAtteints->pull($value);
        }
            
        // 4e passe : Vérification des objectifs conditionnés
        $objectifsToDelete = [];
        foreach ($listeObjectifsAtteints as $key => $objectif) {
            if ($objectif->objectif_conditionne_id != null)
            {
                // Si l'objectif conditionné n'est pas atteint
                if (!$listeObjectifsAtteints->contains('id', $objectif->objectif_conditionne_id) && !in_array($key, $objectifsToDelete))
                {
                    // Ajout de l'objectif à la liste des atteints à supprimer
                    array_push($objectifsToDelete, $key);
                    // Ajout de l'objectif à la liste des non atteints
                    $listeObjectifsNonAtteints->push($objectif);
                } 
            }
        }

        foreach ($objectifsToDelete as $key => $value) {
            $listeObjectifsAtteints->pull($value);
        }
        $syntheseLaboratoires = $laboratoireRepository->findBilanRFAForExcel($this->annee);

        $cliniques = $cliniqueRepository->findAll($this->annee, null);

        /* Création du fichier de synthèse pour les administrateurs */
    
        // Fichier
        $classeurAdmin = new Spreadsheet();
        $classeurAdmin->getDefaultStyle()->getFont()->getColor()->setRGB('212629');
        $classeurAdmin->getDefaultStyle()->getFont()->setSize(9);
        $classeurAdmin->getDefaultStyle()->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        $classeurAdmin->getDefaultStyle()->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        
        // Création des feuilles "Laboratoires"
        $cptFeuilles = 1;
        foreach ($syntheseLaboratoires as $laboratoire) {
            $laboratoire->{"nbValides"} = 0;
            $laboratoire->{"nbNonValides"} = 0;
            $laboratoire->{"remise"} = 0;

            // Filtre des objectifs atteints pour le laboratoire parcouru
            $objectifsAtteints = $listeObjectifsAtteints->filter(function ($objectif, $key) use ($laboratoire) {
                if ($objectif->lab_id == $laboratoire->id)
                    return $objectif;
            });
            $objectifsAtteints->all();
            $objectifsAtteints = $objectifsAtteints->sortBy('nom');
            $objectifsAtteints->values()->all();

            // Filtre des objectifs non atteints pour le laboratoire parcouru
            $objectifsNonAtteints = $listeObjectifsNonAtteints->filter(function ($objectif, $key) use ($laboratoire) {
                if ($objectif->lab_id == $laboratoire->id)
                    return $objectif;
            });
            $objectifsNonAtteints->all();
            $objectifsNonAtteints = $objectifsNonAtteints->sortBy('nom');
            $objectifsNonAtteints->values()->all();

            // Recherche des produits du laboratoire
            $produitsLaboratoire = null;
            if ($objectifsAtteints != null && sizeof($objectifsAtteints) > 0)
            {
                $listeObjectifsTemp = $objectifsAtteints;
                $listeObjectifsId = array_merge($listeObjectifsTemp->pluck('id')->all());
                $listeObjectifsId = array_unique($listeObjectifsId);
                $listeObjectifsId = array_diff($listeObjectifsId, array(NULL));
                asort($listeObjectifsId);
                $objectifsIds = array_values($listeObjectifsId);
                $produitsLaboratoire = $produitRepository->findBilanRFAForExcel($laboratoire->id, $this->annee, $objectifsIds, null);
            }

            if (($objectifsAtteints != null && sizeof($objectifsAtteints) > 0) || ($objectifsNonAtteints != null && sizeof($objectifsNonAtteints) > 0) || ($produitsLaboratoire != null && sizeof($produitsLaboratoire) > 0))
            {
                $nomLaboratoire = (strlen($laboratoire->nom) > 19 ? substr_replace($laboratoire->nom, "...", 16) : $laboratoire->nom) . ' (cliniques)';
                $feuilleLaboratoireClinique = new Worksheet($classeurAdmin, $nomLaboratoire);
                $feuilleLaboratoireClinique->getPageSetup()->setPaperSize(PageSetup::PAPERSIZE_A4);
                $feuilleLaboratoireClinique->setShowGridLines(false);
                $classeurAdmin->addSheet($feuilleLaboratoireClinique, $cptFeuilles);
                $this->generateBilanRFALaboratoireClinique($feuilleLaboratoireClinique, $this->annee, 'Administrateur', $laboratoire, $cliniques, $objectifsAtteints, $objectifRepository, $cliniqueRepository);
                $cptFeuilles++;
                
                $feuilleLaboratoire = new Worksheet($classeurAdmin, $laboratoire->nom);
                $feuilleLaboratoire->getPageSetup()->setPaperSize(PageSetup::PAPERSIZE_A4);
                $feuilleLaboratoire->setShowGridLines(false);
                $classeurAdmin->addSheet($feuilleLaboratoire, $cptFeuilles);
                $laboratoire = $this->generateBilanRFALaboratoire($this->detail, $feuilleLaboratoire, $this->annee, 'Administrateur', $laboratoire, null, $produitsLaboratoire, $objectifsAtteints, $objectifsNonAtteints, true, $objectifRepository);
                $cptFeuilles++;
            }
        }

        // Création de la feuille de synthèse
        $classeurAdmin->setActiveSheetIndex(0);
        $feuilleSynthese = $classeurAdmin->getActiveSheet()->setTitle('Synthèse');
        $feuilleSynthese->setShowGridLines(false);
        $this->generateBilanRFASynthese($this->detail, $feuilleSynthese, $this->annee, 'Administrateur', $syntheseLaboratoires, true, $laboratoireRepository, null);

        $writer = new Xlsx($classeurAdmin);
        $nomXlsxAdmin = $this->annee . '_Synthèse_administrateurs_VetFamily.xlsx';
        $writer->save($repertoire . $nomXlsxAdmin);
        $zip->addFile($repertoire . $nomXlsxAdmin, $date . '_BilansRFA_VetFamily/' . $nomXlsxAdmin);

        // Release memory
        $writer = null;
        unset($writer);

        if ($this->detail == '1')
        {
            $repertoireAdherents = $repertoire . $date . "/";
            mkdir($repertoireAdherents, 0777, true);
            
            /* Création du fichier de synthèse pour les cliniques */
            foreach ($cliniques as $clinique) {
                $classeurClinique = new Spreadsheet();
                $classeurClinique->getDefaultStyle()->getFont()->getColor()->setRGB('212629');
                $classeurClinique->getDefaultStyle()->getFont()->setSize(9);
                $classeurClinique->getDefaultStyle()->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
                $classeurClinique->getDefaultStyle()->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $classeurClinique->getActiveSheet()->getPageSetup()->setPaperSize(PageSetup::PAPERSIZE_A4);
                    
                // Création des feuilles "Laboratoires"
                $cptFeuilles = 1;
                foreach ($syntheseLaboratoires as $laboratoire) {
                    // Filtre des objectifs atteints pour le laboratoire parcouru
                    $objectifsAtteints = $listeObjectifsAtteints->filter(function ($objectif, $key) use ($laboratoire) {
                        if ($objectif->lab_id == $laboratoire->id)
                            return $objectif;
                    });
                    $objectifsAtteints->all();
                    $objectifsAtteints = $objectifsAtteints->sortBy('nom');
                    $objectifsAtteints->values()->all();

                    // Filtre des objectifs non atteints pour le laboratoire parcouru
                    $objectifsNonAtteints = $listeObjectifsNonAtteints->filter(function ($objectif, $key) use ($laboratoire) {
                        if ($objectif->lab_id == $laboratoire->id)
                            return $objectif;
                    });
                    $objectifsNonAtteints->all();
                    $objectifsNonAtteints = $objectifsNonAtteints->sortBy('nom');
                    $objectifsNonAtteints->values()->all();

                    // Recherche des produits du laboratoire
                    $produitsLaboratoire = null;
                    if ($objectifsAtteints != null && sizeof($objectifsAtteints) > 0)
                    {
                        $listeObjectifsTemp = $objectifsAtteints;
                        $listeObjectifsId = array_merge($listeObjectifsTemp->pluck('id')->all());
                        $listeObjectifsId = array_unique($listeObjectifsId);
                        $listeObjectifsId = array_diff($listeObjectifsId, array(NULL));
                        asort($listeObjectifsId);
                        $objectifsIds = array_values($listeObjectifsId);
                        $produitsLaboratoire = $produitRepository->findBilanRFAForExcel($laboratoire->id, $this->annee, $objectifsIds, $clinique->clinique_id);
                    }

                    if (($objectifsAtteints != null && sizeof($objectifsAtteints) > 0) || ($objectifsNonAtteints != null && sizeof($objectifsNonAtteints) > 0) || ($produitsLaboratoire != null && sizeof($produitsLaboratoire) > 0))
                    {
                        $feuilleLaboratoire = new Worksheet($classeurClinique, $laboratoire->nom);
                        $classeurClinique->addSheet($feuilleLaboratoire, $cptFeuilles);
                        $laboratoire = $this->generateBilanRFALaboratoire($this->detail, $feuilleLaboratoire, $this->annee, $clinique->veterinaires, $laboratoire, $clinique->clinique_id, $produitsLaboratoire, $objectifsAtteints, $objectifsNonAtteints, false, $objectifRepository);
                        $cptFeuilles++;
                    }
                }

                // Création de la feuille de synthèse
                $classeurClinique->setActiveSheetIndex(0);
                $feuilleSynthese = $classeurClinique->getActiveSheet()->setTitle('Synthèse');
                $this->generateBilanRFASynthese($this->detail, $feuilleSynthese, $this->annee, $clinique->veterinaires, $syntheseLaboratoires, false, $laboratoireRepository, $clinique->clinique_id);

                // Save Xlsx file
                $writer = new Xlsx($classeurClinique);
                $filenameClinique = $clinique->clinique_id . '_Synthèse_adhérent_VetFamily_' . $this->annee . '_(' . str_replace('/', '-', $clinique->veterinaires) . ')';
                $writer->save($repertoireAdherents . $filenameClinique . '.xlsx');
                $zip->addFile($repertoireAdherents . $filenameClinique . '.xlsx', $date . '_BilansRFA_VetFamily/adherents/' . $filenameClinique . '.xlsx');

                // Release memory
                $classeurClinique->__destruct();
                $classeurClinique = null;
                unset($classeurClinique);
                $writer = null;
                unset($writer);
            }
        }

        $zip->close();
        
        // Copie du dossier dans le répertoire du FTP
        // $this->custom_copy($repertoire, '/home/ftpusers/vetFamily/BilansRFA/' . $date); 

        // Release memory
        $classeurAdmin->__destruct();
        $classeurAdmin = null;
        unset($classeurAdmin);
        $writer = null;
        unset($writer);
    }

    private function custom_copy($src, $dst) {  
        // open the source directory 
        $dir = opendir($src);  
      
        // Make the destination directory if not exist 
        @mkdir($dst);  
      
        // Loop through the files in source directory 
        while( $file = readdir($dir) ) {  
      
            if (( $file != '.' ) && ( $file != '..' )) {  
                if ( is_dir($src . '/' . $file) )  
                {  
                    // Recursively calling custom copy function 
                    // for sub directory  
                    $this->custom_copy($src . '/' . $file, $dst . '/' . $file);  
      
                }  
                else {  
                    copy($src . '/' . $file, $dst . '/' . $file);  
                }  
            }  
        }  
      
        closedir($dir); 
    }  

    private function addPalierPrecedentAtteint($objectif, $listeObjectifsAtteints, $annee, $objectifRepository)
    {
        if ($objectif->objectif_precedent_id != null)
        {
            $objectifPrecedent = $objectifRepository->findObjectifPrecedent($objectif->objectif_precedent_id, $annee, 12);
            
            if ($objectifPrecedent != null && $objectifPrecedent->valeur_atteinte && !$objectifPrecedent->suivi)
            {
                // Ajout de l'objectif dans la liste des atteints
                if (!$listeObjectifsAtteints->contains('id', $objectifPrecedent->id))
                {
                    $listeObjectifsAtteints->push($objectifPrecedent);
                }
                return $listeObjectifsAtteints;
            } else
            {
                return $this->addPalierPrecedentAtteint($objectifPrecedent, $listeObjectifsAtteints, $annee, $objectifRepository);
            }
        }

        return $listeObjectifsAtteints;
    }
    
    private function searchPalierPrecedent($objectif, $listeObjectifsAtteints, $objectifsToDelete)
    {
        if ($objectif->objectif_precedent_id != null)
        {
            if (!$listeObjectifsAtteints->contains('id', $objectif->objectif_precedent_id))
            {
                $objPrec = $objectif->objectif_precedent_id;
                $keyObjPrec = $listeObjectifsAtteints->search(function ($item, $key) use ($objPrec) {
                    if ($item->id == $objPrec)
                    {
                        return $key;
                    }
                });
                // Ajout de l'objectif précédent à la liste des atteints à supprimer
                if ($keyObjPrec)
                {
                    array_push($objectifsToDelete, $keyObjPrec);
                }
            }

            // Récursivité
            $objectifPrecedent = Objectif::where('id', $objectif->objectif_precedent_id)->first();

            return $this->searchPalierPrecedent($objectifPrecedent, $listeObjectifsAtteints, $objectifsToDelete);
        }

        return $objectifsToDelete;
    }

    private function generateBilanRFASynthese($detail, $feuille, $annee, $nomEntreprise, $syntheseLaboratoires, $admin, $laboratoireRepository, $cliniqueId)
    {
        $feuille->getSheetView()->setZoomScale(130);

        // Tailles des colonnes et des lignes
        $feuille->getDefaultColumnDimension()->setAutoSize(false);
        $feuille->getDefaultRowDimension()->setRowHeight(26);
        $feuille->getColumnDimension('A')->setWidth(0.83203125);
        $feuille->getColumnDimension('B')->setWidth(2.33203125);
        $feuille->getColumnDimension('C')->setWidth(25.5);
        $feuille->getColumnDimension('D')->setWidth(10);
        $feuille->getColumnDimension('E')->setWidth(10);
        $feuille->getColumnDimension('F')->setWidth(15);
        $feuille->getColumnDimension('G')->setWidth(15);
        $feuille->getColumnDimension('H')->setWidth(15);
        $feuille->getColumnDimension('I')->setWidth(2.33203125);
        $feuille->getColumnDimension('J')->setWidth(0.83203125);

        /************************
         * Entête de la feuille *
         ************************/

        $feuille->getRowDimension('1')->setRowHeight(42);
        $feuille->getStyle('A1:J1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('212629');
        $drawing = new Drawing();
		$drawing->setName('Logo');
		$drawing->setPath(public_path('images/logo_white_background.png'));
		$drawing->setHeight(50);
		$drawing->setCoordinates('B1');
		$drawing->setWorksheet($feuille);
        /*$feuille->setCellValue('B1', 'Amadeo');
        $feuille->getStyle('B1')->getFont()->getColor()->setRGB('FFFFFF');
        $feuille->getStyle('B1')->getFont()->setSize(22);
        $feuille->getStyle('B1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);*/
        $feuille->setCellValue('H1', $nomEntreprise);
        $feuille->getStyle('H1')->getFont()->getColor()->setRGB('9B9B9B');
        $feuille->getStyle('H1')->getFont()->setSize(12);
        $feuille->getStyle('H1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        /***********************
         * Titre de la feuille *
         ***********************/

        $feuille->getRowDimension('2')->setRowHeight(42);
        $feuille->getStyle('B2:I2')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('01B4BC');
        $feuille->getStyle('B2:I2')->getFont()->getColor()->setRGB('E8E8E8');
        $feuille->getStyle('C2')->getFont()->setSize(14);
        $feuille->setCellValue('C2', 'Synthèse');
        $feuille->getStyle('C2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        setlocale(LC_ALL, 'en_US.UTF-8');
        $feuille->setCellValue('H2', 'Année ' . $annee);
        $feuille->getStyle('H2')->getFont()->setSize(11);
        $feuille->getStyle('H2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        /*********************
         * Entête du tableau *
         *********************/

        $feuille->getRowDimension('3')->setRowHeight(62);
        $feuille->getStyle('B3:I3')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('E8E8E8');
        $feuille->getStyle('B3:I3')->getFont()->getColor()->setRGB('01B4BC');
        $feuille->getStyle('B3:I3')->getAlignment()->setWrapText(true);
        $feuille->setCellValue('C3', 'Laboratoire');
        $feuille->setCellValue('D3', 'Nombre objectifs validés');
        if (!$detail)
        {
            $feuille->setCellValue('E3', 'Nombre objectifs non validés');
        }
        
        if ($admin)
        {
            $feuille->setCellValue('F3', 'Remise estimée pour le groupement *');
        } else
        {
            $feuille->setCellValue('F3', 'Remise estimée pour l\'entité juridique *');
        }
        $feuille->setCellValue('G3', 'CA HT total (valorisation centrale)');
        $feuille->setCellValue('H3', 'CA HT total remisé (valorisation centrale)');
        
        /*********************
         * Lignes du tableau *
         *********************/

        $cpt = 3;   
        foreach ($syntheseLaboratoires as $laboratoire) 
        {
            $cpt++;
            $feuille->getRowDimension($cpt)->setRowHeight(26);
            
            // Estimation des remises
            $feuille->getStyle('B' . $cpt . ':I' . $cpt)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FFFFFF');
            $feuille->getStyle('B' . $cpt . ':I' . $cpt)->getBorders()->getTop()->setBorderStyle(Border::BORDER_THICK);
            $feuille->getStyle('B' . $cpt . ':I' . $cpt)->getBorders()->getTop()->getColor()->setRGB('E8E8E8');
            $feuille->setCellValue('C' . $cpt, $laboratoire->nom);
            $feuille->setCellValue('D' . $cpt, $laboratoire->nbValides);
            if (!$detail)
            {
                $feuille->setCellValue('E' . $cpt, $laboratoire->nbNonValides);
            }
            $feuille->setCellValue('F' . $cpt, $laboratoire->remise);
            if ($admin)
            {
                $ca = $laboratoire->ca;
                $ca_remise = $laboratoire->ca_remise;
            }
            else
            {
                $lab = $laboratoireRepository->findCACliniqueById($laboratoire->id, $cliniqueId, 1, 12, $annee);
                $ca = $lab[0]->ca;
                $ca_remise = $lab[0]->ca_remise;
            }
            $feuille->setCellValue('G' . $cpt, round($ca, 2));
            $feuille->setCellValue('H' . $cpt, round($ca_remise, 2));
            $feuille->getStyle('F' . $cpt . ':H' . $cpt)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_CURRENCY_EUR_SIMPLE);
            $feuille->getStyle('F' . $cpt . ':H' . $cpt)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        }

        /********************
         * Total du tableau *
         ********************/

        $feuille->getRowDimension($cpt+1)->setRowHeight(10);
        $feuille->getStyle('B' . ($cpt+1) . ':I' . ($cpt+1))->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('E8E8E8');
        $feuille->getRowDimension($cpt+2)->setRowHeight(26);
        $feuille->getStyle('B' . ($cpt+2) . ':I' . ($cpt+2))->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FFFFFF');
        $feuille->getStyle('B' . ($cpt+2) . ':I' . ($cpt+2))->getBorders()->getTop()->setBorderStyle(Border::BORDER_MEDIUM);
        $feuille->getStyle('B' . ($cpt+2) . ':I' . ($cpt+2))->getBorders()->getTop()->getColor()->setRGB('01B4BC');
        $feuille->getStyle('B' . ($cpt+2) . ':I' . ($cpt+2))->getBorders()->getBottom()->setBorderStyle(Border::BORDER_MEDIUM);
        $feuille->getStyle('B' . ($cpt+2) . ':I' . ($cpt+2))->getBorders()->getBottom()->getColor()->setRGB('01B4BC');
        $feuille->getStyle('B' . ($cpt+2) . ':I' . ($cpt+2))->getBorders()->getLeft()->setBorderStyle(Border::BORDER_MEDIUM);
        $feuille->getStyle('B' . ($cpt+2) . ':I' . ($cpt+2))->getBorders()->getLeft()->getColor()->setRGB('01B4BC');
        $feuille->getStyle('B' . ($cpt+2) . ':I' . ($cpt+2))->getBorders()->getRight()->setBorderStyle(Border::BORDER_MEDIUM);
        $feuille->getStyle('B' . ($cpt+2) . ':I' . ($cpt+2))->getBorders()->getRight()->getColor()->setRGB('01B4BC');
        $feuille->setCellValue('C' . ($cpt+2), 'Totaux');
        $feuille->setCellValue('D' . ($cpt+2), '=SUM(D4:D' . $cpt . ')');
        if (!$detail)
        {
            $feuille->setCellValue('E' . ($cpt+2), '=SUM(E4:E' . $cpt . ')');
        }
        $feuille->setCellValue('F' . ($cpt+2), '=SUM(F4:F' . $cpt . ')');
        $feuille->setCellValue('G' . ($cpt+2), '=SUM(G4:G' . $cpt . ')');
        $feuille->setCellValue('H' . ($cpt+2), '=SUM(H4:H' . $cpt . ')');
        $feuille->getStyle('F' . ($cpt+2) . ':H' . ($cpt+2))->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_CURRENCY_EUR_SIMPLE);
        $feuille->getStyle('F' . ($cpt+2) . ':H' . ($cpt+2))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        /*******************
         * Pied du tableau *
         *******************/

        $feuille->getRowDimension($cpt+3)->setRowHeight(14);
        $feuille->getStyle('B' . ($cpt+3) . ':I' . ($cpt+3))->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('E8E8E8');
        $feuille->setCellValue('C' . ($cpt+3), '* Les calculs de remises ne prennent pas en compte les conditions particulières à l\'échelle de la clinique');
        $feuille->getStyle('C' . ($cpt+3))->getFont()->setSize(8);
        $feuille->getStyle('C' . ($cpt+3))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

        /**********************
         * Filtres du tableau *
         **********************/

        $feuille->setAutoFilter('C3:H' . $cpt);

        /**********************
         * Contour du tableau *
         **********************/

        $feuille->getRowDimension($cpt+4)->setRowHeight(5);
        $feuille->getStyle('A1:A' . ($cpt+4))->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('212629');
        $feuille->getStyle('J1:J' . ($cpt+4))->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('212629');
        $feuille->getStyle('A' . ($cpt+4) . ':J' . ($cpt+4))->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('212629');
    }

    private function generateBilanRFALaboratoireClinique($feuille, $annee, $nomEntreprise, $laboratoire, $cliniques, $objectifsAtteints, $objectifRepository, $cliniqueRepository)
    {
        $feuille->getSheetView()->setZoomScale(130);

        /**************************************
         * Tailles des colonnes et des lignes *
         **************************************/

        $feuille->getDefaultColumnDimension()->setAutoSize(false);
        $feuille->getColumnDimension('A')->setWidth(0.83203125);
        $feuille->getColumnDimension('B')->setWidth(2.33203125);
        $feuille->getColumnDimension('C')->setWidth(36.63203125);

        /************************
         * Entête de la feuille *
         ************************/

        $feuille->getRowDimension('1')->setRowHeight(42);
        $drawing = new Drawing();
		$drawing->setName('Logo');
		$drawing->setPath(public_path('images/logo_white_background.png'));
		$drawing->setHeight(50);
		$drawing->setCoordinates('B1');
		$drawing->setWorksheet($feuille);

        /***********************
         * Titre de la feuille *
         ***********************/

        $feuille->getRowDimension('2')->setRowHeight(42);
        $feuille->getStyle('C2')->getFont()->setSize(14);
        $feuille->setCellValue('C2', $laboratoire->nom . ' - Estimation des remises des cliniques / objectif suivi validé');
        $feuille->getStyle('C2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

        /*********************
         * Entête du tableau *
         *********************/

        $feuille->getRowDimension('3')->setRowHeight(62);
        $feuille->setCellValue('C3', 'Nom clinique');

        $cptCliniques = 3;
        $lastObjColumn = null;
        foreach ($cliniques as $clinique) {
            $cptCliniques++;
            
            $feuille->getRowDimension($cptCliniques)->setRowHeight(30);

            $feuille->setCellValue('C' . $cptCliniques, $clinique->veterinaires);
            $feuille->getStyle('C' . $cptCliniques)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            $feuille->getStyle('C' . $cptCliniques)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
            $feuille->getStyle('C' . $cptCliniques)->getAlignment()->setWrapText(true);

            $cptColumn = 'C';
            foreach ($objectifsAtteints as $objectif) 
            {
                $cptColumn++;
                if ($cptCliniques == 4)
                {
                    $feuille->getColumnDimension($cptColumn)->setWidth(36.63203125);
                    $objectifAtteintNom = $objectif->nom . ' (' . $objectif->cat_nom . ')';
                    $feuille->setCellValue($cptColumn . '3', (strlen($objectifAtteintNom) > 85 ? substr_replace($objectifAtteintNom, "...", 50) : $objectifAtteintNom));
                    $feuille->getComment($cptColumn . '3')->getText()->createTextRun($objectifAtteintNom);
                    $feuille->getComment($cptColumn . '3')->setWidth("400px");
                }
                
                // Recherche du CA pour la clinique et l'objectif
                $caClinique = $cliniqueRepository->findCAByIdAndTargetId($clinique->clinique_id, null, $objectif->id, $annee, false, null, null, 12, null);
                // Cas "classique" : objectif simple ou palier non incrémentiel
                $valeurRemise = $caClinique[0]->remise_euro;

                if (($objectif->type_objectif_id == 2) && $objectif->incrementiel)
                {
                    // Objectif palier incrémentiel
                    if ($objectif->pourcentage_remise_moyen == null)
                    {
                        $remiseUnite = $this->calculRemisePalierIncrementiel((($objectif->ca_unite - $objectif->valeur)*$objectif->pourcentage_remise/100), $objectif, null, $objectifRepository);
                        $objectif->{"pourcentage_remise_moyen"} = $remiseUnite / $objectif->ca_unite * 100;
                    }
                    $valeurRemise = $caClinique[0]->ca_euro * $objectif->pourcentage_remise_moyen / 100;
                } else if ($objectif->type_objectif_id == 3)
                {
                    // Objectif individuel
                    if ($objectif->incrementiel)
                    {
                        $remiseUnite = $this->calculRemisePalierIncrementiel(0, $objectif, $caClinique[0]->ca_unite, $objectifRepository);
                        $valeurRemise = $caClinique[0]->ca_euro * ($remiseUnite / $caClinique[0]->ca_unite * 100);
                    } else 
                    {
                        $pourcentageRemise = $this->calculRemisePalierIndividuel($caClinique[0]->ca_unite, $objectif, $objectifRepository);
                        $valeurRemise = $caClinique[0]->ca_euro * $pourcentageRemise / 100;
                    }
                }
                $feuille->setCellValue($cptColumn . $cptCliniques, round($valeurRemise, 2));
                $feuille->getStyle($cptColumn . $cptCliniques)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                $feuille->getStyle($cptColumn . $cptCliniques)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_CURRENCY_EUR_SIMPLE);

                if (!property_exists($objectif, "remise_euro_calcule"))
                {
                    $objectif->{"remise_euro_calcule"} = 0;
                }
                $objectif->{"remise_euro_calcule"} += $valeurRemise;
            }

            if ($lastObjColumn == null)
            {
                $lastObjColumn = $feuille->getHighestColumn();
                $totalColumn = $lastObjColumn;
                $totalColumn++;
            }

            $feuille->getColumnDimension($totalColumn)->setWidth(36.63203125);
            $feuille->setCellValue($totalColumn . '3', 'Total');
            if (count($objectifsAtteints) > 0)
            {
                $feuille->setCellValue($totalColumn . $cptCliniques, '=SUM(C' . $cptCliniques . ':' . $lastObjColumn . $cptCliniques . ')');
            } else 
            {
                $feuille->setCellValue($totalColumn . $cptCliniques, 0);
            }
            $feuille->getStyle($totalColumn . $cptCliniques)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_CURRENCY_EUR_SIMPLE);
            $feuille->getStyle($totalColumn . $cptCliniques)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $feuille->getStyle('B' . $cptCliniques . ':' . $totalColumn . $cptCliniques)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FFFFFF');
            $feuille->getStyle('B' . $cptCliniques . ':' . $totalColumn . $cptCliniques)->getBorders()->getTop()->setBorderStyle(Border::BORDER_THICK);
            $feuille->getStyle('B' . $cptCliniques . ':' . $totalColumn . $cptCliniques)->getBorders()->getTop()->getColor()->setRGB('E8E8E8');
        }

        /********************
         * Total du tableau *
         ********************/

        $feuille->getRowDimension($cptCliniques+2)->setRowHeight(5);
        $totalColumn++;
        $feuille->getColumnDimension($totalColumn)->setWidth(2.33203125);

        $feuille->getRowDimension($cptCliniques+1)->setRowHeight(26);
        $feuille->setCellValue('C' . ($cptCliniques+1), 'Total');
        for ($column = 'D'; $column !== $totalColumn; $column++) 
        {
            if ($cptCliniques > 3)
            {
                $feuille->setCellValue($column . ($cptCliniques+1), '=SUM(' . $column . '4:' . $column . $cptCliniques . ')');
            } else
            {
                $feuille->setCellValue($column . ($cptCliniques+1), 0);
            }
            $feuille->getStyle($column . ($cptCliniques+1))->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_CURRENCY_EUR_SIMPLE);
            $feuille->getStyle($column . ($cptCliniques+1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        }

        /**********************
         * Contour du tableau *
         **********************/

        $totalColumn++;
        $feuille->getColumnDimension($totalColumn)->setWidth(0.83203125);

        $feuille->getStyle('A1:' . $totalColumn . '1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('212629');
        $feuille->getStyle('B2:' . $totalColumn . '2')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('01B4BC');
        $feuille->getStyle('B2:' . $totalColumn . '2')->getFont()->getColor()->setRGB('E8E8E8');
        $feuille->getStyle('B3:' . $totalColumn . '3')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('E8E8E8');
        $feuille->getStyle('B3:' . $totalColumn . '3')->getFont()->getColor()->setRGB('01B4BC');
        $feuille->getStyle('B3:' . $totalColumn . '3')->getAlignment()->setWrapText(true);

        $feuille->getStyle('B' . ($cptCliniques+1) . ':' . $totalColumn . ($cptCliniques+1))->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FFFFFF');
        $feuille->getStyle('B' . ($cptCliniques+1) . ':' . $totalColumn . ($cptCliniques+1))->getBorders()->getTop()->setBorderStyle(Border::BORDER_MEDIUM);
        $feuille->getStyle('B' . ($cptCliniques+1) . ':' . $totalColumn . ($cptCliniques+1))->getBorders()->getTop()->getColor()->setRGB('01B4BC');
        $feuille->getStyle('B' . ($cptCliniques+1) . ':' . $totalColumn . ($cptCliniques+1))->getBorders()->getBottom()->setBorderStyle(Border::BORDER_MEDIUM);
        $feuille->getStyle('B' . ($cptCliniques+1) . ':' . $totalColumn . ($cptCliniques+1))->getBorders()->getBottom()->getColor()->setRGB('01B4BC');
        $feuille->getStyle('B' . ($cptCliniques+1) . ':' . $totalColumn . ($cptCliniques+1))->getBorders()->getLeft()->setBorderStyle(Border::BORDER_MEDIUM);
        $feuille->getStyle('B' . ($cptCliniques+1) . ':' . $totalColumn . ($cptCliniques+1))->getBorders()->getLeft()->getColor()->setRGB('01B4BC');
        $feuille->getStyle('B' . ($cptCliniques+1) . ':' . $totalColumn . ($cptCliniques+1))->getBorders()->getRight()->setBorderStyle(Border::BORDER_MEDIUM);
        $feuille->getStyle('B' . ($cptCliniques+1) . ':' . $totalColumn . ($cptCliniques+1))->getBorders()->getRight()->getColor()->setRGB('01B4BC');

        $feuille->getStyle('A1:A' . ($cptCliniques+2))->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('212629');
        $feuille->getStyle($totalColumn . '1:' . $totalColumn . ($cptCliniques+2))->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('212629');
        $feuille->getStyle('A' . ($cptCliniques+2) . ':' . $totalColumn . ($cptCliniques+2))->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('212629');
    }

    private function generateBilanRFALaboratoire($detail, $feuille, $annee, $nomEntreprise, $laboratoire, $cliniqueId, $produitsLaboratoire, $objectifsAtteints, $objectifsNonAtteints, $admin, $objectifRepository)
    {
        $feuille->getSheetView()->setZoomScale(130);

        /**************************************
         * Tailles des colonnes et des lignes *
         **************************************/

        $feuille->getDefaultColumnDimension()->setAutoSize(false);
        // Estimation des remises par objectif suivi validé
        $feuille->getColumnDimension('A')->setWidth(0.83203125);
        $feuille->getColumnDimension('B')->setWidth(2.33203125);
        $feuille->getColumnDimension('C')->setWidth(36.63203125);
        $feuille->getColumnDimension('D')->setWidth(13.7640625);
        $feuille->getColumnDimension('E')->setWidth(13.7640625);
        $feuille->getColumnDimension('F')->setWidth(7.6640625);
        $feuille->getColumnDimension('G')->setWidth(13.7640625);
        $feuille->getColumnDimension('H')->setWidth(10);
        $feuille->getColumnDimension('I')->setWidth(2.33203125);
        $feuille->getColumnDimension('J')->setWidth(0.83203125);
        // Estimation des remises par produit
        $feuille->getColumnDimension('K')->setWidth(0.83203125);
        $feuille->getColumnDimension('L')->setWidth(1.6640625);
        $feuille->getColumnDimension('M')->setWidth(25.5);
        $feuille->getColumnDimension('N')->setWidth(25.5);
        $feuille->getColumnDimension('O')->setWidth(16);
        $feuille->getColumnDimension('P')->setWidth(14.4);
        $feuille->getColumnDimension('Q')->setWidth(16);
        $feuille->getColumnDimension('R')->setWidth(1.6640625);
        $feuille->getColumnDimension('S')->setWidth(0.83203125);
        // Objectifs suivis non validés
        if (!$detail)
        {
            $feuille->getColumnDimension('T')->setWidth(0.83203125);
            $feuille->getColumnDimension('U')->setWidth(2.33203125);
            $feuille->getColumnDimension('V')->setWidth(36.63203125);
            $feuille->getColumnDimension('W')->setWidth(13.7640625);
            $feuille->getColumnDimension('X')->setWidth(13.7640625);
            $feuille->getColumnDimension('Y')->setWidth(7.6640625);
            $feuille->getColumnDimension('Z')->setWidth(13.7640625);
            $feuille->getColumnDimension('AA')->setWidth(10);
            $feuille->getColumnDimension('AB')->setWidth(2.33203125);
            $feuille->getColumnDimension('AC')->setWidth(0.83203125);
        }

        /************************
         * Entête de la feuille *
         ************************/

        $feuille->getRowDimension('1')->setRowHeight(42);
        if (!$detail)
        {
            $feuille->getStyle('A1:AC1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('212629');
        } else
        {
            $feuille->getStyle('A1:S1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('212629');
        }
        // Estimation des remises par objectif suivi validé
        $drawing = new Drawing();
		$drawing->setName('Logo');
		$drawing->setPath(public_path('images/logo_white_background.png'));
		$drawing->setHeight(50);
		$drawing->setCoordinates('B1');
		$drawing->setWorksheet($feuille);
        /*$feuille->setCellValue('B1', 'Amadeo');
        $feuille->getStyle('B1')->getFont()->getColor()->setRGB('FFFFFF');
        $feuille->getStyle('B1')->getFont()->setSize(22);
        $feuille->getStyle('B1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);*/
        $feuille->setCellValue('H1', $nomEntreprise);
        $feuille->getStyle('H1')->getFont()->getColor()->setRGB('9B9B9B');
        $feuille->getStyle('H1')->getFont()->setSize(12);
        $feuille->getStyle('H1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        // Estimation des remises par produit
        $drawing = new Drawing();
		$drawing->setName('Logo');
		$drawing->setPath(public_path('images/logo_white_background.png'));
		$drawing->setHeight(50);
		$drawing->setCoordinates('L1');
		$drawing->setWorksheet($feuille);
        /*$feuille->setCellValue('L1', 'Amadeo');
        $feuille->getStyle('L1')->getFont()->getColor()->setRGB('FFFFFF');
        $feuille->getStyle('L1')->getFont()->setSize(22);
        $feuille->getStyle('L1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);*/
        $feuille->setCellValue('Q1', $nomEntreprise);
        $feuille->getStyle('Q1')->getFont()->getColor()->setRGB('9B9B9B');
        $feuille->getStyle('Q1')->getFont()->setSize(12);
        $feuille->getStyle('Q1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        // Objectifs suivis non validés
        if (!$detail)
        {
            $drawing = new Drawing();
            $drawing->setName('Logo');
            $drawing->setPath(public_path('images/logo_white_background.png'));
            $drawing->setHeight(50);
            $drawing->setCoordinates('U1');
            $drawing->setWorksheet($feuille);
            /*$feuille->setCellValue('U1', 'Amadeo');
            $feuille->getStyle('U1')->getFont()->getColor()->setRGB('FFFFFF');
            $feuille->getStyle('U1')->getFont()->setSize(22);
            $feuille->getStyle('U1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);*/
            $feuille->setCellValue('AA1', $nomEntreprise);
            $feuille->getStyle('AA1')->getFont()->getColor()->setRGB('9B9B9B');
            $feuille->getStyle('AA1')->getFont()->setSize(12);
            $feuille->getStyle('AA1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        }

        /***********************
         * Titre de la feuille *
         ***********************/

        // Estimation des remises par objectif suivi validé
        $feuille->getRowDimension('2')->setRowHeight(42);
        $feuille->getStyle('B2:I2')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('01B4BC');
        $feuille->getStyle('B2:I2')->getFont()->getColor()->setRGB('E8E8E8');
        $feuille->getStyle('C2')->getFont()->setSize(14);
        $feuille->setCellValue('C2', $laboratoire->nom . ' - Estimation des remises / objectif suivi validé');
        $feuille->getStyle('C2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        $feuille->setCellValue('H2', 'Année ' . $annee);
        $feuille->getStyle('H2')->getFont()->setSize(11);
        $feuille->getStyle('H2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        // Estimation des remises par produit
        $feuille->getStyle('L2:R2')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('01B4BC');
        $feuille->getStyle('L2:R2')->getFont()->getColor()->setRGB('E8E8E8');
        $feuille->getStyle('M2')->getFont()->setSize(14);
        $feuille->setCellValue('M2', $laboratoire->nom . ' - Estimation des remises / produit');
        $feuille->getStyle('M2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        $feuille->setCellValue('Q2', 'Année ' . $annee);
        $feuille->getStyle('Q2')->getFont()->setSize(11);
        $feuille->getStyle('Q2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        // Objectifs suivis non validés
        if (!$detail)
        {
            $feuille->getRowDimension('2')->setRowHeight(42);
            $feuille->getStyle('U2:AB2')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('01B4BC');
            $feuille->getStyle('U2:AB2')->getFont()->getColor()->setRGB('E8E8E8');
            $feuille->getStyle('V2')->getFont()->setSize(14);
            $feuille->setCellValue('V2', $laboratoire->nom . ' - Objectif suivi non validé');
            $feuille->getStyle('V2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            $feuille->setCellValue('AA2', 'Année ' . $annee);
            $feuille->getStyle('AA2')->getFont()->setSize(11);
            $feuille->getStyle('AA2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        }

        /*********************
         * Entête du tableau *
         *********************/

        // Estimation des remises / objectif suivi validé
        $feuille->getRowDimension('3')->setRowHeight(62);
        $feuille->getStyle('B3:I3')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('E8E8E8');
        $feuille->getStyle('B3:I3')->getFont()->getColor()->setRGB('01B4BC');
        $feuille->getStyle('B3:I3')->getAlignment()->setWrapText(true);
        $feuille->setCellValue('C3', 'Nom objectif (catégorie)');
        if ($admin)
        {
            $feuille->setCellValue('D3', 'Valeur objectif');
        }
        $feuille->setCellValue('E3', 'Valeur atteinte (valorisation contrat)');
        $feuille->setCellValue('F3', '% de remise');
        $feuille->setCellValue('G3', 'Remise estimée (€) *');
        $feuille->setCellValue('H3', 'Espèces');
        
        // Estimation des remises / produit
        $feuille->getStyle('L3:R3')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('E8E8E8');
        $feuille->getStyle('L3:R3')->getFont()->getColor()->setRGB('01B4BC');
        $feuille->getStyle('L3:R3')->getAlignment()->setWrapText(true);
        $feuille->setCellValue('M3', 'Dénomination du produit');
        $feuille->setCellValue('N3', 'Conditionnement du produit');
        $feuille->setCellValue('O3', 'Achats  sur la période (€ au tarif de référence)');
        $feuille->setCellValue('P3', '% de remise');
        $feuille->setCellValue('Q3', 'Remise financière estimée sur la période (€)*');

        // Objectifs suivis non validés
        if (!$detail)
        {
            $feuille->getStyle('U3:AB3')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('E8E8E8');
            $feuille->getStyle('U3:AB3')->getFont()->getColor()->setRGB('01B4BC');
            $feuille->getStyle('U3:AB3')->getAlignment()->setWrapText(true);
            $feuille->setCellValue('V3', 'Nom objectif');
            if ($admin)
            {
                $feuille->setCellValue('W3', 'Valeur objectif');
            }
            $feuille->setCellValue('X3', 'Valeur atteinte (valorisation contrat)');
            $feuille->setCellValue('Y3', '% d\'écart');
            $feuille->setCellValue('Z3', 'Catégorie');
            $feuille->setCellValue('AA3', 'Espèce');
        }
        
        /*********************************************
         * Lignes du tableau / objectif suivi validé *
         *********************************************/

        $cptObjValides = 3;
        if ($objectifsAtteints == null)
        {
            $objectifsAtteints = [];
        }
        foreach ($objectifsAtteints as $objectif) 
        {
            $cptObjValides++;
            $feuille->getRowDimension($cptObjValides)->setRowHeight(30);
            $feuille->getStyle('B' . $cptObjValides . ':I' . $cptObjValides)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FFFFFF');
            $feuille->getStyle('B' . $cptObjValides . ':I' . $cptObjValides)->getBorders()->getTop()->setBorderStyle(Border::BORDER_THICK);
            $feuille->getStyle('B' . $cptObjValides . ':I' . $cptObjValides)->getBorders()->getTop()->getColor()->setRGB('E8E8E8');
            $objectifAtteintNom = $objectif->nom . ' (' . $objectif->cat_nom . ')';
            $feuille->setCellValue('C' . $cptObjValides, (strlen($objectifAtteintNom) > 85 ? substr_replace($objectifAtteintNom, "...", 85) : $objectifAtteintNom));
            $feuille->getComment('C' . $cptObjValides)->getText()->createTextRun($objectifAtteintNom);
            $feuille->getComment('C' . $cptObjValides)->setWidth("400px");
            $feuille->getStyle('C' . $cptObjValides)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            $feuille->getStyle('C' . $cptObjValides)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
            $feuille->getStyle('C' . $cptObjValides)->getAlignment()->setWrapText(true);
            if ($admin)
            {
                $feuille->setCellValue('D' . $cptObjValides, $objectif->valeur);
            }
            $feuille->getStyle('D' . $cptObjValides)->getNumberFormat()->setFormatCode('# ### ##0.00');
            if ($admin)
            {
                $valeurCA = $objectif->ca_unite;

                if (($objectif->type_objectif_id == 1) || ($objectif->type_objectif_id == 2 && !$objectif->incrementiel))
                {
                    // Objectif simple ou palier non incrémentiel
                    $valeurRemise = $objectif->remise_euro;
                    // Calcul de la remise
                    if ($objectif->ca_euro != null && $objectif->ca_euro != 0 && $valeurRemise != null && $valeurRemise != 0)
                    {
                        $objectif->{"pourcentage_remise_moyen"} = ($valeurRemise / $objectif->ca_euro) * 100;
                    } else 
                    {
                        $objectif->{"pourcentage_remise_moyen"} = $objectif->pourcentage_remise;
                    }
                } else if ($objectif->type_objectif_id == 2 && $objectif->incrementiel)
                {
                    // Objectif palier incrémentiel
                    $valeurRemise = $objectif->ca_euro * $objectif->pourcentage_remise_moyen / 100;
                } else if ($objectif->type_objectif_id == 3)
                {
                    // Objectif individuel
                    $valeurRemise = $objectif->remise_euro_calcule;
                    if ($objectif->ca_euro != null && $objectif->ca_euro != 0 && $valeurRemise != null && $valeurRemise != 0)
                    {
                        $objectif->{"pourcentage_remise_moyen"} = ($valeurRemise / $objectif->ca_euro) * 100;
                    } else 
                    {
                        $objectif->{"pourcentage_remise_moyen"} = 0;
                    }
                }
            }
            else
            {
                $caClinique = $objectifRepository->findCACliniqueById($objectif->id, $cliniqueId, 12);
                $valeurCA = $caClinique->ca_unite;

                if ($objectif->type_objectif_id == 3)
                {
                    // Objectif individuel
                    if ($objectif->incrementiel)
                    {
                        $remiseUnite = $this->calculRemisePalierIncrementiel(0, $objectif, $valeurCA, $objectifRepository);
                        $valeurRemise = $caClinique->ca_euro * ($remiseUnite / $valeurCA * 100);
                        $pourcentageRemise = ($valeurRemise / $caClinique->ca_euro) * 100 ;
                    } else 
                    {
                        $pourcentageRemise = $this->calculRemisePalierIndividuel($valeurCA, $objectif, $objectifRepository);
                        $valeurRemise = $caClinique->ca_euro * $pourcentageRemise / 100;
                    }
                    $objectif->{"pourcentage_remise_clinique"} = $pourcentageRemise;
                } else 
                {
                    $valeurRemise = $caClinique->ca_euro * $objectif->pourcentage_remise_moyen / 100;
                    $objectif->{"pourcentage_remise_clinique"} = $objectif->pourcentage_remise_moyen;
                }
            }
            $feuille->setCellValue('E' . $cptObjValides, round($valeurCA, 2));
            $feuille->getStyle('E' . $cptObjValides)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $feuille->getStyle('E' . $cptObjValides)->getNumberFormat()->setFormatCode('# ### ##0.00');
            if ($admin)
            {
                $feuille->setCellValue('F' . $cptObjValides, round($objectif->pourcentage_remise_moyen, 2));
            } else 
            {
                $feuille->setCellValue('F' . $cptObjValides, round($objectif->pourcentage_remise_clinique, 2));
            }
            $feuille->getStyle('F' . $cptObjValides)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $feuille->getStyle('F' . $cptObjValides)->getNumberFormat()->setFormatCode('# ##0.00_-"%"');
            $feuille->setCellValue('G' . $cptObjValides, round($valeurRemise,2));
            $feuille->getStyle('G' . $cptObjValides)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $feuille->getStyle('G' . $cptObjValides)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_CURRENCY_EUR_SIMPLE);
            $feuille->setCellValue('H' . $cptObjValides, $objectif->especes);
            $feuille->getStyle('H' . $cptObjValides)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            $feuille->getStyle('H' . $cptObjValides)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
            $feuille->getStyle('H' . $cptObjValides)->getAlignment()->setWrapText(true);
        }

        /*******************************
         * Lignes du tableau / produit *
         *******************************/

        $cptProduit = 3;
        if ($produitsLaboratoire == null)
        {
            $produitsLaboratoire = [];
        }
        foreach ($produitsLaboratoire as $produit) 
        {
            $cptProduit++;
            $feuille->getRowDimension($cptProduit)->setRowHeight(30);
            
            // Estimation des remises
            $feuille->getStyle('L' . $cptProduit . ':R' . $cptProduit)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FFFFFF');
            $feuille->getStyle('L' . $cptProduit . ':R' . $cptProduit)->getBorders()->getTop()->setBorderStyle(Border::BORDER_THICK);
            $feuille->getStyle('L' . $cptProduit . ':R' . $cptProduit)->getBorders()->getTop()->getColor()->setRGB('E8E8E8');
            $feuille->setCellValue('M' . $cptProduit, $produit->denomination);
            $feuille->getStyle('M' . $cptProduit)->getFont()->setSize(8);
            $feuille->getStyle('M' . $cptProduit)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            $feuille->getStyle('M' . $cptProduit)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
            $feuille->getStyle('M' . $cptProduit)->getAlignment()->setWrapText(true);
            $feuille->setCellValue('N' . $cptProduit, $produit->conditionnement);
            $feuille->getStyle('N' . $cptProduit)->getFont()->setSize(8);
            $feuille->getStyle('N' . $cptProduit)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            $feuille->getStyle('N' . $cptProduit)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
            $feuille->getStyle('N' . $cptProduit)->getAlignment()->setWrapText(true);
            $feuille->setCellValue('O' . $cptProduit, round($produit->ca_periode, 2));
            $feuille->getStyle('O' . $cptProduit)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_CURRENCY_EUR_SIMPLE);
            $feuille->getStyle('O' . $cptProduit)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            if ((($produit->type_objectif_id == 2) && $produit->incrementiel) || ($produit->type_objectif_id == 3)) {
                // Recherche du pourcentage moyen de l'objectif dans le cas d'un palier incrémentiel ou d'un individuel
                $objProduitId = $produit->objectif_id;
                $objProduit = $objectifsAtteints->filter(function ($o) use (&$objProduitId) {
                    if ($o->id == $objProduitId)
                        return $o;
                } );
                $objProduit = $objProduit->values()->first();
                if ($admin)
                {
                    $feuille->setCellValue('P' . $cptProduit, round($objProduit->pourcentage_remise_moyen, 2));
                } else
                {
                    $feuille->setCellValue('P' . $cptProduit, round($objProduit->pourcentage_remise_clinique, 2));
                }
            } else 
            {
                $feuille->setCellValue('P' . $cptProduit, round($produit->pourcentage_remise, 2));
            }
            $feuille->getStyle('P' . $cptProduit)->getNumberFormat()->setFormatCode('# ##0.00_-"%"');
            $feuille->getStyle('P' . $cptProduit)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $commentPourcentageRemise = $feuille->getComment('P' . $cptProduit)->getText()->createTextRun('Objectif rattaché :');
            $commentPourcentageRemise->getFont()->setBold(true);
            $feuille->getComment('P' . $cptProduit)->getText()->createTextRun("\r\n");
            $feuille->getComment('P' . $cptProduit)->getText()->createTextRun($produit->objectif);
            $feuille->setCellValue('Q' . $cptProduit, '=O' . $cptProduit . '*P' . $cptProduit . '/100');
            $feuille->getStyle('Q' . $cptProduit)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_CURRENCY_EUR_SIMPLE);
            $feuille->getStyle('Q' . $cptProduit)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        }

        /*************************************************
         * Lignes du tableau / objectif suivi non validé *
         *************************************************/

        $cptObjNonValides = 3;
        if (!$detail)
        {
                if ($objectifsNonAtteints == null)
            {
                $objectifsNonAtteints = [];

            }
            foreach ($objectifsNonAtteints as $objectif)
            {
                $cptObjNonValides++;
                $feuille->getRowDimension($cptObjNonValides)->setRowHeight(30);
                $feuille->getStyle('U' . $cptObjNonValides . ':AB' . $cptObjNonValides)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FFFFFF');
                $feuille->getStyle('U' . $cptObjNonValides . ':AB' . $cptObjNonValides)->getBorders()->getTop()->setBorderStyle(Border::BORDER_THICK);
                $feuille->getStyle('U' . $cptObjNonValides . ':AB' . $cptObjNonValides)->getBorders()->getTop()->getColor()->setRGB('E8E8E8');
                $feuille->setCellValue('V' . $cptObjNonValides, $objectif->nom);
                $feuille->getStyle('V' . $cptObjNonValides)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                $feuille->getStyle('V' . $cptObjNonValides)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
                $feuille->getStyle('V' . $cptObjNonValides)->getAlignment()->setWrapText(true);
                if ($admin)
                {
                    $feuille->setCellValue('W' . $cptObjNonValides, $objectif->valeur);
                }
                $feuille->getStyle('W' . $cptObjNonValides)->getNumberFormat()->setFormatCode('# ### ##0.00');
                $feuille->setCellValue('X' . $cptObjNonValides, round($objectif->ca_unite, 2));
                $feuille->getStyle('X' . $cptObjNonValides)->getNumberFormat()->setFormatCode('# ### ##0.00');
                $feuille->setCellValue('Y' . $cptObjNonValides, '=100-X' . $cptObjNonValides . '*100/W' . $cptObjNonValides);
                $feuille->setCellValue('Z' . $cptObjNonValides, $objectif->cat_nom);
                $feuille->getStyle('Z' . $cptObjNonValides)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                $feuille->getStyle('Z' . $cptObjNonValides)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
                $feuille->getStyle('Z' . $cptObjNonValides)->getAlignment()->setWrapText(true);
                $feuille->setCellValue('AA' . $cptObjNonValides, $objectif->especes);
                $feuille->getStyle('AA' . $cptObjNonValides)->getAlignment()->setWrapText(true);
                $feuille->getStyle('AA' . $cptObjNonValides)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                $feuille->getStyle('AA' . $cptObjNonValides)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
            }
        }

        /********************
         * Total du tableau *
         ********************/

        $feuille->getRowDimension($cptProduit+1)->setRowHeight(26);
        
        // Estimation des remises / objectif suivi validé
        $feuille->getStyle('B' . ($cptObjValides+1) . ':I' . ($cptObjValides+1))->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FFFFFF');
        $feuille->getStyle('B' . ($cptObjValides+1) . ':I' . ($cptObjValides+1))->getBorders()->getTop()->setBorderStyle(Border::BORDER_MEDIUM);
        $feuille->getStyle('B' . ($cptObjValides+1) . ':I' . ($cptObjValides+1))->getBorders()->getTop()->getColor()->setRGB('01B4BC');
        $feuille->getStyle('B' . ($cptObjValides+1) . ':I' . ($cptObjValides+1))->getBorders()->getBottom()->setBorderStyle(Border::BORDER_MEDIUM);
        $feuille->getStyle('B' . ($cptObjValides+1) . ':I' . ($cptObjValides+1))->getBorders()->getBottom()->getColor()->setRGB('01B4BC');
        $feuille->getStyle('B' . ($cptObjValides+1) . ':I' . ($cptObjValides+1))->getBorders()->getLeft()->setBorderStyle(Border::BORDER_MEDIUM);
        $feuille->getStyle('B' . ($cptObjValides+1) . ':I' . ($cptObjValides+1))->getBorders()->getLeft()->getColor()->setRGB('01B4BC');
        $feuille->getStyle('B' . ($cptObjValides+1) . ':I' . ($cptObjValides+1))->getBorders()->getRight()->setBorderStyle(Border::BORDER_MEDIUM);
        $feuille->getStyle('B' . ($cptObjValides+1) . ':I' . ($cptObjValides+1))->getBorders()->getRight()->getColor()->setRGB('01B4BC');
        $feuille->setCellValue('C' . ($cptObjValides+1), 'Total');
        $feuille->setCellValue('D' . ($cptObjValides+1), ($cptObjValides-3) . ' objectifs');
        $laboratoire->{"nbValides"} = $cptObjValides-3;
        $feuille->setCellValue('G' . ($cptObjValides+1), '=SUM(G4:G' . $cptObjValides . ')');
        $feuille->getStyle('G' . ($cptObjValides+1))->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_CURRENCY_EUR_SIMPLE);
        $feuille->getStyle('G' . ($cptObjValides+1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $laboratoire->{"remise"} = "='" . str_replace("'", "''", $laboratoire->nom) . "'!G" . ($cptObjValides+1);

        // Estimation des remises / produit
        $feuille->getStyle('L' . ($cptProduit+1) . ':R' . ($cptProduit+1))->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FFFFFF');
        $feuille->getStyle('L' . ($cptProduit+1) . ':R' . ($cptProduit+1))->getBorders()->getTop()->setBorderStyle(Border::BORDER_MEDIUM);
        $feuille->getStyle('L' . ($cptProduit+1) . ':R' . ($cptProduit+1))->getBorders()->getTop()->getColor()->setRGB('01B4BC');
        $feuille->getStyle('L' . ($cptProduit+1) . ':R' . ($cptProduit+1))->getBorders()->getBottom()->setBorderStyle(Border::BORDER_MEDIUM);
        $feuille->getStyle('L' . ($cptProduit+1) . ':R' . ($cptProduit+1))->getBorders()->getBottom()->getColor()->setRGB('01B4BC');
        $feuille->getStyle('L' . ($cptProduit+1) . ':R' . ($cptProduit+1))->getBorders()->getLeft()->setBorderStyle(Border::BORDER_MEDIUM);
        $feuille->getStyle('L' . ($cptProduit+1) . ':R' . ($cptProduit+1))->getBorders()->getLeft()->getColor()->setRGB('01B4BC');
        $feuille->getStyle('L' . ($cptProduit+1) . ':R' . ($cptProduit+1))->getBorders()->getRight()->setBorderStyle(Border::BORDER_MEDIUM);
        $feuille->getStyle('L' . ($cptProduit+1) . ':R' . ($cptProduit+1))->getBorders()->getRight()->getColor()->setRGB('01B4BC');
        $feuille->setCellValue('M' . ($cptProduit+1), 'Total');
        $feuille->setCellValue('Q' . ($cptProduit+1), '=SUM(Q4:Q' . $cptProduit . ')');
        $feuille->getStyle('Q' . ($cptProduit+1))->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_CURRENCY_EUR_SIMPLE);
        $feuille->getStyle('Q' . ($cptProduit+1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            
        // Objectif suivi non validé
        if (!$detail)
        {
            $feuille->getStyle('U' . ($cptObjNonValides+1) . ':AB' . ($cptObjNonValides+1))->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FFFFFF');
            $feuille->getStyle('U' . ($cptObjNonValides+1) . ':AB' . ($cptObjNonValides+1))->getBorders()->getTop()->setBorderStyle(Border::BORDER_MEDIUM);
            $feuille->getStyle('U' . ($cptObjNonValides+1) . ':AB' . ($cptObjNonValides+1))->getBorders()->getTop()->getColor()->setRGB('01B4BC');
            $feuille->getStyle('U' . ($cptObjNonValides+1) . ':AB' . ($cptObjNonValides+1))->getBorders()->getBottom()->setBorderStyle(Border::BORDER_MEDIUM);
            $feuille->getStyle('U' . ($cptObjNonValides+1) . ':AB' . ($cptObjNonValides+1))->getBorders()->getBottom()->getColor()->setRGB('01B4BC');
            $feuille->getStyle('U' . ($cptObjNonValides+1) . ':AB' . ($cptObjNonValides+1))->getBorders()->getLeft()->setBorderStyle(Border::BORDER_MEDIUM);
            $feuille->getStyle('U' . ($cptObjNonValides+1) . ':AB' . ($cptObjNonValides+1))->getBorders()->getLeft()->getColor()->setRGB('01B4BC');
            $feuille->getStyle('U' . ($cptObjNonValides+1) . ':AB' . ($cptObjNonValides+1))->getBorders()->getRight()->setBorderStyle(Border::BORDER_MEDIUM);
            $feuille->getStyle('U' . ($cptObjNonValides+1) . ':AB' . ($cptObjNonValides+1))->getBorders()->getRight()->getColor()->setRGB('01B4BC');
            $feuille->setCellValue('V' . ($cptObjNonValides+1), 'Total');
            $feuille->setCellValue('W' . ($cptObjNonValides+1), ($cptObjNonValides-3) . ' objectifs');
            $laboratoire->{"nbNonValides"} = $cptObjNonValides-3;
        }

        /*******************
         * Pied du tableau *
         *******************/

        $feuille->getRowDimension($cptProduit+2)->setRowHeight(26);
        
        // Estimation des remises / objectif suivi validé
        $feuille->getStyle('B' . ($cptObjValides+2) . ':I' . ($cptObjValides+2))->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('E8E8E8');
        $feuille->setCellValue('C' . ($cptObjValides+2), '* Les calculs de remises ne prennent pas en compte les conditions particulières à l\'échelle de la clinique');
        $feuille->getStyle('C' . ($cptObjValides+2))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        $feuille->getStyle('C' . ($cptObjValides+2))->getFont()->setSize(8);

        // Estimation des remises / produit
        $feuille->getStyle('L' . ($cptProduit+2) . ':R' . ($cptProduit+2))->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('E8E8E8');
        $feuille->setCellValue('M' . ($cptProduit+2), '* Les calculs de remises ne prennent pas en compte les conditions particulières à l\'échelle de la clinique');
        $feuille->getStyle('M' . ($cptProduit+2))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        $feuille->getStyle('M' . ($cptProduit+2))->getFont()->setSize(8);

        // Objectifs suivis non validés
        if (!$detail)
        {
            $feuille->getStyle('U' . ($cptObjNonValides+2) . ':AB' . ($cptObjNonValides+2))->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('E8E8E8');
            $feuille->setCellValue('V' . ($cptObjNonValides+2), '* Les calculs de remises ne prennent pas en compte les conditions particulières à l\'échelle de la clinique');
            $feuille->getStyle('V' . ($cptObjNonValides+2))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            $feuille->getStyle('V' . ($cptObjNonValides+2))->getFont()->setSize(8);
        }

        /**********************
         * Filtres du tableau *
         **********************/

        if (!$detail)
        {
            $feuille->setAutoFilter('C3:AA' . max($cptProduit, $cptObjValides, $cptObjNonValides));
        } else
        {
            $feuille->setAutoFilter('C3:Q' . max($cptProduit, $cptObjValides, $cptObjNonValides));
        }

        /**********************
         * Contour du tableau *
         **********************/

        $feuille->getRowDimension(max($cptProduit+3, $cptObjValides+3, $cptObjNonValides+3))->setRowHeight(5);
        $feuille->getStyle('A1:A' . ($cptObjValides+3))->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('212629');
        $feuille->getStyle('J1:J' . ($cptObjValides+3))->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('212629');
        $feuille->getStyle('K1:K' . ($cptProduit+3))->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('212629');
        $feuille->getStyle('S1:S' . ($cptProduit+3))->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('212629');
        $feuille->getStyle('A' . ($cptObjValides+3) . ':J' . ($cptObjValides+3))->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('212629');
        $feuille->getStyle('K' . ($cptProduit+3) . ':S' . ($cptProduit+3))->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('212629');
        $feuille->setBreak('J' . ($cptObjValides+3), Worksheet::BREAK_COLUMN);
        $feuille->setBreak('S' . ($cptProduit+3), Worksheet::BREAK_COLUMN);
        if (!$detail)
        {
            $feuille->getStyle('T1:T' . ($cptObjNonValides+3))->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('212629');
            $feuille->getStyle('AC1:AC' . ($cptObjNonValides+3))->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('212629');
            $feuille->getStyle('T' . ($cptObjNonValides+3) . ':AC' . ($cptObjNonValides+3))->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('212629');
            $feuille->getPageSetup()->setPrintArea('A1:J' . ($cptObjValides+3) . ',K1:S' . ($cptProduit+3) . ',T1:AC' . ($cptObjNonValides+3));
        } else
        {
            $feuille->getPageSetup()->setPrintArea('A1:J' . ($cptObjValides+3) . ',K1:S' . ($cptProduit+3));
        } 
    }

    private function calculRemisePalierIncrementiel($remise, $objectif, $caClinique, $objectifRepository)
    {
        if ($objectif->type_objectif_id == 2)
        {
            if ($objectif->objectif_precedent_id != null)
            {
                $objectifPrecedent = Objectif::where('id', $objectif->objectif_precedent_id)->first();
                if ($objectifPrecedent->incrementiel)
                {
                    $remisePrec = ($objectif->valeur - $objectifPrecedent->valeur)*$objectifPrecedent->pourcentage_remise/100;
                } else
                {
                    $remisePrec = $objectif->valeur*$objectifPrecedent->pourcentage_remise/100;
                }
                return $this->calculRemisePalierIncrementiel($remise+$remisePrec, $objectifPrecedent, null, $objectifRepository);
            } else
            {
                return $remise;
            }
        } else if ($objectif->type_objectif_id == 3)
        {
            // Recherche des paliers de l'objectif individuel
            $levels = $objectifRepository->findListLevelsById($objectif->id);
            $levels->sortBy('valeur', 'desc');

            $caTmp = $caClinique;
            foreach($levels as $level)
            {
                if ($caClinique >= $level->valeur)
                {
                    $caTmp = $caTmp - $level->valeur;
                    $remise += $caTmp * $level->pourcentage_remise / 100;
                }
            }

            return $remise;
        }
    }

    private function calculRemisePalierIndividuel($caClinique, $objectif, $objectifRepository)
    {
        // Recherche des paliers de l'objectif individuel
        $levels = $objectifRepository->findListLevelsById($objectif->id);
        $remise = 0;

        foreach($levels as $level)
        {
            if ($caClinique >= $level->valeur)
            {
                $remise = $level->pourcentage_remise;
            } else 
            {
                break;
            }
        }

        return $remise;
    }

}
