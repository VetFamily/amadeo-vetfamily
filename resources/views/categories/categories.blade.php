<!doctype html>
<html lang="{{ app()->getLocale() }}">
	<head>
		<meta charset="utf-8">
		<meta http-equiv="X-UA-Compatible" content="IE=edge">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		
		<title>@lang('amadeo.categories.title')</title>

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
		{{ Html::script('https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js') }}
		{{ Html::script(asset('js/commun.js')) }}
		{{ Html::script(asset('js/header.js')) }}
		{{ Html::script(asset('js/toast.js')) }}
		@include('categories/categories_js')

		<script type="text/javascript">
			addEvent(window, "load", loadSortNumericComma);
			addEvent(window, "load", loadAjaxFormCategories);
		</script>
	</head>
	<body>
		@include('commun/header')

		{!! Form::open(['id' => 'ajax-form-categories']) !!}
			<!-- Gestion des catégories -->
			<div class="layout_withoutParams form-style">
				<div class="zone_title">
					<div class="title">@lang('amadeo.categories.title')</div>
					<div id="buttonsGeneral" class="buttonsGeneral">
						<div id="addButtonGeneral" class="button"><a>@lang("amadeo.add")</a><span class="btn_add"></span></div>
						<div id="copyButtonGeneral" class="button"><a>@lang("amadeo.copy")</a><span class="btn_add"></span></div>
						<div id="deleteButtonGeneral" class="button"><a>@lang("amadeo.delete")</a><span class="btn_delete"></span></div>
					</div>
				</div>

				<div class="zone-tableau-withoutOnglets">
					<div id="load" class="load">
						{{ Html::image('images/activity_indicator.gif', \Lang::get('amadeo.load')) }}
					</div>
					<div id="tableau" class="tableau" style='display:none'>
						<table id='tab-categories' class='' cellspacing='0' width='100%'>
							<thead>
								<tr>
									<th></th>
									<th>@lang('amadeo.categories.year')</th>
									<th>@lang('amadeo.categories.species')</th>
									<th>@lang('amadeo.categories.seller')</th>
									<th>@lang('amadeo.categories.name')</th>
									<th class="nombre">@lang('amadeo.categories.products-nb')</th>
								</tr>
								<tr id='forFilters'>
									<th></th>
									<th class='select-filter'></th>
									<th class='text-filter'></th>
									<th class='select-filter'></th>
									<th class='text-filter'></th>
									<th></th>
								</tr>
							</thead>
						</table>
					</div>
				</div>

				<div id="addCategorieProduitModal" class="modal">
					<div class="modal-dialog" role="document">
						<div class="modal-content">
						    <div class="modal-header">
								<button type="button" class="bootpopup-button close" data-dismiss="modal" aria-label="@lang('amadeo.close')"><img src="/images/CROIX_FERMETURE_BLANC.svg" height="32px" width="32px"></button>
								<h4 class="modal-title">@lang("amadeo.categories.add-product.title")</h4>
						    </div>
						    <div id="divListeProduitsCandidats" class="modal-body modal-body-table flex-column">
									<div id="loadProductsModal" class="load">
										{{ Html::image('images/activity_indicator.gif', \Lang::get('amadeo.load')) }}
									</div>
									<div id="tableProductsModal" class="tableau"></div>
							</div>
						    <div id="divButtonListeProduits" class="modal-footer"></div>
						</div>
					</div>
				</div>

				<div id="addCategorieModal" class="modal">
					<div class="modal-dialog" role="document">
						<div class="modal-content">
						    <div class="modal-header">
								<button type="button" class="bootpopup-button close" data-dismiss="modal" aria-label="@lang('amadeo.close')"><img src="/images/CROIX_FERMETURE_BLANC.svg" height="32px" width="32px"></button>
								<h4 class="modal-title">@lang('amadeo.categories.add.title')</h4>
						    </div>
						    <div class="modal-body flex-column">
						    	<div id="divAnneeCategorie" class="form-group">
					                <label class="label_text">@lang('amadeo.categories.year') <input type="text" id="addCategorieAnnee" autofocus></label>
					            </div>
					            <div id="divLaboratoireCategorie" class="form-group">
					                <label class="label_text">@lang('amadeo.categories.seller') <select id="selectCategorieLaboratoire" autofocus><option value="">@lang('amadeo.list.message')</option></select></label>
					            </div>
					            <div id="divNomCategorie" class="form-group">
					                <label class="label_text">@lang('amadeo.categories.name') <input type="text" id="addCategorieNom"></label>
					            </div>
						    </div>
						    <div class="modal-footer">
						    	<div class="confirm-buttons-modal">
						    		<div id="saveButtonCategorie" class="button"><a>@lang('amadeo.save')</a><span class="btn_save"></span></div>
						    	</div>
						    </div>
						</div>
					</div>
				</div>

				<div id="copyCategorieModal" class="modal">
					<div class="modal-dialog" role="document">
						<div class="modal-content">
						    <div class="modal-header">
								<button type="button" class="bootpopup-button close" data-dismiss="modal" aria-label="@lang('amadeo.close')"><img src="/images/CROIX_FERMETURE_BLANC.svg" height="32px" width="32px"></button>
								<h4 class="modal-title">@lang('amadeo.categories.copy.title')</h4>
						    </div>
						    <div class="modal-body flex-column">
						    	<div id="divAnneeCategorie" class="form-group">
					                <label class="label_text">@lang('amadeo.categories.year') <input type="text" id="copyCategorieAnnee" autofocus></label>
					            </div>
					            <div id="divLaboratoireCategorie" class="form-group">
					                <label class="label_text">@lang('amadeo.categories.seller') <p id="copyCategorieLaboratoire"></p></label>
					            </div>
					            <div id="divAncienNomCategorie" class="form-group">
					                <label class="label_text">@lang('amadeo.categories.copy.name') <p id="copyCategorieAncienNom"></p></label>
					            </div>
					            <div id="divNomCategorie" class="form-group">
					                <label class="label_text">@lang('amadeo.categories.name') <input type="text" id="copyCategorieNom"></label>
					            </div>
						    </div>
				            <div id="divProduitsCategorie" class="modal-body modal-body-table" style="border-top: 1px solid var(--light-grey);">
				            	<table id='tab-copy-categorie-produits' class='' cellspacing='0' width='100%'>
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
						    		<div id="saveCopyButtonCategorie" class="button"><a>@lang('amadeo.save')</a><span class="btn_save"></span></div>
						    	</div>
						    </div>
						</div>
					</div>
				</div>
			</div>
		{!! Form::close() !!}
	</body>
</html>