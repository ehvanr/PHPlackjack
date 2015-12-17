<?php

require "./dbConnection.inc";

function passByReference($arr){
    
    //Reference is required for PHP 5.3+
    if (strnatcmp(phpversion(),'5.3') >= 0){ 
        $refs = array();
        foreach($arr as $key => $value)
            $refs[$key] = &$arr[$key];
        return $refs;
    }
    return $arr;
}

function returnJson ($stmt){
	$stmt->store_result();
 	$meta = $stmt->result_metadata();
    $bindVarsArray = array();

	while ($column = $meta->fetch_field()) {
    	$bindVarsArray[] = &$results[$column->name];
    }

	call_user_func_array(array($stmt, 'bind_result'), $bindVarsArray);

	while($stmt->fetch()) {
    	$clone = array();
        foreach ($results as $k => $v) {
        	$clone[$k] = $v;
        }
        $data[] = $clone;
    }

    if(isset($data))
        return json_encode($data);
    else
        return NULL;
}

/**
 * This is a generic SQL function.  You input the appropriate variables
 * and the function does all the SQL for you and returns information based
 * on SQL type (Update / Insert / Select / Delete) in a consistent manner. 
 *
 * @param $sql                  SQL Statement
 * @param $param_type_array     SQL Param Types (integer, string, etc)
 * @param $param_array          SQL Params
 * @param $sql_op               SQL Operations (Update, Insert, Select, Delete)
 *
 * UPDATE / INSERT / DELETE:
 * @return                      Success / Failure / Warning Codes (Constants)
 *
 * SELECT:
 * @return                      JSON Object / Failure Codes
 * */
function GenericSQL($sql, $param_type_array, $param_array, $sql_op){
	global $mysqli;

	try{
		if($stmt=$mysqli->prepare($sql)){
            if($param_type_array !== NULL && $param_array !== NULL)
                call_user_func_array(
                    array($stmt, "bind_param"), 
                    array_merge(
                        passByReference($param_type_array), 
                        passByReference($param_array)
                    )
                );

            $stmt->execute();

            if($stmt->affected_rows === 0 && $sql_op !== SQL_SELECT){
                return NOTHING_AFFECTED;
            }elseif($stmt->affected_rows === -1 && $sql_op !== SQL_SELECT){
                return $stmt->errno;
            }elseif($sql_op === SQL_SELECT){
                $data = returnJson($stmt);
                return $data;
            }else{
                return SUCCESS; 
            }
        }else{
            // Throw error
            fwrite(STDOUT, "else");
            return FAILURE;
        }
	}catch (Exception $e) {
        // Return generic error
        fwrite(STDOUT, "exception");
		return FAILURE;
    }
}

/**
 * Creates a new user in the database.
 *
 * @param $Username             The username to be added
 * @param $HashSaltedPassword   The already salted and hashed password
 *
 * @return                      Success / Failure / Warning Codes
 **/
function DBCreateUser($Username, $HashSaltedPassword){
    $sql = "INSERT INTO Users (Username, Password) VALUES (?, ?)";
    $param_type_array = array("ss");
    $param_array = array($Username, $HashSaltedPassword);
    $return = GenericSQL($sql, $param_type_array, $param_array, SQL_INSERT);

    switch($return){
        case SUCCESS:
            // Success
            return SUCCESS;
            break;
        case FAILURE:
            // Generic failure
            return FAILURE;
            break;
        case ER_DUP_ENTRY:
            // User already exists
            return USERNAME_ALREADY_EXISTS;
            break;
        default:
            // If not success, some error
            return FAILURE;
    }
}

/**
 * Verifies the users attempted login.
 *
 * @param $Username             The username to be added
 * @param $Password             The users challenge password
 *
 * @return                      Success / Failure / InvalidCredentials Codes
 **/
