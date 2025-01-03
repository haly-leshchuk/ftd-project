// <?php
// require_once __DIR__ . '/../models/db_ops.php';
// require_once __DIR__ . '/../vendor/autoload.php';
//
// use GuzzleHttp\Client;
//
// function scrapeSite($url, $headerSelector, $urlSelector) {
//     echo "!!!!! i am here";
// //     $client = new Client();
// //     $response = $client->get($url);
// //     $html = $response->getBody()->getContents();
// //
// //     $dom = new DOMDocument();
// //     @$dom->loadHTML($html);
// //
// //     $xpath = new DOMXPath($dom);
// //     $headers = $xpath->query($headerSelector);
// //
// //     foreach ($headers as $header) {
// //         $title = $header->nodeValue;
// //         $link = $header->getAttribute('href');
// //         echo "Title: $title, Link: $link\n";
// //         createOrUpdateArticle($title, $link);
// //     }
// }
//
// // Example values for parameters
// $url = 'https://example.com';
// $headerSelector = '//h2'; // XPath or CSS selector for headers
// $urlSelector = '//a'; // XPath or CSS selector for URLs
//
// // Call the function
// scrapeSite($url, $headerSelector, $urlSelector);
//
//

require_once __DIR__ . '/../vendor/autoload.php'; // Assuming Composer dependencies
require_once __DIR__ . '/../models/db_ops.php';  // For database operations

use GuzzleHttp\Client;
use Symfony\Component\CssSelector\CssSelectorConverter;

class NewsScraper
{
    private $websites;

    public function __construct()
    {
        $this->websites = [
            [
                'url' => 'https://www.bbc.com/ukrainian',
                'header_selector' => 'h3 a.focusIndicatorDisplayBlock',
                'url_selector' => 'a'
            ],
            [
                'url' => 'https://www.pravda.com.ua/news/',
                'header_selector' => 'div.article_header',
                'url_selector' => 'a'
            ]
        ];
    }

    public function startScraping()
    {
        while (true) {
            foreach ($this->websites as $site) {
                $articles = $this->scrape($site['url'], $site['header_selector'], $site['url_selector']);
                echo "Articles from " . $site['url'] . ":\n";

                foreach ($articles as $article) {
                    $header = $article['header'];
                    $url = $article['url'];

                    echo "Header: $header, URL: $url\n";

                    // Assuming a function for DB context and article creation exists in db_ops.php
                    createOrUpdateArticle($header, $url); // Function to save articles in DB
                }
            }

            // Sleep for 60 seconds
            sleep(60);
        }
    }

    private function scrape($url, $headerSelector, $urlSelector)
    {
        try {
            $client = new Client();
            $response = $client->get($url);
            $html = $response->getBody()->getContents();
            $html = mb_convert_encoding($html, 'HTML-ENTITIES', 'auto');

            $dom = new DOMDocument();
            @$dom->loadHTML($html);
            $xpath = new DOMXPath($dom);

            $articles = [];
            $headers = $xpath->query($this->convertCssToXPath($headerSelector));

            foreach ($headers as $header) {
                $title = $header->nodeValue;

                $linkElement = $xpath->query($this->convertCssToXPath($urlSelector), $header);
                $link = null;

                if ($header->hasAttribute('href')) {
                    $link = $header->getAttribute('href');
                } elseif ($linkElement->length > 0 && $linkElement->item(0)->hasAttribute('href')) {
                    $link = $linkElement->item(0)->getAttribute('href');
                }

                if ($link) {
                    $articles[] = [
                        'header' => $title,
                        'url' => $link
                    ];
                }
            }

            return $articles;
        } catch (Exception $e) {
            echo "Error scraping $url: " . $e->getMessage() . "\n";
            return [];
        }
    }


    private function convertCssToXPath($cssSelector)
    {
        $converter = new CssSelectorConverter();
        return $converter->toXPath($cssSelector);
    }
}

// Start the scraper
$scraper = new NewsScraper();
$scraper->startScraping();

?>
