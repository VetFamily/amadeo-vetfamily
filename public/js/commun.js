function addEvent(obj, event, fct) {
    if (obj.attachEvent) //Est-ce IE ?
        obj.attachEvent("on" + event, fct); //Ne pas oublier le "on"
    else
        obj.addEventListener(event, fct, true);
}

function getSelectValueById(selectId)
{
	var elmt = document.getElementById(selectId);
	 
	if(elmt.multiple == false)
	{	
		return elmt.options[elmt.selectedIndex].value;
	}
	var values = new Array();
	for(var i=0; i< elmt.options.length; i++)
	{
		if(elmt.options[i].selected == true)
		{
			values[values.length] = elmt.options[i].value; 
		}
	}	
	return values;	
}

function getCheckboxRadioValueByName(selectName)
{
	var elmts = document.getElementsByName(selectName);
	var values = new Array();
	
	for(var i=0; i< elmts.length; i++)
	{
		if(elmts[i].checked)
		{
			values[values.length] = elmts[i].value; 
		}
	}	
	
	return values;	
}

function createSelectMois(name, selectedMonth)
{
  return '<select id="' + name + '" style="text-transform: capitalize;" name="' + name + '">'
    + '<option value="1"' + (selectedMonth == 1 ? ' selected' : '') + '>Janvier</option>'
    + '<option value="2"' + (selectedMonth == 2 ? ' selected' : '') + '>Février</option>'
    + '<option value="3"' + (selectedMonth == 3 ? ' selected' : '') + '>Mars</option>'
    + '<option value="4"' + (selectedMonth == 4 ? ' selected' : '') + '>Avril</option>'
    + '<option value="5"' + (selectedMonth == 5 ? ' selected' : '') + '>Mai</option>'
    + '<option value="6"' + (selectedMonth == 6 ? ' selected' : '') + '>Juin</option>'
    + '<option value="7"' + (selectedMonth == 7 ? ' selected' : '') + '>Juillet</option>'
    + '<option value="8"' + (selectedMonth == 8 ? ' selected' : '') + '>Août</option>'
    + '<option value="9"' + (selectedMonth == 9 ? ' selected' : '') + '>Septembre</option>'
    + '<option value="10"' + (selectedMonth == 10 ? ' selected' : '') + '>Octobre</option>'
    + '<option value="11"' + (selectedMonth == 11 ? ' selected' : '') + '>Novembre</option>'
    + '<option value="12"' + (selectedMonth == 12 ? ' selected' : '') + '>Décembre</option>'
    + '</select>';
}

function numberWithSpaces(nb) {
  if (nb == null || nb == 0)
    return "0,00";
  else
  {
    var parts = nb.toString().split('.');
    var part1 = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, " ");
    var part2 = parts[1];
        
    return part1 + (part2 != null ? ',' + part2 : "");
  }
}

function loadSortNumericComma() 
{
	jQuery.fn.dataTableExt.oSort['numeric-comma-asc']  = function(a,b) {
		var x = (a == "-") ? 0 : a.replace( / /g, "" ).replace( /,/, "." );
		var y = (b == "-") ? 0 : b.replace( / /g, "" ).replace( /,/, "." );
		x = parseFloat( x );
		y = parseFloat( y );
		return ((x < y) ? -1 : ((x > y) ?  1 : 0));
	};

	jQuery.fn.dataTableExt.oSort['numeric-comma-desc'] = function(a,b) {
		var x = (a == "-") ? 0 : a.replace( / /g, "" ).replace( /,/, "." );
		var y = (b == "-") ? 0 : b.replace( / /g, "" ).replace( /,/, "." );
		x = parseFloat( x );
		y = parseFloat( y );
		return ((x < y) ?  1 : ((x > y) ? -1 : 0));
	};
}

function onclickParamsArrow(isSmall)
{
	if (isSmall)
	{
		// Agrandissement du panneau des paramètres
		document.getElementById("parametrage").style.display = "flex";
		// Affichage de la flèche gauche
		document.getElementById("img_params_arrow_left").style.display = "block";
		// Non affichage de la flèche droite
		document.getElementById("img_params_arrow_right").style.display = "none";

	} else
	{
		// Réduction du panneau des paramètres
		document.getElementById("parametrage").style.display = "none";
		var table = $('#detail-chiffres-tab').DataTable();
		table.columns.adjust().draw();
		// Affichage de la flèche droite
		document.getElementById("img_params_arrow_right").style.display = "block";
		// Non affichage de la flèche gauche
		document.getElementById("img_params_arrow_left").style.display = "none";
	}
}

