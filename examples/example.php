<?php

use GuzzleHttp\Client;
use paslandau\XmlSitemaps\SitemapManager;

require_once __DIR__ . "/bootstrap.php";

$defaults = ["debug" => false];
$config = ["defaults" => $defaults];
$client = new Client($config);
$sm = new SitemapManager($client);
$cacheDir = 'E:\Programmierung_Cache\xml-sitemaps';
$sm->activateCache($cacheDir);
$url = 'http://www.myseosolution.de/sitemap.xml';
$s = $sm->fetchSitemap($url, true);
foreach ($s->getPlainUrls() as $url) {
    echo "$url\n";
}
