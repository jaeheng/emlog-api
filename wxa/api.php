<?php
include "../../../init.php";

/**
 * 统一格式返回数据
 * 返回的是JSON数据
 */
function ajaxReturn ($state, $msg = '操作成功', $data = array())
{
    $data = array(
        'state' => $state,
        'msg'   => $msg,
        'data'  => $data
    );
    die(json_encode($data, JSON_UNESCAPED_UNICODE));
}

// 0. 获取系统设置
function getOptions ()
{
    global $CACHE;
    $user_cache = $CACHE->readCache('user');
    $user = array();
    foreach ($user_cache as $key => $value) {
        $value['uid'] = $key;
        $user = $value;
        break;
    }
    $user['avatar'] = $user['avatar'] ? $user['avatar'] : 'admin/views/images/avatar.jpg';
    if (substr(BLOG_URL, -1) === '/') {
        $avatar = BLOG_URL . $user['avatar'];
    } else {
        $avatar = BLOG_URL . '/' . $user['avatar'];
    }
    $options = Option::getAll();
    return array(
        'blogname'         => $options['blogname'],
        'index_lognum'     => $options['index_lognum'],
        'bloginfo'         => $options['bloginfo'],
        'site_key'         => $options['site_key'],
        'blogurl'          => $options['blogurl'],
        'icp'              => $options['icp'],
        'site_title'       => $options['site_title'],
        'site_description' => $options['site_description'],
        'comment_pnum' => $options['comment_pnum'],
        'index_twnum' => $options['index_twnum'],
        'iscomment' => $options['iscomment'],
        'user_avatar' => $avatar,
        'user_email' => $user['mail']
    );
}

/**
 * 获取第一个上传的图片附件,没有则返回false
 */
function getFirstAtt ($blogid) {
    $db = Database::getInstance();
    $sql = 'select filepath from ' . DB_PREFIX . 'attachment where blogid = ' . $blogid . ' and mimetype like "image%" and thumfor = 0';
    $query = $db->query($sql);
    $res = array();
    while ($value = $db->fetch_array($query)) {
        $res[] = BLOG_URL . substr($value['filepath'], 3);
    }
    if ($res) {
        return $res;
    } else {
        return false;
    }
}

/**
 * 获取一段html中的第一个图片
 * @param $content
 * @return array
 */
function getImgFromDesc($content)
{
    preg_match_all("|<img[^>]+src=\"([^>\"]+)\"?[^>]*>|is", $content, $img);
    return !empty($img[1]) ? $img[1] : array();
}

/**
 * 获取文章列表
 */
function article ($params) {
    $options = getOptions();
    // 接收参数
    $sid     = isset($params['sid']) ? intval(trim($params['sid'])) : 0;
    $page    = isset($params['page']) ? intval(trim($params['page'])) : 1;
    $perpage = isset($params['perpage']) ? intval(trim($params['perpage'])) : $options['index_lognum'];
    $top     = isset($params['top']) ? intval(trim($params['top'])) : 0;
    $keyword     = isset($params['keyword']) ? trim($params['keyword']) : '';

    // 判断参数
    $map = '';

    if ($sid) {
        $map .= ' and sortid = ' . $sid;
    }
    if ($top) {
        $map .= ' and (sortop = "y" or top = "y")';
    }
    if (!empty($keyword)) {
        $map .= ' and title like "%' . $keyword . '%" ';
    }

    // 获取数据
    global $CACHE;
    $sort_cache = $CACHE->readCache('sort');
    $author_cache = $CACHE->readCache('user');
    $logModel = new Log_Model();
    
    $logs = $logModel->getLogsForHome($map . " order by top desc, sortop desc, gid desc", $page, $perpage);

    $data = array();

    foreach ($logs as $key => $value) {
        $imgs = getFirstAtt($value['gid']);
        if (!$imgs) {
            $imgs = getImgFromDesc($value['content']);
        }
        if (count($imgs) > 3) {
            $imgs = array_slice($imgs, 0, 3);
        }
        $data[] = array(
            'gid' => $value['gid'], // 文章id
            'title' => $value['title'], // 文章标题
            'date' => date('Y/m/d H:i:s', $value['date']), // 发布时间，unix时间戳
            'author' => $value['author'],
            'nickname' => $author_cache[$value['author']]['name'],
            'sortid' => $value['sortid'], // 分类id
            'sortname' => $sort_cache[$value['sortid']]['sortname'], // 分类名称
            'views' => $value['views'], // 浏览数
            'comnum' => $value['comnum'], // 评论数
            'top' => $value['top'],
            'sortop' => $value['sortop'],
            'imgs' => $imgs
        );
    }
    ajaxReturn(1, '获取文章列表成功', $data);
}

