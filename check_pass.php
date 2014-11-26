<?php
$servername = "localhost";
$username = "editor";
$password = "editor";
$db = "co-sc";
$table = "security";
$column = "passphrase";

// Create connection
$mysqli = new mysqli($servername, $username, $password, $db);

// Check connection
if ($mysqli->connect_error) {
    $msg = array('error' => $mysqli->connect_error);
}

$query = "SELECT " . $column . " FROM " . $table;

if ($stmt = $mysqli->prepare($query)) {

    /* execute statement */
    $stmt->execute();

    /* bind result variables */
    $stmt->bind_result($col);

    /* fetch values */
    while ($stmt->fetch()) {
        $dbPass = $col;
    }

    /* close statement */
    $stmt->close();
}

/* close connection */
$mysqli->close();


// remove placeholder from incoming passhash
$pass = str_replace("*", "", $_POST['passphrase']);

if(isset($pass) && isset($dbPass)){
    if($pass == $dbPass){
        $msg = "equal";
        $resp = array( 'response' => $msg  );
        echo json_encode($resp);
    }
}

?>
