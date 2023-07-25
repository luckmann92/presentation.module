<?php

namespace PresentModule\App\Services\Dependent;

use Bitrix\Main\Context;
use Bitrix\Main\Engine\CurrentUser;
use Laravel\Illuminate\App\Models\B24\Task\TaskStage;
use Laravel\Illuminate\App\Models\B24\UserField;
use Laravel\Illuminate\App\Models\Bitrix\EventType;
use Laravel\Illuminate\App\Models\Bitrix\User;
use Laravel\Illuminate\App\Models\Main\Option;
use Illuminate\Http\Request;
use PresentModule\App\Helpers\HlBlock\HlBlockHelper;
use PresentModule\App\Models\Employee;
use PresentModule\App\Models\Group\Group;
use PresentModule\App\Models\Object\ConstructionObject;
use PresentModule\App\Models\Task\NotificationSetting;
use PresentModule\App\Models\Task\Task;
use PresentModule\App\Models\Task\TaskDeadline;
use PresentModule\App\Models\Task\TaskUserFields;
use PresentModule\App\Services\Task\FieldSectionService;

class NotificationsService extends BaseDependentService
{
	protected int $groupId;
	protected int $hlBlockId;

	public function __construct()
	{
		$request = Context::getCurrent()->getRequest();
		$this->groupId = (int)$request->get('groupId');

		$hlBlock = HlBlockHelper::getHlBlockByName('Notifications');
		$this->hlBlockId = $hlBlock['ID'];
	}

	public function fields()
	{
		return [
			[
				'FIELD_NAME' => 'FIELD',
				'EDIT_FORM_LABEL' => 'Поле',
			],
			[
				'FIELD_NAME' => 'ACTIVE',
				'EDIT_FORM_LABEL' => 'Активность',
			],
			[
				'FIELD_NAME' => 'RECIPIENT',
				'EDIT_FORM_LABEL' => 'Получатель',
			],
			[
				'FIELD_NAME' => 'DELTA',
				'EDIT_FORM_LABEL' => 'Добавочное кол-во дней',
			],
			[
				'FIELD_NAME' => 'TEMPLATE',
				'EDIT_FORM_LABEL' => 'Шаблон',
			],
		];
	}

