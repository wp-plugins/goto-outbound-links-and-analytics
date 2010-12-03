<?php
/*
Plugin Name: Goto Outbound Links and Analytics
Plugin URI: http://wordpress.org/extend/plugins/goto-outbound-links-and-analytics/
Description: Create and analyze your outbound "Goto" links. Designed for affiliates.
Version: 1.12
Author: Emil Thidell
Author URI: http://www.emilthidell.se
License: GPL2
*/


add_action('admin_menu', 'links_admin');

function links_activate(){
    global $wpdb;
	$table_name = "stats_outboundstats";
	$table_name2 = "stats_linkstats";
	$table_name3 = "stats_settingsstats";

	$sql = "CREATE TABLE " .$table_name ." (`ID` BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY, `URL` TEXT NOT NULL, `GOTO` TEXT NOT NULL, `PARENT` BIGINT NOT NULL, `NAME` TEXT NOT NULL) ENGINE = MyISAM;";
    createTable($table_name, $sql, 'no'); 

	$sql = "CREATE TABLE " .$table_name2 ." (`ID` BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY, `DATUM` DATETIME NOT NULL, `OWNER` TEXT NOT NULL, `SLUG` TEXT NOT NULL, `PARENT` BIGINT NOT NULL) ENGINE = MyISAM;";
    createTable($table_name2, $sql, 'no'); 

	$sql = "CREATE TABLE " .$table_name3 ." (`ID` BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY, `GOTO` TEXT NOT NULL, `DBNAME` TEXT NOT NULL, `DBPASS` TEXT NOT NULL, `DBUSER` TEXT NOT NULL, `DBHOST` TEXT NOT NULL) ENGINE = MyISAM;";
    createTable($table_name3, $sql, 'setup'); 
	
	
}

function createTable($theTable, $sql, $opt){
    global $wpdb;//call $wpdb to the give us the access to the DB
    if($wpdb->get_var("show tables like '". $theTable . "'") != $theTable) {
        $wpdb->query($sql);
		echo '<br />Database created: <font color="green">' .$theTable ."</font>";
		if($opt === 'setup'){
			$sql = "INSERT INTO " .$theTable ." (`GOTO`, `DBNAME`, `DBPASS`, `DBUSER`, `DBHOST`) VALUES('','','','','');";
			$wpdb->query($sql);
			echo '<br />Preparing setup... <font color="green"></font>';
			echo "<h2>Database created!</h2><form action='options-general.php?page=goto-settings' method='post'><input type='submit' value='Click here to install plugin'></form>";
			die();
		}
    }
}
 

