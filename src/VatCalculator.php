<?php
declare(strict_types = 1);

namespace Spaze\VatCalculator;

use Spaze\VatCalculator\Exceptions\VatCheckUnavailableException;
use SoapClient;
use SoapFault;

class VatCalculator
{

	/**
	 * VAT Service check URL provided by the EU.
	 *
	 * @var string
	 */
	const VAT_SERVICE_URL = 'http://ec.europa.eu/taxation_customs/vies/checkVatService.wsdl';

	/** @var SoapClient */
	private $soapClient;

	/** @var VatRates */
	private $vatRates;

	/** @var string */
	private $businessCountryCode;

	/** @var string */
	private $businessVatNumber;


	public function __construct(VatRates $vatRates)
	{
		$this->vatRates = $vatRates;
	}


	public function shouldCollectVat(string $countryCode): bool
	{
		return $this->vatRates->shouldCollectVat($countryCode);
	}


	/**
	 * Calculate the VAT based on the net price, country code and indication if the
	 * customer is a company or not.
	 *
	 * @param float $netPrice
	 * @param string $countryCode
	 * @param string|null $postalCode
	 * @param bool $company
	 * @param string $type
	 * @return VatPrice
	 */
	public function calculate(float $netPrice, string $countryCode, ?string $postalCode, bool $company, string $type = VatRates::GENERAL): VatPrice
	{
		$taxRate = $this->getTaxRateForLocation($countryCode, $postalCode, $company, $type);
		$taxValue = $taxRate * $netPrice;
		return new VatPrice($netPrice, $netPrice + $taxValue, $taxValue, $taxRate);
	}


	/**
	 * Calculate the net price on the gross price, country code and indication if the
	 * customer is a company or not.
	 *
	 * @param float $gross
	 * @param string $countryCode
	 * @param string|null $postalCode
	 * @param bool $company
	 * @param string $type
	 * @return VatPrice
	 */
	public function calculateNet(float $gross, string $countryCode, ?string $postalCode, bool $company, string $type = VatRates::GENERAL): VatPrice
	{
		$taxRate = $this->getTaxRateForLocation($countryCode, $postalCode, $company, $type);
		$taxValue = $taxRate > 0 ? $gross / (1 + $taxRate) * $taxRate : 0;
		return new VatPrice($gross - $taxValue, $gross, $taxValue, $taxRate);
	}


	public function setBusinessCountryCode(string $businessCountryCode): void
	{
		$this->businessCountryCode = strtoupper($businessCountryCode);
	}


	public function setBusinessVatNumber(string $businessVatNumber): void
	{
		$this->businessVatNumber = $businessVatNumber;
	}


	/**
	 * Returns the tax rate for the given country code.
	 * If a postal code is provided, it will try to lookup the different
	 * postal code exceptions that are possible.
	 *
	 * @param string $countryCode
	 * @param string|null $postalCode
	 * @param bool $company
	 * @param string|null $type
	 * @return float
	 */
	public function getTaxRateForLocation(string $countryCode, ?string $postalCode, bool $company, ?string $type = null): float
	{
		if ($company && strtoupper($countryCode) !== $this->businessCountryCode) {
			return 0;
		}

		return $this->vatRates->getTaxRateForLocation($countryCode, $postalCode, $type);
	}


	/**
	 * @param string $vatNumber
	 * @return bool
	 * @throws VatCheckUnavailableException
	 */
	public function isValidVatNumber(string $vatNumber): bool
	{
		$details = $this->getVatDetails($vatNumber);

		if ($details) {
			return $details->isValid();
		} else {
			return false;
		}
	}


	/**
	 * @param string $vatNumber
	 * @param string|null $requesterVatNumber
	 * @return VatDetails
	 * @throws VatCheckUnavailableException
	 */
	public function getVatDetails(string $vatNumber, ?string $requesterVatNumber = null): VatDetails
	{
		$vatNumber = str_replace([' ', "\xC2\xA0", "\xA0", '-', '.', ','], '', trim($vatNumber));
		$countryCode = substr($vatNumber, 0, 2);
		$vatNumber = substr($vatNumber, 2);
		try {
			if ($this->soapClient === null) {
				$this->soapClient = new SoapClient(self::VAT_SERVICE_URL);
			}
			if ($requesterVatNumber === null) {
				$requesterVatNumber = $this->businessVatNumber;
			}

			$result = $this->soapClient->checkVatApprox([
				'countryCode' => $countryCode,
				'vatNumber' => $vatNumber,
				'requesterCountryCode' => $requesterVatNumber ? substr($requesterVatNumber, 0, 2) : null,
				'requesterVatNumber' => $requesterVatNumber ? substr($requesterVatNumber, 2) : null,
			]);
			return new VatDetails($result->valid, $result->countryCode, $result->vatNumber, $result->requestIdentifier);
		} catch (SoapFault $e) {
			throw new VatCheckUnavailableException($e->getMessage(), $e->getCode(), $e);
		}
	}


	public function setSoapClient(SoapClient $soapClient): void
	{
		$this->soapClient = $soapClient;
	}

}
