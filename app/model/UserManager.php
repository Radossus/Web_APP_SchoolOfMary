<?php

namespace Model;

use Nette,
	Nette\Utils\Strings;


/**
 * Users management.
 */
class UserManager extends Nette\Object implements Nette\Security\IAuthenticator
{
	const
		TABLE_NAME = 'user',
		COLUMN_ID = 'id',
                COLUMN_JMENO = 'jmeno',
                COLUMN_PRIJMENI = 'prijmeni',
		COLUMN_NAME = 'username',
		COLUMN_PASSWORD = 'password',
                COLUMN_STUPEN_ID = 'stupen_id',
		COLUMN_ROLE = 'role',
                COLUMN_FILIE_ID = 'filie_id',
		PASSWORD_MAX_LENGTH = 4096;


	/** @var Nette\Database\Context */
	private $database;


	public function __construct(Nette\Database\Context $database)
	{
		$this->database = $database;
	}


	/**
	 * Performs an authentication.
	 * @return Nette\Security\Identity
	 * @throws Nette\Security\AuthenticationException
	 */
	public function authenticate(array $credentials)
	{
		list($username, $password) = $credentials;
		$row = $this->database->table(self::TABLE_NAME)->where(self::COLUMN_NAME, $username)->fetch();

		if (!$row) {
			throw new Nette\Security\AuthenticationException('Chybné uživatelské jméno.', self::IDENTITY_NOT_FOUND);

		} elseif (!self::verifyPassword($password, $row[self::COLUMN_PASSWORD])) {
			throw new Nette\Security\AuthenticationException('Chybné uživatelské heslo.', self::INVALID_CREDENTIAL);

		} elseif (PHP_VERSION_ID >= 50307 && substr($row[self::COLUMN_PASSWORD], 0, 3) === '$2a') {
			$row->update(array(
				self::COLUMN_PASSWORD => self::hashPassword($password),
			));
		}

		$arr = $row->toArray();
		unset($arr[self::COLUMN_PASSWORD]);
		return new Nette\Security\Identity($row[self::COLUMN_ID], $row[self::COLUMN_ROLE], $arr);
	}


	/**
	 * Adds new user.
	 * @param  string
	 * @param  string
	 * @return void
	 */
	public function add($jmeno, $prijmeni, $username, $password, $stupen, $role, $filie)
	{
	
                try {
                        $insert = $this->database->table(self::TABLE_NAME)->insert(array(
                        self::COLUMN_JMENO => $jmeno,
                        self::COLUMN_PRIJMENI => $prijmeni,
                        self::COLUMN_NAME => $username,
			self::COLUMN_PASSWORD => self::hashPassword($password),
                        self::COLUMN_STUPEN_ID => $stupen,
                        self::COLUMN_ROLE => $role,
                        self::COLUMN_FILIE_ID => $filie,
		));
                } catch (\DOException $ex) {
                  //  throw new \PDOException('Toto přihlašovací jméno je již registrováno');
                }
                
                
	}
        
        public function edit($userForm)
        {
                $u = $userForm->getValues();
               // echo $u['prijmeni'];
                //echo $u['id'];
                try {
                        $update = $this->database->table(self::TABLE_NAME)->where('id =', $u['id'])->update(array(
                        self::COLUMN_JMENO => $u['jmeno'],
                        self::COLUMN_PRIJMENI => $u['prijmeni'],
                        self::COLUMN_NAME => $u['username'],
                        self::COLUMN_STUPEN_ID => $u['stupen_id'],
                        self::COLUMN_ROLE => $u['role'],
                        self::COLUMN_FILIE_ID => $u['filie_id']  
		));
                      // dump($user->toArray());
                } catch (\DOException $ex) {
                  //  throw new \PDOException('Toto přihlašovací jméno je již registrováno');
                }
        }

                /**
	 * Computes salted password hash.
	 * @param  string
	 * @return string
	 */
	public static function hashPassword($password, $options = NULL)
	{
		/*if ($password === Strings::upper($password)) { // perhaps caps lock is on
			$password = Strings::lower($password);
		}
		$password = substr($password, 0, self::PASSWORD_MAX_LENGTH);
		$options = $options ?: implode('$', array(
			'algo' => PHP_VERSION_ID < 50307 ? '$2a' : '$2y', // blowfish
			'cost' => '07',
			'salt' => Strings::random(22),
		)); 
		return crypt($password, $options);
                 */
            return md5($password);
                 
	}

        
        public function overeniHesla($userId, $password)
        {
            $user = $this->database->table(self::TABLE_NAME)->get($userId);
            
            if($user->password == self::hashPassword($password))
            {
                return true;
            }
            else
            {
                throw new Nette\Security\AuthenticationException('Chybné původní heslo.', self::INVALID_CREDENTIAL);
            }
        }
        
        public function nastaveniHesla($userId, $password)
        {            
            try{
                $this->database->table(self::TABLE_NAME)->where('id',$userId)->update(array(self::COLUMN_PASSWORD=>self::hashPassword($password)));
            }
            catch (Nette\DI\Extensions $e)
            {
                $form->addError($e->getMessage());
            }
        }

        

        /**
	 * Verifies that a password matches a hash.
	 * @return bool
	 */
	public static function verifyPassword($password, $hash)
	{
            return self::hashPassword($password, $hash) === $hash
			|| (PHP_VERSION_ID >= 50307 && substr($hash, 0, 3) === '$2a' && self::hashPassword($password, $tmp = '$2x' . substr($hash, 3)) === $tmp);
	}
        
        public function verifyLoginName($username)
        {
            $user = $this->database->table(self::TABLE_NAME)->where('username',$username)->fetch();   
            
            if(!$user)
            {
                throw new Nette\Security\AuthenticationException('Tento email není registrovaný. Zkuste si vzpomenout na správný.', self::IDENTITY_NOT_FOUND);
            }
            
            return $user;
        }
        

}
