<?php

interface ServiceInterface {
	public function select($table, $filters=null);
	public function insert(&$obj);
	public function update($obj);
	public function delete(&$obj);
}


class Service implements ServiceInterface {
	const IDS_IDX = 'ids';
	const OBJECTS_IDX = 'objects';

	private $fileName = '';

	public function __construct($fileName) {
		$this->fileName = $fileName;
	}

	private function read() {
		$string = @file_get_contents($this->fileName);
		return json_decode($string, true) ? : [];
	}

	private function write($data) {
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

	public function select($table, $filters=null) {
		$data = $this->read();

		$result = [];

		if (!array_key_exists($table, $data[self::OBJECTS_IDX])) {
			return $result;
		}

		foreach ($data[self::OBJECTS_IDX][$table] as $id => $objString) {
			$obj = $this->unserializeObject($objString);

			if ($filters) {
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

					switch ($filterType) {
					    case '=':
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
							break;

					    case '~':
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

							break;
					}
				}

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
		$data = $this->read();

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

		$this->write($data);

		return $id;
	}

	public function update($obj) {
		$id = $obj->getID();

		if ($id === null) {
			return false;
		}

		$table = $this->getTable($obj);
		$data = $this->read();

		$data[self::OBJECTS_IDX][$table][$id] = $this->serializeObject($obj);

		$this->write($data);

		return true;
	}

	public function delete(&$obj) {
		$id = $obj->getID();

		if ($id === null) {
			return false;
		}

		$table = $this->getTable($obj);
		$data = $this->read();

		unset($data[self::OBJECTS_IDX][$table][$id]);

		$this->write($data);

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
}


class Person extends BaseObject implements ObjectInterface {
	public $name;
	public $surname;

	public function __construct($service, $name, $surname) {
		parent::__construct($service);

		$this->name = $name;
		$this->surname = $surname;
	}

    public function __toString() {
	    $personID = $this->getID();

	    $service = $this->service;
	    $personLanguages = $service->select(
	    	'PersonLanguage',
	    	['=spersonID' => $personID]
	    );

	    $languages = [];
	    foreach($personLanguages as $personLanguage) {
	    	$languageID = $personLanguage->languageID;

		    $language = $service->select(
		    	'Language',
		    	['=sid' => $languageID]
		    )[0];

	    	$languages[] = $language;
	    }


	    $languages = implode(', ', $languages);

    	// ID. Imiê Nazwisko - (jêzyk1, jêzyk2, ...)
	    $string = '%1$s. %2$s %3$s (%4$s)';

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

	    $service = $this->service;
		$personLanguage = new PersonLanguage($service, $personID, $languageID);
		$service->insert($personLanguage);
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

	public function getID() {
		return mb_strtolower($this->name, 'UTF-8');
	}
}


function getPersonList() {

}


function filterPersonsByName() {

}


function filterPersonsByLanguage() {

}


$commands = [
    'list' => 'getPersonList',
    'find' => 'filterPersonsByName',
    'languages' => 'filterPersonsByLanguage',
    'addPerson' => 'addPerson',
    'removePerson' => 'removePerson',
    'addLanguage' => 'addLanguage',
    'removeLanguage' => 'removeLanguage',
];


if ($argc < 2) {
    $usage = 'Usage: %s [%s]';
    $usage = sprintf(
        $usage,
        __FILE__,
        implode(', ', array_keys($commands))
    );

    exit($usage . PHP_EOL);
}

$config = parse_ini_file(__DIR__ . '/demo.ini');

$dbFileName = $config['db_file_name'];

$service = new Service($dbFileName);

$command = $argv[1];


/*
$obj = new Person($service, 'Przemek', 'Czerkas');
$service->insert($obj);

$obj2 = $service->select('Person')[0];
echo $obj2 . PHP_EOL;

$obj2->name = 'XXX';
$service->update($obj2);

$obj3 = $service->select('Person', ['~iname' => 'Xx'])[0];
echo $obj3 . PHP_EOL;

$obj4 = new Language($service, 'PHP');
$service->insert($obj4);

$obj3->addLanguage($obj4);
*/
