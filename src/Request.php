<?php

namespace tachyon;

class Request
{
    /**
     * @var array
     */
    private static array $parameters = [];

    /**
     * @param string $name
     * @param mixed  $val
     *
     * @return void
     */
    public static function set(string $name, $val): void
    {
        if (is_null($val)) {
            return;
        }
        if ($name !== 'files') {
            $val = self::_filter($val);
        }
        self::$parameters[$name] = $val;
    }

    /**
     * @param string $name
     * @param        $val
     *
     * @return void
     */
    public static function add(string $name, $val): void
    {
        if ($name !== 'files') {
            $val = self::_filter($val);
        }
        self::$parameters[$name] = array_merge(self::$parameters[$name], $val);
    }

    /**
     * @param string $name
     *
     * @return mixed
     */
    public static function get(string $name)
    {
        return self::$parameters[$name] ?? null;
    }

    /**
     * Страница, с которой редиректились
     *
     * @return string
     */
    public static function getReferer(): string
    {
        return $_COOKIE['referer'] ?? '/';
    }

    /**
     * Запоминаем страницу, с которой редиректимся
     *
     * @return void
     */
    public static function setReferer(): void
    {
        setcookie('referer', $_SERVER['REQUEST_URI'], 0, '/');
    }

    /**
     * Шорткат
     *
     * @param $queryType string
     *
     * @return array
     */
    public static function getQuery(string $queryType = null): array
    {
        if (is_null($queryType)) {
            $queryType = 'get';
        }
        return self::$parameters[$queryType];
    }

    /**
     * Шорткат для $_GET
     *
     * @param string|null $key
     *
     * @return mixed
     */
    public static function getGet(string $key = null)
    {
        if (!is_null($key)) {
            return self::$parameters['get'][$key] ?? null;
        }
        return self::$parameters['get'] ?? null;
    }

    /**
     * Шорткат для $_POST
     *
     * @param string|null $key
     *
     * @return mixed
     */
    public static function getPost(string $key = null)
    {
        if (!is_null($key)) {
            return self::$parameters['post'][$key] ?? null;
        }
        return self::$parameters['post'];
    }

    /**
     * @return string
     */
    public static function getRoute(): string
    {
        return htmlspecialchars($_SERVER['REQUEST_URI']);
    }

    /**
     * Разбирает строку запроса
     *
     * @return string
     */
    public static function parseUri(): string
    {
        $uri = $_SERVER['REQUEST_URI'];
        $path = parse_url($uri)['path'];
        if ($path !== '/') {
            if (strpos($path, '/') === 0) {
                $path = substr($path, 1 - strlen($path));
            }
            if (substr($path, -1) === '/') {
                $path = substr($path, 0, -1);
            }
        }
        self::set('path', $path);

        return $path;
    }

    /**
     * @return boolean
     */
    public static function isPost(): bool
    {
        return $_SERVER['REQUEST_METHOD'] === 'POST';
    }

    /**
     * Защита от XSS и SQL injection
     *
     * @param mixed $data
     *
     * @return mixed
     */
    private static function _filter($data)
    {
        if (is_string($data)) {
            return htmlentities($data);
        }
        if (is_array($data)) {
            foreach ($data as &$val) {
                $val = self::_filter($val);
            }
            return array_filter($data);
        }
    }

    /**
     * @return void
     */
    public static function boot(): void
    {
    }
}
