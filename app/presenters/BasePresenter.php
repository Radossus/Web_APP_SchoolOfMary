<?php

namespace App;

use Nette,
	Model;


/**
 * Base presenter for all application presenters.
 */
class BasePresenter extends Nette\Application\UI\Presenter
{
   private $database;
   private $user;
   

    public function __construct(Nette\Database\Context $database)
    {
       $this->database = $database;
    }
   
}