function links_admin(){
    add_options_page('Analyze Goto links', 'Analyze Goto links', 'manage_options', 'goto-settings', 'options_page');
}
function install(){
	if(!$_POST[dbhost]){
		echo "<table><form method='post' action='options-general.php?page=goto-settings'>";
		echo "<tr><td>Database Host: </td><td><input id='dbhost' name='dbhost' type='text'></td></tr>";
		echo "<tr><td>Database Name: </td><td><input id='dbname' name='dbname' type='text'></td></tr>";
		echo "<tr><td>Database Username: </td><td><input id='dbuser' name='dbuser' type='text'></td></tr>";
		echo "<tr><td>Database Password: </td><td><input id='dbpass' name='dbpass' type='password'></td></tr>";
		echo "<tr><td>Goto Directory: </td><td><input id='goto' name='goto' type='text'></td></tr>";
		echo "<tr><td>http://www.yoursite.com/<strong>goto</strong>/link </td><td><input type='submit' value='Install!'></td></tr>";
		echo "</table></form>";	
	}else{
		$dbhost = $_POST[dbhost];
		$dbuser = $_POST[dbuser];
		$dbpass = $_POST[dbpass];
		$dbname = $_POST[dbname];
		$goto = $_POST[goto];
		echo "<br />Plugin installed in directory: <strong>/" .$goto . "./</strong> Click done to continue!";
		global $wpdb;
		$sql = "UPDATE stats_settingsstats SET DBHOST='" .$dbhost ."', DBUSER='" .$dbuser ."', DBPASS='" .$dbpass ."', DBNAME='" .$dbname ."', GOTO='" .$goto ."');";
	    echo "<form method='post' action='options-general.php?page=goto-settings'><input type='submit' value='Done'></form>";
		
		$wpdb->update( 'stats_settingsstats', array( 'DBHOST' => $dbhost, 'DBUSER' => $dbuser, 'DBPASS' => $dbpass, 'DBNAME' => $dbname, 'GOTO' => $goto ), array( 'ID' => 1 ) );
	}
}
function options_page(){
	links_activate();
	global $wpdb;
	$table_name3 = "stats_settingsstats";
	$newsetups=$wpdb->get_results("SELECT * FROM stats_settingsstats");
	foreach ($newsetups as $setup){
		$dbhost = $setup->DBHOST;
		$dbuser = $setup->DBUSER;
		$dbpass = $setup->DBPASS;
		$dbname = $setup->DBNAME;
		$dbgoto = $setup->GOTO;
	}
	$filename2 = str_replace("wp-admin", "", getcwd());
	if($dbhost === ""){
		echo "<h2>Install plugin</h2>";
		install();
	}else{
	if($_POST[fixit] === 'true'){
		echo 'Bulding goto directory with correct config.php...<br />'; 
	 
		$thisdir = $filename2;
		//echo $thisdir . $dbgoto;
		if(mkdir($thisdir .$dbgoto  , 0777)) 
		{ 
		   echo "Directory has been created successfully..."; 
		} 
		else 
		{ 
		   echo "Failed to create directory..."; 
		}
		
		$myFile = $thisdir .$dbgoto ."/config.php";
		$fh = fopen($myFile, 'w') or die("can't open file");
		
		$line[0] = "<?php \n";
		$line[1] = ":dollar:dynstring = '" .$dbgoto ."'; \n";
		$line[2] = ":dollar:dbhost = '" .$dbhost ."'; \n";
		$line[3] = ":dollar:dbuser = '" .$dbuser ."'; \n";
		$line[4] = ":dollar:dbname = '" .$dbname ."'; \n";
		$line[5] = ":dollar:dbpass = '" .$dbpass ."'; \n";
		$line[6] = "?>";

		$superline =$line[0] . $line[1] . $line[2] . $line[3] . $line[4]. $line[5] . $line[6];
		$superline = str_replace(":dollar:", "$", "$superline");
		fwrite($fh, $superline);
		
		fclose($fh);
		echo "<br />Config.php done...";

		$myFile = $thisdir .$dbgoto ."/.htaccess";
		$fh = fopen($myFile, 'w') or die("can't open file");
		
		$line[1] = "Options +FollowSymLinks\n";
		$line[2] = "RewriteEngine on\n";
		$line[3] = "RewriteRule ^/(.*)/ index.php?id=$1\n";
		$line[4] = "RewriteRule ^(.*) index.php?id=$1\n";

		$superline = $line[1] . $line[2] . $line[3] . $line[4];
		fwrite($fh, $superline);
		
		fclose($fh);
		echo "<br />.htaccess done...";

		$file = $thisdir . "/wp-content/plugins/goto-outbound-links-and-analytics/data.php";
		$newfile = $myFile = $thisdir .$dbgoto ."/index.php";

		if (!copy($file, $newfile)) {
    	echo "failed to copy $file...\n";
		}else{
		echo "<h2><font color='green'>Install complete!</font></h2>";
		$complete = "true";
		}


		
		}else{
	$filename = $filename2 .$dbgoto  ."/config.php"; 

	if (file_exists($filename)) { 
	    
	} else { 
		
	   echo "The config file does not exist! Click here to fix this problem... <form method='post' action='options-general.php?page=goto-settings'><input type='hidden' id='fixit' name='fixit' value='true'><input id='fix' name='fix' type='submit' value='Fix now!'> "; 
	}
	}
	if($_GET['edit']){

		if($_POST['type'] === "goto"){
			if($_POST['save'] === "Save"){
				
				$wpdb->update( 'stats_outboundstats', array( 'GOTO' => $_POST['editgoto'], 'URL' => $_POST['editurl']), array( 'ID' => $_POST['gotoid'] ) );
				echo "<font color='green'>Save complete...</font>";
			}
			if($_POST['delete'] === "X"){
				
					$sql = "DELETE FROM stats_outboundstats WHERE ID=" .$_POST['gotoid'] ."";
					$wpdb->query($sql);
					

					
				echo "<font color='green'>Post deleted...</font>";
			}
		}
		if($_POST['type'] === "name"){
			if($_POST['save'] === "Save name"){
				$wpdb->update( 'stats_outboundstats', array( 'NAME' => $_POST['editname']), array( 'ID' => $_GET['edit'] ) );
				$wpdb->update( 'stats_outboundstats', array( 'NAME' => $_POST['editname']), array( 'PARENT' => $_GET['edit'] ) );
				echo "<font color='green'>Name changed...</font>";
			}
			if($_POST['delete'] === "X"){
				$sql = "DELETE FROM stats_outboundstats WHERE ID=" .$_GET['edit'] ."";
				$wpdb->query($sql);
				
				$sql = "DELETE FROM stats_outboundstats WHERE PARENT=" .$_GET['edit'] ."";
				$wpdb->query($sql);
				
				echo "<font color='green'>Name and posts deleted...</font><br /><h2><a href='options-general.php?page=goto-settings'>Click here to go back</a></h2>";
			}
		}

		$editid = $_GET['edit'];
		$editlink=$wpdb->get_results("SELECT * FROM stats_outboundstats WHERE ID=" .$editid ."");
		foreach ($editlink as $editsetup){
			$edit_id = $editsetup->ID;
			$edit_name = $editsetup->NAME;
			$edit_goto = $editsetup->GOTO;
			$edit_url = $editsetup->URL;
		}
		
		
		
		if(!$edit_name){
			
			}else{
			echo "<h2>Edit - " .$edit_name ."</h2>";
			echo "<form method='post' action='options-general.php?page=goto-settings&edit=" .$edit_id ."'>";
			echo "<table><tr><td>Name:</td><td><input type='hidden' id='type' name='type' value='name'><input type='text' name='editname' id='editname' value='" .$edit_name ."'></td><td><input type='submit' name='save' id='save' value='Save name'><input type='submit' name='delete' id='delete' value='X'><font color='red' size='2px'>(Removes all goto links!)</font></td></tr>";
			echo "</form>";
	
			echo "<form method='post' action='options-general.php?page=goto-settings&edit=" .$edit_id ."'>";
			echo "<tr><td>Goto:</td><td><input type='hidden' id='gotoid' name='gotoid' value='" .$edit_id ."'><input type='hidden' id='type' name='type' value='goto'><input type='text' name='editgoto' id='editgoto' value='" .$edit_goto ."'></td><td>URL:<input id='editurl' size='80' name='editurl' value='" .$edit_url ."'></td><td><input type='submit' name='save' id='save' value='Save'></td><td>Can't delete main goto</td></tr>";
			echo "</form>";
	
			$editgoto=$wpdb->get_results("SELECT * FROM stats_outboundstats WHERE PARENT=" .$editid ."");
			foreach ($editgoto as $gotogo){
				echo "<form method='post' action='options-general.php?page=goto-settings&edit=" .$edit_id ."'>";
				echo "<tr><td>Goto:</td><td><input type='hidden' id='gotoid' name='gotoid' value='" .$gotogo->ID ."'><input type='hidden' id='type' name='type' value='goto'><input type='text' name='editgoto' id='editgoto' value='" .$gotogo->GOTO ."'></td><td>URL:<input size='80' id='editurl' name='editurl' value='" .$gotogo->URL ."'></td><td><input type='submit' name='save' id='save' value='Save'></td><td><input type='submit' name='delete' id='delete' value='X'></td></tr>";
				echo "</form>";
			}
			
			echo "</table>";
		}
	}else{
	$startdate = $_POST['startdate'];
	$enddate = $_POST['enddate'];
    if(!$startdate){
	$startdate = date('Y/m/d');
	}
	if(!$enddate){
	$enddate = date('Y/m/d');
	}
	$goto = $_POST['goto'];
	$add = $_POST['add'];
	$url = $_POST['url'];
	$name = $_POST['name'];
	$parent = $_POST['parent'];
	if(!$add){
			}else{
			if(!$url){
			}else{
				if(!$goto){
					echo '<font color="red">Error:</font> Invalid input (<font color="red">Goto</font>)';
				}else{
					if($name === ""){
						$success = "true";
						echo "<font color='red'>You need a name for the link</font>";
					}else{
						$success = "true";
						addlink($url,$goto,$name,$parent);
					}
				}
			}
			if(!$goto){
			}else{
				if(!$url){
					echo 'Invalid input (<font color="red">URL</font>)';
				}else{
					if(!$success == "true"){
						if($name === ""){
							echo "Error: You need a name for the link";
						}else{
							addlink($url,$goto,$name,$parent);
						}
					}
				}
			}
			if(!$url && !$goto){
				if($_PFOST['date'] === "Search"){
				}else{
					if($url === "" && $goto === "" && $name === ""){
						
						}else{
						echo '<font color="red">Error:</font> Invalid input (<font color="red">URL & Goto</font>)';
					}
				}
			}
	
		}

	echo "<div>";
    echo "<h2>Add tracking link</h2>";
    echo '<form action="options-general.php?page=goto-settings" method="post">';
    echo '<table><tr><td>URL:</td><td><input name="url" id="url" type="text" col="40"></td></tr>';
	echo '<input type="hidden" id="add" name="add" value="true"><tr><td>Goto:</td><td><input name="goto" id="goto" type="text"></td></tr><tr><td>Name:</td><td><input name="name" id="name" type="text"></td></tr><tr><td>Select parent:</td><td><input type="radio" name="parent" checked value="0" /> No parent</td></tr>';
    echo '<tr><td><input name="Submit" type="submit" value="Add link" /></td><td></td></tr></table><hr /></div>';
	echo "<strong></strong><br /><input name='startdate' name='startdate' value='" .$startdate ."' type='textbox'> - <input name='enddate' name='enddate' value='" .$enddate ."' type='textbox'><input type='submit' id='date' name='date' value='Search'>";
	
	
	
	
		
	

	showlist($startdate,$enddate);
	}
}
}
function addlink($url, $goto, $name,$parent){
	global $wpdb;
	$table_name = "stats_outboundstats";
	if($parent != 0){
		
		$visits=$wpdb->get_results("SELECT * FROM stats_outboundstats WHERE ID = " .$parent ."");
		foreach ($visits as $visit){
		
		$name = $visit->NAME;
		}
		
	}else{
		echo "teststestets<br />";
		$visits=$wpdb->get_results("SELECT * FROM stats_outboundstats WHERE NAME = '" .$name ."'");
		foreach ($visits as $visit){
			$parent = $visit->PARENT;
			
		}
		
	}

	$sql = "INSERT INTO " .$table_name ." (`URL`, `GOTO`, `PARENT`, `NAME`) VALUES('" .$url ."','" .$goto ."','" .$parent ."','" .$name ."');";
    
	$wpdb->query($sql);
	echo '<font color="green"><strong>Link added!</strong></font>';
}

