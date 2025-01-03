<?php
class Article {
    public $id;
    public $header;
    public $url;
    public $timestamp;

    public function __construct($id, $header, $url, $timestamp) {
        $this->id = $id;
        $this->header = $header;
        $this->url = $url;
        $this->timestamp = $timestamp;
    }
}
?>
