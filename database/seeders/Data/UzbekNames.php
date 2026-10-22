<?php

namespace Database\Seeders\Data;

/**
 * Uzbek (Latin) name parts for seeded people. A full name is "<Surname> <FirstName>",
 * the surname being a root plus -ov/-ova (-yev/-yeva after a root ending in "a").
 */
class UzbekNames
{
    public const MALE_FIRST_NAMES = [
        'Jasur', 'Sardor', 'Bekzod', 'Javohir', 'Shohruh', 'Doniyor', 'Azizbek', 'Otabek', 'Sherzod', 'Diyorbek',
        'Jahongir', 'Islom', 'Abdulloh', 'Muhammadali', 'Behruz', 'Asadbek', 'Samandar', 'Ulug‘bek', 'Firdavs', 'Akmal',
        'Bobur', 'Nodirbek', 'Temur', 'Ibrohim', 'Ali', 'Umar', 'Sanjar', 'Elbek', 'Mirjalol', 'Xurshid',
    ];

    public const FEMALE_FIRST_NAMES = [
        'Madina', 'Malika', 'Dilnoza', 'Sevara', 'Zarina', 'Shahzoda', 'Mohinur', 'Gulnoza', 'Nilufar', 'Kamola',
        'Sabina', 'Robiya', 'Muslima', 'Oysha', 'Maftuna', 'Diyora', 'Charos', 'Nigora', 'Ziyoda', 'Mubina',
        'Shaxnoza', 'Feruza', 'Laylo', 'Asal', 'Durdona', 'Mahliyo', 'Iroda', 'Nozima', 'Sarvinoz', 'Hilola',
    ];

    public const SURNAME_ROOTS = [
        'Karim', 'Rahim', 'Yusup', 'Tursun', 'Abdulla', 'Ergash', 'Xolmat', 'Qodir', 'Ismoil', 'Rashid',
        'Nazar', 'Usmon', 'Sobir', 'Hamid', 'Jo‘ra', 'Toshpo‘lat', 'Mirza', 'Sultan', 'Olim', 'Norbo‘ta',
        'Haydar', 'Ahmad', 'Murod', 'Shukur', 'Botir', 'Azim', 'Komil', 'Sodiq', 'Raxmat', 'Nurmat',
    ];

    public static function surname(string $root, bool $male): string
    {
        $vowelEnding = str_ends_with($root, 'a');

        return $root.($vowelEnding ? ($male ? 'yev' : 'yeva') : ($male ? 'ov' : 'ova'));
    }

    public static function fullName(string $root, string $firstName, bool $male): string
    {
        return self::surname($root, $male).' '.$firstName;
    }

    /**
     * Lowercase ASCII for e-mail addresses: apostrophe-like marks are dropped ("Ulug‘bek" → "ulugbek").
     */
    public static function slug(string $value): string
    {
        $value = str_replace(['‘', '’', 'ʻ', 'ʼ', '`', '\''], '', $value);

        return preg_replace('/[^a-z0-9]/', '', mb_strtolower($value)) ?? '';
    }
}
