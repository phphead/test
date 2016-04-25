<?php namespace Rj\Model;

use Exception,
	Rj\Mvc\Model, Rj\Assert,
	Rj\UserPhoneConfirmationInterface as UserInterface;

/**
 * @method PhoneConfirmation findFirst($parameters = null) static
 * @package Rj\Model
 */
abstract class PhoneConfirmation extends Model {

	/** @return string */
	public static function genCode() {
		return substr(str_shuffle('0123456789'), 0, 4);
	}

	/** @return static */
	public static function findActive(UserInterface $user, $code = null) {
		if ($code) {
			$ret = static::findFirst([
				'user_id = ?0 and active = 1 and code = ?1',
				'bind' => [ $user->getId(), $code ],
			]);

		} else {
			$ret = static::findFirst([
				'user_id = ?0 and active = 1',
				'bind' => [ $user->getId() ],
			]);
		}

		if ( ! $ret)
			return null;

		$expiresTime = strtotime($ret->expires_at);
		if ( ! $expiresTime || time() > $expiresTime || ! $user->getPhoneNumber() || $user->getPhoneNumber() != $ret->phone) {
			$ret->save([ 'active' => null ]);
			Assert::noMessages($ret);
			return null;

		} else {
			return $ret;
		}
	}

	/** @return static|bool */
	public static function createForUser(UserInterface $user) {
		$model = new static;
		$model->save([
			'user_id'    => $user->getId(),
			'phone'      => $user->getPhoneNumber(),
			'code'       => static::genCode(),
			'expires_at' => date('Y-m-d H:i:s', strtotime('+300 second')),
			'active'     => 1,
		]);
		Assert::noMessages($model);

		return $model;
	}

	public static function expireOld() {
		$model = new static;
		$model->getWriteConnection()->query("
			update {$model->getSource()} set active = null where now() > expires_at
		");
	}

	public $phone_confirm_id, $user_id, $phone, $code, $created_at, $expires_at, $active, $result;

	public function getSource() {
		return 'phone_confirm';
	}

	public function confirm(UserInterface $user, $code) {
		if ($user->getId() == $this->user_id && $user->getPhoneNumber() == $this->phone) {
			if ($code && $code != $this->code) {
				return false;
			}

			$this->getWriteConnection()->begin();
			try {
				$user->setPhoneNumberConfirmed(true);
				Assert::noMessages($user);

				$this->save([ 'active' => null, 'result' => 1 ]);
				Assert::noMessages($this);

				$this->expireAnother();

				$this->getWriteConnection()->commit();

			} catch (Exception $e) {
				$this->getWriteConnection()->rollback();
				throw $e;
			}

			return true;

		} else {
			$this->save([ 'active' => null, 'result' => 0 ]);
			Assert::noMessages($this);
		}

		return false;
	}

	public function expireAnother() {
		$this->getWriteConnection()->query("
			update {$this->getSource()} set active = null where user_id = ? and phone_confirm_id != ?
		", [ $this->user_id, $this->phone_confirm_id ]);
	}

	abstract public function sendMessage();

}
