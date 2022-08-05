<?php
/***
 * there's no attempt at efficiency here, this is just a step by step, 
 * part by part analysis and extraction (mostly into usable arrays) of the components 
 * of an ebook (meta, book metadata, chapter list, etc)
***/

$version = "1.01";
$opttesting=$optdump=$optbhdump=$optpreproc=false;


echo "Project Gutenberg Book Processor, Test Version (".$version.")\n";
if(count($argv)>1){
	foreach(range(1,count($argv)-1) as $option){
		if(($argv[$option]<=>'-t')==0){
			echo "testing only\n";
			$opttesting = true;
		}
		if(($argv[$option]<=>'-d')==0){
			echo "dump file requested\n";
			$optdump = true;
		}
		if(($argv[$option]<=>'-D')==0){
			echo "dump file + binhex requested\n";
			$optbhdump = true;
		}
		if(($argv[$option]<=>'-p')==0){
			echo "preprocessor invoked\n";
			$optpreproc = true;
		}
	}
}
$bookurl = "https://www.gutenberg.org/files/863/863-0.txt"; // test is Agatha Christie's "The Mysterious Affair at Styles" 
//$bookurl = "gutenberg-863-0.txt"; // test is Agatha Christie's "The Mysterious Affair at Styles" 

//$bookurl ="https://www.gutenberg.org/cache/epub/105/pg105.txt"; // Persuasion by Jane Austin
//$bookurl ="gutenberg-pg105.txt"; // Persuasion by Jane Austin

//$bookurl = "https://www.gutenberg.org/cache/epub/68562/pg68562.txt"; //The peoples of Europe
//$bookurl = "gutenberg-pg68562.txt"; //The peoples of Europe

//$bookurl = "https://www.gutenberg.org/files/98/98-0.txt"; //The peoples of Europe

// get book and character count
$textinfull = file_get_contents($bookurl);
if($textinfull == false){
	echo "ERROR: failed to file_get_contents $bookurl\n";
	exit;
}
$charc = strlen($textinfull);
conwrite("chars:", $charc);

if($optpreproc){
//if(true){
	$textinfull = preg_replace("/'_/","_",$textinfull);
	$textinfull = preg_replace("/_'/","_",$textinfull);
	$textinfull = preg_replace("/_([\w\s-]+)_/m","_[$1]_",$textinfull);
	//preg_replace("/_(.*)\n(.*)_/","_",$textinfull);
}
// put whole book into an array of lines
$textinlines = explode("\n",$textinfull);
$textinfull=''; //release original memory

//output lines count
$linesc=count($textinlines);
conwrite("lines:", $linesc);

// output words count
$wordc=0;
foreach($textinlines as $line){
	$words = explode(" ",$line);
	$wordc += count($words);
}
conwrite("words:",$wordc);

// find & record line numbers for internal top and bottom book markers
$delims=[]; $x=0;
foreach($textinlines as $lc=>$line){
	if(preg_match("/\*\*\*[\w\s]+\*\*\*/",$line)){
		$delims[$x++]=$lc;
	}

}
echo "Delimits lines\n";
print_r($delims);


// find & record book metadata by first finding all lines with semis, then processing those below line count of start of book (from delims)

$semis=[]; $x=0;
foreach($textinlines as $lc=>$line){
	if(strstr($line, ":")){
		$semis[$x]['line']=$lc;
		$semis[$x]['content']=$line;
		$split=explode(':',$line);
		$semis[$x]['field']=$split[0];
		$semis[$x++]['value']=$split[1];
	}
}
$meta=[]; $x=0;
foreach($semis as $semi){
	if($semi['line'] < $delims[0]){
		$metas[$x]['line']=$semi['line'];
		$metas[$x]['content']=$semi['content'];
		$metas[$x]['field']=trim($semi['field']);
		$metas[$x++]['value']=trim($semi['value']);
	}
}
/* dump out book meta data
echo "\nMetadata";
print_r($metas);*/

