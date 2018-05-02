<?php

namespace App; 
        
use Nette,
	Model;



/**
 * Sign in/out presenters.
 */
class SkolaPresenter extends BasePresenter
{
    private $database;
    private $user;
    private $namespace;
    
    /**
    * @var array
    * @persistent
    */
    public $dataPrihlaseniTermin = array();

    public function startup() {
        parent::startup();
        /*
        if (!($this instanceof LoginPresenter || $this instanceof
            SignupPresenter)) {
            $session = $this->getSession(__CLASS__);
            $session->last = $this->getAction(TRUE); 
	}*/
    }

    public function __construct(Nette\Database\Context $database, \Nette\Security\User $user)
    {
       $this->user = $user;
       $this->database = $database;
       $this->namespace = \Nette\Environment::getSession('skola');
    }
    
    public function actionDefault()
    {
        $this->redirect("Skola:termin");
    }


    public function beforeRender() {
        parent::beforeRender();
        $this->template->fotogalery = Null;
        $this->template->menuItems = Null;
        $this->template->menuItemsUvod = Null;
        $this->template->menuFilie = $this->database->table('filie')->where('NOT url ?',NULL)->order('order');
    }
    
    public function renderTermin()
    {
        $today = date("Y-m-d");
        $registrace = FALSE;
        $povolenaRegistrace = FALSE;
        
        $result = $this->database->table('termin')->where("datum_konec >= ?", $today)->fetchall();
        
       
      //  dump($result);
        
        if($result){
            foreach ($result as $key => $value) {
                $this->namespace->terminID = $value->termin_id;
                if((date_format($value->registrace_od, "Y-m-d") <= $today) AND (date_format($value->registrace_do, "Y-m-d") >= $today)){
                    $povolenaRegistrace = TRUE;
                    if($this->overeniPrihlaseniTermin($this->user->id, $key))
                    {
                        $registrace[$key] = TRUE;
                    }
                    else {
                        $registrace[$key] = FALSE;
                    }
                }else {
                        $povolenaRegistrace = FALSE;
                }
            }
        }
            
        $this->template->povolenaRegistrace = $povolenaRegistrace;
        $this->template->registrace = $registrace;
        $this->template->today = $today;
        $this->template->terminy = $result;
    }
    
    public function renderPrihlasenitermin($id)
    {   
        $today = date("Y-m-d");
        $result = $this->database->table('termin')->get($id);
        $this->template->termin = $result;
        
        $jizRegistrovan = $this->overeniPrihlaseniTermin($this->user->id,$id);
          
        $resultObedy = $this->database->table('obed')->fetchAll();
       // echo count($resultObedy);
        $this->template->obedy = $resultObedy;
        $this->template->jizRegistrovan = $jizRegistrovan;
        
        if((date_format($result->registrace_od, "Y-m-d") <= $today) AND ($result->registrace_do >= $today)){
            $this->template->registrace = TRUE;
        }
        else {
            $this->template->registrace = FALSE;
        }
  
    }
    
    protected function createComponentObedyForm()
    {
       // $this->userManager = new \Model\UserManager($this->database);
       $form = new Nette\Application\UI\Form;
        
       $obedy = $this->database->table("obed")->select("id,den,datum,cena,pocet_max,poznamka")->fetchall();
       
       $termin = $this->getParam('id');
       
       $form->addHidden('termin', $termin);
       $form->addHidden('user_id', $this->user->id);
        
     //  dump($obedy);
              
       foreach ($obedy as $key => $obed) {
          // $form->addCheckbox($obed->id,$obed->den." ".$obed->cena.",- Kč");
           if($obed->pocet_max)
           {    
               $currentNoclehPocet = $this->database->table("prihlaseni_obed")->where("termin_id",$termin)->where("obed_id",$obed->id)->count();
               if($currentNoclehPocet < $obed->pocet_max)
               {
                   $arrayObedy[$obed->id] = $obed->den." ".$obed->cena.",- Kč "." $obed->poznamka";
               }
           }
           else{
               $arrayObedy[$obed->id] = $obed->den." ".$obed->cena.",- Kč "." $obed->poznamka"; 
           }
       }
        
        $form->addCheckboxList("obedy", "", $arrayObedy);
        $form->addSubmit('send', 'Pokračovat v přihlášení');
        $form->onSuccess[] = $this->obedyFormSucceeded;
        return $form;
    }
    
