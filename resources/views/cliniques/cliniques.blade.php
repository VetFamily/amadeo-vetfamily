<!doctype html>
<html lang="{{ app()->getLocale() }}">
	<head>
		<meta charset="utf-8">
		<meta http-equiv="X-UA-Compatible" content="IE=edge">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		
		<title>@lang('amadeo.clinics.title')</title>

		<!-- Font -->
		
		<!-- Styles -->
		{{ Html::style('https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.12.1/jquery-ui.css') }}
		{{ Html::style('https://cdn.datatables.net/1.10.16/css/jquery.dataTables.min.css') }}
		{{ Html::style('https://cdn.datatables.net/rowreorder/1.2.3/css/rowReorder.dataTables.min.css') }}
		{{ Html::style('https://cdn.datatables.net/responsive/2.2.1/css/responsive.dataTables.min.css') }}
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
		@include('cliniques/cliniques_js')

		<script type="text/javascript">
			addEvent(window, "load", loadAjaxFormCliniques);
		</script>
	</head>
	<body>
		<div class="body">
			@include('commun/header')

			{!! Form::open(['id' => 'ajax-form-cliniques']) !!}
				<!-- Gestion des cliniques -->
				<div class="layout_withoutParams form-style">
					<div class="zone_title">
						<div class="title">@lang('amadeo.clinics.title')</div>
						<div id="buttonsGeneral" class="buttonsGeneral">
							@if ((sizeof(Auth::user()->roles) >0) AND ("Administrateur" == Auth::user()->roles[0]['nom']))
							<div id="downloadButtonGeneral" class="button" onclick='window.location="clinic-ajax/downloadClinicsCSV"'><a>@lang("amadeo.download")</a><span class="btn_download"></span></div>
							<!--<div id="addButtonGeneral" class="button"><a>@lang("amadeo.add")</a><span class="btn_add"></span></div>
							<div id="deleteButtonGeneral" class="button"><a>@lang("amadeo.delete")</a><span class="btn_delete"></span></div>-->
							@endif
						</div>
					</div>

					<div class="zone-tableau-withoutOnglets">
						<div id="load" class="load">
							{{ Html::image('images/activity_indicator.gif', \Lang::get('amadeo.load')) }}
						</div>
						<div id="tableau" class="tableau" style='display:none'>
							<table id='tab-clinics' class='' cellspacing='0' width='100%'>
								<thead>
									<tr>
										<th></th>
										<th>@lang('amadeo.clinics.country')</th>
										<th>@lang('amadeo.clinics.veterinaries')</th>
										<th>@lang('amadeo.clinics.name')</th>
										<th>@lang('amadeo.clinics.address')</th>
										<th>@lang('amadeo.clinics.zip-code')</th>
										<th>@lang('amadeo.clinics.city')</th>
										<th>@lang('amadeo.clinics.entry-year')</th>
										<th>@lang('amadeo.clinics.date-left')</th>
									</tr>
									<tr id='forFilters'>
										<th></th>
										<th class='select-filter'></th>
										<th class='text-filter'></th>
										<th class='text-filter'></th>
										<th class='text-filter'></th>
										<th class='text-filter'></th>
										<th class='text-filter'></th>
										<th class='text-filter'></th>
										<th class='text-filter'></th>
									</tr>
								</thead>
							</table>
						</div>
					</div>

					<div id="addClinicModal" class="modal">
						<div class="modal-dialog" role="document">
							<div class="modal-content">
							    <div class="modal-header">
									<button type="button" class="bootpopup-button close" data-dismiss="modal" aria-label="@lang('amadeo.close')"><img src="/images/CROIX_FERMETURE_BLANC.svg" height="32px" width="32px"></button>
									<h4 class="modal-title">@lang("amadeo.clinics.add.title")</h4>
							    </div>
							    <div class="modal-body flex-column">
						            <div id="divClinicVeterinaries" class="form-group">
						                <label class="label_text">@lang('amadeo.clinics.veterinaries') <input type="text" id="addClinicVeterinaries"></label>
						            </div>
						            <div id="divClinicName" class="form-group">
						                <label class="label_text">@lang('amadeo.clinics.name') <input type="text" id="addClinicName"></label>
						            </div>
						            <div id="divClinicCity" class="form-group">
						                <label class="label_text">@lang('amadeo.clinics.city') <input type="text" id="addClinicCity"></label>
						            </div>
							    	<div id="divClinicYear" class="form-group">
						                <label class="label_text">@lang("amadeo.clinics.entry-year") <input type="date" id="addClinicYear" autofocus></label>
						            </div>
							    </div>
							    <div class="modal-footer">
							    	<div class="confirm-buttons-modal">
							    		<div id="saveButtonClinic" class="button"><a>@lang('amadeo.save')</a><span class="btn_save"></span></div>
							    	</div>
							    </div>
							</div>
						</div>
					</div>
				</div>
			{!! Form::close() !!}
		</div>
	</body>
</html>