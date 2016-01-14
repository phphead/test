<?php namespace Rj\Model;

use Exception,
	Rj\Mvc\Model, Rj\Assert, Rj\Logger, Rj\Random, Rj\UserInterface,
	Phalcon\DI,
	Phalcon\Db\RawValue as Phalcon_RawValue,
	Phalcon\Mvc\Model as Phalcon_Model;
/*
CREATE TABLE `ani_email_confirm` (
  `email_confirm_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `created_at` datetime NOT NULL,
  `expires_at` datetime DEFAULT NULL,
  `user_id` int(10) unsigned NOT NULL,
  `email` varchar(255) NOT NULL,
  `mail_queue_id` int(10) unsigned DEFAULT NULL,
  `confirm_key` varchar(255) DEFAULT NULL,
  `confirm_success` enum('y') DEFAULT NULL,
  PRIMARY KEY (`email_confirm_id`) USING BTREE,
  KEY `person_id` (`person_id`) USING BTREE
) ENGINE=InnoDB;
*/

abstract class EmailConfirm extends Model {

    /**
     * @return bool|EmailConfirm
     */
    public static function createForPerson(UserInterface $person) {
        if ( ! $person->getEmail()) {
            return false;
        }

        $ret = new static();
		try {
			$created = $ret->save([ 'person_id' => $person->getId(), 'email' => $person->getEmail() ]);
			Assert::noMessages($ret);

			if ($created) {
				// Не будем отменять все другие подтверждения
				//$ret->expireOlder();
				return $ret;

			} else {
				return false;
			}

		} catch (Exception $e) {
			Logger::messages()->exception($e);
			return false;
		}
    }

	/** @return EmailConfirm|false */
	public static function findLastConfirmation(UserInterface $person) {
		return static::findFirst([
			'person_id = :uid: and person_id is not null',
			'bind' => [ 'uid' => $person->getId() ],
			'order' => 'email_confirm_id desc'
		]);
	}

    public static function findByPersonIdAndKey($person_id, $confirm_key) {
        /** @var EmailConfirm $confirm */
        $confirm = static::findFirst([
			'expires_at is not null and expires_at > NOW() and person_id = :uid: and person_id is not null',
            'bind' => [ 'uid' => $person_id ],
        ]);

        if ($confirm) {
            if ($confirm->confirm_key === $confirm_key) {
                return $confirm;
            } else {
                throw new Exception("Bad restore key");
            }
        } else {
            return false;
        }
    }

    public
        // Primary key
        $email_confirm_id,
        // Creation date
        $created_at,
        // Expiration date
        $expires_at,
        // Found user ID
        $person_id,
        // Input email
        $email,
        // Mail queue ID
        $mail_queue_id,
        // Password restore key
        $confirm_key,
        // "y" if this restore was successful
        $confirm_success;

    public function save($data = null, $whiteList = null) {
        if ( ! $this->expires_at) {
            $this->expires_at = date('Y-m-d H:i:s', strtotime('+3 days'));
        }

        if ( ! $this->confirm_key) {
            $this->confirm_key = Random::generateKey(64);
        }

		return parent::save($data, $whiteList);
    }

    public function expireOlder() {
        $this->getWriteConnection()->begin();
        try {
            if ($this->person_id) {
                $this->getWriteConnection()->query("
                    update {$this->getSource()} set expires_at = null where person_id = ? and email_confirm_id != ?
                ", array($this->person_id, $this->email_confirm_id));
            }

            $this->getWriteConnection()->commit();

        } catch (Exception $e) {
            $this->getWriteConnection()->rollback();
            throw $e;
        }
    }

    public function expire() {
        $this->save([ 'expires_at' => new Phalcon_RawValue('NULL') ]);
		Assert::noMessages($this);
    }

    public function confirm() {
        if ($person = $this->_getUserModel()) {
            $this->getWriteConnection()->begin();
            try {
                $this->save([
                    'expires_at'      => new Phalcon_RawValue('NULL'),
                    'confirm_success' => 'y',
                ]);
				Assert::noMessages($this);

				$this->expireOlder();

                $person->save([ 'email_confirmed' => 1 ]);
				Assert::noMessages($person);

                $this->getWriteConnection()->commit();

            } catch (Exception $e) {
                $this->getWriteConnection()->rollback();
                throw $e;
            }

        } else {
            throw new Exception('Bad confirmation record: no such user');
        }
    }

	/** @return Phalcon_Model */
	abstract protected function _getUserModel();

}
