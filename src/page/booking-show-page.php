<?php

	$url =   get_permalink();
	$parts = explode('/',$url);
	$addChar = "?";
	if (strpos($parts[count($parts)-1],"?") ) {
		$addChar = "&";
	}
	$url = $url.$addChar."rcal_desktop=true";
	$edit_resource = array();
	$limit_resource = array();
	$reserve_possible_cnt = 0;
	
	$chk_from = str_replace("-","",substr($this->valid_from,0,10));
	$chk_to = str_replace("-","",substr($this->valid_to,0,10));
	$limit_exist = false;	
	foreach ($this->resource_datas as $k1 => $d1 ) {
		if (($chk_from <= $d1['chk_to']  && $d1['chk_to'] <= $chk_to ) ||
			($chk_from <= $d1['chk_from']  && $d1['chk_from'] <= $chk_to ) ){
			$limit_exist = true;
		}
		if ( empty($d1['photo_result'][0]) ) {
			$tmp='<sapn class="rcal_noimg" >'.htmlspecialchars($d1['name'],ENT_QUOTES).'</span>';
			$tmp2="";
		}
		else {
			$tmp = "<img src='".$d1['photo_result'][0]['photo_resize_path']."' alt='' />";
			$url = site_url();
			$url = substr($url,strpos($url,':')+1);
			$url = str_replace('/','\/',$url);
			$tmp2 = $d1['photo_result'][0]['photo_path'];
			if (is_ssl() ) {
				$tmp = preg_replace("/([hH][tT][tT][pP]:".$url.")/","https:".$url,$tmp);
				$tmp2 = preg_replace("/([hH][tT][tT][pP]:".$url.")/","https:".$url,$tmp2);
			}
			else {
				$tmp = preg_replace("/([hH][tT][tT][pP][sS]:".$url.")/","http:".$url,$tmp);
				$tmp2 = preg_replace("/([hH][tT][tT][pP][sS]:".$url.")/","http:".$url,$tmp2);
			}
		}

		$edit_resource[$d1['resource_cd']]['img'] = $tmp;
		$edit_resource[$d1['resource_cd']]['label'] = htmlspecialchars($d1['name'],ENT_QUOTES);
		$edit_resource[$d1['resource_cd']]['href'] = $tmp2;

		$limit_resource[] = array($d1['resource_cd'],$d1['chk_from'] ,$d1['chk_to'] );

	}
	$init_target_day = date_i18n('Ymd');
	$resource_holiday_class = "rcal_holiday";
	$resource_holiday_set = __('Holiday',RCAL_DOMAIN);
	//必須項目
	$require_array = unserialize($this->config_datas['RCAL_CONFIG_REQUIRED']);
	$nameRequire = in_array('rcal_name',$require_array) ? "required": ""; 
	$telRequire = in_array('rcal_tel',$require_array) ? "required": ""; 
	$mailRequire = in_array('rcal_mail',$require_array) ? "required": ""; 
	if ($this->config_datas['RCAL_CONFIG_ENABLE_RESERVATION'] == ResourceCalendar_Config::CONFIRM_BY_MAIL ) {
		$mailRequire = "required";
	}
	
?>
<div id="rcal_content" role="main">

<?php if ($this->config_datas['RCAL_CONFIG_CAL_SIZE'] !== 100) : ?>
	<style>
		.ui-datepicker {
			font-size: <?php echo $this->config_datas['RCAL_CONFIG_CAL_SIZE']; ?>%;
		}
		.entry-content th,td {
			font-size: <?php echo $this->config_datas['RCAL_CONFIG_CAL_SIZE']; ?>%;
			padding:0px;
		}
	</style>
<?php endif; ?>
	
	<script type="text/javascript" charset="utf-8">
		var $j = jQuery;
		var top_pos;
		var bottom_pos;
		var today = "<?php echo $init_target_day; ?>";
		
		var target_day_from = new Date();
		var target_day_to = new Date();
		var operate = "";
		var save_id = "";

		var is_limitExist = <?php if ($limit_exist) echo "true"; else echo "false"; ?>;
		
		var is_holiday= false;

		var selected_day;	<?php //表示用と設定用でわけとく ?>

		var isTouch = ('ontouchstart' in window);
		var tap_interval = <?php echo ResourceCalendar_Config::TAP_INTERVAL; ?>;

		var resource_items = new Array();

		var save_user_login = "";
		
		var setMonth = new Object();

		rcalSchedule.config={
					days: []
					,days_detail:[]
					,full_half : []
					,day_full:[<?php _e('"Sunday","Monday","Tuesday","Wednesday","Thursday","Friday","Saturday"',RCAL_DOMAIN); ?>]
					,day_short:[<?php _e('"Sun","Mon","Tue","Wed","Thu","Fri","Sat"',RCAL_DOMAIN); ?>]
					,resource_holidays:[]
					,open_time:"<?php echo($this->config_datas['RCAL_CONFIG_OPEN_TIME']); ?>"
					,close_time:"<?php echo($this->config_datas['RCAL_CONFIG_CLOSE_TIME']); ?>"
					,step:<?php echo ($this->config_datas['RCAL_CONFIG_TIME_STEP']); ?>
		}

<?php 
		foreach ( $this->month_datas as $k1 => $d1 ) {
			echo 'rcalSchedule._months["'.$k1.'"]= {};';
			foreach ( $d1 as $k2 => $d2 ) {
				$title_data = array();
				$exist_tentative = false;
				foreach ($d2 as $k3=>$d3 ) {
					if ($k3 == ResourceCalendar_Reservation_Status::COMPLETE ){
						$title_data [] = sprintf(__('Completed Reservations',RCAL_DOMAIN).":%d ",$d3);
					}
					else if ($k3 == ResourceCalendar_Reservation_Status::TEMPORARY ){
						$title_data[] = sprintf(__('Temporary Reservations',RCAL_DOMAIN).":%d  ",$d3);
						$exist_tentative = true;
					}
				}
				echo 'rcalSchedule._months["'.$k1.'"]["'.$k2.'"]= "'.implode('\n',$title_data).'";';
				if ($exist_tentative ) {
					echo 'rcalSchedule._months["'.$k1.'"]["'.$k2.'_flg"]= true;';
				}
			}
		}
		foreach ($this->resource_datas as $k1 => $d1 ) {
				echo 'var fr = fnSetDay("'.$d1['chk_from'].'");';
				echo 'var to = fnSetDay("'.$d1['chk_to'].'");';
				echo 'rcalSchedule.config.resource_holidays["'.$d1['resource_cd'].'"]= [fr,to];';
		}
	if ($this->config_datas['RCAL_CONFIG_USE_SUBMENU'] == ResourceCalendar_Config::USE_SUBMENU) {
		//カテゴリーのパターンを設定する		
		echo 'var category_patern = new Object();';
		foreach($this->category_datas as $k1 => $d1 ) {
			echo 'category_patern["i'.$d1['category_cd'].'"]='.$d1['category_patern'].';';
		}
	}
