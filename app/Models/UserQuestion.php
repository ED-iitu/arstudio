<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class UserQuestion extends Model
{
    protected $table = 'user_questions';

    protected $fillable = ['name', 'email', 'phone', 'message'];
}
