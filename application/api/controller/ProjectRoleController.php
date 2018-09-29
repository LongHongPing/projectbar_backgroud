<?php
/*
 * 后台
 * 添加项目所需角色信息
 * 修改项目所需角色信息
 * 查询项目所需角色信息
 * 删除项目所需角色信息
 */

namespace app\api\controller;

use think\view;
use think\controller;

class ProjectRoleController extends Common {
    public function index(){
        $view = new View();
        return $view -> fetch('index');
    }

    //增加角色
    public function addRole(){
        //实例化
        $project_role = model('project_role');

        $project_role -> project_id = input('post.project_id');
        $project_role -> role = input('post.role');
        $project_role -> create_time = date("Y-m-d H:i:s",time());

        //规则验证
        $result = $project_role
            -> validate(true)
            -> allowField(['role'])
            -> save();
        if($result != true){
            $this -> _json(['error' => '输入信息有误','code' => 400]);
        }

        //写入数据库
        if($project_role -> save()){
            $this -> _json(['success' => '添加成功','code' => 200]);
        }
        else{
            $this -> _json(['error' => '添加失败','code' => 400]);
        }
    }

    //添加所属项目对应角色技能
    public function addRoleSkill(){
        $role_require = model("role_require");

        $role_require -> role_id = input('post.role_id');
        $role_require -> skill = input ('post.skill');
        $role_require -> skill_level = input('post.skill_level');
        $role_require -> create_time = date("Y-m-d H:i:s",time());

        //规则验证
        $result = $role_require
            -> validate(true)
            -> allowField(['skill','skill_level'])
            -> save();
        if($result != true){
            $this -> _json(['error' => '输入信息有误','code' => 400]);
        }

        //写入数据库
        if($role_require -> save()){
            $this -> _json(['success' => '添加成功','code' => 200]);
        }
        else{
            $this -> _json(['error' => '添加失败','code' => 400]);
        }
    }

    //修改所需角色信息
    public function updateRole(){
        //实例化
        $project_role = model("project_role");

        //修改数据
        $project_role_id = input('post.role_id');
        // $project_id = input('post.project_id');
        $role = input('post.role');
        $update_time = date("Y-m-d H:i:s",time());

        //规则验证
        $result = $project_role
            -> validate(true)
            -> allowField(['role'])
            -> update();
        if($result != true){
            $this -> _json(['error' => '输入信息有误','code' => 400]);
        }

        $data = array(
            'role' => $role,
            'update_time' => $update_time
        );

        //写入数据库
        $update = $project_role -> where('project_role_id ',$project_role_id ) -> setField($data);
        if($update){
            $this -> _json(['success' => '修改成功','code' => 200]);
        }
        else{
            $this -> _json(['error' => '修改失败','code' => 400]);
        }
    }

    //修改对应角色技能
    public function updateRoleSkill(){
        //实例化
        $role_require = model("role_require");

        //修改数据
        $role_id = input('post.role_id');
        $skill = input('post.skill');
        $skill_level = input('post.skill_level');
        $update_time = date("Y-m-d H:i:s",time());

        //规则验证
        $result = $role_require
            -> validate(true)
            -> allowField(['skill','skill_level'])
            -> update();
        if($result != true){
            $this -> _json(['error' => '输入信息有误','code' => 400]);
        }

        //写入数据库
        $data = array(
            'skill' => $skill,
            'skill_level' => $skill_level,
            'update_time' => $update_time
        );
        $update = $role_require -> where('role_id ',$role_id ) -> setField($data);
        if($update){
            $this -> _json(['success' => '修改成功','code' => 200]);
        }
        else{
            $this -> _json(['error' => '修改失败','code' => 400]);
        }
    }

    //查询角色信息
    public function searchRole(){
        //实例化
        $project_role = model('project_role');

        //查询
        $project_role_id = input('post.project_role_id');
        $list = $project_role -> where('project_role_id',$project_role_id) -> select();
        $data = array(
            $list['role']
        );

        if(!$list){
            $this -> _json(['error' => '查询失败','code' => 400]);
        }
        return json(['success' => '查询成功','code' => 200,'msg' => $data]);
    }

    //查询对应技能
    public function searchRoleSkill(){
        //实例化
        $role_require = model('role_require');

        //查询
        $require_id = input('post.require_id');
        $list = $role_require -> where('require_id',$require_id) -> select();
        $data = array(
            $list['skill'],
            $list['skill_level']
        );

        if(!$list){
            $this -> _json(['error' => '查询失败','code' => 400]);
        }

        return json(['success' => '查询成功','code' => 200,'msg' => $data]);
    }

    //删除角色信息
    public function deleteRole(){
        //实例化
        $project_role = model('project_role');

        //删除
        $project_role_id = input('post.project_role_id');
        $delete = $project_role -> where('project_role_id',$project_role_id) -> delete();

        if($delete){
            $this -> _json(['error' => '删除成功','code' => 200]);
        }
        else{
            $this -> _json(['error' => '删除失败','code' => 400]);
        }
    }

    //删除角色技能信息
    public function deleteRoleSkill(){
        //实例化
        $role_require = model('role_require');

        //删除
        $require_id = input('post.require_id');
        $delete = $role_require -> where('_id',$require_id) -> delete();

        if($delete){
            $this -> _json(['error' => '删除成功','code' => 200]);
        }
        else{
            $this -> _json(['error' => '删除失败','code' => 400]);
        }
    }
}