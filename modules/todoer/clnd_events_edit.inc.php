<?php
/*
* @version 0.1 (wizard)
*/
 $tm = time();

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
	$out['DUE'] = date('Y-m-d H:i:00', $tm);
	$out['END_TIME'] = date('Y-m-d H:i:00', $tm);
	$out['REMIND_TIME']= date('Y-m-d H:i:00', $tm);
	//$out['IS_TASK'] = 0;
	$out['IS_NODATE'] = 0;
	$out['ALL_DAY'] = 0;
	$out['IS_REPEATING'] = 0;
	$out['REPEAT_UNTIL'] = date('Y-m-d H:i:00', $tm);
	$out['LOG'] = '';

  }

  if ($this->mode=='update') {
   $ok=1;

   //global $is_task;
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
    $rec['DUE'] = date('Y-m-d H:i'.':00', $tm + 60);
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
	
   //global $is_begin;
   //if($tm < strtotime($rec['DUE'])) {$rec['IS_BEGIN'] = 0;} else {$rec['IS_BEGIN'] = 1;}
   $rec['IS_BEGIN'] = ( $tm < strtotime($rec['DUE']))?0:1;

   global $is_done;//признак закрытия
   if ($is_done == "1" && $rec['IS_DONE'] != "1") {
    $marked_done = 1; 
	$rec['IS_BEGIN'] = 1; 
   }
   
   if ($is_done == "0"  && $rec['IS_DONE'] > 0) {//переоткроем закрытую задачу
    //if($tm < strtotime($rec['DUE'])) $rec['IS_BEGIN'] = 0;
	$rec['IS_DONE'] = 0;
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
     if($remd < $tm ) $remd = $tm + 60; //must be in future
	 if($rec['REMIND_TIMER'] > 7 || $rec['ALL_DAY']){//напоминания для Напомнить за день - в стандартное время

		$standart_remind_time = (SETTINGS_TODOER_STD_REMIND)?SETTINGS_TODOER_STD_REMIND:"12:00";
		$remd = strtotime(date('Y-m-d H:i:00', strtotime(date('Y-m-d',$remd)." ".$standart_remind_time.":00")));
	 }
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
	if(!$rec['REPEAT_UNTIL'] || strtotime($rec['REPEAT_UNTIL']) == 0){
		$rec['REPEAT_UNTIL'] = $rec['END_TIME'];
	}

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
     $rec['ADDED'] = date('Y-m-d H:i:s');
     $rec['ID'] = SQLInsert('clnd_events', $rec);
    }
    if ($marked_done) {
     $this->task_done($rec['ID'],1);
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
    


//end file

