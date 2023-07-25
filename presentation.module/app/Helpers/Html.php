<?php
namespace PresentModule\App\Helpers;

class Html
{
    private $arElementsType;
    private $module_id;

    public function __construct($module_id)
    {
        $this->arElementsType = self::getElementsType();
        $this->module_id = $module_id;
    }

    function getTabHtml(&$arTab)
    {
        foreach ( $arTab['GROUPS'] as &$arGroup ) {
            self::getGroupsHtml($arGroup);
        }
    }

    function getGroupsHtml(&$arGroup)
    {
        $arGroup['HTML'] = '<tr class="heading"><td colspan="2">'.$arGroup['TITLE'].'</td></tr>';

        foreach ( $arGroup['OPTIONS'] as $code => &$arOption ) {
            self::getFieldHtml($code, $arOption);
        }
    }

    public function getFieldHtml($optionCode, &$arOption)
    {
        $val = $arOption['CUR_VALUE'];
        $opt = htmlspecialchars($optionCode);

        switch ( $arOption['TYPE'] ) {
            case 'CHECKBOX':
                $html = '<label class="ui-ctl ui-ctl-checkbox">
                            <input type="checkbox" name="'.$opt.'" id="'.$opt.'" value="Y"'.($val == 'Y' ? ' checked' : '').' 
                                '.($arOption['REFRESH'] == 'Y' ? 'data-event="refresh"' : '').' class="ui-ctl-element">
                            <div class="ui-ctl-label-text"></div>
                        </label>';
                break;
            case 'TEXT':
                $cols = $arOption['COLS'] ?: 50;
                $rows = $arOption['ROWS'] ?: 10;
                $html = '<div class="ui-ctl ui-ctl-textarea">
                            <textarea cols="'.$cols.'" rows="'.$rows.'" name="'.$opt.'" class="ui-ctl-element">'.
                    htmlspecialchars($val).'</textarea>
                        </div>';
                break;
            case 'SELECT':
            case 'MSELECT':
                $html = self::getSelectHtml($arOption, $opt, $val, $arOption['TYPE']);
                break;
            case 'FILE':
                $html = self::getFileHtml($arOption, $opt, $val);
                break;
            /*case 'USER_SELECTOR':
                $html = self::getUserSelectorHtml($arOption, $opt, $val);
                break;*/
            case 'CUSTOM':
                $html = $arOption['VALUE'];
                break;
            default:
                $size = $arOption['SIZE'] ?: 25;
                $maxLength = $arOption['MAXLENGTH'] ?: 255;
                $html = '<div class="ui-ctl ui-ctl-textbox">
                            <input type="'.($arOption['TYPE'] == 'INT' ? 'number' : '').'" size="'.$size.'" 
                            maxlength="'.$maxLength.'" value="'.$val.'" name="'.$opt.'" class="ui-ctl-element">
                        </div>';
                break;
        }

        if ( $arOption['REFRESH'] == 'Y' && $arOption['TYPE'] != 'CHECKBOX' && $arOption['TYPE'] != 'SELECT' ) {
            $html .= '<button type="submit" name="refresh" data-event="refresh" class="ui-btn ui-btn-xs ui-btn-primary-dark">OK</button>';
        }

        $arOption['HTML'] = '<tr><td valign="top" width="40%" class="adm-detail-content-cell-l">'.$arOption['TITLE']
            .'</td><td valign="top" nowrap class="adm-detail-content-cell-r">'.$html.'</td></tr>';
    }

    static function getElementsType()
    {
        return [
            'CHECKBOX'    => [],
            'TEXT'        => [
                'COLS' => 25,
                'ROWS' => 5
            ],
            'SELECT'      => [],
            'MSELECT'     => [],
            'COLORPICKER' => [
                'FIELD_SIZE' => 25
            ],
            'FILE'        => [
                'FIELD_SIZE'  => 25,
                'BUTTON_TEXT' => '...'
            ],
            'CUSTOM'      => [],
            'DEFAULT'     => [
                'SIZE'      => 25,
                'MAXLENGTH' => 255
            ],
            'NOTES'       => []
        ];
    }

    private function getSelectHtml($arOption, $opt, $val, $type = 'SELECT')
    {
        $size = $arOption['SIZE'] ?: 5;
        if ( $type == 'MSELECT' ) {
            $mSelectHtml = $type == 'MSELECT' ? 'multiple size="'.$size.'"' : '';
            $html = '<div class="ui-ctl ui-ctl-multiple-select">
                        <select name="'.$opt.'[]" id="'.$opt.'[]" '.$mSelectHtml.' class="ui-ctl-element">';
        } else {
            $refresh = $arOption['REFRESH'] == 'Y' ? 'data-event="refresh"' : '';
            $html = '<div class="ui-ctl ui-ctl-after-icon ui-ctl-dropdown">
                        <div class="ui-ctl-after ui-ctl-icon-angle"></div>
                        <select name="'.$opt.'" id="'.$opt.'" '.$refresh.' class="ui-ctl-element">';
        }

        $arReference = [];
        if ( $arOption['VALUES']['REFERENCE'] && is_array($arOption['VALUES']['REFERENCE']) ) {
            $arReference = $arOption['VALUES']['REFERENCE'];
        }

        $arReferenceId = [];
        if ( $arOption['VALUES']['REFERENCE_ID'] && is_array($arOption['VALUES']['REFERENCE_ID']) ) {
            $arReferenceId = $arOption['VALUES']['REFERENCE_ID'];
        }

        if ( $arOption['DEFAULT'] ) {
            $html .= '<option value="">'.$arOption['DEFAULT'].'</option>';
        }

        foreach ( $arReference as $key => $value ) {
            if ( $type == 'MSELECT' ) {
                $selected = in_array($arReferenceId[$key], $val) ? 'selected' : '';
            } else {
                $selected = $arReferenceId[$key] === $val ? 'selected' : '';
            }
            $html .= '<option '.$selected.' value="'.$arReferenceId[$key].'">'.$value.'</option>';
        }
        $html .= '</select></div>';

        return $html;
    }

    private function getFileHtml($arOption, $opt, $val)
    {
        return '<label class="ui-ctl ui-ctl-file-btn">
                    <input type="file" id="'.$opt.'" name="'.$opt.'"
                        '.($arOption['FIELD_READONLY'] == 'Y' ? 'readonly' : '').' class="ui-ctl-element">
                    <div class="ui-ctl-label-text">Добавить файл</div>
                    <div class="iit-core__file-label">'.$val['name'].'</div>
                </label>';
    }

    public static function getUserSelectorHtml($arOption, $opt, $val)
    {
        global $APPLICATION;
        ob_start();
        $APPLICATION->IncludeComponent(
            'bitrix:system.field.edit',
            'employee_custom',
            [
                'arUserField'   => [
                    'USER_TYPE'  => 'employee_custom',
                    'FIELD_NAME' => $opt,
                    'MULTIPLE'   => 'N',
                    'SETTINGS'   => [],
                    'VALUE'      => $val,
                ],
                'bVarsFromForm' => false,
                'form_name'     => 'iitcompany.mailsyncsettings',
            ],
            false
        );
        $userSelectContent = ob_get_contents();
        ob_end_clean();

        return $userSelectContent;
    }
}
