<?php

declare(strict_types=1);

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

final readonly class Base64ImageRule implements ValidationRule
{
    public const MAX_DECODED_BYTES = 2097152;

    private const DATA_URI_PATTERN = '/^data:image\/(?:png|jpe?g);base64,/i';

    /**
     * @var array<int, string>
     */
    private const ALLOWED_MIME_TYPES = ['image/png', 'image/jpeg'];

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_string($value)) {
            $fail('The :attribute must be a base64 encoded string.');

            return;
        }

        $encoded = preg_replace(self::DATA_URI_PATTERN, '', $value);
        $decoded = base64_decode((string) $encoded, true);

        if ($decoded === false || $decoded === '') {
            $fail('The :attribute must be a valid base64 encoded image.');

            return;
        }

        if (strlen($decoded) > self::MAX_DECODED_BYTES) {
            $fail('The :attribute must not be larger than 2 MB.');

            return;
        }

        $mimeType = (new \finfo(FILEINFO_MIME_TYPE))->buffer($decoded);

        if (!in_array($mimeType, self::ALLOWED_MIME_TYPES, true) || getimagesizefromstring($decoded) === false) {
            $fail('The :attribute must be a jpg or png image.');
        }
    }
}
