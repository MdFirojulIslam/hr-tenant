<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class HelperFunctionController extends Controller
{
    public static function numberToWords($number)
    {
        $number = (int)$number;

        $words = [
            0 => '',
            1 => 'one',
            2 => 'two',
            3 => 'three',
            4 => 'four',
            5 => 'five',
            6 => 'six',
            7 => 'seven',
            8 => 'eight',
            9 => 'nine',
            10 => 'ten',
            11 => 'eleven',
            12 => 'twelve',
            13 => 'thirteen',
            14 => 'fourteen',
            15 => 'fifteen',
            16 => 'sixteen',
            17 => 'seventeen',
            18 => 'eighteen',
            19 => 'nineteen',
            20 => 'twenty',
            30 => 'thirty',
            40 => 'forty',
            50 => 'fifty',
            60 => 'sixty',
            70 => 'seventy',
            80 => 'eighty',
            90 => 'ninety'
        ];

        if ($number == 0) return 'zero';

        if ($number < 21) return $words[$number];

        if ($number < 100) {
            $tens = $words[10 * floor($number / 10)];
            $units = $number % 10;
            return $units ? $tens . ' ' . $words[$units] : $tens;
        }

        if ($number < 1000) {
            return $words[floor($number / 100)] . ' hundred ' .
                self::numberToWords($number % 100);
        }

        // THOUSAND
        if ($number < 100000) {
            return self::numberToWords(floor($number / 1000)) . ' thousand ' .
                self::numberToWords($number % 1000);
        }

        // LAKH (FIXED ✅)
        if ($number < 10000000) {
            return self::numberToWords(floor($number / 100000)) . ' lakh ' .
                self::numberToWords($number % 100000);
        }

        // CRORE
        return self::numberToWords(floor($number / 10000000)) . ' crore ' .
            self::numberToWords($number % 10000000);
    }
}
