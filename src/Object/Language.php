<?php

namespace Demo\Object;

use Demo\Interfaces\ObjectInterface;

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

	public static function getIDByName($name) {
		return mb_strtolower($name, 'UTF-8');
	}

	public function getID() {
		return $this->getIDByName($this->name);
	}
}