function DBUserLogin($Username, $Password){
    $sql = "SELECT Password FROM Users WHERE Username = ?";
    $param_type_array = array("s");
    $param_array = array($Username);
    $return = GenericSQL($sql, $param_type_array, $param_array, SQL_SELECT);
    
    if($return === FAILURE){
        return FAILURE;
    }else{
        $decoded_json = json_decode($return);
        $returned_password = $decoded_json;

        if($decoded_json !== NULL && hash_equals($decoded_json[0]->Password, crypt($Password, $decoded_json[0]->Password))){
            return SUCCESS;
        }else{
            return LOGIN_FAILED;
        }
    }
}

// FINISHED
function DBSetToken($Username, $Token){
    $sql = "UPDATE Users SET Token = ? WHERE Username = ?";
    $param_type_array = array("ss");
    $param_array = array($Token, $Username);
    $return = GenericSQL($sql, $param_type_array, $param_array, SQL_UPDATE);

    switch($return){
        case SUCCESS:
        case NOTHING_AFFECTED:
            return SUCCESS;
            break;
        case FAILURE:
            return FAILURE;
        default:
            return FAILURE;
            break;
    }
}

// FINISHED
function DBGetToken($Username){
    $sql = "SELECT Token FROM Users WHERE Username = ?";
    $param_type_array = array("s");
    $param_array = array($Username);
    $return = GenericSQL($sql, $param_type_array, $param_array, SQL_SELECT);

    if($return === FAILURE || $return === NULL){
        return FAILURE;
    }else{
        $returned_token= json_decode($return)[0]->Token;
        return $returned_token;
    }
}

// FINISHED
function DBDestroyToken($Username){
    $sql = "UPDATE Users SET Token = NULL WHERE Username = ?";
    $param_type_array = array("s");
    $param_array = array($Username);
    $return = GenericSQL($sql, $param_type_array, $param_array, SQL_UPDATE);

    switch($return){
        case SUCCESS:
        case NOTHING_AFFECTED:
            return SUCCESS;
            break;
        case FAILURE:
            return FAILURE;
        default:
            return FAILURE;
            break;
    }
}

// FINISHED
function DBSendMessage($Username, $Message){
    $sql = "INSERT INTO ChatHistory (Username, Message) VALUES (?, ?)";
    $param_type_array = array("ss");
    $param_array = array($Username, $Message);
    $return = GenericSQL($sql, $param_type_array, $param_array, SQL_INSERT);

    switch($return){
        case SUCCESS:
            return SUCCESS;
            break;
        case FAILURE:
            return FAILURE;
        default:
            return FAILURE;
            break;
    }
}

// FINISHED
function DBGetMessages($MessageID){
    $sql = "SELECT * FROM ChatHistory WHERE MessageID > ?";
    $param_type_array = array("i");
    $param_array = array($MessageID);
    $return = GenericSQL($sql, $param_type_array, $param_array, SQL_SELECT);

    if($return === FAILURE){
        return FAILURE;
    }else{
        $decoded_json = json_decode($return);
        return $decoded_json;
    }
}

// FINISHED
function DBCreateGame($GameName){
    $sql = "INSERT INTO Games (GameName) VALUES (?)";
    $param_type_array = array("s");
    $param_array = array($GameName);
    $return = GenericSQL($sql, $param_type_array, $param_array, SQL_INSERT);

    switch($return){
        case SUCCESS:
            return SUCCESS;
            break;
        case FAILURE:
            return FAILURE;
        default:
            return FAILURE;
            break;
    }
}

// FINISHED
function DBGetLastInsertID(){
    $sql = "SELECT LAST_INSERT_ID() AS LastID";
    $return = GenericSQL($sql, NULL, NULL, SQL_SELECT);

    if($return === FAILURE){
        return FAILURE;
    }else{
        $lastID = json_decode($return)[0]->LastID;
        return $lastID;
    }
}

