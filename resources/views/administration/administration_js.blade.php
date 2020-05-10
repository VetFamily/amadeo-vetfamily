<script type="text/javascript">
	/* Chargement de la liste des cliniques */
	function loadListCliniques() {

		var json = $.parseJSON("{{ htmlspecialchars(Session::get('cliniques_liste')) }}".replace( /&quot;/g, '"' ));

		$('.cliniques').dropdown({
		    data: json,
		    limitCount: Infinity,
		    multipleMode: 'label',
		    choice: function () {
		      	// console.log(arguments,this);
		    }
	  	});

	  	// Sélection du premier élément
	  	var firstClinique = $('.cliniques .dropdown-option:first');
	  	firstClinique.addClass(' dropdown-chose');
	  	$('.cliniques .dropdown-search').remove();
	  	$('.cliniques .dropdown-chose-list').append('<span class="dropdown-selected">' + firstClinique.html() + '<img src="/images/CROIX_FERMETURE_BLANC.svg" class="del" data-id="' + firstClinique.data('value') + '"></span>');

		loadListCodesCentralesCliniques(getSelectValueById("estimationRFA-cliniques[]"));

		$(".cliniques .dropdown-option").click(function(){
			loadListCodesCentralesCliniques($(this).data('value'));
		});
	}

	/* Chargement de la liste des codes centrales des cliniques */
	function loadListCodesCentralesCliniques(clinique) {
		var params = {
			"_token": document.getElementsByName("_token")[0].value,
			"clinique": clinique
		};

		$.ajax({
			url: "getCodesCentralesClinique", 
			type: "POST",
			dataType: "json",
			data: $.param(params),
			success: function(json) {
				//json.splice(0, 0, {"id": 0, name: "Toutes", "selected": true});

				if ($('.codes_centrale_clinique').hasClass('dropdown-multiple-label'))
				{
					$('.codes_centrale_clinique').dropdown().clearAll;
				}
				$('.codes_centrale_clinique').html('<select style="display:none" id="estimationRFA-codes_centrale_clinique[]" name="codes_centrale_clinique[]" multiple></select>');
				
				$('.codes_centrale_clinique').dropdown({
					data: json,
					limitCount: Infinity,
					multipleMode: 'label',
					choice: function () {
					  // console.log(arguments,this);
					}
				});
			}
		});
	}

	$(function()
	{
		$('.administration-criteres-dropdown a').click(function(e)
		{
			e.stopPropagation();
		    $(this).closest('.administration-encart').children().eq(1).toggleClass('active');
		});
	
	});

	/* Génère l'estimation des remises de fin d'année */
	function loadExportEstimationRFA() {
		$("#generateEstimationRfaButton").click(function(){
			// Récupération des paramètres du formulaire
			var token = document.getElementsByName("_token")[0].value,
				mois_debut = document.getElementById("estimationRFA-periode-mois-debut").value,
				mois_fin = document.getElementById("estimationRFA-periode-mois-fin").value,
				annee_debut = document.getElementById("estimationRFA-periode-annee-debut").value,
				annee_fin = document.getElementById("estimationRFA-periode-annee-fin").value,
				cliniques = getSelectValueById("estimationRFA-cliniques[]"),
				codes_centrales = getSelectValueById("estimationRFA-codes_centrale_clinique[]"),
				annee_obj = document.getElementById("estimationRFA-annee-objectif").value;

			window.location = 'exportEstimationRFAExcel/0/' + mois_debut + '/' + annee_debut + '/' + mois_fin + '/' + annee_fin + '/' + cliniques + '/' + codes_centrales + '/' + annee_obj;

		});      

		$("#generateEstimationRfaDetailButton").click(function(){
			// Récupération des paramètres du formulaire
			var token = document.getElementsByName("_token")[0].value,
				mois_debut = document.getElementById("estimationRFA-periode-mois-debut").value,
				mois_fin = document.getElementById("estimationRFA-periode-mois-fin").value,
				annee_debut = document.getElementById("estimationRFA-periode-annee-debut").value,
				annee_fin = document.getElementById("estimationRFA-periode-annee-fin").value,
				cliniques = getSelectValueById("estimationRFA-cliniques[]"),
				codes_centrales = getSelectValueById("estimationRFA-codes_centrale_clinique[]"),
				annee_obj = document.getElementById("estimationRFA-annee-objectif").value;

			window.location = 'exportEstimationRFAExcel/1/' + mois_debut + '/' + annee_debut + '/' + mois_fin + '/' + annee_fin + '/' + cliniques + '/' + codes_centrales + '/' + annee_obj;

		});      
	}

	/* Génère le bilan des remises de fin d'année */
	function loadExportBilanRFA() {
		$("#generateBilanRfaButton").click(function(){
			// Récupération des paramètres du formulaire
			var token = document.getElementsByName("_token")[0].value,
				annee = document.getElementById("bilanRFA-annee").value;

			var message = '<p class="avertissement">Le bilan sera disponible sur le FTP à l\'issue du traitement. Pour information, la génération du bilan dure quelques minutes.</p><p class="question">@lang("amadeo.save.question")</p>';
			    	
        	confirmBox(
        		"Export du bilan RFA administrateur", 
        		message, 
        		'@lang("amadeo.yes")',
        		'@lang("amadeo.no")', 
        		function()
        		{	
					$.ajax({
						url: 'exportBilanRFAExcel/0/' + annee, 
						type: "GET",
						success: function(json) {
                            bootoast.toast({
                                message: 'L\'export est en cours de génération.',
                                type: 'success'
                            });
						}
					});	
        		}, 
        		function() {}
            );
		});      

		$("#generateBilanRfaDetailButton").click(function(){
			// Récupération des paramètres du formulaire
			var token = document.getElementsByName("_token")[0].value,
				annee = document.getElementById("bilanRFA-annee").value;

        	var message = '<p class="avertissement">Le bilan sera disponible sur le FTP à l\'issue du traitement. Pour information, la génération du bilan dure environ 4h30.</p><p class="question">@lang("amadeo.save.question")</p>';
			    	
        	confirmBox(
        		"Export du bilan RFA administrateur", 
        		message, 
        		'@lang("amadeo.yes")',
        		'@lang("amadeo.no")',
        		function()
        		{	
					$.ajax({
						url: 'exportBilanRFAExcel/1/' + annee, 
						type: "GET",
						success: function(json) {
                            bootoast.toast({
                                message: 'L\'export est en cours de génération.',
                                type: 'success'
                            });
						}
					});	
        		}, 
        		function() {}
            );

		});      
	}

	/* Génère l'extraction des prix nets */
	function loadExportExtractionPrixNets() {
		$("#generateExtractionPrixNetsButton").click(function(){
			// Récupération des paramètres du formulaire
			var annee = document.getElementById("extractionPrixNet-annee").value,
				remise_centrale = document.getElementById("extractionPrixNet-remise-centrale").value != null ? document.getElementById("extractionPrixNet-remise-centrale").value : 0;

			if (remise_centrale == null || remise_centrale == "")
			{
				bootoast.toast({
					message: 'La remise centrale est obligatoire.',
					type: 'danger'
				});
			} else
			{
				window.location = 'exportExtractionPrixNetsExcel/' + annee + '/' + remise_centrale.replace( /,/, "." );
			}
		});
	}




</script>