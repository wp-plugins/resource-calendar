<?php

	require_once(RCAL_PLUGIN_SRC_DIR . 'data/resource-calendar-data.php');

	
class Booking_Data extends ResourceCalendar_Data {
	
	const TABLE_NAME = 'rcal_reservation';	
	
	public function __construct() {
		parent::__construct();
	}
	
	

	public function getAllEventData($target_day="",$isOnly_target_day =false){
		global $wpdb;

		$now = date_i18n('Ymd');

		$to_date = '2099-12-31 12:00:00';
		if (empty($target_day) ) {
			$target_day = ResourceCalendar_Component::computeDate(-1*$this->getConfigData('RCAL_CONFIG_BEFORE_DAY'),substr($now,0,4),substr($now,4,2),substr($now,6,2));
		}
		if ($isOnly_target_day) {
			$to_date = ResourceCalendar_Component::computeDate(1,substr($target_day,0,4),substr($target_day,4,2),substr($target_day,6,2));
			$target_day = ResourceCalendar_Component::computeDate(0,substr($target_day,0,4),substr($target_day,4,2),substr($target_day,6,2));
		}
		else {
			$to_date = ResourceCalendar_Component::computeDate($this->getConfigData('RCAL_CONFIG_AFTER_DAY'),substr($now,0,4),substr($now,4,2),substr($now,6,2));
		}
		
		$sql = 	$wpdb->prepare(
						' SELECT '.
						'reservation_cd,resource_cd,user_login,name,mail,tel,'.
						'time_from,'.
						'time_to,'.
						'status,'.
						'remark,memo,notes,delete_flg,insert_time,update_time,activate_key '.
						' FROM '.$wpdb->prefix.'rcal_reservation '.
						'   WHERE time_from >= %s '.
						'     AND time_to < %s '.
						'     AND delete_flg <> '.ResourceCalendar_Table_Status::DELETED.
						'     AND status <> '.ResourceCalendar_Reservation_Status::CANCELED.
						' ORDER BY time_from ',
						$target_day,$to_date
				);
		if ($wpdb->query($sql) === false ) {
			$this->_dbAccessAbnormalEnd();
		}
		else {
			$result = $wpdb->get_results($sql,ARRAY_A);
		}
		return $result;
	}

	public function insertTable ($table_data){
		$reservation_cd = $this->insertSql(self::TABLE_NAME,$table_data,'%d,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%d');
		if ($reservation_cd === false ) {
			$this->_dbAccessAbnormalEnd();
		}
		return $reservation_cd;
	}

	public function updateTable ($table_data){

			$set_string = 	' resource_cd = %d , '.
							' name = %s , '.
							' mail = %s , '.
							' tel = %s , '.
							' activate_key = %s , '.
							' time_from = %s , '.
							' time_to = %s , '.
							' remark = %s , '.
							' status = %d , '.
							' update_time = %s '.
							(empty($table_data['item_cds']) ? ' ' : ' , item_cds = %s  ' );
													
			$set_data_temp = array(
							$table_data['resource_cd'],
							$table_data['name'],
							$table_data['mail'],
							$table_data['tel'],
							$table_data['activate_key'],
							$table_data['time_from'],
							$table_data['time_to'],
							$table_data['remark'],
							$table_data['status'],
							date_i18n('Y-m-d H:i:s')	);
			$set_data_temp[] = $table_data['reservation_cd'];
		$where_string = ' reservation_cd = %d ';
		if ( $this->updateSql(self::TABLE_NAME,$set_string,$where_string,$set_data_temp) === false) {
			$this->_dbAccessAbnormalEnd();
		}
		return true;
	}
	
	public function cancelTable ($table_data){
		$set_string = 	' status = %d  '.
						' ,update_time = %s ';
		$set_string .= 	' ,memo = concat(remark,"'.sprintf(__("\nCanceled by %s. ",RCAL_DOMAIN),__("[Screen of Booking]",RCAL_DOMAIN)).'") ';
												
		$set_data_temp = array(
						$table_data['status']
						,date_i18n('Y-m-d H:i:s')
						,$table_data['reservation_cd']);
		$where_string = ' reservation_cd = %d ';
		if ( $this->updateSql(self::TABLE_NAME,$set_string,$where_string,$set_data_temp) === false) {
			$this->_dbAccessAbnormalEnd();
		}
	}

	public function deleteTable ($table_data){
		$set_string = 	' delete_flg = %d, update_time = %s  ';
		$set_data_temp = array(ResourceCalendar_Table_Status::DELETED,
						date_i18n('Y-m-d H:i:s'),
						$table_data['reservation_cd']);
		$where_string = ' reservation_cd = %d ';
		
		if ( $this->updateSql(self::TABLE_NAME,$set_string,$where_string,$set_data_temp) === false) {
			$this->_dbAccessAbnormalEnd();
		}
		return true;
	}

	public function getCategoryDatas() {
		global $wpdb;
		$sql = 'SELECT  '.
				' category_cd '.
				' ,category_name '.
				' ,category_patern '.
				' ,category_values '.
				' FROM '.$wpdb->prefix.'rcal_category '.
				' WHERE delete_flg <> '.ResourceCalendar_Table_Status::DELETED.
				' ORDER BY display_sequence,category_cd ';
		if ($wpdb->query($sql) === false ) {
			$this->_dbAccessAbnormalEnd();
		}
		else {
			$result = $wpdb->get_results($sql,ARRAY_A);
		}
		return $result;
	}
//kokomade

	
	public function getInitDatas() {
	}
}