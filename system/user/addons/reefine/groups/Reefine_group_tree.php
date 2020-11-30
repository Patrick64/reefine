<?php
class Reefine_group_tree extends Reefine_group_category {
	public $type = 'tree';
	function __construct($reefine,$group_name) {
		parent::__construct($reefine,$group_name);
	}
	
	function set_filters() {
		$this->filters = array();
		// for each field in the filter group
		foreach ($this->fields as &$field) {
			// get list of possible values
			//$results = $this->get_filter_groups_for_list($this->get_field_value_column($field),$this->get_field_title_column($field));
			$results = $this->get_filter_groups_for_list(
					$this->get_field_value_column($field),
					$this->get_field_title_column($field),
					$field->get_filter_id_field(),
					$field->get_filter_extra_columns(),
					$field->get_filter_extra_clause(),
					$field->get_filter_order_by());
			$this->filters = array_merge($this->filters,$results);
				
		}

	
		// set totals for use in templates
	
		$this->set_filter_totals();

	
	
	}
	
	
	/**
	 * Go though each filter and add the url attribute to filter array
	 */
	public function add_filter_url_to_filters() {
		foreach ($this->filters as &$filter) {			
			$filter['url'] = $this->reefine->create_url($this->reefine->get_filter_url($this->group_name,$filter['filter_value']));
		}
		$this->clear_url = $this->reefine->create_url($this->reefine->get_filter_url($this->group_name,null));
	}
	


	
	// this doesnt work, replace with this http://ellislab.com/expressionengine/user-guide/development/reference/tree_datastructure.html
	
	function compare_filter_for_tree($a, $b)
	{
		if ( $a['filter_id'] == $b['filter_id'] ) {
			return 0;
	
		} else if ( $a['parent_id'] ) {
			if ( $a['parent_id'] == $b['parent_id'] ) {
				return ( $a['filter_id'] < $b['filter_id'] ? -1 : 1 );
			} else {
				return ( $a['parent_id'] >= $b['filter_id'] ? 1 : -1 );
			}
		} else if ( $b['parent_id'] ) {
			return ( $b['parent_id'] >= $a['filter_id'] ? -1 : 1);
		} else {
			return ( $a['filter_id'] < $b['filter_id'] ? -1 : 1 );
		}
	}
	
	/**
	 * Sort filters based on $sort
	 * @param array $filters
	 * @param string $sort value, count, active, or active_count
	 */
	public function sort_filters() {
		//usort($this->filters, array($this,"compare_filter_for_tree"));
		$this->reefine->EE->load->library('datastructures/tree');
		$root = $this->reefine->EE->tree->from_list($this->filters,array('key'=>'filter_id','parent_id'=>'parent_id'));
		$result = array();
		$it = $root->preorder_iterator();
		
		foreach ($it as $node)
		{
			$data = $node->data();
			$data['depth']=$node->getDepth();
			$result[] = $data; 
		}
		$this->filters = $result;
		
	}
	
	public function set_filter_depths() {
		$filter_depths = array();
		foreach($this->filters as &$filter) {
			if ($filter['parent_id']==0) {
				$filter['depth']=0;
				$filter_depths[$filter['filter_id']] = 0;
			} else {
				$filter['depth'] = $filter_depths[$filter['parent_id']] + 1;
				$filter_depths[$filter['filter_id']] = $filter['depth'];
			}
		}
		unset($filter);
	}
	
	/**
	 * Get array of group values formatted for output with array of filters.
	 * @param string $only_show_active If true only returns active filters
	 * @return array
	 */
	function get_filters_for_output($only_show_active) {
		if ($only_show_active) {
			return parent::get_filters_for_output(true);
		} else {

			$group = array();

			// get attributes of group
			foreach (get_object_vars($this) as $key => $val) {
				if (is_string($val)) {
					$group[$key] = htmlspecialchars($val, ENT_QUOTES);
				}
			}

			// format filters for output
			$group['filters'] = array();
			$group['active_filters'] = $this->active_filters;
			$group['total_filters'] = $this->total_filters;
			$group['matching_filters'] = $this->matching_filters;
			$filter_values = array();
			foreach ($this->values as $filter_value) {
				$filter_values[] = array('value'=>$filter_value);
			}
			$group['active_filter_values'] = $filter_values;

			// extract $subfilters, $has_active_subfilters
			extract($this->get_subfilters_for_output($only_show_active,0,0));
			$group['filters'] = $subfilters;

			return $group;
		}
	}

	private function get_subfilters_for_output($only_show_active,$parent_id,$level) {
		$output_filters = array();
		$found_active_filters = false;
		
		foreach ($this->filters as $filter_key => $filter) {
			
			if ($filter['parent_id']==$parent_id) {

				$filter_active = $filter['filter_active'];
				$filter_quantity = $filter['filter_quantity'];
					
				// Check that - if only show active then only show if active ALSO if hide empty filters then only show if filter is not empty or is active
				if ( (!$only_show_active || $filter_active) &&
				($this->show_empty_filters || $filter_active || $filter_quantity>0) )  {
			
					$filter_out = array();
			
					// used for formatting
					$filter_out['filter_active_class'] = ( $filter_active ? 'active' : 'inactive' );
					$filter_out['filter_active_boolean'] = ( $filter_active ? 'true' : 'false' );
			
					// stop xss
					foreach ($filter as $key => $val) {
						$filter_out[$key] = htmlspecialchars($val, ENT_QUOTES);
					}
			
					// number range doessome stuff
					$this->format_filter_for_output($filter,$filter_out);
					
					// call recursive function on subfilter 
					$subfilter_output = $this->get_subfilters_for_output($only_show_active, $filter['filter_id'],$level+1)																	;
					$subfilters = $subfilter_output['subfilters'];
					$has_active_subfilters = $subfilter_output['has_active_subfilters'];

					// set values for if it has an active subfilter, useful for expand/collapse trees
					$filter_out['has_active_subfilters'] = $has_active_subfilters;
					$filter_out['has_active_subfilters_class'] = ( $has_active_subfilters ? 'has-active-subfilters' : 'no-active-subfilters' );
						
					
					// if level 2 or more then add _2, _3... to end of value coz of EE's rubbish templateparsing 
					if ($level>0) {
						$filter_out2 = array();
						foreach ($filter_out as $key => $val) {
							$filter_out2[$key . '_' . $level] = $val;
						} 
						$filter_out = $filter_out2;
						unset($val);
					}
					
					
					// get subfilters
					if (count($subfilters)>0)
						$filter_out['subfilters_' . ($level+1)] = array(array('filters_' . ($level+1) =>$subfilters));
					else
						$filter_out['subfilters_' . ($level+1)] = array();
					$output_filters[] = $filter_out;

					if ($filter['filter_active'] || $has_active_subfilters) {
						// fix for issue https://github.com/Patrick64/reefine/issues/3
						$found_active_filters = true;
					}
				}
			}
		}
		// sned back filters and whether it has an active filter or subfilter
		return array('subfilters'=>$output_filters,'has_active_subfilters'=>$found_active_filters);
			
	}	
	
}