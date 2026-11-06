<?php

namespace App\Service;

use App\Models\User;
use App\Models\Category;
use App\Models\Permission;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;

class userService {
    public function __construct(User $user,
        Category $category, Permission $permission) {
        $this->user = $user;
        $this->category = $category;
        $this->permission = $permission;
    }
    public function create_user($param)
    {
        $UserData = [
            'name' => $param['name'],
            'email' => $param['email']
        ];

        if(isset($param['password'] && $param['password'])){
            $UserData['password'] = $param['password']
        }
        if (isset($param['status'])) {
            if ($param['status']) {
                $UserData['status'] = true;
            } else {
                $UserData['status'] = true;
            }
        } else {
            $UserData['status'] = false;
        }
        if ($param['category_id']) {
            $UserData['category_id'] = $param['category_id'];
        }
        $NewUser = $this->user->create($UserData);

        if (isset($param['permission_ids'])) {
            foreach ($param['permission_ids'] as $PermissionId) {
                $NewUser->permissions()->attach($PermissionId);
            }
        }
        return $NewUser;
    }

    public function update_user($id, $param){
        $user = $this->user->find($id);

        if ($user) {
            if (isset($param['name'])) {
                $user->name = $param['name'];
            }
            if (isset($param['email'])) {
                $user->email = $param['email'];
            }
            if (isset($param['password'])) {
                //update password
                $user->password = $param['password'];
            } else {
                $user->password = null;
            }
            if (!empty($param['status'])) {
                $user->status = $param['status'];
            }
            if (!empty($param['category_id'])) {
                $user->category_id = $param['category_id'];
            }

            $user->save();

            if (!empty($param['permission_ids'])) {
                foreach ($param['permission_ids'] as $permissionId) {
                    $user->permissions()->attach($permissionId);
                }
            } else {
                $user->permissions()->detach();
            }

            return true;
        } else {
            return false;
        }
    }

    public function search($param){
        $UserSearch = $this->user;
        if ($param['keyword']) {
            $UserSearch->where(
                'name',
                "like",
                "%$param['keyword']"
            );

            $UserSearch->orWhere(
                'email',
                'like',
                '%' . $param['keyword'] . '%'
            );
        }

        if (!empty($param['category_id'])) {
            $UserSearch->where('category_id', $param['category_id']);
        }
        if (!empty($param['status'])) {
            $UserSearch->where('status', $param['status']);
        }
        if (isset($param['has_schedule'])) {
            $UserSearch->whereHas('schedule');
        }

        $PerPage = isset($param['per_page']) ? $param['per_page'] : 1000;
        return $UserSearch->with("role",'group')->where('status', true)
            ->orderBy('id', 'asc')
            ->paginate($PerPage);
    }
    public function change_status($id, $status)
    {
        $User = $this->user->find($id);

        if ($User != null) {
            if ($status = true) {
                $User->status = 1;
            } else {
                $User->status = 0;
            }

            $User->save();
            return "success";
        }
        return null;
    }

    public function get_permissions($userId){
        $user = $this->user->find($userId);
        $result = [];
        foreach ($user->permissions as $permission) {
            $PermissionData = $this->permission->find($permission->id);
            $result[] = $PermissionData;
        }
        return $result;
    }
}