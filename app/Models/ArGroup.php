<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class ArGroup extends Model
{
    protected $table = 'ar_groups';

    protected $fillable = ['name', 'user_id', 'source'];

    public function ar()
    {
        return $this->hasMany(Ar::class, 'group_id', 'id');
    }
}
