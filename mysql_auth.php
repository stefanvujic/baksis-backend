<?php

$con = mysqli_connect("localhost", "baksis", "h[f_d6rK3n<ddfB{H7X4W*S{}:?h7gHgh", "Baksa");

if (!$con) {
    echo "Error: Unable to connect to MySQL." . PHP_EOL;
    echo "Debugging errno: " . mysqli_connect_errno() . PHP_EOL;
    echo "Debugging error: " . mysqli_connect_error() . PHP_EOL;
    exit;
}