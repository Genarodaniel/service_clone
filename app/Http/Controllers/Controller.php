<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    public function deleteReports()
    {

    }
    function connection(){

        $connected = (object)[];
        Config::set('database.connections.irroba', array(
            'driver'    => 'mysql',
            'host'      => '127.0.0.1',
            'database'  => 'irroba',
            'username'  => 'irroba',
            'password'  => 'abc123456*',
            'charset'   => 'utf8',
            'collation' => 'utf8_general_ci',
        ));
        return true;
    }
}
