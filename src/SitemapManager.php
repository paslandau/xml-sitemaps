<?php
namespace paslandau\XmlSitemaps;

use Doctrine\Common\Cache\FilesystemCache;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Event\AbstractTransferEvent;
use GuzzleHttp\Event\BeforeEvent;
use GuzzleHttp\Event\EndEvent;
use GuzzleHttp\Event\ErrorEvent;
use GuzzleHttp\Message\RequestInterface;
use GuzzleHttp\Message\ResponseInterface;
use GuzzleHttp\Pool;
use paslandau\DomUtility\DomConverter;
use paslandau\GuzzleApplicationCacheSubscriber\ApplicationCacheSubscriber;
use paslandau\GuzzleApplicationCacheSubscriber\CacheStorage;
use paslandau\IOUtility\IOUtil;
use paslandau\XmlSitemaps\Logging\LoggerTrait;

class SitemapManager
{
    use LoggerTrait;

    const GUZZLE_CONFIG_KEY_CACHE_RESPONSE = "xml_sitemaps.cache_response";
    const GUZZLE_CONFIG_KEY_RETRY_COUNT = "xml_sitemaps.retry_count";
    const GUZZLE_CONFIG_KEY_REQUEST_ID = "xml_sitemaps.id";

    /**
     * @var Client
     */
    private $client;

    /**
     * @var DomConverter
     */
    private $domConverter;

    /**
     * @var bool
     */
    private $cacheIsActive;

    /**
     * @param Client $client
     */
    function __construct(Client $client)
    {
        $this->client = $client;
        $this->domConverter = new DomConverter(DomConverter::XML);
    }

    /**
     * @param string $cacheDir. Path to directory to use as caching folder
     * @param string $forceFresh [optional]. Default: null. List of URLs that should not be retrieved from cache.
     */
    public function activateCache($cacheDir, $forceFresh = null)
    {
        if ($forceFresh === null) {
            $forceFresh = [];
        }
        if ($this->cacheIsActive !== true) {
            $canCache = function (EndEvent $event) {
                $can = $event->getRequest()->getConfig()->get(self::GUZZLE_CONFIG_KEY_CACHE_RESPONSE);
                return $can !== null;
            };
            $mustRequestFresh = function (BeforeEvent $event) use ($forceFresh) {
                $url = $event->getRequest()->getUrl();
                return (array_key_exists($url, $forceFresh));
            };
            IOUtil::createDirectoryIfNotExists($cacheDir);
            $cacheDriver = new FilesystemCache($cacheDir);
            $cache = new CacheStorage($cacheDriver);
            $cacheSub = new ApplicationCacheSubscriber($cache, $canCache, $mustRequestFresh);
            $this->client->getEmitter()->attach($cacheSub);
            $this->cacheIsActive = true;
        }
    }

    /**
     * @param $url
     * @param bool $includeChildSitemaps [optional]. Default: null(true).
     * @param int $maxRetries [optional]. Default: null(3).
     * @return null|IndexSitemap
     */
    public function fetchIndexSitemap($url, $includeChildSitemaps = null, $maxRetries = null)
    {
        $sitemaps = $this->fetchIndexSitemaps([$url => $url], $includeChildSitemaps, $maxRetries);
        if (count($sitemaps) == 0) {
            return null;
        }
        return reset($sitemaps);
    }

    /**
     * @param string [] $urls
     * @param bool $includeChildSitemaps [optional]. Default: null(true). If true, all referenced child sitemaps will be fetched as well
     * @param null $maxRetries
     * @param null $parallelRequests
     * @return IndexSitemap[]
     */
    public function fetchIndexSitemaps($urls, $includeChildSitemaps = null, $maxRetries = null, $parallelRequests = null)
    {

        if ($includeChildSitemaps === null) {
            $includeChildSitemaps = true;
        }
        $successFn = function (RequestInterface $request, ResponseInterface $response) {
            $body = $this->getDecompressedBody($response);
            return $this->parseIndexSitemapContent($body, $request->getUrl());
        };
        $results = $this->get($urls, $successFn, $maxRetries, $parallelRequests);
        $indexSitemaps = [];
        foreach ($results as $key => $result) {
            if ($result instanceof Exception) {
                unset($results[$key]); // todo handle errors more elegantly? Pass the back to the caller via class property?
            } else {
                $indexSitemaps[$key] = $result["sitemap"];
            }
        }
        if ($includeChildSitemaps) {
            /**
             * @var  $key
             * @var IndexSitemap $sitemap
             */
            foreach ($indexSitemaps as $key => $sitemap) {
                $urls = $results[$key]["urls"];
                $sitemaps = $this->fetchSitemaps($urls);
                $sitemap->setSitemaps($sitemaps);
            }

        }
        return $indexSitemaps;
    }

