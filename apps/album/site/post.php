<?php 
defined('IN_MWEB') or die('access denied');

checkLogin();

if(isPost()){
    //
    $album_id = getGet('id');
    $title = getPost('title');
    $cate_id = intval(getPost('cate_id'));
    $priv_type = intval(getPost('priv_type'));
    $description = trim(getPost('description'));
    $tags = trim(getPost('tags'));

    $pic_ids = array_intval(getPost('pic_ids'));

    //创建相册 
    $data['name'] = $title;
    $data['uid'] = $_G['user']['id'];
    $data['tags'] = $tags;
    $data['description'] = $description;
    $data['cate_id'] = $cate_id;
    $data['create_time'] = $data['up_time'] = CURRENT_TIME;
    $data['priv_type'] = $priv_type;
    
    if(!$data['name']){
        alert('标题不能为空！');
    }
    if(!$data['cate_id']){
        alert('请选择分类！');
    }
    if(!$pic_ids){
        alert('至少上传一张图片！');
    }
    $m_album =  M('albums');
    $m_photos =  M('album_photos');
    if($album_id){
        $albumInfo = $m_album->load($album_id);
        if($albumInfo['uid']!=$_G['user']['id']){
            alert('非法操作，没有权限！');
        }

        //保存基本信息并更新照片排序
        if($m_album->update($album_id,$data)){
            app('album')->updateTags('album',$album_id,$data['tags'],$albumInfo['cover_path'],false);
            app('album')->updatePhotoSort($pic_ids);
            app('album')->updateCover($album_id,array_shift($pic_ids));

            alert('保存成功！',true,U('album','space','id='.$_G['user']['id']));
        }else{
            alert('保存失败！');
        }
    }else{
        if($m_album->insert($data)){
            $album_id = $m_album->insertId();
            
            //保存图片信息
            $m_photos->updateW('id in ('.implode(',', $pic_ids).')',array('name'=>$title,'cate_id'=>$cate_id,'album_id'=>$album_id,'priv_type'=>$priv_type,'tags'=>$data['tags']));
            app('album')->updatePhotoNum($album_id);
            app('album')->updatePhotoSort($pic_ids);
            app('album')->updateCover($album_id,array_shift($pic_ids));

            if($data['tags']){
                //保存图片tag，取消保存图片tag吧
                /*foreach ($pic_ids as $picid) {
                    $info = $m_photos->load($picid);
                    app('album')->updateTags('photo',$picid,$data['tags'],$info['path'],true);
                }*/
                app('album')->updateTags('album',$album_id,$data['tags'],'',true);
            }

            alert('保存成功！',true,U('album','space','id='.$_G['user']['id']));
        }else{
            alert('保存失败！');
        }
    }
}else{
    if(@$_G['settings']['album_email_notactive_cannotpost'] && !$_G['user']['email_actived']){
        showInfo('Email未激活不允许上传！',U('space','account'));
    }
    if(@$_G['settings']['album_mobile_notactive_cannotpost'] && !$_G['user']['mobile_actived']){
        showInfo('手机未绑定不允许上传！',U('space','account'));
    }

    $album_id = intval(getGet('id'));
    if($album_id){
        $albumInfo = M('albums')->load($album_id);

        if($albumInfo['uid']!=$_G['user']['id']){
            showInfo('非法操作，没有权限！',U('album','space','id='.$_G['user']['id']));
        }
        $view->assign('albumInfo',$albumInfo);
    }

    //分类列表
    $cates = app('album')->getCateList();
    $view->assign('cates',$cates);

    $view->assign('album_id',$album_id);

    //取出当前相册的所有的图片(或不属于任何相册的图片)
    $photo_list = M('album_photos')->findAll(array(
        'fields' => 'id,path,width,height',
        'where' => 'uid='.$_G['user']['id'].' AND album_id='.$album_id,
        'order' => 'sort asc,id asc'
    ));
    $view->assign('photo_list',$photo_list);

    $view->display('album/post.php');
}