?>

		$j(window).on('resize', function(){
			_fnCalcDisplayMonth();
			AutoFontSize();
			setDayData(fnDayFormat(selected_day,"%Y%m%d"));
		});

		function _fnGetMonthData(base_day){
			<?php //base_day YYYYMM?>
			var yyyy = base_day.substr(0,4);
			var mm = base_day.substr(-2);
			var last = new Date(yyyy,mm,0); <?php //翌月の0日=今月末 ?>
			$j.ajax({
				 	type: "post",
					url:  "<?php echo get_bloginfo( 'wpurl' ); ?>/wp-admin/admin-ajax.php?action=rcalbooking",
					dataType : "json",
					data: {
						"from":yyyy+'-'+mm+'-1',
						"to":$j.format.date(last, "yyyy-MM-dd"),
						"nonce":"<?php echo $this->nonce; ?>",
						"menu_func":"Booking_Get_Month"
					},

					success: function(data) {
						rcalSchedule._months[data.set_data.yyyymm] = {};
						if (data.set_data.cnt > 0 ) {
//							var tmp_target_day = "";
//							var index = 0;
//							var tmp_array = new Object();
//							for(var k1 = 0 ;k1 < data.cnt ;k1++) {
//								if (tmp_target_day == "" ) tmp_target_day = data.datas[k1]["target_day"];
//								if ( tmp_target_day != data.datas[k1]["target_day"]) {
//									getReservation[tmp_target_day] = tmp_array;
//									tmp_array = new Object();
//									index = 0;
//								}
//								tmp_array[index++] = data.datas[k1];
//								tmp_target_day = data.datas[k1]["target_day"];
//							}
//							getReservation[tmp_target_day] = tmp_array;
						}
					},
					error:  function(XMLHttpRequest, textStatus){
						alert (textStatus);
					}
			 });			
		}
		
		function existMonthData(targetDate ) {
			if (!rcalSchedule._months[yyyymm]) return false;
			return true;
		}



		$j(document).ready(function() {


			<?php parent::echoSearchCustomer($this->url); //検索画面 ?>	

			<?php parent::set_datepicker_date($this->config_datas['RCAL_SP_DATES']); ?>
			
			<?php  parent::set_datepickerDefault(false,true); ?>
			<?php  
				$addCode = '
						,onChangeMonthYear: function( year, month, inst ) {

							var lastMonth = new Date(year ,month-1,1);
							lastMonth.setMonth(lastMonth.getMonth()-1);
							var yyyymm = lastMonth.getFullYear() + ("0"+(lastMonth.getMonth()+1)).slice(-2);
							if (!rcalSchedule._months[yyyymm]) {
								_fnGetMonthData(yyyymm);
							}
							var nextMonth = new Date(year ,month+1,1);
							nextMonth.setMonth(nextMonth.getMonth()+1);
							yyyymm = nextMonth.getFullYear() + ("0"+(nextMonth.getMonth()+1)).slice(-2);
							if (!rcalSchedule._months[yyyymm]) {
								_fnGetMonthData(yyyymm);
							}
						
					}';

//				$addCode .= ',changeMonth: false,onSelect: function(dateText, inst) { _fnSetResource(dateText.replace(/'.__('\/',RCAL_DOMAIN).'/g,""));setDayData(dateText.replace(/'.__('\/',RCAL_DOMAIN).'/g,"")); }';
				$addCode .= ',changeMonth: false,onSelect: function(dateText, inst) { var yyyymmdd = _fnTextDateReplace(dateText); _fnSetResource(yyyymmdd);setDayData(yyyymmdd); }';
				$display_month = 1;
				
				if ( !ResourceCalendar_Component::isMobile() ) $display_month = $this->config_datas['RCAL_CONFIG_SHOW_DATEPICKER_CNT'];

				parent::set_datepicker("rcal_calendar",false,$this->config_datas['RCAL_CONFIG_CLOSED'],$addCode,$display_month,true); 
			?>			
			_fnCalcDisplayMonth();
			var timer;

			<?php parent::echoSetHolidayMobile($this->resource_datas,$this->target_year);	?>
			
			<?php 
				foreach($this->resource_datas as $k1 => $d1 ) {
					if ($d1['setting_patern_cd'] == ResourceCalendar_Config::SETTING_PATERN_ORIGINAL)
						echo 'resource_items['.$d1['resource_cd'].'] = true;';
				}
			?>
			
			$j("#rcal_page_regist").hide();
			var top = 	$j("#rcal_main_data").outerHeight()	- $j("#rcal_holiday").css("font-size").toLowerCase().replace("px","");
			$j("#rcal_holiday").css("padding-top",top / 2 + "px");
			$j("#rcal_holiday").height($j("#rcal_main_data").outerHeight()- (top/2));			
			$j("#rcal_holiday").width($j("#rcal_main_data").outerWidth());	
			$j("#rcal_holiday").hide();
			
			$j("#rcal_regist_button").click(function(){
<?php if (RCAL_DEMO ) : ?>
			$j("#rcal_login_div").hide();
<?php endif; ?>

				var now = new Date(); 
				now.setMinutes(now.getMinutes()+<?php echo $this->config_datas['RCAL_CONFIG_RESERVE_DEADLINE']; ?>);
				_fnAddReservation(now.getHours()+1);


				$j('#rcal_resource_cd').prop('selectedIndex', 0).change();
				$j("#rcal_exec_regist").text("<?php _e('Booking',RCAL_DOMAIN); ?>");
			<?php 	if (is_user_logged_in() && (! $this->isPluginAdmin()) ) : ?>
						$j("#rcal_name").val("<?php echo $this->user_inf["user_name"]; ?>");
						$j("#rcal_tel").val("<?php echo $this->user_inf["set_tel"]; ?>");
						$j("#rcal_mail").val("<?php echo $this->user_inf["user_email"]; ?>");
						save_user_login = "<?php echo $this->user_inf["user_login"]; ?>"
			<?php endif; ?>				
				

			});
			$j("#rcal_mainpage").click(function(){
				$j("#rcal_page_main").show();
				$j("html,body").animate({ scrollTop: top_pos }, 'fast');
			});
			$j("#rcal_mainpage_regist").click(function(){
				$j("#rcal_page_main").show();
				$j("#rcal_page_regist").hide();
				_fnCalcDisplayMonth();
				$j("html,body").animate({ scrollTop: top_pos }, 'fast');
<?php if (RCAL_DEMO ) : ?>
			$j("#rcal_login_div").show();
<?php endif; ?>
			});
			$j("#rcal_exec_regist").click(function(){
				_UpdateEvent();
			});
			
			$j("#rcal_exec_delete").click(function() {
				if (confirm("<?php _e("This reservation delete ?",RCAL_DOMAIN); ?>") ) {
					operate = "deleted";				
					_UpdateEvent();
				}
			});

			$j(".rcal_patern_original_sel").change(function(){
				var time_fromto = $j(this).val();
				if (time_fromto) {
					var time_fromto_array = time_fromto.split("-");
					target_day_from.setHours(+time_fromto_array[0].substr(0,2));
					target_day_from.setMinutes(+time_fromto_array[0].substr(3,2));
					target_day_to.setHours(+time_fromto_array[1].substr(0,2));
					target_day_to.setMinutes(+time_fromto_array[1].substr(3,2));
				}
				else {
					alert("<?php _e('select please',RCAL_DOMAIN); ?>");
				}
			});
			

			if (document.getElementById("rcal_today") != null ) {
				$j("#rcal_today").click(function() {
					setDayData(today);
					$j("#rcal_calendar").datepicker("setDate", fnDayFormat(selected_day,"<?php echo __('%m/%d/%Y',RCAL_DOMAIN); ?>"));
				});
			}

			$j(document).on('click','.rcal_on_business',function(){
				var tmp_val = $j(this.children).text();
				_fnAddReservation(+tmp_val.split(":")[1]);
				$j("#rcal_resource_cd").val(tmp_val.split(":")[0]).change();
			});
			
			$j("#rcal_resource_cd").change(function(){
				$j("#rcal_setting_patern_time_wrap").hide();
				$j(".rcal_patern_original").hide();
				var sel = $j(this).val();
				if (sel ) {
					if (resource_items[sel]) {
						$j("#rcal_setting_patern_"+sel+"_wrap").show();
						$j("#rcal_setting_patern_"+sel).prop("selectedIndex", 0).change();
						
					}
					else {
						$j("#rcal_setting_patern_time_wrap").show();
						$j("#rcal_time_from").prop("selectedIndex", 0).change();
						$j("#rcal_time_to").prop("selectedIndex", 0).change();
					}
				}
				
			});
			
			$j("#rcal_searchdate").change(function(){
				var in_date = _fnTextDateReplace( $j("#rcal_searchdate").val() );
				<?php //in_dateはYYYYMMDD形式で戻る ?>
				if ( in_date === false ) return;
				setDayData(in_date);
			});

			$j(".rcal_time_li").click(function() {
<?php if (RCAL_DEMO ) : ?>
			$j("#rcal_login_div").hide();
<?php endif; ?>
				var tmp_resource_cd = this.parentElement.id.split("_")[2];
				var tmp_time = +$j(this).children().text();
				if (! tmp_time ) tmp_time = <?php echo  +substr($this->config_datas['RCAL_CONFIG_OPEN_TIME'],0,2); ?>;
				_fnAddReservation(tmp_time);
//				$j("#rcal_resource_cd").val(tmp_resource_cd).change();
				$j("#rcal_resource_cd").val(tmp_resource_cd);
				<?php //ここで時間を設定しないと上のchangeでクリアされる　?>
				
				$j("#rcal_setting_patern_time_wrap").hide();
				$j(".rcal_patern_original").hide();
				if (resource_items[tmp_resource_cd]) {
					$j("#rcal_setting_patern_"+tmp_resource_cd+"_wrap").show();
					$j("#rcal_setting_patern_"+tmp_resource_cd).prop("selectedIndex", 0).change();
				}
				else {
					$j("#rcal_setting_patern_time_wrap").show();
					$j("#rcal_time_from").val(toHHMM(target_day_from));
					$j("#rcal_time_to").val(toHHMM(target_day_to));
				}
			});
			
			<?php parent::echoSetItemLabelMobile(); ?>
			<?php
				$res = parent::echoMobileData($this->reservation_datas,date_i18n('Ymd') ,$this->first_hour);
				$from = substr($this->valid_from,0,10);
				$to = str_replace("-","",substr($this->valid_to,0,10));
				for($i=0;$i<10000;$i++) {
					$target =  date("Ymd",strtotime($from." + ".($i+1)." days") );
					if (array_key_exists($target,$res)) {
						echo "rcalSchedule._daysResource[".$target."] = ".$res[$target].";";
					}
					else {
						echo "rcalSchedule._daysResource[".$target."] = {\"e\":0};";
					}
					if ($target == $to ) break;
				}

			?>

			<?php /*?>ヘッダがどんなかわからないのでいちづけと */?>
			top_pos = $j("#rcal_main").offset().top;
			bottom_pos = top_pos + $j("#rcal_main").height();
			$j("html,body").animate({ scrollTop: top_pos }, 'fast');
			
			AutoFontSize();

			setDayData(today);
		
	});

		<?php parent::echoCheckDeadline	($this->config_datas['RCAL_CONFIG_RESERVE_DEADLINE']); ?>

		<?php parent::echoTextDateReplace(); ?>



		function _fnSetResource(date) {
<?php //リソースで期限指定がある場合は、日によって出力するコンボの内容を変える ?>
			if (is_limitExist) {
				
				$j('#rcal_resource_cd').children().remove();
<?php
		$echo_data = '';
		foreach($this->resource_datas as $k1 => $d1 ) {
			$from = +$d1['chk_from'];
			$name = htmlspecialchars($d1['name'],ENT_QUOTES);
			echo <<<EOT
			if ( (${from} <= date ) && ( date <= ${d1['chk_to']} ) ) {
				\$j("#rcal_resource_cd").append("<option value=\"${d1['resource_cd']}\">${name}</option>");
			}
EOT;
		}
		echo $echo_data;
?>
			}
		}
		
		function _fnMakeTimeItem() {
			$j('#rcal_setting_patern_time_wrap').children().remove();
			
			var setcn = rcalSchedule.makeSelectDate(selected_day);
			$j('#rcal_setting_patern_time_wrap').append('<ul><li class="rcal_li"><select id="rcal_time_from" name="rcal_time_from" class="rcal_sel rcal_time" >'+setcn+'</select></li></ul>');
			$j('#rcal_setting_patern_time_wrap').append('<ul><li class="rcal_li"><select id="rcal_time_to" name="rcal_time_from" class="rcal_sel rcal_time" >'+setcn+'</select></li></ul>');


			$j("#rcal_time_from").attr("placeholder",check_items["rcal_time_from"]["label"]);
			$j("#rcal_time_from").parent().before('<li class="rcal_label"><label id="rcal_time_from_lbl" for="rcal_time_from" >'+check_items["rcal_time_from"]["label"]+':</label></li>');
			$j("#rcal_time_to").attr("placeholder",check_items["rcal_time_to"]["label"]);
			$j("#rcal_time_to").parent().before('<li class="rcal_label"><label id="rcal_time_to_lbl" for="rcal_time_to" >'+check_items["rcal_time_to"]["label"]+':</label></li>');


			$j('#rcal_time_from').on('change',function(){
				var start  = $j(this).val();
				if (start && start != -1 )	{
					target_day_from.setHours(+start.substr(0,2));
					target_day_from.setMinutes(+start.substr(3,2));
				}
			});
			$j('#rcal_time_to').on('change',function(){
				var end  = $j(this).val();
				if (end && end != -1 )	{
					target_day_to.setHours(+end.substr(0,2));
					target_day_to.setMinutes(+end.substr(3,2));
				}
			});

		}
		
		function _fnCalcDisplayMonth() {

			var screen_cnt = $j(".ui-datepicker-inline").children().length;
			var base = $j(".ui-datepicker-group-first").width();
			if ( ! base  ) {
				base = $j("#rcal_calendar").children().width();
				if (! base ) return;
			}
			var w = $j("#rcal_content").width() ;
			if (w > base * 3 ) {
				$j("#rcal_calendar").datepicker("option", "numberOfMonths", 3);
			}
			else if (w > base * 2 ) {
				$j("#rcal_calendar").datepicker("option", "numberOfMonths", 2);
			}
			else {
				$j("#rcal_calendar").datepicker("option", "numberOfMonths", 1);
			}
		}

		function _fnAddReservation (startHour) {
			<?php //過去は予約できないようにしとく ?>
			var chk_date = 	new Date(selected_day);
			if (startHour) { 
				chk_date.setHours(startHour); 
			}
			var now = new Date();

			if (!_checkDeadline(chk_date) ) return;

			$j("#rcal_page_main").hide();
			$j("#rcal_page_regist").show();
			$j("#rcal_exec_delete").hide();
			$j("#rcal_target_day").text(fnDayFormat(selected_day,"<?php echo __('%m/%d/%Y',RCAL_DOMAIN); ?>"));
			target_day_from = new Date(selected_day);
			if (startHour) { 
				target_day_from.setHours(startHour); 
			}
			target_day_to = new Date(target_day_from.getTime());
			operate = "inserted";
			setStatus();
			save_id = "";


			_fnMakeTimeItem();

			$j("#rcal_time_from").val(toHHMM(target_day_from));
			$j("#rcal_time_to").val(toHHMM(target_day_to));
			
			$j("#rcal_exec_regist").text("<?php _e('Booking',RCAL_DOMAIN); ?>");
			$j("#rcal_remark").val("");
			
			
		}
		
		function _fnCalcDay(ymd,add) {
			var clas = Object.prototype.toString.call(ymd).slice(8, -1);
			if (clas !== 'Date') {
				return ymd;
			}
			var tmpDate = ymd.getDate();
			ymd.setDate(tmpDate + add);
			return fnDayFormat(ymd,"%Y%m%d");
		}
		
		function setDayData(yyyymmdd) {
			yyyymmdd=yyyymmdd+"";
			<?php //すでにエラーになっている場合は、表示しておしまい ?>
			if (rcalSchedule._daysResource[yyyymmdd]) {
				if (rcalSchedule._daysResource[yyyymmdd]["err"]) {
					<?php //エラーなのでDatePickerの日付を戻す ?>
					$j("#rcal_calendar").datepicker("setDate", fnDayFormat(selected_day,"<?php echo __('%m/%d/%Y',RCAL_DOMAIN); ?>"));
					alert(rcalSchedule._daysResource[yyyymmdd]["err"]);
					return false;
				}
			}
			<?php //初めての日付はサーバへ ?>
			else {
				_GetEvent(yyyymmdd);
				 return;
			}
			var yyyy = yyyymmdd.substr(0,4);
			var mm = yyyymmdd.substr(4,2);
			var dd = yyyymmdd.substr(6,2);
			selected_day = new Date(yyyy, +mm - 1,dd);
			
			
			$j("#rcal_searchdate").val(fnDayFormat(selected_day,"<?php echo __('%m/%d/%Y',RCAL_DOMAIN); ?>"));
			$j(".rcal_tile").off("click");
			$j(".rcal_tile").remove();
			$j(".rcal_resource_holiday").remove();
			
			$j("#rcal_searchdays").text(rcalSchedule.config.day_full[selected_day.getDay()]);

<?php			//各liの幅が異なるので配列で ?>
			var tmp_width = Array();
<?php			//単純にひとつめだと期間によって消えている場合がある。 ?>
			var search_idx = null;
			$j("#rcal_main_data ul").each(function(){
				if ($j(this).is(':visible')) {
					search_idx = $j(this).attr("id");
				}
			});
			$j("#"+search_idx+" li.rcal_time_li").each(function(){
				tmp_width.push($j(this).outerWidth());
			});
<?php			//予約の部分でも使用 ?>
			var left_start = $j("#"+search_idx+" li:first-child").outerWidth();
			
			var setWidth = tmp_width.join(",");
			rcalSchedule.setWidth(setWidth);

			<?php		//リソース単位の使えない日　?>
			for ( var i = 0 ; i < rcalSchedule.config.resource_holidays.length ; i ++ ) {
				if ( rcalSchedule.config.resource_holidays[i] ) {
					if ((rcalSchedule.config.resource_holidays[i][0] <=  selected_day) && 
						(selected_day <= rcalSchedule.config.resource_holidays[i][1]  )) {				
						$j("#rcal_st_"+i).show();
					}
					else {
						$j("#rcal_st_"+i).hide();
					}
				}
			}
			<?php		//休みだったら ?>
			if (rcalSchedule.chkHoliday(selected_day) ) {
				
				var top = 	$j("#rcal_main_data").outerHeight()	- $j("#rcal_holiday").css("font-size").toLowerCase().replace("px","");
				$j("#rcal_holiday").css("padding-top",top / 2 + "px");
				$j("#rcal_holiday").height($j("#rcal_main_data").outerHeight()- (top/2));			
				$j("#rcal_holiday").css("left",rcalSchedule.getHolidayLeft(selected_day,left_start));	
				$j("#rcal_holiday").width(rcalSchedule.getHolidayWidth(selected_day));	
				$j("#rcal_holiday").show();
				if (rcalSchedule.chkFullHoliday(selected_day) ) {
					$j("#rcal_regist_button").hide();
					return;
				}
				else {
					$j("#rcal_regist_button").show();
				}
			}
			else {
				$j("#rcal_holiday").hide();
				$j("#rcal_regist_button").show();
			}
<?php //過去の場合は予約ボタンをおせないように ?>
			if (yyyymmdd < <?php echo $init_target_day; ?> ) {
				$j("#rcal_regist_button").hide();
			}

<?php /*
		tmpb:0:左開始位置（5分単位） 1:幅 2:イベントID（ログインしていない場合はランダム） 3:開始時刻(YYMM) 4:終了時刻(YYMM) 5:エディットOK(1)NG(0)
		tmpd:0:備考 1:P2 2:名前 3:電話 4:メール
*/ ?>
			for(var seq0 in rcalSchedule._daysResource[yyyymmdd]["d"]){
				for(var resource_cd in rcalSchedule._daysResource[yyyymmdd]["d"][seq0]){
					var base=+rcalSchedule._daysResource[yyyymmdd]["d"][seq0][resource_cd]["s"];
					var height = Math.floor($j("#rcal_st_" + resource_cd).outerHeight()/base)-2;	//微調整
									
					for(var seq1 in rcalSchedule._daysResource[yyyymmdd]["d"][seq0][resource_cd]["d"]) {
						for(var level in rcalSchedule._daysResource[yyyymmdd]["d"][seq0][resource_cd]["d"][seq1]) {
							var tmpb = rcalSchedule._daysResource[yyyymmdd]["d"][seq0][resource_cd]["d"][seq1][level]["b"];
							var tmpd = rcalSchedule._daysResource[yyyymmdd]["d"][seq0][resource_cd]["d"][seq1][level]["d"];
							var left = rcalSchedule.getLeft( left_start,tmpb[0] );
							var width = rcalSchedule.getWidth( tmpb[0], tmpb[0]+tmpb[1] );
							var top = (+level) * height;
							var eid = 'rcal_event_'+resource_cd+'_'+tmpb[2];
							rcalSchedule._events[tmpb[2]]={"resource_cd":resource_cd,"from":tmpb[3],"to":tmpb[4],"status":tmpb[6]};
							
							var set_class = "rcal_tile";
							var set_title = "";
							if (tmpb[6] == <?php echo ResourceCalendar_Reservation_Status::COMPLETE; ?> ) {
								set_title =  "<?php _e('Completed Reservation',RCAL_DOMAIN); ?>";
								set_class += " rcal_myres_comp";
							}
							else if (tmpb[6] == <?php echo ResourceCalendar_Reservation_Status::TEMPORARY; ?>) {
								set_title = "<?php _e('Temporary Reservation',RCAL_DOMAIN); ?>";
								set_class += " rcal_myres_temp";
							}

//							var setcn = '<div id="'+eid+'" class="'+set_class+'"style="position:absolute; top:'+top+'px; height: '+height+'px; left:'+left+'px; width:'+width+'px;"><span title="'+tmpb[3]+'-'+tmpb[4]+'"/>'+set_title+'</div>';
							var setcn = '<div id="'+eid+'" class="'+set_class+'"style="position:absolute; top:'+top+'px; height: '+height+'px; left:'+left+'px; width:'+width+'px;">'+set_title+'</div>';
							
							$j("#rcal_st_"+resource_cd+"_dummy").prepend(setcn);
							
							if (tmpb[5]=="<?php echo ResourceCalendar_Edit::OK; ?>") {
								rcalSchedule.setEventDetail(tmpb[2],tmpd);
								$j("#"+eid).on("click",function(){
									_fnMakeTimeItem();
									$j("#rcal_page_main").hide();
									$j("#rcal_page_regist").show();
									$j("#rcal_exec_delete").show();
									$j("#rcal_exec_regist").text("<?php _e("Reservation Update",RCAL_DOMAIN); ?>");
									var ids = this.id.split("_");
									save_id = ids[3];
									var ev_tmp = rcalSchedule._events[save_id];
									
									$j("#rcal_resource_cd").val(ev_tmp["resource_cd"]).change();

									var settimeFrom = ev_tmp["from"].substr(0,2)+":"+ev_tmp["from"].substr(2,2);
									var settimeTo = ev_tmp["to"].substr(0,2)+":"+ev_tmp["to"].substr(2,2);

									var yyyymmdd = fnDayFormat(new Date(selected_day),"%Y/%m/%d");
									if (resource_items[ev_tmp["resource_cd"]] ) {
//									if (ev_tmp["resource_cd"] == <?php echo ResourceCalendar_Config::SETTING_PATERN_ORIGINAL; ?>) {
										target_day_from = new Date(yyyymmdd);
										target_day_to = new Date(yyyymmdd);
										var setKey = settimeFrom + '-' + settimeTo;
										$j("#rcal_setting_patern_"+ev_tmp["resource_cd"]).val(setKey).change();
									}
									else {
										target_day_from = new Date(yyyymmdd+" "+settimeFrom);
										$j("#rcal_time_from").val(settimeFrom).change();
										target_day_to = new Date(yyyymmdd+" "+settimeTo);
										$j("#rcal_time_to").val(settimeTo).change();
									}
									$j("#rcal_name").val(htmlspecialchars_decode(ev_tmp["name"]));
									$j("#rcal_tel").val(ev_tmp["tel"]);
									$j("#rcal_mail").val(ev_tmp["mail"]);
									$j("#rcal_remark").val(htmlspecialchars_decode(ev_tmp["remark"]));
									$j("#rcal_target_day").text($j("#rcal_searchdate").val()); 



<?php if ($this->config_datas['RCAL_CONFIG_USE_SUBMENU'] == ResourceCalendar_Config::USE_SUBMENU) : ?>
			var record = ev_tmp["memo"];
			if (record) {
				for (var k1 in record) {
					if (record.hasOwnProperty(k1)){
						switch(category_patern[k1]) {
						case <?php echo ResourceCalendar_Category::TEXT; ?> :
							$j("#category_"+k1).val(record[k1]);
							break;
						case <?php echo ResourceCalendar_Category::SELECT; ?> :
							$j("#category_"+k1).val(record[k1]);
							break;
						case <?php echo ResourceCalendar_Category::RADIO; ?> :
							
							$j("#category_"+k1+"_option_wrap input").attr("checked",false);
							$j("#category_"+k1+"_"+record[k1]).attr("checked",true);
							break;
						case <?php echo ResourceCalendar_Category::CHECK_BOX; ?> :
							$j("#category_"+k1+"_check_wrap input").attr("checked",false);
							var tmp_split = record[k1].split(",");
							for ( var i = 0 ; i < tmp_split.length ; i++ ) {
								$j("#category_"+k1+"_"+tmp_split[i]).attr("checked",true);
							}
							break;
						}
					}
				}
			}

<?php endif; ?>

									operate = "updated";
									setStatus();
									
								});
							}
						}
					}
				}
			}
		}
		
		<?php
