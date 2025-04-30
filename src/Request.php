<?php

namespace tachyon;

class Request
{
    private array $parameters = [];

    public function __construct()
    {
        $this->set('get', $_GET);
        $this->set('post', $_POST);
        $this->set('files', $_FILES);
    }

    public function set(string $name, $val): void
    {
        if (is_null($val)) {
            return;
        }
        if ($name !== 'files') {
            $val = $this->filter($val);
        }
        $this->parameters[$name] = $val;
    }

    public function add(string $name, $val): void
    {
        if ($name !== 'files') {
            $val = $this->filter($val);
        }
        $this->parameters[$name] = array_merge($this->parameters[$name], $val);
    }

    public function get(string $name): string | array | null
    {
        return $this->parameters[$name] ?? null;
    }

    /**
     * Remember the page from which we are redirecting
     */
    public function setReferer(): void
    {
        setcookie('referer', $_SERVER['REQUEST_URI'], 0, '/');
    }

    /**
     * Get page from which we are redirecting
     */
    public function getReferer(): string
    {
        return $_COOKIE['referer'] ?? '/';
    }

    /**
     * Shortcut for query
     */
    public function getQuery(string $queryType = null): ?array
    {
        if (is_null($queryType)) {
            $queryType = 'get';
        }
        return $this->parameters[$queryType] ?? null;
    }

    /**
     * Shortcut for $_GET
     */
    public function getGet(string $key = null): string | array | null
    {
        if (!is_null($key)) {
            return $this->parameters['get'][$key] ?? null;
        }
        return $this->parameters['get'] ?? null;
    }

    /**
     * Shortcut for $_POST
     */
    public function getPost(string $key = null): string | array | null
    {
        if (!is_null($key)) {
            return $this->parameters['post'][$key] ?? null;
        }
        return $this->parameters['post'] ?? null;
    }

    public function getRoute(): string
    {
        return htmlspecialchars($_SERVER['REQUEST_URI']);
    }

    public function isPost(): bool
    {
        return $_SERVER['REQUEST_METHOD'] === 'POST';
    }

    /**
     * Protect against XSS and SQL injection
     */
    private function filter(mixed $data): string | array | null
    {
        if (is_string($data)) {
            return htmlentities($data);
        }
        if (is_array($data)) {
            foreach ($data as &$datum) {
                $datum = $this->filter($datum);
            }
            return array_filter($data);
        }
    }

    /**
     * Parses a query string
     */
    public function parseUri(): string
    {
        $path = parse_url($_SERVER['REQUEST_URI'])['path'];
        if ($path !== '/') {
            $path = trim($path, '/');
        }
        $this->set('path', $path);

        return $path;
    }
}
