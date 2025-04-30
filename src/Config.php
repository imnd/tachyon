<?php
namespace tachyon;

/**
 * @author imndsu@gmail.com
 */
class Config
{
    public const APP_DIR = '/../../../';

    private array $options;

    public function __construct(string $mode = null)
    {
        $basePath = dirname(str_replace('\\', '/', realpath(__DIR__)));
        // Constant options
        $this->options = [
            'base_path' => $basePath,
            // the path to the routes file
            'routes' => require($basePath . self::APP_DIR . 'app/config/routes.php'),
            'mode' => $GLOBALS['APP_MODE'] ?? $mode ?? 'work'
        ];
        // Environment options
        $this->loadEnv($basePath);
    }

    private function loadEnv($basePath): void
    {
        // read .env file
        $envFileName = '.env' . ($this->options['mode'] === 'test' ? '-test' : '');
        if (!file_exists($envFilePath = $basePath . self::APP_DIR . $envFileName)) {
            return;
        }
        $envFile = file($envFilePath);
        foreach ($envFile as $string) {
            if ("\n" === $string || "\r\n" === $string) {
                continue;
            }
            $arr = explode(':', $string);
            $key = strtolower( trim($arr[0]) );
            if (str_starts_with($key, '#')) {
                continue;
            }
            $val = trim($arr[1]);
            if (false !== $point = strpos($key, '.')) {
                $key0 = substr($key, 0, $point);
                if (!isset($this->options[$key0])) {
                    $this->options[$key0] = array();
                }
                $key1 = substr($key, $point + 1);
                $this->options[$key0][$key1] = $val;
            } else {
                $this->options[$key] = $val;
            }
        }
    }

    /**
     * extract value by key
     */
    public function get(string $key): mixed
    {
        return $this->options[$key] ?? null;
    }
}
