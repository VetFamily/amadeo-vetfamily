sfHover = function() {
    var sfEls = document.getElementById("menu").getElementsByTagName("LI");
    for (var i=0; i<sfEls.length; i++) {
        sfEls[i].onmouseover=function() {
            this.className+=" sfhover";
        }
        sfEls[i].onmouseout=function() {
            this.className=this.className.replace(new RegExp(" sfhover\\b"), "");
        }
    }
}
if (window.attachEvent) window.attachEvent("onload", sfHover);

$(function() {
	
	$( '.menu>li' ).hover(function() {
		if (!$( '#menu-mobile').is(":checked"))
		{
			$(this).find('a:first').css('border-bottom', '7px solid var(--black)');
			$(this).find('a:first').css('box-shadow', '0 7px 0 var(--turquoise)');

			$(this).find('.sous-menu').css('display', 'flex');
		
		}
	}, function() {
		// on mouseout, reset the background colour
		$(this).find('a:first').css('border-bottom', '');
		$(this).find('a:first').css('box-shadow', '');
		
		$(this).find('.sous-menu').css('display', 'none');
	
	});

	$( '.menu a' ).click(function() {
		if (window.engagementsChanged && engagementsChanged)
		{
			var message = '<p>Des engagements sont en cours de modification.</p><p>@lang("amadeo.save.question")</p>';
	        var href = $(this).attr('href');
	        	    
	    	$.confirm({
	            'title'     : "Modification des engagements",
	            'message'   : message,
	            'buttons'   : {
	                'Oui'   : {
	                    'action': function(){
	                    	engagementsChanged = false;
	                    	window.location = href;
	                    }
	                },
	                'Non'    : { 'action': function(){  } }
	            }
	        });

	        return false;
		}
	});
});
