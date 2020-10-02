<script type="text/javascript">
	var tableCentrals = null;

	/* 
	* Formate le détail d'une ligne.
	*/
	function format ( row ) {
		// Calcul du nombre de colonnes
		var sizeTypesList = "{{sizeof(Session::get('list_of_types'))}}";
		var sizeSpeciesList = "{{sizeof(Session::get('list_of_species'))}}";
		var nbCol = Math.max(sizeTypesList, sizeSpeciesList);
		var idRow = row[8];

		// Types
		var types = row[12];
		var html_types = '<tr><td class="detail-row-title">@lang("amadeo.products.types")</td>';
		var liste_types = [];
		if (types != null)
		{
			liste_types = types.split("|")
		}
		@foreach (Session::get('list_of_types') as $type)
			var type_checked = false;
			if (liste_types.length > 0 && liste_types.includes('{{ $type->id }}'))
				type_checked = true;
			html_types += '<td><div class="checkbox-item-horizontal">' 
							+ '<div class="checkboxContainer">' 
								+ '<input id="type-' + idRow + '-{{ $type->id }}"';
			if (type_checked)
			{
				html_types += ' checked="checked"';
			}
			html_types += ' disabled name="types[]" type="checkbox" value="{{ $type->id }}">' 
								+ '<label for="type-' + idRow + '-{{ $type->id }}"></label>' 
							+ '</div>' 
							+ '<div class="checkboxLabel">' 
								+ '<label for="type-' + idRow + '-{{ $type->id }}">{{ $type->nom }}</label>' 
							+ '</div>' 
						+ '</div></td>';
		@endforeach
		if (sizeTypesList < nbCol)
		{
			for(var i=0;i<nbCol-sizeTypesList;i++) {
				html_types += '<td></td>';
			}
		}

		// Species
		var species = row[13];
		var html_species = '<tr><td class="detail-row-title">@lang("amadeo.products.species")</td>';
		var liste_species = [];
		if (species != null)
		{
			liste_species = species.split("|")
		}
		@foreach (Session::get('list_of_species') as $species)
			var species_checked = false;
			if (liste_species.length > 0 && liste_species.includes('{{ $species->id }}'))
				species_checked = true;
			html_species += '<td><div class="checkbox-item-horizontal">' 
							+ '<div class="checkboxContainer">' 
								+ '<input id="species-' + idRow + '-{{ $species->id }}"';
			if (species_checked)
			{
				html_species += ' checked="checked"';
			}
			html_species += ' disabled name="species[]" type="checkbox" value="{{ $species->id }}">' 
								+ '<label for="species-' + idRow + '-{{ $species->id }}"></label>' 
							+ '</div>' 
							+ '<div class="checkboxLabel">' 
								+ '<label for="species-' + idRow + '-{{ $species->id }}">{{ $species->nom }}</label>' 
							+ '</div>' 
						+ '</div></td>';
		@endforeach
		if (sizeSpeciesList < nbCol)
		{
			for(var i=0;i<nbCol-sizeSpeciesList;i++) {
				html_species += '<td></td>';
			}
		}
		html_species += '</tr>';

		// Affichage des centrales
		var html_centrals = '<tr><td class="detail-row-title" style="vertical-align: top; width: 15%;">@lang("amadeo.products.sources")<br /><br /><div class="checkbox-item-horizontal"><div class="checkboxContainer"><input id="mask-obsolete-' + idRow + '" name="mask-obsolete-' + idRow + '" type="checkbox" value="1"><label for="mask-obsolete-' + idRow + '"></label></div><div class="checkboxLabel"><label for="mask-obsolete-' + idRow + '">@lang("amadeo.products.show-obsolete")</label></div></div></td><td colspan="' + nbCol + '" class="detail-row-subTable" style="max-width: 80vh; border-right: 1px solid var(--dark-grey);""><div id="load-product-centrals-' + idRow + '">{{ Html::image("images/activity_indicator.gif", \Lang::get("amadeo.load")) }}</div><div id="tableau-product-centrals-' + idRow + '" class="tableau" style="display:none"><table id="tab-product-centrals-' + idRow + '" class="" cellspacing="0" width="100%"><thead><tr><th>@lang("amadeo.products.country")</th><th>@lang("amadeo.products.source")</th><th>@lang("amadeo.products.source-code")</th><th>@lang("amadeo.products.source-name")</th><th>@lang("amadeo.products.source-price")</th></tr></thead></table></div></td></tr>';

		var html = '<table class="detail-row">'+ html_types + html_species + html_centrals + '</table>';

		return html;
	}

	function loadDatatableCentrals(rowData, json)
	{
		var data = jQuery.map(json, function(el, i) {
			return [[el.country, el.centrale_nom, el.code_produit, el.denomination, el.prix_unitaire, el.cp_id, el.obsolete, el.date_obsolescence, el.date_denomination, el.date_prix_unitaire]];
		});

		var idRow = rowData[8];

		var date = new Date();
		var firstDay = new Date(date.getFullYear(), date.getMonth(), 1);
		var hasObsoleteCode = false;

		// DataTable
		tableCentrals = $('#tab-product-centrals-' + idRow).DataTable( {
			"language": {
				"url": "//cdn.datatables.net/plug-ins/1.10.16/i18n/French.json"
			},
			"bSortCellsTop": true,
			"bLengthChange": false,
			"bAutoWidth": false,
			"info": false,
			"paging": false,
			"searching": true,
			"sScrollY": "50vh",
			"bScrollCollapse": true,
			"order": [[ 0, "asc" ], [ 1, "asc" ], [ 2, "asc" ]],
			"aoColumns": [ 
				null, 
				{ "render": function ( data, type, row ) { 
					return data.capitalize(); 
				}} ,
				{ "render": function ( data, type, row ) { 
					return (data != null) ? data + ((row[7] != null) ? ' (' + row[7] + ')' : '') : '-'; 
				}} , 
				{ "render": function ( data, type, row ) { 
					return (data != null) ? data + ((row[8] != null && row[8] != firstDay) ? ' <i>(' + row[8] + ')</i>' : '') : '-'; 
				}} , 
				{ "render": function ( data, type, row ) { 
					var dataResult = (data != null) ? (numberWithSpaces(parseFloat(data.replace( /,/, "." ).replace( / /g, "" )).toFixed(2))) : '-';

					return (data != null) ? (dataResult + ' €'+ ((row[9] != null && row[9] != firstDay) ? ' <i>(' + row[9] + ')</i>' : '')) : dataResult;
				}}
			],
			"aaData": data,
			"createdRow": function ( row, data, index ) {
				$('td', row).each(function(){
					$(this).html('<div>' + $(this).html() + '</div>');
				});

				$('td', row).eq(0).addClass('width-10');
				$('td', row).eq(0).find('div').addClass('texte');
				$('td', row).eq(1).addClass('width-10');
				$('td', row).eq(1).find('div').addClass('texte');
				$('td', row).eq(1).addClass('width-10');
				$('td', row).eq(2).find('div').addClass('texte');
				$('td', row).eq(2).find('div').attr('title', $('td', row).eq(2).find('div').html());
				$('td', row).eq(2).addClass('width-20');
				$('td', row).eq(3).find('div').addClass('texte');
				$('td', row).eq(3).find('div').attr('title', $('td', row).eq(3).find('div').html().replace( '<i>', "" ).replace( '</i>', "" ));
				$('td', row).eq(4).find('div').addClass('nombre');
				$('td', row).eq(4).find('div').attr('title', $('td', row).eq(4).find('div').html().replace( '<i>', "" ).replace( '</i>', "" ));

				if (data[6])
				{
					hasObsoleteCode = true;
					$('td', row).eq(0).find('div').addClass('obsolete');
					$('td', row).eq(1).find('div').addClass('obsolete');
					$('td', row).eq(2).find('div').addClass('obsolete');
					if (data[7] != null)
						$('td', row).eq(2).find('div').attr('title', data[7]);
					$('td', row).eq(3).find('div').addClass('obsolete');
					$('td', row).eq(4).find('div').addClass('obsolete');
				}
			},
			initComplete: function () {
				document.getElementById('tableau-product-centrals-' + idRow).style.display = "block";
				document.getElementById('load-product-centrals-' + idRow).style.display = "none";
				// Suppression de la recherche globale du tableau
				document.getElementById('tab-product-centrals-' + idRow + '_filter').style.display = "none";

				tableCentrals.columns.adjust();

				$('#mask-obsolete-' + idRow).on('change', function() {
					$.fn.dataTable.ext.search.push(
						function( settings, searchData, index, rowData, counter ) {
							if ( !$('#mask-obsolete-' + idRow).is(':checked') )
								return !rowData[6];
							else 
								return true;
						}     
					);
					$('#tab-product-centrals-' + idRow).DataTable().draw();
					$.fn.dataTable.ext.search.pop();
				});

				if (!hasObsoleteCode)
				{
					$('#mask-obsolete-' + idRow).prop( "disabled", "disabled" );
					$('#mask-obsolete-' + idRow).parent().parent().addClass("disable");
				} else 
				{
					$('#mask-obsolete-' + idRow).prop( "checked", rowData[10] ).trigger("change");
				}
			}
		});

	}

	/*
	* Charge le tableau des produits.
	*/
	function loadAjaxFormProduits()
	{
		$.ajax({
		    url: "{{ route('produit-ajax.index') }}", 
		    success: function(json) {
		    	var data = jQuery.map(json, function(el, i) {
				  return [[null, el.countries, el.laboratoire, el.denomination, el.conditionnement, el.code_gtin, el.valo_euro, el.valo_volume, el.id, el.classe_therapeutique, el.obsolete, el.unite_valo_volume, el.types_id, el.especes_id, el.invisible]];
				});

			    // DataTable
			    var table = $('#tab-produits').DataTable( {
			    	"language": {
		              "url": "//cdn.datatables.net/plug-ins/1.10.16/i18n/French.json",
		            	"decimal": ",",
            			"thousands": " "
		            },
		            "bSortCellsTop": true,
		            "bLengthChange": false,
		            "bAutoWidth": false,
		            "scrollY": "60vh",
		            "paging": true,
					"pageLength": 100,
					"deferRender": true,
		            "info": false,
        			"scrollCollapse": true,
		            "columnDefs": [ {
						"targets": 0,
						"orderable": false
					} ],
					"order": [[ 2, "asc" ], [ 3, "asc" ], [ 4, "asc" ]],
					"aoColumns": [ 
						null, 
						null,
						null, 
						{"render": function ( data, type, row ) {
			                  return data + (row[14] ? ' (invisible)' : '');
			            	}
			            },  
						null, 
						null, 
						{ "render": function ( data, type, row ) {
			                  return (data != null) ? (numberWithSpaces(parseFloat(data.replace( /,/, "." ).replace( / /g, "" )).toFixed(2)) + ' €') : '-';
			            	}, 
			            	"sType": "numeric-comma"
			            }, 
		            	{ "render": function ( data, type, row ) {
			                  return (data != null) ? (numberWithSpaces(parseFloat(data.replace( /,/, "." ).replace( / /g, "" )).toFixed(2)) + (row[11] != null ? ' ' + row[11] : '')) : '-';
			            	}, 
			            	"sType": "numeric-comma"
			            }
			        ],
					"aaData": data,
					"createdRow": function ( row, data, index ) {
						$('td', row).each(function(){
							$(this).html('<div>' + $(this).html() + '</div>');
						});

						$('td', row).eq(2).addClass('width-10');
						$('td', row).eq(2).find('div').addClass('texte');
						$('td', row).eq(2).find('div').attr('title', $('td', row).eq(2).find('div').html());
						$('td', row).eq(3).addClass('width-30');
						$('td', row).eq(3).find('div').addClass('texte');
						$('td', row).eq(3).find('div').attr('title', $('td', row).eq(2).find('div').html());
						$('td', row).eq(4).addClass('width-30');
						$('td', row).eq(4).find('div').addClass('texte');
						$('td', row).eq(4).find('div').attr('title', $('td', row).eq(3).find('div').html());
						$('td', row).eq(6).find('div').addClass('nombre');
						$('td', row).eq(6).find('div').css('padding-left', '5px');
						$('td', row).eq(6).find('div').css('padding-right', '5px');
						$('td', row).eq(7).addClass('width-5');
						$('td', row).eq(7).find('div').addClass('texte');

						if (data[10] || data[14])
						{
							$('td', row).eq(0).find('div').addClass('details-control-obsolete');
							$('td', row).eq(1).find('div').addClass('obsolete');
							$('td', row).eq(2).find('div').addClass('obsolete');
							$('td', row).eq(3).find('div').addClass('obsolete');
							$('td', row).eq(4).find('div').addClass('obsolete');
							$('td', row).eq(5).find('div').addClass('obsolete');
							$('td', row).eq(6).find('div').addClass('obsolete');
							$('td', row).eq(7).find('div').addClass('obsolete');
						} else
						{
							$('td', row).eq(0).find('div').addClass('details-control');
						}
			        },
					initComplete: function () {
			            var api = this.api();
			            $('#tab-produits thead tr#forFilters th').each(function(i) {
			            	var column = api.column(i);
			            	if ($(this).hasClass('select-filter')) 
			                {
			                    // Création des select pour les colonnes de classe "select-filter"
			                	var select = $('<select style="width:100%;"><option value=""></option></select>').on('change', function() {
			                        var val = $.fn.dataTable.util.escapeRegex($(this).val());
			                        column.search(val ? '^' + val + '$' : '', true, false).draw();
			                    });
			                    $('#forFilters th').eq(i).html(select);
			                    column.data().unique().sort().each(function(d, j) {
			                        if (d != null)
                    					select.append('<option value="' + d + '">' + d + '</option>')
			                    });
			                } else if ($(this).hasClass('text-filter'))
			    			{
			    				// Création des input pour les colonnes de classe "text-filter"
			                	var input = $('<input type="text" placeholder="@lang("amadeo.input.message")" style="width:100%;" />').on('keyup', function() {
			    					var val = $.fn.dataTable.util.escapeRegex($(this).val());
			    					column.search(val ? val : '', true, false).draw();
			    				});
			    				$('#forFilters th').eq(i).html(input);
			    			}
			            });

						document.getElementById('tableau').style.display = "block";
				        document.getElementById('load').style.display = "none";
			            // Suppression de la recherche globale du tableau
					    document.getElementById('tab-produits_filter').style.display = "none";

					    table.columns.adjust();
					}
			    });

			    // Ouverture et fermeture du détail d'un produit
			    $('#tab-produits tbody').on('click', 'td div[class^="details-control"]', function () {
			    	var tr = $(this).closest('tr');
			        var row = table.row( tr );
					var isObsolete = row.data()[10] || row.data()[14];
			       
			        if ( row.child.isShown() ) {
			            // This row is already open - close it
			            row.child.hide();

			            // Mise à jour du tableau
						$('#tab-produits').dataTable().fnUpdate(row.data(),tr,undefined,false);

			            tr.children().each(function(index) {
					    	if (index == 0)
					    	{
					    		$(this).html('<div class="details-control' + (isObsolete ? '-obsolete' : '') + '"></div>');
					    	} else if (index == 2)
					    	{
					    		$(this).addClass("width-10");
					    		$(this).html('<div title="' + $(this).html() + '" class="texte' + (isObsolete ? ' obsolete' : '') + '">' + $(this).html() + '</div>');
					    	} else if (index == 3 || index == 4)
					    	{
					    		$(this).addClass("width-30");
					    		$(this).html('<div title="' + $(this).html() + '" class="texte' + (isObsolete ? ' obsolete' : '') + '">' + $(this).html() + '</div>');
					    	} else if (index == 6)
					    	{
								$(this).html('<div class="nombre' + (isObsolete ? ' obsolete' : '') + '" style="padding-left: 5px; padding-right: 5px;">' + $(this).html() + '</div>');
					    	} else if (index == 7)
					    	{
					    		$(this).addClass("width-5");
					    		$(this).html('<div title="' + $(this).html() + '" class="texte' + (isObsolete ? ' obsolete' : '') + '">' + $(this).html() + '</div>');
					    	}
 							else
					    	{
					    		$(this).html('<div' + (isObsolete ? ' class="obsolete"' : '') + '>' + $(this).html() + '</div>');
					    	}
					    });
						
			            tr.removeClass('shown');
			        }
			        else {
						// Open this row
						var params = {
							"_token": document.getElementsByName("_token")[0].value
						};

						// Création de l'URL
						var idRow = row.data()[8];
						var url = '{{ route("produit-ajax.show", "id") }}';
						url = url.replace('id', idRow);
						
						$.ajax({
							dataType: "json",
							url: url, 
							data: $.param(params),
							success: function(data) {
								row.child( '<div>' + format( row.data() ) + '</div>' ).show();
								if (row.data()[10])
								{
									row.child().addClass('child-obsolete');
								} else
								{
									row.child().addClass('child');
								}
								tr.addClass('shown');

								loadDatatableCentrals(row.data(), data);
							}
						});
			        }
			    } );
		  	}
		});
	}
 </script>