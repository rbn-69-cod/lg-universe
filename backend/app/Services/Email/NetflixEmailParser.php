<?php

namespace App\Services\Email;

class NetflixEmailParser
{
    public function parse(string $subject, string $html, string $plainText): array
    {
        $decodedHtml = $this->decodeBody($html);
        $decodedText = $this->decodeBody($plainText);
        $searchableText = $this->htmlToText($decodedHtml."\n".$decodedText);
        $normalizedSubject = $this->normalize($subject);
        $normalizedText = $this->normalize($searchableText);
        $durationMinutes = $this->extractDurationMinutes($searchableText);

        if ($this->isHouseholdUpdate($normalizedSubject, $normalizedText)) {
            return [
                'platform' => 'Netflix',
                'type' => 'household_update',
                'code' => null,
                'action_url' => $this->extractActionUrl($decodedHtml, $searchableText, 'si la envie yo'),
                'duration_minutes' => $durationMinutes,
            ];
        }

        if ($this->isTemporaryAccess($normalizedSubject, $normalizedText)) {
            return [
                'platform' => 'Netflix',
                'type' => 'temporary_access',
                'code' => null,
                'action_url' => $this->extractActionUrl($decodedHtml, $searchableText, 'obtener codigo'),
                'duration_minutes' => $durationMinutes,
            ];
        }

        if ($this->isLoginCode($normalizedSubject, $normalizedText)) {
            return [
                'platform' => 'Netflix',
                'type' => 'login_code',
                'code' => $this->extractLoginCode($searchableText),
                'action_url' => null,
                'duration_minutes' => $durationMinutes,
            ];
        }

        return [
            'platform' => 'Netflix',
            'type' => 'unknown',
            'code' => null,
            'action_url' => null,
            'duration_minutes' => $durationMinutes,
        ];
    }

    public function extractLinks(string $html, string $plainText): array
    {
        $links = [];

        foreach ($this->extractAnchors($this->decodeBody($html)) as $anchor) {
            $href = trim((string) ($anchor['href'] ?? ''));
            if ($href !== '') {
                $links[] = $href;
            }
        }

        if (preg_match_all('/https?:\/\/[^\s"\'<>]+/iu', $this->decodeBody($plainText), $matches)) {
            foreach ($matches[0] ?? [] as $url) {
                $cleanUrl = html_entity_decode(trim((string) $url), ENT_QUOTES | ENT_HTML5, 'UTF-8');
                if ($cleanUrl !== '') {
                    $links[] = $cleanUrl;
                }
            }
        }

        return array_values(array_unique($links));
    }

    private function isHouseholdUpdate(string $subject, string $text): bool
    {
        $haystack = $subject.' '.$text;

        return str_contains($haystack, 'solicitaste actualizar tu hogar con netflix')
            || str_contains($haystack, 'actualizar tu hogar con netflix')
            || str_contains($haystack, 'hogar con netflix');
    }

    private function isTemporaryAccess(string $subject, string $text): bool
    {
        $haystack = $subject.' '.$text;

        return str_contains($haystack, 'tu codigo de acceso temporal')
            || str_contains($haystack, 'codigo de acceso temporal')
            || str_contains($haystack, 'acceso temporal');
    }

    private function isLoginCode(string $subject, string $text): bool
    {
        $haystack = $subject.' '.$text;

        return str_contains($haystack, 'ingresa este codigo para iniciar sesion')
            || str_contains($haystack, 'codigo para iniciar sesion')
            || str_contains($haystack, 'codigo de inicio de sesion')
            || str_contains($haystack, 'iniciar sesion en netflix');
    }

    private function extractLoginCode(string $text): ?string
    {
        $normalizedText = $this->normalize($text);
        $patterns = [
            '/ingresa\s+este\s+codigo\s+para\s+iniciar\s+sesion[\s:\-]*([0-9]{4})/i',
            '/codigo\s+para\s+iniciar\s+sesion[\s:\-]*([0-9]{4})/i',
            '/codigo\s+de\s+inicio\s+de\s+sesion[\s:\-]*([0-9]{4})/i',
            '/ingresa\s+este\s+codigo[^\d]{0,40}\b([0-9]{4})\b/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $normalizedText, $matches)) {
                return $matches[1];
            }
        }

