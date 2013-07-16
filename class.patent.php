<?php

/**
 * @author	Steve Cook <booyahmedia@gmail.com>
 * @copyright	2013 Stephen Cook
 * @license 	http://www.gnu.org/licenses/lgpl-3.0.txt GNU LESSER GENERAL PUBLIC LICENSE (LGPL) version 3
 * @link	http://booyahmedia.com/
 * @version 	Release: @package_version@
 * @todo	Split claims into array. Add figure data, split up refs/citations into array
 * 
 * This is the main class for a single patent.
 * 
 **/

namespace phpPatent;

/**
 * @type string This is the url for the single patent at the USPTO.gov site. Patent number is inserted at <PATENT_NUMBER>
 **/
define("PURL","http://patft.uspto.gov/netacgi/nph-Parser?Sect2=PTO1&Sect2=HITOFF&p=1&u=%2Fnetahtml%2FPTO%2Fsearch-bool.html&r=1&f=G&l=50&d=PALL&RefSrch=yes&Query=PN%2F<PATENT_NUMBER>");

/**
 * @type string This is where to store the downloaded patent html file
 **/
define("PATENT_DIR","/tmp/");

/**
 * Primary patent class that stores all infomation about a single patent.
 **/
class Patent {

	public $patentNumber;
	public $intPatentNumber;
	public $title;
	public $issueDate;
	public $issueDateSec;
	public $filingDate;
	public $filedDate;
	public $filedDateSec;
	public $inventors; // inventors
	public $inventorsList; // Array of inventors
	public $assignee; // Array of inventors
	public $familyID; // Array of inventors
	public $applNo; // Array of inventors
	public $appNumber;
	public $currentUSClass;
	public $currentIntClass;
	public $currentCPC;
	public $fieldOfSearch;
	public $refCited; // Array of patent links
	public $refByURL; // link to referenced by list.
	public $txtAbstract;
	public $txtOtherRef;
	public $txtGovInterests;
	public $txtPatentCase;
	public $txtParentCase;
	public $txtClaims;
	public $txtDescription;
	public $applicant;
	public $nameCityStateCountryType;
	public $htmlPage;
	public $rawText;

	private $logging; // 0=no logging/printing => 5=full logging

	private $aTables; // Internal list of table html for parsing;
	
	/**
	 * Class constructor.
	 * @param string|null $patentNumber The integer value for the patent.
	 * @param int $logging Do any logging/printing
	 **/
	public function __construct($patentNumber="",$logging=1) {
		if(strlen("$patentNumber")>0) {
			// In case they left the commas in the string
			$this->patentNumber=intval(str_replace(",","",$patentNumber));
		} else {
			$this->patentNumber=0;
		}
		if($this->patentNumber<1) {
			$this->patentNumber=0;
		}

		// Now set logging level
		$logging=intval($logging);
		$this->logging=($logging<0 || $logging>5)?0:$logging;
	}


