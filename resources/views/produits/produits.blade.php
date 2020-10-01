<!doctype html>
<html lang="{{ app()->getLocale() }}">
	<head>
		<meta charset="utf-8">
		<meta http-equiv="X-UA-Compatible" content="IE=edge">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		
		<title>@lang('amadeo.products.title')</title>

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
		@include('produits/produits_js')

		<script type="text/javascript">
			addEvent(window, "load", loadSortNumericComma);
			addEvent(window, "load", loadAjaxFormProduits);
		</script>
	</head>
	<body>
		@include('commun/header')

		{!! Form::open(['id' => 'ajax-form-produits']) !!}
			<!-- Gestion des produits -->
			<div class="layout_withoutParams form-style">
				<div class="zone_title">
					<div class="title">@lang('amadeo.products.title')</div>
				</div>

				<div class="zone-tableau-withoutOnglets">
					<div id="load" class="load">
						{{ Html::image('images/activity_indicator.gif', \Lang::get('amadeo.load')) }}
					</div>
					<div id="tableau" class="tableau" style='display:none'>
						<table id='tab-produits' class='' cellspacing='0' width='100%'>
							<thead>
								<tr>
									<th></th>
									<th>@lang('amadeo.products.countries')</th>
									<th>@lang('amadeo.products.seller')</th>
									<th>@lang('amadeo.products.name')</th>
									<th>@lang('amadeo.products.packaging')</th>
									<th>@lang('amadeo.products.gtin')</th>
									<th class="nombre">@lang('amadeo.products.valorization-euro')</th>
									<th class="nombre">@lang('amadeo.products.valorization-volume')</th>
								</tr>
								<tr id='forFilters'>
									<th></th>
									<th class='text-filter'></th>
									<th class='select-filter'></th>
									<th class='text-filter'></th>
									<th class='text-filter'></th>
									<th class='text-filter'></th>
									<th></th>
									<th></th>
								</tr>
							</thead>
						</table>
					</div>
				</div>
			</div>
		{!! Form::close() !!}
	</body>
</html>