<?php 
/*
for local test purpose:
	database name: survey_potal;
	database user: cs546;
	passward: cs546;

CONTACTS_Tbl: 
idCONTACTS_Tbl
SRC_ID
CON_ID
CON_FIRST_NAME
CON_MIDDLE_NAME
CON_LAST_NAME
CON_SALUTATION
CON_PREFERED_PHONE_NO
CON_PREFERED_FAX_NO
CON_PREFERED_EMAIL
CON_LAST_UPDATED
CON_LAST_PROCESSED

CONTACTS_MAPPING_TBL:
idCONMAP_Tbl
SRC_ID
JVA_CON_ID
CON_ID
MAPPING_TYPE
CREATED_DATE

Client sample data: clientA from 1-03_Survey_Sample_Data_NEW.xlsx, named ClientA after imported into database;
Assume we have a Cli_SRC_Table (SRC_ID, Client_Name)

Salutation	First_Nm	Mid_Nm	Last_Nm	Full_Nm	eMail	Phone_No Fax_No	Contact_Source_ID


*/
	//a databese class provided by Professor Steven A. Gabarro for php 5.0 or later
	include ("include/databaseClassMySqli.php");
	
	global $JVA_Host = "127.0.0.1", $JVA_DB = "survey_portal", $JVA_User = "cs546", $JVA_Pass = "cs546";
	
	$today = date("Y-m-d H:i:s");
	//create new database object and setup the connection
	$con_map = new database();
	$con_map->setup($JVA_User, $JVA_Pass, $JVA_Host, $JVA_DB);
	
	//find the sourceID of given client: client A
	$sourceId_res = $con_map->send_sql("SELECT SRC_ID FROM Cli_SRC_Table 
										WHERE client_name = 'Client A'");
	$sourceID = ($sourceId_res->fetch_assoc())["SRC_ID"];
	
	//Select necessary data for contact mapping
	$query = "SELECT Contact_Source_ID, Salutation, First_Nm, Mid_Nm, Last_Nm, Phone_No, Fax_No FROM ClientA";
	$res = $con_map->send_sql($query);
	
	//mapping
	while($nextrow = $res->fetch_assoc()) 
	{
		$conID = $nextrow["Contact_Source_ID"];
		$fn = $nextrow["First_Nm"];
		$mn = $nextrow["Mid_Nm"];
		$ln = $nextrow["Last_Nm"];
		$salutation = $nextrow["Salutation"];
		$Phone = $nextrow["Phone_No"];
		$fax = $nextrow["Fax_No"];
		$email = $nextrow["email"];

		$JVA_query = "SELECT CON_ID FROM contacts_tbl
						WHERE SRC_ID = 'JVA' AND CON_FIRST_NAME = '$fn' 
						AND CON_LAST_NAME = '$ln' AND CON_PREFERED_EMAIL = '$email'";
		$JVA_res = $con_map->send_sql($JVA_query);
		
		//Already have record in JVA data source
		if ($JVA_res->num_rows > 0) 
		{
			//Insert data only into contacts_tbl
			$CON_insert = "INSERT INTO contacts_tbl (SRC_ID, CON_ID, CON_FIRST_NAME, CON_MIDDLE_NAME, CON_LAST_NAME, CON_SALUTATION, 
							CON_PREFERED_PHONE_NO, CON_PREFERED_FAX_NO, CON_PREFERED_EMAIL, CON_LAST_UPDATED, CON_LAST_PROCESSED)
							VALUES ('$sourceID', '$conID', '$fn', '$mn', '$ln', '$salutation', '$phone', '$fax', '$email', '$today', '$today')";
			$con_map->send_sql($CON_insert);
			//also need a function to update contact information in JVA database if there are any;
		}
		//No record in JVA_CON_TBL, new contact
		else 
		{
			//Insert contact into contacts_tbl (SRC_ID = sourceID)
			$CON_insert = "INSERT INTO contacts_tbl (SRC_ID, CON_ID, CON_FIRST_NAME, CON_MIDDLE_NAME, CON_LAST_NAME, CON_SALUTATION, 
							CON_PREFERED_PHONE_NO, CON_PREFERED_FAX_NO, CON_PREFERED_EMAIL, CON_LAST_UPDATED, CON_LAST_PROCESSED)
							VALUES ('$sourceID', '$conID', '$fn', '$mn', '$ln', '$salutation', '$phone', '$fax', '$email', '$today', '$today')";
			$con_map->send_sql($CON_insert);
			
			//generate JVA_CON_ID and insert contact into contacts_tbl (SRC_ID = JVA)
			$JVA_CON_ID = "JVA_CON_".$con_map->insert_id();
			$JVA_insert = "INSERT INTO contacts_tbl (SRC_ID, CON_ID, CON_FIRST_NAME, CON_MIDDLE_NAME, CON_LAST_NAME, CON_SALUTATION, 
							CON_PREFERED_PHONE_NO, CON_PREFERED_FAX_NO, CON_PREFERED_EMAIL, CON_LAST_UPDATED, CON_LAST_PROCESSED)
							VALUES ('JVA', '$JVA_CON_ID', '$fn', '$mn', '$ln', '$salutation', '$phone', '$fax', '$email', '$today', '$today')";
			$con_map->send_sql($JVA_insert);
			
			//Insert entry into CONTACTS_MAPPING_TBL
			$CON_Map_insert = "INSERT INTO CONTACTS_MAPPING_TBL (SRC_ID, JVA_CON_ID, CON_ID, MAPPING_TYPE, CREATED_DATE) 
							VALUES ('$sourceID', '$JVA_CON_ID', '$conID', 'wizard', ''$today)";
			$con_map->send_sql($CON_Map_insert);
		}	
	}
	
	$con_map->disconnect();


?>
