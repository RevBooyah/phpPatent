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
 *  * @type string This is the url for the single patent at the USPTO.gov site. Patent number is inserted at <PATENT_NUMBER>
 *   **/
define("SURL","http://patft.uspto.gov/netacgi/nph-Parser?Sect2=PTO1&Sect2=HITOFF&p=1&u=%2Fnetahtml%2FPTO%2Fsearch-bool.html&r=1&f=G&l=50&d=PALL&RefS
	rch=yes&Query=PN%2F<PATENT_NUMBER>");

/**
 * @type string This is where to store the downloaded patent results file
 **/
define("PATENT_RESULT_DIR","/tmp/");


/**
 * Performs searches and stores list of patent numbers of matching results
 **/
class PatentSearch {

	/**
	 * An array of search modifiers and values
	 **/
	public $searchTerms;

	/** 
	 * List of patent numbers
	 **/
	public $searchResults;

	public $numMatches; // Integer amount of matched patents.

	public function __construct() {
		$this->searchTerms=array();
	}


	public function addTerm($mod,$val) {
		$this->searchTerms[$mod]=$val;
	}

	public function listTerms($mod,$val) {
		foreach($this->searchTerms as $k=>$v) {
			print(" $k => $v\n");
		}
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
