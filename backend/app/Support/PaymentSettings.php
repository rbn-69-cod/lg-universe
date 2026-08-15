<?php

namespace App\Support;

use App\Models\AppSetting;

class PaymentSettings
{
    public const KEY = 'payment_settings';

    public static function get(): array
    {
        $stored = AppSetting::query()->where('key', self::KEY)->value('value');

        return self::normalize(is_array($stored) ? $stored : []);
    }

    public static function save(array $value): array
    {
        $normalized = self::normalize($value);

        AppSetting::query()->updateOrCreate(
            ['key' => self::KEY],
            ['value' => $normalized]
        );

        return $normalized;
    }

    public static function normalize(array $value): array
    {
        $defaults = self::defaults();
        $settings = array_replace_recursive($defaults, $value);

        $settings['instructions'] = trim((string) ($settings['instructions'] ?? $defaults['instructions']));
        $settings['seller'] = self::seller($settings['seller'] ?? []);
        $settings['methods'] = array_values(array_map(
            fn (array $method, int $index) => self::method($method, $defaults['methods'][$index] ?? [], $index + 1),
            array_slice(is_array($settings['methods'] ?? null) ? $settings['methods'] : [], 0, 4),
            array_keys(array_slice(is_array($settings['methods'] ?? null) ? $settings['methods'] : [], 0, 4))
        ));

        if ($settings['methods'] === []) {
            $settings['methods'] = $defaults['methods'];
        }

        return $settings;
    }

    public static function defaults(): array
    {
        return [
            'seller' => [
                'business_name' => 'IG UNIVERSE',
                'display_name' => 'IG UNIVERSE',
                'contact_name' => 'Igarlos R Mamani Q',
                'whatsapp' => '51954850003',
                'phone' => '954850003',
                'email' => '',
                'address' => '',
                'support_text' => 'Finaliza tu compra y envia el comprobante.',
            ],
            'instructions' => '1) Escanea QR o transfiere al numero - 2) Paga el monto exacto - 3) Envia el comprobante por WhatsApp - 4) Te activamos rapido.',
            'methods' => [
                [
                    'id' => 1,
                    'title' => 'Yape / Plin',
                    'subtitle' => 'Opcion 1',
                    'badge' => 'recomendado',
                    'recommended' => true,
                    'qr_src' => '/images/qr-yape.jpeg',
                    'qr_fallback' => 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=Yape:954850003&color=4A6FFF&bgcolor=ffffff',
                    'account_name' => 'Igarlos R Mamani Q',
                    'account_phone' => '954850003',
                    'copy_phone' => '907978279',
                    'whatsapp' => '51954850003',
                    'active' => true,
                ],
                [
                    'id' => 2,
                    'title' => 'Yape / Plin',
                    'subtitle' => 'Opcion 2',
                    'badge' => '',
                    'recommended' => false,
                    'qr_src' => '/images/qr-yape-2.jpeg',
                    'qr_fallback' => 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=Yape:968238516&color=4A6FFF&bgcolor=ffffff',
                    'account_name' => 'Jennifer N Gallegos Q',
                    'account_phone' => '968238516',
                    'copy_phone' => '968238516',
                    'whatsapp' => '51968238516',
                    'active' => true,
                ],
            ],
        ];
    }

    private static function seller(array $seller): array
    {
        $defaults = self::defaults()['seller'];

        return [
            'business_name' => trim((string) ($seller['business_name'] ?? $defaults['business_name'])),
            'display_name' => trim((string) ($seller['display_name'] ?? $defaults['display_name'])),
            'contact_name' => trim((string) ($seller['contact_name'] ?? $defaults['contact_name'])),
            'whatsapp' => preg_replace('/\D+/', '', (string) ($seller['whatsapp'] ?? $defaults['whatsapp'])),
            'phone' => trim((string) ($seller['phone'] ?? $defaults['phone'])),
            'email' => trim((string) ($seller['email'] ?? $defaults['email'])),
            'address' => trim((string) ($seller['address'] ?? $defaults['address'])),
            'support_text' => trim((string) ($seller['support_text'] ?? $defaults['support_text'])),
        ];
    }

    private static function method(array $method, array $fallback, int $id): array
    {
        $fallback = $fallback ?: self::defaults()['methods'][0];

        return [
            'id' => (int) ($method['id'] ?? $fallback['id'] ?? $id),
            'title' => trim((string) ($method['title'] ?? $fallback['title'] ?? 'Yape / Plin')),
            'subtitle' => trim((string) ($method['subtitle'] ?? $fallback['subtitle'] ?? 'Opcion '.$id)),
            'badge' => trim((string) ($method['badge'] ?? $fallback['badge'] ?? '')),
            'recommended' => (bool) ($method['recommended'] ?? $fallback['recommended'] ?? false),
            'qr_src' => trim((string) ($method['qr_src'] ?? $fallback['qr_src'] ?? '')),
            'qr_fallback' => trim((string) ($method['qr_fallback'] ?? $fallback['qr_fallback'] ?? '')),
            'account_name' => trim((string) ($method['account_name'] ?? $fallback['account_name'] ?? '')),
            'account_phone' => trim((string) ($method['account_phone'] ?? $fallback['account_phone'] ?? '')),
            'copy_phone' => trim((string) ($method['copy_phone'] ?? $fallback['copy_phone'] ?? '')),
            'whatsapp' => preg_replace('/\D+/', '', (string) ($method['whatsapp'] ?? $fallback['whatsapp'] ?? '')),
            'active' => (bool) ($method['active'] ?? $fallback['active'] ?? true),
        ];
    }
}
