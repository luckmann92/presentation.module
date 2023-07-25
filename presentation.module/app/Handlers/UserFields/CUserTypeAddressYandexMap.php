<?php
/**
 * @author Lukmanov Mikhail <lukmanof92@gmail.com>
 */

namespace PresentModule\App\Handlers\UserFields;

use Bitrix\Main;
use Bitrix\Main\Loader;
use Bitrix\Highloadblock\HighloadBlockTable;
use Bitrix\Main\UserField\Types\EnumType;
use Bitrix\Main\UserField\Types\StringType;
use Laravel\Illuminate\App\Models\B24\UserField;

class CUserTypeAddressYandexMap extends StringType
{
    const USER_TYPE_ID = "address_custom";

    public const DISPLAY_LIST = 'LIST';
    public const DISPLAY_CHECKBOX = 'CHECKBOX';

    public static function GetUserTypeDescription(): array
    {
        return array(
            "USER_TYPE_ID" => self::USER_TYPE_ID,
            "CLASS_NAME" => __CLASS__,
            "DESCRIPTION" => 'Адрес (Яндекс Карты)',
            "BASE_TYPE" => \CUserTypeManager::BASE_TYPE_STRING,
        );
    }

	public static function getSettingsHtml($userField, ?array $additionalParameters, $varsFromForm): string
	{
		return '';
	}
}
