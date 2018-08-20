# tachyon2

Tiny MVC php framework.

Features:
- Dependency Injection Container. It uses setter methods and xml configuration files;
- Front Controller with a simple (file system) routing;
- PDO based DBAL;
- ORM (AR with foreign keys support and special self-invented kind of models for sub-queries). Related records can be extracted by "greedy" (by one request) or "lazy" way;
- 2 types of simplest (file system) caching for DB and http requests;
- protection against XSS and SQL injection;
- very simple i18n;
- view layout (vanilla PHP and XSLT).

Components:
- form generator, which picks up the model validation rules and turns them to JS (instead of AJAX-validation for speed sake). Usual server-side validation also exists. It depends on the "scenario" of model using;
- html tag helper;
- grid widget;
- datepicker widget;
- model validator;
- files-uploading;
- work with cookies;
- authentication.
- Tiny JS framework.

All PHP and JS code, except datepicker is written by me.


MVC микро-PHP-фреймворк.

Это Front Controller с простым (физическим) роутингом.
Реализован Dependency Injection Container, который использует сеттеры и файлы конфигурации xml;
Реализована ORM (AR с поддержкой внешних ключей + особый вид моделей для подзапросов) на собственном DBAL на основе PDO.
Связанные записи могут загружаться как "жадно" так и "лениво".

Компоненты:
- генератор форм, который подхватывает правила валидации из модели и превращает в JS (вместо AJAX-валидации, для скорости). Обычная валидация на основе правил валидации из модели так же имеется. Она зависит от "сценария" использования модели.
- 2 вида кэширования в простейшем виде для ДБ и http запросов. Для этого используется файловая система.
- защита от XSS и SQL injection.
- элементарная многоязычность.
- компоненты для загрузки файлов, работы с куки и авторизации.
- несложный layout. Шаблонизация: простая PHP и XSLT.
- виджет для отображения таблиц.

В составе так же есть микро-JS-фреймворк.

От первой версии отличеется наличием DIC, архитектура полностью переписана под это. Удалены "поведения" и магические вызовы методов. Все компоненты полностью независимы и ничего не знают друг о друге. Новая версия так же сконфигурирована под юнит тестирование на PHPUnit. Для его установки надо в корне запустить "composer install". Тесты располагаются в папке "/tests". 

Цель проекта: продемонстрировать навыки программирования. Весь PHP и JS код, за исключением дэйтпикера, написан лично мной.
