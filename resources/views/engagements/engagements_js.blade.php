<script type="text/javascript">

	/* 
	* Formate le détail d'une ligne.
	*/
	function format ( rowData ) {
		// Produits
		var html_produits = "<tr><td style='vertical-align: top;'>Produits</td>" + "<td style='vertical-align: top;'>:</td>" + "<td colspan='3' style='max-width: 80vh;'><table id='tab-engagement-produits-" + rowData[11] + "' class='display' cellspacing='0' width='100%'><thead><tr><th>Dénomination</th><th>Conditionnement</th><th>Code GTIN</th></tr></thead></table></td><td style='display:flex;' class='flex-column'>";

		var html = '<table class="detail-row" style="width: 100%;">'+ html_produits + '</table>';

		return html;
	}

	/*
	* Met en forme le tableau des produits d'un objectif.
	*/
	function loadDatatableProduits(rowData)
	{
		var idRow = rowData[11];
    	// Récupération des informations
    	var params = {
			"_token": document.getElementsByName("_token")[0].value,
			"objectif": rowData[13]
		};

		$.ajax({
		    url: "{{ route('objectif-produit-ajax.index') }}", 
	        data: $.param(params),
		    success: function(json) {
		    	var data = jQuery.map(json, function(el, i) {
				  return [[el.denomination, el.conditionnement, el.code_gtin, el.obj_prod_id, el.obsolete]];
				});

			    // DataTable
			    var tableProduits = $('#tab-engagement-produits-' + idRow).DataTable( {
			    	"language": {
		              "url": "//cdn.datatables.net/plug-ins/1.10.16/i18n/French.json"
		            },
		            "bSortCellsTop": true,
		            "info": false,
		            "paging": false,
		            "searching": false,
		            "sScrollY": "50vh",
		            "bScrollCollapse": true,
		            "order": [[ 0, "asc" ], [ 1, "asc" ]],
					"aoColumns": [ null, null, null ],
					"pageLength": 25,
					"aaData": data,
					"createdRow": function ( row, data, index ) {
						if (data[6])
						{
							$('td', row).eq(0).addClass('obsolete');
							$('td', row).eq(1).addClass('obsolete');
							$('td', row).eq(2).addClass('obsolete');
							$('td', row).eq(3).addClass('obsolete');
							$('td', row).eq(4).addClass('obsolete');
							$('td', row).eq(4).html('-');
						}
			        }
			    });
			}
	    });
	}

	/*
	* Charge le tableau des catégories.
	*/
	function loadAjaxFormEngagements()
	{
		var params = {
			"_token": document.getElementsByName("_token")[0].value
		};

		var dateMAJ = "{{ Session::get('last_date') }}".split("-");
		var dateJour = new Date();
		dateJour.setFullYear({{date('Y')}}, dateMAJ[1]-1, (dateMAJ[2] > 15 ? 15 : 1));

		$.ajax({
		    url: "{{ route('engagement-ajax.index') }}",
		    data: $.param(params),
		    success: function(json) {
		    	var data = jQuery.map(json, function(el, i) {
		    		// Espèces
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

					// Calcul des écarts
					var jour;
					var totalJours = days_of_a_year({{date('Y')}});
					if (el.annee == {{date('Y')}})
					{
						jour = getDayOfYear(dateJour);
					}
					else
					{
						jour = totalJours;
					}
					var ecartEuros = (el.ca_periode - (el.valeur_eng * jour / totalJours)).toFixed(2);
					var ecartPourcents = (el.valeur_eng != '' && el.valeur_eng != 0) ? ((ecartEuros * 100) / (el.valeur_eng * jour / totalJours)) : "-";
					
					return [[ null, el.annee, especes, el.laboratoire, el.categorie, [el.type_obj, el.objectif, el.valeur_obj], (el.valeur_eng).replace( /\./, "," ), numberWithSpaces(ecartEuros), numberWithSpaces(ecartPourcents), numberWithSpaces(el.ca_periode), numberWithSpaces(el.ca_periode_prec), el.id, el.especes, el.objectif_id ]];
				});

			    // DataTable
			    var table = $('#tab-engagements').DataTable( {
			    	"language": {
		              "url": "//cdn.datatables.net/plug-ins/1.10.16/i18n/French.json"
		            },
		            "bSortCellsTop": true,
		            "columnDefs": [ {
						"targets": 0,
						"orderable": false
					} ],
					"order": [[ 1, "desc" ], [ 3, "asc" ], [ 4, "asc" ], [ 5, "asc" ]],
					"aoColumns": [ 
						null, 
   						null, 
						null, 
						null, 
						null, 
						{ "render": function ( data, type, row ) {
							var type = '';
							if (data[0] == 1)
							{
								type = 'S / ';
							} else if (data[0] == 2)
							{
								type = 'P / ';
							}
							return type + data[1] + ' (' + numberWithSpaces(data[2]) + ')';
						}},
						{ "render": function ( data, type, row ) {
							return numberWithSpaces(data);
						}, "sType": "numeric-comma" },
						{ "render": function ( data, type, row ) {
							return data;
						}, "sType": "numeric-comma" }, 
						{ "render": function ( data, type, row ) {
							if (data != '-')
							{
								return (data != null) ? numberWithSpaces(parseFloat(data.replace( /,/, "." ).replace( / /g, "" )).toFixed(2))+' %' : '-';
							}
							else
								return data;
						}, "sType": "numeric-comma" },
						{ "render": function ( data, type, row ) {
							return data;
						}, "sType": "numeric-comma" }, 
						{ "render": function ( data, type, row ) {
							return data;
						}, "sType": "numeric-comma" }
					],
					"pageLength": 25,
					"aaData": data,
					"createdRow": function ( row, data, index ) {
						// Affichage du bouton 'Détail'
						$('td', row).eq(0).addClass('details-control');
						$('td', row).eq(1).addClass('annee');
						$('td', row).eq(4).addClass('texte width-20');
						$('td', row).eq(4).attr('title', $('td', row).eq(4).html());
						$('td', row).eq(5).addClass('texte width-30');
						$('td', row).eq(5).attr('title', $('td', row).eq(5).html());
						
						@if ((sizeof(Auth::user()->roles) >0) AND ("Vétérinaire" == Auth::user()->roles[0]['nom']))
							// Affichage des valeurs modifiables pour les vétérinaires
							var inputValeur = $('<input type="text" id="row-' + index + '-valeur" value="' + data[6] + '" />').on('keyup', function() {
								var valeur = $(this).val().replace( /,/, "." );
								// Calcul des écarts
								var jour;
								var totalJours = days_of_a_year({{date('Y')}});
								if (data[1] == {{date('Y')}})
								{
									jour = getDayOfYear(dateJour);
								}
								else
								{
									jour = totalJours;
								}
								var ecartEuros = (data[9].replace( /,/, "." ).replace( / /g, "" ) - (valeur * jour / totalJours)).toFixed(2);
								var ecartPourcents = (valeur != '' && valeur != 0) ? ((ecartEuros * 100) / (valeur * jour / totalJours)) : "-";
								data[7] = numberWithSpaces(ecartEuros);
								data[8] = numberWithSpaces(ecartPourcents);
								
								$(this).parent().parent().children().eq(7).html(data[7] + ' €');
								if (data[7].replace( /,/, "." ).replace( / /g, "" ) > 0)
								{
									$(this).parent().parent().children().eq(7).addClass('positif');
									$(this).parent().parent().children().eq(7).removeClass('negatif');
								} else if (data[7].replace( /,/, "." ).replace( / /g, "" ) < 0)
								{
									$(this).parent().parent().children().eq(7).addClass('negatif');
									$(this).parent().parent().children().eq(7).removeClass('positif');
								} else
								{
									$(this).parent().parent().children().eq(7).removeClass('negatif');
									$(this).parent().parent().children().eq(7).removeClass('positif');
								}
								
								var htmlEcartPourcent;
								if (ecartPourcents != '-')
									htmlEcartPourcent = (ecartPourcents != null) ? numberWithSpaces(ecartPourcents.toFixed(2)).replace( /\./, "," )+' %' : '-';
								else
									htmlEcartPourcent = ecartPourcents;

								$(this).parent().parent().children().eq(8).html(htmlEcartPourcent);
								if (htmlEcartPourcent.replace( /,/, "." ).replace( / /g, "" ).replace( /%/, "" ) > 0)
								{
									$(this).parent().parent().children().eq(8).addClass('positif');
									$(this).parent().parent().children().eq(8).removeClass('negatif');
								} else if (htmlEcartPourcent.replace( /,/, "." ).replace( / /g, "" ).replace( /%/, "" ) < 0)
								{
									$(this).parent().parent().children().eq(8).addClass('negatif');
									$(this).parent().parent().children().eq(8).removeClass('positif');
								} else
								{
									$(this).parent().parent().children().eq(8).removeClass('negatif');
									$(this).parent().parent().children().eq(8).removeClass('positif');
								}
						
							});
							$('td', row).eq(6).html(inputValeur);
						@endif

						$('td', row).eq(6).addClass('nombre');
						$('td', row).eq(7).addClass('nombre' + ($('td', row).eq(7).html().replace( /,/, "." ).replace( / /g, "" ).replace( /€/, "" ) > 0 ? ' positif' : ($('td', row).eq(7).html().replace( /,/, "." ).replace( / /g, "" ).replace( /€/, "" ) < 0 ? ' negatif' : '')));
						$('td', row).eq(8).addClass('nombre' + ($('td', row).eq(8).html().replace( /,/, "." ).replace( / /g, "" ).replace( /%/, "" ) > 0 ? ' positif' : ($('td', row).eq(8).html().replace( /,/, "." ).replace( / /g, "" ).replace( /%/, "" ) < 0 ? ' negatif' : '')));
						$('td', row).eq(9).addClass('nombre');
						$('td', row).eq(10).addClass('nombre');
			        },
					initComplete: function () {
			            var api = this.api();
			            $('#tab-engagements thead tr#forFilters th').each(function(i) {
			            	var column = api.column(i);
			            	if ($(this).hasClass('select-filter')) 
			                {
			                    // Création des select pour les colonnes de classe "select-filter"
			                	var select = $('<select id="selectFilter-'+ i + '" style="width:100%;"><option value=""></option></select>').on('change', function() {
			                        var val = $.fn.dataTable.util.escapeRegex($(this).val());
			                        column.search(val ? '^' + val + '$' : '', true, false).draw();
			                    });
			                    $('#tab-engagements thead tr#forFilters th').eq(i).html(select);
			                    column.data().unique().sort().each(function(d, j) {
			                        select.append('<option value="' + d + '">' + d + '</option>')
			                    });
			                } else if ($(this).hasClass('text-filter'))
			    			{
			    				// Création des input pour les colonnes de classe "text-filter"
			                	var input = $('<input type="text" placeholder="" style="width:100%;" />').on('keyup', function() {
			    					var val = $.fn.dataTable.util.escapeRegex($(this).val());
			    					column.search(val ? val : '', true, false).draw();
			    				});
			    				$('#tab-engagements thead tr#forFilters th').eq(i).html(input);
			    			}
			            });

						document.getElementById('tableau').style.display = "block";
				        document.getElementById('load').style.display = "none";
			            // Suppression de la recherche globale du tableau
					    document.getElementById('tab-engagements_filter').style.display = "none";
					}
			    });

			    // Ouverture et fermeture du détail d'un engagement
			    $('#tab-engagements tbody').on('click', 'td.details-control', function () {
			    	var tr = $(this).closest('tr');
			        var row = table.row( tr );
					var idRow = row.data()[11];
			       
			        if ( row.child.isShown() ) {
			            // This row is already open - close it
			            row.child.hide();

			            // Mise à jour du tableau
				    	tr.removeClass('shown');

			        }
			        else {
			            // Open this row
			            $( this ).find('img').attr("src", "/images/table_details_close.png");
			            row.child( format(row.data()) ).show();
			            loadDatatableProduits(row.data());
						tr.addClass('shown');
			        }
			    } );

			    @if ((sizeof(Auth::user()->roles) >0) AND ("Vétérinaire" == Auth::user()->roles[0]['nom']))
				    $('.tableau').on('click', '#cancelButtonGeneral', function () {
				    	var message = '<p><font size="6" style="vertical-align:middle;">&#9888;</font> Toutes les informations non sauvegardées seront perdues.</p><p>@lang("amadeo.save.question")</p>';
				    	
				    	$.confirm({
				            'title'     : "Modification des engagements",
				            'message'   : message,
				            'buttons'   : {
				                '@lang("amadeo.yes")'   : {
				                    'action': function(){
				                    	$('#tab-engagements tbody tr').each(function(index) {
											$(this).find('input').eq(0).val(table.row( $(this) ).data()[6]);
								    	});
				                    }
				                },
				                '@lang("amadeo.no")'    : { 'action': function(){} }
				            }
				        });					    	
				    } );

				    $('.tableau').on('click', '#saveButtonGeneral', function () {
				    	var message = '<p>@lang("amadeo.save.question")</p>';
			    	
				    	$.confirm({
				            'title'     : "Modification des engagements",
				            'message'   : message,
				            'buttons'   : {
				                '@lang("amadeo.yes")'   : {
				                    'action': function(){
				                    	var engagements = [];
								    	$('#tab-engagements tbody tr').each(function(index) {
								    		var valeur = $(this).find('input').eq(0).val();
											engagements.push({
												"id": table.row( $(this) ).data()[11], 
												"objectif": table.row( $(this) ).data()[13],  
												"type_objectif": table.row( $(this) ).data()[5][0], 
												"valeur": (valeur != null && valeur != '') ? valeur.replace( /,/, "." ) : 0
											});
								    	});
								    		
								    	var params = {
											"_token": document.getElementsByName("_token")[0].value,
											"engagements": engagements
										};
	    	
								    	$.ajax({
								    		dataType: 'json',
									        url: '{{ route("engagement-ajax.store") }}',
									        type: "POST",
									        data: $.param(params),
							    			success: function(data) {
							    				if (data)
							    				{
							    					$('#success-message p').html('<strong>Succès !</strong> Les engagements ont été modifiés.');
								                    $('#success-message').removeClass('hide');
								                    var int = setInterval(function(){ 
								                        $('#success-message').addClass('hide');
								                        clearInterval(int);
								                    }, 3000);

													$('#tab-engagements tbody tr').each(function(index) {
														var valeur = $(this).find('input').eq(0).val();
														var rowData = table.row( $(this) ).data();
														rowData[6] = valeur.replace( /,/, "." );
											    	});
							    				}
							    			}
							    		});
				                    }
				                },
				                '@lang("amadeo.no")'    : { 'action': function(){} }
					        }
				        });
				    } );
				@endif
		  	}
		});
	}

</script>