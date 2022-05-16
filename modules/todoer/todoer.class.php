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
   $this->admin($out);
  }
  $this->checkSettings();
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
  $p=new parser(DIR_TEMPLATES . $this->name . "/" . $this->name . ".html", $this->data, $this);
  $this->result=$p->result;
}
/**
* Title
*
* Description
*
* @access public
*/
 function checkSettings() {
  
  $settings=array(
   array(
    'NAME'=>'TODOER_SOONLIMIT', 
    'TITLE'=>'Сколько дней показывать в "Скоро"', 
    'TYPE'=>'text',
    'DEFAULT'=>'14'
    ),
   array(
    'NAME'=>'TODOER_SHOWDONE', 
    'TITLE'=>'Показывать недавно выполненные дела',
    'TYPE'=>'yesno',
    'DEFAULT'=>'1'
    ),
   array(
    'NAME'=>'TODOER_STD_REMIND', 
    'TITLE'=>'Стандартное время напоминания', 
    'TYPE'=>'text',
    'DEFAULT'=>'10:15'
    ),
   array(
    'NAME'=>'TODOER_LOGGING', 
    'TITLE'=>'Логирование', 
    'TYPE'=>'yesno',
    'DEFAULT'=>'1'
    ),
   array(
    'NAME'=>'TODOER_SHOWMAINONLY', 
    'TITLE'=>'Не показывать подчиненные задачи*', 
    'TYPE'=>'yesno',
    'DEFAULT'=>'0'
    ),
   );


   foreach($settings as $k=>$v) {
    $rec=SQLSelectOne("SELECT ID FROM settings WHERE NAME='".$v['NAME']."'");
    if (!$rec['ID']) {
     $rec['NAME']=$v['NAME'];
     $rec['VALUE']=$v['DEFAULT'];
     $rec['DEFAULTVALUE']=$v['DEFAULT'];
     $rec['TITLE']=$v['TITLE'];
     $rec['TYPE']=$v['TYPE'];
     $rec['DATA']=$v['DATA'];
     $rec['ID']=SQLInsert('settings', $rec);
     Define('SETTINGS_'.$rec['NAME'], $v['DEFAULT']);
    }
   }

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
   //$this->redirect("?");
	$this->redirect("?data_source=clnd_events");
  }
 }
