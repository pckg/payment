<?php

namespace Pckg\Payment\Record;

use Pckg\Collection;
use Pckg\Database\Record;
use Pckg\Generic\Entity\SettingsMorphs;
use Pckg\Payment\Entity\Companies;

/**
 * Class Company
 *
 * @property Country $country
 * @property string $address_line1
 * @property string $address_line2
 * @property string $address_line3
 * @property string $short_name
 * @property string $long_name
 * @property string $vat_number
 * @property string $business_number
 * @property string $note1
 * @property string $note2
 * @property string $incorporated_at
 */
class Company extends Record
{
    protected $entity = Companies::class;

    protected $toArray = ['country'];

    public function getAddressAttribute($separator = ' ')
    {
        return (new Collection([$this->address_line1, $this->address_line2, $this->address_line3]))->removeEmpty()
            ->implode($separator);
    }

    public function getFullInfoAttribute()
    {
        return $this->short_name . '; ' . $this->getAddressAttribute(', ') . '; VAT: ' . $this->vat_number . '; BN: ' .
            $this->business_number;
    }

    public function getHeaderInfoAttribute()
    {
        return collect([
                $this->long_name ?? $this->short_name,
                $this->address_line1,
                $this->address_line2,
                $this->address_line3,
                $this->country->title,
                $this->vat_number || $this->business_number ? '&nbsp;' : null,
                $this->vat_number ? __('document.bill.label.vatNumber') . ': ' . $this->vat_number : null,
                $this->business_number ? __('document.bill.label.businessNumber') . ': ' . $this->business_number : null,
                $this->vat_number || $this->business_number ? '&nbsp;' : null,
                $this->note1,
                $this->note2,
            ])->trim()->removeEmpty()->implode('<br />') . '<br />';
    }

    public function applyConfig()
    {
        (new SettingsMorphs())->where('morph_id', Companies::class)
            ->where('poly_id', $this->id)
            ->withSetting()
            ->all()->each->registerToConfig();
    }

    public function getFiscalizationHandler()
    {
        return $this->country->getFiscalizationHandler();
    }
}
