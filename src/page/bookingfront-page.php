<?php

	require_once(RCAL_PLUGIN_SRC_DIR . 'page/resource-calendar-page.php');

	
class BookingFront_Page extends ResourceCalendar_Page {
	
	const Y_PIX = 550;

	private $resource_datas = null;
	private $month_datas = null;
	
	private $first_hour = '';
	private $last_hour = '';

	private $reseration_cd = '';
	

	private $url = '';

	private $reservation_datas = null;
	private $category_datas = null;

	private $user_inf = null;	
	

	private $valid_from = '';
	private $valid_to = '';
	

	public function __construct() {
		parent::__construct();
		$this->target_year = date_i18n("Y");
		$url = get_bloginfo('wpurl');
		if (is_ssl() && strpos(strtolower ( $url),'https') === false ) {
			$url = preg_replace("/[hH][tT][tT][pP]:/","https:",$url);
		}
		$this->url = $url;
		
		
	}
	

	public function set_resource_datas ($datas) {
		$this->resource_datas = $datas;
		if (count($this->resource_datas) === 0 ) {
			throw new Exception(ResourceCalendar_Component::getMsg('E010',__function__.':'.__LINE__ ) );
		}
	}

	public function set_month_datas ($datas) {
		$this->month_datas = $datas;
	}
	
	public function set_reservation_datas($reservation_datas) {
		$this->reservation_datas = $reservation_datas;
		
	}
	
	public function set_category_datas ($set_data ) {
		$this->category_datas = $set_data;
	}
	
	public function set_user_inf($user_inf) {
		//携帯優先
		$user_inf["set_tel"] = "";
		if (!empty($user_inf["mobile"] )) {
			$user_inf["set_tel"] = $user_inf["mobile"] ;
		}
		else if (!empty($user_inf["tel"] )) {
			$user_inf["set_tel"] = $user_inf["tel"] ;
		}
		$this->user_inf = $user_inf;
	}

	
	public function set_config_datas($config_datas) {
		parent::set_config_datas($config_datas);
		$edit = ResourceCalendar_Component::computeDate($config_datas['RCAL_CONFIG_AFTER_DAY']);
		$this->insert_max_day = substr($edit,0,4).','.(intval(substr($edit,5,2))-1).','.(intval(substr($edit,8,2))+1);
		
		$this->first_hour = substr($config_datas['RCAL_CONFIG_OPEN_TIME'],0,2);
		$this->last_hour = substr($config_datas['RCAL_CONFIG_CLOSE_TIME'],0,2);
//		if (intval($this->last_hour) > 0 ) $this->last_hour++;

		$now = date_i18n('Ymd');
		$this->valid_from = ResourceCalendar_Component::computeDate(-1*$config_datas['RCAL_CONFIG_BEFORE_DAY'],substr($now,0,4),substr($now,4,2),substr($now,6,2));
		$this->valid_to = ResourceCalendar_Component::computeDate($this->config_datas['RCAL_CONFIG_AFTER_DAY'],substr($now,0,4),substr($now,4,2),substr($now,6,2));

		

	}


	public function show_page() {
		require_once(RCAL_PLUGIN_SRC_DIR . '/page/booking-show-page.php');
	}
	private function _editDate($yyyymmdd) {
		return substr($yyyymmdd,0,4). substr($yyyymmdd,5,2).  substr($yyyymmdd,8,2);
	}
	private function _editTime($yyyymmdd) {
		return substr($yyyymmdd,11,2). substr($yyyymmdd,14,2);
	}
	
	
}		//class

