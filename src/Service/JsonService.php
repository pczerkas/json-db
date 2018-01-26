<?php

namespace Demo\Service;

use Demo\Interfaces\ServiceInterface;

class JsonService implements ServiceInterface {
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