/**
 * 前台获取单篇文章
 */
function getOneLogForHome($blogId) {
    $db = Database::getInstance();
    $sql = "SELECT * FROM " . DB_PREFIX . "blog WHERE gid=$blogId AND hide='n' AND checked='y'";
    $res = $db->query($sql);
    $row = $db->fetch_array($res);
    if ($row) {
        $logData = array(
            'log_title' => htmlspecialchars($row['title']),
            'timestamp' => $row['date'],
            'date' => $row['date'] + Option::get('timezone') * 3600,
            'logid' => intval($row['gid']),
            'sortid' => intval($row['sortid']),
            'type' => $row['type'],
            'author' => $row['author'],
            'log_content' => rmBreak($row['content']),
            'views' => intval($row['views']),
            'comnum' => intval($row['comnum']),
            'top' => $row['top'],
            'sortop' => $row['sortop'],
            'attnum' => intval($row['attnum']),
            'allow_remark' => Option::get('iscomment') == 'y' ? $row['allow_remark'] : 'n'
        );
        return $logData;
    } else {
        return false;
    }
}

/**
 * 获取文章详情
 * @param $params
 */
function articleInfo ($params)
{
    $gid = isset($params['gid']) ? addslashes(trim($params['gid'])) : false;
    if (!$gid) {
        ajaxReturn(0, '参数错误');
    }
    $log = getOneLogForHome($gid);

    if (!$log) {
        ajaxReturn(0, '找不到该文章');
    }
    $data = array(
        'gid' => $log['logid'], // 文章id
        'title' => $log['log_title'], // 文章标题
        'date' => date('Y/m/d H:i:s', $log['date']), // 发布时间
        'content' => $log['log_content'], // 内容
        'sortid' => $log['sortid'], // 分类id
        'views' => $log['views'], // 浏览数
        'comnum' => $log['comnum'], // 评论数
        'allow_remark' => $log['allow_remark'], // 是否允许评论
        'author' => $log['author'] // 作者id
    );

    // 标签
    $tagModel = new Tag_Model();
    $data['tags'] = $tagModel->getTag($gid);

    // 分类名称
    global $CACHE;
    $sort_cache = $CACHE->readCache('sort');
    $data['sortname'] = $data['sortid'] == -1 ? '未分类' : $sort_cache[$data['sortid']]['sortname'];

    $author_cache = $CACHE->readCache('user');
    $data['nickname'] = $author_cache[$data['author']]['name'];

    // 更新阅读数
    $db = Database::getInstance();
    $db->query('UPDATE '.DB_PREFIX."blog SET views = views + 1 WHERE gid=" . $data['gid']);

    ajaxReturn(1, '获取文章详情成功', $data);
}

// 6. 获取某文章关联的评论列表
function comments ($params)
{
    $gid = isset($params['gid']) ? addslashes(trim($params['gid'])) : false;
    $page = isset($params['page']) ? addslashes(trim($params['page'])) : 1;

    if (!$gid) {
        ajaxReturn(0, '不知道要获取那篇文章的评论？');
    }

    $db = Database::getInstance();
    $totalSql = "select count(*) as total from " . DB_PREFIX . "comment where gid = " . $gid. " and hide = 'n' and pid = 0;";
    $res = $db->fetch_array($db->query($totalSql));
    $total = $res['total'];

    $options = getOptions();
    $start = ($page-1) * $options['comment_pnum'];
    $limit = " limit " . $start . "," . $options['comment_pnum'] . ";";
    $listSql = "select * from " . DB_PREFIX . "comment where gid = " . $gid. " and hide = 'n' and pid = 0 order by cid desc" . $limit;
    
    $listQuery = $db->query($listSql);
    $comments = array();
    while ($comment = $db->fetch_array($listQuery)) {
        $data = array(
            'cid'     => $comment['cid'],
            'date'    => date('Y/m/d H:i:s', $comment['date'] + Option::get('timezone') * 3600),
            'poster'  => $comment['poster'],
            'comment' => $comment['comment']
        );
        // 获取子评论
        $children = getCommentChildren($comment['cid']);
        $comments[] = $data;
        foreach ($children as $value) {
            $comments[] = $value;
        }
    };

    ajaxReturn(1, '获取评论成功', array('total' => (int)$total, 'list' => $comments));
}

/**
 * 获取子评论
 */
