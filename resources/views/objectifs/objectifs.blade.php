<!doctype html>
<html lang="{{ app()->getLocale() }}">
	<head>
		<meta charset="utf-8">
		<meta http-equiv="X-UA-Compatible" content="IE=edge">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		
		<title>@lang('amadeo.targets.title')</title>

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

		<!-- JS Script -->
		{{ Html::script('https://cdnjs.cloudflare.com/ajax/libs/jquery/3.2.1/jquery.min.js') }}
		{{ Html::script('https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.js') }}
		{{ Html::script('https://cdn.datatables.net/1.10.16/js/jquery.dataTables.min.js') }}
		{{ Html::script('https://cdn.datatables.net/plug-ins/1.10.16/dataRender/ellipsis.js') }}
		{{ Html::script('https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js') }}
		{{ Html::script(asset('js/commun.js')) }}
		{{ Html::script(asset('js/header.js')) }}
		{{ Html::script(asset('js/toast.js')) }}
		@include('objectifs/objectifs_js')

		<script type="text/javascript">
			addEvent(window, "load", loadSortNumericComma);
			addEvent(window, "load", loadAjaxFormObjectifs);
		</script>
	</head>
	<body>
		@include('commun/header')

		{!! Form::open(['id' => 'ajax-form-objectifs']) !!}
			<!-- Gestion des objectifs -->
			<div class="layout_withoutParams form-style">
				<div class="zone_title">
					<div class="title">@lang('amadeo.targets.title')</div>
					@if ((sizeof(Auth::user()->roles) >0) AND ("Administrateur" == Auth::user()->roles[0]['nom']))
					<div id="buttonsGeneral" class="buttonsGeneral">
						<div id="addButtonGeneral" class="button"><a>@lang('amadeo.add')</a><span class="btn_add"></span></div>
						<div id="copyButtonGeneral" class="button"><a>@lang('amadeo.copy')</a><span class="btn_add"></span></div>
						<div id="deleteButtonGeneral" class="button"><a>@lang('amadeo.delete')</a><span class="btn_delete"></span></div>
					</div>
					@endif
				</div>

				<div class="zone-tableau-withoutOnglets">
					<div id="load" class="load">
						{{ Html::image('images/activity_indicator.gif', \Lang::get('amadeo.load')) }}
					</div>
					<div id="tableau" class="tableau" style='display:none'>
						<table id='tab-objectifs' class='' cellspacing='0' width='100%'>
							<thead>
								<tr>
									<th></th>
									<th>@lang('amadeo.categories.country')</th>
									<th>@lang('amadeo.categories.year')</th>
									<th>@lang('amadeo.categories.specie')</th>
									<th>@lang('amadeo.categories.seller')</th>
									<th>@lang('amadeo.categories.name')</th>
									<th>@lang('amadeo.targets.name')</th>
									<th>@lang('amadeo.targets.value')</th>
									<th>@lang('amadeo.targets.rebate-total')</th>
									<th>@lang('amadeo.targets.difference')</th>
									<th>@lang('amadeo.targets.difference')</th>
									<th>@lang('amadeo.targets.advancement-short')<br><span>@lang('amadeo.targets.year') N</span></th>
									<th>@lang('amadeo.targets.advancement-short')<br><span>@lang('amadeo.targets.year') N-1</span></th>
									<th>@lang('amadeo.targets.evolution-short')</th>
								</tr>
								<tr id='forFilters'>
									<th class='star-filter'></th>
									<th class='select-filter'></th>
									<th class='select-filter'></th>
									<th class='text-filter'></th>
									<th class='select-filter'></th>
									<th class='text-filter'></th>
									<th class='text-filter'></th>
									<th></th>
									<th></th>
									<th></th>
									<th></th>
									<th></th>
									<th></th>
									<th></th>
								</tr>
							</thead>
						</table>
					</div>
				</div>

				<div id="addObjectifModal" class="modal">
					<div class="modal-dialog" role="document">
						<div class="modal-content">
						    <div class="modal-header">
								<button type="button" class="bootpopup-button close" data-dismiss="modal" aria-label="@lang('amadeo.close')"><img src="/images/CROIX_FERMETURE_BLANC.svg" height="32px" width="32px"></button>
								<h4 class="modal-title">@lang('amadeo.targets.add.title')</h4>
						    </div>
						    <div class="modal-body flex-column">
						    	<div id="divCountryObjectif" class="form-group">
					                <label class="label_text">@lang('amadeo.categories.country') <select id="selectObjectifCountry" autofocus><option value="">@lang("amadeo.list.message")</option></select></label>
					            </div>
						    	<div id="divAnneeObjectif" class="form-group">
					                <label class="label_text">@lang('amadeo.categories.year') <input type="text" id="addObjectifAnnee"></label>
					            </div>
						    	<div id="divLaboratoireObjectif" class="form-group">
					                <label class="label_text">@lang('amadeo.categories.seller') <select id="selectObjectifLaboratoire" autofocus><option value="">@lang("amadeo.list.message")</option></select></label>
					            </div>
					            <div id="divCategorieObjectif" class="form-group">
					                <label class="label_text">@lang('amadeo.categories.name') <select id="selectObjectifCategorie" autofocus><option value="">@lang("amadeo.list.message")</option></select></label>
					            </div>
					            <div id="divNomObjectif" class="form-group">
					                <label class="label_text">@lang('amadeo.targets.name') <input type="text" id="addObjectifNom"></label>
					            </div>
						    </div>
						    <div class="modal-footer">
						    	<div class="confirm-buttons-modal">
						    		<div id="saveButtonObjectif" class="button"><a>@lang('amadeo.save')</a><span class="btn_save"></span></div>
						    	</div>
						    </div>
						</div>
					</div>
				</div>

				<div id="copyObjectifModal" class="modal">
					<div class="modal-dialog" role="document">
						<div class="modal-content">
						    <div class="modal-header">
								<button type="button" class="bootpopup-button close" data-dismiss="modal" aria-label="@lang('amadeo.close')"><img src="/images/CROIX_FERMETURE_BLANC.svg" height="32px" width="32px"></button>
								<h4 class="modal-title">@lang('amadeo.targets.copy.title')</h4>
						    </div>
						    <div class="modal-body flex-column">
						    	<div id="divCountryObjectif" class="form-group">
					                <label class="label_text">@lang('amadeo.categories.country') <p id="copyObjectifCountry"></label>
					            </div>
						    	<div id="divAnneeObjectif" class="form-group">
					                <label class="label_text">@lang('amadeo.categories.year') <p id="copyObjectifAnnee"></label>
					            </div>
					            <div id="divLaboratoireObjectif" class="form-group">
					                <label class="label_text">@lang('amadeo.categories.seller') <p id="copyObjectifLaboratoire"></p></label>
					            </div>
					            <div id="divCategorieObjectif" class="form-group">
					                <label class="label_text">@lang('amadeo.categories.name') <p id="copyObjectifCategorie"></p></label>
					            </div>
					            <div id="divAncienNomObjectif" class="form-group">
					                <label class="label_text">@lang('amadeo.targets.copy.name') <p id="copyObjectifAncienNom"></p></label>
					            </div>
					            <div id="divNomObjectif" class="form-group">
					                <label class="label_text">@lang('amadeo.targets.name') <input type="text" id="copyObjectifNom" autofocus></label>
					            </div>
						    </div>
				            <div id="divProduitsObjectif" class="modal-body modal-body-table" style="border-top: 1px solid #E8E8E8;">
				            	<table id='tab-copy-objectif-produits' class='' cellspacing='0' width='100%'>
				            		<thead>
				            			<tr>
				            				<th>@lang('amadeo.products.name')</th>
				            				<th>@lang('amadeo.products.packaging')</th>
				            				<th>@lang('amadeo.products.gtin')</th></tr>
				            		</thead>
				            	</table>
				            </div>
						    <div class="modal-footer">
						    	<div class="confirm-buttons-modal">
						    		<div id="saveCopyButtonObjectif" class="button"><a>@lang('amadeo.save')</a><span class="btn_save"></span></div>
						    	</div>
						    </div>
						</div>
					</div>
				</div>
			</div>
		{!! Form::close() !!}
	</body>
</html>