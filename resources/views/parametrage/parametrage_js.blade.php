<script type="text/javascript">

  var tabClinics = null;
  var nbSelectedClinics = 0;
  var nbSelectedClinicsTmp = 0;
  var tabProductTherapeuticClasses = null;
  var tabProducts = null;
  var nbSelectedProducts = 0;
  var nbSelectedProductsTmp = 0;
  var currentScreen = "";
  @if (Request::is('statistiques'))
    currentScreen = "purchases";
  @endif

  
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

  $(function()
  { 
    $(".onglets").tabs({ 
      show: {effect: "fade", duration: 500}, 
      hide: {effect: "fade", duration: 500} 
    }); 

    // Masquage des panneaux de critères par défaut
    $panneaux = $('div.params-panneau').hide();
    // Affichage des panneaux de critères
    $('.params-criteres a').click(function() 
    {
      if ($(this).attr('aria-expanded') == 'false') 
      {
              $(this).attr('aria-expanded', true).parent().next($panneaux).show();
              $(this).parent().css('background', '#01b4bc');
              $(this).parent().css('color', '#FFFFFF');
              $(this).parent().find('img').attr('src', '/images/REPLIER_BLANC.svg');
              $(this).parent().find('img').attr('title', 'Replier');
      } 
      else 
      {
        $(this).attr('aria-expanded', false).parent().next($panneaux).hide();
              $(this).parent().css('background', '#D8D8D8');
              $(this).parent().css('color', '#212629');
              $(this).parent().find('img').attr('src', '/images/DEPLIER_NOIR.svg');
              $(this).parent().find('img').attr('title', 'Déplier');
      }
      return false;
    });

    $('.params-criteres-dropdown a').click(function(e)
    {
      e.stopPropagation();
        $(this).closest('.params-encart').children().eq(1).toggleClass('active');
    });
  });

  /* Loading years of clinic entries */
  function loadListOfClinicYears() {
    // Remove the drop-down list of years of clinic entries 
    if ($('.clinic-years option').length > 0)
    {
      $('.clinic-years').dropdown().clearAll;
      $('.clinic-years').html('<select style="display:none" id="clinic-years[]" name="clinic-years[]" multiple></select>');
    }

    // Create the drop-down list
    var json = [];
    var years = [];
    for (year=2016 ; year < ((new Date()).getFullYear() + 2) ; year++)
    {
      var selected = false;
      if ((sessionStorage.getItem(currentScreen + "-clinicYears") == null && year < ((new Date()).getFullYear() + 1) && year != 2016) 
        || (sessionStorage.getItem(currentScreen + "-clinicYears") != null && JSON.parse(sessionStorage.getItem(currentScreen + "-clinicYears")).includes(year.toString())))
      {
        selected = true;
        years.push(year.toString());
      }
      json.push({
        "id": year, 
        "name": year, 
        "selected": selected
      });
    }

    // Initialize the dropdown list
    $('.clinic-years').dropdown({
      data: json,
      limitCount: Infinity,
      multipleMode: 'label',
      choice: function () {
        // console.log(arguments,this);
      }
    });

    // Max height
    $('.clinic-years .dropdown-main ul').css('max-height', '130px');
  }

  /* Save count of total clinics by step 1 */
  function saveCountOfAllClinics()
  {
    $('.nbTotalClinicsModal').html("-");

    var params = {
      "_token": document.getElementsByName("_token")[0].value,
      "currentScreen": currentScreen
    };

    // Searching products
    $.ajax({
      type: "POST",
      url: "getCountOfClinicsByParams", 
      dataType: "json",
      data: $.param(params),
      success: function(json) {
        var count = json[0]['count'];
        $('.nbTotalClinicsModal').html(count);
        nbSelectedClinics = (sessionStorage.getItem(currentScreen + "-selectedClinics") != null ? (JSON.parse(sessionStorage.getItem(currentScreen + "-selectedClinics")).length) : 0);
        $('#nbFilteredClinics').html(nbSelectedClinics != 0 ? nbSelectedClinics : count);
      } 
    });
  }

  /* Save count of filtered clinics by step 1 */
  function saveCountOfClinicsStep1(clinicYears)
  {
    $('#nbFilteredClinicsStep1Modal').html("-");

    var params = {
      "_token": document.getElementsByName("_token")[0].value,
      "currentScreen": currentScreen,
      "clinicYears": clinicYears
    };

    // Searching clinics
    $.ajax({
      type: "POST",
      url: "getCountOfClinicsByParams", 
      dataType: "json",
      data: $.param(params),
      success: function(json) {
        $('#nbFilteredClinicsStep1Modal').html(json[0]['count']);
      } 
    });
  }

  /* Loading clinics */
  function loadListOfClinics(clinicYears, selectedClinics)
  {
    nbSelectedClinicsTmp = nbSelectedClinics;
    if (selectedClinics != null)
    {
      $('#nbFilteredClinicsStep2Modal').html(selectedClinics.length);
    } else 
    {
      $('#nbFilteredClinicsStep2Modal').html("0");
    }
    
    document.getElementById('tableClinicsModal').style.display = "none";
    document.getElementById('loadClinicsModal').style.display = "block";

    var params = {
      "_token": document.getElementsByName("_token")[0].value,
      "currentScreen": currentScreen,
      "clinicYears": clinicYears,
      "selectedClinics": selectedClinics
    };

    // Searching clinics
    $.ajax({
      type: "POST",
      url: "getListOfClinicsByParams", 
      dataType: "json",
      data: $.param(params),
      success: function(json) {
        var data = jQuery.map(json, function(el, i) {
          return [[ el.veterinaires, el.nom, el.code_postal, el.ville, el.id ]];
        });

        if (tabClinics != null)
        {
          tabClinics.destroy();
        }

        // DataTable
        tabClinics = $('#tab-clinics').DataTable( {
          "language": {
            "url": "//cdn.datatables.net/plug-ins/1.10.16/i18n/French.json",
            "emptyTable": "Aucune clinique correspondante",
            "decimal": ",",
            "thousands": " "
          },
          "bSortCellsTop": true,
          "destroy": true,
          "bLengthChange": false,
          "bAutoWidth": false,
          "scrollY": "40vh",
          "paging": true,
          "pageLength": 25,
          "info": false,
          "scrollCollapse": true,
          "columnDefs": [ {
            "targets": 0,
            "orderable": false
          } ],
          "order": [[ 0, "asc" ], [ 1, "asc" ]],
          "aoColumns": [  
            {"sWidth": "30%"},  
            {"sWidth": "30%"}, 
            {"sWidth": "8%"}, 
            {"sWidth": "12%"}
          ],
          "aaData": data,
          "createdRow": function ( row, data, index ) {
            if (sessionStorage.getItem(currentScreen + "-selectedClinics") != null && JSON.parse(sessionStorage.getItem(currentScreen + "-selectedClinics")).includes(data[4]))
            {
                $(row).addClass("selected");
            }

            $('td', row).each(function(){
              $(this).html('<div>' + $(this).html() + '</div>');
            });
            $('td', row).eq(0).addClass('width-30');
            $('td', row).eq(0).find('div').addClass('texte');
            $('td', row).eq(1).addClass('width-30');
            $('td', row).eq(1).find('div').addClass('texte');
            $('td', row).eq(2).addClass('width-5');
            $('td', row).eq(2).find('div').addClass('texte');
            $('td', row).eq(3).addClass('width-10');
            $('td', row).eq(3).find('div').addClass('texte');
          },
          initComplete: function () {
            var api = this.api();
            $('#tab-clinics thead tr#forFiltersClinicsModal th').each(function(i) {
              var column = api.column(i);
              if ($(this).hasClass('select-filter')) 
              {
                // Création des select pour les colonnes de classe "select-filter"
                var select = $('<select id="filter-column-'+ i + '" style="width:100%;"><option value=""></option></select>').on('change', function() {
                  var val = $.fn.dataTable.util.escapeRegex($(this).val());
                  column.search(val ? '^' + val + '$' : '', true, false).draw();
                });
                $('#forFiltersClinicsModal th').eq(i).html(select);
                column.data().unique().sort().each(function(d, j) {
                  if (d != null)
                    select.append('<option value="' + d + '">' + d + '</option>')
                });
              } else if ($(this).hasClass('text-filter'))
              {
                // Création des input pour les colonnes de classe "text-filter"
                var input = $('<input type="text" id="filter-column-'+ i + '" placeholder="" style="width:100%;" />').on('keyup', function() {
                  var val = $.fn.dataTable.util.escapeRegex($(this).val());
                  column.search(val ? val : '', true, false).draw();
                });
                $('#forFiltersClinicsModal th').eq(i).html(input);
              }
            });
            document.getElementById('tableClinicsModal').style.display = "block";
            document.getElementById('loadClinicsModal').style.display = "none";
            // Suppression de la recherche globale du tableau
            document.getElementById('tab-clinics_filter').style.display = "none";

            tabClinics.columns.adjust();

            // Select rows on click
            $('#tab-clinics').off( 'click.rowClick' ).on('click.rowClick', 'td', function () {
              if ($(this).parent().hasClass('selected'))
              /* Line is unselected */
              {
                $(this).parent().removeClass('selected');
                // Update count of selected products
                nbSelectedClinicsTmp--;
              } else
              /* Line is selected */
              {
                $(this).parent().addClass('selected');
                // Update count of selected clinics
                nbSelectedClinicsTmp++;
              } 
              $('#nbFilteredClinicsStep2Modal').html(nbSelectedClinicsTmp);
            });

            // Select and unselect links
            $('<div class="dataTables_buttons"><div><a id="selectAllFilteredClinics">@lang("amadeo.select-all-filtered")</a><a id="selectAllClinics">@lang("amadeo.select-all")</a></div><div><a id="unselectAllFilteredClinics">@lang("amadeo.unselect-all-filtered")</a><a id="unselectAllClinics">@lang("amadeo.unselect-all")</a></div></div>').insertBefore('#tab-clinics_paginate');
            $('#selectAllClinics').on('click', function() 
            {
              nbSelectedClinicsTmp = 0;
              tabClinics.rows().every(function() {
                this.nodes().to$().addClass('selected');
                // Update count of selected clinics
                nbSelectedClinicsTmp++;
              })
              $('#nbFilteredClinicsStep2Modal').html(nbSelectedClinicsTmp);
            });

            $('#unselectAllClinics').on('click', function() 
            {
              nbSelectedClinicsTmp = 0;
              tabClinics.rows().every(function() {
                this.nodes().to$().removeClass('selected')
              })
              $('#nbFilteredClinicsStep2Modal').html(nbSelectedClinicsTmp);
            });

            $('#selectAllFilteredClinics').on('click', function() 
            {
              tabClinics.rows( { search: 'applied' } ).every(function() {
                if (!this.nodes().to$().hasClass('selected'))
                {
                  this.nodes().to$().addClass('selected')
                  // Update count of selected clinics
                  nbSelectedClinicsTmp++;
                }
              })
              $('#nbFilteredClinicsStep2Modal').html(nbSelectedClinicsTmp);
            });

            $('#unselectAllFilteredClinics').on('click', function() 
            {
              tabClinics.rows( { search: 'applied' } ).every(function() {
                if (this.nodes().to$().hasClass('selected'))
                {
                  this.nodes().to$().removeClass('selected')
                  // Update count of selected clinics
                  nbSelectedClinicsTmp--;
                }
              })
              $('#nbFilteredClinicsStep2Modal').html(nbSelectedClinicsTmp);
            });
          }
        });
      }
    });
  }

  /* Loading display types : detail by product, detail by clinic, sum by laboratory or sum by group */
  function loadDisplayTypes()
  {
    switch (currentScreen)
    {
      case "purchases":
        // Default value : sum by laboratory
        $('#display-type-laboratory').prop("checked", true);

        if (sessionStorage.getItem("purchases-displayType") != null && sessionStorage.getItem("purchases-displayType").includes("product"))
        {
          $('#display-type-product').prop("checked", true);
        }

        if (sessionStorage.getItem("purchases-displayType") != null && sessionStorage.getItem("purchases-displayType").includes("clinic"))
        {
          $('#display-type-clinic').prop("checked", true);
        }

        if (sessionStorage.getItem("purchases-displayType") != null && sessionStorage.getItem("purchases-displayType").includes("category"))
        {
          $('#display-type-category').prop("checked", true);
        }

        break;
    }
  }
  
  /* Loading laboratories */
  function loadListOfLaboratories() {
    // Remove the drop-down list of laboratories
    if ($('.laboratories option').length > 0)
    {
      $('.laboratories').dropdown().clearAll;
      $('.laboratories').html('<select style="display:none" id="laboratories[]" name="laboratories[]" multiple></select>');
    }

    // Create the drop-down list
    var json = [];
    var laboratories = [];
    var list_of_laboratories = $.parseJSON("{{ htmlspecialchars(Session::get('list_of_laboratories')) }}".replace( /&quot;/g, '"' ));
    for (var i = 0; i < list_of_laboratories.length; i++) {
      var id = list_of_laboratories[i]["id"];
      var name = list_of_laboratories[i]["name"];
      var selected = false;
      if (sessionStorage.getItem(currentScreen + "-laboratories") != null && JSON.parse(sessionStorage.getItem(currentScreen + "-laboratories")).includes(id.toString()))
      {
        // Select value if first page loading or criterion already selected by user
        selected = true;
        laboratories.push(id);
      }
      json.push({
        "id": id, 
        "name": name, 
        "selected": selected
      });
    }

    // Initialize the dropdown list
    $('.laboratories').dropdown({
      data: json,
      limitCount: Infinity,
      multipleMode: 'label',
      choice: function () {
        // console.log(arguments,this);
      }
    });

    $('#selectAllLaboratories').on('click', function() 
    {
      $('select[id^=laboratories]').children().each(function(index){
        $(this).prop('selected', true);
      });

      $('.laboratories li.dropdown-option').each(function(index){
        $(this).addClass("dropdown-chose");
      });
    });

    $('#unselectAllLaboratories').on('click', function() 
    {
      $('select[id^=laboratories]').children().each(function(index){
        $(this).prop('selected', false);
      });

      $('.laboratories li.dropdown-option').each(function(index){
        $(this).removeClass("dropdown-chose");
      });
    });
  }

  /* Loading valorizations */
  function loadListOfValorizations() {
    // Default value : central
    $('#valorization-1').prop("checked", true);

    if (sessionStorage.getItem(currentScreen + "-valorization") != null && sessionStorage.getItem(currentScreen + "-valorization").includes("2"))
    {
      $('#valorization-2').prop("checked", true);
    }else if (sessionStorage.getItem(currentScreen + "-valorization") != null && sessionStorage.getItem(currentScreen + "-valorization").includes("3"))
    {
      $('#valorization-3').prop("checked", true);
    }
  }

  /* Loading product types */
  function loadListOfProductTypes()
  {
    if (sessionStorage.getItem(currentScreen + "-productTypes") != null)
    {
      // Types selected by user
      var types = JSON.parse(sessionStorage.getItem(currentScreen + "-productTypes"));
      for (var i = 0; i < types.length; i++) {
        $('input[id^=product-type-' + types[i] + ']').prop("checked", true);
      }
    } else 
    {
      // All types are unselected
      $('input[id^=product-type-]').prop("checked", false);
    }
  }

  /* Loading product species */
  function loadListOfProductSpecies()
  {
    if (sessionStorage.getItem(currentScreen + "-productSpecies") != null)
    {
      // Species selected by user
      var species = JSON.parse(sessionStorage.getItem(currentScreen + "-productSpecies"));
      for (var i = 0; i < species.length; i++) {
        $('input[id^=product-species-' + species[i] + ']').prop("checked", true);
      }
    } else 
    {
      // All species are unselected
      $('input[id^=product-species-]').prop("checked", false);
    }
  }

  /* Save count of total products by step 1 */
  function saveCountOfAllProducts()
  {
    $('.nbTotalProductsModal').html("-");

    var params = {
      "_token": document.getElementsByName("_token")[0].value,
      "currentScreen": currentScreen
    };

    // Searching products
    $.ajax({
      type: "POST",
      url: "getCountOfProductsByParams", 
      dataType: "json",
      data: $.param(params),
      success: function(json) {
        var count = json[0]['count'];
        $('.nbTotalProductsModal').html(count);
        nbSelectedProducts = (sessionStorage.getItem(currentScreen + "-selectedProducts") != null ? (JSON.parse(sessionStorage.getItem(currentScreen + "-selectedProducts")).length) : 0);
        $('#nbFilteredProducts').html(nbSelectedProducts != 0 ? nbSelectedProducts : count);
      } 
    });
  }

  /* Save count of filtered products by step 1 */
  function saveCountOfProductsStep1(laboratories, productTypes, productSpecies)
  {
    $('#nbFilteredProductsStep1Modal').html("-");

    var params = {
      "_token": document.getElementsByName("_token")[0].value,
      "currentScreen": currentScreen,
      "laboratories": laboratories,
      "productTypes" : productTypes,
      "productSpecies" : productSpecies
    };

    // Searching products
    $.ajax({
      type: "POST",
      url: "getCountOfProductsByParams", 
      dataType: "json",
      data: $.param(params),
      success: function(json) {
        $('#nbFilteredProductsStep1Modal').html(json[0]['count']);
      } 
    });
  }

  /* Save count of filtered products by step 2 */
  function saveCountOfProductsStep2(laboratories, productTypes, productSpecies, therapeuticClasses)
  {
    $('#nbFilteredProductsStep2Modal').html("-");

    var params = {
      "_token": document.getElementsByName("_token")[0].value,
      "currentScreen": currentScreen,
      "laboratories": laboratories,
      "productTypes" : productTypes,
      "productSpecies" : productSpecies,
      "therapeuticClasses" : therapeuticClasses
    };

    // Searching products
    $.ajax({
      type: "POST",
      url: "getCountOfProductsByParams", 
      dataType: "json",
      data: $.param(params),
      success: function(json) {
        $('#nbFilteredProductsStep2Modal').html(json[0]['count']);
      } 
    });
  }

  /* Loading therapeutic classes */
  function loadListOfProductTherapeuticClasses(laboratories, productTypes, productSpecies)
  {
    document.getElementById('tableProductTherapeuticClassesModal').style.display = "none";
    document.getElementById('loadProductTherapeuticClassesModal').style.display = "block";

    var params = {
      "_token": document.getElementsByName("_token")[0].value,
      "currentScreen": currentScreen,
      "laboratories": laboratories,
      "productTypes" : productTypes,
      "productSpecies" : productSpecies
    };

    // Searching therapeutic classes
    $.ajax({
      type: "POST",
      url: "getListOfTherapeuticClassesByParams", 
      dataType: "json",
      data: $.param(params),
      success: function(json) {
        var data = jQuery.map(json, function(el, i) {
          return [[ el.classe1_nom, el.classe2_nom, el.classe3_nom, ((el.classe1_code != null ? el.classe1_code : '') + (el.classe2_code != null ? el.classe2_code : '') + (el.classe3_code != null ? el.classe3_code : '')), el.id ]];
        });
        
        if (tabProductTherapeuticClasses != null)
        {
          tabProductTherapeuticClasses.destroy();
        }

        // DataTable
        tabProductTherapeuticClasses = $('#tab-products-therapeuticClasses').DataTable( {
          "language": {
            "url": "//cdn.datatables.net/plug-ins/1.10.16/i18n/French.json",
            "decimal": ",",
            "thousands": " "
          },
          "bSortCellsTop": true,
          "bLengthChange": false,
          "bAutoWidth": false,
          "destroy": true,
          "scrollY": "40vh",
          "paging": true,
          "pageLength": 25,
          "info": false,
          "scrollCollapse": true,
          "columnDefs": [ {
            "targets": 0,
            "orderable": false
          } ],
          "order": [[ 0, "asc" ], [1, "asc"]],
          "aoColumns": [ 
            {"sWidth": "30%"},  
            {"sWidth": "30%"},  
            {"sWidth": "30%"},
            {"sWidth": "12%"}
          ],
          "aaData": data,
          "createdRow": function ( row, data, index ) {
            if ( sessionStorage.getItem(currentScreen + "-selectedProductTherapeuticClasses") != null && JSON.parse(sessionStorage.getItem(currentScreen + "-selectedProductTherapeuticClasses")).includes(data[4]))
            {
                $(row).addClass("selected");
            }

            $('td', row).each(function(){
              $(this).html('<div>' + $(this).html() + '</div>');
            });
            $('td', row).eq(0).addClass('width-30');
            $('td', row).eq(0).find('div').addClass('texte');
            $('td', row).eq(0).find('div').attr('title', $('td', row).eq(0).find('div').html());
            $('td', row).eq(1).addClass('width-30');
            $('td', row).eq(1).find('div').addClass('texte');
            $('td', row).eq(1).find('div').attr('title', $('td', row).eq(0).find('div').html());
            $('td', row).eq(2).addClass('width-30');
            $('td', row).eq(2).find('div').addClass('texte');
            $('td', row).eq(2).find('div').attr('title', $('td', row).eq(0).find('div').html());
            $('td', row).eq(3).addClass('width-10');
            $('td', row).eq(3).find('div').addClass('texte');
            $('td', row).eq(3).find('div').attr('title', $('td', row).eq(0).find('div').html());
          },
          initComplete: function () {
            var api = this.api();
            $('#tab-products-therapeuticClasses thead tr#forFiltersProductTherapeuticClassesModal th').each(function(i) {
              var column = api.column(i);
              if ($(this).hasClass('select-filter')) 
              {
                // Création des select pour les colonnes de classe "select-filter"
                var select = $('<select id="filter-column-'+ i + '" style="width:100%;"><option value=""></option></select>').on('change', function() {
                  var val = $.fn.dataTable.util.escapeRegex($(this).val());
                  column.search(val ? '^' + val + '$' : '', true, false).draw();
                });
                $('#forFiltersProductTherapeuticClassesModal th').eq(i).html(select);
                column.data().unique().sort().each(function(d, j) {
                  if (d != null)
                    select.append('<option value="' + d + '">' + d + '</option>')
                });
              } else if ($(this).hasClass('text-filter'))
              {
                // Création des input pour les colonnes de classe "text-filter"
                var input = $('<input type="text" id="filter-column-'+ i + '" placeholder="" style="width:100%;" />').on('keyup', function() {
                  var val = $.fn.dataTable.util.escapeRegex($(this).val());
                  column.search(val ? val : '', true, false).draw();
                });
                $('#forFiltersProductTherapeuticClassesModal th').eq(i).html(input);
              }
            });
            document.getElementById('tableProductTherapeuticClassesModal').style.display = "block";
            document.getElementById('loadProductTherapeuticClassesModal').style.display = "none";
            // Suppression de la recherche globale du tableau
            document.getElementById('tab-products-therapeuticClasses_filter').style.display = "none";

            tabProductTherapeuticClasses.columns.adjust();

            // Action lors de la sélection d'une ligne
            $('#tab-products-therapeuticClasses').off( 'click.rowClick' ).on('click.rowClick', 'td', function () {
              $(this).parent().toggleClass('selected');
            });

            // Select and unselect links
            $('<div class="dataTables_buttons"><div><a id="selectAllFilteredProductTherapeuticClasses">@lang("amadeo.select-all-filtered")</a><a id="selectAllProductTherapeuticClasses">@lang("amadeo.select-all")</a></div><div><a id="unselectAllFilteredProductTherapeuticClasses">@lang("amadeo.unselect-all-filtered")</a><a id="unselectAllProductTherapeuticClasses">@lang("amadeo.unselect-all")</a></div></div>').insertBefore('#tab-products-therapeuticClasses_paginate');
            $('#selectAllProductTherapeuticClasses').on('click', function() 
            {
              tabProductTherapeuticClasses.rows().every(function() {
                this.nodes().to$().addClass('selected')
              })
            });

            $('#unselectAllProductTherapeuticClasses').on('click', function() 
            {
              tabProductTherapeuticClasses.rows().every(function() {
                this.nodes().to$().removeClass('selected')
              })
            });

            $('#selectAllFilteredProductTherapeuticClasses').on('click', function() 
            {
              tabProductTherapeuticClasses.rows( { search: 'applied' } ).every(function() {
                this.nodes().to$().addClass('selected')
              })
            });

            $('#unselectAllFilteredProductTherapeuticClasses').on('click', function() 
            {
              tabProductTherapeuticClasses.rows( { search: 'applied' } ).every(function() {
                this.nodes().to$().removeClass('selected')
              })
            });
          }
        });
      }
    });
  }

  /* Loading central purchasing */
  function loadListOfCentralPurchasing()
  {
    if (sessionStorage.getItem(currentScreen + "-centralPurchasing") != null)
    {
      // Central purchasing selected by user
      var centralPurchasing = JSON.parse(sessionStorage.getItem(currentScreen + "-centralPurchasing"));
      for (var i = 0; i < centralPurchasing.length; i++) {
        $('input[id^=central-purchasing-' + centralPurchasing[i] + ']').prop("checked", true);
      }
    } else 
    {
      // All central purchasing are selected
      $('input[id^=central-purchasing-]').prop("checked", true);
    }
  }

  /* Loading products */
  function loadListOfProducts(laboratories, productTypes, productSpecies, therapeuticClasses, selectedProducts)
  {
    nbSelectedProductsTmp = nbSelectedProducts;
    if (selectedProducts != null)
    {
      $('#nbFilteredProductsStep3Modal').html(selectedProducts.length);
    } else 
    {
      $('#nbFilteredProductsStep3Modal').html("0");
    }
    
    document.getElementById('tableProductsModal').style.display = "none";
    document.getElementById('loadProductsModal').style.display = "block";

    var params = {
      "_token": document.getElementsByName("_token")[0].value,
      "currentScreen": currentScreen,
      "laboratories": laboratories,
      "productTypes" : productTypes,
      "productSpecies" : productSpecies,
      "therapeuticClasses" : therapeuticClasses,
      "selectedProducts" : selectedProducts
    };

    // Searching products
    $.ajax({
      type: "POST",
      url: "getListOfProductsByParams", 
      dataType: "json",
      data: $.param(params),
      success: function(json) {
        var data = jQuery.map(json, function(el, i) {
          return [[ el.laboratoire, el.denomination, el.conditionnement, el.code_gtin, el.id ]];
        });
        
        if (tabProducts != null)
        {
          tabProducts.destroy();
        }

        // DataTable
        tabProducts = $('#tab-products').DataTable( {
          "language": {
            "url": "//cdn.datatables.net/plug-ins/1.10.16/i18n/French.json",
            "emptyTable": "Aucun produit correspondant",
            "decimal": ",",
            "thousands": " "
          },
          "bSortCellsTop": true,
          "destroy": true,
          "bLengthChange": false,
          "bAutoWidth": false,
          "scrollY": "40vh",
          "paging": true,
          "pageLength": 25,
          "info": false,
          "scrollCollapse": true,
          "columnDefs": [ {
            "targets": 0,
            "orderable": false
          } ],
          "order": [[ 0, "asc" ], [1, "asc"]],
          "aoColumns": [ 
            {"sWidth": "20%"}, 
            {"sWidth": "30%"},  
            {"sWidth": "30%"},
            {"sWidth": "12%"}
          ],
          "aaData": data,
          "createdRow": function ( row, data, index ) {
            if (sessionStorage.getItem(currentScreen + "-selectedProducts") != null && JSON.parse(sessionStorage.getItem(currentScreen + "-selectedProducts")).includes(data[4]))
            {
                $(row).addClass("selected");
            }

            $('td', row).each(function(){
              $(this).html('<div>' + $(this).html() + '</div>');
            });
            $('td', row).eq(0).addClass('width-20');
            $('td', row).eq(0).find('div').addClass('texte');
            $('td', row).eq(1).addClass('width-30');
            $('td', row).eq(1).find('div').addClass('texte');
            $('td', row).eq(2).addClass('width-30');
            $('td', row).eq(2).find('div').addClass('texte');
            $('td', row).eq(3).addClass('width-10');
            $('td', row).eq(3).find('div').addClass('texte');
          },
          initComplete: function () {
            var api = this.api();
            $('#tab-products thead tr#forFiltersProductsModal th').each(function(i) {
              var column = api.column(i);
              if ($(this).hasClass('select-filter')) 
              {
                // Création des select pour les colonnes de classe "select-filter"
                var select = $('<select id="filter-column-'+ i + '" style="width:100%;"><option value=""></option></select>').on('change', function() {
                  var val = $.fn.dataTable.util.escapeRegex($(this).val());
                  column.search(val ? '^' + val + '$' : '', true, false).draw();
                });
                $('#forFiltersProductsModal th').eq(i).html(select);
                column.data().unique().sort().each(function(d, j) {
                  if (d != null)
                    select.append('<option value="' + d + '">' + d + '</option>')
                });
              } else if ($(this).hasClass('text-filter'))
              {
                // Création des input pour les colonnes de classe "text-filter"
                var input = $('<input type="text" id="filter-column-'+ i + '" placeholder="" style="width:100%;" />').on('keyup', function() {
                  var val = $.fn.dataTable.util.escapeRegex($(this).val());
                  column.search(val ? val : '', true, false).draw();
                });
                $('#forFiltersProductsModal th').eq(i).html(input);
              }
            });
            document.getElementById('tableProductsModal').style.display = "block";
            document.getElementById('loadProductsModal').style.display = "none";
            // Suppression de la recherche globale du tableau
            document.getElementById('tab-products_filter').style.display = "none";

            tabProducts.columns.adjust();

            // Select rows on click
            $('#tab-products').off( 'click.rowClick' ).on('click.rowClick', 'td', function () {
              if ($(this).parent().hasClass('selected'))
              /* Line is unselected */
              {
                $(this).parent().removeClass('selected');
                // Update count of selected products
                nbSelectedProductsTmp--;
              } else
              /* Line is selected */
              {
                $(this).parent().addClass('selected');
                // Update count of selected products
                nbSelectedProductsTmp++;
              } 
              $('#nbFilteredProductsStep3Modal').html(nbSelectedProductsTmp);
            });

            // Select and unselect links
            $('<div class="dataTables_buttons"><div><a id="selectAllFilteredProducts">@lang("amadeo.select-all-filtered")</a><a id="selectAllProducts">@lang("amadeo.select-all")</a></div><div><a id="unselectAllFilteredProducts">@lang("amadeo.unselect-all-filtered")</a><a id="unselectAllProducts">@lang("amadeo.unselect-all")</a></div></div>').insertBefore('#tab-products_paginate');
            $('#selectAllProducts').on('click', function() 
            {
              nbSelectedProductsTmp = 0;
              tabProducts.rows().every(function() {
                this.nodes().to$().addClass('selected');
                // Update count of selected products
                nbSelectedProductsTmp++;
              })
              $('#nbFilteredProductsStep3Modal').html(nbSelectedProductsTmp);
            });

            $('#unselectAllProducts').on('click', function() 
            {
              nbSelectedProductsTmp = 0;
              tabProducts.rows().every(function() {
                this.nodes().to$().removeClass('selected')
              })
              $('#nbFilteredProductsStep3Modal').html(nbSelectedProductsTmp);
            });

            $('#selectAllFilteredProducts').on('click', function() 
            {
              tabProducts.rows( { search: 'applied' } ).every(function() {
                if (!this.nodes().to$().hasClass('selected'))
                {
                  this.nodes().to$().addClass('selected')
                  // Update count of selected products
                  nbSelectedProductsTmp++;
                }
              })
              $('#nbFilteredProductsStep3Modal').html(nbSelectedProductsTmp);
            });

            $('#unselectAllFilteredProducts').on('click', function() 
            {
              tabProducts.rows( { search: 'applied' } ).every(function() {
                if (this.nodes().to$().hasClass('selected'))
                {
                  this.nodes().to$().removeClass('selected')
                  // Update count of selected products
                  nbSelectedProductsTmp--;
                }
              })
              $('#nbFilteredProductsStep3Modal').html(nbSelectedProductsTmp);
            });
          }
        });
      }
    });
  }

  function loadClinicScope() {
    $('#selectButtonClinicsPurchases').click( function () {
      // Loading parameters
      loadListOfClinicYears();
      
      // Show/hide criteria
      $('#divClinicGeneralCriteria').removeClass('hide');
      $('#divClinicTable').addClass('hide');
      
      // Show/hide buttons
      $('#previousButtonClinicsPurchases').addClass('hide');
      $('#nextButtonClinicsPurchases').removeClass('hide');
      $('#saveButtonClinicsPurchases').addClass('hide');
        
      $( '#selectClinicModal' ).modal('show');
      $( '#selectClinicModal' ).draggable({
        handle: ".modal-header"
      });
    });

    /* Reset button */
    $('#resetButtonClinicsPurchases').click( function () {
      // Clear session
      sessionStorage.removeItem(currentScreen + "-clinicYears");
      sessionStorage.removeItem(currentScreen + "-selectedClinics");

      // Update count of selected clinics
      nbSelectedClinics = 0;
      $('#nbFilteredClinics').html($('.nbTotalClinicsModal').html());
    });

    /* Previous button */
    $('#previousButtonClinicsPurchases').click( function () {
      // Show/hide criteria
      $('#divClinicGeneralCriteria').removeClass('hide');
      $('#divClinicTable').addClass('hide');
      
      // Show/hide buttons
      $('#previousButtonClinicsPurchases').addClass('hide');
      $('#nextButtonClinicsPurchases').removeClass('hide');
      $('#saveButtonClinicsPurchases').addClass('hide');
    });

    /* Next button */ 
    $('#nextButtonClinicsPurchases').click( function () {
      var clinicYears = getSelectValueById("clinic-years[]");

      // Setting criteria in session
      sessionStorage.setItem(currentScreen + "-clinicYears", JSON.stringify(clinicYears));

      // Show/hide criteria
      $('#divClinicGeneralCriteria').addClass('hide');
      $('#divClinicTable').removeClass('hide');
      
      // Show/hide buttons
      $('#previousButtonClinicsPurchases').removeClass('hide');
      $('#nextButtonClinicsPurchases').addClass('hide');
      $('#saveButtonClinicsPurchases').removeClass('hide');

      // Save list of filtered products
      saveCountOfClinicsStep1(clinicYears);

      // Loading list of clinics
      loadListOfClinics(clinicYears, JSON.parse(sessionStorage.getItem(currentScreen + "-selectedClinics")))
    });

    /* Save button */
    $('#saveButtonClinicsPurchases').click( function () {
      // Setting ID of selected clinics in session
      var arrClinicsSelectedId = [];
      var selectedData = tabClinics != null ? tabClinics.rows( '.selected' ).data() : [];
      for (var i = 0; i < selectedData.length; i++) {
        arrClinicsSelectedId.push(selectedData[i][4]);
      }
      
      sessionStorage.setItem(currentScreen + "-selectedClinics", JSON.stringify(arrClinicsSelectedId));
      nbSelectedClinics = nbSelectedClinicsTmp;
      $('#nbFilteredClinics').html(arrClinicsSelectedId.length > 0 ? arrClinicsSelectedId.length : $('.nbTotalClinicsModal').html());

      $( '#selectClinicModal' ).modal('hide');
    });
  }

  function loadProductScope() {
    $('#selectButtonProductsPurchases').click( function () {
      // Loading parameters
      loadListOfLaboratories();
      loadListOfProductTypes();
      loadListOfProductSpecies();

      // Show/hide criteria
      $('#divProductGeneralCriteria').removeClass('hide');
      $('#divProductTherapeuticClassesCriteria').addClass('hide');
      $('#divProductTable').addClass('hide');
      
      // Show/hide buttons
      $('#previousButtonProductsPurchases').addClass('hide');
      $('#nextButtonProductsPurchases').removeClass('hide');
      $('#saveButtonProductsPurchases').addClass('hide');
        
      $( '#selectProductModal' ).modal('show');
      $( '#selectProductModal' ).draggable({
        handle: ".modal-header"
      });
    });

    /* Reset button */
    $('#resetButtonProductsPurchases').click( function () {
      // Clear session
      sessionStorage.removeItem(currentScreen + "-laboratories");
      sessionStorage.removeItem(currentScreen + "-productTypes");
      sessionStorage.removeItem(currentScreen + "-productSpecies");
      sessionStorage.removeItem(currentScreen + "-selectedProductTherapeuticClasses");
      sessionStorage.removeItem(currentScreen + "-selectedProducts");

      // Update count of selected products
      nbSelectedProducts = 0;
      $('#nbFilteredProducts').html($('.nbTotalProductsModal').html());
    });

    /* Previous button */
    $('#previousButtonProductsPurchases').click( function () {
      if (!$('#divProductTherapeuticClassesCriteria').hasClass('hide'))
      /* Tab of therapeutic classes criteria => Tab of general criteria */
      {
        // Show/hide criteria
        $('#divProductGeneralCriteria').removeClass('hide');
        $('#divProductTherapeuticClassesCriteria').addClass('hide');
        $('#divProductTable').addClass('hide');
        
        // Show/hide buttons
        $('#previousButtonProductsPurchases').addClass('hide');
        $('#nextButtonProductsPurchases').removeClass('hide');
        $('#saveButtonProductsPurchases').addClass('hide');
      } else if (!$('#divProductTable').hasClass('hide'))
      /* Tab of list of products => Tab of therapeutic classes criteria */
      {
        // Show/hide criteria
        $('#divProductGeneralCriteria').addClass('hide');
        $('#divProductTherapeuticClassesCriteria').removeClass('hide');
        $('#divProductTable').addClass('hide');
        
        // Show/hide buttons
        $('#previousButtonProductsPurchases').removeClass('hide');
        $('#nextButtonProductsPurchases').removeClass('hide');
        $('#saveButtonProductsPurchases').addClass('hide');
      }
    });

    /* Next button */ 
    $('#nextButtonProductsPurchases').click( function () {
      var productTypes = getCheckboxRadioValueByName("product-types[]");
      var productSpecies = getCheckboxRadioValueByName("product-species[]");
      var laboratories = getSelectValueById("laboratories[]");
      
      if (!$('#divProductGeneralCriteria').hasClass('hide'))
      /* Tab of general criteria => Tab of therapeutic classes criteria */
      {
        // Setting criteria in session
        sessionStorage.setItem(currentScreen + "-laboratories", JSON.stringify(laboratories));
        sessionStorage.setItem(currentScreen + "-productTypes", JSON.stringify(productTypes));
        sessionStorage.setItem(currentScreen + "-productSpecies", JSON.stringify(productSpecies));

        // Show/hide criteria
        $('#divProductGeneralCriteria').addClass('hide');
        $('#divProductTherapeuticClassesCriteria').removeClass('hide');
        $('#divProductTable').addClass('hide');
        
        // Show/hide buttons
        $('#previousButtonProductsPurchases').removeClass('hide');
        $('#nextButtonProductsPurchases').removeClass('hide');
        $('#saveButtonProductsPurchases').addClass('hide');

        // Save list of filtered products
        saveCountOfProductsStep1(laboratories, productTypes, productSpecies);

        // Loading list of therapeutic classes
        loadListOfProductTherapeuticClasses(laboratories, productTypes, productSpecies);
      } else if (!$('#divProductTherapeuticClassesCriteria').hasClass('hide'))
      /* Tab of therapeutic classes criteria => Tab of list of products */
      {
        // Setting ID of selected products in session
        var arrSelectedId = [];
        var selectedData = tabProductTherapeuticClasses != null ? tabProductTherapeuticClasses.rows( '.selected' ).data() : [];
        for (var i = 0; i < selectedData.length; i++) {
          arrSelectedId.push(selectedData[i][4]);
        }
      
        sessionStorage.setItem(currentScreen + "-selectedProductTherapeuticClasses", JSON.stringify(arrSelectedId));

        // Show/hide criteria
        $('#divProductGeneralCriteria').addClass('hide');
        $('#divProductTherapeuticClassesCriteria').addClass('hide');
        $('#divProductTable').removeClass('hide');
        
        // Show/hide buttons
        $('#previousButtonProductsPurchases').removeClass('hide');
        $('#nextButtonProductsPurchases').addClass('hide');
        $('#saveButtonProductsPurchases').removeClass('hide');

        // Save list of filtered products
        saveCountOfProductsStep2(laboratories, productTypes, productSpecies, arrSelectedId);

        // Loading list of products
        loadListOfProducts(laboratories, productTypes, productSpecies, arrSelectedId, JSON.parse(sessionStorage.getItem(currentScreen + "-selectedProducts")))
      }
    });

    /* Save button */
    $('#saveButtonProductsPurchases').click( function () {
      // Setting ID of selected products in session
      var arrProductsSelectedId = [];
      var selectedData = tabProducts != null ? tabProducts.rows( '.selected' ).data() : [];
      for (var i = 0; i < selectedData.length; i++) {
        arrProductsSelectedId.push(selectedData[i][4]);
      }
      
      sessionStorage.setItem(currentScreen + "-selectedProducts", JSON.stringify(arrProductsSelectedId));
      nbSelectedProducts = nbSelectedProductsTmp;
      $('#nbFilteredProducts').html(arrProductsSelectedId.length > 0 ? arrProductsSelectedId.length : $('.nbTotalProductsModal').html());

      $( '#selectProductModal' ).modal('hide');
    });
  }

</script>
