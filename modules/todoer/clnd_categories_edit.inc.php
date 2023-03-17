<?php
/*
* @version 0.1 (wizard)
*/
  if ($this->owner->name=='panel') {
   $out['CONTROLPANEL']=1;
  }
  $table_name='clnd_categories';
  $rec=SQLSelectOne("SELECT * FROM $table_name WHERE ID='$id'");
  if ($this->mode=='update') {
   $ok=1;
  //updating 'TITLE' (varchar, required)
   global $title;
   $rec['TITLE']=$title;
   if ($rec['TITLE']=='') {
    $out['ERR_TITLE']=1;
    $ok=0;
   }
  //updating 'ACTIVE' (int)
   //global $active;
   //$rec['ACTIVE']=(int)$active;
  //updating 'PRIORITY' (int)
   //global $priority;
   //$rec['PRIORITY']=(int)$priority;
   global $color;
   $rec['COLOR']=$color;
   global $at_calendar;
   $rec['AT_CALENDAR']=(int)$at_calendar;

   global $holidays;
   $rec['HOLIDAYS']=(int)$holidays;
  //UPDATING RECORD
   if ($ok) {
    if ($rec['ID']) {
     SQLUpdate($table_name, $rec); // update
    } else {
     $new_rec=1;
     $rec['ID']=SQLInsert($table_name, $rec); // adding new record
    }
   //updating 'ICON' (image)
   global $icon;
   global $icon_name;
   global $delete_icon;
   if ($icon!="" && file_exists($icon) && (!$delete_icon)) {
     $filename=strtolower(basename($icon_name));
     $ext=strtolower(end(explode(".",basename($icon_name))));
     if (
         (filesize($icon)<=(0*1024) || 0==0) && (Is_Integer(strpos('gif jpg png svg', $ext)))
        ) {
           $filename=$rec["ID"]."_icon_".time().".".$ext;
           if ($rec["ICON"]!='') {
            @Unlink(ROOT.'./cms/todoer/'.$rec["ICON"]);
           }
           Copy($icon, ROOT.'./cms/todoer/'.$filename);
           $rec["ICON"]=$filename;
           SQLUpdate($table_name, $rec);
          }
   } elseif ($delete_icon) {
      @Unlink(ROOT.'./cms/todoer/'.$rec["ICON"]);
      $rec["ICON"]='';
      SQLUpdate($table_name, $rec);
   }
    $out['OK']=1;
   } else {
    $out['ERR']=1;
   }
  }
  if (is_array($rec)) {
   foreach($rec as $k=>$v) {
    if (!is_array($v)) {
     $rec[$k]=htmlspecialchars($v);
    }
   }
  }
  outHash($rec, $out);
?>