<?php
namespace paslandau\XmlSitemaps;

use paslandau\DomUtility\DomUtil;

/**
 *
 * @see http://support.google.com/webmasters/bin/answer.py?hl=de&answer=178636
 * @author Hirnhamster
 *
 */
class SitemapImage
{
    /**
     * The SitemapUrl this image belongs to.
     * @var SitemapURL
     */
    private $parent;
    /**
     * URL of image.
     * Element is required!
     * @var String
     */
    private $loc;

    /**
     * Caption of image.
     * Element is optional!
     * @var String
     */
    private $caption;

    /**
     * Geo location of the image, e.g. <image:geo_location>Limerick, Irland</image:geo_location>
     * Element is optional!
     * @var String
     */
    private $geo_location;

    /**
     * Title of the image.
     * Element is optional!
     * @var String
     */
    private $title;

    /**
     * URL to the license of the image.
     * Element is optional!
     * @var String
     */
    private $license;

    public function __construct(SitemapUrl $parent = null)
    {
        $this->parent = $parent;
    }

    /**
     * @param \DomNode $node
     */
    public function fillFromXmlNode(\DomNode $node)
    {
        $reflectionClass = new \ReflectionClass(SitemapImage::class);
        $flatElements = array("loc", "caption", "geo_location", "title", "license");
        $doc = $node->ownerDocument;
        $xpath = SitemapNamespaceUtil::getDOMXpathWithNamespaces($doc);
        $ns = SitemapNamespaceUtil::IMAGE;
        foreach ($flatElements as $element) {
            $query = "./$ns:$element";
            $value = "";
            if (DomUtil::elementExists($xpath, $query, $node)) {
                $value = DomUtil::getText($xpath, $query, $node);
            }
            $prop = $reflectionClass->getProperty($element);
            $prop->setAccessible(true);
            $prop->setValue($this, $value);
        }
    }

    public function __toString()
    {
        $s = "{$this->loc} - {$this->caption} - {$this->geo_location} - {$this->title} - {$this->license}";
        return $s;
    }

    /**
     * @return SitemapURL
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * @return String
     */
    public function getLoc()
    {
        return $this->loc;
    }

    /**
     * @return String
     */
    public function getCaption()
    {
        return $this->caption;
    }

    /**
     * @return String
     */
    public function getGeoLocation()
    {
        return $this->geo_location;
    }

    /**
     * @return String
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @return String
     */
    public function getLicense()
    {
        return $this->license;
    }
}

?>