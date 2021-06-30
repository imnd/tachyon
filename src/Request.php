<?php

namespace tachyon;

class Request
{
    /**
     * @var array
     */
    private array $parameters = [];

    /**
     * @param string $name
     * @param mixed  $val
     *
     * @return void
     */
    public function set(string $name, $val): void
    {
        if (is_null($val)) {
            return;
        }
        if ($name !== 'files') {
            $val = $this->_filter($val);
        }
        $this->parameters[$name] = $val;
    }

    /**
     * @param string $name
     * @param        $val
     *
     * @return void
     */
    public function add(string $name, $val): void
    {
        if ($name !== 'files') {
            $val = $this->_filter($val);
        }
        $this->parameters[$name] = array_merge($this->parameters[$name], $val);
    }

    /**
     * @param string $name
     *
     * @return mixed
     */
    public function get(string $name)
    {
        return $this->parameters[$name] ?? null;
    }

    /**
     * Страница, с которой редиректились
     *
     * @return string
     */
    public function getReferer(): string
    {
        return $_COOKIE['referer'] ?? '/';
    }

    /**
     * Запоминаем страницу, с которой редиректимся
     *
     * @return void
     */
    public function setReferer(): void
    {
        setcookie('referer', $_SERVER['REQUEST_URI'], 0, '/');
    }

    /**
     * Шорткат
     *
     * @param string|null $queryType string
     *
     * @return array|null
     */
    public function getQuery(string $queryType = null): ?array
    {
        if (is_null($queryType)) {
            $queryType = 'get';
        }
        return $this->parameters[$queryType] ?? null;
    }

    /**
     * Шорткат для $_GET
     *
     * @param string|null $key
     *
     * @return mixed
     */
    public function getGet(string $key = null)
    {
        if (!is_null($key)) {
            return $this->parameters['get'][$key] ?? null;
        }
        return $this->parameters['get'] ?? null;
    }

    /**
     * Шорткат для $_POST
     *
     * @param string|null $key
     *
     * @return mixed
     */
    public function getPost(string $key = null)
    {
        if (!is_null($key)) {
            return $this->parameters['post'][$key] ?? null;
        }
        return $this->parameters['post'] ?? null;
    }

    /**
     * @return string
     */
    public function getRoute(): string
    {
        return htmlspecialchars($_SERVER['REQUEST_URI']);
    }

    /**
     * Разбирает строку запроса
     *
     * @return string
     */
    public function parseUri(): string
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
        $this->set('path', $path);

        return $path;
    }

    /**
     * @return boolean
     */
    public function isPost(): bool
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
    private function _filter($data)
    {
        if (is_string($data)) {
            return htmlentities($data);
        }
        if (is_array($data)) {
            foreach ($data as &$val) {
                $val = $this->_filter($val);
            }
            return array_filter($data);
        }
    }

    /**
     * @return void
     */
    public function boot(): void
    {
    }
}
