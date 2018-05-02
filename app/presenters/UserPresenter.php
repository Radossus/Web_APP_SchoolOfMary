<?php

namespace App; 
        
use Nette,
	Model;
use Mail;


/**
 * Sign in/out presenters.
 */
class UserPresenter extends BasePresenter
{

    private $database;
    public $userManager;
    private $user;

    public function __construct(Nette\Database\Context $database, \Nette\Security\User $user)
    {
       $this->user = $user;
       $this->database = $database;
    }
    
    public function startup() {
        parent::startup();
    }

        public function beforeRender() {
        parent::beforeRender();
        $this->template->fotogalery = Null;
        $this->template->menuItems = Null;
        $this->template->menuFilie = $this->database->table('filie')->where('NOT url ?',NULL)->order('order');
        $this->template->menuItemsUvod = Null;
    }
         
	protected function createComponentSignInForm()
	{
		$form = new Nette\Application\UI\Form;
		$form->addText('username', 'Váš email:')
			->setRequired('Vyplňte vaše přihlašovací jméno.');

		$form->addPassword('password', 'Heslo:')
			->setRequired('Vyplňte vaše heslo.');
                
		$form->addCheckbox('remember', 'Pamatovat si mé přihlášení.');

		$form->addSubmit('send', 'Přihlášení');

		// call method signInFormSucceeded() on success
		$form->onSuccess[] = $this->signInFormSucceeded;
		return $form;
	}
        
        public function signInFormSucceeded($form)
	{
		$values = $form->getValues();

		if ($values->remember) {
			$this->getUser()->setExpiration('14 days', FALSE);
		} else {
			$this->getUser()->setExpiration('20 minutes', TRUE);
		}

		try {
			$this->getUser()->login($values->username, $values->password);
			$this->redirect('Homepage:');

		} catch (Nette\Security\AuthenticationException $e) {
			$form->addError($e->getMessage());
		}
	}
        
        //pro registraci novych uzivatelu z webu bez zasahu admin
        protected function createComponentRegistrationForm()
	{
		$form = new Nette\Application\UI\Form;
		
                $form->addText('jmeno', 'Jméno:')
                        ->setRequired('Vyplňte vaše jméno.');
                
                $form->addText('prijmeni', 'Přijmení:')
                        ->setRequired('Vyplňte vaše prijmeni.');
                
                $form->addText('username', 'Přihlašovací jméno - email:')
			->setRequired('Vyplňte vaš email.')
                        ->addRule(Nette\Application\UI\Form::EMAIL, 'Email není platný');

		$form->addPassword('password', 'Heslo:')
			->setRequired('Vyplňte vaše heslo.')
                        ->addRule(Nette\Application\UI\Form::MIN_LENGTH, 'Heslo musí mít minimálně 6 znaků.', 6);
                
                $form->addPassword('passwordVerify', 'Heslo pro kontrolu:')
                        ->setRequired('Zadejte prosím heslo ještě jednou pro kontrolu')
                        ->addRule(Nette\Application\UI\Form::EQUAL, 'Hesla se neshodují', $form['password']);
                
                $form->addHidden('stupen',999);
                
                $form->addHidden('role','student');
                
                $form->addHidden('filie',999);

		$form->addSubmit('send', 'Registrovat');

		// call method signInFormSucceeded() on success
		$form->onSuccess[] = $this->registrationFormSucceeded;
		return $form;
	}
        
        public function registrationFormSucceeded($form)
	{
		$values = $form->getValues();

		try {
                 //   $this->userManager->add($values);
                    $this->userManager = new \Model\UserManager($this->database);
                    $this->userManager->add($values->jmeno, $values->prijmeni, $values->username, $values->password, $values->stupen, $values->role, $values->filie);
                    $this->flashMessage('Registrace proběhla uspěšně!', 'success');
                    $this->redirect('User:in');

		} catch (\PDOException $e) {
			$form->addError('Toto přihlašovací jméno (email) je již registrováno.');
		}
	}
        
