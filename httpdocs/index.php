<?php
require_once 'includes/scgaUi.php';
$scga = new scgaUi(); 
header("Cache-Control: no-cache, must-revalidate");
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");

print html();
function html() { 
	global $scga;
	ob_start();
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
</style>
<script type="text/javascript">
$(document).ready(function() { 
	// use j-ui to roll tabs
	$('#tabs').tabs({ fx:{opacity: 'toggle'}, cookie:{expires: 30} }).show();
	$('#tabs > div').height( $('html').height()-100 ).css('overflow', 'auto');
	// add a date picker 
	$('.date-picker').datepicker({ maxDate:'-2D', showWeek:true });
	// add confirmations 
	scga.dialogConfirm();
	// highlight recent report 
	$('option[value*="<?php print $scga->reports[0]?>"]').attr('selected', 'selected'); $('input[value="Load File"]').click();
	// show a progress and run in background 
	$('input[value="Batch Run Batch"]').click(function(){
		$.get('./index.php?action=batch-run-all'); 
		$('a[href*=reports]').click();
		scga.showProgress();
		return false; 
	});

});

</script>
</head>
<body>
<div class="wrapper">
				<div>
								<em><a href="./">SCGA</a></em>
								<div id="tabs" style="display:none">
												<ul id="tab-links">
																<li><a href="#accounts">Accounts</a></li>
																<li><a href="#jobs">Jobs</a></li>
																<li><a href="#silos">Silos/ Clients</a></li>
																<li><a href="#reports">Reports</a></li>
																<li><a href="#debugging">Debugging</a></li>
												</ul>
												<div id="accounts">
																<h2>Account Managment</h2>
																<div>
																				<h3>Add/refresh your accounts</h3>
																				<a href="./scrape_accounts.php">Add/refresh your accounts</a>
																</div>
																<div>
																				<h3>Add Account Secret</h3>
																				<form method="post">
																								<input type="hidden" name="action" value="add-account-secret" />
																								<select name="account-id">
																												<option>**Facebook Account</option>
																												<option></option>
																												<?php foreach($scga->accounts as $account): ?>
																												<option value="<?php print $account['application']?>"><?php print $account['name']?> - <?php print $account['application']?> - <?php print $account['category']?></option>
																												<?php endforeach?>
																								</select>
																								<input type="text" name="account-secret" value="" />
																								<input type="submit" value="Add Secret" />
																								<div>
																												The account secret is used to impersonate the account in code or fql.query calls. It is online at <a href="http://facebook.com/developers">facebook.com/developers</a>.
																								</div>
																				</form>
																</div>
																<div>
																				<h3>Delete Account</h3>
																				<form method="post">
																								<input type="hidden" name="action" value="delete-account" />
																								<select name="account-id[]" multiple="multiple" size="8">
																												<option>**Facebook Account</option>
																												<option></option>
																												<?php foreach($scga->accounts as $account): ?>
																												<option value="<?php print $account['application']?>"><?php print $account['name']?> - <?php print $account['application']?> - <?php print $account['category']?></option>
																												<?php endforeach?>
																								</select>
																								<input type="submit" value="Delete Account" />
																								<div>
																												After an account is deleted jobs for it will not run anymore. Old job results will be kept.
																								</div>
																				</form>
																</div>
												</div>
												<div id="jobs">
																<h2>Job Display</h2>
																<div>
																				<form method="post">
																								<input type="hidden" name="action" value="batch-new-jobs" />
																								<input type="submit" value="Batch New Jobs" />
																								Add new jobs to batched queries (Normally each midnight)
																				</form>
																</div>
																<div>
																				<form method="post">
																								<input type="hidden" name="action" value="batch-all-jobs" />
																								<input type="submit" value="Batch All Jobs" />
																								Add all jobs to batched queries (Normally each midnight, will create duplicates)
																				</form>
																</div>
																<div>
																				<form method="post">
																								<input type="hidden" name="action" value="batch-run-all" />
																								<input type="submit" value="Batch Run Batch" />
																								Run batched queries (Normally each 15 minutes)
																				</form>
																</div>
																<div>
																				<h3>Add New Job</h3>
																				<form method="post">
																								<input type="hidden" name="action" value="add-job" />
																								<select name="account-id">
																												<option>**Facebook Account</option>
																												<option></option>
																												<option>****Pages</option>
																												<?php foreach($scga->accounts as $account): ?>
																												<?php if(strpos($account['category'], 'Application') === false): ?>
																												<option value="<?php print $account['application']?>"><?php print $account['name']?> (<?php print $account['application']?>) <?php print $account['category']?></option>
																												<?php endif; endforeach;?>
																												<option></option>
																												<option>****Other/ Applications</option>
																												<?php foreach($scga->accounts as $account): ?>
																												<?php if(strpos($account['category'], 'Application') !== false): ?>
																												<option value="<?php print $account['application']?>"><?php print $account['name']?> (<?php print $account['application']?>) <?php print $account['category']?></option>
																												<?php endif; endforeach;?>
																								</select>
																								Start date:
																								<input type="text" class="date-picker" name="start-date" />
																								End date:
																								<input type="text" class="date-picker" name="end-date" />
																								<input type="submit" value="Add job" />
																								<div>
																												Both dates optional. If <tt>start_date</tt> is in the future, it won't run until that date. If the <tt>end_date</tt> is in the future, we only collect until today. <strong>Reccuring jobs </strong>have no <tt>start_date</tt> and no <tt>end_date</tt>.
																								</div>
																				</form>
																</div>
																<div>
																				<h3>Delete Job</h3>
																				<form method="post">
																								<input type="hidden" name="action" value="delete-job" />
																								<select name="job-id">
																												<option>**Job Id</option>
																												<option></option>
																												<?php foreach($scga->jobs->jobs as $job): ?>
																												<option value="<?php print $job['id']?>"><?php print $job['id']?> - <?php print $job['name']?></option>
																												<?php endforeach?>
																								</select>
																								<input type="submit" value="Delete Job" />
																				</form>
																</div>
																<div>
																				<h3>Recurring, Nightly Jobs</h3>
																				<table>
																								<tr>
																												<th>Id</th>
																												<th>Application</th>
																												<th>Start Date</th>
																												<th>End Date</th>
																												<th>Batched</th>
																												<th>Deleted</th>
																								</tr>
																								<?php foreach($scga->jobs->recurringJobs as $job): ?>
																								<tr>
																												<td class="cell id"><?php print $job['id']?></td>
																												<td class="cell name"><?php print $job['name'] .' ('. $job['application'] .')'?></td>
																												<td class="cell start-date"><?php print $job['start_date']?></td>
																												<td class="cell end-date"><?php print $job['end_date']?></td>
																												<td class="cell is-batched"><?php print $job['is_batched']?></td>
																												<td class="cell is-deleted"><?php print $job['is_deleted']?></td>
																								</tr>
																								<?php endforeach?>
																				</table>
																</div>
																<div>
																				<h3>One-Time, History Jobs.</h3>
																				<table>
																								<tr>
																												<th>Id</th>
																												<th>Application</th>
																												<th>Start Date</th>
																												<th>End Date</th>
																												<th>Batched</th>
																												<th>Deleted</th>
																								</tr>
																								<?php foreach($scga->jobs->oneTimeJobs as $job): ?>
																								<tr>
																												<td class="cell id"><?php print $job['id']?></td>
																												<td class="cell name"><?php print $job['name'] .' ('. $job['application'] .')'?></td>
																												<td class="cell start-date"><?php print $job['start_date']?></td>
																												<td class="cell end-date"><?php print $job['end_date']?></td>
																												<td class="cell is-batched"><?php print $job['is_batched']?></td>
																												<td class="cell is-deleted"><?php print $job['is_deleted']?></td>
																								</tr>
																								<?php endforeach?>
																				</table>
																</div>
												</div>
												<div id="silos">
																<h2>Silo Managment</h2>
																<div>
																				<h3>Create New Silo</h3>
																				<form method="post">
																								<input type="hidden" name="action" value="add-silo" />
																								<input type="text" name="silo-name" value="" />
																								<input type="submit" value="Add Silo" />
																								<div>
																												Add container name for siloing application results.
																								</div>
																				</form>
																</div>
																<div>
																				<h3>Delete Silo Container</h3>
																				<form method="post">
																								<input type="hidden" name="action" value="delete-silo" />
																								<select name="silo-id">
																												<option>**SCGA Silo</option>
																												<option></option>
																												<?php foreach($scga->silos->silos as $silo): ?>
																												<option value="<?php print $silo['id']?>"><?php print $silo['silo_machine_name']?> - <?php print $silo['silo_name']?> (<?php print $silo['id']?>)</option>
																												<?php endforeach?>
																								</select>
																								<input type="submit" value="Delete Silo" />
																								<div>
																												Delete silo used for client results. Old results are kept. Generic container will be used for new queries.
																								</div>
																				</form>
																</div>
																<div>
																				<h3>Add Account to Silo</h3>
																				<form method="post">
																								<input type="hidden" name="action" value="add-account-silo" />
																								<select name="account-id[]" multiple="multiple" size="8">
																												<option>**Facebook Account</option>
																												<option></option>
																												<?php foreach($scga->accounts as $account): ?>
																												<option value="<?php print $account['application']?>"><?php print $account['name']?> (<?php print $account['application']?>) <?php print $account['category']?></option>
																												<?php endforeach;?>
																								</select>
																								<select name="silo-id">
																												<option>**SCGA Silo</option>
																												<option></option>
																												<?php foreach($scga->silos->silos as $silo): ?>
																												<option value="<?php print $silo['id']?>"><?php print $silo['silo_machine_name']?> - <?php print $silo['silo_name']?> (<?php print $silo['id']?>)</option>
																												<?php endforeach?>
																								</select>
																								<input type="submit" value="Add Accounts" />
																				</form>
																</div>
																<div>
																				<h3>Remove Application from Silo</h3>
																				<form method="post">
																								<input type="hidden" name="action" value="remove-account-silo" />
																								<select name="account-id[]" multiple="multiple" size="8">
																												<option>**Facebook Account</option>
																												<option></option>
																												<?php foreach($scga->accounts as $account): 
																												$silo = $scga->silos->getAccountSilo($account['application']);
																												if($silo != $scga->silos->defaultSilo): 
																												?>
																												<option value="<?php print $account['application']?>"><?php print $silo?> - <?php print $account['name']?> (<?php print $account['application']?>)</option>
																												<?php endif; endforeach;?>
																								</select>
																								<input type="submit" value="Remove Siloing" />
																				</form>
																</div>
												</div>
												<div id="reports">
																<h2>Progress</h2>
																Run Maxium: <tt class="countRunMaxium"><?php print $scga->batch->countRunMaxium?></tt> Count Batched: <tt class="countBatched"><?php print $scga->batch->countBatched?></tt> Count Remaining: <tt class="countRemaining"><?php print $scga->batch->countRemaining?></tt>
																<div id="progressbar">
																</div>
																<h2>Reports</h2>
																<div>
																				<form method="post">
																								<input type="hidden" name="action" value="reports-generate" />
																								<input type="submit" value="Generate Report" />
																								Create new batch report <br />
																				</form>
																</div>
																<div>
																				<form method="post">
																								<select name="report">
																												<option>**Report File</option>
																												<option></option>
																												<?php foreach($scga->reports as $report): ?>
																												<option value="reports/<?php print $report?>"><?php print $report?></option>
																												<?php endforeach;?>
																								</select>
																								<input type="submit" value="Load File" />
																				</form>
																				<pre></pre>
																</div>
												</div>
												<div id="debugging">
																<h2>Debugging</h2>
																<div>
																				<h3>Messages</h3>
																				<pre class="messages"><?php print $scga->messages ? implode("\n\n", $scga->messages) : 'None'; ?></pre>
																				<h3>Errors</h3>
																				<pre class="errors"><?php print $scga->errors ? implode("\n\n", $scga->errors) : 'None'; ?></pre>
																</div>
																<div>
																				<h3>Data Dump</h3>
																				<pre><?php print print_r($scga, true)?></pre>
																</div>
												</div>
								</div>
				</div>
</div>
</body>
</html>
<? return ob_get_clean(); } ?>