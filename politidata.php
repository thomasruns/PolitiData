<?php

//Initialize some variables
$cntInserts=0;
$cntUpdates=0;
$cntInvalid=0;
$cntValid=0;
$cntErrors=0;
$existsInd=0;
$debugFlag=0;		//Set to 1 for testing which will skip the file download and use the existing data file
$logdir='logs';
$logfile=$logdir.'/politidata_'.date("mdY").'.log';


//Set database stuff
$dbhostname = 'localhost';
$dbusername = 'your_db_username';
$dbpassword = 'your_db_password';
$dbname = 'your_db_name';

//Connect to the database
$conn = mysql_connect($dbhostname, $dbusername, $dbpassword);
mysql_select_db($dbname);

//Initialize the log
if(!is_dir($logdir))
{
      die("Directory '".$logdir."' doesn't exist. Unable to create log. Exiting.\n\n");
}
else
{
      $logvar=fopen($logfile, 'a');
      fwrite($logvar, "--------------------------------------------------------------------\n");
      fwrite($logvar, date("mdY_H:i:s ").":: BEGIN LOG\n");
}

//Set the timezone
date_default_timezone_set('CST6CDT');

//If debug flag is not set, remove the old files and download the latest from the FEC FTP site
if($debugFlag!=1)
{
	//Remove the old zip and dta files
	exec("rm *.zip *.dta");

	//Download the latest file from the FEC and unzip it
	exec("(wget ftp://ftp.fec.gov/FEC/cn12.zip)");
	exec("unzip cn12.zip");
}

if(!file_exists("foiacn.dta"))
{
      fwrite($logvar, date("mdY_H:i:s ").":: Error: FEC .dta file doesn't exist. Exiting.\n");
      $cntErrors=$cntErrors+1;
}
else
{
      $fp = fopen('foiacn.dta','r');
}

if($cntErrors==0)
{
	$allCandData = array();
	while ($s = fgets($fp)) {
	    $candData['fec_id'] = trim(substr($s,0,9));
	    $candData['name_candidate'] = trim(substr($s,9,38));
	    $candData['party_code'] = trim(substr($s,47,3));
	    $candData['party_code2'] = trim(substr($s,53,3));
	    $candData['cand_type'] = trim(substr($s,56,1));
	    $candData['cand_status'] = trim(substr($s,58,1));
	    $candData['address1'] = trim(substr($s,59,34));
	    $candData['address2'] = trim(substr($s,93,34));
	    $candData['city'] = trim(substr($s,127,18));
	    $candData['state'] = trim(substr($s,145,2));
	    $candData['zip'] = trim(substr($s,147,5));
	    $candData['election_year'] = trim(substr($s,161,2));
	    $candData['district'] = trim(substr($s,163,2));
	    $allCandData[] = $candData;
	}
	fclose($fp) or die("Can't close file");

	foreach($allCandData as $candData) {

		if($candData['cand_status']=='P' || $candData['cand_status']=='F')
		{
			//We don't want to store these. Just increment a counter for stats purposes
			$cntInvalid=$cntInvalid+1;
		}
		else
		{
			$existsInd=0;
			$existsInd=checkExists($candData['fec_id']);
			$cntValid=$cntValid+1;
		
			//Set date variable
			$curdate=date("Y-m-d H:i:s", time());

			if($existsInd!=0)
			{
				//Update the existing record
				$cand_query="UPDATE candidate set name_candidate='".mysql_real_escape_string($candData['name_candidate'])."', party_code='".$candData['party_code']."', party_code2='".$candData['party_code2']."', cand_type='".$candData['cand_type']."', address1='".mysql_real_escape_string($candData['address1'])."', address2='".mysql_real_escape_string($candData['address2'])."', city='".mysql_real_escape_string($candData['city'])."', state='".$candData['state']."', zip='".$candData['zip']."', election_year='".$candData['election_year']."', district='".$candData['district']."', date_modified='".$curdate."' WHERE fec_id='".$candData['fec_id']."'";
				$cntUpdates=$cntUpdates+1;
			}
			else
			{
				//Insert a new record for this candidate
				$cand_query="INSERT INTO candidate (fec_id,name_candidate,party_code,party_code2,cand_type,address1,address2,city,state,zip,election_year,district,date_created,date_modified,status_id) VALUES('".$candData['fec_id']."','".mysql_real_escape_string($candData['name_candidate'])."','".$candData['party_code']."','".$candData['party_code2']."','".$candData['cand_type']."','".mysql_real_escape_string($candData['address1'])."','".mysql_real_escape_string($candData['address2'])."','".mysql_real_escape_string($candData['city'])."','".$candData['state']."','".$candData['zip']."','".$candData['election_year']."','".$candData['district']."','".$curdate."','".$curdate."','1')";
				$cntInserts=$cntInserts+1;

			}

			//Execute the query
			$cand_query_result = mysql_query($cand_query);
			if($cand_query_result)
			{
				//Successful, nothing to log
			}
			else
			{
				//Write out the error to the log
				fwrite($logvar, date("mdY_H:i:s ").":: Error updating record for fec_id: ".$candData['fec_id'].".\n");
				fwrite($logvar, date("mdY_H:i:s ").":: MySQL Error: ".$mysql_error.".\n");
				$cntErrors=$cntErrors+1;
			}
		}
	}
}

//Write out final summary to the log file
fwrite($logvar, date("mdY_H:i:s ").":: Processing is complete.\n");
fwrite($logvar, date("mdY_H:i:s ").":: Candidates for this election cycle: ".$cntValid."\n");
fwrite($logvar, date("mdY_H:i:s ").":: Candidates not for this election cycle: ".$cntInvalid."\n");
fwrite($logvar, date("mdY_H:i:s ").":: Candidates inserted into database (new): ".$cntInserts."\n");
fwrite($logvar, date("mdY_H:i:s ").":: Candidates updated in database (existing): ".$cntUpdates."\n");
fwrite($logvar, date("mdY_H:i:s ").":: Total Errors: ".$cntErrors."\n");
fwrite($logvar, date("mdY_H:i:s ").":: END LOG\n");
fwrite($logvar, "--------------------------------------------------------------------\n");




function checkExists($fec_id)
{
	//This function checks to see if a record already exists for the fec_id value passed in. Returns 1 if yes, 0 if no.
	$checkExists_query = "SELECT * FROM candidate WHERE fec_id='".$fec_id."'";
	$checkExists_result = mysql_query($checkExists_query);
	$checkExists_count=mysql_num_rows($checkExists_result);
	return $checkExists_count;
}
?>

