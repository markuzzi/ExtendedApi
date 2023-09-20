<?php

include_once INCLUDE_DIR.'class.api.php';
include_once INCLUDE_DIR.'class.user.php';
require_once EXTENDEDAPI_INCLUDE_DIR.'class.eapi.php';

class UserApiController extends ExtendedApiController {

    protected $object_class_name = "User";
    protected $object_id = 'id';
    protected $object_name = 'user';

    function list($id='', $json=true) {
        $this->validateAllow($id, true);
        $this->checkReadAccess();

        $users = $this->getObjectsQuery($id);

        $users->values(
            'id', 'name', 'default_email__address', 'account__id',
            'account__status', 'created', 'updated'
        );

        $users->order_by('-created');

        if (!$json) return $users;

        $this->write_response(200, $users->all());
    }

    function get($id='') {
        $this->validateAllow($id);
        $this->checkReadAccess();

        return $this->list($id);
    }

    function delete($id='') {
        $this->validateAllow($id);
        $this->checkWriteAccess();

        $user = $this->getObject($id);
        $result = $user->delete();

        if($result)
            $this->write_response(200, $result);
        else
            $this->exerr(500, _S("unknown error"));
    }

    function create() {
        $this->validateAllow('', true);
        $this->checkWriteAccess();

        $vars = $this->getRequest('json');
        $user = User::lookupByEmail($vars['email']);

        if ($user)
            $this->exerr(500, _S("user already exists"));

        $user = User::fromVars($vars, true);

        if ($user)
            $this->write_response(201, $user->getId());
        else
            $this->exerr(500, _S("unknown error"));
    }

    function update($id='') {
        $this->validateAllow('', true);
        $this->checkWriteAccess();

        $vars = $this->getRequest('json');
        $user = $id !== '' ?
                User::lookup($id) :
                User::lookupByEmail($vars['email']);

        if (!$user)
            $this->exerr(500, _S("user does not exist"));

        $user = User::fromVars($vars, false, true);
        $result = array('email'=>$user->getEmail());

        if ($user)
            $this->write_response(201, $result);
        else
            $this->exerr(500, _S("unknown error"));
    }

    function createAccount($id='') {
        $this->validateAllow('', true);
        $this->checkWriteAccess();

        $vars = $this->getRequest('json');
        $user = $id !== '' ?
                User::lookup($id) :
                User::lookupByEmail($vars['email']);

        if (!$user)
            $this->exerr(500, _S("user does not exist"));

        $errors = array();
        $account = UserAccount::register($user, $vars, $errors);

        if ($user || !$errors)
            $this->write_response(201, $account->getId());
        else
            $this->exerr(500, print_r($errors, true));
    }
}


?>
