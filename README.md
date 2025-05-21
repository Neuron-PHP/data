[![Build Status](https://app.travis-ci.com/Neuron-PHP/data.svg?token=F8zCwpT7x7Res7J2N4vF&branch=master)](https://app.travis-ci.com/Neuron-PHP/data)
# Neuron-PHP Data

## Overview

## Installation

Install php composer from https://getcomposer.org/

Install the neuron data component:

    composer require neuron-php/data

## Filtering

Wrappers for filter_input.

* Cookie
* Get
* Post
* Server
* Session

All filters use the following interface:
```php
interface IFilter
{
	public static function filterScalar( $Data ): mixed;
	public static function filterArray( array $Data ): array|false|null;
}
```


## Data Objects

* DateRange
* GpsPoint
* NumericRange
* Version

The Version data object is designed to work with the [Bump](https://github.com/ljonesfl/bump)
command line utility to reference version information from .version.json

## String

The string class supports the BASIC string manipulation
commands such as left, right, mid and trim.
Also, quote, dequote, toCamelCase and toSnakeCase.

## ArrayHelper

# More Information

You can read more about the Neuron components at [neuronphp.com](http://neuronphp.com)
