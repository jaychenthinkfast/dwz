<?php
/**
 * Created by PhpStorm.
 * User: chenjie
 */

namespace ctrl;
use Hashids\Hashids;

class Index{

    public function index($request, $response){
        $html = <<<EOT
<!DOCTYPE html>
<html>
<title>短网址生成器</title>
<meta name="robots" content="noindex, nofollow">
</html>
<body>
<form id="shortener"><label for="longurl">生成短网址(请填写(http|https开头的网址)</label> <input type="text" name="longurl" id="longurl" value="">  <input type="submit" value="提交"></form><div id="url"></div>
<script src="//ajax.googleapis.com/ajax/libs/jquery/1.3.2/jquery.min.js"></script>
<script>
$(function () {
	$('#shortener').submit(function () {
		$.ajax({data: {longurl: $('#longurl').val()}, url: '/Index/shorten', complete: function (XMLHttpRequest, textStatus) {
			$('#url').text(XMLHttpRequest.responseText);
		}});
		return false;
	});
});
</script>
</body>
</html>
EOT;
        return $html;
    }

    public function shorten($request, $response){

        $url_to_shorten = get_magic_quotes_gpc() ? stripslashes(trim($request->get['longurl'])) : trim($request->get['longurl']);

        if(!empty($url_to_shorten) && preg_match('|^https?://|', $url_to_shorten)) {
            try {
                $dbh = new \PDO(DB_TYPE.':host='.DB_HOST.';dbname='.DB_NAME, DB_USER, DB_PASSWORD);
                $dbh->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            } catch (\PDOException $e) {
                return "数据库连接错误";
            }
            // 检查url是否有效
            if(CHECK_URL) {
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url_to_shorten);
                curl_setopt($ch,  CURLOPT_RETURNTRANSFER, TRUE);
                $response = curl_exec($ch);
                $response_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);
                if($response_status != '200') {
                    return '这是一个无效的网址';
                }
            }

            $stmt = $dbh->prepare('SELECT id FROM '.DB_TABLE.' WHERE long_url=?');

            // 检测url是否被缩短过
            $stmt->execute(array($url_to_shorten));
            $tmp = $stmt->fetch(\PDO::FETCH_NUM);
            $already_shortened = $tmp[0];
            if(!empty($already_shortened)) {

                $shortened_url = $this->getShortenedURLFromID($already_shortened);
            } else {
                // url不在数据库
                try {
                    $stmt2 = $dbh->prepare('INSERT INTO '.DB_TABLE." (long_url, created, creator) VALUES (?,?,?)");
                    $stmt2->execute(array($url_to_shorten, time(), $request->server['remote_addr']));
                    $stmt->execute(array($url_to_shorten));
                    $tmp = $stmt->fetch(\PDO::FETCH_NUM);
                    $shortened_url = $this->getShortenedURLFromID($tmp[0]);
                    if(empty($shortened_url)) {
                        return "数据库入库失败";
                    }
                } catch (\Exception $e) {
                    return "数据库错误";
                }
            }
            return 'http://'.$request->header['host'] .'/'. $shortened_url;
        }
    }

    public function redirect($request, $response){

        $url = $request->get['url'];
        $shortened_id = $this->getIDFromShortenedURL($url);
        try {
            $dbh = new \PDO(DB_TYPE.':host='.DB_HOST.';dbname='.DB_NAME, DB_USER, DB_PASSWORD);
            $dbh->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        } catch (\PDOException $e) {
            return "数据库连接错误";
        }
        if(CACHE) {
            $long_url = @file_get_contents(CACHE_DIR . $shortened_id);
            if(empty($long_url) || !preg_match('|^https?://|', $long_url)) {
                try {
                    $stmt = $dbh->prepare('SELECT long_url FROM '.DB_TABLE." WHERE id=?");
                    $stmt->execute(array($shortened_id));
                    $tmp = $stmt->fetch(\PDO::FETCH_NUM);
                    $long_url = $tmp[0];
                    @mkdir(CACHE_DIR, 0777);
                    $handle = fopen(CACHE_DIR . $shortened_id, 'w+');
                    fwrite($handle, $long_url);
                    fclose($handle);
                } catch (\Exception $e) {
                    return "数据库错误";
                }
            }
        } else {
            try {
                $stmt = $dbh->prepare('SELECT long_url FROM '.DB_TABLE." WHERE id=?");
                $stmt->execute(array($shortened_id));
                $tmp = $stmt->fetch(\PDO::FETCH_NUM);
                $long_url = $tmp[0];
            } catch (\Exception $e) {
                return "数据库错误";
            }
        }

        if(TRACK) {
            $dbh->query('UPDATE ' . DB_TABLE . ' SET referrals=referrals+1 WHERE id="' . intval($shortened_id) . '"');
        }
        $response->status(301);
        $response->header('Location', $long_url);
        return $long_url;
    }

    public function getIDFromShortenedURL ($string, $base = ALLOWED_CHARS){

        $hashids = new Hashids();
        $hex = $hashids->decode($string);
        return $hex[0];
    }

    public function getShortenedURLFromID ($integer, $base = ALLOWED_CHARS){
        $hashids = new Hashids();
        return  $hashids->encode($integer);
    }
}