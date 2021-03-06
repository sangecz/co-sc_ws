<?php

/**
 * Handling database connection
 *
 * This class extends tutorial mentioned below.
 *
 * @author Petr Marek, Ravi Tamada
 * @link http://www.androidhive.info/2014/01/how-to-create-rest-api-for-android-app-using-php-slim-and-mysql-day-12-2/
 */
class DbConnect {

    /**
     * @var mysqli connection instance
     */
    private $conn;

    public function __construct() {
    }

    /**
     * Establishing database connection, constants for DB connection are from Config.php
     *
     * @return mysqli connection instance
     */
    public function connect() {

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
