<?php
/***
 * there's no attempt at efficiency here, this is just a step by step, 
 * part by part analysis and extraction (mostly into usable arrays) of the components 
 * of an ebook (meta, book metadata, chapter list, etc)
***/

$bookurl = "https://www.gutenberg.org/files/863/863-0.txt"; // test is Agatha Christie's "The Mysterious Affair at Styles" 


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
print_r($delims);


// find & record book metadata by first finding all lines with semis, then processing those below line count of start of book (from delims)

$semis=[]; $x=0;
foreach($textinlines as $lc=>$line){
	if(strstr($line, ":")){
		$semis[$x]['line']=$lc;
		$semis[$x++]['content']=$line;
	}
}
$meta=[]; $x=0;
foreach($semis as $semi){
	if($semi['line'] < $delims[0]){
		$metas[$x]['line']=$semi['line'];
		$metas[$x++]['content']=$semi['content'];
	}
}
// dump out book meta data
print_r($metas);


// find and record the entirety of the 'Contents' block for book
$contents=[]; $x=0; $started=0; $newline=0;
foreach($textinlines as $lc=>$line){
	if(strstr($line, "Contents")){
		$contents[$x]['line']=$lc;
		$contents[$x++]['content']=$line;
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
			$contents[$x++]['content']=$line;
		}
	}
$previousline = $line;
}
//print_r($contents);

// find and store into array chapter numbers and titles
$tarray=[]; $chapters=[]; $x=0;
foreach($contents as $line){
	if(!strstr($line['content'], 'Contents')){  //provided its not the header
		$tarray = explode('.',$line['content']);
		$chapters[$x]['number']=$tarray[0];
		$chapters[$x++]['title']=$tarray[1];
	}

}
// dump chapters array to output
print_r($chapters);

// dump entire processed lines representation into a 'debug file'
file_put_contents("debugoutput.txt",tocodedtext($textinlines));







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