function showlist($startdate1,$enddate1){  
  	global $wpdb;
	include("pChart/pData.class");
  	include("pChart/pChart.class");
	$mypath = str_replace("wp-admin", "", getcwd());
	$mypath = $mypath . "/wp-content/plugins/goto-outbound-links-and-analytics/";
	
	$monthdate1 = substr($enddate1, -2);
	$currentmonth = substr($enddate1, -5, 2);
	$monthdate1 = date("Y")."/" .$currentmonth ."/" . "31";
	$monthdate2 = date("Y")."/" .$currentmonth ."/" ."01";
	
	for ($i = 1; $i <= 31; $i++) {
    	$total_day[$i] = 0;
	}

	
	$totalcounter = "nej";
	$totalvisits=$wpdb->get_results("SELECT * FROM stats_linkstats WHERE DATUM between '".$monthdate2 ."' AND '" .$monthdate1 ."' ORDER BY DATUM");
	foreach ($totalvisits as $totalvisit){
		$calcday = substr($totalvisit->DATUM, -11, 2);
		if ($calcday == "01"){
			$calcday = 1;
		}
		if ($calcday == "02"){
			$calcday = 2;
		}
		if ($calcday == "03"){
			$calcday = 3;
		}
		if ($calcday == "04"){
			$calcday = 4;
		}
		if ($calcday == "05"){
			$calcday = 5;
		}
		if ($calcday == "06"){
			$calcday = 6;
		}
		if ($calcday == "07"){
			$calcday = 7;
		}
		if ($calcday == "08"){
			$calcday = 8;
		}
		if ($calcday == "09"){
			$calcday = 9;
		}
		$startcounter++;
		if($totalcounter === "nej"){
			$totalcounter = $calcday;
		}else{
			if($calcday === $totalcounter){
				
			}else{
				$total_day[$totalcounter] = $startcounter;
				$startcounter = 0;
			}
		$totalcounter = $calcday;
		}	
	}
	$total_day[$totalcounter] = $startcounter-1;
	
	
	echo "<table><tr>";	
	echo "<td></td>";
	echo "<td>";
// Dataset definition 
  $DataSet = new pData;

	

	
	$DataSet->AddPoint($total_day[1],"Serie1");
	foreach ($total_day as $i => $value) {
		$DataSet->AddPoint($total_day[$i],"Serie1");
		if($total_day[$i] > $totaltop){
			$totaltop = $total_day[$i];
		}
	}
	$totaltop = $totaltop*1.4;
  
  $DataSet->AddAllSeries();
  $DataSet->SetAbsciseLabelSerie();
  $DataSet->SetSerieName("Total clicks","Serie1");

  // Initialise the graph
  $Test = new pChart(700,230);
  $Test->setFixedScale(0,$totaltop);
  $Test->setFontProperties($mypath."fonts/tahoma.ttf",8);
  $Test->setGraphArea(50,30,585,200);
  $Test->drawFilledRoundedRectangle(7,7,693,223,5,240,240,240);
  $Test->drawRoundedRectangle(5,5,695,225,5,230,230,230);
  $Test->drawGraphArea(255,255,255,TRUE);
  $Test->drawScale($DataSet->GetData(),$DataSet->GetDataDescription(),SCALE_NORMAL,150,150,150,TRUE,0,2);   
  $Test->drawGrid(4,TRUE,230,230,230,50);

  // Draw the 0 line
  $Test->setFontProperties($mypath."fonts/tahoma.ttf",6);
  $Test->drawTreshold(0,143,55,72,TRUE,TRUE);

  // Draw the cubic curve graph
  $Test->drawCubicCurve($DataSet->GetData(),$DataSet->GetDataDescription());

  // Finish the graph
  $Test->setFontProperties($mypath ."fonts/tahoma.ttf",8);
  $Test->drawLegend(600,30,$DataSet->GetDataDescription(),255,255,255);
  $Test->setFontProperties($mypath."fonts/tahoma.ttf",10);
  $Test->drawTitle(50,22,"Selected month total clicks",50,50,50,585);
  $Test->Render("total_month.png");
	echo "<img src='total_month.png'>";
	echo "</td>";
	echo "</tr></table>";
	$norecords = "true";
	
	$table_name = "stats_outboundstats";
	$visits=$wpdb->get_results("SELECT * FROM " .$table_name . " GROUP BY NAME ORDER BY NAME");
	
	echo '<br />';
	echo "<table>";
	foreach ($visits as $visit){
		echo "<tr><td style='vertical-align:top; border-bottom: #ccc 2px solid;'>";
	    $table_name = "stats_linkstats";
		$num = 0;
		echo "<input type='radio' name='parent' value='" .$visit->ID ."' />";
		echo ': ';
	    $startdate = $startdate1;
		$enddate = $enddate1;
		
		$numcounts = 0;
		$numvisits=$wpdb->get_results("SELECT * FROM " .$table_name . " WHERE OWNER = " .$visit->ID ." AND DATUM between '".$startdate ."' AND '" .$enddate ."' ORDER BY SLUG");
		foreach ($numvisits as $numvisit){
		$numcounts++;
		$norecords = "false";
		}
		echo "<strong><a href='options-general.php?page=goto-settings&edit=" .$visit->ID ."'>";
		echo $visit->NAME;
		echo "</a></strong>";
		echo " Clicks:<strong> " .$numcounts . "</strong><br />";
		$arrnames[] = $visit->NAME; 
		echo " <font size='1px' color='red'>";
		echo $visit->GOTO;
		
		
		$visitsslugs=$wpdb->get_results("SELECT * FROM stats_outboundstats WHERE PARENT = '" .$visit->ID ."'");
		$slugcounter = 0;
		foreach ($visitsslugs as $visitsslug){
			if($slugcounter >= 3){
				$slugcounter = 0;
				echo "<br />";
			}else{
				$slugcounter++;
			}
			echo "</font>, <font size='1px' color='red'>" .$visitsslug->GOTO;
		}	
		echo "</font><br />";
		
		$totalclicks = $totalclicks + $numcounts;
	  	$arrclicks[] = $numcounts; 
		$slug = 'startslug';
		$slugcounter = 0 ;
		$slugs[0] = 0;
		foreach ($numvisits as $numvisit){
			if($numvisit->SLUG <> $slug){
				if(!$slugcounter == 0){
				echo $slug .": " . $num . "<br />";	
				$arrnamessub[] = $slug;
				$arrclickssub[] = $num;
				$num = 0;	
				}
			}
			$num++;
			$slug = $numvisit->SLUG;
			$slugcounter++;
			if($slugcounter == $numcounts){
				echo $slug .": " . $num . "<br />";
				$arrnamessub[] = $slug;
				$arrclickssub[] = $num;
			}
		}
		echo "</td><td style='vertical-align:top; border-bottom: #ccc 2px solid;'>";
		if($numcounts === 0){
		echo "No data available...";
		}else{
		// Dataset definition 
  $DataSet = new pData;
	foreach ($arrclickssub as $i => $value) {
    	$DataSet->AddPoint($arrclickssub[$i],"Serie1");
	}
	foreach ($arrnamessub as $i => $value) {
		$DataSet->AddPoint($arrnamessub[$i],"Serie2");
	}
  //$DataSet->AddPoint(array(1),"Serie1");
  //$DataSet->AddPoint(array("hej"),"Serie2");
  $DataSet->AddAllSeries();
  $DataSet->SetAbsciseLabelSerie("Serie2");

	  
  // Initialise the graph
  $Test = new pChart(430,150);
  $Test->drawFilledRoundedRectangle(7,7,425,143,5,240,240,240);
  $Test->drawRoundedRectangle(5,5,80,105,5,230,230,230);
  $Test->loadColorPalette($mypath . 'palette.txt');

	  // Draw the pie chart
	  
	  $Test->setFontProperties($mypath . "fonts/tahoma.ttf",8);
	  $Test->drawPieGraph($DataSet->GetData(),$DataSet->GetDataDescription(),140,65,80,PIE_PERCENTAGE,TRUE,50,20,5);
	  $Test->drawPieLegend(255,15,$DataSet->GetData(),$DataSet->GetDataDescription(),250,250,250);
		
	  $Test->Render("total_" .$visit->ID .".png");
	  echo "<img src='total_" .$visit->ID .".png' />";
		}
		unset($arrnamessub);
		unset($arrclickssub);
		echo "</td></tr>";
	
	}

	 echo "</table>";
  
	if($norecords === "true"){
		echo "No records found!";
		}else{
	  // Dataset definition 
	  $DataSet = new pData;
		foreach ($arrclicks as $i => $value) {
	    	$DataSet->AddPoint($arrclicks[$i],"Serie1");
		}
		foreach ($arrnames as $i => $value) {
			$DataSet->AddPoint($arrnames[$i],"Serie2");
		}
	  //$DataSet->AddPoint(array(1),"Serie1");
	  //$DataSet->AddPoint(array("hej"),"Serie2");
	  $DataSet->AddAllSeries();
	  $DataSet->SetAbsciseLabelSerie("Serie2");
	$mypath = str_replace("wp-admin", "", getcwd());
		  $mypath = $mypath . "/wp-content/plugins/goto-outbound-links-and-analytics/";
	  // Initialise the graph
	  $Test = new pChart(600,400);
	  $Test->drawFilledRoundedRectangle(7,7,573,303,5,240,240,240);
	  $Test->drawRoundedRectangle(5,5,575,305,5,230,230,230);
	  $Test->loadColorPalette($mypath . 'palette.txt');

	  // Draw the pie chart
	  
	  $Test->setFontProperties($mypath . "fonts/tahoma.ttf",8);
	  $Test->drawPieGraph($DataSet->GetData(),$DataSet->GetDataDescription(),240,150,180,PIE_PERCENTAGE,TRUE,50,20,5);
	  $Test->drawPieLegend(450,15,$DataSet->GetData(),$DataSet->GetDataDescription(),250,250,250);
		
	  $Test->Render("total.png");
	  echo "<br />Total: <strong>" .$totalclicks ."</strong> Clicks<br /><img src='total.png' />";
	}
		echo "</form>";
}
?>