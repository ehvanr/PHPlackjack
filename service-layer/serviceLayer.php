<?php
session_start();

require_once($_SERVER['DOCUMENT_ROOT']."/data-layer/dbCommunication.php");

if(isset($_POST['update_chat'])){
    GetMessages();
}

if(isset($_POST['update_leaderboard'])){
    GetLeaderboard();
}

if(isset($_POST['get_games'])){
    GetGames();
}

if(isset($_POST['send_message'])){
    $Message = $_POST["message"];
    SendMessage($Message);
}

if(isset($_POST['update_game'])){
    UpdateBoard();
}

if(isset($_POST['create_game'])){
    $GameName = $_POST["game_name"];
    $Username = $_SESSION["Username"];

    $output = CreateGame($GameName, $Username);
    UpdateBoard();
}

if(isset($_POST['game_hit'])){
    $Username = $_SESSION["Username"];
    $GameID = $_SESSION["GameID"];
    if(IsUserTurn($Username, $GameID)){
        HitPlayer();
        UpdateBoard();
    }
}

if(isset($_POST['game_fold'])){
    $Username = $_SESSION["Username"];
    $GameID = $_SESSION["GameID"];
    if(IsUserTurn($Username, $GameID)){
        FoldPlayer();
        UpdateBoard();
    }
}

if(isset($_POST['game_stay'])){
    $Username = $_SESSION["Username"];
    $GameID = $_SESSION["GameID"];
    if(IsUserTurn($Username, $GameID)){
        StayPlayer();
        UpdateBoard();
    }
}

if(isset($_POST['leave_game'])){
    $Username = $_SESSION["Username"];
    $GameID = $_SESSION["GameID"];
    if(IsUserTurn($Username, $GameID)){
        RemoveUserFromGame();
        UpdateBoard();
    }
}

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

function GetGames(){
    $output = DBGetGames();
    echo $output;
}

function GetLeaderboard(){
    $output = DBGetLeaderboard();
    echo $output;
}

function GetMessages(){
    $output = DBGetMessages(0);
    $decoded_json = json_decode($output);
    echo $output;
}

function SendMessage($Message){
    $Username = $_SESSION["Username"];
    DBSendMessage($Username, $Message);
}

