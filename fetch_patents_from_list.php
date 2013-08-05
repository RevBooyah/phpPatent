<?php

/** 
 * Fetch patents from a list of patents. Uses default file locations and writes files in the same location.
 *
 **/

namespace phpPatent;

require_once("class.patent.php");
require_once("class.patentSearch.php");

$s = new PatentSearch();

// Read from the default location: /tmp/patents.dat
$s->readSearchResults();

// Now to fetch the patent pages and store them
foreach($s->searchResults as $k=>$v) {
	$p=new Patent($v);
	print("Fetching => \t".$p->patentNumber."\n");
	$p->fetchPatent();
	$p->writePatentFile();
}
