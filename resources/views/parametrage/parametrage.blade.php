<div id="parametrage" class="layout_withParams_left parametrage form-style">
	<input type="checkbox" id="parametrage-mobile" role="button" />
	<div class="params_title">
		<label for="parametrage-mobile">@lang('amadeo.purchases.parameters')</label>
		<!-- Fermeture du menu -->
		<img src="/images/MENU_TURQUOISE.svg" title="@lang('amadeo.purchases.parameters-mask')" alt="@lang('amadeo.purchases.parameters-mask')" onclick="closeNav()">
		<!--<img src="/images/MENU_TURQUOISE.png" id="params_arrow" width="25" height="25" title="Masquer les paramètres" onclick="onclickParamsArrow(false);" alt="Masquer les paramètres">-->
	</div>

	<!-- Bouton -->
	<div class="params_buttons">
		<div id="search-buttons" class="button"><a>@lang('amadeo.purchases.start-research')</a><span class="btn_search"></span></div>
	</div>
	
	<!-- Liste des paramètres -->
	<ul>
        <li class="border-bottom" style="padding-top: 0;">
	    	<div class="params-criteres-dropdown">
	    		<h1>@lang('amadeo.purchases.criteria-period')</h1>
	    	</div>	
        	<ul>
        		<li>
        			<div class="params-periode div-periode">
                        <div style="flex: 0.25; text-align: center;">@lang('amadeo.purchases.criteria-period-start') </div>
	        			{{ Form::selectMonth('period-startMonth', (Session::get((Request::is('statistiques') ? 'purchases' : ((Request::is('remises')) ? 'discounts' : '') ) . 'Criteria') != null ? Session::get((Request::is('statistiques') ? 'purchases' : ((Request::is('remises')) ? 'discounts' : '') ) . 'Criteria')["startMonth"] : 1), ['id' => 'period-startMonth', 'style' => 'text-transform: capitalize;']) }} {{ Form::selectYear('period-startYear', date('Y'), 2016, (Session::get((Request::is('statistiques') ? 'purchases' : ((Request::is('remises')) ? 'discounts' : '') ) . 'Criteria') != null ? Session::get((Request::is('statistiques') ? 'purchases' : ((Request::is('remises')) ? 'discounts' : '') ) . 'Criteria')["startYear"] : date('Y')), ['id' => 'period-startYear']) }}
					</div>
					<div class="params-periode">
						<div style="flex: 0.25; text-align: center;">@lang('amadeo.purchases.criteria-period-end')</div>
	        			{{ Form::selectMonth('period-endMonth', (Session::get((Request::is('statistiques') ? 'purchases' : ((Request::is('remises')) ? 'discounts' : '') ) . 'Criteria') != null ? Session::get((Request::is('statistiques') ? 'purchases' : ((Request::is('remises')) ? 'discounts' : '') ) . 'Criteria')["endMonth"] : (int)(explode("/", Session::get('date_maj'))[1]-1)), ['id' => 'period-endMonth', 'style' => 'text-transform: capitalize;']) }}
					    {{ Form::selectYear('period-endYear', date('Y'), 2016, (Session::get((Request::is('statistiques') ? 'purchases' : ((Request::is('remises')) ? 'discounts' : '') ) . 'Criteria') != null ? Session::get((Request::is('statistiques') ? 'purchases' : ((Request::is('remises')) ? 'discounts' : '') ) . 'Criteria')["endYear"] : date('Y')), ['id' => 'period-endYear']) }}
					</div>
                    <div class="checkbox-item checkbox-sub">
                    	<div class="checkboxContainer" style="width: 14px !important; height: 14px !important; margin-top: 2px;">
                            <input id="byMonth" name="byMonth" type="checkbox" {{ (Session::get('purchasesCriteria') != null) ? (Session::get('purchasesCriteria')["byYear"] == 1 ? "" : "checked='checked'") : "" }} value="true">
                            <label for="byMonth" style="width: 8px !important; height: 8px !important; left: 2px; top: 2px;"></label>
                        </div>
                        <div class="checkboxLabel">
                            <label for="byMonth">@lang('amadeo.purchases.criteria-period-by-month')</label>
                        </div>
                    </div>
        		</li>
        	</ul>
        </li>
        <li class="border-bottom" style="padding-top: 0; padding-bottom: 0;">
	    	<div class="params-criteres-dropdown">
	    		<h1>@lang('amadeo.purchases.criteria-display-type')</h1>
	    	</div>
    		<div style="margin-top: -5px; margin-bottom: 10px;">
    			<div class="radio-item radio-sub">
        			<div class="radioContainer-14" style="width: 14px !important; height: 14px !important; margin-top: 2px;">
        				<input id="display-type-product" name="display-type[]" type="radio" value="product">
  						<label for="display-type-product" style="width: 8px !important; height: 8px !important; left: 2px; top: 2px;"></label>
					</div>
        			<div class="radioLabel-14">
						<label for="display-type-product">@lang('amadeo.purchases.criteria-display-type-product')</label>
					</div>
				</div>
    			<div class="radio-item radio-sub">
        			<div class="radioContainer-14" style="width: 14px !important; height: 14px !important; margin-top: 2px;">
        				<input id="display-type-category" name="display-type[]" type="radio" value="category">
  						<label for="display-type-category" style="width: 8px !important; height: 8px !important; left: 2px; top: 2px;"></label>
					</div>
        			<div class="radioLabel-14">
						<label for="display-type-category">@lang('amadeo.purchases.criteria-display-type-category')</label>
					</div>
				</div>
	    		<div class="radio-item radio-sub">
        			<div class="radioContainer-14" style="width: 14px !important; height: 14px !important; margin-top: 2px;">
        				<input id="display-type-clinic" name="display-type[]" type="radio" value="clinic">
  						<label for="display-type-clinic" style="width: 8px !important; height: 8px !important; left: 2px; top: 2px;"></label>
					</div>
        			<div class="radioLabel-14">
						<label for="display-type-clinic">@lang('amadeo.purchases.criteria-display-type-clinic')</label>
					</div>
				</div>
	        	<div class="radio-item radio-sub">
        			<div class="radioContainer-14" style="width: 14px !important; height: 14px !important; margin-top: 2px;">
        				<input id="display-type-laboratory" name="display-type[]" type="radio" value="laboratory">
  						<label for="display-type-laboratory" style="width: 8px !important; height: 8px !important; left: 2px; top: 2px;"></label>
					</div>
        			<div class="radioLabel-14">
						<label for="display-type-laboratory">@lang('amadeo.purchases.criteria-display-type-seller')</label>
					</div>
				</div>
			</div>
        </li>
	    <li>
	    	<div class="params-encart">
	        	<div class="cliniques">
					<div class="params_criteres_buttons" style="border: 0;">
						<div id="selectButtonClinicsPurchases" class="button"><a>@lang('amadeo.purchases.criteria-clinics-button')</a><span class="btn_search"></span></div>
					</div>
					<div class="params-criteres-label">
						<label id="nbFilteredClinics">-</label> / <label class="nbTotalClinicsModal"></label>
						<a id="resetButtonClinicsPurchases" class="params-criteres-reset">
							<img src="/images/RAFRAICHIR_NOIR.svg" alt="@lang('amadeo.purchases.criteria-clinics-reset')" title="@lang('amadeo.purchases.criteria-clinics-reset')" style="width: 15px; height: 15px;" />
						</a>
					</div>
				</div>
			</div>
	    </li>
	    <li class="border-bottom" style="padding-top: 0;">
	    	<div class="params-encart">
	        	<div class="produits">
					<div class="params_criteres_buttons" style="border: 0;">
						<div id="selectButtonProductsPurchases" class="button"><a>@lang('amadeo.purchases.criteria-products-button')</a><span class="btn_search"></span></div>
					</div>
					<div class="params-criteres-label">
						<label id="nbFilteredProducts">-</label> / <label id="nbTotalProductsModal" class="nbTotalProductsModal"></label>
						<a id="resetButtonProductsPurchases" class="params-criteres-reset">
							<img src="/images/RAFRAICHIR_NOIR.svg" alt="@lang('amadeo.purchases.criteria-products-reset')" title="@lang('amadeo.purchases.criteria-products-reset')" style="width: 15px; height: 15px;" />
						</a>
					</div>
				</div>
			</div>
	    </li>
        <li>
        	<!--<div class="params-encart">
	        	<div class="params-criteres">
	        		<h1>@lang('amadeo.purchases.criteria-valorization')</h1>
	        		<a href="#params-liste-valorizations" aria-expanded="false" aria-controls="params-liste-valorizations"><img src="/images/DEPLIER_NOIR.svg" width="15" height="15" alt="Déplier"></a>
	        	</div>
	        	<div id="params-liste-valorizations" class="params-panneau">
    				<div class="radio-item" style="width: 50%;">
	        			<div class="radioContainer-14">
	        				<input id="valorization-1" name="valorizations[]" type="radio" value="1">
      						<label for="valorization-1"></label>
						</div>
	        			<div class="radioLabel-14">
							<label for="valorization-1">@lang('amadeo.purchases.criteria-valorization1')</label>
						</div>
					</div>
    				<div class="radio-item" style="width: 50%;">
	        			<div class="radioContainer-14">
	        				<input id="valorization-2" name="valorizations[]" type="radio" value="2">
      						<label for="valorization-2"></label>
						</div>
	        			<div class="radioLabel-14">
							<label for="valorization-2">@lang('amadeo.purchases.criteria-valorization2')</label>
						</div>
					</div>
    				<div class="radio-item">
	        			<div class="radioContainer-14">
	        				<input id="valorization-3" name="valorizations[]" type="radio" value="3">
      						<label for="valorization-3"></label>
						</div>
	        			<div class="radioLabel-14">
							<label for="valorization-3">@lang('amadeo.purchases.criteria-valorization3')</label>
						</div>
					</div>
			    </div>
			</div>
        </li>
        <li style="padding-top: 0;">-->
        	<div class="params-encart">
	        	<div class="params-criteres">
	        		<h1>@lang('amadeo.purchases.criteria-sources')</h1>
	        		<a href="#params-liste-central-purchasing" aria-expanded="false" aria-controls="params-liste-central-purchasing"><img src="/images/DEPLIER_NOIR.svg" width="15" height="15" alt="Déplier"></a>
	        	</div>
	        	<div id="params-liste-central-purchasing" class="params-panneau">
		        	@foreach (Session::get('list_of_central_purchasing') as $central)
		        	@if (sizeof(Session::get('list_of_central_purchasing'))%2 AND $loop->last)
    				<div class="checkbox-item">
		        	@else
    				<div class="checkbox-item" style="width: 50%;">
    				@endif
	        			<div class="checkboxContainer-14">
	        				<input id="central-purchasing-{{ $central->id }}" name="central-purchasing[]" type="checkbox" value="{{ $central->id }}">
      						<label for="central-purchasing-{{ $central->id }}"></label>
						</div>
	        			<div class="checkboxLabel-14">
							<label for="central-purchasing-{{ $central->id }}" style='text-transform: capitalize;'>{{ (strlen($central->nom) > 9 ? substr_replace(strtolower($central->nom), ".", 9) : strtolower($central->nom)) }}</label>
						</div>
					</div>
    				@endforeach
			    </div>
			</div>
        </li>
	</ul>
</div>