<?php

namespace App;

use GuzzleHttp\Client;
use Symfony\Component\DomCrawler\Crawler;

class ProductItemHelper
{
    /**
     * @param Crawler product object representing a product.
     * @param Product item The `fetchProduct` object to store the product information.
     * @param string colour  string representing the color of the product.
     * @param string baseUrl  string representing the base url of the image source.
     */
    public static function fetchProduct(Crawler $product, Product $item, string $colour, string $baseUrl): void
    {
        $item->title = $product->filter('h3')->text();
        /* extract the price of a product */
        $item->price = self::extractPrice($product);
        $item->image_url = $baseUrl . ltrim($product->filter('img')->first()->attr('src'), '.');
        /* extract the capacity information of a product */
        $item->capacityMB = self::extractCapacity($product);
        $item->colour = $colour;

        /* extracting availability and shipping information from a product and injecting that
        information into the `Product` object passed as a parameter. */
        self::extractAndInjectAvailabilityAndShipping($item, $product);
        $item->isAvailable = str_contains($item->availabilityText, "In Stock");
    }

    /**
     * @param Product item for injects availability and shipping information into the `Product` object.
     * @param Crawler product for extracts availability and shipping information.
     */
    public static function extractAndInjectAvailabilityAndShipping(Product $item, Crawler $product): void
    {
        $availabilityAndShipping = $product->filter('.my-4.text-sm.block.text-center');
        $availabilityText = $availabilityAndShipping->first()->text('empty');
        $item->availabilityText = $availabilityText !== 'empty'
            ? str_replace('Availability: ', '', $availabilityText)
            : null;

        $item->shippingText = $availabilityAndShipping->count() > 1 ?
            $availabilityAndShipping->last()->text('empty') :
            null;

        if (isset($item->shippingText)) {
            $item->shippingDate = self::extractDateFromMessage($item->shippingText);
        }
    }

    /**
     * @param string deliveryMessage The `extractDateFromMessage` function is designed to extract a
     * date from the provided `deliveryMessage` string. The function uses a regular expression pattern
     * to search for date formats like 'YYYY-MM-DD', 'DD MMM YYYY', or 'tomorrow' within the message.
     */
    public static function extractDateFromMessage(string $deliveryMessage): ?string
    {
        $pattern = '/\b(?:\d{4}-\d{2}-\d{2}|\d{1,2}(?:th|st|nd|rd)? (?:Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec) \d{4}|tomorrow)\b/i';

        if (preg_match($pattern, $deliveryMessage, $matches)) {
            return date('Y-m-d', strtotime($matches[0]));
        }

        return null;
    }

    /**
     * @param Crawler product The `extractPrice` function takes a parameter named `` of type
     * `Crawler`. This function extracts the price of a product by filtering the HTML content of the
     * product using a CSS selector `.my-8.block.text-center.text-lg` and then converting the extracted
     * price to a float value after
     */
    public static function extractPrice(Crawler $product): ?float
    {
        $price = $product->filter('.my-8.block.text-center.text-lg')->text('empty');

        if ($price === 'empty') {
            return null;
        }

        return (float) ltrim($price, '£');
    }

    /**
     * @param Crawler product The `extractCapacity` function takes a `Crawler` object named ``
     * as a parameter. This function extracts the capacity information from the product by filtering
     * the HTML content using a CSS selector and then processing the extracted text to determine the
     * capacity value in megamegabytes.
     */
    public static function extractCapacity(Crawler $product): ?int
    {
        $capacity = $product->filter('h3 > .product-capacity')->text('empty');

        if ($capacity === 'empty') {

            return null;
        } else {

            if (str_contains($capacity, 'MB')) {
                return intval($capacity);
            } else {
                return intval($capacity) * 1024;
            }
        }
    }


}
