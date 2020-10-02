<script type="text/javascript">

	/* 
	* Formate le détail d'une ligne.
	*/
	function format ( row ) {
		// Calcul du nombre de colonnes
		var nbCol = "{{sizeof(Session::get('list_of_species'))}}";
		var idRow = row[7];
		
		// Affichage des espèces
		var html_especes = '<tr><td class="detail-row-title">@lang("amadeo.categories.species")</td>';
		var liste_especes = null;
		if (row[8] != null)
		{
			liste_especes = row[8].split("|")
		}
		@foreach (Session::get('list_of_species') as $espece)
			var espece_checked = false;
			if (liste_especes != null && liste_especes.includes('{{ $espece->id }}'))
				espece_checked = true;
			html_especes += '<td><div class="radio-item-horizontal">' 
							+ '<div class="radioContainer">' 
								+ '<input id="espece-' + idRow + '-{{ $espece->id }}"';
			if (espece_checked)
			{
				html_especes += ' checked="checked"'
			}
			@if ((sizeof(Auth::user()->roles) >0) AND ("Vétérinaire" == Auth::user()->roles[0]['nom']))
				html_especes += ' disabled="disabled"'
			@endif
			html_especes += ' name="especes_' + idRow + '[]" type="radio" value="{{ $espece->id }}">' 
								+ '<label for="espece-' + idRow + '-{{ $espece->id }}"></label>' 
							+ '</div>' 
							+ '<div class="radioLabel">' 
								+ '<label for="espece-' + idRow + '-{{ $espece->id }}">{{ $espece->nom }}</label>' 
							+ '</div>' 
						+ '</div></td>';
		@endforeach
		html_especes += '</tr>';

		var html_produits = "<tr>" + "<td class='detail-row-title' style='vertical-align: top;'>@lang('amadeo.categories.products')</td>" + "<td colspan='" + (nbCol-1) + "' class='detail-row-subTable'><table id='tab-categorie-produits-" + idRow + "' class='' cellspacing='0' width='100%'><thead><tr><th>@lang('amadeo.products.source')</th><th>@lang('amadeo.products.name')</th><th>@lang('amadeo.products.packaging')</th><th>@lang('amadeo.products.gtin')</th></tr></thead></table></td><td style='border: 1px solid var(--dark-grey); border-left: none;'>";

		@if ((sizeof(Auth::user()->roles) >0) AND ("Vétérinaire" != Auth::user()->roles[0]['nom']))
			html_produits += '<div class="detail-row-subTable-buttons">'
			+ '<div id="addButtonCategorieProduit-' + idRow + '" class="button"><a>@lang("amadeo.categories.add-product.button")</a><span class="btn_add_product"></span></div>'
			+ '<div id="deleteButtonCategorieProduit-' + idRow + '" class="button"><a>@lang("amadeo.categories.delete-product.button")</a><span class="btn_delete_product"></span></div>';
		@endif
		
		html_produits += "</td></tr>";
		
		var html_commentaires = "<tr>" + "<td class='detail-row-title' style='vertical-align: top;'>@lang('amadeo.categories.comments')</td>" + "<td colspan='" + (nbCol-1) + "'><div id='div-categorie-commentaires-" + idRow + "' class='commentaires'></div>";
		
		@if ((sizeof(Auth::user()->roles) >0) AND ("Vétérinaire" != Auth::user()->roles[0]['nom']))
		html_commentaires += "<textarea id='textarea-categorie-commentaires-" + idRow + "' rows='4' placeholder='@lang('amadeo.textarea.message')'></textarea>";
		@endif
		html_commentaires += "</td><td></td></tr>";

		var html_buttons = "";
		@if ((sizeof(Auth::user()->roles) >0) AND ("Administrateur" == Auth::user()->roles[0]['nom']))
			html_buttons += '<tr><td colspan="' + (nbCol+1) + '"><div class="detail-row-buttons">'
			+ '<div id="cancelButtonCategorie-' + idRow + '" class="button"><a>@lang("amadeo.reset")</a><span class="btn_cancel"></span></div>'
			+ '<div id="saveButtonCategorie-' + idRow + '" class="button button_bold"><a>@lang("amadeo.save")</a><span class="btn_save"></span></div>'
			+ '</div></td></tr>';
		@endif

		var html = '<table class="detail-row">'+ html_especes + html_produits + html_commentaires + html_buttons + '</table>';

		return html;
	}

	/*
	* Met en forme le tableau des produits d'une catégorie.
	*/
	function loadDatatableProduits(rowData)
	{
		var idRow = rowData[7];
    	// Récupération des informations
    	var params = {
			"_token": document.getElementsByName("_token")[0].value,
			"categorie": idRow
		};

		$.ajax({
		    url: "{{ route('categorie-produit-ajax.index') }}", 
	        data: $.param(params),
		    success: function(json) {
		    	var data = jQuery.map(json, function(el, i) {
				  return [[el.source, el.denomination, el.conditionnement, el.code_gtin, el.id, el.obsolete]];
				});

			    // DataTable
			    var tableProduits = $('#tab-categorie-produits-' + idRow).DataTable( {
			    	"language": {
		              "url": "//cdn.datatables.net/plug-ins/1.10.16/i18n/French.json"
		            },
		            "bSortCellsTop": true,
		            "info": false,
		            "paging": false,
		            "searching": false,
		            "sScrollY": "50vh",
		            "bScrollCollapse": true,
		            "order": [[ 1, "asc" ], [ 2, "asc" ]],
					"aoColumns": [ 
						{
							"render": function ( data, type, row ) { 
								return data.capitalize(); 
							},
							"sWidth": "10%"
						}, 
						{"sWidth": "45%"}, 
						{"sWidth": "45%"}, 
						{"sWidth": "10%"} 
					],
					"aaData": data,
					"createdRow": function ( row, data, index ) {
						$('td', row).each(function(){
							$(this).html('<div>' + $(this).html() + '</div>');
						});
						$('td', row).eq(0).addClass('width-10');
						$('td', row).eq(0).find('div').addClass('texte');
						$('td', row).eq(1).addClass('width-30');
						$('td', row).eq(1).find('div').addClass('texte');
						$('td', row).eq(1).find('div').attr('title', $('td', row).eq(1).find('div').html());
						$('td', row).eq(2).addClass('width-30');
						$('td', row).eq(2).find('div').addClass('texte');
						$('td', row).eq(2).find('div').attr('title', $('td', row).eq(2).find('div').html());

						if (data[5])
						{
							$('td', row).eq(0).find('div').addClass('obsolete');
							$('td', row).eq(1).find('div').addClass('obsolete');
							$('td', row).eq(2).find('div').addClass('obsolete');
							$('td', row).eq(3).find('div').addClass('obsolete');
						}
			        },
			        initComplete: function () {
			        	tableProduits.columns.adjust();
			        }
			    });
			    
			    @if ((sizeof(Auth::user()->roles) >0) AND ("Vétérinaire" != Auth::user()->roles[0]['nom']))
			    // Action lors de la sélection d'un produit
		    	$('#tab-categorie-produits-' + idRow + ' tbody').on('click', 'tr td', function () {
			        $(this).parent().toggleClass('selected');
			    } );

			    // Ajout d'une ligne lors de l'action sur le bouton 'Ajouter produit'
			    $('#addButtonCategorieProduit-' + idRow).click( function () {
					document.getElementById('tableProductsModal').style.display = "none";
					document.getElementById('loadProductsModal').style.display = "block";

					$( '#addCategorieProduitModal' ).modal('show');
					$( '#addCategorieProduitModal' ).draggable({
						handle: ".modal-header"
					});

			    	// Récupération des informations
			    	var ids = [];
                    $( '#tab-categorie-produits-' + idRow + ' tbody tr' ).each(function() {
                    	if ($('#tab-categorie-produits-' + idRow).DataTable().data().count())
                    	{
                    		ids.push($('#tab-categorie-produits-' + idRow).DataTable().row( $(this) ).data()[4]);
                    	}
                    });
                    
			    	var params = {
						"_token": document.getElementsByName("_token")[0].value,
						"country": rowData[10],
						"laboratoire": rowData[9],
						"produits": ids
					};

					$( '#tableProductsModal' ).html("<table id='tab-categorie-produits-candidats-" + idRow + "' class='' cellspacing='0' width='100%'><thead><tr><th>@lang('amadeo.products.source')</th><th>@lang('amadeo.products.name')</th><th>@lang('amadeo.products.packaging')</th></tr><tr id='forFiltersModal'><th class='text-filter'><th class='text-filter'></th><th class='text-filter'></th></tr></thead></table>");

					$( '#divButtonListeProduits' ).html('<div class="confirm-buttons-modal"><div id="saveButtonCategorieProduit-' + idRow + '" class="button"><a>@lang("amadeo.validate")</a><span class="btn_save"></span></div></div>');

			    	// Recherche des produits candidats pour la catégorie	    	
			    	$.ajax({
					    type: "POST",
      					url: "categorie-produit-ajax/showListOfProducts", 
				        data: $.param(params),
					    success: function(json) {
					    	var data = jQuery.map(json, function(el, i) {
							  return [[el.source, el.denomination, el.conditionnement, el.code_gtin, el.id, el.obsolete]];
							});

						    // DataTable
						    var tableProduitsCandidats = $('#tab-categorie-produits-candidats-' + idRow).DataTable( {
						    	"language": {
					            	"url": "//cdn.datatables.net/plug-ins/1.10.16/i18n/French.json",
                					"emptyTable": '@lang("amadeo.categories.search-empty")'
					            },
								"bSortCellsTop": true,
								"destroy": true,
        						"bLengthChange": false,
								"pageLength": 100,
					            "info": false,
					            "sScrollY": "50vh",
					            "bScrollCollapse": true,
					            "order": [[ 1, "asc" ], [ 2, "asc" ]],
								"aoColumns": [ 
									{
										"render": function ( data, type, row ) { 
											return data.capitalize(); 
										}
									}, 
									null, 
									null
								],
								"aaData": data,
								"createdRow": function ( row, data, index ) {
									$('tr', row).eq(0).removeClass('selected');
									
									$('td', row).each(function(){
										$(this).html('<div>' + $(this).html() + '</div>');
									});

									$('td', row).eq(0).addClass('width-10');
									$('td', row).eq(0).find('div').addClass('texte');
									$('td', row).eq(1).addClass('width-30');
									$('td', row).eq(1).find('div').addClass('texte');
									$('td', row).eq(1).find('div').attr('title', $('td', row).eq(1).find('div').html());
									$('td', row).eq(2).addClass('width-30');
									$('td', row).eq(2).find('div').addClass('texte');
									$('td', row).eq(2).find('div').attr('title', $('td', row).eq(2).find('div').html());

									if (data[5])
									{
										$('td', row).eq(0).find('div').addClass('obsolete');
										$('td', row).eq(1).find('div').addClass('obsolete');
										$('td', row).eq(2).find('div').addClass('obsolete');
									}
						        },
						        initComplete: function () {
									var api = this.api();
              						$('#tab-categorie-produits-candidats-' + idRow + ' thead tr#forFiltersModal th').each(function(i) {
										var column = api.column(i);
										if ($(this).hasClass('text-filter'))
										{
											// Création des input pour les colonnes de classe "text-filter"
											var input = $('<input class="filter-column-' + i + '" type="text" placeholder="@lang("amadeo.input.message")" style="width:100%;" />').on('keyup', function() {
												var val = $.fn.dataTable.util.escapeRegex($(this).val());
												column.search(val ? val : '', true, false).draw();
											});
											$('#forFiltersModal th').eq(i).html(input);
										}
									});

						        	document.getElementById('tableProductsModal').style.display = "block";
									document.getElementById('loadProductsModal').style.display = "none";
									// Suppression de la recherche globale du tableau
						            document.getElementById('tab-categorie-produits-candidats-' + idRow + '_filter').style.display = "none";
									tableProduitsCandidats.columns.adjust();
									
									$('#tab-categorie-produits-candidats-' + idRow + ' tbody').on('click', 'tr', function () {
										$(this).toggleClass('selected');
									} );
													
									// Select and unselect links
									$('<div class="dataTables_buttons"><div><a id="selectAllFilteredProducts">@lang("amadeo.select-all-filtered")</a><a id="selectAllProducts">@lang("amadeo.select-all")</a></div><div><a id="unselectAllFilteredProducts">@lang("amadeo.unselect-all-filtered")</a><a id="unselectAllProducts">@lang("amadeo.unselect-all")</a></div></div>').insertBefore('#tab-categorie-produits-candidats-' + idRow + '_paginate');
									$('#selectAllProducts').on('click', function() 
									{
										tableProduitsCandidats.rows().every(function() {
											this.nodes().to$().addClass('selected')
										})
									});

									$('#unselectAllProducts').on('click', function() 
									{
										tableProduitsCandidats.rows().every(function() {
											this.nodes().to$().removeClass('selected')
										})
									});

									$('#selectAllFilteredProducts').on('click', function() 
									{
										tableProduitsCandidats.rows( { search: 'applied' } ).every(function() {
											this.nodes().to$().addClass('selected')
										})
									});

									$('#unselectAllFilteredProducts').on('click', function() 
									{
										tableProduitsCandidats.rows( { search: 'applied' } ).every(function() {
											this.nodes().to$().removeClass('selected')
										})
									});
						        }
						    });

						    // Action sur le bouton 'Enregistrer' du modal
						    $('#divButtonListeProduits').on('click', 'div[id^="saveButtonCategorieProduit-"]', function () {
						    	var data = $('#tab-categorie-produits-candidats-' + idRow).DataTable().rows( '.selected' ).data();

						    	for (i=0 ; i<data.length ; i++){
						    		// Mise à jour du tableau
						    		var obj = data[i];
			                    	$('#tab-categorie-produits-' + idRow).DataTable().row.add( [ obj[0], obj[1], obj[2], obj[3], obj[4], obj[5] ] ).draw();
			                    };
			                    $('#tab-categorie-produits-candidats-' + idRow).DataTable().rows( '.selected' ).remove().draw( false );
			                    $( '#addCategorieProduitModal' ).modal('hide');
						    });

					    	$( '#addCategorieProduitModal' ).modal('show');
					    	$( '#addCategorieProduitModal' ).draggable({
						    	handle: ".modal-header"
						  	});
					    }
					});
			    });

			    $('#deleteButtonCategorieProduit-' + idRow).click( function () {
			    	$('#tab-categorie-produits-' + idRow).DataTable().rows('.selected').remove().draw( false );
                });

				@endif
			}
	    });

	}

	function loadDivCommentaires(idRow)
	{
		// Création de l'URL
    	var url = '{{ route("categorie-ajax.show", "id") }}';
    	url = url.replace('id', idRow);

		$.ajax({
		    url: url, 
		    success: function(json) {
		    	var commentaires = "";

		    	for (i=0 ; i<json.length ; i++)
		    	{
		    		var obj = json[i];
		    		commentaires += "<div class='bulle" + (i%2 ? " darker" : "") + "'><p class='writer'>" + obj["name"] + " a écrit :</p><p>" + obj["commentaire"] + "</p><span class='time-right'>" + formatDateString(obj["date"]) + "</span></div>";
		    	}

		    	$( '#div-categorie-commentaires-' + idRow ).html(commentaires);
		    	$( '#div-categorie-commentaires-' + idRow ).scrollTop($( '#div-categorie-commentaires-' + idRow )[0].scrollHeight);
		    }
		});
	}

	/*
	* Ajoute des select pour le nom d'une ligne.
	*/
	function createSelectForRow(tr, idRow)
	{
		tr.find('div').each(function(index) {
	    	if (index == 5)
	    	{
	    		// Nom modifiable
	    		var input = $('<input type="text" id="row-' + idRow + '-nom" value="' + $(this).html() + '" style="width:100%;" />');
	    		$(this).html(input);
	    	}
	    });
	}

	/*
	* Charge le tableau des catégories.
	*/
	function loadAjaxFormCategories()
	{
		$.ajax({
		    url: "{{ route('categorie-ajax.index') }}", 
		    success: function(json) {
		    	var data = jQuery.map(json, function(el, i) {
		    	  var especes = "";
		    	  var isFirst = true;
		    	  var liste_especes = null;
		    	  if (el.especes != null)
		    	  {
		    	  	liste_especes = el.especes.split("|");
		    	  }
				  @foreach (Session::get('list_of_species') as $espece)
		    	  	if (liste_especes != null && liste_especes.includes('{{ $espece->id }}'))
		    	  	{
		    	  		if (isFirst)
		    	  		{
		    	  			isFirst = false;
		    	  		} else {
		    	  			especes += ", ";
		    	  		}
		    	  		especes += '{{ $espece->nom }}';
		    	  	}
				  @endforeach

				  return [[null, el.country, el.annee, especes, el.laboratoire, el.categorie, el.nb_produits, el.id, el.especes, el.laboratoire_id, el.country_id]];
				});

			    // DataTable
			    var table = $('#tab-categories').DataTable( {
			    	"language": {
		              "url": "//cdn.datatables.net/plug-ins/1.10.16/i18n/French.json"
		            },
		            "bSortCellsTop": true,
		            "bLengthChange": false,
		            "bAutoWidth": false,
		            "scrollY": "60vh",
		            "paging": true,
					"pageLength": 50,
		            "info": false,
        			"scrollCollapse": true,
		            "columnDefs": [ {
						"targets": 0,
						"orderable": false
					} ],
					"order": [[ 1, "desc" ], [ 2, "desc" ], [ 4, "asc" ], [ 5, "asc" ]],
					"aoColumns": [ {"sWidth": "4%"}, {"sWidth": "10%"}, {"sWidth": "10%"}, {"sWidth": "15%"}, {"sWidth": "15%"}, {"sWidth": "41%"}, {"sWidth": "15%"} ],
					"aaData": data,
					"createdRow": function ( row, data, index ) {
						$('td', row).each(function(){
							$(this).html('<div>' + $(this).html() + '</div>');
						});
						// Affichage du bouton 'Détail'
						$('td', row).eq(0).find('div').addClass('details-control');
						$('td', row).eq(5).addClass('width-50');
						$('td', row).eq(5).find('div').addClass('texte');
						$('td', row).eq(5).find('div').attr('title', $('td', row).find('div').eq(5).html());
						$('td', row).eq(6).find('div').addClass('nombre');
			        },
					initComplete: function () {
			            var api = this.api();
			            $('#tab-categories thead tr#forFilters th').each(function(i) {
			            	var column = api.column(i);
			            	if ($(this).hasClass('select-filter')) 
			                {
			                    // Création des select pour les colonnes de classe "select-filter"
			                	var select = $('<select id="selectFilter-'+ i + '" style="width:100%;"><option value=""></option></select>').on('change', function() {
			                        var val = $.fn.dataTable.util.escapeRegex($(this).val());
			                        column.search(val ? '^' + val + '$' : '', true, false).draw();
			                    });
			                    $('#forFilters th').eq(i).html(select);
			                    column.data().unique().sort().each(function(d, j) {
			                        select.append('<option value="' + d + '">' + d + '</option>')
			                    });
			                } else if ($(this).hasClass('text-filter'))
			    			{
			    				// Création des input pour les colonnes de classe "text-filter"
			                	var input = $('<input class="filter-column-' + i + '" type="text" placeholder="@lang("amadeo.input.message")" style="width:100%;" />').on('keyup', function() {
			    					var val = $.fn.dataTable.util.escapeRegex($(this).val());
			    					column.search(val ? val : '', true, false).draw();
			    				});
			    				$('#forFilters th').eq(i).html(input);
			    			}
			            });

						document.getElementById('tableau').style.display = "block";
				        document.getElementById('load').style.display = "none";
			            // Suppression de la recherche globale du tableau
					    document.getElementById('tab-categories_filter').style.display = "none";

					    table.columns.adjust();
					}
			    });
				
			    // Ouverture et fermeture du détail d'une catégorie
			    $('#tab-categories tbody').on('click', 'td div.details-control', function () {
			    	var tr = $(this).closest('tr');
			        var row = table.row( tr );
			       
			        if ( row.child.isShown() ) {
			            // This row is already open - close it
			            row.child.hide();

			            // Mise à jour du tableau
				    	$('#tab-categories').dataTable().fnUpdate(row.data(),tr,undefined,false);

			            tr.children().each(function(index) {
					    	if (index == 0)
					    	{
					    		$(this).html('<div class="details-control"></div>');
					    	} else if (index == 5)
					    	{
					    		$(this).addClass('width-50');
					    		$(this).html('<div class="texte" title="' + $(this).html() + '">' + $(this).html() + '</div>');
					    	} else if (index == 6)
					    	{
					    		$(this).html('<div class="nombre">' + $(this).html() + '</div>');
					    	} else
					    	{
					    		$(this).html('<div>' + $(this).html() + '</div>');
					    	}
					    });
						
			            tr.removeClass('shown');
			        }
			        else {
			            // Open this row
			            row.child( '<div>' + format( row.data() ) + '</div>' ).show();
			            loadDatatableProduits(row.data());
			            loadDivCommentaires(row.data()[7]);
			            row.child().addClass('child');
			            tr.addClass('shown');
			            @if ((sizeof(Auth::user()->roles) >0) AND ("Vétérinaire" != Auth::user()->roles[0]['nom']))
			            	createSelectForRow(tr, row.data()[7]);
			            @endif
			        }
			    } );

			    @if ((sizeof(Auth::user()->roles) >0) AND ("Vétérinaire" != Auth::user()->roles[0]['nom']))
			    	var tableProduits = null;
				    // Action lors de la sélection d'une ligne
			    	$('#tab-categories tbody').on('click', '> tr > td:not(:first-child)', function () {
				        if (($(this).parent().hasClass('odd') || $(this).parent().hasClass('even')) && !$(this).parent().hasClass('shown'))
			    		{
			    			$(this).parent().toggleClass('selected');
			    		}
				    } );

				    // Ouverture du modal lors de l'action sur le bouton 'Ajouter'
				    $('#addButtonGeneral').click( function () {
				    	$('#addCategorieModal').modal('show');
				    	$( '#addCategorieModal' ).draggable({
					    	handle: ".modal-header"
					  	});
				    	$('#selectCategorieCountry').val('');
				    	$('#selectCategorieLaboratoire').val('');
				    	$('#addCategorieNom').val('');

				    	var $selectCountry = $( '#selectCategorieCountry' ); 
				    	$selectCountry.find( 'option' ).remove();
	    				$selectCountry.append('<option value="">@lang("amadeo.list.message")</option>');

	    				@foreach (Session::get('list_of_countries') as $country)
	    					$selectCountry.append('<option value="{{ $country->ctry_id }}">{{ $country->ctry_name }}</option>');
						@endforeach
						
				    	var annee = (new Date().getMonth() < 8) ? new Date().getFullYear() : (new Date().getFullYear()) + 1;
			    		$( '#addCategorieAnnee' ).val(annee);
			    		
				    	var $selectLaboratoire = $( '#selectCategorieLaboratoire' ); 
				    	$selectLaboratoire.find( 'option' ).remove();
	    				$selectLaboratoire.append('<option value="">@lang("amadeo.list.message")</option>');

	    				@foreach (Session::get('laboratoires_liste') as $laboratoire)
	    					$selectLaboratoire.append('<option value="{{ $laboratoire->id }}">{{ $laboratoire->nom }}</option>');
	    				@endforeach
	    				$('#selectCategorieLaboratoire option').each(function(i)
						{
							if (i>0)
							{	
								var option = $(this).text();
								option = option.toLowerCase(); 
								var newOption = "Multi-laboratoires".toLowerCase();
								if ( option > newOption) 
								{
								    $(this).before('<option value="Multi-laboratoires">@lang("amadeo.categories.seller-multiple")</option>');
								    return false;
								}
							}
						});
				    });

				    // Ouverture du modal lors de l'action sur le bouton 'Dupliquer'
				    $('#copyButtonGeneral').click( function () {
				    	if ($('#tab-categories tbody > tr.selected').length == 1)
				    	{
				    		$('#copyCategorieModal').modal('show');
					    	$( '#copyCategorieModal' ).draggable({
						    	handle: ".modal-header"
						  	});

				    		var annee = (new Date().getMonth() < 8) ? new Date().getFullYear() : (new Date().getFullYear()) + 1;
				    		var select = table.row( $('#tab-categories tbody > tr.selected').first());
				    		$( '#copyCategorieCountry' ).html(select.data()[1]);
				    		$( '#copyCategorieAnnee' ).val(annee);
				    		$( '#copyCategorieLaboratoire' ).html(select.data()[4]);
				    		$( '#copyCategorieAncienNom' ).html(select.data()[5]);
				    		$( '#copyCategorieNom' ).val(select.data()[5]);
					    	
					    	// Récupération des informations
					    	var params = {
								"_token": document.getElementsByName("_token")[0].value,
								"categorie": select.data()[7]
							};

							$.ajax({
							    url: "{{ route('categorie-produit-ajax.index') }}", 
						        data: $.param(params),
							    success: function(json) {
							    	var data = jQuery.map(json, function(el, i) {
									  return [[el.denomination, el.conditionnement, el.code_gtin, el.id, el.obsolete]];
									});

								    // DataTable
								    tableProduits = $('#tab-copy-categorie-produits').DataTable( {
								    	"language": {
							              "url": "//cdn.datatables.net/plug-ins/1.10.16/i18n/French.json"
							            },
							            "destroy": true,
							            "bSortCellsTop": true,
							            "info": false,
							            "paging": false,
							            "searching": false,
							            "sScrollY": "25vh",
							            "bScrollCollapse": true,
							            "order": [[ 0, "asc" ], [ 1, "asc" ]],
										"aaData": data,
										"createdRow": function ( row, data, index ) {
											$('td', row).each(function(){
												$(this).html('<div>' + $(this).html() + '</div>');
											});
											$('td', row).eq(0).addClass('width-20');
											$('td', row).eq(0).find('div').addClass('texte');
											$('td', row).eq(0).find('div').attr('title', $('td', row).eq(0).find('div').html());
											$('td', row).eq(1).addClass('width-20');
											$('td', row).eq(1).find('div').addClass('texte');
											$('td', row).eq(1).find('div').attr('title', $('td', row).eq(1).find('div').html());

											if (data[4])
											{
												$('td', row).eq(0).find('div').addClass('obsolete');
												$('td', row).eq(1).find('div').addClass('obsolete');
												$('td', row).eq(2).find('div').addClass('obsolete');
											}
								        }
								    });
								}
							});
				    	} else if ($('#tab-categories tbody > tr.selected').length == 0)
				    	{
				    		bootoast.toast({
								message: "@lang('amadeo.categories.copy.no-select')",
								type: 'warning'
							});
				    	} else if ($('#tab-categories tbody > tr.selected').length > 1)
				    	{
				    		bootoast.toast({
								message: "@lang('amadeo.categories.copy.multiple-select')",
								type: 'warning'
							});
				    	}
				    });

				    // Action sur le bouton 'Enregistrer' du modal de création
				    $('#saveButtonCategorie').click( function () {
				    	var message = '<p class="question">@lang("amadeo.save.question")</p>';
			    	
		            	confirmBox(
		            		"@lang('amadeo.categories.add.title')", 
		            		message, 
		            		'@lang("amadeo.yes")',
        					'@lang("amadeo.no")',
		            		function()
		            		{
						    	// Récupération des informations
						    	var selectedCountry = $( '#selectCategorieCountry' ).find('option:selected');
						    	var selectedLaboratoire = $( '#selectCategorieLaboratoire' ).find('option:selected');

						    	var params = {
									"_token": document.getElementsByName("_token")[0].value,
									"country": selectedCountry.val(),
									"laboratoire": selectedLaboratoire.val(),
									"nom": document.getElementById("addCategorieNom").value,
									"annee": document.getElementById("addCategorieAnnee").value
								};
								
						    	$.ajax({
						    		dataType: 'json',
							        url: '{{ route("categorie-ajax.store") }}', 
							        type: "POST",
							        data: $.param(params),
					    			success: function(data) {
										if(data.errors) {
											var errors = jQuery.map(data.errors, function(el, i) {
												bootoast.toast({
													message: el[0],
													type: 'danger',
													timeout: 5
												});
											});
										}

						                if(data.success) {
						                	bootoast.toast({
												message: "@lang('amadeo.categories.add.ok')",
												type: 'success'
											});
						                	$('#addCategorieModal').modal('hide');

						                    // Mise à jour du tableau
						                    var laboratoire = data.categorie[0]["laboratoire_id"] != null ? data.categorie[0]["laboratoire"] : "@lang('amadeo.categories.seller-multiple')";
						                    var index = $('#tab-categories').dataTable().fnAddData( [ null, data.categorie[0]["country"], data.categorie[0]["annee"], null, laboratoire, data.categorie[0]["categorie"], data.categorie[0]["nb_produits"], data.categorie[0]["id"], null, data.categorie[0]["laboratoire_id"], data.categorie[0]["country_id"] ] );

						                    if ($( '#selectFilter-1 option[value="' + laboratoire + '"]' ).length == 0)
						                    {
						                    	$('#selectFilter-1 option').each(function(i)
												{
													if (i>0)
													{	
														var option = $(this).text();
														option = option.toLowerCase(); 
														var newOption = laboratoire.toLowerCase();
														if ( option > newOption) 
														{
														    $(this).before('<option value="' + laboratoire + '">' + laboratoire + '</option>');
														    return false;
														}
													}
												});
						                    }

						                    $( '.filter-column-1 option[value=""]' ).prop( 'selected', true ).trigger('change');
						                    $( '.filter-column-2 option[value=""]' ).prop( 'selected', true ).trigger('change');
						                    $( '.filter-column-3' ).val( '' );
						                    $( '.filter-column-3' ).trigger($.Event('keyup', { keycode: 13 }));
						                    $( '.filter-column-4 option[value=""]' ).prop( 'selected', true ).trigger('change');
						                    $( '.filter-column-5' ).val( data.categorie[0]["categorie"] );
						                    $( '.filter-column-5' ).focus();
						                    $( '.filter-column-5' ).trigger($.Event('keyup', { keycode: 13 }));
						                    
						                    $("td div.details-control", $('#tab-categories').dataTable().fnGetNodes( index )).click();

											//tableProduits.destroy();
						                }
					    			}
						    	});
		                    }, 
		            		function() { }
		            	);
				    });

				    // Action sur le bouton 'Enregistrer' du modal de duplication
				    $('#saveCopyButtonCategorie').click( function () {
				    	var message = '<p class="question">@lang("amadeo.save.question")</p>';
			    	
		            	confirmBox(
		            		"@lang('amadeo.categories.copy.title')", 
		            		message, 
		            		'@lang("amadeo.yes")',
        					'@lang("amadeo.no")',
		            		function()
		            		{
								// Récupération de la catégorie d'origine
								var select = table.row( $('#tab-categories tbody > tr.selected').first());
						    	
						    	// Récupération des ID produits de la catégorie d'origine
						    	var produits = [];
						    	$( '#tab-copy-categorie-produits tbody tr' ).each(function() {
				                	if ($('#tab-copy-categorie-produits').DataTable().row( $(this) ).data())
				                	{
				                		produits.push($('#tab-copy-categorie-produits').DataTable().row( $(this) ).data()[3]);
				                	}
				                });
								
						    	var params = {
									"_token": document.getElementsByName("_token")[0].value,
									"country": select.data()[10],
									"laboratoire": select.data()[9],
									"nom": document.getElementById("copyCategorieNom").value,
									"annee": document.getElementById("copyCategorieAnnee").value,
									"produits": produits,
									"especes": select.data()[8] != null ? select.data()[8].split("|") : null
								};
								
						    	$.ajax({
						    		dataType: 'json',
							        url: '{{ route("categorie-ajax.store") }}', 
							        type: "POST",
							        data: $.param(params),
					    			success: function(data) {
										if(data.errors) {
											var errors = jQuery.map(data.errors, function(el, i) {
												bootoast.toast({
													message: el[0],
													type: 'danger',
													timeout: 5
												});
											});
										}

						                if(data.success) {
						                	bootoast.toast({
												message: "@lang('amadeo.categories.add.ok')",
												type: 'success'
											});
						                	$('#copyCategorieModal').modal('hide');

						                    // Affichage du laboratoire
						                    var laboratoire = data.categorie[0]["laboratoire_id"] != null ? data.categorie[0]["laboratoire"] : "@lang('amadeo.categories.seller-multiple')";
						                    
											// Mise à jour du tableau
						                    var index = $('#tab-categories').dataTable().fnAddData( [ null, data.categorie[0]["country"], data.categorie[0]["annee"], data.categorie[0]["especes_noms"], laboratoire, data.categorie[0]["categorie"], data.categorie[0]["nb_produits"], data.categorie[0]["id"], data.categorie[0]["especes"], data.categorie[0]["laboratoire_id"], data.categorie[0]["country_id"] ] );
						                    $('#tab-categories tbody > tr.selected').first().removeClass('selected');

						                    $( '.filter-column-5' ).val( data.categorie[0]["categorie"] );
						                    $( '.filter-column-5' ).focus();
						                    $( '.filter-column-5' ).trigger($.Event('keyup', { keycode: 13 }));
						                    
						                    $("td div.details-control", $('#tab-categories').dataTable().fnGetNodes( index )).click();
						                }
					    			}
						    	});
		            		}, 
		            		function() { }
		            	);
				    });

				    // Action sur le bouton 'Supprimer'
				    $('#deleteButtonGeneral').click( function () {
				    	var ids = [];

				    	$('#tab-categories tbody > tr.selected').each(function() {
                        	ids.push(table.row( $(this) ).data()[7]);
                        });
                        
				    	if (ids.length > 0)
						{
					    	var message = '<p class="avertissement">@lang("amadeo.categories.delete.warning")</p><p class="question">@lang("amadeo.save.question")</p>';
				    	
			            	confirmBox(
			            		"@lang('amadeo.categories.delete.title')", 
			            		message, 
								'@lang("amadeo.yes")',
								'@lang("amadeo.no")',
			            		function()
			            		{
						    		// Création de l'URL
							    	var url = '{{ route("categorie-ajax.destroy", "id") }}';
							    	url = url.replace('id', ids);
								    	
							    	var params = {
										"_token": document.getElementsByName("_token")[0].value
									};
			
							    	$.ajax({
							    		dataType: 'json',
								        url: url, 
								        type: "DELETE",
								        data: $.param(params),
						    			success: function(data) {
						    				if (data)
						    				{
						    					bootoast.toast({
													message: "@lang('amadeo.categories.delete.ok')",
													type: 'success'
												});

										        // Mise à jour du tableau
										        table.rows('.selected').remove().draw( false );
						    				}
						    			}
							    	});
			            		}, 
			            		function() { }
			            	);
			            } else
			            {
		            		bootoast.toast({
								message: "@lang('amadeo.categories.delete.no-select')",
								type: 'warning'
							});
			            }
				    } );
			    @endif
			 
			    // Action sur le bouton "Rétablir"
			    $('#tab-categories tbody').on('click', 'div[id^="cancelButtonCategorie-"]', function () {
			    	var tr = $(this).closest('.child').prev();
			    	var row = table.row( tr );
				
		            var message = '<p class="avertissement">@lang("amadeo.categories.reset.warning")</p><p class="question">@lang("amadeo.save.question")</p>';
		    	
		            confirmBox(
	            		"@lang('amadeo.categories.update.title')", 
	            		message, 
						'@lang("amadeo.yes")',
						'@lang("amadeo.no")',
	            		function() 
	            		{
	            			// Mise à jour du tableau
				            $('#tab-categories').dataTable().fnUpdate(row.data(),tr,undefined,false);

				            tr.children().each(function(index) {
						    	if (index == 0)
						    	{
						    		$(this).html('<div class="details-control"></div>');
						    	} else if (index == 5)
						    	{
						    		$(this).addClass('width-50');
						    		$(this).html('<div class="texte" title="' + $(this).html() + '">' + $(this).html() + '</div>');
						    	} else if (index == 6)
						    	{
						    		$(this).html('<div class="nombre">' + $(this).html() + '</div>');
						    	} else
						    	{
						    		$(this).html('<div>' + $(this).html() + '</div>');
						    	}
						    });
						    loadDatatableProduits(row.data());
			            	loadDivCommentaires(row.data()[7]);
							createSelectForRow(tr, row.data()[7]);
					        row.child( '<div>' + format( row.data() ) + '</div>' ).show();
				            row.child().addClass('child');
	            		},
	            		function() { }
	            	);
			    } );

			    // Action sur le bouton "Enregistrer" d'une catégorie
			    $('#tab-categories tbody').on('click', 'div[id^="saveButtonCategorie-"]', function () {
			    	var tr = $(this).closest('.child');
			    	var trPrev = tr.prev();
			    	var rowPrevData = table.row( trPrev ).data();

			    	// Récupération des informations
			    	var id = rowPrevData[7];
			    	var nom = trPrev.children().find('input').eq(0).val();
			    	var especes = getCheckboxRadioValueByName('especes_' + id + '[]');
			    	var produits = [];
			    	$( '#tab-categorie-produits-' + id + ' tbody tr' ).each(function() {
                    	if ($('#tab-categorie-produits-' + id).DataTable().row( $(this) ).data())
                    	{
                    		produits.push({
                    			"categorie_id": id,
                    			"produit_id": $('#tab-categorie-produits-' + id).DataTable().row( $(this) ).data()[4]
                    		});
                    	}
                    });

                    var commentaires = [];
                    if ($( '#textarea-categorie-commentaires-' + id ).val() != "")
                    {
	                    commentaires.push({
	                		"user_id": '{{ Auth::user()->id }}', 
	                		"categorie_id": id,
	                		"commentaire": $( '#textarea-categorie-commentaires-' + id ).val(), 
	                		"date": formatDateStringToSql(formatDate(new Date()))
	                	});
                    }
			    	
		            var message = '<p class="question">@lang("amadeo.save.question")</p>';

		            confirmBox(
	            		"@lang('amadeo.categories.update.title')", 
	            		message, 
						'@lang("amadeo.yes")',
						'@lang("amadeo.no")',
	            		function() 
	            		{
	            			// Création de l'URL
					    	var url = '{{ route("categorie-ajax.update", "id") }}';
					    	url = url.replace('id', id);
					    	
					    	var params = {
								"_token": document.getElementsByName("_token")[0].value,
								"nom": nom,
								"especes": especes,
								"produits": produits,
								"commentaires": commentaires
							};

					    	// Enregistrement en base
					    	$.ajax({
					    		dataType: 'json',
						        url: url, 
						        type: "PUT",
						        data: $.param(params),
				    			success: function(data) {
				    				if (data)
				    				{	
				    					bootoast.toast({
											message: "@lang('amadeo.categories.update.ok')", 
											type: 'success'
										});
					                    
								        // Mise à jour du tableau
								        var especes = "";
										var isFirst = true;
										@foreach (Session::get('list_of_species') as $espece)
											if (params["especes"] != null && params["especes"].includes('{{ $espece->id }}'))
											{
												if (isFirst)
												{
													isFirst = false;
												} else {
													especes += ", ";
												}
												especes += '{{ $espece->nom }}';
											}
										@endforeach

					                    rowPrevData[5] = params["nom"];
									    trPrev.children().eq(4).attr('title', params["nom"]);
					                    rowPrevData[3] = especes;
								    	rowPrevData[6] = produits.length;
								    	rowPrevData[8] = params["especes"].join("|");

								    	$('#tab-categories').dataTable().fnUpdate(rowPrevData,trPrev,undefined,false);

								    	table.row( trPrev ).child.hide();
							            trPrev.children().each(function(index) {
									    	if (index == 0)
									    	{
									    		$(this).html('<div class="details-control"></div>');
									    	} else if (index == 5)
									    	{
									    		$(this).addClass('width-50');
									    		$(this).html('<div class="texte" title="' + $(this).html() + '">' + $(this).html() + '</div>');
									    	} else if (index == 6)
									    	{
									    		$(this).html('<div class="nombre">' + $(this).html() + '</div>');
									    	} else
									    	{
									    		$(this).html('<div>' + $(this).html() + '</div>');
									    	}
									    });
								    	trPrev.removeClass('shown');
				    				}
				    			}
					    	});
	            		}, 
	            		function() { }
	            	);
			    } );

		  	}
		});
	}
</script>