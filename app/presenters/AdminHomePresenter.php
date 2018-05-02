<?php

namespace App; 
        
use Nette,
	Model;

use Application,
                UI;
/*
use Vodacek,
            Forms,
                Controls,
                    DateInput;*/


/**
 * Sign in/out presenters.
 */
class AdminHomePresenter extends BasePresenter
{
    private $database;
    private $user;
    
    public function __construct(Nette\Database\Context $database, \Nette\Security\User $user) {
        parent::__construct($database);  
        $this->database = $database;
        $this->user = $user;
    }
    
    public function startup() {
        parent::startup();
        $this->template->fotogalery = Null;
        if($this->user->isInRole('student')){
           $this->redirect('User:in');
        }
        $this->template->menuItems = Null;
        $this->template->menuItemsUvod = Null;
        $this->template->menuFilie = $this->database->table('filie')->where('NOT url ?',NULL)->order('order');
    }
        
    public function renderDefault()
    {
        
    }
    
    public function renderNovinky()
    {
        
        if($this->user->isInRole('adminSuper'))
        {
            $news = $this->database->table('novinky')->order('datum')->fetchAll();
        }
        else{
            $news = $this->database->table('novinky')->where('filie_id',$this->user->identity->filie_id)->fetchAll(); 
        }
        $this->template->news = $news;
       
    }
    
    public function createComponentAddNewForm()
    {
        $form = new Nette\Application\UI\Form;
        $filie = $this->database->table('filie')->fetchPairs('filie_id', 'nazev');
        
        
        $form->addText('nadpis', 'Nadpis:',70)->setRequired('Vyplňte nadpis.');
        
        $form->addText('datum','Datum:')->setRequired('Vyplňte datum.')->setAttribute('class','datepicker');
        
        $form->addText('zobrazit_do','Zobraz do:')->setAttribute('class','datepicker');
        
        if($this->user->isInRole('adminSuper')){
            $userFilie = $this->database->table('filie')->fetchPairs('filie_id', 'nazev');
        }
        else{
            $userFilie = $this->getUserFilieID($this->user->id);
        }
        
        $form->addSelect('filie_id', 'škola: ',$userFilie);
          
        $form->addTextArea('notifikace', 'Notifikace:', 70,18)
                ->setRequired('Vyplňte notifikaci.');
        //$form->addTextArea('obsah','Obsah:',70,18);
        $form->addCheckbox('zobrazit','Publikovat')->setDefaultValue(TRUE);

        $form->addSubmit('send', 'Uložit');

	$form->onSuccess[] = $this->addNovinkaFormSucceeded;

        return $form;                
    }
    
    public function createComponentEditNewForm()
    {
        $form = new Nette\Application\UI\Form;
        $filie = $this->database->table('filie')->fetchPairs('filie_id', 'nazev');
        
        $form->addText('nadpis', 'Nadpis:',70)
                        ->setRequired('Vyplňte nadpis.');
         $form->addText('datum','Datum:')->setRequired('Vyplňte datum.')->setAttribute('class','datepicker');
       // $form->addText('datum','Datum:',DateInput::TYPE_DATETIME)->setRequired('Vyplňte datum.');
        $form->addText('zobrazit_do','Zobraz do:')->setAttribute('class','datepicker');
        
        if($this->user->isInRole('adminSuper')){
            $userFilie = $this->database->table('filie')->fetchPairs('filie_id', 'nazev');
        }
        else{
            $userFilie = $this->getUserFilieID($this->user->id);
        }
        $form->addSelect('filie_id', 'škola: ',$userFilie);
        
        $form->addTextArea('notifikace', 'Notifikace:', 70,18)
                ->setRequired('Vyplňte notifikaci.');
     //   $form->addTextArea('obsah','Obsah:',70,18);
        $form->addCheckbox('zobrazit','Publikovat')->setDefaultValue(TRUE);

        $form->addSubmit('send', 'Uložit');

	$form->onSuccess[] = $this->addNovinkaFormSucceeded;

        return $form;                
    }
    
    public function addNovinkaFormSucceeded($form)
    {
        $values = $form->getValues();
        
       // \Nette\Diagnostics\Debugger::dump($values);
        
        $postId = $this->getParameter('id');
        
       /* foreach ($values as $key => $value)
        {
            
              if($key == 'datum')
              {
                  $value = new \Nette\DateTime($value);
              }
              if($key == 'zobrazit_do')
              {    
                  if($value != ""){
                      $value = new \Nette\DateTime($value);
                  }  else {
                      $value = NULL;
                  }
              }
            $values1[$key] = $value; 
        }*/
        
        if($postId)
        {
            $post = $this->database->table('novinky')->get($postId);
            $post->update($values);
            $this->flashMessage("Příspěvek byl úspěšně editován.", 'success');
            $this->redirect('novinky');
        }else{
            $post = $this->database->table('novinky')->insert($values);
            $this->flashMessage("Příspěvek byl úspěšně přidán.", 'success');
            $this->redirect('novinky');
        }                
    }
    
    public function actionDelete($id)
    {
        $this->database->table('novinky')->where('id',$id)->delete();
        $this->redirect('novinky'); 
    }
    
    public function actionEditNovinka($id)
    {
        $post = $this->database->table('novinky')->get($id);
      //  \Nette\Diagnostics\Debugger::dump($post->toArray());
        
        foreach($post->toArray() as $key => $value)
        {
            if(($key == 'datum') || ($key == 'zobrazit_do'))
            {
                if($value == '-0001-11-30 00:00:00')
                {
                    $value = null;
                }
                else {
                    $value = $value->format('Y-m-d');  
                }
            }
            

            $values1[$key] = $value;
        }
        if (!$post) {
            $this->error('Příspěvek nebyl nalezen');
        }
        $this['editNewForm']->setDefaults($values1);
    }
   
    
    public function getUserFilieID($userId)
    {
        $userCurrentFilie = $this->database->table('user')->get($userId);
        return array($userCurrentFilie->filie_id => $userCurrentFilie->filie->nazev);
    }
    
 }

