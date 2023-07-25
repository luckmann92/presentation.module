<?php

namespace PresentModule\App\Services\Dependent;

use Bitrix\Main\Context;
use Bitrix\Main\Engine\CurrentUser;
use Laravel\Illuminate\App\Models\B24\Task\TaskStage;
use Laravel\Illuminate\App\Models\Main\Option;
use Illuminate\Http\Request;
use PresentModule\App\Models\Task\Task;
use PresentModule\App\Models\Task\TaskDeadline;

class TaskDeadlinesService extends BaseDependentService
{
	protected int $groupId;

	public function __construct()
	{
		$request = Context::getCurrent()->getRequest();
		$this->groupId = (int)$request->get('groupId');
	}

	public function fields()
	{
		return [
			[
				'FIELD_NAME' => 'STAGE',
				'EDIT_FORM_LABEL' => 'Стадия',
			],
			[
				'FIELD_NAME' => 'DAYS',
				'EDIT_FORM_LABEL' => 'Кол-во дней',
			],
		];
	}

	public function rows()
	{
		$rows = [];

		$stages = TaskStage::where('ENTITY_TYPE', 'G')
			->with('deadline')
			->where('ENTITY_ID', $this->groupId)
			->orderBy('SORT')
			->get();

		foreach ($stages as $stage) {
			$data = ['ID' => $stage->ID, 'STAGE' => $stage->TITLE];

			$value = $stage->deadline ? $stage->deadline->UF_DAYS : 0;

			$data['DAYS'] =
				'<div class="ui-ctl ui-ctl-textbox">
    				<input type="number" class="ui-ctl-element" data-stage-id="' . $stage->ID . '" 
    				onkeyup="setDeadline(event)" value="' . $value . '">
				</div>';

			$rows[] = ['data' => $data];
		}

		return $rows;
	}

	public function setDeadline(Request $request)
	{
		$deadline = TaskDeadline::firstOrCreate(['UF_STAGE' => $request->stageId]);
		$deadline->UF_DAYS = $request->days;
		$deadline->save();
	}

	public function getDeadlines(Request $request)
	{
		if (!$request->taskIds || !$request->groupId) {
			return [];
		}

		$stages = TaskStage::where('ENTITY_TYPE', '=', 'G')
			->where('ENTITY_ID', '=', $request->groupId)
			->get();

		$deadlines = TaskDeadline::whereIn('UF_STAGE', $stages->pluck('ID')->toArray());

		$tasks = Task::with(['userFieldsData' => function ($query) {
				$query->select(['VALUE_ID', 'UF_OBJECT']);
			}])
			->whereIn('ID', $request->taskIds)
			->select(['ID', 'STAGE_ID', 'GROUP_ID', 'DEADLINE'])
			->get();

		$result = [];

		$deadlinesActive = Option::where('MODULE_ID', '=', 'presentation.module')
			->where('NAME', '=', 'DEADLINES_ACTIVE')->first();
		foreach ($tasks as $task) {
			if ($task->DEADLINE && $task->userFieldsData->UF_OBJECT && $deadlinesActive && $deadlinesActive->VALUE == 'Y') {
				$taskStage = $stages->find($task->STAGE_ID);
				if (!$taskStage) {
					$taskStage = $stages->where('SYSTEM_TYPE', '=', 'NEW')->first();
				}

				$nextStages = $stages->where('ENTITY_ID', '=', $task->GROUP_ID)
					->where('SORT', '>=', $taskStage->SORT);

				$deadlineDate = strtotime($task->DEADLINE);
				$deadlines = $deadlines->whereIn('UF_STAGE', $nextStages->pluck('ID')->toArray());
				foreach ($deadlines as $deadline) {
					$deadlineDate += $deadline->UF_DAYS*60*60*24;
				}

				if (strtotime($task->userFieldsData->object->UF_CONCLUSION_MGE) < $deadlineDate) {
					$result[$task->ID] = true;
				}
			}
		}

		return $result;
	}

	public function checkDeadlineChange($id, $deadline)
	{
		$task = Task::with('userFieldsData')->find($id);

		if (!$task->GROUP_ID || !$deadline) {
			return;
		}

		$calculatedDeadline = $this->getDeadline($task);

		$deadline = substr($deadline, 0, 10);

		if ($calculatedDeadline && strtotime($deadline) > $calculatedDeadline) {
			$addedDeadline = date_diff(new \DateTime(date('d.m.Y', $calculatedDeadline)), new \DateTime($deadline))->days;
			$task->userFieldsData->UF_ADDED_DEADLINE = $addedDeadline;
			$task->userFieldsData->save();
		}
	}