    public function obedyFormSucceeded($form)
    {
        $data = $form->getValues();
      //  \Nette\Diagnostics\Debugger::dump($data);
        $this->namespace->terminID = $data['termin'];
        $this->namespace->obedy = $data['obedy'];
        $this->redirect("Skola:potvrzeniprihlaseni");
    }
    
    public function renderPotvrzeniprihlaseni()
    {
        $termin = $this->database->table('termin')->get($this->namespace->terminID);
        $obedyFromForm = $this->namespace->obedy;
        $resultObedy = $this->database->table('obed')->where('id',$obedyFromForm)->fetchAll();
        $jizRegistrovan = $this->overeniPrihlaseniTermin($this->user->id,$this->namespace->terminID);
                
        $cena = $termin->poplatek;
        
        if(!$resultObedy)
        {
            $resultObedy = NULL;
        }
        else {
            foreach ($resultObedy as $key => $value) {
                $cena = $cena + $value->cena;
            } 
        }
        
        $this->template->jizRegistrovan = $jizRegistrovan;
        $this->namespace->cena = $cena;
        $this->template->termin = $termin;
        $this->template->obedy = $resultObedy;
        $this->template->cena = $cena;
        $this->template->form = $this['prihlaseniFinal'];
    }
    
    protected function createComponentPrihlaseniFinal()
    {
        $form = new Nette\Application\UI\Form;
        
      //  $form->addSubmit('back', 'Zpět na předchozi stránku');
        $form->addSubmit('send', 'Odeslat přihlášku');
        $form->onSuccess[] = $this->prihlaseniFinalFormSucceeded;
        return $form;
    }
    
    public function prihlaseniFinalFormSucceeded($form)
    {
        $this->namespace->terminID;
        $this->database->table('prihlaseni_termin')->insert(array(
            'user_id' => $this->user->id,
            'termin_id' => $this->namespace->terminID,
        ));
        
        if($this->namespace->obedy)
        {
            foreach ($this->namespace->obedy as $obedID)
            {
                $this->database->table("prihlaseni_obed")->insert(array(
                    'user_id' => $this->user->identity->getId(),
                    'obed_id' => $obedID,
                    'termin_id' => $this->namespace->terminID,
                    'jmeno' => $this->user->identity->jmeno." ".$this->user->identity->prijmeni,
                ));
            }
        }
        
      //  $this->flashMessage("Byl jste úspěšně přihlášen na zvolený termín.", "success");
        $this->redirect("Skola:prihlasenifinal");
        
      // unset($this->namespace->obedy);
      //  unset($this->namespace->terminID);
    }
    
    public function renderPrihlasenifinal()
    {
        $termin = $this->database->table('termin')->where('termin_id',1)->fetch();

        
        $this->template->termin = $termin;
        $this->template->cena = $this->namespace->cena;
        
    }


//overuje zda je uzivatel jiz prihlaseny na nejaky termin 
    public function overeniPrihlaseniTermin($user_id, $termin_id)
    {
        $result = $this->database->table('prihlaseni_termin')->where(array(
            'user_id' => $user_id ,
            'termin_id' => $termin_id
        ))->fetch();
        
        return $result;
    }
    
    //pouze pro letni skolu
    /*
    protected function createComponentLetniSkola()
    {
        $form = new Nette\Application\UI\Form;
        
        $form->addTextArea('poznamka', '',60,4);
        $form->addSubmit('send', 'Odeslat závaznou přihlášku');
        $form->onSuccess[] = $this->prihlaseniLetniSkolaFormSucceeded;
        return $form;
    }
    
    //pouze pro letni skolu
    public function prihlaseniLetniSkolaFormSucceeded($form)
    {  
        $values = $form->getValues();
        $this->namespace->terminID;
        $this->database->table('prihlaseni_termin')->insert(array(
            'user_id' => $this->user->id,
            'termin_id' => $this->namespace->terminID,
            'user_poznamka' => $values['poznamka']
        ));
        $this->redirect("Skola:prihlasenifinal");
    }
     * */
     
    
    
    
}
