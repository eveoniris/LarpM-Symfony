<?php

declare(strict_types=1);

namespace App\Service;

class GeoJson
{
    private float $maxDistance;

    public function __construct(float $maxDistance = 1.0)
    {
        $this->maxDistance = $maxDistance;
    }

    /**
     * @param array<string, mixed> $geoJson1
     * @param array<string, mixed> $geoJson2
     *
     * @return array<int, array<string, mixed>>
     */
    public function comparePoints(array $geoJson1, array $geoJson2): array
    {
        $matchingPoints = [];

        foreach ($this->extractPoints($geoJson1) as $point1) {
            foreach ($this->extractPoints($geoJson2) as $point2) {
                if ($this->calculateDistance($point1, $point2) > $this->maxDistance) {
                    continue;
                }

                $matchingPoints[] = [
                    'point1' => $point1,
                    'point2' => $point2,
                    'distance' => $this->calculateDistance($point1, $point2),
                ];
            }
        }

        return $matchingPoints;
    }

    /**
     * @param array<string, mixed> $geoJson
     *
     * @return array<int, array<int, float>>
     */
    private function extractPoints(array $geoJson): array
    {
        $points = [];
        array_walk_recursive($geoJson, static function ($value, $key) use (&$points): void {
            if (is_numeric($value)) {
                static $temp = [];
                $temp[] = $value;
                if (2 === \count($temp)) {
                    $points[] = $temp;
                    $temp = [];
                }
            }
        });

        return $points;
    }

    /**
     * @param array<int, float> $point1
     * @param array<int, float> $point2
     */
    private function calculateDistance(array $point1, array $point2): float
    {
        // Formule de Haversine pour calculer la distance entre deux points géographiques
        $earthRadius = 6371; // Rayon de la Terre en kilomètres

        $lat1 = deg2rad($point1[1]);
        $lon1 = deg2rad($point1[0]);
        $lat2 = deg2rad($point2[1]);
        $lon2 = deg2rad($point2[0]);

        $deltaLat = $lat2 - $lat1;
        $deltaLon = $lon2 - $lon1;

        $a = (sin($deltaLat / 2) * sin($deltaLat / 2)) + (cos($lat1) * cos($lat2) * sin($deltaLon / 2) * sin($deltaLon / 2));

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    public function getMaxDistance(): float
    {
        return $this->maxDistance;
    }

    public function setMaxDistance(float $maxDistance): static
    {
        $this->maxDistance = $maxDistance;

        return $this;
    }
}
