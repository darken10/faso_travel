<?php

namespace App\Enums;

/**
 * Type d'un paramètre de compagnie.
 *
 * Porte la logique de conversion entre la valeur stockée (chaîne en base)
 * et la valeur métier typée, ainsi que la règle de validation de base.
 */
enum CompagnieSettingType: string
{
    case Boolean = 'bool';
    case Integer = 'int';
    case Float = 'float';
    case String = 'string';
    case Text = 'text';
    case Select = 'select';
    case MultiSelect = 'multiselect';
    case Time = 'time';
    case Color = 'color';
    case Json = 'json';

    /** Convertit la valeur brute stockée en base vers sa valeur métier typée. */
    public function cast(?string $raw): mixed
    {
        if ($raw === null) {
            return null;
        }

        return match ($this) {
            self::Boolean     => filter_var($raw, FILTER_VALIDATE_BOOLEAN),
            self::Integer     => (int) $raw,
            self::Float       => (float) $raw,
            self::MultiSelect,
            self::Json        => json_decode($raw, true) ?? [],
            default           => $raw,
        };
    }

    /** Convertit une valeur métier en chaîne stockable. */
    public function serialize(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return match ($this) {
            self::Boolean     => $value ? '1' : '0',
            self::Integer     => (string) (int) $value,
            self::Float       => (string) (float) $value,
            self::MultiSelect,
            self::Json        => json_encode(array_values((array) $value), JSON_UNESCAPED_UNICODE),
            default           => (string) $value,
        };
    }

    /**
     * Règles de validation de base, complétées par celles de la définition.
     *
     * @return array<int, string>
     */
    public function baseRules(): array
    {
        return match ($this) {
            self::Boolean     => ['boolean'],
            self::Integer     => ['integer'],
            self::Float       => ['numeric'],
            self::String      => ['string', 'max:255'],
            self::Text        => ['string', 'max:5000'],
            self::Select      => ['string'],
            self::MultiSelect => ['array'],
            self::Time        => ['date_format:H:i'],
            self::Color       => ['string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            self::Json        => ['array'],
        };
    }

    /** Le paramètre est-il multi-valeurs (stocké en JSON) ? */
    public function isMultiple(): bool
    {
        return in_array($this, [self::MultiSelect, self::Json], true);
    }
}
