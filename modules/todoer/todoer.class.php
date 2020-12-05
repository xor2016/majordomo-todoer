<?php
/**
* Планировщик 
* @package project
* @author Wizard <sergejey@gmail.com>
* @copyright http://majordomo.smartliving.ru/ (c)
* @version 0.1 (wizard, 23:11:27 [Nov 25, 2020])
*/
//
//
class todoer extends module {
/**
* todoer
*
* Module class constructor
*
* @access private
*/
function __construct() {
  $this->name="todoer";
  $this->title="Планировщик";
  $this->module_category="<#LANG_SECTION_APPLICATIONS#>";
  $this->checkInstalled();
}
/**
* saveParams
*
* Saving module parameters
*
* @access public
*/
function saveParams($data=1) {
 $p=array();
 if (IsSet($this->id)) {
  $p["id"]=$this->id;
 }
 if (IsSet($this->view_mode)) {
  $p["view_mode"]=$this->view_mode;
 }
 if (IsSet($this->edit_mode)) {
  $p["edit_mode"]=$this->edit_mode;
 }
 if (IsSet($this->data_source)) {
  $p["data_source"]=$this->data_source;
 }
 if (IsSet($this->tab)) {
  $p["tab"]=$this->tab;
 }
 return parent::saveParams($p);
}
/**
* getParams
*
* Getting module parameters from query string
*
* @access public
*/
function getParams() {
  global $id;
  global $mode;
  global $view_mode;
  global $edit_mode;
  global $data_source;
  global $tab;
  if (isset($id)) {
   $this->id=$id;
  }
  if (isset($mode)) {
   $this->mode=$mode;
  }
  if (isset($view_mode)) {
   $this->view_mode=$view_mode;
  }
  if (isset($edit_mode)) {
   $this->edit_mode=$edit_mode;
  }
  if (isset($data_source)) {
   $this->data_source=$data_source;
  }
  if (isset($tab)) {
   $this->tab=$tab;
  }
}
/**
* Run
*
* Description
*
* @access public
*/
function run() {
 global $session;
  $out=array();
  if ($this->action=='admin') {
   $this->admin($out);
  } else {
   $this->usual($out);
  }
  if (IsSet($this->owner->action)) {
   $out['PARENT_ACTION']=$this->owner->action;
  }
  if (IsSet($this->owner->name)) {
   $out['PARENT_NAME']=$this->owner->name;
  }
  $out['VIEW_MODE']=$this->view_mode;
  $out['EDIT_MODE']=$this->edit_mode;
  $out['MODE']=$this->mode;
  $out['ACTION']=$this->action;
  $out['DATA_SOURCE']=$this->data_source;
  $out['TAB']=$this->tab;
  $this->data=$out;
  $p=new parser(DIR_TEMPLATES.$this->name."/".$this->name.".html", $this->data, $this);
  $this->result=$p->result;
}
/**
* BackEnd
*
* Module backend
*
* @access public
*/
function admin(&$out) {

 if (isset($this->data_source) && !$_GET['data_source'] && !$_POST['data_source']) {
  $out['SET_DATASOURCE']=1;
 }

 if ($this->data_source=='clnd_events' || $this->data_source=='') {
  if ($this->view_mode=='' || $this->view_mode=='search_clnd_events') {
   $this->search_clnd_events($out);
  }
  if ($this->view_mode=='edit_clnd_events') {
   $this->edit_clnd_events($out, $this->id);
  }
  if ($this->view_mode=='delete_clnd_events') {
   $this->delete_clnd_events($this->id);
   $this->redirect("?");
  }
 }
 if ($this->data_source=='clnd_categories') {
  if ($this->view_mode=='' || $this->view_mode=='search_clnd_categories') {
   $this->search_clnd_categories($out);
  }
}
 if ($this->data_source=='clnd_full') {
   $this->calendar_full($out);
}

}
/**
* FrontEnd
*
* Module frontend
*
* @access public
*/
function usual(&$out) {
 $this->admin($out);
}
/**
* clnd_events search
*
* @access public
*/
 function search_clnd_events(&$out) {
  require(dirname(__FILE__).'/clnd_events_search.inc.php');
 }
/**
* clnd_events edit/add
*
* @access public
*/
 function edit_clnd_events(&$out, $id) {
  global $title;
  global $id;

  if ($id) {
	$rec=SQLSelectOne("SELECT * FROM clnd_events WHERE ID='".(int)$id."'");

	if ($this->mode=='delete') {
		SQLExec("DELETE FROM clnd_events WHERE ID='".(int)$rec['ID']."'");
		//освободим подчиненные задачи
		SQLExec("UPDATE clnd_events SET PARENT_ID=0 WHERE PARENT_ID='".$rec['ID']."'");
	$this->redirect("?");
}

  } else { 
	//add new by title
	$out['TITLE']=$title;
	$out['DUE'] = date('Y-m-d H:i:00');
	$out['END_TIME'] = date('Y-m-d H:i:00');
	$out['REMIND_TIME']= date('Y-m-d H:i:00');
	//$out['IS_TASK'] = 0;
	$out['IS_NODATE'] = 0;
	$out['ALL_DAY'] = 0;
	$out['IS_REPEATING'] = 0;
	$out['LOG'] = '';

  }

  if ($this->mode=='update') {
   $ok=1;

   global $is_task;
   global $notes;

   $rec['TITLE']=$title;

   if (!$rec['TITLE']) {
    $ok=0;
    $out['ERR_TITLE']=1;
   }

   //$rec['IS_TASK']=(int)$is_task;
   $rec['NOTES']=$notes;

   global $due; //начало события 
   $rec['DUE'] = $due;
   if (!$rec['DUE'] ) {
    $rec['DUE'] = date('Y-m-d H:i'.':00',time()+60);
   }

   global $end_time; //конец события
   $rec['END_TIME'] = $end_time;
   if (!$rec['END_TIME']) {
    $rec['END_TIME']=$rec['DUE'];
   }
   
   global $is_repeating;//признак повтора
   $rec['IS_REPEATING']=(int)$is_repeating;

   //global $is_repeating_after;//рудимент?
   //$rec['IS_REPEATING_AFTER']=(int)$is_repeating_after;
   global $repeat_in;
   $rec['REPEAT_IN']=(int)$repeat_in;

   global $repeat_type;
   $rec['REPEAT_TYPE']=(int)$repeat_type;
   
   global $is_done;//признак закрытия
   if ($is_done && !$rec['IS_DONE']) {
    $marked_done = 1; 
   }
   if (!$is_done && $rec['IS_DONE']) {//переоткроем закрытую задачу
    //$marked_undone = 1; 
    $rec['IS_BEGIN'] = 0; //для запуска кода по сроку
   }


   $rec['IS_DONE']=(int)$is_done;

   global $is_nodate; //сложно - без указания даты - всегда - спец. обработка(
   $rec['IS_NODATE']=(int)$is_nodate;
   if ($is_nodate) {
    $rec['IS_REPEATING']=0; //ignore sets
    $rec['ALL_DAY']=0;      
   }

   global $all_day; //на весь день - с 00:00 до 23:59
   $rec['ALL_DAY'] = (int)$all_day;
   if($all_day){
     $rec['DUE'] = date('Y-m-d',strtotime($rec['DUE'])).' 00:00:00';
     $rec['END_TIME'] = date('Y-m-d',strtotime($rec['END_TIME'])).' 23:59:00';
   }

   global $user_id;
   $rec['USER_ID']=(int)$user_id;

   global $calendar_category_id;
   $rec['CALENDAR_CATEGORY_ID']=(int)$calendar_category_id;
/*	
   global $done_script_id;
   $rec['DONE_SCRIPT_ID']=(int)$done_script_id;
*/
   global $done_code;//код при закрытии задачи (is_done ставится в 1)
   $rec['DONE_CODE'] = $done_code;

   global $is_remind; //напоминание есть
   $rec['IS_REMIND'] = (int)$is_remind; 

   global $remind_time; //его время рассчитанное/указаное
   $rec['REMIND_TIME'] = $remind_time;
   if (!$rec['REMIND_TIME'] && $is_remind ) {
     $rec['REMIND_TIME'] = $rec['DUE']; //todo
   }

   global $remind_type; // сказать/выполнить код
   $rec['REMIND_TYPE'] = $remind_type;

   global $remind_code; //код напоминания
   $rec['REMIND_CODE'] = $remind_code;

   global $week_days;//дни недели для повторов
   $rec['WEEK_DAYS'] = @implode(',', $week_days);
   if (is_null($rec['WEEK_DAYS'])) {
        $rec['WEEK_DAYS'] = '';
    }

   global $y_months;//месяцы для повторов
   $rec['YE_MONTHS'] = @implode(',', $y_months);
   if (is_null($rec['YE_MONTHS'])) {
        $rec['YE_MONTHS'] = '';
    }

   global $autodone; //признак автоматическое завершение при старте задачи
   $rec['AUTODONE'] = $autodone;
 
   global $remind_in;//напомнить за remind_in мин/час/дней ???
   $rec['REMIND_IN'] = $remind_in;

   global $remind_timer; // 0..10 мин/час/дней/как явно указано в REMIND_TIME
   $rec['REMIND_TIMER'] = $remind_timer;
   if($remind_timer < 10) {  
     $delta = array(0 => 5*60,
                  1 => 15*60,
                  2 => 30*60,
                  3 => 45*60,
                  4 => 60*60,
                  5 => 2*60*60,
                  6 => 8*60*60,
                  7 => 12*60*60,
                  8 => 24*60*60,
                  9 => 48*60*60,
                  );
     $remd = strtotime($rec['DUE']) - $delta[$remind_timer];
     if($remd < time()) $remd = $remd + 60; //must be in future
     $rec['REMIND_TIME'] = date('Y-m-d H:i'.':00',$remd);

   }
   global $parent_id;//главная задача
   $rec['PARENT_ID'] = $parent_id;

   global $autodone_by_childs;//для главной - автоматическое завершение по готовности подзадач
   $rec['AUTODONE_BY_CHILDS'] = $autodone_by_childs;

   global $log;
   $rec['LOG'] = $log;

   global $is_repeat_until;//признак проверки окончания повторов
   $rec['IS_REPEAT_UNTIL'] = $is_repeat_until;

   global $repeat_until;//повторять до
   $rec['REPEAT_UNTIL'] = $repeat_until;

   global $begin_code;//код, выполняющийся при старте задачи
   $rec['BEGIN_CODE'] = $begin_code;

   global $is_cron;//признак использования синтаксиса Крона для повторов
   $rec['IS_CRON'] = $is_cron;

   global $repeat_cron;//строка синтаксиса Крона для повторов
   $rec['REPEAT_CRON'] = $repeat_cron;

////////////////////////////////
}
   if ($ok) {
    if ($rec['ID']) {
     SQLUpdate('clnd_events', $rec);
    } else {
     $rec['ADDED']=date('Y-m-d H:i:s');
     $rec['ID']=SQLInsert('clnd_events', $rec);
    }
    if ($marked_done) {
     $this->task_done($rec['ID']);
    }

    $this->redirect("?");
   }


  //}


  outHash($rec, $out);
  $out['DONE_WHEN'] = date('d.m.Y H:i:00',strtotime($rec['DONE_WHEN']));
  $out['USERS'] = SQLSelect("SELECT * FROM users ORDER BY NAME");
  //$out['LOCATIONS']=SQLSelect("SELECT * FROM gpslocations ORDER BY TITLE");
  //$out['SCRIPTS'] = SQLSelect("SELECT ID, TITLE FROM scripts ORDER BY TITLE");
  $out['CALENDAR_CATEGORIES'] = SQLSelect("SELECT ID, TITLE from clnd_categories ORDER BY TITLE");
//обработка дней недели
$w_days = array();
if ($rec['WEEK_DAYS']!=='') {
  $w_days = explode(',', $rec['WEEK_DAYS']);
}

$days = array( 1=>"Пн","Вт","Ср","Чт","Пт","Сб","Вс");
	for ($i = 1; $i < 8; $i++) {
	    $out['WDAYS'][] = array(
	         'VALUE'    => $i,
	         'DNAME'    => $days[$i],
	         'SELECTED' => (in_array($i, $w_days))?1:0,
	   );
	}

//обработка месяцев года
$y_months = array();
if ($rec['YE_MONTHS']!=='') {
  $y_months = explode(',', $rec['YE_MONTHS']);
}
$months = array(1=>"Янв","Фев","Мар","Апр","Май","Июн","Июл","Авг","Сен","Окт","Ноя","Дек");
	for ($i = 1; $i < 13; $i++) {
	    $out['YMONTHS'][] = array(
	         'VALUE'    => $i,
	         'MNAME'    => $months[$i],
	         'SELECTED' => (in_array($i, $y_months))?1:0,
	   );
	}

  if ($out['ID']) {
    //подчиненные задачи + признак просрочки - overdue
    $out['OTHERS'] = SQLSelect("SELECT ID, TITLE, IS_DONE, case when DUE < NOW() AND END_TIME< NOW() AND IS_DONE=0 AND IS_NODATE=0 then '1' else '0' end  OVERDUE FROM clnd_events WHERE PARENT_ID=".$out['ID']." ORDER BY TITLE");
    $out['OTHERS_REC_COUNT'] = count($out['OTHERS']);
    //progress main ???
    if($out['OTHERS_REC_COUNT']>0) {
      $rec = SQLSelectOne( "SELECT sum(IS_DONE)*100/count(ID) PR FROM clnd_events WHERE PARENT_ID=".$out['ID']);
      $out['PROGRESS'] = round($rec['PR']);
    }else{
      $out['PROGRESS'] = 0;
    }
	//список задач для выбора главной 
    $out['FOR_LINKED_TASKS'] = SQLSelect("SELECT clnd_events.`ID`, clnd_events.`TITLE`,clnd_events.`DUE`,clnd_events.`PARENT_ID` ,clnd_categories.ICON FROM clnd_events left join clnd_categories on clnd_events.calendar_category_id=clnd_categories.id WHERE (`IS_DONE`=0 or `IS_REPEATING`=1) and clnd_events.`ID`<>".$out['ID']." order by `TITLE`");

    
  }
}

/** ???? 
* clnd_events delete record
*
* @access public
*/
 function delete_clnd_events($id) {
  //$rec=SQLSelectOne("SELECT * FROM clnd_events WHERE ID='$id'");//???
  SQLExec("DELETE FROM clnd_events WHERE ID='".$id."'");
  //освободим подчиненные задачи
  SQLExec("UPDATE clnd_events SET PARENT_ID=0 WHERE PARENT_ID='".$id."'");
 }

/**
* clnd_categories search
*
* @access public
*/
 function search_clnd_categories(&$out) {
  require(dirname(__FILE__).'/clnd_categories_search.inc.php');
 }

/**
* Title
*
* Description mark task doned
* $id task 
* $autoend = 0 - запустим обновление без запусков скриптов (для задач, истекших по времени, повторяющихся, но не выполненых)
* @access public
*/


