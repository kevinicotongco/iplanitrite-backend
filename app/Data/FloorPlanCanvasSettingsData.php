<?php

declare(strict_types=1);

namespace App\Data;

final readonly class FloorPlanCanvasSettingsData
{
    public const DEFAULT_WIDTH = 1200;
    public const DEFAULT_HEIGHT = 800;
    public const DEFAULT_ZOOM = 1;
    public const DEFAULT_GRID_SIZE = 20;
    public const DEFAULT_BACKGROUND_COLOR = '#f8fafc';

    public function __construct(
        public int|float $width,
        public int|float $height,
        public int|float $zoom,
        public int|float $gridSize,
        public bool $snapToGrid,
        public string $backgroundColor,
    ) {}

    public static function default(): self
    {
        return new self(
            width: self::DEFAULT_WIDTH,
            height: self::DEFAULT_HEIGHT,
            zoom: self::DEFAULT_ZOOM,
            gridSize: self::DEFAULT_GRID_SIZE,
            snapToGrid: true,
            backgroundColor: self::DEFAULT_BACKGROUND_COLOR,
        );
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            width: $data['width'],
            height: $data['height'],
            zoom: $data['zoom'],
            gridSize: $data['gridSize'],
            snapToGrid: $data['snapToGrid'],
            backgroundColor: $data['backgroundColor'],
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'width' => $this->width,
            'height' => $this->height,
            'zoom' => $this->zoom,
            'gridSize' => $this->gridSize,
            'snapToGrid' => $this->snapToGrid,
            'backgroundColor' => $this->backgroundColor,
        ];
    }
}
