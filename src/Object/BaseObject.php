<?php

namespace Demo\Object;

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
