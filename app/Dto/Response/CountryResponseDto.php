<?php

declare(strict_types=1);

namespace App\Dto\Response;

use App\Models\Country;

readonly class CountryResponseDto
{
    public function __construct(
        public string $id,
        public string $name,
        public string $iso2Code,
        public string $iso3Code,
        public string $languageLocale,
        public string $callingCode,
        public ?string $flag,
        public string $currencyCode,
        public string $currencyName,
        public string $currencySymbol,
    ) {}

    public static function fromModel(Country $country): self
    {
        return new self(
            id: $country->id,
            name: $country->name,
            iso2Code: $country->iso2_code,
            iso3Code: $country->iso3_code,
            languageLocale: $country->language_locale,
            callingCode: $country->calling_code,
            flag: $country->flag,
            currencyCode: $country->currency_code,
            currencyName: $country->currency_name,
            currencySymbol: $country->currency_symbol,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'iso2Code' => $this->iso2Code,
            'iso3Code' => $this->iso3Code,
            'languageLocale' => $this->languageLocale,
            'callingCode' => $this->callingCode,
            'flag' => $this->flag,
            'currencyCode' => $this->currencyCode,
            'currencyName' => $this->currencyName,
            'currencySymbol' => $this->currencySymbol,
        ];
    }
}
