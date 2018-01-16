<?php 

class Reefine_util_matrix_fields {
	private static $instance;
	/**
	 * @var Reefine
	 */
	private $reefine;

	private $grid_fields = array();

	public static function get_instance($reefine) {
		if (!isset(Reefine_util_matrix_fields::$instance))
			Reefine_util_matrix_fields::$instance = new Reefine_util_matrix_fields($reefine);
		return Reefine_util_matrix_fields::$instance;
	}

	/**
	 *
	 * @param Reefine $reefine
	 */
	private function __construct($reefine) {
		$this->reefine = $reefine;
		$rows = $this->reefine->EE->db->select('col_id, field_id, col_type, col_label, col_name')
		->where('site_id', $this->reefine->site)
		->get("{$this->reefine->dbprefix}matrix_cols")->result_array();
		foreach ($rows as $row) {
			$this->grid_fields[$row['field_id']][$row['col_name']] = $row;
		}
	}

	public function get_grid_field($field_id,$col_name) {
		if (isset($this->grid_fields[$field_id][$col_name]))
			return $this->grid_fields[$field_id][$col_name];
		else
			throw new Exception ("Matrix column " . $col_name . " not found.");
	}

}