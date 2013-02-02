<?php
require_once 'includes/scgaUi.php';
$scga = new scgaUi(); 




?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" >
<head>
<title>SCGA</title>
<script src="//ajax.googleapis.com/ajax/libs/jquery/1.8.0/jquery.min.js"></script>
<script src="//ajax.googleapis.com/ajax/libs/jqueryui/1.8.23/jquery-ui.min.js"></script>
<script src="includes/jquery.cookie.js" type="text/javascript"></script>
<script src="includes/js/scga.js" type="text/javascript"></script>
<style type="text/css">
@import "includes/css/base.css";
@import "includes/css/flick/jquery-ui.css";
@import "includes/css/flick/jquery-ui-1.8.16.custom.css";
.wrapper .scroll {
	border-bottom: 1px dotted #DCDCDC;
	height: 50%;
	max-height: 550px;
	overflow: auto;
	width: 70%;
	margin: 1em 0; 
}
</style>
<script type="text/javascript">
$(document).ready(function() {
	
	checkboxes = $('input[type=checkbox]');
	$('.toggleAll').click(function() { 
		$(checkboxes).each(function() {
			$(this).attr('checked', true); 
		});
	});
	$('.toggleNone').click(function() { 
		$(checkboxes).each(function() {
			$(this).removeAttr('checked');
		});
	});
	$('.toggle').click(function() {
		$(checkboxes).each(function() {
			var c = $(this).is(':checked'); 
			if(c) $(this).removeAttr('checked');
			else $(this).attr('checked', true);
		});
	});
	
	$('input[value="Add Accounts"]').click(function() {
		msg = 'Ready to add these accounts? They can be deleted later.'; 
		scga.confirmation(msg, $(this).parent('form')); return false; 
	});

});
</script>
</head>
<body>
<div class="wrapper">
		<div><em><a href="./">SCGA</a></em>
				<h2>Scrape Facebook Auth Accounts</h2>
				<div>Below is a list your of accounts. Checked accounts alraed exist. If your Facebook password changes or you "remove" this app, you will have to revisit this page.</div>
				<div>&bull;<a class="toggle" href="#">Toggle Checkboxes</a> &bull;<a class="toggleAll" href="#">Check All</a> &bull;<a class="toggleNone" href="#">Check None</a></div>
				<form method="post">
						<input type="hidden" name="action" value="add-accounts" />
						<div class="scroll">
								<?php 
$arr = isset($scga->user['accounts']['data']) ? $scga->user['accounts']['data'] : array();
foreach($arr as $account) {
	$known = false;
	if(($known = $scga->getAccount($account['id'])) && isset($known['id'])) { 
		$known = '<tt>**Already Saved</tt>';
	}
	print '<br /><input type="checkbox" name="account-id[]" value="'. $account['id'] .'" '. ($known ? 'checked="checked" ' : '') .'/><strong>'. $account['name'] .'</strong> '. $account['id'] .' ('. $account['category'] .')'; 
}
?>
						</div>
						<input type="submit" value="Add Accounts" />
				</form>
				<h2>Debugging</h2>
				<div>
						<h3>Messages</h3>
						<pre class="messages"><?php print $scga->messages ? implode("\n\n", $scga->messages) : 'None'; ?></pre>
						<h3>Errors</h3>
						<pre class="errors"><?php print $scga->errors ? implode("\n\n", $scga->errors) : 'None'; ?></pre>
						<h3>Log</h3>
						<pre class="log"><?php print $scga->log ? implode("\n\n", $scga->log) : 'None'; ?></pre>
				</div>
		</div>
</div>
</body>
</html><?php //print '<pre>'. print_r($scga, true) . print_r($_REQUEST, true);