//		parent::echoClientItemMobile(array('search_day','customer_name','booking_tel','resource_cd','time_from','time_to','mail','remark'));
		parent::echoClientItemMobile(array('search_day','customer_name','booking_tel','time_from','time_to','booking_mail','remark'));
		?> 
		<?php parent::echoDayFormat(); ?>
		<?php parent::echosSetDay(); ?>
		<?php parent::echoCheckDeadline	($this->config_datas['RCAL_CONFIG_RESERVE_DEADLINE']); ?>


		function AutoFontSize(){
			var each = $j("#rcal_main_data ul li:nth-child(2)").outerWidth();
<?php //字は12px。時間はゼロ埋めしているので2ケタ。初期表示で０の場合があるので判定をいれとく ?>
			if (each > 0 ) {
				var fpar = Math.floor(each/24*100);
				$j(".rcal_main_line li").css("font-size",fpar+"%");
				$j(".rcal_main_line li:first-child").css("font-size","100%");
			}
		}

		function _GetEvent(targetDay) {
			$j.ajax({
				 	type: "post",
					url:  "<?php echo get_bloginfo( 'wpurl' ); ?>/wp-admin/admin-ajax.php?action=rcalbooking", 
					dataType : "json",
					data: {
						"rcal_target_day":targetDay
						,"first_hour":<?php echo +$this->first_hour; ?>
						,"nonce":"<?php echo $this->nonce; ?>"
						,"menu_func":"Booking_Get_Reservation"
					},
					success: function(data) {
						if (data.status == "Error" ) {
							rcalSchedule._daysResource[targetDay] = {"err":data.message};
							<?php //エラーなのでDatePickerの日付を戻す ?>
							$j("#rcal_searchdate").val(fnDayFormat(selected_day,"<?php echo __('%m/%d/%Y',RCAL_DOMAIN); ?>"));
							$j("#rcal_calendar").datepicker("setDate", fnDayFormat(selected_day,"<?php echo __('%m/%d/%Y',RCAL_DOMAIN); ?>"));
							alert(data.message);
						}
						else {
							rcalSchedule._daysResource[targetDay] = data.set_data[targetDay];
							setDayData(targetDay)
						}
					},
					error:  function(XMLHttpRequest, textStatus){
						alert (textStatus);
					}
			 });			
		}

		function _UpdateEvent() {
			var temp_p2 = '';
			if (operate != 'inserted') {
				temp_p2 = rcalSchedule._events[save_id]['p2'];
			}
<?php if ($this->config_datas['RCAL_CONFIG_USE_SUBMENU'] == ResourceCalendar_Config::USE_SUBMENU) : ?>
			var record_array = Object();

			$j(".rcal_category_wrap").find("input[type=checkbox]:checked,input[type=radio]:checked,textarea,select").each(function(){

				var id = $j(this).attr("id");
				var tag = $j(this)[0].tagName.toLowerCase();
				
				
				var id_array = id.split("_");
				
				if (tag == "input" ) {
					var type =  $j(this).attr("type");
					if (type == "checkbox" ) {
						if (record_array[id_array[1]]) 
							record_array[id_array[1]] += ","+id_array[2];
						else 
							record_array[id_array[1]] = id_array[2];
						
					}
					else if (type == "radio" ) {
						record_array[id_array[1]] = id_array[2];
					}
					
					
				}
				else if (tag == "textarea") {
					record_array[id_array[1]] = $j(this).val();
					
				}
				else if (tag == "select" ) {
					record_array[id_array[1]] = $j(this).val();
				}
			});

<?php endif; ?>


			$j.ajax({
				 	type: "post",
					url:  "<?php echo get_bloginfo( 'wpurl' ); ?>/wp-admin/admin-ajax.php?action=rcalbooking", 
					dataType : "json",
					data: {
						"rcal_resource_cd":$j("#rcal_resource_cd").val()
						,"id":save_id
						,"rcal_name":$j("#rcal_name").val()
						,"rcal_mail":  $j("#rcal_mail").val()
						,"rcal_time_from":toYYYYMMDD(target_day_from)
						,"rcal_time_to":toYYYYMMDD(target_day_to)
<?php if ($this->config_datas['RCAL_CONFIG_USE_SUBMENU'] == ResourceCalendar_Config::USE_SUBMENU) : ?>
						,"rcal_memo":record_array
<?php endif; ?>
						,"rcal_remark": $j("#rcal_remark").val()
						,"rcal_tel": $j("#rcal_tel").val()
						,"rcal_user_login": save_user_login
						,"type":operate
						,"p2":temp_p2
						,"nonce":"<?php echo $this->nonce; ?>"
						,"menu_func":"Booking_Edit"
					},
					success: function(data) {
						if (data.status == "Error" ) {
							alert(data.message);
						}
						else {
							var setDate = fnDayFormat(new Date(selected_day),"%Y%m%d");
							rcalSchedule._daysResource[setDate] = data.set_data[setDate];
							$j("#rcal_mainpage_regist").trigger("click");
							setDayData(setDate);
							var yyyymm = setDate.slice(0,6);
<?php /*
		tmpb:0:左開始位置（5分単位） 1:幅 2:イベントID（ログインしていない場合はランダム） 3:開始時刻(YYMM) 4:終了時刻(YYMM) 5:エディットOK(1)NG(0)
*/ ?>
							var displayData = Array(0,0,0);
							rcalSchedule._months[yyyymm][setDate+"_flg"]= false;
							for(var seq0 in rcalSchedule._daysResource[setDate]["d"]){
								for(var resource_cd in rcalSchedule._daysResource[setDate]["d"][seq0]){
									for(var seq1 in rcalSchedule._daysResource[setDate]["d"][seq0][resource_cd]["d"]) {
										for(var level in rcalSchedule._daysResource[setDate]["d"][seq0][resource_cd]["d"][seq1]) {
											var tmpb = rcalSchedule._daysResource[setDate]["d"][seq0][resource_cd]["d"][seq1][level]["b"];
											displayData[tmpb[6]]++;
											if (tmpb[6] == <?php echo ResourceCalendar_Reservation_Status::TEMPORARY; ?> ) {
												rcalSchedule._months[yyyymm][setDate+"_flg"]= true;
											}
										}
									}
								}
							}
							var setString = null;
							if (displayData[1] > 0 ) {
								setString = "<?php _e('Completed Reservations',RCAL_DOMAIN); ?>:"+displayData[1]+"\n";
							}
							if (displayData[2] > 0 ) {
								setString += "<?php _e('Temporary Reservations',RCAL_DOMAIN); ?>:"+displayData[2];
							}
							rcalSchedule._months[yyyymm][setDate]=setString;
							$j("#rcal_calendar").datepicker("refresh");
							if (operate != "deleted")	alert(data.message);
<?php if (RCAL_DEMO ) : ?>
			$j("#rcal_login_div").show();
<?php endif; ?>
						}
					},
					error:  function(XMLHttpRequest, textStatus){
						alert (textStatus);
					}
			 });			
		}
		
		function toYYYYMMDD( date ){
			var month = date.getMonth() + 1;
			return  [date.getFullYear(),( '0' + month ).slice( -2 ),('0' + date.getDate()).slice(-2)].join( "-" ) + " "+ ('0' + date.getHours() ).slice(-2)+ ":" + ( '0' + date.getMinutes() ).slice( -2 );
		}
		
		function toHHMM( date ) {
			return ('0'+date.getHours()).slice(-2)+ ":" + ('0'+date.getMinutes()).slice(-2);
		}
		
		function setStatus() {
			$j("#rcal_status_name").text("");
			if (operate == "inserted" ) {
				$j("#rcal_status_name").text("<?php _e('Register',RCAL_DOMAIN); ?>");
			}
			else if (operate == "updated" ) {
				var status = rcalSchedule._events[save_id]["status"];
				if (rcalSchedule._events[save_id]["status"] == <?php echo ResourceCalendar_Reservation_Status::COMPLETE; ?>) {
					$j("#rcal_status_name").text("<?php _e('Completed Reservation',RCAL_DOMAIN); ?>" );
				}
				else if (rcalSchedule._events[save_id]["status"] == <?php echo ResourceCalendar_Reservation_Status::TEMPORARY; ?>) {
					$j("#rcal_status_name").text("<?php _e('Temporary Reservation',RCAL_DOMAIN); ?>" );
				}
				else if (rcalSchedule._events[save_id]["status"] == <?php echo ResourceCalendar_Reservation_Status::CANCELED; ?>) {
					$j("#rcal_status_name").text("<?php _e('Canceled Reservation',RCAL_DOMAIN); ?>" );
				}
			}
		}

		function _fnGetServerData(base_day){
			<?php //base_day YYYYMM?>
			var yyyy = base_day.substr(0,4);
			var mm = base_day.substr(-2);
			var last = new Date(yyyy,mm,0); <?php //翌月の0日=今月末 ?>
			$j.ajax({
				 	type: "post",
					url:  "<?php echo get_bloginfo( 'wpurl' ); ?>/wp-admin/admin-ajax.php?action=rcalbooking",
					dataType : "json",
					data: {
						"from":yyyy+'-'+mm+'-1',
						"to":$j.format.date(last, "yyyy-MM-dd"),
						"nonce":"<?php echo $this->nonce; ?>",
						"menu_func":"Bookint_Get_Month"
					},

					success: function(data) {
						setMonth[data.yyyymm] = data.cnt;
						if (data.cnt > 0 ) {
							var tmp_target_day = "";
							var index = 0;
							var tmp_array = new Object();
							for(var k1 = 0 ;k1 < data.cnt ;k1++) {
								if (tmp_target_day == "" ) tmp_target_day = data.datas[k1]["target_day"];
								if ( tmp_target_day != data.datas[k1]["target_day"]) {
									getReservation[tmp_target_day] = tmp_array;
									tmp_array = new Object();
									index = 0;
								}
								tmp_array[index++] = data.datas[k1];
								tmp_target_day = data.datas[k1]["target_day"];
							}
							getReservation[tmp_target_day] = tmp_array;
						}
					},
					error:  function(XMLHttpRequest, textStatus){
						alert (textStatus);
					}
			 });			
		}
		
		<?php $this->echoRemoveModal(); ?>
