<?php
class ResourceCalendar_Table_Status {
	const DELETED = 3;
}

class ResourceCalendar_Response_Type {
	const JASON = 1;
	const HTML =2;
	const XML = 3;
	const JASON_406_RETURN = 4;
}

class ResourceCalendar_Status {
	const OPEN = 0;
	const CLOSE = 1;
}

class ResourceCalendar_Reservation_Status {
	const COMPLETE = 1;
	const TEMPORARY = 2;
	const CANCELED =  3;
	const INIT =  0;
}

class ResourceCalendar_Edit {
	const OK = 1;
	const NG = 0;
}

class ResourceCalendar_Config {
	const OPEN_TIME = '0900';
	const CLOSE_TIME ='1800';
	const TIME_STEP = 15;
	
	

	const DEFALUT_BEFORE_DAY = 3;
	const DEFALUT_AFTER_DAY = 100;

	const DETAIL_MSG_OK = 1;
	const DETAIL_MSG_NG = 2;
	const NAME_ORDER_JAPAN = 1;
	const NAME_ORDER_OTHER = 2;
	const LOG_NEED =1;
	const LOG_NO_NEED =2;
	const TAP_INTERVAL = 500;
	//
	const DEFALUT_RESERVE_DEADLINE = 30;
	const DEFALUT_RESERVE_DEADLINE_UNIT_DAY = 1;
	const DEFALUT_RESERVE_DEADLINE_UNIT_HOUR = 2;
	const DEFALUT_RESERVE_DEADLINE_UNIT_MIN = 3;


	const CONFIRM_NO = 1; 
	const CONFIRM_BY_ADMIN = 2;
	const CONFIRM_BY_MAIL = 3;
	
	const USER_ANYONE = 1;
	const USER_REGISTERED = 2;
	
	const SETTING_PATERN_TIME = 1;
	const SETTING_PATERN_ORIGINAL = 2;
	
	const RCAL_CONFIG_SHOW_DATEPICKER_CNT= 3;

	const USE_SESSION = 1;
	const USE_NO_SESSION = 2;

	const USE_SUBMENU = 1;
	const USE_NO_SUBMENU = 2;

	const REQUIRED = 1;

}


class ResourceCalendar_Color {
	const HOLIDAY = "#FFCCFF";
	const USUALLY = "#6699FF";
}

class ResourceCalendar_HOLIDAY_PATERN {
	const FULL = 0;
	const HALF = 1;
}

class ResourceCalendar_Category {
	const RADIO = 1;
	const CHECK_BOX = 2;
	const TEXT = 3;
	const SELECT = 4;
}


class ResourceCalendar_Component {
	
