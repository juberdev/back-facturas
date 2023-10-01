<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RoleDetails extends Model
{
    use HasFactory;
    protected $table = 'roles_details';

    protected $fillable = [
        'menu_id',
        'rol_id',
    ];

    public function menu()
    {
        return $this->belongsTo('App\Menu', 'menu_id');
    }

    public function role()
    {
        return $this->belongsTo('App\Role', 'rol_id');
    }
}
