<?php
namespace paslandau\XmlSitemaps;

use paslandau\DomUtility\DomUtil;

class SitemapXHTMLLink{
	/**
	 * The SitemapUrl this Link belongs to.
	 * @var SitemapURL
	 */
	private $parent;
	/**
	 * href Attribute
	 * Element is required!
	 * @var String
	 */
	private $href;
	
	/**
	 * rel Attribute
	 * Element is required!
	 * @var String
	 */
	private $rel;
	
	/**
	 * media Attribute
	 * Element is optional!
	 * @var String
	 */
	private $media;
	
	/**
	 * Element is optional!
	 * @var String
	 */
	private $hreflang;
	
	/**
	 * Element is optional!
	 * @var String
	 */
	private $hreflang_x;

    /**
     * @param SitemapURL $parent
     */
	public function __construct(SitemapUrl $parent = null){
		$this->parent = $parent;
	}

    /**
     * @param \DomNode $node
     */
	public function fillFromXmlNode (\DomNode $node){
		$reflectionClass = new \ReflectionClass(SitemapXHTMLLink::class);
		$flatElements = array("@href"=>"href","@rel"=>"rel","@media"=>"media","@hreflang" => "hreflang","@hreflang-x" => "hreflang_x");
		$doc = $node->ownerDocument;
		$xpath = SitemapNamespaceUtil::getDOMXpathWithNamespaces($doc);
		$ns = SitemapNamespaceUtil::XHTML;
		foreach($flatElements as $element => $property){
			$query = "./$element";
            $value = "";
            if (DomUtil::elementExists($xpath, $query, $node)) {
                $value = DomUtil::getText($xpath, $query, $node);
            }
            $prop = $reflectionClass->getProperty($property);
            $prop->setAccessible(true);
            $prop->setValue($this, $value);
		}
	}
	
	public function __toString(){
		return "{$this->href} {$this->rel}";
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
    public function getHref()
    {
        return $this->href;
    }

    /**
     * @return String
     */
    public function getRel()
    {
        return $this->rel;
    }

    /**
     * @return String
     */
    public function getMedia()
    {
        return $this->media;
    }

    /**
     * @return String
     */
    public function getHreflang()
    {
        return $this->hreflang;
    }

    /**
     * @return String
     */
    public function getHreflangX()
    {
        return $this->hreflang_x;
    }
}