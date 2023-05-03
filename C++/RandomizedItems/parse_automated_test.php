<?php
set_time_limit( 2 * 60 );
InitStatList();

//$files = scandir("./RiTests/");
$FilesBeginString = "res_";
$ScanDirPath = "./AutomatedTestsResults/";
$files = scandir($ScanDirPath);
$PlayerClassNames = array("0","Warrior","Paladin","Hunter","Rogue","Priest","DeathK","Shaman","Mage","Warlock","0","Druid");

foreach($files as $key => $FileName)
{
//	echo "$key=$FileName<br>";
	if(strpos("#".$FileName ,$FilesBeginString) != 1)
//	if(strpos("#".$FileName ,"price") != 1)
		continue;
	//get the HPS / VPS in this test
	$FileName = $ScanDirPath.$FileName;
	$f = fopen($FileName, "rt");
	$PlayerClass = (int)fgets($f);
	$DamageDone = (float)fgets($f);
	$HealingDone = (float)fgets($f);
	$HealingDone = -$HealingDone;	// we expect that we healed more than damage received
	$TestDuration = (float)fgets($f) / 1000;
	$DPS = fgets($f);
	$HPS = fgets($f);
	fclose($f);	
	//try to get the stats we used for this setup. Should be the same file without the "beginning"
	$RealFileStart = strpos( $FileName, "_", 0 );
	$RealFileStart = strpos( $FileName, "_", $RealFileStart + 1 );
	$TestFileName = substr( $FileName, $RealFileStart + 1 );
	$f = fopen($ScanDirPath.$TestFileName, "rt");
	$PlayerClassMask = fgets($f);
	$AllowMobHits = fgets($f);
	$TargetPlayerClass = fgets($f);
	$StatList = fgets($f);
	$Duration = (int)fgets($f);
	$BuildId = (int)fgets($f);
	$BuildName = trim(rtrim(fgets($f)));
	fclose($f);	
	if( !isset($BuildName) || $BuildName == "")
		$BuildName = $PlayerClassNames[$PlayerClass]."_".$BuildId; // older versions of tests did not yet have a build name
	$IsVPSParsing = 1;
	if(strpos($BuildName, "Heal") != 0)
		$IsVPSParsing = 0;
	if($IsVPSParsing == 0) 
		continue;
//if(strpos("#".$BuildName, "warr") != 0 || strpos("#".$FileName, "res_1_"))	echo "Loading warrior build : $BuildName - file name $FileName<br>";
//	if($IsVPSParsing == 1) continue;
//echo "buid name is : $BuildName <br>";	
	$StatListOri = $StatList;
	$StatList = FormatStatList($StatList);
//echo "$BuildName)$key=$StatList<br>";
//if( strpos("#".$StatListOri,"0 122 ") == 1 || strpos("#".$StatListOri,"0 299 ") == 1 || strpos("#".$StatListOri,"0 300 ") == 1)continue;
//if( strpos("#".$StatListOri,"0 163 ") == 1 || strpos("#".$StatListOri,"0 71 ") == 1 || strpos("#".$StatListOri,"0 70 ") == 1)continue;
	$UniqueStatCombos[$StatList] = $FileName;
	$UniqueStatCombosFile[$StatList] = $StatListOri;
	$UniqueBuildNames[$BuildName] = 1;
	
	if($IsVPSParsing == 1)
	{
		if(!isset($StatComboVPS[$BuildName][$StatList]))
			$StatComboVPS[$BuildName][$StatList] = (int)( $DPS );
		else if( $StatComboVPS[$BuildName][$StatList] < (int)( $DPS ) )
				$StatComboVPS[$BuildName][$StatList] = (int)( $DPS );
	}	
	else
	{
		if(!isset($StatComboVPS[$BuildName][$StatList]))
			$StatComboVPS[$BuildName][$StatList] = (int)( $HealingDone / $TestDuration );
		else if( $StatComboVPS[$BuildName][$StatList] < (int)( $HealingDone / $TestDuration ) )
				$StatComboVPS[$BuildName][$StatList] = (int)( $HealingDone / $TestDuration );
	}
}
foreach($UniqueStatCombosFile as $key => $val)
{
	$parts = explode(" ",$val);
	$StatCountGettingTested = (int)(count($parts)/3);
	break;
}
//GenerateStatComboMinMaxAVG();
//GenerateStatComboNormalized();
GenerateOrderedVPSList();
//GenerateProposedKeepStatLists();

//PrintClassBuildStatVPS();
//PrintClassBuildStatNormalizedVPS();
//PrintClassBestVPSBuilds();

//ProposeNewTestList();
echo "<br><br>";
FormatStatVPSForGenNextIteration();
echo "<br><br>";
ProposeStatsCouldBoost();

//stats that do not reach the top 10 DPS chart
function ProposeStatsCouldBoost()
{
	global $UniqueStatCombos,$OrderedList,$OrderedListStatName,$UniqueBuildNames,$StatComboVPS, $IRS, $UniqueStatCombosFile;
	//go backwards, check if a stat is below the top line, see how far is bellow the line
	//get the max of the max
	$Max = 0;
	foreach($UniqueStatCombos as $StatCombo => $FileName)
		foreach($UniqueBuildNames as $BuildName => $Just1 )
			if( isset($StatComboVPS[$BuildName][$StatCombo]) && $StatComboVPS[$BuildName][$StatCombo] > 0)
				if($StatComboVPS[$BuildName][$StatCombo]>$Max)
					$Max = $StatComboVPS[$BuildName][$StatCombo];
echo "Max DPS is $Max <br>";			
	//generate normalized values
	foreach($UniqueStatCombos as $StatCombo => $FileName)
		foreach($UniqueBuildNames as $BuildName => $Just1 )
			if( isset($StatComboVPS[$BuildName][$StatCombo]) && $StatComboVPS[$BuildName][$StatCombo] > 0)
				$StatComboBoostCoef[$BuildName][$StatCombo] = $Max / $StatComboVPS[$BuildName][$StatCombo];
	//we could boost a stat combo if it is below the x% in all possible builds
	foreach($UniqueStatCombos as $StatCombo => $FileName)
	{
//		$IsBelowLimitForAll = 1;
		$WorstBoostItCouldGet = 0x00FFFFFF;
		foreach($UniqueBuildNames as $BuildName => $Just1 )
			if( isset($StatComboBoostCoef[$BuildName][$StatCombo]))
			{
				$BoostItCouldGet = $StatComboBoostCoef[$BuildName][$StatCombo];
//echo "Could boost stat combo $StatCombo by $BoostItCouldGet <br>";
/*				if( $StatComboBoostCoef[$BuildName][$StatCombo] > 0.9)
				{
					$IsBelowLimitForAll = 0;
					break;
				}*/
				if($WorstBoostItCouldGet > $BoostItCouldGet)
					$WorstBoostItCouldGet = $BoostItCouldGet;
			}		
//		if($IsBelowLimitForAll != 0 && $WorstBoostItCouldGet != 0x00FFFFFF)
		if($WorstBoostItCouldGet != 0x00FFFFFF)
			$StatBoostToStat[$StatCombo] = $WorstBoostItCouldGet;
	}
	asort($StatBoostToStat);
	foreach($StatBoostToStat as $StatCombo => $BoostPCT)
	{
		$OriStatComboList = GetStatIndexForName($StatCombo);
//		$StatComboName = $IRS[$StatCombo];
		echo "Could boost stat combo $StatCombo - $OriStatComboList by $BoostPCT <br>";
	}
	
	//now generate a normalized value for each of stat combos
/*	foreach($UniqueStatCombos as $StatCombo => $FileName)
	{
		$StatVPS[$StatCombo]=0;
		$Counter = 0;
		foreach($UniqueBuildNames as $BuildName => $Just1 )
		{
			if( isset($StatComboVPS[$BuildName][$StatCombo]) && $StatComboVPS[$BuildName][$StatCombo] > 0)
			{
				$StatVPS[$StatCombo] += $StatComboVPS[$BuildName][$StatCombo];
				$Counter++;
			}
		}
		$StatVPS[$StatCombo] = $StatVPS[$StatCombo] / $Counter;
	}
	$DPSSum = 0;
	$Counter = 0;
	foreach($UniqueStatCombos as $StatCombo => $FileName)
	{
		$DPSSum += $StatVPS[$StatCombo];
		$Counter++;
	}
	$DPSAVG = $DPSSum / $Counter;*/
}

