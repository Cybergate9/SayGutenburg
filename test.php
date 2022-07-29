<?php
/***
 * there's no attempt at efficiency here, this is just a step by step, 
 * part by part analysis and extraction (mostly into usable arrays) of the components 
 * of an ebook (meta, book metadata, chapter list, etc)
***/

$version = "1.01";
$opttesting=false;
$optdump=false;

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
	}
}
$bookurl = "https://www.gutenberg.org/files/863/863-0.txt"; // test is Agatha Christie's "The Mysterious Affair at Styles" 
//$bookurl ="https://www.gutenberg.org/cache/epub/105/pg105.txt"; // Persuasion by Jane Austin
//$bookurl = "https://www.gutenberg.org/cache/epub/68562/pg68562.txt"; //The peoples of Europe


// get book and character count
$textinfull = file_get_contents($bookurl);
$charc = strlen($textinfull);
conwrite("chars:", $charc);

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
	if(strstr($line, "***")){
		$delims[$x++]=$lc;
	}

}
echo "\nDelimit lines";
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
			if(preg_match("/^[CcIXVB0-9]/",$line)){ // this is a clude we allow for starts with (c)ontents (C)CONTENTS (I)ntroduction (B)ib Romans (XVI)
				$tarray=explode('.',$line);
				$contents[$x]['startswithnumber']=trim($tarray[0]);
				$contents[$x]['content1st']=trim($line);
				$contents[$x]['content']=trim($line);
				$contents[$x]['line']=$lc;
				$contents[$x]['chapter#']=$cc++;
			}
			else{
				if(isset($contents[$x]['content'])){
					$contents[$x]['content'] .= ' '.trim($line);
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

// dump contents array to output
/*echo "\nContents Block";
print_r($contents);
*/
$endofcontentblock=$contents[count($contents)+1]['line']+1;


// find and further process contents into chapters numbers and titles
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

// zero out contents block
$contents=[];
/* dump chapters array to output
echo "\nChapters I";
print_r($chapters);
*/

foreach($chapters as $cc=>$chap){
	//echo "\n".$chap['number'];
	$x=0;
	foreach($textinlines as $lc=>$line){
		if(preg_match("/^[--9IVXivx_\.]*".$chap['title']."/",$line)){
			$chapters[$cc]['startline'][$x++]=$lc;	
			$chapters[$cc]['no']=$cc+1;
		}
		/*if(strstr($line,$chap['title'])){
			$chapters[$cc]['startline'][$x++]=$lc;	
			$chapters[$cc]['no']=$cc+1;	
		} */
		if(strstr(strtoupper($line),$chap['number'].'.')){
			$chapters[$cc]['startline'][$x++]=$lc;	
			$chapters[$cc]['no']=$cc+1;	
		}

	}
	//$x++;	
}

/*echo "\nChapters II";
print_r($chapters);*/


// so, if we get here and no content block or chapters found
// we'll search through for basic chapter titles with text
// and populated chapters array that way
/*$cc=0;
if(count($chapters) <= 0){ //no contents block
	foreach($textinlines as $lc=>$line){
		$x=0;
		if(strstr($line, "Chapter")){
			$chapters[$cc]['title']=$line;	
			$chapters[$cc]['startline'][$x++]=$lc;
			$chapters[$cc++]['no']=$cc;	
		}
	}
}*/

echo "\nChapters III\n";
print_r($chapters);
echo "EOFCB:".$endofcontentblock."\n";

//select the first start line thats greater >$endofcontentblock

// go through and dump contents of each chapter (based on start lines etc.) into a separate file

foreach($chapters as $cc=>$chap){
	//echo "\n".$chap['number']."\n";
	$filename = preg_replace("/ /",'',$title);
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


// dump entire processed lines representation into a big 'debug file'
if($optdump){
	$filename=preg_replace("/ /",'',$title)."-debug-coded.txt";
	echo "\ndebug output dumped into ".$filename."\n";
	file_put_contents($filename,tocodedtext($textinlines));
}


function firstvaluegreaterthan($inarray, $testvalue){
	//print_r($inarray);
	foreach($inarray as $value){
		if($value > $testvalue)
			return $value;
		$max = $value;
	}
	return $max;
}



/***************/

function conwrite($string, $value){
	echo $string." ".$value."\n";
}


function tocodedtext($arrayin){
	$output='';
	foreach($arrayin as $count=>$line){
		if(strlen($line) <=2){
			$output = $output.'['.strval($count).'] '.strtohex($line)."\n";
		} else {
		$output = $output.'['.strval($count).'] '.$line;
	    }
	}
	return $output;
}


function strtohex($string){
    $hex='';
    foreach((array) $string as $char){
        $hex .= '['.ord($char).']';
    }
    return $hex;
}
?>

