<?php
/*
 * 项目消息，个人消息
 * 发送方式method = 0-短信，1-邮箱，2-应用内
 * 消息类型type = 0-申请加入项目，1-项目邀请被接受/拒绝
 */
namespace app\api\controller;

Vendor('qiniu.php-sdk.autoload.php'); //tp5是这样引入vendor包的
require "SentShortMessage.php";
use think\Controller;
use think\Db;
use Qiniu\Auth;
use Qiniu\Storage\UploadManager;
use think\Request;

class MessageController extends Common{

    public $opendId;

    public $userId;

    //初始化
    public function _initialize(){
        parent::_initialize();
    }

   //管理员发消息给个人
    public function AdministratorSentMessage(){
        $isLogin = $this -> check_user();  //先判断这个用户是否存在
        if(!empty($isLogin))
            return $isLogin;

        //要发送的消息
        $type = input('post.message_type');
        $method = input('post.message_method');
        $message_content = Db::name('message_content') -> where('message_content_id',$type) -> select();
        $content = $message_content['content'];

        $result = Db::name('project_member') //找到其要发送的项目并判断其是否为管理员
        -> where([
            'user_id' => $this -> userInfo['user_id'],
            'project_id' => $this -> params['project_id'],
            'status' => 1,
        ])
            ->where('user_status','<',3)
            ->find();
        if(!$result)
            $this -> _json(['error' => '该用户没有资格发送群消息','code' => 400]);
        $people_id = input('post.to_id');                  //接收人
        $sent = Db::name('message_action') -> insert([       //将发送信息的行为加入到数据表中
            'from_id' =>  $this -> userInfo['user_id'],
            'to_id' =>  $people_id,
            'project_id' => $this -> params['project_id'],
            'message_status' => 1,
            'message_method' => $method,
            'message_type' => $type,
            'create_time' => date('Y-m-d H:i:s',time())
        ]);
        if(!$sent) //判断记录该行为是否成功
            $this -> _json(['error' => '发送消息错误','code' => 400]);
        //$people_id = explode(',',$people_id); //将字符串重新转换为数组形式
        $lastId = Db::name('message_action') -> getLastInsID();//获取上一个添加到表的id
        //记录行为成功后，发送给每一个人对应的app消息中
        $is_bug = 0;//判断发送信息过程中是否出现了问题
        for($id = 0;$id < $this -> params['people_number'];$id++){ //记录每一个收到信息的用户id
            $sent = Db::name('app_message') -> insert([ //将发送信息的行为加入到数据表中
                'app_message_receiver_id' =>  $people_id,
                'app_message' =>  $content,
                'app_message_author_id' => $this -> userInfo['user_id'],
                'app_message_method' => '2',
                'app_message_father_method' => '5',
                'app_message_father' => $lastId,
                'app_message_status' =>  $this -> request -> ip(),
                'app_message_create_at' => date('Y-m-d H:i:s',time()),
            ]);
            if(!$sent){//判断发送信息过程中是否出现了问题
                $is_bug = 1;
            }
        }
        if($is_bug == 1)
            $this -> _json(['error'=>'发送消息中出现了未知的错误','code'=>400]);
        return json(['code' => 200, 'msg' => '消息发送成功','data'=>
            [
                'from_id' => $this -> userInfo['user_id'],
                'people_number' => $this -> params['people_number'],
                'to_id' => $people_id,
                'project_id' => $this -> params['project_id'],
                'time' => date('Y-m-d H:i:s',time()),
            ]]);
    }

    //个人向项目发送消息
    public function IndividualSentMessage(){
        $isLogin = $this -> check_user();         //判断用户是否存在
        if(!empty($isLogin))
            return $isLogin;

        $type = input('post.message_type');
        $message_content = Db::name('message_content') -> where('message_content_id',$type) -> select();     //将要发送的消息
        $content = $message_content['content'];
       // $project_id = input('post.project_id');         //想要发送的项目

        $people = Db::name('project_member')     //找到要发送的项目，寻找全部管理员
                  -> where([
                      'project_id' => $this -> params['project_id'],
                      'status' => 1
                  ])
                  ->where('user_status','<',3)
                  -> select();
        for($id = 0;$id < count($people,0);$id++){           //收到消息的人标记1
            $people[$people[$id]]['user_id'] = 1;
        }
        $people_id = $this -> params['people_id'];
        for($id =  0;$id < $this -> params['people_number'];$id++){             //判断传入参数是否有误
            if(empty($people_id[$id]))           //传入为空
                $this -> _json(['error'=> '发送消息人员数量有误','code' => 400]);
            if($people[$people_id][$id] == 0)         //判断成员是否在此项目
                $this -> _json(['error' => '存在不在此项目管理员','code' => 400]);
        }

        $people_id = implode(',',$people_id);    //拆分将要发送信息的成员
        $sent = Db::name('message_action') -> insert([         //记录发送信息的行为
            'from_id' => $this -> userInfo['user_id'],
            'to_id' => $people_id,
            'project_id' =>$this -> params['project_id'],
            'message_type' => $type,
            'create_time' => date('Y-m-d H:i:s',time())
        ]);
        if(!$sent)    //发送不成功
            $this -> _json(['error' => '信息发送失败','code' => 400]);

        $people_id = explode(',',$people_id);   //管理员id重新转化为数组
        $lastId = Db::name('message_action')->getLastInsID();//获取上一个添加到表的id
        //记录行为成功后，发送给每一个人对应的app消息中
        $is_bug = 0;//判断发送信息过程中是否出现了问题
        for($id=0;$id<$this -> params['people_number'];$id++) { //记录每一个收到信息的用户id
            $sent = Db::name('app_message')->insert([ //将发送信息的行为加入到数据表中
                'app_message_receiver_id' => $people_id[$id],
                'app_message' => $content,
                'app_message_author_id' => $this->userInfo['user_id'],
                'app_message_type' => '2',
                'app_message_father_type' => '5',
                'app_message_father' => $lastId,
                'app_message_status' => $this->request->ip(),
                'app_message_create_at' => date('Y-m-d H:i:s', time()),
            ]);
            if (!$sent)          //判断发送是否出错
                $is_bug = 1;
        }
        if($is_bug == 1)
            $this -> _json(['erroe' => '消息发现出现未知错误','code' => 400]);
        return json(['code' => 200 ,'msg' => '消息发送成功','data' => [
            'user_id' => $this -> userInfo['user_id'],
            'people_number' => $this -> params['people_number'],
            'people_id' => $people_id,
            'peoject_id' => $this -> params['project_id'],
            'time' => date('Y-m-d H:i:s',time())
        ]]);
    }

