<?php

function get_rating($waiter_id) {
	require 'mysql_auth.php';

	$query_string = "SELECT * FROM waiter_ratings WHERE waiter_id = ?";

	$get_ratings = $con->prepare($query_string);
	$get_ratings->bind_param('s', $waiter_id);

	$get_ratings->execute();

	$result = $get_ratings->get_result();
	$ratings = $result->fetch_assoc();

	if ($ratings) {
		$total_reviews = $ratings["1_star"] + $ratings["2_star"] + $ratings["3_star"] + $ratings["4_star"] + $ratings["5_star"];
		if ($total_reviews !== 0) {
			$rating = (1*$ratings["1_star"] + 2*$ratings["2_star"] + 3*$ratings["3_star"] + 4*$ratings["4_star"] + 5*$ratings["5_star"]) / ($total_reviews);
			$rating = round($rating);
		}else {
			$rating = 0;
		}
	}else {
		$rating = 0;
	}

	return $rating;
}

