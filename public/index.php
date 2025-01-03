<?php

require __DIR__ . '/../vendor/autoload.php'; // Load Composer dependencies
require_once __DIR__ . '/../db.php'; // Include the database connection
require_once __DIR__ . '/../models/db_ops.php'; // Include database operations

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Selective\BasePath\BasePathMiddleware;
use Slim\Factory\AppFactory;

// Initialize Slim App
$app = AppFactory::create();

// Add Slim routing middleware
$app->addRoutingMiddleware();
$app->addBodyParsingMiddleware();


$app->add(new BasePathMiddleware($app));
$app->addErrorMiddleware(true, true, true);

$app->get('/test', function ($request, $response, $args) {
    $response->getBody()->write('Slim app is running!');
    return $response;
});

// Base route to check if the server is running
$app->get('/', function (Request $request, Response $response) {
    $response->getBody()->write('Server is running');
    return $response->withHeader('Content-Type', 'text/plain');
});

// Fetch all articles
$app->get('/articles', function (Request $request, Response $response) {
    global $pdo;

    try {
        // Query the database directly
        $stmt = $pdo->query("SELECT * FROM articles");
        $articles = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Encode the result as JSON and write it to the response
        $response->getBody()->write(json_encode($articles, JSON_UNESCAPED_UNICODE));
    } catch (PDOException $e) {
        // Handle any database errors
        $error = ['error' => 'Failed to fetch articles', 'message' => $e->getMessage()];
        $response->getBody()->write(json_encode($error, JSON_UNESCAPED_UNICODE));
        return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
    }

    // Return the response as JSON
    return $response->withHeader('Content-Type', 'application/json');
});

// GET: Retrieve an article by ID
$app->get('/article/{article_id}', function (Request $request, Response $response, $args) {
    global $pdo;
    $article_id = (int) $args['article_id'];

    try {
        $article = findArticleById($article_id, $pdo); // Assuming this function exists in db_ops.php
        if (!$article) {
            throw new Exception("Article with ID $article_id not found");
        }

        $response->getBody()->write(json_encode($article));
        return $response->withHeader('Content-Type', 'application/json');
    } catch (Exception $e) {
        $error = ['error' => $e->getMessage()];
        $response->getBody()->write(json_encode($error));
        return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
    }
});

// POST: Create or update an article
$app->post('/article', function (Request $request, Response $response) {
    global $pdo;
    $data = $request->getParsedBody();

    try {
        $header = $data['header'] ?? '';
        $url = $data['url'] ?? '';
        $article = createOrUpdateArticle($header, $url, $pdo); // Assuming this function exists in db_ops.php

        $response->getBody()->write(json_encode($article));
        return $response->withHeader('Content-Type', 'application/json');
    } catch (Exception $e) {
        $error = ['error' => $e->getMessage()];
        $response->getBody()->write(json_encode($error));
        return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
    }
});

// DELETE: Delete an article by ID
$app->delete('/article/{article_id}', function (Request $request, Response $response, $args) {
    global $pdo;
    $article_id = (int) $args['article_id'];

    try {
        // Check if the article exists
        $stmt = $pdo->prepare("SELECT * FROM articles WHERE id = :id");
        $stmt->execute([':id' => $article_id]);
        $article = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$article) {
            // If the article doesn't exist, return a 404 error
            throw new Exception("Article with ID $article_id not found");
        }

        // Delete the article
        $stmt = $pdo->prepare("DELETE FROM articles WHERE id = :id");
        $stmt->execute([':id' => $article_id]);

        // Return the deleted article details
        $response->getBody()->write(json_encode([
            'success' => true,
            'deleted_article' => $article,
        ]));
        return $response->withHeader('Content-Type', 'application/json');
    } catch (Exception $e) {
        // Return 404 status code with error message
        $error = ['error' => $e->getMessage()];
        $response->getBody()->write(json_encode($error));
        return $response
            ->withStatus(404)
            ->withHeader('Content-Type', 'application/json');
    }
});



// POST: Find articles by keywords
$app->post('/articles/find', function (Request $request, Response $response) {
    global $pdo;
    $data = $request->getParsedBody();
    $keywords = explode(',', $data['keywords'] ?? '');

    try {
        $articles = findArticlesByKeywords($keywords, $pdo); // Assuming this function exists in db_ops.php
        $response->getBody()->write(json_encode($articles, JSON_UNESCAPED_UNICODE));
        return $response->withHeader('Content-Type', 'application/json');
    } catch (Exception $e) {
        $error = ['error' => $e->getMessage()];
        $response->getBody()->write(json_encode($error, JSON_UNESCAPED_UNICODE));
        return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
    }
});

$app->get('/test-db', function (Request $request, Response $response) {
    global $pdo;

    try {
        // Count the records in the articles table
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM articles");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        // Respond with the count
        $response->getBody()->write(json_encode([
            'status' => 'success',
            'total_records' => $result['total'],
        ]));
    } catch (PDOException $e) {
        // Handle any errors
        $response->getBody()->write(json_encode([
            'status' => 'error',
            'message' => $e->getMessage(),
        ]));
        return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
    }

    return $response->withHeader('Content-Type', 'application/json');
});

// Run the Slim app
$app->run();

?>
