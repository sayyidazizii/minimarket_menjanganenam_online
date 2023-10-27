<?php

namespace App\Helpers;

class Configuration
{
    public static function numtotxt($num) 
	{
		$tdiv 	= array("","","Ratus ","Ribu ", "Ratus ", "Juta ", "Ratus ","Miliar ");
		$divs 	= array( 0,0,0,0,0,0,0);
		$pos 	= 0;
		$num 	= strval(strrev(number_format($num, 2, '.',''))); 
		$answer = "";
		while (strlen($num)) {
			if ( strlen($num) == 1 || ($pos >2 && $pos % 2 == 1))  {
				$answer = static::doone(substr($num, 0, 1)) . $answer;
				$num 	= substr($num,1);
			} else {
				$answer = static::dotwo(substr($num, 0, 2)) . $answer;
				$num 	= substr($num,2);
				if ($pos < 2)
					$pos++;
			}

			if (substr($num, 0, 1) == '.') {
				if (! strlen($answer)){
					$answer = "";
				}

				$answer = "" . $answer . "";
				$num 	= substr($num,1);
				if (strlen($num) == 1 && $num == '0') {
					$answer = "" . $answer;
					$num 	= substr($num,1);
				}
			}
		    if ($pos >= 2 && strlen($num)) {
				if (substr($num, 0, 1) != 0  || (strlen($num) >1 && substr($num,1,1) != 0 && $pos %2 == 1)  ) {
					if ( $pos == 4 || $pos == 6 ) {
						if ($divs[$pos -1] == 0)
							$answer = $tdiv[$pos -1 ] . $answer;
					}
					$divs[$pos] = 1;
					$answer 	= $tdiv[$pos++] . $answer;
				} else {
					$pos++;
				}
			}
	    }
	    return $answer.'Rupiah';
	}
	

	public static function doone2($onestr) 
	{
	    $tsingle = array("","Satu ","Dua ","Tiga ","Empat ","Lima ","Enam ","Tujuh ","Delapan ","Sembilan ");

        return $tsingle[$onestr];
	}	
	 
	public static function doone($onestr) 
	{
	    $tsingle = array("","Satu ","Dua ","Tiga ","Empat ","Lima ", "Enam ","Tujuh ","Delapan ","Sembilan ");

	    return $tsingle[$onestr];
	}	

	public static function dotwo($twostr) 
	{
	    $tdouble = array("","puluh ","Dua Puluh ","Tiga Puluh ","Empat Puluh ","Lima Puluh ", "Enam Puluh ","Tujuh Puluh ","Delapan Puluh ","Sembilan Puluh ");
	    $teen = array("Sepuluh ","Sebelas ","Dua Belas ","Tiga Belas ","Empat Belas ","Lima Belas ", "Enam Belas ","Tujuh Belas ","Delapan Belas ","Sembilan Belas ");
	    if ( substr($twostr,1,1) == '0') {
			$ret = static::doone2(substr($twostr,0,1));
	    } else if (substr($twostr,1,1) == '1') {
			$ret = $teen[substr($twostr,0,1)];
	    } else {
			$ret = $tdouble[substr($twostr,1,1)] . static::doone2(substr($twostr,0,1));
	    }

	    return $ret;
	}

	public static function dateReduction($tgl1, $tgl2) 
	{
		$jarak = strtotime($tgl1) - strtotime($tgl2);
		$hari = $jarak / 60 / 60 / 24;

		return $hari;
	}

	public static function dateSummation($tgl1, $tgl2) 
	{
		$jarak = strtotime($tgl1) + strtotime($tgl2);
		$hari = $jarak / 60 / 60 / 24;

		return $hari;
	}
}