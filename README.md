Endomondo API
============

This unofficial Endomondo API, which they are using in web app, allows you to read informations about workouts and update them. Creating new workouts is not possible with this API and you have to use endomondo-api-old project.

### Authorize
Authorization is simple, just log in with Endomondo login and password. For every post or put request, you have to have csfr token. It is generated automatic by the library.

```php
$endomondo = new \Fabulator\Endomondo\EndomondoApi();
$endomondo->login(ENDOMONDO_LOGIN, ENDOMONDO_PASSWORD);

$workout = $endomondo->getWorkout('775131509');
echo $workout->toString();
```

### Editing workouts

```php
$workout = $endomondo->getWorkout('775131509');

$workout
    ->setDistance(5.3)
    ->setNotes('My beautiful note');

$endomondo->editWorkout($workout);
```

### Creating workouts
You have to use [fabulator/endomondo-api-old](https://github.com/fabulator/endomondo-api-old) package.