</script>


	<?php if (RCAL_DEMO ) : ?>

		<div id="rcal_login_div" >
		<?php if ( is_user_logged_in() ) : ?>
				<a href="<?php echo get_bloginfo( 'wpurl' ); ?>/wp-admin" ><?php _e('Settings here',RCAL_DOMAIN); ?></a><br>
				<a href="<?php echo wp_logout_url(get_permalink() ); ?>" ><?php _e('Logout here',RCAL_DOMAIN); ?></a>
		<?php else : ?>
				<p><?php _e('Please try settings.',RCAL_DOMAIN); ?></p>
<a title="login" href="<?php echo wp_login_url(get_permalink() ) ?>"><?php _e('Login',RCAL_DOMAIN); ?></a>
<br/><?php echo _e('Username'); ?>: demologin
<br/><?php echo _e('Password'); ?>: demo001
		<?php endif; ?>
		</div>
	<?php endif; ?>

<div id="rcal_main" >
    <div id="rcal_page_main" >
    
		<div id="rcal_header_r3" class="rcal_line">
			<ul>
				<li class="rcal_date"><input type="input" id="rcal_searchdate" name="rcal_searchdate" placeholder="<?php _e('MM/DD/YYYY',RCAL_DOMAIN); ?>"></li>
				<li class="rcal_date"><span id="rcal_searchdays"></span></li>
			</ul>
			<ul>
