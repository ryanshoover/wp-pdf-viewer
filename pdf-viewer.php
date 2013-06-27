<?php
/**
*   Plugin name: PDF Viewer
*   Plugin URI: http://ryanhoover.net
*   Description: A PDF viewer that renders the file natively in the browser. Kind of like your own WP Scribd.
*   Author: Ryan Hoover
*   Author URI: http://ryanhoover.net
*   Version: 1.00
**/

//for initialization of features and dashboard admin
class pdf_viewer{
	
	private $myBase; 
	
	public function __construct() {
		
		$this->myBase = plugins_url("", __FILE__);
		
		//add doc upload mgmt into media management screen
		add_filter('media_upload_tabs', array($this, 'add_media_menu'));
		add_action('media_upload_document', array($this, 'media_menu_handle'));
		add_action('wp_ajax_delete_post', array($this, 'delete_post') );
		//set up doc type and shortcode
		add_action( 'init', array($this, 'add_post_type') );
		//add_action( 'admin_menu', array($this, 'add_doc_management') );
		add_shortcode( 'pdf', array( &$this, 'display' ) );
		
		wp_enqueue_script("jquery");
	}
	
	//add document to media tab menu
	public function add_media_menu($tabs) {
	  $newtab = array('document' => __('Insert Document', 'document'));
	  return array_merge($tabs, $newtab);
	}
	
	//creates the iframe for media window
	public function media_menu_handle() {
	   return wp_iframe( array($this, 'media_html'));
	}
	
	//returns the shortcode to the tinymce box
	private function insert_doc_into_post($vars) {
		//strip blank values
		$vars['insert_existing']=0;
		$vars['insert_new']=0;
		$vars=array_filter($vars);
		
		$html = '[pdf ';
		foreach($vars as $key => $val) 
			$html .= " $key=" .(is_numeric($val) ? $val : "'".$val."'");	
		
		$html .= ']';
	  	$html = apply_filters('media_send_to_editor', $html, $send_id, $attachment);
		
		return media_send_to_editor($html);
	}
	