	/**
	 * Fetch the patent from the USPTO site and put it into htmlPage
	 **/
	public function fetchPatent() {
		if(intval($this->patentNumber)<1) {
			print("You must include a patent number when fetching a patent.\n");
			print("Fatal Error. Exiting.\n");
			exit();
		}

		// Check to verify cURL is installed with PHP - fatal if not installed
		if(!function_exists('curl_init')) {
			echo "You must have cURL installed with php.\n";
			echo "Fatal Error. Please install curl.\nSee: http://www.php.net/manual/en/curl.installation.php\n";
			exit();
		}


		// create the url using the patent number
		$url=str_replace("<PATENT_NUMBER>",$this->patentNumber,PURL);
	        if($logging>0) print("Fetching Patent $this->patentNumber at $url\n");
        	$ch=curl_init($url);
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_REFERER, "phpPatent class");
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_TIMEOUT, 120); // Lots of time because USPTO can be slow sometimes
	        curl_setopt($ch, CURLOPT_HEADER, 0);
	        $this->htmlPage=curl_exec($ch);
	        $this->rawText=strip_tags($this->htmlPage);
	        curl_close($ch);
	}


	/**
	 * Fully parse the patent html page into its component parts.
	 **/
	public function parse() {
		// verify that htmlPage has be udpated before parsing


		preg_match_all("/<TABLE(.+)\/TABLE>/Uis",$this->htmlPage,$mtab);
		$this->aTables=$mtab;

		// Parse the classes sections.
		$this->parseClasses();
		$this->parsePatentNumber();
		$this->parseIssueDate();
		$this->parseSections();
	}



	private function parseClasses() {
		//Easiest to split the document up by tables 
		foreach($this->aTables[1] as $k=>$v) {
			if(strpos($v,"Class:")>0) {
				$v=str_replace(array("\r\n","\n"),"",$v);
				preg_match_all("/<TR>(.*)<\/TR>/Uis",$v,$cla);
				foreach($cla[1] as $n=>$o) {
					$o=strip_tags($o);
					$tmp=explode(":",$o);
					$tlist=explode(";",$tmp[1]);
					foreach($tlist as $ke=>$va) {
						$tlist[$ke]=trim($va);
					}
					switch($tmp[0]) {
						case "Current U.S. Class":
							$this->currentUSClass=$tlist;
						break;
						case "Current International Class":
							$this->currentIntClass=$tlist;
						break;
						case "Current CPC Class":
							$this->currentCPC=$tlist;
						break;
						case "Field of Search":
							$this->fieldOfSearch=explode(",",$tlist[0]);;
						break;
					}
				}
			}
	
		}
	}

	private function parsePatentNumber() {
		preg_match('/\d,\d\d\d,\d\d\d/',$this->aTables[1][2],$out);
		if(isset($out[0])) {
			$this->patentNumber=$out[0]; // sets it to what it read - WARNING: COULD OVERWRITE CALLED NUMBER!!!
			$this->intPatentNumber=intval(trim(str_replace(",","",$this->patentNumber)));
		}
	}


	private function parseIssueDate() {
		preg_match_all('/<TD ALIGN="RIGHT" WIDTH="50%">(.*)<\/TD>/Uis',$this->aTables[1][2],$out);
		$this->issueDate=(trim(strip_tags($out[1][1])));
		$this->issueDateSec=strtotime($this->issueDate." 12:00:00"); // Sets it to noon - no time given
	}


	private function parseSections() {
		// Split the html document up by sections (marked by the <HR>'s the page has)
		$htab=explode("<HR>",$this->htmlPage);
		// Get the title and the abstract
		foreach($htab as $k=>$v) {
			if(strpos($v,'<CENTER><B>Abstract</B></CENTER>')>0) {
				$tmp=explode("<CENTER><B>Abstract</B></CENTER>",$v);
				$this->title=preg_replace("/\s+/",' ',trim(strip_tags($tmp[0])));
				$this->txtAbstract=preg_replace("/\s+/",' ',trim(strip_tags($tmp[1])));
	
			} else if (preg_match("/<CENTER>.*Government Interests.*<\/CENTER>/",$v)) {
				$new=$k+1;
				$this->txtGovInterests=preg_replace("/\s+/",' ',trim(strip_tags($htab[$new])));
				//$this->txtGovInterests=preg_replace("/\s+/",' ',trim(strip_tags($tmp[1])));
			} else if (preg_match("/<CENTER>.*Claims.*<\/CENTER>/",$v)) {
				$new=$k+1;
				$this->txtClaims=trim($htab[$new]);
			} else if (preg_match("/<CENTER>.*Description.*<\/CENTER>/",$v)) {
				$new=$k+1;
				$this->txtDescription=trim($htab[$new]);
			} else if (preg_match("/<CENTER><B>References Cited.*<\/CENTER>/",$v)) {
				$new=$k+1;
				$tmp=preg_match_all('/<TABLE(.*)<\/TABLE>/Uis',$htab[$new],$m);
				$ta=array();
				preg_match_all('/<TR>\s{0,1}<TD .*>.*href="(.*)".*>(.*)<\/a><\/TD><TD.*>(.*)<\/TD><TD align=left>(.*)<\/TD><\/TR>/Uis',$m[0][0],$mm);
				foreach($mm[1] as $key=>$val) {
					$ta[]=array("Number"=>$mm[2][$key],"URL"=>$mm[1][$key],"IssueDate"=>trim($mm[3][$key]),"Inventor"=>trim($mm[4][$key]));
	
				}
				$this->refCited=$ta;
			} else if (preg_match("/<CENTER>.*Parent Case Text.*<\/CENTER>/",$v)) {
				$new=$k+1;
				$this->txtParentCase=preg_replace("/\s+/",' ',trim(strip_tags($htab[$new])));
			//	print_r($p);
			} else if (strpos($v,">Inventor:</TH>")>0 || strpos($v,">Inventors:</TH>")>0) {
				$tmp=explode("<TR",$v);
				foreach($tmp as $y=>$z) {
					$tmp2=explode("<TD",$z);
					$tmp2[0]=trim(preg_replace("/\s+/",' ',strip_tags("<TD".$tmp2[0])));
					if(isset($tmp2[1])) {
						$tmp2[1]=trim(preg_replace("/\s+/",' ',strip_tags("<TD".$tmp2[1])));
						switch($tmp2[0]) {
							case "Inventors:": 
								$this->inventors=$tmp2[1]; 
								preg_match_all('/(.+)\((.+)\)/Uis',strip_tags($tmp2[1]),$m);
								$this->inventorsList=array();
								foreach($m[1] as $key=>$val) {
									$this->inventorsList[]=array("Name"=>trim($val,", "),"City"=>trim($m[2][$key]));
								}
								
							break;
							case "Applicant:": $this->applicant=$tmp2[1]; break;
							case "Name City State Country Type": $this->nameCityStateCountryType=$tmp2[1]; break;
							case "Assignee:": $this->assignee=$tmp2[1]; break;
							case "Appl. No.:": $this->applNo=$tmp2[1]; break;
							case "Filed:": 
								$this->filedDate=$tmp2[1]; 
								$this->filedDateSec=strtotime($this->filedDate." 12:00:00");
								break;
							case "Family ID:": $this->familyID=$tmp2[1]; break;
						}
					}
				}
			}
		}
	
	}
}

$p=new Patent("5,881,811",1);
$p->fetchPatent();
$p->parse();
print_r($p);
