<?php namespace Rj;

use Closure,
	Phalcon\Validation as Phalcon_Validation,
	Phalcon\Validation\Validator\PresenceOf,
	Phalcon\Validation\Validator\Email,
	Phalcon\Validation\Validator\Regex,
	Phalcon\Validation\Validator\Between,
	Phalcon\Validation\Message,
	Rj\Validator\Callback;

class Validation extends Phalcon_Validation {

	public static function callback(Closure $callback, $options = []) {
		return new Callback($options + [
				'callback' => $callback
			]);
	}

	public static function presenceOf($options = []) {
		return new PresenceOf($options + [
				'message' => 'Обязательное поле',
			]);
	}

	public static function email($options = []) {
		return new Email($options + [
				'message' => 'Неправильный адрес',
			]);
	}

	public static function phone($options = []) {
		return new Regex($options + [
				'pattern' => '/^(\+\d{6,12})?$/iD',
				'message' => 'Формат должен быть такой: +12345678901',
			]);
	}

	public static function date($options = []) {
		return new Regex($options + [
				'pattern' => '/^(\d{4}-\d{2}-\d{2})?$/iD',
				'message' => 'Формат даты должен быть ГГГГ-мм-дд',
			]);
	}

	public static function time($options = []) {
		return new Regex($options + [
				'pattern' => '/^(\d{2}:\d{2})?$/iD',
				'message' => 'Формат времени должен быть ММ:СС',
			]);
	}

	public static function datetime($options = []) {
		return new Regex($options + [
				'pattern' => '/^(\d{4}-\d{1,2}-\d{1,2}\ \d{1,2}:\d{1,2}:\d{1,2})?$/iD',
				'message' => 'Формат даты-времени должен быть ГГГГ-мм-дд ЧЧ:мм:сс',
			]);
	}

	public static function int($options = []) {
		return new Regex($options + [
				'pattern' => '/^(\d+)?$/iD',
				'message' => 'Значение должно быть целым числом',
			]);
	}

	public static function uint($options = []) {
		return self::callback(function(Validation $validation, $attribute) {
			$value = $validation->getValue($attribute);
			if (preg_match('/^(\d+)?$/iD', $value) && $value >= 0) {
				return true;

			} else {
				$validation->appendMessage(new Message('Значение должно быть положительным целым числом', $attribute));
				return false;
			}
		}, $options);
	}

	public static function between($min, $max, $options = []) {
		return new Between($options + [
				'minimum' => $min,
				'maximum' => $max,
				'message' => "Значение должно быть между $min и $max",
			]);
	}

	public function getValue($attribute) {
		if (null !== ($ret = parent::getValue($attribute))) {
			return $ret;

		} else if ( ! empty($this->_filters[$attribute])) {
			return $this->filter->sanitize(null, $this->_filters[$attribute]);

		} else {
			return null;
		}
	}

}