        public function createComponentChangepasswordForm()
        {
            
            $form = new Nette\Application\UI\Form;
            
          //  $form->addHidden($user->getIdentity);
            
            $form->addPassword('passwordOld', 'Původní heslo:')
                 ->setRequired('Vyplňte původní heslo.');
            
            $form->addPassword('passwordNew', 'Nové heslo:')
                 ->setRequired('Vyplňte vaše heslo.')
                 ->addRule(Nette\Application\UI\Form::MIN_LENGTH, 'Heslo musí mít minimálně 6 znaků.', 6);
                
            $form->addPassword('passwordNewVerify', 'Nové heslo pro kontrolu:')
                 ->setRequired('Zadejte prosím heslo ještě jednou pro kontrolu nové heslo.')
                 ->addRule(Nette\Application\UI\Form::EQUAL, 'Hesla se neshodují', $form['passwordNew']);
            
            $form->addSubmit('send', 'Změnit heslo');

		// call method signInFormSucceeded() on success
            $form->onSuccess[] = $this->changepasswordFormSucceeded;
            return $form;
        }
        
        public function changepasswordFormSucceeded($form)
        {
            $values = $form->getValues();  
            $this->userManager = new \Model\UserManager($this->database);

            try {
		$passOldOK = $this->userManager->overeniHesla($this->user->id, $values->passwordOld); 
		
                if($passOldOK)
                {
                    $this->userManager->nastaveniHesla($this->user->id, $values->passwordNew);
                    $this->flashMessage('Změna hesla proběhla úspěšně!', 'success');
                    $this->redirect('User:userinfo');
                }
                
            } catch (Nette\Security\AuthenticationException $e) {
			$form->addError($e->getMessage());
            }
        }
        
        public function renderLostpassword()
        {
            
        }
        
        public function createComponentLostPassForm()
        {
            $form = new Nette\Application\UI\Form;
		$form->addText('username', 'Váš email:')
			->setRequired('Vyplňte vaše přihlašovací jméno.');

		$form->addSubmit('send', 'Zmenit heslo');

		// call method signInFormSucceeded() on success
		$form->onSuccess[] = $this->lostPasswordSucceeded;
		return $form;
        }
        
        public function lostPasswordSucceeded($form)
        {
            $values = $form->getValues();
            $this->userManager = new \Model\UserManager($this->database);
            $newPassword = 'skolazivota12';
            
            try {
                $user = $this->userManager->verifyLoginName($values->username);
                
                $this->userManager->nastaveniHesla($user['id'], $newPassword);
                
                $mail = new \Nette\Mail\Message;
                
                $mail->setFrom('Škola Marie <info@skolamarie.cz>')
                    ->addTo($values->username)
                    ->setSubject('Nastavení hesla')
                    ->setBody("Dobrý den,\n\n Vaše heslo je: $newPassword\n\n Nyní se můžete přihlásit na adrese http:\\\www.skolamarie.cz \n\n Pokud potřebujete další informace, pište na email privoz.farnost@centrum.cz \n\n S pozdravem,\n Škola křesťanského života a evangelizace\n\nP.S.: Na tento email prosím neodpovídejte.");
                
                $mailer = new \Nette\Mail\SendmailMailer();
                $mailer->send($mail);
                
                $this->flashMessage('Na váš email bylo zasláno heslo, přejděte do vašeho emailu a nyní se přihlašte s novým heslem!', 'success');
                $this->redirect('User:in');
            }
            catch (Nette\Security\AuthenticationException $e) {
			$form->addError($e->getMessage());
            }
        }
        
        


        public function renderUserinfo()
        {
            $this->template->userInfo = $this->database->table('user')->get($this->user->id);
        }
        