	private $version = '1.0';
	protected $datas = null;
	
//	protected $isAdmin = null;
	
//	protected $isStaff = null;
//	protected $isPromotion = null;
//	protected $isCustomer = null;

	
	public function __construct() {

	}

//ここからメール
	public function sendMail($set_data,$is_ConfirmbyCustomer = false) {
		if($set_data['status'] == ResourceCalendar_Reservation_Status::CANCELED) {
			$this->sendInformationMail($set_data,$set_data['mail'],$this->datas->getConfigData('RCAL_CONFIG_SEND_MAIL_SUBJECT_CANCELED'),$this->datas->getConfigData('RCAL_CONFIG_SEND_MAIL_TEXT_CANCELED'));
		}
		if 	($this->datas->getConfigData('RCAL_CONFIG_CONFIRM_STYLE') ==  ResourceCalendar_Config::CONFIRM_BY_ADMIN ) {
			//管理者が登録／更新（承認）したらユーザに対する完了メールだけ「予約が完了しました」
			if ($this->isPluginAdmin() ) {
				if($set_data['status'] != ResourceCalendar_Reservation_Status::CANCELED) {
					$this->sendInformationMail($set_data,$set_data['mail'],$this->datas->getConfigData('RCAL_CONFIG_SEND_MAIL_SUBJECT_COMPLETED'),$this->datas->getConfigData('RCAL_CONFIG_SEND_MAIL_TEXT_COMPLETED'));
				}
			}
			else {
				if($set_data['status'] != ResourceCalendar_Reservation_Status::CANCELED) {
					//管理者にメールを送る
					$this->sendInformationMail($set_data,$this->getMailOfAdminUser(),$this->datas->getConfigData('RCAL_CONFIG_SEND_MAIL_SUBJECT_ADMIN'),$this->datas->getConfigData('RCAL_CONFIG_SEND_MAIL_TEXT_ADMIN'));
					//ここではユーザに対してお知らせメールを送る。「予約を受け付けました」
					$this->sendInformationMail($set_data,$set_data['mail'],$this->datas->getConfigData('RCAL_CONFIG_SEND_MAIL_SUBJECT_ACCEPTED'),$this->datas->getConfigData('RCAL_CONFIG_SEND_MAIL_TEXT_ACCEPTED'));
				}
			}
		}
		elseif ($this->datas->getConfigData('RCAL_CONFIG_CONFIRM_STYLE') ==  ResourceCalendar_Config::CONFIRM_BY_MAIL ) {
			//管理者が登録したらユーザに対するお知らせメールだけ
			if ($this->isPluginAdmin() ) {
				if($set_data['status'] != ResourceCalendar_Reservation_Status::CANCELED) {
					$this->sendInformationMail($set_data,$set_data['mail'],$this->datas->getConfigData('RCAL_CONFIG_SEND_MAIL_SUBJECT_COMPLETED'),$this->datas->getConfigData('RCAL_CONFIG_SEND_MAIL_TEXT_COMPLETED'));
				}
			}
			else {
				//客が予約確定画面から更新したら客へ「予約が完了しました」
				if ($is_ConfirmbyCustomer) {
					if($set_data['status'] != ResourceCalendar_Reservation_Status::CANCELED) {
						$this->sendInformationMail($set_data,$set_data['mail'],$this->datas->getConfigData('RCAL_CONFIG_SEND_MAIL_SUBJECT_COMPLETED'),$this->datas->getConfigData('RCAL_CONFIG_SEND_MAIL_TEXT_COMPLETED'));
					}
				}
				//客が登録したら客へ「以下のＵＲＬで確認しろ」
				else {
					if ($set_data['status'] == ResourceCalendar_Reservation_Status::TEMPORARY) {
						$this->sendMailForConfirm($set_data);
					}
				}
			}
		}
		else {
			if($set_data['status'] != ResourceCalendar_Reservation_Status::CANCELED) {
			//即登録は完了した旨を客へ
				$this->sendInformationMail($set_data,$set_data['mail'],$this->datas->getConfigData('RCAL_CONFIG_SEND_MAIL_SUBJECT_COMPLETED'),$this->datas->getConfigData('RCAL_CONFIG_SEND_MAIL_TEXT_COMPLETED'));
			}
		}
		//スタッフへのお知らせはいつでも送る
		$this->sendInformationMail($set_data);
	}

	public function getMailOfAdminUser() {
		$mails = array();
		$users = get_users(array('role'=>'administrator'));
		foreach ($users as $k1 => $d1 ) {
			$mails[] = $d1->user_email;
		}
		return implode(',',$mails);
	}