function getCommentChildren ($cid)
{
    if (!$cid) {
        ajaxReturn(0, '获取子评论参数错误');
    }

    $db = Database::getInstance();
    $listSql = "select * from " . DB_PREFIX . "comment where pid = " . $cid. " and hide = 'n' order by cid desc";
    $listQuery = $db->query($listSql);
    $comments = array();
    while ($comment = $db->fetch_array($listQuery)) {
        $data = array(
            'cid'     => $comment['cid'],
            'date'    => date('Y/m/d H:i:s', $comment['date'] + Option::get('timezone') * 3600),
            'poster'  => $comment['poster'],
            'comment' => $comment['comment']
        );
        // 获取子评论
        $children = getCommentChildren($comment['cid']);
        $comments[] = $data;
        foreach ($children as $value) {
            $comments[] = $value;
        }
    };
    return $comments;
}

// 7. 发表评论 
// gid 文章ID
// poster 评论者昵称
// mail 评论者邮箱 选填
// url 评论人个人主页 选填
// comment 评论内容
// imgcode 验证码
function addComment ($params) 
{
    $name = isset($params['poster']) ? addslashes(trim($params['poster'])) : '';
    $content = isset($params['comment']) ? addslashes(trim($params['comment'])) : '';
    $mail = isset($params['mail']) ? addslashes(trim($params['mail'])) : '';
    $url = isset($params['url']) ? addslashes(trim($params['url'])) : '';
    $imgcode = isset($params['imgcode']) ? addslashes(trim(strtoupper($params['imgcode']))) : '';
    $blogId = isset($params['gid']) ? intval($params['gid']) : -1;
    $pid = isset($params['pid']) ? intval($params['pid']) : 0;

    if (ISLOGIN === true) {
        $CACHE = Cache::getInstance();
        $user_cache = $CACHE->readCache('user');
        $name = addslashes($user_cache[UID]['name_orig']);
        $mail = addslashes($user_cache[UID]['mail']);
        $url = addslashes(BLOG_URL);
    }

    if ($url && strncasecmp($url,'http',4)) {
        $url = 'http://'.$url;
    }

    doAction('comment_post');

    $Comment_Model = new Comment_Model();
    $Comment_Model->setCommentCookie($name,$mail,$url);
    if($Comment_Model->isLogCanComment($blogId) === false) {
        ajaxReturn(0, '评论失败：该文章已关闭评论');
    } elseif ($Comment_Model->isCommentExist($blogId, $name, $content) === true) {
        ajaxReturn(0, '评论失败：已存在相同内容评论');
    } elseif (ROLE == ROLE_VISITOR && $Comment_Model->isCommentTooFast() === true) {
        ajaxReturn(0, '评论失败：您提交评论的速度太快了，请稍后再发表评论');
    } elseif (empty($name)) {
        ajaxReturn(0, '评论失败：请填写姓名');
    } elseif (strlen($name) > 20) {
        ajaxReturn(0, '评论失败：姓名不符合规范');
    } elseif ($mail != '' && !checkMail($mail)) {
        ajaxReturn(0, '评论失败：邮件地址不符合规范');
    } elseif (ISLOGIN == false && $Comment_Model->isNameAndMailValid($name, $mail) === false) {
        ajaxReturn(0, '评论失败：禁止使用管理员昵称或邮箱评论');
    } elseif (!empty($url) && preg_match("/^(http|https)\:\/\/[^<>'\"]*$/", $url) == false) {
        ajaxReturn(0, '评论失败：主页地址不符合规范','javascript:history.back(-1);');
    } elseif (empty($content)) {
        ajaxReturn(0, '评论失败：请填写评论内容');
    } elseif (strlen($content) > 8000) {
        ajaxReturn(0, '评论失败：内容不符合规范');
    } elseif (ROLE == ROLE_VISITOR && Option::get('comment_needchinese') == 'y' && !preg_match('/[\x{4e00}-\x{9fa5}]/iu', $content)) {
        ajaxReturn(0, '评论失败：评论内容需包含中文');
    } else {
        $_SESSION['code'] = null;
        addCommentHandle($name, $content, $mail, $url, $blogId, $pid);
    }
}

function addCommentHandle ($name, $content, $mail, $url, $blogId, $pid)
{
    $db = Database::getInstance();
    $commentModel = new Comment_Model();

    $ipaddr = getIp();
    if (empty($ipaddr)) {
        $ipaddr = '微信小程序';
    }
    $utctimestamp = time();

    if($pid != 0) {
        $comment = $commentModel->getOneComment($pid);
        $content = '@' . addslashes($comment['poster']) . '：' . $content;
    }

    $ischkcomment = Option::get('ischkcomment');
    $hide = ROLE == ROLE_VISITOR ? $ischkcomment : 'n';

    $sql = 'INSERT INTO '.DB_PREFIX."comment (date,poster,gid,comment,mail,url,hide,ip,pid)
            VALUES ('$utctimestamp','$name','$blogId','$content','$mail','$url','$hide','$ipaddr','$pid')";
    $ret = $db->query($sql);
    $cid = $db->insert_id();
    $CACHE = Cache::getInstance();

    if ($hide == 'n') {
        $db->query('UPDATE '.DB_PREFIX."blog SET comnum = comnum + 1 WHERE gid='$blogId'");
        $CACHE->updateCache(array('sta', 'comment'));
        doAction('comment_saved', $cid);
        ajaxReturn(1, '评论发表成功');
    } else {
        $CACHE->updateCache('sta');
        doAction('comment_saved', $cid);
        ajaxReturn(1, '评论发表成功，请等待管理员审核');
    }
}