	//lays out the add document media html
	public function media_html() {
	  $type = 'document';
	 
	  wp_enqueue_media();
	  wp_enqueue_script('jquery');
	  wp_enqueue_script('media-upload');
	  wp_enqueue_script('thickbox');
	  wp_enqueue_style( 'thickbox');
	  wp_enqueue_script('pdfjs', $this->myBase .'/classes/pdf.js/pdf.js');
	  wp_enqueue_script('pdf-viewer-admin', $this->myBase .'/classes/script-admin.js');
	  wp_enqueue_style( 'pdf-viewer-admin', $this->myBase .'/classes/style-admin.css');
	  //wp_enqueue_script('jquery-validate', $this->myBase .'/classes/jquery.validate.min.js');
	  
	  media_upload_header();
	  
	  //get current user id
	  global $current_user;
	  get_currentuserinfo();
	  
	  //insert new doc into post
	  	$error = array();
		if( isset($_POST['insert_existing']) ) {
			//validate
			$post = $_POST;
			$post['linktext'] = sanitize_text_field($post['linktext']);
			if(!$post['post_id']) $error[] = "Oops. Looks like you forgot to pick a document to insert.";
			
			//submit
			if(empty($error))	
				$this->insert_doc_into_post($post);
		}
		if( isset($_POST['insert_new']) ) { 
			//validate
			$post = $_POST;
			$post['title'] = sanitize_text_field($post['title']);
			$post['linktext'] = sanitize_text_field($post['linktext']);
			if($post['title'] == '') $error[] = "Oops. You forgot to include a title for the document.";
			if($_FILES["uploadfile"]["error"]> 0) $error[] = "Hmm. Somthing went wrong. Will you try uploading the file again?";
			if($_FILES["uploadfile"]["type"] != "application/pdf") $error[] = "Oops. Looks like you didn't upload a PDF file. Sadly, that's all you can embed right now.";
			
			//submit
			if(empty($error)) {
		   		$post_id = $this->upload($post);
				if(is_numeric($post_id)) {
					$post['post_id'] = $post_id;
					$this->insert_doc_into_post($post);
				} else {
					$error[] = "Hmm. Somthing went wrong. Will you try uploading the file again?";
				}
			}
		}
	  
	  //Upload new doc 
	  ?>
	  <div class='document-nav-menu'>
        	<div id="upload" class="active">Upload Document</div>
            <div id="doc-lib" class="">Document Library</div>
      </div>
      <div style="clear:both;" id="upload_div"></div>
      <div id="upload-div" class="major-sec">
		  <?php
		  if(isset($_POST['insert_new']) && !empty($error)) {
			  $error_msg  = "<div class='error'><ul>";
			  foreach($error as $e) 
				  $error_msg .= "<li>$e</li>";
			  $error_msg .= "</ul></div>";
			  echo $error_msg;
		  }
		  ?>
      	<form id="upload-form" action="" method="post" enctype="multipart/form-data">
        	<input type="hidden" name="insert_new" value="1">
            <div id="media-items">
            <h3 class="media-title">Upload a new document</h3>

            <table class="describe">
            <tr>
            <th valign="top" scope="row" class="label">
            <label for="title">Document title</label><span class="alignright"><abbr title="required" class="required">*</abbr></span>
            </th>
            <td class="field">
            <input type="text" name="title" aria-required="true" placeholder="Used as the document's title" class="required" minlength="2" value="<?php echo $post['title']; ?>">
            </td></tr>
            
            <tr>
            <th valign="top" scope="row" class="label">
            <label for="uploadfile">Choose a PDF file</label><span class="alignright"><abbr title="required" class="required">*</abbr></span>
            </th>
            <td class="field">
            <input type="file" name="uploadfile" id="uploadfile" size="40" aria-required="true" class="required" accept="application/pdf"> The file <em>must</em> be a PDF!
            </td></tr>
            
            <tr class="extra-space">
            <th valign="top" scope="row" class="label">
            <label for="lightbox">Lightbox</label>
            </th>
            <td class="field">
            <input type="checkbox" name="lightbox" class="show_other" <?php if($post['lightbox']) echo "checked"; ?>> Insert a link to a popup window view of the document?
            </td></tr>
			
            <tr class="initial-hide" id="linktext-row">
            <th valign="top" scope="row" class="label">
            <label for="linktext">Link</label>
            </th>
            <td class="field">
            <input type="text" size="30" name="linktext" placeholder="What should the link to the lightbox say?" value="<?php echo $post['linktext']; ?>">
            </td></tr>
			
            <tr class="extra-space">
            <th valign="top" scope="row" class="label">
            <label for="booklet">Booklet</label>
            </th>
            <td class="field">
            <input type="checkbox" name="booklet" class="show_other" <?php if($post['booklet']) echo "checked"; ?>> Display as a booklet with side-by-side pages?
            </td></tr>
			
            <tr class="initial-hide" id="coverpage-row">
            <th valign="top" scope="row" class="label">
            <label for="coverpage">Cover page</label>
            </th>
            <td class="field">
            <input type="checkbox" name="coverpage" <?php if($post['coverpage']) echo "checked"; ?>> Does the PDF have a cover page?
            </td></tr>
            
            <tr>
            <th valign="top" scope="row" class="label">
            </th>
            <td class="field">
            <input type="submit" class="button" value="Insert new document"></input>
            </td></tr>
            
            </table>
            
            </div>
        </form>
        <p id="f1_upload_process">Loading...<br/><img src="<?php echo $this->myBase; ?>/images/loader.gif" /></p>
		<p id="result"></p>
      </div>
	  
	  <?php  
	  //Choose existing  doc
	  ?> 
      <div id="doc-lib-div" class="major-sec">
		  <?php
		  if(isset($_POST['insert_existing']) && !empty($error)) {
			  $error_msg  = "<div class='error'><ul>";
			  foreach($error as $e) 
				  $error_msg .= "<li>$e</li>";
			  $error_msg .= "</ul></div>";
			  echo $error_msg;
		  }
		  ?>
      <h3 class="media-title">Pick a document to insert</h3>
      <div id="doc-lib-container">
      <?php
	  echo $this->list_posts();
	  ?>
      </div>
        <form id="doc-lib-form" action="" method="post" style="clear:both;">
        	<input type="hidden" name="insert_existing" value="1">
        	<input type="hidden" id="existing_post_id" name="post_id" value="0">
            <input type="hidden" id="existing_title" name="title" value="none">
           	<table class="describe">
               <tr class="extra-space">
               <th valign="top" scope="row" class="label">
               <label for="lightbox">Lightbox</label>
               </th>
               <td class="field">
               <input type="checkbox" name="lightbox" class="show_other" <?php if($post['lightbox']) echo "checked"; ?>> Insert a link to a popup window view of the document?
               </td></tr>
			
               <tr class="initial-hide" id="linktext-row">
               <th valign="top" scope="row" class="label">
               <label for="linktext">Link</label>
               </th>
               <td class="field">
               <input type="text" size="30" name="linktext" placeholder="What should the link to the lightbox say?" value="<?php echo $post['linktext']; ?>">
               </td></tr>
			
               <tr class="extra-space">
               <th valign="top" scope="row" class="label">
               <label for="booklet">Booklet</label>
               </th>
               <td class="field">
               <input type="checkbox" name="booklet" class="show_other" <?php if($post['booklet']) echo "checked"; ?>> Display as a booklet with side-by-side pages?
               </td></tr>
			
               <tr class="initial-hide" id="coverpage-row">
               <th valign="top" scope="row" class="label">
               <label for="coverpage">Cover page</label>
               </th>
               <td class="field">
               <input type="checkbox" name="coverpage" <?php if($post['coverpage']) echo "checked"; ?>> Does the PDF have a cover page?
               </td></tr>
            
            
		    <tr>
            <th scope="row" class="label">
            </th>
            <td class="field">
              <input type="submit" class="button" value="Insert document"></input>
            </td>
            </tr>
          </table>
        </form>
	  </div>
	  <?php
	}
	
