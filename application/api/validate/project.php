<?php
namespace app\think\validate;

use think\Validate;

class Project extends Validate{
    //写规则
    protected $rule = [
        'project_name' => 'require|max:15|token',
        'project_first_type' => 'require',
        'project_second_type' => 'require',
        'project_introduction' => 'require|max:140',
        'project_tag' => 'require|max:15',
        'role' => 'require',
        'skill' => 'require',
        'skill_level' => 'require'
    ];

    //报错信息
    protected $message = [
        'protect_name.require' => '必须填写',
        'protect_name.max' => '不能超过15个字符',
        'protect_name.token' => '不能重复提交',
        'project_first_type.require' => '请选择项目类型',
        'project_second_type.require' => '请选择项目类型',
        'protect_introduction.require' => '请填写项目介绍',
        'protect_introduction.max' => '项目介绍字数过多',
        'protect_tag.require' => '至少选择一个项目标签',
        'protect_tag.max' => '请选择1-6项目标签',
        'role.require' => '请选择角色',
        'skill.require' => '请输入技能名称',
        'skill_level.require' => '请选择技能等级'
    ];

    protected $scene = [
        'addProject' => ['project_name','project_first_type','project_second_type','protect_introduction','protect_tag'],
        'updateProject' => ['project_name','project_first_type','project_second_type','protect_introduction','protect_tag'],
        'addRole' => ['role'],
        'addRoleSkill' => ['skill','skill_level'],
        'updateRole' => ['role']
    ];
}
