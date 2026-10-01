<?php

namespace App\Support;

/**
 * Writes keys into the .env file, replacing the existing lines or adding them at the end (setup commands).
 */
class EnvFile
{
    /**
     * @param  array<string, string>  $values
     */
    public static function put(array $values): void
    {
        $path = app()->environmentFilePath();
        $content = file_exists($path) ? file_get_contents($path) : '';
        $eol = str_contains($content, "\r\n") ? "\r\n" : "\n";

        foreach ($values as $key => $value) {
            $line = $key.'='.(preg_match('/[\s#"]/', $value) ? '"'.addcslashes($value, '"\\').'"' : $value);
            $content = preg_match("/^{$key}=.*$/m", $content)
                ? preg_replace_callback("/^{$key}=[^\r\n]*/m", fn () => $line, $content)
                : rtrim($content, "\r\n").$eol.$line.$eol;
        }

        file_put_contents($path, $content);
    }
}
