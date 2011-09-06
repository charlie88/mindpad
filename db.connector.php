<?
require_once("config.php");

function dbValImplode($array) {
	$arrayString;
	for ($i = 0; $i < count($array); $i++) {
		if ($i == 0) {
			$arrayString = "'".$array[$i]."'";
		} else {
			$arrayString = $arrayString.",'".$array[$i]."'";
		}
	}
	return $arrayString;
}

function getRefDataForTable($table) {
	return dbQuery("SELECT * FROM ReferenceData WHERE key1 = '$table' ORDER BY value");
}

function cleansePureString($str, $char = '\\')
{
	///[^a-zA-Z0-9\s]/ NON ALPHA
	$cleansed = preg_replace("/[%_'\"]/", '', $str); //_, % and '
    return $cleansed;
}

function cleanseArray($opts) {
	$cleansedArray = array();
	$keys = array_keys($opts);
	$vals = array_values($opts);

	for ($i = 0; $i < count($keys); $i++) {
		$newKey = cleansePureString($keys[$i],"'");
		$newVal = cleansePureString($vals[$i],"'");
		$cleansedArray[$newKey] = $newVal;
	}
	return $cleansedArray;
}

//////////////// GENERIC CRUD FUNCTIONS ////////////

function genericSelect() {
	
}

function genericCreate($tablename, $opts, $link) {
	$opts = cleanseArray($opts);
	$comma_keys = implode(",", array_keys($opts));
	$comma_vals = dbValImplode(array_values($opts));

	$sql = "INSERT INTO $tablename (".$comma_keys.") VALUES (".$comma_vals.")";

	logDebug("SQL TO CREATE KEYS: ".$sql);
	if (isset($link))
		return dbQuery($link, $sql);
	else 
		return dbQuery($sql);
}

function genericUpdate($tablename, $opts, $where) {
	$opts = cleanseArray($opts);
	$keys = array_keys($opts);
	$vals = array_values($opts);

	$setString;
	$whereString;
	for ($i = 0; $i < count($keys); $i++) {
		if (in_array($keys[$i], $where)) {
			if (!isset($whereString))
				$whereString = $keys[$i]." = '".$vals[$i]."'";
			else
				$whereString = $whereString." AND ".$keys[$i]." = '".$vals[$i]."'";
		} else {
			if (!isset($setString))
				$setString = $keys[$i]." = '".$vals[$i]."'";
			else
				$setString = $setString.", ".$keys[$i]." = '".$vals[$i]."'";
		}
	}

	$sql = "UPDATE $tablename SET ".$setString." WHERE ".$whereString;

	logDebug("SQL TO Update KEYS: ".$sql);

	return dbQuery($sql);

}

function genericDelete($tablename, $opts) {
	$opts = cleanseArray($opts);
	$keys = array_keys($opts);
	$vals = array_values($opts);

	$whereString;
	for ($i = 0; $i < count($keys); $i++) {
		if ($i == (count($keys) - 1))
		$whereString = $whereString.$keys[$i]." = '".$vals[$i]."'";
		else
		$whereString = $whereString.$keys[$i]." = '".$vals[$i]."' AND ";
	}

	$sql = "DELETE FROM ". $tablename . " WHERE " . $whereString;
	logDebug("SQL TO DELETE KEYS: ".$sql);

	return dbQuery($sql);
}

////////////// DBDRIVER FUNCTIONS ///////////////

function dbConnect()
{
	global $dbURL, $dbUser, $dbPass, $dbSchema;
	$upLink = mysqli_connect($dbURL, $dbUser, $dbPass, $dbSchema);
	return $upLink;
}

function dbAlive($upLink)
{
	if (is_resource($upLink) && $upLink)
	{ return true; }
	return false;
}

function dbQuery($sqlQuery)
{
	$argv = func_get_args();
	$argc = func_num_args();
	switch ($argc)
	{
		/*
			1 Argument
			Autonomous MySQL Query {Automatically: Connects, Cleans + Closes}
			Creates Own Connection
			*/
		case 1:
			/*
				if (!is_string($argv[0]))
				{
				die("Argument 0: Not A MySQL String");
				}
				*/
			return dbQuery_Auto($argv[0]);
			/*
			 2 Arguments
			 Manual MySQL Query {Must Manually: Connect, Cleanup + Close}
			 Uses Existing Connection
			 */
		case 2:
			/*
				if (!dbAlive($argv[0]))
				{
				die("Argument 0: Not A Valid MySQL Connection");
				}
				if (!is_string($argv[1]))
				{
				die("Argument 1: Not A MySQL String");
				}
				*/
			return dbQuery_Manual($argv[0], $argv[1]);
		default:
			return false;
	}

}

function dbQuery_Auto($sqlQuery)
{
	$dbUplink = dbConnect();
	$queryResult = dbQuery_Manual($dbUplink, $sqlQuery);
	dbClose($dbUplink);
	return $queryResult;
}

function dbQuery_Manual($dbUplink, $sqlQuery)
{
	$result = mysqli_query($dbUplink, $sqlQuery);
	
	if (mysqli_errno($dbUplink)) {
		logError("Error msg: " . mysqli_errno($dbUplink) . mysqli_error($dbUplink));
		die(getCommonErrorString(mysqli_errno($dbUplink) . " " .mysqli_error($dbUplink)));
	}
	return $result;
}

function countQuery($sql) {
	$rs = dbQuery($sql);
	$result = dbFetchArray($rs);
	return $result[0];
}

function getColumnNames($tablename) {
	return dbQuery("SELECT * FROM $tablename LIMIT 0");
}

function dbClose($link)
{
	mysqli_commit($link);
	mysqli_close($link);
}

//////////////////////////////////////////////

function dbAffectedId($result) {
	return mysqli_insert_id($result);
}

function dbNumAffectedRows($result) {
	return mysqli_affected_rows($result);
}

function dbNumRows($result) {
	return mysqli_num_rows($result);
}

function dbNumFields($result) {
	return mysqli_num_fields($result);
}

function dbFetchObject($result) {
	return mysqli_fetch_object($result);
}

function dbFetchField($result) {
	return mysqli_fetch_field($result);
}

function dbFetchArray($results) {
	return mysqli_fetch_assoc($results);
}

function dbFetchNumArray($results) {
	return mysqli_fetch_array($results);
}

function dbFetchAll($results) {
	$fetchArray = array();
	$i = 0;
	while ($obj = dbFetchArray($results)){
		$fetchArray[$i] = $obj;
		$i++;
	}
	return $fetchArray;
}

function dbRelease($result)
{
	@mysqli_free_result($result);
}

function cleanseString($link, $str) {
	return mysqli_real_escape_string($link, $str);
}

function dbCloseRollback($link) {
	mysqli_rollback($link);
	mysqli_close($link);
}

function dbInsertedId($link) {
	return mysqli_insert_id($link);
}

function dbRowSeek($rs, $no) {
	return mysqli_data_seek($rs, $no);
}
?>