	public function getDeadline(Task $task) {
		$taskStages = TaskStage::where('ENTITY_ID', $task->GROUP_ID)
			->where('ENTITY_TYPE', 'G')
			->orderBy('SORT')
			->get();

		$taskStage = $taskStages->where('ID', $task->STAGE_ID)->first();
		if (!$taskStage) {
			$taskStage = $taskStages->where('SYSTEM_TYPE', 'NEW')->first();
		}

		$deadlineStages = $taskStages->where('SORT', '<=', $taskStage->SORT);

		$days = 0;
		$deadlines = TaskDeadline::whereIn('UF_STAGE', $deadlineStages->pluck('ID')->toArray())->get();
		foreach ($deadlines as $deadline) {
			$days += $deadline->UF_DAYS;
		}

		return strtotime($task->userFieldsData->UF_DATE_RECEIVING_ID) + $days*60*60*24;
	}

	public function checkDeadline($id)
	{
		$request = Context::getCurrent()->getRequest();
		if ($request->get('action') == 'setDeadline') {
			return;
		}

		$task = Task::with('userFieldsData')->find($id);

		if (!$task->GROUP_ID) {
			return;
		}

		$taskStages = TaskStage::where('ENTITY_ID', $task->GROUP_ID)
			->where('ENTITY_TYPE', 'G')
			->orderBy('SORT')
			->get();

		$taskStage = $taskStages->where('ID', $task->STAGE_ID)->first();
		if (!$taskStage) {
			$taskStage = $taskStages->where('SYSTEM_TYPE', 'NEW')->first();
		}

		$firstStage = $taskStages->first();
		$runStages = $taskStages->where('SORT', '>', $firstStage->SORT);
		if ($runStages->find($taskStage->ID)) {
			$task->STATUS = 3;
			$task->STATUS_CHANGED_BY = CurrentUser::get()->getId();
			$task->STATUS_CHANGED_DATE = date('Y-m-d H:i:s');
			$task->DATE_START = date('Y-m-d H:i:s');
			$task->save();
		}

		$closeStages = $taskStages->whereIn('SYSTEM_TYPE', ['FAILURE', 'FINAL']);

		/*$lastStage = $taskStages->last();
		$closeStages = $taskStages->where('SORT', '=', $lastStage->SORT);
		if ($lastStage->SYSTEM_TYPE == 'FAILURE') {
			$preLastStage = $taskStages[$taskStages->count() - 2];
			$closeStages = $taskStages->where('SORT', '>=', $preLastStage->SORT);
		}*/

		if ($closeStages->find($taskStage->ID)) {
			$task->STATUS = 5;
			$task->CLOSED_BY = CurrentUser::get()->getId();
			$task->CLOSED_DATE = date('Y-m-d H:i:s');
			$task->save();

			return;
		}

		$deadlineStages = $taskStages->where('SORT', '<=', $taskStage->SORT);

		$days = 0;
		$deadlines = TaskDeadline::whereIn('UF_STAGE', $deadlineStages->pluck('ID')->toArray())->get();
		foreach ($deadlines as $deadline) {
			$days += $deadline->UF_DAYS;
        }

		if ($task->userFieldsData->UF_ADDED_DEADLINE) {
			$days += $task->userFieldsData->UF_ADDED_DEADLINE;
		}

        if ($days && $task->userFieldsData->UF_DATE_RECEIVING_ID) {
			$task->DEADLINE = date('Y-m-d H:i:s', strtotime($task->userFieldsData->UF_DATE_RECEIVING_ID) + $days*60*60*24);
			$task->save();
        }
	}

	public function getTimestamps(Request $request)
	{
		$tasks = Task::with(['userFieldsData'])
			->whereIn('ID', $request->taskIds)
			->select(['ID'])
			->get();

		$result = [];
		foreach ($tasks as $task) {
			$result[$task->ID]['UF_RSO_THOUGHT_RESPONSE_DATE'] = $task->userFieldsData->UF_RSO_THOUGHT_RESPONSE_DATE ?: ''; //Планируемая дата получения ТУ
			$result[$task->ID]['UF_AGREEMENT_OFFER_PLAN_OK_DATE'] = $task->userFieldsData->UF_AGREEMENT_OFFER_PLAN_OK_DATE ?: ''; //Планируемая дата заключения договора ТП
			$result[$task->ID]['UF_PLANNED_DATE_APPROVAL_PD'] = $task->userFieldsData->UF_PLANNED_DATE_APPROVAL_PD ?: ''; //Планируемая дата согласования ПД
			$result[$task->ID]['UF_PLANNED_DATE_APPROVAL_RD'] = $task->userFieldsData->UF_PLANNED_DATE_APPROVAL_RD ?: ''; //Планируемая дата согласования РД
			$result[$task->ID]['UF_SKP_DATE_PLAN'] = $task->userFieldsData->UF_SKP_DATE_PLAN ?: ''; //Планируемая дата заключения СКП

			if (!$task->userFieldsData->UF_OBJECT) {
				continue;
			}

			$result[$task->ID]['UF_CONCLUSION_MGE'] = $task->userFieldsData->object->UF_CONCLUSION_MGE;
		}

		return $result;
	}
}
