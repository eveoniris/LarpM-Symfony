<?php

declare(strict_types=1);

namespace App\Service;

use Imagine\Gd\Imagine;
use Imagine\Image\Box;

class ImageOptimizer
{
    private const MAX_WIDTH = 200;
    private const MAX_HEIGHT = 150;

    private Imagine $imagine;
    private string $projectDirectory;

    public function __construct(string $projectDirectory)
    {
        $this->imagine = new Imagine();
        $this->projectDirectory = $projectDirectory;
    }

    public function getImagine(string $projectDirectory): Imagine
    {
        return $this->imagine;
    }

    public function resize(string $filename): void
    {
        $size = getimagesize($filename);
        if (false === $size) {
            return;
        }
        [$iwidth, $iheight] = $size;
        $ratio = $iwidth / $iheight;
        $width = self::MAX_WIDTH;
        $height = self::MAX_HEIGHT;
        if (($width / $height) > $ratio) {
            $width = $height * $ratio;
        } else {
            $height = $width / $ratio;
        }

        $photo = $this->imagine->open($filename);
        $photo->resize(new Box((int) $width, (int) $height))->save($filename);
    }

    public function getProjectDirectory(): string
    {
        return $this->projectDirectory;
    }
}
