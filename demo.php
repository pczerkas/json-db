<?php

interface ServiceInterface {
	public function select($obj, $filters=null);
	public function insert(&$obj);
	public function update($obj);
	public function delete(&$obj);
}


class Service implements ServiceInterface {
	const IDS_IDX = 'ids';
	const OBJECTS_IDX = 'objects';

	private $file_name = '';

	function __construct($file_name) {
		$this->file_name = $file_name;
	}

	private function read() {
		$string = file_get_contents($this->file_name);
		return json_decode($string, true);
	}

	private function write($data) {
		$string = json_encode($data);
		file_put_contents($this->file_name, $string);
	}

	private function get_table($obj) {
		return get_class($obj);
	}

	private function serialize_object($obj) {
		return serialize($obj);
	}

	private function unserialize_object($obj_string) {
		return unserialize($obj_string);
	}

	public function select($obj, $filters=null) {
		$table = $this->get_table($obj);
		$data = $this->read();

		$result = [];

		foreach ($data[self::OBJECTS_IDX][$table] as $id => $obj_string) {
			$obj = $this->unserialize_object($obj_string);

			if ($filters) {
				$matches = false;

				foreach ($filters as $filter => $filter_value) {
					$filter_type = substr($filter, 0, 1);
					$case_type = substr($filter, 1, 2);
					$attr_name = substr($filter, 2);

					$attr_value = $obj->{$attr_name};

					switch ($filter_type) {
					    case '=':
					    	switch ($case_type) {
					    		case 's':
									if (strcmp($attr_value, $value) == 0) {
										$matches = true;
									}
									break;

					    		case 'i':
									if (strcasecmp($attr_value, $value) == 0) {
										$matches = true;
									}
									break;
					    	}
							break;

					    case '~':
					    	switch ($case_type) {
					    		case 's':
									break;

					    		case 'i':
					    			$attr_value = mb_strtolower($attr_value, 'UTF-8');
					    			$value = mb_strtolower($value, 'UTF-8');
									break;

					    	}

							if (strpos($attr_value, $value) !== false) {
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
		$table = $this->get_table($obj);
		$data = $this->read();

		// generate new id
		$id = $data[IDS_IDX][$table] ? : -1;
		$id += 1;

		$data[IDS_IDX][$table] = $id;

		// assign id
		$obj->id = $id;

		$data[self::OBJECTS_IDX][$table][$id] = $this->serialize_object($obj);

		$this->write($data);

		return $id;
	}

	public function update($obj) {
		$id = $obj->id;

		if ($id === null) {
			return false;
		}

		$table = $this->get_table($obj);
		$data = $this->read();

		$data[self::OBJECTS_IDX][$table][$id] = $this->serialize_object($obj);

		$this->write($data);

		return true;
	}

	public function delete(&$obj) {
		$id = $obj->id;

		if ($id === null) {
			return false;
		}

		$table = $this->get_table($obj);
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
	public $id;

	public function getID() {
		return $this->id;

	}

	public function setID($id) {
		$this->id = $id;
	}
}


class Person extends BaseObject implements ObjectInterface {
	public $name;
	public $surname;

	function __construct($name, $surname) {
		$this->name = $name;
		$this->surname = $surname;
	}
}


class Language extends BaseObject implements ObjectInterface {
	public $name;

	function __construct($name) {
		$this->name = $name;
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

    exit($usage . "\n");
}

$config = parse_ini_file(__DIR__ . '/demo.ini');

$db_file_name = $config['db_file_name'];

$service = new Service($db_file_name);

$command = $argv[1];