function FormatStatVPSForGenNextIteration()
{
	global $UniqueBuildNames, $StatComboVPS, $UniqueStatCombosFile,$StatCountGettingTested;
	foreach($UniqueBuildNames as $BuildName => $Just1 )
	{
		$ToPrint = "\$Builds[GBID('$BuildName')]['StatDPSValues'][$StatCountGettingTested] = Merge2Arrays(\$Builds[GBID('$BuildName')]['StatDPSValues'][$StatCountGettingTested],array(";
		foreach($StatComboVPS[$BuildName] as $FormatedStatCombo => $VPS)
		{
			if($VPS<=0)
				$VPS = 1;
			$OriginalStatCombo = GetStatIndexForName($FormatedStatCombo);
			$ToPrint .= "\"$OriginalStatCombo\" => $VPS,";
		}
		$ToPrint = substr($ToPrint,0,-1);
		$ToPrint = $ToPrint."));";
//		echo "$BuildName<br>";
		echo "$ToPrint<br>";
	}
}

function ProposeNewTestList()
{
	global $UniqueBuildNames, $ProposedKeepStatsInBuilds,$ProposedSkipStatsInBuilds,$UniqueStatCombosFile,$StatCountGettingTested,$IRS;
	// remove stats from builds that barelly reached the minimum
	foreach($UniqueBuildNames as $BuildName => $Just1 )
	{
		if(!isset($ProposedKeepStatsInBuilds[$BuildName]))
			continue;
		$print = "\$Build['TestedStats'] = array(0,";
		sort($ProposedKeepStatsInBuilds[$BuildName]);
		$AllStatCombos = "";
		foreach($ProposedKeepStatsInBuilds[$BuildName] as $Ind => $StatCombo)
			$AllStatCombos .= $StatCombo.",";
//$print .= "122,299,300,";
		$AllStatCombos = substr($AllStatCombos,0,-1);
		//need to make each stat to be unqiue in this list
		$part = explode(",",$AllStatCombos);
		unset($UnqiueList);
		foreach($part as $key => $val)
			$UnqiueList[$val]=1;
		ksort($UnqiueList);
		foreach($UnqiueList as $key => $val)
			$print.= "$key,";
		$print = substr($print,0,-1);
		$print = $print.");";
		echo "$BuildName<br>";
		echo "$print<br>";
	}
	foreach($UniqueBuildNames as $BuildName => $Just1 )
	{
		if(!isset($ProposedSkipStatsInBuilds[$BuildName]))
			continue;
		$print = "\$Build['TestedSkipStats'][$StatCountGettingTested] = \"#,";
//print_r($ProposedSkipStatsInBuilds[$BuildName]);echo"<br>";		
		foreach($ProposedSkipStatsInBuilds[$BuildName] as $Ind => $StatCombo)
			$print .= $StatCombo.",";
		$print = $print."\";";
		echo "$BuildName<br>";
		echo "$print<br>";
	}
	if($StatCountGettingTested==1)
	{
		foreach($UniqueBuildNames as $BuildName => $Just1 )
		{
			if(!isset($ProposedSkipStatsInBuilds[$BuildName]))
				continue;
			$print = "\$Build['TestedSkipStats'][$StatCountGettingTested] = \"#,";
	//print_r($ProposedSkipStatsInBuilds[$BuildName]);echo"<br>";		
			foreach($ProposedSkipStatsInBuilds[$BuildName] as $Ind => $StatCombo)
				$print .= $IRS[$StatCombo]."($StatCombo),";
			$print = $print."\";";
			echo "$BuildName<br>";
			echo "$print<br>";
		}
	}
}

function GenerateOrderedVPSList()
{
	global $UniqueBuildNames,$StatComboVPS,$UniqueStatCombos,$OrderedList,$OrderedListStatName;
	//prepare the data to be printed
	foreach($UniqueBuildNames as $BuildName => $Just1 )
	{
		//generate a VPS list for this build
//		echo "Build name = $BuildName<br>";
		$OrderedList[$BuildName] = array();
		$ListCounter = 0;
		unset($StatVPS);
		$StatVPS = array();
		foreach($UniqueStatCombos as $StatCombo => $FileName)
			if( isset($StatComboVPS[$BuildName][$StatCombo]) && $StatComboVPS[$BuildName][$StatCombo] > 0)
				$StatVPS[$StatCombo] = $StatComboVPS[$BuildName][$StatCombo];
		//print them in an ordered fashion
//print_r($StatVPS); die();	
		do{
			//search for new minimum
			$MaxVPS = 0;
			foreach($StatVPS as $StatCombo => $VPS)
				if($MaxVPS < $VPS)
				{
					$MaxVPS = $VPS;
					$StatOfVPS = $StatCombo;
				}
			$StatVPS[$StatOfVPS] = 0;
			//print the new minimum			
//			echo "$MaxVPS($StatOfVPS),";
			if($MaxVPS>0)
			{
				$OrderedList[$BuildName][$ListCounter] = $MaxVPS;
				$OrderedListStatName[$BuildName][$ListCounter++] = $StatOfVPS;
			}
		}while($MaxVPS > 0);
//		echo "<br>";
	}
}

