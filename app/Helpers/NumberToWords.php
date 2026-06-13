<?php

namespace App\Helpers;

class NumberToWords
{
    private static array $ones = ['', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine',
        'Ten', 'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen', 'Seventeen', 'Eighteen', 'Nineteen'];

    private static array $tens = ['', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety'];

    public static function convert(float $number, string $currency = 'Rupees'): string
    {
        if ($number == 0) return 'Zero ' . $currency . ' only';

        $whole = (int) floor($number);
        $paise = (int) round(($number - $whole) * 100);

        $words = self::convertWholeNumber($whole);
        $result = trim($words) . ' ' . $currency;

        if ($paise > 0) {
            $result .= ' and ' . self::convertWholeNumber($paise) . ' Paise';
        }

        return $result . ' only';
    }

    private static function convertWholeNumber(int $number): string
    {
        if ($number < 0) return 'Minus ' . self::convertWholeNumber(-$number);
        if ($number == 0) return '';

        $result = '';

        if ($number >= 10000000) {
            $result .= self::convertWholeNumber((int) floor($number / 10000000)) . ' Crore ';
            $number %= 10000000;
        }

        if ($number >= 100000) {
            $result .= self::convertWholeNumber((int) floor($number / 100000)) . ' Lakh ';
            $number %= 100000;
        }

        if ($number >= 1000) {
            $result .= self::convertWholeNumber((int) floor($number / 1000)) . ' Thousand ';
            $number %= 1000;
        }

        if ($number >= 100) {
            $result .= self::$ones[(int) floor($number / 100)] . ' Hundred ';
            $number %= 100;
        }

        if ($number >= 20) {
            $result .= self::$tens[(int) floor($number / 10)] . ' ';
            $number %= 10;
        }

        if ($number > 0) {
            $result .= self::$ones[$number] . ' ';
        }

        return trim($result);
    }
}
