<?php
/**
 * @author Lukmanov Mikhail <lukmanof92@gmail.com>
 */

use Bitrix\Main\Loader;
use Laravel\Illuminate\Eloquent\Database\Connection;
use PresentModule\App\Models\Object\ConstructionObject;
use PresentModule\App\Models\Reference\TuType;
use PresentModule\App\Models\Task\FieldSection;
use PresentModule\App\Services\Dependent\FieldSettingsService;
use PresentModule\App\Services\Dependent\NotificationsService;
use PresentModule\App\Services\Dependent\ProjectSectionSettingsService;
use PresentModule\App\Services\Dependent\ProjectTaskFieldRelationService;
use PresentModule\App\Services\Dependent\TaskDeadlinesService;
use PresentModule\App\Services\Dependent\TaskFieldSectionRelationService;
use PresentModule\App\Models\Reference\RsoRequestMethod;
use PresentModule\App\Models\Reference\ContractType;
use PresentModule\App\Models\Reference\PaymentType;
use PresentModule\App\Models\Reference\PaymentStatus;
use PresentModule\App\Models\Reference\SectionPD;
use PresentModule\App\Models\Reference\ServiceStatus;
use PresentModule\App\Models\Reference\TypeAgreement;
use PresentModule\App\Models\Reference\TypeSKP;

global $APPLICATION;

if (!Loader::includeModule('laravel.illuminate.component')) {
    $APPLICATION->ThrowException('Не установлен модуль "laravel.illuminate.component"!');
}

$obConnection = new Connection();
$obConnection->addConnection();

$vendorPath = $_SERVER['DOCUMENT_ROOT'].'/local/modules/presentation.module/vendor/';
if (file_exists($vendorPath.'autoload.php')) {
    require_once $vendorPath . 'autoload.php';
}

app()->bind('tdb_contract_types', ContractType::class);
app()->bind('tdb_payment_status', PaymentStatus::class);
app()->bind('tdb_payment_type', PaymentType::class);

app()->bind('tdb_service_status', ServiceStatus::class);
app()->bind('tdb_type_agreement', TypeAgreement::class);
