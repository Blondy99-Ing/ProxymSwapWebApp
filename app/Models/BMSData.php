<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BMSData extends Model
{
    
    public $timestamps = false;
    protected $table = 'bms_data';
    protected $fillable = [
    'state',
    'seting',
     'mac_id', 
     'timestamp'];
}
