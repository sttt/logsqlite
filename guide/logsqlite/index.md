## Мінімальні вимоги {#requires}
Оскільки в модулі використовується стиль створення масивів, типу `$array = []`, то це вимагає версії PHP >= 5.4
## Рекомендації для підключення {#incluedes}
Модуль можна стандартно підключити через файл `bootstrap.php`, але щоб не дублювались логи і в файлах, і в базі SQLite - краще в тому ж файлі `bootstrap.php` зробити слідуюче:
~~~
// Спочатку шукаємо рядок "Kohana::$log->attach(new Log_File(APPPATH.'logs'));"
// і змінюємо його наступним чином
Kohana::$log->attach($file_log_writer =  new Log_File(APPPATH.'logs'));

// Потім переходимо до частини підключення модулів і додаємо змінну $modules
$modules = Kohana::modules([

   // Тут перелік потрібних вам модулів

   // Далі підключаємо модуль logsqlite
	 'logsqlite'  => MODPATH.'logsqlite',

	]);

// Оскільки підключається модуль Logsqlite, то зразу після підключення модулів
// можна від'єднати записувач логів у файли.
if(isset($modules['logsqlite']) and class_exists('SQLite3'))
	Kohana::$log->detach($file_log_writer);
~~~
## Читання логів
Для того щоб прочитати логи перейдіть за адресою `http://localhost/logsqlite`