<?php

namespace PresentModule\App\Helpers\HlBlock;

use Bitrix\Highloadblock\HighloadBlockTable as HLBT;
use Bitrix\Main\Loader;

class HlHelper
{
	protected $HLBlockId;
	protected $HlBlock;
	protected $entityClass = null;
	protected $entityCompile = null;
	protected $arFields = [];

	public function __construct($HLBlockId = false)
	{
		if (!Loader::includeModule('highloadblock')) {
			return false;
		}

		if ($HLBlockId) {
			$this->HLBlockId = $HLBlockId;
			$this->getEntityClass($HLBlockId);
		}
	}

	public function getEntityClassName()
	{
		return str_replace('Iit', '', $this->getName());
	}

	public function getName()
	{
		return $this->entityCompile->getName();
	}

	public function setHlBlockId($HLBlockId)
	{
		if (!$HLBlockId) {
			return false;
		}
		$this->HLBlockId = $HLBlockId;
		$this->getEntityClass($HLBlockId);

		return $this;
	}

	public function getHlBlockId()
	{
		return $this->HLBlockId;
	}

	public function getEntityClass($HLBlockId)
	{
		if ($HLBlockId) {
			$this->HLBlockId = $HLBlockId;
		}

		if ($this->entityClass) {
			return $this->entityClass;
		}

		if (!$this->HlBlock) {
			$this->HlBlock = HLBT::getById($this->HLBlockId)->fetch();
		}
		$this->entityCompile = HLBT::compileEntity($this->HlBlock);
		$this->entityClass = $this->entityCompile->getDataClass();

		return $this->entityClass;
	}

	public function add($arFields) {
		$rs = $this->entityClass::add($arFields);
		if (!$rs->isSuccess()) {
			return [
				'result' => false,
				'error' => $rs->geterrorMessages(),
			];
		}

		return [
			'result' => true,
			'id' => $rs->getId()
		];
	}

	public function update($id, $arFields) {
		$rs = $this->entityClass::update($id, $arFields);
		if (!$rs->isSuccess()) {
			return [
				'result' => false,
				'error' => $rs->geterrorMessages()
			];
		}

		return [
			'result' => true,
			'id' => $id
		];
	}

	public function delete($id) {
		$rs = $this->entityClass::delete($id);
		if (!$rs->isSuccess()) {
			return [
				'result' => false,
				'error' => $rs->geterrorMessages()
			];
		}

		return [
			'result' => true,
			'id' => $id
		];
	}

	public function addBlock($arFields) {
		$rs = HLBT::add($arFields);
		if (!$rs->isSuccess()) {
			return [
				'result' => false,
				'error' => $rs->geterrorMessages()
			];
		}

		return [
			'result' => true,
			'id' => $rs->getId()
		];
	}

	public function updateBlock($id, $arFields) {
		$rs = HLBT::update($id, $arFields);
		if (!$rs->isSuccess()) {
			return [
				'result' => false,
				'error' => $rs->geterrorMessages()
			];
		}

		return [
			'result' => true,
			'id' => $rs->getId()
		];
	}

	public function deleteBlock($id) {
		$rs = HLBT::delete($id);
		if (!$rs->isSuccess()) {
			return [
				'result' => false,
				'error' => $rs->geterrorMessages()
			];
		}

		return [
			'result' => true,
			'id' => $id
		];
	}

	public function addField($arFields) {
		$arPropFields = [
			'ENTITY_ID' => 'HLBLOCK_'.$this->HLBlockId,
			'FIELD_NAME' => $arFields['CODE'],
			'USER_TYPE_ID' => $arFields['TYPE'],
			'MANDATORY' => $arFields['MANDATORY'],
			'MULTIPLE' => $arFields['MULTIPLE'] ?: 'N',
			'SHOW_FILTER' => $arFields['SHOW_FILTER'],
			'SHOW_IN_LIST' => $arFields['SHOW_IN_LIST'] ?: 'Y',
			'EDIT_FORM_LABEL' => ['ru' => $arFields['NAME']],
			'LIST_COLUMN_LABEL' => ['ru' => $arFields['NAME']],
			'LIST_FILTER_LABEL' => ['ru' => $arFields['NAME']],
			'SETTINGS' => $arFields['SETTINGS'] ?: []
		];

		$obUserField  = new \CUserTypeEntity;
		$id = $obUserField->Add($arPropFields);

		if (!$id) {
			global $APPLICATION;

			return [
				'result' => false,
				'error' => $APPLICATION->GetException()
			];
		}

		return [
			'result' => true,
			'id' => $id
		];
	}

	public function updateField($id, $arFields, $entityId = false) {

		$arFields['ENTITY_ID'] = $entityId ?: 'HLBLOCK_'.$this->HLBlockId;
		$arFields['FIELD_NAME'] =  $arFields['FIELD_NAME'] ?: $arFields['CODE'];
		$arFields['USER_TYPE_ID'] =  $arFields['USER_TYPE_ID'] ?: $arFields['TYPE'];

		$obUserField  = new \CUserTypeEntity;
		$rs = $obUserField->Update($id, $arFields);

		if (!$rs) {
			global $APPLICATION;

			return [
				'result' => false,
				'error' => $APPLICATION->GetException()
			];
		}

		return [
			'result' => true,
			'id' => $id,
		];
	}

	public function deleteField($id) {
		$obUserField  = new \CUserTypeEntity;
		$rs = $obUserField->Delete($id);

		if (!$rs) {
			global $APPLICATION;

			return [
				'result' => false,
				'error' => $APPLICATION->GetException()
			];
		}

		return [
			'result' => true,
			'id' => $id
		];
	}

