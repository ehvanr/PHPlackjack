<?php
    session_start();
    require_once($_SERVER['DOCUMENT_ROOT']."/data-layer/dbCommunication.php");
    require_once($_SERVER['DOCUMENT_ROOT']."/service-layer/serviceLayer.php");



    // Verify that we're authorized
    if(!isset($_SESSION["Token"]) || !IsAuthorized()){
        // Redirect to index.php
        header("Location: index.php");
    }

    // Hit, Stay, Fold
    //

/**
    $output = CreateGame("GameTest", "Evan");
    if(IsUserTurn($_SESSION["Username"], $_SESSION["GameID"]))
        HitPlayer("Evan");
    if(IsUserTurn($_SESSION["Username"], $_SESSION["GameID"]))
        FoldPlayer();

    UpdateBoard();

    ResetEntireDatabase();
    // Check if user logged in, if so show leaderboard, if not, login / registration
 */ 
?>
<html>
<head></head>
<body>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/2.1.3/jquery.min.js"></script>
<script type="text/javascript">
$(document).ready(function() {
    refreshChat();
});

function refreshChat(){
    $.ajax({
        type: "POST",
        url: "./service-layer/serviceLayer.php",
        data: "update_game",
        success: function(data){
            var boardDiv = document.getElementById("cards");
            boardDiv.innerHTML = data;
            setTimeout(refreshChat, 3000);
        }
    });
}

</script>

<form action="game.php" method="post">
    <input type="submit" name="game_hit" value="Hit">
    <input type="submit" name="game_stay" value="Stay">
    <input type="submit" name="game_fold" value="Fold">
</form>
</body>
</html>
