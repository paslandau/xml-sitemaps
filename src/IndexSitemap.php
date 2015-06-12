<?php

namespace paslandau\XmlSitemaps;


use paslandau\IOUtility\IOUtil;
use paslandau\WebUtility\WebUtil;

class IndexSitemap {
    /**
     * @var string
     */
    private $url;

    /**
     * @var Sitemap[]
     */
    private $sitemaps;

    /**
     * @param string $url
     * @param Sitemap[] $urls
     */
    function __construct($url, array $urls = null)
    {
        $this->url = $url;
        if($urls === null) {
            $urls = [];
        }
        $this->sitemaps = $urls;
    }

    /**
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * @return Sitemap[]
     */
    public function getSitemaps()
    {
        return $this->sitemaps;
    }

    /**
     * @param Sitemap[] $sitemaps
     */
    public function setSitemaps($sitemaps)
    {
        $this->sitemaps = $sitemaps;
    }

//    /**
//     * @param string $pathToDir
//     * @param string $fileName [optional]. Default: null(Filename of $this->url).
//     * @param $includeChildSitemaps
//     */
//    public function saveToFile($pathToDir, $fileName = null, $includeChildSitemaps = null){
//        if($includeChildSitemaps === null){
//            $includeChildSitemaps = false;
//        }
//        if($fileName === null){
//            if(preg_match("#^https?://#",$this->url)){ //todo find a better way to identify remote URLs -- something like http://stackoverflow.com/questions/2528415/check-if-the-path-input-is-url-or-local-file maybe?
//                $fileName = WebUtil::getPathFilename($this->url);
//            }else{
//                $fileName = basename($this->url);
//            }
//
//        }
//        $pathToFile = IOUtil::combinePaths($pathToDir,$fileName);
//
//          WRITE XML
//
//        if($includeChildSitemaps === true){
//            foreach($this->getSitemaps() as $sitemap){
//                $sitemap->saveToFile($pathToDir);
//            }
//        }
//    }
}