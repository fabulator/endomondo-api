<?php

namespace Fabulator\Endomondo;

class EndomondoAPI extends EndomondoAPIBase
{
    /**
     * @param string $username
     * @param string $password
     * @return array
     */
    public function login($username, $password)
    {
        $response = $this->request('POST', 'rest/session', [
            'email' => $username,
            'password' => $password,
            'remember' => true,
        ]);

        $this->userId = $response['id'];

        return $response;
    }

    /**
     * @param $method string http method
     * @param $endpoint string Endomondo endpoint
     * @param array $data
     * @return array;
     */
    public function request($method, $endpoint, $data = [])
    {
        $response = parent::request($method, $endpoint, $data);
        return json_decode((string) $response->getBody(), true);
    }

    /**
     * @param $id string
     * @return Workout
     */
    public function getWorkout($id)
    {
        return ApiParser::parseWorkout($this->get('rest/v1/users/' . $this->userId . '/workouts/' . $id));
    }

    /**
     * @param $id
     * @return array
     */
    public function deleteWorkout($id)
    {
        return $this->delete('rest/v1/users/' . $this->userId . '/workouts/' . $id);
    }

    /**
     * @param array $filters array
     * @return array<Workout>
     */
    public function getWorkouts($filters = [])
    {
        $data = [
            'workouts' => [],
        ];

        $filters = array_merge([
            'expand' => 'workout',
        ], $filters);

        $response = $this->get('rest/v1/users/' . $this->userId . '/workouts/history?' . http_build_query($filters));

        foreach ($response['data'] as $workout) {
            $data['workouts'][] = ApiParser::parseWorkout($workout);
        }

        $data['total'] = $response['paging']['total'];
        $data['next'] = $response['paging']['next'];

        return $data;
    }

    /**
     * @param \DateTime $from
     * @return array
     */
    public function getWorkoutsFrom(\DateTime $from)
    {
        return $this->getWorkouts([
            'after' => $from->format('c')
        ]);
    }

    /**
     * @param \DateTime $until
     * @return array
     */
    public function getWorkoutsUntil(\DateTime $until)
    {
        return $this->getWorkouts([
            'before' => $until->format('c')
        ]);
    }

    /**
     * @param \DateTime $from
     * @param \DateTime $to
     * @return array
     */
    public function getWorkoutsFromTo(\DateTime $from, \DateTime $to)
    {
        return $this->getWorkouts([
            'after' => $from->format('c'),
            'before' => $to->format('c')
        ]);
    }

    /**
     * @param $name
     * @param Workout $workout
     * @return array
     */
    public function addHastag($name, Workout $workout)
    {
        return $this->post('rest/v1/users/' . $this->userId . '/workouts/' . $workout->getId() . '/hashtags/' . $name, []);
    }

    /**
     * @param $name
     * @param Workout $workout
     * @return array
     */
    public function removeHashtag($name, Workout $workout)
    {
        return $this->delete('rest/v1/users/' . $this->userId . '/workouts/' . $workout->getId() . '/hashtags/' . $name);
    }

    /**
     * @param Workout $workout
     * @return array
     */
    public function editWorkout(Workout $workout)
    {
        $data = [
            'duration' => $workout->getDuration(),
            'distance' => $workout->getDistance(),
            'sport' => $workout->getTypeId(),
            'start_time' => $workout->getStart()->format('Y-m-d\TH:i:s.u\Z'),
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

        return $this->put('rest/v1/users/' . $this->userId . '/workouts/' . $workout->getId(), $data);
    }

}