foreach($metas as $meta){ // get into globals what we need
    if(strstr($meta['field'],'Title')){
    	$title = trim($meta['value']);
    }
    // echo rest
    echo 'meta:field:['.$meta['field'].']:value:['.$meta['value']."]\n";
}



// find and record the entirety of the 'Contents' block for book
$contents=[]; $x=0; $started=0; $newline=0; $cc=0;
foreach($textinlines as $lc=>$line){
	if(preg_match("/^CONTENTS/",strtoupper($line))){
		//$contents[$x]['line']=$lc;
		//$contents[$x++]['content']=$line;
		$started = 1; $newline=0;
	}
	if($started ==1 and (strtohex($line) <=> '[13]')==0){
		$newline++;
	}
	if($started == 1){
		if($newline >= 3) // two newlines in a row
		break;
		if(strstr($line,"CHAPTER")){
			$contents[$x]['line']=$lc;
			$contents[$x]['content']=$line;
			$newline=0;
		}
		if(((strtohex($line)<=>'[13]')!=0)){
			if(preg_match("/^[CcB0-9]/",$line)){ // this is a include we allow for starts with (c)ontents (C)CONTENTS (I)ntroduction (B)ib
				$tarray=explode('.',$line);
				$contents[$x]['startswithnumber']=trim($tarray[0]);
				$contents[$x]['content1st']=trim($line);
				$contents[$x]['content']=trim($line);
				$contents[$x]['line']=$lc;
				$contents[$x]['chapter#']=$cc++;
			}
			else{
				$x--;
				if(isset($contents[$x]['content'])){
					$contents[$x]['content'] =  $contents[$x]['content']. ' '.trim($line);
				}
				else
				{
					$contents[$x]['content1st']=trim($line);
					$contents[$x]['line']=$lc;
				}
				if(!isset($contents[$x]['line'])){
					$contents[$x]['line']=$lc;
				}
			}
			$newline=0;

		}
			$x++;		
	}
$previousline = $line;
}
$endofcontentblock=0;
if(count($contents)>0){
	$endofcontentblock=$contents[count($contents)+1]['line']+1;
}

// if we have a contents block find and further process contents into chapters numbers and titles
if($endofcontentblock > 1){
	$tarray=[]; $chapters=[]; $x=0;
	foreach($contents as $line){
		//if(!preg_match("/^CONTENTS/",strtoupper($line['content1st']))){  //provided its not the header
			$tarray = explode('.',$line['content1st']);
			if(count($tarray)>1){
				$chapters[$x]['number']=$tarray[0];
				$chapters[$x]['titlefull']=trim($line['content']);
				$chapters[$x]['title']=trim($tarray[1]);

			} else {
				$chapters[$x]['number']='unnumbered';
				$chapters[$x]['title']=trim($tarray[0]);
				$chapters[$x]['titlefull']=trim($line['content']);
			}
		//}
		//copy across the rest
		$chapters[$x]['line']=$line['line'];
		$chapters[$x]['content']=$line['content'];
		$chapters[$x]['content1st']=$line['content1st'];
		$chapters[$x]['startswithnumber']=$line['startswithnumber'];
		$chapters[$x]['chapter#']=$line['chapter#'];
		$x++;
	}
	foreach($chapters as $cc=>$chap){
	//echo "\n".$chap['number'];
	$x=0;
		foreach($textinlines as $lc=>$line){
			if(preg_match("/^[IVXivx\.]*".$chap['title']."/",$line) and $lc < $delims[1]){
				$chapters[$cc]['startline'][$x]=$lc;	
				$chapters[$cc]['startlinemode'][$x++]="numeral";	
				$chapters[$cc]['no']=$cc+1;
			}
			elseif(preg_match("/^".$chap['number']."\./",$line) and $lc < $delims[1]){
				$chapters[$cc]['startline'][$x]=$lc;	
				$chapters[$cc]['startlinemode'][$x++]="number";		
				$chapters[$cc]['no']=$cc+1;	
			}
			elseif(preg_match("/".$chap['title']."/",$line) and $lc < $delims[1]){
				$chapters[$cc]['startline'][$x]=$lc;	
				$chapters[$cc]['startlinemode'][$x++]="title";	
				$chapters[$cc]['no']=$cc+1;	
			}

		}	
	}
}




