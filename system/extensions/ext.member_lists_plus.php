<?php
/* ===========================================================================
ext.member_lists_plus.php ---------------------------
Allows you to add custom member fields to member lists in the CP.
            
INFO ---------------------------
Developed by: Nathan Pitman, ninefour.co.uk/labs
Created:   Jul 25 2010

Related Thread: 
=============================================================================== */
if ( ! defined('EXT')) exit('Invalid file request');


class Member_lists_plus
{
   
	var $settings = array();
	var $name = 'Member Lists Plus';
	var $version = '1.0';
	var $description = 'Allows you to add custom member fields to member lists in the CP';
	var $settings_exist = 'y';
	var $docs_url = 'http://ninefour.co.uk/labs';

	// --------------------------------
	//  PHP 4 Constructor
	// --------------------------------
	function Member_lists_plus($settings='')
	{
		$this->__construct($settings);
	}
   
	// --------------------------------
	//  PHP 5 Constructor
	// --------------------------------
	function __construct($settings='')
	{
		$this->settings = $settings;
	}
	
	// --------------------------------
	//  Change Settings
	// --------------------------------  
	function settings()
	{
		$settings = array();
		$settings['additional_field_titles'] = '';
		$settings['additional_field_names'] = '';
	
		return $settings;
	}

	// --------------------------------
	//  Activate Extension
	// --------------------------------
	function activate_extension()
	{
		global $DB, $PREFS;
		
		$default_settings = serialize(
			array(
				
			  'additional_field_titles' => "Location",
			  'additional_field_names' => "location"
			)
		);
		
		$sql[] = $DB->insert_string( 'exp_extensions', 
			array('extension_id' 	=> '',
				'class'			=> get_class($this),
				'method'		=> "member_plus_show_full_control_panel_end",
				'hook'			=> "show_full_control_panel_end",
				'settings'		=> $default_settings,
				'priority'		=> 10,
				'version'		=> $this->version,
				'enabled'		=> "y"
			)
		);

		// run all sql queries
		foreach ($sql as $query)
		{
			$DB->query($query);
		}
		return TRUE;
	}
	
	// --------------------------------
	//  Disable Extension
	// -------------------------------- 
	function disable_extension()
	{
		global $DB;
		$DB->query("DELETE FROM exp_extensions WHERE class = '" . get_class($this) . "'");
	}
	
