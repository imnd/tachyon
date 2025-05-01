# tachyon

**Tiny MVC php framework**

Features:
- Dependency Injection Container. At initial realisation it used setter methods and xml configuration files, now I changed it for using __constructor() methods and json configuration files. Also, it uses controllers actions variables. You can embed dependencies both through class names and through interface names. In this case, the correspondence of interfaces must be specified in file app/config/implementations.php.
- Front Controller with a simple (file system) routing;
- PDO based DBAL. It works on both MySql and PgSql. Instantiation of the corresponding class occurs using the Factory Method pattern;
- ORMs: 
    - Active Record with foreign keys support and classes that implement relationships between tables. Active Record interacts with the database using the Bridge pattern via a DBAL. Related records can be loaded by "greedy" (by one request) or "lazy" way;
    - Data Mapper with Unit of work. 
- 2 types of simplest (file system) caching for the DB and http requests;
- protection against XSS, CSRF assaults and SQL injections;
- very simple i18n;
- migrations;
- PHPUnit tests;
- view layout: 
    - plain PHP template with inheritance (4 template instructions: @include, @contents, @extends and {{ }});
    - XSLT template.

Components:
- form generator, which picks up the model validation rules and turns them to JS (instead of AJAX-validation for speed`s sake). Usual server-side validation also exists. It depends on the "scenario" of model using;
- html tag helper;
- grid widget;
- model validator;
- files-uploading;
- work with cookies;
- authentication;
- XSLT template system;
- flash messages;
- component for publishing js and css resource files.
- global helper shortcut functions for simplifying access to singletons.

The code written in compliance with the PSR-12 standard.

