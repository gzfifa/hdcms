<?php
/**
 * Class UserControl
 * 用户管理
 */
class UserControl extends RbacControl
{
    /**
     * 显示前台会员列表
     */
    public function userList()
    {
        $rid = $this->_get("rid"); //会员组rid
        $db = V("user");
        $db->view = array(
            'user_role' => array(
                'type' => "inner",
                "on" => "user.uid=user_role.uid",
            ),
            "role" => array(
                "type" => "inner",
                "on" => "user_role.rid=role.rid"
            )
        );
        $db->where("type=2");
        if ($rid) {
            $db->where("role.rid=$rid");
        }
        $db->group("hd_user.uid");
        $page = new Page(count($db->field()->count()));
        $optOld = $db->db->optOld;
        $limit =$page->limit();
        $field = C("DB_PREFIX").str_replace("`uid","user.`uid",$optOld['field']).',rname';
        $sql =" SELECT ".$field." FROM ".$optOld["from_table"].$optOld["where"].$optOld["group"]." LIMIT ".$limit['limit'];
        $user = $db->query($sql);
        $this->assign("role", M("role")->all("type=2"));
        $this->assign('user', $user);
        $this->assign("page", $page->show());
        $this->display();
    }

    /**
     * 添加用户
     */
    public function UserAdd()
    {
        if (isset($_POST['username'])) {
            $db = M("user");
            $this->_post("password","md5");
            if ($uid = $db->add()) {
                $db->table("user_role")->add(array(
                    "uid" => $uid,
                    "rid" => $_POST['rid']
                ));
                $this->success("添加用户成功", "userList", 1);
            }
        } else {
            $this->assign("role", M("role")->where("type=2")->all());
            $this->display();
        }
    }

    /**
     * 验证用户名
     */
    public function checkUser()
    {
        $username = $this->_post("username");
        if (M("user")->where("username='$username'")->find()) {
            echo 0;
        } else {
            echo 1;
        }
        exit;
    }

    /**
     * 删除用户
     */
    public function userDel()
    {
        $db = M("user");
        $uid = $this->_get("uid");
        $db->del($uid);
        $db->table("user_role")->del("uid=" . $uid);
        $this->success("删除成功", "userList", 1);
    }

    /**
     * 编辑用户
     */
    public function userEdit()
    {
        $uid = $this->_post("uid");
        if ($uid) {
            //没有编辑缩略图时，删除缩略图POST空值
            if (isset($_POST['favicon']) && empty($_POST['favicon'])) {
                unset($_POST['favicon']);
            }
            $db = M("user");
            //密码处理
            if (isset($_POST['password2']) && !empty($_POST['password2'])) {
                if ($_POST['password2'] != $_POST['password']) {
                    $this->error("两次密码不一致");
                }
                $this->_post("password", "md5");
            }else if(isset($_POST['password'])){
                unset($_POST['password']);
            }
            $db->save(); //修改用户信息
            $db->table("user_role")->where("uid=$uid")->save();
            $this->success("修改成功", "userList", 1);
        } else {
            $uid = $this->_get("uid");
            $db = V("user");
            $db->view = array(
                'user_role' => array(
                    'type' => "inner",
                    "on" => "user.uid=user_role.uid"
                ),
            );
            $table = C("DB_PREFIX") . "user";
            $field = $db->find($table . '.uid=' . $uid);
            $role = M("role")->all("type=2");
            $this->assign("role",$role );
            $this->assign("field", $field);
            $this->display();
        }
    }

    /**
     * 会员组列表
     */
    public function groupList()
    {
        $db = R("role");
        $db->join = array(
            'user_role' => array( //关联表
                'type' => 'has_many', //包含一条主表记录
                "foreign_key" => "rid",
                "count" => "rid",
                "other" => 1
            ),
            "member_access" => array(
                "type" => "has_one",
                "foreign_key" => "rid",
            )
        );
        $data = $db->all("type=2");
        $this->assign("data", $data);
        $this->display();
    }

    /**
     * 添加会员组
     */
    public function groupAdd()
    {
        $rname = $this->_post("rname");
        if ($rname) {
            $db = M("role");
            if ($db->find("rname='$rname'")) {
                $this->error("用户组已经存在");
            }
            $_POST['type'] = 2; //2 为普通会员  1 为后台管理员
            $rid = $db->add();
            $_POST['rid'] = $rid;
            $db->table("member_access")->add();
            $this->success("添加会员组成功", "groupList", 1);
        } else {
            $this->display();
        }
    }

    public function groupEdit()
    {
        $rid = $this->_post("rid");
        if ($rid) {
            $db = M("role");
            if ($db->find("rname='{$_POST['rname']}' and rid !=$rid")) {
                $this->error("用户组已经存在");
            }
            $db->save();
            $_POST['reply'] = isset($_POST['reply']) ? $_POST['reply'] : 0;
            $_POST['send'] = isset($_POST['send']) ? $_POST['send'] : 0;
            $db->table("member_access")->where("rid=$rid")->save();
            $this->success("修改会员组成功", "groupList", 1);
        } else {
            $rid = $this->_get("rid");
            $db = R("role");
            $db->join = array(
                'user_role' => array( //关联表
                    'type' => 'has_many', //包含一条主表记录
                    "foreign_key" => "rid",
                    "count" => "rid",
                    "other" => 1
                ),
                "member_access" => array(
                    "type" => "has_one",
                    "foreign_key" => "rid",
                )
            );
            $table = C("DB_PREFIX") . "role";
            $field = $db->find($table . '.rid=' . $rid);
            $this->assign("field", $field);
            $this->display();
        }
    }

    /**
     * 删除会员组
     */
    public function groupDel()
    {
        $rid = $this->_get("rid");
        $db = M("user_role");
        //将此用户组设置为普通会员
        $db->where("rid=$rid")->save(array("rid" => 4)); //将删除组中的会员设置为普通会员即rid=4
        $db->table("user_role")->del("rid=$rid"); //删除会员角色中间表
        $db->table("role")->del("rid=$rid"); //删除角色表
        $db->table("member_access")->del("rid=$rid"); //前台会员权限表
        $this->success("删除成功", "groupList", 1);
    }


}


















