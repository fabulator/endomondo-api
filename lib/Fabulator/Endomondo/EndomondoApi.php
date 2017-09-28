<?php

namespace Fabulator\Endomondo;

use GuzzleHttp\Exception\ClientException;

/**
 * Class EndomondoAPI
 * @package Fabulator\Endomondo
 */
class EndomondoApi extends EndomondoAPIBase
{
    /**
     * @param string $username
     * @param string $password
     * @return array
     */
    public function login($username, $password)
    {
        $response = json_decode(parent::login($username, $password)->getBody(), true);
        $this->setUserId($response['id']);
        return $response;
    }

    /**
     * Generate csfr token.
     *
     * @return void
     * @throws EndomondoApiException When generating fail
     */
    protected function generateCSRFToken()
    {
        try {
            parent::generateCSRFToken();
        } catch (ClientException $e) {
            // too many request, sleep for a while
            if ($e->getCode() === 429) {
                throw new EndomondoApiException('Too many requests', $e->getCode(), $e);
            }

            throw new EndomondoApiException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * @param $method string http method
     * @param $endpoint string Endomondo endpoint
     * @param array $data
     * @throws EndomondoApiException when api failed
     * @return array;
     */
    public function request($method, $endpoint, $data = [])
    {
        try {
            $response = parent::send($method, $endpoint, $data);
        } catch (ClientException $e) {
            // too many request, sleep for a while
            if ($e->getCode() === 429) {
                throw new EndomondoApiException('Too many requests', $e->getCode(), $e);
            }
            throw new EndomondoApiException($e->getMessage(), $e->getCode(), $e);
        }
        return json_decode((string) $response->getBody(), true);
    }

    /**
     * Send a GET request
     *
     * @param string $endpoint
     * @param array $options
     * @return array
     */
    public function get($endpoint, $options = [])
    {
        return $this->request('GET', 'rest/v1/users/' . $this->userId . '/' . $endpoint . '?' . http_build_query($options));
    }

    /**
     * @param string $endpoint
     * @return array
     */
    public function delete($endpoint)
    {
        return $this->request('DELETE', 'rest/v1/users/' . $this->userId . '/' . $endpoint);
    }

    /**
     * @param string $endpoint
     * @param array $data
     * @return array
     */
    public function post($endpoint, $data)
    {
        return $this->request('POST', 'rest/v1/users/' . $this->userId . '/' . $endpoint, $data);
    }

    /**
     * @param string $endpoint
     * @param array $data
     * @return array
     */
    public function put($endpoint, $data)
    {
        return $this->request('PUT', 'rest/v1/users/' . $this->userId . '/' . $endpoint, $data);
    }

    /**
     * Get Endomondo Profile of logged user;
     *
     * @return array
     */
    public function getProfile()
    {
        return $this->get('');
    }

    /**
     * @param $id string
     * @return Workout
     */
    public function getWorkout($id)
    {
        return ApiParser::parseWorkout($this->get('workouts/' . $id));
    }

    /**
     * @param string $id Id of workout to delete
     * @return array
     */
    public function deleteWorkout($id)
    {
        return $this->delete('workouts/' . $id);
    }

    /**
     * @param array $filters array
     * @return Workout[]
     * @return array $options {
     *     @var int $total Total found workout
     *     @var string $next Url for next workouts
     *     @var Workout[] $workout List of workouts
     * }
     */
    public function getWorkouts($filters = [])
    {
        $data = [
            'workouts' => [],
        ];

        $filters = array_merge([
            'expand' => 'workout,points',
            'limit' => 1000,
        ], $filters);

        $response = $this->get('workouts/history', $filters);

        foreach ($response['data'] as $workout) {
            $data['workouts'][] = ApiParser::parseWorkout($workout);
        }

        $data['total'] = $response['paging']['total'];
        $data['next'] = $response['paging']['next'];

        return $data;
    }

    /**
     * @param \DateTime $from
     * @param int $limit number of workouts on page
     * @return array $options {
     *     @var int $total Total found workout
     *     @var string $next Url for next workouts
     *     @var Workout[] $workout List of workouts
     * }
     */
    public function getWorkoutsFrom(\DateTime $from, $limit = 10)
    {
        return $this->getWorkouts([
            'after' => $from->format('c'),
            'limit' => $limit,
        ]);
    }

    /**
     * @param \DateTime $until
     * @param int $limit number of workouts on page
     * @return array $options {
     *     @var int $total Total found workout
     *     @var string $next Url for next workouts
     *     @var Workout[] $workout List of workouts
     * }
     */
    public function getWorkoutsUntil(\DateTime $until, $limit = 10)
    {
        return $this->getWorkouts([
            'before' => $until->format('c'),
            'limit' => $limit,
        ]);
    }

    /**
     * Try to find a single workout between two dates.
     *
     * @param \DateTime $from
     * @param \DateTime $to
     * @return Workout|null
     */
    public function getWorkoutBetween(\DateTime $from, \DateTime $to)
    {
        $workouts = $this->getWorkoutsUntil($to, 1);

        /* @var $workout Workout */
        $workout = $workouts['workouts'][0];

        if (!$workout) {
            return null;
        }

        if ($workout->getStart() > $from) {
            return $workout;
        }

        return null;
    }

    /**
     * Get all workouts between two dates.
     *
     * @param \DateTime $from
     * @param \DateTime $to
     * @return array $options {
     *     @var int $total Total found workout
     *     @var string $next Url for next workouts
     *     @var Workout[] $workout List of workouts
     * }
     */
    public function getWorkoutsFromTo(\DateTime $from, \DateTime $to)
    {
        return $this->getWorkouts([
            'after' => $from->format('c'),
            'before' => $to->format('c'),
        ]);
    }

    /**
     * @param $name
     * @param string $workoutId
     * @return array
     */
    public function addHastag($name, $workoutId)
    {
        return $this->post('workouts/' . $workoutId . '/hashtags/' . $name, []);
    }

    /**
     * @param $name
     * @param string $workoutId
     * @return array
     */
    public function removeHashtag($name, $workoutId)
    {
        return $this->delete('workouts/' . $workoutId . '/hashtags/' . $name);
    }

    /**
     * @param Workout $workout
     * @return array
     */
    public function editWorkout(Workout $workout)
    {
        $data = [
            'duration' => $workout->getDuration(),
            'distance' => $workout->getDistance() ?: 0,
            'sport' => $workout->getTypeId(),
            'start_time' => $workout->getStart()->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d\TH:i:s.u\Z'),
            'update_calories' => true,
        ];

        if ($workout->getAvgHeartRate() !== null) {
            $data['heart_rate_avg'] = $workout->getAvgHeartRate();
        }

        if ($workout->getMaxHeartRate() !== null) {
            $data['heart_rate_max'] = $workout->getMaxHeartRate();
        }

        if ($workout->getNotes() !== null) {
            $data['notes'] = $workout->getNotes();
        }

        if ($workout->getTitle() !== null) {
            $data['title'] = $workout->getTitle();
        }

        if ($workout->getWorkoutPrivacy() !== null) {
            $data['show_workout'] = $workout->getWorkoutPrivacy();
        }

        if ($workout->getMapPrivacy() !== null) {
            $data['show_map'] = $workout->getMapPrivacy();
        }

        if ($workout->getAscent() !== null) {
            $data['ascent'] = $workout->getAscent();
        }

        if ($workout->getDescent() !== null) {
            $data['descent'] = $workout->getDescent();
        }

        return $this->put('workouts/' . $workout->getId(), $data);
    }

}