<li class="rcal_date"></li>
<li class="rcal_date"></li>
				<li class="rcal_date"><input type="button" id="rcal_today" value="<?php _e('Today',RCAL_DOMAIN); ?>" ></li>
			</ul>
		</div>
		<div id="rcal_header" class="rcal_line" >
			<ul><div type="text" id="rcal_calendar" value="" /></div></ul>
		</div>
		<div id="rcal_main_data" class="rcal_line rcal_main_line">
			<?php
			foreach ($edit_resource as $k1 => $d1) {
			    echo "<ul id=\"rcal_st_{$k1}\"><li class=\"rcal_first_li\">";
				if (ResourceCalendar_Component::isMobile() || empty($d1['href']) ) {
					echo $d1['img'];
				}
				else {
					echo "<a class=\"lightbox\" rel=\"resource".$k1."\" href=\"".$d1['href']."\">".$d1['img']."</a>";
				}
			    echo "</li>";
			    for($i = +$this->first_hour ; $i < $this->last_hour ; $i++ ) {
					
					echo '<li class="rcal_time_li"><span>'.sprintf("%02d",$i).'</span></li>';
			    }
				echo "<div id=\"rcal_st_{$k1}_dummy\"></div>";
			    echo '</ul>';
			}
			?>
<?php /*
			<div id="rcal_holiday" class="rcal_holiday" ><?php _e('Holiday',RCAL_DOMAIN); ?></div>
*/?>
			<div id="rcal_holiday" class="rcal_holiday" ></div>
