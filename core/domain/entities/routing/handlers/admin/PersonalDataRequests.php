<?php

namespace EventEspresso\core\domain\entities\routing\handlers\admin;

use EE_Dependency_Map;
use EventEspresso\core\domain\entities\routing\handlers\frontend\PublicRoute;

/**
 * Class PersonalDataRequests
 * loads resources and dependencies required for user privacy related logic
 *
 * @package EventEspresso\core\domain\entities\routing\handlers\admin
 * @author  Brent Christensen
 * @since   $VID:$
 */
class PersonalDataRequests extends PublicRoute
{

    /**
     * returns true if the current request matches this route
     *
     * @return bool
     * @since   $VID:$
     */
    public function matchesCurrentRequest()
    {
        return ($this->request->isAdmin() || $this->request->isAjax()) && $this->maintenance_mode->models_can_query();
    }


    /**
     * @since $VID:$
     */
    protected function registerDependencies()
    {
        $this->dependency_map->registerDependencies(
            'EventEspresso\core\domain\services\admin\privacy\policy\PrivacyPolicy',
            [
                'EEM_Payment_Method'                                       => EE_Dependency_Map::load_from_cache,
                'EventEspresso\core\domain\values\session\SessionLifespan' => EE_Dependency_Map::load_from_cache
            ]
        );
        $this->dependency_map->registerDependencies(
            'EventEspresso\core\domain\services\admin\privacy\export\ExportAttendee',
            ['EEM_Attendee' => EE_Dependency_Map::load_from_cache]
        );
        $this->dependency_map->registerDependencies(
            'EventEspresso\core\domain\services\admin\privacy\export\ExportAttendeeBillingData',
            [
                'EEM_Attendee'       => EE_Dependency_Map::load_from_cache,
                'EEM_Payment_Method' => EE_Dependency_Map::load_from_cache
            ]
        );
        $this->dependency_map->registerDependencies(
            'EventEspresso\core\domain\services\admin\privacy\export\ExportCheckins',
            ['EEM_Checkin' => EE_Dependency_Map::load_from_cache]
        );
        $this->dependency_map->registerDependencies(
            'EventEspresso\core\domain\services\admin\privacy\export\ExportRegistration',
            ['EEM_Registration' => EE_Dependency_Map::load_from_cache]
        );
        $this->dependency_map->registerDependencies(
            'EventEspresso\core\domain\services\admin\privacy\export\ExportTransaction',
            ['EEM_Transaction' => EE_Dependency_Map::load_from_cache]
        );
        $this->dependency_map->registerDependencies(
            'EventEspresso\core\domain\services\admin\privacy\erasure\EraseAttendeeData',
            ['EEM_Attendee' => EE_Dependency_Map::load_from_cache]
        );
        $this->dependency_map->registerDependencies(
            'EventEspresso\core\domain\services\admin\privacy\erasure\EraseAnswers',
            [
                'EEM_Answer'   => EE_Dependency_Map::load_from_cache,
                'EEM_Question' => EE_Dependency_Map::load_from_cache,
            ]
        );
        $this->dependency_map->registerDependencies(
            'EventEspresso\core\domain\services\admin\privacy\forms\PrivacySettingsFormHandler',
            [
                'EE_Registry' => EE_Dependency_Map::load_from_cache,
                'EE_Config'   => EE_Dependency_Map::load_from_cache
            ]
        );
    }


    /**
     * implements logic required to run during request
     *
     * @return bool
     * @since   $VID:$
     */
    protected function requestHandler()
    {
        $this->loader->getShared('EventEspresso\core\services\privacy\erasure\PersonalDataEraserManager');
        $this->loader->getShared('EventEspresso\core\services\privacy\export\PersonalDataExporterManager');
        return true;
    }
}