/*
 if ($this->data_source=='clnd_categories') {
  if ($this->view_mode=='' || $this->view_mode=='search_clnd_categories') {
   $this->search_clnd_categories($out);
  }
}
*/
 if ($this->view_mode=='delete_past_events') {
   $this->delete_past_events();
   $this->redirect("?data_source=clnd_events");
  }
 if ($this->view_mode=='delete_done_tasks') {
   $this->delete_done_tasks();
   $this->redirect("?data_source=clnd_events");
  }

 if ($this->data_source=='clnd_full') {
   $this->calendar_full($out);
}

 if ($this->data_source=='clnd_categories') {
  if ($this->view_mode=='' || $this->view_mode=='search_clnd_categories') {
   $this->search_clnd_categories($out);
  }
  if ($this->view_mode=='edit_clnd_categories') {
   $this->edit_clnd_categories($out, $this->id);
  }
  if ($this->view_mode=='delete_clnd_categories') {
   $this->delete_clnd_categories($this->id);
   $this->redirect("?data_source=clnd_categories");
  }
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
 if ($this->view_mode=='edit') {
  $this->edit_clnd_events($out,$this->$id);
 }

 if ($this->view_mode=='') {
  

  if ($this->mode=='is_done') {
   global $id;
   $this->task_done($id,1);
   $this->redirect("?");
  }

  if ($this->mode=='reset_done') {
   global $id;

   $rec=SQLSelectOne("SELECT * FROM clnd_events WHERE ID='".(int)$id."'");
   $rec['IS_DONE'] = 0;
   $rec['IS_BEGIN'] = ( time()< strtotime($rec['DUE']))?0:1;

   SQLUpdate('clnd_events', $rec);

   $this->redirect("?");
  }

  $this->calendar_full($out);
  $hidetask = "";
if(SETTINGS_TODOER_SHOWMAINONLY){
	$hidetask = " and clnd_events.parent_id=0";
}

 }//$this->view_mode==''
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
	require(dirname(__FILE__).'/clnd_events_edit.inc.php');
}
 function usual_edit(&$out, $id) {
 	require(dirname(__FILE__).'/clnd_events_edit.inc.php');
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
* calendar_categories edit/add
*
* @access public
*/
 function edit_clnd_categories(&$out, $id) {
  require(DIR_MODULES.$this->name.'/clnd_categories_edit.inc.php');
 }
/**
* calendar_categories delete
*
* @access public
*/
 function delete_clnd_categories($id) {
  SQLExec("DELETE FROM clnd_categories WHERE `ID`=".$id);
  SQLExec("UPDATE clnd_events SET CALENDAR_CATEGORY_ID=0 WHERE CALENDAR_CATEGORY_ID=".$id);
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
		debmes('Bad deleting task id ='. $id,'todoer');
		return; 
	}
	$tm = time();
	if( strlen($rec['LOG']) > 100) $rec['LOG'] = "..."; //обрежем лог

	//if(SETTINGS_TODOER_LOGGING && $rec['IS_BEGIN']==0) debmes('IS_BEGIN = 0 for doned task "'.$rec['TITLE'].'" ','todoer');

	if($autoend == "0"){ //завершилась по времени окончания без признака готовности (для нового повтора)
		if($rec['IS_REPEATING']){
			$rec['DONE_WHEN'] = null;
			$rec['IS_DONE'] = 0;
			$rec['IS_BEGIN'] = 0; //перезапустим всю задачу
			if(SETTINGS_TODOER_LOGGING) debmes('repeated task "'.$rec['TITLE'].'" ended but not marked done','todoer');
			$rec['LOG'] .= date('d.m.y H:i:s', $tm).' - завершено без исполнения.';
			
		}else{//просрочено
			$rec['DONE_WHEN'] = null;
			$rec['IS_DONE'] = 2;
			$flag_done = 2; 
			if(SETTINGS_TODOER_LOGGING) debmes('task "'.$rec['TITLE'].'" is overdue','todoer');
			$rec['LOG'] .= date('d.m.y H:i:s', $tm).' - просрочено.';

		}
	}else{ //задача была выполнена
		$rec['DONE_WHEN'] = date('Y-m-d H:i:s', $tm);
		$rec['IS_DONE'] = 1;
		$flag_done = 1;
		if(SETTINGS_TODOER_LOGGING) debmes('--> "'.$rec['TITLE'].'" (id='.$id.') is done!','todoer');
		$rec['LOG'] .= date('d.m.y H:i:s', $tm).' - завершено.';
	}


	if ($rec['IS_REPEATING']) {
		$flag_done = 0;
		$due_time = strtotime(date('Y-m-d H:i:00',strtotime($rec['DUE']))); //unixtime
		$end_time = strtotime(date('Y-m-d H:i:00',strtotime($rec['END_TIME']))); //unixtime
		if($end_time < $due_time) $end_time = $rec['ALL_DAY']?(strtotime(date('Y-m-d')." 23:59:00",$tm)):$due_time; //fix end_time

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

			$cron_due = $this -> parse_cron_str($rec['REPEAT_CRON'],time() + 60 ); //todo - check it!
			$new_due = date('Y-m-d H:i:00', $cron_due);
			$new_end = date('Y-m-d H:i:00', $cron_due + $duration);
	
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
			      $due_time_next_week = $due_time + $repeat_in*7*24*60*60;
					if($due_time_next_week <= $tm){ 
						$tt = (int)(($tm - $due_time)/($repeat_in*7*24*60*60));
						$due_time_next_week = $due_time + $tt*$repeat_in*7*24*60*60;
					}
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
				if($due_time_next_day <= $tm){ 
					$tt = (int)(($tm - $due_time)/($repeat_in*24*60*60));
					$due_time_next_day = $due_time + $tt*$repeat_in*24*60*60;
				}
		       $new_due = date('Y-m-d H:i:00', $due_time_next_day);
		       $new_end = date('Y-m-d H:i:00', $due_time_next_day + $duration);
		
		   } elseif ($rec['REPEAT_TYPE'] == 5) {//часы
				
		       $due_time_next_hour = $due_time + $repeat_in*60*60;
				if($due_time_next_hour <= $tm){ //in future only!
					$tt = (int)(($tm - $due_time)/($repeat_in*60*60));
					$due_time_next_hour = $due_time + $tt*$repeat_in*60*60;
				}

		       $new_due = date('Y-m-d H:i:00', $due_time_next_hour);
		       $new_end = date('Y-m-d H:i:00', $due_time_next_hour + $duration);
		
		   } elseif ($rec['REPEAT_TYPE'] == 6) {//минуты

				$due_time_next_minute = $due_time + $repeat_in*60;
				if($due_time_next_minute <= $tm){ //in future only!
					$tt = (int)(($tm - $due_time)/($repeat_in*60));
					$due_time_next_minute = $due_time + $tt*$repeat_in*60;
				}

		       $new_due = date('Y-m-d H:i:00', $due_time_next_minute);
		       $new_end = date('Y-m-d H:i:00', $due_time_next_minute + $duration);
			}
		} 
	if(SETTINGS_TODOER_LOGGING) debmes('repeated task "'.$rec['TITLE'].'" sets new due '.$new_due,'todoer');
	$rec['LOG'] .= " Новый срок: ".date('d.m.y H:i:00',strtotime($new_due));
	//upd remind for repeat events/tasks
	if($rec['IS_REMIND']!=0 && $rec['REMIND_TIMER']<10){
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
     if($remd < $tm) $remd = $tm + 60; //must be in future
	 if($rec['REMIND_TIMER'] > 7 || $rec['ALL_DAY']){//напоминания для Напомнить за день - в стандартное время
		//$standart_remind_time = (SETTINGS_TODOER_STD_REMIND)?SETTINGS_TODOER_STD_REMIND:"12:00";
		$remd = strtotime(date('Y-m-d H:i:00', strtotime(date('Y-m-d',$remd)." ".SETTINGS_TODOER_STD_REMIND)));
	 }
     $rec['REMIND_TIME'] = date('Y-m-d H:i'.':00', $remd);
	 $rec['IS_REMIND'] = 1;
  }
	 
	//check repeat_until
	if($rec['IS_REPEAT_UNTIL'] == "1" ){
		if(strtotime($new_due) >= strtotime($rec['REPEAT_UNTIL'])){
			//сбросим флаг повтора, завершим задачу и отпишемся в лог
			$rec['IS_REPEATING'] = 0;
			$flag_done = 1; 
			$new_due = $rec['DUE']; 
			$new_end = $rec['END_TIME'];
			if(SETTINGS_TODOER_LOGGING) debmes('repeated task "'.$rec['TITLE'].'" ended by REPEAT_UNTIL','todoer');
			$rec['LOG'] .= ' '.date('d.m.y H:i:s',$tm).' - завершены повторы';
		}
	}
	//fin process for repeating
	$rec['IS_DONE'] = $flag_done;
	$rec['DUE'] = $new_due;
	$rec['END_TIME'] = $new_end;
	$rec['IS_BEGIN'] = ($tm < strtotime($new_due))?0:$rec['IS_BEGIN'];
	if(SETTINGS_TODOER_LOGGING) debmes('Fin: repeated task "'.$rec['TITLE'].'" new due:'. $new_due.'-'.$new_end.'  is_begin='.$rec['IS_BEGIN'],'todoer');

}
//save 
SQLUpdate('clnd_events', $rec);

	if($autoend){
		if($rec['DONE_CODE']){

			try {
				$code = $rec['DONE_CODE'];
				$success = eval($code);//todo - safe mode to run
				if ($success === false)
					DebMes("Error in Todoer Done code: " . $code);
			} catch (Exception $e) {
				DebMes('Error: exception ' . get_class($e) . ', ' . $e->getMessage() . '.');
			}

		}
	}
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
	SQLExec("update clnd_events set IS_BEGIN=1, LOG='".date('d.m.y H:i:s')." - старт.' where id = $id");	
	if($code){
	//	setTimeOut('start_tsk'.$id.'_'.$tm,$code,1);
	
		try {
			$success = eval($code);//todo - safe mode to run
			if ($success === false)
				DebMes("Error in Todoer Begin code: " . $code);
		} catch (Exception $e) {
			DebMes('Error: exception ' . get_class($e) . ', ' . $e->getMessage() . '.');
		}
	}
}

 function process_remind($id) {
	$rec = SQLSelectOne("SELECT * FROM clnd_events WHERE ID='".(int)$id."'");
	$txt = '';
	if($rec['ID']){
	  //?user
	  if($rec['USER_ID']){
		$users = SQLSelectOne("SELECT * FROM users  WHERE ID='".$rec['USER_ID']."'");
		if($users){
			$user_name = $users['NAME'];
			$txt .= $user_name."!";
		}
	  }
	  if($rec['REMIND_TYPE']==0){
		$task = $rec['TITLE'];
		$cmd = "say('".$txt." Напоминаю о задаче - $task!',2);";
		setTimeOut('reminder_task_'.$rec['ID'],$cmd,1);//вместо say - иначе почему-то валится цикл exec
	  }else{
		if ($rec['REMIND_CODE']!==""){
			                        try {
			                            $code = $rec['REMIND_CODE'];
			                            $success = eval($code);
			                            if ($success === false)
			                                DebMes("Error in Todoer Reminder code: " . $code);
			                        } catch (Exception $e) {
			                            DebMes('Error: exception ' . get_class($e) . ', ' . $e->getMessage() . '.');
			                        }
			                    }
	  }
		($rec['IS_REPEATING'] && $rec['REMIND_TIMER']<10)?$rec['IS_REMIND'] = -1:$rec['IS_REMIND'] = 0;
		SQLUpdate('clnd_events', $rec);
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
		if($tasks[$i]['IS_BEGIN'] == "0" ){//process begin_code
				if(SETTINGS_TODOER_LOGGING) debmes('start task finded "'.$tasks[$i]['TITLE'].'". process...',"todoer");
				$this -> task_begin($tasks[$i]['ID'],$tasks[$i]['BEGIN_CODE']);
		}
	}

	//закроем окончившиеся задачи + окончившиеся по времени повторяющиеся задачи и вычислим новые для повторов
	$sql = "SELECT ID,TITLE,AUTODONE FROM `clnd_events` WHERE `IS_DONE`=0 and IS_NODATE=0 and date_FORMAT(`END_TIME`, '%Y%m%d%H%i')<=date_FORMAT(NOW(), '%Y%m%d%H%i')";
	$tasks = SQLSelect($sql);
	$total = count($tasks);
	for ($i = 0; $i < $total; $i++) {
			$id = $tasks[$i]['ID'];
			if($id){
				if(SETTINGS_TODOER_LOGGING) debmes('repeated/ended task "'.$tasks[$i]['TITLE'].'" finded. process with autodone='.$tasks[$i]['AUTODONE'],"todoer");

				$this -> task_done($id, $tasks[$i]['AUTODONE']);//запустим обновление с/без запуска скриптов
			}
	}
	//напоминалки
	$sql = "SELECT ID,TITLE FROM `clnd_events` WHERE `IS_REMIND`=1 and IS_DONE=0 and date_FORMAT(`REMIND_TIME`, '%Y%m%d%H%i')<=date_FORMAT(NOW(), '%Y%m%d%H%i')";
	$tasks = SQLSelect($sql);
	$total = count($tasks);
	for ($i = 0; $i < $total; $i++) {
			$id = $tasks[$i]['ID'];
			if($id){
				if(SETTINGS_TODOER_LOGGING) debmes('reminder finded for task "'.$tasks[$i]['TITLE'].'". process ...',"todoer");
				$this -> process_remind($id);
			}
		}
	//закроем главные задачи, у которых выполнены все подчиненные
	$sql = "SELECT ID,TITLE FROM clnd_events a WHERE (SELECT SUM(c.IS_DONE)/COUNT(c.ID) FROM clnd_events c WHERE c.PARENT_ID = a.ID )>=1 and a.AUTODONE_BY_CHILDS=1 AND a.IS_DONE=0";
		$tasks = SQLSelect($sql);
		$total = count($tasks);
		for ($i = 0; $i < $total; $i++) {
			$id = $tasks[$i]['ID'];
			if($id){
				if(SETTINGS_TODOER_LOGGING) debmes('main task finded "'.$tasks[$i]['TITLE'].'". Done it by all childs finished!',"todoer");
				$this -> task_done($id, 1);
			}
		}


}

