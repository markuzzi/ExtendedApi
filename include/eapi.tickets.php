<?php

include_once INCLUDE_DIR.'class.api.php';
include_once INCLUDE_DIR.'api.tickets.php';
include_once INCLUDE_DIR.'class.ticket.php';
require_once EXTENDEDAPI_INCLUDE_DIR.'class.eapi.php';

class TicketExtendedApiController extends ExtendedApiController {

    protected $object_class_name = "Ticket";
    protected $object_id = 'ticket_id';
    protected $object_name = 'ticket';

    private TicketApiController $controller;

    function __construct() {
        parent::__construct();
        $this->controller = new TicketApiController();
    }

    function getRequestStructure($format, $data=null) {
        return $this->controller->getRequestStructure($format, $data);
    }

    function validate(&$data, $format, $strict=true) {
        return $this->controller->validate($data, $format, true);
    }

    function listTickets($id='', $print_as_json=true) {
        $this->validateAllow($id, true);
        $this->checkReadAccess();

        $tickets = $this->getObjectsQuery($id);

        $tickets->values(
            'ticket_id',
            'number',
            'cdata__subject',
            'staff_id', 'staff__firstname', 'staff__lastname',
            'team__name', 'team_id',
            'lock__lock_id', 'lock__staff_id',
            'isoverdue',
            'status_id', 'status__name', 'status__state',
            'source',
            'dept_id', 'dept__name',
            'user_id', 'user__default_email__address', 'user__name',
            'lastupdate'
        );

        $tickets->order_by('-created');

        if (!$print_as_json) return $tickets;

        $this->write_response(200, $tickets->all());
    }

    function get($id='') {
        $this->validateAllow($id);
        $this->checkReadAccess();

        return $this->listTickets($id);
    }

    function thread($id='') {
        $this->validateAllow($id);
        $this->checkReadAccess();

        $threads = TicketThread::objects()
                    ->filter(array('ticket__ticket_id' => $id))
                    ->first()->getEntries()
                    ->values();

        $this->write_response(200, $threads->all());
    }

    function fields($id='') {
        $this->validateAllow($id);
        $this->checkReadAccess();

        $result = array();

        foreach (DynamicFormEntry::forTicket($id) as $form) {
            $answersQuery = $form->getAnswers()->values(
                'entry_id', 'value', 'value_id',
                'field_id', 'field__form_id', 'field__type', 'field__label', 'field__name', 'field__configuration', 'field__sort', 'field__hint'
            );

            $result = array_merge($result, $answersQuery->all());
        }

        $this->write_response(200, $result);
    }

    function delete($id='') {
        $this->validateAllow($id);
        $this->checkWriteAccess();

        $ticket = $this->getObject($id);
        $result = $ticket->delete();

        if($result)
            $this->write_response(200, $result);
        else
            $this->exerr(500, _S("unknown error"));
    }

    function create() {
        $this->validateAllow('', true);
        $this->checkWriteAccess();

        $data = $this->getRequest('json');

        $ticket = $this->controller->createTicket($data);

        if ($ticket)
            $this->write_response(201, $ticket->getId());
        else
            $this->exerr(500, _S("unknown error"));
    }

    function update($id) {
        $this->validateAllow($id);
        $this->checkWriteAccess();

        $staff = $this->getGlobalStaff();

        $ticket = $this->getObject($id);

        $data = $this->getRequest('json');

        $ticket_fields = array(
            'topicId',
            'slaId',
            'duedate',
            'user_id',
            'source',
            'note',
            'status',
            'priority',
            'subject',

            'forms'
        );

        $update_data = array();
        foreach ($ticket_fields as $ticket_field) {
            if (isset($data[$ticket_field])) {
                $update_data[$ticket_field] = $data[$ticket_field];
            }
        }

        // set current values if no update values exist
        if (!isset($update_data['staffId'])) $update_data['staffId'] = $staff->getId();
        if (!isset($update_data['slaId'])) $update_data['slaId'] = $ticket->getSLAId();
        if (!isset($update_data['source'])) $update_data['source'] = $ticket->getSource();
        if (!isset($update_data['duedate'])) $update_data['duedate'] = $ticket->getDueDate();

        // set list of forms
        $forms = DynamicFormEntry::forTicket($id, true);
        foreach ($forms as $form) {
            $update_data['forms'] ??= array();
            array_push($update_data['forms'], $form->getId());

            $fields = $form->getFields();
            foreach ($fields as $field) {
                $field_name = $field->ht['name'];

                // set new value to form
                if (isset($data[$field_name])) {
                    $field->setValue($data[$field_name]);
                }

                // create form post data for comparison of before and after values
                if ($field::$widget == 'CheckboxWidget') {
                    $update_data['_field-checkboxes'] ??= array();

                    // take new value if existing
                    if (isset($data[$field->ht['name']])) {
                        $checkbox_value = $data[$field->ht['name']];
                        if ($checkbox_value === 1 || $checkbox_value === true) {
                            $update_data['_field-checkboxes'][] = $field->getId();
                        }
                    }

                    // take old value
                    else if ($field->getAnswer()->getValue()) {
                        $update_data['_field-checkboxes'][] = $field->getId();
                    }
                }

                // for all but checkboxes
                else if ($field->getAnswer()) {
                    $key = $field->ht['name'];
                    $value = $data[$key] ?? $field->getAnswer()->getValue();
                    $update_data[$key] = $value;
                }
            }
        }

        // set global POST variable to store dynamic form data
        global $_POST;
        $_POST = $update_data;

        // now update
        $errors=array();
        $result = $ticket->update($update_data, $errors);

        if ($result && count($errors)<1)
            // return $result;
            $this->write_response(204, $result);
        else
            $this->exerr(500, print_r($errors, true));
            // return print_r($errors, true);
    }

    function postNote($id='') {
        global $cfg;

        $this->validateAllow($id);
        $this->checkWriteAccess();

        $staff = $this->getGlobalStaff();
        $ticket = $this->getObject($id);
        $vars = $this->getRequest('json');

        $vars['staffId'] = $staff->getId();
        $vars['source'] ??= 'API';

        // // Data from Request:
        // poster
        // ip_address
        // reply
        // ccs
        // reply_status_id // set new status
        // reply_to
        // from_email_id
        // signature
        // files
        // attachments[] = {cid => }
        // source
        // title
        // response (body)

        $errors = array();
        $alert = strcasecmp('none', $vars['reply-to']);

        $response = $ticket->postReply($vars, $errors, ($alert && !$cfg->notifyONNewStaffTicket()));

        if ($response && count($errors)<1)
            $this->write_response(201, $response->getId());
        else
            $this->exerr(500, print_r($errors, true));
    }

    function postInternalNote($id='') {
        global $cfg;

        $this->validateAllow($id);
        $this->checkWriteAccess();

        $staff = $this->getGlobalStaff();
        $ticket = $this->getObject($id);
        $vars = $this->getRequest('json');

        $vars['staffId'] = $staff->getId();
        $vars['source'] ??= 'API';

        // // Data from Request:
        // poster
        // ip_address
        // reply
        // note_status_id // set new status
        // activity
        // files
        // attachments[] = {cid => }
        // title
        // response (body)


        $errors = array();
        $alert = strcasecmp('none', $vars['reply-to']);

        $response = $ticket->postNote($vars, $errors, ($alert && !$cfg->notifyONNewStaffTicket()));

        if ($response && count($errors)<1)
            $this->write_response(201, $response->getId());
        else
            $this->exerr(500, print_r($errors, true));
    }
}


?>