    /**
     * @param $url
     * @param int $maxRetries [optional]. Default: null(3).
     * @return null|Sitemap
     */
    public function fetchSitemap($url, $maxRetries = null)
    {
        $sitemaps = $this->fetchSitemaps([$url => $url], $maxRetries);
        if (count($sitemaps) == 0) {
            return null;
        }
        return reset($sitemaps);
    }

    /**
     * @param array $urls
     * @param null $maxRetries
     * @param null $parallelRequests
     * @return Sitemap[]
     */
    public function fetchSitemaps(array $urls, $maxRetries = null, $parallelRequests = null)
    {
        $successFn = function (RequestInterface $request, ResponseInterface $response) {
            $body = $this->getDecompressedBody($response);
            return $this->parseSitemapContent($body, $request->getUrl());
        };
        $sitemaps = $this->get($urls, $successFn, $maxRetries, $parallelRequests);
        foreach ($sitemaps as $key => $result) {
            if ($result instanceof Exception) {
                unset($sitemaps[$key]); // todo handle errors more elegantly? Pass the back to the caller via class property?
            }
        }
        return $sitemaps;
    }

    /**
     * @param string[] $pathsToFile
     * @param string $encoding
     * @return Sitemap[]
     */
    public function fetchSitemapsFromPath($pathsToFile, $encoding = null){
        $sitemaps = [];
        foreach($pathsToFile as $path){
            $sitemaps[$path] = $this->fetchSitemapFromPath($path,$encoding);
        }
        return $sitemaps;
    }

    /**
     * @param string $pathToFile
     * @param string $encoding
     * @return Sitemap
     */
    public function fetchSitemapFromPath($pathToFile, $encoding = null){
        $content = IOUtil::getFileContent($pathToFile, $encoding);
        $sitemap = $this->parseSitemapContent($content, $pathToFile);
        return $sitemap;
    }

    /**
     * @param ResponseInterface $response
     * @return string
     */
    private function getDecompressedBody(ResponseInterface $response)
    {
        $body = $response->getBody();
        if ($response->hasHeader("content-type") && stripos($response->getHeader("content-type"), "gzip") !== false) {
            $body = gzdecode($body);
        }
        return $body;
    }

    /**
     * @param string $content
     * @param string $url
     * @return Sitemap
     */
    private function parseSitemapContent($content, $url){
        $doc = $this->domConverter->convert($content);
        $xpath = SitemapNamespaceUtil::getDOMXpathWithNamespaces($doc);
        $ns = SitemapNamespaceUtil::SITEMAP;
        $expression = "//$ns:urlset/$ns:url";
        $urlNodes = $xpath->query($expression);
        $sus = [];
        $sitemap = new Sitemap($url);
        foreach ($urlNodes as $node) {
            $su = new SitemapURL ($sitemap);
            $su->FillFromXmlNode($node);
            $sus [] = $su;
        }
        $sitemap->setUrls($sus);
        return $sitemap;
    }

    /**
     * @param string $content
     * @param string $url
     * @return mixed[]. An array like this: ["sitemap" => IndexSitemap, "urls" => string[]]
     */
    private function parseIndexSitemapContent($content, $url){
        $doc = $this->domConverter->convert($content);
        $xpath = SitemapNamespaceUtil::getDOMXpathWithNamespaces($doc);
        $ns = SitemapNamespaceUtil::SITEMAP;
        $expression = "//$ns:sitemapindex/$ns:sitemap/$ns:loc";
        $urlNodes = $xpath->query($expression);
        $urls = [];
        foreach ($urlNodes as $node) {
            $su = trim($node->nodeValue);
            $urls[$su] = $su;
        }
        $index = new IndexSitemap($url);
        return ["sitemap" => $index, "urls" => $urls];
    }

