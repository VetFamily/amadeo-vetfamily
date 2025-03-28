<!doctype html>
<html lang="{{ app()->getLocale() }}">
	<head>
		<meta charset="utf-8">
		<meta http-equiv="X-UA-Compatible" content="IE=edge">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		
		<title>@lang('amadeo.purchases.title')</title>

		<!-- Font -->
		
		<!-- Styles -->
		{{ Html::style('https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.0.3/css/font-awesome.min.css') }}
		{{ Html::style('https://cdn.datatables.net/1.10.16/css/jquery.dataTables.min.css') }}
		{{ Html::style(asset('css/commun/button.css')) }}
		{{ Html::style(asset('css/commun/commun.css')) }}
		{{ Html::style(asset('css/commun/form.css')) }}
		{{ Html::style(asset('css/commun/header.css')) }}
		{{ Html::style(asset('css/commun/modal.css')) }}
		{{ Html::style(asset('css/commun/onglet.css')) }}
		{{ Html::style(asset('css/commun/table.css')) }}
		{{ Html::style(asset('css/commun/bootoast.css')) }}
		{{ Html::style(asset('css/parametrage/parametrage.css')) }}
		
		<!-- JS Script -->
		{{ Html::script('https://cdnjs.cloudflare.com/ajax/libs/jquery/3.2.1/jquery.min.js') }}
		{{ Html::script('https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.js') }}
		{{ Html::script('https://cdn.datatables.net/1.10.16/js/jquery.dataTables.min.js') }}
		{{ Html::script('https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js') }}
		{{ Html::script(asset('js/commun.js')) }}
		{{ Html::script(asset('js/header.js')) }}
		{{ Html::script(asset('js/toast.js')) }}
		{{ Html::script(asset('js/jquery.dropdown.js')) }}
		<script type="text/javascript">
			var listeProduitsIdsSelect = [];
		</script>
		@include('parametrage/parametrage_js')
		@include('statistiques/detail-chiffres_js')

		<script type="text/javascript">
			
			/* Chargement de la page */
			addEvent(window, "load", loadClinicScope);
			addEvent(window, "load", loadProductScope);
      		addEvent(window, "load", loadDisplayTypes);
      		addEvent(window, "load", loadListOfCentralPurchasing);
      		addEvent(window, "load", loadListOfValorizations);
			addEvent(window, "load", loadSortNumericComma);
			addEvent(window, "load", loadListOfPurchases);
			addEvent(window, "load", saveCountOfAllClinics);
			addEvent(window, "load", saveCountOfAllProducts);

		</script>
	</head>
	<body>
		@include('commun/header')

		{!! Form::open(['class' => 'ajax-form-stats']) !!}
			<div class="zone_title">
				<div class="title">@lang('amadeo.purchases.title')</div>
				<div id="buttonsGeneral" class="buttonsGeneral">
					<div title="Last purchases loaded">
						@if (null != Session::get('last_date'))
						{{ ucwords(strftime("%B %Y", DateTime::createFromFormat('Y-m-d', Session::get('last_date'))->getTimestamp())) }}
						@endif
					</div>
					@if ((sizeof(Auth::user()->roles) >0) AND ("Vétérinaire" != Auth::user()->roles[0]['nom']))
						<div id="downloadPurchasesButton" class="button"><a>@lang('amadeo.purchases.download-purchases')</a><span class="btn_download"></span></div>
						<div id="downloadPurchasesByParamsButton" class="button" download="false"><a>@lang('amadeo.purchases.download-purchases-filtered')</a><span class="btn_download"></span></div>
						<div class="button" onclick="location.href='{{ config('app.url_v2') }}'"><a>Load purchases</a><span class="btn_add"></span></div>
					@endif
				</div>
			</div>

			<div class="layout_withParams">
				<!-- Paramétrage -->
				@include('parametrage/parametrage')

				<!-- Détail des chiffres -->
				<div id="main" class="layout_withParams_right detail-chiffres form-style">
					
					<!-- Ouverture du menu -->
					<img src="/images/MENU_TURQUOISE.svg" title="@lang('amadeo.purchases.parameters-show')" alt="@lang('amadeo.purchases.parameters-show')" class="open_params_menu" onclick="openNav()">
					
					<div class="main_tableau">
						<div id="load" class="load" style="display: none;">
							{{ Html::image('images/activity_indicator.gif', \Lang::get('amadeo.load')) }}
						</div>

						<div class='zone-tableau-withoutOnglets'>
							<div id="tableau" class="tableau" style='display:none'>
								<table id='tab-purchases' class='' cellspacing='0' width='100%'>
								</table>
							</div>
						</div>
					</div>
				</div>

				<!-- Clinics scope -->
				@include('parametrage/parametrage_cliniques')

				<!-- Products scope -->
				@include('parametrage/parametrage_produits')

				<div id="downloadPurchasesModal" class="modal">
					<div class="modal-dialog" role="document">
						<div class="modal-content">
						    <div class="modal-header">
								<button type="button" class="bootpopup-button close" data-dismiss="modal" aria-label="@lang('amadeo.close')"><img src="/images/CROIX_FERMETURE_BLANC.svg" height="32px" width="32px"></button>
								<h4 class="modal-title">@lang('amadeo.purchases.download-purchases')</h4>
						    </div>
						    <div class="modal-body flex-column">
						    	<div id="divPurchasesYear" class="form-group">
									<label class="label_text">@lang('amadeo.year') 
										<div class="flex-center text-center">
											{{ Form::selectMonth('downloadPurchases-startMonth', 1, ['id' => 'downloadPurchases-startMonth', 'style' => 'text-transform: capitalize;flex:2;']) }} {{ Form::selectYear('downloadPurchases-startYear', date('Y'), 2018, date('Y'), ['id' => 'downloadPurchases-startYear', 'style' => 'flex:1;']) }} <div style="flex:1;font-size: 10px;color: #000;">@lang('amadeo.purchases.criteria-period-end')</div> {{ Form::selectMonth('downloadPurchases-endMonth', 1, ['id' => 'downloadPurchases-endMonth', 'style' => 'text-transform: capitalize;flex:2;']) }} {{ Form::selectYear('downloadPurchases-endYear', date('Y'), 2018, date('Y'), ['id' => 'downloadPurchases-endYear', 'style' => 'flex:1;']) }}</label>
										</div>
					            </div>
					            <div id="divPurchasesCountry" class="form-group">
					                <label class="label_text">@lang('amadeo.purchases.country') <select id="downloadPurchasesCountry" autofocus><option value="">Sélectionner...</option></select></label>
					            </div>
					            <div id="divPurchasesSource" class="form-group">
					                <label class="label_text">@lang('amadeo.purchases.source') <select id="downloadPurchasesSource" autofocus><option value="">Sélectionner...</option></select></label>
					            </div>
					            <div id="divPurchasesSupplier" class="form-group">
					                <label class="label_text">@lang('amadeo.purchases.supplier') <select id="downloadPurchasesSupplier" autofocus><option value="">Sélectionner...</option></select></label>
					            </div>
						    </div>
						    <div class="modal-footer">
						    	<div class="confirm-buttons-modal">
						    		<div id="launchButtonDownloadPurchases" class="button"><a>@lang('amadeo.download')</a><span class="btn_download_black"></span></div>
						    	</div>
						    </div>
						</div>
					</div>
				</div>

			</div>
		{!! Form::close() !!}
	</body>
</html>