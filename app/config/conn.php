<?php
    require_once 'config.php';
    
    try {
        // database connection
        $conn = mysqli_connect("localhost", "root", "", "pharmacy_management_system");

        if(!$conn){
            die("Connection failed: " . mysqli_connect_error());
        }
    }
    catch(Exception $e){
        error_log($e->getMessage());
        exit('Database connection failed. Please try again.');
    }
?>