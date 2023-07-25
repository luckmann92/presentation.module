<?
namespace PresentModule\App\Helpers;

use Bitrix\Main\Context;

class Option
{
    public $arCurOptionValues = [];

    private $module_id = '';
    private $arTabs = [];
    private $arGroups = [];
    private $arOptions = [];
    private $need_access_tab = false;

    public function __construct($module_id, $arTabs, $arGroups, $arOptions, $need_access_tab = false)
    {
        $this->module_id = $module_id;
        $this->arTabs = $arTabs;
        $this->arGroups = $arGroups;
        $this->arOptions = $arOptions;
        $this->need_access_tab = $need_access_tab;

        if ($need_access_tab)
        {
            $this->arTabs[] = [
                'DIV' => 'edit_access_tab',
                'TAB' => 'Права доступа',
                'ICON' => '',
                'TITLE' => 'Настройка прав доступа'
            ];
        }

        if($this->getRequest()->get('update') == 'Y' && check_bitrix_sessid())
        {
            $this->save();
        }

        $this->getCurrentValues();
    }

    private function getRequest()
    {
        return Context::getCurrent()->getRequest();
    }

    private function save()
    {
		foreach($this->arOptions as $opt => $arOptParams)
        {
            switch ($arOptParams['TYPE']) {
                case 'FILE':
                    $arFile = $_FILES['FILE'];
                    if (!$arFile['size']) break;
                    $arParams = array("replace_space"=>"-","replace_other"=>"-","safe_chars"=>".");
                    $arFile['name'] = \Cutil::translit($arFile['name'],"ru", $arParams);
                    $fileId = \CFile::SaveFile(array_merge(['MODULE_ID' => 'sl.company.core'], $arFile), "files");
                    \Bitrix\Main\Config\Option::set($this->module_id, $opt, $fileId);
					break;
                case 'CUSTOM':
                    break;
				case 'MSELECTDRAGABLE':
					$val = str_replace('\'', '"', $this->getRequest()->get($opt));
					$val = json_decode($val, true);
					$val = serialize($val);
					\Bitrix\Main\Config\Option::set($this->module_id, $opt, $val);
					break;
                default:
                    $val = $this->getRequest()->get($opt);

                    if($arOptParams['TYPE'] == 'CHECKBOX' && $val != 'Y')
                    {
                        $val = 'N';
                    }
                    elseif (is_array($val))
                    {
                        $val = serialize($val);
                    }

                    \Bitrix\Main\Config\Option::set($this->module_id, $opt, $val);
            }
		}

        $rqRights = $this->getRequest()->get('RIGHTS');
        $rqGroups = $this->getRequest()->get('GROUPS');

        if ($rqGroups && $rqRights)
        {
            global $APPLICATION;

            foreach ($rqGroups as $key => $GROUP)
            {
                $APPLICATION->SetGroupRight($this->module_id, $GROUP, $rqRights[$key]);
            }
        }
    }

    private function getCurrentValues()
    {
        foreach($this->arOptions as $opt => &$arOptParams)
        {
            $arOptParams['CUR_VALUE'] = \Bitrix\Main\Config\Option::get($this->module_id, $opt, $arOptParams['DEFAULT']);
            switch ($arOptParams['TYPE']) {
                case 'FILE':
                    $arFile['ID'] = $arOptParams['CUR_VALUE'];
                    $arFile['PATH'] = \CFile::GetPath($arOptParams['CUR_VALUE']);
                    $arFile = array_merge($arFile, \CFile::MakeFileArray($arFile['PATH']));
                    $arOptParams['CUR_VALUE'] = $arFile;
                    break;
                case 'CUSTOM':
                    break;
                default:
                    if(in_array($arOptParams['TYPE'], ['MSELECT'])) {
                        $arOptParams['CUR_VALUE'] = unserialize($arOptParams['CUR_VALUE']);
                    }
            }
        }
    }

    public function render()
    {
        global $APPLICATION;

        foreach ($this->arOptions as $code => $arOption)
        {
            $this->arGroups[$arOption['GROUP']]['OPTIONS'][$code] = $arOption;
        }

        foreach ($this->arGroups as $code => $arGroup)
        {
            $this->arTabs[$arGroup['TAB']]['GROUPS'][$code] = $arGroup;
        }

        $tabControl = new \CAdminTabControl(
            "tabControl",
            $this->arTabs
        );

        $tabControl->Begin();?>

        <form method="POST"
              class="iit-core__form-option"
              name="<?=$this->module_id?>"
              enctype="multipart/form-data"
              action="<?=$APPLICATION->GetCurPage()?>?mid=<?=$this->module_id?>">

            <?=bitrix_sessid_post()?>

            <?foreach ($this->arTabs as &$arTab)
            {
                $tabControl->BeginNextTab();

                if ($arTab['GROUPS']) {
                    $tabHtml = new Html($this->module_id);
                    $tabHtml->getTabHtml($arTab);

                    foreach ($arTab['GROUPS'] as $arGroup)
                    {
                        echo $arGroup['HTML'];

                        foreach ($arGroup['OPTIONS'] as $arOption)
                        {
                            echo $arOption['HTML'];
                        }
                    }
                }
            }

            if ($this->need_access_tab)
            {
                require_once ($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/admin/group_rights.php");
            }

            $tabControl->Buttons();?>

            <input type="hidden" name="update" value="Y">
            <button type="submit" name="apply" class="ui-btn ui-btn-success">Сохранить</button>
            <button type="reset" name="default" class="ui-btn ui-btn-light-border">Сбросить</button>

        </form>
        <?
        $tabControl->End();
    }
}
?>
