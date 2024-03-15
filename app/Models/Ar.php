<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
class Ar extends Model
{
    protected $table = 'ar_files';

    protected $fillable = ['mind_file_path', 'file_path', 'video_path', 'group_id', 'user_id', 'status'];
}
