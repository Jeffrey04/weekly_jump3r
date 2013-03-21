<?php

/**
 * Quick Hack
 */
error_reporting(E_ALL);

if(array_key_exists('badge_id', $_GET)) :

header('Content-type: image/png');

$badge = query(database(), get_badge(database(), $_GET['badge_id']), get_count());

if(empty($_SERVER['HTTP_REFERER']) === FALSE AND get_badge(database(), $_GET['badge_id']) != 0) {
    badge_log_website(
        badge_prepare_log(pdo()),
        get_website($_SERVER['HTTP_REFERER']),
        get_badge(database(), $_GET['badge_id']),
        get_count()
    );
}

$base = imagecreatetruecolor(100, 20);
imagealphablending($base, true);
imagesavealpha($base, true);

$text = imagecreate(100, 20);
imagecolorallocate($text, 0xFF, 0xFF, 0xFF);

imagefttext($text, 8.5, 0, 20, 14, imagecolorallocate($text, 0x11, 0x11, 0x11), '../external/wt006.ttf', $badge['caption']);
imagerectangle($text, 0, 0, 99, 19, imagecolorallocate($text, 0x11, 0x11, 0x11));

$medal = imagecreatefrompng(medal($badge['medal']));

imagecopy($base, $text, 0, 0, 0, 0, 100, 20);
imagecopy($base, $medal, 2, 2, 0, 0, 16, 16);

imagepng($base);
imagedestroy($base);
imagedestroy($text);
imagedestroy($medal);

else :
    header(sprintf('Location: http://%s%s', $_SERVER['SERVER_NAME'], dirname($_SERVER['PHP_SELF'])));
endif;

function badge_prepare_log(PDO $pdo) {
    return $pdo->prepare('INSERT INTO log.website(url, badge_id, count) VALUES(:url, :badge_id, :count)');
}

function badge_log_website(PDOStatement $statement, $url, $badge_id, $count) {
    $statement->execute(array(
        ':url' => $url,
        ':badge_id' => $badge_id,
        ':count' => $count
    ));
}

function pdo() {
    return call_user_func(
        function($config) {
            return new PDO(sprintf(
                '%s:host=%s;port=%s;dbname=%s;user=%s;password=%s',
                $config['driver'],
                $config['host'],
                $config['port'],
                $config['database'],
                $config['username'],
                $config['password']
            ));
        },
        json_decode(file_get_contents('../config/database.json'), TRUE)
    );
}

function get_website($url) {
    return sprintf(
        '%s://%s/%s',
        parse_url($url, PHP_URL_SCHEME),
        parse_url($url, PHP_URL_HOST),
        (parse_url($url, PHP_URL_PATH) != '/' AND strpos(parse_url($url, PHP_URL_HOST), 'blogspot') === FALSE) ?
            array_pop(explode('/', substr(parse_url($url, PHP_URL_PATH), 1))) . '/'
            : ''
    );
}

function get_count() {
    return (array_key_exists('count', $_GET) AND is_integer(intval($_GET['count']))) ?
        $_GET['count']
        : 1;
}

function get_badge(Array $database, $badge_id) {
    return array_key_exists($badge_id, $database) === FALSE ? 0 : $badge_id;
}

function database() {
    return json_decode(file_get_contents('database.json'), TRUE);
}

function badge_filter_count($badge, $count) {
    return array_merge(
        $badge,
        call_user_func(
            'array_pop',
            array_filter(
                $badge['tiers'],
                function($badge) use($count) {
                    return $badge['min_count'] <= $count;
                })));
}

function query(Array $database, $badge_id, $count) {
    return call_user_func(
        function($badge) use($count) {
            return $badge['multiple'] > 0 ?
                badge_filter_count($badge, $count)
                : $badge;
        },
        $database[array_key_exists($badge_id, $database) === FALSE ? 0 : $badge_id]
    );
}

function medal($badge) {
    return "./images/icons/{$badge}";
}
