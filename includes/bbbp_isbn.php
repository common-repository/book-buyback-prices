<?php

class bbbp_isbn {

	static function cleanISBN($isbn) {
		$isbn = str_replace(" ","",$isbn);
		$isbn = str_replace("\t","",$isbn);
		$isbn = str_replace("\n","",$isbn);
		$isbn = str_replace("-","",$isbn);
		$isbn = str_replace('"',"",$isbn);
		$isbn = str_replace('=',"",$isbn);
		$isbn = trim($isbn);
		return $isbn;
	}

	static function giveMeISBN13($isbn) {
	
		if(strpos($isbn,'E+12')!==false) {
			$isbn2 = number_format($isbn,0,"","");
			$isbn2 = substr($isbn2,0,12);
			$sum13 = self::isbn_genchksum13($isbn2);
			$isbn13 = $isbn2 . $sum13;
			$isbn = $isbn13;
		}
	
		if(strlen($isbn)==13 && in_array(substr($isbn,0,3), array("290","298"))) {
			// $orig_check_digit = substr($asin,12,1);
			$temp10 = self::giveMeISBN10($isbn);
			$isbn = $temp10;
		}
	
		if(stristr($isbn,"B")!==FALSE) {
			return "";
		} else {
			$isbn = self::cleanISBN($isbn);
			if(strlen($isbn)==10) {
				$isbn = self::isbn10_to_13($isbn);
			} else if(strlen($isbn)==13) {
				if(substr($isbn,-1)!=self::isbn_genchksum13($isbn)) {
					$isbn = self::giveMeISBN10($isbn);
					$isbn = self::giveMeISBN13($isbn);
				}
			}
			return $isbn;
		}
	}

	static function giveMeISBN10($isbn) {
		$isbn = self::cleanISBN($isbn);
		if(strlen($isbn)==13) {
			$isbn = self::ISBN13toISBN10($isbn);
		} 
		return $isbn;
	}
	
	static function ISBN13toISBN10($isbn) {
		if (preg_match('/^\d{3}(\d{9})\d$/', $isbn, $m)) {
			$sequence = $m[1];
			$sum = 0;
			$mul = 10;
			for ($i = 0; $i < 9; $i++) {
				$sum = $sum + ($mul * (int) $sequence[$i]);
				$mul--;
			}
			$mod = 11 - ($sum%11);
			if ($mod == 10) {
				$mod = "X";
			}
			else if ($mod == 11) {
				$mod = 0;
			}
			$isbn = $sequence.$mod;
		}
		return $isbn;
	}

	static function isbn10_to_13($isbn) {
			$isbn2 = substr("978" . trim($isbn), 0, -1);
			$sum13 = self::isbn_genchksum13($isbn2);
			$isbn13 = $isbn2 . $sum13;
			return ($isbn13);
	}

	static function isbn_genchksum13($isbn) {
		$t = 2;
		$isbn = trim($isbn);
		$b=0;
		for($i = 1; $i <= 12; $i++){
			$c = substr($isbn,($i-1),1);
			if ($i % 2==0){
				$a = (3 * $c);
			} else {
				$a = (1 * $c);
			}
		$b=$b+$a;
		}
		$sum = 10 - ($b % 10);
		if($sum == 10) $sum = 0;
		return $sum;
	}
}