[![CI](https://github.com/Neuron-PHP/data/actions/workflows/ci.yml/badge.svg)](https://github.com/Neuron-PHP/data/actions)
[![codecov](https://codecov.io/gh/Neuron-PHP/data/branch/develop/graph/badge.svg)](https://codecov.io/gh/Neuron-PHP/data)
# Neuron-PHP Data

A comprehensive data handling and utility component for PHP 8.4+ that provides essential data manipulation, filtering, parsing, and configuration management tools for the Neuron framework.

## Table of Contents

- [Installation](#installation)
- [Core Features](#core-features)
- [Input Filtering](#input-filtering)
- [Settings Management](#settings-management)
- [Environment Variables](#environment-variables)
- [Data Objects](#data-objects)
- [Parsers](#parsers)
- [Unit Conversion](#unit-conversion)
- [Testing](#testing)
- [More Information](#more-information)

## Installation

### Requirements

- PHP 8.4 or higher
- Extensions: curl, json, calendar
- Composer

### Install via Composer

```bash
composer require neuron-php/data
```

## Core Features

The Data component provides:

- **Input Filtering**: Secure wrappers for PHP's filter_input functions
- **Settings Management**: Unified configuration from multiple sources (YAML, INI, ENV)
- **Environment Variables**: .env file loading and management
- **Data Objects**: Specialized objects for common data structures
- **Parsers**: CSV, positional, and name parsing utilities
- **Array Utilities**: Advanced array manipulation functions
- **Unit Conversion**: Common unit conversions
- **Date Utilities**: Date range and manipulation tools

## Input Filtering

Secure, type-safe wrappers for PHP's filter_input functions with a consistent interface.

### Available Filters

- `Cookie` - Access $_COOKIE values
- `Get` - Access $_GET values
- `Post` - Access $_POST values
- `Server` - Access $_SERVER values
- `Session` - Access $_SESSION values

### Filter Interface

All filters implement the `IFilter` interface:

```php
interface IFilter
{
    public static function filterScalar($Data): mixed;
    public static function filterArray(array $Data): array|false|null;
}
```

### Usage Examples

```php
use Neuron\Data\Filter\Get;
use Neuron\Data\Filter\Post;
use Neuron\Data\Filter\Cookie;

// Get scalar values
$page = Get::filterScalar('page');        // From $_GET['page']
$username = Post::filterScalar('username'); // From $_POST['username']
$session = Cookie::filterScalar('session_id'); // From $_COOKIE['session_id']

// Get array values
$filters = Get::filterArray('filters');   // From $_GET['filters'][]
$items = Post::filterArray('items');      // From $_POST['items'][]

// With validation
$email = Post::filterScalar('email');
if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
    // Valid email
}
```

## Settings Management

The `SettingManager` provides a unified interface for configuration from multiple sources with fallback support.

### Supported Sources

- **YAML** - YAML configuration files
- **INI** - Traditional INI files
- **ENV** - Environment variables
- **Memory** - In-memory configuration

### Basic Usage

```php
use Neuron\Data\Settings\SettingManager;
use Neuron\Data\Settings\Source\Yaml;
use Neuron\Data\Settings\Source\Env;

// Create primary source (YAML file)
$yamlSource = new Yaml('/path/to/neuron.yaml');
$settings = new SettingManager($yamlSource);

// Add fallback to environment variables
$envFallback = new Env();
$settings->setFallback($envFallback);

// Get settings (checks YAML first, then ENV)
$dbHost = $settings->get('database', 'host');
$apiKey = $settings->get('api', 'key');

// Set values
$settings->set('cache', 'enabled', 'true');

// Get all section names
$sections = $settings->getSectionNames();

// Get all settings in a section
$dbSettings = $settings->getSectionSettingNames('database');
```

### YAML Configuration Example

```yaml
# neuron.yaml
database:
  host: localhost
  port: 3306
  name: myapp
  username: root
  password: secret

cache:
  enabled: true
  driver: redis
  ttl: 3600

api:
  endpoint: https://api.example.com
  key: your-api-key
  timeout: 30
```

### INI Configuration Example

```ini
; config.ini
[database]
host = localhost
port = 3306
name = myapp

[cache]
enabled = true
driver = file
ttl = 3600
```

### Memory Source

```php
use Neuron\Data\Settings\Source\Memory;

$memory = new Memory();
$memory->set('app', 'name', 'My Application');
$memory->set('app', 'version', '1.0.0');

$settings = new SettingManager($memory);
```

## Environment Variables

The `Env` class provides secure .env file loading and environment variable management using a singleton pattern.

### Basic Usage

```php
use Neuron\Data\Env;

// Get singleton instance (auto-loads .env from document root)
$env = Env::getInstance();

// Get environment variables
$dbHost = $env->get('DB_HOST');
$dbPort = $env->get('DB_PORT');
$debug = $env->get('DEBUG');

// Load custom .env file
$env = Env::getInstance('/path/to/custom/.env');

// Set environment variables programmatically
$env->put('RUNTIME_CONFIG=dynamic_value');

// Check if variable exists
if ($env->get('API_KEY')) {
    // API key is set
}
```

### .env File Format

```bash
# Database Configuration
DB_HOST=localhost
DB_PORT=3306
DB_NAME=myapp
DB_USER=root
DB_PASSWORD=secret

# Application Settings
APP_ENV=development
APP_DEBUG=true
APP_URL=http://localhost:8000

# API Configuration
API_KEY=your-secret-key-here
API_TIMEOUT=30

# Comments are supported
# DISABLED_SETTING=value
```

## Data Objects

Specialized objects for common data structures with built-in validation and manipulation.

### Version

Works with semantic versioning and integrates with the [Bump](https://github.com/ljonesfl/bump) utility.

```php
use Neuron\Data\Objects\Version;

$version = new Version();

// Load from .version.json file
$version->loadFromFile('.version.json');

// Set version components
$version->setMajor(2);
$version->setMinor(1);
$version->setPatch(5);
$version->setPreRelease('beta');
$version->setBuildNumber('123');

// Get formatted version
echo $version->getAsString();  // "2.1.5-beta+123"

// Increment version
$version->incrementPatch();    // 2.1.6
$version->incrementMinor();    // 2.2.0
$version->incrementMajor();    // 3.0.0

// Save to file
$version->saveToFile('.version.json');
```

### DateRange

Manage date ranges with validation and comparison.

```php
use Neuron\Data\Objects\DateRange;

$range = new DateRange(
    new DateTime('2024-01-01'),
    new DateTime('2024-12-31')
);

// Check if date is in range
$date = new DateTime('2024-06-15');
if ($range->contains($date)) {
    // Date is within range
}

// Get duration
$days = $range->getDays();        // Number of days
$months = $range->getMonths();    // Number of months

// Format range
echo $range->format('Y-m-d');     // "2024-01-01 to 2024-12-31"
```

### NumericRange

Handle numeric ranges with validation.

```php
use Neuron\Data\Objects\NumericRange;

$range = new NumericRange(10, 100);

// Check if value is in range
if ($range->contains(50)) {
    // Value is within range
}

// Get range properties
$min = $range->getMin();          // 10
$max = $range->getMax();          // 100
$size = $range->getSize();        // 90

// Validate range
if ($range->isValid()) {
    // Min is less than max
}
```

### GpsPoint

Geographic coordinate handling with distance calculations.

```php
use Neuron\Data\Objects\GpsPoint;

// Create GPS points
$point1 = new GpsPoint(40.7128, -74.0060);  // New York
$point2 = new GpsPoint(51.5074, -0.1278);   // London

// Calculate distance
$distance = $point1->distanceTo($point2);   // Distance in kilometers

// Get coordinates
$lat = $point1->getLatitude();
$lon = $point1->getLongitude();

// Validate coordinates
if ($point1->isValid()) {
    // Coordinates are within valid ranges
}

// Format for display
echo $point1->toString();  // "40.7128, -74.0060"
```

## Parsers

Data parsing utilities for various formats and structures.

### CSV Parser

Parse CSV data with custom delimiters and headers.

```php
use Neuron\Data\Parsers\CSV;

$csv = new CSV();

// Parse CSV string
$data = $csv->parse("name,age,city\nJohn,30,NYC\nJane,25,LA");

// Parse with custom delimiter
$csv->setDelimiter(';');
$data = $csv->parse("name;age;city\nJohn;30;NYC");

// Parse without headers
$csv->setHasHeaders(false);
$rows = $csv->parse("John,30,NYC\nJane,25,LA");

// Parse from file
$data = $csv->parseFile('/path/to/data.csv');
```

### Positional Parser

Parse fixed-width positional data.

```php
use Neuron\Data\Parsers\Positional;

$parser = new Positional([
    'name' => [0, 10],   // Position 0-10
    'age' => [10, 3],    // Position 10-13
    'city' => [13, 10]   // Position 13-23
]);

$data = $parser->parse("John Doe  30 New York  ");
// Result: ['name' => 'John Doe', 'age' => '30', 'city' => 'New York']
```

### Name Parsers

Parse names in various formats.

```php
use Neuron\Data\Parsers\FirstMI;
use Neuron\Data\Parsers\LastFirstMI;

// Parse "First MI Last" format
$parser = new FirstMI();
$name = $parser->parse("John A. Smith");
// Result: ['first' => 'John', 'middle' => 'A', 'last' => 'Smith']

// Parse "Last, First MI" format
$parser = new LastFirstMI();
$name = $parser->parse("Smith, John A.");
// Result: ['first' => 'John', 'middle' => 'A', 'last' => 'Smith']
```

## Unit Conversion

Common unit conversion utilities for measurements.

```php
use Neuron\Data\UnitConversion;

// Volume conversions
$ml = UnitConversion::usFlOuncesToMilliliters(16);     // ~473.18 ml
$oz = UnitConversion::millilitersToUsFlOunces(500);    // ~16.91 oz

// Weight conversions
$kg = UnitConversion::poundsToKilograms(150);          // ~68.04 kg
$lbs = UnitConversion::kilogramsToPounds(75);          // ~165.35 lbs

// Temperature conversions (if available)
$celsius = UnitConversion::fahrenheitToCelsius(98.6);   // 37°C
$fahrenheit = UnitConversion::celsiusToFahrenheit(20);  // 68°F

// Distance conversions
$km = UnitConversion::milesToKilometers(100);          // ~160.93 km
$miles = UnitConversion::kilometersToMiles(42.195);    // ~26.22 miles
```

## Testing

Run the test suite:

```bash
# Run all tests
vendor/bin/phpunit tests

# Run with coverage
vendor/bin/phpunit tests --coverage-html coverage

# Run specific test file
vendor/bin/phpunit tests/Data/EnvTest.php
```

### Test Structure

```
tests/
├── Data/
│   ├── ArrayHelperTest.php
│   ├── DateTest.php
│   ├── EnvTest.php
│   ├── UnitConversionTest.php
│   ├── Filter/
│   │   ├── GetTest.php
│   │   ├── PostTest.php
│   │   └── ...
│   ├── Object/
│   │   ├── VersionTest.php
│   │   ├── DateRangeTest.php
│   │   └── ...
│   ├── Parser/
│   │   ├── CSVTest.php
│   │   └── ...
│   └── Setting/
│       ├── SettingManagerTest.php
│       └── Source/
│           ├── YamlTest.php
│           └── ...
├── bootstrap.php
└── phpunit.xml
```

## Best Practices

### Configuration Management

```php
// Use fallback chain for flexible configuration
$settings = new SettingManager(new Yaml('neuron.yaml'));
$settings->setFallback(new Env());

// This checks neuron.yaml first, then environment variables
$value = $settings->get('section', 'key');
```

### Input Validation

```php
// Always validate filtered input
$email = Post::filterScalar('email');
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    throw new InvalidArgumentException('Invalid email');
}

// Use array filtering for multiple values
$ids = Get::filterArray('ids');
if ($ids) {
    $ids = array_map('intval', $ids);
    $ids = array_filter($ids, fn($id) => $id > 0);
}
```

### Environment Configuration

```php
// Use .env for local development
// config/.env.development
DB_HOST=localhost
APP_DEBUG=true

// Use environment variables in production
// Set via server configuration or container
```

## More Information

- **Neuron Framework**: [neuronphp.com](http://neuronphp.com)
- **GitHub**: [github.com/neuron-php/data](https://github.com/neuron-php/data)
- **Packagist**: [packagist.org/packages/neuron-php/data](https://packagist.org/packages/neuron-php/data)

## License

MIT License - see LICENSE file for details
