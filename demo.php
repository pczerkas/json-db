<?php

interface ServiceInterface {
	public function select($table, $filters = null);
	public function insert(&$obj);
	public function update($obj);
	public function delete(&$obj);
}


class Service implements ServiceInterface {
	const IDS_IDX = 'ids';
	const OBJECTS_IDX = 'objects';

	private $fileName;

	public function __construct($fileName) {
		$this->fileName = $fileName;
	}

	private function readFile() {
		$string = @file_get_contents($this->fileName);
		return json_decode($string, true) ? : [];
	}

	private function writeFile($data) {
		$string = json_encode($data);
		file_put_contents($this->fileName, $string);
	}

	private function getTable($obj) {
		return get_class($obj);
	}

	private function serializeObject($obj) {
		return serialize($obj);
	}

	private function unserializeObject($objString) {
		return unserialize($objString);
	}

	private function selectFilterEqual($caseType, $attrValue, $filterValue) {
		$matches = false;

    	switch ($caseType) {
    		case 's':
				if (strcmp($attrValue, $filterValue) == 0) {
					$matches = true;
				}
				break;

    		case 'i':
				if (strcasecmp($attrValue, $filterValue) == 0) {
					$matches = true;
				}
				break;
    	}

    	return $matches;
	}

	private function selectFilterContains($caseType, $attrValue, $filterValue) {
		$matches = false;

    	switch ($caseType) {
    		case 's':
				break;

    		case 'i':
    			$attrValue = mb_strtolower($attrValue, 'UTF-8');
    			$filterValue = mb_strtolower($filterValue, 'UTF-8');
				break;
    	}

		if (strpos($attrValue, $filterValue) !== false) {
			$matches = true;
		}

    	return $matches;
	}

	private function selectFilters($obj, $filters) {
		$matches = false;

		foreach ($filters as $filter => $filterValue) {
			$filterType = substr($filter, 0, 1);
			$caseType = substr($filter, 1, 1);
			$attrName = substr($filter, 2);

			if ($attrName == 'id') {
				$attrValue = $obj->getID();

			} else {
				$attrValue = $obj->{$attrName};
			}

			$filterValues = is_array($filterValue) ? $filterValue : [$filterValue];

			foreach ($filterValues as $filterValue) {
				switch ($filterType) {
				    case '=':
				    	$matches = $this->selectFilterEqual($caseType, $attrValue, $filterValue);
						break;

				    case '~':
				    	$matches = $this->selectFilterContains($caseType, $attrValue, $filterValue);
						break;
				}

				if ($matches) {
					break;
				}
			}
		}

		return $matches;
	}

	public function select($table, $filters = null) {
		$data = $this->readFile();

		$result = [];

		if (!array_key_exists(self::OBJECTS_IDX, $data)) {
			$data[self::OBJECTS_IDX] = [];
		}

		if (!array_key_exists($table, $data[self::OBJECTS_IDX])) {
			return $result;
		}

		foreach ($data[self::OBJECTS_IDX][$table] as $id => $objString) {
			$obj = $this->unserializeObject($objString);

			if (!is_null($filters)) {
				$matches = $this->selectFilters($obj, $filters);

			} else {
				$matches = true;
			}

			if ($matches) {
				$result[] = $obj;
			}
		}

		return $result;
	}

	public function insert(&$obj) {
		$table = $this->getTable($obj);
		$data = $this->readFile();

		$id = $obj->getID();

		if (is_null($id)) {
			// generate new unique id
			if (!array_key_exists(self::IDS_IDX, $data)) {
				$data[self::IDS_IDX] = [];
			}

			$id = 0;
			if (array_key_exists($table, $data[self::IDS_IDX])) {
				$id = $data[self::IDS_IDX][$table];
			}

			$id += 1;

			$data[self::IDS_IDX][$table] = $id;

			// assign id
			$obj->setID($id);
		}

		$data[self::OBJECTS_IDX][$table][$id] = $this->serializeObject($obj);

		$this->writeFile($data);

		return $id;
	}

	public function update($obj) {
		$id = $obj->getID();

		if ($id === null) {
			return false;
		}

		$table = $this->getTable($obj);
		$data = $this->readFile();

		$data[self::OBJECTS_IDX][$table][$id] = $this->serializeObject($obj);

		$this->writeFile($data);

		return true;
	}

	public function delete(&$obj) {
		$id = $obj->getID();

		if ($id === null) {
			return false;
		}

		$table = $this->getTable($obj);
		$data = $this->readFile();

		// clear id
		$obj->setID(null);

		unset($data[self::OBJECTS_IDX][$table][$id]);

		$this->writeFile($data);

		return true;
	}
}


interface ObjectInterface {
	public function getID();
	public function setID($id);
}


class BaseObject {
	protected $service;
	private $id;

	public function __construct($service) {
		$this->service = $service;
	}

	public function getID() {
		return $this->id;
	}

	public function setID($id) {
		$this->id = $id;
	}
}


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
	    	'Person',
	    	['=sid' => $pID]
	    )[0];
    }

    public function getLanguage() {
	    $lID = $this->languageID;

	    $s = $this->service;

	    return $s->select(
	    	'Language',
	    	['=sid' => $lID]
	    )[0];
    }
}


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
	    	'PersonLanguage',
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


class Language extends BaseObject implements ObjectInterface {
	public $name;

	public function __construct($service, $name) {
		parent::__construct($service);

		$this->name = $name;
	}

    public function __toString() {
	    $string = '%1$s';

	    return sprintf(
	        $string,
	        $this->name
	    );
    }

	public function getID($name = null) {
		if (is_null($name)) {
			$name = $this->name;
		}

		return mb_strtolower($name, 'UTF-8');
	}
}


class Command {
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

		$persons = $s->select('Person');

		foreach ($persons as $p) {
			echo $p . PHP_EOL;
		}
	}

	private function filterPersonsByText($text) {
		$s = $this->service;

		$persons = $s->select(
			'Person',
			['~itext' => $text]
		);

		foreach ($persons as $p) {
			echo $p . PHP_EOL;
		}
	}

	private function filterPersonsByLanguage(...$languageIDs) {
		$s = $this->service;

		$personLanguages = $s->select(
			'PersonLanguage',
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
			'Person',
			['=sid' => $personID]
		)[0];

		$s->delete($person);

	    // remove m2m records
	    $personLanguages = $s->select(
	    	'PersonLanguage',
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

	private function removeLanguage($languageID) {
		$s = $this->service;

		$language = $s->select(
			'Language',
			['=sid' => $languageID]
		)[0];

		$s->delete($language);

	    // remove m2m records
	    $personLanguages = $s->select(
	    	'PersonLanguage',
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


if ($argc < 2) {
    $usage = 'Usage: %s [%s]';
    $usage = sprintf(
        $usage,
        __FILE__,
        implode(', ', array_keys(Command::COMMANDS))
    );

    exit($usage . PHP_EOL);
}

$config = parse_ini_file(__DIR__ . '/demo.ini');

$dbFileName = $config['db_file_name'];

$service = new Service($dbFileName);

$command = new Command($service);

$command->dispatch(
	$argv[1],
	array_slice($argv, 2)
);