function CreateUser($Username, $Password){

    // Hash and salt password
    $cost = 10;
    $salt = strtr(base64_encode(mcrypt_create_iv(16, MCRYPT_DEV_URANDOM)), '+', '.');
    $salt = sprintf("$2a$%02d$", $cost) . $salt;
    $HashSaltedPassword = crypt($Password, $salt);

    $return_status = DBCreateUser($Username, $HashSaltedPassword);


    if($return_status === SUCCESS){
        // Add to Leaderboard with no stats
        DBAddUserToLeaderboard($Username);
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
        DBUpdateToken($Username, $NewToken);

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
    $OrderedDeckString = "1S2S3S4S5S6S7S8S9STSJSQSKS1H2H3H4H5H6H7H8H9HTHJHQHKH1D2D3D4D5D6D7D8D9DTDJDQDKD1C2C3C4C5C6C7C8C9CTCJCQCKC";
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

    // Store GameID in SESSION
    $_SESSION["GameID"] = $GameID;

    // Shuffles the deck in the game
    ShuffleDeck($GameID);

    // Adds user to the game
    DBAddUserToGame($GameID, $Username);

    // User is added, now update user to ACTIVE (First user, goes first)
    DBUpdateGameUserStatus($GameID, $Username, "ACTIVE");

    // DEAL OUT
    // Get number of players + dealer
    $GameUsers = DBGetGameUsers($GameID, "ACTIVE");
    $DealerHand = DBGetDealerHand($GameID);
    $Deck = DBGetGameDeck($GameID);
    $DeckPointer = $Deck[0]->DeckPointer;

    // Loop twice (two cards deal out)
    for($i = 0; $i < 2; $i++){
        // Deal out to dealer
        $ZeroPadCard = sprintf("%02d", $DeckPointer);
        $DealerHand .= $ZeroPadCard;
        $DeckPointer++;

        foreach($GameUsers as $User){
            $ZeroPadCard = sprintf("%02d", $DeckPointer);
            $User->UserHand .= $ZeroPadCard;
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
    
    // Update DeckPointer
    DBUpdateDeckPointer($GameID, $DeckPointer);

    // Update UserTurn in Games to Username
    $Username = $_SESSION["Username"];
    DBUpdateUserTurn($Username, $GameID);

    // Update User Board with the appropriate card mappings
    $UserTurn = DBGetUserTurn($GameID);
    $_SESSION["UserTurn"] = $UserTurn;
    //UpdateBoard();
}

function UpdateBoard(){
    // Get all user hands
    $GameID = $_SESSION["GameID"];
    $CurrentDeckString = DBGetGameDeck($GameID)[0]->CurrentDeck;
    $CurrentDeckArr = str_split($CurrentDeckString, 2);
    $GameUsers = DBGetGameUsers($GameID, "ACTIVE");

    echo "<div id='cards'>";

    foreach($GameUsers as $User){
        $CurrentUsername = $User->Username;
        $CardIndexStr = $User->UserHand;
        $CardIndexArr = str_split($CardIndexStr, 2);

        echo $CurrentUsername . "'s Hand: ";
        foreach($CardIndexArr as $CardIndex){
            // convert to int
            $UnpaddedCardIndex = intval($CardIndex);
            echo $CurrentDeckArr[$UnpaddedCardIndex] . " ";
        }

        echo "</br>";
    }

    // Dealer Hand
    $DealerCardIndexStr = DBGetDealerHand($GameID);
    $DealerCardIndexArr = str_split($DealerCardIndexStr, 2);

    echo "Dealer's Hand: ";
    foreach($DealerCardIndexArr as $CardIndex){
        // convert to int
        $UnpaddedCardIndex = intval($CardIndex);
        echo $CurrentDeckArr[$UnpaddedCardIndex] . " ";
    }
    
    echo "</br></div>";

    // User resumes 2s check in
}

function AddUserToGame($GameID, $Username){
    DBAddUserToGame($GameID, $Username);   
    // USER IS WAITING
}

function RemoveUserFromGame(){
    // Need to check if active user and fold and further move to next user if so
    $Username = $_SESSION["Username"];
    $GameID = $_SESSION["GameID"];
    DBUpdateFoldedValue($Username, $GameID, 1);

    // Move to next player
    if(IsUserTurn($Username, $GameID)){
        UpdateUserTurn();
    }
    // If its our turn, fold and UpdateUserTurn() so its the next persons turn
}

function NewHand(){
    $GameID = $_SESSION["GameID"];
    $WaitingGameUsers = DBGetGameUsers($GameID, "WAITING");
    $LeftGameUsers = DBGetGameUsers($GameID, "LEFT");

    foreach($WaitingGameUsers as $User){
        $Username = $User->Username;
        DBUpdateGameUserStatus($GameID, $Username, "ACTIVE");
    }

    foreach($LeftGameUsers as $User){
        $Username = $User->Username;
        //DBRemoveUserFromGame();
    }

    $ActiveGameUsers = DBGetGameUsers($GameID, "ACTIVE");
    foreach($ActiveGameUsers as $User){
        $Username = $User->Username;
        DBUpdateUserHand($GameID, $Username, $Userhand);
    }

    DBUpdateDealerHand($GameID, NULL);

    // Shuffles the deck in the game
    ShuffleDeck($GameID);

    // DEAL OUT
    // Get number of players + dealer
    $GameUsers = DBGetGameUsers($GameID, "ACTIVE");
    $DealerHand = DBGetDealerHand($GameID);
    $Deck = DBGetGameDeck($GameID);
    $DeckPointer = $Deck[0]->DeckPointer;

    // Loop twice (two cards deal out)
    for($i = 0; $i < 2; $i++){
        // Deal out to dealer
        $ZeroPadCard = sprintf("%02d", $DeckPointer);
        $DealerHand .= $ZeroPadCard;
        $DeckPointer++;

        foreach($GameUsers as $User){
            $ZeroPadCard = sprintf("%02d", $DeckPointer);
            $User->UserHand .= $ZeroPadCard;
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
    
    // Update DeckPointer
    DBUpdateDeckPointer($GameID, $DeckPointer);

    // Update UserTurn in Games to Username
    $Username = $_SESSION["Username"];

    $UserTurn = DBGetGameUserUsernameByIndex(1, $GameID);
    DBUpdateUserTurn($UserTurn, $GameID);
    $_SESSION["UserTurn"] = $UserTurn;
    UpdateBoard();
}

function UpdateUserTurn(){
    //
    // CHECK IF PLAYER HAS FOLDED (LEFT) THE GAME, IF SO MOVE TO THE NEXT USER
    //
    // Increment, if no user, dealers turn, in which case to dealer, calc results
    $Username = $_SESSION["Username"];
    $GameID = $_SESSION["GameID"];

    $CurrentTurn = DBGetUserTurn($GameID);
    $Index = DBGetGameUserIndex($Username, $GameID);
    $NextPlayer = DBGetGameUserUsernameByIndex($Index + 1, $GameID);

    // If NextPlayer is null, its the dealers turn
    if($NextPlayer === DEALERS_TURN){
        // Dealer Turn
        DBUpdateUserTurn(NULL, $GameID);
        // Trigger dealer turn
        DealerPlay();
    }else{
        DBUpdateUserTurn($NextPlayer, $GameID);
    }

}

function IsUserTurn($Username, $GameID){
    // Compare $_SESSION["UserTurn"] with DB UserTurn (Query every two seconds)
    $GameID = $_SESSION["GameID"];
    $LastUserTurn = $_SESSION["UserTurn"];
    $CurrentUserTurn = DBGetUserTurn($GameID);

    if($LastUserTurn !== $CurrentUserTurn){
        // New user, update board
        $_SESSION["UserTurn"] = $CurrentUserTurn;
        //UpdateBoard();
    }

    if($_SESSION["UserTurn"] === $_SESSION["Username"]){
        // It is our turn, update controls to allow and give ability to fold / stay / hit
        return true;
    }else{
        return false;
    }
}

function HitPlayer(){
    // Hit player with next card.

    $Username = $_SESSION["Username"];
    $GameID = $_SESSION["GameID"];
    $UserHand = DBGetUserHand($GameID, $Username);
    $Deck = DBGetGameDeck($GameID);
    $DeckPointer = $Deck[0]->DeckPointer;

    $ZeroPadCard = sprintf("%02d", $DeckPointer);
    $UserHand .= $ZeroPadCard;
    $DeckPointer++;

    // Update the UserHand and DeckPointer
    DBUpdateUserHand($GameID, $Username, $UserHand);
    DBUpdateDeckPointer($GameID, $DeckPointer);
    
    CheckIfBust();
    // Update DB with new card
    // Determine of bust
    //   If so IncremenetHandsLost(UserID)
}

function FoldPlayer(){
    $Username = $_SESSION["Username"];
    $GameID = $_SESSION["GameID"];
    DBUpdateFoldedValue($Username, $GameID, 1);
    // Update UserTurn
    UpdateUserTurn();
}

function StayPlayer(){
    // Update UserTurn
    UpdateUserTurn();
}

function CheckIfBust(){
    $Username = $_SESSION["Username"];
    $GameID = $_SESSION["GameID"];
    $CurrentDeckString = DBGetGameDeck($GameID)[0]->CurrentDeck;
    $CardIndexStr = DBGetUserHand($GameID, $Username);
    $CurrentDeckArr = str_split($CurrentDeckString, 2);
    $CardIndexArr = str_split($CardIndexStr, 2);

    // Cumulative point value of the cards
    $Cumulative = 0;

    $Cumulative = DetermineHandValue($CardIndexArr, $CurrentDeckArr);

    if($Cumulative > 21){
        // If bust, Update UserTurn, update HandsLost
        DBIncrementHandsLost($Username);
        UpdateUserTurn();
        return true;
    }

    return false;
}

function DetermineHandValue($CardIndexArr, $CurrentDeckArr){
    // Cumulative point value of the cards
    $Cumulative = 0;

    // How many aces do we have? (Detriment as we fake bust)
    $NumAce = 0;

    foreach($CardIndexArr as $CardIndex){
        // convert to int
        $UnpaddedCardIndex = intval($CardIndex);
        $CurrentCard = $CurrentDeckArr[$UnpaddedCardIndex];
        $CardVal = $CurrentCard[0];

        switch($CardVal){
            case '1':
                $Cumulative += 11;
                $NumAce++;
                break;
            case 'K':
            case 'Q':
            case 'J':
            case 'T':
                $Cumulative += 10;
                break;
            default:
                $Cumulative += intval($CardVal);
        }
    }

    if($Cumulative > 21){
        for($NumAce; $NumAce > 0; $NumAce--){
            $Cumulative -= 10;
            if($Cumulative <= 21){
                break;
            }
        }
    }

    return $Cumulative;
}

function DealerHit(){
    
}

function DealerPlay(){
    // Dealer play rules
    // Hit if < 17, stay on 17+

    $GameID = $_SESSION["GameID"];

    while(true){
        $Deck = DBGetGameDeck($GameID)[0];
        $DeckPointer = $Deck->DeckPointer;
        $CurrentDeckString = $Deck->CurrentDeck;

        $CardIndexStr = DBGetDealerHand($GameID);
        $CurrentDeckArr = str_split($CurrentDeckString, 2);
        $CardIndexArr = str_split($CardIndexStr, 2);

        $Cumulative = DetermineHandValue($CardIndexArr, $CurrentDeckArr);

        if($Cumulative < 17){
            // Hit
            $ZeroPadCard = sprintf("%02d", $DeckPointer);
            $CardIndexStr .= $ZeroPadCard;
            $DeckPointer++;

            // Update the UserHand and DeckPointer
            DBUpdateDealerHand($GameID, $CardIndexStr);
            DBUpdateDeckPointer($GameID, $DeckPointer);
        }if($Cumulative >= 17){
            // Either stay or bust
            $Cumulative = DetermineHandValue($CardIndexArr, $CurrentDeckArr);

            echo "DEALERS HAND: " . $Cumulative . "</br>";
            CheckWinners();
            break;
        }
    }
}

function CheckWinners(){
    // Cycle through all hands, check if dealer beats, pushes or loses
    // Make sure to ignore all valx > 21 (those who busted)
    // Update hands lost / hands won
    // Call NewHand
    
    $GameID = $_SESSION["GameID"];
    $GameUsers = DBGetGameUsers($GameID, "ACTIVE");

    $CurrentDeckString = DBGetGameDeck($GameID)[0]->CurrentDeck;
    $CurrentDeckArr = str_split($CurrentDeckString, 2);

    $DealerCardIndexStr = DBGetDealerHand($GameID);
    $DealerCardIndexArr = str_split($DealerCardIndexStr, 2);
    $DealerCumulative = DetermineHandValue($DealerCardIndexArr, $CurrentDeckArr);

    foreach($GameUsers as $User){
        $CurrentUsername = $User->Username;
        $CardIndexStr = DBGetUserHand($GameID, $CurrentUsername);
        $UserFoldedLast = intval(DBGetFoldedValue($CurrentUsername, $GameID));
        $CardIndexArr = str_split($CardIndexStr, 2);

        $UserCumulative = DetermineHandValue($CardIndexArr, $CurrentDeckArr);
        
        if($UserFoldedLast){
            // User folded. No loss or win.
            // Reset fold status
            echo "folded </br>";
            DBUpdateFoldedValue($CurrentUsername, $GameID, 0);
        }elseif($UserCumulative > 21){
            // User Loses, Loss Increment Happened on Bust
            echo "user busts </br>";
        }elseif($DealerCumulative > 21){
            // User Wins
            echo "user wins by dealer bust </br>";
            DBIncrementHandsWon($CurrentUsername);
        }elseif($UserCumulative > $DealerCumulative){
            // User Wins
            echo "user wins </br>";
            DBIncrementHandsWon($CurrentUsername);
        }elseif($UserCumulative < $DealerCumulative){
            // User Loses
            echo "user lost </br>";
            DBIncrementHandsLost($CurrentUsername);
        }elseif($UserCumulative === $DealerCumulative){
            // Push
            echo "push </br>";
        }
    }

    NewHand();
}

function ResetEntireDatabase(){
    // Add some sort of authentication here
    DBTruncateAllTables();
}

?>
