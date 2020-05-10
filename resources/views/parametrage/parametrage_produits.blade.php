<div id="selectProductModal" class="modal">
	<div class="modal-dialog modal-parametrage" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="bootpopup-button close" data-dismiss="modal" aria-label="@lang('amadeo.close')"><img src="/images/CROIX_FERMETURE_BLANC.svg" height="32px" width="32px"></button>
				<h4 class="modal-title">@lang('amadeo.purchases.criteria-products-title')</h4>
				</div>
				
				<div id="divProductScope" class="modal-body modal-body-table flex-column">
					<div id="divProductGeneralCriteria" class="modal-body-criteres">
						<h1 class="modal-body-criteres-title">@lang('amadeo.purchases.criteria-products-step1')</h1>
						<ul class="horizontal">
							<li style="margin-right: 40px; margin-top: 20px;">
								<div class="params-encart-with-select">
									<div class="params-encart">
										<div class="params-criteres-dropdown">
											<h1>@lang('amadeo.purchases.criteria-products-step1-seller')</h1>
										</div>
										<div class="laboratories">
											<select style="display:none" id="laboratories[]" name="laboratories[]" multiple></select>
										</div>
									</div>
									<div class="params-criteres-dropdown-buttons">
										<a id="selectAllLaboratories">@lang('amadeo.select-all')</a>
										<a id="unselectAllLaboratories">@lang('amadeo.unselect-all')</a>
									</div>
								</div>
							</li>
							<div class="vertical" style="margin: 20px 50px 0px 40px;">
								<li>
									<div class="params-encart">
										<div class="params-criteres">
											<h1>@lang('amadeo.purchases.criteria-products-step1-type')</h1>
										</div>
										<div id="params-product-liste-types" class="params-panneau">
											@foreach (Session::get('list_of_types') as $type)
											@if (sizeof(Session::get('list_of_types'))%2 AND $loop->last)
											<div class="checkbox-item">
											@else
											<div class="checkbox-item" style="width: 50%;">
											@endif
												<div class="checkboxContainer-14">
													<input id="product-type-{{ $type->id }}" name="product-types[]" type="checkbox" value="{{ $type->id }}">
														<label for="product-type-{{ $type->id }}"></label>
												</div>
												<div class="checkboxLabel-14">
													<label for="product-type-{{ $type->id }}">{{ $type->nom }}</label>
												</div>
											</div>
											@endforeach
										</div>
									</div>
								</li>
								<li style="margin-bottom: 7px;">
									<div class="params-encart">
										<div class="params-criteres">
											<h1>@lang('amadeo.purchases.criteria-products-step1-specie')</h1>
										</div>
										<div id="params-product-liste-especes" class="params-panneau">
											@foreach (Session::get('list_of_species') as $species)
											@if (sizeof(Session::get('list_of_species'))%2 AND $loop->last)
											<div class="checkbox-item">
											@else
											<div class="checkbox-item" style="width: 50%;">
											@endif
												<div class="checkboxContainer-14">
													<input id="product-species-{{ $species->id }}" name="product-species[]" type="checkbox" value="{{ $species->id }}">
														<label for="product-species-{{ $species->id }}"></label>
												</div>
												<div class="checkboxLabel-14">
													<label for="product-species-{{ $species->id }}">{{ $species->nom }}</label>
												</div>
											</div>
											@endforeach
										</div>
									</div>
								</li>
							</div>
							<li></li>
						</ul>
					</div>
					<div id="divProductTherapeuticClassesCriteria" class="zone-tableau-withoutOnglets modal-body modal-body-table flex-column hide">
						<h1 class="modal-body-criteres-title">@lang('amadeo.purchases.criteria-products-step2')<p>@lang('amadeo.purchases.criteria-products-step1-nb') : <span id="nbFilteredProductsStep1Modal">-</span> / <span class="nbTotalProductsModal"></span></p></h1>
						<div id="loadProductTherapeuticClassesModal" class="load">
							{{ Html::image('images/activity_indicator.gif', \Lang::get('amadeo.load')) }}
						</div>
						<div id="tableProductTherapeuticClassesModal" class="tableau" style='display:none'>
							<table id='tab-products-therapeuticClasses' class='' cellspacing='0' width='100%'>
								<thead>
									<tr>
										<th>@lang('amadeo.products.therapeutic-classes.level1')</th>
										<th>@lang('amadeo.products.therapeutic-classes.level2')</th>
										<th>@lang('amadeo.products.therapeutic-classes.level3')</th>
										<th>@lang('amadeo.products.therapeutic-classes.code')</th>
									</tr>
									<tr id='forFiltersProductTherapeuticClassesModal'>
										<th class='text-filter'></th>
										<th class='text-filter'></th>
										<th class='text-filter'></th>
										<th class='text-filter'></th>
									</tr>
								</thead>
							</table>
						</div>
					</div>
					<div id="divProductTable" class="modal-body-criteres hide">
							<h1 class="modal-body-criteres-title">@lang('amadeo.purchases.criteria-products-step3')<p>@lang('amadeo.purchases.criteria-products-step2-nb') : <span id="nbFilteredProductsStep2Modal">-</span> / <span class="nbTotalProductsModal"></span></p><br><p>@lang('amadeo.purchases.criteria-products-step3-nb') : <span id="nbFilteredProductsStep3Modal">0</span> / <span class="nbTotalProductsModal"></span></p></h1>
							<div id="loadProductsModal" class="load">
								{{ Html::image('images/activity_indicator.gif', \Lang::get('amadeo.load')) }}
							</div>
							<div id="tableProductsModal" class="tableau" style='display:none'>
								<table id='tab-products' class='' cellspacing='0' width='100%'>
									<thead>
										<tr>
											<th>@lang('amadeo.products.seller')</th>
											<th>@lang('amadeo.products.name')</th>
											<th>@lang('amadeo.products.packaging')</th>
											<th>@lang('amadeo.products.gtin')</th>
										</tr>
										<tr id='forFiltersProductsModal'>
											<th class='select-filter'></th>
											<th class='text-filter'></th>
											<th class='text-filter'></th>
											<th class='text-filter'></th>
										</tr>
									</thead>
								</table>
								<p class="note">@lang('amadeo.purchases.criteria-products-step3-note')</p>
							</div>
					</div>

					<div id="divButtonListProducts" class="modal-footer" style="margin-top: 20px;">
						<div class="confirm-buttons-modal">
							<div id="previousButtonProductsPurchases" class="button hide"><a>@lang('amadeo.previous')</a><span class="btn_cancel"></span></div>
							<div id="nextButtonProductsPurchases" class="button"><a>@lang('amadeo.next')</a><span class="btn_add_product"></span></div>
							<div id="saveButtonProductsPurchases" class="button hide"><a>@lang('amadeo.validate')</a><span class="btn_save"></span></div>
						</div>
					</div>
				</div>
		</div>
	</div>
</div>