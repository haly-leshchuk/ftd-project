<?php
require_once(__DIR__ . '/../db.php');
require_once(__DIR__ . '/Article.php'); // Ensure the Article class exists

/**
 * Create or update an article based on the URL.
 *
 * @param string $header The header of the article.
 * @param string $url The URL of the article.
 */
function createOrUpdateArticle($header, $url) {
    global $pdo;

    try {
        $query = "INSERT INTO articles (header, url, timestamp) VALUES (:header, :url, NOW())
                  ON CONFLICT (url) DO UPDATE SET header = :header, timestamp = NOW()";

        $stmt = $pdo->prepare($query);
        $stmt->execute(['header' => $header, 'url' => $url]);

        // Fetch the updated/inserted record if needed
        $query = "SELECT * FROM articles WHERE url = :url";
        $stmt = $pdo->prepare($query);
        $stmt->execute(['url' => $url]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        die('Error: ' . $e->getMessage());
    }
}

/**
 * Fetch articles by keywords. If no keywords are provided, return all articles.
 *
 * @param array $keywords An array of keywords to search for.
 * @return array An array of Article objects.
 */
function getArticlesByKeywords($keywords) {
    global $pdo;

    try {
        if (empty($keywords)) {
            $query = "SELECT * FROM articles";
            $stmt = $pdo->query($query);
            return $stmt->fetchAll(PDO::FETCH_CLASS, 'Article');
        }

        $query = "SELECT * FROM articles WHERE ";
        $conditions = [];
        foreach ($keywords as $index => $keyword) {
            $conditions[] = "header LIKE :keyword_$index";
        }
        $query .= implode(' OR ', $conditions);

        $stmt = $pdo->prepare($query);
        foreach ($keywords as $index => $keyword) {
            $stmt->bindValue(":keyword_$index", "%$keyword%");
        }
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_CLASS, 'Article');
    } catch (PDOException $e) {
        die('Error: ' . $e->getMessage());
    }
}

/**
 * Retrieve an article by ID from the database.
 *
 * @param int $article_id The ID of the article to retrieve.
 * @param PDO $pdo The PDO instance for database connection.
 * @return array|null The article data as an associative array, or null if not found.
 */
function findArticleById(int $article_id, PDO $pdo): ?array {
    try {
        // Prepare the SQL query
        $query = "SELECT * FROM articles WHERE id = :id";
        $stmt = $pdo->prepare($query);

        // Execute the query with the provided article ID
        $stmt->execute(['id' => $article_id]);

        // Fetch the article as an associative array
        $article = $stmt->fetch(PDO::FETCH_ASSOC);

        // Return the article or null if not found
        return $article ?: null;
    } catch (PDOException $e) {
        // Handle any database errors
        throw new Exception("Database error: " . $e->getMessage());
    }
}

/**
 * Delete an article by ID from the database.
 *
 * @param int $article_id The ID of the article to delete.
 * @param PDO $pdo The PDO instance for database connection.
 * @return bool True if the article was deleted, false if it was not found.
 * @throws Exception If a database error occurs.
 */
function deleteArticleById(int $article_id, PDO $pdo): bool {
    try {
        // Prepare the SQL query
        $query = "DELETE FROM articles WHERE id = :id";
        $stmt = $pdo->prepare($query);

        // Execute the query with the provided article ID
        $stmt->execute(['id' => $article_id]);

        // Check if any rows were affected
        return $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        // Handle any database errors
        throw new Exception("Database error: " . $e->getMessage());
    }
}

/**
 * Find articles that match the given keywords.
 *
 * @param array $keywords The array of keywords to search for.
 * @param PDO $pdo The PDO instance for database connection.
 * @return array The articles that match the keywords.
 * @throws Exception If a database error occurs.
 */
function findArticlesByKeywords(array $keywords, PDO $pdo): array {
    try {
        // Base query
        $query = "SELECT * FROM articles WHERE ";

        // Dynamically build conditions for each keyword
        $conditions = [];
        $params = [];
        foreach ($keywords as $index => $keyword) {
            $param = ":keyword$index";
            $conditions[] = "header LIKE $param OR url LIKE $param";
            $params[$param] = '%' . trim($keyword) . '%'; // Add wildcard for partial matching
        }

        // Combine conditions with OR operator
        $query .= implode(' OR ', $conditions);

        // Prepare and execute the query
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);

        // Fetch the results
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // Handle any database errors
        throw new Exception("Database error: " . $e->getMessage());
    }
}



?>
