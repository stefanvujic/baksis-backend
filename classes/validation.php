<?php

require_once '../../vendor/autoload.php';
use Respect\Validation\Validator as v;

//validate cirilic

class Validation
{
	public function username($username) {
		(v::stringType()->length(5, 14)->validate($username)) ? ($validated = true) : ($validated = false);
		return $validated;
	}	
	public function password($password) {
		(v::stringType()->length(8, 20)->validate($password) && v::stringVal()->validate($password) && $password == strip_tags($password)) ? ($validated = true) : ($validated = false);
		return $validated;
	}
	public function email($email) {
		(v::email()->validate($email) && $email == strip_tags($email)) ? ($validated = true) : ($validated = false);
		return $validated;
	}
	public function name($name) {
		(v::stringType()->length(3, 30)->validate($name) && $name == strip_tags($name)) ? ($validated = true) : ($validated = false);
		return $validated;
	}
	public function company_name($company_name) {
		(v::stringType()->length(3, 30)->validate($company_name) && $company_name == strip_tags($company_name)) ? ($validated = true) : ($validated = false);
		return $validated;
	}			
	public function address($address) {
		(v::stringType()->length(5, 100)->validate($address) && $address == strip_tags($address)) ? ($validated = true) : ($validated = false);
		return $validated;
	}	
	public function city($city) {
		(v::stringType()->length(3, 30)->validate($city) && $city == strip_tags($city)) ? ($validated = true) : ($validated = false);
		return $validated;
	}	
	public function country($country) {
		(v::stringType()->length(3, 30)->validate($country) && $country == strip_tags($country)) ? ($validated = true) : ($validated = false);
		return $validated;
	}		
	public function phone($number) {
		(v::noWhitespace()->validate($number) && v::Phone()->validate($number) && $number == strip_tags($number)) ? ($validated = true) : ($validated = false);
		return $validated;
	}
	public function postal_code($postal_code) {
		(v::numericVal()->validate($postal_code) && v::stringType()->length(3, 10)->validate($postal_code) && $postal_code == strip_tags($postal_code)) ? ($validated = true) : ($validated = false);
		return $validated;
	}	
	public function account_number($account_number) {
		(v::intVal()->length(10, 50)->validate($account_number) && $account_number == strip_tags($account_number)) ? ($validated = true) : ($validated = false);
		return $validated;
	}	
	public function avatar_img($img) {
		($img && v::size('1KB', '200KB')->validate($img) && v::mimetype('image/png')->validate($img)) ? ($validated = true) : ($validated = false);
		return $validated;
	}
	public function user_type($type) {
		(v::identical("waiter")->validate($type) || v::identical("user")->validate($type) && $type == strip_tags($type)) ? ($validated = true) : ($validated = false);
		return $validated;
	}		

	public function amount($amount) {
		(v::intVal()->between(20, 99999999)->validate($amount)) ? ($validated = true) : ($validated = false);
		return $validated;
	}
	public function ID($ID) {
		(v::intVal()->between(1, 9999999)->validate($ID) && $ID == strip_tags($ID)) ? ($validated = true) : ($validated = false);
		return $validated;
	}	
	public function token($token) {
		(v::alnum()->validate($token) && v::stringType()->length(64)->validate($token) && $token == strip_tags($token)) ? ($validated = true) : ($validated = false);
		return $validated;
	}		
	public function rating($rating) {
		(v::intVal()->between(0, 5)->validate($rating) && $rating == strip_tags($rating)) ? ($validated = true) : ($validated = false);
		return $validated;
	}		
}

