<?php  	if (($this->config_datas['RCAL_CONFIG_ENABLE_RESERVATION'] ==  ResourceCalendar_Config::USER_ANYONE) ||
 			( $this->config_datas['RCAL_CONFIG_ENABLE_RESERVATION'] ==  ResourceCalendar_Config::USER_REGISTERED &&  is_user_logged_in() ) ): ?>
			<a  data-role="button"  id="rcal_regist_button" class="rcal_tran_button" href="javascript:void(0)" ><?php _e('Booking',RCAL_DOMAIN); ?></a></li>
<?php   endif;		?>		   
		</div>
		
    </div>
    
    <div id="rcal_page_regist">

<?php if ($this->isPluginAdmin() ) : ?>
	<div id="rcal_search" class="modal">
		<div class="modalBody">
			<div id="rcal_search_result"></div>
		</div>
	</div>
<?php endif; ?>

<?php  	if (($this->config_datas['RCAL_CONFIG_ENABLE_RESERVATION'] ==  ResourceCalendar_Config::USER_ANYONE) ||
 			( $this->config_datas['RCAL_CONFIG_ENABLE_RESERVATION'] ==  ResourceCalendar_Config::USER_REGISTERED &&  is_user_logged_in() ) ): ?>
		<div id="rcal_regist_detail" class="rcal_line" >
		<ul>
			<li class="rcal_label" ><label ><?php _e('Date',RCAL_DOMAIN); ?>:</label></li>
			<li class="rcal_li"><span id="rcal_target_day"></span></li>
		</ul>
		<ul>
			<li class="rcal_label" ><label ><?php _e('Status',RCAL_DOMAIN); ?>:</label></li>
			<li class="rcal_li"><span id="rcal_status_name"></span></li>
		</ul>