// https://tc39.github.io/ecma262/#sec-array.prototype.includes
if (!Array.prototype.includes) {
  Object.defineProperty(Array.prototype, 'includes', {
    value: function(searchElement, fromIndex) {

      // 1. Let O be ? ToObject(this value).
      if (this == null) {
        throw new TypeError('"this" is null or not defined');
      }

      var o = Object(this);

      // 2. Let len be ? ToLength(? Get(O, "length")).
      var len = o.length >>> 0;

      // 3. If len is 0, return false.
      if (len === 0) {
        return false;
      }

      // 4. Let n be ? ToInteger(fromIndex).
      //    (If fromIndex is undefined, this step produces the value 0.)
      var n = fromIndex | 0;

      // 5. If n ≥ 0, then
      //  a. Let k be n.
      // 6. Else n < 0,
      //  a. Let k be len + n.
      //  b. If k < 0, let k be 0.
      var k = Math.max(n >= 0 ? n : len - Math.abs(n), 0);

      // 7. Repeat, while k < len
      while (k < len) {
        // a. Let elementK be the result of ? Get(O, ! ToString(k)).
        // b. If SameValueZero(searchElement, elementK) is true, return true.
        // c. Increase k by 1.
        // NOTE: === provides the correct "SameValueZero" comparison needed here.
        if (o[k] === searchElement) {
          return true;
        }
        k++;
      }

      // 8. Return false
      return false;
    }
  });
}

(function($){
    $.confirm = function(params){

        if($('#confirmOverlay').length){
            // A confirm is already shown on the page:
            return false;
        }

        var buttonHTML = '';
        $.each(params.buttons,function(name,obj){

            // Generating the markup for the buttons:

            buttonHTML += '<a href="#" class="button">'+name+'<span></span></a>';

            if(!obj.action){
                obj.action = function(){};
            }
        });

        var markup = [
            '<div id="confirmOverlay">',
            '<div id="confirmBox">',
            '<h1>',params.title,'</h1>',
            '<div>',params.message,'</div>',
            '<div id="confirmButtons">',
            buttonHTML,
            '</div></div></div>'
        ].join('');

        $(markup).hide().appendTo('body').fadeIn();

        var buttons = $('#confirmBox .button'),
            i = 0;

        $.each(params.buttons,function(name,obj){
            buttons.eq(i++).click(function(){

                // Calling the action attribute when a
                // click occurs, and hiding the confirm.

                obj.action();
                $.confirm.hide();
                return false;
            });
        });
    }

    $.confirm.hide = function(){
        $('#confirmOverlay').fadeOut(function(){
            $(this).remove();
        });
    }

})(jQuery);

function formatDateString(value)
{
  var date = new Date(value);

  return (date.getDate() < 10 ? '0' : '') + date.getDate() + "/" + (((date.getMonth()+1) < 10 ? '0' : '') + (date.getMonth()+1)) + "/" + date.getFullYear();
}

function formatDate(date)
{
  return (date.getDate() < 10 ? '0' : '') + date.getDate() + "/" + (((date.getMonth()+1) < 10 ? '0' : '') + (date.getMonth()+1)) + "/" + date.getFullYear();
}

function formatDateStringToSql(value)
{
  return value.replace( /(\d{2})\/(\d{2})\/(\d{4})/, "$1/$2/$3");
}

function getDayOfYear(date) 
{
  var timestamp = new Date().setFullYear(new Date().getFullYear(), 0, 1);
  var yearFirstDay = Math.floor(timestamp / 86400000);
  var today = Math.ceil((date.getTime()) / 86400000 );
  var dayOfYear = today - yearFirstDay;
  return dayOfYear;
}

function days_of_a_year(year) 
{ 
  return isLeapYear(year) ? 366 : 365;
}

function getNbDaysOfPeriod(year, month1, month2)
{
  var result = 0;
  for (var i = month1; i <= month2; i++) {
    result += new Date(year, i, 0).getDate();
  }

  return result;
}

function getDayOfPeriod(annee, moisDateMAJ, jourDateMAJ, moisDebutPeriode)
{
  var result = getDayOfYear(new Date(annee, moisDateMAJ, jourDateMAJ)) - getDayOfYear(new Date(annee, moisDebutPeriode, 1));

  if (result < 0)
    return 0;
  else
    return result;
}

