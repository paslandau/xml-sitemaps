<?php
namespace paslandau\XmlSitemaps;

use paslandau\DomUtility\DomUtil;

class SitemapURL{
	/**
	 * The Sitemap this url belongs to.
	 * @var Sitemap
	 */
	private $parent;
	
	/**
	 * URL
	 * @var String
	 */
	private $loc;
	
	/**
	 * 
	 * @var String
	 */
	private $lastmod;
	/**
	 * 
	 * @var String
	 */
	private $changefreq;
	/**
	 * Value between 0 and 1, e.g. 0.5
	 * @var String
	 */
	private $priority;
	
	/**
	 * 
	 * @var SitemapImage[]
	 */
	private $images;
	
	/**
	 *
	 * @var SitemapXHTMLLink[]
	 */
	private $xhtmlLinks;

    /**
     * @param Sitemap $parent
     */
	public function __construct(Sitemap $parent = null){
		$this->parent = $parent;
		$this->images = array();
		$this->xhtmlLinks = array();
	}

    /**
     * @param \DomNode $node
     */
	public function fillFromXmlNode(\DomNode $node){
		$reflectionClass = new \ReflectionClass(SitemapURL::class);
		$flatElements = array("loc","lastmod","changefreq","priority");
		$imageNs = SitemapNamespaceUtil::IMAGE;
		$imageQuery = "./$imageNs:image";
		$xhtmlLinkNs = SitemapNamespaceUtil::XHTML;
		$xhtmlLinkQuery = "./$xhtmlLinkNs:link";
		
		$doc = $node->ownerDocument;
		$definedNamespaces = SitemapNamespaceUtil::getValidNamespaces($doc);
		$xpath = SitemapNamespaceUtil::getDOMXpathWithNamespaces($doc);
		$ns = SitemapNamespaceUtil::SITEMAP;
		foreach($flatElements as $element){
			$query = "./$ns:$element";
            $value = "";
            if (DomUtil::elementExists($xpath, $query, $node)) {
                $value = DomUtil::getText($xpath, $query, $node);
            }
            $prop = $reflectionClass->getProperty($element);
            $prop->setAccessible(true);
            $prop->setValue($this, $value);
		}
		if(array_key_exists(SitemapNamespaceUtil::XHTML, $definedNamespaces)){
			$xhtmlLinkNodes = $xpath->query($xhtmlLinkQuery, $node);
			foreach($xhtmlLinkNodes as $xhtmlLinkNode){
				$xhtmlLink = new SitemapXHTMLLink($this);
				$xhtmlLink->FillFromXmlNode($xhtmlLinkNode);
				$this->xhtmlLinks[] = $xhtmlLink;
			}
		}
		if(array_key_exists(SitemapNamespaceUtil::IMAGE, $definedNamespaces)){
			$imageNodes = $xpath->query($imageQuery, $node);
			foreach($imageNodes as $imageNode){
				$image = new SitemapImage($this);
				$image->FillFromXmlNode($imageNode);
				$this->images[] = $image;
			}
		}
	}
	
	public function __toString(){
		$s = "{$this->loc} - {$this->lastmod} - {$this->changefreq} - {$this->priority}";
		if(count($this->images)){
			$s.= "\n";
			foreach($this->images as $image){
				$s.= "  $image\n";
			}
		}
		return $s;
	}

    /**
     * @return Sitemap
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
    public function getLastmod()
    {
        return $this->lastmod;
    }

    /**
     * @return String
     */
    public function getChangefreq()
    {
        return $this->changefreq;
    }

    /**
     * @return String
     */
    public function getPriority()
    {
        return $this->priority;
    }

    /**
     * @return SitemapImage[]
     */
    public function getImages()
    {
        return $this->images;
    }

    /**
     * @return SitemapXHTMLLink[]
     */
    public function getXhtmlLinks()
    {
        return $this->xhtmlLinks;
    }
}
?>