/**
* create_new_task
* no_double = 1  do not create task if exists the same (overwrite?)
* @access public
*/
function create_new_task($task, $no_double=0){
$rec = array();
if($task['TITLE']){
	$rec['TITLE'] = $task['TITLE'];
}else{
	return "no title - no task!";
}
if($no_double){
	//$tst = SQLSELECTONE("select ID from clnd_events where `TITLE`='".$task['TITLE']."'");
	//if($tst){
	//	return "task with this title exists!";
	//}
	SQLEXEC("delete from clnd_events where `TITLE`='".$task['TITLE']."'");
}
if($task['IS_NODATE']){
	$rec['IS_NODATE'] = 1;
}else{
	$rec['IS_NODATE'] = 0;
	$task['DUE']?$rec['DUE'] = $task['DUE']:$rec['DUE'] = date('Y-m-d H:i:00');
	$task['END_TIME']?$rec['END_TIME'] = $task['END_TIME']:$rec['END_TIME'] = date('Y-m-d H:i:00');
}
if($task['NOTES'])$rec['NOTES'] = $task['NOTES'];
if($task['IS_REPEATING'])$rec['IS_REPEATING'] = $task['IS_REPEATING'];
if($task['REPEAT_TYPE'])$rec['REPEAT_TYPE'] = $task['REPEAT_TYPE'];
if($task['REPEAT_IN'])$rec['REPEAT_IN'] = $task['REPEAT_IN'];
if($task['BEGIN_CODE'])$rec['BEGIN_CODE'] = $task['BEGIN_CODE'];
if($task['DONE_CODE'])$rec['DONE_CODE'] = $task['DONE_CODE'];
if($task['AUTODONE'])$rec['AUTODONE'] = $task['AUTODONE'];
$task['REPEAT_UNTIL']?$rec['REPEAT_UNTIL'] = $task['REPEAT_UNTIL']:$rec['REPEAT_UNTIL'] = date('Y-m-d H:i:00');
$task['REMIND_TIME']?$rec['REMIND_TIME'] = $task['REMIND_TIME']:$rec['REMIND_TIME'] = date('Y-m-d H:i:00');
if($task['CATEGORY']){
	$cat = SQLSELECTONE("select * from clnd_categories where title like '%".dbsafe($task['CATEGORY'])."%'");
	if($cat){
		$rec['CALENDAR_CATEGORY_ID'] = $cat['ID'];
	}
}
//if($task['CATEGORY_ID'])$rec['CATEGORY_ID'] = $task['CATEGORY_ID'];
$rec['ADDED'] = date('Y-m-d H:i:s');

return SQLInsert('clnd_events', $rec);
}
/**
* calendar_full
*
* @access public
*/
 function calendar_full(&$out,$m1=1,$m2=12) {
  require(DIR_MODULES.$this->name.'/clnd_full.inc.php');
 }
