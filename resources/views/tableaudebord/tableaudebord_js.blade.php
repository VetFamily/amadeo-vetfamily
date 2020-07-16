<script type="text/javascript">

	/* Ouverture du menu de navigation */
	function openNav() {
		$('#parametrage').css('width', '345px');
		$('#parametrage').css('padding', '0px 5px 5px 5px');
		$('#main').css('paddingLeft', '345px');
		$('.open_params_menu').css('display', 'none');
	}

	/* Fermeture du menu de navigation */
	function closeNav() {
		$('#parametrage').css('width', '0');
		$('#parametrage').css('padding', '0');
		$('#main').css('paddingLeft', '0');
		$('.open_params_menu').css('display', 'block');
	}

	// Variables globales
	var listeObjectifsAtteints, listeObjectifsAtteintsConditionKo, listeObjectifsSecurite, listeObjectifsLignePlus, listeObjectifsLigneMoins, listeObjectifsDanger;
	
	function loadTbdGeneral()
	{
		var dateMAJ = "{{ Session::get('last_date') }}".split("-");
		$('.tbd_graphe').html('<div id="load" class="load">{{ Html::image("images/activity_indicator.gif", "Chargement en cours") }}</div><canvas id="tbd_general_pie"></canvas>');
		
		var annee = dateMAJ[0];
		$('#commentaire_general').html("Year : " + annee);
		
    	var params = {
			"_token": document.getElementsByName("_token")[0].value,
			"jour": getDayOfPeriod(annee, dateMAJ[1]-1, (dateMAJ[0] > 15 ? 15 : 1), 0)
		};

		$.ajax({
		    dataType: 'json',
		    url: "tableaudebord-general", 
	        type: "POST",
	        data: $.param(params),
		    success: function (data) 
		    {
		    	// Récupération des listes d'objectifs
		    	listeObjectifsAtteints = data.atteints;
		    	listeObjectifsAtteintsConditionKo = data.atteints_condition_ko;
		    	listeObjectifsSecurite = data.securite;
		    	listeObjectifsLignePlus = data.ligne_plus;
		    	listeObjectifsLigneMoins = data.ligne_moins;
		    	listeObjectifsDanger = data.danger;

		    	var myChartConfig = {
			      type: 'pie',
			      data: 
			      {
			        labels: ["Atteint", "Atteint condition non atteinte", "En sécurité", "En ligne +", "En ligne -", "En danger"],
			        datasets: [
					    {
					      data: [listeObjectifsAtteints.length, listeObjectifsAtteintsConditionKo.length, listeObjectifsSecurite.length, listeObjectifsLignePlus.length, listeObjectifsLigneMoins.length, listeObjectifsDanger.length],
					      backgroundColor: ['#096A09', '#18A55D', '#1174B5', '#43CCCB', '#FD852F', '#DB4C3F']
					    }
					]
			      },
			      options: 
			      {
			      	legend: false,
					animation: {
						duration: 2000,
						onProgress: function(animation) {
							document.getElementById('load').style.display = "none";
						}
					}
			      }
			    };

				var myChart = new Chart(document.getElementById("tbd_general_pie"), myChartConfig);
		    }
		});
	}

	function generateTable(index)
	{
		var title = "Liste des objectifs ";
		var data;
		switch (index)
		{
			case 0:
				title += "atteints";
				data = listeObjectifsAtteints;
				break;
			case 1:
				title += "atteints condition non atteinte";
				data = listeObjectifsAtteintsConditionKo;
				break;
			case 2:
				title += "en sécurité";
				data = listeObjectifsSecurite;
				break;
			case 3:
				title += "en ligne +";
				data = listeObjectifsLignePlus;
				break;
			case 4:
				title += "en ligne -";
				data = listeObjectifsLigneMoins;
				break;
			case 5:
				title += "en danger";
				data = listeObjectifsDanger;
				break;
		};

		$('#tbd_tableau_title').html(title);

	    // DataTable
	    var table = $('#tab-objectifs').DataTable( {
	    	"language": {
              "url": "//cdn.datatables.net/plug-ins/1.10.16/i18n/French.json",
            	"decimal": ",",
    			"thousands": " "
            },
            "bSortCellsTop": true,
            "bLengthChange": false,
            "bAutoWidth": false,
            "scrollY": "50vh",
            "paging": false,
            "info": false,
            "destroy": true,
			"scrollCollapse": true,
			"order": [[ 1, "asc" ], [ 2, "asc" ]],
			"aoColumns": [ 
				{"sWidth": "10%"}, 
				{"sWidth": "10%"}, 
				{"sWidth": "30%"}, 
				{
					"render": function ( data, type, row ) {
						return (data != null) ? numberWithSpaces(data) + (row[6] != null ? ' ' + row[6] : '') : '';
					}, 
					"sType": "numeric-comma",
					"sWidth": "5%" 
				}, 
            	{
					"render": function ( data, type, row ) {
						return (data != null) ? numberWithSpaces(data) : '-';
					}, 
					"sType": "numeric-comma",
					"sWidth": "5%"  
				}, 
            	{
					"render": function ( data, type, row ) {
						if (data != '-')
							return (data != null) ? numberWithSpaces(parseFloat(data.toString().replace( /,/, "." ).replace( / /g, "" )).toFixed(2)) + (row[6] != null ? ' ' + row[6] : '') : '-';
						else
							return data;
					}, 
					"sType": "numeric-comma",
					"sWidth": "5%"  
				}
	        ],
			"aaData": data,
			"createdRow": function ( row, data, index ) {
				$('td', row).each(function(){
					$(this).html('<div>' + $(this).html() + '</div>');
				});

				$('td', row).eq(0).addClass('width-5');
				$('td', row).eq(0).find('div').addClass('texte');
				$('td', row).eq(0).find('div').attr('title', $('td', row).eq(0).find('div').html());
				$('td', row).eq(1).addClass('width-5');
				$('td', row).eq(1).find('div').addClass('texte');
				$('td', row).eq(1).find('div').attr('title', $('td', row).eq(1).find('div').html());
				$('td', row).eq(2).addClass('width-20');
				$('td', row).eq(2).find('div').addClass('texte');
				$('td', row).eq(2).find('div').attr('title', $('td', row).eq(2).find('div').html());
				$('td', row).eq(3).addClass('width-15');
				$('td', row).eq(3).find('div').addClass('texte');
				$('td', row).eq(3).find('div').attr('title', $('td', row).eq(3).find('div').html());
				$('td', row).eq(4).addClass('width-15');
				$('td', row).eq(4).find('div').addClass('texte');
				$('td', row).eq(4).find('div').attr('title', $('td', row).eq(4).find('div').html());
				$('td', row).eq(5).addClass('width-15');
				$('td', row).eq(5).find('div').addClass('texte');
				$('td', row).eq(5).find('div').attr('title', $('td', row).eq(5).find('div').html());
	        },
			initComplete: function () {
	            var api = this.api();
	            $('#tab-objectifs thead tr#forFilters th').each(function(i) {
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
	                	var input = $('<input type="text" placeholder="" style="width:100%;" />').on('keyup', function() {
	    					var val = $.fn.dataTable.util.escapeRegex($(this).val());
	    					column.search(val ? val : '', true, false).draw();
	    				});
	    				$('#forFilters th').eq(i).html(input);
	    			}
	            });

	            document.getElementById('tableau').style.display = "block";
	            $( "#echeanceAvancement" ).html(new Date("{{ date('Y') }}", 0).toLocaleString("fr", { month: "short" }) + " à " + new Date("{{ date('Y') }}", "{{ (date('d') > 5 ? (int)(date('m')-2) : (int)(date('m')-3)) }}").toLocaleString("fr", { month: "short" }) + " " + "{{ date('Y') }}");
	            // Suppression de la recherche globale du tableau
			    document.getElementById('tab-objectifs_filter').style.display = "none";

			    table.columns.adjust();

			}
	    });
	}

</script>