	public function sendMailForConfirm($set_data) {

		$to = $set_data['mail'];
		$subject = sprintf($this->datas->getConfigData('RCAL_CONFIG_SEND_MAIL_SUBJECT').'[%d]',$set_data['reservation_cd']);
		$url = get_bloginfo( 'url' );
		$page = get_option('rcal_confirm_page_id');
		$send_mail_text = $this->datas->getConfigData('RCAL_CONFIG_SEND_MAIL_TEXT');
		
		$body = $send_mail_text;

		$url = sprintf('%s/?page_id=%d&P1=%d&P2=%s',$url,intval($page),intval($set_data['reservation_cd']),$set_data['activate_key']);

		$body = str_replace('{X-URL}',$url,$body);

		$resource_name = $this->datas->getResourceName($set_data['resource_cd']);

		$body = str_replace('{X-TO_NAME}',htmlspecialchars($set_data['name'],ENT_QUOTES),$body);
		
		$body = str_replace('{X-TO_TIME}',$set_data['target_day'].' '.$set_data['time_from'].' - '.$set_data['time_to'],$body);
		$body = str_replace('{X-TO_RESOURCE}',htmlspecialchars($resource_name,ENT_QUOTES),$body);
		$body = str_replace('{X-TO_REMARK}',htmlspecialchars($set_data['remark'],ENT_QUOTES),$body);

		$body = str_replace('{X-SHOP_NAME}',$this->datas->getConfigData('RCAL_CONFIG_NAME'),$body);
		$body = str_replace('{X-SHOP_ADDRESS}',$this->datas->getConfigData('RCAL_CONFIG_ADDRESS'),$body);
		$body = str_replace('{X-SHOP_TEL}',$this->datas->getConfigData('RCAL_CONFIG_TEL'),$body);
		$body = str_replace('{X-SHOP_MAIL}',$this->datas->getConfigData('RCAL_CONFIG_MAIL'),$body);


		$header = $this->datas->getConfigData('RCAL_CONFIG_SEND_MAIL_FROM');	
		if (!empty($header))	$header = "from:".$header."\n";

		add_action( 'phpmailer_init', array( &$this,'setReturnPath') );
		
		if (wp_mail( $to,$subject, $body,$header ) === false ) {
			$msg = error_get_last();
			throw new Exception(ResourceCalendar_Component::getMsg('E907',$msg['message']));
		}



	}
	
	public function sendInformationMail($set_data,$to = "",$subject = "",$send_mail_text = "") {
		//メールによる確認で管理者が登録した場合は、ユーザに完了のお知らせメッセージを送る
		if (empty($to) ) {
			$to = $this->datas->getConfigData('RCAL_CONFIG_SEND_MAIL_BCC');
		}
		if (!empty($to)  ){
			if (empty($subject) ) {
				$subject = $this->datas->getConfigData('RCAL_CONFIG_SEND_MAIL_SUBJECT_INFORMATION');
			}
			$header = $this->datas->getConfigData('RCAL_CONFIG_SEND_MAIL_FROM');	
			if (!empty($header))	$header = "from:".$header."\n";
			add_action( 'phpmailer_init', array( &$this,'setReturnPath') );
			if (empty($send_mail_text) ) {
				$send_mail_text = $this->datas->getConfigData('RCAL_CONFIG_SEND_MAIL_TEXT_INFORMATION');
			}
			
			$body = $send_mail_text;
			
			$status = "";
			if($set_data['status'] == ResourceCalendar_Reservation_Status::TEMPORARY) {
				$status  = __('Temporary Reservation',RCAL_DOMAIN);
			}
			else {
				if($set_data['status'] == ResourceCalendar_Reservation_Status::CANCELED) {
					$status  = __('Canceled Reservation',RCAL_DOMAIN);
				}
				else {
					$status  = __('Completed Reservation',RCAL_DOMAIN);
				}
			}
			$resource_name = "";
			//
			$type = $_POST['type'];
			if ($type == "deleted" ) $type="canceled";
//言語対応でダミーソースをいれとく
$d1 = __('exec',RCAL_DOMAIN);
$d1 = __('inserted',RCAL_DOMAIN);
$d1 = __('updated',RCAL_DOMAIN);
$d1 = __('canceled',RCAL_DOMAIN);
//言語対応でダミーソースをいれとく
			$status = sprintf(__('Action:%s Satus:%s',RCAL_DOMAIN),__($type,RCAL_DOMAIN),__($status,RCAL_DOMAIN));
			$resource_name = $this->datas->getResourceName($set_data['resource_cd']);
			
			$body = str_replace('{X-TO_STATUS}',$status,$body);
			$body = str_replace('{X-TO_NAME}',htmlspecialchars($set_data['name'],ENT_QUOTES),$body);
			$body = str_replace('{X-TO_TIME}',$set_data['target_day'].' '.$set_data['time_from'].' - '.$set_data['time_to'],$body);
			$body = str_replace('{X-TO_RESOURCE}',htmlspecialchars($resource_name,ENT_QUOTES),$body);
			$body = str_replace('{X-TO_REMARK}',htmlspecialchars($set_data['remark'],ENT_QUOTES),$body);

			
			$body = str_replace('{X-SHOP_NAME}',$this->datas->getConfigData('RCAL_CONFIG_NAME'),$body);
			$body = str_replace('{X-SHOP_ADDRESS}',$this->datas->getConfigData('RCAL_CONFIG_ADDRESS'),$body);
			$body = str_replace('{X-SHOP_TEL}',$this->datas->getConfigData('RCAL_CONFIG_TEL'),$body);
			$body = str_replace('{X-SHOP_MAIL}',$this->datas->getConfigData('RCAL_CONFIG_MAIL'),$body);

			if (wp_mail( $to,$subject, $body,$header ) === false ) {
				$msg = error_get_last();
				throw new Exception(ResourceCalendar_Component::getMsg('E907',$msg['message']));
			}

		}
	}
	
	
	public function setReturnPath( $phpmailer ) {
		$path = $this->datas->getConfigData('RCAL_CONFIG_SEND_MAIL_RETURN_PATH');
		if (empty($path)) return;
		$phpmailer->Sender = $path;
	}

//ここまでメール


