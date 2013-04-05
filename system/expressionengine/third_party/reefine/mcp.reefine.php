<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');



class Reefine_mcp {

    function Reefine_mcp ()
    {
        $this->EE =& get_instance();
        $this->EE->load->library('email');
		$this->EE->load->helper('text');
       
    }
    
    // --------------------------------------------------------------------

	/**
	 * Main Page
	 *
	 * @access	public
	 */
	function index()
	{
		$this->EE->cp->set_variable('cp_page_title', 'Reefine Admin');
		Return file_get_contents(PATH_THIRD . 'reefine/intro.txt');
	}

}
// END CLASS

/* End of file mcp.module_name.php */
/* Location: ./system/expressionengine/third_party/modules/module_name/mcp.module_name.php */