// FINISHED
function DBAddUserToGame($GameID, $Username){
    $sql = "INSERT INTO GameUsers (GameID, Username, UserStatus) VALUES (?, ?, 'WAITING')";
    $param_type_array = array("is");
    $param_array = array($GameID, $Username);
    $return = GenericSQL($sql, $param_type_array, $param_array, SQL_INSERT);

    switch($return){
        case SUCCESS:
            return SUCCESS;
            break;
        case ER_DUP_ENTRY:
            return USERNAME_ALREADY_IN_GAME;
        case FAILURE:
            return FAILURE;
        default:
            return FAILURE;
            break;
    }
}

// FINISHED
function DBGetGames(){
    $sql = "SELECT * FROM Games";
    $return = GenericSQL($sql, NULL, NULL, SQL_SELECT);

    if($return === FAILURE){
        return FAILURE;
    }else{
        $decoded_json = json_decode($return);
        return $decoded_json;
    }
}

// FINISHED
function DBUpdateUserGameStatus($GameID, $Username, $StatusUpdate){
    $sql = "UPDATE GameUsers SET UserStatus = ? WHERE GameID = ? AND Username = ?";
    $param_type_array = array("sis");
    $param_array = array($StatusUpdate, $GameID, $Username);
    $return = GenericSQL($sql, $param_type_array, $param_array, SQL_UPDATE);

    switch($return){
        case SUCCESS:
        case NOTHING_AFFECTED:
            return SUCCESS;
            break;
        case FAILURE:
            return FAILURE;
        default:
            return FAILURE;
            break;
    }
}

// FINISHED
function DBRemoveUserFromGame($GameID, $Username){
    $sql = "DELETE FROM GameUsers WHERE GameID = ? AND Username = ?";
    $param_type_array = array("is");
    $param_array = array($GameID, $Username);
    $return = GenericSQL($sql, $param_type_array, $param_array, SQL_DELETE);

    switch($return){
        case SUCCESS:
        case NOTHING_AFFECTED:
            return SUCCESS;
            break;
        case FAILURE:
            return FAILURE;
        default:
            return FAILURE;
            break;
    }
}

// FINISHED
function DBUpdateGameDeck($GameID, $NewDeck){
    $sql = "UPDATE Games SET CurrentDeck = ?, DeckPointer = 0 WHERE GameID = ?";
    $param_type_array = array("si");
    $param_array = array($NewDeck, $GameID);
    $return = GenericSQL($sql, $param_type_array, $param_array, SQL_UPDATE);

    switch($return){
        case SUCCESS:
        case NOTHING_AFFECTED:
            return SUCCESS;
            break;
        case FAILURE:
            return FAILURE;
        default:
            return FAILURE;
            break;
    }
}

// FINISHED
function DBUpdateDeckPointer($GameID, $DeckPointer){
    $sql = "UPDATE Games SET DeckPointer = ? WHERE GameID = ?";
    $param_type_array = array("ii");
    $param_array = array($DeckPointer, $GameID);
    $return = GenericSQL($sql, $param_type_array, $param_array, SQL_UPDATE);

    switch($return){
        case SUCCESS:
        case NOTHING_AFFECTED:
            return SUCCESS;
            break;
        case FAILURE:
            return FAILURE;
        default:
            return FAILURE;
            break;
    }
}

// I wanna cry
// SELECT * FROM (SELECT @row_number:=@row_number+1 'ID', GameUsers.* FROM GameUsers, (SELECT @row_number:=0) AS T) A WHERE ID = ?;

function DBGetUserGameIndex($Username, $GameID){
    $sql = "SELECT * FROM (SELECT @row_number:=@row_number+1 'ID', GameUsers.* FROM GameUsers, (SELECT @row_number:=0) AS T) A WHERE Username = ? AND GameID = ?";
    $param_type_array = array("si");
    $param_array = array($Username, $GameID);
    $return = GenericSQL($sql, $param_type_array, $param_array, SQL_SELECT);

    if($return === FAILURE){
        return FAILURE;
    }else{
        $ID = json_decode($return)[0]->ID;
        return $ID;
    }
}

