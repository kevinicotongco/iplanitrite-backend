<?php

declare(strict_types=1);

namespace Tests\Concerns;

use App\Rules\Base64ImageRule;

trait CreatesBase64Images
{
    protected function base64Png(): string
    {
        return $this->encodeImage('imagepng');
    }

    protected function base64Jpeg(): string
    {
        return $this->encodeImage('imagejpeg');
    }

    protected function base64Gif(): string
    {
        return $this->encodeImage('imagegif');
    }

    protected function base64Text(): string
    {
        return base64_encode('this is not an image');
    }

    protected function oversizedBase64(): string
    {
        return base64_encode(str_repeat('a', Base64ImageRule::MAX_DECODED_BYTES + 1));
    }

    /**
     * @return array<string, array{0: string}>
     */
    protected function invalidAvatarPayloads(): array
    {
        return [
            'not base64' => ['%%% not base64 %%%'],
            'plain text encoded' => [$this->base64Text()],
            'gif image' => [$this->base64Gif()],
            'too large' => [$this->oversizedBase64()],
            'wrong data uri type' => ['data:image/gif;base64,' . $this->base64Png()],
        ];
    }

    private function encodeImage(callable $encoder): string
    {
        $image = imagecreatetruecolor(8, 8);

        ob_start();
        $encoder($image);
        $bytes = (string) ob_get_clean();

        return base64_encode($bytes);
    }
}
