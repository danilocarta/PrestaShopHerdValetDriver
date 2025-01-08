<?php

namespace Valet\Drivers\Custom;

use Valet\Drivers\LaravelValetDriver;

class PrestaShopValetDriver extends LaravelValetDriver
{
    protected $rewriteRules = [];
    protected $htaccessPath;
    protected $adminFolder = null;
    protected $sitePath;

    /**
     * Determine if the driver serves the request.
     */
    public function serves($sitePath, $siteName, $uri): bool
    {
        $this->sitePath = $sitePath;

        // Find admin folder if exists
        $this->findAdminFolder($sitePath);

        // Parse root .htaccess
        $this->htaccessPath = $sitePath . '/.htaccess';
        if (file_exists($this->htaccessPath)) {
            $this->parseHtaccess($this->htaccessPath);
        }

        return file_exists($sitePath . '/classes/PrestashopAutoload.php');
    }

    /**
     * Find the admin folder by checking common PrestaShop admin folder patterns
     */
    protected function findAdminFolder($sitePath): void
    {
        $directories = glob($sitePath . '/admin*', GLOB_ONLYDIR);
        foreach ($directories as $dir) {
            if (file_exists($dir . '/index.php')) {
                $this->adminFolder = basename($dir);
                break;
            }
        }
    }

    /**
     * Get the fully resolved path to the application's front controller.
     */
    public function frontControllerPath($sitePath, $siteName, $uri): ?string
    {
        // Check if we're accessing the admin area
        if ($this->adminFolder) {
            $adminPrefix = '/' . $this->adminFolder;

            // Check if the URI starts with admin folder or if we have admin context in query
            $isAdminRequest = strpos($uri, $adminPrefix) === 0 ||
                (isset($_GET['controller']) && strpos($_GET['controller'], 'Admin') === 0);

            if ($isAdminRequest) {
                $adminPath = $sitePath . $adminPrefix . '/index.php';

                if (file_exists($adminPath)) {
                    // Set correct admin context
                    $_SERVER['SCRIPT_FILENAME'] = $adminPath;
                    $_SERVER['SCRIPT_NAME'] = $adminPrefix . '/index.php';
                    $_SERVER['PHP_SELF'] = $adminPrefix . '/index.php';

                    // Ensure REQUEST_URI maintains admin folder context
                    if (!strpos($_SERVER['REQUEST_URI'], $this->adminFolder)) {
                        $query = $_SERVER['QUERY_STRING'] ?? '';
                        $_SERVER['REQUEST_URI'] = $adminPrefix . '/index.php' . ($query ? '?' . $query : '');
                    }

                    // Force admin directory in controller URLs
                    if (isset($_GET['controller']) && strpos($_GET['controller'], 'Admin') === 0) {
                        if (!isset($_SERVER['SCRIPT_URL']) || strpos($_SERVER['SCRIPT_URL'], $this->adminFolder) === false) {
                            $_SERVER['SCRIPT_URL'] = $adminPrefix . '/index.php';
                        }
                    }

                    // Set additional context variables that PrestaShop might need
                    $_SERVER['REDIRECT_URL'] = $adminPrefix . '/index.php';
                    $_SERVER['REDIRECT_STATUS'] = '200';

                    // Define admin constants if not already defined
                    if (!defined('_PS_ADMIN_DIR_')) {
                        define('_PS_ADMIN_DIR_', $sitePath . $adminPrefix);
                    }
                    if (!defined('PS_ADMIN_DIR')) {
                        define('PS_ADMIN_DIR', _PS_ADMIN_DIR_);
                    }

                    return $adminPath;
                }
            }
        }

        // Default to front controller for non-admin requests
        $_SERVER['SCRIPT_FILENAME'] = $sitePath . '/index.php';
        $_SERVER['SCRIPT_NAME'] = '/index.php';
        $_SERVER['PHP_SELF'] = '/index.php';

        return $sitePath . '/index.php';
    }

    /**
     * Determine if the incoming request is for a static file.
     */
    public function isStaticFile($sitePath, $siteName, $uri)
    {
        // Check basic static file first
        if (is_file($staticFilePath = "{$sitePath}/{$uri}")) {
            return $staticFilePath;
        }

        // Check if we're in admin context
        if ($this->adminFolder && strpos($uri, $this->adminFolder) === 1) {
            // Remove admin folder from URI for static file checks
            $adminUri = substr($uri, strlen($this->adminFolder) + 1);
            if (is_file($adminFile = "{$sitePath}/{$this->adminFolder}/{$adminUri}")) {
                return $adminFile;
            }
        }

        // Apply rewrite rules from .htaccess
        $rewrittenPath = $this->applyRewriteRules($uri, $sitePath);
        if ($rewrittenPath) {
            return $rewrittenPath;
        }

        return false;
    }

    /**
     * Parse the .htaccess file and extract rewrite rules
     */
    protected function parseHtaccess(string $htaccessPath): void
    {
        $content = file_get_contents($htaccessPath);
        $lines = explode("\n", $content);

        $inRewriteBlock = false;
        foreach ($lines as $line) {
            $line = trim($line);

            // Skip comments and empty lines
            if (empty($line) || strpos($line, '#') === 0) {
                continue;
            }

            // Check for RewriteEngine On
            if (strpos($line, 'RewriteEngine On') === 0) {
                $inRewriteBlock = true;
                continue;
            }

            // Parse RewriteRule
            if ($inRewriteBlock && strpos($line, 'RewriteRule') === 0) {
                $rule = $this->parseRewriteRule($line);
                if ($rule) {
                    $this->rewriteRules[] = $rule;
                }
            }
        }
    }

    /**
     * Parse a single RewriteRule line
     */
    protected function parseRewriteRule(string $line): ?array
    {
        // Remove RewriteRule keyword
        $line = trim(substr($line, 11));

        // Split into pattern, substitution, and flags
        preg_match('/^\s*(\S+)\s+(\S+)(?:\s+\[([^\]]+)\])?\s*$/', $line, $matches);

        if (count($matches) < 3) {
            return null;
        }

        return [
            'pattern' => $this->convertApacheRegexToPhp($matches[1]),
            'substitution' => $matches[2],
            'flags' => isset($matches[3]) ? $this->parseFlags($matches[3]) : []
        ];
    }

    // Helper methods from previous version remain the same
    protected function convertApacheRegexToPhp(string $pattern): string
    {
        if ($pattern[0] === '^') {
            $pattern = substr($pattern, 1);
        }

        $pattern = str_replace(
            ['%{ENV:REWRITEBASE}', '.'],
            ['', '\.'],
            $pattern
        );

        return '#^' . $pattern . '#i';
    }

    protected function parseFlags(string $flags): array
    {
        $flagsArray = explode(',', $flags);
        return array_map('trim', $flagsArray);
    }

    protected function applyRewriteRules(string $uri, string $sitePath): ?string
    {
        foreach ($this->rewriteRules as $rule) {
            if (preg_match($rule['pattern'], $uri, $matches)) {
                $substitution = $rule['substitution'];
                for ($i = 1; $i < count($matches); $i++) {
                    $substitution = str_replace('$' . $i, $matches[$i], $substitution);
                }

                $substitution = preg_replace('/%\{[^\}]+\}/', '', $substitution);

                $staticFilePath = $sitePath . '/' . ltrim($substitution, '/');
                if (file_exists($staticFilePath)) {
                    return $staticFilePath;
                }
            }
        }

        return null;
    }
}