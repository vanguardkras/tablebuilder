Данная библиотека позволяет легко и быстро отобразить таблицы из баз данных вашего сайта.
Содержит минимальный набор функций и простой синтаксис. Система сама определяет тип полей для редактирования.

Установка.
-------------

Скопируйте файлы в папку с исполняемым файлом вашего сайта. Обычно index.php.

Подключение классов.
---------------------

#### Если установили библиотеку вручную:
```php
set_include_path(get_include_path() . PATH_SEPARATOR . './tablebuilder/');
spl_autoload_register();
```
#### Если установили через composer, подключите как обычно:
```php
require __DIR__ . '/vendor/autoload.php';

use \Tablebuilder\TableBuilder;
```


Настройка.
-----------

Файл properties.php содержит данные для подключения к вашей базе данных.
Изменить метод импорта настроек можно отредактировав метод setSettings() в файле tablebuilder/database.php.

Инструкция.
------------

### 1) Создайте объект tablebuilder. В качестве параметра укажите имя таблицы, с которой хотите работать.

```php
$a = new TableBuilder('templates');
```

Также можно добавить второй параметр, чтобы задать класс CSS для этой таблицы.

```php
$a = new TableBuilder('templates', 'transparent');
```

Третий параметр служит для смены языка кнопок.

```php
$a = new TableBuilder('templates', '', 'en');
```

### 2) Постройте таблицу в нужном месте.

```php
echo $a->build();
```

Если указать в качестве параметра функций любое true значение, то кнопка "Добавить" будет расположена в верхней части таблицы.

```php
echo $a->build(true);
```

### 3) Дополнительные параметры.

Перед вызовом build() можно указать опциональные настройки.

#### 3.1) Изменение имен столбцов таблицы.

Вызовите метод setTitles и передайте массив имён таблицы в качестве параметра.

```php
$a->setTitles(['Имя', 'Фамилия']);
```

#### 3.2) Получение не всех столбцов.

Вызовите метод setColumns и в качестве параметра передайте массив столбцов таблицы MySQL. Также нужно указать значения по-умолчанию для оставшихся столбцов в MySQL, чтобы кнопка "Добавить" работала корректно.

```php
$a->setColumns(['name', 'last_name']);
```

#### 3.3) Изменение типов полей input.

Система автоматически определяет какой тип поля input требуется для столбцов таблицы. Однако вы можете задать их вручную методом setTypes(). В качестве параметра передайте массив с типами полей input.

```php
$a->setTypes(['date', 'number', 'text']);
```

#### 3.4) Добавить отступ в html.

Также можно сдвинуть таблицы на определенное количество пробелов, вручную добавив количество отступов, если необходимо.

```php
$a->setIndent(8);
```

Примеры.
---------

#### Простой вызов:
```php
$a = new TableBuilder('templates');
echo $a->build();
```

#### С указанием класса, имен столбцов и Добавить в начале:
```php
$a = new TableBuilder('templates', 'transparent');
$a->setTitles(['Имя', 'Фамилия']);
echo $a->build(1);
```

#### Только некоторые столбцы:
```php
$a = new TableBuilder('employees');
$a->setColumns(['name', 'last_name']);
$a->setTitles(['Имя', 'Фамилия']);
echo $a->build();
```
