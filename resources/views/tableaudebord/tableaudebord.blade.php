<!doctype html>
<html lang="{{ app()->getLocale() }}">
	<head>
		<meta charset="utf-8">
		<meta http-equiv="X-UA-Compatible" content="IE=edge">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		
		<title>Tableau de bord</title>
		<!-- Font -->
		
		<!-- Styles -->
		<!-- {{ Html::style('https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.0.3/css/font-awesome.min.css') }} -->
		{{ Html::style('https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.12.1/jquery-ui.css') }}
		{{ Html::style('https://cdn.datatables.net/1.10.16/css/jquery.dataTables.min.css') }}
		{{ Html::style('https://netdna.bootstrapcdn.com/font-awesome/4.2.0/css/font-awesome.min.css') }}
		{{ Html::style('https://netdna.bootstrapcdn.com/bootstrap/3.3.0/css/bootstrap.min.css') }}
		{{ Html::style(asset('css/commun/button.css')) }}
		{{ Html::style(asset('css/commun/commun.css')) }}
		{{ Html::style(asset('css/commun/form.css')) }}
		{{ Html::style(asset('css/commun/header.css')) }}
		{{ Html::style(asset('css/commun/modal.css')) }}
		{{ Html::style(asset('css/commun/table.css')) }}
		{{ Html::style(asset('css/tableaudebord/tableaudebord.css')) }}

		<!-- JS Script -->
		{{ Html::script('https://cdnjs.cloudflare.com/ajax/libs/jquery/3.2.1/jquery.js') }}
		{{ Html::script('https://cdnjs.cloudflare.com/ajax/libs/jquery/3.2.1/jquery.min.js') }}
		{{ Html::script('https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.js') }}
		{{ Html::script('https://cdn.datatables.net/1.10.16/js/jquery.dataTables.min.js') }}
		{{ Html::script('https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js') }}
		{{ Html::script(asset('js/commun.js')) }}
		{{ Html::script(asset('js/header.js')) }}
		{{ Html::script('https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.7.2/Chart.bundle.js') }}
		{{ Html::script('https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.7.2/Chart.bundle.min.js') }}
		@include('tableaudebord/tableaudebord_js')

		<script type="text/javascript">
			addEvent(window, "load", loadSortNumericComma);
		    addEvent(window, "load", loadTbdGeneral);
		</script>
	</head>
	<body>
		@include('commun/header')

		{!! Form::open(['class' => 'ajax-form-stats']) !!}
		<div class="layout_withoutParams form-style">
			<!-- <div class="zone_title">
				<div class="title">Tableau de bord</div>
				<div id="buttonsGeneral" class="buttonsGeneral">
					<div id="commentaire_general"></div>
				</div>
			</div> -->

			<!-- Tableau de bord -->
			{{-- <div id="main" class="layout_withParams_right tableaudebord form-style">
				<div class="main_tableaudebord">
					@include('tableaudebord/tableaudebord_general')
				</div>
			</div> --}}

		<iframe id="dashboard" width="100%" style="height : 100vh" src="{{ config('app.dashboard_url') }}">
		</div>
		{!! Form::close() !!}
	</body>
</html>