	// --------------------------------
	//  Update Extension
	// --------------------------------  
	function update_extension($current='')
	{
		global $DB;
		
		if ($current == '' OR $current == $this->version)
		{
			return FALSE;
		}
		
		$DB->query("UPDATE exp_extensions
		            SET version = '".$DB->escape_str($this->version)."'
		            WHERE class = '".get_class($this)."'");
	}
   
   	// ---------------------------------
	//  Rewrite the member data tables
	// ---------------------------------
	
	function member_plus_show_full_control_panel_end( $out )
	{
		global $DB, $DSP, $EXT, $IN, $LOC;
		  
		if($EXT->last_call !== FALSE)
		{
			$out = $EXT->last_call;
		}
		
		if (!empty($this->settings['additional_field_names'])) {
			
			$field_titles = explode("|", $this->settings['additional_field_titles']);
			$field_names = explode("|", $this->settings['additional_field_names']);
			
			$page = "";   	
         	$row_count = 1;
         	
         	// if its a CP request in the Members module and on the Validate Pending Members page
			if (REQ == 'CP' && $IN->GBL('M') == "members" && $IN->GBL('P') == "member_validation") {

				$page = "member_validation";

				$select = "SELECT DISTINCT m.member_id, m.group_id, m.username, m.screen_name, m.email, m.join_date, m.last_visit";
				foreach($field_names AS $name) {
					$select .= ", ".$name;
				}
				$from = "FROM exp_members m, exp_member_data d, exp_member_groups g";
				$where = "WHERE (m.member_id=d.member_id) AND (m.group_id=4)";
				
				$sql = $select." ".$from." ".$where;
				$query = $DB->query($sql);
				
				if ($query->num_rows>=1) {
						
					$replacement = '';
					$replacement .= $DSP->table_open(array('class' => 'tableBorder', 'width' => '100%'));
					
					$top[]	= array(
						'text'	=> '&nbsp;',
						'class'	=> 'tableHeadingAlt'
					);
					$top[]	= array(
						'text'	=> $DSP->input_checkbox('toggleflag', '', '', "onclick=\"toggle(this);\""),
						'class'	=> 'tableHeadingAlt'
					);
					$top[]	= array(
						'text'	=> 'Username',
						'class'	=> 'tableHeadingAlt'
					);
					$top[]	= array(
						'text'	=> 'Screen Name',
						'class'	=> 'tableHeadingAlt'
					);				
					
					foreach($field_titles AS $title) {
						$top[]	= array(
							'text'	=> $title,
							'class'	=> 'tableHeadingAlt'
						);				
					}
		
					$top[]	= array(
						'text'	=> 'Email Address',
						'class'	=> 'tableHeadingAlt'
					);
					$top[]	= array(
						'text'	=> 'Join Date',
						'class'	=> 'tableHeadingAlt'
					);
		
					$replacement .= $DSP->table_row( $top );
									
					$i = 0;
					
					foreach($query->result AS $row) {
					
						unset($rows);
						$rows[]	= '<span class="default">'.$row_count.'</span>';
						$rows[] = $DSP->input_checkbox('toggle[]', $row['member_id'], '', "id='delete_box_".$row['member_id']."'");
						$rows[] = $DSP->anchor( BASE.AMP.'S=0'.AMP.'C=myaccount'.AMP.'id='.$row['member_id'], "<b>".$row['username']."</b>" );
						$rows[] = '<b>'.$row['screen_name'].'</b>';
		
						foreach($field_names AS $name) {
							$string = "\$row['".$name."'];";
							$rows[] = eval("return $string");				
						}
		
						$rows[] = $DSP->anchor('mailto:'.$row['email'], $row['email'] );
						$rows[] = '<nobr>'.$LOC->set_human_time( $row['join_date'] ).'</nobr>';
		
			         	$replacement .= $DSP->table_qrow( ($i++ % 2) ? 'tableCellOne' : 'tableCellTwo', $rows);
						$row_count++;
						
					}
				
				} else {
				
					return $out;
				
				}
			
			// if its a CP request in the Members module and on the View Members page
			} elseif (REQ == 'CP' && $IN->GBL('M') == "members" && $IN->GBL('P') == "view_members" || $IN->GBL('P') == "mbr_delete" || $IN->GBL('P') == "register_member") {
			
				$page = "view_members";
			
				$select = "SELECT DISTINCT m.member_id, m.group_id, m.username, m.screen_name, m.email, m.join_date, m.last_visit, g.group_title";
				foreach($field_names AS $name) {
					$select .= ", ".$name;
				}
				$from = "FROM exp_members m, exp_member_data d, exp_member_groups g";
				$where1 = "WHERE (m.member_id=d.member_id) AND (g.group_id=m.group_id)";
				$where2 = "";
				$order = " ORDER BY member_id DESC";
				$limit_offset = " LIMIT 50";
				
				// Offset for pagination
				if (isset($_GET['rownum'])) {
					$limit_offset = " LIMIT 50 OFFSET ".$_GET['rownum'];
				}
				
				// Filter by member group
				if (isset($_GET['group_id'])) {
					$where2 = " AND (m.group_id=".$_GET['group_id'].")";
				}
			
	         	if ($_POST) {
	         	
	         		// Member group filter
		         	if (!empty($_POST['group_id'])) {
						$where2 = " AND (m.group_id=".$_POST['group_id'].")";
					}
					
					// Order by filter
					if (!empty($_POST['order'])) {					
						switch ($_POST['order']) {
							case "asc":
								$order = " ORDER BY member_id ASC";
								break;
							case "desc":
								$order = " ORDER BY member_id DESC";
								break;
							case "username_asc":
								$order = " ORDER BY username ASC";
								break;
							case "username_desc":
								$order = " ORDER BY username DESC";
								break;
							case "screen_name_asc":
								$order = " ORDER BY screen_name ASC";
								break;
							case "screen_name_desc":
								$order = " ORDER BY screen_name DESC";
								break;
							case "email_asc":
								$order = " ORDER BY email ASC";
								break;
							case "email_desc":
								$order = " ORDER BY email DESC";
								break;
						}
					}	
				
				}
	         	
	         	$sql = $select." ".$from." ".$where1.$where2.$order.$limit_offset;
				$query = $DB->query($sql);
				
				if ($query->num_rows>=1) {
						
					$replacement = '';
					$replacement .= $DSP->table_open(array('class' => 'tableBorder', 'width' => '100%'));
					
					
					$top[]	= array(
						'text'	=> '#',
						'class'	=> 'tableHeadingAlt'
					);
					$top[]	= array(
						'text'	=> 'Username',
						'class'	=> 'tableHeadingAlt'
					);
					$top[]	= array(
						'text'	=> 'Screen Name',
						'class'	=> 'tableHeadingAlt'
					);
					$top[]	= array(
						'text'	=> 'Email Address',
						'class'	=> 'tableHeadingAlt'
					);
					$top[]	= array(
						'text'	=> 'Join Date',
						'class'	=> 'tableHeadingAlt'
					);
					$top[]	= array(
						'text'	=> 'Last Visit',
						'class'	=> 'tableHeadingAlt'
					);
					$top[]	= array(
						'text'	=> 'Member Group',
						'class'	=> 'tableHeadingAlt'
					);			
					
					foreach($field_titles AS $title) {
						$top[]	= array(
							'text'	=> $title,
							'class'	=> 'tableHeadingAlt'
						);				
					}
	
					$top[]	= array(
						'text'	=> $DSP->input_checkbox('toggleflag', '', '', "onclick=\"toggle(this);\""),
						'class'	=> 'tableHeadingAlt'
					);
		
					$replacement .= $DSP->table_row( $top );
					
					$i = 0;
					
					foreach($query->result AS $row) {
					
						unset($rows);
						$rows[]	= '<span class="default">'.$row['member_id'].'</span>';
						$rows[] = $DSP->anchor(BASE.AMP.'S=0'.AMP.'C=myaccount'.AMP.'id='.$row['member_id'], "<b>".$row['username']."</b>");
						$rows[] = '<b>'.$row['screen_name'].'</b>';
						$rows[] = $DSP->anchor('mailto:'.$row['email'], $row['email'] );
						$rows[] = '<nobr>'.$LOC->decode_date("%Y-%m-%d",$row['join_date'],TRUE).'</nobr>';
											
						if ($row['last_visit']!=0) {
							$rows[] = '<nobr>'.$LOC->set_human_time( $row['last_visit'] ).'</nobr>';
						} else {
							$rows[] = ' -- ';
						}
						$rows[] = $DSP->anchor(BASE.AMP.'S=0'.AMP.'C=admin'.AMP.'M=members'.AMP.'P=view_members'.AMP.'group_id='.$row['group_id'], $row['group_title']);
	
						foreach($field_names AS $name) {
							$string = "\$row['".$name."'];";
							$rows[] = eval("return $string");				
						}
		
						$rows[] = $DSP->input_checkbox('toggle[]', $row['member_id'], '', "id='delete_box_".$row['member_id']."'");
	
		
			         	$replacement .= $DSP->table_qrow( ($i++ % 2) ? 'tableCellOne' : 'tableCellTwo', $rows);
						$row_count++;
						
					}
				
				} else {
				
					return $out;
				
				}

			
			} else {
	      
	      		return $out;
	      	
	      	}      
         
         	$replacement .=	$DSP->table_c();
         
			$dom = new DOMDocument;
			@$dom->loadHTML($out); // suppress errors
			$xPath = new DOMXPath($dom);
			$nodes = $xPath->query('//*[@class="tableBorder"]');
			$frag = $dom->createElement("to-be-replaced");   
			$nodes->item(0)->parentNode->replaceChild($frag, $nodes->item(0));
			$out = $dom->saveHTML();
         	
         	$pattern = "<to-be-replaced></to-be-replaced>";

			$out = str_replace($pattern, $replacement, $out);
			
      }
      
      return $out;
      
   }
   
   
}
// END class

/* End of file ext.member_lists_plus.php */
/* Location: ./system/extensions/ext.member_lists_plus.php */