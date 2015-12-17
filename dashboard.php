<?php
    session_start();

    require_once($_SERVER['DOCUMENT_ROOT']."/data-layer/dbCommunication.php");
    require_once($_SERVER['DOCUMENT_ROOT']."/service-layer/serviceLayer.php");

    if(isset($_POST['login'])){
        $Username = $_POST['username'];
        $Password = $_POST['password'];
        $output = UserLogin($Username, $Password);
        var_dump($output);
        // If success, redirect to dashboard.php
    }

    if(isset($_POST['register'])){
        echo "why";
        $Username = $_POST['username'];
        $Password = $_POST['password'];
        $output = CreateUser($Username, $Password);
        var_dump($output);
    }

    // Verify that we're authorized
    if(!isset($_SESSION["Token"]) || !IsAuthorized()){
        // Redirect to index.php
        header("Location: index.php");
    }

    $_SESSION["LastMessageID"] = 0;

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
    refreshLeaderboard();
    refreshGameList();
});

function refreshChat(){
    $.ajax({
        type: "POST",
        url: "./service-layer/serviceLayer.php",
        data: "update_chat",
        success: function(data){
            var parsedData = JSON.parse(data);
            var chatDiv = document.getElementById("chat");
            chatDiv.innerHTML = "";
            chatDiv.innerHTML = "<h2>Chat</h2>"
            for(var i = 0; i < parsedData.length; i++){
                var msg = document.createElement("span");
                var Username = parsedData[i].Username;
                var Message = parsedData[i].Message;
                var Time = parsedData[i].Time;

                msg.innerHTML = "[" + Time + "] " + Username + ": " + Message + "</br>";
                chatDiv.appendChild(msg);
            }
            setTimeout(refreshChat, 3000);
        }
    });
}

function refreshLeaderboard(){
    $.ajax({
        type: "POST",
        url: "./service-layer/serviceLayer.php",
        data: "update_leaderboard",
        success: function(data){
            var parsedData = JSON.parse(data);
            var leaderboardDiv = document.getElementById("leaderboard");

            var innerHTML = "<h2>Leaderboard</h2><table border='1'><tr><th>Username</th><th>Hands Won</th><th>Hands Lost</th><tr>";
            for(var i = 0; i < parsedData.length; i++){
                var Username = parsedData[i].Username;
                var HandsWon = parsedData[i].HandsWon;
                var HandsLost = parsedData[i].HandsLost;
                
                innerHTML += "<tr>";
                innerHTML += "<td>" + Username + "</td><td>" + HandsWon + "</td><td>" + HandsLost + "</td>";
                innerHTML += "</tr>";
            }
            innerHTML += "</table>";
            leaderboardDiv.innerHTML = innerHTML;

            setTimeout(refreshLeaderboard, 3000);
        }
    });
}

function refreshGameList(){
    $.ajax({
        type: "POST",
        url: "./service-layer/serviceLayer.php",
        data: "get_games",
        success: function(data){
            var parsedData = JSON.parse(data);
            var gameDiv = document.getElementById("game_list");

            var innerHTML = "<h2>Game List</h2><table border='1'><tr><th>GameID</th><th>GameName</th><tr>";
            for(var i = 0; i < parsedData.length; i++){
                var GameID = parsedData[i].GameID;
                var GameName = parsedData[i].GameName;
                
                innerHTML += "<tr>";
                innerHTML += "<td>" + GameID + "</td><td>" + GameName + "</td>";
                innerHTML += "</tr>";
            }
            innerHTML += "</table>";
            gameDiv.innerHTML = innerHTML;
            setTimeout(refreshGameList, 3000);
        }
    });
}

</script>
<?php
?>
<form action="game.php" method="post">
    Name: <input type="text" name="game_name"></br>
    <input type="submit" name="create_game" value="Create Game">
</form>

<form id="send_message" action"dashboard.php" method="post">
    Message: <input type="text" name="message"></br>
    <input type="submit" name="send_message" value="Send">
</form>

<div id="chat">
</div>
<div id="game_list">
</div>
<div id="leaderboard">
</div>
</body>
</html>
