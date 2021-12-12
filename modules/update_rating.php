<?php

function add_rating($waiter_id, $rating) {
	require 'mysql_auth.php';

	$col_name = $rating . "_star";
	$query_string = "SELECT " . $col_name . " FROM waiter_ratings WHERE waiter_id = ?";

	$get_ratings = $con->prepare($query_string);
	$get_ratings->bind_param('s', $waiter_id);

	$get_ratings->execute();
	$result = $get_ratings->get_result();
	$ratings = $result->fetch_assoc();

	if ($ratings) { // if already has ratings
		$new_rating = $ratings[$col_name] + 1;
		$query_string = "UPDATE waiter_ratings SET " . $col_name . " = ? WHERE waiter_id = ?";

		$insert_rating = $con->prepare($query_string);
		$insert_rating->bind_param('ss', $new_rating, $waiter_id);
		$result = $insert_rating->execute();
	}else {
		$query_string = "INSERT INTO waiter_ratings (ID, waiter_id, 1_star, 2_star, 3_star, 4_star, 5_star) VALUES (DEFAULT, '" . $waiter_id . "', '0', '0', '0', '0', '0')";
		$insert_ratings = mysqli_query($con, $query_string);

		$query_string = "UPDATE waiter_ratings SET " . $col_name . " = 1 WHERE waiter_id = " . $waiter_id;
		$result = mysqli_query($con, $query_string);
	}

	return $result;
}