	static function getMsg($err_cd, $add_char = '') {
		$err_msg = '';
		switch ($err_cd) {
			case 'I001':
				$err_msg = sprintf(__("This demo site can't insert,update and delete.",RCAL_DOMAIN),$add_char);
				break;	
			case 'N001':
				$err_msg = sprintf(__("%s Normal end",RCAL_DOMAIN),$add_char);
				break;	
			case 'E002':
				$err_msg = sprintf(__("This user not registerd",RCAL_DOMAIN));
				break;	
			case 'E007':
				$err_msg = sprintf(__("%s An unexpected error has occurred %s",RCAL_DOMAIN),$err_cd,$add_char);
				break;	
			case 'E008':
				$err_msg = sprintf(__("Sorry! This page not displayed. checks cookies on ? %s ",RCAL_DOMAIN),$add_char);
				break;	
			case 'E011':
				$err_msg = sprintf(__("This reservation has expired. [%s]",RCAL_DOMAIN),$add_char);
				break;	
			case 'E012':
				$err_msg = sprintf(__("This reservation updated. [%s]",RCAL_DOMAIN),$add_char);
				break;	
			case 'E021':
				$err_msg = sprintf(__("This request is invalid nonce. [%s]",RCAL_DOMAIN),$add_char);
				break;	
			case 'E201':
				$err_msg = sprintf(__("%s Required[%s]",RCAL_DOMAIN),$err_cd,$add_char);
				break;	
			case 'E202':
				$err_msg = sprintf(__("%s This is not time data[%s]",RCAL_DOMAIN),$err_cd,$add_char);
				break;	
			case 'E203':
				$err_msg = sprintf(__("%s Numeric input please[%s]",RCAL_DOMAIN),$err_cd,$add_char);
				break;	
			case 'E205':
				$err_msg = sprintf(__("%s Zip code XXXXX-XXXX input please[%s]",RCAL_DOMAIN),$err_cd,$add_char);
				break;	
			case 'E206':
				$err_msg = sprintf(__("%s Telephone XXXX-XXX-XXXX input please[%s]",RCAL_DOMAIN),$err_cd,$add_char);
				break;	
			case 'E207':
				$err_msg = sprintf(__("%s XXX@XXX.XXX input please[%s]",RCAL_DOMAIN),$err_cd,$add_char);
				break;	
			case 'E208':
				$err_msg = sprintf(__("%s MM/DD/YYYY or MMDDYYYY input please[%s]",RCAL_DOMAIN),$err_cd,$add_char);
				break;	
			case 'E209':
				$err_msg = sprintf(__("%s This day not exist[%s]",RCAL_DOMAIN),$err_cd,$add_char);
				break;	
			case 'E210':
				$err_msg = sprintf(__("%s Need to space input between fires-name and last-name[%s]",RCAL_DOMAIN),$err_cd,$add_char);
				break;	
			case 'E211':
				$err_msg = sprintf(__("%s Within %d characters[%s]",RCAL_DOMAIN),$err_cd,$add_char[0],$add_char[1]);
				break;	
			case 'E213':
				$err_msg = $err_cd.' '.__("This time zones can not be reserved",RCAL_DOMAIN);
				break;	
			case 'E214':
				$err_msg = sprintf(__("%s This time zones are unacceptable.[%s] [%s]",RCAL_DOMAIN),$err_cd,$add_char[0],$add_char[1]);
				break;	
			case 'E215':
				$err_msg = sprintf(__("Valid period (from) <= Valid period (to)",RCAL_DOMAIN));
				break;	
			case 'E301':
				$err_msg = sprintf(__("This time periods already reserved. ",RCAL_DOMAIN),$add_char);
				break;
			case 'E302':
				$err_msg = sprintf(__("This time periods are out of ranges. ",RCAL_DOMAIN),$add_char);
				break;
			case 'E401':
				$err_msg = __('An unexpected error has occurred',RCAL_DOMAIN);
				break;	
			case 'E901':
				$err_msg = sprintf(__("This data is unacceptble.Bug?[%s]",RCAL_DOMAIN),$add_char);
				break;	
			case 'E902':
				$err_msg = sprintf(__("Database error [%s][%s]",RCAL_DOMAIN),$add_char[0],$add_char[1]);
				break;	
			case 'E904':
				$err_msg = sprintf(__("File open error[%s]",RCAL_DOMAIN),$add_char);
				break;	
			case 'E906':
				$err_msg = sprintf(__("The target data not found",RCAL_DOMAIN));
				break;	
			case 'E908':
				$err_msg = sprintf("This access is out of the authority[%s]",$add_char);
				break;
			case 'E909':
				$err_msg = sprintf(__("This reservation already updated.",RCAL_DOMAIN),$add_char);
				break;
			default:
				$err_msg = $err_cd.__("This message is not found",RCAL_DOMAIN).$add_char;
				
		}
		return $err_msg;
	}
	
