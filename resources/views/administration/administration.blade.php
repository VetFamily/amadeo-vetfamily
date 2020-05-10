<!doctype html>
<html lang="{{ app()->getLocale() }}">
	<head>
		<meta charset="utf-8">
		<meta http-equiv="X-UA-Compatible" content="IE=edge">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		
		<title>Administration</title>

		<!-- Font -->
		
		<!-- Styles -->
		{{ Html::style('https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.12.1/jquery-ui.css') }}
		{{ Html::style('https://cdn.datatables.net/1.10.16/css/jquery.dataTables.min.css') }}
		{{ Html::style(asset('css/commun/button.css')) }}
		{{ Html::style(asset('css/commun/commun.css')) }}
		{{ Html::style(asset('css/commun/form.css')) }}
		{{ Html::style(asset('css/commun/header.css')) }}
		{{ Html::style(asset('css/commun/modal.css')) }}
		{{ Html::style(asset('css/commun/table.css')) }}
		{{ Html::style(asset('css/commun/bootoast.css')) }}
		{{ Html::style(asset('css/administration/administration.css')) }}

		<!-- JS Script -->
		{{ Html::script('https://cdnjs.cloudflare.com/ajax/libs/jquery/3.2.1/jquery.min.js') }}
		{{ Html::script('https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.js') }}
		{{ Html::script('https://cdn.datatables.net/1.10.16/js/jquery.dataTables.min.js') }}
		{{ Html::script('https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js') }}
		{{ Html::script(asset('js/header.js')) }}
		{{ Html::script(asset('js/commun.js')) }}
		{{ Html::script(asset('js/toast.js')) }}
		{{ Html::script(asset('js/jquery.dropdown.js')) }}
		@include('administration/administration_js')

		<script type="text/javascript">
			addEvent(window, "load", loadListCliniques);
			addEvent(window, "load", loadExportEstimationRFA);
			addEvent(window, "load", loadExportBilanRFA);
			addEvent(window, "load", loadExportExtractionPrixNets);
		</script>
	</head>
	<body>
		@include('commun/header')

		{!! Form::open(['class' => 'ajax-form-administration']) !!}
			<div class="layout_withoutParams form-style administration">
				<div class="zone_title">
					<div class="title">Module d'administration</div>
				</div>

				@if ((sizeof(Auth::user()->roles) >0) AND ("Administrateur" == Auth::user()->roles[0]['nom']) AND Session::get('user_is_super_admin'))
				<h1>Estimation des RFA</h1>
				<div id="estimationRFA" class="estimationRFA form-style">
					<div class="administration-critere">
						<h2>Période</h2>
						<div>
							de <?php setlocale(LC_ALL, 'fr_FR.UTF-8'); ?>
		        			{{ Form::selectMonth('estimationRFA-periode-mois-debut', 1, ['id' => 'estimationRFA-periode-mois-debut', 'style' => 'text-transform: capitalize; margin-left: 10px; margin-right: 10px;']) }}
		        			{{ Form::selectYear('estimationRFA-periode-annee-debut', date('Y'), 2016, date('Y'), ['id' => 'estimationRFA-periode-annee-debut', 'style' => 'margin-right: 10px;']) }}
							 à 
		        			{{ Form::selectMonth('estimationRFA-periode-mois-fin', date('d') > 15 ? (int)(date('m')) : (int)(date('m')-1), ['id' => 'estimationRFA-periode-mois-fin', 'style' => 'text-transform: capitalize; margin-left: 10px;']) }}
		        			{{ Form::selectYear('estimationRFA-periode-annee-fin', date('Y'), 2016, date('Y'), ['id' => 'estimationRFA-periode-annee-fin', 'style' => 'margin-left: 10px;']) }}
						</div>
			        </div>
					<div class="administration-critere">
						<div class="administration-encart">
				        	<div class="administration-criteres-dropdown">
				        		<h2>Cliniques</h2>
				        		<a><img src="/images/DEPLIER_NOIR.svg" width="15" height="15" alt="Déplier"></a>
				        	</div>
		        			<div class="cliniques" style="width: 80%;">
								<select style="display:none" id="estimationRFA-cliniques[]" name="estimationRFA-cliniques[]"></select>
							</div>
						</div>
			        </div>
					<div class="administration-critere">
						<div class="administration-encart">
				        	<div class="administration-criteres-dropdown">
				        		<h2>Codes cliniques</h2>
				        		<a><img src="/images/DEPLIER_NOIR.svg" width="15" height="15" alt="Déplier"></a>
				        	</div>
		        			<div class="codes_centrale_clinique" style="width: 80%;">
								<select style="display:none" id="estimationRFA-codes_centrale_clinique[]" name="estimationRFA-codes_centrale_clinique[]" multiple></select>
							</div>
						</div>
			        </div>
					<div class="administration-critere">
						<h2>Objectifs pris en compte</h2>
						<div>
							{{ Form::selectYear('estimationRFA-annee-objectif', date('Y'), 2016, date('Y'), ['id' => 'estimationRFA-annee-objectif', 'style' => 'margin-right: 10px;']) }}
						</div>
			        </div>
			        <div class="administration-button">
			        	<div id="generateEstimationRfaButton" class="button"><a>Télécharger</a><span class="btn_download_black"></span></div>
			        </div>
			        <div class="administration-button">
			        	<div id="generateEstimationRfaDetailButton" class="button"><a>Télécharger avec détail</a><span class="btn_download_black"></span></div>
			        </div>
				</div>

				<h1>Bilans des RFA</h1>
				<div id="bilanRFA" class="bilanRFA form-style">
					<div class="administration-critere">
						<h2>Année</h2>
						<div>
							{{ Form::selectYear('bilanRFA-annee', date('Y'), 2016, date('Y')-1, ['id' => 'bilanRFA-annee', 'style' => 'margin-right: 10px;']) }}
						</div>
			        </div>
			        <div class="administration-button">
			        	<div id="generateBilanRfaButton" class="button"><a>Télécharger</a><span class="btn_download_black"></span></div>
			        </div>
			        <div class="administration-button">
			        	<div id="generateBilanRfaDetailButton" class="button"><a>Télécharger avec adhérents</a><span class="btn_download_black"></span></div>
			        </div>
				</div>
				@endif

				<!--<h1>Prix net</h1>
				<div id="extractionPrixNet" class="extractionPrixNet form-style">
					<div class="administration-critere">
						<h2>Année</h2>
						<div>
							{{ Form::selectYear('extractionPrixNet-annee', (date('m') > 8 ? (int)(date('Y')+1) : (int)(date('Y'))), 2016, date('Y'), ['id' => 'extractionPrixNet-annee', 'style' => 'margin-right: 10px;']) }}
						</div>
			        </div>
					<div class="administration-critere">
						<h2>Remise centrale</h2>
						<div>
							<input type="number" id="extractionPrixNet-remise-centrale" step="0.1"/> %
						</div>
			        </div>
			        <div class="administration-button" style="flex: 2;">
			        	<div id="generateExtractionPrixNetsButton" class="button"><a>Télécharger</a><span class="btn_download_black"></span></div>
			        </div>
				</div>-->

				<!--

				<h1>Génération d'une requête</h1>
				<div id="generationRequete" class="administration-action form-style">
					<div class="administration-critere">
						<h2>Période</h2>
						<div>
							de <?php setlocale(LC_ALL, 'fr_FR.UTF-8'); ?>
		        			{{ Form::selectMonth('periode-mois-debut', 1, ['id' => 'periode-mois-debut', 'style' => 'text-transform: capitalize; margin-left: 10px; margin-right: 10px;']) }}
							 à 
		        			{{ Form::selectMonth('periode-mois-fin', str_replace('0', '', (date('d') > 15 ? date('m') : date('m')-1)), ['id' => 'periode-mois-fin', 'style' => 'text-transform: capitalize; margin-left: 10px;']) }}
		        			{{ Form::selectYear('periode-annee-fin', date('Y'), 2016, date('Y'), ['id' => 'periode-annee-fin', 'style' => 'margin-left: 10px;']) }}
						</div>
			        </div>
					<div class="administration-critere">
						<div class="administration-encart">
				        	<div class="administration-criteres-dropdown">
				        		<h2>Cliniques</h2>
				        		<a><img src="/images/DEPLIER_NOIR.svg" width="15" height="15" alt="Déplier"></a>
				        	</div>
		        			<div class="cliniques" style="width: 80%;">
								<select style="display:none" id="cliniques[]" name="cliniques[]" multiple></select>
							</div>
						</div>
			        </div>
					<div class="administration-critere">
						<div class="administration-encart">
				        	<div class="administration-criteres-dropdown">
				        		<h2>Produits</h2>
				        	</div>
				        	<div class="produits">
								<div class="administration-button">
									<div id="select-produits-button" class="button"><a>Sélectionner</a><span class="btn_search"></span></div>
								</div>
								<div class="params-criteres-label">
									Produits sélectionnés : <label class="nb_produits">Tous</label>
								</div>
							</div>
						</div>
		        	</div>
			        <div class="administration-button">
			        	<div id="generateRequeteButton" class="button"><a>Télécharger</a><span class="btn_download_black"></span></div>
			        </div>
				</div>
			-->
			</div>
		{!! Form::close() !!}
	</body>
</html>