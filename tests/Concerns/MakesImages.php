<?php

namespace Tests\Concerns;

trait MakesImages
{
    /** Bytes of a real JPEG of the given size (GD), for upload and conversion tests. */
    protected function jpegBytes(int $width, int $height): string
    {
        $image = imagecreatetruecolor($width, $height);
        imagefilledrectangle($image, 0, 0, $width, $height, imagecolorallocate($image, 180, 30, 40));

        ob_start();
        imagejpeg($image, null, 85);
        $bytes = (string) ob_get_clean();
        imagedestroy($image);

        return $bytes;
    }
}
