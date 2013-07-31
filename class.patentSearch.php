<?php

/**
 * Patent search class -
 * @author      Steve Cook <booyahmedia@gmail.com>
 * @copyright   2013 Stephen Cook
 * @license     http://www.gnu.org/licenses/lgpl-3.0.txt GNU LESSER GENERAL PUBLIC LICENSE (LGPL) version 3
 * @link        http://booyahmedia.com/
 * @version     Release: @package_version@
 * @todo        
 * 
 **/

namespace phpPatent;

/**
 * @type string This is where to store the downloaded patent results file
 **/
define("PATENT_RESULT_DIR","/tmp/");

/**
 * Performs searches and stores list of patent numbers of matching results
 **/
class PatentSearch {

	/**
	 * The URL to search - with replacement "tags" in place.
	 * "<SEARCH_QUERY> gets replaced with url safe search terms
	 *  Easiest to just create the first page url and parse out the next list
	 */	

	const SURL = 'http://patft.uspto.gov/netacgi/nph-Parser?Sect1=PTO2&Sect2=HITOFF&u=%2Fnetahtml%2FPTO%2Fsearch-adv.htm&r=0&f=S&l=50&d=PTXT&RS=<SEARCH_QUERY>&Refine=Refine+Search&Refine=Refine+Search&Query=<SEARCH_QUERY>';

	const MAX_RESULTS = 2500; // This would be 50 requests for pages.
	const RESULTS_PER_PAGE = 50;  // The number of patents per page on the USPTO.gov search results
	const MAX_PAGES = 50; // The maximum number of pages to retrieve.

	/**
	 * An array of search modifiers and values
	 **/
	public $searchTerms;

	/**
	* The maximum number of patents to return. Default is MAX_RESULTS
	**/
	private $maxResults;
	private $maxPages;

	/** 
	 * List of patent numbers
	 **/
	public $searchResults;

	/**
	 * Raw HTML from current page.
	 **/
	public $curHtml;

	public $numMatches; // Integer amount of matched patents.
	public $totalResults; // Total results as listed by the results page.

	public function __construct() {
		$this->searchTerms=array();
		$this->totalResults=0;
		$this->numMatches=0;
		$this->curHtml='';
		$this->setMaxResults(self::MAX_RESULTS);
	}

	/**
	 * Set a new meximum number of patents to return for the search.
	 **/
	public function setMaxResults($max) {
		$this->maxResults=intval($max);
		// Set the maximum number of pages to fetch.
		$this->maxPages=ceil($this->maxResults/self::RESULTS_PER_PAGE);;
	}

	/**
	 * Get the current value for the maximum number of patents to return
	 **/
	public function getMaxResults() {
		return($this->maxResults);
	}

	/**
	 * Add a search term to the query array.
	 * @parameter string mod The terms - the section to search, title, abstract, etc. Values can be 
	 *
	 * TI,ABTX,ISD,PN,AD,AP,KD, etc. Available in ::listAvailableTerms();
	 * @parameter string val The value to search for.
	 **/
	public function addTerm($mod,$val) {
		// If there are spaces, we need to quote the search term.
		//if(strpos($val," ")!==false) $val='"$val"';
		$this->searchTerms[$mod]=$val;
	}

	public function listTerms($mod,$val) {
		foreach($this->searchTerms as $k=>$v) {
			print(" $k => $v\n");
		}
	}



	public function performSearch() {
		// Verify there are terms.
		if(count($this->searchTerms)<1) {
			echo "Fatal Error. You must include search terms before performing a search.\n";
			exit();
		}


		$totalPages=0; 	// Because we don't know how many results, set loop limit to 0
		$curpage=0; 	// The current page to retrieve.
		$bounceLoop=false;

		$terms=array("<SEARCH_QUERY>");
		// Convert array into valid URL
		$newterms=array($this->setUrlTerms());
		$url=str_replace($terms,$newterms,self::SURL);

		// Search the search loop - loops through each page of results.
		do {
			print("Fetching search page #".($curpage+1)."...\n");
			//print("URL: \n$url\n\n");
			// Fetch the page
			$this->fetchPage($url);

			// If it's the first page, set the total count, and totalpages to fetch
			if($curpage==0) {
				$this->totalResults=$this->fetchTotalCount();
				$this->setMaxResults($this->totalResults); // Set the new maximums
				print("\t Total Matches Found =>\t$this->totalResults\n");
				print("\t Total Search Pages => \t$this->maxPages\n");
			}	
			$curpage++; // increment the current page number.
			
			// Fetch the next URL, if not exists, break loop.
			$url=$this->parseNextUrl();
			if(strlen($url)<10) $bounceLoop=true; // No NEXT LIST, so we're done.

			// Parse the page and add the patents to the list
			if(false==$this->parsePage()) $bounceLoop==true; // Got no results.

				


		} while ($bounceLoop==false && $curpage<self::MAX_PAGES);

		//print_r($this->searchResults);
	}