function GenerateProposedKeepStatLists()
{
	global $UniqueBuildNames,$ProposedKeepStatsInBuilds,$OrderedList,$OrderedListStatName,$IsVPSParsing,$ProposedSkipStatsInBuilds,$StatCountGettingTested;
	//minimal stats should be removed from the build
	foreach($UniqueBuildNames as $BuildName => $val2 )
	{
		if( !isset($OrderedList[$BuildName]))
			continue;
		$BuildsUsed = count($OrderedList[$BuildName]);
		/*
		$MaxValue = $OrderedList[$BuildName][0];
		$SumValue = 0;
		$SumValueCount = 0;
		$MinValue = 0;
		for($i=$BuildsUsed-1;$i>0;$i--)
		{
			if( $OrderedList[$BuildName][$i]<=0 )
				continue;
			if( $MinValue == 0)
				$MinValue = $OrderedList[$BuildName][$i];
			$SumValue += $OrderedList[$BuildName][$i];
			$SumValueCount++;
		}
		if( $SumValueCount != 0 )
		{
			$AvgValue = $SumValue / $SumValueCount;
			$ValueLimit = ( $MinValue + $AvgValue ) / 2;
		}
		else
		{
			$ValueLimit = $MinValue * 5;
		} 
echo "$BuildName minimal dps $MinValue, avg dps $AvgValue, keep higher than $ValueLimit<br>";
		*/
		if($StatCountGettingTested == 1)
		{
			if($IsVPSParsing == 1 )
				$ValueLimit = 4000;	// set manually !!!!!
		}
		else if($StatCountGettingTested == 2)
		{
			if($IsVPSParsing == 1 )
				$ValueLimit = 35000;	// set manually !!!!!
		}
		//always keep 1 minimum so the list does not get smaller and smaller
		$IndSkip = 0;
		$IndKeep = 0;
		for($i=0;$i<$BuildsUsed;$i++)
		{
			if(!isset($ProposedSkipStatsInBuilds[$BuildName]))
				$ProposedSkipStatsInBuilds[$BuildName] = array();
			if(!isset($ProposedKeepStatsInBuilds[$BuildName]))
				$ProposedKeepStatsInBuilds[$BuildName] = array();
				
			if( $OrderedList[$BuildName][$i] <  $ValueLimit)
				$CheckedList = $ProposedSkipStatsInBuilds[$BuildName];
			else
				$CheckedList = $ProposedKeepStatsInBuilds[$BuildName];
				
//			if( $OrderedList[$BuildName][$i] > $MaxValue / 20 )
			$RealStatName = GetStatIndexForName($OrderedListStatName[$BuildName][$i]);
			$ListAlreadyHasStat = 0;
			foreach($CheckedList as $ind => $StatCombo2)
				if($StatCombo2 == $RealStatName)
				{
					$ListAlreadyHasStat = 1;
					break;
				}
			if($ListAlreadyHasStat == 0)
			{
				if( $OrderedList[$BuildName][$i] <  $ValueLimit)
					$ProposedSkipStatsInBuilds[$BuildName][$IndSkip++] = $RealStatName;
				else
					$ProposedKeepStatsInBuilds[$BuildName][$IndKeep++] = $RealStatName;
			}
		}
	}
}

function PrintClassBestVPSBuilds()
{
	global $UniqueBuildNames,$OrderedList,$OrderedListStatName;
	//now print it
	echo "<table border=1>";
	echo "<tr>";
	foreach($UniqueBuildNames as $BuildName => $val2)
		echo "<td>$BuildName</td><td></td>";
	echo "</tr>";
	$i = 0;
	do{	
		$RowHasValues = 0;
		$row = "<tr>";
		foreach($UniqueBuildNames as $BuildName => $val2 )
		{
			if(!isset($OrderedList[$BuildName]) || !isset($OrderedList[$BuildName][$i]))
				$row .= "<td></td><td></td>";
			else
			{
				$row .= "<td>".$OrderedList[$BuildName][$i]."</td>"."<td>".$OrderedListStatName[$BuildName][$i]."</td>";
				$RowHasValues++;
			}
		}	
		$row .= "</tr>\n";
		if($RowHasValues > 0)
			echo $row;
		else
			break;
		$i++;
	}while($RowHasValues>0 && $i<20);
	echo "</table>";
}

function GetStatIndexForName($StatCombo)
{
	global $IRS, $UniqueStatCombosFile;
	
	if( !isset($UniqueStatCombosFile[$StatCombo] ) )
		echo "!!!could not find stat combo $StatCombo <br>";
	$StatListOri = $UniqueStatCombosFile[$StatCombo];
	$parts = explode(" ",$StatListOri);
	$partCount = count($parts);
	$ret = "";
	for($i=0;$i+3<$partCount; $i+=3)
	{
		$ItemSlot = $parts[$i+0];
		$StatType = $parts[$i+1];
		$StatValue = $parts[$i+2];
		$ret .= $StatType.",";
	}
	$ret = substr($ret, 0, -1 );
	return $ret;
}

//how class specific is a stat and how usefull is for other classes
function PrintClassBuildStatNormalizedVPS()
{
	global $UniqueStatCombos,$StatComboNormalizedVPS,$UniqueBuildNames,$StatComboVPSBasedAvg,$StatComboVPSBasedMax,$StatComboVPSBasedMin;
	echo "<table border=1>";
	echo "<tr>";
	echo "<td>StatCombo</td>";
	foreach($UniqueBuildNames as $BuildName => $val2)
		echo "<td>$BuildName</td>";
	echo "</tr>";		
	foreach($UniqueStatCombos as $StatCombo => $val)
	{	
		echo "<tr>";
		echo "<td>$StatCombo</td>";
		foreach($UniqueBuildNames as $BuildName => $val2 )
		{
			if(!isset($StatComboNormalizedVPS[$BuildName]) || !isset($StatComboNormalizedVPS[$BuildName][$StatCombo]))
				echo "<td></td>";
			else
				echo "<td>".$StatComboNormalizedVPS[$BuildName][$StatCombo]."</td>";
		}	
		echo "</tr>\n";
	}
	echo "</table>";
}
/**/