/**
* calendar_events delete_done_tasks
*
* @access public
*/
 function delete_done_tasks() {
  SQLExec("DELETE FROM clnd_events WHERE IS_DONE=1 AND IS_REPEATING=0 ");
 }

/**
* calendar_events delete all old tasks
*
* @access public
*/
 function delete_past_events() {
$hl_ID=-1;
$workdays_ID=-1;
$rec=SQLSelectOne('select ID from clnd_categories where holidays=1');
if ($rec) 
 $hl_ID=$rec['ID'];

$rec=SQLSelectOne('select ID from clnd_categories where holidays=2');
if ($rec) 
 $workdays_ID=$rec['ID'];

  SQLExec("DELETE FROM clnd_events WHERE not CALENDAR_CATEGORY_ID in (".$hl_ID.",".$workdays_ID.") AND IS_REPEATING=0  and IS_NODATE=0 and (TO_DAYS(NOW())-TO_DAYS(DUE))>1");
 //прошлый год - праздники почистим
  SQLExec("DELETE FROM clnd_events WHERE CALENDAR_CATEGORY_ID in (".$hl_ID.",".$workdays_ID.") and IS_REPEATING=0 and IS_NODATE=0 and YEAR(NOW())>YEAR(DUE)");

 }
/**
* GetHolidays
*
* @access public
* holidays =1 holiday, 2 workday
*/
 function clnd_getholidays($year='') {
	if($year){
		$year = (int)$year;
	}else{
		$year = date('Y');
	}
$rec = SQLSelectOne('select ID from clnd_categories where holidays=1');
	if ($rec) {

		$calendar = simplexml_load_file('http://xmlcalendar.ru/data/ru/'.$year.'/calendar.xml');
		if($calendar){
			$hl_ID = $rec['ID'];
			//Удаляем все записи за текущий год из календаря
			//с категорией у которой стоит галочка Праздники
			SQLExec('delete from clnd_events where CALENDAR_CATEGORY_ID=' . $hl_ID . ' and Year(DUE)=' . $year);
			
			$rec = SQLSelectOne('select ID from clnd_categories where holidays=2');
			if($rec){
				$workdays_ID = $rec['ID'];
				//Удаляем все записи за текущий год из календаря
				//с категорией у которой стоит галочка Рабочие
				SQLExec('delete from clnd_events where CALENDAR_CATEGORY_ID=' . $workdays_ID . ' and Year(DUE)=' . $year);
			}

			$hd = $calendar->holidays->holiday; 
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
				 if($day->attributes()->f){
				 	$ph = $day->attributes()->f;
					$Record['NOTES'] = "Перенесено с ".substr($ph,3,2).".".substr($ph,0,2).".".$year;
					$Record['TITLE'] = $hd_name." (перенос c ". substr($ph,3,2).".".substr($ph,0,2).")";
				 }else{
			     	$Record['NOTES'] = "добавлено из производственного календаря";
					$Record['TITLE'] = $hd_name; 
				 }
			     $Record['CALENDAR_CATEGORY_ID'] = $hl_ID;
				 if(time()> strtotime($Record['END_TIME']))$Record['IS_DONE'] = 1;
				 if(time()> strtotime($Record['DUE']))$Record['IS_BEGIN'] = 1;
			     $Record['ID']=SQLInsert('clnd_events', $Record);
			     
			    }
			    elseif ( $day->attributes()->t ==3 ) {
			//     $arWorkdays[]=array('DAY'=>substr($d, 3, 2),'MONTH'=>substr($d, 0, 2));
			     $Record = Array();
				 //$Record['IS_TASK'] = 0;
			     $Record['DUE'] = $year . '-' . substr($d, 0, 2) . '-' . substr($d, 3, 2) .' 00:00:00' ;
			     $Record['END_TIME'] = $year . '-' . substr($d, 0, 2) . '-' . substr($d, 3, 2) .' 23:59:00';
			     $Record['ALL_DAY'] = 1; 
			     $Record['NOTES'] = "добавлено из производственного календаря"; 
			     $Record['CALENDAR_CATEGORY_ID'] = $workdays_ID;
			     $Record['TITLE'] = 'Рабочий день';
				 if(time()> strtotime($Record['END_TIME']))$Record['IS_DONE'] = 1;
				 if(time()> strtotime($Record['DUE']))$Record['IS_BEGIN'] = 1;

			     $Record['ID']=SQLInsert('clnd_events', $Record);
				}
				elseif ( $day->attributes()->t ==2 ) {
			//     $arWorkdays[]=array('DAY'=>substr($d, 3, 2),'MONTH'=>substr($d, 0, 2));
				 //только для субботы и воскресенья
				 $s_day = substr($d, 3, 2);
				 $s_month = substr($d, 0, 2);
				 $dt =  mktime(0, 0, 0, (int)$s_month, (int)$s_day, (int)$year);
				 if (date('w', $dt) == 0 || date('w', $dt) == 6) {
				     $Record = Array();
					 //$Record['IS_TASK'] = 0;
				     $Record['DUE'] = $year . '-' . $s_month . '-' . $s_day .' 00:00:00' ;
				     $Record['END_TIME'] = $year . '-' . $s_month . '-' . $s_day .' 23:59:00';
				     $Record['ALL_DAY'] = 1; 
				     $Record['NOTES'] = "добавлено из производственного календаря"; 
				     $Record['CALENDAR_CATEGORY_ID'] = $workdays_ID;
				     $Record['TITLE'] = 'Сокращенный рабочий день';
     				 if(time()> strtotime($Record['END_TIME']))$Record['IS_DONE'] = 1;
					 if(time()> strtotime($Record['DUE']))$Record['IS_BEGIN'] = 1;

				     $Record['ID']=SQLInsert('clnd_events', $Record);
					}
				}
			}
		}
	}
}