	public function rows()
	{
		$rows = [];

		if ($this->groupId) {
			$settings = NotificationSetting::where('UF_GROUP_ID', '=', $this->groupId)->get();
			$sections = app()->make(FieldSectionService::class)->index($this->groupId);
			$fields = TaskUserFields::taskUserFields()
				->with('lang')
				->where('USER_TYPE_ID', '=', 'date')
				->get();
		} else {
			$settings = NotificationSetting::where('UF_GROUP_ID', '=', 0)->get();
			$fields = UserField::where('ENTITY_ID', 'HLBLOCK_2')
				->with('lang')
				->where('USER_TYPE_ID', '=', 'date')
				->get();
		}

		if ($settings->isEmpty()) {
			$settings = collect([new NotificationSetting()]);
		}

		foreach ($settings as $fieldSettings) {
			$data = ['ID' => $fieldSettings->ID];

			$options = [];
			if ($this->groupId) {
				foreach ($sections as $section) {
					$options[] = '<option value="" disabled>----'.$section['name'].'</option>';

					foreach ($section['fields'] as $fieldCode) {
						$field = $fields->where('FIELD_NAME', $fieldCode)->first();
						if (!$field) {
							continue;
						}

						$selected = $fieldSettings->UF_FIELD_ID == $field->ID;
						$options[] = '<option value="'.$field->ID.'" '.
							($selected ? 'selected="selected"' : '').'>'.$field->lang->EDIT_FORM_LABEL.'</option>';
					}
				}
			} else {
				foreach ($fields as $field) {
					$selected = $fieldSettings->UF_FIELD_ID == $field->ID;
					$options[] = '<option value="'.$field->ID.'" '.
						($selected ? 'selected="selected"' : '').'>'.$field->lang->EDIT_FORM_LABEL.'</option>';
				}
			}

			$data['FIELD'] = '<div class="ui-ctl ui-ctl-after-icon ui-ctl-dropdown">
				<div class="ui-ctl-after ui-ctl-icon-angle"></div>
				<select class="ui-ctl-element" name="field">
					<option value="">-</option>'.implode('', $options).'
				</select>
				</div>';

			$data['ACTIVE'] = '<label class="ui-ctl-checkbox">
						<input name="active" type="checkbox" class="ui-ctl-element" '.($fieldSettings->UF_ACTIVE ? 'checked' : '').'>
					</label>';

			$departments = [
				313 => 'Отдел электроснабжения',
				316 => 'Отдел слаботочных систем',
				318 => 'СПС',
				328 => 'Отдел теплоснабжения',
				330 => 'Отдел водоотведения',
				332 => 'Отдел водоснабжения',
				336 => 'Отдел канализации',
				339 => 'Управление сноса',
			];

			$recipients = $fieldSettings && $fieldSettings->UF_RECIPIENTS ? $fieldSettings->UF_RECIPIENTS : [];

			$options = [
				'<option value="rp" '.(in_array('rp', $recipients) ? 'selected="selected"' : '').'>РП</option>',
				'<option value="gluhov" '.(in_array('gluhov', $recipients) ? 'selected="selected"' : '').'>Глухову</option>',
			];
			foreach ($departments as $id => $department) {
				$selected = in_array($id, $recipients) ? 'selected="selected"' : '';
				$options[] = '<option value="'.$id.'" '.$selected.'>'.$department.'</option>';
			}

			$data['RECIPIENT'] = '<div class="ui-ctl ui-ctl-multiple-select">
				<select class="ui-ctl-element height-70" name="recipient" multiple size="3">'.implode('', $options). '
				</select>
				</div>';

			$data['DELTA'] = '<div class="ui-ctl ui-ctl-textbox ui-ctl-wa">
					<input type="text" class="ui-ctl-element" name="delta" value="'.
						($fieldSettings ? $fieldSettings->UF_DELTA : '').'">
				</div>';

			$eventTypes = EventType::where('LID', '=', 'ru')
				->where('EVENT_TYPE', '=', 'email')
				->where('EVENT_NAME', 'like', 'TASK_NOTIFICATION_%')
				->get();

			$options = [];
			foreach ($eventTypes as $eventType) {
				$selected = $eventType->ID == $fieldSettings->UF_TEMPLATE_ID;
				$options[] = '<option value="'.$eventType->ID.'" '.
                    ($selected ?'selected="selected"' : '').'>'.$eventType->NAME.'</option>';
            }

			$data['TEMPLATE'] = '<div class="ui-ctl ui-ctl-after-icon ui-ctl-dropdown">
				<div class="ui-ctl-after ui-ctl-icon-angle"></div>
				<select class="ui-ctl-element" name="template">
					<option value="">-</option>'.implode('', $options).'
				</select>
				</div>';
			$data['TEMPLATE'] .= '<div class="delete-notification js-init-delete-notification"></div>';

			$rows[] = ['data' => $data];
		}

		return $rows;
	}

	public function gridParams()
	{
		$params = parent::gridParams();
		$params['SAVE_BUTTON'] = true;
		$params['HEAD_NAV'] = true;
		$params['FOOTER_NAV'] = true;
		$params['ADD_ROW'] = true;
		$params['GROUP_ID'] = $this->groupId;

		return $params;
	}

	public function setNotifications(Request $request)
	{
		$groupId = (int)$request->get('groupId');

		$rowIds = array_diff(array_column($request->rows, 'settingId'), ['']);

		if ($groupId) {
			$settings = NotificationSetting::where('UF_GROUP_ID', '=', $this->groupId)->get();
		} else {
			$settings = NotificationSetting::where('UF_GROUP_ID', '=', 0)->get();
		}

		$settingsForDelete = $settings->whereNotIn('ID', $rowIds);
		foreach ($settingsForDelete as $setting) {
			$setting->delete();
		}

		foreach ($request->rows as $row) {
			if ($row['settingId']) {
				$fieldSettings = NotificationSetting::find($row['settingId']);
			} else {
				$fieldSettings = new NotificationSetting();
			}

			$fieldSettings->UF_GROUP_ID = $groupId ?: '';
			$fieldSettings->UF_FIELD_ID = $row['field'];
			$fieldSettings->UF_ACTIVE = $row['active'] === 'true';
			$fieldSettings->UF_DELTA = $row['delta'];
			$fieldSettings->UF_RECIPIENTS = $row['recipients'];
			$fieldSettings->UF_TEMPLATE_ID = $row['template'];
			$fieldSettings->save();
		}
	}

