# Mixpanel PHP Library [![Build Status](https://travis-ci.org/mixpanel/mixpanel-php.svg)](https://travis-ci.org/github/diagvn/mixpanel-php)

This library provides an API to track events and update profiles on Mixpanel.

## Install with Composer

Add diagvn/mixpanel-php as a dependency and run composer update

```json
"require": {
    ...
    "diagvn/mixpanel-php" : "1.*"
    ...
}
```

Now you can start tracking events and people:

```php
<?php
// import dependencies
use MixPanel\Services\MixPanelService;
// get the MixPanelService class instance, replace with your project token
$mp = new MixPanelService();
$mp->setToken("MIXPANEL_PROJECT_TOKEN");

// track an event
$mp->track("button clicked", array("label" => "sign-up"));

// create/update a profile for user id 12345
$mp->people->set(12345, array(
    '$first_name'       => "John",
    '$last_name'        => "Doe",
    '$email'            => "john.doe@example.com",
    '$phone'            => "5555555555",
    "Favorite Color"    => "red"
));
```

## Install Manually

1. <a href="https://github.com/DiagVN/mixpanel/archive/refs/heads/master.zip">Download the Mixpanel PHP Library</a>
2. Extract the zip file to a directory called "mixpanel-php" in your project root
3. Now you can start tracking events and people:

```php
<?php
// import dependencies
use MixPanel\Services\MixPanelService;
// get the MixPanelService class instance, replace with your project token
$mp = new MixPanelService();
$mp->setToken("MIXPANEL_PROJECT_TOKEN");

// track an event
$mp->track("button clicked", array("label" => "sign-up"));

// create/update a profile for user id 12345
$mp->people->set(12345, array(
    '$first_name'       => "John",
    '$last_name'        => "Doe",
    '$email'            => "john.doe@example.com",
    '$phone'            => "5555555555",
    "Favorite Color"    => "red"
));
```

## Production Notes

By default, data is sent using ssl over cURL. This works fine when you're tracking a small number of events or aren't concerned with the potentially blocking nature of the PHP cURL calls. However, this isn't very efficient when you're sending hundreds of events (such as in batch processing). Our library comes packaged with an easy way to use a persistent socket connection for much more efficient writes. To enable the persistent socket, simply pass `'consumer' => 'socket'` as an entry in the `$options` array when you instantiate the Mixpanel class. Additionally, you can contribute your own persistence implementation by creating a custom Consumer.

## Documentation

- <a href="https://mixpanel.com/help/reference/php" target="_blank">Reference Docs</a>
- <a href="http://mixpanel.github.io/mixpanel-php" target="_blank">Full API Reference</a>

For further examples and options checkout out the "examples" folder

## Changelog

Version 1.4.2

- Init project
