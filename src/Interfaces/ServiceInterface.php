<?php

namespace Demo\Interfaces;

interface ServiceInterface {
	public function select($table, $filters = null);
	public function insert(&$obj);
	public function update($obj);
	public function delete(&$obj);
}
