<?php

/*
 * 后台
 * 增加项目信息
 * 修改项目信息
 * 查询项目信息
 * 删除项目信息
 * 返回有效项目总数
 * 项目日增长数
 * 项目截止倒计时
 */
namespace app\api\controller;

vendor("PHPExcel.PHPExcel"); //引入Excel
use think\view;
use think\controller;

class ProjectController extends Common
{
    public function index()
    {
        $view = new View();
        return $view->fetch('index');
    }

    //增加项目信息
    public function addProject()
    {
        //实例化Project
        $project = model("project");
        //接受数据
        $project_tag = input('post.project_tag');
        $str = implode(',', $project_tag);           //将数组组合成字符串

        $project->projcet_name = input('post.project_name');
        $project->project_first_type = input('post.project_first_type');
        $project->project_second_type = input('post.project_second_type');
        $project->project_introduction = input('post.project_introduction');
        $project->project_deadline = input('post.project_deadline');
        $project->project_create_time = date("Y-m-d H:i:s", time());
        $project->project_tag = $str;
        $project->project_status = 1;
        //规则验证
        $result = $project
            ->validate(true)
            ->allowField(['project_name', 'project_first_type', 'project_second_type', 'protect_introduction', 'protect_tag'])
            ->save();
        if ($result != true) {
            $this->_json(['error' => '输入信息有误', 'code' => 400]);
        }

        //写入数据库
        if ($project->save()) {
            $this->_json(['success' => '发布成功', 'code' => 200]);
        } else {
            $this->_json(['error' => '发布失败', 'code' => 400]);
        }
    }

    //修改项目信息
    public function updateProject()
    {
        //实例化Project
        $project = model("project");
        //修改数据
        $project_id = input('post.project_id');
        $project_name = input('post.project_name');
        $project_type = input('post.project_type');
        $project_introduction = input('post.project_introduction');
        $project_deadline = input('post.project_deadline');
        $project_update_time = date("Y-m-d");
        $project_tag = input('post.project_tag');
        $str = implode(',', $project_tag);           //用 ， 将数组组合成字符串

        //规则验证
        $result = $project
            ->validate(true)
            ->allowField(['project_name', 'project_first_type', 'project_second_type', 'protect_introduction', 'protect_tag'])
            ->update();
        if ($result != true) {
            $this->_json(['error' => '输入信息有误', 'code' => 400]);
        }

        $data = array(
            'project_name' => $project_name,
            'project_type' => $project_type,
            'project_introduction' => $project_introduction,
            'project_tag' => $str,
            'project_deadline' => $project_deadline,
            'project_update_time' => $project_update_time
        );

        $update = $project->where('project_id ', $project_id)->setField($data);

        //写入数据库
        if ($update) {
            $this->_json(['success' => '修改成功', 'code' => 200]);
        } else {
            $this->_json(['error' => '修改失败', 'code' => 400]);
        }
    }

    //查询项目
    public function searchProject()
    {
        //实例化
        $project = model("project");

        $project_id = input('post.project_id');
        $list = $project->where('project_id', $project_id)->select();
        $project_tag = $list['project_tag'];
        $tag_array = explode(',', $project_tag);

        $data = array(
            $list['project_name'],
            $list['project_first_type'],
            $list['project_second_type'],
            $list['project_introduction'],
            $list['project_create_time'],
            $list['project_deadline'],
            $tag_array
        );

        if ($list) {
            return json(['success' => '查询成功', 'code' => 200, 'msg' => $data]);
        } else {
            $this->_json(['error' => '查询失败', 'code' => 400]);
        }
    }

    //删除项目
    public function deleteProject()
    {
        $project = model("project");

        $project_id = input('post.project_id');
        $delete = $project->where('project_id', $project_id)->delete();

        if ($delete) {
            $this->_json(['success' => '删除成功', 'code' => 200]);
        } else {
            $this->_json(['error' => '删除失败', 'code' => 400]);
        }
    }

    //返回有效项目数
    public function rtnValidateProject()
    {
        //实例化对象
        $project = model("project");

        //查询有效项目数
        $validate_project = $project -> where("project_status",1) -> count();

        if($validate_project)
            $this -> _json(['succeed' => 200,'msg' => "查询成功", 'data' => $validate_project]);
        else
            $this -> _json((['error' => 400,'msg' => "查询失败"]));
    }

    //返回日增长数
    public function rtnDayIncrease(){
        //实例化
        $project = model("project");

        $nowadays = date("Y-m-d");
        $now = date("Y-m-d H:i:s");
        $num = $project -> whereTime('date','between',["$nowadays 00:00:00","$now"]) -> count();  //一种统计时间范围的写法，wheretime

        if($num)
            $this -> _json(['succeed' => 200,'msg' => "查询成功",'data' => $num]);
        else
            $this -> _json(['error' => 400,'msg' => "查询失败"]);
    }

    //截止倒计时
    public function CountDown(){
        //实例化
        $project = model("project");

        $project_id = input('post.project_id');

        //获取截止时间
        $list = $project -> where('project_id',$project_id) -> select();
        $deadline = $list['project_deadline'];

        if($deadline){
            $end_time = $deadline - time();
            $timedata = '';                   //存放截止时间
            $d = floor($end_time / (3600 * 24));                //floor为tp5的一个向下取整函数
            if ($d) {
                $timedata .= $d . "天";
            }
            $h = floor($end_time % (3600 * 24) / 3600);
            if ($h) {
                $timedata .= $h . "小时";
            }
            $m = floor($end_time % (3600 * 24) % 3600 / 60);
            if ($m) {
                $timedata .= $m . "分";
            }
            $this -> _json(['succeed' => 200,'msg' => "查询成功",'data' => $timedata]);
        }
        else{
            $this -> _json(['error' => 400,'msg' => "查询失败"]);
        }


    }
    //导出Excel
    public function excel() {
        //实例化
        $project = model("project");

        $project_id = input('post.project_id');

        $list = $project -> where('project_id',$project_id) -> select();

        $project_name = $list['project_name'];

        $header = ['项目id','项目名称','一级项目类型','二级项目类型','项目标签','项目简介','项目截止日期','项目状态'];
        //$id = input('id/a');

        for($a = 0;$a < count($project_id);$a++){
            $data[$a] = $project ->where('project_id',$project_id)->select();
            for($b = 0;$b < count($data);$b++){

                //判断项目状态，转为中文
                if($data[$a]['project_status'] == 1)
                    $data[$a]['project_status'] = "发布中";
                elseif ($data[$a]['project_status'] == 0)
                    $data[$a]['project_status'] = "已过期";

                $newdata[$b]['project_id'] = $data[$a]['project_id'];
                $newdata[$b]['project_name'] = $data[$a]['project_name'];
                $newdata[$b]['project_first_type'] = $data[$a]['project_first_type'];
                $newdata[$b]['project_second_type'] = $data[$a]['project_second_type'];
                $newdata[$b]['project_tag'] = $data[$a]['project_tag'];
                $newdata[$b]['project_introduction'] = $data[$a]['project_introduction'];
                $newdata[$b]['project_deadline'] = $data[$a]['project_deadline'];
                $newdata[$b]['project_status'] = $data[$a]['project_status'];
            }
        }
        //导出
        $this -> excelExport($project_name,$header,$newdata);
    }
}