<?php

namespace paslandau\XmlSitemaps;


use paslandau\IOUtility\IOUtil;

class Sitemap {
    /**
     * @var string
     */
    private $url;

    /**
     * @var SitemapURL[]
     */
    private $urls;

    /**
     * @param string $url
     * @param SitemapURL[] $urls
     */
    function __construct($url, array $urls = null)
    {
        $this->url = $url;
        if($urls === null) {
            $urls = [];
        }
        $this->urls = $urls;
    }

    /**
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * @return SitemapURL[]
     */
    public function getUrls()
    {
        return $this->urls;
    }

    /**
     * @param string $url
     */
    public function setUrl($url)
    {
        $this->url = $url;
    }

    /**
     * @param SitemapURL[] $urls
     */
    public function setUrls($urls)
    {
        $this->urls = $urls;
    }

    /**
     * Gets the URLs as plain strings instead of SitemapUrl objects.
     * The URLs will be unique.
     * @return string[]
     */
    public function getPlainUrls(){
        $urls = [];
        foreach($this->urls as $url){
            $urls[$url->getLoc()] = $url->getLoc();
        }
        return $urls;
    }
}