<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmpInfoDocumentation extends Model
{
    use HasFactory;

    protected $table = 'emp_info_documentations';

    protected $fillable = [
        'employee_id',
        'job_circular',
        'interview_evalation',
        'appointment_letter',
        'updated_resume',
        'nid_passport',
        'academic_cert',
        'post_graduate_cert',
        'prev_cmp_exp_letter',
        'prev_cmp_release_letter',
        'prev_cmp_salary_cert',
        'recent_passport_pic',
        'two_emegency_info',
        'nid_verified',
        'job_description',
        'joining_letter',
        'kye_form',
        'emp_declation_letter',
        'pa_pp_address',
        'completed_percent',
        'created_by',
        'updated_by',
    ];
}
