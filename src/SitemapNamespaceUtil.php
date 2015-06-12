<?php
namespace paslandau\XmlSitemaps;
use paslandau\DomUtility\DomUtil;

class SitemapNamespaceUtil
{
    const SITEMAP = "sitemap";
    const IMAGE = "image";
    const VIDEO = "video";
    const MOBILE = "mobile";
    const NEWS = "news";
    const XHTML = "xhtml";

    /**
     * @see http://stackoverflow.com/questions/693691/php-how-to-initialize-static-variables
     * @var string[]
     */
    private static $allowedNamespaces;

    /**
     * Initializes the self::allowedNamespaces.
     * Required due to @see http://stackoverflow.com/questions/693691/php-how-to-initialize-static-variables
     * @param array $arr
     * @internal param \string[] $array
     */
    public static function init(array $arr)
    {
        self::$allowedNamespaces = $arr;
    }

    /**
     * Returns an \DOMXPath Object that has all needed namespaces registered.
     * The allowed namespace URIs are defined in @see http://support.google.com/webmasters/bin/answer.py?hl=de&answer=183668#2
     * The prefixes can be retrieved from the constants of this class. They must be used when performing a query on the \DOMXpath Object!
     * @param \DOMDocument $doc
     * @return \DOMXPath
     */
    public static function getDOMXpathWithNamespaces(\DOMDocument $doc)
    {
        $xpath = new \DOMXPath($doc);
        $ns = self::getValidNamespaces($doc);
        foreach ($ns as $prefix => $ans) {
            $xpath->registerNamespace($prefix, $ans);
        }
        return $xpath;
    }

    /**
     * Returns an array that has all namespaces that are defined in self::allowedNamespaces AND in the document.
     * The allowed namespace URIs are defined in @see http://support.google.com/webmasters/bin/answer.py?hl=de&answer=183668#2
     * @param \DOMDocument $doc
     * @return string[] - array of all namespaces in the given $doc. E.g. array(self::IMAGE => "http://www.google.com/schemas/sitemap-image/1.1");
     */
    public static function getValidNamespaces(\DOMDocument $doc)
    {
        $docNs = DomUtil::getAllNamespaces($doc);
        $nameSpaces = array();
        foreach ($docNs as $ns) {
            foreach (self::$allowedNamespaces as $prefix => $ans) {
                if (in_array($ns, $ans)) {
                    $nameSpaces[$prefix] = $ns;
                    //TODO what happens if multiple namespaces for the same "type" are defined in the document?
                    break;
                }
            }
        }
        return $nameSpaces;
    }
}

SitemapNamespaceUtil::init(array(
    SitemapNamespaceUtil::SITEMAP => array("http://www.sitemaps.org/schemas/sitemap/0.9", "http://www.google.com/schemas/sitemap/0.9"),
    SitemapNamespaceUtil::IMAGE => array("http://www.google.com/schemas/sitemap-image/1.1"),
    SitemapNamespaceUtil::VIDEO => array("http://www.google.com/schemas/sitemap-video/1.1"),
    SitemapNamespaceUtil::MOBILE => array("http://www.google.com/schemas/sitemap-mobile/1.0"),
    SitemapNamespaceUtil::NEWS => array("http://www.google.com/schemas/sitemap-news/0.9"),
    SitemapNamespaceUtil::XHTML => array("http://www.w3.org/1999/xhtml")
));
?>