<?php

namespace Demo;

use Demo\Object\Language;
use Demo\Object\Person;
use Demo\Object\PersonLanguage;

class Application {
	private $service;

	const COMMANDS = [
	    'list' => 'getPersonList',
	    'find' => 'filterPersonsByText',
	    'languages' => 'filterPersonsByLanguage',
	    'addPerson' => 'addPerson',
	    'removePerson' => 'removePerson',
	    'addLanguage' => 'addLanguage',
	    'removeLanguage' => 'removeLanguage',
	];

	public function __construct($service) {
		$this->service = $service;
	}

	private function getPersonList() {
		$s = $this->service;

		$persons = $s->select('Demo\\Object\\Person');

		foreach ($persons as $p) {
			echo $p . PHP_EOL;
		}
	}

	private function filterPersonsByText($text) {
		$s = $this->service;

		$persons = $s->select(
			'Demo\\Object\\Person',
			['~itext' => $text]
		);

		foreach ($persons as $p) {
			echo $p . PHP_EOL;
		}
	}

	private function filterPersonsByLanguage(...$languageNames) {
		$s = $this->service;

		$languageIDs = [];
		foreach ($languageNames as $languageName) {
			$languageIDs[] = Language::getIDByName($languageName);
		}

		$personLanguages = $s->select(
			'Demo\\Object\\PersonLanguage',
			['=slanguageID' => $languageIDs]
		);

		$persons = [];
		foreach ($personLanguages as $personLanguage) {
			$persons[] = $personLanguage->getPerson();
		}

		foreach ($persons as $p) {
			echo $p . PHP_EOL;
		}
	}

	private function addPerson($name, $surname, ...$languageNames) {
		$s = $this->service;

		$person = new Person($s, $name, $surname);

		$s->insert($person);

		foreach ($languageNames as $languageName) {
			$language = new Language($s, $languageName);

			$s->insert($language);

			$person->addLanguage($language);
		}
	}

	private function removePerson($personID) {
		$s = $this->service;

		$person = $s->select(
			'Demo\\Object\\Person',
			['=sid' => $personID]
		)[0];

		$s->delete($person);

	    // remove m2m records
	    $personLanguages = $s->select(
	    	'Demo\\Object\\PersonLanguage',
	    	['=spersonID' => $personID]
	    );

	    foreach ($personLanguages as $personLanguage) {
	    	$s->delete($personLanguage);
	    }
	}

	private function addLanguage($name) {
		$s = $this->service;

		$language = new Language($s, $name);

		$s->insert($language);
	}

	private function removeLanguage($languageName) {
		$s = $this->service;

		$languageID = Language::getIDByName($languageName);

		$language = $s->select(
			'Demo\\Object\\Language',
			['=sid' => $languageID]
		)[0];

		$s->delete($language);

	    // remove m2m records
	    $personLanguages = $s->select(
	    	'Demo\\Object\\PersonLanguage',
	    	['=slanguageID' => $languageID]
	    );

	    foreach ($personLanguages as $personLanguage) {
	    	$s->delete($personLanguage);
	    }
	}

	public function dispatch($command, $args) {
		$func = $this::COMMANDS[$command];

		call_user_func_array(array($this, $func), $args);
	}
}
