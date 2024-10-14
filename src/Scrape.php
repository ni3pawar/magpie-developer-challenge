<?php

namespace App;

use Symfony\Component\DomCrawler\Crawler;

require 'vendor/autoload.php';

class Scrape
{
    private array $products = [];
    private string $baseUrl = 'https://www.magpiehq.com/developer-challenge/smartphones';
    
    /**
     * @return void The code snippet provided is a PHP function that performs web scraping to extract
     * data from multiple pages and then saves the scraped data into a JSON file.
     */
    public function run(): void
    {
        /* The code snippet is fetching a document from a specific URL using a helper
        method `ScrapeHelper::fetchDocument(->baseUrl)`. This method likely makes an HTTP
        request to the URL specified by `->baseUrl` and returns a `Crawler` object representing
        the HTML content of the webpage. */
        $document = ScrapeHelper::fetchDocument($this->baseUrl);
        $pages = $document->filter('#pages div')->children('a')->each(function ($node) {     return $node->text(); });

        /* The `foreach` loop is iterating over each page number extracted from the
        initial document. For each page number, it constructs a new URL by appending the page number
        as a query parameter to the base URL. */
        foreach ($pages as $page) {
            $document = ScrapeHelper::fetchDocument($this->baseUrl . '?page=' . $page);
            $this->fetchProductsFromPage($document);
        }

        file_put_contents('output.json',  json_encode(array_values($this->products)));
    }

    /**
     * @param Crawler document The `document` parameter in the `fetchProductsFromPage` function is of
     * type `Crawler`. It represents the HTML document that is being scraped for product information.
     * The function uses this `Crawler` object to filter and extract product data from the document.
     */
    private function fetchProductsFromPage(Crawler $document): void
    {
        $products = $document->filter('#products .product');

        foreach ($products as $productNode) {
            $product = new Crawler($productNode);

            $colours = $product->filter('div span[data-colour]');

            foreach ($colours as $colourNode) {
                $colour = new Crawler($colourNode);
                $colourVariant = $colour->attr('data-colour');
                $item = new Product();
                $this->fetchProduct($product, $item, $colourVariant);
                $id = $item->title . ' ' . $item->colour;
                $this->products[$id] = $item;                    
            }
        }

    }

    /**
     * @param Crawler product object representing a product.
     * @param Product item The `fetchProduct` object to store the product information.
     * @param string colour  string representing the color of the product.
     */
    private function fetchProduct(Crawler $product, Product $item, string $colour): void
    {
        $item->title = $product->filter('h3')->text();
        /* extract the price of a product */
        $item->price = $this->extractPrice($product);
        $item->image_url = $this->baseUrl . ltrim($product->filter('img')->first()->attr('src'), '.');
        /* extract the capacity information of a product */
        $item->capacityMB = $this->extractCapacity($product);
        $item->colour = $colour;

        /* extracting availability and shipping information from a product and injecting that
        information into the `Product` object passed as a parameter. */
        $this->extractAndInjectAvailabilityAndShipping($item, $product);
        $item->isAvailable = str_contains($item->availabilityText, "In Stock");
    }

    /**
     * @param Product item for injects availability and shipping information into the `Product` object.
     * @param Crawler product for extracts availability and shipping information.
     */
    private function extractAndInjectAvailabilityAndShipping(Product $item, Crawler $product): void
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
            $item->shippingDate = $this->extractDateFromMessage($item->shippingText);
        }
    }

    /**
     * @param string deliveryMessage The `extractDateFromMessage` function is designed to extract a
     * date from the provided `deliveryMessage` string. The function uses a regular expression pattern
     * to search for date formats like 'YYYY-MM-DD', 'DD MMM YYYY', or 'tomorrow' within the message.
     */
    private function extractDateFromMessage(string $deliveryMessage): ?string
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
    private function extractPrice(Crawler $product): ?float
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
    private function extractCapacity(Crawler $product): ?int
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

/* The code snippet ` = new Scrape(); ->run();` is creating a new instance of the
`Scrape` class and then calling the `run` method on that instance. */
$scrape = new Scrape();
$scrape->run();
