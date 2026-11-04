<?php

it('spells a sale price in Arabic words', function (int $number, string $words): void {
    expect(convert_number_to_arabic_words($number))->toBe($words);
})->with([
    'zero' => [0, 'صفر'],
    'teen' => [15, 'خمسة عشر'],
    'compound tens' => [61, 'واحد وستون'],
    'hundreds' => [800, 'ثمانمائة'],
    'one thousand' => [1000, 'ألف'],
    'two thousand' => [2000, 'ألفان'],
    'few thousands' => [3000, 'ثلاثة آلاف'],
    'many thousands' => [12000, 'اثنا عشر ألف'],
    'contract price' => [1861000, 'مليون وثمانمائة وواحد وستون ألف'],
    'rounded price' => [1870000, 'مليون وثمانمائة وسبعون ألف'],
    'two millions' => [2500750, 'مليونان وخمسمائة ألف وسبعمائة وخمسون'],
    'few millions' => [3000000, 'ثلاثة ملايين'],
]);