// in the event of no contents block we are looking for chapter marker in the form of:
// 1) Chapter 1

if($endofcontentblock <= 1){ // no contents block
	$endofcontentblock=$delims[0]; // if no contents block set this to start of book
	$chapters=[]; $x=0;
	foreach($textinlines as $lc=>$line){
		if(preg_match("/(^CHAPTER)(.*)/",strtoupper($line),$matches)){
			$chapters[$x]['number']=trim($matches[2]);
			$chapters[$x]['titlefull']=trim($matches[0]);
			$chapters[$x]['title']=trim($matches[0]);
			$chapters[$x]['line']=$lc;
			$chapters[$x]['startline']=$lc;
			// fill out the rest
			$chapters[$x]['content1st']=$line;
			$chapters[$x]['startswithnumber']=$lc;
			$chapters[$x]['chapter#']=$x;
			$chapters[$x]['content']=$line;
			$chapters[$x]['no']=$chapters[$x]['number'];
			$x++;
		}
		
	}

}






// zero out contents block
$contents=[];

echo "\nChapters \n";
print_r($chapters);
echo "EOFCB:".$endofcontentblock."\n";
//exit;

//select the first start line thats greater >$endofcontentblock


// go through and dump contents of each chapter (based on start lines etc.) into a separate file
foreach($chapters as $cc=>$chap){
	//echo "\n".$chap['number']."\n";
	$filename = $cc.'-';
	$filename .= preg_replace("/ /",'',$title);
	$filename .= '-Chapter-'.$chap['no'].'.txt';
	$x=0;
	$ttext='';
	$start=firstvaluegreaterthan($chapters[$cc]['startline'],$endofcontentblock); //assume last occurrence is 'in text' chapter start
	if(!isset($chapters[$cc+1])){
		$end = $delims[1]-1;
	} else {
		$end = firstvaluegreaterthan($chapters[$cc+1]['startline'],$endofcontentblock)-1;
	}
	echo "\nChap:".$cc.' s:'.$start.' e:'.$end;
	//echo "\n";
	if($start >= $end){
		echo "\nskipping (based on start & end numbers)\n";
	} else {
		foreach(range($start,$end) as $lc){
			$ttext .= $textinlines[$lc];
		}
		if($opttesting){
			echo "\n".'test only, would create: '.$filename;
		}else{
			file_put_contents($filename,$ttext);
			echo "\n".$filename." written ..";
		}
	}
	
}


// dump entire processed lines representation into a big 'debug file'
if($optdump){
	$filename=preg_replace("/ /",'',$title)."-debug-coded.txt";
	echo "\ndebug output dumped into ".$filename."\n";
	file_put_contents($filename,tocodedtext($textinlines));
}


function firstvaluegreaterthan($invalue, $testvalue){
	//print_r($inarray);
	$minvalue = $testvalue;
	if(is_array($invalue)){
		foreach($invalue as $value){
			if($value > $testvalue)
				return $value;
			$max = $value;
		}

	} else {
		if($invalue > $testvalue){
		$max = $invalue;
		}else{
		$max = $textvalue;
	}
	}
	if($max < $minvalue){
			return $minvalue;
	}else{
			return $max;
	}
}



/***************/

function conwrite($string, $value){
	echo $string." ".$value."\n";
}


function tocodedtext($arrayin){
	$output='';
	foreach($arrayin as $count=>$line){
		if(strlen($line) <=2){
			//$output = $output.'['.strval($count).'] '.bin2hex($line)."\n";
			//$output = $output.'['.strval($count).'] '.$line."\n";
			$output = $output.'['.strval($count).'] '.strtohex($line)."\n";
		} else {
		$output = $output.'['.strval($count).'] '.$line;
	    }
	}
	return $output;
}


function strtohex($string){
    $hex='';
    foreach((object) $string as $char){
        $hex = $hex.'['.ord($char).']';
    }
    return $hex;
}
?>

