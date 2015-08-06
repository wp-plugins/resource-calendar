<?php 

	require_once(RCAL_PLUGIN_SRC_DIR . 'page/resource-calendar-page.php');

	
class Mail_Edit extends ResourceCalendar_Page {
	
	private $table_data = null;
	private $default_mail = '';
	
	public function __construct() {
		parent::__construct();
	}

	public function check_request() {
		if (defined ( 'RCAL_DEMO' ) && RCAL_DEMO ) {
			throw new Exception(ResourceCalendar_Component::getMsg('I001',null) ,1);
		}
		$msg = null;
		ResourceCalendar_Page::serverCheck(array(),$msg);
		
	}
	
	
	public function show_page() {
		echo '{	"status":"Ok","message":"'.ResourceCalendar_Component::getMsg('N001').'",
				"set_data":'.json_encode($this->table_data).' }';
	}


}