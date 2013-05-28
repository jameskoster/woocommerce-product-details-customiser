(function ($) {

jQuery( document ).ready( function( $ ) {
	jQuery( 'input#wc_pdc_related, input#wc_pdc_upsells' ).change( function() {
		if ( jQuery( 'input#wc_pdc_related, input#wc_pdc_upsells' ).is( ':checked' ) ) {
			jQuery( 'input#wc_pdc_related' ).parents( ':eq(3)' ).next().show();
		} else {
			jQuery( 'input#wc_pdc_related' ).parents( ':eq(3)' ).next().hide();
		}
	}).change();
});

}(jQuery));