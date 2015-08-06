<?php

class Booking_Component extends ResourceCalendar_Component  {
	

	public function __construct(&$datas) {
		$this->datas = $datas;
	}
	
	public function getTargetResourceData() {
		$result = $this->datas->getTargetResourceData();
		foreach ($result as $k1 => $d1 ) {
			//[PHOTO]
			$photo_result = $this->datas->getPhotoData($d1['photo']);
			$tmp = array();
			for($i = 0 ;$i<count($photo_result);$i++) {
				$tmp[] = $photo_result[$i];
			}
			$result[$k1]['photo_result'] = $tmp;
			//[PHOTO]
		}
		return $result;
	}

	public function getMonthData($day_from="",$day_to="") {
		global $wpdb;

		//datepickerの表示タイミングがデータ取得の後なので1月分多くとる。
		//現状、day_fromとday_toが空なのは、初期表示の場合のみ
		if (empty($day_from) && empty($day_to) ) {
			$day_from = ResourceCalendar_Component::computeMonth(-1);
			$day_to = ResourceCalendar_Component::computeMonth(3);
		}
		//月の配列をつくる
		$start_month = $day_from;
		$month_array = array();
		$target_month = substr($day_to,0,4).substr($day_to,5,2);
		for(;;) {
			$set_month = substr($start_month,0,4).substr($start_month,5,2);
			$month_array[] = $set_month;
			if ($set_month >= $target_month) break;
			$start_month = ResourceCalendar_Component::computeMonth(1,substr($start_month,0,4),substr($start_month,5,2),1);
		}
		$edit_result = array();
		foreach ($month_array as $d1) {
			$edit_result[$d1] = array();
		}
				
		$sql = 	$wpdb->prepare(
						' SELECT '.
						' rs.target_month,rs.YYYYMMDD,rs.status,COUNT(*) as cnt '.
						' FROM (  '.
						'        SELECT  DATE_FORMAT(time_from,"%%Y%%m") as target_month,status, '.
						'                DATE_FORMAT(time_from,"%%Y%%m%%d") as YYYYMMDD '.
						'        FROM '.$wpdb->prefix.'rcal_reservation  '.
						'        WHERE time_from >= %s '.
						'        AND time_to < %s '.
						'        AND delete_flg <> '.ResourceCalendar_Table_Status::DELETED.
						'      ) rs '.
						' GROUP BY  rs.target_month,YYYYMMDD,rs.status '.
						' ORDER BY rs.target_month,YYYYMMDD ',
						$day_from,$day_to
				);


		if ($wpdb->query($sql) === false ) {
			$this->_dbAccessAbnormalEnd();
		}
		else {
			$result = $wpdb->get_results($sql,ARRAY_A);
		}
		if (count($result)>0){
			
			foreach($result as $k1 => $d1){
				$edit_result[$d1['target_month']][$d1['YYYYMMDD']][$d1['status']] = +$d1['cnt'];
			}
		}
		return $edit_result;
	}

	public function editTableData ($user_login) {
		if  ($_POST['type'] == 'deleted' ) {
			$set_data['reservation_cd'] = intval($_POST['id']);
			$set_data['status'] = ResourceCalendar_Reservation_Status::CANCELED;
		}
		else {
			$set_data['resource_cd'] = intval($_POST['rcal_resource_cd']);	
			$set_data['mail'] = stripslashes($_POST['rcal_mail']);		
			$set_data['name'] = stripslashes($_POST['rcal_name']);		
			$set_data['tel'] = stripslashes($_POST['rcal_tel']);		
			$set_data['remark'] = stripslashes($_POST['rcal_remark']);		
			$set_data['memo'] = '';
			$set_data['notes'] = '';

			$set_data['time_from'] =stripslashes($_POST['rcal_time_from']);		
			$set_data['time_to'] =stripslashes($_POST['rcal_time_to']);		
			
			$set_data['activate_key'] = substr(md5(uniqid(mt_rand(),1)),0,8);
			//管理者が登録する場合は、設定されてユーザのログインID
			if ($this->isPluginAdmin() ) {
				$set_data['user_login'] = stripslashes($_POST['rcal_user_login']);
			}
			else {
				$set_data['user_login'] = $user_login;
			}
			
			if 	(($this->datas->getConfigData('RCAL_CONFIG_CONFIRM_STYLE') ==  ResourceCalendar_Config::CONFIRM_BY_ADMIN ) || 
				($this->datas->getConfigData('RCAL_CONFIG_CONFIRM_STYLE') ==  ResourceCalendar_Config::CONFIRM_BY_MAIL ) ){
				if ($this->isPluginAdmin() ) {
					$set_data['status'] = ResourceCalendar_Reservation_Status::COMPLETE;
				}
				else {
					$set_data['status'] = ResourceCalendar_Reservation_Status::TEMPORARY;
				}
			}
			else {
				$set_data['status'] = ResourceCalendar_Reservation_Status::COMPLETE;
			}

			$edit_record = array();
			foreach ($_POST['rcal_memo'] as $k1 => $d1 ){
				$edit_record[$k1] = stripslashes($d1);
			}
			
			$set_data['memo'] = serialize($edit_record);


			if ($_POST['type'] == 'updated' ) {
				$set_data['reservation_cd'] = intval($_POST['id']);
			}
		}
		return $set_data;
	}
	

