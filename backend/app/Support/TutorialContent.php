<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class TutorialContent
{
    public const KEYS = [
        'hogar' => 'Code hogar',
        'temporal' => 'Code temporal',
        'whatsapp' => 'WhatsApp',
        'nombre' => 'Nombre de perfil',
        'pin' => 'PIN',
        'acceso4' => 'Inicio sesion 4 digitos',
        'general' => 'General',
    ];

    private const JSON_PATH = 'tutorials.json';

    public static function all(): array
    {
        $stored = [];

        if (Storage::disk('local')->exists(self::JSON_PATH)) {
            $stored = json_decode(Storage::disk('local')->get(self::JSON_PATH), true) ?: [];
        }

        $tutorials = [];

        foreach (self::KEYS as $key => $label) {
            $tutorials[$key] = array_replace_recursive([
                'key' => $key,
                'title' => $label,
                'steps' => [],
                'media_url' => null,
                'media_type' => null,
                'media_path' => null,
                'updated_at' => null,
            ], $stored[$key] ?? []);
        }

        return $tutorials;
    }

    public static function public(): array
    {
        return collect(self::all())
            ->map(fn (array $tutorial) => [
                'title' => $tutorial['title'],
                'steps' => array_values(array_filter($tutorial['steps'] ?? [])),
                'media_url' => ! empty($tutorial['media_path'])
                    ? url('tutorial-media/'.$tutorial['media_path'])
                    : ($tutorial['media_url'] ?? null),
                'media_type' => $tutorial['media_type'] ?? null,
            ])
            ->all();
    }

    public static function save(string $key, string $title, array $steps, ?UploadedFile $file): void
    {
        $tutorials = self::all();
        $current = $tutorials[$key];

        $current['title'] = trim($title) ?: (self::KEYS[$key] ?? $key);
        $current['steps'] = array_values(array_filter(array_map('trim', $steps)));
        $current['updated_at'] = now()->toDateTimeString();

        if ($file) {
            if (! empty($current['media_path'])) {
                Storage::disk('public')->delete($current['media_path']);
            }

            $path = $file->storeAs(
                'tutorials',
                $key.'-'.Str::random(10).'.'.$file->getClientOriginalExtension(),
                'public'
            );

            $current['media_path'] = $path;
            $current['media_url'] = url('tutorial-media/'.$path);
            $current['media_type'] = str_starts_with((string) $file->getMimeType(), 'video/') ? 'video' : 'image';
        }

        $tutorials[$key] = $current;

        self::write($tutorials);
    }

    public static function removeMedia(string $key): void
    {
        $tutorials = self::all();

        if (! empty($tutorials[$key]['media_path'])) {
            Storage::disk('public')->delete($tutorials[$key]['media_path']);
        }

        $tutorials[$key]['media_path'] = null;
        $tutorials[$key]['media_url'] = null;
        $tutorials[$key]['media_type'] = null;
        $tutorials[$key]['updated_at'] = now()->toDateTimeString();

        self::write($tutorials);
    }

    private static function write(array $tutorials): void
    {
        Storage::disk('local')->put(self::JSON_PATH, json_encode($tutorials, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }
}
