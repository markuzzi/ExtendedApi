<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of plugin
 *
 * @author Markus Luckey <luckey at kernblick.net>
 */

set_include_path(get_include_path().PATH_SEPARATOR.dirname(__file__).'/include');

return array(
    'id' =>             'kernblick:extendedapi', # notrans
    'version' =>        '0.1',
    'name' =>           'ExtendedApi Manager',
    'author' =>         'Markus Luckey',
    'description' =>    'Provides extended API capability.',
    'url' =>            'http://www.kernblick.de/osticket/plugins/extendedapi',
    'plugin' =>         'extendedapi.php:ExtendedApiPlugin'
);

?>