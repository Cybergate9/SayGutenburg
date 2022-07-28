<?php
/***
 * there's no attempt at efficiency here, this is just a step by step, 
 * part by part analysis and extraction (mostly into usable arrays) of the components 
 * of an ebook (meta, book metadata, chapter list, etc)
***/

$bookurl = "https://www.gutenberg.org/files/863/863-0.txt"; // test is Agatha Christie's "The Mysterious Affair at Styles" 
//$bookurl = "https://www.gutenberg.org/cache/epub/105/pg105.txt";
$version = "1.01";

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
// dump out book meta data
echo "\nMetadata";
print_r($metas);

foreach($metas as $meta){ // get into globals what we need
    if(strstr($meta['field'],'Title')){
    	$title = trim($meta['value']);
    }
}



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
//print_r($chapters);

foreach($chapters as $cc=>$chap){
	//echo "\n".$chap['number'];
	$x=0;
	foreach($textinlines as $lc=>$line){
		if(strstr($line,$chap['number'].'.')){
			//echo "\n";
			//print($chap['number'].' at '.$lc.' {'.$line.'}'); 
			$chapters[$cc]['startline'][$x++]=$lc;
			$chapters[$cc]['no']=$cc+1;	
		}
	}
}


// so, if we get here and no content block or chapters found
// we'll search through for basic chapter titles with text
// and populated chapters array that way
$cc=0;
if(count($chapters) <= 0){ //no contents block
	foreach($textinlines as $lc=>$line){
		$x=0;
		if(strstr($line, "Chapter")){
			$chapters[$cc]['title']=$line;	
			$chapters[$cc]['startline'][$x++]=$lc;
			$chapters[$cc++]['no']=$cc;	
		}
	}
}


echo "\nChapters";
print_r($chapters);

// go through and dump contents of each chapter (based on start lines etc.) into a separate file
foreach($chapters as $cc=>$chap){
	//echo "\n".$chap['number']."\n";
	$filename = preg_replace("/ /",'',$title);
	$filename .= '-Chapter-'.$chap['no'].'.txt';
	$x=0;
	$ttext='';
	$start=$chapters[$cc]['startline'][count($chapters[$cc]['startline'])-1]; //assume last occurrence is 'in text' chapter start
	if(!isset($chapters[$cc+1])){
		$end = $delims[1]-1;
	} else {
		$end = $chapters[$cc+1]['startline'][count($chapters[$cc+1]['startline'])-1]-1;
	}
	echo "\nChap:".$cc.' s:'.$start.' e:'.$end;
	//echo "\n";
	foreach(range($start,$end) as $lc){
		$ttext .= $textinlines[$lc];
	}
	if(false){
		file_put_contents($filename,$ttext);
		echo "\n".$filename." written ..";
	}else{
		echo "\n".'test only, would create: '.$filename;
	}
	//file_put_contents($filename,$ttext);
	//echo "\n".$filename." ..";
}


// dump entire processed lines representation into a big 'debug file'
file_put_contents(preg_replace("/ /",'',$title)."-debug-coded.txt",tocodedtext($textinlines));






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