/**
* parse_cron_str from Eraser
*
* @access public
*/

function parse_cron_str($_cron_string,$_after_timestamp=null){
        $cron   = preg_split("/[\s]+/i",trim($_cron_string));
        $start  = empty($_after_timestamp)?time():$_after_timestamp;
        $date   = array(    'minutes'   =>$this->_parseCronNumbers1($cron[0],0,59),
                            'hours'     =>$this->_parseCronNumbers1($cron[1],0,23),
                            'dom'       =>$this->_parseCronNumbers1($cron[2],1,31),
                            'month'     =>$this->_parseCronNumbers1($cron[3],1,12),
                            'dow'       =>$this->_parseCronNumbers1($cron[4],0,6),
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
* data_out выдадим данные в массиве по параметру - для тех, кто не любит запросы писать
* what -> all, today, soon, latest, recently_done, done, overdue, nodate или явное выражение sql для фильтра where
* @access public
*/
function  data_out($what='all')
{
	$qry = "1=1";
	$what = trim($what);
	if($what == "all" || $what == ''){
		$qry .= "";
	}elseif($what == "today"){
		$qry .= " and TO_DAYS(DUE)<=TO_DAYS(NOW()) and TO_DAYS(END_TIME)>=TO_DAYS(NOW()) and IS_NODATE=0";
	}elseif($what == "soon"){
		$qry .= " and TO_DAYS(DUE)>=TO_DAYS(NOW())+1 and TO_DAYS(END_TIME)>=TO_DAYS(NOW())+1 and IS_NODATE=0 AND TO_DAYS(DUE)<=TO_DAYS(NOW())+".SETTINGS_TODOER_SOONLIMIT;
	}elseif($what == "latest"){//недавно
		$qry .= " and TO_DAYS(DUE)<=TO_DAYS(NOW()) and IS_NODATE=0 AND TO_DAYS(DUE)>=TO_DAYS(NOW())- 7";
	}elseif($what == "nodate"){
		$qry .= " and IS_NODATE=1";
	}elseif($what == "overdue"){
		$qry .= " and IS_DONE=2 AND holidays=0";
	}elseif($what == "recently_done"){		//recently done
		$qry .= " and ((IS_DONE=1 AND TO_DAYS(NOW())-TO_DAYS(DONE_WHEN)<=1) OR (IS_REPEATING=1 AND NOW() between END_TIME and DUE))";
	}elseif($what == "done"){		
		$qry .= " and IS_DONE=1";
	}else{//free form
		$qry .= " ".$what ;
	}
	$res = SQLSelect("SELECT clnd_events.*,clnd_categories.TITLE as CATEGORY,clnd_categories.ICON,clnd_categories.holidays CAT_HDAYS,clnd_categories.AT_CALENDAR, (SELECT COUNT( d.ID ) FROM clnd_events d WHERE d.parent_id = clnd_events.id ) IS_MAIN FROM clnd_events left join clnd_categories ON clnd_events.calendar_category_id=clnd_categories.id WHERE $qry ORDER BY IS_NODATE DESC,DUE");
	 return $res;
}
    // Find data in module
    function findData($data) {
        $res = array();
        $tasks = SQLSelect("SELECT `ID`,`TITLE` FROM `clnd_events` WHERE `TITLE` like '%" . DBSafe($data) . "%' OR `NOTES` like '%" . DBSafe($data) . "%' or `DONE_CODE` like '%" . DBSafe($data) . "%' or `REMIND_CODE` like '%" . DBSafe($data) . "%' or `BEGIN_CODE` like '%" . DBSafe($data) . "%' order by TITLE");
        foreach($tasks as $task){
        $res[]= '<span class="label label-primary">Task</span>&nbsp;<a href="/panel/todoer.html?md=todoer&view_mode=edit_clnd_events&id=' . $task['ID'] . '.html">' . $task['TITLE']. '</a>';
        }
        return $res;
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
 clnd_events: LOCATION_ID int(10) NOT NULL DEFAULT '0'

 clnd_categories: ID int(10) unsigned NOT NULL auto_increment
 clnd_categories: TITLE varchar(255) NOT NULL DEFAULT ''
 clnd_categories: ACTIVE int(255) NOT NULL DEFAULT '0'
 clnd_categories: PRIORITY int(10) NOT NULL DEFAULT '0'
 clnd_categories: ICON varchar(70) NOT NULL DEFAULT ''
 clnd_categories: AT_CALENDAR tinyint(1) NOT NULL DEFAULT 0
 clnd_categories: CALENDAR_COLOR int(11) NOT NULL DEFAULT 0
 clnd_categories: HOLIDAYS tinyint(1) NOT NULL DEFAULT 0
 
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
