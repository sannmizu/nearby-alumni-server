<?php
include_once 'module/Utils/utils_include.php';
include_once 'module/Utils/geohash.class.php';

class PostServer extends BaseSever {
    public function __construct(string $logToken = NULL, string $connToken = NULL){
        parent::__construct($logToken, $connToken);
    }
    
    /**
     * @param int $id
     * @throws MyException
     * {"posts":[	{"post":{"postId":""}}, ...}
     */
    public function getAllPost($id) {
        $mysqldb = new MysqlDB();
        try {
            $result = $mysqldb->query("SELECT POST_ID FROM USER_POSTS WHERE USER_ID = '$id'");
            $jsonRoot = array("posts" => array());
            while($row = $result->fetch_assoc()) {
                $paramArray = array(
                    "post" => array(
                        "postId" => $row['POST_ID']
                    ) 
                );
                array_push($jsonRoot["posts"], $paramArray);
            }
            $this->response = OkJson(json_encode($jsonRoot));
        } catch (MyException $e) {
            throw $e;
        } finally {
            $mysqldb->close();
        }
        return $this->response;
    }
    /**
     * @param int $postid
     * @param string $target
     * @throws MyException
     * @return string
     * "":         {"post":{"title":"", "content":"", ...}}
     * "comment":  {"comment":{"commentId":""}}
     * "comments": {"postId":"",
     *                      "comments":[
     *                      {"comment":{"commentId":"", "time":"", "repost":"", "info":{"content":""}}}
     *                      {"comment":{"commentId":"", "time":"", "repost":"", "info":{"content":""}}}
     *                      ...
     *                      ]}
     */
    public function getPost($postid, $target) {
        $mysqldb = new MysqlDB();
        try{
            switch($target) {
                case "":
                    $result = $mysqldb->query("SELECT AUTHOR, REPOST, TIME, TITLE, CONTENT, MEDIA, NUM_LIKES, NUM_REPOSTS, AREA_ID
                                               FROM POSTS WHERE POST_ID = '$postid'");
                    if($mysqldb->affected_rows == 0) {
                        throw new MyException("帖子不存在", 10404);
                    }
                    
                    $row = $result->fetch_assoc();
                    $jsonRoot = array("post" => array());
                    $Array = array(
                        "postId" => (int)$postid,
                        "time" => $row['TIME'],
                        "repost" => $row['REPOST'],
                        "info" => array(
                            "author" => $row['AUTHOR'],
                            "title" => $row['TITLE'],
                            "content" => $row['CONTENT'],
                            "meida" => $row['MEDIA'],
                            "numlikes" => $row['NUM_LIKES'],
                            "numreposts" => $row['NUM_REPOSTS'],
                            "areaId" => $row['AREA_ID']
                            )
                    );
                    array_push($jsonRoot["post"], $Array);
                    $this->response = OkJson(json_encode($jsonRoot));
                    break;
                case "comment":
                    $result = $mysqldb->query("SELECT COMMENT_ID FROM POST_COMMENT WHERE POST_ID = '$postid'");
                    if($mysqldb->affected_rows == 0) {
                        throw new MyException("帖子不存在", 10404);
                    }
                    $row = $result->fetch_assoc();
                    $id = $row['COMMENT_ID'];
                    $this->response = OkJson('{"comment":{"commentId":"'.$id.'"}}');
                    break;
                case "comments":
                    $result = $mysqldb->query("SELECT COMMENT_ID FROM POST_COMMENT WHERE POST_ID = '$postid'");
                    if($mysqldb->affected_rows == 0) {
                        throw new MyException("帖子不存在", 10404);
                    }
                    $row = $result->fetch_assoc();
                    $comment_id = $row['COMMENT_ID'];
                    $result = $mysqldb->query("SELECT COMMENT_ID, AUTHOR, TIME, CONTENT, NUM_LIKES
                                               FROM COMMENTS
                                               WHERE REPOST = '$comment_id'
                                               ORDER BY NUM_LIKES DESC
                                               LIMIT 0, 3");
                    $jsonRoot = array("postId" => $postid, "comments" => array());
                    while($row = $result->fetch_assoc()) {
                        $paramArray = array(
                            "comment" => array(
                                "commentId" => $row['COMMENT_ID'],
                                "time" => $row['TIME'],
                                "repost" => (int)$comment_id,
                                "info" => array(
                                    "author" => $row['AUTHOR'],
                                    "content" => $row['CONTENT'],
                                    "numlikes" => $row['NUM_LIKES']
                                )
                            )
                        );
                        array_push($jsonRoot["comments"], $paramArray);
                    }
                    $this->response = OkJson(json_encode($jsonRoot));
                    break;
                default:
                    throw new MyException("参数错误", 10012);
                    break;
            }
        } catch (MyException $e) {
            throw $e;
        } finally {
            $mysqldb->close();
        }
        return $this->response;
    }
    
    /**
     * @param string $data
     *
     * @Description
     * {"post":
     *    {"title":"", "content":"", "repost":"",
     *     "location":{"areaid":"", "latitude":"", "longitude":""},
     *     "mentions":[{"name":""}, {"name":""}, ...],
     *     "extra":{"extra.xxx":"png", "extra.xxx":"", ...}
     *    }
     * }
     * @return
     * {"postid":"'.$post_id.'", "commentid":"'.$comment_id.'"}
     */
    public function sendPost($data) {
        $data = str_replace("\n", "\\n", $data);
        if(!$json = json_decode($data)) throw new MyException("json格式错误", 10017);
        $json = new JsonElement($json);
        //初始化数据
        $user_id = $this->user_id;
        $repost = $json->post->repost;
        $repost = ($repost=="" || $repost==null) ? "NULL" : $repost;
        if(!$title = $json->post->title) throw new MyException("缺少参数type", 10016);
        if(!$content = $json->post->content) throw new MyException("缺少参数type", 10016);
        $medias = $json->post->extra;
        if(!$area_id = $json->post->location->areaid) throw new MyException("缺少参数type", 10016);
        if(!$latitude = $json->post->location->latitude) throw new MyException("缺少参数type", 10016);   //纬度
        if(!$longitude = $json->post->location->longitude) throw new MyException("缺少参数type", 10016); //经度
        $pos = strpos($latitude, ".");
        $latitude = substr($latitude, 0, $pos+8);
        $pos = strpos($longitude, ".");
        $longitude = substr($longitude, 0, $pos+8);
        $geohash = (new Geohash())->encode($latitude, $longitude);
        $geohash = substr($geohash, 0, 11);
        $time = date("Y-m-d H:i:s");
        $mentions = array();
        foreach ($json->post->mentions as $mention) {
            if(!isset($mention->name)) throw new MyException("mantions中缺少参数name", 10016);
            array_push($mentions, $mention->name);
        }
        
        $mysqldb = new MysqlDB();
        try {
            $mysqldb->autocommit(false);
            $mysqldb->prepare("INSERT INTO POSTS(POST_ID, REPOST, TIME, TITLE, CONTENT, MEDIA,
                                                    AREA_ID, AUTHOR, NUM_LIKES, NUM_REPOSTS)
                                VALUES
                                (NULL, $repost, ?, ?, ?, NULL, ?, ?, 0, 0)");
            $mysqldb->bind_param("sssss", $time, $title, $content, $area_id, $user_id);
            $mysqldb->execute();
            //获取帖子id
            $sql = "SELECT LAST_INSERT_ID()";
            $result = $mysqldb->query($sql);
            if($result->num_rows == 0){
                throw new MyException("未能获取ID", MyException::SERVER_ERROR);
            }
            $row = $result->fetch_assoc();
            $post_id = $row["LAST_INSERT_ID()"];
            //插入媒体数据地址
            $mysqldb->prepare("UPDATE POSTS SET MEDIA = ? WHERE POST_ID = $post_id");
            $mediaPath = "/api/v1/public/medias/".$user_id."/posts/".$post_id."/";
            $mediaJson = saveMedia($medias, $mediaPath);
            $mysqldb->bind_param("s", $mediaJson);
            $mysqldb->execute();
            //插入用户帖子表
            $mysqldb->query("INSERT INTO USER_POSTS(USER_ID, POST_ID, TIME)
                             VALUES ('$this->user_id', '$post_id', '$time')");
            //插入位置
            $mysqldb->query("INSERT INTO POSTS_LOC(POST_ID, LOCATION, GEOHASH, TIME)
                             VALUES
                             ('$post_id', ST_GEOMFROMTEXT('POINT($longitude $latitude)'), '$geohash', '$time')");
            //创建评论区root
            $result = $mysqldb->query("INSERT INTO COMMENTS(COMMENT_ID, REPOST, AUTHOR, CONTENT, TIME, NUM_LIKES)
                                       VALUES
                                       (NULL, NULL, '$user_id', '', '$time', 0)");
            //获取评论id
            $sql = "SELECT LAST_INSERT_ID()";
            $result = $mysqldb->query($sql);
            if($result->num_rows == 0){
                throw new MyException("未能获取ID", MyException::SERVER_ERROR);
            }
            $row = $result->fetch_assoc();
            $comment_id = $row["LAST_INSERT_ID()"];
            //添加@人员
            foreach($mentions as $to_name){
                $mysqldb->prepare("SELECT USER_ID FROM USERS_DATA WHERE NICKNAME = ?");
                $mysqldb->bind_param("s", $to_name);
                $mysqldb->execute();
                $result = $mysqldb->get_result();
                while($row = $result->fetch_assoc()) {
                    $to_id = $row['USER_ID'];
                    $mysqldb->query("INSERT INTO MENTIONS(ID, FROM_ID, TO_ID, COMMENT_ID)
                             VALUES (NULL, '$user_id', '$to_id', '$comment_id')");
                }
                //TODO:通知
            }
            //建立联系
            $mysqldb->query("INSERT INTO POST_COMMENT(POST_ID, COMMENT_ID)
                             VALUES ('$post_id', '$comment_id')");
            $mysqldb->commit();
            $this->response = OkJson('{"postid":"'.$post_id.'", "commentid":"'.$comment_id.'"}');
        } catch (MyException $e) {
            $mysqldb->rollback();
            throw $e;
        } finally {
            $mysqldb->close();
        }
        return $this->response;
    }
    
    public function deletePost($post_id) {
        $mysqldb = new MysqlDB();
        try {
            $mysqldb->autocommit(false);
            $result = $mysqldb->query("SELECT COMMENT_ID FROM POST_COMMENT WHERE POST_ID = '$post_id'");
            $row = $result->fetch_assoc();
            $comment_id = $row['COMMENT_ID'];
            $mysqldb->query("DELETE FROM POSTS WHERE POST_ID = '$post_id'");
            $mysqldb->query("DELETE FROM COMMENTS WHERE COMMENT_ID = '$comment_id'");
            $mysqldb->commit();
            $mysqldb->query("DELETE FROM POSTS_LOC WHERE POST_ID = '$post_id'");
            $mysqldb->commit();
            $this->response = OkJson("");
        } catch (MyException $e) {
            throw $e;
        } finally {
            $mysqldb->close();
        }
        return $this->response;
    }
    
    public function like($target, $id, $i) {
        switch($target) {
            case "blog":
                $table = "POSTS";
                $col = "POST_ID";
                break;
            case "comment":
                $table = "COMMENTS";
                $col = "COMMENT_ID";
                break;
            default:
                throw new MyException("参数错误", 10012);
                break;
        }
        $mysqldb = new MysqlDB();
        try {
            $mysqldb->query("UPDATE $table SET NUM_LIKES = NUM_LIKES $i WHERE $col = '$id'");
            $this->response = OkJson("");
        } catch (MyException $e) {
            throw $e;
        } finally {
            $mysqldb->close();
        }
        return $this->response;
    }
    
    public function star($post_id) {
        $mysqldb = new MysqlDB();
        try {
            $mysqldb->query("REPLACE INTO POST_STARS(USER_ID, POST_ID) VALUES ('$this->user_id', '$post_id')");
            $this->response = OkJson("");
        } catch (MyException $e) {
            throw $e;
        } finally {
            $mysqldb->close();
        }
        return $this->response;
    }
    
    public function unstar($post_id) {
        $mysqldb = new MysqlDB();
        try {
            $mysqldb->query("DELETE FROM POST_STARS WHERE USER_ID = '$this->user_id' AND POST_ID = '$post_id'");
            $this->response = OkJson("");
        } catch (MyException $e) {
            throw $e;
        } finally {
            $mysqldb->close();
        }
        return $this->response;
    }
    
    /**
     * @param int $father_id
     * @param int $page
     * @param bool $isPost
     * @throws MyException
     * @return string|mixed
     * {"commentGroup":{
     *      "rootcomment":{"id":"", "repost":"", "author":"", "content":"", "time":"", "numlikes":(int)},
     *      "subcomments":[
     *              {"comment":{"id":"", "repost":"", "author":"", "content":"", "time":"", "numlikes":(int)}}
     *              {"comment":{"id":"", "repost":"", "author":"", "content":"", "time":"", "numlikes":(int)}}
     *              {"comment":{"id":"", "repost":"", "author":"", "content":"", "time":"", "numlikes":(int)}}
     *              ...
     *      ]}
     * }
     */
    public function getComments($father_id, $page, bool $isPost = false) {
        $mysqldb = new MysqlDb();
        try {
            if(!$isPost) {
                if($page == "") {
                    throw new MyException("参数limit不能为空", 10016);
                }
                //自己信息
                $result = $mysqldb->query("SELECT REPOST, AUTHOR, CONTENT, TIME, NUM_LIKES
                                           FROM COMMENTS WHERE COMMENT_ID = '$father_id'");
                if($result->num_rows == 0) {
                    throw new MyException("动态已删除", 10404);
                } else {
                    $jsonRoot = array("commentGroup" => array());
                    $row = $result->fetch_assoc();
                    $paramArray = array(
                        "id" => $father_id,
                        "repost" => $row['REPOST'],
                        "author" => $row['AUTHOR'],
                        "time" => $row['TIME'],
                        "content" => $row['CONTENT'],
                        "numlikes" => $row['NUM_LIKES']
                    );
                    $jsonRoot["commentGroup"]["rootcomment"] = $paramArray;
                    $start = ($page - 1) * 10;
                    $result = $mysqldb->query("SELECT COMMENT_ID, AUTHOR, CONTENT, TIME, NUM_LIKES
                                               FROM COMMENTS WHERE REPOST = '$father_id'
                                               ORDER BY TIME DESC
                                               LIMIT $start, 10");
                    $jsonRoot["commentGroup"]["subcomment"] = array();
                    while($row = $result->fetch_assoc()) {
                        $paramArray = array(
                            "comment" => array(
                                "id" => $row['COMMENT_ID'],
                                "repost" => $father_id,
                                "author" => $row['AUTHOR'],
                                "time" => $row['TIME'],
                                "content" => $row['CONTENT'],
                                "numlikes" => $row['NUM_LIKES']
                            )
                        );
                        $jsonRoot["commentGroup"]["subcomment"][] = $paramArray;
                    }
                    $this->response = OkJson(json_encode($jsonRoot));
                }
            } else {
                $search_id = $father_id;
                while(1) {
                    $result = $mysqldb->query("SELECT REPOST
                                               FROM COMMENTS WHERE COMMENT_ID = '$search_id'");
                    $row = $result->fetch_assoc();
                    $id = $row['REPOST'];
                    if($id == NULL) break;
                    $search_id = $id;
                }
                $result = $mysqldb->query("SELECT POST_ID FROM POST_COMMENT WHERE COMMENT_ID = '$search_id'");
                if($result->num_rows == 0) {
                    throw new MyException("动态已删除", 10404);
                } else {
                    $row = $result->fetch_assoc();
                    $post_id = $row['POST_ID'];
                    $this->response = OkJson('{"post":{"id":"'.$post_id.'"}}');
                }
            }
        } catch (MyException $e) {
            throw $e;
        } finally {
            $mysqldb->close();
        }
        return $this->response;
    }
    /**
     * @return string|mixed
     * {"comments":[{"comment":{"commentId":""}},
     *              {"comment":{"commentId":""}},
     *              ...
     * ]}
     */
    public function getSelfComments() {
        $mysqldb = new MysqlDb();
        try {
            $result = $mysqldb->query("SELECT COMMENT_ID FROM COMMENTS WHERE AUTHOR = '$this->user_id'");
            $jsonRoot = array("comments" => array());
            while($row = $result->fetch_assoc()) {
                $paramArray = array(
                    "comment" => array(
                        "commentId" => $row['COMMENT_ID']
                    )
                );
                $jsonRoot["comments"][] = $paramArray;
            }
            $this->response = OkJson(json_encode($jsonRoot));
        } catch (MyException $e) {
            throw $e;
        } finally {
            $mysqldb->close();
        }
        return $this->response;
    }
    /**
     * @param int $repost_id
     * @param string $value
     * {"comment":{"content":"", "mentions":[{"name":""}, {"name":""}]}}
     * @throws MyException
     * @return string|mixed
     * {"comment":{"id":""}}
     */
    public function sendComment($repost_id, $data) {
        $data = str_replace("\n", "\\n", $data);
        if(!$json = json_decode($data)) throw new MyException("json格式错误", 10017);
        $json = new JsonElement($json);
        if(!$content = $json->comment->content) throw new MyException("缺少参数content", 10016);
        $mentions = array();
        foreach ($json->comment->mentions as $mention) {
            if(!isset($mention->name)) throw new MyException("mantions中缺少参数name", 10016);
            array_push($mentions, $mention->name);
        }
        $mysqldb = new MysqlDB();
        try {
            $mysqldb->autocommit(false);
            $mysqldb->prepare("INSERT INTO COMMENTS(COMMENT_ID, REPOST, AUTHOR, CONTENT, TIME, NUM_LIKES)
                               VALUES (NULL, ?, ?, ?, NOW(), 0)");
            $mysqldb->bind_param("sss", $repost_id, $this->user_id, $content);
            $mysqldb->execute();
            $result = $mysqldb->query("SELECT LAST_INSERT_ID()");
            $row = $result->fetch_assoc();
            $comment_id = $row["LAST_INSERT_ID()"];
            //添加@人员
            foreach($mentions as $to_name){
                $mysqldb->prepare("SELECT USER_ID FROM USERS_DATA WHERE NICKNAME = ?");
                $mysqldb->bind_param("s", $to_name);
                $mysqldb->execute();
                $result = $mysqldb->get_result();
                while($row = $result->fetch_assoc()) {
                    $to_id = $row['USER_ID'];
                    $mysqldb->query("INSERT INTO MENTIONS(ID, FROM_ID, TO_ID, COMMENT_ID)
                             VALUES (NULL, '$this->user_id', '$to_id', '$comment_id')");
                }
                //TODO:通知
            }
            $mysqldb->commit();
            $this->response = OkJson('{"comment":{"id":"'.$comment_id.'"}}');
        } catch (MyException $e) {
            $mysqldb->rollback();
            throw $e;
        } finally {
            $mysqldb->close();
        }
        return $this->response;
    }
    
    public function deleteComment($comment_id) {
        $mysqldb = new MysqlDB();
        try {
            $mysqldb->autocommit(false);
            $mysqldb->query("DELETE FROM COMMENTS WHERE COMMENT_ID = '$comment_id'");
            $mysqldb->commit();
            $this->response = OkJson("");
        } catch (MyException $e) {
            $mysqldb->rollback();
            throw $e;
        } finally {
            $mysqldb->close();
        }
        return $this->response;
    }
    /**
     * @throws MyException
     * @return string|mixed
     * {"posts":[{"post":{"postId":""}}, {"post":{"postId":""}}, ...]}
     */
    public function getSelfStars() {
        $mysqldb = new MysqlDB();
        try {
            $result = $mysqldb->query("SELECT POST_ID FROM POST_STARS WHERE USER_ID = '$this->user_id'");
            $jsonRoot = array("posts" => array());
            while($row = $result->fetch_assoc()) {
                $paramArray = array(
                    "post" => array(
                        "postId" => $row['POST_ID']
                        )
                );
                $jsonRoot["posts"][] = $paramArray;
            }
            $this->response = OkJson(json_encode($jsonRoot));
        } catch (MyException $e) {
            throw $e;
        } finally {
            $mysqldb->close();
        }
        return $this->response;
    }
    
    var $relation2str = array(
        0 => "normal",
        1 => "special",
        "0" => "normal",
        "1" => "special"
    );
    var $str2relation = array(
        "normal" => 0,
        "special" => 1
    );
    /**
     * @param string $type
     * @param string $line
     * @param int $limit
     * @throws MyException
     * @return string|mixed
     * {"posts":[{"post":{"postId":""}}, {"post":{"postId":""}}, ...]}
     */
    public function pushPosts($type, $line, $limit) {
        $mysqldb = new MysqlDB();
        $start = ($limit - 1) * 10;
        try {
            switch($type) {
                case "follow":
                    $group = isset($_GET['group']) ? $_GET['group'] : "all";
                    if($group == "all") {
                        $relStr = "";
                    } else {
                        $relation = isset($this->str2relation[$group]) ? $this->str2relation[$group] : "";
                        if($relation == "") {
                            throw new MyException("不存在的关系", 10404);
                        }
                        $relStr = " AND RELATION = '$relation'";
                    }
                    $result = $mysqldb->query("SELECT POST_ID FROM USER_POSTS WHERE USER_ID IN
                                               (SELECT FOLLOW_ID FROM USER_FOLLOWS WHERE USER_ID = '$this->user_id' $relStr) AND
                                               UNIX_TIMESTAMP(TIME) > $line
                                               ORDER BY TIME DESC
                                               LIMIT $start, 10");
                    break;
                case "friend":
                    $result = $mysqldb->query("SELECT POST_ID FROM USER_POSTS WHERE USER_ID IN
                                               (SELECT FRIEND_ID FROM USER_FRIENDS WHERE USER_ID = '$this->user_id') AND
                                               UNIX_TIMESTAMP(TIME) > $line
                                               ORDER BY TIME DESC
                                               LIMIT $start, 10");
                    break;
                case "surround":
                    $result = $mysqldb->query("SELECT ST_ASTEXT(LOCATION), GEOHASH FROM USERS_LOC WHERE USER_ID = '$this->user_id'");
                    if($result->num_rows == 0) {
                        throw new MyException("需要先上传位置", 10016);
                    }
                    $row = $result->fetch_assoc();
                    $location = $row['ST_ASTEXT(LOCATION)'];
                    $geohash = $row['GEOHASH'];
                    
                    $geohashclass = new Geohash();
                    $geohashArray = $geohashclass->expand($geohash);
                    $upleft = substr($geohashArray['upleft'], 0, 4);
                    $up = substr($geohashArray['up'], 0, 4);
                    $upright = substr($geohashArray['upright'], 0, 4);
                    $right = substr($geohashArray['right'], 0, 4);
                    $downright = substr($geohashArray['downright'], 0, 4);
                    $down = substr($geohashArray['down'], 0, 4);
                    $downleft = substr($geohashArray['downleft'], 0, 4);
                    $left = substr($geohashArray['left'], 0, 4);
                    
                    $result = $mysqldb->query("SELECT POST_ID, LOCATION, TIME, ST_DISTANCE_SPHERE(ST_GEOMFROMTEXT('$location'), LOCATION) AS DISTANCE
                                               FROM POSTS_LOC
                                               WHERE UNIX_TIMESTAMP(TIME) > $line AND
                                               GEOHASH LIKE '$upleft%' OR GEOHASH LIKE '$up%' OR GEOHASH LIKE '$upright%' OR
                                               GEOHASH LIKE '$left%' OR GEOHASH LIKE '$geohash%' OR GEOHASH LIKE '$right%' OR
                                               GEOHASH LIKE '$downleft%' OR GEOHASH LIKE '$down%' OR GEOHASH LIKE '$downright%'
                                               HAVING DISTANCE < 5000
                                               ORDER BY DISTANCE
                                               LIMIT $start , 10");
                    break;
            }
            $jsonRoot = array("posts" => array());
            while($row = $result->fetch_assoc()) {
                $paramArray = array(
                    "post" => array(
                        "postId" => $row['POST_ID']
                    )
                );
                $jsonRoot["posts"][] = $paramArray;
            }
            $this->response = OkJson(json_encode($jsonRoot));
        } catch (MyException $e) {
            throw $e;
        } finally {
            $mysqldb->close();
        }
        return $this->response;
    }
}
?>