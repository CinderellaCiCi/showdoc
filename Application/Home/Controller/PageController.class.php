<?php
namespace Home\Controller;
use Think\Controller;
class PageController extends BaseController {

    //展示某个项目的单个页面
    public function index(){
        import("Vendor.Parsedown.Parsedown");
        $page_id = I("page_id");
        $page = D("Page")->where(" page_id = '$page_id' ")->find();
        $Parsedown = new \Parsedown();
        $page['page_content'] = $Parsedown->text(htmlspecialchars_decode($page['page_content']));
        $this->assign("page" , $page);
        $this->display();
    }

    //返回单个页面的源markdown代码
    public function md(){
        $page_id = I("page_id");
        $page = D("Page")->where(" page_id = '$page_id' ")->find();
        echo $page['page_content'];
    }

    //编辑页面
    public function edit(){
        $page_id = I("page_id");
        $page_history_id = I("page_history_id");

        if ($page_id > 0 ) {
            if ($page_history_id) {
                $page = D("PageHistory")->where(" page_history_id = '$page_history_id' ")->find();
            }else{
                $page = D("Page")->where(" page_id = '$page_id' ")->find();
            }
            
            $this->assign("page" , $page);
        }

        $item_id = $page['item_id'] ?$page['item_id'] :I("item_id");

        $login_user = $this->checkLogin();
        if (!$this->checkItemPermn($login_user['uid'] , $item_id)) {
            $this->message("你无权限");
            return;
        }

        $this->assign("item_id" , $item_id);


        $this->display();        
    }

    //保存
    public function save(){
        $login_user = $this->checkLogin();
        $page_id = I("page_id") ? I("page_id") : 0 ;
        $page_title = I("page_title") ?I("page_title") : '默认页面';
        $page_content = I("page_content");
        $cat_id = I("cat_id")? I("cat_id") : 0;
        $item_id = I("item_id")? I("item_id") : 0;
        $order = I("order")? I("order") : 99;

        $login_user = $this->checkLogin();
        if (!$this->checkItemPermn($login_user['uid'] , $item_id)) {
            $this->message("你无权限");
            return;
        }

        $data['page_title'] = $page_title ;
        $data['page_content'] = $page_content ;
        $data['order'] = $order ;
        $data['item_id'] = $item_id ;
        $data['cat_id'] = $cat_id ;
        $data['addtime'] = time();
        $data['author_uid'] = $login_user['uid'] ;
        $data['author_username'] = $login_user['username'];

        if ($page_id > 0 ) {
            
            //在保存前先把当前页面的版本存档
            $page = D("Page")->where(" page_id = '$page_id' ")->find();
            $insert_history = array(
                'page_id'=>$page['page_id'],
                'item_id'=>$page['item_id'],
                'cat_id'=>$page['cat_id'],
                'page_title'=>$page['page_title'],
                'page_content'=>$page['page_content'],
                'order'=>$page['order'],
                'addtime'=>$page['addtime'],
                'author_uid'=>$page['author_uid'],
                'author_username'=>$page['author_username'],
                );
             D("PageHistory")->add($insert_history);

            $ret = D("Page")->where(" page_id = '$page_id' ")->save($data);
            $return = D("Page")->where(" page_id = '$page_id' ")->find();
        }else{
            
            $page_id = D("Page")->add($data);
            $return = D("Page")->where(" page_id = '$page_id' ")->find();
        }
        if (!$return) {
            $return['error_code'] = 10103 ;
            $return['error_message'] = 'request  fail' ;
        }
        $this->sendResult($return);
        
    }

    //删除页面
    public function delete(){
        $page_id = I("page_id")? I("page_id") : 0;
        $page = D("Page")->where(" page_id = '$page_id' ")->find();

        $login_user = $this->checkLogin();
        if (!$this->checkItemPermn($login_user['uid'] , $page['item_id'])) {
            $this->message("你无权限");
            return;
        }

        if ($page) {
            
            $ret = D("Page")->where(" page_id = '$page_id' ")->limit(1)->delete();

        }
        if ($ret) {
           $this->message("删除成功！",U("Home/item/show").'?item_id='.$page['item_id']);
        }else{
           $this->message("删除失败！",U("Home/item/show").'?item_id='.$page['item_id']);
        }
    }

    //历史版本
    public function history(){
        $page_id = I("page_id") ? I("page_id") : 0 ;
        $this->assign("page_id" , $page_id);

        $PageHistory = D("PageHistory")->where("page_id = '$page_id' ")->order(" addtime desc")->limit(10)->select();

        if ($PageHistory) {
            foreach ($PageHistory as $key => &$value) {
                $value['addtime'] = date("Y-m-d H:i:s" , $value['addtime']);
            }
        }

        $this->assign("PageHistory" , $PageHistory);

        $this->display();        

    }

    //上传图片
    public function uploadImg(){
        $upload = new \Think\Upload();// 实例化上传类
        $upload->maxSize  = 3145728 ;// 设置附件上传大小
        $upload->allowExts  = array('jpg', 'gif', 'png', 'jpeg');// 设置附件上传类型
        $upload->rootPath = './Public/Uploads/';// 设置附件上传目录
        $upload->savePath = '';// 设置附件上传子目录
        $info = $upload->upload() ;
        if(!$info) {// 上传错误提示错误信息
          $this->error($upload->getError());
          return;
        }else{// 上传成功 获取上传文件信息
          
          $url = get_domain().__ROOT__.substr($upload->rootPath,1).$info['editormd-image-file']['savepath'].$info['editormd-image-file']['savename'] ;
          echo json_encode(array("url"=>$url,"success"=>1));
        }
    }


}