	public function serverCheck($set_data) {

		global $wpdb;
		$reservation_data = '';
		if ( $_POST['type'] == 'inserted'    ) {
			if ( ! empty($set_data['reservation_cd']) )
				throw new Exception(ResourceCalendar_Component::getMsg('E901',basename(__FILE__).':'.__LINE__),1);
		}
		else {
			$reservation_data = $this->datas->getTargetReservationData($set_data['reservation_cd']);
			if ($_POST['p2'] != $reservation_data[0]['activate_key'] ) {
				throw new Exception(ResourceCalendar_Component::getMsg('E909', basename(__FILE__).':'.__LINE__),2);
			}
		}
		//同一時間帯はひとつだけ。
		$reservation_cd  = "";
		if ( $_POST['type'] == 'updated'    ) $reservation_cd = $set_data['reservation_cd'];
		if ( ($_POST['type'] != 'deleted')&&($_POST['type'] != 'cancel') ) {
			//管理者はいつでも予約可能
			if (!$this->isPluginAdmin()){
				//fromは指定分以降より後
				$from = strtotime($set_data['time_from']);
				$limit_time = new DateTime(date_i18n('Y-m-d H:i'));
				$limit_time->modify("+".$this->datas->getConfigData('RCAL_CONFIG_RESERVE_DEADLINE')." min");
				if (+$limit_time->format('U') > $from) {
					throw new Exception(ResourceCalendar_Component::getMsg('E901', basename(__FILE__).':'.__LINE__),3);
				}
			}
			//休業日のチェックと特別な営業日のチェック
			//
			$sp_dates = $this->datas->getConfigData("RCAL_SP_DATES");
			$year = substr($set_data['time_from'],0,4);
			//yyyy-mm-dd
			$ymd = str_replace('-','',substr($set_data['time_from'],0,10));
			$in_time = str_replace(':','',substr($set_data['time_from'],-5));
			$out_time = str_replace(':','',substr($set_data['time_to'],-5));
			if(isset($sp_dates[$year][$ymd]) && $sp_dates[$year][$ymd] == ResourceCalendar_Config::OPEN ) {
			}
			elseif(isset($sp_dates[$year][$ymd]) && $sp_dates[$year][$ymd] == ResourceCalendar_Config::CLOSE ) {
				throw new Exception(ResourceCalendar_Component::getMsg('E213'),__LINE__);
			}
			else {
				//休みの場合も全休と半休がある
				//曜日;from;to,曜日;from;to,曜日;from;to
				$holidays_tmp = $this->datas->getConfigData("RCAL_CONFIG_CLOSED");
				if (!empty($holidays_tmp)) {
					$holidays_data = explode(',',$holidays_tmp);
					$holidays = array();
					if (count($holidays_data) > 0 ) {
						$holidays_detail = array();
						
						foreach($holidays_data  as $k1 => $d1 ) {
							$splitdata = explode(';',$d1);
							$holidays[] = $splitdata[0];
							$holidays_detail[] = array($splitdata[1],$splitdata[2]);
						}
						$set_holiday = ResourceCalendar_Component::getDayOfWeek($set_data['time_from']);
		
						if (in_array($set_holiday,$holidays)  ) {
							$idx = array_search($set_holiday,$holidays);
							if ($idx !== false) {	//定休日の登録があって、
								//休みの時間の中にはいっていてはいけない
								if ($out_time <= $holidays_detail[$idx][0] || $holidays_detail[$idx][1] <= $in_time ) {
								}
								else {
									throw new Exception(ResourceCalendar_Component::getMsg('E213',basename(__FILE__).':'.__LINE__),4);
								}
							}
						}
					}
				}
			}
			//予約が営業時間内に収まっているか？
			$in_time = str_replace(':','',substr($set_data['time_from'],-5));
			$out_time = str_replace(':','',substr($set_data['time_to'],-5));
			if ($this->datas->getConfigData('RCAL_CONFIG_OPEN_TIME') > $in_time  ||  $out_time > $this->datas->getConfigData('RCAL_CONFIG_CLOSE_TIME') ) {
				throw new Exception(self::getMsg('E302'),__LINE__);
			}
			//重複はできない。今後考慮する？
			$possible_cnt = 0;
			$cnt = $this->datas->countReservation($set_data['resource_cd'],$set_data['time_from'],$set_data['time_to'],$reservation_cd);
			if ($cnt > $possible_cnt ) {
				throw new Exception(self::getMsg('E301'),__LINE__);
			}
		}
		return true;

	}

	
}
