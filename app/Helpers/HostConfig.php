<?php

namespace App\Helpers;

/**
 * Helper class to handle multiple sites configurations identified by the request host name
 */
class HostConfig
{
    protected static $hostData = null;

    public static function get($key = null)
    {
        // Handle singleton loading to avoid multiple host search
        if (static::$hostData === null) {
            static::$hostData = self::searchHostData();
        }

        // If not key, return all data
        if (!$key) {
            return static::$hostData;
        }

        // Return specific key
        if (isset(static::$hostData[$key])) {
            return static::$hostData[$key];
        }

        // Return empty
        return null;
    }

    protected static function searchHostData()
    {
        // Validate host availability
        if (empty($_SERVER['HTTP_HOST'])) {
            return null;
        }

        // Get host
        $host = $_SERVER['HTTP_HOST'];

        // Search every index until host match
        $index = 0;
        while (true) {
            $hostKey  = "MDOCS_{$index}_HOST";
            $dirKey   = "MDOCS_{$index}_DIR";
            $nameKey  = "MDOCS_{$index}_NAME";
            $themeKey = "MDOCS_{$index}_THEME";
            $typeKey  = "MDOCS_{$index}_TYPE";
            $configuredHost = env($hostKey);

            // If not valid host index, stop searching
            if (!$configuredHost) {
                break;
            }

            // If host found return array data
            if ($configuredHost === $host) {
                return [
                    'dir'   => env($dirKey),
                    'name'  => env($nameKey),
                    'theme' => env($themeKey),
                    'type'  => env($typeKey),
                ];
            }

            $index++;
        }
    }
}
