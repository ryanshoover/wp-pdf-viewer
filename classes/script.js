// JavaScript Document

//resize thickbox popups
jQuery(function($) {
	var spacer = 120;
	$('a.thickbox').each(function(index) {
		$(this).attr('href', $(this).attr('href').replace('width=800','width='+ ($(window).width()-spacer) ).replace('height=600','height='+ ($(window).height()-spacer) ) ); 
	});
});

