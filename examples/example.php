<?php

use GuzzleHttp\Client;
use paslandau\IOUtility\IOUtil;
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
$result = [];
    foreach($s->getUrls() as $u) {
        $result[] = ["sitemap" => $url, "url" => $u->getLoc()];
    }