 function task_done($id, $autoend = 0) {

	$rec = SQLSelectOne("SELECT * FROM clnd_events WHERE ID=".$id);

	if(!$rec) {
		debmes('Bad task id ='. $id,'todoer');
		return; 
	}
	$tm = time();
	if( strlen($rec['LOG']) > 100) $rec['LOG'] = "..."; //обрежем лог
	if(!$autoend){ //завершилась по времени окончания без признака готовности (для нового повтора)
		$rec['DONE_WHEN'] = null;
		$rec['IS_DONE'] = 0;
		$rec['IS_BEGIN'] = 0;
		if('SETTINGS_TODOER_LOGGING') debmes('repeated task "'.$rec['TITLE'].'" ended but not marked done, renew due/end date only','todoer');
		$rec['LOG'] .= date('d.m.y H:i:s', $tm).' - завершено без исполнения.';

	}else{ //задача была выполнена
		$rec['DONE_WHEN'] = date('Y-m-d H:i:s', $tm);
		$rec['IS_DONE'] = 1;
		if('SETTINGS_TODOER_LOGGING') debmes('--> "'.$rec['TITLE'].'" (id='.$id.') is done!','todoer');
		$rec['LOG'] .= date('d.m.y H:i:s', $tm).' - завершено.';
	}

	if ($rec['IS_REPEATING']) {
		$due_time = strtotime(date('Y-m-d H:i:00',strtotime($rec['DUE']))); //unixtime
		$end_time = strtotime(date('Y-m-d H:i:00',strtotime($rec['END_TIME']))); //unixtime
		
		//if(!$due_time) $due_time = $rec['ALL_DAY']?(strtotime(date('Y-m-d')." 00:00:00",$tm)):$tm;
		//if(!$end_time) $end_time = $rec['ALL_DAY']?(strtotime(date('Y-m-d')." 23:59:00"),$tm)):$due_time;
		$repeat_in = $rec['REPEAT_IN']?$rec['REPEAT_IN']:1;

		//найдем длительность для определения нового end_time
		$duration = $end_time - $due_time;
		$part_due = date_parse($rec['DUE']);

	if($rec['IS_CRON'] == 1){
		//calc new due by cron
		if(!$rec['REPEAT_CRON']) {
			debmes('Bad Cron line in task id ='. $id,'todoer');
			return;
		}
		$cron_due = parse_cr($rec['REPEAT_CRON'], $due_time + 60); //todo - check it!
		$new_due = date('Y-m-d H:i:00', $cron_due);
		$new_end = date('Y-m-d H:i:00', $$cron_due + $duration);

	}else{
		if ($rec['REPEAT_TYPE'] == 1) {//годы
			// yearly task
			$due_time_next_year = mktime($part_due['hour'], $part_due['minute'], 0, $part_due['month'], $part_due['day'], $part_due['year']+$repeat_in*1);
			$new_due = date('Y-m-d H:i:00', $due_time_next_year);
			$new_end = date('Y-m-d H:i:00', $due_time_next_year + $duration);
		} elseif ($rec['REPEAT_TYPE'] == 2) {//месяцы
			// monthly task
			$time_next_month = $due_time + $repeat_in*31*24*60*60;
			$due_time_next_month = mktime($part_due['hour'], $part_due['minute'], 0, date('m', $time_next_month), $part_due['day'], date('Y', $time_next_month));
			$new_due = date('Y-m-d H:i:00', $due_time_next_month);
			$new_end = date('Y-m-d H:i:00', $due_time_next_month + $duration);
	
	   } elseif ($rec['REPEAT_TYPE'] == 3) {//недели
	     if(!$rec['WEEK_DAYS']){
		       // weekly task
		      $due_time_next_week = $due_time + 7*24*60*60;
		      $new_due = date('Y-m-d H:i:00', $due_time_next_week);
		      $new_end = date('Y-m-d H:i:00', $due_time_next_week + $duration);
		
		      $rec['WEEK_DAYS'] = date("N", $due_time_next_week);//запишем на будущее чтобы галочка стояла(?)
	     }else{
	       //задача назначена на пн, вт 
	       //если задача пн закрыта во вт, то следующий срок - пн
	       $week_days = array();
		   if ($rec['WEEK_DAYS'] !== '') $week_days = explode(',', $rec['WEEK_DAYS']);
	       $dd = $tm;
	       $due = date("N", $dd); //переведём в формат пн=1...вс=7
	       for($i = 0; $i < 7;$i++){
	         $dd = $dd + $repeat_in*24*60*60; //след. дата
	         $due = $due + 1;
	         if($due > 7) $due = 1;
	         if(in_array($due, $week_days)) { //первый следующий запуск
	           //$next = $dd;
	           break;
	         }
	       }
	       $due_time_next_week = mktime($part_due['hour'],$part_due['minute'],0, date('m', $dd), date('j', $dd), date('Y', $dd));
	       $new_due = date('Y-m-d H:i:00', $due_time_next_week);
	       $new_end = date('Y-m-d H:i:00', $due_time_next_week + $duration);
	     }
	   } elseif ($rec['REPEAT_TYPE'] == 4) {//дни
	       $due_time_next_day = $due_time + $repeat_in*24*60*60;
			while ($due_time_next_day <= $tm){ //in future only!
				$due_time_next_day = $due_time + $repeat_in*24*60*60;
			}
	       $new_due = date('Y-m-d H:i:00', $due_time_next_day);
	       $new_end = date('Y-m-d H:i:00', $due_time_next_day + $duration);
	
	   } elseif ($rec['REPEAT_TYPE'] == 5) {//часы
			
	       $due_time_next_hour = $due_time + $repeat_in*60*60;
			while ($due_time_next_hour <= $tm){ //in future only!
				$due_time_next_hour = $due_time + $repeat_in*60*60;
			}
	       $new_due = date('Y-m-d H:i:00', $due_time_next_hour);
	       $new_end = date('Y-m-d H:i:00', $due_time_next_hour + $duration);
	
	   } elseif ($rec['REPEAT_TYPE'] == 6) {//минуты
			$due_time_next_minute = $due_time + $repeat_in*60;
			while ($due_time_next_minute <= $tm){ //in future only!
				$due_time_next_minute = $due_time + $repeat_in*60;
			}
	       $new_due = date('Y-m-d H:i:00', $due_time_next_minute);
	       $new_end = date('Y-m-d H:i:00', $due_time_next_minute + $duration);
		}
	} 
	if('SETTINGS_TODOER_LOGGING') debmes('repeated task "'.$rec['TITLE'].'" sets new due '.$new_due,'todoer');
	$rec['LOG'] .= " Новый срок: ".date('d.m.y H:i:00',strtotime($new_due));
	//upd remind for repeat events/tasks
	if($rec['IS_REMIND'] && $rec['REMIND_TIMER']<10){
		$delta = array(0 => 5*60,
                  1 => 15*60,
                  2 => 30*60,
                  3 => 45*60,
                  4 => 60*60,
                  5 => 2*60*60,
                  6 => 8*60*60,
                  7 => 12*60*60,
                  8 => 24*60*60,
                  9 => 48*60*60,
                  );
     $remd = strtotime($new_due) - $delta[$remind_timer];
     if($remd < $tm) $remd = $remd + 60; //must be in future
     $rec['REMIND_TIME'] = date('Y-m-d H:i'.':00', $remd);
  }
	$flag_done = 0; //for task's new instance 
	//check repeat_until
	if($rec['IS_REPEAT_UNTIL'] == "1" ){
		if(strtotime($new_due) >= strtotime($rec['REPEAT_UNTIL'])){
			//сбросим флаг повтора, завершим задачу и отпишемся в лог
			$rec['IS_REPEATING'] = 0;
			$flag_done = 1; 
			$new_due = $rec['DUE']; 
			$new_end = $rec['END_TIME'];
			if('SETTINGS_TODOER_LOGGING') debmes('repeated task "'.$rec['TITLE'].'" ended by REPEAT_UNTIL','todoer');
			$rec['LOG'] .= ' '.date('d.m.y H:i:s',$tm).' - завершены повторы';
		}
	}
	//fin process for repeating
	$rec['IS_DONE'] = $flag_done;
	$rec['DUE'] = $new_due;
	$rec['END_TIME'] = $new_end;
	$rec['IS_BEGIN'] = 0;
}
//save 
SQLUpdate('clnd_events', $rec);

	if($autoend){
		if($rec['DONE_CODE']){
			setTimeOut('done_tsk'.$id.'_'.$tm,$code,1);
/*
			try {
				$code = $rec['DONE_CODE'];
				$success = eval($code);//todo - safe mode to run
				if ($success === false)
					DebMes("Error in Todoer Done code: " . $code);
			} catch (Exception $e) {
				DebMes('Error: exception ' . get_class($e) . ', ' . $e->getMessage() . '.');
			}
*/
		}
	}
//$delta = time()- $tm;
//debmes("task ".$rec['TITLE']." done duration ".$delta);
}//end  function task_done


/**
* Title
*
* Description mark task as began & procecc begin_code
* $id task 
* $code
* @access public
*/
function task_begin($id, $code="") {
$tm = time();
if($code){
	setTimeOut('start_tsk'.$id.'_'.$tm,$code,1);
/*	
	try {
		$success = eval($code);//todo - safe mode to run
		if ($success === false)
			DebMes("Error in Todoer Begin code: " . $code);
	} catch (Exception $e) {
		DebMes('Error: exception ' . get_class($e) . ', ' . $e->getMessage() . '.');
	}
*/
}
SQLExec("update clnd_events set IS_BEGIN=1, LOG='".date('d.m.y H:i:s')." - старт.' where id = $id");
//$delta = time()- $tm;
//debmes("task id=".$id." begin code duration -".$delta);
}

