<script type="text/javascript">

  var tabPurchases = null;

  /**
  * Récupération des paramètres du formulaire du "Détail des chiffres"
  */ 
  function getDetailChiffresParameters() 
  {
    var token = document.getElementsByName("_token")[0].value,
          startMonth = document.getElementById("period-startMonth").value,
          endMonth = document.getElementById("period-endMonth").value,
          startYear = document.getElementById("period-startYear").value,
          endYear = document.getElementById("period-endYear").value,
          byMonth = getCheckboxRadioValueByName("byMonth"),
          displayType = getCheckboxRadioValueByName("display-type[]"),
          centralPurchasing = getCheckboxRadioValueByName("central-purchasing[]"),
          valorization = 1; //getCheckboxRadioValueByName("valorizations[]");
    var data = null;
      
    var erreur = false;
    // Check if end date is before start date
    if (new Date(startYear,startMonth,1) > new Date(endYear,endMonth,30))
    {
      bootoast.toast({
        message: 'La date de début ne doit pas être postérieure à la date de fin.',
        type: 'warning'
      });
      erreur = true;
    }

    // Check if the period is over 12 months (only if the detail by month is selected)
    var nbMonthDiff = (endYear - startYear) * 12 + (endMonth - startMonth) + 1;
    if (byMonth.length > 0 && nbMonthDiff > 12)
    {
      bootoast.toast({
        message: 'Détail par mois : la période ne peut excéder 12 mois.',
        type: 'warning'
      });
      erreur = true;
    }

    if (erreur)
    {
      $(".ajax-list-lab-stats").html("<div class='no-objectif'>Veuillez renseigner des paramètres pour voir les résultats</div>");
      return null;
    } else
    {
      $(".div-periode").removeClass("has-error");
        
      // Affichage de l'image de chargement en cours
      document.getElementById('load').style.display = "block";

      // Setting in session
      sessionStorage.setItem("purchases-displayType", JSON.stringify(displayType));
      sessionStorage.setItem("purchases-centralPurchasing", JSON.stringify(centralPurchasing));
      sessionStorage.setItem("purchases-valorization", JSON.stringify(valorization));

      var clinics = JSON.parse(sessionStorage.getItem("purchases-selectedClinics"));
      var products = JSON.parse(sessionStorage.getItem("purchases-selectedProducts"));

      data = {
        "_token": token,
        "startMonth" : startMonth,
        "endMonth" : endMonth,
        "startYear" : startYear,
        "endYear" : endYear,
        "byYear" : (byMonth.length == 0 ? 1 : 0), // 1:true ; 0:false
        "clinics" : clinics,
        "products" : products,
        "valorization" : (valorization != null && valorization.length > 0 ? valorization[0] : "1"),
        "centralPurchasing" : centralPurchasing,
        "displayType" : displayType,
        "nbMonthDiff" : nbMonthDiff
      };
    }

    return data;
  }

  /**
  * Success AJAX request : display "By product" table
  */
  function getListOfPurchasesByProducts(params)
  {
    $.ajax({
      type: "POST",
      dataType: "json",
      url: "getListOfPurchasesByParams", 
      data: $.param(params),
      success: function(json) {
        var startMonthName = new Date(params["startYear"], params["startMonth"]-1).toLocaleString("fr", { month: "short" });
        var endMonthName = new Date(params["endYear"], params["endMonth"]-1).toLocaleString("fr", { month: "short" });
        var nbYearDiff = (params["startYear"] != params["endYear"]) ? (params["endYear"] - params["startYear"]) : 1;

        if (params["byYear"])
        {
          // Complete year
          var data = jQuery.map(json, function(el, i) {
            var evol = (el.ca_periode_prec != 0 ? ((el.ca_periode*100/el.ca_periode_prec)-100).toFixed(2) : "-");

            return [[ el.laboratoire, [el.denomination, el.conditionnement], el.ca_periode, el.qte_periode, el.ca_periode_prec, el.qte_periode_prec, evol, el.prod_id, el.obsolete, el.manque_periode, el.manque_periode_prec ]];
          });

          if (tabPurchases != null)
          {
            tabPurchases.destroy();
          }

          // Construct tab by product
          var periode = startMonthName + params["startYear"].substring(2,4) + " à " + endMonthName + params["endYear"].substring(2,4);
          var periodePrec = startMonthName + String(params["startYear"]-nbYearDiff).substring(2,4) + " à " + endMonthName + String(params["endYear"]-nbYearDiff).substring(2,4);
          $('#tab-purchases').html("<thead><tr><th class='texte width-10'>@lang('amadeo.products.seller')</th><th class='texte width-30'>@lang('amadeo.purchases.product')</th><th class='width-15 text-center'>@lang('amadeo.purchases.amount')<br><span>" + periode + "</span></th><th class='width-15 text-center'>@lang('amadeo.purchases.quantity')<br><span>" + periode + "</span></th><th class='width-15 text-center'>@lang('amadeo.purchases.amount')<br><span>" + periodePrec + "</span></th><th class='width-15 text-center'>@lang('amadeo.purchases.quantity')<br><span>" + periodePrec + "</span></th><th class='width-9 text-center'>@lang('amadeo.purchases.evolution-short')</th></tr><tr id='forFilters'><th class='select-filter'></th><th class='text-filter'></th><th colspan=5><div id='div-mask-null-quantities' class='checkbox-item-horizontal hide'><div class='checkboxContainer'><input id='mask-null-quantities' name='mask-null-quantities' type='checkbox' value='1'><label for='mask-null-quantities'></label></div><div class='checkboxLabel'><label for='mask-null-quantities' style='font-size: 12px;'>@lang('amadeo.purchases.mask-empty')</label></div></div></th></tr></thead><tbody></tbody><tfoot><tr><td class='texte width-10'>@lang('amadeo.purchases.total')</td><td class='texte width-30'></td><td class='nombre width-15'></td><td class='nombre width-15'></td><td class='nombre width-15'></td><td class='nombre width-15'></td><td class='nombre width-9'></td></tr></tfoot>");

          // Complete period
          tabPurchases = $('#tab-purchases').DataTable( {
            "paging": true,
            "pageLength": 100,
            "bLengthChange": false,
            "deferRender": true,
            "ordering": true,
            "info": false,
            "scrollY": "37em",
            "scrollX": true,
            "scrollCollapse": true,
            "bSortCellsTop": true,
            "destroy": true,
            "order": [[ 0, "asc" ], [ 1, "asc" ]],
            "language": {
              "url": "//cdn.datatables.net/plug-ins/1.10.16/i18n/French.json",
              "emptyTable": '@lang("amadeo.products.search-empty")'
            },
            "aoColumns": [
              null,
              { "render": function ( data, type, row ) {
                  return data[0] + "<br><span>" + data[1];
                } 
              },
              { "render": function ( data, type, row ) {
                  return numberWithSpaces(data)+' €';
              }, "sType": "numeric-comma" },
              { "render": function ( data, type, row ) {
                  return (numberWithSpaces(data) == "0,00" ? "0" : numberWithSpaces(data));
              }, "sType": "numeric-comma" },
              { "render": function ( data, type, row ) {
                  return numberWithSpaces(data)+' €';
              }, "sType": "numeric-comma" },
              { "render": function ( data, type, row ) {
                  return (numberWithSpaces(data) == "0,00" ? "0" : numberWithSpaces(data));
              }, "sType": "numeric-comma" },
              { "render": function ( data, type, row ) {
                  if (data != '-')
                    return data.toString().replace(".", ",")+' %';
                  else
                    return data;
              }, "sType": "numeric-comma" }
            ],
            "aaData": data,
            "createdRow": function ( row, data, index ) {
              $('td', row).each(function(){
                $(this).html('<div>' + $(this).html() + '</div>');
              });

              $('td', row).eq(0).addClass('width-10');
              $('td', row).eq(0).find('div').addClass('texte');
              $('td', row).eq(0).find('div').attr('title', $('td', row).eq(0).find('div').html());
              $('td', row).eq(1).addClass('width-30');
              $('td', row).eq(1).find('div').addClass('texte');
              $('td', row).eq(1).find('div').attr('title', (data[1][0] + " : " + data[1][1]));
              $('td', row).eq(1).find('div').css('line-height', '20px');
              $('td', row).eq(2).addClass('width-15');
              $('td', row).eq(2).find('div').addClass('nombre');
              if (data[9])
              {
                $('td', row).eq(2).find('div').addClass('orange');
              }
              $('td', row).eq(3).addClass('width-15');
              $('td', row).eq(3).find('div').addClass('nombre');
              $('td', row).eq(4).addClass('width-15');
              $('td', row).eq(4).find('div').addClass('nombre');
              if (data[10])
              {
                $('td', row).eq(4).find('div').addClass('orange');
              }
              $('td', row).eq(5).addClass('width-15');
              $('td', row).eq(5).find('div').addClass('nombre');
              $('td', row).eq(6).addClass('width-9');
              $('td', row).eq(6).find('div').addClass('nombre' + (data[6] > 0 ? ' positif' : (data[6] < 0 ? ' negatif' : '')));

              if (data[8])
              {
                $('td', row).eq(0).find('div').addClass('obsolete');
                $('td', row).eq(1).find('div').addClass('obsolete');
                $('td', row).eq(2).find('div').addClass('obsolete');
                $('td', row).eq(3).find('div').addClass('obsolete');
                $('td', row).eq(4).find('div').addClass('obsolete');
                $('td', row).eq(5).find('div').addClass('obsolete');
                $('td', row).eq(6).find('div').addClass('obsolete');
              }
            },
            "footerCallback": function ( row, data, start, end, display ) {
                var api = this.api(), data;

                // Remove the formatting to get integer data for summation
                var intVal = function ( i ) {
                    return typeof i === 'string' ?
                        i.replace(/[\$,]/g, '')*1 :
                        typeof i === 'number' ?
                            i : 0;
                };

                var totalCA = 0;
                var totalCAPrec = 0;
     
                for (var i = 2; i < 6; i++) {
                  // Total over all pages
                  total = api.column( i ).data().reduce( function (a, b) {
                    if (i == 2 || i == 4)
                    {
                      return parseFloat(a) + parseFloat(b);
                    } else
                    {
                      return parseInt(a) + parseInt(b);
                    }
                  }, 0 );

                  if (i == 2)
                  {
                    totalCA = total;
                  } else if (i == 4)
                  {
                    totalCAPrec = total;
                  }
         
                  // Update footer
                  if (i == 2 || i == 4)
                  {
                    $( api.column( i ).footer() ).html(numberWithSpaces(total.toFixed(2)) + ' €');
                  } else 
                  {
                    $( api.column( i ).footer() ).html(numberWithSpaces(total) == "0,00" ? "0" : numberWithSpaces(total));
                  }
                }

                var totalEvol = (totalCAPrec != 0 ? ((totalCA * 100 / totalCAPrec) - 100).toFixed(2) : "-");
                $( api.column( 6 ).footer() ).html(totalEvol.toString().replace(".", ",") + ' %'); 
                if (totalEvol > 0)
                {
                  $( api.column( 6 ).footer() ).addClass("positif");
                } else if (totalEvol < 0)
                {
                  $( api.column( 6 ).footer() ).addClass("negatif");
                }
            },
            initComplete: function () {
              var api = this.api();
              // Header
              $('#tab-purchases thead tr#forFilters th').each(function(i) {
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

              // Delete global search tab
              $('#tab-purchases_filter').css('display', 'none');
              // Display tab
              $('.load').css('display', 'none');
              $('.layout_withParams_right').css('background', '#E8E8E8');
              $('.tableau').css('display', 'block');

              tabPurchases.columns.adjust();

              $('#mask-null-quantities').on('change', function() {
                $.fn.dataTable.ext.search.push(
                  function( settings, searchData, index, rowData, counter ) {
                    if ( $('#mask-null-quantities').is(':checked') )
                      return (rowData[2] != 0) || (rowData[3] != 0) || (rowData[4] != 0) || (rowData[5] != 0);
                    else 
                      return true;
                  }     
                );
                tabPurchases.draw();
                $.fn.dataTable.ext.search.pop();
              });
              //$('#mask-null-quantities').prop( "checked", true ).trigger("change");
              $('#div-mask-null-quantities').removeClass('hide');
            }
          } );
        } else
        {
          // Detail by month
          var data = jQuery.map(json, function(el, j) {
            var array = [ el.laboratoire, [el.denomination, el.conditionnement] ];
            var arrayMissing = [];

            for (var i = 0; i < params["nbMonthDiff"]; i++) {
              var evol = (el["ca_periode_prec_m"+i] != 0 ? ((el["ca_periode_m"+i] * 100 / el["ca_periode_prec_m"+i]) - 100).toFixed(2) : "-");
              array.push(el["ca_periode_m"+i]);
              array.push(el["qte_periode_m"+i]);
              array.push(el["ca_periode_prec_m"+i]);
              array.push(el["qte_periode_prec_m"+i]);
              array.push(evol);

              arrayMissing.push(el["manque_periode_m"+i]);
              arrayMissing.push(el["manque_periode_prec_m"+i]);
            }

            array.push(el.prod_id);
            array.push(el.obsolete);
            array = array.concat(arrayMissing);

            return [ array ];
          });

          var headerLine1 = "<tr><th class='texte width-10' rowspan='2'>@lang('amadeo.products.seller')</th><th class='texte width-30' rowspan='2'>@lang('amadeo.purchases.product')</th>";
          var headerLine2 = "<tr>";
          var headerLine3 = "<tr id='forFilters'><th class='select-filter'></th><th class='text-filter'></th>"
          var footer = "<tr><td class='texte width-10'>@lang('amadeo.purchases.total')</td><td class='texte width-30'></td>";
          var aoColumns = [
                            null,
                            { "render": function ( data, type, row ) {
                                return data[0] + "<br><span>" + data[1];
                              }
                            }
                          ];
          var incAnnee = false;

          for (var i = 0; i < params["nbMonthDiff"]; i++) {
            // Header
            headerLine1 += "<th colspan='5' class='text-center'";
            var impair = false;
            if (!(i%2))
            {
              headerLine1 += " style='background: #F4F3F3;'";
              impair = true;
            }
            headerLine1 += ">" + new Date(params["startYear"], (params["startMonth"]-1+i)%12).toLocaleString("fr", { month: "long" }) + "</th>";

            if (i>0 && (params["startMonth"]-1+i)%12 == 0)
            {
              incAnnee = true;
            }
            headerLine2 += "<th class='width-15 text-center'";
            if (impair)
            {
              headerLine2 += " style='background: #F4F3F3;'";
            }
            headerLine2 += ">@lang('amadeo.purchases.amount')<br><span>" + (incAnnee ? params["endYear"] : params["startYear"]) + "</span></th>"
            + "<th class='width-9 text-center text-center'";
            if (impair)
            {
              headerLine2 += " style='background: #F4F3F3;'";
            }
            headerLine2 += ">@lang('amadeo.purchases.quantity-short')<br><span>" + (incAnnee ? params["endYear"] : params["startYear"]) + "</span></th>"
            + "<th class='width-15 text-center text-center'";
            if (impair)
            {
              headerLine2 += " style='background: #F4F3F3;'";
            }
            headerLine2 += ">@lang('amadeo.purchases.amount')<br><span>" + (incAnnee ? params["endYear"]-1 : params["startYear"]-1) + "</span></th>"
            + "<th class='width-9 text-center text-center'";
            if (impair)
            {
              headerLine2 += " style='background: #F4F3F3;'";
            }
            headerLine2 += ">@lang('amadeo.purchases.quantity-short')<br><span>" + (incAnnee ? params["endYear"]-1 : params["startYear"]-1) + "</span></th>"
            + "<th class='width-9 text-center text-center'";
            if (impair)
            {
              headerLine2 += " style='background: #F4F3F3;'";
            }
            headerLine2 += ">@lang('amadeo.purchases.evolution-short')</th>";
            if (i == 0)
            {
              headerLine3 += "<th colspan=5><div id='div-mask-null-quantities' class='checkbox-item-horizontal hide'><div class='checkboxContainer'><input id='mask-null-quantities' name='mask-null-quantities' type='checkbox' value='1'><label for='mask-null-quantities'></label></div><div class='checkboxLabel'><label for='mask-null-quantities' style='font-size: 12px;'>@lang('amadeo.purchases.mask-empty')</label></div></div></th>"
            } else 
            {
              headerLine3 += "<th></th><th></th><th></th><th></th><th></th>";
            }

            // Footer
            footer += "<td class='nombre width-15'></td><td class='nombre width-9'></td><td class='nombre width-15'></td><td class='nombre width-9'></td><td class='nombre width-9'></td>";

            // Columns
            aoColumns.push({ "render": function ( data, type, row ) {
              return numberWithSpaces(data)+' €';
            }, "sType": "numeric-comma" });
            aoColumns.push({ "render": function ( data, type, row ) {
              return (numberWithSpaces(data) == "0,00" ? "0" : numberWithSpaces(data));
            }, "sType": "numeric-comma" });
            aoColumns.push({ "render": function ( data, type, row ) {
              return numberWithSpaces(data)+' €';
            }, "sType": "numeric-comma" });
            aoColumns.push({ "render": function ( data, type, row ) {
              return (numberWithSpaces(data) == "0,00" ? "0" : numberWithSpaces(data));
            }, "sType": "numeric-comma" });
            aoColumns.push({ "render": function ( data, type, row ) {
              if (data != '-')
                return data.toString().replace(".", ",") + ' %';
              else
                return data;
            }, "sType": "numeric-comma" });
          }

          if (tabPurchases != null)
          {
            tabPurchases.destroy();
          }
          
          $('#tab-purchases').html("<thead>" + headerLine1 + "</tr>" + headerLine2 + "</tr>" + headerLine3 + "</tr></thead><tbody></tbody><tfoot>" + footer + + "</tr></tfoot>");

          // Complete period
          tabPurchases = $('#tab-purchases').DataTable( {
            "paging": true,
            "pageLength": 100,
            "bLengthChange": false,
            "deferRender": true,
            "ordering": true,
            "info": false,
            "scrollY": "37em",
            "scrollX": true,
            "scrollCollapse": true,
            "bSortCellsTop": true,
            "destroy": true,
            "order": [[ 0, "asc" ], [ 1, "asc" ]],
            "language": {
              "url": "//cdn.datatables.net/plug-ins/1.10.16/i18n/French.json",
              "emptyTable": '@lang("amadeo.products.search-empty")'
            },
            "aoColumns": aoColumns,
            "aaData": data,
            "createdRow": function ( row, data, index ) {
              $('td', row).each(function(){
                $(this).html('<div>' + $(this).html() + '</div>');
              });

              $('td', row).eq(0).addClass('width-10');
              $('td', row).eq(0).find('div').addClass('texte');
              $('td', row).eq(0).find('div').attr('title', $('td', row).eq(0).find('div').html());
              $('td', row).eq(1).addClass('width-30');
              $('td', row).eq(1).find('div').addClass('texte');
              $('td', row).eq(1).find('div').attr('title', (data[1][0] + " : " + data[1][1]));
              $('td', row).eq(1).find('div').css('line-height', '20px');

              var obsolete = data[5*params["nbMonthDiff"]+3];

              for (var i = 0; i < params["nbMonthDiff"]; i++) {
                var j=i*5;
                var manque = data[5*params["nbMonthDiff"]+(2*i)+4];
                var manquePrec = data[5*params["nbMonthDiff"]+(2*i)+5];
                $('td', row).eq(j+2).addClass('width-15');
                $('td', row).eq(j+2).find('div').addClass('nombre');
                if (manque)
                {
                  $('td', row).eq(j+2).find('div').addClass('orange');
                }
                $('td', row).eq(j+3).addClass('width-9');
                $('td', row).eq(j+3).find('div').addClass('nombre');
                $('td', row).eq(j+4).addClass('width-15');
                $('td', row).eq(j+4).find('div').addClass('nombre');
                if (manquePrec)
                {
                  $('td', row).eq(j+4).find('div').addClass('orange');
                }
                $('td', row).eq(j+5).addClass('width-9');
                $('td', row).eq(j+5).find('div').addClass('nombre');
                $('td', row).eq(j+6).addClass('width-9');
                $('td', row).eq(j+6).find('div').addClass('nombre' + (data[j+6] > 0 ? ' positif' : (data[j+6] < 0 ? ' negatif' : '')));

                if (obsolete)
                {
                  $('td', row).eq(j+1).find('div').addClass('obsolete');
                  $('td', row).eq(j+2).find('div').addClass('obsolete');
                  $('td', row).eq(j+3).find('div').addClass('obsolete');
                  $('td', row).eq(j+4).find('div').addClass('obsolete');
                  $('td', row).eq(j+5).find('div').addClass('obsolete');
                  $('td', row).eq(j+6).find('div').addClass('obsolete');
                }
              }

              if (obsolete)
              {
                $('td', row).eq(0).find('div').addClass('obsolete');
              }
            },
            "footerCallback": function ( row, data, start, end, display ) {
              var api = this.api(), data;

              // Remove the formatting to get integer data for summation
              var intVal = function ( i ) {
                  return typeof i === 'string' ?
                      i.replace(/[\$,]/g, '')*1 :
                      typeof i === 'number' ?
                          i : 0;
              };

              for (var i = 0; i < params["nbMonthDiff"]; i++) {
                var totalCA = 0;
                var totalCAPrec = 0;

                for (var j = (i*5)+2; j < (i*5)+6; j++) {
                  // Total over all pages
                  total = api.column( j ).data().reduce( function (a, b) {
                    if (j == (i*5)+2 || j == (i*5)+4)
                    {
                      return parseFloat(a) + parseFloat(b);
                    } else
                    {
                      return parseInt(a) + parseInt(b);
                    }
                  }, 0 );

                  if (j == (i*5)+2)
                  {
                    totalCA = total;
                  } else if (j == (i*5)+4)
                  {
                    totalCAPrec = total;
                  }
          
                  // Update footer
                  if (j == (i*5)+2 || j == (i*5)+4)
                  {
                    $( api.column( j ).footer() ).html(numberWithSpaces(total.toFixed(2)) + ' €');
                  } else 
                  {
                    $( api.column( j ).footer() ).html(numberWithSpaces(total) == "0,00" ? "0" : numberWithSpaces(total));
                  }
                }
                
                var totalEvol = (totalCAPrec != 0 ? ((totalCA * 100 / totalCAPrec) - 100).toFixed(2) : "-");
                $( api.column( (i*5)+6 ).footer() ).html(totalEvol.toString().replace(".", ",") + ' %'); 
                if (totalEvol > 0)
                {
                  $( api.column( (i*5)+6 ).footer() ).addClass("positif");
                } else if (totalEvol < 0)
                {
                  $( api.column( (i*5)+6 ).footer() ).addClass("negatif");
                }
              }
            },
            initComplete: function () {
              var api = this.api();
              // Header
              $('#tab-purchases thead tr#forFilters th').each(function(i) {
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

              // Delete global search tab
              $('#tab-purchases_filter').css('display', 'none');
              // Display tab
              $('.load').css('display', 'none');
              $('.layout_withParams_right').css('background', '#E8E8E8');
              $('.tableau').css('display', 'block');

              tabPurchases.columns.adjust();

              $('#mask-null-quantities').on('change', function() {
                $.fn.dataTable.ext.search.push(
                  function( settings, searchData, index, rowData, counter ) {
                    if ( $('#mask-null-quantities').is(':checked') )
                    {
                      for (var i = 0; i < params["nbMonthDiff"]; i++) {
                        if ((rowData[(i*5)+2] != 0) || (rowData[(i*5)+3] != 0) || (rowData[(i*5)+4] != 0) || (rowData[(i*5)+5] != 0))
                          return true;
                      }
                      return false;
                    }
                    else 
                      return true;
                  }     
                );
                tabPurchases.draw();
                $.fn.dataTable.ext.search.pop();
              });
              //$('#mask-null-quantities').prop( "checked", true ).trigger("change");
              $('#div-mask-null-quantities').removeClass('hide');
            }
          });
        }
      }
    });
  }

  /**
  * Success AJAX request : display "By laboratory" table
  */
  function getListOfPurchasesByLaboratories(params)
  {
    $.ajax({
      type: "POST",
      dataType: "json",
      url: "getListOfPurchasesByParams", 
      data: $.param(params),
      success: function(json) {
        var startMonthName = new Date(params["startYear"], params["startMonth"]-1).toLocaleString("fr", { month: "short" });
        var endMonthName = new Date(params["endYear"], params["endMonth"]-1).toLocaleString("fr", { month: "short" });
        var nbYearDiff = (params["startYear"] != params["endYear"]) ? (params["endYear"] - params["startYear"]) : 1;

        if (params["byYear"])
        {
          // Complete year
          var data = jQuery.map(json, function(el, i) {
            var evol = (el.ca_periode_prec != 0 ? ((el.ca_periode*100/el.ca_periode_prec)-100).toFixed(2) : "-");

            return [[ el.laboratoire, el.ca_periode, el.ca_periode_prec, evol, el.lab_id, el.manque_periode, el.manque_periode_prec ]];
          });

          if (tabPurchases != null)
          {
            tabPurchases.destroy();
          }

          // Construct tab by laboratory
          var periode = startMonthName + params["startYear"].substring(2,4) + " à " + endMonthName + params["endYear"].substring(2,4);
          var periodePrec = startMonthName + String(params["startYear"]-nbYearDiff).substring(2,4) + " à " + endMonthName + String(params["endYear"]-nbYearDiff).substring(2,4);
          $('#tab-purchases').html("<thead><tr><th class='texte width-50'>@lang('amadeo.products.seller')</th><th class='text-center'>@lang('amadeo.purchases.amount')<br><span>" + periode + "</span></th><th class='text-center'>@lang('amadeo.purchases.amount')<br><span>" + periodePrec + "</span></th><th class='text-center'>@lang('amadeo.purchases.evolution')</th></tr><tr id='forFilters'><th class='text-filter'></th><th colspan=3><div id='div-mask-null-quantities' class='checkbox-item-horizontal hide'><div class='checkboxContainer'><input id='mask-null-quantities' name='mask-null-quantities' type='checkbox' value='1'><label for='mask-null-quantities'></label></div><div class='checkboxLabel'><label for='mask-null-quantities' style='font-size: 12px;'>@lang('amadeo.purchases.mask-empty')</label></div></div></th></tr></thead><tbody></tbody><tfoot><tr><td class='texte width-50'>@lang('amadeo.purchases.total-noothers')</td><td class='nombre'></td><td class='nombre'></td><td class='nombre'></td></tr></tfoot>");

          // Complete period
          tabPurchases = $('#tab-purchases').DataTable( {
            "paging": false,
            "bLengthChange": false,
            "ordering": true,
            "info": false,
            "scrollY": "37em",
            "scrollX": true,
            "scrollCollapse": true,
            "bSortCellsTop": true,
            "destroy": true,
            "order": [],
            "language": {
              "url": "//cdn.datatables.net/plug-ins/1.10.16/i18n/French.json",
              "emptyTable": '@lang("amadeo.sellers.search-empty")'
            },
              "aoColumns": [
                null,
                { "render": function ( data, type, row ) {
                    return numberWithSpaces(data)+' €';
                }, "sType": "numeric-comma" },
                { "render": function ( data, type, row ) {
                    return numberWithSpaces(data)+' €';
                }, "sType": "numeric-comma" },
                { "render": function ( data, type, row ) {
                    if (data != '-')
                      return data.toString().replace(".", ",") + ' %';
                    else
                      return data;
                }, "sType": "numeric-comma" },
              ],
            "aaData": data,
            "createdRow": function ( row, data, index ) {
              $('td', row).each(function(){
                $(this).html('<div>' + $(this).html() + '</div>');
              });

              if (data[4] == 40)
              {
                $(row).addClass("other");
              }

              $('td', row).eq(0).addClass('width-50');
              $('td', row).eq(0).find('div').addClass('texte');
              $('td', row).eq(0).find('div').attr('title', $('td', row).eq(0).find('div').html());
              $('td', row).eq(1).find('div').addClass('nombre');
              if (data[5])
              {
                $('td', row).eq(1).find('div').addClass('orange');
              }
              $('td', row).eq(2).find('div').addClass('nombre');
              if (data[6])
              {
                $('td', row).eq(2).find('div').addClass('orange');
              }
              $('td', row).eq(3).find('div').addClass('nombre');
              $('td', row).eq(3).find('div').addClass('nombre' + (data[3] > 0 ? ' positif' : (data[3] < 0 ? ' negatif' : '')));
            },
            "footerCallback": function ( row, data, start, end, display ) {
                var api = this.api(), data;

                // Remove the formatting to get integer data for summation
                var intVal = function ( i ) {
                    return typeof i === 'string' ?
                        i.replace(/[\$,]/g, '')*1 :
                        typeof i === 'number' ?
                            i : 0;
                };

                var totalCA = 0;
                var totalCAPrec = 0;
     
                api.rows( ":not('.other')" ).every(function () {
                  totalCA += parseFloat(this.data()[1]);
                  totalCAPrec += parseFloat(this.data()[2]);
                });

                // Update footer
                $( api.column( 1 ).footer() ).html(numberWithSpaces(totalCA.toFixed(2)) + ' €');
                $( api.column( 2 ).footer() ).html(numberWithSpaces(totalCAPrec.toFixed(2)) + ' €');

                var totalEvol = (totalCAPrec != 0 ? ((totalCA * 100 / totalCAPrec) - 100).toFixed(2) : "-");
                $( api.column( 3 ).footer() ).html(totalEvol.toString().replace(".", ",") + ' %'); 
                if (totalEvol > 0)
                {
                  $( api.column( 3 ).footer() ).addClass("positif");
                } else if (totalEvol < 0)
                {
                  $( api.column( 3 ).footer() ).addClass("negatif");
                }
            },
            initComplete: function () {
              var api = this.api();
              // Header
              $('#tab-purchases thead tr#forFilters th').each(function(i) {
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

              // Delete global search tab
              $('#tab-purchases_filter').css('display', 'none');
              // Display tab
              $('.load').css('display', 'none');
              $('.layout_withParams_right').css('background', '#E8E8E8');
              $('.tableau').css('display', 'block');

              tabPurchases.columns.adjust();

              $('#mask-null-quantities').on('change', function() {
                $.fn.dataTable.ext.search.push(
                  function( settings, searchData, index, rowData, counter ) {
                    if ( $('#mask-null-quantities').is(':checked') )
                      return (rowData[1] != 0) || (rowData[2] != 0);
                    else 
                      return true;
                  }     
                );
                tabPurchases.draw();
                $.fn.dataTable.ext.search.pop();
              });
              //$('#mask-null-quantities').prop( "checked", true ).trigger("change");
              $('#div-mask-null-quantities').removeClass('hide');
            }
          } );
        } else
        {
          // Detail by month
          var data = jQuery.map(json, function(el, j) {
            var array = [ el.laboratoire ];
            var arrayMissing = [];

            for (var i = 0; i < params["nbMonthDiff"]; i++) {
              array.push(el["ca_periode_m"+i]);
              array.push(el["ca_periode_prec_m"+i]);
              var evol = (el["ca_periode_prec_m"+i] != 0 ? ((el["ca_periode_m"+i] * 100 / el["ca_periode_prec_m"+i]) - 100).toFixed(2) : "-");
              array.push(evol);

              arrayMissing.push(el["manque_periode_m"+i]);
              arrayMissing.push(el["manque_periode_prec_m"+i]);
            }

            array.push(el.lab_id);
            array = array.concat(arrayMissing);

            return [ array ];
          });

          var headerLine1 = "<tr><th class='texte width-50' rowspan='2'>@lang('amadeo.products.seller')</th>";
          var headerLine2 = "<tr>";
          var headerLine3 = "<tr id='forFilters'><th class='text-filter'></th>"
          var footer = "<tr><td class='texte width-50'>@lang('amadeo.purchases.total-noothers')</td>";
          var aoColumns = [ null ];
          var incAnnee = false;

          for (var i = 0; i < params["nbMonthDiff"]; i++) {
            // Header
            headerLine1 += "<th colspan='3' class='text-center'";
            var impair = false;
            if (!(i%2))
            {
              headerLine1 += " style='background: #F4F3F3;'";
              impair = true;
            }
            headerLine1 += ">" + new Date(params["startYear"], (params["startMonth"]-1+i)%12).toLocaleString("fr", { month: "long" }) + "</th>";

            if (i>0 && (params["startMonth"]-1+i)%12 == 0)
            {
              incAnnee = true;
            }
            headerLine2 += "<th class='width-15 text-center'";
            if (impair)
            {
              headerLine2 += " style='background: #F4F3F3;'";
            }
            headerLine2 += ">@lang('amadeo.purchases.amount')<br><span>" + (incAnnee ? params["endYear"] : params["startYear"]) + "</span></th>"
            + "<th class='width-15 text-center text-center'";
            if (impair)
            {
              headerLine2 += " style='background: #F4F3F3;'";
            }
            headerLine2 += ">@lang('amadeo.purchases.amount')<br><span>" + (incAnnee ? params["endYear"]-1 : params["startYear"]-1) + "</span></th>"
            + "<th class='width-9 text-center text-center'";
            if (impair)
            {
              headerLine2 += " style='background: #F4F3F3;'";
            }
            headerLine2 += ">@lang('amadeo.purchases.evolution-short')</th>";
            if (i == 0)
            {
              headerLine3 += "<th colspan=3><div id='div-mask-null-quantities' class='checkbox-item-horizontal hide'><div class='checkboxContainer'><input id='mask-null-quantities' name='mask-null-quantities' type='checkbox' value='1'><label for='mask-null-quantities'></label></div><div class='checkboxLabel'><label for='mask-null-quantities' style='font-size: 12px;'>@lang('amadeo.purchases.mask-empty')</label></div></div></th>"
            } else 
            {
              headerLine3 += "<th></th><th></th><th></th>";
            }

            // Footer
            footer += "<td class='nombre width-15'></td><td class='nombre width-15'></td><td class='nombre width-9'></td>";

            // Columns
            aoColumns.push({ "render": function ( data, type, row ) {
              return numberWithSpaces(data)+' €';
            }, "sType": "numeric-comma" });
            aoColumns.push({ "render": function ( data, type, row ) {
              return numberWithSpaces(data)+' €';
            }, "sType": "numeric-comma" });
            aoColumns.push({ "render": function ( data, type, row ) {
              if (data != '-')
                return data.toString().replace(".", ",") + ' %';
              else
                return data;
            }, "sType": "numeric-comma" });
          }

          if (tabPurchases != null)
          {
            tabPurchases.destroy();
          }
          
          $('#tab-purchases').html("<thead>" + headerLine1 + "</tr>" + headerLine2 + "</tr>" + headerLine3 + "</tr></thead><tbody></tbody><tfoot>" + footer + + "</tr></tfoot>");

          tabPurchases = $('#tab-purchases').DataTable( {
            "paging": false,
            "bLengthChange": false,
            "ordering": true,
            "info": false,
            "scrollY": "37em",
            "scrollX": true,
            "scrollCollapse": true,
            "bSortCellsTop": true,
            "destroy": true,
            "order": [ ],
            "language": {
              "url": "//cdn.datatables.net/plug-ins/1.10.16/i18n/French.json",
              "emptyTable": '@lang("amadeo.sellers.search-empty")'
            },
            "aoColumns": aoColumns,
            "aaData": data,
            "createdRow": function ( row, data, index ) {
              $('td', row).each(function(){
                $(this).html('<div>' + $(this).html() + '</div>');
              });

              if (data[3*params["nbMonthDiff"]+1] == 40)
              {
                $(row).addClass("other");
              }

              $('td', row).eq(0).addClass('width-50');
              $('td', row).eq(0).find('div').addClass('texte');
              $('td', row).eq(0).find('div').attr('title', $('td', row).eq(0).find('div').html());
              
              for (var i = 0; i < params["nbMonthDiff"]; i++) {
                var j=i*3;
                var manque = data[3*params["nbMonthDiff"]+(2*i)+2];
                var manquePrec = data[3*params["nbMonthDiff"]+(2*i)+3];
                $('td', row).eq(j+1).addClass('width-15');
                $('td', row).eq(j+1).find('div').addClass('nombre');
                if (manque)
                {
                  $('td', row).eq(j+1).find('div').addClass('orange');
                }
                $('td', row).eq(j+2).addClass('width-15');
                $('td', row).eq(j+2).find('div').addClass('nombre');
                if (manquePrec)
                {
                  $('td', row).eq(j+2).find('div').addClass('orange');
                }
                $('td', row).eq(j+3).addClass('width-9');
                $('td', row).eq(j+3).find('div').addClass('nombre' + (data[j+3] > 0 ? ' positif' : (data[j+3] < 0 ? ' negatif' : '')));
              }
            },
            "footerCallback": function ( row, data, start, end, display ) {
              var api = this.api(), data;

              // Remove the formatting to get integer data for summation
              var intVal = function ( i ) {
                  return typeof i === 'string' ?
                      i.replace(/[\$,]/g, '')*1 :
                      typeof i === 'number' ?
                          i : 0;
              };

              for (var i = 0; i < params["nbMonthDiff"]; i++) {
                var totalCA = 0;
                var totalCAPrec = 0;
                var j=i*3;

                api.rows( ":not('.other')" ).every(function () {
                  totalCA += parseFloat(this.data()[j+1]);
                  totalCAPrec += parseFloat(this.data()[j+2]);
                });

                // Update footer
                $( api.column( j+1 ).footer() ).html(numberWithSpaces(totalCA.toFixed(2)) + ' €');
                $( api.column( j+2 ).footer() ).html(numberWithSpaces(totalCAPrec.toFixed(2)) + ' €');

                var totalEvol = (totalCAPrec != 0 ? ((totalCA * 100 / totalCAPrec) - 100).toFixed(2) : "-");
                $( api.column( j+3 ).footer() ).html(totalEvol.toString().replace(".", ",") + ' %'); 
                if (totalEvol > 0)
                {
                  $( api.column( j+3 ).footer() ).addClass("positif");
                } else if (totalEvol < 0)
                {
                  $( api.column( j+3 ).footer() ).addClass("negatif");
                }
              }
            },
            initComplete: function () {
              var api = this.api();
              // Header
              $('#tab-purchases thead tr#forFilters th').each(function(i) {
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

              // Delete global search tab
              $('#tab-purchases_filter').css('display', 'none');
              // Display tab
              $('.load').css('display', 'none');
              $('.layout_withParams_right').css('background', '#E8E8E8');
              $('.tableau').css('display', 'block');

              tabPurchases.columns.adjust();

              $('#mask-null-quantities').on('change', function() {
                $.fn.dataTable.ext.search.push(
                  function( settings, searchData, index, rowData, counter ) {
                    if ( $('#mask-null-quantities').is(':checked') )
                    {
                      for (var i = 0; i < params["nbMonthDiff"]; i++) {
                        if ((rowData[(i*3)+1] != 0) || (rowData[(i*3)+2] != 0))
                          return true;
                      }
                      return false;
                    }
                    else 
                      return true;
                  }     
                );
                tabPurchases.draw();
                $.fn.dataTable.ext.search.pop();
              });
              //$('#mask-null-quantities').prop( "checked", true ).trigger("change");
              $('#div-mask-null-quantities').removeClass('hide');
            }
          });
        }
      }
    });
  }

  /**
  * Success AJAX request : display "By clinic" table
  */
  function getListOfPurchasesByClinics(params)
  {
    $.ajax({
      type: "POST",
      dataType: "json",
      url: "getListOfPurchasesByParams", 
      data: $.param(params),
      success: function(json) {
        var startMonthName = new Date(params["startYear"], params["startMonth"]-1).toLocaleString("fr", { month: "short" });
        var endMonthName = new Date(params["endYear"], params["endMonth"]-1).toLocaleString("fr", { month: "short" });
        var nbYearDiff = (params["startYear"] != params["endYear"]) ? (params["endYear"] - params["startYear"]) : 1;

        if (params["byYear"])
        {
          // Complete year
          var data = jQuery.map(json, function(el, i) {
            var evol = (el.ca_periode_prec != 0 ? ((el.ca_periode*100/el.ca_periode_prec)-100).toFixed(2) : "-");

            return [[ el.veterinaires, el.clinique, el.ca_periode, el.ca_periode_prec, evol, el.clinique_id, el.manque_periode, el.manque_periode_prec ]];
          });

          if (tabPurchases != null)
          {
            tabPurchases.destroy();
          }

          // Construct tab by clinic
          var periode = startMonthName + params["startYear"].substring(2,4) + " à " + endMonthName + params["endYear"].substring(2,4);
          var periodePrec = startMonthName + String(params["startYear"]-nbYearDiff).substring(2,4) + " à " + endMonthName + String(params["endYear"]-nbYearDiff).substring(2,4);
          $('#tab-purchases').html("<thead><tr><th class='texte width-30'>@lang('amadeo.clinics.veterinaries')</th><th class='texte width-20'>@lang('amadeo.clinics.name')</th><th class='width-15 text-center'>@lang('amadeo.purchases.amount')<br><span>" + periode + "</span></th><th class='width-15 text-center'>@lang('amadeo.purchases.amount')<br><span>" + periodePrec + "</span></th><th class='width-9 text-center'>@lang('amadeo.purchases.evolution-short')</th></tr><tr id='forFilters'><th class='text-filter'></th><th class='text-filter'></th><th colspan=3><div id='div-mask-null-quantities' class='checkbox-item-horizontal hide'><div class='checkboxContainer'><input id='mask-null-quantities' name='mask-null-quantities' type='checkbox' value='1'><label for='mask-null-quantities'></label></div><div class='checkboxLabel'><label for='mask-null-quantities' style='font-size: 12px;'>@lang('amadeo.purchases.mask-empty')</label></div></div></th></tr></thead><tbody></tbody><tfoot><tr><td class='texte width-30'>@lang('amadeo.purchases.total')</td><td class='texte width-20'></td><td class='nombre width-15'></td><td class='nombre width-15'></td><td class='nombre width-9'></td></tr></tfoot>");

          // Complete period
          tabPurchases = $('#tab-purchases').DataTable( {
            "paging": false,
            "bLengthChange": false,
            "ordering": true,
            "info": false,
            "scrollY": "37em",
            "scrollX": true,
            "scrollCollapse": true,
            "bSortCellsTop": true,
            "destroy": true,
            "order": [[ 0, "asc" ]],
            "language": {
              "url": "//cdn.datatables.net/plug-ins/1.10.16/i18n/French.json",
              "emptyTable": '@lang("amadeo.clinics.search-empty")'
            },
              "aoColumns": [
                null,
                null,
                { "render": function ( data, type, row ) {
                    return numberWithSpaces(data)+' €';
                }, "sType": "numeric-comma" },
                { "render": function ( data, type, row ) {
                    return numberWithSpaces(data)+' €';
                }, "sType": "numeric-comma" },
                { "render": function ( data, type, row ) {
                    if (data != '-')
                      return data.toString().replace(".", ",") + ' %';
                    else
                      return data;
                }, "sType": "numeric-comma" },
              ],
            "aaData": data,
            "createdRow": function ( row, data, index ) {
              $('td', row).each(function(){
                $(this).html('<div>' + $(this).html() + '</div>');
              });

              $('td', row).eq(0).addClass('width-30');
              $('td', row).eq(0).find('div').addClass('texte');
              $('td', row).eq(0).find('div').attr('title', $('td', row).eq(0).find('div').html());
              $('td', row).eq(1).addClass('width-20');
              $('td', row).eq(1).find('div').addClass('texte');
              $('td', row).eq(1).find('div').attr('title', $('td', row).eq(1).find('div').html());
              $('td', row).eq(2).addClass('width-15');
              $('td', row).eq(2).find('div').addClass('nombre');
              if (data[9])
              {
                $('td', row).eq(2).find('div').addClass('orange');
              }
              $('td', row).eq(3).addClass('width-15');
              $('td', row).eq(3).find('div').addClass('nombre');
              if (data[10])
              {
                $('td', row).eq(3).find('div').addClass('orange');
              }
              $('td', row).eq(4).addClass('width-15');
              $('td', row).eq(4).find('div').addClass('nombre');
              $('td', row).eq(4).find('div').addClass('nombre' + (data[4] > 0 ? ' positif' : (data[4] < 0 ? ' negatif' : '')));
            },
            "footerCallback": function ( row, data, start, end, display ) {
              var api = this.api(), data;

              // Remove the formatting to get integer data for summation
              var intVal = function ( i ) {
                  return typeof i === 'string' ?
                      i.replace(/[\$,]/g, '')*1 :
                      typeof i === 'number' ?
                          i : 0;
              };

              var totalCA = 0;
              var totalCAPrec = 0;

              for (var i = 2; i < 4; i++) {
                // Total over all pages
                var total = api.column( i ).data().reduce( function (a, b) {
                  return parseFloat(a) + parseFloat(b);
                }, 0 );

                if (i == 2)
                {
                  totalCA = total;
                } else 
                {
                  totalCAPrec = total;
                }
                
                // Update footer
                $( api.column( i ).footer() ).html(numberWithSpaces(total.toFixed(2)) + ' €');
              }
          
              var totalEvol = (totalCAPrec != 0 ? ((totalCA * 100 / totalCAPrec) - 100).toFixed(2) : "-");
              $( api.column( 4 ).footer() ).html(totalEvol.toString().replace(".", ",") + ' %'); 
              if (totalEvol > 0)
              {
                $( api.column( 4 ).footer() ).addClass("positif");
              } else if (totalEvol < 0)
              {
                $( api.column( 4 ).footer() ).addClass("negatif");
              }
            },
            initComplete: function () {
              var api = this.api();
              // Header
              $('#tab-purchases thead tr#forFilters th').each(function(i) {
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

              // Delete global search tab
              $('#tab-purchases_filter').css('display', 'none');
              // Display tab
              $('.load').css('display', 'none');
              $('.layout_withParams_right').css('background', '#E8E8E8');
              $('.tableau').css('display', 'block');

              tabPurchases.columns.adjust();

              $('#mask-null-quantities').on('change', function() {
                $.fn.dataTable.ext.search.push(
                  function( settings, searchData, index, rowData, counter ) {
                    if ( $('#mask-null-quantities').is(':checked') )
                      return (rowData[2] != 0) || (rowData[3] != 0);
                    else 
                      return true;
                  }     
                );
                tabPurchases.draw();
                $.fn.dataTable.ext.search.pop();
              });
              //$('#mask-null-quantities').prop( "checked", true ).trigger("change");
              $('#div-mask-null-quantities').removeClass('hide');
            }
          } );
        } else
        {
          // Detail by month
          var data = jQuery.map(json, function(el, j) {
            var array = [ el.veterinaires, el.clinique ];
            var arrayMissing = [];

            for (var i = 0; i < params["nbMonthDiff"]; i++) {
              array.push(el["ca_periode_m"+i]);
              array.push(el["ca_periode_prec_m"+i]);
              var evol = (el["ca_periode_prec_m"+i] != 0 ? ((el["ca_periode_m"+i] * 100 / el["ca_periode_prec_m"+i]) - 100).toFixed(2) : "-");
              array.push(evol);

              arrayMissing.push(el["manque_periode_m"+i]);
              arrayMissing.push(el["manque_periode_prec_m"+i]);
            }

            array.push(el.clinique_id);
            array = array.concat(arrayMissing);

            return [ array ];
          });

          var headerLine1 = "<tr><th class='texte width-30' rowspan='2'>@lang('amadeo.clinics.veterinaries')</th><th class='texte width-20' rowspan='2'>@lang('amadeo.clinics.name')</th>";
          var headerLine2 = "<tr>";
          var headerLine3 = "<tr id='forFilters'><th class='text-filter'></th><th class='text-filter'></th>"
          var footer = "<tr><td class='texte width-30'>@lang('amadeo.purchases.total')</td><td class='texte width-20'></td>";
          var aoColumns = [ null, null ];
          var incAnnee = false;

          for (var i = 0; i < params["nbMonthDiff"]; i++) {
            // Header
            headerLine1 += "<th colspan='3' class='text-center'";
            var impair = false;
            if (!(i%2))
            {
              headerLine1 += " style='background: #F4F3F3;'";
              impair = true;
            }
            headerLine1 += ">" + new Date(params["startYear"], (params["startMonth"]-1+i)%12).toLocaleString("fr", { month: "long" }) + "</th>";

            if (i>0 && (params["startMonth"]-1+i)%12 == 0)
            {
              incAnnee = true;
            }
            headerLine2 += "<th class='width-15 text-center'";
            if (impair)
            {
              headerLine2 += " style='background: #F4F3F3;'";
            }
            headerLine2 += ">@lang('amadeo.purchases.amount')<br><span>" + (incAnnee ? params["endYear"] : params["startYear"]) + "</span></th>"
            + "<th class='width-15 text-center'";
            if (impair)
            {
              headerLine2 += " style='background: #F4F3F3;'";
            }
            headerLine2 += ">@lang('amadeo.purchases.amount')<br><span>" + (incAnnee ? params["endYear"]-1 : params["startYear"]-1) + "</span></th>"
            + "<th class='width-15 text-center'";
            if (impair)
            {
              headerLine2 += " style='background: #F4F3F3;'";
            }
            headerLine2 += ">@lang('amadeo.purchases.evolution-short')</th>";
            if (i == 0)
            {
              headerLine3 += "<th colspan=3><div id='div-mask-null-quantities' class='checkbox-item-horizontal hide'><div class='checkboxContainer'><input id='mask-null-quantities' name='mask-null-quantities' type='checkbox' value='1'><label for='mask-null-quantities'></label></div><div class='checkboxLabel'><label for='mask-null-quantities' style='font-size: 12px;'>@lang('amadeo.purchases.mask-empty')</label></div></div></th>"
            } else 
            {
              headerLine3 += "<th></th><th></th><th></th>";
            }

            // Footer
            footer += "<td class='nombre width-15'></td><td class='nombre width-15'></td><td class='nombre width-9'></td>";

            // Columns
            aoColumns.push({ "render": function ( data, type, row ) {
              return numberWithSpaces(data)+' €';
            }, "sType": "numeric-comma" });
            aoColumns.push({ "render": function ( data, type, row ) {
              return numberWithSpaces(data)+' €';
            }, "sType": "numeric-comma" });
            aoColumns.push({ "render": function ( data, type, row ) {
              if (data != '-')
                return data.toString().replace(".", ",") + ' %';
              else
                return data;
            }, "sType": "numeric-comma" });
          }

          if (tabPurchases != null)
          {
            tabPurchases.destroy();
          }
          
          $('#tab-purchases').html("<thead>" + headerLine1 + "</tr>" + headerLine2 + "</tr>" + headerLine3 + "</tr></thead><tbody></tbody><tfoot>" + footer + + "</tr></tfoot>");

          tabPurchases = $('#tab-purchases').DataTable( {
            "paging": false,
            "bLengthChange": false,
            "ordering": true,
            "info": false,
            "scrollY": "37em",
            "scrollX": true,
            "scrollCollapse": true,
            "bSortCellsTop": true,
            "destroy": true,
            "order": [[ 0, "asc" ]],
            "language": {
              "url": "//cdn.datatables.net/plug-ins/1.10.16/i18n/French.json",
              "emptyTable": '@lang("amadeo.clinics.search-empty")'
            },
            "aoColumns": aoColumns,
            "aaData": data,
            "createdRow": function ( row, data, index ) {
              $('td', row).each(function(){
                $(this).html('<div>' + $(this).html() + '</div>');
              });

              $('td', row).eq(0).addClass('width-30');
              $('td', row).eq(0).find('div').addClass('texte');
              $('td', row).eq(0).find('div').attr('title', $('td', row).eq(0).find('div').html());
              $('td', row).eq(1).addClass('width-20');
              $('td', row).eq(1).find('div').addClass('texte');
              $('td', row).eq(1).find('div').attr('title', $('td', row).eq(1).find('div').html());
              
              for (var i = 0; i < params["nbMonthDiff"]; i++) {
                var j=i*3;
                $('td', row).eq(j+2).addClass('width-15');
                $('td', row).eq(j+2).find('div').addClass('nombre');
                if (data[3*params["nbMonthDiff"]+(2*i)+3])
                {
                  $('td', row).eq(j+2).find('div').addClass('orange');
                }
                $('td', row).eq(j+3).addClass('width-15');
                $('td', row).eq(j+3).find('div').addClass('nombre');
                if (data[3*params["nbMonthDiff"]+(2*i)+4])
                {
                  $('td', row).eq(j+3).find('div').addClass('orange');
                }
                $('td', row).eq(j+4).addClass('width-9');
                $('td', row).eq(j+4).find('div').addClass('nombre' + (data[j+4] > 0 ? ' positif' : (data[j+4] < 0 ? ' negatif' : '')));
              }
            },
            "footerCallback": function ( row, data, start, end, display ) {
              var api = this.api(), data;

              // Remove the formatting to get integer data for summation
              var intVal = function ( i ) {
                  return typeof i === 'string' ?
                      i.replace(/[\$,]/g, '')*1 :
                      typeof i === 'number' ?
                          i : 0;
              };

              for (var i = 0; i < params["nbMonthDiff"]; i++) {
                var totalCA = 0;
                var totalCAPrec = 0;

                for (var j = (i*3)+2; j < (i*3)+4; j++) {
                  // Total over all pages
                  var total = api.column( j ).data().reduce( function (a, b) {
                    return parseFloat(a) + parseFloat(b);
                  }, 0 );
                  
                  if (j == (i*3)+2)
                  {
                    totalCA = total;
                  } else
                  {
                    totalCAPrec = total;
                  }
          
                  // Update footer
                  $( api.column( j ).footer() ).html(numberWithSpaces(total.toFixed(2)) + ' €');
                }

                var totalEvol = (totalCAPrec != 0 ? ((totalCA * 100 / totalCAPrec) - 100).toFixed(2) : "-");
                $( api.column( (i*3)+4 ).footer() ).html(totalEvol.toString().replace(".", ",") + ' %'); 
                if (totalEvol > 0)
                {
                  $( api.column( (i*3)+4 ).footer() ).addClass("positif");
                } else if (totalEvol < 0)
                {
                  $( api.column( (i*3)+4 ).footer() ).addClass("negatif");
                }
              }
            },
            initComplete: function () {
              var api = this.api();
              // Header
              $('#tab-purchases thead tr#forFilters th').each(function(i) {
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

              // Delete global search tab
              $('#tab-purchases_filter').css('display', 'none');
              // Display tab
              $('.load').css('display', 'none');
              $('.layout_withParams_right').css('background', '#E8E8E8');
              $('.tableau').css('display', 'block');

              tabPurchases.columns.adjust();

              $('#mask-null-quantities').on('change', function() {
                $.fn.dataTable.ext.search.push(
                  function( settings, searchData, index, rowData, counter ) {
                    if ( $('#mask-null-quantities').is(':checked') )
                    {
                      for (var i = 0; i < params["nbMonthDiff"]; i++) {
                        if ((rowData[(i*3)+2] != 0) || (rowData[(i*3)+3] != 0))
                          return true;
                      }
                      return false;
                    }
                    else 
                      return true;
                  }     
                );
                tabPurchases.draw();
                $.fn.dataTable.ext.search.pop();
              });
              //$('#mask-null-quantities').prop( "checked", true ).trigger("change");
              $('#div-mask-null-quantities').removeClass('hide');
            }
          });
        }
      }
    });
  }

  /**
  * Success AJAX request : display "By category" table
  */
  function getListOfPurchasesByCategories(params)
  {
    $.ajax({
      type: "POST",
      dataType: "json",
      url: "getListOfPurchasesByParams", 
      data: $.param(params),
      success: function(json) {
        var startMonthName = new Date(params["startYear"], params["startMonth"]-1).toLocaleString("fr", { month: "short" });
        var endMonthName = new Date(params["endYear"], params["endMonth"]-1).toLocaleString("fr", { month: "short" });
        var nbYearDiff = (params["startYear"] != params["endYear"]) ? (params["endYear"] - params["startYear"]) : 1;

        if (params["byYear"])
        {
          // Complete year
          var data = jQuery.map(json, function(el, i) {
            var evol = (el.ca_periode_prec != 0 ? ((el.ca_periode*100/el.ca_periode_prec)-100).toFixed(2) : "-");

            return [[ el.annee, el.especes, el.laboratoire, el.categorie, el.ca_periode, el.ca_periode_prec, evol, el.categorie_id, el.manque_periode, el.manque_periode_prec ]];
          });

          if (tabPurchases != null)
          {
            tabPurchases.destroy();
          }

          // Construct tab by category
          var periode = startMonthName + params["startYear"].substring(2,4) + " à " + endMonthName + params["endYear"].substring(2,4);
          var periodePrec = startMonthName + String(params["startYear"]-nbYearDiff).substring(2,4) + " à " + endMonthName + String(params["endYear"]-nbYearDiff).substring(2,4);
          $('#tab-purchases').html("<thead><tr><th class='texte width-10'>@lang('amadeo.categories.year')</th><th class='texte width-10'>@lang('amadeo.categories.specie')</th><th class='texte width-10'>@lang('amadeo.products.seller')</th><th class='texte width-20'>@lang('amadeo.categories.name')</th><th class='width-10 text-center'>@lang('amadeo.purchases.amount')<br><span>" + periode + "</span></th><th class='width-10 text-center'>@lang('amadeo.purchases.amount')<br><span>" + periodePrec + "</span></th><th class='width-9 text-center'>@lang('amadeo.purchases.evolution-short')</th></tr><tr id='forFilters'><th class='select-filter'></th><th class='text-filter'></th><th class='text-filter'></th><th class='text-filter'></th><th colspan=3><div id='div-mask-null-quantities' class='checkbox-item-horizontal hide'><div class='checkboxContainer'><input id='mask-null-quantities' name='mask-null-quantities' type='checkbox' value='1'><label for='mask-null-quantities'></label></div><div class='checkboxLabel'><label for='mask-null-quantities' style='font-size: 12px;'>@lang('amadeo.purchases.mask-empty')</label></div></div></th></tr></thead><tbody></tbody><tfoot><tr><td class='texte width-10'>@lang('amadeo.purchases.total')</td><td class='texte width-10'></td><td class='texte width-10'></td><td class='texte width-20'></td><td class='nombre width-10'></td><td class='nombre width-10'></td><td class='nombre width-9'></td></tr></tfoot>");

          // Complete period
          tabPurchases = $('#tab-purchases').DataTable( {
            "paging": false,
            "bLengthChange": false,
            "ordering": true,
            "info": false,
            "scrollY": "37em",
            "scrollX": true,
            "scrollCollapse": true,
            "bSortCellsTop": true,
            "destroy": true,
            "order": [[ 0, "asc" ], [ 2, "asc" ], [ 3, "asc" ]],
            "language": {
              "url": "//cdn.datatables.net/plug-ins/1.10.16/i18n/French.json",
              "emptyTable": '@lang("amadeo.categories.search-empty")'
            },
              "aoColumns": [
                null,
                null,
                null,
                null,
                { "render": function ( data, type, row ) {
                    return numberWithSpaces(data)+' €';
                }, "sType": "numeric-comma" },
                { "render": function ( data, type, row ) {
                    return numberWithSpaces(data)+' €';
                }, "sType": "numeric-comma" },
                { "render": function ( data, type, row ) {
                    if (data != '-')
                      return data.toString().replace(".", ",") + ' %';
                    else
                      return data;
                }, "sType": "numeric-comma" },
              ],
            "aaData": data,
            "createdRow": function ( row, data, index ) {
              $('td', row).each(function(){
                $(this).html('<div>' + $(this).html() + '</div>');
              });

              $('td', row).eq(0).addClass('width-10');
              $('td', row).eq(0).find('div').addClass('texte');
              $('td', row).eq(0).find('div').attr('title', $('td', row).eq(0).find('div').html());
              $('td', row).eq(1).addClass('width-10');
              $('td', row).eq(1).find('div').addClass('texte');
              $('td', row).eq(1).find('div').attr('title', $('td', row).eq(1).find('div').html());
              $('td', row).eq(2).addClass('width-10');
              $('td', row).eq(2).find('div').addClass('texte');
              $('td', row).eq(2).find('div').attr('title', $('td', row).eq(2).find('div').html());
              $('td', row).eq(3).addClass('width-20');
              $('td', row).eq(3).find('div').addClass('texte');
              $('td', row).eq(3).find('div').attr('title', $('td', row).eq(3).find('div').html());
              $('td', row).eq(4).addClass('width-10');
              $('td', row).eq(4).find('div').addClass('nombre');
              if (data[11])
              {
                $('td', row).eq(4).find('div').addClass('orange');
              }
              $('td', row).eq(5).addClass('width-10');
              $('td', row).eq(5).find('div').addClass('nombre');
              if (data[12])
              {
                $('td', row).eq(5).find('div').addClass('orange');
              }
              $('td', row).eq(6).addClass('width-9');
              $('td', row).eq(6).find('div').addClass('nombre');
              $('td', row).eq(6).find('div').addClass('nombre' + (data[6] > 0 ? ' positif' : (data[6] < 0 ? ' negatif' : '')));
            },
            "footerCallback": function ( row, data, start, end, display ) {
              var api = this.api(), data;

              // Remove the formatting to get integer data for summation
              var intVal = function ( i ) {
                  return typeof i === 'string' ?
                      i.replace(/[\$,]/g, '')*1 :
                      typeof i === 'number' ?
                          i : 0;
              };

              var totalCA = 0;
              var totalCAPrec = 0;

              for (var i = 4; i < 6; i++) {
                // Total over all pages
                var total = api.column( i ).data().reduce( function (a, b) {
                  return parseFloat(a) + parseFloat(b);
                }, 0 );

                if (i == 4)
                {
                  totalCA = total;
                } else 
                {
                  totalCAPrec = total;
                }
                
                // Update footer
                $( api.column( i ).footer() ).html(numberWithSpaces(total.toFixed(2)) + ' €');
              }
          
              var totalEvol = (totalCAPrec != 0 ? ((totalCA * 100 / totalCAPrec) - 100).toFixed(2) : "-");
              $( api.column( 6 ).footer() ).html(totalEvol.toString().replace(".", ",") + ' %'); 
              if (totalEvol > 0)
              {
                $( api.column( 6 ).footer() ).addClass("positif");
              } else if (totalEvol < 0)
              {
                $( api.column( 6 ).footer() ).addClass("negatif");
              }
            },
            initComplete: function () {
              var api = this.api();
              // Header
              $('#tab-purchases thead tr#forFilters th').each(function(i) {
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

              // Delete global search tab
              $('#tab-purchases_filter').css('display', 'none');
              // Display tab
              $('.load').css('display', 'none');
              $('.layout_withParams_right').css('background', '#E8E8E8');
              $('.tableau').css('display', 'block');

              tabPurchases.columns.adjust();

              $('#mask-null-quantities').on('change', function() {
                $.fn.dataTable.ext.search.push(
                  function( settings, searchData, index, rowData, counter ) {
                    if ( $('#mask-null-quantities').is(':checked') )
                      return (rowData[4] != 0) || (rowData[5] != 0);
                    else 
                      return true;
                  }     
                );
                tabPurchases.draw();
                $.fn.dataTable.ext.search.pop();
              });
              //$('#mask-null-quantities').prop( "checked", true ).trigger("change");
              $('#div-mask-null-quantities').removeClass('hide');
            }
          } );
        } else
        {
          // Detail by month
          var data = jQuery.map(json, function(el, j) {
            var array = [ el.annee, el.especes, el.laboratoire, el.categorie ];
            var arrayMissing = [];

            for (var i = 0; i < params["nbMonthDiff"]; i++) {
              array.push(el["ca_periode_m"+i]);
              array.push(el["ca_periode_prec_m"+i]);
              var evol = (el["ca_periode_prec_m"+i] != 0 ? ((el["ca_periode_m"+i] * 100 / el["ca_periode_prec_m"+i]) - 100).toFixed(2) : "-");
              array.push(evol);

              arrayMissing.push(el["manque_periode_m"+i]);
              arrayMissing.push(el["manque_periode_prec_m"+i]);
            }

            array.push(el.categorie_id);
            array = array.concat(arrayMissing);

            return [ array ];
          });
        
          var headerLine1 = "<tr><th class='texte width-10' rowspan='2'>@lang('amadeo.categories.year')</th><th class='texte width-10' rowspan='2'>@lang('amadeo.categories.specie')</th><th class='texte width-10' rowspan='2'>@lang('amadeo.products.seller')</th><th class='texte width-20' rowspan='2'>@lang('amadeo.categories.name')</th>";
          var headerLine2 = "<tr>";
          var headerLine3 = "<tr id='forFilters'><th class='select-filter'></th><th class='text-filter'></th><th class='text-filter'></th><th class='text-filter'></th>"
          var footer = "<tr><td colspan='4' class='texte width-50'>@lang('amadeo.purchases.total')</td>";
          var aoColumns = [ null, null, null, null ];
          var incAnnee = false;

          for (var i = 0; i < params["nbMonthDiff"]; i++) {
            // Header
            headerLine1 += "<th colspan='3' class='text-center'";
            var impair = false;
            if (!(i%2))
            {
              headerLine1 += " style='background: #F4F3F3;'";
              impair = true;
            }
            headerLine1 += ">" + new Date(params["startYear"], (params["startMonth"]-1+i)%12).toLocaleString("fr", { month: "long" }) + "</th>";

            if (i>0 && (params["startMonth"]-1+i)%12 == 0)
            {
              incAnnee = true;
            }
            headerLine2 += "<th class='width-15 text-center'";
            if (impair)
            {
              headerLine2 += " style='background: #F4F3F3;'";
            }
            headerLine2 += ">@lang('amadeo.purchases.amount')<br><span>" + (incAnnee ? params["endYear"] : params["startYear"]) + "</span></th>"
            + "<th class='width-15 text-center'";
            if (impair)
            {
              headerLine2 += " style='background: #F4F3F3;'";
            }
            headerLine2 += ">@lang('amadeo.purchases.amount')<br><span>" + (incAnnee ? params["endYear"]-1 : params["startYear"]-1) + "</span></th>"
            + "<th class='width-15 text-center'";
            if (impair)
            {
              headerLine2 += " style='background: #F4F3F3;'";
            }
            headerLine2 += ">@lang('amadeo.purchases.evolution-short')</th>";
            if (i == 0)
            {
              headerLine3 += "<th colspan=3><div id='div-mask-null-quantities' class='checkbox-item-horizontal hide'><div class='checkboxContainer'><input id='mask-null-quantities' name='mask-null-quantities' type='checkbox' value='1'><label for='mask-null-quantities'></label></div><div class='checkboxLabel'><label for='mask-null-quantities' style='font-size: 12px;'>@lang('amadeo.purchases.mask-empty')</label></div></div></th>";
            } else 
            {
              headerLine3 += "<th></th><th></th><th></th>";
            }

            // Footer
            footer += "<td class='nombre width-15'></td><td class='nombre width-15'></td><td class='nombre width-9'></td>";

            // Columns
            aoColumns.push({ "render": function ( data, type, row ) {
              return numberWithSpaces(data)+' €';
            }, "sType": "numeric-comma" });
            aoColumns.push({ "render": function ( data, type, row ) {
              return numberWithSpaces(data)+' €';
            }, "sType": "numeric-comma" });
            aoColumns.push({ "render": function ( data, type, row ) {
              if (data != '-')
                return data.toString().replace(".", ",") + ' %';
              else
                return data;
            }, "sType": "numeric-comma" });
          }

          if (tabPurchases != null)
          {
            tabPurchases.destroy();
          }
          
          $('#tab-purchases').html("<thead>" + headerLine1 + "</tr>" + headerLine2 + "</tr>" + headerLine3 + "</tr></thead><tbody></tbody><tfoot>" + footer + + "</tr></tfoot>");

          tabPurchases = $('#tab-purchases').DataTable( {
            "paging": false,
            "bLengthChange": false,
            "ordering": true,
            "info": false,
            "scrollY": "37em",
            "scrollX": true,
            "scrollCollapse": true,
            "bSortCellsTop": true,
            "destroy": true,
            "order": [[ 0, "asc" ], [ 2, "asc" ], [ 3, "asc" ]],
            "language": {
              "url": "//cdn.datatables.net/plug-ins/1.10.16/i18n/French.json",
              "emptyTable": '@lang("amadeo.categories.search-empty")'
            },
            "aoColumns": aoColumns,
            "aaData": data,
            "createdRow": function ( row, data, index ) {
              $('td', row).each(function(){
                $(this).html('<div>' + $(this).html() + '</div>');
              });

              $('td', row).eq(0).addClass('width-10');
              $('td', row).eq(0).find('div').addClass('texte');
              $('td', row).eq(0).find('div').attr('title', $('td', row).eq(0).find('div').html());
              $('td', row).eq(1).addClass('width-10');
              $('td', row).eq(1).find('div').addClass('texte');
              $('td', row).eq(1).find('div').attr('title', $('td', row).eq(1).find('div').html());
              $('td', row).eq(2).addClass('width-10');
              $('td', row).eq(2).find('div').addClass('texte');
              $('td', row).eq(2).find('div').attr('title', $('td', row).eq(2).find('div').html());
              $('td', row).eq(3).addClass('width-20');
              $('td', row).eq(3).find('div').addClass('texte');
              $('td', row).eq(3).find('div').attr('title', $('td', row).eq(3).find('div').html());
              
              for (var i = 0; i < params["nbMonthDiff"]; i++) {
                var j=i*3;
                $('td', row).eq(j+4).addClass('width-15');
                $('td', row).eq(j+4).find('div').addClass('nombre');
                if (data[3*params["nbMonthDiff"]+(2*i)+5])
                {
                  $('td', row).eq(j+4).find('div').addClass('orange');
                }
                $('td', row).eq(j+5).addClass('width-15');
                $('td', row).eq(j+5).find('div').addClass('nombre');
                if (data[3*params["nbMonthDiff"]+(2*i)+6])
                {
                  $('td', row).eq(j+5).find('div').addClass('orange');
                }
                $('td', row).eq(j+6).addClass('width-9');
                $('td', row).eq(j+6).find('div').addClass('nombre' + (data[j+6] > 0 ? ' positif' : (data[j+6] < 0 ? ' negatif' : '')));
              }
            },
            "footerCallback": function ( row, data, start, end, display ) {
              var api = this.api(), data;

              // Remove the formatting to get integer data for summation
              var intVal = function ( i ) {
                  return typeof i === 'string' ?
                      i.replace(/[\$,]/g, '')*1 :
                      typeof i === 'number' ?
                          i : 0;
              };

              for (var i = 0; i < params["nbMonthDiff"]; i++) {
                var totalCA = 0;
                var totalCAPrec = 0;

                for (var j = (i*3)+4; j < (i*3)+6; j++) {
                  // Total over all pages
                  var total = api.column( j ).data().reduce( function (a, b) {
                    return parseFloat(a) + parseFloat(b);
                  }, 0 );
                  
                  if (j == (i*3)+4)
                  {
                    totalCA = total;
                  } else
                  {
                    totalCAPrec = total;
                  }
          
                  // Update footer
                  $( api.column( j ).footer() ).html(numberWithSpaces(total.toFixed(2)) + ' €');
                }

                var totalEvol = (totalCAPrec != 0 ? ((totalCA * 100 / totalCAPrec) - 100).toFixed(2) : "-");
                $( api.column( (i*3)+6 ).footer() ).html(totalEvol.toString().replace(".", ",") + ' %'); 
                if (totalEvol > 0)
                {
                  $( api.column( (i*3)+6 ).footer() ).addClass("positif");
                } else if (totalEvol < 0)
                {
                  $( api.column( (i*3)+6 ).footer() ).addClass("negatif");
                }
              }
            },
            initComplete: function () {
              var api = this.api();
              // Header
              $('#tab-purchases thead tr#forFilters th').each(function(i) {
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

              // Delete global search tab
              $('#tab-purchases_filter').css('display', 'none');
              // Display tab
              $('.load').css('display', 'none');
              $('.layout_withParams_right').css('background', '#E8E8E8');
              $('.tableau').css('display', 'block');

              tabPurchases.columns.adjust();

              $('#mask-null-quantities').on('change', function() {
                $.fn.dataTable.ext.search.push(
                  function( settings, searchData, index, rowData, counter ) {
                    if ( $('#mask-null-quantities').is(':checked') )
                    {
                      for (var i = 0; i < params["nbMonthDiff"]; i++) {
                        if ((rowData[(i*3)+4] != 0) || (rowData[(i*3)+5] != 0))
                          return true;
                      }
                      return false;
                    }
                    else 
                      return true;
                  }     
                );
                tabPurchases.draw();
                $.fn.dataTable.ext.search.pop();
              });
              //$('#mask-null-quantities').prop( "checked", true ).trigger("change");
              $('#div-mask-null-quantities').removeClass('hide');
            }
          });
        }
      }
    });
  }

  /* Loading list of purchases */
  function loadListOfPurchases() {
    $("#search-buttons").click(function(){
      //console.log("DEBUT loadSubmitStatistiques");
      $("#downloadPurchasesByParamsButton").attr("download", "true");

      // Récupération des paramètres du formulaire
      var params = getDetailChiffresParameters();

      if (params != null)
      {
        $('.tableau').css('display', 'none');
        $('.load').css('display', 'block');

        switch (params["displayType"][0])
        {
          case "product":
            getListOfPurchasesByProducts(params);
            break;

          case "laboratory":
            getListOfPurchasesByLaboratories(params);
            break;

          case "clinic":
            getListOfPurchasesByClinics(params);
            break;

          case "category":
            getListOfPurchasesByCategories(params);
            break;
        }
      }

      //console.log("FIN loadSubmitStatistiques");

      return false;
    });      

    // Add countries values
    var $countriesList = $( '#downloadPurchasesCountry' ); 
    $countriesList.find( 'option' ).remove();
    $countriesList.append('<option value="">Sélectionner...</option>');
    var list_of_countries = $.parseJSON("{{ htmlspecialchars(Session::get('select_of_countries')) }}".replace( /&quot;/g, '"' ));
    list_of_countries.forEach(country => {
      $countriesList.append('<option value="' + country["id"] + '">' + country["name"] + '</option>');
    });
    
    // Add sources values
    var $sourcesList = $( '#downloadPurchasesSource' ); 
    $sourcesList.find( 'option' ).remove();
    $sourcesList.append('<option value="">Sélectionner...</option>');
    var list_of_sources = $.parseJSON("{{ htmlspecialchars(Session::get('select_of_sources')) }}".replace( /&quot;/g, '"' ));
    list_of_sources.forEach(source => {
      $sourcesList.append('<option value="' + source["id"] + '">' + source["name"] + '</option>');
    });
    
    // Add suppliers values
    var $suppliersList = $( '#downloadPurchasesSupplier' ); 
    $suppliersList.find( 'option' ).remove();
    $suppliersList.append('<option value="">Sélectionner...</option>');
    var list_of_suppliers = $.parseJSON("{{ htmlspecialchars(Session::get('list_of_laboratories')) }}".replace( /&quot;/g, '"' ));
    list_of_suppliers.forEach(supplier => {
      $suppliersList.append('<option value="' + supplier["id"] + '">' + supplier["name"] + '</option>');
    });
    
    $("#downloadPurchasesButton").click(function(){
      // Open popup
      $('#downloadPurchasesModal').modal("show");
      $( '#downloadPurchasesModal' ).draggable({
        handle: ".modal-header"
      });
    });

    $("#launchButtonDownloadPurchases").click(function(){
      // Récupération des paramètres du formulaire
      var startMonth = getSelectValueById("downloadPurchases-startMonth");
      var startYear = getSelectValueById("downloadPurchases-startYear");
      var endMonth = getSelectValueById("downloadPurchases-endMonth");
      var endYear = getSelectValueById("downloadPurchases-endYear");
      var country = getSelectValueById("downloadPurchasesCountry");
      var source = getSelectValueById("downloadPurchasesSource");
      var supplier = getSelectValueById("downloadPurchasesSupplier");

      document.location.href="downloadPurchasesCSV/" + startMonth + '/' + startYear+ '/' + endMonth+ '/' + endYear+ '/' + country + '/' + (source != "" ? source : 0) + '/' + (supplier != "" ? supplier : 0);

      bootoast.toast({
        message: "@lang('amadeo.download-progress')",
        type: 'success'
      });
      $('#downloadPurchasesModal').modal('hide');
    });      

    $("#downloadPurchasesByParamsButton").click(function(){
        if ($(this).attr("download") == "true")
        {
          document.location.href="downloadPurchasesByParamsCSV";
        } else
        {
          bootoast.toast({
            message: "@lang('amadeo.purchases.no-research')",
            type: 'warning'
          });
        }
    });
  }

</script>