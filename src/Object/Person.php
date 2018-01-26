<?php

namespace Demo\Object;

use Demo\Interfaces\ObjectInterface;

class Person extends BaseObject implements ObjectInterface {
	public $name;
	public $surname;
	public $text;

	public function __construct($service, $name, $surname) {
		parent::__construct($service);

		$this->name = $name;
		$this->surname = $surname;

    	// Imiê Nazwisko
	    $text = '%1$s %2$s';

	    $this->text = sprintf(
	        $text,
	        $this->name,
	        $this->surname
	    );
	}

    public function __toString() {
	    $personID = $this->getID();

	    $s = $this->service;
	    $personLanguages = $s->select(
	    	'Demo\\Object\\PersonLanguage',
	    	['=spersonID' => $personID]
	    );

	    $languages = [];
	    foreach ($personLanguages as $personLanguage) {
	    	$languages[] = $personLanguage->getLanguage();
	    }

	    $languages = implode(', ', $languages);

    	// ID. Imiê Nazwisko - (jêzyk1, jêzyk2, ...)
	    $string = '%1$s. %2$s %3$s - (%4$s)';

	    return sprintf(
	        $string,
	        $personID,
	        $this->name,
	        $this->surname,
	    	$languages
	    );
    }

    public function addLanguage($language) {
	    $personID = $this->getID();
	    $languageID = $language->getID();

	    $s = $this->service;

		$personLanguage = new PersonLanguage($s, $personID, $languageID);

		return $s->insert($personLanguage);
    }
}
