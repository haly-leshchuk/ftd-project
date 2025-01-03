<?php

use PHPUnit\Framework\TestCase;

class ApiTest extends TestCase
{
    private $baseUrl = 'http://localhost:8000';

    // Helper method to send HTTP requests
    private function sendRequest(string $method, string $uri, array $data = [])
    {
        $client = new GuzzleHttp\Client(['base_uri' => 'http://localhost:8000']);
        $options = [];

        if (!empty($data)) {
            $options['json'] = $data;
        }

        $response = $client->request($method, $uri, $options);

        return [
            'statusCode' => $response->getStatusCode(),
            'body' => (string)$response->getBody(),
        ];
    }


    public function testIndex()
    {
        $response = $this->sendRequest('GET', '/');
        $this->assertEquals(200, $response['statusCode']);
        $this->assertStringContainsString('Server is running', $response['body']);
    }

    public function testCreateOrUpdateArticle()
    {
        $postData = [
            'id' => 1,
            'header' => 'header1',
            'url' => 'url2',
        ];

        // Send a POST request to the endpoint
        $response = $this->sendRequest('POST', '/article', $postData);

        // Decode the JSON response
        $responseBody = json_decode($response['body'], true);

        // Check if decoding was successful
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->fail('Failed to decode JSON response.');
        }

        // Assert response structure and content
        $this->assertEquals(200, $response['statusCode']);
        $this->assertEquals('header1', $responseBody['header']);
        $this->assertEquals('url2', $responseBody['url']);
        $this->assertNotNull($responseBody['timestamp']);
        $this->assertNotEmpty($responseBody['id']);

        // Update the same article
        $response = $this->sendRequest('POST', '/article', [
            'id' => 1,
            'header' => 'header1',
            'url' => 'url2'
        ]);

        $responseBody = json_decode($response['body'], true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->fail('Failed to decode JSON response.');
        }

        $this->assertEquals(200, $response['statusCode']);
        $this->assertEquals('header1', $responseBody['header']);
        $this->assertEquals('url2', $responseBody['url']);
        $this->assertNotNull($responseBody['timestamp']);
        $this->assertNotEmpty($responseBody['id']);
    }


    public function testFindArticleById()
    {
        $postData = [
            'header' => 'header1',
            'url' => 'url2',
        ];

        // Send a POST request to the endpoint
        $response_post = $this->sendRequest('POST', '/article', $postData);

        // Decode the POST response
        $responseBodyPost = json_decode($response_post['body'], true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->fail('Failed to decode POST JSON response: ' . json_last_error_msg());
        }

        // Fetch the ID from the POST response
        $id = $responseBodyPost['id'];

        // Send a GET request using the fetched ID
        $response = $this->sendRequest('GET', "/article/{$id}");

        // Decode the GET response
        $responseBody = json_decode($response['body'], true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            echo "!!! JSON Decoding Error: " . json_last_error_msg();
            echo "!!! Raw Response Body: " . $response['body'];
            $this->fail('Failed to decode GET JSON response.');
        }

        // Validate the status code
        $this->assertEquals(200, $response['statusCode']);

        // Validate the response content
        $this->assertEquals('header1', $responseBody['header']);
        $this->assertEquals('url2', $responseBody['url']);
        $this->assertNotNull($responseBody['timestamp']);
        $this->assertEquals($id, $responseBody['id']);
    }



    public function testDeleteArticleById()
    {
        $postData = [
            'header' => 'header1',
            'url' => 'url2',
        ];

        // Send a POST request to the endpoint
        $response_post = $this->sendRequest('POST', '/article', $postData);

        // Decode the POST response
        $responseBodyPost = json_decode($response_post['body'], true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->fail('Failed to decode POST JSON response: ' . json_last_error_msg());
        }

        // Fetch the ID from the POST response
        $id = $responseBodyPost['id'];

        $response = $this->sendRequest('DELETE', "/article/{$id}");
        $this->assertEquals(200, $response['statusCode']);
        $responseBody = json_decode($response['body'], true);
        $data = $responseBody['deleted_article'];
        $this->assertEquals('header1', $data['header']);
        $this->assertEquals('url2', $data['url']);
        $this->assertNotNull($data['timestamp']);
        $this->assertEquals($id, $data['id']);

        // Try deleting again
        $response = $this->sendRequest('DELETE', "/article/{$id}");
        $this->assertStringContainsString("Article with ID {$id} not found", $response['body']);
    }

    public function testFindArticlesByKeywords()
    {
        $articles = [
            ['header' => 'header1', 'url' => 'url1'],
            ['header' => 'header2', 'url' => 'url2'],
            ['header' => 'header3', 'url' => 'url3'],
            ['header' => 'header4', 'url' => 'url4'],
            ['header' => 'url', 'url' => 'url4'],
            ['header' => 'holader5', 'url' => 'url5'],
            ['header' => 'url', 'url' => 'url5']
        ];

        foreach ($articles as $article) {
            $this->sendRequest('POST', '/article', $article);
        }

        // Search with keyword "head"
        $response = $this->sendRequest('POST', '/articles/find', ['keywords' => 'head']);
        $responseBody = json_decode($response['body'], true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->fail('Failed to decode JSON response: ' . json_last_error_msg());
        }

        $this->assertEquals(200, $response['statusCode']);
        $this->assertIsArray($responseBody);
        $this->assertCount(3, $responseBody);

        // Search with keyword "url"
        $response = $this->sendRequest('POST', '/articles/find', ['keywords' => 'url']);
        $responseBody = json_decode($response['body'], true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->fail('Failed to decode JSON response: ' . json_last_error_msg());
        }

        $this->assertEquals(200, $response['statusCode']);
        $this->assertIsArray($responseBody);
        $this->assertCount(5, $responseBody);

        // Search with keyword "hoad"
        $response = $this->sendRequest('POST', '/articles/find', ['keywords' => 'hoad']);
        $responseBody = json_decode($response['body'], true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->fail('Failed to decode JSON response: ' . json_last_error_msg());
        }

        $this->assertEquals(200, $response['statusCode']);
        $this->assertIsArray($responseBody);
        $this->assertCount(0, $responseBody);
    }
}
