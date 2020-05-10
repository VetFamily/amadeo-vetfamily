<!doctype html>
<html lang="{{ app()->getLocale() }}">
	<head>
		<meta charset="utf-8">
		<meta http-equiv="X-UA-Compatible" content="IE=edge">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		
		<title>Gestion des engagements</title>

		<!-- Font -->
		
		<!-- Styles -->
		<!-- {{ Html::style('http://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.0.3/css/font-awesome.min.css') }} -->
		{{ Html::style('https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.12.1/jquery-ui.css') }}
		{{ Html::style('https://cdn.datatables.net/1.10.16/css/jquery.dataTables.min.css') }}
		{{ Html::style(asset('css/commun/button.css')) }}
		{{ Html::style(asset('css/commun/commun.css')) }}
		{{ Html::style(asset('css/commun/form.css')) }}
		{{ Html::style(asset('css/commun/header.css')) }}
		{{ Html::style(asset('css/commun/jquery.confirm.css')) }}
		{{ Html::style(asset('css/commun/modal.css')) }}
		{{ Html::style(asset('css/commun/table.css')) }}

		<!-- JS Script -->
		{{ Html::script('https://cdnjs.cloudflare.com/ajax/libs/jquery/3.2.1/jquery.min.js') }}
		{{ Html::script('https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.js') }}
		{{ Html::script('https://cdn.datatables.net/1.10.16/js/jquery.dataTables.min.js') }}
		{{ Html::script('http://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js') }}
		{{ Html::script(asset('js/header.js')) }}
		{{ Html::script(asset('js/commun.js')) }}
		@include('engagements/engagements_js')

		<script type="text/javascript">
			addEvent(window, "load", loadSortNumericComma);
			addEvent(window, "load", loadAjaxFormEngagements);
		</script>
	</head>
	<body>
		@include('commun/header')

		{!! Form::open(['id' => 'ajax-form-engagements']) !!}
			<!-- Gestion des engagements -->
			<div class="layout_withoutParams form-style">
				<div class="title">Gestion des engagements</div>

				<div class="zone-tableau-withoutOnglets">
					<div id="load" class="text-center margin-top-50">
						{{ Html::image('images/activity_indicator.gif', \Lang::get('amadeo.load')) }}
					</div>
					<div id="tableau" class="tableau" style="display:none">
						<div id="success-message" class="hide">
		                    <div class="alert alert-info alert-dismissible fade in" role="alert">
		                      <button type="button" class="close" data-dismiss="alert" aria-label="Close">
		                        <span aria-hidden="true">×</span>
		                      </button>
		                      <p></p>
		                    </div>
		                </div>

						@if ((sizeof(Auth::user()->roles) >0) AND ("Vétérinaire" == Auth::user()->roles[0]['nom']))
						<div id="buttonsGeneral" class="button-section-right">
							<a id="cancelButtonGeneral" class="button save">Rétablir</a>
							<a id="saveButtonGeneral" class="button save">Enregistrer</a>
						</div>
						@endif
						
						<table id='tab-engagements' class='display' cellspacing='0' width='100%'>
							<thead>
								<tr>
									<th></th>
									<th>Année</th>
									<th>Espèce</th>
									<th>Laboratoire</th>
									<th>Catégorie</th>
									<th>Nom</th>
									<th>Valeur</th>
									<th>Écart</th>
									<th>Écart</th>
									<th>Avancement<br><span>Année N</span></th>
									<th>Total<br><span>Année N-1</span></th>
								</tr>
								<tr id='forFilters'>
									<th></th>
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
								</tr>
							</thead>
						</table>
					</div>
				</div>
			</div>
		{!! Form::close() !!}
	</body>
</html>