<?php

/**
 * class.patentClusters.php
 * Examine the output from a k-means clustering run of mahout on a list of patents.
 * You can run this after you run cluster.sh and get output in the two files (TXT and CSV)
 * @copyright 2013, Stephen Cook booyahmedia@gmail.com
 **/

namespace phpPatent;


class patentClusters {

	/**
	 * array of clusters.
	 **/
	public $Cluster;

	/**
	 * Stats for the clusters
	 **/
	public $stats;

	private $txtFile;
	private $csvFile;


	function __construct($tFile, $cFile) {
		if(!file_exists($tFile) || !file_exists($cFile)) {
			print("Both input files must exist.\n");
			exit(); // FATAL ERROR.
		}

		// Read the full text file
		$this->txtFile=file_get_contents($tFile); // If this file is too big, may need to combine with parse
		
		// Read and parse the CSV File
		$row = 0;
		$this->csvFile=array();
		if (($fp=fopen($cFile, "r")) !== FALSE) {
			// read the whole file
			while (($line=fgetcsv($fp, 16384, ",")) !== false) {
				foreach($line as $k=>$v) {
					$line[$k]=trim($v,"/"); // Trim off the leading slashes.
				}
				$this->csvFile[$row]=$line;
				$row++;
			}
		        fclose($fp);
		}
	}

	public function parseClusters() {
		$c=preg_split("/:VL\-/",$this->txtFile);
		print("Parsing ".(count($c)-1)." clusters.\n");
		$this->stats['numDocs']=0; // reset
		$this->stats['numClusters']=0; // reset
		$this->Cluster=array(); // Start with new list
		$cid=0;
		foreach($c as $k=>$v) {
			preg_match('/(\d+){n=(\d+)/',$v,$m);
			if(isset($m[1]) && isset($m[2])) {
				$this->stats['numDocs']+=intval($m[2]);
				$this->stats['numClusters']++;
				$this->Cluster[$cid]=array();
				$this->Cluster[$cid]['ClusterID']=$m[1];
				$this->Cluster[$cid]['ClusterID']=$m[1];
				$this->Cluster[$cid]['numDocs']=intval($m[2]);
				$this->Cluster[$cid]['Terms']=array();
				$tmpterms=preg_match_all("/Top Terms:(.*)Weight :/iUs",$v,$m);
				if(isset($m[1][0])) {
					//print_r($m[1]);
					preg_match_all('/(.*)=>(.*)/',$m[1][0],$out);
					$i=0;
					foreach($out[1] as $tmpid=>$val) {
						$this->Cluster[$cid]['Terms'][$i]['Term']=trim($val);
						$this->Cluster[$cid]['Terms'][$i]['Value']=floatval(trim($out[2][$tmpid]));
						$i++;
					}
					//print_r($this->Cluster);

				}

				$cid++;
			}
		}

		// Get the average (just numDocs/clusters)
		$this->stats['avgDocs']=$this->stats['numDocs']/floatval($this->stats['numClusters']);

		// Update with summaries/stats
		$this->stats['minDocs']=1000000000;
		$this->stats['maxDocs']=-1;
		$aNum=array();
		$aVariance=array();
		foreach($this->Cluster as $k=>$v) {
			$aNum[]=$this->Cluster[$k]['numDocs'];
			$aVariance[]=pow($this->Cluster[$k]['numDocs']-$this->stats['avgDocs'],2);
			// Set percentage
			$this->Cluster[$k]['percDocs']=$this->Cluster[$k]['numDocs']/$this->stats['numDocs'];

			// Set maximum doc count
			if($this->Cluster[$k]['numDocs']>$this->stats['maxDocs']) 
				$this->stats['maxDocs']=$this->Cluster[$k]['numDocs'];
			// Set minimum doc count
			if($this->Cluster[$k]['numDocs']<$this->stats['minDocs']) 
				$this->stats['minDocs']=$this->Cluster[$k]['numDocs'];
		}

		$this->stats['stdDev']=sqrt((array_sum($aVariance))/($this->stats['numClusters']-1.0));
		
		print_r($this->Cluster);
		print_r($this->stats);

	}
}

$pc=new patentClusters("/tmp/cdump.out","/tmp/cdump.csv");
$pc->parseClusters();

