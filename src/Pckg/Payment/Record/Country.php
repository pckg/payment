<?php

namespace Pckg\Payment\Record;

use Pckg\Database\Record;
use Pckg\Payment\Entity\Countries;

/**
 * @property string $code
 */
class Country extends Record
{
    protected $entity = Countries::class;

    /**
     * @return array
     */
    public function getAlpha32Mapper(): array
    {
        return config('static.countries.alpha-3-2', []); // THR => TW
    }

    /**
     * @return array
     */
    public function getAlpha23Mapper(): array
    {
        return array_flip($this->getAlpha32Mapper()); // TW => THR
    }

    /**
     * @return array
     */
    public function getEUCountries(): array
    {
        return config('static.countries.eu', []); // CO => Title
    }

    /**
     * @return bool
     */
    public function isEU(): bool
    {
        return in_array($this->getISO2(), array_keys($this->getEUCountries()));
    }

    public function getISO2()
    {
        $code = strtoupper($this->code ?? '');

        return $this->getAlpha32Mapper()[$code] ?? substr($code, 0, 2);
    }

    public function getISO3()
    {
        $code = strtoupper($this->code ?? '');

        return $this->getAlpha23Mapper()[$code] ?? substr($code, 0, 3);
    }
}