	/** 
	 * Parse the whole page to get all the patent numbers from the list.
	 **/
	private function parsePage() {
		if(strlen($this->curHtml)<100) return(false);
		// parse out all patent numbers. This regex also puts search URL in results, but we don't use it.
		// NOTE: RE,P, and D are seperate patent types - I don't handle these specially now.
		preg_match_all('!<TD valign=top>.*HREF=(.+)>([REPD\d\,]+)</A></TD>!',$this->curHtml,$m);
		if(isset($m[2]) && count($m[2])>0) {
			foreach($m[2] as $val) {
				$this->searchResults[]=$val; // I leave the commas in.
			}
		} else {
			return(false);
		}
		return(true);
	}


	/**
	 * Get the "NEXT LIST" url from the page - you could gen it,but this gets the correct URL with less possiblity
	 * of an error in generating a url from the parameters.
	 **/
	private function parseNextUrl() {
		preg_match('/HREF=(.*)>.+ALT=\[NEXT_LIST\]/',$this->curHtml,$m);
		if(!isset($m[1])) return("");

		// PHP/cURL converts the URL to have quot/%22 - not what we want...
		$out=str_replace(array("&quot;","%22"),'"',$m[1]);// They don't like &quot;
		return("http://patft.uspto.gov".$out);
	}

	/**
	 * Fetch the page from the USPTO.gov site
	 **/
	private function fetchPage($url) {
		//print("URL: $url\n\n");
		// Fetch the page and set results to curHtml
		$c=curl_init($url);
		curl_setopt($c,CURLOPT_FAILONERROR,true);
		curl_setopt($c,CURLOPT_FOLLOWLOCATION,true);
		curl_setopt($c,CURLOPT_RETURNTRANSFER,true);
		curl_setopt($c,CURLOPT_CONNECTTIMEOUT,120); // Give it a couple minutes - they can be slow
		curl_setopt($c,CURLOPT_URL,$url); 
		
		// Set the output the curHtml
		$this->curHtml=curl_exec($c);
	}

	/**
	 * Covert the search terms into a valid URL GET format.
	 **/
	private function setUrlTerms() {
		$out='';
		$logic=''; // CURRENTLY ONLY DOING "AND" joins for search terms. Could modify easily later.
		foreach($this->searchTerms as $k=>$v) {
			$out.=$logic."$k%2F".urlencode($v);
			$logic="+AND+"; // They like to use + for space instad of %20...
		}
		$out=str_replace(array("&quot;","%22"),'"',$out);// They don't like &quot;
		return($out);
	}


	public function fetchTotalCount() {
		if(strlen($this->curHtml)<10) {
			return(0);
		}
		preg_match("/strong> out of <strong>(\d+)<\/strong>/",$this->curHtml,$m);
		//print_r($m); // $m[1] is the number of total matches
		if(!isset($m[1])) {
			return(0);
		}
		return(intval($m[1]));
	}
	/**
	 * Just list out the known search modifiers
	 **/
	public function listAvailableTerms() {

		echo <<<__TERMS__
The following search fields are available.

	TI => Title
	ABTX => Abstract
	ISD => Issue Date
	PN => Patent Number
	AD => Application Date
	AP => Application Serial Number
	KD => Application Type
	AANM => Applicant Name
	AACI => Applicant City
	AAST => Applicant State
	AACO => Applicant Country
	AAAT => Applicant Type
	ASNM => Assignee Name
	ASCI => Assignee City
	ASST => Assignee State
	ASCO => Assignee Country
	CIPC => International Classification
	CPC => Current CPC Classification
	ORCL => Current US Classification
	XP => Primary Examiner
	XA => Assistant Examiner
	INNM => Inventor Name
	INCI => Inventor City
	INST => Inventor State
	INCO => Inventor Country
	GOTX => Government Interest
	LREP => Attorney or Agent
	PCTA => PCT Information
	PT3D => PCT 371C124 Date
	PTAD => PCT Filing Date
	PRFR => Foreign Priority
	REIS => Reissue Data
	RPAF => Reissued Patent Application Filing Date
	RLAP => Related US App. Data
	RLFD => Related Application Filing Date
	PRAD => Priority Claims Date
	PPPD => Prior Published Document Date
	UREF => Referenced By
	FREF => Foreign References
	OREF => Other References
	ACLM => Claim(s)
	PPDB => Description/Specification
	FMID => Patent Family ID
__TERMS__;
	}



}
