<?php
namespace App\Http\Controllers\Push\Models;

use Illuminate\Database\Eloquent\Model;

class CloudMessages extends Model
{
    	
	protected $fillable = ['uuid'];
	
    protected $table		= 'cloud_messages';
	//protected $dateFormat	= 'U';
	protected $primaryKey = 'id';
	
	//const CREATED_AT = 'created_at';
	//const UPDATED_AT = null;
}

