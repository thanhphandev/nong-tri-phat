<?php

namespace App\Models;

use Jenssegers\Mongodb\Eloquent\Model as Eloquent;

class Log extends Eloquent
{
    //
    protected $connection = 'mongodb';
    protected $collection = 'logs';
    //_id, action, id_user, email, name, id_collection, collection, data
}
