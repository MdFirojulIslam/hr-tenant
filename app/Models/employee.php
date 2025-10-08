<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class employee extends Model
{
    use HasFactory;
    protected $fillable = [
        'Company_Id',
        'Employee_Id',
        'Card_No',
        'Full_Name',
        'Father_Name',
        'Mother_Name',
        'Spouse_Name',
        'Marital_Status',
        'DOB',
        'Birth_Date',
        'Place_of_Birth',
        'Present_Address',
        'Permanent_Address',
        'Contact_No',
        'Emergency_Contact',
        'Gender',
        'Personal_Email',
        'Official_Email',
        'Blood_Group_Id',
        'Religion_Id',
        'Nationality',
        'NID',
        'created_by',
        'updated_by',
    ];

    public function company()
    {
        return $this->belongsTo(company::class, 'Company_Id');
    }
    public function blood()
    {
        return $this->belongsTo(blood_group::class, 'Blood_Group_Id');
    }
    public function religion()
    {
        return $this->belongsTo(religion::class, 'Religion_Id');
    }

    public function academic()
    {
        return $this->hasMany(academic_info::class, 'EID');
    }
    public function training()
    {
        return $this->hasMany(training_info::class, 'EID');
    }
    public function experience()
    {
        return $this->hasMany(work_experience::class, 'EID');
    }
    public function official()
    {
        return $this->hasOne(official::class, 'EID');
    }
    public function nominee()
    {
        return $this->hasMany(nominee::class, 'EID');
    }
    public function child()
    {
        return $this->hasMany(child::class, 'EID');
    }

    public function empImg()
    {
        return $this->hasOne(emp_img::class, 'EID');
    }


    public function leave()
    {
        return $this->hasMany(leave::class);
    }
    public function leaveBalance()
    {
        return $this->hasMany(leave_balance::class);
    }

    public function attendence()
    {
        return $this->hasMany(attendence::class, 'EID');
    }
    
    public function employee_asset()
    {
        return $this->hasOne(employee_asset::class, 'EID');
    }

    public function town_belong()
    {
        return $this->hasMany(town_belong::class, 'EID');
    }
    
    public function app_attendance()
    {
        return $this->hasOne(town_belong::class, 'EID');
    }

    public function vehicle_driver()
    {
        return $this->hasOne(vehicle::class, 'Driver_Id');
    }
    public function vehicle_supervisor()
    {
        return $this->hasOne(vehicle::class, 'Supervisor_Id');
    }


    //added by firoj
     public function loan()
    {
        return $this->hasMany(Loan::class, 'employee_id', 'id');
    }

    public function employee_salary()
    {
        return $this->hasOne(employee_salary::class, 'EID');
    }

    public function salary_earning()
    {
        return $this->hasMany(salary_earning::class, 'employee_id');
    }

    public function salary_deduction(){
        return $this->hasMany(salary_deduction::class,'employee_id');
    }
}