	static function computeDate($addDays = 1,$year = null, $month = null , $day =null) {
		if ( empty($year) ) $year = date_i18n("Y");
		if ( empty($month) ) $month = date_i18n("m");
		if ( empty($day) ) $day = date_i18n("d");
		$baseSec = mktime(0, 0, 0, $month, $day, $year);
		$addSec = $addDays * 86400;
		$targetSec = $baseSec + $addSec;
		return date("Y-m-d H:i:s", $targetSec);
	}
	
	static function getMonthEndDay($year, $month) {
		$dt = mktime(0, 0, 0, $month + 1, 0, $year);
		return date("d", $dt);
	}
	
	static function computeMonth($addMonths=1,$year = null, $month = null , $day =null) {
		if ( empty($year) ) $year = date_i18n("Y");
		if ( empty($month) ) $month = date_i18n("m");
		if ( empty($day) ) $day = date_i18n("d");
		$month += $addMonths;
		$endDay = self::getMonthEndDay($year, $month);
		if($day > $endDay) $day = $endDay;
		$dt = mktime(0, 0, 0, $month, $day, $year);
		return date("Y-m-d H:i:s", $dt);
	}
	
	static function computeYear($addYears=1,$year = null, $month = null , $day =null) {
		if ( empty($year) ) $year = date_i18n("Y");
		if ( empty($month) ) $month = date_i18n("m");
		if ( empty($day) ) $day = date_i18n("d");
		$year += $addYears;
		$dt = mktime(0, 0, 0, $month, $day, $year);
		return date("Y-m-d H:i:s", $dt);
	}
	
	static function zenSp2han($in) {
		if (function_exists( 'mb_convert_kana' )) {
			return  mb_convert_kana($in,"s");
		}
		else {
			return $in;
		}
	}
	
