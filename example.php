<?php

/** 
 * Example program for the phpPatent classes.
 *
 **/

namespace phpPatent;

require_once("class.patent.php");
require_once("class.patentSearch.php");

$s = new PatentSearch();

//$s->addTerm("AN","sandia");  // Searches for all patents with "Sandia" in the Assignee section
//$s->addTerm("TTL",'"video games"');  // Searches for all patents with "video games" in the title. NOTE the QUOTES

// These two are a combined search (AND). Gets Sandia assigned patents with inventors in Arizona
$s->addTerm("AN",'sandia');  // Searches for all patents with "Sandia" in the Assignee section
$s->addTerm("IS",'AZ');  // Searches for all patents with Invention State as "AZ"



//$s->performSearch();
//$s->writeSearchResults(); // use the default file location (/tmp/patents.dat)

// if you already saved the search and just want to get the list from the file
$s->readSearchResults();

//print out the list of results if so desired...
print_r($s->searchResults);

// Now to fetch the patent pages and store them
foreach($s->searchResults as $k=>$v) {
	$p=new Patent($v);
	print("$k => \t".$p->patentNumber."\n");

}
