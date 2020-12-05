<?php
/*
* @version 0.1 (wizard)
*/
 global $session;

  if ($this->owner->name=='panel') {
   $out['CONTROLPANEL']=1;
  }
  $qry="1";
  // search filters
  //searching 'TITLE' (varchar)
  global $title;
  if ($title!='') {
   $qry.=" AND clnd_events.TITLE LIKE '%".DBSafe($title)."%'";
   $out['TITLE']=$title;
  }
  global $calendar_category_id;
  if ($calendar_category_id!="") {
	$out['CALENDAR_CATEGORY_ID']=(int)$calendar_category_id;
	$qry.=" AND clnd_events.CALENDAR_CATEGORY_ID=".$out['CALENDAR_CATEGORY_ID'];
  }
global $clnd_date_search;
if($clnd_date_search != ""){
	if($clnd_date_search == "1"){
		//today
		$qry.=" and TO_DAYS(DUE)<=TO_DAYS(NOW()) and TO_DAYS(END_TIME)>=TO_DAYS(NOW()) and IS_NODATE=0";
		$out['DATE_SEARCH'] = 1;
	}elseif($clnd_date_search == "2"){
		$qry.=" and TO_DAYS(DUE)>=TO_DAYS(NOW()) and TO_DAYS(END_TIME)>=TO_DAYS(NOW()) and IS_NODATE=0 AND TO_DAYS(DUE)<=TO_DAYS(NOW())+ 7";
		$out['DATE_SEARCH'] = 2;
	}elseif($clnd_date_search == "3"){
		$qry.=" and TO_DAYS(DUE)<=TO_DAYS(NOW()) and IS_NODATE=0 AND TO_DAYS(DUE)>=TO_DAYS(NOW())- 7";
		$out['DATE_SEARCH'] = 3;
	}
}else{
	$out['DATE_SEARCH'] = "";
}
  // QUERY READY
  global $save_qry;
 
  if ($save_qry) {
   $qry = $session->data['clnd_events_qry'];
  } else {
   $session->data['clnd_events_qry'] = $qry;
  }
  if (!$qry) $qry="1";
  // FIELDS ORDER
  global $sortby;
/*
  if (!$sortby) {
   $sortby = $session->data['clnd_events_sort'];
   //$sortby = "IS_NODATE DESC,DUE DESC";
   } else {
   if ($session->data['clnd_events_sort']==$sortby) {
    if (Is_Integer(strpos($sortby, ' DESC'))) {
     $sortby=str_replace(' DESC', '', $sortby);
    } else {
     $sortby=$sortby." DESC";
    }
   }
   $session->data['clnd_events_sort']=$sortby;
  }
*/
	$sortby = "IS_NODATE DESC,DUE DESC";//one love
	$session->data['clnd_events_sort'] = $sortby;
  // SEARCH RESULTS
  $res = SQLSelect("SELECT clnd_events.*,clnd_categories.TITLE as CATEGORY FROM clnd_events left join clnd_categories ON clnd_events.calendar_category_id=clnd_categories.id WHERE $qry ORDER BY ".$sortby);
  $out['SORTBY'] = $sortby;
  if ($res[0]['ID']) {
   paging($res, 15, $out); // search result paging
   $total=count($res);
   for($i=0;$i<$total;$i++) {
    // some action for every record if required
    if ($res[$i]['IS_NODATE'] == 1) {
     $res[$i]['DUE'] = '';
    } elseif($res[$i]['ALL_DAY'] == 1) {
	 $res[$i]['DUE'] = date('d.m.Y',strtotime($res[$i]['DUE']));
	} else {
	 //$res[$i]['DUE_TIME'] = date('H:i',strtotime($res[$i]['DUE']));
     $res[$i]['DUE'] = date('d.m.Y H:i',strtotime($res[$i]['DUE']));
    }
   }
   $out['RESULT']=$res;
  }
	//Categories
	$res = sqlselect("select * from clnd_categories");
	$out['CATEGORIES'] = $res;