function isLeapYear(year) 
{
  return year % 400 === 0 || (year % 100 !== 0 && year % 4 === 0);
}
 
function sort_list(selectId)
{
  var arrTexts = new Array(); 
  var arrValues = new Array(); 
  var arrOldTexts = new Array();
  var select = $("#" + selectId + " option");
  // Sauvegarde de la valeur sélectionnée
  var selectedVal = $("#" + selectId).val();

  for(i=0; i<select.length; i++) 
  { 
    arrTexts[i] = select[i].text; 
    arrValues[i] = select[i].value; 

    arrOldTexts[i] = select[i].text; 
  }

  arrTexts.sort(naturalSort);

  for(i=0; i<select.length; i++) 
  { 
    select[i].text = arrTexts[i]; 
    for(j=0; j<select.length; j++) 
    { 
      if (arrTexts[i] == arrOldTexts[j]) 
      { 
        select[i].value = arrValues[j]; 
      j = select.length; 
      } 
    } 
  } 

  // Restitution de la valeur sélectionnée
  $("#" + selectId).val(selectedVal);
}

function naturalSort(as, bs){
    var a, b, a1, b1, i= 0, L, rx=  /(\d+)|(\D+)/g, rd=  /\d/;
    if(isFinite(as) && isFinite(bs)) return as - bs;
    a= String(as).toLowerCase();
    b= String(bs).toLowerCase();
    if(a=== b) return 0;
    if(!(rd.test(a) && rd.test(b))) return a> b? 1: -1;
    a= a.match(rx);
    b= b.match(rx);
    L= a.length> b.length? b.length: a.length;
    while(i<L){
        a1= a[i];
        b1= b[i++];
        if(a1!== b1){
            if(isFinite(a1) && isFinite(b1)){
                if(a1.charAt(0)=== '0') a1= '.' + a1;
                if(b1.charAt(0)=== '0') b1= '.' + b1;
                return a1 - b1;
            }
            else return a1> b1? 1: -1;
        }
    }
    return a.length - b.length;
}

function confirmBox(title, msg, $true, $false, doAction, cancelAction) {
  var content =  '<div class="confirmBox">'
                    + '<div class="modal-dialog">'
                      + '<div class="modal-content">'
                        + '<div class="modal-header">'
                          + '<button type="button" class="bootpopup-button close" data-dismiss="modal" aria-label="Fermer"><img src="/images/CROIX_FERMETURE_BLANC.svg" height="32px" width="32px"></button>'
                          + '<h4 class="modal-title" id="bootpopup-title">' + title + '</h4>'
                        + '</div>'
                        + '<div class="modal-body" style="display: flex; flex-direction: column; align-items: center;">' + msg + '</div>'
                        + '<div class="modal-footer">'
                          + '<div class="confirm-buttons">'
                            + '<div class="button cancelAction"><a>' + $false + '</a><span class="btn_delete"></span></div>'
                            + '<div class="button button_bold doAction"><a>' + $true + '</a><span class="btn_save"></span></div>'
                          + '</div>'
                        + '</div>'
                      + '</div>'
                    + '</div>'
                  + '</div>';

  if ($('.modal-backdrop').length == 0)
  {
    content += '<div id="confirmBox-backdrop" class="modal-backdrop in" style="background-color: var(--black);"></div>';
  }
  $('body').prepend(content);

  if ($('.modal-backdrop').length > 0)
  {
    $('.modal').css('opacity', '.75');
  }

  $('.doAction').click(function() {
    doAction(); 
    $('.confirmBox').remove();
    $('.modal').css('opacity', '1');
    $('#confirmBox-backdrop').remove();
  });
  $('.cancelAction, .fa-close').click(function() {
    cancelAction(); 
    $('.confirmBox').remove();
    $('.modal').css('opacity', '1');
    $('#confirmBox-backdrop').remove();
  });
  $('button.close').click(function() {
    $('.confirmBox').remove();
    $('.modal').css('opacity', '1');
    $('#confirmBox-backdrop').remove();
  });
}

(function($) {
    'use strict';
    $.fn.tooltipOnOverflow = function() {
        $(this).on("mouseenter", function() {
            if (this.offsetWidth < this.scrollWidth) {
                $(this).attr('title', $(this).text());
            } else {
                $(this).removeAttr("title");
            }
        });
    };
})(jQuery);

String.prototype.capitalize = function() {
  return this.charAt(0).toUpperCase() + this.toLowerCase().slice(1);
}
