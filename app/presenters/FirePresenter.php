<?php

namespace App;

use Nette,
	Model;

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of FirePresenter
 *
 * @author radoss
 */
class FirePresenter extends BasePresenter{
    //put your code here
    private $database;
    private $user;
    
    public function __construct(Nette\Database\Context $database, \Nette\Security\User $user) {
        parent::__construct($database);  
        $this->database = $database;
        $this->user = $user;
    }
    
    public function startup() {
        parent::startup();
        $this->template->fotogalery = null;
        $this->template->menuItems = Null;
      
        if($this->user->isInRole('student')){
           $this->redirect('User:in');
        }
        $this->template->menuItems = Null;
        $this->template->menuItemsUvod = Null;
        $this->template->menuFilie = $this->database->table('filie')->where('NOT url ?',NULL)->order('order');
    }
    
    public function renderDefault()
    {
        //$this->template->novinky = $this->database->table('novinky')->order('datum DESC');
    }
    
    protected function createComponentFireForm()
    {
       // $this->userManager = new \Model\UserManager($this->database);
       $form = new Nette\Application\UI\Form;
       
       //$form->addHidden('termin', $termin);
       $form->addText('jmeno', 'Jméno: ')->setRequired();
       $form->addText('prijmeni', 'Přijmení: ')->setRequired();
       $form->addText('email', 'Email: ')->setRequired();
               //->addRule(Form::EMAIL,"vložte správný email");
       $volbaObed = array(
            'ANO' => 'ANO',
            'NE' => 'NE',
        );
       $form->addRadioList('obed', 'Přihlásit oběd:', $volbaObed)->setDefaultValue('ANO');
       $form->addSubmit('send', 'Přihlásit se');
       $form->onSuccess[] = $this->fireFormSucceeded;
       return $form;
    }
    
    public function fireFormSucceeded($form)
    {
        $values = $form->getValues();
        
        $this->database->table('fire')->insert(array(
            'jmeno' => $values->jmeno,
            'prijmeni' => $values->prijmeni,
            'email' => $values->email,
            'obed' => $values->obed,
        ));

        $this->flashMessage('registrace proběhla v pořádku.', 'success');
        $this->redirect('this');
    }
    
    public function renderRegistration(){
        $this->template->registration = $this->database->table('fire')->order('registrace');
    }
}
