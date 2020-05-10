<div id="selectClinicModal" class="modal">
	<div class="modal-dialog modal-parametrage" role="document">
		<div class="modal-content">
		    <div class="modal-header">
				<button type="button" class="bootpopup-button close" data-dismiss="modal" aria-label="@lang('amadeo.close')"><img src="/images/CROIX_FERMETURE_BLANC.svg" height="32px" width="32px"></button>
				<h4 class="modal-title">@lang('amadeo.purchases.criteria-clinics-title')</h4>
			</div>
			<div id="divClinicScope" class="modal-body modal-body-table flex-column">
				<div id="divClinicGeneralCriteria" class="modal-body-criteres">
					<h1 class="modal-body-criteres-title">@lang('amadeo.purchases.criteria-clinics-step1')</h1>
					<ul class="horizontal">
						<li style="margin-right: 20px;">
							<div class="params-encart">
								<div class="params-criteres-dropdown">
									<h1>@lang('amadeo.purchases.criteria-clinics-step1-entry-date')</h1>
								</div>
								<div class="clinic-years">
									<select style="display:none" id="clinic-years[]" name="clinic-years[]" multiple></select>
								</div>
							</div>
						</li>
						<li></li>
						<li></li>
			    	</ul>
				</div>
				<div id="divClinicTable" class="modal-body-criteres hide">
					<h1 class="modal-body-criteres-title">@lang('amadeo.purchases.criteria-clinics-step2')<p>@lang('amadeo.purchases.criteria-clinics-step1-nb') : <span id="nbFilteredClinicsStep1Modal">-</span> / <span class="nbTotalClinicsModal"></span></p><br><p>@lang('amadeo.purchases.criteria-clinics-step2-nb') : <span id="nbFilteredClinicsStep2Modal">0</span> / <span class="nbTotalClinicsModal"></span></p></h1>
					<div id="loadClinicsModal" class="load">
						{{ Html::image('images/activity_indicator.gif', \Lang::get('amadeo.load')) }}
					</div>
					<div id="tableClinicsModal" class="tableau" style='display:none'>
						<table id='tab-clinics' class='' cellspacing='0' width='100%'>
							<thead>
								<tr>
									<th>@lang('amadeo.clinics.veterinaries')</th>
									<th>@lang('amadeo.clinics.name')</th>
									<th>@lang('amadeo.clinics.zip-code')</th>
									<th>@lang('amadeo.clinics.city')</th>
								</tr>
								<tr id='forFiltersClinicsModal'>
									<th class='text-filter'></th>
									<th class='text-filter'></th>
									<th class='text-filter'></th>
									<th class='text-filter'></th>
								</tr>
							</thead>
						</table>
						<p class="note">@lang('amadeo.purchases.criteria-clinics-step2-note')</p>
					</div>
				</div>

				<div id="divButtonListClinics" class="modal-footer" style="margin-top: 20px;">
					<div class="confirm-buttons-modal">
						<div id="previousButtonClinicsPurchases" class="button hide"><a>@lang('amadeo.previous')</a><span class="btn_cancel"></span></div>
						<div id="nextButtonClinicsPurchases" class="button"><a>@lang('amadeo.next')</a><span class="btn_add_product"></span></div>
						<div id="saveButtonClinicsPurchases" class="button hide"><a>@lang('amadeo.validate')</a><span class="btn_save"></span></div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>