//for a specific class, which stat gives the best VPS
function PrintClassBuildStatVPS()
{
	global $UniqueStatCombos,$StatComboVPS,$UniqueBuildNames,$StatComboVPSBasedAvg,$StatComboVPSBasedMax,$StatComboVPSBasedMin;
	echo "<table border=1>";
	echo "<tr>";
	echo "<td>FileName</td>";
	echo "<td>StatCombo</td>";
	foreach($UniqueBuildNames as $BuildName => $val2)
	{
		echo "<td>$BuildName</td>";
	}
	echo "<td>Min</td>";
	echo "<td>Max</td>";
	echo "<td>AVG</td>";
	echo "</tr>";		
	foreach($UniqueStatCombos as $StatCombo => $val)
	{	
		echo "<tr>";
		echo "<td>$val</td>";
		echo "<td>$StatCombo</td>";
		foreach($UniqueBuildNames as $BuildName => $val2 )
		{
			if(!isset($StatComboVPS[$BuildName]) || !isset($StatComboVPS[$BuildName][$StatCombo]))
				$VPS = "";
			else
				$VPS = $StatComboVPS[$BuildName][$StatCombo];
			
			$TDWithColorColor = "<td>";
			if(isset($StatComboVPSBasedAvg[$StatCombo]) && (int)$VPS < $StatComboVPSBasedAvg[$StatCombo])
				$TDWithColorColor = "<td bgcolor=\"#BB0000\">";
			else if($VPS == $StatComboVPSBasedMax[$StatCombo])
				$TDWithColorColor = "<td bgcolor=\"#00AA00\">";	
			else
				$TDWithColorColor = "<td>";
			
			echo "$TDWithColorColor$VPS</td>";
		}	
		echo "<td>".$StatComboVPSBasedMin[$StatCombo]."</td>";
		echo "<td>".$StatComboVPSBasedMax[$StatCombo]."</td>";
		echo "<td>".$StatComboVPSBasedAvg[$StatCombo]."</td>";
		echo "</tr>\n";
	}
	echo "</table>";
}

function GenerateStatComboMinMaxAVG()
{
	global $UniqueStatCombos,$StatComboVPSBasedMin,$StatComboVPSBasedMax,$StatComboVPSBasedAvg,$StatComboVPS,$UniqueBuildNames;
	//calculate which classes consider a stat useless. If they are below the avg, we can consider them as a useless stat
	foreach($UniqueStatCombos as $StatCombo => $val)
	{
		$MinVPS = 2147483647;
		$MaxVPS = 0;
		$VPSSum = 0;
		$VPSSumCounter = 0;
		foreach($UniqueBuildNames as $BuildName => $val2 )
		{
	//echo $class.$StatCombo."<br>";
			if(!isset($StatComboVPS[$BuildName]) || !isset($StatComboVPS[$BuildName][$StatCombo]))
				continue;	
			$VPS = $StatComboVPS[$BuildName][$StatCombo];		
			if($VPS < $MinVPS )
				$MinVPS = $VPS;
			if($VPS > $MaxVPS )
				$MaxVPS = $VPS;
			$VPSSum += $VPS;
			$VPSSumCounter++;
		}

		$AVG = (int)($VPSSum / $VPSSumCounter);
		$StatComboVPSBasedMin[$StatCombo] = $MinVPS;
		$StatComboVPSBasedMax[$StatCombo] = $MaxVPS;
		$StatComboVPSBasedAvg[$StatCombo] = $AVG;
	}
}

function GenerateStatComboNormalized()
{
	global $UniqueStatCombos, $StatComboVPS, $StatComboNormalizedVPS, $UniqueBuildNames;
	//calculate for a specific class, which stat gives the best VPS
	foreach($UniqueBuildNames as $BuildName => $val2 )
	{
		$MinVPS = 2147483647;
		$MaxVPS = 0;
		foreach($UniqueStatCombos as $StatCombo => $val)
		{
			if(!isset($StatComboVPS[$BuildName]) || !isset($StatComboVPS[$BuildName][$StatCombo]))
				continue;
			$VPS = $StatComboVPS[$BuildName][$StatCombo];
			if($VPS > 0 && $VPS < $MinVPS )
				$MinVPS = $VPS;
			if($VPS > $MaxVPS )
				$MaxVPS = $VPS;
		}
		// calculate each stat how far is from the best. Best is 100%, rest are below the best
		foreach($UniqueStatCombos as $StatCombo => $val)
		{
			if(!isset($StatComboVPS[$BuildName]) || !isset($StatComboVPS[$BuildName][$StatCombo]))
				continue;
			$VPS = $StatComboVPS[$BuildName][$StatCombo];
			$StatComboNormalizedVPS[$BuildName][$StatCombo] = (int)($VPS / $MaxVPS * 100);
		}
	}
	//print_r($StatComboNormalized);
}

function FormatStatList($StatList)
{
	global $IRS;
	$parts = explode(" ",$StatList);
	$partCount = count($parts);
	$ret = "";
	for($i=0;$i+3<$partCount; $i+=3)
	{
		$ItemSlot = $parts[$i+0];
		$StatType = $parts[$i+1];
		$StatValue = $parts[$i+2];
		$ret .= sprintf( $IRS[$StatType], $StatValue)." & ";
	}
	$ret = substr($ret, 0, -3 );
	return $ret;
}

