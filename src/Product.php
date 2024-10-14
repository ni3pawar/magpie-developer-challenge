<?php

namespace App;

class Product
{

    /**
     * Device title as described by retailer.
     *
     * @var string
     */
    public $title;

    /**
     * Device price.
     *
     * @var float
     */
    public $price;

    /**
     * Device image URL.
     *
     * @var string|null
     */
    public $image_url;

    /**
     * Device storage capacity, in megabytes.
     *
     * @var int|null
     */
    public $capacityMB;

    /**
     * Device colour.
     *
     * @var string
     */
    public $colour;

    /**
     * Device Availibility Text
     *
     * @var string|null
     */
    public $availabilityText;

    /**
     * Device Availibility
     *
     * @var bool
     */
    public $isAvailable = false;

    /**
     * Delivery message.
     *
     * @var string|null
     */
    public $shippingText = null;

    /**
     * Earliest delivery date.
     *
     * @var string|null
     */
    public $shippingDate = null;

}
