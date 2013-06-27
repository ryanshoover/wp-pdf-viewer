// JavaScript Document
var selectedDoc = '';
var selectedDocName = '';

function clickNav(id) {
	var nav_link = '#'+id;
	var div_link = '#'+id+'-div';
	jQuery('.document-nav-menu div').removeClass('active');
	jQuery(nav_link).addClass('active');
	jQuery('.major-sec').hide();
	jQuery(div_link).show();
}

function startUpload(){
    document.getElementById('f1_upload_process').style.visibility = 'visible';
	document.getElementById('upload-form').style.visibility = 'hidden';
    return true;
}

function configDeleteButton() {
	jQuery("img.delete").click(function(e) {
		var postid = this.id.substr(7);
		var data = {
		action: 'delete_post',
		postid: postid
		};
		jQuery.post(ajaxurl, data, function(response) {
			jQuery("#doc-lib-container").html(response);
			configDeleteButton();
		});
	});
}

//load 1st page of pdf
function loadPageOne(pdf, container) {
	PDFJS.getDocument(pdf).then(function(pdf) {
	  pdf.getPage(1).then(function(page) {
		var scale = 1.5;
		var viewport = page.getViewport(scale);
		var canvas = document.getElementById(container);
		var context = canvas.getContext('2d');
		canvas.height = viewport.height;
		canvas.width = viewport.width;
		var renderContext = {
		  canvasContext: context,
		  viewport: viewport
		};
		page.render(renderContext);
	  });
	});
}

jQuery(document).ready(function(e) {
	//form validation
	/*
	jQuery("#upload-form").validate({
	  rules: {
		uploadfile: {
		  required: true,
		  accept: "application/pdf"
		}
	  }
	});
	*/
	
	//nav buttons
	jQuery('#upload').click(function(e) {
        clickNav('upload');
    });
	jQuery('#doc-lib').click(function(e) {
        clickNav('doc-lib');
    });
	
	//doc library
	jQuery('.doc-div').click(function(e) {
		jQuery('.doc-div').removeClass('doc-selected');
		jQuery(this).addClass('doc-selected');
		
		selectedDoc = jQuery(this).attr('id');
		selectedDocName = jQuery(this).children('p').text();
		jQuery("#existing_title").val(selectedDocName);
		jQuery("#existing_post_id").val(selectedDoc);
	});
	
	configDeleteButton();
	
	//advanced options
	jQuery(".advanced_options div").hide();
	jQuery(".arrow-down").hide();
	jQuery(".advanced_options h2").click(function () {
      jQuery(".advanced_options div").slideToggle("slow");
	  jQuery(".arrow-right").toggle();
	  jQuery(".arrow-down").toggle();
	  e.preventDefault();
    });
	
});