<?php if ($this->isPluginAdmin() ) : ?>
		<ul><li class="rcal_li">
			<input type="text" id="rcal_name"  <?php echo $nameRequire; ?> />
			<input id="button_search" type="button" class="rcal_button" value=<?php _e('Search',RCAL_DOMAIN); ?> />
		</li></ul>
<?php else: ?>
		<ul><li class="rcal_li"><input type="text" id="rcal_name"  <?php echo $nameRequire; ?> /></li></ul>
<?php endif; ?>
		<ul><li class="rcal_li"><input type="tel" id="rcal_tel" <?php echo $telRequire; ?> /></li></ul>
		<ul><li class="rcal_li"><input type="mail" id="rcal_mail"  <?php echo $mailRequire; ?> /></li></ul>

		<ul>
       	<li class="rcal_label" ><label ><?php echo $this->config_datas['RCAL_CONFIG_RESOURCE_NAME']; ?>:</label></li>
		<li class="rcal_li" >
		<select id="rcal_resource_cd" name="rcal_resource_cd" class="rcal_sel">
<?php
		$echo_data = '';
		$echo_data .= '<option value="">'.__('select please',RCAL_DOMAIN).'</option>';
		foreach($this->resource_datas as $k1 => $d1 ) {
			if ($d1['chk_from'] <= $init_target_day &&  $init_target_day <= $d1['chk_to'] ) { 
				$echo_data .= '<option value="'.$d1['resource_cd'].'">'.htmlspecialchars($d1['name'],ENT_QUOTES).'</option>';
			}
		}
		echo $echo_data;