        $lines = preg_split('/\R+/', $text) ?: [];
        foreach ($lines as $index => $line) {
            if (preg_match('/^\s*([0-9]{4})\s*$/', trim($line), $matches)) {
                $context = $this->normalize(($lines[$index - 1] ?? '').' '.($lines[$index - 2] ?? '').' '.($lines[$index + 1] ?? ''));
                if (
                    str_contains($context, 'iniciar sesion')
                    || str_contains($context, 'inicio de sesion')
                    || str_contains($context, 'ingresa este codigo')
                ) {
                    return $matches[1];
                }
            }
        }

        return null;
    }

    private function extractDurationMinutes(string $text): ?int
    {
        if (preg_match('/vence\s+en\s+(\d+)\s+minutos?/iu', $text, $matches)) {
            return (int) $matches[1];
        }

        if (preg_match('/enlace\s+vence\s+en\s+(\d+)\s+minutos?/iu', $text, $matches)) {
            return (int) $matches[1];
        }

        return null;
    }

    private function extractActionUrl(string $html, string $plainText, string $buttonLabel): ?string
    {
        $expectedLabel = $this->normalize($buttonLabel);
        $anchors = $this->extractAnchors($html);

        foreach ($anchors as $anchor) {
            if ($this->isMatchingButtonText((string) $anchor['text'], $expectedLabel)) {
                return $anchor['href'];
            }
        }

        return $this->singleFallbackLink($html, $plainText);
    }

    private function extractAnchors(string $html): array
    {
        if (trim($html) === '') {
            return [];
        }

        $anchors = [];

        if (preg_match_all('/<a\b[^>]*href=(["\'])(.*?)\1[^>]*>(.*?)<\/a>/is', $html, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $href = trim((string) ($match[2] ?? ''));
                $text = trim(strip_tags(html_entity_decode((string) ($match[3] ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8')));

                if ($text === '' || $href === '') {
                    continue;
                }

                $anchors[] = [
                    'text' => $text,
                    'href' => html_entity_decode($href, ENT_QUOTES | ENT_HTML5, 'UTF-8'),
                ];
            }
        }

        return $anchors;
    }

    private function isMatchingButtonText(string $anchorText, string $expectedLabel): bool
    {
        $normalizedAnchor = $this->normalize($anchorText);

        if ($normalizedAnchor === '') {
            return false;
        }

        return $normalizedAnchor === $expectedLabel
            || str_contains($normalizedAnchor, $expectedLabel)
            || str_contains($expectedLabel, $normalizedAnchor);
    }

    private function singleFallbackLink(string $html, string $plainText): ?string
    {
        $links = $this->extractLinks($html, $plainText);

        return count($links) === 1 ? $links[0] : null;
    }

    private function htmlToText(string $html): string
    {
        $html = preg_replace('/<\s*br\s*\/?>/i', "\n", $html) ?? $html;
        $html = preg_replace('/<\/(p|div|td|tr|h\d|li)>/i', "\n", $html) ?? $html;
        $text = strip_tags($html);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/[ \t]+/', ' ', $text) ?? $text;
        $text = preg_replace('/\n{2,}/', "\n", $text) ?? $text;

        return trim((string) $text);
    }

    private function decodeBody(string $value): string
    {
        if (! mb_check_encoding($value, 'UTF-8')) {
            $value = @mb_convert_encoding($value, 'UTF-8', 'ISO-8859-1,Windows-1252,UTF-8') ?: $value;
        }

        return html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    private function normalize(string $value): string
    {
        $value = mb_strtolower($value, 'UTF-8');
        $value = str_replace(
            ['á', 'é', 'í', 'ó', 'ú', 'ñ', 'Á', 'É', 'Í', 'Ó', 'Ú', 'Ñ', 'Ã¡', 'Ã©', 'Ã­', 'Ã³', 'Ãº', 'Ã±'],
            ['a', 'e', 'i', 'o', 'u', 'n', 'a', 'e', 'i', 'o', 'u', 'n', 'a', 'e', 'i', 'o', 'u', 'n'],
            $value
        );
        $value = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value) ?: $value;
        $value = preg_replace('/[^a-z0-9]+/', ' ', $value) ?? $value;

        return trim((string) preg_replace('/\s+/', ' ', $value));
    }
}
