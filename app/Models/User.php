<?php

namespace App\Models;

use Illuminate\Auth\Authenticatable;
use Illuminate\Support\Facades\Request;
use Laravel\Lumen\Auth\Authorizable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Passport\HasApiTokens;

class User extends Model implements AuthenticatableContract, AuthorizableContract
{
    use Authenticatable, Authorizable, SoftDeletes, HasApiTokens;

    const ADMIN_ROLE = 'ADMIN_USER';
    const BASIC_ROLE = 'BASIC_USER';

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'users';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'uid',
        'firstName',
        'lastName',
        'middleName',
        'email',
        'password',
        'address',
        'zipCode',
        'username',
        'city',
        'state',
        'country',
        'phone',
        'mobile',
        'role_id',
        'isActive',
        'profileImage',
        'device_token'
    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [
        'password',
    ];

    /**
     * @return bool
     */
    public function isAdmin()
    {
        return (isset($this->role) ? $this->role : self::BASIC_ROLE) == self::ADMIN_ROLE;
    }

    public function profile()
    {
        return $this->hasOne('App\Models\Profile');
    }

    public function transaction()
    {
        return $this->hasMany('App\Models\Transaction');
    }

    public function student()
    {
        return $this->hasMany('App\Models\Sessions', 'student_id');
    }

    public function tutor()
    {
        return $this->hasMany('App\Models\Sessions', 'tutor_id');
    }

    public function role()
    {
        return $this->belongsTo('App\Models\Role');
    }

    public function findForPassport($username) {
        if(Request::input('role') === 'admin'){
            return self::where('email', $username)->first();
        }else{
            $request  = Request::all();
            $user = self::where('phone', $username)->first();
            if(isset($request['device_token']) && !empty($request['device_token'])){
                self::where('id','=',$user->id)->update(['device_token'=>$request['device_token']]);
            }
            return $user;
        }
    }
    
    public function findBookedUser($tutor_id){
        $user = User::select('users.*')
                ->select('users.*','programmes.name as p_name','subjects.name as s_name'
                    ,'programmes.id as p_id','subjects.id as s_id','profiles.is_group',
                    'profiles.is_home as t_is_home')
                ->leftjoin('profiles','profiles.user_id','=','users.id')
                ->leftjoin('programmes','programmes.id','=','profiles.programme_id')
                ->leftjoin('subjects','subjects.id','=','profiles.subject_id')
                ->where('users.role_id','=',2)
                ->where('users.id','=',$tutor_id)
                ->first();
        return $user;
    }
}
