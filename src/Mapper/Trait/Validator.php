<?php
declare(strict_types = 1);
/***
 * Date 25.04.2023
 * @author zeroc0de <98693638+zeroc0de2022@users.noreply.github.com>
 */

namespace Cpsync\Mapper\Trait;

use Exception;
use Cpsync\Mapper\Const\Notice;

/**
 * Trait Validator
 */
trait Validator
{
	/**
	 * Check the input key for validity
	 * @param array $data
	 * @return void
	 * @throws Exception
	 */
	public function checkInputKey(array $data): void
	{
		array_walk_recursive($data, function($value, $key) {
			if(preg_match('~[^\w\-]+~u', $key)) {
				throw new Exception(__LINE__.': '.Notice::W_HACKING_ATTEMPT);
			}
		});
	}


	/**
	 * Check the input value for validity
	 * @param array $data
	 * @return mixed
	 * @throws Exception
	 */
	public function sanitizeKeyValue(mixed $data): mixed
	{
		if(is_array($data)) {
			$sanitized_array = [];
			foreach($data as $key => $value) {
				$sanitized_key = preg_replace('~[^\w%\[\]]+~u', '', $key);
				$sanitized_value = is_array($value) ? $this->sanitizeKeyValue($value) : preg_replace('~[^\w%\[\]]+~u', '', $value);
				$sanitized_array[$sanitized_key] = $sanitized_value;
			}
			return $sanitized_array;
		} elseif(is_string($data)) {
			return preg_replace('~[^a-z0-9_%\[\]]+~u', '', $data);
		}
		return $data;
	}


	/**
	 * Check the input value for validity
	 * @param mixed|null $param
	 * @param mixed|null $value
	 * @return bool
	 */
	public function isEqualParamValue(mixed $param = null, mixed $value = null): bool
	{
		return ($param !== null && $param == $value);
	}


	/**
	 * Check username for validity
	 * @param string $username
	 * @return bool
	 */
	public function isNameValid(string $username): bool
	{
		return !preg_match('~[^А-яA-z\s]+~u', $username);

	}

	/**
	 * Check email for validity
	 * @param string $email
	 * @return bool
	 */
	public function isEmailValid(string $email): bool
	{
		return (bool)(filter_var($email, FILTER_VALIDATE_EMAIL));
	}

	/**
	 * Check username size
	 * @param string $username
	 * @param int $min_size
	 * @param int $max_size
	 * @return bool
	 */
	public function isSizeValid(string $username, int $min_size = 5, int $max_size = 20): bool
	{
		return (mb_strlen(trim($username)) > $min_size) && (mb_strlen(trim($username)) <= $max_size);
	}

	/**
	 * Check login for validity
	 * @param string $user_login
	 * @return bool
	 */
	public function isLoginValid(string $user_login): bool
	{
		return !preg_match('~[^\w-]~', $user_login);
	}

}