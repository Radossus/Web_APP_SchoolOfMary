<?php

namespace App; 
        
use Nette,
	Model;


/**
 * Sign in/out presenters.
 */
class FotogaleriePresenter extends BasePresenter
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

    public function beforeRender($filie = NULL) {
        parent::beforeRender();
        
        $this->template->menuItems = Null;
       // $this->template->menuItemsUvod = $this->database->table('stranky')->where('filie_id=?',999)->order('poradi');
        $this->template->menuFilie = $this->database->table('filie')->where('NOT url ?',NULL)->order('order');
        $this->template->fotogalery = $this->database->table('photo_category')->order('date')->fetchAll(); 
    }
    
    public function renderDefault($filie = NULL)
    {   
        $this->template->menuFilie = $this->database->table('filie')->where('NOT url ?',NULL)->order('order');
        $this->template->menuItems = Null;
        $isPublic = 1;
        if($this->user->isLoggedIn()){
            $isPublic = 0;
        }
        
        if($filie){
           $this->template->menuItemsUvod = $this->database->table('stranky')->where('filie_id=?',$filie)->order('poradi'); 
           $this->template->fotogalery = $this->database->table('photo_category')->where('filie_id=?',$filie)->where('public',$isPublic)->fetchAll();
           $this->template->fotogaleries = $this->database->table('photo_category')->where('filie_id',$filie)->where('public',$isPublic)->fetchAll();
        }
        else {
            $this->template->menuItemsUvod = null;
            $this->template->fotogalery = null;
            $this->template->fotogaleries = $this->database->table('photo_category')->fetchAll();
        }
        
        
       // $this->template->menuItems = $this->database->table('stranky')->where('filie_id=?',$filie)->order('poradi');
    }

    
    public function renderAlbum($filie,$url){
        $this->template->fotogalery = $this->database->table('photo_category')->where('filie_id=?',$filie)->where('public',1)->fetchAll();;
        //$this->template->menuItemsUvod = $this->database->table('stranky')->where('filie_id=?',999)->order('poradi');
        $this->template->menuItemsUvod = $this->database->table('stranky')->where('filie_id=?',$filie)->order('poradi');
        $album = $this->database->table('photo_category')->get($url);
        $photos = $this->database->table('photo_gallery')->where('photo_category', $url);
        
        if(!$album)
        {
          $this->error('Stránka nebyla nalezena.'); 
        }
        else
        {
          $this->template->album = $album;
          $this->template->fotos = $photos;
        }  
    }
         
    

}
