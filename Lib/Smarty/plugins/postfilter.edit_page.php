<?php
/**
 * Smarty plugin
 * @package Smarty
 * @subpackage plugins
 */

/**
 * Smarty edit page postfilter plugin
 *
 * File:     postfilter.edit_page.php<br>
 * Type:     postfilter<br>
 * Name:     edit_page<br>
 * Date:     Nov 24th, 2008<br>
 * Purpose:  If the user is logged in "Admin" mode, then display a block at the bottom of the page that gives them 
 *			 the ability to edit the text in the page   
 *           
 *           
 * @author   Charlie Russ <cruss at reputationdefender dot com>
 * @version  1.0
 * @param string
 * @param Smarty
 */

function smarty_postfilter_edit_page($tpl_source, &$smarty)
{
	$top_source = "<div class=\"editheader\"><span>
		<a href=\"?edit_page=false\" id=\"edit_page\">STOP EDITING </a>						
		
        </span>
    </div>
<div class=\"editheader_bottom\"><span></span></div>"; 

	if ( strpos( $smarty->_current_file, 'wrapper.tpl' ) === FALSE && strpos( $smarty->_current_file, 'badges_bottom.tpl' ) === FALSE && strpos( $smarty->_current_file, 'module_message_center.tpl' ) === FALSE  && strpos( $smarty->_current_file, 'resourceSideBar.tpl' ) === FALSE) {
	$bottom_source = ""; 
	if ( $_GET['edit_page'] == "true"){
	   	$bottom_source .= "<script type=\"text/javascript\">
				var editing  = false;
				window.addEvent('domready', function() {
					//disable all the links in the page so that we dont get redirected when trying to edit text
					var allAnchors = $$('a');
					//remove the href from the anchors, except for the very last one, which is the link to leave \"Edit mode\"
					for(var i = 0; i < allAnchors.length; i++){
						var str = String(allAnchors[i]);
						if (! str.test('edit_page') )
							allAnchors[i].removeProperty('href');
					}
					$(document).addEvent('click', function(e){
						if (editing) return;
						if (!document.getElementById || !document.createElement) return;
						
						var element = e.target;
						
						if (element.id != \"edit_page\"){
							//if no id for this current element, traverse through its parentNodes until we find one with an id
							if (! element.id){
								var el = element.parentNode;
								while(!el.id){
									el = el.parentNode;
								}
					
							}else{
								var el = element;
							}
							var x = element.innerHTML;
							var y = document.createElement('TEXTAREA');
							//create the 'save' button
							var butt = document.createElement('BUTTON');
							var buttext = document.createTextNode('Save');
							butt.appendChild(buttext);
				
							butt.onclick = function(){
								var old_text = x;
								var new_text = y.value;
								saveEdit(el.id, old_text, new_text);

							};
							
							//create the 'cancel' button
							var cancel_butt = document.createElement('BUTTON');
							var buttext = document.createTextNode('Cancel');
							cancel_butt.appendChild(buttext);
				
							cancel_butt.onclick = function(){
								location.reload();
							};
							
							var z = element.parentNode;
							z.insertBefore(y,element);
							z.insertBefore(butt,element);
							z.insertBefore(cancel_butt,element);
							z.removeChild(element);
							y.value = x;
							y.focus();
							editing = true;
						}
					});
			
				}); // end domready						
				function saveEdit(nearest_id, old_text, new_text) {
					//ajax call to update the template
					var temp_url = '/secure/ajax_update_text.php?id=' + nearest_id +'&old_text=' + old_text + '&new_text=' + new_text;
					req1 = new Request.HTML({
								        method: 'get',
								        url: temp_url,
								        data: { 'do' : '1' },
								        onComplete: function(response) {
											if (req1.response['text'] == \"success\"){
												location.reload();
											}else if (req1.response['text'] == null){
												alert('error, unable to edit text within forms');
											}else{
												alert(req1.response['text']);
												location.reload();

											}
										}

					}); 				
					req1.send();
				}
				function undo_changes() {
					//ajax call to update the template
					var temp_url = '/secure/ajax_update_text.php?undo_changes=true';
					req2 = new Request.HTML({
								        method: 'get',
								        url: temp_url,
								        data: { 'do' : '1' },
								        onComplete: function(response) {
											if (req2.response['text'] == \"success\"){
												location.reload();
											}else if (req2.response['text'] == null){
												alert('error, unable to edit text within forms');
											}else{
												alert(req2.response['text']);
												location.reload();

											}
										}

					}); 				
					req2.send();
				}
				
			</script>
			        <div class=\"editheader\"><span>
						<a href=\"?edit_page=false\" id=\"edit_page\">STOP EDITING </a>						
						<br />
						
				        </span>
				    </div>
				<div class=\"editheader_bottom\"><span></span></div>";
			
	}else{
		
		$top_source = "<div class=\"editheader\"><span>
			<a href=\"?edit_page=true\" id=\"edit_page\">Edit Page </a>						

	        </span>
	    </div>
	<div class=\"editheader_bottom\"><span></span></div>";
	
	
		$bottom_source = "<div class=\"editheader\"><span>
			<a href=\"?edit_page=true\">Edit Page </a>						
			<br />
			
	        </span>
	    </div>
	<div class=\"editheader_bottom\"><span></span></div>";
	}
	return $top_source.$tpl_source.$bottom_source;
}
	return $tpl_source;
}
?>