	//creates post type for docs
	public function add_post_type() {
		$options = array(
			'label' 		=> 'Documents',
			'description'	=> 'Inserts PDF directly into the webpage',
		);
		register_post_type('seu_document', $options);	
	}
	
	public function delete_post() {
		global $wpdb;
		$postid = $_POST['postid'];
		
		$result = wp_delete_post( $postid, 'true' );
		if ($result) echo $this->list_posts();
		die();
		return false;
	}
	
	public function list_posts() {
	  $args = array (
	  	'post_type' => 'seu_document',
		'numberposts' => -1
	  );
	  $docs = get_posts($args);
	  $return = '';
	  foreach($docs as $doc) {
		  $return .= "<div class='doc-div' id='".$doc->ID."'><img src='$this->myBase/images/x-circle.png' class='delete' id='delete_".$doc->ID."' title='Delete $doc->post_name'><canvas id='pdf-canvas-".$doc->ID."'></canvas><p>" .$doc->post_name."</p></div>";
	  }
	  $return .= "
	  <script>
	  jQuery(document).ready(function(e) {
	  PDFJS.workerSrc='".$this->myBase ."/classes/pdf.js/pdf.js';\n";
	  foreach($docs as $doc) {
		$return .= "loadPageOne('$doc->guid', 'pdf-canvas-$doc->ID');\n";  
	  }
	  $return .= "});\n</script>\n";
	  /*
	  foreach($docs as $doc) {
		$return .= "<div class='doc-div' id='".$doc->ID."'><img src='$this->myBase/images/x-circle.png' class='delete' id='delete_".$doc->ID."'><img src='".$doc->guid ."/page-000.png'><p>" .$doc->post_name."</p></div>";  
	  }	
	  */
	  return $return;
	}
	
	//creates mgmt page
	//**not yet implemented
	public function add_doc_management() {
		add_media_page( 'Manage Documents', 'Documents', 'upload_files', 'managedocs', array($this, 'doc_management_form') );
	}
	
	//sets up WP dashboard mgmt form
	//**not yet implemented
	public function doc_management_form() {
		
		wp_enqueue_media(); //setup all necessary upload media scripts
		?>
		<form enctype="multipart/form-data" id="new_doc" action="" method="post"  class="media-upload-form type-form validate">
		<div id="media-items">
		<h3>Insert a new document</h3>
		
		<table class="describe">
		<tr>
		<th valign="top" scope="row" class="label">
		<label for="title">Title</label><span class="alignright"><abbr title="required" class="required">*</abbr></span>
		</th>
		<td class="field">
		<input type="text" name="title" aria-required="true" placeholder="Used as the document's title"></input>
		</td></tr>
        
        <tr>
		<th valign="top" scope="row" class="label">
		<label for="uploadfile">Choose a PDF</label><span class="alignright"><abbr title="required" class="required">*</abbr></span>
		</th>
		<td class="field">
		<input type="file" name="uploadfile" size="40" aria-required="true">
		</td></tr>
		
		<tr>
		<th valign="top" scope="row" class="label">
		</th>
		<td class="field">
		<input type="submit" class="button" value="Insert new document"></input>
		</td></tr></table>
		
		</div>
		</form>
        
		<?php
		//handles submit function of form
		if (isset($_POST['title']))
		   	$this->upload($_POST);
		
	}
	
	//handles the upload and processing of document
	public function upload($input) {
		$title = urlencode( basename( $input['title']) );
		$wp_upload_dir = wp_upload_dir();
		$filename = urlencode($_FILES['uploadfile']['name']);
		
		if( move_uploaded_file($_FILES['uploadfile']['tmp_name'], $wp_upload_dir['path']."/$filename" ) ) {
			 $post_id = wp_insert_post( array(
				  'post_name'		=> $title,
				  'post_type'		=> 'seu_document',
				  'post_content'	=> 'no',
				  'post_status'		=> 'publish',
				  'post_mime_type'	=> 'application/pdf',
				  'guid'			=> $wp_upload_dir['url']."/$filename"
			 ));
		} else{
			 $error = "There was an error uploading the file. Please try again.\n";
			 return $error;
		}
		return $post_id;
	}
	
	//add shortcode
	//handles the shortcode with pdf.js
	public function display($atts) {
		extract( shortcode_atts( array(
			'post_id' => 1,
			'linktext' => 'PDF Document',
			'width' => '100%',
			'height' => '650px',
			'lightbox' => false,
			'iframe' => false,
			'booklet' => true,
			'coverpage' => false,
			'savable' => true,
		), $atts ) );
		
		$post = get_post($post_id);
		$post_path = $post->guid;
		
		if($lightbox) {
			//insert link that opens pdf in a lightbox
			add_thickbox();
			wp_enqueue_script('pdf-viewer', $this->myBase .'/classes/script.js');
	  		//wp_enqueue_style( 'pdf-viewer', $this->myBase .'/classes/style.css');
			$content  = "<div id='pdf-viewer-$post_id' class='pdf-viewer-thickbox' style='display:none;'>";
			$content .= '<iframe class="pdf" webkitallowfullscreen mozallowfullscreen allowfullscreen frameborder="no" width="'.$width.'" height="'.$height.'" src="';
  			$content .=  $this->myBase.'/classes/pdf.js/web/viewer.html?file=' . $post_path;
			$content .= '">' . $post_path . '</iframe>';
			$content .= "</div>";
			$content .= "<a href='#TB_inline?width=800&height=600&inlineId=pdf-viewer-$post_id' class='thickbox' title='$linktext'>$linktext</a>";
			return $content;
			
		} else {
			//insert pdf directly into post
			$content = '<iframe class="pdf" webkitallowfullscreen mozallowfullscreen allowfullscreen frameborder="no" width="'.$width.'" height="'.$height.'" src="';
  			$content .=  $this->myBase.'/classes/pdf.js/web/viewer.html?file=' . $post_path;
			$content .= '">' . $post_path . '</iframe>';
			return $content;	
		}
	}
	
	public function load_booklet_js() {
       // -- Booklet --
        wp_enqueue_script('jqueryui', 'https://ajax.googleapis.com/ajax/libs/jqueryui/1.8.8/jquery-ui.min.js');
        wp_enqueue_script('jqueryeasing', $this->myBase .'/booklet/jquery.easing.1.3.js');
        wp_enqueue_script('booklet', $this->myBase ."/booklet/jquery.booklet.latest.js");
        wp_enqueue_style('bookletcss', $this->myBase ."/booklet/jquery.booklet.latest.css");
	}
	
	public function load_fancybox() {
		wp_enqueue_style('fancyboxcss', $this->myBase ."/classes/fancybox/jquery.fancybox.css");
		wp_enqueue_script('fancyboxjs', $this->myBase ."/classes/fancybox/jquery.fancybox.pack.js");
		//wp_enqueue_script('fancyboxfullscreenjs', $myBase ."/fancybox/jquery.fullscreen.js");
	}

}


new pdf_viewer();
?>