// addComment(array(
//     'gid' => 1276,
//     'poster' => 'jaeheng',
//     'mail' => 'jaeheng@qq.com',
//     'url' => 'http://www.baidu.com',
//     'comment' => '测试评论5',
//     'imgcode' => 'rmr3b'
// ));

/**
 * 获取最新评论 (个数可在侧边栏设置)
 */
function newComments ($params)
{
    global $CACHE;
    $comments = $CACHE->readCache('comment');
    $list = array();

    // 去除mail和page
    foreach ($comments as $key => $value) {
        unset($value['mail']);
        unset($value['page']);
        $list[] = $value;
    }

    ajaxReturn(1, '获取最新评论成功', $list);
}

// newComments($_GET);

/**
 * 获取最新碎语
 */
function twitter ($params)
{
    $page = isset($params['page']) ? intval($params['page']) : 1;
    $options = getOptions();
    $perpage = $options['index_twnum'];
    $twitterModel = new Twitter_Model();
    $twitter = $twitterModel->getTwitters($page);
    
    // 获取发布人昵称
    global $CACHE;
    $author_cache = $CACHE->readCache('user');
    $list = array();
    foreach ($twitter as $value) {
        $value['nickname'] = $author_cache[$value['author']]['name'];
        $list[] = $value;
    }
    ajaxReturn(1, '获取最新碎语成功', $list);
}

// twitter($_GET);

/**
 * 获取碎语回复
 */
function replyTwitter ($params)
{
    $tid = isset($params['tid']) ? intval($params['tid']) : 0;
    if (!$tid) {
        ajaxReturn(0, '参数错误');
    }
    $replyModel = new Reply_Model();
    $replys = $replyModel->getReplys($tid, 'n');
    $list = array();
    foreach ($replys as $value) {
        unset($value['tid']);
        unset($value['ip']);
        unset($value['hide']);
        $list[] = $value;
    }
    ajaxReturn(1, '获取碎语回复成功', $list);
}

// replyTwitter($_GET);
function sorts ()
{
    global $CACHE;
    $sort_cache = $CACHE->readCache('sort');
    $data = array();
    foreach ($sort_cache as $sortid => $value) {
        unset($value['children']);
        if ($value['pid'] == 0) {
            $data[$sortid] = $value;
        } else {
            $data[$value['pid']]['children'][] = $value;
        }
    }
    sort($data);

    ajaxReturn(1, '获取分类列表成功', $data);
}

// sorts();

function attachment ($params)
{
    $gid = isset($params['blogid']) ? intval($params['blogid']) : 0;
    $thumb = isset($params['thumb']) ? intval($params['thumb']) : 1;

    if (!$gid) {
        ajaxReturn(0, '参数错误');
    }
    $map = 'blogid = ' . $gid;
    if (!$thumb) {
        $map .= ' and thumfor = 0';
    }
    $db = Database::getInstance();
    $sql = "select * from " . DB_PREFIX . "attachment where " . $map;
    $res = $db->query($sql);
    $list = array();
    while ($attach = $db->fetch_array($res)) {
        $list[] = $attach;
    };
    ajaxReturn(1, '获取附件成功', $list);
}

// attachment($_GET);

function options ()
{
    $options = getOptions();
    ajaxReturn(1, '获取系统设置成功', $options);
}

// options();
$route = $_GET['route'];

if (!$route) {
    ajaxReturn(0, '参数错误');
}
$getRouteList = array(
    'article',
    'articleInfo',
    'comments',
    'newComments',
    'twitter',
    'replyTwitter',
    'sorts',
    'attachment',
    'options'
);
$postRouteList = array(
    'addComment'
);

if (in_array($route, $postRouteList)) {
    $params = $_POST;
    if (empty($params)) {
        ajaxReturn(0, 'post数据为空');
    }
} else {
    $params = $_GET;
}

$apiRouteList = array_merge($getRouteList, $postRouteList);

if (in_array($route, $apiRouteList)) {
    call_user_func($route, $params);
} else {
    ajaxReturn(0, '参数错误');
}
