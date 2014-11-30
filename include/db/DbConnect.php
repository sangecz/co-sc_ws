<?php

/**
 * Handling database connection
 * This class greatly extends tutorial mentioned below
 *
 * @author Ravi Tamada, Petr Marek
 * @link URL Tutorial link http://www.androidhive.info/2014/01/how-to-create-rest-api-for-android-app-using-php-slim-and-mysql-day-12-2/
 */
class DbConnect {

    private $conn;

    function __construct() {        
    }

    /**
     * Establishing database connection
     * @internal param app $path path
     * @return database connection handler
     */
    function connect() {

        include_once $_SERVER['DOCUMENT_ROOT'] . '/co-sc/Config.php';

        // Connecting to mysql database
        $this->conn = new mysqli(DB_HOST, DB_USERNAME, DB_PASSWORD, DB_NAME);

        // Check for database connection error
        if (mysqli_connect_errno()) {
            echo "Failed to connect to MySQL: " . mysqli_connect_error();
        }

        // returing connection resource
        return $this->conn;
    }

}

?>
