<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of config
 *
 * @author Markus Luckey <luckey at kernblick.de>
 */

require_once(INCLUDE_DIR.'/class.plugin.php');
require_once(INCLUDE_DIR.'/class.forms.php');

class ExtendedApiConfig extends PluginConfig{
    function getOptions() {

        $form_choices = array('0' => '--None--');
        foreach (DynamicForm::objects()->filter(array('type'=>'G')) as $group)
        {
            $form_choices[$group->get('id')] = $group->get('title');
        }

        $staffs = Staff::objects()->values('username', 'firstname', 'lastname')->all();
        $usernames = [];
        foreach ($staffs as $staff) {
            $usernames[$staff['username']] = $staff["firstname"] . " " . $staff["lastname"];
        }

        return array(
            'extendedapi_enable' => new BooleanField(array(
                'id'    => 'extendedapi_enable',
                'label' => 'Enable Extended API',
                'configuration' => array(
                    'desc' => 'Enable the extended REST API.')
            )),
            'api_user' => new ChoiceField(array(
                'id'    => 'api_user',
                'label' => 'The User for API',
                'configuration' => array(
                    'desc' => 'Which user shall be used for API operations?'),
                'choices' => $usernames)
            ),
            'ticket_read' => new BooleanField(array(
                'id'    => 'ticket_read',
                'label' => 'Ticket API - Read Access',
                'configuration' => array(
                    'desc' => 'Allow to read tickets and related models.')
            )),
            'ticket_write' => new BooleanField(array(
                'id'    => 'ticket_write',
                'label' => 'Ticket API - Write Access',
                'configuration' => array(
                    'desc' => 'Allow to write tickets and related models.')
            )),
            'user_read' => new BooleanField(array(
                'id'    => 'user_read',
                'label' => 'User API - Read Access',
                'configuration' => array(
                    'desc' => 'Allow to read users and related models.')
            )),
            'user_write' => new BooleanField(array(
                'id'    => 'user_write',
                'label' => 'User API - Write Access',
                'configuration' => array(
                    'desc' => 'Allow to write users and related models.')
            ))
        );
    }

      function pre_save(&$config, &$errors) {
        global $msg;

        if (!$errors)
            $msg = 'Configuration updated successfully';

        return true;
    }
}
?>