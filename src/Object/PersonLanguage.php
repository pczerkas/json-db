<?php

namespace Demo\Object;

use Demo\Interfaces\ObjectInterface;

class PersonLanguage extends BaseObject implements ObjectInterface {
	public $personID;
	public $languageID;

	public function __construct($service, $personID, $languageID) {
		parent::__construct($service);

		$this->personID = $personID;
		$this->languageID = $languageID;
	}

    public function __toString() {
	    $string = 'Person ID:%1$s, Language ID: %1$s';

	    return sprintf(
	        $string,
			$this->personID,
			$this->languageID
	    );
    }

	public function getID() {
		$id = '%1$s-%2$s';

	    return sprintf(
	        $id,
	        mb_strtolower($this->personID, 'UTF-8'),
	        mb_strtolower($this->languageID, 'UTF-8')
	    );
	}

    public function getPerson() {
	    $pID = $this->personID;

	    $s = $this->service;

	    return $s->select(
	    	'Demo\\Object\\Person',
	    	['=sid' => $pID]
	    )[0];
    }

    public function getLanguage() {
	    $lID = $this->languageID;

	    $s = $this->service;

	    return $s->select(
	    	'Demo\\Object\\Language',
	    	['=sid' => $lID]
	    )[0];
    }
}
