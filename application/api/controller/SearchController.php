<?php
/*
 * 按名称\标签搜索项目和人才
 * 分页，每页返回$page
 */

namespace app\api\controller;

use think\view;
use think\controller;

class SearchController extends Common {
    public function search(){
        $view = new view();
        return $view -> fetch('search');
    }

//搜项目，按截至时间降序排序
    public function findProject(){

        $project_name = input("post.project_name");
        $project_tag = input("post.project_tag");
        $per = input("post.page");

        //名称检索
       if($project_name == true && $project_tag == false){
           $status = 1;
           $keyword = $project_name;
       }
       //标签检索
        else if($project_name == false && $project_tag == true){
            $status = 2;
            $keyword = $project_tag;
        }
       //名称、标签检索
        else if($project_name == true && $project_tag == true){
            $status = 3;
            $keyword = array('project_name' => $project_name,'project_tag' => $project_tag);
        }
       //未填写
        else{
            $this -> _json(['error'=>'请选择检索条件','code'=>400]);
        }
    //检索结果
        $answer = 0;                           //搜索结果初始状态，成功为1
        if($status == 1){
            $map['project_name'] = ['like','%'.$keyword.'%'];
            $map['project_status'] = 1;
            $search = db('project') -> where($map) -> order('project_deadline ','desc')
                      -> paginate($per,$simple = false,$config = ['query' => array('keyword' => $keyword)]);
            if($search)
                $answer = 1;                           //搜索成功

            $response = array(
                'search' => $search,
                'keyword' => $keyword
            );
        }
        else if ($status == 2){
            $map['project_tag'] = ['like','%'.$keyword.'%'];
            $map['project_status'] = 1;
            $search = db('project') -> where($map) -> order('project_deadline desc')
                -> paginate($per,$simple = false,$config = ['query' => array('keyword' => $keyword)]);
            if($search)
                $answer = 1;                           //搜索成功

            $response = array(
                'search' => $search,
                'keyword' => $keyword
            );
        }
        else{
            $map['project_name'] = ['like','%'.$keyword['project_name'].'%'];
            $map['project_tag'] = ['like','%'.$keyword['project_tag'].'%'];
            $map['project_status'] = 1;
            $search = db('project') -> where($map) -> order('project_deadline desc')
                -> paginate($per,$simple = false,$config = ['query' => array('keyword' => $keyword)]);
            if($search)
                $answer = 1;                           //搜索成功

            $response = array(
                'search' => $search,
                'keyword' => $keyword
            );
        }

        //返回结果
        if($answer == 0){
            $this -> _json(['error' => '搜索失败','code' => 400]);
        }
        return json(['success' => '搜索成功','code' => 200,'msg' => $response]);
    }

//搜人才，按信息完整度降序排序
    public function findUser(){

        $user_name = input("post.user_name");
        $user_tag = input("post.user_tag");
        $per = input("post.page");

        //名称检索
        if($user_name == true && $user_tag == false){
            $status = 1;
            $keyword = $user_name;
        }
        //标签检索
        else if($user_name == false && $user_tag == true){
            $status = 2;
            $keyword = $user_tag;
        }
        //名称、标签检索
        else if($user_name == true && $user_tag == true){
            $status = 3;
            $keyword = array('project_name' => $user_name,'project_tag' => $user_tag);
        }
        //未填写
        else{
            $data = array('status' => 0,'msg' => '请填写搜索条件！');
            exit(json_encode($data));
        }
        //检索结果
        $answer = 0;                           //搜索结果初始状态，成功为1
        if($status == 1){
            $map['user_name'] = ['like','%'.$keyword.'%'];
            $search = db('user') -> where($map) -> order('user_information_integrity ','desc')
                -> paginate($per,$simple = false,$config = ['query' => array('keyword' => $keyword)]);
            if($search)
                $answer = 1;                           //搜索成功

            $response = array(
                'search' => $search,
                'keyword' => $keyword
            );
        }
        else if ($status == 2){
            $map['user_lable'] = ['like','%'.$keyword.'%'];
            $search = db('user') -> where($map) -> order('user_information_integrity desc')
                -> paginate($per,$simple = false,$config = ['query' => array('keyword' => $keyword)]);
            if($search)
                $answer = 1;                           //搜索成功

            $response = array(
                'search' => $search,
                'keyword' => $keyword
            );
        }
        else{
            $map['user_name'] = ['like','%'.$keyword['user_name'].'%'];
            $map['user_lable'] = ['like','%'.$keyword['user_tag'].'%'];
            $search = db('user') -> where($map) -> order('user_information_integrity desc')
                -> paginate($per,$simple = false,$config = ['query' => array('keyword' => $keyword)]);
            if($search)
                $answer = 1;                           //搜索成功

            $response = array(
                'search' => $search,
                'keyword' => $keyword
            );
        }

        //返回结果
        if($answer == 0){
            $this -> _json(['error' => '搜索失败','code' => 400]);
        }
        return json(['success' => '搜索成功','code' => 200,'msg' => $response]);
    }
}