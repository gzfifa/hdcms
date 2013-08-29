<?php
class IndexControl extends Control
{

    public function index()
    {
        if (!session("uid")) {
            go("login");
        }
        $this->assign("model", M("model")->all());
        $this->display();
    }

    /**
     * 退出系统
     */
    public function out()
    {
        session(NULL);
        go("login");
    }

    public function login()
    {
        if (session("uid")) {
            go("index");
        }
        $username = $this->_post("username", "strip_tags,htmlspecialchars");
        if ($username) {
            $pre = C("DB_PREFIX");
            $user = $pre . "user";
            $role = $pre . 'role';
            $user_role = $pre . 'user_role';
            $sql = "SELECT {$role}.type,{$role}.rid,{$role}.rname,{$user}.uid,username,realname,password,status FROM $role JOIN $user_role JOIN $user
             ON {$role}.rid = {$user_role}.rid AND {$user_role}.uid = {$user}.uid WHERE {$user}.uid = {$user_role}.uid AND username='$username'";

            $user = M()->query($sql);
            if (!$user) {
                $this->error("用户不存在", '', 1);
                go("login");
            }
            if ($user[0]['password'] !== md5($_POST['password'])) {
                $this->error("密码错误", '', 1);
                go("login");
            }

            session("uid", $user[0]['uid']);
            session("username", $user[0]['username']);
            session("realname", $user[0]['realname']);
            session("rname", $user[0]['rname']);//角色名
            session("type", $user[0]['type']);// 1 后台管理员  2 前台会员
            session("rid", $user[0]['rid']);

            session("status", $user[0]['status']);

            go("index");
        } else {
            $this->display();
        }
    }

    public function checkUser()
    {
        $username = $this->_post("username");
        if (M("user")->field("uid")->find("username='$username'")) {
            echo 0;
            exit;
        } else {
            echo 1;
            exit;
        }
    }

    public function checkCode()
    {
        $username = $this->_post("username");
        if ($_SESSION['code'] !== strtoupper($_POST['code'])) {
            echo 0;
            exit;
        } else {
            echo 1;
            exit;
        }
    }

    public function reg()
    {
        $username = $this->_post("username", "strip_tags,htmlspecialchars");
        if ($username) {
            if ($_SESSION['code'] !== strtoupper($_POST['code'])) {
                $this->error("验证码输入错误");
            }
            $userDb = M("user");
            if ($userDb->find("username='$username'")) {
                $this->error("用户名已经存在");
            }
            $_POST['realname']=$username;
            $this->_post("password","md5");//密码加密
            $uid = $userDb->add();
            $userDb->table("user_role")->add(array("uid" => $uid, "rid" => 5));
            session("uid", $uid);
            session("rid", 5);//默认组 新手上路
            session("rname", "普通用户");
            session("username", $username);
            session("realname", $username);
            go("index");
        } else {
            if (session("uid")) {
                go("index");
            }
            $this->display();
        }
    }

    public function code()
    {
        C(array(
            "CODE_BG_COLOR" => "#ffffff",
            "CODE_FONT_COLOR" => "",
            "CODE_LEN" => 4,
            "CODE_FONT_SIZE" => 22,
            "CODE_WIDTH" => 150,
            "CODE_HEIGHT" => 35,
        ));
        $code = new Code();
        $code->show();
    }
}

?>