<?php

/** 
 * Example program for the phpPatent classes.
 *
 **/

namespace phpPatent;

require_once("class.patent.php");
require_once("class.patentSearch.php");

$s = new PatentSearch();

//$s->addTerm("AN","cheese");  // Searches for all patents with "Sandia" in the Assignee section
$s->addTerm("TTL",'"video games"');  // Searches for all patents with "Sandia" in the Assignee section


$s->performSearch();


print_r($s->searchResults);