function DBGetUserGameUsernameByIndex($Index, $GameID){
    $sql = "SELECT * FROM (SELECT @row_number:=@row_number+1 'ID', GameUsers.* FROM GameUsers, (SELECT @row_number:=0) AS T) A WHERE ID = ? AND GameID = ?";
    $param_type_array = array("ii");
    $param_array = array($Index, $GameID);
    $return = GenericSQL($sql, $param_type_array, $param_array, SQL_SELECT);

    if($return === FAILURE){
        return FAILURE;
    }elseif($return === NULL){
        return DEALERS_TURN; 
    }else{
        $Username = json_decode($return)[0]->Username;
        return $Username;
    }
}

function DBUpdateUserTurn($Username, $GameID){
    $sql = "UPDATE Games SET UserTurn = ? WHERE GameID = ?";
    $param_type_array = array("si");
    $param_array = array($Username, $GameID);
    $return = GenericSQL($sql, $param_type_array, $param_array, SQL_UPDATE);

    switch($return){
        case SUCCESS:
        case NOTHING_AFFECTED:
            return SUCCESS;
            break;
        case FAILURE:
            return FAILURE;
        default:
            return FAILURE;
            break;
    }
}

function DBGetUserTurn($GameID){
    $sql = "SELECT UserTurn FROM Games WHERE GameID = ?";
    $param_type_array = array("i");
    $param_array = array($GameID);
    $return = GenericSQL($sql, $param_type_array, $param_array, SQL_SELECT);

    if($return === FAILURE){
        return FAILURE;
    }else{
        $user_turn = json_decode($return)[0]->UserTurn;
        return $user_turn;
    }
}

// FINISHED
function DBGetGameDeck($GameID){
    $sql = "SELECT CurrentDeck, DeckPointer FROM Games WHERE GameID = ?";
    $param_type_array = array("i");
    $param_array = array($GameID);
    $return = GenericSQL($sql, $param_type_array, $param_array, SQL_SELECT);

    if($return === FAILURE){
        return FAILURE;
    }else{
        $current_deck = json_decode($return);
        return $current_deck;
    }
}

// FINISHED
function DBGetDealerHand($GameID){
    $sql = "SELECT DealerHand FROM Games WHERE GameID = ?";
    $param_type_array = array("i");
    $param_array = array($GameID);
    $return = GenericSQL($sql, $param_type_array, $param_array, SQL_SELECT);

    if($return === FAILURE){
        return FAILURE;
    }else{
        $dealer_hand = json_decode($return)[0]->DealerHand;
        return $dealer_hand;
    }
}

// FINISHED
function DBUpdateDealerHand($GameID, $DealerHand){
    $sql = "UPDATE Games SET DealerHand= ? WHERE GameID = ?";
    $param_type_array = array("si");
    $param_array = array($DealerHand, $GameID);
    $return = GenericSQL($sql, $param_type_array, $param_array, SQL_UPDATE);

    switch($return){
        case SUCCESS:
        case NOTHING_AFFECTED:
            return SUCCESS;
            break;
        case FAILURE:
            return FAILURE;
        default:
            return FAILURE;
            break;
    }
}

// FINISHED
function DBGetGameUsers($GameID, $UserStatus){
    $sql = "SELECT * FROM GameUsers WHERE GameID = ? AND UserStatus = ?";
    $param_type_array = array("is");
    $param_array = array($GameID, $UserStatus);
    $return = GenericSQL($sql, $param_type_array, $param_array, SQL_SELECT);

    if($return === FAILURE){
        return FAILURE;
    }else{
        $game_users = json_decode($return);
        return $game_users;
    }
}

