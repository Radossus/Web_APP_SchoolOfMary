<?php

namespace App;

use Nette,
	Model;


/**
 * Homepage presenter.
 */
class HomepagePresenter extends BasePresenter
{
        /** @var Nette\Database\Context */
    private $database;

    public function __construct(Nette\Database\Context $database)
    {
       $this->database = $database;
    }   
    
    public function beforeRender() {
        parent::beforeRender();
        $this->template->fotogalery = null;
        $this->template->menuItems = Null;
        $this->template->menuItemsUvod = $this->database->table('stranky')->where('filie_id=?',999)->order('poradi');
        $this->template->menuFilie = $this->database->table('filie')->where('NOT url ?',NULL)->order('order');
    }
    
    public function renderDefault()
    {
       // $this->template->novinky = $this->database->table('novinky')->where('zobrazit_do ? || zobrazit_do >= ? ', NULL, date('Y-m-d'))->where('zobrazit = ?',1)->order('datum DESC');
        //$this->template->novinky = $this->database->table('novinky')->where('zobrazit = ?', 1)->order('datum DESC');  
        $this->template->text = $this->database->table('stranky')->where('id',1)->fetch();
    }
    
    public function renderSkola($url,$page_url=NULL)
    {
        $filie = $this->database->table('stranky')->where('filie.url=?',$url);
        $this->template->menuItemsUvod = $this->database->table('stranky')->where('filie_id=?',999)->where('onlyLogged',1)->order('poradi');;
        $this->template->menuItems = $this->database->table('stranky')->where('filie.url=?',$url)->order('poradi');
        
        if($url != 'oasis'){
                        $this->template->novinkaAll = $this->database->table('novinky')
                                ->where('zobrazit_do ? || zobrazit_do >= ? ', '0000-00-00', date('Y-m-d'))->where('zobrazit = ?',1)->where('filie_id',999);
        }
        else{ $this->template->novinkaAll = NULL;}
        
    $this->template->novinky = $this->database->table('novinky')->where('zobrazit_do ? || zobrazit_do >= ? ', '0000-00-00', date('Y-m-d'))->where('zobrazit = ?',1)->where('filie.url =?',$url)->order('datum DESC');
        $this->template->stranka = NULL;
        
        foreach ($filie as $key => $value){
            $filie_id = $value->filie_id;
        } 
 
        $this->template->fotogalery = $this->database->table('photo_category')->where('filie_id=?',$filie_id)->where('public',1)->fetchAll();
      //  dump($this->template->fotogalery);
        if($page_url)
        {
            $page = $this->database->table('stranky')->where("url", $page_url)->fetch();
            if(!$page){
               $this->error('Stránka nebyla nalezena.'); 
            }
            else {
                $this->template->stranka = $page;
            }
        }

    }


    public function renderStranka($url, $filie)
    {
        $page = $this->database->table('stranky')->where("url", $url)->fetch();
        if(!$page){
           $this->error('Stránka nebyla nalezena.'); 
        }
        else {
            $this->template->stranka = $page;
        }
        
    }
    
    public function renderNovinka($url)
    {
        $this->template->novinka = $this->database->table('novinky')->get($url);
    }
  
}