	public function getById($id) {
		$rs = $this->entityClass::getList([
			'select' => ['*'],
			'order' => [],
			'filter' => ['=id' => $id],
			'limit' => 1,
		]);
		return $rs->fetch();
	}

	public function getList($arOrder = [], $arFilter = [], $arSelect = ['*'], $cacheTtl = 60, $arRuntime = []) {
		$arresult = [];
		$rs = $this->entityClass::getList([
			'select' => $arSelect,
			'order' => $arOrder['sort'] ?: [],
			'filter' => $arFilter,
			'limit' => $arOrder['limit'] ?: 0,
			'offset' => $arOrder['offset'] ?: 0,
			'runtime' => $arRuntime,
			'cache' => [
				'ttl' => $cacheTtl,
				'cache_joins' => true,
			]
		]);


		$count = 0;
		$arHasMany = [];
		while ($ar = $rs->fetch()) {
			foreach ( $ar as $key => $item ) {
				if ( strpos($key, '__') !== false && empty($arresult[$ar['ID']]) ) {
					$arRelation = explode('__', $key);

					if ( $ar[$arRelation[0]] && !is_array($ar[$arRelation[0]]) ) {
						unset($ar[$arRelation[0]]);
					} elseif ( $ar[$arRelation[0].'_ID'] ) {
						unset($ar[$arRelation[0].'_ID']);
					}

					$ar[$arRelation[0]][$arRelation[1]] = $item;

					if ( $ar[$arRelation[0]] !== $key ) {
						unset($ar[$key]);
					}
				} elseif ( strpos($key, '--') !== false ) {
					if (!is_null($item)) {
						$arRelation = explode('--', $key);
						$arHasMany[$arRelation[0]][$count][$arRelation[1]] = $item;
					}else {
//                        unset($arHasMany);
					}
					unset($ar[$key]);
				}
			}

			if ( empty($arresult[$ar['ID']]) ) {
				$arresult[$ar['ID']] = $ar;
			}
			if (!empty($arHasMany)) {
				$arresult[$ar['ID']] = array_merge($arresult[$ar['ID']], $arHasMany);
			}
			$count++;
		}

		return $arresult;
	}

	public function getRowCount($arFilter = []) {
		return $this->entityClass::getList([
			'select' => ['ID'],
			'filter' => $arFilter,
		])->getSelectedRowsCount();
	}

	public function getFieldByCode($fieldCode)
	{
		foreach ($this->getFields() as $arField) {
			if ($arField['FIELD_NAME'] == $fieldCode) {
				return $arField;
			}
		}
		return false;
	}

	public function getFields($LANG_ID = LANGUAGE_ID, $entityId = false) {
		$entity = new \CUserTypeManager;
		$this->arFields = $entity->GetUserFields($entityId ?: 'HLBLOCK_' . $this->HLBlockId, 0, $LANG_ID);

		return $this->arFields;
	}

	public function getBlocks($arFilter = []) {
		$arresult = [];
		$rs = HLBT::getList(['filter' => $arFilter]);
		while ($ar = $rs->fetch()) {
			$arresult[$ar['ID']] = $ar;
		}
		return $arresult;
	}

	public function getEnumList($userFieldName, $id = false)
	{
		$arEnum = [];
		$arFilter['USER_FIELD_NAME'] = $userFieldName;

		$rsEnum = \CUserFieldEnum::GetList([], $arFilter);
		while ($ar = $rsEnum->Fetch()) {
			$arEnum[$ar['ID']] = $ar['VALUE'];
		}
		return $id ? $arEnum[$id] : $arEnum;
	}

	public function getEnumIdByValue($value, $userFieldId = false)
	{
		$arFilter = [
			'VALUE' => $value
		];
		if ($userFieldId) {
			$arFilter['USER_FIELD_ID'] = $userFieldId;
		}
		$arEnum = \CUserFieldEnum::GetList([], ['VALUE' => $value])->Fetch();
		return $arEnum['ID'];
	}

	public function getField($userFieldName, $entityId = false, $getId = true)
	{
		$arFilter['FIELD_NAME'] = $userFieldName;
		if ($entityId) {
			$arFilter['ENTITY_ID'] = $entityId;
		}
		$arField = \CUserTypeEntity::GetList([], $arFilter)->Fetch();

		if ($getId) {
			return $arField['ID'];
		}
		return $arField;
	}

	public function getEnumXmlIdById($id)
	{
		$arEnum = \CUserFieldEnum::GetList([], ['id' => $id])->Fetch();
		return $arEnum['XML_ID'];
	}

	public function getEnumNameById($id)
	{
		$arEnum = \CUserFieldEnum::GetList([], ['id' => $id])->Fetch();
		return $arEnum['VALUE'];
	}

	public function getEnumIdByXmlId($xmlId, $fieldName = '')
	{
		$arFilter = ['XML_ID' => $xmlId];
		if ($this->getHlBlockId()) {
			$fieldId = $this->getField($fieldName, 'HLBLOCK_'.$this->getHlBlockId());
			$arFilter['USER_FIELD_ID'] = $fieldId;
		}
		$arEnum = \CUserFieldEnum::GetList([], $arFilter)->Fetch();
		return $arEnum['ID'];
	}

	public function getBlockByTableName($tableName)
	{
		$rs = HLBT::getList(['filter' => ['TABLE_NAME' => $tableName]]);

		return $rs->fetch();
	}

	public function getBlockById($id) {
		return HLBT::getList(['filter' => ['ID' => $id]])->fetch();
	}

	public function getBlock() {
		return $this->getBlockById($this->HLBlockId);
	}
}
