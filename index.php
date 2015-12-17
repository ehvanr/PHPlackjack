<?php
    session_start();

    require "./service-layer/serviceLayer.php";

/**
    $_SESSION["Token"] = NULL;
    CreateUser("Evan", "password");
    $output = UserLogin("Evan", "password");
    $output = CreateGame("GameTest", "Evan");
    if(IsUserTurn($_SESSION["Username"], $_SESSION["GameID"]))
        HitPlayer("Evan");
    if(IsUserTurn($_SESSION["Username"], $_SESSION["GameID"]))
        FoldPlayer();

    UpdateBoard();

    ResetEntireDatabase();
    // Check if user logged in, if so show leaderboard, if not, login / registration


<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.11.2/jquery.min.js"></script>
<script type="text/javascript">
    $(function () {
        $('form').on('submit', function (e) {
            e.preventDefault();

            $.ajax({
                type: 'POST',
                url: 'dashboard.php',
                data: $('form').serialize(),
                success: function() {
                    window.location.href = 'dashboard.php';
                }
            });
        });
    });
</script>
<form>
    Username: <input type="text" name="username"></br>
    Password: <input type="password" name="password"></br>
    <input name="register" type="submit" value="Register">
    <input name="login" type="submit" value="Login">
</form>
 */ 
?>
<html>
<head></head>
<body>
<form action="dashboard.php" method="post">
    Username: <input type="text" name="username"></br>
    Password: <input type="password" name="password"></br>
    <input type="submit" name="register" value="Register">
    <input type="submit" name="login" value="Login">
</form>
</body>
</html>
