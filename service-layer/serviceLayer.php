<?php

require "./data-layer/dbCommunication.php";

// Replacement Function for PHP < 5.6.0 (Required for hash comparisons)
if(!function_exists('hash_equals')){
    function hash_equals($str1, $str2){
        if(strlen($str1) != strlen($str2)){
            return false;
        }else{
            $res = $str1 ^ $str2;
            $ret = 0;
            for($i = strlen($res) - 1; $i >= 0; $i--){
                $ret |= ord($res[$i]);
            }
            return !$ret;
        }
    }
}

function CreateUser($Username, $Password){

    // Hash and salt password
    $cost = 10;
    $salt = strtr(base64_encode(mcrypt_create_iv(16, MCRYPT_DEV_URANDOM)), '+', '.');
    $salt = sprintf("$2a$%02d$", $cost) . $salt;
    $HashSaltedPassword = crypt($Password, $salt);

    $return_status = DBCreateUser($Username, $HashSaltedPassword);

    if($return_status === SUCCESS){
        return array(SUCCESS, "Successfully created user."); 
    }elseif($return_status === USERNAME_ALREADY_EXISTS){
        return array(FAILURE, "Username already exists."); 
    }else{
        return array(FAILURE, "Failed created user."); 
    }
}

function UserLogin($Username, $Password){
    $return_status = DBUserLogin($Username, $Password);

    if($return_status === SUCCESS){
        // Destroy any existing token
        DBDestroyToken($Username);

        // Create token
        $NewToken = bin2hex(openssl_random_pseudo_bytes(64));

        // Store in SESSION
        $_SESSION["Token"] = $NewToken;
        $_SESSION["Username"] = $Username;

        // Store in DB
        DBSetToken($Username, $NewToken);

        return array(SUCCESS, "Successfully logged user in."); 
    }elseif($return_status === LOGIN_FAILED){
        return array(FAILURE, "Login failed."); 
    }else{
        return array(FAILURE, "Error."); 
    }
}

function UserLogout($Username){
    // Destory token
    DBDestroyToken($Username);
    
    // Destroy session
    $_SESSION["Token"] = NULL;

    // Execute logout procedure
}

function IsAuthorized(){
    // Eliminates warning and prevents potential security bug
    $Username = isset($_SESSION["Username"]) ? $_SESSION["Username"] : NULL;
    $SessionToken = isset($_SESSION["Token"]) ? $_SESSION["Token"] : NULL;
    $return = DBGetToken($Username);

    if($return !== FAILURE){
        if($SessionToken === $return)
            return true;
        else
            return false;
    }else{
        return false;
    }
}

function ShuffleDeck($GameID){
    $OrderedDeckString = "1S2S3S4S5S6S7S8S9S0SJSQSKS1H2H3H4H5H6H7H8H9H0HJHQHKH1D2D3D4D5D6D7D8D9D0DJDQDKD1C2C3C4C5C6C7C8C9C0CJCQCKC";
    $OrderedDeckArr = str_split($OrderedDeckString, 2);
    shuffle($OrderedDeckArr);
    $ShuffledDeckString = implode($OrderedDeckArr);

    DBUpdateGameDeck($GameID, $ShuffledDeckString);
}

function CreateGame($GameName, $Username){
    // Creates the game
    DBCreateGame($GameName);

    // Gets the GameID (Last inserted ID)
    $GameID = DBGetLastInsertID();

    // Shuffles the deck in the game
    ShuffleDeck($GameID);

    // Adds user to the game
    DBAddUserToGame($GameID, $Username);

    // User is added, now update user to ACTIVE (First user, goes first)
    DBUpdateUserGameStatus($GameID, $Username, "ACTIVE");

    // DEAL OUT
    // Get number of players + dealer
    $GameUsers = DBGetGameUsers($GameID);
    $DealerHand = DBGetDealerHand($GameID);
    $Deck = DBGetGameDeck($GameID);
    $DeckPointer = $Deck[0]->DeckPointer;

    // Loop twice (two cards deal out)
    for($i = 0; $i < 2; $i++){
        // Deal out to dealer
        $DealerHand .= $DeckPointer;
        $DeckPointer++;

        foreach($GameUsers as $User){
            $User->UserHand .= $DeckPointer;
            $DeckPointer++;
        }
    }

    // Update DB with UserHands and DealerHand
    foreach($GameUsers as $User){
        $Username = $User->Username;
        $UserHand = $User->UserHand;
        DBUpdateUserHand($GameID, $Username, $UserHand);
    }

    DBUpdateDealerHand($GameID, $DealerHand);

    echo "UserHand: " . $GameUsers[0]->UserHand;
    echo "\nDealerHand: " . $DealerHand . "\n";

    // Update User Board with the appropriate card mappings
}

function NewHand($GameID){
    // Get GameUsers, see if any are WAITING or  LEFT, set to ACTIVE or remove item in GameUsers
    // Shuffle Deck 
}

function HitPlayer($Username, $GameID){
    // Hit player with next card.
    // Update DB with new card
    // Determine of bust
    //   If so IncremenetHandsLost(UserID)
}

function IsUserTurn($Username, $GameID){

}

function FoldPlayer($Username){

}

function UpdateBoard($Username, $GameID){
    // Return board layout
}

function CheckIn($GameID){
    
}

?>
