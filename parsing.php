<?php

if ($argc < 2) {
    echo "Usage: php parsing.php fichier_html1 fichier_html2 ...\n";
    exit(1);
}

$result = array(
    "status" => "ok",
    "result" => array(
        "movie" => array(),
        "tv" => array()
    )
);

for ($i = 1; $i < $argc; $i++) {
    $html = file_get_contents($argv[$i]);
    $data = extractData($html);

    if ($data) {
        $result["result"][$data["type"]][] = $data["info"];
    }
}

$json = json_encode($result, JSON_PRETTY_PRINT);
file_put_contents("result.json", $json);

function extractData($html) {
    $title = extractValue($html, '/<meta property=og:title content="([^"]+)">/');
    $type = extractValue($html, '/<meta property=og:type content=(.*?)>/');
    $releaseDate = extractValue($html, '/<span class=release_date>\((\d+)\)<\/span>/');
    $summary = extractValue($html, '/<meta name=description content="([^"]+)">/');
    $status = extractValue($html, '/<p><strong><bdi>Status<\/bdi><\/strong>\s*(.*?)<\/p>/');
    $duration = extractValue($html, '/<p><strong><bdi>Runtime<\/bdi><\/strong>\s*(.*?)<\/p>/');
    $originalLanguage = extractValue($html, '/<p><strong><bdi>Original Language<\/bdi><\/strong>\s*(.*?)<\/p>/');
    $budget = extractValue($html, '/<p><strong><bdi>Budget<\/bdi><\/strong>\s*(.*?)<\/p>/');
    $revenue = extractValue($html, '/<p><strong><bdi>Revenue<\/bdi><\/strong>\s*(.*?)<\/p>/');

    $genres = extractValues($html, '/<h4><bdi>Genres<\/bdi><\/h4>\s*<ul>(.*?)<\/ul>/s');
    $genres = extractValues($genres[0], '/<li><a [^>]*>([^<]+)<\/a><\/li>/', 5);
    $genres = trimHtmlChar($genres, '&amp;');

    $keywords = extractValues($html, '/<h4><bdi>Keywords<\/bdi><\/h4>\s*<ul>(.*?)<\/ul>/s');
    $keywords = extractValues($keywords[0], '/<li><a [^>]*>([^<]+)<\/a><\/li>/', 5);

    $cast = extractValues($html, '/<ol class="people scroller">(.*?)<\/ol>/s');
    $cast = extractCast($cast[0]);

    $info = array(
        "title" => $title,
        "releaseDate" => $releaseDate,
        "summary" => $summary,
        "status" => $status,
        "duration" => $duration,
        "budget" => $budget,
        "revenue" => $revenue,
        "originalLanguage" => $originalLanguage,
        "genre" => $genres,
        "keywords" => $keywords,
        "cast" => $cast
    );

    foreach ($info as $key => $value) {
        if (is_null($value) || $value === '') {
            unset($info[$key]);
        }
    }

    if ($type) {
        return array(
            "type" => $type,
            "info" => $info
        );
    } else {
        return null;
    }
}

function extractValue($text, $pattern) {
    preg_match($pattern, $text, $matches);
    return isset($matches[1]) ? $matches[1] : null;
}

function extractValues($text, $pattern, $limit = -1) {
    preg_match_all($pattern, $text, $matches);
    return isset($matches[1]) ? ($limit > 0 ? array_slice($matches[1], 0, $limit) : $matches[1]) : [];
}

function extractCast($text) {
    $pattern = '/<p><a[^>]+>([^<]+)<\/a><\/p>\s*<p class=character>([^<]+)<\/p>/';
    preg_match_all($pattern, $text, $matches, PREG_SET_ORDER);
    
    $cast = [];
    foreach ($matches as $key => $match) {
        if ($key >= 5) {
            break;
        }
        $name = isset($match[1]) ? $match[1] : "";
        $character = isset($match[2]) ? $match[2] : "";
        $cast[] = array(
            "name" => $name,
            "character" => $character
        );
    }
    
    return $cast;
}

function trimHtmlChar($array, $char) {
    $new_array = array();
    foreach($array as $value) {
        if (strpos($value, $char)) {
            $value = explode($char, $value);
            $value = array_map('trim', $value);
            $new_array = array_merge($new_array, $value);
        } else {
            $new_array[] = $value;
        }
    }

    return $new_array;
}