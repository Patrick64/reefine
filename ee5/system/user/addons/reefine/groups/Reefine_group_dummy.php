<?php

class Reefine_group_dummy extends Reefine_group {
	public $type = 'dummy';
	public function __construct($reefine,$group_name) {
		parent::__construct($reefine,$group_name);
	}
}