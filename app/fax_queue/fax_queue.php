<?php
/*
	FusionPBX
	Version: MPL 1.1

	The contents of this file are subject to the Mozilla Public License Version
	1.1 (the "License"); you may not use this file except in compliance with
	the License. You may obtain a copy of the License at
	http://www.mozilla.org/MPL/

	Software distributed under the License is distributed on an "AS IS" basis,
	WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License
	for the specific language governing rights and limitations under the
	License.

	The Original Code is FusionPBX

	The Initial Developer of the Original Code is
	Mark J Crane <markjcrane@fusionpbx.com>
	Portions created by the Initial Developer are Copyright (C) 2022
	the Initial Developer. All Rights Reserved.
*/

//includes
	require_once "root.php";
	require_once "resources/require.php";
	require_once "resources/check_auth.php";
	require_once "resources/paging.php";

//check permissions
	if (permission_exists('fax_queue_view')) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//get the http post data
	if (is_array($_POST['fax_queue'])) {
		$action = $_POST['action'];
		$search = $_POST['search'];
		$fax_queue = $_POST['fax_queue'];
	}

//process the http post data by action
	if ($action != '' && is_array($fax_queue) && @sizeof($fax_queue) != 0) {

		switch ($action) {
			case 'copy':
				if (permission_exists('fax_queue_add')) {
					$obj = new fax_queue;
					$obj->copy($fax_queue);
				}
				break;
			//case 'toggle':
			//	if (permission_exists('fax_queue_edit')) {
			//		$obj = new fax_queue;
			//		$obj->toggle($fax_queue);
			//	}
			//	break;
			case 'delete':
				if (permission_exists('fax_queue_delete')) {
					$obj = new fax_queue;
					$obj->delete($fax_queue);
				}
				break;
		}

		//redirect the user
		header('Location: fax_queue.php'.($search != '' ? '?search='.urlencode($search) : null));
		exit;
	}

//set the time zone
	if (isset($_SESSION['domain']['time_zone']['name'])) {
		$time_zone = $_SESSION['domain']['time_zone']['name'];
	}
	else {
		$time_zone = date_default_timezone_get();
	}

//get order and order by
	$order_by = $_GET["order_by"];
	$order = $_GET["order"];

//add the search
	if (isset($_GET["search"])) {
		$search = strtolower($_GET["search"]);
	}

//get the count
	$sql = "select count(fax_queue_uuid) ";
	$sql .= "from v_fax_queue ";
	if ($_GET['show'] == "all" && permission_exists('fax_queue_all')) {
		$sql .= "where true ";
	}
	else {
		$sql .= "where domain_uuid = :domain_uuid ";
		$parameters['domain_uuid'] = $domain_uuid;
	}
	if (isset($search)) {
		$sql .= "and (";
		$sql .= "	lower(hostname) like :search ";
		$sql .= "	or lower(fax_caller_id_name) like :search ";
		$sql .= "	or lower(fax_caller_id_number) like :search ";
		$sql .= "	or lower(fax_number) like :search ";
		$sql .= "	or lower(fax_email_address) like :search ";
		$sql .= "	or lower(fax_file) like :search ";
		$sql .= "	or lower(fax_status) like :search ";
		$sql .= "	or lower(fax_accountcode) like :search ";
		$sql .= ") ";
		$parameters['search'] = '%'.$search.'%';
	}
	$database = new database;
	$num_rows = $database->select($sql, $parameters, 'column');
	unset($sql, $parameters);

//prepare to page the results
	$rows_per_page = ($_SESSION['domain']['paging']['numeric'] != '') ? $_SESSION['domain']['paging']['numeric'] : 50;
	$param = $search ? "&search=".$search : null;
	$param = ($_GET['show'] == 'all' && permission_exists('fax_queue_all')) ? "&show=all" : null;
	$page = is_numeric($_GET['page']) ? $_GET['page'] : 0;
	list($paging_controls, $rows_per_page) = paging($num_rows, $param, $rows_per_page);
	list($paging_controls_mini, $rows_per_page) = paging($num_rows, $param, $rows_per_page, true);
	$offset = $rows_per_page * $page;

