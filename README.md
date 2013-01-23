laravel-precedent
=================

***Auto Cache and Purge on Eloquent***

## Features

1. Auto cache on find method and purge on update, delete.
2. Validate via model.
2. Possible to sync data for fields in the pivote table.

## Installation

Install this bundle by running the following CLI command:

	php artisan bundle:install precedent

Add the following line to application/bundles.php

	'precedent' => array(
		'autoloads' => array(
			'map' => array(
				'Has_Many_And_Belongs_To' => '(:bundle)/has_many_and_belongs_to.php',
				'Precedent'               => '(:bundle)/model.php'
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

Auto Cache on find method (Work only static method).
```php
// Cache on find.
// This works.
User::find(10);

// This doesn't work.
$user = new User;
$user->find(10)
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

## Bonus 1

Validate via model

```php
class User extends Precedent {

	/**
	 * Cache enabled
	 *
	 * @type {Boolean}
	 */
	public static $cache = true;

	/**
	 * Validate rules
	 *
	 * @type array
	 */
	public static $rules = array(
		'email' => 'required|unique:users'
	);

	/**
	 * Validate messages
	 *
	 * @type array
	 */
	public static $messages = array(
		'email_required' => 'Please enter you email.',
		'email_email'    => 'Please enter valid email.'
	);

}
```

Your controller.

```php
$user = new User; // or User::find(1);

$data = array(
	'email' => Input::get('email')
);

if ( ! $user->valid($data))
{
	$errors = $user->errors;

	return Redirect::back()->with_errors($errors)->with_input();
}
```

## Bonus 2

Can add attributes at method 'sync' for has_many_and_belongs_to relationship.

```php
$user = User::find(1);
$user->roles()->sync(array(1, 3, 9), array('remark' => 'wow'));
```

## Support or Contact

If you have some problem, Contact teepluss@gmail.com