	static function checkTime($time_data) {
		$hour = +substr($time_data,0,2); 
		if ($hour < 0 || $hour > 23 || !is_numeric($hour)) {
			return false;
		}
		$min = +substr($time_data,2); 
		if ($min < 0 || $min > 59 || !is_numeric($min)) {
			return false;
		}
		return true;
	}


	static function formatTime($time_data) {
		return sprintf("%02s:%02s",+substr($time_data,0,2),substr($time_data,2,2));
	}
	
	static function replaceTimeToDb($time_data) {
		if (preg_match('/(?<hour>\d+):(?<minute>\d+)/', $time_data, $matches) == 0 ) {
			$matches['hour'] = substr($time_data,0,2);
			$matches['minute'] = substr($time_data,2,2);
		}
		return sprintf("%02d%02d",+$matches['hour'],+$matches['minute']);
	}
	
	
	static function editRequestYmdForDb($in) {
		if (empty($in) ) return;
		if (preg_match('/^'.__('(?<month>\d{1,2})[\/\.\-](?<day>\d{1,2})[\/\.\-](?<year>\d{4})',RCAL_DOMAIN).'$/',$in,$matches) == 0 )  
		   preg_match('/^'.__('(?<month>\d{2})(?<day>\d{2})(?<year>\d{4})',RCAL_DOMAIN).'$/',$in,$matches); 
		return sprintf("%4d-%02d-%02d",+$matches['year'],+$matches['month'],+$matches['day']);
	}
	
	static function getDayOfWeek($in) {
		return date("w", strtotime($in));
	}
	
	static function isMobile(){

		$useragents = array(
			'iPhone', // iPhone
			'iPod', // iPod touch
			'Android.*Mobile', // 1.5+ Android *** Only mobile
			'Windows.*Phone', // *** Windows Phone
			'dream', // Pre 1.5 Android
			'CUPCAKE', // 1.5+ Android
			'blackberry9500', // Storm
			'blackberry9530', // Storm
			'blackberry9520', // Storm v2
			'blackberry9550', // Storm v2
			'blackberry9800', // Torch
			'webOS', // Palm Pre Experimental
			'incognito', // Other iPhone browser
			'webmate' // Other iPhone browser
		);
		$pattern = '/'.implode('|', $useragents).'/i';
		return (preg_match($pattern, $_SERVER['HTTP_USER_AGENT']) == 1) ;
	}
	
	static function calcMinute($from,$to) {
		//$from toはHHMM
		if (strlen($from) == 3 ) $from = '0'.$from;
		if (strlen($to) == 3 ) $to = '0'.$to;
		$pasttime=strtotime('2000/01/01 '.sprintf("%s:%s:00",substr($from,0,2),substr($from,2,2)));
		$thistime=strtotime('2000/01/01 '.sprintf("%s:%s:00",substr($to,0,2),substr($to,2,2)));
		$diff=$thistime-$pasttime;
		return floor($diff/60);
	}

	static function checkRole($class_name) {
		$class_name_array = explode('_',$class_name);
		if (empty($class_name_array[0]) ) {
				throw new Exception(self::getMsg('E908',basename(__FILE__).':'.__LINE__),1);
		}
		$target_name = strtolower ($class_name_array[0]);
		if ( $target_name == 'booking'  ) return;
		if ( $target_name == 'confirm'  ) return;
		global $current_user;
		get_currentuserinfo();
		$user_roles = $current_user->roles;
		$user_role = array_shift($user_roles);
		
		//このプラグインでは寄稿者は管理させない
		if (empty($user_role) || $user_role == 'subscriber' ) {
				throw new Exception(self::getMsg('E908',basename(__FILE__).':'.__LINE__),1);
		}
	}
	
	//ここは後で権限のロジックをいれるためにきりだしておく
	//ここはログインユーザの権限を調べる
	//
	public function isPluginAdmin(){	
		//シングルサイトの場合は、ユーザ削除権限がある人

		if ( is_super_admin() ) {
			return true;
		}
		return false;

	}
	
	
	
	
}