    /**
     * @param string[] $urls
     * @param callable $successFn . Callable that should follow the signature $successFn = function (RequestInterface $request, ResponseInterface $response){ ... }
     * @param int $maxRetries [optional]. Default: null (3).
     * @param int $parallelRequests [optional]. Default: (20).
     * @return mixed[] - Failed requests will be returned as Exception so make sure to check for that! Otherwise the returned value depends on $successFn
     */
    private function get($urls, callable $successFn, $maxRetries = null, $parallelRequests = null)
    {
        if ($maxRetries === null) {
            $maxRetries = 3;
        }

        if ($parallelRequests === null) {
            $parallelRequests = 20;
        }
        $result = [];
        // Prepare requests
        $requests = [];
        foreach ($urls as $id => $url) {
            $req = $this->client->createRequest("GET", $url);
            $req->getConfig()->set(self::GUZZLE_CONFIG_KEY_REQUEST_ID, $id);
            $requests[$id] = $req;
        }
        $this->getLogger()->info("Requesting a total of " . count($requests) . "\n");

// successful request
        $completeFn = function (RequestInterface $request, ResponseInterface $response) use (&$result, $successFn) {
            $id = $request->getConfig()->get(self::GUZZLE_CONFIG_KEY_REQUEST_ID);
            $body = $response->getBody();
            $cached = $request->getConfig()->get(ApplicationCacheSubscriber::CACHED_RESPONSE_KEY);
            if ($cached) {
                $this->getLogger()->info("[from cache] ");
            }
            if ($response->getStatusCode() == 200 && $body !== null) {
                $this->getLogger()->info("Success on {$request->getUrl()} [ID: {$id}]\n");
                $result[$id] = $successFn($request, $response);
                $request->getConfig()->set(self::GUZZLE_CONFIG_KEY_CACHE_RESPONSE, true); // cache it
            } else {
                $bodyNull = $body === null ? "is null" : "is not null";
                $msg = "Failed on {$request->getUrl()} [StatusCode: {$response->getStatusCode()}; Body: body {$bodyNull}; ID: {$id}]";
                $this->getLogger()->error($msg);
                $result[$id] = new Exception($msg);
                $request->getConfig()->set(self::GUZZLE_CONFIG_KEY_CACHE_RESPONSE, true); // cache it
            }
        };

// failed request
        $errorFn = function (RequestInterface $request, Exception $exception, ErrorEvent $event) use (&$result, $maxRetries) {
            $id = $request->getConfig()->get(self::GUZZLE_CONFIG_KEY_REQUEST_ID);
            $this->getLogger()->error("Failed with on {$request->getUrl()}  [ID: {$id}] request: " . $exception->getMessage() . "\n");
            $curRetries = $request->getConfig()->get(self::GUZZLE_CONFIG_KEY_RETRY_COUNT);
            if ($curRetries === null) {
                $curRetries = 0;
            }
            if ($curRetries < $maxRetries) {
                $curRetries++;
                $this->getLogger()->info("Retring {$request->getUrl()} [ID: {$id}] ($curRetries)\n");
                $request->getConfig()->set(self::GUZZLE_CONFIG_KEY_RETRY_COUNT, $curRetries);
                $event->retry();
            } else {
                $msg = "Giving up on {$request->getUrl()} after $maxRetries retries";
                $this->getLogger()->error($msg);
                $result[$id] = $exception;
            }
        };

        $total = count($requests);
        $infoFn = function () use (&$result, $total) {
            if (count($result) % 50 !== 0) {
                return "";
            }
            return "Crawled " . count($result) . " of $total \n";
        };

        $f = function (AbstractTransferEvent $event) use ($completeFn, $errorFn, $infoFn) {
            $request = $event->getRequest();
            $response = $event->getResponse();
            $exception = null;
            if ($event instanceof ErrorEvent) {
                $exception = $event->getException();
            }
            if ($exception !== null) {
                $errorFn($request, $exception, $event);
            } else {
                $completeFn($request, $response);
            }
            $this->logger->debug($infoFn());
        };

        $pool = new Pool($this->client, $requests, [
            "pool_size" => $parallelRequests,
            "complete" => $f,
            "error" => $f,
        ]);
        $pool->wait();
        return $result;
    }
}

?>