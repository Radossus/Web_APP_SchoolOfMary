<?php

namespace App; 
        
use Nette,
	Model;

use Application,
                UI;

/**
 * Sign in/out presenters.
 */
class AdminskolaPresenter extends BasePresenter
{
    
   private $database;
    private $user;
    
    public function __construct(Nette\Database\Context $database, \Nette\Security\User $user) {
        parent::__construct($database);  
        $this->database = $database;
        $this->user = $user;
    }
    

    
   public function beforeRender() {
    parent::beforeRender();
        $this->template->fotogalery = Null;
        $this->template->menuItems = Null;
        $this->template->menuItemsUvod = Null;
        $this->template->menuFilie = $this->database->table('filie')->where('NOT url ?',NULL)->order('order');
   }
    
    public function renderUsers()
    {
        $this->template->users = $this->database->table('user')->fetchAll();
    }
    
    public function renderTerminy()
    {
        $this->template->terminy = $this->database->table('termin')->order('datum_zacatek DESC')->fetchAll();
    }
    
    public function renderPrihlaseniuzivatele($id)
    {
        $pocetObedyByDen = null;
        $this->template->prihlaseniUzivatele = $this->database->table('prihlaseni_termin')->where('termin_id=',$id)->order('datum_prihlaseni');
        $obedIDinTable = $this->database->table('prihlaseni_obed')->where('termin_id=',$id)->group('obed_id')->fetchAll();
        
   
        
        if($obedIDinTable)
        {
            foreach ($obedIDinTable as $key => $obed)
            {
                $pocetObedyByDen[$key] = $this->database->table('prihlaseni_obed')->where('obed_id',$obed->obed_id)->where('termin_id',$id)->fetchAll();         
            }
        }
                
        $this->template->prihlaseniObed = $pocetObedyByDen;
    }
    
    public function renderObeducastnici($terminId, $obedId)
    {
        
        $obedyByDen = $this->database->table('prihlaseni_obed')->where('termin_id',$terminId)->where('obed_id',$obedId)->order('datum_prihlaseni')->fetchAll();
        $this->template->seznamUcastniku = $obedyByDen;
    }
    
    public function createComponentAddTerminForm($name) {
        $form = new Nette\Application\UI\Form;
                
        $vychoziDatum = date("d.m.Y");
        
        $form->addText('datum_zacatek','Datum od:')
                ->setRequired('Vyplňte Datum od.')->setDefaultValue($vychoziDatum);
        
        $form->addText('datum_konec','Datum do:')
                ->setRequired('Vyplňte Datum do.')->setDefaultValue($vychoziDatum);
        
        $form->addText('registrace_od','Registrace povolena od:')
                ->setRequired('Vyplňte Registrace povolena od.')->setDefaultValue($vychoziDatum);
        
        $form->addText('registrace_do','Registrace povolena do:')
                ->setRequired('Vyplňte Registrace povolena do.')->setDefaultValue($vychoziDatum);
        
        $form->addText('poplatek','Poplatek:');
        
        $form->addTextArea('misto_konani','Místo konání: ',50,8);
        
        $userCurrentFilie = $this->database->table('user')->get($this->user->id);

        $form->addHidden('filie_id', $userCurrentFilie['filie_id']);
        
        $form->addTextArea('poznamka','Poznámka: ',50,8);
        
        $form->addSubmit('send', 'Uložit');

	$form->onSuccess[] = array($this, 'addTerminFormSucceeded');

        return $form; 
    }
    
    public function addTerminFormSucceeded($form)
    {
       // $terminId = $this->getParameter('terminId');
        
        $values = $form->getValues();
        
        $this->database->table('termin')->insert($values);
        
        $this->flashMessage('Termín byl vypsán.', 'success');
        $this->redirect('terminy');
    }
    
    public function actionDeleteTermin($id)
    {
        $this->database->table('termin')->where('termin_id',$id)->delete();
        $this->redirect('terminy'); 
    }
    
    public function actionEditTermin($id)
    {
        $post = $this->database->table('termin')->get($id);
        
        if (!$post) {
            $this->error('Příspěvek nebyl nalezen');
        }
    }

}