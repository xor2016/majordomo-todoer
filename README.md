# majordomo-ToDoer
Пробую добавить время для событий/задач
Напоминалку
Рабочая версия

2021-03-09 поправил напоминалку для повторяющихся задач

2021-03-14 добавлены svg для иконок категорий, чуть увеличил их для красоты

2021-03-15 test connect with master branch

API
Команды:
query
<pre>
$command='query';
$data = array(
'query'=>"SELECT * FROM clnd_categories"
);
callAPI('/api/module/todoer/' . $command, 'GET', $data);
</pre>
categories
tasks
параметры:
filter
значения для filter:
all, today, soon, latest, recently_done, done, overdue, nodate, between или явное выражение sql для фильтра where 1=1...

//получим задачи, актуальные между -6 часов и +6 часов от сейчас
$command='tasks';
$data = array(
'filter'=>"between",
'from' =>time()-6*60*60,
'to' =>time()+6*60*60,
);
callAPI('/api/module/todoer/' . $command, 'GET', $data);

//получим задачи, актуальные сегодня
$command='tasks';
$data = array('filter'=>"today");
callAPI('/api/module/todoer/' . $command, 'GET', $data);