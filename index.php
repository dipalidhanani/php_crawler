<?php 
/* Create "Crawler" Class and define all crawler methods in it. */
class crawler
{
    protected $_url;
    protected $_host;
    protected $_useHttpAuth = false;

    public function __construct($url, $pages = 5)
    {
        $this->_url = $url;
        $this->_pages = $pages;
        $parse = parse_url($url);
        $this->_host = $parse['host'];
    }
	/* Method to process/crawl all Images from a web page.  */
    public function _processImages($content, $url)
    {
        $dom = new DOMDocument('1.0');
        @$dom->loadHTML($content);
        $allimages = $dom->getElementsByTagName('img');
		
		$u_images_stack = array();
				
        foreach ($allimages as $element) {
            array_push($u_images_stack,$element->getAttribute('data-src'));
        }
		$unique_images = array_unique($u_images_stack);
		$total_images = count($unique_images);
		/* Returns Unique Total Images on webpage. */
		return $total_images;
       
    }
	/* Method to process/crawl all internal/external Links from a web page.  */
    public function _processAnchors($content, $url, $type)
    {
        $dom = new DOMDocument('1.0');
        @$dom->loadHTML($content);
        $allAnchors = $dom->getElementsByTagName('a');
		
		$u_internal_anchor_stack = array();
		$u_external_anchor_stack = array();
		$returnArr = array();		
        foreach ($allAnchors as $element) {
			$href = $element->getAttribute('href');
			if((substr($href, 0, 1) == '/') || (substr($href, 0, 1) == '#')){
				  array_push($u_internal_anchor_stack,$element->getAttribute('href'));
			}
			else{
				array_push($u_external_anchor_stack,$element->getAttribute('href'));
			}
          
        }
		$unique_internal_anchors = array_unique($u_internal_anchor_stack);
		$unique_external_anchors = array_unique($u_external_anchor_stack);
		
		/* Returns Unique Total Internal/External Links on webpage. */
		
		if($type == 'internal'){return count($unique_internal_anchors);}
		elseif($type == 'external'){return count($unique_external_anchors);}
			
    }
	/* Method to process/crawl all Words from a web page.  */
    public function _processWords($content, $url)
    {
        $dom = new DOMDocument('1.0');
        @$dom->loadHTML($content);
		$alldiv = $dom->getElementsByTagName('div');
		$divlength = 0;
        foreach ($alldiv as $div) {
				$divCount = str_word_count(trim($div->nodeValue));
				$divlength += $divCount;			
        }
		/* Returns Words Length (all divs which includes p,span,anchor,etc tags) on webpage. */
		return $divlength;
    }
	/* Method to process/crawl all Title tags from a web page.  */
    public function _processTitle($content, $url)
    {
        $dom = new DOMDocument('1.0');
        @$dom->loadHTML($content);
        $title_tag = $dom->getElementsByTagName('title');
        foreach ($title_tag as $title) {
			$title_length = strlen(trim($title->nodeValue));
        }
		/* Returns Title Length */
		return $title_length;
    }
	/* Method to process/crawl all Content regarding HTTP code, Page load time and Response from a web page.  */
    public function _getContent($url)
    {
        $handle = curl_init($url);
        // return the content
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, TRUE);

        /* Get the HTML or whatever is linked in $url. */
        $response = curl_exec($handle);
        // response total time
        $time = curl_getinfo($handle, CURLINFO_TOTAL_TIME);
        /* Check for 404 (file not found). */
        $httpCode = curl_getinfo($handle, CURLINFO_HTTP_CODE);

        curl_close($handle);
        return array($response, $httpCode, $time);
    }
	
	/* Method to check url of a web page is valid or not.  */
    public function isValid($url)
    {
        if (strpos($url, $this->_host) === false)
        {
            return false;
        }     
        return true;
    }
	/* Method to print result of _getContent method for table.  */
    public function crawl_url_table($url)
    {
        if (!$this->isValid($url)) {
            return;
        }		   
        // get Content and Return Code
        list($content, $httpcode, $time) = $this->_getContent($url);
        // print Result for current Page
        echo "<tr><td>".$url."</td><td>".$httpcode."</td><td>".$time."</tr></tr>";  
    }
}

// USAGE
/* Crawled Pages array. */
$arrPages = array('https://agencyanalytics.com',
				'https://agencyanalytics.com/integrations',
				'https://agencyanalytics.com/features',
				'https://agencyanalytics.com/pricing',
				'https://agencyanalytics.com/company/about'
			   );
			  
$totalPages = count($arrPages);  /* Total Crawled Pages. */
echo "<h2>Crawled Pages with HTTP Status Code:</h2>";
/* A table that shows each page I've crawled and the HTTP status code with page load Time */
echo "<table border='1'><tr><td><b>Pages</b></td><td><b>HTTP Status Code</b></td><td><b>Total Time to Load Page</b></td></tr>";
foreach($arrPages as $pageURL){
	$crawler = new crawler($pageURL);
	$crawler->crawl_url_table($pageURL);
}
echo "</table>";

$arrList = array();   /* Initialize crawled info array */
$arrList['total_pages'] = $totalPages;  /* Push Total pages in crawled info array */

$totalImg = 0;
$totalInternalLinks = 0;
$totalExternalLinks = 0;
$totalPageLoad = 0;
$totalWords = 0;
$totalTitlelen = 0;
foreach($arrPages as $pageCrawler){
	$crawler1 = new crawler($pageCrawler);  /* Create object for crawler class to access all methods and properties. */
	list($content, $httpcode, $time) = $crawler1->_getContent($pageCrawler);  /* To retrieve $content. */
	
    $totalImg += $crawler1->_processImages($content, $pageCrawler);  /* Total unique images */
  	$arrList['total_imgs'] = $totalImg;
	
	$totalInternalLinks += $crawler1->_processAnchors($content, $pageCrawler, 'internal');  /* Total unique internal links */
	$arrList['total_internal_links'] = $totalInternalLinks;
	
	$totalExternalLinks += $crawler1->_processAnchors($content, $pageCrawler, 'external');  /* Total unique external links */
	$arrList['total_external_links'] = $totalExternalLinks;
	
	$totalPageLoad += $time;  /* Total page load time */
	$arrList['avg_page_load'] = ($totalPageLoad/$totalPages); /* Average Page load time of all pages */
	
	$totalWords += $crawler1->_processWords($content, $pageCrawler);  /* Total Words */
	$arrList['avg_word_count'] = round($totalWords/$totalPages);  /* Average Word Count of all pages */
	
	$totalTitlelen += $crawler1->_processTitle($content, $pageCrawler);  /* Total Title length */
	$arrList['avg_title_len'] = round($totalTitlelen/$totalPages);    /* Average Title tag length of all pages */

}

/*  Print all web crawler information from all web pages  */

echo "<h2>Web Crawler Information</h2>";
echo "Number of pages crawled :             ".$arrList['total_pages'];
echo "<br>Number of a unique images :       ".$arrList['total_imgs'];
echo "<br>Number of unique internal links : ".$arrList['total_internal_links'];
echo "<br>Number of unique external links : ".$arrList['total_external_links'];
echo "<br>Average page load in seconds :    ".$arrList['avg_page_load'];
echo "<br>Average word count :              ".$arrList['avg_word_count'];
echo "<br>Average title length :            ".$arrList['avg_title_len'];
	
//print_r($arrList);
 ?>