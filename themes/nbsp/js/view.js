(function($) {	


$(document).on( 'click', '#puzzle_check', function( event ) {
		event.preventDefault();
		size_x = $( 'input#size_x' ).val();
		size_y = $( 'input#size_y' ).val();
		the_post_id = $( 'input#the_post_id' ).val();
			
		$.ajax({
			url: ajaxpagination.ajaxurl,
			type: 'post',
			data: {
				action: 'tpuzzle_check',
				query_vars: ajaxpagination.query_vars,
				the_form:  $('#puzzle_form').serialize(),
				size_x: size_x ,
				size_y: size_y,
				the_post_id: the_post_id
				
			},
			success: function( html ) {
				$('.puzzle_check-container').html( html );
			},
			error: function (request, status, error) {
				alert(request.responseText);
			}
		})
	});

$(document).on( 'click', '#puzzle_save', function( event ) {
		event.preventDefault();
		size_x = $( 'input#size_x' ).val();
		size_y = $( 'input#size_y' ).val();
		the_post_id = $( 'input#the_post_id' ).val();
			
		$.ajax({
			url: ajaxpagination.ajaxurl,
			type: 'post',
			data: {
				action: 'tpuzzle_save',
				query_vars: ajaxpagination.query_vars,
				the_form: $('#puzzle_form').serialize(),
				size_x: size_x ,
				size_y: size_y,
				the_post_id: the_post_id
			},
			success: function( html ) {
				$('.puzzle_check-container').html( html );
			},
			error: function (request, status, error) {
				alert(request.responseText);
			}
		})
	});
	
$(".cel_letter").keyup(function() {
	if (this.value.length == this.maxLength) {
		var tab = $(this).attr("tabindex");
		tab++;
		$("[tabindex='"+tab+"']").focus();
	 }
});

})(jQuery);