 function process_remind($id) {
  $rec = SQLSelectOne("SELECT * FROM clnd_events WHERE ID='".(int)$id."'");
  if($rec['ID']){
	  $rec['IS_REMIND'] = 0;
	  SQLUpdate('clnd_events', $rec);
	  if($rec['REMIND_TYPE']==0){
	    say("Напоминаю о задаче " .$rec['TITLE'],2);
	  }else{
		if ($rec['REMIND_CODE']!==""){
			                        try {
			                            $code = $rec['REMIND_CODE'];
			                            $success = eval($code);
			                            if ($success === false)
			                                DebMes("Error in Calendar Reminder code: " . $code);
			                        } catch (Exception $e) {
			                            DebMes('Error: exception ' . get_class($e) . ', ' . $e->getMessage() . '.');
			                        }
			                    }
	  }

  }
}

/**
* processSubscription
*
* @access public
*/
function processSubscription($event, $details=''){
	//process tasks by due
	$sql = "SELECT * FROM `clnd_events` WHERE `IS_DONE`=0 and IS_NODATE=0 and date_FORMAT(`DUE`, '%Y%m%d%H%i')<=date_FORMAT(NOW(), '%Y%m%d%H%i')";
	
	$tasks = SQLSelect($sql);
	$total = count($tasks);
	for ($i = 0; $i < $total; $i++) {
		if($tasks[$i]['IS_BEGIN'] =="0" ){//process begin_code
			if($tasks[$i]['BEGIN_CODE'] !== ""){
				$this -> task_begin($tasks[$i]['ID'],$tasks[$i]['BEGIN_CODE']);
				if('SETTINGS_TODOER_LOGGING') debmes('start task finded "'.$tasks[$i]['TITLE'].'" with code. process...',"todoer");
			}
		}
	}

	//закроем окончившиеся задачи + окончившиеся по времени повторяющиеся задачи и вычислим новые для повторов
	$sql = "SELECT ID,TITLE,AUTODONE FROM `clnd_events` WHERE `IS_DONE`=0 and IS_NODATE=0 and date_FORMAT(`END_TIME`, '%Y%m%d%H%i')<=date_FORMAT(NOW(), '%Y%m%d%H%i')";
	$tasks = SQLSelect($sql);
	$total = count($tasks);
	for ($i = 0; $i < $total; $i++) {
			if('SETTINGS_TODOER_LOGGING') debmes('repeat ended task finded "'.$tasks[$i]['TITLE'].'". process...',"todoer");
			$id = $tasks[$i]['ID'];
			if($id){
				$this -> task_done($id, $tasks[$i]['AUTODONE']);//запустим обновление с/без запуска скриптов
			}
	}
	//напоминалки
	$sql = "SELECT ID,TITLE FROM `clnd_events` WHERE `IS_REMIND`=1 and IS_DONE=0 and date_FORMAT(`REMIND_TIME`, '%Y%m%d%H%i')<=date_FORMAT(NOW(), '%Y%m%d%H%i')";
	$tasks = SQLSelect($sql);
	$total = count($tasks);
	for ($i = 0; $i < $total; $i++) {
		if('SETTINGS_TODOER_LOGGING') debmes('reminder finded for task "'.$tasks[$i]['TITLE'].'". process ...',"todoer");
			$id = $tasks[$i]['ID'];
			if($id){
				$this -> process_remind($id);
			}
		}
	//закроем главные задачи, у которых выполнены все подчиненные
	$sql = "SELECT ID,TITLE FROM clnd_events a WHERE (SELECT SUM(c.IS_DONE)/COUNT(c.ID) FROM clnd_events c WHERE c.PARENT_ID = a.ID )>=1 and a.AUTODONE_BY_CHILDS=1 AND a.IS_DONE=0";
		$tasks = SQLSelect($sql);
		$total = count($tasks);
		for ($i = 0; $i < $total; $i++) {
			if('SETTINGS_TODOER_LOGGING') debmes('main task finded "'.$tasks[$i]['TITLE'].'". Done it by all childs finished!',"todoer");
			$id = $tasks[$i]['ID'];
			if($id){
				$this -> task_done($id, 1);
			}
		}


}

/**
* calendar_full
*
* @access public
*/
 function calendar_full(&$out,$m1=1,$m2=12) {
  //debmes('we are here - function calendar_full');
  require(DIR_MODULES.$this->name.'/clnd_full.inc.php');
 }
/**
* GetHolidays
*
* @access public
*/
 function clnd_getholidays() {
$year = date('Y');

$rec = SQLSelectOne('select ID from clnd_categories where holidays=1');
if ($rec) {
$hl_ID = $rec['ID'];
//Удаляем все записи за текущий год из календаря
//с категорией у которой стоит галочка Праздники
SQLExec('delete from clnd_events where CALENDAR_CATEGORY_ID=' . $hl_ID . ' and Year(DUE)=' . $year);

$rec = SQLSelectOne('select ID from clnd_categories where holidays=2');
$workdays_ID = $rec['ID'];
//Удаляем все записи за текущий год из календаря
//с категорией у которой стоит галочка Праздники
SQLExec('delete from clnd_events where CALENDAR_CATEGORY_ID=' . $workdays_ID . ' and Year(DUE)=' . $year);

$calendar = simplexml_load_file('http://xmlcalendar.ru/data/ru/'.date('Y').'/calendar.xml');
$hd=$calendar->holidays->holiday; 
$calendar = $calendar->days->day;
foreach( $hd as $hday ){
    $id = (array)$hday->attributes()->id;
    $id = $id[0]; 
    $title = (array)$hday->attributes()->title;
    $title = $title[0]; 
    $holidays[$id]=$title;
}

//все праздники за текущий год
foreach( $calendar as $day ){
    $d = (array)$day->attributes()->d;
    $d = $d[0];
    //не считая короткие дни
    if( $day->attributes()->t == 1 ) {
     $h=$day->attributes()->h;
     if (isset($holidays[(int)$h]))
      $hd_name=$holidays[(int)$h];
     else
      $hd_name='Выходной день';
//     $arHolidays[] = array('DAY'=>substr($d, 3, 2),'MONTH'=>substr($d, 0, 2),'HD_NAME'=>$hd_name);
     $Record = Array();
	 //$Record['IS_TASK'] = 0;
     $Record['DUE'] = $year . '-' . substr($d, 0, 2) . '-' . substr($d, 3, 2) .' 00:00:00';
     $Record['END_TIME'] = $year . '-' . substr($d, 0, 2) . '-' . substr($d, 3, 2) .' 23:59:00';
     $Record['ALL_DAY'] = 1; 
     $Record['CALENDAR_CATEGORY_ID'] = $hl_ID;
     $Record['TITLE'] = $hd_name;
     $Record['ID']=SQLInsert('clnd_events', $Record);
     
    }
    elseif ( $day->attributes()->t ==3 ) {
//     $arWorkdays[]=array('DAY'=>substr($d, 3, 2),'MONTH'=>substr($d, 0, 2));
     $Record = Array();
	 //$Record['IS_TASK'] = 0;
     $Record['DUE'] = $year . substr($d, 0, 2) . substr($d, 3, 2) .' 00:00:00' ;
     $Record['END_TIME'] = $year . '-' . substr($d, 0, 2) . '-' . substr($d, 3, 2) .' 23:59:00';
     $Record['ALL_DAY'] = 1; 
     $Record['CALENDAR_CATEGORY_ID'] = $workdays_ID;
     $Record['TITLE'] = 'Перенесенный рабочий день';
     $Record['ID']=SQLInsert('clnd_events', $Record);

    }
}
}
}
/**
* parse_cr from Eraser
*
* @access public
*/
function parse_cr($_cron_string,$_after_timestamp=null){
        $cron   = preg_split("/[\s]+/i",trim($_cron_string));
        $start  = empty($_after_timestamp)?time():$_after_timestamp;
        $date   = array(    'minutes'   =>_parseCronNumbers1($cron[0],0,59),
                            'hours'     =>_parseCronNumbers1($cron[1],0,23),
                            'dom'       =>_parseCronNumbers1($cron[2],1,31),
                            'month'     =>_parseCronNumbers1($cron[3],1,12),
                            'dow'       =>_parseCronNumbers1($cron[4],0,6),
                        );
						//echo '<pre>';
						//print_r($date);
        // limited to time()+366 - no need to check more than 1year ahead
        for($i=0;$i<=60*60*24*366;$i+=60){
            if( in_array(intval(date('j',$start+$i)),$date['dom']) &&
                in_array(intval(date('n',$start+$i)),$date['month']) &&
                in_array(intval(date('w',$start+$i)),$date['dow']) &&
                in_array(intval(date('G',$start+$i)),$date['hours']) &&
                in_array(intval(date('i',$start+$i)),$date['minutes']) 
                ){
                    return $start+$i;
            }
        }
        return null;
    }
/**
* _parseCronNumbers1 from Eraser
*
* @access public
*/
function _parseCronNumbers1($s,$min,$max){
        $result = array();

        $v = explode(',',$s);
        foreach($v as $vv){
            $vvv  = explode('/',$vv);
            $step = empty($vvv[1])?1:$vvv[1];
            $vvvv = explode('-',$vvv[0]);
            $_min = count($vvvv)==2?$vvvv[0]:($vvv[0]=='*'?$min:$vvv[0]);
            $_max = count($vvvv)==2?$vvvv[1]:($vvv[0]=='*'?$max:$vvv[0]);

            for($i=$_min;$i<=$_max;$i+=$step){
                $result[$i]=intval($i);
            }
        }
        ksort($result);
        return $result;
    } 
/**
* Install
*
* Module installation routine
*
* @access private
*/
 function install($data='') {
 @umask(0);
  if (!Is_Dir(ROOT."./cms/todoer")) {
   mkdir(ROOT."./cms/todoer", 0777);
  }
  parent::install();
 }
/**
* Uninstall
*
* Module uninstall routine
*
* @access public
*/
 function uninstall() {
  SQLExec('DROP TABLE IF EXISTS clnd_events');
  SQLExec('DROP TABLE IF EXISTS clnd_categories');
  unsubscribeFromEvent('todoer', 'MINUTELY');
  parent::uninstall();
 }
/**
* dbInstall
*
* Database installation routine
*
* @access private
*/
 function dbInstall($data) {
/*
clnd_events - Events
clnd_categories - Categories
*/
  $data = <<<EOD
 clnd_events: ID int(10) unsigned NOT NULL auto_increment
 clnd_events: TITLE varchar(255) NOT NULL DEFAULT ''
 clnd_events: SYSTEM varchar(255) NOT NULL DEFAULT ''
 clnd_events: NOTES text
 clnd_events: DUE datetime
 clnd_events: ADDED datetime
 clnd_events: DONE_WHEN datetime
 clnd_events: IS_DONE int(3) NOT NULL DEFAULT '0'
 clnd_events: IS_NODATE int(3) NOT NULL DEFAULT '0'
 clnd_events: IS_REPEATING int(3) NOT NULL DEFAULT '0'
 clnd_events: REPEAT_TYPE int(3) NOT NULL DEFAULT '0'
 clnd_events: WEEK_DAYS varchar(255) NOT NULL DEFAULT ''
 clnd_events: REPEAT_IN int(10) NOT NULL DEFAULT '0'
 clnd_events: USER_ID int(10) NOT NULL DEFAULT '0'
 clnd_events: CALENDAR_CATEGORY_ID int(10) NOT NULL DEFAULT '0'
 clnd_events: DONE_CODE text
 clnd_events: LOG text
 clnd_events: END_TIME datetime
 clnd_events: IS_REMIND int(3) NOT NULL DEFAULT '0'
 clnd_events: REMIND_TIME datetime DEFAULT NULL
 clnd_events: REMIND_TYPE int(3) NOT NULL DEFAULT '0'
 clnd_events: REMIND_TIMER INT(3) NOT NULL DEFAULT '0'
 clnd_events: REMIND_IN INT(3) NOT NULL DEFAULT '0'
 clnd_events: REMIND_CODE text
 clnd_events: ALL_DAY int(3) NOT NULL DEFAULT '0'
 clnd_events: AUTODONE int(3) NOT NULL DEFAULT '0'
 clnd_events: PARENT_ID int(10) NOT NULL DEFAULT '0'
 clnd_events: AUTODONE_BY_CHILDS int(3) NOT NULL DEFAULT '0'
 clnd_events: BEGIN_CODE text
 clnd_events: REPEAT_UNTIL datetime DEFAULT NULL
 clnd_events: IS_REPEAT_UNTIL int(3) NOT NULL DEFAULT '0'
 clnd_events: IS_CRON int(3) NOT NULL DEFAULT '0'
 clnd_events: REPEAT_CRON varchar(255) NOT NULL DEFAULT ''
 clnd_events: YE_MONTHS varchar(255) NOT NULL DEFAULT ''
 clnd_events: IS_BEGIN int(3) NOT NULL DEFAULT '0'


 clnd_categories: ID int(10) unsigned NOT NULL auto_increment
 clnd_categories: TITLE varchar(255) NOT NULL DEFAULT ''
 clnd_categories: ACTIVE int(255) NOT NULL DEFAULT '0'
 clnd_categories: PRIORITY int(10) NOT NULL DEFAULT '0'
 clnd_categories: ICON varchar(70) NOT NULL DEFAULT ''
 clnd_categories: AT_CALENDAR tinyint(1) NOT NULL DEFAULT 0
 clnd_categories: CALENDAR_COLOR int(11) NOT NULL DEFAULT 0
 clnd_categories: HOLIDAYS tinyint(1) NOT NULL DEFAULT 0
 clnd_categories: WORKDAYS tinyint(1) NOT NULL DEFAULT 0
 
EOD;
  parent::dbInstall($data);
  subscribeToEvent('todoer', 'MINUTELY');
 }
// -
// --------------------------------------------------------------------
}
/*
*
* TW9kdWxlIGNyZWF0ZWQgTm92IDI1LCAyMDIwIHVzaW5nIFNlcmdlIEouIHdpemFyZCAoQWN0aXZlVW5pdCBJbmMgd3d3LmFjdGl2ZXVuaXQuY29tKQ==
*
*/