// FINISHED
function DBGetUserHand($GameID, $Username){
    $sql = "SELECT UserHand FROM GameUsers WHERE GameID = ? AND Username = ?";
    $param_type_array = array("is");
    $param_array = array($GameID, $Username);
    $return = GenericSQL($sql, $param_type_array, $param_array, SQL_SELECT);

    if($return === FAILURE){
        return FAILURE;
    }else{
        $user_hand = json_decode($return)[0]->UserHand;
        return $user_hand;
    }
}

// FINISHED
function DBUpdateUserHand($GameID, $Username, $UserHand){
    $sql = "UPDATE GameUsers SET UserHand= ? WHERE GameID = ? AND Username = ?";
    $param_type_array = array("sis");
    $param_array = array($UserHand, $GameID, $Username);
    $return = GenericSQL($sql, $param_type_array, $param_array, SQL_UPDATE);

    switch($return){
        case SUCCESS:
        case NOTHING_AFFECTED:
            return SUCCESS;
            break;
        case FAILURE:
            return FAILURE;
        default:
            return FAILURE;
            break;
    }
}

// FINISHED
function DBAddUserToLeaderboard($Username){
    $sql = "INSERT INTO Leaderboard VALUES (?, 0, 0)";
    $param_type_array = array("s");
    $param_array = array($Username);
    $return = GenericSQL($sql, $param_type_array, $param_array, SQL_INSERT);

    switch($return){
        case SUCCESS:
            return SUCCESS;
            break;
        case ER_DUP_ENTRY:
            return USERNAME_ALREADY_IN_LEADERBOARD;
        case FAILURE:
            return FAILURE;
        default:
            return FAILURE;
            break;
    }
}

// FINISHED
function DBIncrementHandsWon($Username){
    $sql = "UPDATE Leaderboard SET HandsWon = HandsWon + 1 WHERE Username = ?";
    $param_type_array = array("s");
    $param_array = array($Username);
    $return = GenericSQL($sql, $param_type_array, $param_array, SQL_UPDATE);

    switch($return){
        case SUCCESS:
        case NOTHING_AFFECTED:
            return SUCCESS;
            break;
        case FAILURE:
            return FAILURE;
        default:
            return FAILURE;
            break;
    }
}

// FINISHED
function DBIncrementHandsLost($Username){
    $sql = "UPDATE Leaderboard SET HandsLost= HandsLost + 1 WHERE Username = ?";
    $param_type_array = array("s");
    $param_array = array($Username);
    $return = GenericSQL($sql, $param_type_array, $param_array, SQL_UPDATE);

    switch($return){
        case SUCCESS:
        case NOTHING_AFFECTED:
            return SUCCESS;
            break;
        case FAILURE:
            return FAILURE;
        default:
            return FAILURE;
            break;
    }
}

// FINISHED
function DBGetLeaderboard(){
    $sql = "SELECT * FROM Leaderboard";
    $return = GenericSQL($sql, NULL, NULL, SQL_SELECT);

    if($return === FAILURE){
        return FAILURE;
    }elseif($return === NULL){
        return EMPTY_SELECT;
    }else{
        $decoded_json = json_decode($return);
        return $decoded_json;
    }
}

// FINISHED
function DBTruncateAllTables(){
    GenericSQL("SET FOREIGN_KEY_CHECKS = 0", NULL, NULL, SQL_DELETE);
    GenericSQL("TRUNCATE TABLE GameUsers", NULL, NULL, SQL_DELETE);
    GenericSQL("TRUNCATE TABLE ChatHistory", NULL, NULL, SQL_DELETE);
    GenericSQL("TRUNCATE TABLE Leaderboard", NULL, NULL, SQL_DELETE);
    GenericSQL("TRUNCATE TABLE Games", NULL, NULL, SQL_DELETE);
    GenericSQL("TRUNCATE TABLE Users", NULL, NULL, SQL_DELETE);
    GenericSQL("TRUNCATE TABLE GameUsers", NULL, NULL, SQL_DELETE);
    GenericSQL("SET FOREIGN_KEY_CHECKS = 1", NULL, NULL, SQL_DELETE);
}

?>
