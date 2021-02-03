<?php
/*
* @version 0.1 (wizard)
*/
 global $session;
 global $clnd_cat_hide;
  if ($this->owner->name=='panel') {
   $out['CONTROLPANEL']=1;
   $clnd_cat_hide=0;
  }else{
   $out['CONTROLPANEL']=0;
   $clnd_cat_hide=1;
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
global $clnd_user;
if($clnd_user != ""){
	$out['CLND_USER']=(int)$clnd_user;
	$qry.=" AND clnd_events.USER_ID=".$out['CLND_USER'];
}
global $clnd_date_search;
if($clnd_date_search != ""){
	if($clnd_date_search == "1"){
		//today

		$qry.=" and TO_DAYS(DUE)<=TO_DAYS(NOW()) and TO_DAYS(END_TIME)>=TO_DAYS(NOW()) and IS_NODATE=0";
		$out['DATE_SEARCH'] = 1;
		if($out['CONTROLPANEL']==0) $qry.=" and IS_DONE=0";

	}elseif($clnd_date_search == "2"){
		//Soon
		$qry.=" and TO_DAYS(DUE)>=TO_DAYS(NOW())+1 and TO_DAYS(END_TIME)>=TO_DAYS(NOW())+1 and IS_NODATE=0 AND TO_DAYS(DUE)<=TO_DAYS(NOW())+".SETTINGS_TODOER_SOONLIMIT;
		$out['DATE_SEARCH'] = 2;
	}elseif($clnd_date_search == "3"){
		//
		$qry.=" and TO_DAYS(DUE)<=TO_DAYS(NOW()) and IS_NODATE=0 AND TO_DAYS(DUE)>=TO_DAYS(NOW())- 7";
		$out['DATE_SEARCH'] = 3;
	}elseif($clnd_date_search == "4"){
		//no_date
		$qry.=" and IS_NODATE=1";
		$out['DATE_SEARCH'] = 4;
	}elseif($clnd_date_search == "5"){
		//overdue
		$qry.=" and IS_DONE=2 AND (holidays=0 or clnd_categories.title is null)";
		$out['DATE_SEARCH'] = 5;
	}elseif($clnd_date_search == "6"){
		//recently done
		$qry.=" and (IS_DONE=1 AND TO_DAYS(NOW())-TO_DAYS(DONE_WHEN)<=1) OR (IS_REPEATING=1 AND NOW() between END_TIME and DUE)";
		$out['DATE_SEARCH'] = 6;
	}
}else{
	$out['DATE_SEARCH'] = "";
}

if($clnd_cat_hide !=""){
	$qry.=" AND ifnull(AT_CALENDAR,1)!=0";
	$out['CLND_CAT_HIDE'] = 1;
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
	$sortby = "IS_NODATE DESC,DUE ";//one love
	$session->data['clnd_events_sort'] = $sortby;
  // SEARCH RESULTS
  $res = SQLSelect("SELECT clnd_events.*,clnd_categories.TITLE as CATEGORY,clnd_categories.ICON, (SELECT COUNT( d.ID ) FROM clnd_events d WHERE d.parent_id = clnd_events.id ) IS_MAIN FROM clnd_events left join clnd_categories ON clnd_events.calendar_category_id=clnd_categories.id WHERE $qry ORDER BY ".$sortby);
  $out['SORTBY'] = $sortby;
  if ($res[0]['ID']) {
   paging($res, 15, $out); // search result paging
   $total=count($res);
   for($i=0;$i<$total;$i++) {
    // some action for every record if required
    if ($res[$i]['IS_NODATE'] == 1) {
     $res[$i]['DUE'] = '';
	 $res[$i]['END_TIME'] = '';
   
	} elseif($res[$i]['ALL_DAY'] == 1) {
	 $res[$i]['DUE_DAY'] = date('d.m.y',strtotime($res[$i]['DUE']));
	} else {
     $res[$i]['DUE'] = date('d.m.y H:i',strtotime($res[$i]['DUE']));
     $res[$i]['END_TIME'] = date('d.m.y H:i',strtotime($res[$i]['END_TIME']));
    }
   }
   $out['RESULT']=$res;
  }
	//Categories
    if( $clnd_cat_hide){
		$res = sqlselect("select * from clnd_categories where ifnull(AT_CALENDAR,1)!=0");
	}else{
		$res = sqlselect("select * from clnd_categories");
    }
	$out['CATEGORIES'] = $res;
	//Users
	$res = sqlselect("select * from users");
	$out['USERS'] = $res;
  if($out['CONTROLPANEL']==0){
	  $rs_count = SQLSelectOne("SELECT count(*) N FROM clnd_events left join clnd_categories ON clnd_events.calendar_category_id=clnd_categories.id WHERE ifnull(AT_CALENDAR,1)!=0 and TO_DAYS(DUE)<=TO_DAYS(NOW()) and TO_DAYS(END_TIME)>=TO_DAYS(NOW()) and IS_NODATE=0 and IS_DONE=0");
	  $out['COUNT_TODAY'] = $rs_count['N'];
	  $rs_count = SQLSelectOne("SELECT count(*) N FROM clnd_events left join clnd_categories ON clnd_events.calendar_category_id=clnd_categories.id WHERE ifnull(AT_CALENDAR,1)!=0 and  IS_NODATE=1");
	  $out['COUNT_IS_NODATE'] = $rs_count['N'];
	  $rs_count = SQLSelectOne("SELECT count(*) N FROM clnd_events left join clnd_categories on clnd_events.calendar_category_id=clnd_categories.id  WHERE IS_DONE=2 AND ((ifnull(AT_CALENDAR,1)!=0 AND holidays=0) or clnd_categories.title is null)");
	  $out['COUNT_OVERDUE'] = $rs_count['N'];
	  $rs_count = SQLSelectOne("SELECT count(*) N FROM clnd_events left join clnd_categories on clnd_events.calendar_category_id=clnd_categories.id  WHERE IS_DONE=0 and TO_DAYS(DUE)>=TO_DAYS(NOW())+1 and TO_DAYS(END_TIME)>=TO_DAYS(NOW())+1 and IS_NODATE=0 AND TO_DAYS(DUE)<=TO_DAYS(NOW())+".SETTINGS_TODOER_SOONLIMIT);
	  $out['COUNT_SOON'] = $rs_count['N'];
	  $rs_count = SQLSelectOne("SELECT count(*) N FROM clnd_events left join clnd_categories on clnd_events.calendar_category_id=clnd_categories.id  WHERE (IS_DONE=1 AND TO_DAYS(NOW())-TO_DAYS(DONE_WHEN)<=1) OR (IS_REPEATING=1 AND NOW() between END_TIME and DUE)");
	  $out['COUNT_DONE'] = $rs_count['N'];
}
