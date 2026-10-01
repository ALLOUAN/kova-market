<?php

namespace App\Services\WhatsApp;

use InvalidArgumentException;

/**
 * A WhatsApp template message: one of the templates of config/whatsapp.php and the values of its {{1}}, {{2}}…
 */
final class WhatsAppMessage
{
    /**
     * @param  list<string>  $parameters
     */
    private function __construct(
        public readonly string $key,
        public readonly array $parameters,
    ) {}

    /**
     * @param  list<string|int>  $parameters
     */
    public static function template(string $key, array $parameters): self
    {
        $template = config("whatsapp.templates.{$key}") ?? throw new InvalidArgumentException("Modèle WhatsApp inconnu : {$key}");
        $expected = preg_match_all('/\{\{\d+\}\}/', $template['body']);

        if (count($parameters) !== $expected) {
            throw new InvalidArgumentException("Le modèle WhatsApp « {$key} » attend {$expected} valeurs, ".count($parameters).' données.');
        }

        // Meta refuses empty values and line breaks inside a parameter.
        return new self($key, array_map(fn ($value) => trim(preg_replace('/\s+/', ' ', (string) $value)) ?: '—', array_values($parameters)));
    }

    /** The template's name at Meta. */
    public function name(): string
    {
        return config("whatsapp.templates.{$this->key}.name");
    }

    public function hasCopyCodeButton(): bool
    {
        return (bool) config("whatsapp.templates.{$this->key}.copy_code_button", false);
    }

    /** The text as the person reads it (logs, tests). */
    public function text(): string
    {
        return preg_replace_callback('/\{\{(\d+)\}\}/', fn (array $match) => $this->parameters[(int) $match[1] - 1] ?? '', config("whatsapp.templates.{$this->key}.body"));
    }
}
