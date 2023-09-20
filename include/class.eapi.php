<?php

use Mpdf\Tag\B;

include_once INCLUDE_DIR.'class.api.php';
include_once INCLUDE_DIR.'class.user.php';

class ExtendedApiController extends ApiController {

    protected $object_class_name = "VerySimpleModel";
    protected $object_id = 'object_id';
    protected $object_name = 'object';

    static $config;

    // force strict validation
    function validate(&$data, $format, $strict=true) {
        return parent::validate($data, $format, true);
    }

    protected function getGlobalStaff() {
        global $thisstaff;

        $user = UserApiController::$config->get('api_user');
        if (!$user) $this->exerr(401, __('No API user configured.'));

        $thisstaff = Staff::lookup($user);
        if (!$thisstaff) $this->exerr(401, __('API user not found.'));

        return $thisstaff;
    }

    protected function checkReadAccess() {
        if (!UserApiController::$config->get($this->object_name.'_read'))
            $this->exerr(401, __('No read access allowed.'));
    }

    protected function checkWriteAccess() {
        if (!UserApiController::$config->get($this->object_name.'_write'))
            $this->exerr(401, __('No write access allowed.'));
    }

    protected function validateAllow($id='', $allowNoId=false) {

        if (!($key=$this->requireApiKey()) || !$key->canCreateTickets())
            return $this->exerr(401, __('API key not authorized'));

        if (!$allowNoId && (!$id || $id == ''))
            return $this->exerr(401, __('No ticket id given'));
    }

    // always use php://input as data input
    public function isCli() { return false; }


    protected function write_response($code=201, $response=false, $content_type='application/json', $encode=true, $charset='UTF-8') {
        if($content_type == 'application/json' && $encode)
            $response = json_encode($response);

        Http::response($code, $response, $content_type, $charset);
        exit();
    }

    protected function getObjectsQuery($id) {
        $objectsQuery = $id == ''
            ? $this->object_class_name::objects()
            : $this->object_class_name::objects()->filter(array($this->object_id=>$id));
        if (!$objectsQuery)
            $this->write_response(404, __("No " . $this->object_name . " found"));

        return $objectsQuery;
    }

    protected function getObject($id) {
        $object = $this->getObjectQuery($id)->one();
        if (!$object)
            $this->write_response(404, __("No " . $this->object_name . " with given id found"));

        return $object;
    }

    protected function getObjectQuery($id) {
        $objectQuery = $this->object_class_name::objects()->filter(array($this->object_id=>$id));

        if (!$objectQuery || $objectQuery->count() < 1)
            $this->write_response(404, __("No " . $this->object_name . " with given id found"));

        return $objectQuery;
    }
}


?>
