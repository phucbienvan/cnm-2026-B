<?php

namespace App\Service;

use App\Models\User;
use App\Models\Category;
use App\Models\Permission;

class UserService {
    public function __construct(
        User $user,
        Category $category, 
        Permission $permission
    ){
        $this->user = $user;
        $this->category = $category;
        $this->permission = $permission;
    }

    public function createUser($params)
    {
        $userData = [
            'name' => $params['name'],
            'email' => $params['email']
        ];

        if(isset($params['password'])){
            $userData['password'] = $params['password'];
        }

        $status = isset($param['status']) ?? false;
        $newUser = $this->user->create($userData);

        if (isset($params['permission_ids'])) {
            foreach ($params['permission_ids'] as $permissionId) {
                $newUser->permissions()->attach($permissionId);
            }
        }

        return $newUser;
    }

    public function updateUser($id, $params)
    {
        $user = $this->user->find($id);

        if (!$user) {
            return false;
        }

        if (isset($params['name'])) {
            $user->name = $params['name'];
        }

        if (isset($params['email'])) {
            $user->email = $params['email'];
        }

        if (isset($params['password'])) {
            $user->password = $params['password'];
        } else {
            $user->password = null;
        }

        if (!empty($params['status'])) {
            $user->status = $params['status'];
        }

        if (!empty($params['category_id'])) {
            $user->category_id = $params['category_id'];
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
    }

    public function search($params)
    {
        $userSearch = $this->user;

        if ($params['keyword']) {
            $userSearch->where('name', 'like', '%' . $params['keyword'] . '%');

            $userSearch->orWhere('email', 'like', '%' . $params['keyword'] . '%');
        }

        if (!empty($param['category_id'])) {
            $userSearch->where('category_id', $params['category_id']);
        }

        if (!empty($params['status'])) {
            $userSearch->where('status', $params['status']);
        }

        if (isset($param['has_schedule'])) {
            $userSearch->whereHas('schedule');
        }

        $perPage = isset($param['per_page']) ? $params['per_page'] : 1000;

        return $userSearch->with("role",'group')->where('status', true)
            ->orderBy('id', 'asc')
            ->paginate($perPage);
    }

    public function changeStatus($id, $status)
    {
        $user = $this->user->find($id);

        if ($user == null) {
            return null;
        }

        if ($status == true) {
            $user->status = 1;
        } else {
            $user->status = 0;
        }

        $user->save();

        return 'success';
    }

    public function getPermissions($userId){
        $user = $this->user->find($userId);
        $result = [];
        foreach ($user->permissions as $permission) {
            $permissionData = $this->permission->find($permission->id);
            $result[] = $permissionData;
        }

        return $result;
    }
}
