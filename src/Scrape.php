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
                ProductItemHelper::fetchProduct($product, $item, $colourVariant, $this->baseUrl);
                $id = $item->title . ' ' . $item->colour;
                $this->products[$id] = $item;                    
            }
        }

    }
}

/* The code snippet ` = new Scrape(); ->run();` is creating a new instance of the
`Scrape` class and then calling the `run` method on that instance. */
$scrape = new Scrape();
$scrape->run();