       //pridani noveho uzivatele adminem
        public function createComponentAddUserForm()
        {
            
                $form = new Nette\Application\UI\Form;
		
                $form->addText('jmeno', 'Jméno:')
                        ->setRequired('Vyplňte jméno.');
                
                $form->addText('prijmeni', 'Přijmení:')
                        ->setRequired('Vyplňte prijmeni.');
                
                $form->addText('username', 'Přihlašovací jméno - email:')
			->setRequired('Vyplňte email.')
                        ->addRule(Nette\Application\UI\Form::EMAIL, 'Email není platný');

		$form->addPassword('password', 'Heslo:')
			->setRequired('Vyplňte vaše heslo.')
                        ->addRule(Nette\Application\UI\Form::MIN_LENGTH, 'Heslo musí mít minimálně 6 znaků.', 6);
                
                $form->addPassword('passwordVerify', 'Heslo pro kontrolu:')
                        ->setRequired('Zadejte prosím heslo ještě jednou pro kontrolu')
                        ->addRule(Nette\Application\UI\Form::EQUAL, 'Hesla se neshodují', $form['password']);
                
                $stupen = $this->database->table('stupen')->fetchPairs('stupen_id','stupen');
                
                
                $form->addSelect('stupen', 'Stupeň:', $stupen)->setDefaultValue(100)
                        ->setRequired('Zadejte prosím stupeň');
                 
                $form->addText('role','Role:')->setDefaultValue('student')
                        ->setRequired('Zadejte prosím roli');
                
                $form->addSelect('filie', 'Škola v:', $this->database->table('filie')->fetchPairs('filie_id', 'nazev'))
                        ->setRequired('Zadejte pobočku');
                
                $form->addSubmit('send', 'Uložit');

		$form->onSuccess[] = $this->addUserFormSucceeded;
		return $form;
        }
        
        public function addUserFormSucceeded($form)
	{
		$values = $form->getValues();
                 
                   try {
                        $this->userManager = new \Model\UserManager($this->database);
                        $this->userManager->add($values->jmeno, $values->prijmeni, $values->username, $values->password, $values->stupen, $values->role, $values->filie);
                        $this->flashMessage('Účastník byl úspěšně vložen!', 'success');
                        $this->redirect('Adminskola:users');

                    } catch (\PDOException $e) {
                            $form->addError('Toto přihlašovací jméno (email) je již registrováno.');
                    }
                
	}
        
        public function createComponentEditUserForm()
        {
                $userId = $this->getParameter('url');
                $user = $this->database->table('user')->get($userId)->toArray();
            
                $form = new Nette\Application\UI\Form;
		
                $form->addHidden('id')->setDefaultValue($userId);
                        
                $form->addText('jmeno', 'Jméno:')->setDefaultValue($user['jmeno'])
                        ->setRequired('Vyplňte jméno.');
                
                $form->addText('prijmeni', 'Přijmení:')->setDefaultValue($user['prijmeni'])
                        ->setRequired('Vyplňte prijmeni.');
                
                $form->addText('username', 'Přihlašovací jméno - email:')->setDefaultValue($user['username'])
			->setRequired('Vyplňte email.')
                        ->addRule(Nette\Application\UI\Form::EMAIL, 'Email není platný');
                
                $stupen = $this->database->table('stupen')->fetchPairs('stupen_id','stupen');
                
                
                $form->addSelect('stupen_id', 'Stupeň:', $stupen)->setDefaultValue($user['stupen_id'])
                        ->setRequired('Zadejte prosím stupeň');
                 
                $form->addText('role','Role:')->setDefaultValue($user['role'])
                        ->setRequired('Zadejte prosím roli');
                
                $form->addSelect('filie_id', 'Škola v:', $this->database->table('filie')->fetchPairs('filie_id', 'nazev'))->setDefaultValue($user['filie_id'])
                        ->setRequired('Zadejte pobočku');
                
                $form->addSubmit('send', 'Uložit');

		$form->onSuccess[] = $this->editUserFormSucceeded;
		return $form;
        }
        
       public function editUserFormSucceeded($form)
       {
           
           $userId = $this->getParameter('url');
            $user = $this->database->table('user')->get($userId);
            $this->userManager = new \Model\UserManager($this->database);
            $this->userManager->edit($form);
            $this->flashMessage('Účastník byl úspěšně editován!', 'success');
            $this->redirect('Adminskola:users');

       }
       
                
        public function actionEditUser($url)
        {
            if (!$this->user->isLoggedIn()) {
                $this->flashMessage('Pro editaci účastníků musíte být přihlášen.');
                $this->redirect('User:in');
            } 
            
            $user = $this->database->table('user')->get($url);
            $this->template->userId = $url;

            if(!$user){
                $this->error('Účastník nebyl nalezen.');
            }            
        }
         

        public function actionOut()
	{
		$this->getUser()->logout();
		$this->flashMessage('Byli jste odhlášeni.');
		$this->redirect('in');
                unset($this->namespace->obedy);
                unset($this->namespace->terminID);
	}
        
        public function isAdmin(){
            $userRole = $this->database->table('user')->select('role')->where('username',$this->user->identity);
            dump($userRole);
        }
        

}
