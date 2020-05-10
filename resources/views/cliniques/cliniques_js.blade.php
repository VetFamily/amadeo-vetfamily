<script type="text/javascript">
	/* 
	* Formate le détail d'une ligne.
	*/
	function format ( row, clinic ) {
		var nbCol = 8;
		var idRow = row[7];
		
		// Affichage des codes pour chaque centrale
		var off_web_centrals = JSON.parse(clinic["infos_hors_web"]);
		var web_centrals = JSON.parse(clinic["infos_web"]);
		var html_centrals = '<tr class="tr_centrales">';
		var html_centralsWeb = '<tr class="tr_centrales">';
		for(var i=0;i<off_web_centrals.length;i++) {
			if ((i == 4) || (i == 8))
			{
				html_centrals += '</tr><tr class="tr_centrales">';
				html_centralsWeb += '</tr><tr class="tr_centrales">';
			}
			var off_web_central = off_web_centrals[i];
			var off_web_codes = off_web_central["identifiant"];
			var centId = off_web_central["centrale_id"];
			var web_central = web_centrals[i];
			var web_codes = web_central["identifiant"];
			html_centrals += '<td class="detail-row-title" style="width: 10%;">' + off_web_central["centrale_nom"] + '</td><td style="width: 10%;">' + off_web_codes.replace(/\|/g, ' ; ');
			html_centralsWeb += '<td class="detail-row-title" style="width: 10%;">' + web_central["centrale_nom"] + ' (web)</td><td style="width: 10%;">' + web_codes.replace(/\|/g, ' ; ');
			// Affichage du bouton pour l'administrateur
			@if ((sizeof(Auth::user()->roles) >0) AND ("Administrateur" == Auth::user()->roles[0]['nom']))
				html_centrals += (off_web_codes != '' && off_web_codes != ' ' ? '<br>' : '') + '<input type="text" placeholder="@lang("amadeo.clinics.add.code")" id="central-code-' + idRow + '-' + centId + '" data-centid="' + centId + '" data-web="false" ' + (off_web_codes != '' && off_web_codes != ' ' ? 'style="margin-top: 5px;" ' : '') + '/>';
				html_centralsWeb += (web_codes != '' && web_codes != ' ' ? '<br>' : '') + '<input type="text" placeholder="@lang("amadeo.clinics.add.code")" id="central-code-' + idRow + '-' + centId + '-web" data-centid="' + centId + '" data-web="true" ' + (web_codes != '' && web_codes != ' ' ? 'style="margin-top: 5px;" ' : '') + '/>';
				//nbCol++;
			@endif
			html_centrals += '</td>';
			html_centralsWeb += '</td>';
		}
		html_centrals += '</tr>' + html_centralsWeb + '</tr>';

		// Restricted users
		var users = clinic["utilisateurs"];
		var html_restricted_users = '<tr><td class="detail-row-title" style="vertical-align: top; width: 10%;">@lang("amadeo.clinics.user-access")</td><td colspan="' + (nbCol-1) + '">' + (users != null ? users : '@lang("amadeo.clinics.user-access-none")') + '</td></tr>';

		// Affichage du commentaire
		var comment = clinic["commentaire"];
		var html_comment = '<tr><td class="detail-row-title" style="vertical-align: top; width: 10%;">@lang("amadeo.clinics.comments")</td><td colspan="' + (nbCol-1) + '"><textarea id="textarea-clinic-comment-' + idRow + '" rows="4" placeholder="@lang("amadeo.textarea.message")">' + (comment != null ? comment : '') + '</textarea></td></tr>';

		var html_buttons = "";
		@if ((sizeof(Auth::user()->roles) >0) AND ("Administrateur" == Auth::user()->roles[0]['nom']))
			html_buttons += '<tr><td colspan="' + nbCol + '"><div class="detail-row-buttons">'
			+ '<div id="cancelButtonClinic-' + idRow + '" class="button"><a>@lang("amadeo.reset")</a><span class="btn_cancel"></span></div>'
			+ '<div id="saveButtonClinic-' + idRow + '" class="button button_bold"><a>@lang("amadeo.save")</a><span class="btn_save"></span></div>'
			+ '</div></td></tr>';
		@endif

		var html = '<table class="detail-row">'+ html_centrals + html_restricted_users + html_comment + html_buttons + '</table>';

		return html;
	}
	
	/*
	* Ajoute des select pour la clinique, les vétérinaires et la ville.
	*/
	function createSelectForRow(tr, idRow)
	{
		tr.find('div').each(function(index) {
	    		
	    	if (index > 0)
	    	{
	    		var value = $(this).html();
	    		$(this).html($('<input type="text" id="row-' + idRow + '-' + index + '" value="' + value + '" />'));
	    	}
	    });
	}

	/*
	* Charge le tableau des cliniques.
	*/
	function loadAjaxFormCliniques()
	{
    	$.ajax({
		    url: "{{ route('clinic-ajax.index') }}", 
		    success: function(json) {
		    	var data = jQuery.map(json, function(el, i) {
				  return [[null, el.veterinaires, el.clinique, el.adresse, el.code_postal, el.ville, el.date_entree, el.clinique_id]];
				});

			    // DataTable
			    var table = $('#tab-clinics').DataTable( {
			    	"language": {
		            	"url": "//cdn.datatables.net/plug-ins/1.10.16/i18n/French.json",
                		"emptyTable": '@lang("amadeo.clinics.search-empty")'
		            },
		            "bSortCellsTop": true,
					"destroy": true,
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
					"order": [[ 1, "asc" ]],
					"aoColumns": [ 
						{"sWidth": "4%"}, 
						{"sWidth": "10%"}, 
						{"sWidth": "20%"},  
						{"sWidth": "12%"}, 
						{"sWidth": "5%"}, 
						{"sWidth": "15%"}, 
						{"sWidth": "10%"} 
					],
					"aaData": data,
					"createdRow": function ( row, data, index ) {
						$('td', row).each(function(){
							$(this).html('<div>' + $(this).html() + '</div>');
						});

						$('td', row).eq(1).addClass('width-10');
						$('td', row).eq(1).find('div').addClass('texte');
						$('td', row).eq(2).addClass('width-20');
						$('td', row).eq(2).find('div').addClass('texte');
						$('td', row).eq(3).addClass('width-10');
						$('td', row).eq(3).find('div').addClass('texte');
						$('td', row).eq(4).addClass('width-5');
						$('td', row).eq(4).find('div').addClass('texte');
						$('td', row).eq(5).addClass('width-10');
						$('td', row).eq(5).find('div').addClass('texte');

						// Affichage du bouton 'Détail' si administrateur ou pour la clinique du vétérinaire
						@if ((sizeof(Auth::user()->roles) >0) AND ("Laboratoire" != Auth::user()->roles[0]['nom']))
							$('td', row).eq(0).find('div').addClass('details-control');
							@if ((sizeof(Auth::user()->roles) >0) AND ("Vétérinaire" == Auth::user()->roles[0]['nom']))
							// Open this row
							var tr = $('td', row).eq(0).closest('tr');
							var nRow = this.api().row( tr );
							var params = {
								"_token": document.getElementsByName("_token")[0].value
							};

							// Création de l'URL
							var idRow = row.data()[7];
							var url = '{{ route("clinic-ajax.show", "id") }}';
							url = url.replace('id', idRow);
							
							$.ajax({
								dataType: "json",
								url: url, 
								data: $.param(params),
								success: function(data) {
									var clinic = data.clinic[0];

									nRow.child( '<div>' + format( nRow.data(), clinic ) + '</div>' ).show();
									nRow.child().addClass('child');
									tr.addClass('shown');
								}
							});
						    @endif
						@endif
			        },
					initComplete: function () {
			            var api = this.api();
			            $('#tab-clinics thead tr#forFilters th').each(function(i) {
			            	var column = api.column(i);
			            	if ($(this).hasClass('select-filter')) 
			                {
			                    // Création des select pour les colonnes de classe "select-filter"
			                	var select = $('<select id="filter-column-'+ i + '" style="width:100%;"><option value=""></option></select>').on('change', function() {
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
			                	var input = $('<input type="text" id="filter-column-'+ i + '" placeholder="" style="width:100%;" />').on('keyup', function() {
			    					var val = $.fn.dataTable.util.escapeRegex($(this).val());
			    					column.search(val ? val : '', true, false).draw();
			    				});
			    				$('#forFilters th').eq(i).html(input);
			    			}
			            });

						document.getElementById('tableau').style.display = "block";
				        document.getElementById('load').style.display = "none";
			            // Suppression de la recherche globale du tableau
					    document.getElementById('tab-clinics_filter').style.display = "none";

					    // Affichage des boutons 'Ajouter' et 'Supprimer' pour les administrateurs
					    @if ((sizeof(Auth::user()->roles) >0) AND ("Administrateur" == Auth::user()->roles[0]['nom']))
					    	if ($(window).width() < 600) {
								$( '#div-buttonsGeneral' ).click(function() {
										if ($(this).find('.buttonsGeneral').css('display') != 'none')
											$(this).find('.buttonsGeneral').css('display', 'none');
										else
											$(this).find('.buttonsGeneral').css('display', 'flex');
								});

							} else
							{
								document.getElementById('buttonsGeneral').style.display = "flex";
							}
					    @endif

					    table.columns.adjust();
					}
			    });
				
			    // Action sur l'ouverture et la fermeture du détail d'une clinique
			    $('#tab-clinics tbody').on('click', 'td div.details-control', function () {
			    	var tr = $(this).closest('tr');
			        var row = table.row( tr );
			       
			        if ( row.child.isShown() ) {
			            // This row is already open - close it
			            row.child.hide();
			            
			            // Mise à jour du tableau si administrateur
			            @if ((sizeof(Auth::user()->roles) >0) AND ("Administrateur" == Auth::user()->roles[0]['nom']))
				            $('#tab-clinics').dataTable().fnUpdate(row.data(),tr,undefined,false);
						@endif

			            tr.children().each(function(index) {
					    	if (index == 0)
					    	{
					    		$(this).html('<div class="details-control"></div>');
					    	} else if (index > 0 && index < 3)
					    	{
					    		$(this).addClass('width-20');
					    		$(this).html('<div class="texte">' + $(this).html() + '</div>');
					    	} else if (index == 3 || index == 5)
					    	{
					    		$(this).addClass('width-10');
					    		$(this).html('<div class="texte">' + $(this).html() + '</div>');
					    	} else if (index == 4)
					    	{
					    		$(this).addClass('width-5');
					    		$(this).html('<div class="texte">' + $(this).html() + '</div>');
					    	} else
					    	{
					    		$(this).html('<div>' + $(this).html() + '</div>');
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
						var idRow = row.data()[7];
						var url = '{{ route("clinic-ajax.show", "id") }}';
						url = url.replace('id', idRow);
						
						$.ajax({
							dataType: "json",
							url: url, 
							data: $.param(params),
							success: function(data) {
								var clinic = data.clinic[0];

								row.child( '<div>' + format( row.data(), clinic ) + '</div>' ).show();
								row.child().addClass('child');
								tr.addClass('shown');
								@if ((sizeof(Auth::user()->roles) >0) AND ("Administrateur" == Auth::user()->roles[0]['nom']))
									createSelectForRow(tr, idRow);
								@endif
							}
						});
			        }
			    } );

			    @if ((sizeof(Auth::user()->roles) >0) AND ("Administrateur" == Auth::user()->roles[0]['nom']))
				    // Action lors de la sélection d'une ligne
			    	$('#tab-clinics tbody').on('click', '> tr > td:not(:first-child)', function () {
				        if (($(this).parent().hasClass('odd') || $(this).parent().hasClass('even')) && !$(this).parent().hasClass('shown'))
			    		{
			    			$(this).parent().toggleClass('selected');
			    		}
				    } );
				    
				    // Ouverture du modal lors de l'action sur le bouton 'Ajouter'
				    $('#addButtonGeneral').click( function () {
						// Reset input 
						$( '#addClinicName' ).val('');
						$( '#addClinicAddress' ).val('');
						$( '#addClinicZipCode' ).val('');
						$( '#addClinicCity' ).val('');

				    	$('#addClinicModal').modal("show");
				    	$( '#addClinicModal' ).draggable({
					    	handle: ".modal-header"
					  	});
				    });

				    // Action sur le bouton 'Enregistrer' du modal
				    $('#saveButtonClinic').click( function () {
		            	var message = '<p class="avertissement">@lang("amadeo.clinics.save.warning")</p><p class="question">@lang("amadeo.save.question")</p>';
			    	
		            	confirmBox(
		            		"@lang('amadeo.clinics.add.title')", 
		            		message, 
		            		'@lang("amadeo.yes")',
        					'@lang("amadeo.no")',
		            		function()
		            		{
						    	// Récupération des informations
						    	var params = {
									"_token": document.getElementsByName("_token")[0].value,
									"name": document.getElementById("addClinicName").value,
									"veterinaries": document.getElementById("addClinicVeterinaries").value,
									"city": document.getElementById("addClinicCity").value,
									"year": document.getElementById("addClinicYear").value
								};

						    	$.ajax({
						    		dataType: 'json',
							        url: '{{ route("clinic-ajax.store") }}', 
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
												message: "@lang('amadeo.clinics.add.ok')",
												type: 'success'
											});
						                    $('#addClinicModal').modal('hide');

											// Mise à jour du tableau
											var index = $('#tab-clinics').dataTable().fnAddData( [ null, data.clinic[0]["veterinaires"], data.clinic[0]["clinique"], null, null, data.clinic[0]["ville"], data.clinic[0]["annee"], data.clinic[0]["clinique_id"], data.clinic[0]["centrales_hors_web"], data.clinic[0]["centrales_web"], null ] );

											$( '#filter-column-1' ).val( data.clinic[0]["veterinaires"] );
						                    $( '#filter-column-1' ).focus();
						                    $( '#filter-column-1' ).trigger($.Event('keyup', { keycode: 13 }));
											$( '#filter-column-2' ).val( '' );
						                    $( '#filter-column-2' ).trigger($.Event('keyup', { keycode: 13 }));
											$( '#filter-column-3' ).val( '' );
						                    $( '#filter-column-3' ).trigger($.Event('keyup', { keycode: 13 }));
											$( '#filter-column-4' ).val( '' );
						                    $( '#filter-column-4' ).trigger($.Event('keyup', { keycode: 13 }));
											$( '#filter-column-5' ).val( '' );
						                    $( '#filter-column-5' ).trigger($.Event('keyup', { keycode: 13 }));
						                    $( '#filter-column-6 option[value=""]' ).prop( 'selected', true ).trigger('change');
						                    
						                    $("td div.details-control", $('#tab-clinics').dataTable().fnGetNodes( index )).click();

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
                        $( '.selected' ).each(function() {
                        	ids.push(table.row( $(this) ).data()[7]);
                        });

				    	if (ids.length > 0)
						{
					    	var message = '<p class="avertissement">@lang("amadeo.clinics.delete.warning")</p><p class="question">@lang("amadeo.save.question")</p>';
				    	
			            	confirmBox(
			            		"@lang('amadeo.clinics.delete.title')", 
			            		message, 
								'@lang("amadeo.yes")',
								'@lang("amadeo.no")',
			            		function()
			            		{
						    		// Création de l'URL
							    	var url = '{{ route("clinic-ajax.destroy", "id") }}';
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
													message: '@lang("amadeo.clinics.delete.ok")',
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
								message: '@lang("amadeo.clinics.delete.no-select")',
								type: 'warning'
							});
			            }
				    } );
			    @endif
			 
			    // Action sur le bouton "Rétablir"
			    $('#tab-clinics tbody').on('click', 'div[id^="cancelButtonClinic-"]', function () {
			    	var tr = $(this).closest('.child').prev();
			    	var row = table.row( tr );
				
		            var message = '<p class="avertissement">@lang("amadeo.clinics.reset.warning")</p><p class="question">@lang("amadeo.save.question")</p>';
		    	
		            confirmBox(
	            		'@lang("amadeo.clinics.reset.title")', 
	            		message, 
						'@lang("amadeo.yes")',
						'@lang("amadeo.no")',
	            		function() 
	            		{
	            			// Mise à jour du tableau
				            $('#tab-clinics').dataTable().fnUpdate(row.data(),tr,undefined,false);
							tr.children().each(function(index) {
								if (index == 0)
								{
									$(this).html('<div class="details-control"></div>');
								} else if (index > 0 && index < 3)
								{
									$(this).addClass('width-20');
									$(this).html('<div class="texte">' + $(this).html() + '</div>');
								} else if (index == 3 || index == 5)
								{
									$(this).addClass('width-10');
									$(this).html('<div class="texte">' + $(this).html() + '</div>');
								} else if (index == 4)
								{
									$(this).addClass('width-5');
									$(this).html('<div class="texte">' + $(this).html() + '</div>');
								} else
								{
									$(this).html('<div>' + $(this).html() + '</div>');
								}
							});
								
							// Open this row
							var params = {
								"_token": document.getElementsByName("_token")[0].value
							};

							// Création de l'URL
							var idRow = row.data()[7];
							var url = '{{ route("clinic-ajax.show", "id") }}';
							url = url.replace('id', idRow);
							
							$.ajax({
								dataType: "json",
								url: url, 
								data: $.param(params),
								success: function(data) {
									var clinic = data.clinic[0];

									row.child( '<div>' + format( row.data(), clinic ) + '</div>' ).show();
									row.child().addClass('child');
									tr.addClass('shown');
									@if ((sizeof(Auth::user()->roles) >0) AND ("Administrateur" == Auth::user()->roles[0]['nom']))
										createSelectForRow(tr, idRow);
									@endif
								}
							});
	            		},
	            		function() { }
	            	);
			    } );

			    // Action sur le bouton "Enregistrer"
			    $('#tab-clinics tbody').on('click', 'div[id^="saveButtonClinic-"]', function () {
			    	var tr = $(this).closest('.child');
			    	var trPrev = tr.prev();
			    	var rowPrevData = table.row( trPrev ).data();

			    	// Récupération des informations
			    	var id = rowPrevData[7];
			    	var veterinaries = $('input#row-' + id + '-1').val();
			    	var clinic = $('input#row-' + id + '-2').val();
			    	var addresse = $('input#row-' + id + '-3').val();
			    	var zipCode = $('input#row-' + id + '-4').val();
			    	var city = $('input#row-' + id + '-5').val();
			    	var year = $('input#row-' + id + '-6').val();
					var comment = $('textarea#textarea-clinic-comment-' + id).val();

					var centralsCodes = [];
					$( 'input[id^="central-code-' + id + '"]').each(function(i){
						if ($(this).val())
						{
							var codes = $(this).val().split(/[,;|]/);
							for (let i = 0; i < codes.length; i++) {
								centralsCodes.push({
									"centId": $(this).data("centid"), 
									"web": $(this).data("web"), 
									"identifier": codes[i].trim()
								});
							}
						}
					});

		            var message = '<p class="avertissement">@lang("amadeo.clinics.save.warning")</p><p class="question">@lang("amadeo.save.question")</p>';
			    	
		            confirmBox(
	            		'@lang("amadeo.clinics.update.title")', 
	            		message, 
						'@lang("amadeo.yes")',
						'@lang("amadeo.no")',
	            		function() 
	            		{
	            			// Création de l'URL
					    	var url = '{{ route("clinic-ajax.update", "id") }}';
					    	url = url.replace('id', id);
					    	
					    	var params = {
								"_token": document.getElementsByName("_token")[0].value,
								"clinic": clinic,
								"veterinaries": veterinaries,
								"addresse": addresse,
								"city": city,
								"zipCode": zipCode,
								"year": year,
								"comment": comment,
								"centralsCodes": centralsCodes
							};

					    	// Enregistrement en base
					    	$.ajax({
					    		dataType: 'json',
						        url: url, 
						        type: "PUT",
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

				    				if (data.success)
				    				{		
										bootoast.toast({
											message: '@lang("amadeo.clinics.update.ok")',
											type: 'success'
										});
							                    
										// Mise à jour du tableau
										rowPrevData[1] = params["veterinaries"];
										rowPrevData[2] = params["clinic"];
								    	rowPrevData[3] = params["addresse"];
								    	rowPrevData[4] = params["zipCode"];
								    	rowPrevData[5] = params["city"];
										rowPrevData[6] = params["year"];

										$('#tab-clinics').dataTable().fnUpdate(rowPrevData,trPrev,undefined,false);
										
								    	table.row( trPrev ).child.hide();
										trPrev.children().each(function(index) {
											if (index == 0)
											{
												$(this).html('<div class="details-control"></div>');
											} else if (index > 0 && index < 3)
											{
												$(this).addClass('width-20');
												$(this).html('<div class="texte">' + $(this).html() + '</div>');
											} else if (index == 3 || index == 5)
											{
												$(this).addClass('width-10');
												$(this).html('<div class="texte">' + $(this).html() + '</div>');
											} else if (index == 4)
											{
												$(this).addClass('width-5');
												$(this).html('<div class="texte">' + $(this).html() + '</div>');
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
	            		function() {}
	            	);
			    } );
		  	}
		});
	}
</script>