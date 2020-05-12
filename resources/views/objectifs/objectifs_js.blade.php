<script type="text/javascript">

	/* 
	* Formate le détail d'une ligne.
	*/
	function format ( row, rowsData, target ) {
		var idRow = row[13];
		var isPalier = (row[5][0] == 1) ? false : true;

		// Type d'objectif
		var html_types = '<tr><td id="tdTypeObjectif-' + idRow + '" class="detail-row-title" style="width: 20%">' + "@lang('amadeo.targets.type')" + '</td>';
		var type_checked = false;
		@foreach (Session::get('types_objectif_liste') as $type)
			if ((row[5][0] != null && row[5][0] == '{{ $type->id }}') || (row[5][0] == null && '{{ $type->id }}' == '1'))
				type_checked = true;
			else
				type_checked = false;

			html_types += '<td style="width: 25%"><div class="radio-item-horizontal">' 
							+ '<div class="radioContainer">' 
								+ '<input id="type-obj-' + idRow + '-{{ $type->id }}"';
			if (type_checked)
			{
				html_types += ' checked="checked"'
			}
			@if ((sizeof(Auth::user()->roles) >0) AND ("Vétérinaire" == Auth::user()->roles[0]['nom']))
				html_types += ' disabled="disabled"'
			@endif
			html_types += ' name="types_obj_' + idRow + '" type="radio" value="{{ $type->id }}">' 
								+ '<label for="type-obj-' + idRow + '-{{ $type->id }}"></label>' 
							+ '</div>' 
							+ '<div class="radioLabel">' 
								+ '<label for="type-obj-' + idRow + '-{{ $type->id }}">{{ $type->nom }}</label>' 
							+ '</div>' 
						+ '</div></td>';
		@endforeach
		html_types += '<td style="width: 25%"></td></tr>';

		// Période et CA de la période précédente
		var start_month = target["mois_debut"];
		var end_month = target["mois_fin"];
		var amount_prev_total = target["ca_periode_total_prec"];
		var html_periode = '<tr><td class="detail-row-title">@lang("amadeo.targets.period")</td><td><div class="div-periode" style="text-transform: capitalize;">@lang("amadeo.purchases.criteria-period-start") '; 
		@if ((sizeof(Auth::user()->roles) >0) AND ("Administrateur" == Auth::user()->roles[0]['nom']))
			html_periode += createSelectMois('periode-mois-debut-' + idRow, (start_month != null ? start_month : 1)) + ' @lang("amadeo.purchases.criteria-period-end") ' + createSelectMois('periode-mois-fin-' + idRow, (end_month != null ? end_month : 12));
		@else
			html_periode += (start_month != null ? new Date("{{date('Y')}}", start_month-1).toLocaleString("en", { month: "long" }) : new Date("{{date('Y')}}", 0).toLocaleString("en", { month: "long" })) + ' à ' + (end_month != null ? new Date("{{date('Y')}}", end_month-1).toLocaleString("fr", { month: "long" }) : new Date("{{date('Y')}}", 11).toLocaleString("fr", { month: "long" }));
		@endif

		html_periode += '</div></td><td id="objectif-ca-prec">Total N-1 : <span class="' + (row[16] ? 'orange' : '') + '">' + (amount_prev_total != null ? numberWithSpaces(amount_prev_total) : '-') + '</span></td><td></td></tr>';

		// Remise : pourcentage et indicateur "additionnelle"
		var rebate = target["pourcentage_remise"];
		var add_rebate = target["remise_additionnelle"];
		var html_remise = '<tr><td class="detail-row-title">@lang("amadeo.targets.rebate")</td><td>';
		@if ((sizeof(Auth::user()->roles) >0) AND ("Administrateur" == Auth::user()->roles[0]['nom']))
			html_remise += '<input type="text" id="input-remise-' + idRow + '" name="input-remise-' + idRow + '" value="' + (rebate != null ? numberWithSpaces(rebate) : 0) + '" style="width: 50%;">';
		@else
			html_remise += (rebate != null ? rebate : 0);
		@endif
		html_remise += ' %</td><td><div class="checkbox-item-horizontal">' 
							+ '<div class="checkboxContainer">' 
								+ '<input id="remise-add-obj-' + idRow + '" name="remises_obj_' + idRow + '" type="checkbox" value="1"';
		if (add_rebate)
		{
			html_remise += ' checked="checked"';
		}
		@if ((sizeof(Auth::user()->roles) >0) AND ("Vétérinaire" == Auth::user()->roles[0]['nom']))
			html_remise += ' disabled="disabled"'
		@endif
		html_remise += '>' 
								+ '<label for="remise-add-obj-' + idRow + '"></label>' 
							+ '</div>' 
							+ '<div class="checkboxLabel">' 
								+ '<label for="remise-add-obj-' + idRow + '">@lang("amadeo.targets.rebate-add")</label>' 
							+ '</div>' 
						+ '</div></td><td></td></tr>';

		// Remise : pourcentage et indicateur "additionnelle"
		var rebate_source = target["pourcentage_remise_source"];
		var html_remise_source = '<tr><td class="detail-row-title">@lang("amadeo.targets.rebate-source")</td><td>';
		@if ((sizeof(Auth::user()->roles) >0) AND ("Administrateur" == Auth::user()->roles[0]['nom']))
		html_remise_source += '<input type="text" id="input-remise-source-' + idRow + '" name="input-remise-source-' + idRow + '" value="' + (rebate_source != null ? numberWithSpaces(rebate_source) : 0) + '" style="width: 50%;">';
		@else
		html_remise_source += (rebate_source != null ? rebate_source : 0);
		@endif
		html_remise_source += ' %</td><td></td><td></tr>';

		// Objectif conditionnant
		var conditionned = target["obj_conditionne"];
		var html_condition = '<tr><td class="detail-row-title">' + "@lang('amadeo.targets.conditionned')" + '</td><td>';
		@if ((sizeof(Auth::user()->roles) >0) AND ("Administrateur" == Auth::user()->roles[0]['nom']))
			html_condition += '<select id="select-obj-conditionne-' + idRow + '"><option value=""></option>';
			for (var i = 0; i < rowsData.length; i++) {
				// Si l'année et le laboratoire correspondent à l'objectif actuel, ajout dans la liste déroulante
				if (row[1] == rowsData[i][1] && row[3] == rowsData[i][3] && rowsData[i][13] != row[13])
				{
					html_condition += '<option value="' + rowsData[i][13] + '"';
					if (conditionned == rowsData[i][13])
					{
						html_condition += ' selected'
					}
					html_condition += '>' + (rowsData[i][5][1]).replace(/(.{40})..+/, "$1&hellip;") + '</option>';
				}
			}
			html_condition += '</select>';
		@else
			if (conditionned != null)
			{
				for (var i = 0; i < rowsData.length; i++) {
					if (conditionned == rowsData[i][13])
						html_condition += (rowsData[i][5][1]).replace(/(.{40})..+/, "$1&hellip;");
				}
			} else
			{
				html_condition += '-';
			}
		@endif
		html_condition +='</td><td></td><td></td></tr>';

		// Palier précédent + Paliers incrémentiels ?
		var previous = target["obj_precedent"];
		var incremental = target["incrementiel"];
		var html_palier_precedent = '<tr id="tr-palier-prec-' + idRow + '"';
		if (!isPalier)
			html_palier_precedent += ' class="hide"';
		html_palier_precedent += '><td class="detail-row-title">@lang("amadeo.targets.previous")</td><td>';
		@if ((sizeof(Auth::user()->roles) >0) AND ("Administrateur" == Auth::user()->roles[0]['nom']))
			html_palier_precedent += '<select id="select-obj-prec-' + idRow + '"><option value=""></option>';
			
			for (var i = 0; i < rowsData.length; i++) {
				// Si l'année, le laboratoire et la catégorie correspondent à l'objectif actuel, ajout dans la liste déroulante
				if (row[1] == rowsData[i][1] && row[3] == rowsData[i][3] && row[4] == rowsData[i][4] && rowsData[i][13] != row[13])
				{
					html_palier_precedent += '<option value="' + rowsData[i][13] + '"';
					if (previous == rowsData[i][13])
					{
						html_palier_precedent += ' selected';
					}
					html_palier_precedent +='>' + (rowsData[i][5][1]).replace(/(.{40})..+/, "$1&hellip;") + '</option>';
				}
			}
			html_palier_precedent += '</select>';
		@else
			if (previous != null)
			{
				for (var i = 0; i < rowsData.length; i++) {
					if (previous == rowsData[i][13])
						html_palier_precedent += (rowsData[i][5][1]).replace(/(.{40})..+/, "$1&hellip;");
				}
			} else
			{
				html_palier_precedent += '-';
			}
		@endif
		html_palier_precedent += '</td>'
						+ '<td><div class="checkbox-item-horizontal"><div class="checkboxContainer">' 
							+ '<input id="palier-prec-obj-' + idRow + '" name="palier_prec_' + idRow + '" type="checkbox" value="1"';
		if (incremental)
		{
			html_palier_precedent += ' checked="checked"';
		}
		@if ((sizeof(Auth::user()->roles) >0) AND ("Vétérinaire" == Auth::user()->roles[0]['nom']))
			html_palier_precedent += ' disabled="disabled"'
		@endif
		html_palier_precedent += '>' 
								+ '<label for="palier-prec-obj-' + idRow + '"></label>' 
							+ '</div>' 
							+ '<div class="checkboxLabel">' 
								+ '<label for="palier-prec-obj-' + idRow + '">@lang("amadeo.targets.incremental")</label>' 
							+ '</div>'
						+ '</div></td><td></td></tr>'

		// Palier suivant (non modifiable)
		var next = target["obj_suivant"];
		var html_palier_suivant = '<tr id="tr-palier-suiv-' + idRow + '"';
		if (!isPalier)
			html_palier_suivant += ' class="hide"';
		var obj_suivant_nom = null;
		for (var i = 0; i < rowsData.length; i++) {
			// Si l'objectif correspond à l'objectif suivant
			if (next == rowsData[i][13])
			{
				obj_suivant_nom = (rowsData[i][5][1]).replace(/(.{40})..+/, "$1&hellip;");
			}
		}
		html_palier_suivant += '><td class="detail-row-title">@lang("amadeo.targets.next")</td><td>' + obj_suivant_nom + '</td><td></td><td></td></tr>';

		// Produits
		var html_produits = "<tr>" + "<td class='detail-row-title' style='vertical-align: top;'>@lang('amadeo.categories.products')</td>" + "<td colspan='3' class='detail-row-subTable' style='max-width: 80vh; border-right: 1px solid #9B9B9B;'><table id='tab-objectif-produits-" + idRow + "' class='' cellspacing='0' width='100%'><thead><tr><th>@lang('amadeo.products.name')</th><th>@lang('amadeo.products.packaging')</th><th>@lang('amadeo.targets.products-nb')</th><th>@lang('amadeo.targets.rebate')</th><th>@lang('amadeo.targets.rebate-source')</th></tr></thead></table></td></tr>";

		var html_commentaires = "<tr>" + "<td class='detail-row-title' style='vertical-align: top;'>@lang('amadeo.targets.comments')</td>" + "<td colspan='3'><div id='div-objectif-commentaires-" + idRow + "' class='commentaires'></div>";
		
		@if ((sizeof(Auth::user()->roles) >0) AND ("Vétérinaire" != Auth::user()->roles[0]['nom']))
		html_commentaires += "<textarea id='textarea-objectif-commentaires-" + idRow + "' rows='4' placeholder='@lang('amadeo.textarea.message')'></textarea>";
		@endif
		html_commentaires += "</td><td></td></tr>";

		var html_buttons = "";
		@if ((sizeof(Auth::user()->roles) >0) AND ("Administrateur" == Auth::user()->roles[0]['nom']))
			html_buttons += '<tr><td colspan="5"><div class="detail-row-buttons">'
			+ '<div id="cancelButtonObjectif-' + idRow + '" class="button"><a>@lang("amadeo.reset")</a><span class="btn_cancel"></span></div>'
			+ '<div id="saveButtonObjectif-' + idRow + '" class="button button_bold"><a>@lang("amadeo.save")</a><span class="btn_save"></span></div>'
			+ '</div></td></tr>';
		@endif

		var html = '<table class="detail-row" style="width: 100%;">'+ html_types + html_periode + html_remise + html_remise_source + html_condition + html_palier_precedent + (obj_suivant_nom != null ? html_palier_suivant : '') + html_produits + html_commentaires + html_buttons + '</table>';

		return html;
	}

	/*
	* Met en forme le tableau des produits d'un objectif.
	*/
	function loadDatatableProduits(rowData, moisFin)
	{
		var idRow = rowData[13];
    	// Récupération des informations
    	var params = {
			"_token": document.getElementsByName("_token")[0].value,
			"objectif": idRow,
			"moisFin": moisFin
		};

		$.ajax({
		    url: "{{ route('objectif-produit-ajax.index') }}", 
	        data: $.param(params),
		    success: function(json) {
		    	var data = jQuery.map(json, function(el, i) {
				  return [[el.denomination, el.conditionnement, el.volume, numberWithSpaces(el.pourcentage_remise), numberWithSpaces(el.pourcentage_remise_source), el.cat_prod_obj_id, el.obsolete]];
				});

			    // DataTable
			    var tableProduits = $('#tab-objectif-produits-' + idRow).DataTable( {
			    	"language": {
		              "url": "//cdn.datatables.net/plug-ins/1.10.16/i18n/French.json"
		            },
		            "bSortCellsTop": true,
		            "bLengthChange": false,
		            "bAutoWidth": false,
		            "info": false,
		            "paging": false,
		            "searching": false,
		            "sScrollY": "50vh",
		            "bScrollCollapse": true,
		            "order": [[ 0, "asc" ], [ 1, "asc" ]],
					"aoColumns": [ {"sWidth": "35%"}, {"sWidth": "35%"}, {"sWidth": "10%"}, {"sWidth": "10%"}, { "sWidth": "10%", "render": function ( data, type, row ) { return data+' %'; }} ],
					"aaData": data,
					"createdRow": function ( row, data, index ) {
						$('td', row).each(function(){
							$(this).html('<div>' + $(this).html() + '</div>');
						});
						$('td', row).eq(0).addClass('width-30');
						$('td', row).eq(0).find('div').addClass('texte');
						$('td', row).eq(0).find('div').attr('title', $('td', row).eq(0).find('div').html());
						$('td', row).eq(1).addClass('width-30');
						$('td', row).eq(1).find('div').addClass('texte');
						$('td', row).eq(1).find('div').attr('title', $('td', row).eq(1).find('div').html());
						$('td', row).eq(2).find('div').addClass('nombre');
						$('td', row).eq(3).find('div').html('<input type="text" id="row-' + idRow + '-produit-remise-' + data[5] + '" value="' + data[3] + '"/> %');
						$('td', row).eq(3).find('div').attr('noWrap','noWrap');
						$('td', row).eq(4).find('div').html('<input type="text" id="row-' + idRow + '-produit-remise-source-' + data[5] + '" value="' + data[4] + '"/> %');
						$('td', row).eq(4).find('div').attr('noWrap','noWrap');
						if (data[6])
						{
							$('td', row).eq(0).find('div').addClass('obsolete');
							$('td', row).eq(1).find('div').addClass('obsolete');
							$('td', row).eq(2).find('div').addClass('obsolete');
							$('td', row).eq(3).find('div').addClass('obsolete');
							$('td', row).eq(4).find('div').addClass('obsolete');
						}
			        },
			        initComplete: function () {
			        	tableProduits.columns.adjust();
			        }
			    });
			}
	    });

	}

	function loadDivCommentaires(idRow)
	{
		// Création de l'URL
    	var url = '{{ route("objectif-ajax.show", "id") }}';
    	url = url.replace('id', idRow);

		var params = {
			"_token": document.getElementsByName("_token")[0].value,
			"comment": 1,
		};

		$.ajax({
			dataType: "json",
		    url: url, 
			data: $.param(params),
		    success: function(json) {
		    	var commentaires = "";

		    	for (i=0 ; i<json.length ; i++)
		    	{
		    		var obj = json[i];
		    		commentaires += "<div class='bulle" + (i%2 ? " darker" : "") + "'><p class='writer'>" + obj["name"] + " @lang('amadeo.targets.comments-author') :</p><p>" + obj["commentaire"] + "</p><span class='time-right'>" + formatDateString(obj["date"]) + "</span></div>";
		    	}

		    	$( '#div-objectif-commentaires-' + idRow ).html(commentaires);
		    	$( '#div-objectif-commentaires-' + idRow ).scrollTop($( '#div-objectif-commentaires-' + idRow )[0].scrollHeight);
		    }
		});
	}

	function loadChangeActions(idRow, rowData)
	{
        $( 'input[type=radio][name^=types_obj_' + idRow + ']').change(function() {
        	switch (this.value)
        	{
        		case "1":
        			$( '#tr-palier-prec-' + idRow ).addClass('hide');
        			$( '#tr-palier-suiv-' + idRow ).addClass('hide');
        			break;
        		case "2":
        			$( '#tr-palier-prec-' + idRow ).removeClass('hide');
        			$( '#tr-palier-suiv-' + idRow ).removeClass('hide');
        			break;
        	}
        });

        $( 'input[type=text][name=input-remise-' + idRow + ']' ).on('keyup', function() {
	    	var remise = this.value;
	    	$( '#tab-objectif-produits-' + idRow + ' tbody tr' ).each(function() {
	    		var rowData = $('#tab-objectif-produits-' + idRow).DataTable().row( $(this) ).data();
            	rowData[3] = remise;
            	$('#row-' + idRow + '-produit-remise-' + rowData[5]).val(remise);
            });
        });

        $( 'input[type=text][name=input-remise-source-' + idRow + ']' ).on('keyup', function() {
	    	var remise = this.value;
	    	$( '#tab-objectif-produits-' + idRow + ' tbody tr' ).each(function() {
	    		var rowData = $('#tab-objectif-produits-' + idRow).DataTable().row( $(this) ).data();
            	rowData[4] = remise;
            	$('#row-' + idRow + '-produit-remise-source-' + rowData[5]).val(remise);
            });
        });
	}

	/*
	* Ajoute des select pour la dénomination, le conditionnement et la valorisation d'une ligne.
	*/
	function createSelectForRow(tr, data)
	{
		tr.find('div').each(function(index) {
	    	var value = data[index];
				
	    	if (index==5)
	    	{
	    		// Nom modifiable
	    		var input = $('<input type="text" id="row-' + data[13] + '-denomination" value="' + value[1] + '" style="width:100%;" />');
	    	} else if (index==6)
	    	{
	    		// Valeur modifiable
	    		var input = $('<input type="text" id="row-' + data[13] + '-valeur" value="' + value + '" style="width:100%;" />');
	    	}
	    	$(this).html(input);
	    	
	    });
	}

	/*
	* Charge le tableau des catégories.
	*/
	function loadAjaxFormObjectifs()
	{
		var html, htmlPrec;
		var dateMAJ = "{{ Session::get('date_maj') }}".split("/");
		var moisFin;
		// Si l'année est différente de la date de MAJ des achats, ou si la date de MAJ est antérieure au 15/02
		if (("{{date('Y')}}" != dateMAJ[2]) || (Date.parse(dateMAJ[2] + "-" + dateMAJ[1] + "-" + dateMAJ[0]) < Date.parse("{{date('Y')}}-02-15")))
		{
			moisFin = '01';
			
		} else 
		{ 
			if (dateMAJ[0] > 15)
			{
				moisFin = dateMAJ[1];
				
			} else {
				moisFin = dateMAJ[1]-1;
			}
		}
		
    	var params = {
			"_token": document.getElementsByName("_token")[0].value,
			"mois_fin": moisFin
		};

		$.ajax({
		    url: "{{ route('objectif-ajax.index') }}",
		    data: $.param(params),
		    success: function(json) {
		    	var data = jQuery.map(json, function(el, i) {
		    		// Calcul des écarts
					var jour;
					var totalJours = getNbDaysOfPeriod(el.annee, el.mois_debut, el.mois_fin);

					if (el.annee == "{{date('Y')}}")
					{
						jour = getDayOfPeriod(el.annee, dateMAJ[1]-1, (dateMAJ[0] > 15 ? 15 : 1), (el.mois_debut != null ? (el.mois_debut-1) : 0));
					}
					else if (el.annee < "{{date('Y')}}")
					{
						jour = totalJours;
					} else 
					{
						jour = null;
					}
					var ecartPourcents = el.valeur != null && el.valeur != 0 && jour != null ? (((el.ca_periode / el.valeur) - (jour / totalJours)) * 100).toFixed(2) : "-";
					var ecartEuros = ecartPourcents != null && ecartPourcents != "-" ? (el.valeur * ecartPourcents / 100).toFixed(2) : el.ca_periode;

					// Calcul de l'évolution
					var evol = (el.ca_periode_prec != null && el.ca_periode_prec != 0 ? ((el.ca_periode*100/el.ca_periode_prec)-100).toFixed(2) : "-");

					if (el.annee > 2017)
						return [[ el.suivi, el.annee, el.especes_noms, el.laboratoire, el.categorie, [el.type_obj, el.objectif], el.valeur != null ? (el.valeur).replace( /\./, "," ) : 0, numberWithSpaces(el.pourcentage_remise*1 + el.pourcentage_remise_source*1), numberWithSpaces(ecartEuros), numberWithSpaces(ecartPourcents), el.ca_periode, el.ca_periode_prec, numberWithSpaces(evol), el.id, el.especes, el.manque_valo_periode, el.manque_valo_periode_prec ]];
				});

			    // DataTable
			    var table = $('#tab-objectifs').DataTable( {
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
					}],
					"order": [[ 1, "desc" ], [ 3, "asc" ], [ 4, "asc" ], [ 5, "asc" ]],
					"aoColumns": [ 
						{ 
							"render": function ( data, type, row ) {
								var html = '<div style="display: flex; padding-right: 0px; padding-left: 3px;"><a class="details-control" style="cursor: pointer;"><img src="/images/PLUS.svg" alt="Détail"></a><a class="suivi-obj ';

								if (data)
								{
									html += 'objectif-suivi"><img src="/images/ETOILE_TURQUOISE_PLEINE.png" alt="@lang("amadeo.targets.followed")" title="@lang("amadeo.targets.followed")" ';
								} else
								{
									html += 'objectif-non-suivi"><img src="/images/ETOILE_TURQUOISE_VIDE.png" alt="@lang("amadeo.targets.not-followed")" title="@lang("amadeo.targets.not-followed")" ';
								}

								html += 'style="width:20px;"></a>';

								html += '<a href="{{ url("downloadObjectifParCliniquesCSV/objectifId/annee") }}" style="margin-left: 2px;cursor: pointer;"><img src="/images/TELECHARGER_TURQUOISE.svg" width="20" height="20" alt="@lang("amadeo.targets.download-clinics")" title="@lang("amadeo.targets.download-clinics")"></a>';
								html = html.replace('objectifId', row[13]);
								html = html.replace('annee', row[1]);

								html += '</div><p style="display:none;">' + data + '</p>';

								return html;
	   						}
	   					}, 
   						null, 
						null, 
						null, 
						null, 
						{
							"render": function ( data, type, row ) {
								var type = '';
								if (data[0] == 1)
								{
									type = '<img src="/images/OBJECTIF_SIMPLE.png" alt="@lang("amadeo.targets.type-simple")" style="width: 24px; vertical-align: text-bottom; margin-right: 2px;">';
								} else if (data[0] == 2)
								{
									type = '<img src="/images/OBJECTIF_PALIER.png" alt="@lang("amadeo.targets.type-level")" style="width: 24px; margin-right: 2px;">';
								}
								return type + data[1];
							}
						},
						{
							"render": function ( data, type, row ) {
								return numberWithSpaces(data) + ' €';
							}, 
							"sType": "numeric-comma" 
						},
						{
							"render": function ( data, type, row ) {
								if (data != '-')
									return (data != null) ? numberWithSpaces(parseFloat(data.toString().replace( /,/, "." ).replace( / /g, "" )).toFixed(2)) +' %' : '-';
								else
									return data;
							}, 
							"sType": "numeric-comma" 
						},
						{
							"render": function ( data, type, row ) {
								return data;
							}, 
							"sType": "numeric-comma" 
						}, 
						{
							"render": function ( data, type, row ) {
								if (data != '-')
									return (data != null) ? numberWithSpaces(parseFloat(data.toString().replace( /,/, "." ).replace( / /g, "" )).toFixed(2)) +' %' : '-';
								else
									return data;
							}, 
							"sType": "numeric-comma" 
						},
						{
							"render": function ( data, type, row ) {
								return (data != null) ? numberWithSpaces(data) : '-';
							}, 
							"sType": "numeric-comma" 
						}, 
						{
							"render": function ( data, type, row ) {
								return (data != null) ? numberWithSpaces(data) : '-';
							}, 
							"sType": "numeric-comma" 
						}, 
						{
							"render": function ( data, type, row ) {
								if (data != '-')
									return (data != null) ? numberWithSpaces(parseFloat(data.toString().replace( /,/, "." ).replace( / /g, "" )).toFixed(2)) +' %' : '-';
								else
									return data;
							}, 
							"sType": "numeric-comma" 
						}
					],
					"aaData": data,
					"createdRow": function ( row, data, index ) {
						$('td', row).each(function(index){
							if (index > 0)
							{
								$(this).html('<div>' + $(this).html() + '</div>');
							}
						});

						$('td', row).eq(2).addClass('width-10');
						$('td', row).eq(2).find('div').addClass('texte');
						$('td', row).eq(2).find('div').attr('title', $('td', row).eq(2).find('div').html());
						$('td', row).eq(3).addClass('width-10');
						$('td', row).eq(3).find('div').addClass('texte');
						$('td', row).eq(3).find('div').attr('title', $('td', row).eq(3).find('div').html());
						$('td', row).eq(4).addClass('width-15');
						$('td', row).eq(4).find('div').addClass('texte');
						$('td', row).eq(4).find('div').attr('title', $('td', row).eq(4).find('div').html());
						$('td', row).eq(5).addClass('width-20');
						$('td', row).eq(5).find('div').addClass('texte');
						$('td', row).eq(5).find('div').attr('title', data[5][1]);
						$('td', row).eq(6).addClass('width-5');
						$('td', row).eq(6).find('div').addClass('nombre');
						$('td', row).eq(6).find('div').css('padding-left', '5px');
						$('td', row).eq(6).find('div').css('padding-right', '5px');
						$('td', row).eq(7).addClass('width-5');
						$('td', row).eq(7).find('div').addClass('nombre');
						$('td', row).eq(7).find('div').css('padding-left', '5px');
						$('td', row).eq(7).find('div').css('padding-right', '5px');
						$('td', row).eq(8).find('div').addClass('nombre' + ($('td', row).eq(8).find('div').html().replace( /,/, "." ).replace( / /g, "" ).replace( /€/, "" ) > 0 ? ' positif' : ($('td', row).eq(8).find('div').html().replace( /,/, "." ).replace( / /g, "" ).replace( /€/, "" ) < 0 ? ' negatif' : '')));
						$('td', row).eq(8).find('div').css('padding-left', '5px');
						$('td', row).eq(8).find('div').css('padding-right', '5px');
						$('td', row).eq(9).find('div').addClass('nombre' + ($('td', row).eq(9).find('div').html().replace( /,/, "." ).replace( / /g, "" ).replace( /%/, "" ) > 0 ? ' positif' : ($('td', row).eq(9).find('div').html().replace( /,/, "." ).replace( / /g, "" ).replace( /%/, "" ) < 0 ? ' negatif' : '')));
						$('td', row).eq(9).find('div').css('padding-left', '5px');
						$('td', row).eq(9).find('div').css('padding-right', '5px');
						$('td', row).eq(10).find('div').addClass('nombre');
						$('td', row).eq(10).find('div').css('padding-left', '5px');
						$('td', row).eq(10).find('div').css('padding-right', '5px');
						if (data[15])
						{
							$('td', row).eq(10).find('div').addClass('orange');
						}
						$('td', row).eq(11).find('div').addClass('nombre');
						$('td', row).eq(11).find('div').css('padding-left', '5px');
						$('td', row).eq(11).find('div').css('padding-right', '5px');
						if (data[16])
						{
							$('td', row).eq(11).find('div').addClass('orange');
						}
						$('td', row).eq(12).find('div').addClass('nombre' + ($('td', row).eq(12).find('div').html().replace( /,/, "." ).replace( / /g, "" ).replace( /%/, "" ) > 0 ? ' positif' : ($('td', row).eq(12).find('div').html().replace( /,/, "." ).replace( / /g, "" ).replace( /%/, "" ) < 0 ? ' negatif' : '')));
						$('td', row).eq(12).find('div').css('padding-left', '5px');
						$('td', row).eq(12).find('div').css('padding-right', '5px');
			        },
					initComplete: function () {
			            var api = this.api();
			            $('#tab-objectifs thead tr#forFilters th').each(function(i) {
			            	var column = api.column(i);
			            	if ($(this).hasClass('select-filter')) 
			                {
			                    // Création des select pour les colonnes de classe "select-filter"
			                	var select = $('<select class="filter-column-'+ i + '" style="width:100%;"><option value=""></option></select>').on('change', function() {
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
			    			} else if ($(this).hasClass('star-filter'))
			    			{
			    				// Création de l'étoile pour les colonnes de classe "star-filter"
			    				var link = $('<a class="filter-column-' + i + ' suivi-objs objectif-suivi"><img src="/images/ETOILE_TURQUOISE_PLEINE.png" alt="@lang("amadeo.targets.followed")" title="@lang("amadeo.targets.followed")" style="width:20px;"></a>').on('click', function() {
							    	
			    					if ($( this ).hasClass('objectif-suivi'))
							    	{
							    		$( this ).removeClass('objectif-suivi');
							    		$( this ).addClass('objectif-non-suivi');
							    		$( this ).find('img').attr("src", "/images/ETOILE_TURQUOISE_VIDE.png");
							    		$( this ).find('img').attr("alt", '@lang("amadeo.targets.not-followed")');
							    		column.search( 'false|true', true, false ).draw();
							    	} else
							    	{
							    		$( this ).removeClass('objectif-non-suivi');
							    		$( this ).addClass('objectif-suivi');
							    		$( this ).find('img').attr("src", "/images/ETOILE_TURQUOISE_PLEINE.png");
							    		$( this ).find('img').attr("alt", '@lang("amadeo.targets.followed")');
							    		column.search( 'true' ).draw();
							    	}
			    				});
			    				$('#forFilters th').eq(i).html(link);
			    			}
			            });
			            table.columns(0).search( 'true' ).draw();

						document.getElementById('tableau').style.display = "block";
				        document.getElementById('load').style.display = "none";
			            // Suppression de la recherche globale du tableau
					    document.getElementById('tab-objectifs_filter').style.display = "none";

					    table.columns.adjust();
					}
			    });
				
			    // Ouverture et fermeture du détail d'un objectif
			    $('#tab-objectifs tbody').on('click', 'td .details-control', function () {
			    	var tr = $(this).closest('tr');
			        var row = table.row( tr );
			       
			        if ( row.child.isShown() ) {
			            // This row is already open - close it
			            row.child.hide();

			            // Mise à jour du tableau
				    	$('#tab-objectifs').dataTable().fnUpdate(row.data(),tr,undefined,false);

			            tr.children().each(function(index) {
					    	if (index == 2 || index == 3)
					    	{
					    		$(this).addClass('width-10');
					    		$(this).html('<div class="texte" title="' + $(this).html() + '">' + $(this).html() + '</div>');
					    	} else if (index == 4)
					    	{
					    		$(this).addClass('width-15');
					    		$(this).html('<div class="texte" title="' + $(this).html() + '">' + $(this).html() + '</div>');
					    	} else if (index == 5)
					    	{
					    		$(this).addClass('width-20');
					    		$(this).html('<div class="texte" title="' + row.data()[5][1] + '">' + $(this).html() + '</div>');
					    	} else if (index == 6 || index == 7)
					    	{
					    		$(this).addClass('width-5');
					    		$(this).html('<div class="nombre" title="' + $(this).html() + '" style="padding-left: 5px; padding-right: 5px;">' + $(this).html() + '</div>');
					    	} else if (index == 8 || index == 9 || index == 12)
					    	{
					    		$(this).html('<div class="nombre' + ($(this).html().replace( /,/, "." ).replace( / /g, "" ).replace( /€/, "" ).replace( /%/, "" ) > 0 ? ' positif' : ($(this).html().replace( /,/, "." ).replace( / /g, "" ).replace( /€/, "" ).replace( /%/, "" ) < 0 ? ' negatif' : '')) + '" style="padding-left: 5px; padding-right: 5px;">' + $(this).html() + '</div>');
					    	} else if (index == 10)
					    	{
					    		$(this).html('<div class="nombre' + (row.data()[15] ? ' orange' : '') + '" style="padding-left: 5px; padding-right: 5px;">' + $(this).html() + '</div>');
					    	} else if (index == 11)
					    	{
					    		$(this).html('<div class="nombre' + (row.data()[16] ? ' orange' : '') + '" style="padding-left: 5px; padding-right: 5px;">' + $(this).html() + '</div>');
					    	} else if (index > 0)
					    	{
					    		$(this).html('<div>' + $(this).html() + '</div>');
					    	}
					    });

			            tr.removeClass('shown');

			        }
			        else {
						// Open this row
						var params = {
							"_token": document.getElementsByName("_token")[0].value,
							"comment": 0,
						};

						// Création de l'URL
						var idRow = row.data()[13];
						var url = '{{ route("objectif-ajax.show", "id") }}';
						url = url.replace('id', idRow);
						
						$.ajax({
							dataType: "json",
							url: url, 
							data: $.param(params),
							success: function(data) {
								var target = data.target[0];

								// Tri des objectifs conditionnés et précédents
								sort_list('select-obj-conditionne-' + idRow);
								sort_list('select-obj-prec-' + idRow);

								row.child( '<div>' + format( row.data(), table.rows().data(), target ) + '</div>' ).show();
								loadDatatableProduits(row.data(), moisFin);
								loadChangeActions(idRow, row.data());
								loadDivCommentaires(idRow);
								row.child().addClass('child');

								tr.addClass('shown');
								tr.find('div').removeClass('positif');
								tr.find('div').removeClass('negatif');
								tr.find('div').removeClass('orange');
								tr.find('div').find('a.objectif-suivi').find('img').attr("src", "/images/ETOILE_BLANC_PLEINE.png");
								tr.find('div').find('a.objectif-non-suivi').find('img').attr("src", "/images/ETOILE_BLANC_VIDE.png");
								tr.find('div').find('a.details-control').find('img').attr("src", "/images/MOINS.svg");

								@if ((sizeof(Auth::user()->roles) >0) AND ("Vétérinaire" != Auth::user()->roles[0]['nom']))
									createSelectForRow(tr, row.data());
								@endif
							}
						});
			        }
			    } );

			    @if ((sizeof(Auth::user()->roles) >0) AND ("Administrateur" == Auth::user()->roles[0]['nom']))
			    // Action lors de la sélection d'une ligne
		    	$('#tab-objectifs tbody').on('click', '> tr > td:not(:first-child)', function () {
			        if (($(this).parent().hasClass('odd') || $(this).parent().hasClass('even')) && !$(this).parent().hasClass('shown'))
		    		{
		    			$(this).parent().toggleClass('selected');
		    		}
			    } );

			    // Ouverture du modal lors de l'action sur le bouton 'Ajouter'
			    $('#addButtonGeneral').click( function () {
			    	var annee = (new Date().getMonth() < 8) ? new Date().getFullYear() : (new Date().getFullYear()) + 1;
		    		$( '#addObjectifAnnee' ).val(annee);
		    		$( '#addObjectifAnnee' ).change(function() 
    				{
    					var laboratoire = $( '#selectObjectifLaboratoire' ).val();
    					if (this.value != '' && laboratoire != null && laboratoire != '')
    					{
    						var params = {
								"_token": document.getElementsByName("_token")[0].value,
								"annee": this.value,
								"laboratoire": laboratoire
							};

							$.ajax({
							    url: "getCategoriesObjectifAjax", 
							    type: "POST",
						        data: $.param(params),
							    success: function(json) {
							    	var categories = JSON.parse(json);

							    	var $selectCategorie = $( '#selectObjectifCategorie' ); 
							    	$selectCategorie.find( 'option' ).remove();
				    				$selectCategorie.append('<option value="">@lang("amadeo.list.message")</option>');

				    				for (var i = 0; i < categories.length; i++) {
				    					var categorie = categories[i];
				    					$selectCategorie.append('<option value="' + categorie["id"] + '">' + categorie["nom"] + '</option>');
				    				}
							    }
							});
    					}
    				});
		    		
			    	var $selectLaboratoire = $( '#selectObjectifLaboratoire' ); 
			    	$selectLaboratoire.find( 'option' ).remove();
    				$selectLaboratoire.append('<option value="">@lang("amadeo.list.message")</option>');

    				@foreach (Session::get('laboratoires_liste') as $laboratoire)
    					$selectLaboratoire.append('<option value="{{ $laboratoire->id }}">{{ $laboratoire->nom }}</option>');
    				@endforeach

    				$selectLaboratoire.change(function() 
    				{
    					var annee = document.getElementById("addObjectifAnnee").value;
    					if (this.value != '' && annee != null && annee != '')
    					{
    						var params = {
								"_token": document.getElementsByName("_token")[0].value,
								"annee": annee,
								"laboratoire": this.value
							};

							$.ajax({
							    url: "getCategoriesObjectifAjax", 
							    type: "POST",
						        data: $.param(params),
							    success: function(json) {
							    	var categories = JSON.parse(json);

							    	var $selectCategorie = $( '#selectObjectifCategorie' ); 
							    	$selectCategorie.find( 'option' ).remove();
				    				$selectCategorie.append('<option value="">@lang("amadeo.list.message")</option>');

				    				for (var i = 0; i < categories.length; i++) {
				    					var categorie = categories[i];
				    					$selectCategorie.append('<option value="' + categorie["id"] + '">' + categorie["nom"] + '</option>');
				    				}
							    }
							});
    					}
    				});

			    	$('#addObjectifModal').modal('show');
			    	$('#selectObjectifLaboratoire').val('');
			    	$('#selectObjectifCategorie').val('');
			    	$('#addObjectifNom').val('');
			    });

			    // Action sur le bouton 'Enregistrer' du modal de création
			    $('#saveButtonObjectif').click( function () {
			    	var message = '<p class="question">@lang("amadeo.save.question")</p>';
		    	
	            	confirmBox(
	            		"@lang('amadeo.targets.add.title')", 
	            		message, 
						'@lang("amadeo.yes")',
						'@lang("amadeo.no")',
	            		function()
	            		{
					    	// Récupération des informations
					    	var selectedCategorie = $( '#selectObjectifCategorie' ).find('option:selected');
					    	var annee = $( '#addObjectifAnnee' ).val();

							var dateMAJ = "{{ Session::get('date_maj') }}".split("/");
							var moisFin;
							// Si l'année est différente de la date de MAJ des achats, ou si la date de MAJ est antérieure au 15/02
							if (("{{date('Y')}}" != dateMAJ[2]) || (Date.parse(dateMAJ[2] + "-" + dateMAJ[1] + "-" + dateMAJ[0]) < Date.parse("{{date('Y')}}-02-15")))
							{
								moisFin = '01';
								
							} else 
							{
								if (dateMAJ[0] > 15)
								{
									moisFin = dateMAJ[1];
									
								} else {
									moisFin = dateMAJ[1]-1;
								}
							}

					    	var params = {
								"_token": document.getElementsByName("_token")[0].value,
								"isCopy": 0,
								"categorie": selectedCategorie.val(),
								"nom": document.getElementById("addObjectifNom").value,
								"mois_fin": moisFin,
								"annee": annee
							};
							
					    	$.ajax({
					    		dataType: 'json',
						        url: '{{ route("objectif-ajax.store") }}', 
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
												message: "@lang('amadeo.targets.add.ok')",
												type: 'success'
											});
					                    jQuery('#addObjectifModal').modal('hide');	

					                    // Mise à jour du tableau
					                    var index = $('#tab-objectifs').dataTable().fnAddData( [ true, data.objectif[0]["annee"], data.objectif[0]["especes_noms"], data.objectif[0]["laboratoire"], data.objectif[0]["categorie"], [1, data.objectif[0]["nom"]], 0, 0, null, null, null, null, null, data.objectif[0]["id"], data.objectif[0]["especes"], false, false ] );
										
					                    $('#tab-objectifs thead tr#forFilters th.star-filter').find('a').removeClass('objectif-non-suivi');
							    		$('#tab-objectifs thead tr#forFilters th.star-filter').find('a').addClass('objectif-suivi');
							    		$('#tab-objectifs thead tr#forFilters th.star-filter').find('a').find('img').attr("src", "/images/ETOILE_TURQUOISE_PLEINE.png");
							    		$('#tab-objectifs thead tr#forFilters th.star-filter').find('a').find('img').attr("alt", '@lang("amadeo.targets.followed")');
					                    table.columns(0).search( 'true' ).draw();

					                    $( '.filter-column-1 option[value=""]' ).prop( 'selected', true ).trigger('change');
					                    $( '.filter-column-2' ).val( '' );
					                    $( '.filter-column-2' ).trigger($.Event('keyup', { keycode: 13 }));
					                    $( '.filter-column-3 option[value=""]' ).prop( 'selected', true ).trigger('change');
					                    $( '.filter-column-4' ).val( '' );
					                    $( '.filter-column-4' ).trigger($.Event('keyup', { keycode: 13 }));
					                    $( '.filter-column-5' ).val( data.objectif[0]["nom"] );
					                    $( '.filter-column-5' ).focus();
					                    $( '.filter-column-5' ).trigger($.Event('keyup', { keycode: 13 }));
					                    
					                    $("a.details-control", $('#tab-objectifs').dataTable().fnGetNodes( index )).click();
					                }
				    			}
					    	});
	            		}, 
	            		function() {}
	            	);
			    });

			    // Ouverture du modal lors de l'action sur le bouton 'Dupliquer'
			    $('#copyButtonGeneral').click( function () {
			    	if ($('#tab-objectifs tbody > tr.selected').length == 1)
			    	{
			    		$('#copyObjectifModal').modal('show');

			    		var annee = (new Date().getMonth() < 8) ? new Date().getFullYear() : (new Date().getFullYear()) + 1;
			    		var select = table.row( $('#tab-objectifs tbody > tr.selected').first());
			    		$( '#copyObjectifAnnee' ).html(select.data()[1]);
			    		$( '#copyObjectifLaboratoire' ).html(select.data()[3]);
			    		$( '#copyObjectifCategorie' ).html(select.data()[4]);
			    		$( '#copyObjectifAncienNom' ).html(select.data()[5][1]);
			    		$( '#copyObjectifNom' ).val(select.data()[5][1]);
				    	
				    	$( '#divNomObjectif' ).removeClass("has-error");
				    	$( '#nom-error' ).html( "" );

				    	// Récupération des informations
				    	var params = {
							"_token": document.getElementsByName("_token")[0].value,
							"objectif": select.data()[13]
						};

						$.ajax({
						    url: "{{ route('objectif-produit-ajax.index') }}", 
					        data: $.param(params),
						    success: function(json) {
						    	var data = jQuery.map(json, function(el, i) {
								  return [[el.denomination, el.conditionnement, el.code_gtin, el.id, el.obsolete]];
								});

							    // DataTable
							    var tableProduitsCopy = $('#tab-copy-objectif-produits').DataTable( {
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
			    	} else if ($('#tab-objectifs tbody > tr.selected').length == 0)
			    	{
			    		bootoast.toast({
							message: "@lang('amadeo.targets.copy.no-select')",
							type: 'warning'
						});
			    	} else if ($('#tab-objectifs tbody > tr.selected').length > 1)
			    	{
			    		bootoast.toast({
							message: "@lang('amadeo.targets.copy.multiple-select')",
							type: 'warning'
						});
			    	}
			    });

			    // Action sur le bouton 'Enregistrer' du modal de duplication
			    $('#saveCopyButtonObjectif').click( function () {
			    	var message = '<p class="question">@lang("amadeo.save.question")</p>';
		    	
	            	confirmBox(
	            		"@lang('amadeo.targets.copy.title')", 
	            		message, 
						'@lang("amadeo.yes")',
						'@lang("amadeo.no")',
	            		function()
	            		{
					    	$( '#divNomObjectif' ).removeClass("has-error");
					    	$( '#nom-error' ).html( "" );
				    	
							// Récupération de l'objectif d'origine
							var select = table.row( $('#tab-objectifs tbody > tr.selected').first());
					    	
							var dateMAJ = "{{ Session::get('date_maj') }}".split("/");
							var moisFin;
							// Si l'année est différente de la date de MAJ des achats, ou si la date de MAJ est antérieure au 15/02
							if (("{{date('Y')}}" != dateMAJ[2]) || (Date.parse(dateMAJ[2] + "-" + dateMAJ[1] + "-" + dateMAJ[0]) < Date.parse("{{date('Y')}}-02-15")))
							{
								moisFin = '01';
								
							} else 
							{
								if (dateMAJ[0] > 15)
								{
									moisFin = dateMAJ[1];
									
								} else {
									moisFin = dateMAJ[1]-1;
								}
							}

					    	var params = {
								"_token": document.getElementsByName("_token")[0].value,
								"isCopy": 1,
								"ancien_objectif": select.data()[13],
								"nom": document.getElementById("copyObjectifNom").value,
								"mois_fin": moisFin
							};
							
					    	$.ajax({
					    		dataType: 'json',
						        url: '{{ route("objectif-ajax.store") }}', 
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
												message: 'L\'objectif a été créé.',
												type: 'success'
											});
										jQuery('#copyObjectifModal').modal('hide');	
										
										// Calcul des écarts
										var jour;
										var totalJours = getNbDaysOfPeriod(data.obj[0]["annee"], data.obj[0]["mois_debut"], data.obj[0]["mois_fin"]);

										if (data.obj[0]["annee"] == "{{date('Y')}}")
										{
											jour = getDayOfPeriod(data.obj[0]["annee"], dateMAJ[1]-1, (dateMAJ[0] > 15 ? 15 : 1), (data.obj[0]["mois_debut"] != null ? (data.obj[0]["mois_debut"]-1) : 0));
										}
										else
										{
											jour = totalJours;
										}
										var ecartPourcents = data.obj[0]["valeur"] != null && data.obj[0]["valeur"] != 0 ? (((data.obj[0]["ca_periode"] / data.obj[0]["valeur"]) - (jour / totalJours)) * 100).toFixed(2) : "-";
										var ecartEuros = ecartPourcents != null && ecartPourcents != "-" ? (data.obj[0]["valeur"] * ecartPourcents / 100).toFixed(2) : data.obj[0]["ca_periode"];

										// Calcul de l'évolution
										var evol = (data.obj[0]["ca_periode_prec"] != null && data.obj[0]["ca_periode_prec"] != 0 ? ((data.obj[0]["ca_periode"]*100/data.obj[0]["ca_periode_prec"])-100).toFixed(2) : "-");

					                    // Mise à jour du tableau
										var index = $('#tab-objectifs').dataTable().fnAddData( [ data.obj[0]["suivi"], data.obj[0]["annee"], data.obj[0]["especes_noms"], data.obj[0]["laboratoire"], data.obj[0]["categorie"], [data.obj[0]["type_obj"], data.obj[0]["objectif"]], data.obj[0]["valeur"] != null ? (data.obj[0]["valeur"]).replace( /\./, "," ) : 0, numberWithSpaces(data.obj[0]["pourcentage_remise"]*1 + data.obj[0]["pourcentage_remise_source"]*1), numberWithSpaces(ecartEuros), numberWithSpaces(ecartPourcents), data.obj[0]["ca_periode"], data.obj[0]["ca_periode_prec"], numberWithSpaces(evol), data.obj[0]["id"], data.obj[0]["especes"], data.obj[0]["manque_valo_periode"], data.obj[0]["manque_valo_periode_prec"] ] );
										
										if (data.obj[0]["suivi"])
										{
											$('#tab-objectifs thead tr#forFilters th.star-filter').find('a').removeClass('objectif-non-suivi');
											$('#tab-objectifs thead tr#forFilters th.star-filter').find('a').addClass('objectif-suivi');
											$('#tab-objectifs thead tr#forFilters th.star-filter').find('a').find('img').attr("src", "/images/ETOILE_TURQUOISE_PLEINE.png");
											$('#tab-objectifs thead tr#forFilters th.star-filter').find('a').find('img').attr("alt", '@lang("amadeo.targets.followed")');
											table.columns(0).search( 'true' ).draw();
										} else 
										{
											$('#tab-objectifs thead tr#forFilters th.star-filter').find('a').addClass('objectif-non-suivi');
											$('#tab-objectifs thead tr#forFilters th.star-filter').find('a').removeClass('objectif-suivi');
											$('#tab-objectifs thead tr#forFilters th.star-filter').find('a').find('img').attr("src", "/images/ETOILE_TURQUOISE_VIDE.png");
											$('#tab-objectifs thead tr#forFilters th.star-filter').find('a').find('img').attr("alt", '@lang("amadeo.targets.not-followed")');
											table.columns(0).search( 'false|true', true, false ).draw();
										}

					                    $( '.filter-column-1 option[value=""]' ).prop( 'selected', true ).trigger('change');
					                    $( '.filter-column-2' ).val( '' );
					                    $( '.filter-column-2' ).trigger($.Event('keyup', { keycode: 13 }));
					                    $( '.filter-column-3 option[value=""]' ).prop( 'selected', true ).trigger('change');
					                    $( '.filter-column-4' ).val( '' );
					                    $( '.filter-column-4' ).trigger($.Event('keyup', { keycode: 13 }));
					                    $( '.filter-column-5' ).val( data.obj[0]["objectif"] );
					                    $( '.filter-column-5' ).focus();
					                    $( '.filter-column-5' ).trigger($.Event('keyup', { keycode: 13 }));
					                    
										$("a.details-control", $('#tab-objectifs').dataTable().fnGetNodes( index )).click();
										table.rows().every(function() {
											this.nodes().to$().removeClass('selected');
										})
					                }
				    			}
					    	});
	            		}, 
	            		function() {}
	            	);
			    });

			    // Action sur le bouton 'Supprimer'
			    $('#deleteButtonGeneral').click( function () {
			    	var ids = [];

			    	$('#tab-objectifs tbody > tr.selected').each(function() {
                    	ids.push(table.row( $(this) ).data()[13]);
                    });
                
			    	if (ids.length > 0)
					{
				    	var message = '<p class="avertissement">@lang("amadeo.targets.delete.warning")</p><p class="question">@lang("amadeo.save.question")</p>';
			    	
		            	confirmBox(
		            		"@lang('amadeo.targets.delete.title')", 
		            		message, 
		            		'@lang("amadeo.yes")',
        					'@lang("amadeo.no")',
		            		function()
		            		{
					    		// Création de l'URL
						    	var url = '{{ route("objectif-ajax.destroy", "id") }}';
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
												message: "@lang('amadeo.targets.delete.ok')",
												type: 'success'
											});

									        // Mise à jour du tableau
									        table.rows('.selected').remove().draw( false );
					    				}
					    			}
						    	});
		            		}, 
		            		function() {}
		            	);
                    } else
			            {
		            		bootoast.toast({
								message: "@lang('amadeo.targets.delete.no-select')",
								type: 'warning'
							});
			            }
			    } );

			    // Modification du suivi d'un objectif
			    $('#tab-objectifs tbody').on('click', 'td .suivi-obj', function () {
			    	var tr = $(this).closest('tr');
			        var row = table.row( tr );

			        var newSuivi;
			    	if ($( this ).hasClass('objectif-suivi'))
			    	{
			    		newSuivi = false;
			    		$( this ).removeClass('objectif-suivi');
			    		$( this ).addClass('objectif-non-suivi');
			    		$( this ).find('img').attr("src", "/images/ETOILE_TURQUOISE_VIDE.png");
			    		$( this ).find('img').attr("alt", '@lang("amadeo.targets.not-followed")');
			    	} else
			    	{
			    		newSuivi = true;
			    		$( this ).removeClass('objectif-non-suivi');
			    		$( this ).addClass('objectif-suivi');
			    		$( this ).find('img').attr("src", "/images/ETOILE_TURQUOISE_PLEINE.png");
			    		$( this ).find('img').attr("alt", '@lang("amadeo.targets.followed")');
			    	}
			    	
					// Création de l'URL
			    	var url = '{{ route("objectif-ajax.update", "id") }}';
			    	url = url.replace('id', row.data()[13]);
			    			    	   	
			    	// Enregistrement en BDD
		    		var params = {
						"_token": document.getElementsByName("_token")[0].value,
						"isSuivi": 1,
						"suivi": (newSuivi ? 1 : 0)
					};
					
					$.ajax({
					    url: url, 
				        type: "PUT",
						data: $.param(params),
					    success: function(json) {
					    	row.data()[0] = newSuivi;
							$('#tab-objectifs').dataTable().fnUpdate(row.data(),tr,undefined,false);

							tr.children().each(function(index) {
								if (index == 2 || index == 3)
								{
									$(this).addClass('width-10');
									$(this).html('<div class="texte" title="' + $(this).html() + '">' + $(this).html() + '</div>');
								} else if (index == 4)
								{
									$(this).addClass('width-15');
									$(this).html('<div class="texte" title="' + $(this).html() + '">' + $(this).html() + '</div>');
								} else if (index == 5)
								{
									$(this).addClass('width-20');
									$(this).html('<div class="texte" title="' + row.data()[5][1] + '">' + $(this).html() + '</div>');
								} else if (index == 6 || index == 7)
								{
									$(this).addClass('width-5');
									$(this).html('<div class="nombre" title="' + $(this).html() + '" style="padding-left: 5px; padding-right: 5px;">' + $(this).html() + '</div>');
								} else if (index == 8 || index == 9 || index == 12)
								{
									$(this).html('<div class="nombre' + ($(this).html().replace( /,/, "." ).replace( / /g, "" ).replace( /€/, "" ).replace( /%/, "" ) > 0 ? ' positif' : ($(this).html().replace( /,/, "." ).replace( / /g, "" ).replace( /€/, "" ).replace( /%/, "" ) < 0 ? ' negatif' : '')) + '" style="padding-left: 5px; padding-right: 5px;">' + $(this).html() + '</div>');
								} else if (index == 10)
								{
									$(this).html('<div class="nombre' + (row.data()[15] ? ' orange' : '') + '" style="padding-left: 5px; padding-right: 5px;">' + $(this).html() + '</div>');
								} else if (index == 11)
								{
									$(this).html('<div class="nombre' + (row.data()[16] ? ' orange' : '') + '" style="padding-left: 5px; padding-right: 5px;">' + $(this).html() + '</div>');
								} else if (index > 0)
								{
									$(this).html('<div>' + $(this).html() + '</div>');
								}
							});
						}
					});
			    } );

			    // Action sur le bouton "Rétablir" d'un objectif
			    $('#tab-objectifs tbody').on('click', 'div[id^="cancelButtonObjectif-"]', function () {
			    	var tr = $(this).closest('.child').prev();
			    	var row = table.row( tr );
				
		            var message = '<p class="avertissement">@lang("amadeo.targets.reset.warning")</p><p class="question">@lang("amadeo.save.question")</p>';
		    	
		            confirmBox(
	            		"@lang('amadeo.targets.update.title')", 
	            		message, 
						'@lang("amadeo.yes")',
						'@lang("amadeo.no")',
	            		function() 
	            		{
	                       	// Mise à jour du tableau
				            $('#tab-objectifs').dataTable().fnUpdate(row.data(),tr,undefined,false);
											
				            tr.children().each(function(index) {
						    	if (index == 2 || index == 3)
						    	{
						    		$(this).addClass('width-10');
						    		$(this).html('<div class="texte" title="' + $(this).html() + '">' + $(this).html() + '</div>');
						    	} else if (index == 4)
						    	{
						    		$(this).addClass('width-15');
						    		$(this).html('<div class="texte" title="' + $(this).html() + '">' + $(this).html() + '</div>');
						    	} else if (index == 5)
						    	{
						    		$(this).addClass('width-20');
						    		$(this).html('<div class="texte" title="' + row.data()[5][1] + '">' + $(this).html() + '</div>');
						    	} else if (index == 6 || index == 7)
								{
									$(this).addClass('width-5');
									$(this).html('<div class="nombre" title="' + $(this).html() + '" style="padding-left: 5px; padding-right: 5px;">' + $(this).html() + '</div>');
								} else if (index == 8 || index == 9 || index == 12)
								{
									$(this).html('<div class="nombre' + ($(this).html().replace( /,/, "." ).replace( / /g, "" ).replace( /€/, "" ).replace( /%/, "" ) > 0 ? ' positif' : ($(this).html().replace( /,/, "." ).replace( / /g, "" ).replace( /€/, "" ).replace( /%/, "" ) < 0 ? ' negatif' : '')) + '" style="padding-left: 5px; padding-right: 5px;">' + $(this).html() + '</div>');
								} else if (index == 10)
								{
									$(this).html('<div class="nombre' + (row.data()[15] ? ' orange' : '') + '" style="padding-left: 5px; padding-right: 5px;">' + $(this).html() + '</div>');
								} else if (index == 11)
						    	{
						    		$(this).html('<div class="nombre' + (row.data()[16] ? ' orange' : '') + '" style="padding-left: 5px; padding-right: 5px;">' + $(this).html() + '</div>');
						    	} else if (index > 0)
						    	{
						    		$(this).html('<div>' + $(this).html() + '</div>');
						    	}
						    });

							var params = {
								"_token": document.getElementsByName("_token")[0].value,
								"comment": 0,
							};

							// Création de l'URL
							var idRow = row.data()[13];
							var url = '{{ route("objectif-ajax.show", "id") }}';
							url = url.replace('id', idRow);
							
							$.ajax({
								dataType: "json",
								url: url, 
								data: $.param(params),
								success: function(data) {
									var target = data.target[0];

									// Tri des objectifs conditionnés et précédents
									sort_list('select-obj-conditionne-' + idRow);
									sort_list('select-obj-prec-' + idRow);

									row.child( '<div>' + format( row.data(), table.rows().data(), target ) + '</div>' ).show();
									loadDatatableProduits(row.data(), moisFin);
									loadChangeActions(idRow, row.data());
									loadDivCommentaires(idRow);
									row.child().addClass('child');

									tr.addClass('shown');
									tr.find('div').removeClass('positif');
									tr.find('div').removeClass('negatif');
									tr.find('div').removeClass('orange');
									tr.find('div').find('a.objectif-suivi').find('img').attr("src", "/images/ETOILE_BLANC_PLEINE.png");
									tr.find('div').find('a.objectif-non-suivi').find('img').attr("src", "/images/ETOILE_BLANC_VIDE.png");
									tr.find('div').find('a.details-control').find('img').attr("src", "/images/MOINS.svg");

									@if ((sizeof(Auth::user()->roles) >0) AND ("Vétérinaire" != Auth::user()->roles[0]['nom']))
										createSelectForRow(tr, row.data());
									@endif
								}
							});
	            		}, 
	            		function() { }
	            	);	    	
			    } );

			    // Action sur le bouton "Enregistrer" d'un objectif
			    $('#tab-objectifs tbody').on('click', 'div[id^="saveButtonObjectif-"]', function () {
			    	var tr = $(this).closest('.child');
			    	var trPrev = tr.prev();
			    	var rowPrevData = table.row( trPrev ).data();

			    	// Récupération des informations
			    	var id = rowPrevData[13];
			    	var nom = trPrev.children().find('input').eq(0).val();
			    	var valeur = trPrev.children().find('input').eq(1).val();
			    	var typeObjectif = $('input[name=types_obj_' + id + ']:checked').val();
			    	var moisDebut = document.getElementById('periode-mois-debut-' + id).value;
        			var moisFin = document.getElementById('periode-mois-fin-' + id).value;
        			var remise = document.getElementById('input-remise-' + id).value;
					var remiseAdditionnelle = $('input[name=remises_obj_' + id + ']:checked').val();
        			var remiseSource = document.getElementById('input-remise-source-' + id).value;
        			var idObjConditionne, paliersIncrementiels;
        			if (document.getElementById('select-obj-conditionne-' + id) != null)
        			{
        				idObjConditionne = document.getElementById('select-obj-conditionne-' + id).value;
        			}
        			var idObjPrecedent = document.getElementById('select-obj-prec-' + id).value;
        			if ($('input[name=palier_prec_' + id + ']:checked') != null)
        			{
        				paliersIncrementiels = $('input[name=palier_prec_' + id + ']:checked').val();
        			}
        			
					// Vérification des erreurs
					if (nom != '')
        			{
				    	var produits = [];
				    	$( '#tab-objectif-produits-' + id + ' tbody tr' ).each(function() {
	                    	if ($('#tab-objectif-produits-' + id).DataTable().row( $(this) ).data())
	                    	{
	                    		var idProd = $('#tab-objectif-produits-' + id).DataTable().row( $(this) ).data()[5];
	                    		var remise = 0;
								if (document.getElementById('row-' + id + '-produit-remise-' + idProd))
								{
	                    			remise = document.getElementById('row-' + id + '-produit-remise-' + idProd).value;
	                    			remiseSource = document.getElementById('row-' + id + '-produit-remise-source-' + idProd).value;
								}
	                    		produits.push({
	                    			"cat_prod_obj_id": idProd,
	                    			"remise": (remise != null && remise != '') ? remise.replace( /,/, "." ) : 0,
	                    			"remise_source": (remiseSource != null && remiseSource != '') ? remiseSource.replace( /,/, "." ) : 0
	                    		});
	                    	}
	                    });
			                
	                    var commentaires = [];
	                    if ($( '#textarea-objectif-commentaires-' + id ).val() != "")
	                    {
		                    commentaires.push({
		                		"user_id": '{{ Auth::user()->id }}', 
		                		"objectif_id": id,
		                		"commentaire": $( '#textarea-objectif-commentaires-' + id ).val(), 
		                		"date": formatDateStringToSql(formatDate(new Date()))
		                	});
	                    }
				    	
			            var message = '<p class="question">@lang("amadeo.save.question")</p>';

			            confirmBox(
		            		"@lang('amadeo.targets.update.title')", 
		            		message, 
		            		'@lang("amadeo.yes")',
        					'@lang("amadeo.no")',
		            		function() 
		            		{
						    	// Création de l'URL
						    	var url = '{{ route("objectif-ajax.update", "id") }}';
						    	url = url.replace('id', id);
														    	
								var dateMAJ = "{{ Session::get('date_maj') }}".split("/");
								var moisFinCA;
								// Si l'année est différente de la date de MAJ des achats, ou si la date de MAJ est antérieure au 15/02
								if (("{{date('Y')}}" != dateMAJ[2]) || (Date.parse(dateMAJ[2] + "-" + dateMAJ[1] + "-" + dateMAJ[0]) < Date.parse("{{date('Y')}}-02-05")))
								{
									moisFinCA = '01';
									
								} else 
								{
									if (dateMAJ[0] > 15)
									{
										moisFinCA = dateMAJ[1];
										
									} else {
										moisFinCA = dateMAJ[1]-1;
									}
								}
								
						    	var params = {
									"_token": document.getElementsByName("_token")[0].value,
							    	"isSuivi": 0,
							    	"annee": rowPrevData[1],
									"nom": nom,
							    	"valeur": (valeur != null && valeur != '') ? valeur.replace( /,/, "." ) : 0,
							    	"typeObjectif": typeObjectif,
							    	"moisDebut": moisDebut,
				        			"moisFin": moisFin,
				        			"remise": (remise != null && remise != '') ? remise.replace( /,/, "." ) : 0,
				        			"remiseAdditionnelle": (remiseAdditionnelle != null && remiseAdditionnelle.length > 0) ? 1 : 0,
				        			"remiseSource": (remiseSource != null && remiseSource != '') ? remiseSource.replace( /,/, "." ) : 0,
				        			"idObjConditionne": (idObjConditionne != null && idObjConditionne != "") ? idObjConditionne : null,
				        			"idObjPrecedent": (typeObjectif != 1 && idObjPrecedent != null && idObjPrecedent != "") ? idObjPrecedent : null,
				        			"paliersIncrementiels": (typeObjectif != 1 && paliersIncrementiels != null && paliersIncrementiels.length > 0) ? 1 : 0,
				        			"produits": produits,
									"commentaires": commentaires,
				        			"mois_fin_CA": moisFinCA
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
												message: "@lang('amadeo.targets.update.ok')",
												type: 'success'
											});
						                    
											// Calcul des écarts
											var jour;
											var totalJours = getNbDaysOfPeriod(rowPrevData[1], moisDebut, moisFin);
											if (rowPrevData[1] == "{{date('Y')}}")
											{
												jour = getDayOfPeriod(rowPrevData[1], dateMAJ[1]-1, (dateMAJ[0] > 15 ? 15 : 1), (moisDebut != null ? (moisDebut-1) : 0));
											}
											else if (rowPrevData[1] < "{{date('Y')}}")
											{
												jour = totalJours;
											} else 
											{
												jour = null;
											}
											var ecartPourcents = params["valeur"] != null && params["valeur"] != 0 && jour != null ? (((data.objectif[0]["ca_periode"] / params["valeur"]) - (jour / totalJours)) * 100).toFixed(2) : "-";
											var ecartEuros = ecartPourcents != null && ecartPourcents != "-" ? (params["valeur"] * ecartPourcents / 100).toFixed(2) : data.objectif[0]["ca_periode"];
											
											// Calcul de l'évolution
											var evol = data.objectif[0]["ca_periode_prec"] != null && data.objectif[0]["ca_periode_prec"] != 0 ? ((data.objectif[0]["ca_periode"]*100/data.objectif[0]["ca_periode_prec"])-100).toFixed(2) : "-";

									        // Mise à jour du tableau
									        rowPrevData[5] = [params["typeObjectif"], params["nom"]];
									        trPrev.children().eq(5).attr('title', params["nom"]);
											rowPrevData[6] = params["valeur"];
											rowPrevData[7] = numberWithSpaces(params["remise"]*1 + params["remiseSource"]*1);
						                    rowPrevData[8] = numberWithSpaces(ecartEuros);
						                    rowPrevData[9] = numberWithSpaces(ecartPourcents);
						                    rowPrevData[10] = data.objectif[0]["ca_periode"];
						                    rowPrevData[11] = data.objectif[0]["ca_periode_prec"];
						                    rowPrevData[12] = numberWithSpaces(evol);
						                    rowPrevData[15] = data.objectif[0]["manque_valo_periode"];
											rowPrevData[16] = data.objectif[0]["manque_valo_periode_prec"];
											
									    	if (rowPrevData[15])
									    	{
									    		trPrev.children().eq(9).addClass('orange');
									    	}

									    	if (rowPrevData[16])
									    	{
									    		trPrev.children().eq(10).addClass('orange');
									    	}

									    	$('#tab-objectifs').dataTable().fnUpdate(rowPrevData,trPrev,undefined,false);

								            trPrev.children().each(function(index) {
										    	if (index == 2 || index == 3)
										    	{
										    		$(this).addClass('width-10');
										    		$(this).html('<div class="texte" title="' + $(this).html() + '">' + $(this).html() + '</div>');
										    	} else if (index == 4)
										    	{
										    		$(this).addClass('width-15');
										    		$(this).html('<div class="texte" title="' + $(this).html() + '">' + $(this).html() + '</div>');
										    	} else if (index == 5)
										    	{
										    		$(this).addClass('width-20');
										    		$(this).html('<div class="texte" title="' + rowPrevData[5][1] + '">' + $(this).html() + '</div>');
										    	} else if (index == 6 || index == 7)
										    	{
										    		$(this).addClass('width-5');
										    		$(this).html('<div class="nombre" title="' + $(this).html() + '" style="padding-left: 5px; padding-right: 5px;">' + $(this).html() + '</div>');
										    	} else if (index == 8 || index == 9 || index == 12)
										    	{
										    		$(this).html('<div class="nombre' + ($(this).html().replace( /,/, "." ).replace( / /g, "" ).replace( /€/, "" ).replace( /%/, "" ) > 0 ? ' positif' : ($(this).html().replace( /,/, "." ).replace( / /g, "" ).replace( /€/, "" ).replace( /%/, "" ) < 0 ? ' negatif' : '')) + '" style="padding-left: 5px; padding-right: 5px;">' + $(this).html() + '</div>');
										    	} else if (index == 10)
										    	{
										    		$(this).html('<div class="nombre' + (rowPrevData[15] ? ' orange' : '') + '" style="padding-left: 5px; padding-right: 5px;">' + $(this).html() + '</div>');
										    	} else if (index == 11)
										    	{
										    		$(this).html('<div class="nombre' + (rowPrevData[16] ? ' orange' : '') + '" style="padding-left: 5px; padding-right: 5px;">' + $(this).html() + '</div>');
										    	} else if (index > 0)
										    	{
										    		$(this).html('<div>' + $(this).html() + '</div>');
										    	}
										    });

									    	table.row( trPrev ).child.hide();
									    	trPrev.removeClass('shown');
					    				}
					    			}
						    	});
		            		}, 
		            		function() { }
		            	);
				    }
			    } );
				@endif

		  	}
		});
	}

</script>