//get the list
	$sql = "select ";
	$sql .= "d.domain_name, ";
	$sql .= "q.fax_queue_uuid, ";
	$sql .= "q.fax_uuid, ";
	$sql .= "q.fax_date, ";
	$sql .= "to_char(timezone(:time_zone, q.fax_date), 'DD Mon YYYY') as fax_date_formatted, \n";
	$sql .= "to_char(timezone(:time_zone, q.fax_date), 'HH12:MI:SS am') as fax_time_formatted, \n";	
	$sql .= "q.hostname, ";
	$sql .= "q.fax_caller_id_name, ";
	$sql .= "q.fax_caller_id_number, ";
	$sql .= "q.fax_number, ";
	$sql .= "q.fax_prefix, ";
	$sql .= "q.fax_email_address, ";
	$sql .= "q.fax_file, ";
	$sql .= "q.fax_status, ";
	$sql .= "q.fax_retry_date, ";
	$sql .= "q.fax_retry_count, ";
	$sql .= "q.fax_accountcode, ";
	$sql .= "q.fax_command ";
	$sql .= "from v_fax_queue as q, v_domains as d ";
	if ($_GET['show'] == "all" && permission_exists('fax_queue_all')) {
		$sql .= "where true ";
	}
	else {
		$sql .= "where q.domain_uuid = :domain_uuid ";
		$parameters['domain_uuid'] = $domain_uuid;
	}
	$sql .= "and q.domain_uuid = d.domain_uuid ";
	if (isset($_GET["search"])) {
		$sql .= "and (";
		$sql .= "	lower(hostname) like :search ";
		$sql .= "	or lower(fax_caller_id_name) like :search ";
		$sql .= "	or lower(fax_caller_id_number) like :search ";
		$sql .= "	or lower(fax_number) like :search ";
		$sql .= "	or lower(fax_email_address) like :search ";
		$sql .= "	or lower(fax_file) like :search ";
		$sql .= "	or lower(fax_status) like :search ";
		$sql .= "	or lower(fax_accountcode) like :search ";
		$sql .= ") ";
		$parameters['search'] = '%'.$search.'%';
	}
	$sql .= order_by($order_by, $order, 'fax_date', 'desc');
	$sql .= limit_offset($rows_per_page, $offset);
	$parameters['time_zone'] = $time_zone;
	$database = new database;
	$fax_queue = $database->select($sql, $parameters, 'all');
	unset($sql, $parameters);

//create token
	$object = new token;
	$token = $object->create($_SERVER['PHP_SELF']);

//additional includes
	$document['title'] = $text['title-fax_queue'];
	require_once "resources/header.php";

