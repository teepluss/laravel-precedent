laravel-precedent
=================

***Auto Cache and Purge on Eloquent***

## Installation

Install this bundle by running the following CLI command:

	php artisan bundle:install precedent
	
Add the following line to application/bundles.php

	'precedent' => array(
		'autoloads' => array(
			'map' => array(
				'Precedent' => '(:bundle)/model.php'
			),
		)
	),
	
## Example Usage 

Enable config profile to debug.
	
	'profiler' => true
	

Enable cache using attribute $cache in model.
```php
class User extends Precedent {
	
	public static $cache = true;

}
```

Auto Cache on find method.
```php
// Cache on find.
User::find(10);
```

Auto Purge on delete and update method.
```php
$user = User::find(10);

// Purge cache on delete.
$user->delete();

// and 

// Purge cache on update.
$user = User::find(10);
$user->fill(array(
	'name' => 'Tee'
));

$user->save();
```

## Support or Contact

If you have some problem, Contact teepluss@gmail.com 