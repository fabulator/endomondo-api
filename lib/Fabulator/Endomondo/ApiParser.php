<?php

namespace Fabulator\Endomondo;

final class ApiParser {

    /**
     * @param $source array
     * @return Point
     */
    static function parsePoint($source)
    {
        $point = new Point;

        if (isset($source['time'])) {
            $point->setTime(new \DateTime($source['time']));
        }

        if (isset($source['latitude'])) {
            $point->setLatitude($source['latitude']);
        }

        if (isset($source['longitude'])) {
            $point->setLongitude($source['longitude']);
        }

        if (isset($source['altitude'])) {
            $point->setAltitude($source['altitude']);
        }

        if (isset($source['distance'])) {
            $point->setDistance($source['distance']);
        }

        if (isset($source['speed'])) {
            $point->setSpeed($source['speed']);
        }

        if (isset($source['duration'])) {
            $point->setDuration($source['duration']);
        }

        if (isset($source['sensor_data']['heart_rate'])) {
            $point->setHeartRate($source['sensor_data']['heart_rate']);
        }

        if (isset($source['instruction'])) {
            $point->setInstruction($source['instruction']);
        }

        return $point;
    }

    /**
     * @param $source array
     * @return Workout
     */
    static function parseWorkout($source)
    {
        $workout = new Workout();

        $workout
            ->setSource($source)
            ->setTypeId($source['sport'])
            ->setDuration($source['duration'])
            ->setStart(new \DateTime($source['start_time']))
            ->setDistance($source['distance'])
            ->setMapPrivacy($source['show_map'])
            ->setWorkoutPrivacy($source['show_workout'])
            ->setHastags($source['hashtags'])
            ->setId($source['id'])
            ->setCalories($source['calories']);

        if ($source['notes']) {
            $workout->setNotes($source['notes']);
        }

        if ($source['title']) {
            $workout->setTitle($source['title']);
        }

        if ($source['points'] && isset($source['points']['points'])) {
            $points = [];
            foreach ($source['points']['points'] as $point) {
                $points[] = ApiParser::parsePoint($point);
            }
            $workout->setPoints($points);
        }

        if (isset($source['heart_rate_avg'])) {
            $workout->setAvgHeartRate($source['heart_rate_avg']);
        }

        if (isset($source['heart_rate_max'])) {
            $workout->setMaxHeartRate($source['heart_rate_max']);
        }

        if (isset($source['ascent'])) {
            $workout->setAscent($source['ascent']);
        }

        if (isset($source['descent'])) {
            $workout->setDescent($source['descent']);
        }

        return $workout;
    }
}