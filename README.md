# tachyon

Tiny MVC php framework.

Features:
- Dependency Injection Container. It uses setter methods and xml configuration files;
- Front Controller with a simple (file system) routing;
- PDO based DBAL. It works on both MySql and PgSql. Instantiation of the corresponding class occurs using the Factory Method pattern;
- ORM 
    - Active Record with foreign keys support and special kind of models for sub-queries, which interacts with the database using the Bridge pattern via a DBAL. Related records can be loaded by "greedy" (by one request) or "lazy" way;
    - Data Mapper with Unit of work. 
- 2 types of simplest (file system) caching for DB and http requests;
- protection against XSS, CSRF assaults and SQL injections;
- very simple i18n;
- view layout: 
    - plain PHP template with inheritance (3 template instructions: @include, @contents, @extends);
    - XSLT template.

Components:
- form generator, which picks up the model validation rules and turns them to JS (instead of AJAX-validation for speed sake). Usual server-side validation also exists. It depends on the "scenario" of model using;
- html tag helper;
- grid widget;
- model validator;
- files-uploading;
- work with cookies;
- authentication;
- tiny JS framework;
- XSLT template system;
- component for publishing js and css resource files
- flash messages.

The code was written in compliance with the PSR-2 standard.
All PHP and JS code, except datepicker is written by me.


MVC микро-PHP-фреймворк.

Это Front Controller с простым (физическим) роутингом.
Компоненты ядра:
- Dependency Injection Container, который реализован с использованием сеттеров и файлов конфигурации xml;
- DBAL на основе PDO. DBAL работает как на MySql так и на PgSql. Инстанциирование соответствующего класса DBAL происходит с помощью паттерна Factory Method.;
- 2 типа ORM, которые взаимодействует с БД через DBAL используя паттерн Bridge: 
  - Active Record с поддержкой внешних ключей + особый вид моделей для подзапросов. Связанные записи могут загружаться как одним запросом в основной записью так и по требованию.
  - Data Mapper, использующий DAO Entity Repository и шаблон Unit of work.
- 2 вида кэширования в простейшем виде для ДБ и http запросов. Для этого используется файловая система;
- компонент для публикации файлов ресурсов, js и css;
- защита от XSS, CSRF атак и SQL инъекций;
- многоязычность;
- несложный layout 2-х видов:
    - PHP шаблонизация c возможностью наследования шаблонов (поддерживаются инструкции: @include, @contents, @extends);
    - XSLT шаблонизация;

Вспомогательные компоненты:
- генератор форм, который подхватывает правила валидации из модели и превращает в JS (вместо AJAX-валидации, для скорости). Обычная валидация на основе правил валидации из модели так же имеется. Она зависит от "сценария" использования модели;
- компоненты для загрузки файлов, работы с куки и авторизации;
- виджет для отображения таблиц из массива моделей;
- отображение флэш-сообщений.

В составе так же есть микро-JS-фреймворк.

От первой версии отличеется наличием Dependency Injection Container, архитектура полностью переписана под это. Удалены "поведения" и магические методы. Все компоненты полностью независимы.

Код написан с соблюдением стандарта PSR-2.
Весь PHP и JS код, за исключением дэйтпикера, написан лично мной.

На этом фреймворке работает реальная бухгалтерия  (https://github.com/imnd/bookkeep).