//show the content
	echo "<div class='action_bar' id='action_bar'>\n";
	echo "	<div class='heading'><b>".$text['title-fax_queue']." (".$num_rows.")</b></div>\n";
	echo "	<div class='actions'>\n";
	if (permission_exists('fax_queue_add')) {
		echo button::create(['type'=>'button','label'=>$text['button-add'],'icon'=>$_SESSION['theme']['button_icon_add'],'id'=>'btn_add','name'=>'btn_add','link'=>'fax_queue_edit.php']);
	}
	if (permission_exists('fax_queue_add') && $fax_queue) {
		echo button::create(['type'=>'button','label'=>$text['button-copy'],'icon'=>$_SESSION['theme']['button_icon_copy'],'id'=>'btn_copy','name'=>'btn_copy','style'=>'display:none;','onclick'=>"modal_open('modal-copy','btn_copy');"]);
	}
	//if (permission_exists('fax_queue_edit') && $fax_queue) {
	//	echo button::create(['type'=>'button','label'=>$text['button-toggle'],'icon'=>$_SESSION['theme']['button_icon_toggle'],'id'=>'btn_toggle','name'=>'btn_toggle','style'=>'display:none;','onclick'=>"modal_open('modal-toggle','btn_toggle');"]);
	//}
	if (permission_exists('fax_queue_delete') && $fax_queue) {
		echo button::create(['type'=>'button','label'=>$text['button-delete'],'icon'=>$_SESSION['theme']['button_icon_delete'],'id'=>'btn_delete','name'=>'btn_delete','style'=>'display:none;','onclick'=>"modal_open('modal-delete','btn_delete');"]);
	}
	echo 		"<form id='form_search' class='inline' method='get'>\n";
	if (permission_exists('fax_queue_all')) {
		if ($_GET['show'] == 'all') {
			echo "		<input type='hidden' name='show' value='all'>\n";
		}
		else {
			echo button::create(['type'=>'button','label'=>$text['button-show_all'],'icon'=>$_SESSION['theme']['button_icon_all'],'link'=>'?show=all']);
		}
	}
	echo 		"<input type='text' class='txt list-search' name='search' id='search' value=\"".escape($search)."\" placeholder=\"".$text['label-search']."\" onkeydown='list_search_reset();'>";
	echo button::create(['label'=>$text['button-search'],'icon'=>$_SESSION['theme']['button_icon_search'],'type'=>'submit','id'=>'btn_search','style'=>($search != '' ? 'display: none;' : null)]);
	echo button::create(['label'=>$text['button-reset'],'icon'=>$_SESSION['theme']['button_icon_reset'],'type'=>'button','id'=>'btn_reset','link'=>'fax_queue.php','style'=>($search == '' ? 'display: none;' : null)]);
	if ($paging_controls_mini != '') {
		echo 	"<span style='margin-left: 15px;'>".$paging_controls_mini."</span>\n";
	}
	echo "		</form>\n";
	echo "	</div>\n";
	echo "	<div style='clear: both;'></div>\n";
	echo "</div>\n";

	if (permission_exists('fax_queue_add') && $fax_queue) {
		echo modal::create(['id'=>'modal-copy','type'=>'copy','actions'=>button::create(['type'=>'button','label'=>$text['button-continue'],'icon'=>'check','id'=>'btn_copy','style'=>'float: right; margin-left: 15px;','collapse'=>'never','onclick'=>"modal_close(); list_action_set('copy'); list_form_submit('form_list');"])]);
	}
	//if (permission_exists('fax_queue_edit') && $fax_queue) {
	//	echo modal::create(['id'=>'modal-toggle','type'=>'toggle','actions'=>button::create(['type'=>'button','label'=>$text['button-continue'],'icon'=>'check','id'=>'btn_toggle','style'=>'float: right; margin-left: 15px;','collapse'=>'never','onclick'=>"modal_close(); list_action_set('toggle'); list_form_submit('form_list');"])]);
	//}
	if (permission_exists('fax_queue_delete') && $fax_queue) {
		echo modal::create(['id'=>'modal-delete','type'=>'delete','actions'=>button::create(['type'=>'button','label'=>$text['button-continue'],'icon'=>'check','id'=>'btn_delete','style'=>'float: right; margin-left: 15px;','collapse'=>'never','onclick'=>"modal_close(); list_action_set('delete'); list_form_submit('form_list');"])]);
	}


	echo "<form id='form_list' method='post'>\n";
	echo "<input type='hidden' id='action' name='action' value=''>\n";
	echo "<input type='hidden' name='search' value=\"".escape($search)."\">\n";

	echo "<table class='list'>\n";
	echo "<tr class='list-header'>\n";
	if (permission_exists('fax_queue_add') || permission_exists('fax_queue_edit') || permission_exists('fax_queue_delete')) {
		echo "	<th class='checkbox'>\n";
		echo "		<input type='checkbox' id='checkbox_all' name='checkbox_all' onclick='list_all_toggle(); checkbox_on_change(this);' ".($fax_queue ?: "style='visibility: hidden;'").">\n";
		echo "	</th>\n";
	}
	if ($_GET['show'] == 'all' && permission_exists('fax_queue_all')) {
		echo th_order_by('domain_name', $text['label-domain'], $order_by, $order);
	}
	//echo th_order_by('fax_date', $text['label-fax_date'], $order_by, $order);
	echo "<th class='center shrink'>".$text['label-date']."</th>\n";
	echo "<th class='center shrink hide-md-dn'>".$text['label-time']."</th>\n";
	echo th_order_by('hostname', $text['label-hostname'], $order_by, $order);
	echo th_order_by('fax_caller_id_name', $text['label-fax_caller_id_name'], $order_by, $order);
	echo th_order_by('fax_caller_id_number', $text['label-fax_caller_id_number'], $order_by, $order);
	echo th_order_by('fax_number', $text['label-fax_number'], $order_by, $order);
	echo th_order_by('fax_email_address', $text['label-fax_email_address'], $order_by, $order);
	echo th_order_by('fax_file', $text['label-fax_file'], $order_by, $order);
	echo th_order_by('fax_status', $text['label-fax_status'], $order_by, $order);
	echo th_order_by('fax_retry_date', $text['label-fax_retry_date'], $order_by, $order);
	echo th_order_by('fax_retry_count', $text['label-fax_retry_count'], $order_by, $order);
	if (permission_exists('fax_queue_edit') && $_SESSION['theme']['list_row_edit_button']['boolean'] == 'true') {
		echo "	<td class='action-button'>&nbsp;</td>\n";
	}
	echo "</tr>\n";

	if (is_array($fax_queue) && @sizeof($fax_queue) != 0) {
		$x = 0;
		foreach ($fax_queue as $row) {
			if (permission_exists('fax_queue_edit')) {
				$list_row_url = "fax_queue_edit.php?id=".urlencode($row['fax_queue_uuid']);
			}
			echo "<tr class='list-row' href='".$list_row_url."'>\n";
			if (permission_exists('fax_queue_add') || permission_exists('fax_queue_edit') || permission_exists('fax_queue_delete')) {
				echo "	<td class='checkbox'>\n";
				echo "		<input type='checkbox' name='fax_queue[$x][checked]' id='checkbox_".$x."' value='true' onclick=\"checkbox_on_change(this); if (!this.checked) { document.getElementById('checkbox_all').checked = false; }\">\n";
				echo "		<input type='hidden' name='fax_queue[$x][fax_queue_uuid]' value='".escape($row['fax_queue_uuid'])."' />\n";
				echo "	</td>\n";
			}
			if ($_GET['show'] == 'all' && permission_exists('fax_queue_all')) {
				echo "	<td>".escape($row['domain_name'])."</td>\n";
			}
			//echo "	<td>".escape($row['fax_date'])."</td>\n";
			echo "	<td nowrap='nowrap'>".escape($row['fax_date_formatted'])."</td>\n";
			echo "	<td nowrap='nowrap'>".escape($row['fax_time_formatted'])."</td>\n";
			echo "	<td>".escape($row['hostname'])."</td>\n";
			echo "	<td>".escape($row['fax_caller_id_name'])."</td>\n";
			echo "	<td>".escape($row['fax_caller_id_number'])."</td>\n";
			echo "	<td>".escape($row['fax_number'])."</td>\n";
			echo "	<td>".escape($row['fax_email_address'])."</td>\n";
			echo "	<td>".escape($row['fax_file'])."</td>\n";
			echo "	<td>".escape($row['fax_status'])."</td>\n";
			echo "	<td>".escape($row['fax_retry_date'])."</td>\n";
			echo "	<td>".escape($row['fax_retry_count'])."</td>\n";
			if (permission_exists('fax_queue_edit') && $_SESSION['theme']['list_row_edit_button']['boolean'] == 'true') {
				echo "	<td class='action-button'>\n";
				echo button::create(['type'=>'button','title'=>$text['button-edit'],'icon'=>$_SESSION['theme']['button_icon_edit'],'link'=>$list_row_url]);
				echo "	</td>\n";
			}
			echo "</tr>\n";
			$x++;
		}
		unset($fax_queue);
	}

	echo "</table>\n";
	echo "<br />\n";
	echo "<div align='center'>".$paging_controls."</div>\n";
	echo "<input type='hidden' name='".$token['name']."' value='".$token['hash']."'>\n";
	echo "</form>\n";

//include the footer
	require_once "resources/footer.php";

?>
