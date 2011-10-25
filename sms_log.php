<?php

	$ci = & get_instance();
	
	$ci->load->helper('format_helper');
	$ci->load->helper('date_helper');
	require_once(APPPATH . 'libraries/twilio.php');
	
	$ci->twilio = new TwilioRestClient($ci->twilio_sid,$ci->twilio_token,$ci->twilio_endpoint);
	
	if(isset($_GET['p']))
	{
		$last = $_GET['p'];
	}
	else {
		$last = null;
	}
	if ($last != null)
	{
		$page = $last;
	}
	else
	{
		$page = "0";
	}
	
	$log_url = "Accounts/{$this->twilio_sid}/SMS/Messages";
	$log_method = "GET";
	$log_params = array('page' => $page, 'num' => '25');
	$log = $ci->twilio->request($log_url, $log_method, $log_params);
	
	$log_xml = $log->ResponseXml;

	if(!empty($_POST))
	{
		PluginData::set('timezone',$_POST['timezones']);
	}
	
	
	//set initial "is it dst" to false
	$in_dst=false;
	
	//set the year of this campaign for use in calculation
	$yr = date("Y", time());
	
	//figure out when the march start of dst is in the sending year
	$mar = strtotime("second sunday", strtotime("march 1 $yr")); // sunday >= 3/7
	
	//figure out when the november end of dst is in the sending year
	$nov = strtotime("first sunday", strtotime("nov 1 $yr")); // sunday after 11/1
	
	//determine if it's dst or not
	$isDST = time() > $mar && time() < $nov;
		
	$timezone = PluginData::get("timezone",'UM8');
	
?>

<div class="vbx-plugin">
  <script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.6.4/jquery.min.js"></script>
  <script type="text/javascript" src="https://raw.github.com/DataTables/DataTables/master/media/js/jquery.dataTables.js"></script>
  <script type="text/javascript">
    function new_item(sms) {
      $('#table_body tr:first').before('<tr>'+call['From']+'</tr>');
    }
    function formatPhone(phonenum) {
      var regexObj = /^(?:\+?1[-. ]?)?(?:\(?([0-9]{3})\)?[-. ]?)?([0-9]{3})[-. ]?([0-9]{4})$/;
      var parts = phonenum.match(regexObj);
      var phone = "";
      if (parts[1]) { phone += "(" + parts[1] + ") "; }
      phone += parts[2] + "-" + parts[3];
      return phone;
    }
    $(document).ready(function() {
      $('#log').dataTable();
    });
  </script>
	
  <h3>Complete SMS Log</h3>
  <div style="width: 100%; height: auto; overflow: hidden;">
  <br />
    <form method="POST">
      <label style="display: inline; float: left; margin-right: 20px; font-size: 16px; font-weight: bold; position: relative; top: 6px;">Set Your Timezone</label><?=timezone_menu(PluginData::get("timezone",'UM8'))?>
      <button class="submit-button ui-state-focus" style="margin-left: 4px; display: inline; float: left;" type="submit"><span>Set Timezone</span></button>		
    </form>
  </div>
<?  var_dump($log_xml); ?>
  <table id="log">
   <thead>
    <tr>
     <th>Number</th>
     <th>Start Time</th>
     <th>Duration</th>
     <th>Called</th>
     <th>Status</th>
    </tr>
   </thead>
   <tbody id="table_body">
      <?php foreach($log_xml->Calls->Call as $call): ?>
			<tr id="<?=$call->Sid?>">
				<td><?php echo format_phone($call->From);?></td>
				<td><?php echo date('D, M j Y g:i a', gmt_to_local(strtotime($call->StartTime),$timezone,$isDST));?></td>
				<td><?php echo $call->Duration;?> sec</td>
				<td><?php echo format_phone($call->To);?></td>
				<td><?php echo $call->Status;?></td>
			</tr>
		<?php endforeach ?>
		</tbody>
	</table>

<div class="log_pagination" style="float: right;">

	<?php if($last != "0" AND $last != ""):?>
	<a href="<?php echo base_url();?>index.php/p/call_log/?p=<?php echo ($last - 1);?>">Previous</a>
	<?php endif?>
	

	<?php if($log_xml->Calls['numpages']-1 > $last):?>
	<a href="<?php echo base_url();?>index.php/p/call_log/?p=<?php echo ($last + 1);?>">Next</a>
	<?php endif?>
	
</div>
<br />
</div>