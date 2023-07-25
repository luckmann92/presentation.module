<?php
/**
 * @author Lukmanov Mikhail <lukmanof92@gmail.com>
 */
namespace PresentModule\App\Handlers\UserFields;

use Bitrix\Main\Page\Asset;
use Bitrix\Main\UI\Extension;
use Bitrix\Main\UserField\Types\StringType;
use Laravel\Illuminate\App\Models\B24\UserField;
use PresentModule\App\Models\Task\FieldSection;

class CUserTypeUfSelect extends StringType
{
    const USER_TYPE_ID = 'uf_select';

    public static function GetUserTypeDescription(): array
    {
        return array(
            "USER_TYPE_ID" => self::USER_TYPE_ID,
            "CLASS_NAME" => __CLASS__,
            "DESCRIPTION" => "Выбор пользовательских полей",
            "BASE_TYPE" => \CUserTypeManager::BASE_TYPE_STRING,
        );
    }

    public static function GetList()
    {
        $result = [];
       /* $userFields = UserField::where('ENTITY_ID', 'TASKS_TASK')
            ->orderBy('SORT')
            ->with('lang')
            ->get();*/

        $sections = FieldSection::with('fields')->get();

        foreach ($sections as $section) {
            $fields = [];
            foreach ($section->fields as $field) {
                $fieldName = $field->lang->EDIT_FORM_LABEL ?: $field->FIELD_NAME;
                $fields[] = [
                    'ID' => $field->ID,
                    'VALUE' => $fieldName . ' [' . $field->USER_TYPE_ID . '] [' . $field->FIELD_NAME . ']',
                ];
            }
            $result[] = [
                'sectionId' => $section->ID,
                'name' => $section->UF_NAME,
                'fields' => $fields
            ];
        }/*
        foreach ($userFields as $key => $field) {
            $fieldName = $field->lang->EDIT_FORM_LABEL ?: $field->FIELD_NAME;
            $result[] = [
                'ID' => $field->ID,
                'VALUE' => $fieldName . ' [' . $field->USER_TYPE_ID . '] [' . $field->FIELD_NAME . ']',
            ];
        }
*/
        return $result;
    }

    public static function GetEditFormHTML($arUserField, $arHtmlControl): string
    {
        $selectedValues = explode(',', $arHtmlControl['VALUE']);
        $sections = static::GetList();
        $html = '<select id="field_'.$arUserField['ID'].'" name="' . $arHtmlControl['NAME'] . '"';
        $html .= $arUserField['MULTIPLE'] == 'Y' ? ' multiple="multiple" size="5"' : '';
        $html .= '>';
        $html .= '<option value="">Не выбрано</option>';

        foreach ($sections as $options) {
            $html .= '<option value="" disabled>' . htmlspecialcharsbx($options['name']) . '</option>';
            foreach ($options['fields'] as $option) {
                $selected = in_array($option['ID'], $selectedValues) ? ' selected="selected"' : '';
                $html .= '<option value="' . htmlspecialcharsbx($option['ID']) . '"' . $selected . '>';
                $html .= htmlspecialcharsbx($option['VALUE']) . '</option>';
            }
        }

        $html .= '</select>';

        Extension::load('rn.chosen');
        $script = '$(document).ready(function (){$("#field_'.$arUserField['ID'].'").chosen();})';
        Asset::getInstance()->addString('<script>'.$script.'</script>');

        return $html;
    }

    public static function GetEditFormHTMLMulty($arUserField, $arHtmlControl)
    {
        $selectedValues = is_array($arHtmlControl['VALUE']) ? $arHtmlControl['VALUE'] : explode(',', $arHtmlControl['VALUE']);
        $sections = static::GetList();

        $html = '<select id="field_'.$arUserField['ID'].'" name="' . $arHtmlControl['NAME'] . '[]" multiple="multiple" size="5">';

        foreach ($sections as $options) {
            $html .= '<option value="" disabled>' . htmlspecialcharsbx($options['name']) . '</option>';
            foreach ($options['fields'] as $option) {
                $selected = in_array($option['ID'], $selectedValues) ? ' selected="selected"' : '';
                $html .= '<option value="' . htmlspecialcharsbx($option['ID']) . '"' . $selected . '>';
                $html .= htmlspecialcharsbx($option['VALUE']) . '</option>';
            }
        }
       /* foreach ($options as $option) {
            $selected = in_array($option['ID'], $selectedValues) ? ' selected="selected"' : '';
            $html .= '<option value="' . htmlspecialcharsbx($option['ID']) . '"' . $selected . '>';
            $html .= htmlspecialcharsbx($option['VALUE']) . '</option>';
        }*/

        $html .= '</select>';

        Extension::load('rn.chosen');
        $script = '$(document).ready(function (){$("#field_'.$arUserField['ID'].'").chosen();})';
        Asset::getInstance()->addString('<script>'.$script.'</script>');

        return $html;
    }


    public static function GetFilterHTML($arUserField, $arHtmlControl): string
    {
        return self::GetEditFormHTML($arUserField, $arHtmlControl);
    }

    public static function checkFields(array $userField, $value): array
    {
        return [];
    }

    public static function OnBeforeSave($arUserField, $value)
    {
        if ($arUserField['MULTIPLE'] == 'Y') {
            return implode(',', $value);
        }

        return $value;
    }

    public static function OnSearchIndex(array $arUserField): ?string
    {
        if (is_array($arUserField['VALUE'])) {
            return implode("\n", $arUserField['VALUE']);
        }

        return $arUserField['VALUE'];
    }
}