	public function init()
	{
		$settings = NotificationSetting::all();
		$fields = UserField::where('ENTITY_ID', 'HLBLOCK_2')
			->where('USER_TYPE_ID', '=', 'date')
			->get();

		$today = strtotime(date('Y-m-d'));

		$objects = ConstructionObject::all();
		foreach ($objects as $object) {
			foreach ($settings as $setting) {
				$field = $fields->where('ID', $setting->UF_FIELD_ID)->first();

				$value = $object->{$field->FIELD_NAME};
				if ($value) {
					$checkedDate = strtotime($value);
					if (str_contains($setting->UF_DELTA, '-')) {
						$checkedDate = $checkedDate - $setting->UF_DELTA * 86400;
					} else {
						$checkedDate = $checkedDate + $setting->UF_DELTA * 86400;
					}

					if ($today == strtotime($checkedDate)) {
						$eventType = EventType::find($setting->UF_TEMPLATE_ID);

						$emails = [];
						foreach ($setting->UF_RECIPIENTS as $recipient) {
							$email = $this->getEmailByRecipient($recipient, $object);
							if ($email) {
								$emails[] = $email;
							}
						}

						$eventFields = [
							'EMAIL_TO' => implode(',', $emails),
							'OBJECT_NAME' => $object->UF_ADDRESS,
							'RP' => $object->responsible->UF_NAME,
						];
						\CEvent::Send($eventType->EVENT_NAME, 's1', $eventFields);
					}
				}
			}
		}

		/*$groups = Group::with('notifySettings')
			->where('ACTIVE', '=', 'Y')
			->where('CLOSED', '=', 'N')
			->get();

		$fields = TaskUserFields::taskUserFields()
			->where('USER_TYPE_ID', '=', 'date')
			->get();

		$today = strtotime(date('Y-m-d'));

		foreach ($groups as $group) {
			if ($group->notifySettings->isEmpty()) {
				continue;
			}

			foreach ($group->tasks as $task) {
				$object = $task->userFieldsData->object;

				foreach ($group->notifySettings as $setting) {
					$field = $fields->where('ID', $setting->UF_FIELD_ID)->first();

					$taskValue = $task->userFieldsData->{$field->FIELD_NAME};
					if ($taskValue) {
						$checkedDate = strtotime($taskValue);
						if (str_contains($setting->UF_DELTA, '-')) {
							$checkedDate = $checkedDate - $setting->UF_DELTA * 86400;
						} else {
							$checkedDate = $checkedDate + $setting->UF_DELTA * 86400;
						}

						if ($today == strtotime($checkedDate)) {
							$eventType = EventType::find($setting->UF_TEMPLATE_ID);

							$emails = [];
							foreach ($setting->UF_RECIPIENTS as $recipient) {
								$email = $this->getEmailByRecipient($recipient, $object, $group->ID);
								if ($email) {
									$emails[] = $email;
								}
							}

							//$setting->UF_RECIPIENTS

							$eventFields = [
								'EMAIL_TO' => implode(',', $emails),
								'OBJECT_NAME' => $object->UF_ADDRESS,
								'RP' => $object->responsible->UF_NAME,
							];
							\CEvent::Send($eventType->EVENT_NAME, 's1', $eventFields);
						}
					}


				}
			}
		}*/
	}

	protected function getEmailByRecipient($recipient, $object = null, $groupId = 0)
	{
		switch ($recipient) {
			case 'rp':
                return $object->responsible->UF_EMAIL;
            case 'nach':
				$group = Group::with('members')->find($groupId);
				$nach = $group->members->where('ROLE', '=', 'E')->first();
				$user = User::find($nach->USER_ID);
				return $user->EMAIL;
            case 'gluhov':
            	$user = User::find(5);
                return $user->EMAIL;
			default:
				$groupId = (int)$recipient;
				$group = Group::with('userFields')->find($groupId);
				$user = User::find($group->userFields->UF_TASK_INITIATOR);
				return $user->EMAIL;
		}
	}
}