    //获取消息
    public function getAppMessage(){
        $isLogin = $this -> check_user();  //先判断这个用户是否存在
        if(!empty($isLogin))
            return $isLogin;
        $result = Db::name('app_message') //找到其要发送的项目并判断其是否为管理员
        ->where([
            'app_message_receiver_id' => $this -> userInfo['user_id'],
            'app_message_status' => '0',
        ])
            -> order('app_message_create_at','desc')
            -> select();
        for($id = 0;$id < count($result,0);$id++){ //将所有没必要返回的消息删除
            unset($result[$id]['app_message_id']); //删除不传回的信息
            unset($result[$id]['project_id']);
            unset($result[$id]['update_time']);
            unset($result[$id]['app_message_status']);
        }
        return json(['code' => 200, 'msg' => '获取用户信息成功','data' => $result]);
    }

     //发送短信或者邮件
    public function sentInfo($method,$type){
        $isLogin = $this -> check_user();  //先判断这个用户是否存在
        if(!empty($isLogin))
            return $isLogin;
       // $type = input("post.message_type");           //消息类型

        $result = Db::name('message_action') //先找到其2d内是否有发送过，如果有，则定为不可再邀请
        ->where([
            'to_id' => $this -> userInfo['user_id'],
            'message_method' => $method
        ])
            -> order('create_time','desc') //desc为按照时间最近的顺序给出
            -> limit(10)
            -> select();
        if(count($result,0) >= 1){                             //有一条信息，则判其是否在30s内发送过
            if(strtotime($result[0]['create_time'])+60 > time())
                $this -> _json(['error' => '消息发送间隔不能低于60s','code' => 400]);
            if(count($result,0) >= 5){      //有5条以上，判断其1hour内不能发送超过5条
                if(strtotime($result[4]['create_time'])+60*60 > time())
                    $this -> _json(['error'=>'一小时内信息发送次数最多为5次','code'=>400]);
            }
            if(count($result,0) == 10){             //有10条，判断其1天最多10次
                if(strtotime($result[9]['create_time'])+24*60*60 > time())
                    $this -> _json(['error' => '一天内信息发送次数最多为10次','code' => 400]);
            }
        }

        //判断结束表示为可以发送信息
        if(count($result,0) >= 1){
            $loss = Db::name('message_action')
                ->where([
                    'message_id' => $result['0']['message_id'],
                    'message_method' => $method //最近一次邮件或者短信
                ])
                -> update([
                    'message_status' => '0'
                ]);
            if(!$loss)
            $this -> _json(['error' => '发送消息出现错误','code' => 500]);
        }
        //要发送的消息    项目发送的消息为1
        $object = input('post.object');
        if($object == 1){
            $from = Db::name('project') -> where('project_id',$this -> params['project_id']) -> select();
            $fromer = $from['project_name'];
        }else{
            $from = Db::name('user') -> where('user_id',$this -> userInfo['user_id']) -> select();
            $fromer = $from['user_real_name'];
        }

        $message_content = Db::name('message_content') -> where('message_content_id',$type) -> select();
        $content = $message_content['content'];

        if($method == '0'){
            $res = SentShortMessage::sentShortMessage($content,$this -> params['phone']);
            if(empty($res))
                return $this -> _json(['error' => '发送信息出现未知错误','code' => 500]);
        }else{
            $res = SentShortMessage::sentEmail($fromer,$content,$this -> params['email']);
            if(empty($res))
                return $this -> _json(['error' => '发送信息出现未知错误','code' => 500]);
        }
        if($method == '0')
            $recevier = $this -> params['phone'];
        else
            $recevier = $this -> params['email'];

        $sent = Db::name('message_action') -> insert([ //将发送信息的行为加入到数据表中
            'from_id' => $this -> userInfo['user_id'],
            'to_id' => $recevier,
            'project_id' => $this -> projectInfo['project_id'],
            'message_method' => $method,
            'message_type' => $type,
            'create_time' => date('Y-m-d H:i:s',time()),
        ]);
        if(!$sent)
            $this -> _json(['error' => '发送信息出现未知错误','code' => 500]);

        return json(['code' => 200, 'msg' => '发送信息成功','data' =>
            [
                'user_id' => $this -> userInfo['user_id'],
                'project_id' => $this -> projectInfo['project_id'],
                'message_method' => $method,
                'time' => date('Y-m-d H:i:s',time()),
            ]]);
    }

    //发送邮件
    public function sentEmail(){
        $type = input('post.message_type');
        return $this -> sentInfo(1,$type);
    }

    //发送短信
    public function shortMessage(){
        $type = input('post.message_type');
        return $this ->sentInfo(0,$type);
    }
}