?>
		</select>
		</li></ul>
		<div id="rcal_setting_patern_time_wrap" ></div>
		<?php 
		//時間指定と設定パターン（午前１など）
		foreach($this->resource_datas as $k1 => $d1 ) {


			if ($d1['setting_patern_cd'] == ResourceCalendar_Config::SETTING_PATERN_ORIGINAL) {
				echo '<div id="rcal_setting_patern_'.$d1['resource_cd'].'_wrap" class="rcal_patern_original" >';
				echo '<ul>';
				echo '<li class="rcal_label" ><label >'.__('TargetTime',RCAL_DOMAIN).':</label></li>';
				echo '<li class="rcal_li"><select id="rcal_setting_patern_'.$d1['resource_cd'].'"  class="rcal_sel rcal_patern_original_sel"  >';
				$row_array = explode(';',$d1['setting_data']);
				foreach ($row_array as $k2 => $d2 ) {
					$col_array = explode(',',$d2);
					echo '<option value="'.$col_array[1].'-'.$col_array[2].'" >'.$col_array[0].'('.$col_array[1].'-'.$col_array[2].')</option>';
				}
				echo '</select></li>';
				echo '</ul></div>';
			}
		}
		?>
<?php if ($this->config_datas['RCAL_CONFIG_USE_SUBMENU'] == ResourceCalendar_Config::USE_SUBMENU) : ?>
<?php
	foreach($this->category_datas as $k1 => $d1 ) {
		echo '<ul>';
		echo '<li class="rcal_label"><label>'.$d1['category_name'].':</label></li>';
		if ($d1['category_patern'] == ResourceCalendar_Category::RADIO ) {
			echo '<li class="rcal_li rcal_category_wrap rcal_category_check_opt">';
			$tmp_array = explode(',',$d1['category_values']);
			$max_cnt = count($tmp_array);
			for ($i = 0 ; $i < $max_cnt ;$i++ ) {
				echo "<label class=\"sl_category_option\" ><input class=\"rcal_category_option\" type=\"radio\" id=\"category_i{$d1['category_cd']}_{$i}\" value=\"{$i}\" name=\"category_{$d1['category_cd']}\" />{$tmp_array[$i]}</label>";
			}
			echo "</li>";
		}
		elseif ($d1['category_patern'] == ResourceCalendar_Category::CHECK_BOX ) {
			$tmp_array = explode(',',$d1['category_values']);
			$max_cnt = count($tmp_array);
			echo '<li class="rcal_li rcal_category_wrap rcal_category_check_opt">';
			for ($i = 0 ; $i < $max_cnt ;$i++ ) {
				echo "<label class=\"sl_category_option\" ><input class=\"rcal_category_option\" type=\"checkbox\" id=\"category_i{$d1['category_cd']}_{$i}\" value=\"{$d1['category_cd']}\" />{$tmp_array[$i]}</label>";
			}
			echo "</li>";
		}
		if ($d1['category_patern'] == ResourceCalendar_Category::SELECT ) {
			echo '<li class="rcal_li rcal_category_wrap">';
			echo "<select id=\"category_i{$d1['category_cd']}\" name=\"category_{$d1['category_cd']}\" />";
			$tmp_array = explode(',',$d1['category_values']);
			foreach ($tmp_array as $d1 ) {
				echo "<option value=\"{$d1}\">{$d1}</option>";				
			}
			echo "</select>";
			echo "</li>";
		}
		elseif ($d1['category_patern'] == ResourceCalendar_Category::TEXT ) {
			echo '<li class="rcal_li rcal_category_wrap">';
			echo "<textarea id=\"category_i{$d1['category_cd']}\" ></textarea>";
			echo "</li>";
		}
		echo '</ul>';
	}
?>		
<?php endif; ?>
		<ul><li class="rcal_li"><textarea id="rcal_remark"  ></textarea></li></ul>
			
		</div>
		<div id="rcal_footer_r3" class="rcal_line">
			<ul>
			<li><a data-role="button" class="rcal_tran_button" id="rcal_exec_regist"  href="javascript:void(0)" ><?php _e('Booking',RCAL_DOMAIN); ?></a></li>
			<li><a data-role="button" class="rcal_tran_button" id="rcal_exec_delete"  href="javascript:void(0)" ><?php _e('Booking Cancel',RCAL_DOMAIN); ?></a></li>
			<li><a data-role="button" class="rcal_tran_button" id="rcal_mainpage_regist" href="#rcal-page-main"><?php _e('Close',RCAL_DOMAIN); ?></a></li>
			</ul>
			
		</div>
<?php endif;  //556行目あたりの予約をできるかの判定用		?>		   
    </div>
<?php /*?>
  <div data-role="footer">
    Copyright 2013-2014, Kuu
  </div>
<?php */?>
</div>	
	<div id="rcal_hidden_photo_area">
<?php //複数写真用 ":

	if ( ! ResourceCalendar_Component::isMobile() ) {
		foreach ($this->resource_datas as $k1 => $d1 ) {
			if ( !empty($d1['photo_result'][0]) ) {
	
	
				for($i = 1;$i<count($d1['photo_result']);$i++  ){
					$tmp = "<a href='".$d1['photo_result'][$i]['photo_path']."' rel='resource".$d1['resource_cd']."' class='lightbox' ></a>";
					$url = site_url();
					$url = substr($url,strpos($url,':')+1);
					if (is_ssl() ) {
						$tmp = preg_replace("$([hH][tT][tT][pP]:".$url.")$","https:".$url,$tmp);
					}
					else {
						$tmp = preg_replace("$([hH][tT][tT][pP][sS]:".$url.")$","http:".$url,$tmp);
					}
					echo $tmp;
				}
			}
		}
	}
?>    

</div>