function InitStatList()
{
	global $IRNRL,$IRS;
	$IRS[0] = "%d None";
	$IRNRL[0] = 100;
	$IRS[1]="%d Strength";
	$IRNRL[1]=188.00;
	$IRS[2]="%d Agility";
	$IRNRL[2]=160.00;
	$IRS[3]="%d Stamina";
	$IRNRL[3]=275.00;
	$IRS[4]="%d Intelect";
	$IRNRL[4]=188.00;
	$IRS[5]="%d Spirit";
	$IRNRL[5]=250.00;
	$IRS[6]="%d Health";
	$IRNRL[6]=5000.00;
	$IRS[7]="%d Mana";
	$IRNRL[7]=5000.00;
	$IRS[8]="%d Rage";
	$IRNRL[8]=10.00;
	$IRS[9]="%d Focus";
	$IRNRL[9]=10.00;
	$IRS[10]="%d Energy";
	$IRNRL[10]=10.00;
	$IRS[11]="%d Rune";
	$IRNRL[11]=10.00;
	$IRS[12]="%d Runic Power";
	$IRNRL[12]=10.00;
	$IRS[13]="%d Armor";
	$IRNRL[13]=300.00;
	$IRS[14]="%d Resistance Holy";
	$IRNRL[14]=30.00;
	$IRS[15]="%d Resistance Fire";
	$IRNRL[15]=30.00;
	$IRS[16]="%d Resistance Nature";
	$IRNRL[16]=30.00;
	$IRS[17]="%d Resistance Frost";
	$IRNRL[17]=30.00;
	$IRS[18]="%d Resistance Shadow";
	$IRNRL[18]=30.00;
	$IRS[19]="%d Resistance Arcane";
	$IRNRL[19]=30.00;
	$IRS[20]="%d Attack Power";
	$IRNRL[20]=300.00;
	$IRS[21]="%d Ranged Attack Power";
	$IRNRL[21]=160.00;
	$IRS[22]="%d Damage Mainhand";
	$IRNRL[22]=110.00;
	$IRS[23]="%d Damage Offhand";
	$IRNRL[23]=114.00;
	$IRS[24]="%d Damage Ranged";
	$IRNRL[24]=200.00;
	$IRS[25]="%d Dodge Rating";
	$IRNRL[25]=172.00;
	$IRS[26]="%d Parry Rating";
	$IRNRL[26]=112.00;
	$IRS[27]="%d Block Rating";
	$IRNRL[27]=74.00;
	$IRS[28]="%d Block";
	$IRNRL[28]=171.00;
	$IRS[29]="%d Melee Hit Rating";
	$IRNRL[29]=178.00;
	$IRS[30]="%d Ranged Hit Rating";
	$IRNRL[30]=178.00;
	$IRS[31]="%d Spell Hit Rating";
	$IRNRL[31]=178.00;
	$IRS[32]="%d Melee Crit Rating";
	$IRNRL[32]=40.00;
	$IRS[33]="%d Ranged Crit Rating";
	$IRNRL[33]=50.00;
	$IRS[34]="%d Spell Crit Rating";
	$IRNRL[34]=60.00;
	$IRS[35]="%d Melee Hit Taken Rating";
	$IRNRL[35]=178.00;
	$IRS[36]="%d Ranged Hit Taken Rating";
	$IRNRL[36]=178.00;
	$IRS[37]="%d Spell Hit Taken Rating";
	$IRNRL[37]=178.00;
	$IRS[38]="%d Melee Crit Taken Rating";
	$IRNRL[38]=40.00;
	$IRS[39]="%d Ranged Crit Taken Rating";
	$IRNRL[39]=50.00;
	$IRS[40]="%d Spell Crit Taken Rating";
	$IRNRL[40]=60.00;
	$IRS[41]="%d Melee Haste Rating";
	$IRNRL[41]=60.00;
	$IRS[42]="%d Ranged Haste Rating";
	$IRNRL[42]=120.00;
	$IRS[43]="%d Spell Haste Rating";
	$IRNRL[43]=227.00;
	$IRS[44]="%d Expertise Rating";
	$IRNRL[44]=114.00;
	$IRS[45]="%d Armor Penetration Rating";
	$IRNRL[45]=18.00;
	$IRS[46]="%d Hit Rating";
	$IRNRL[46]=172.00;
	$IRS[47]="%d Crit Rating";
	$IRNRL[47]=18.00;
	$IRS[48]="%d Hit Taken Rating";
	$IRNRL[48]=50.00;
	$IRS[49]="%d Crit Taken Rating";
	$IRNRL[49]=50.00;
	$IRS[50]="%d Resiliance Rating";
	$IRNRL[50]=18.00;
	$IRS[51]="%d Haste Rating";
	$IRNRL[51]=60.00;
	$IRS[52]="%d Mana regen";
	$IRNRL[52]=120.00;
	$IRS[53]="%d Spell Power";
	$IRNRL[53]=147.00;
	$IRS[54]="%d Health Regen";
	$IRNRL[54]=375.00;
	$IRS[55]="%d Spell Penetration";
	$IRNRL[55]=18.00;
	$IRS[56]="%d Magic Find";
	$IRNRL[56]=10.00;
	$IRS[57]="%d Magic Find Power Rating";
	$IRNRL[57]=10.00;
	$IRS[58]="%.02f%% Run speed";
	$IRNRL[58]=2.00;
	$IRS[59]="%.02f%% Mounted speed";
	$IRNRL[59]=3.00;
	$IRS[60]="%d Physical Dmg";
	$IRNRL[60]=500.00;
	$IRS[61]="%d Holy Dmg";
	$IRNRL[61]=200.00;
	$IRS[62]="%d Fire Dmg";
	$IRNRL[62]=147.00;
	$IRS[63]="%d Nature Dmg";
	$IRNRL[63]=207.00;
	$IRS[64]="%d Frost Dmg";
	$IRNRL[64]=115.00;
	$IRS[65]="%d Shadow Dmg";
	$IRNRL[65]=175.00;
	$IRS[66]="%d Arcane Dmg";
	$IRNRL[66]=137.00;
	$IRS[67]="%d Spell Healing taken";
	$IRNRL[67]=110.00;
	$IRS[68]="%.02f%% Damage done target health based";
	$IRNRL[68]=0.10;
	$IRS[69]="%.02f%% Damage done target health missing";
	$IRNRL[69]=0.10;
	$IRS[70]="%.02f%% Heal target health based";
	$IRNRL[70]=0.10;
	$IRS[71]="%.02f%% Heal target missing health based";
	$IRNRL[71]=0.10;
	$IRS[72]="%d seconds to spell duration";
	$IRNRL[72]=2.00;
	$IRS[73]="%.02f%% longer spell duration";
	$IRNRL[73]=3.00;
	$IRS[74]="%d spell damage";
	$IRNRL[74]=45.00;
	$IRS[75]="%.02f%% spell damage";
	$IRNRL[75]=3.00;
	$IRS[76]="%d spell DOT damage";
	$IRNRL[76]=60.00;
	$IRS[77]="%.02f%% spell DOT damage";
	$IRNRL[77]=7.00;
	$IRS[78]="%d spell crit dmg";
	$IRNRL[78]=130.00;
	$IRS[79]="%.02f%% spell crit dmg";
	$IRNRL[79]=15.00;
	$IRS[80]="%.02f%% Threat";
	$IRNRL[80]=50.00;
	$IRS[81]="%.02f%% equip item dropchance";
	$IRNRL[81]=5.00;
	$IRS[82]="%.02f%% melee crit dmg";
	$IRNRL[82]=10.00;
	$IRS[83]="%.02f%% ranged crit dmg";
	$IRNRL[83]=12.00;
	$IRS[84]="%.02f%% Threat increase";
	$IRNRL[84]=50.00;
	$IRS[85]="%d continuous health regen";
	$IRNRL[85]=400.00;
	$IRS[86]="%.02f%% continuous health regen";
	$IRNRL[86]=1.00;
	$IRS[87]="%.02f%% continuous missing health regen";
	$IRNRL[87]=1.00;
	$IRS[88]="%.02f%% continuous current health regen";
	$IRNRL[88]=1.00;
	$IRS[89]="%.02f%% continuous current power regen";
	$IRNRL[89]=1.00;
	$IRS[90]="%d target power burn";
	$IRNRL[90]=2000.00;
	$IRS[91]="%.02f%% target power burn";
	$IRNRL[91]=1.00;
	$IRS[92]="%d spell target";
	$IRNRL[92]=2.00;
	$IRS[93]="Gain Dual Wield";
	$IRNRL[93]=100.00;
	$IRS[94]="Gain Titan's Grip";
	$IRNRL[94]=100.00;
	$IRS[95]="Chance for knockdown on dmg";
	$IRNRL[95]=100.00;
	$IRS[96]="%d%% Critical heal amount";
	$IRNRL[96]=15.00;
	$IRS[97]="%d%% Hit chance";
	$IRNRL[97]=10.00;
	$IRS[98]="%d%% Spell Hit chance";
	$IRNRL[98]=10.00;
	$IRS[99]="%.02f%% Damage Taken from mana shield";
	$IRNRL[99]=10.00;
	$IRS[100]="%.02f%% Damage taken from mana";
	$IRNRL[100]=10.00;
	$IRS[101]="Gain Water Walk";
	$IRNRL[101]=100.00;
	$IRS[102]="Gain Feather Fall";
	$IRNRL[102]=100.00;
	$IRS[103]="Gain Hover";
	$IRNRL[103]=100.00;
	$IRS[104]="%.02f%% Spell Healing Taken";
	$IRNRL[104]=10.00;
	$IRS[105]="%.02f Spell Healing Done";
	$IRNRL[105]=110.00;
	$IRS[106]="%.02f%% Spell Healing Done";
	$IRNRL[106]=40.00;
	$IRS[107]="%d%% Magic Find Out Of Instance";
	$IRNRL[107]=20.00;
	$IRS[108]="%d%% Magic Find Strength Rating Out Of Instance";
	$IRNRL[108]=20.00;
	$IRS[109]="%d Damage Done To Health";
	$IRNRL[109]=200.00;
	$IRS[110]="%.02f%% Damage Done To Health";
	$IRNRL[110]=2.00;
	$IRS[111]="%d Mana To Damage";
	$IRNRL[111]=200.00;
	$IRS[112]="PCT Power missing To %.02f%% Damage";
	$IRNRL[112]=2.00;
	$IRS[113]="%d Damage taken To Mana";
	$IRNRL[113]=200.00;
	$IRS[114]="%.02f%% Damage taken To Mana";
	$IRNRL[114]=1.00;
	$IRS[115]="Chance To Cloack on Deadly Blow";
	$IRNRL[115]=100.00;
	$IRS[116]="%.02f%% Extra Gold";
	$IRNRL[116]=10.00;
	$IRS[117]="Chance To Ice Block on Deadly Blow";
	$IRNRL[117]=100.00;
	$IRS[118]="Chance To Divine Shield on Deadly Blow";
	$IRNRL[118]=100.00;
	$IRS[119]="%.02f%% to Min Max Damage";
	$IRNRL[119]=5.00;
	$IRS[120]="%.02f%% Cast speed";
	$IRNRL[120]=5.00;
	$IRS[121]="Target Explodes On Kill";
	$IRNRL[121]=10.00;
	$IRS[122]="%.02f%% damage reflected as Chain Lightning 1 On Struck";
	$IRNRL[122]=2.00;
	$IRS[123]="%d Damage reduction Based On Attacker Count";
	$IRNRL[123]=200.00;
	$IRS[124]="%d Damage Taken Converted to Damage";
	$IRNRL[124]=200.00;
	$IRS[125]="%.02f%% Damage Taken Converted to Damage";
	$IRNRL[125]=2.00;
	$IRS[126]="%.02f%% Strength";
	$IRNRL[126]=3.00;
	$IRS[127]="%.02f%% Agility";
	$IRNRL[127]=3.00;
	$IRS[128]="%.02f%% Stamina";
	$IRNRL[128]=3.00;
	$IRS[129]="%.02f%% Intelect";
	$IRNRL[129]=3.00;
	$IRS[130]="%.02f%% Spirit";
	$IRNRL[130]=3.00;
	$IRS[131]="%.02f%% Stats";
	$IRNRL[131]=3.00;
	$IRS[132]="-%d%% Casttime Pushback";
	$IRNRL[132]=5.00;
	$IRS[133]="-%d%% Global Cooldowns";
	$IRNRL[133]=5.00;
	$IRS[134]="%d Damage taken reduction based on raid size";
	$IRNRL[134]=200.00;
	$IRS[135]="%.02f%% Damage taken reduction based on raid size";
	$IRNRL[135]=1.00;
	$IRS[136]="%d Damage done based on raid size";
	$IRNRL[136]=20.00;
	$IRS[137]="%.02f%% Damage done based on raid size";
	$IRNRL[137]=1.00;
	$IRS[138]="%d Damage taken as 10 silver";
	$IRNRL[138]=2000.00;
	$IRS[139]="%d Talent points";
	$IRNRL[139]=1.00;
	$IRS[140]="%d Damage taken Shared With Nearby Tanks";
	$IRNRL[140]=2000.00;
	$IRS[141]="%.02f%% Damage taken Shared With Nearby Tanks";
	$IRNRL[141]=5.00;
	$IRS[142]="%d Damage taken Shared With Nearby Casters";
	$IRNRL[142]=2000.00;
	$IRS[143]="%.02f%% Damage taken Shared With Nearby Casters";
	$IRNRL[143]=10.00;
	$IRS[144]="Chance to reduce cooldown of previous spell";
	$IRNRL[144]=2.00;
	$IRS[145]="%d Extra dmg while below 20%% HP";
	$IRNRL[145]=4000.00;
	$IRS[146]="%.02f%% Extra dmg while below 20%% HP";
	$IRNRL[146]=3.00;
	$IRS[147]="%d%% Evade while below 20%% HP";
	$IRNRL[147]=3.00;
	$IRS[148]="%d%% Absorb while below 20%% HP";
	$IRNRL[148]=5.00;
	$IRS[149]="%d%% Stat Roll Chance";
	$IRNRL[149]=5.00;
	$IRS[150]="Damage Taken Can't exceed %d%% Max HP";
	$IRNRL[150]=2.00;
	$IRS[151]="Gain Ice Armor";
	$IRNRL[151]=100.00;
	$IRS[152]="Gain Demon Armor";
	$IRNRL[152]=100.00;
	$IRS[153]="Gain Fel Armor";
	$IRNRL[153]=100.00;
	$IRS[154]="Gain Metamorphosis";
	$IRNRL[154]=100.00;
	$IRS[155]="Gain Reincarnation";
	$IRNRL[155]=100.00;
	$IRS[156]="Chance to loose debuff on direct heal";
	$IRNRL[156]=100.00;
	$IRS[157]="Chance To Dispersion on Deadly Blow";
	$IRNRL[157]=100.00;
	$IRS[158]="Chance To Disengage on Damage";
	$IRNRL[158]=100.00;
	$IRS[159]="Chance to stormstrike on direct heal";
	$IRNRL[159]=100.00;
	$IRS[160]="Chance to clear potion cooldown on damage taken";
	$IRNRL[160]=100.00;
	$IRS[161]="%.02f%% Of Overheal is added to your next damage";
	$IRNRL[161]=10.00;
	$IRS[162]="Party mimics some of self cast auras";
	$IRNRL[162]=100.00;
	$IRS[163]="%.02f%% Of Current Health Is Added To Heals";
	$IRNRL[163]=10.00;
	$IRS[164]="Chance to cast same spell for free";
	$IRNRL[164]=100.00;
	$IRS[165]="%d%% chance to favor utility stats roll";
	$IRNRL[165]=10.00;
	$IRS[166]="%d%% chance to favor attack stats roll";
	$IRNRL[166]=10.00;
	$IRS[167]="%d%% chance to favor defense stats roll";
	$IRNRL[167]=10.00;
	$IRS[168]="%.02f Damage done for each unqiue monsters killed";
	$IRNRL[168]=4.10;
	$IRS[169]="%.02f Heal for each unqiue monsters killed";
	$IRNRL[169]=4.10;
	$IRS[170]="%.02f Damage done for each unqiue item used";
	$IRNRL[170]=14.00;
	$IRS[171]="%.02f Heal for each unqiue item used";
	$IRNRL[171]=14.00;
	$IRS[172]="%.02f Damage done for each achievement earned";
	$IRNRL[172]=62.00;
	$IRS[173]="%.02f Heal for each achievement earned";
	$IRNRL[173]=62.00;
	$IRS[174]="%.02f Damage done for each quest finished";
	$IRNRL[174]=10.00;
	$IRS[175]="%.02f Heal for each quest finished";
	$IRNRL[175]=10.00;
	$IRS[176]="%.02f Damage done for honorable kill rating";
	$IRNRL[176]=1.10;
	$IRS[177]="%.02f Heal for honorable kill rating";
	$IRNRL[177]=1.10;
	$IRS[178]="%.02f%% Spell Cost Reduction";
	$IRNRL[178]=10.00;
	$IRS[179]="Leather proficiency";
	$IRNRL[179]=100.00;
	$IRS[180]="Mail proficiency";
	$IRNRL[180]=100.00;
	$IRS[181]="Plate Mail proficiency";
	$IRNRL[181]=100.00;
	$IRS[182]="One-Handed Axe proficiency";
	$IRNRL[182]=100.00;
	$IRS[183]="One-Handed Mace proficiency";
	$IRNRL[183]=100.00;
	$IRS[184]="One-Handed Swords proficiency";
	$IRNRL[184]=100.00;
	$IRS[185]="Polearms proficiency";
	$IRNRL[185]=100.00;
	$IRS[186]="Shield proficiency";
	$IRNRL[186]=100.00;
	$IRS[187]="Two-Handed Axes proficiency";
	$IRNRL[187]=100.00;
	$IRS[188]="Two-Handed Maces proficiency";
	$IRNRL[188]=100.00;
	$IRS[189]="Two-Handed Sword proficiency";
	$IRNRL[189]=100.00;
	$IRS[190]="Staves proficiency";
	$IRNRL[190]=100.00;
	$IRS[191]="Learn Frost Nova";
	$IRNRL[191]=100.00;
	$IRS[192]="Learn Summon Succubus";
	$IRNRL[192]=100.00;
	$IRS[193]="Learn Charge";
	$IRNRL[193]=100.00;
	$IRS[194]="Learn Inervate";
	$IRNRL[194]=100.00;
	$IRS[195]="Gain Mark of the Wild";
	$IRNRL[195]=100.00;
	$IRS[196]="Learn Nature's Grasp";
	$IRNRL[196]=100.00;
	$IRS[197]="Learn Rebirth";
	$IRNRL[197]=100.00;
	$IRS[198]="Learn Deterrence";
	$IRNRL[198]=100.00;
	$IRS[199]="Learn Rapid Fire";
	$IRNRL[199]=100.00;
	$IRS[200]="Learn Arcane Power";
	$IRNRL[200]=100.00;
	$IRS[201]="Learn Counter Spell";
	$IRNRL[201]=100.00;
	$IRS[202]="Learn Evocation";
	$IRNRL[202]=100.00;
	$IRS[203]="Learn Focus Magic";
	$IRNRL[203]=100.00;
	$IRS[204]="Learn Icy Veins";
	$IRNRL[204]=100.00;
	$IRS[205]="Learn Divine Storm";
	$IRNRL[205]=100.00;
	$IRS[206]="Learn Seal of Light";
	$IRNRL[206]=100.00;
	$IRS[207]="Learn Divine Hymn";
	$IRNRL[207]=100.00;
	$IRS[208]="Learn Hymn of Hope";
	$IRNRL[208]=100.00;
	$IRS[209]="Learn Inner Focus";
	$IRNRL[209]=100.00;
	$IRS[210]="Learn Mana Burn";
	$IRNRL[210]=100.00;
	$IRS[211]="Learn Last Stand";
	$IRNRL[211]=100.00;
	$IRS[212]="Learn BloodLust";
	$IRNRL[212]=100.00;
	$IRS[213]="Learn Shamanistic Rage";
	$IRNRL[213]=100.00;
	$IRS[214]="Learn Blade Fury";
	$IRNRL[214]=100.00;
	$IRS[215]="Learn Cold Blood";
	$IRNRL[215]=100.00;
	$IRS[216]="Learn Fan of Knives";
	$IRNRL[216]=100.00;
	$IRS[217]="Learn Killing Spree";
	$IRNRL[217]=100.00;
	$IRS[218]="Learn Hysteria";
	$IRNRL[218]=100.00;
	$IRS[219]="Learn Windfury Weapon";
	$IRNRL[219]=100.00;
	$IRS[220]="%d%% Pet Threat";
	$IRNRL[220]=50.00;
	$IRS[221]="Pet gains Immolation Aura";
	$IRNRL[221]=50.00;
	$IRS[222]="%d Dmg for daily login streak";
	$IRNRL[222]=12.00;
	$IRS[223]="%d Heal for daily login streak";
	$IRNRL[223]=12.00;
	$IRS[224]="Chance to auto charge on target swap";
	$IRNRL[224]=100.00;
	$IRS[225]="%d%% Strength to spell damage";
	$IRNRL[225]=1.00;
	$IRS[226]="%d%% Agility to spell damage";
	$IRNRL[226]=5.00;
	$IRS[227]="%d%% Intelect to spell damage";
	$IRNRL[227]=5.00;
	$IRS[228]="%d%% Spirit to spell damage";
	$IRNRL[228]=5.00;
	$IRS[229]="%d%% Strength to Heal";
	$IRNRL[229]=5.00;
	$IRS[230]="%d%% Agility to Heal";
	$IRNRL[230]=5.00;
	$IRS[231]="%d%% Intelect to Heal";
	$IRNRL[231]=20.00;
	$IRS[232]="%d%% Spirit to Heal";
	$IRNRL[232]=20.00;
	$IRS[233]="%d%% of Heals received recharges Absorb auras";
	$IRNRL[233]=3.00;
	$IRS[234]="Chance to discover transmog on kill";
	$IRNRL[234]=100.00;
	$IRS[235]="Your footsteps burn";
	$IRNRL[235]=100.00;
	$IRS[236]="Cast previous single target spell when moving";
	$IRNRL[236]=100.00;
	$IRS[237]="%.02f%% Armor to Attack Power";
	$IRNRL[237]=0.50;
	$IRS[238]="%d%% Strength to Attack Power";
	$IRNRL[238]=5.00;
	$IRS[239]="%d%% Agility to Attack Power";
	$IRNRL[239]=5.00;
	$IRS[240]="%d%% Intelect to Attack Power";
	$IRNRL[240]=5.00;
	$IRS[241]="%d%% Spirit to Attack Power";
	$IRNRL[241]=5.00;
	$IRS[242]="%d%% Strength to Ranged Attack Power";
	$IRNRL[242]=5.00;
	$IRS[243]="%d%% Agility to Ranged Attack Power";
	$IRNRL[243]=5.00;
	$IRS[244]="%d%% Intelect to Ranged Attack Power";
	$IRNRL[244]=5.00;
	$IRS[245]="%d%% Spirit to Ranged Attack Power";
	$IRNRL[245]=5.00;
	$IRS[246]="Allow casting while moving";
	$IRNRL[246]=100.00;
	$IRS[247]="Can cast instant spells while casting";
	$IRNRL[247]=100.00;
	$IRS[248]="Spells no longer need to face targets";
	$IRNRL[248]=100.00;
	$IRS[249]="%d%% Negative Melee haste to %% Damage";
	$IRNRL[249]=150.00;
	$IRS[250]="%d%% Negative Ranged haste to %% Damage";
	$IRNRL[250]=150.00;
	$IRS[251]="%d%% Negative Spell haste to %% Damage";
	$IRNRL[251]=150.00;
	$IRS[252]="%d%% Negative Spell haste to %% Heal";
	$IRNRL[252]=150.00;
	$IRS[253]="You deal Fire damage";
	$IRNRL[253]=100.00;
	$IRS[254]="You deal Nature damage";
	$IRNRL[254]=100.00;
	$IRS[255]="You deal Frost damage";
	$IRNRL[255]=100.00;
	$IRS[256]="You deal Shadow damage";
	$IRNRL[256]=100.00;
	$IRS[257]="You deal Arcane damage";
	$IRNRL[257]=100.00;
	$IRS[258]="Chance to Slam Dunk at jump( health based )";
	$IRNRL[258]=100.00;
	$IRS[259]="Electrocute while casting(bsed on highest stat)";
	$IRNRL[259]=100.00;
	$IRS[260]="Diablo tranformation";
	$IRNRL[260]=100.00;
	$IRS[261]="%d%% Extra heal the closer you are";
	$IRNRL[261]=5.00;
	$IRS[262]="Chance to gain BloodLust on damage taken";
	$IRNRL[262]=100.00;
	$IRS[263]="Moving charges an explosion";
	$IRNRL[263]=100.00;
	$IRS[264]="Fist Weapon proficiency";
	$IRNRL[264]=100.00;
	$IRS[265]="%d Spell Range";
	$IRNRL[265]=1.00;
	$IRS[266]="Some Auras can stack to 2";
	$IRNRL[266]=1.00;
	$IRS[267]="%.02f%% Armor to Holy Resistance";
	$IRNRL[267]=0.05;
	$IRS[268]="%.02f%% Armor to Fire Resistance";
	$IRNRL[268]=0.05;
	$IRS[269]="%.02f%% Armor to Nature Resistance";
	$IRNRL[269]=0.05;
	$IRS[270]="%.02f%% Armor to Frost Resistance";
	$IRNRL[270]=0.05;
	$IRS[271]="%.02f%% Armor to Shadow Resistance";
	$IRNRL[271]=0.05;
	$IRS[272]="%.02f%% Armor to Arcane Resistance";
	$IRNRL[272]=0.05;
	$IRS[273]="%.02f%% Heal for each nearby player";
	$IRNRL[273]=2.00;
	$IRS[274]="%.02f%% Dmg for each nearby player";
	$IRNRL[274]=2.00;
	$IRS[275]="%d%% More Dust gained";
	$IRNRL[275]=10.00;
	$IRS[276]="Learn Blink";
	$IRNRL[276]=100.00;
	$IRS[277]="Learn Righteous Fury";
	$IRNRL[277]=100.00;
	$IRS[278]="%d Dmg for Killstreak Rating";
	$IRNRL[278]=1000.00;
	$IRS[279]="%d Heal for consecutive low(50%%) target health heals";
	$IRNRL[279]=1000.00;
	$IRS[280]="You take similar damage";
	$IRNRL[280]=10.00;
	$IRS[281]="Non lethal damage taken is split over %d seconds";
	$IRNRL[281]=120.00;
	$IRS[282]="%d extra Blood Rune regen speed";
	$IRNRL[282]=10.00;
	$IRS[283]="%d extra Unholy Rune regen speed";
	$IRNRL[283]=10.00;
	$IRS[284]="%d extra Frost Rune regen speed";
	$IRNRL[284]=10.00;
	$IRS[285]="%d extra Death Rune regen speed";
	$IRNRL[285]=10.00;
	$IRS[286]="%d%% Rune decrease reduction";
	$IRNRL[286]=10.00;
	$IRS[287]="%d%% extra Energy regen speed";
	$IRNRL[287]=21.00;
	$IRS[288]="%d extra Focus regen at tick";
	$IRNRL[288]=10.00;
	$IRS[289]="%d Rage regen at tick";
	$IRNRL[289]=48.00;
	$IRS[290]="Gain Sprint after a kill";
	$IRNRL[290]=100.00;
	$IRS[291]="%d extra direct damage done while behind target";
	$IRNRL[291]=300.00;
	$IRS[292]="%d damage taken reduction while facing attacker";
	$IRNRL[292]=300.00;
	$IRS[293]="Direct Heals restore %d%% of last damage taken";
	$IRNRL[293]=10.00;
	$IRS[294]="%.02f%% XP gained";
	$IRNRL[294]=10.00;
	$IRS[295]="%.02f%% single target damage converted to AOE damage";
	$IRNRL[295]=3.00;
	$IRS[296]="%.02f%% damage as Chain Lightning 1 on direct hit";
	$IRNRL[296]=5.00;
	$IRS[297]="%.02f%% damage as Chain Lightning 2 on direct hit";
	$IRNRL[297]=10.00;
	$IRS[298]="%.02f%% damage as Chain Lightning 3 on direct hit";
	$IRNRL[298]=15.00;
	$IRS[299]="%.02f%% damage reflected as Chain Lightning 2 On Struck";
	$IRNRL[299]=5.00;
	$IRS[300]="%.02f%% damage reflected as Chain Lightning 3 On Struck";
	$IRNRL[300]=10.00;
	$IRS[301]="Learn Death Grip";
	$IRNRL[301]=100.00;
	$IRS[302]="%d Health converted to damage";
	$IRNRL[302]=40.00;
	$IRS[303]="%.02f%% Damage Done";
	$IRNRL[303]=1.00;
}
?>