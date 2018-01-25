<?php

class Service {
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

	private function serialize_object($obj) {
		return unserialize($obj);
	}

	public function select($obj, $filters = null) {
		$table = $this->get_table($obj);
		$data = $this->read();

		$result = [];

		foreach ($data['objects'][$table] as $id => $obj_s) {
			$obj = $this->unserialze_object($obj_s);

			if ($filters) {
				$matches = false;

				foreach ($filters as $filter => $value) {
					$filter_type = substr($filter, 0, 1);
					$attr_name = substr($filter, 1);

					switch ($filter_type) {
					    case '=':
							if ($obj->{$attr_name} == $value) {
								$matches = true;
							}

					    case '~':
							if (strpos($obj->{$attr_name}, $value) !== false) {
								$matches = true;
							}
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
		$id = $data['ids'][$table] ? : -1;
		$id += 1;

		$data['ids'][$table] = $id;

		// assign id
		$obj->id = $id;

		$data['objects'][$table][$id] = $this->serialize_object($obj);

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

		$data['objects'][$table][$id] = $this->serialize_object($obj);

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

		unset($data['objects'][$table][$id]);

		$this->write($data);

		return true;
	}
}

class Person {

}

class Language {

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
