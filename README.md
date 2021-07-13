Yii2 Knomary LMS RBAC DbManager
=========================


Installation
------------

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
composer require kirillemko/yii2-knomary-lms-rbac
```

or add

```
"kirillemko/yii2-knomary-lms-rbac": "*"
```

to the require section of your composer.json.


Использование
-----

Для использования зарегистрировать компонент
```
'components' => [
    ...
    'authManager' => [
        'class' => 'kirillemko\yiilmsrbac\LMSDbManager',
    ],
```

Возможная конфигурация компонента
```
Массив id ролей, который нельзя удалить или модифицировать
'systemRoleIds' => []

Массив свойств, которые будут возвращены при запросе пользователей
'userFieldsToSend' => ['id', 'email']

Массив свойств, по которым будет производиться поиск
'userSearchFields' => ['email', 'last_name', 'first_name', 'midle_name']

ID админ группы, для которой в миграции будут привязаны правила по умолчанию
'adminGroupIdForInitMigration' => 1
```

Далее выполнить миграцию для создания дополнительных таблиц.
```
vendor\kirillemko\yii-ci-integration\src\yii migrate --migrationPath=@vendor/kirillemko/yii2-knomary-lms-rbac/migrations/
```

Пример для работы с менеджером лежит в папке с контроллером Rbac.php. Там есть весь цикл от получения ролей, прав, пользователей. До создания и удаления

Перевод
-----

Если перевод не подключен, для отображения пермишенна используется свойство name

При подключенном переводе компонент пытается найти перевод в категории 'RBAC' по свойству name. Пример перевода есть в папке example/messages

Пример конфигурации

```
'i18n' => [
    'translations' => [
        ...
        'RBAC' => [ 'class' => 'yii\i18n\PhpMessageSource' ],
],
```

Credits
-------

Authors: Kirill Emelianenko

Email: kirill.emko@mail.ru

