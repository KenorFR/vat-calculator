VatCalculator
================

[![Software License](https://img.shields.io/github/license/spaze/vat-calculator)](LICENSE.md)
[![PHP Tests](https://github.com/spaze/vat-calculator/workflows/PHP%20Tests/badge.svg)](https://github.com/spaze/vat-calculator/actions?query=workflow%3A%22PHP+Tests%22)

Handle all the hard stuff related to EU MOSS tax/vat regulations, the way it should be. This is a fork of [mpociot/vat-calculator](https://github.com/mpociot/vat-calculator) without Laravel/Cashier support.

```php
// Easy to use!
use Spaze\VatCalculator\VatCalculator;

$vatRates = new VatRates();
$vatCalculator = new VatCalculator($vatRates);
$countryCode = $vatCalculator->getIpBasedCountry();
$vatCalculator->calculate( 24.00, $countryCode );
$vatCalculator->calculate( 24.00, $countryCode, $postalCode );
$vatCalculator->calculate( 71.00, 'DE', '41352', $isCompany = true );
$vatCalculator->getTaxRateForLocation( 'NL' );
// Check validity of a VAT number
$vatCalculator->isValidVatNumber('NL123456789B01');
```
## Contents

- [Installation](#installation)
	- [Standalone](#installation-standalone)
- [Usage](#usage)
	- [Calculate the gross price](#calculate-the-gross-price)
	- [Receive more information](#receive-more-information)
	- [Validate EU VAT numbers](#validate-eu-vat-numbers)
	- [Get EU VAT number details](#vat-number-details)
	- [Get the IP based country of your user](#get-ip-based-country)
	- [Countries](#countries)
- [License](#license)
- [Contributing](#contributing)

<a name="installation"></a>
## Installation

In order to install the VAT Calculator, just run

```bash
$ composer require spaze/vat-calculator
```

<a name="installation-standalone"></a>
### Standalone

This package is designed for standalone usage. Simply create a new instance of the VAT calculator and use it.

Example:

```php
use Spaze\VatCalculator\VatCalculator;

$vatRates = new VatRates();
$vatCalculator = new VatCalculator($vatRates);
$vatCalculator->setBusinessCountryCode('DE');
$countryCode = $vatCalculator->getIpBasedCountry();
$grossPrice = $vatCalculator->calculate( 49.99, 'LU' );
```

<a name="usage"></a>
## Usage
<a name="calculate-the-gross-price"></a>
### Calculate the gross price
To calculate the gross price use the `calculate` method with a net price and a country code as paremeters.

```php
$grossPrice = $vatCalculator->calculate( 24.00, 'DE' );
```
The third parameter is the postal code of the customer.

As a fourth parameter, you can pass in a boolean indicating whether the customer is a company or a private person. If the customer is a company, which you should check by <a href="#validate-eu-vat-numbers">validating the VAT number</a>, the net price gets returned.


```php
$grossPrice = $vatCalculator->calculate( 24.00, 'DE', '12345', $isCompany = true );
```
<a name="receive-more-information"></a>
### Receive more information
After calculating the gross price you can extract more information from the VatCalculator.

```php
$grossPrice = $vatCalculator->calculate( 24.00, 'DE' ); // 28.56
$taxRate    = $vatCalculator->getTaxRate(); // 0.19
$netPrice   = $vatCalculator->getNetPrice(); // 24.00
$taxValue   = $vatCalculator->getTaxValue(); // 4.56
```

<a name="validate-eu-vat-numbers"></a>
### Validate EU VAT numbers

Prior to validating your customers VAT numbers, you can use the `shouldCollectVat` method to check if the country code requires you to collect VAT
in the first place.

```php
if ($vatCalculator->shouldCollectVat('DE')) {

}
```

To validate your customers VAT numbers, you can use the `isValidVatNumber` method.
The VAT number should be in a format specified by the [VIES](http://ec.europa.eu/taxation_customs/vies/faqvies.do#item_11).
The given VAT numbers will be truncated and non relevant characters / whitespace will automatically be removed.

This service relies on a third party SOAP API provided by the EU. If, for whatever reason, this API is unavailable a `VatCheckUnavailableException` will be thrown.

```php
try {
	$validVat = $vatCalculator->isValidVatNumber('NL 123456789 B01');
} catch( VatCheckUnavailableException $e ){
	// Please handle me
}
```

<a name="vat-number-details"></a>
### Get EU VAT number details

To get the details of a VAT number, you can use the `getVatDetails` method.
The VAT number should be in a format specified by the [VIES](http://ec.europa.eu/taxation_customs/vies/faqvies.do#item_11).
The given VAT numbers will be truncated and non relevant characters / whitespace will automatically be removed.

This service relies on a third party SOAP API provided by the EU. If, for whatever reason, this API is unavailable a `VatCheckUnavailableException` will be thrown.

```php
try {
	$vat_details = $vatCalculator->getVatDetails('NL 123456789 B01');
	print_r($vat_details);
	/* Outputs VatDetails object, use getters to access values
	VatDetails Object
	(
		[valid:VatDetails:private] => false
		[countryCode:VatDetails:private] => NL
		[vatNumber:VatDetails:private] => 123456789B01
		[requestId:VatDetails:private] => FOOBAR338
	)
	*/
} catch( VatCheckUnavailableException $e ){
	// Please handle me
}
```

<a name="countries"></a>
## Countries

EU countries are supported as well as some non-EU countries that use VAT. Some countries are not supported even though they also have VAT. Currently, that's the case for Norway which can be added manually with `VatRates::addRateForCountry()`.

```php
$vatRates = new VatRates();
$vatRates->addRateForCountry('NO');
$vatCalculator = new VatCalculator($vatRates);
```

<a name="license"></a>
## License
This library is licensed under the MIT license. Please see [License file](LICENSE.md) for more information.

<a name="contributing"></a>
## Contributing
Run PHPUnit tests with `composer test`, see `scripts` in `composer.json`. Tests are also run on GitHub with Actions on each push.
