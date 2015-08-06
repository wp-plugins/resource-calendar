<?php

	require_once(RCAL_PLUGIN_SRC_DIR . 'data/resource-calendar-data.php');

	
class Log_Data extends ResourceCalendar_Data {
	
	const TABLE_NAME = 'rcal_log';	
	
	function __construct() {
		parent::__construct();
	}
	

	public function getInitDatas($get_cnt = 100) {
		global $wpdb;
		$join = '';
		$where ='';

		$sql = 'SELECT `sql` as operation,remark,'.
				' DATE_FORMAT(insert_time,"'.__('%%m/%%d/%%Y',RCAL_DOMAIN).'") as logged_day ,'.
				' DATE_FORMAT(insert_time,"%%H:%%i") as logged_time '.
				' FROM '.$wpdb->prefix.self::TABLE_NAME.
				' ORDER BY insert_time DESC'.
				' LIMIT %d ';
		$result = $wpdb->get_results($wpdb->prepare($sql,$get_cnt),ARRAY_A);
		if ($result === false ) {
			$this->_dbAccessAbnormalEnd();
		}
		return $result;



	}

	

	
	
}