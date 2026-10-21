<?php

namespace App\Support;

class CountryCode
{
    public static function resolve(?string $code, ?string $country): ?string
    {
        $code = strtoupper(trim((string) $code));
        if (preg_match('/^[A-Z]{2}$/', $code)) {
            return $code;
        }

        $country = self::normalize((string) $country);
        if ($country === '') {
            return null;
        }

        return self::countryNames()[$country] ?? null;
    }

    private static function countryNames(): array
    {
        static $countryNames;
        if ($countryNames !== null) {
            return $countryNames;
        }

        $countryNames = [
            'pakistan' => 'PK',
            'india' => 'IN',
            'ivorycoast' => 'CI',
            'unitedstates' => 'US',
            'unitedstatesofamerica' => 'US',
            'usa' => 'US',
            'unitedkingdom' => 'GB',
            'uk' => 'GB',
            'greatbritain' => 'GB',
            'uae' => 'AE',
            'southkorea' => 'KR',
            'northkorea' => 'KP',
            'russia' => 'RU',
            'turkey' => 'TR',
            'vietnam' => 'VN',
            'iran' => 'IR',
            'syria' => 'SY',
            'bolivia' => 'BO',
            'tanzania' => 'TZ',
            'venezuela' => 'VE',
            'moldova' => 'MD',
            'brunei' => 'BN',
            'laos' => 'LA',
            'taiwan' => 'TW',
            'palestine' => 'PS',
        ];

        if (!class_exists(\Locale::class) || !class_exists(\ResourceBundle::class)) {
            return $countryNames;
        }

        foreach (\ResourceBundle::getLocales('') as $locale) {
            if (!preg_match('/^en_([A-Z]{2})$/', $locale, $matches)) {
                continue;
            }

            $name = \Locale::getDisplayRegion($locale, 'en');
            $normalizedName = self::normalize($name);
            if ($normalizedName !== '') {
                $countryNames[$normalizedName] = $matches[1];
            }
        }

        return $countryNames;
    }

    private static function normalize(string $country): string
    {
        if (function_exists('iconv')) {
            $country = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $country) ?: $country;
        }

        return strtolower((string) preg_replace('/[^a-z0-9]/i', '', $country));
    }
}
