<?php
/**
 * @author Lukmanov Mikhail <lukmanof92@gmail.com>
 */

require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_admin_before.php');
require($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_admin_after.php');

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Page\Asset;
use PresentModule\App\Helpers\Option;

Loc::loadMessages(__FILE__);
Loc::loadMessages($_SERVER['DOCUMENT_ROOT'].BX_ROOT.'/modules/main/options.php');

$moduleId = 'presentation.module';
global $APPLICATION;

Loader::includeModule($moduleId);
\CJSCore::Init(['jquery2']);

$RIGHT = $APPLICATION->GetGroupRight($moduleId);

$APPLICATION->SetAdditionalCSS('/local/modules/presentation.module/public/css/buttons.css');
$APPLICATION->SetAdditionalCSS('/local/modules/presentation.module/public/css/forms.css');
Asset::getInstance()->addJs('/local/modules/presentation.module/public/js/optionsForm.js');

if ($RIGHT >= "R") {
	$showRightsTab = false;

	$arTabs[0] = [
		"DIV" => "edit1",
		"TAB" => 'Общие настройки модуля',
		"ICON" => "settings",
		"TITLE" => 'Общие настройки модуля',
		"PAGE_TYPE" => "connect_settings",
		"SITE_ID" => $arSite["ID"],
		"SITE_DIR" => $arSite["DIR"],
		"OPTIONS" => []
	];

	$arGroups = [
		'CORE_SETTINGS' => [
			'TITLE' => 'Основные настройки',
			'TAB' => 0
		],
        'EXCHANGE_SETTINGS' => [
            'TITLE' => 'Настройки обмена DevelopmentObject',
            'TAB' => 0
        ]
	];

	$arOptions = [
		'DEADLINES_ACTIVE' => [
			'GROUP' => 'CORE_SETTINGS',
			'TITLE' => 'Активность функционала дедлайнов',
			'TYPE' => 'CHECKBOX',
			'SORT' => 100
		]
	];

	$opt = new Option($moduleId, $arTabs, $arGroups, $arOptions, $showRightsTab);
	$opt->render();
}
