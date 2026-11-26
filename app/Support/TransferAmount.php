<?php

namespace App\Support;

class TransferAmount
{
    public static function minor(string $amount): int
    {
        [$whole, $fraction] = array_pad(explode('.', $amount, 2), 2, '');
        return (int) $whole * 100 + (int) str_pad($fraction, 2, '0');
    }

    public static function display(int $minor): string
    {
        return number_format(intdiv($minor, 100)).'.'.str_pad((string) ($minor % 100), 2, '0', STR_PAD_LEFT);
    }

    public static function words(int $minor, string $language): string
    {
        $whole = intdiv($minor, 100);
        $fraction = $minor % 100;
        if ($language === 'ar') {
            $text = self::arabic($whole, ['جنيه سوداني', 'جنيهان سودانيان', 'جنيهات سودانية', 'جنيهاً سودانياً']);
            if ($fraction) {
                $text .= ' و'.self::arabic($fraction, ['قرش', 'قرشان', 'قروش', 'قرشاً']);
            }
            return $text.' فقط';
        }
        $text = self::english($whole).' Sudanese '.($whole === 1 ? 'pound' : 'pounds');
        if ($fraction) {
            $text .= ' and '.self::english($fraction).' '.($fraction === 1 ? 'piastre' : 'piastres');
        }
        return $text.' only';
    }

    private static function english(int $n): string
    {
        $small = explode(' ', 'zero one two three four five six seven eight nine ten eleven twelve thirteen fourteen fifteen sixteen seventeen eighteen nineteen');
        if ($n < 20) {
            return $small[$n];
        }
        foreach ([1000000000 => 'billion', 1000000 => 'million', 1000 => 'thousand', 100 => 'hundred'] as $scale => $name) {
            if ($n >= $scale) {
                $rest = $n % $scale;
                return self::english(intdiv($n, $scale)).' '.$name.($rest ? ($rest < 100 ? ' and ' : ' ').self::english($rest) : '');
            }
        }
        $tens = [2 => 'twenty', 'thirty', 'forty', 'fifty', 'sixty', 'seventy', 'eighty', 'ninety'];
        return $tens[intdiv($n, 10)].($n % 10 ? '-'.$small[$n % 10] : '');
    }

    private static function arabic(int $n, array $unit): string
    {
        $parts = [];
        foreach ([1000000000 => ['مليار','ملياران','مليارات','ملياراً'], 1000000 => ['مليون','مليونان','ملايين','مليوناً'], 1000 => ['ألف','ألفان','آلاف','ألفاً']] as $scale => $forms) {
            if ($n >= $scale) {
                $parts[] = self::arabicGroup(intdiv($n, $scale), $forms);
                $n %= $scale;
            }
        }
        if ($n || !$parts) {
            $parts[] = self::arabicGroup($n, $unit);
            return implode(' و', $parts);
        }
        return implode(' و', $parts).' '.$unit[0];
    }

    private static function arabicGroup(int $n, array $forms): string
    {
        $ones = ['صفر','واحد','اثنان','ثلاثة','أربعة','خمسة','ستة','سبعة','ثمانية','تسعة','عشرة','أحد عشر','اثنا عشر'];
        $hundreds = ['','مائة','مائتان','ثلاثمائة','أربعمائة','خمسمائة','ستمائة','سبعمائة','ثمانمائة','تسعمائة'];
        $tens = [2 => 'عشرون','ثلاثون','أربعون','خمسون','ستون','سبعون','ثمانون','تسعون'];
        $h = intdiv($n, 100);
        $r = $n % 100;
        if (!$r) {
            return ($h === 2 ? 'مائتا' : ($h ? $hundreds[$h] : $ones[0])).' '.$forms[0];
        }
        $prefix = $h ? $hundreds[$h].' و' : '';
        if ($r <= 2) {
            return $prefix.$forms[$r - 1];
        }
        if ($r <= 12) {
            return $prefix.$ones[$r].' '.$forms[$r <= 10 ? 2 : 3];
        }
        if ($r < 20) {
            return $prefix.$ones[$r % 10].' عشر '.$forms[3];
        }
        return $prefix.($r % 10 ? $ones[$r % 10].' و' : '').$tens[intdiv($r, 10)].' '.$forms[3];
    }
}
