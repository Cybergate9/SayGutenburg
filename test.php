<?php

$textinfull = file_get_contents("https://www.gutenberg.org/files/863/863-0.txt");
$charc = strlen($textinfull);
conwrite("chars:", $charc);


$textinlines = explode("\n",$textinfull);
$textinfull=''; //release memory
$linesc=count($textinlines);
conwrite("lines:", $linesc);

$wordc=0;
foreach($textinlines as $line){
	$words = explode(" ",$line);
	$wordc += count($words);
}
conwrite("words:",$wordc);

$delims=[]; $x=0;
foreach($textinlines as $lc=>$line){
	if(strstr($line, "***")){
		$delims[$x++]=$lc;
	}

}
print_r($delims);


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
print_r($metas);


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
print_r($contents);


$tarray=[]; $chapters=[]; $x=0;
foreach($contents as $line){
	if(!strstr($line['content'], 'Contents')){  //provided its not the header
		$tarray = explode('.',$line['content']);
		$chapters[$x]['number']=$tarray[0];
		$chapters[$x++]['title']=$tarray[1];
	}

}
print_r($chapters);


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

