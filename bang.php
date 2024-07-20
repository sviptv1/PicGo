<?php
/*
一、如果采用本站提供的视频分类和采集接口，以下代码除分类的地区和年代可做修改外，其他均不要做任何改动。
二、重要说明说三遍：
除填入采集接口、分类的地区和年代外，其他均不要做任何改动，以免出错！！！
除填入采集接口、分类的地区和年代外，其他均不要做任何改动，以免出错！！！
除填入采集接口、分类的地区和年代外，其他均不要做任何改动，以免出错！！！
*/

declare (strict_types = 1);
const BF = __DIR__ . '/application/extra/bind.php';

//此处填入采集接口的名称和地址，如:接口名#接口地址
$jiekouarray=[
    "黑木耳#https://json.heimuer.xyz/api.php/provide/vod/?ac=list",
    "红牛#https://www.hongniuzy2.com/api.php/provide/vod/at/xml/",
    ];
//此处填入分类的地区和年代
$diqu='大陆,香港,台湾,美国,法国,英国,日本,韩国,德国,泰国,印度,意大利,西班牙,加拿大,其他';
$niandai='2024,2023,2022,2021,2020,2019,2018,2017,2016,2015,2014,2013,2012,2011,2010,2009,2008,2006,2005,2004';

//以下代码不要做任何修改，以免出错！
$bind = include BF;
$type=[
    "电影"=>["动作","喜剧","爱情","科幻","剧情","悬疑","惊悚","恐怖","犯罪","谍战","冒险","奇幻","灾难","战争","动画","歌舞","历史","传记","纪录","其他"],
    "电视剧"=>["武侠","喜剧","爱情","剧情","青春","悬疑","科幻","军事","警匪","谍战","奇幻","偶像","年代","乡村","都市","家庭","古装","历史","神话","其他"],
    "综艺"=>["脱口秀","真人秀","搞笑","访谈","生活","晚会","美食","游戏","亲子","旅游","文化","体育","时尚","纪实","益智","演艺","歌舞","音乐","播报","其他"],
    "动漫"=>["热血","格斗","恋爱","美少女","校园","搞笑","LOLI","神魔","机战","科幻","真人","青春","魔法","神话","冒险","运动","竞技","童话","亲子","教育","励志","剧情","社会","历史","战争","其他"],
    "纪录"=>["人物","军事","历史","自然","探险","科技","文化","刑侦","社会","旅游","其他"]
   ];
   
db::exec('DELETE FROM mac_type');
$dalei='{\"class\":\"\",\"area\":\"'.$diqu.'\",\"lang\":\"\",\"year\":\"'.$niandai.'\",\"star\":\"\",\"director\":\"\",\"state\":\"\",\"version\":\"\"}';
foreach (array_keys($type) as $k => $type_name) {
	$type_id= $k + 1;
	$sql = 'INSERT INTO mac_type (`type_id`,`type_name`,`type_en`,`type_pid`, `type_tpl`,`type_tpl_list`,`type_tpl_detail`,`type_tpl_play`,`type_tpl_down`,`type_extend`) VALUES ('.$type_id.',"'.$type_name.'","'.Pinyin::get($type_name).'",0,"type.html","show.html","detail.html","play.html","down.html","'.$dalei.'" );';
	db::exec($sql);
	foreach ($type[$type_name] as $id => $name) {
		$tid= $type_id . sprintf("%02d", $id + 1);
		$sql= 'INSERT INTO mac_type (`type_id`,`type_name`,`type_en`,`type_pid`, `type_tpl`,`type_tpl_list`,`type_tpl_detail`,`type_tpl_play`,`type_tpl_down`,`type_extend`) VALUES ( '. $tid.',"'.$name.'","'.Pinyin::get($name).'",'.$type_id.',"type.html","show.html","detail.html","play.html","down.html","'.$dalei.'");';
		db::exec($sql);
	}
}
$arr = [];
foreach ($jiekouarray as $k => $jiekou) {
    $name=explode('#',$jiekou)[0];
    $api=explode('#',$jiekou)[1];
    $sql = 'DELETE FROM mac_collect WHERE collect_url="'.$api.'"';
    db::exec($sql);
    if(substr_count($api,'xml')){
        $sql = 'INSERT INTO mac_collect (`collect_name`,`collect_url`,`collect_type`,`collect_mid`, `collect_filter`,`collect_opt`) VALUES ( "'.$name .'","'.$api.'",1,1,0,0);';
    }else{
        $sql = 'INSERT INTO mac_collect (`collect_name`,`collect_url`,`collect_type`,`collect_mid`, `collect_filter`,`collect_opt`) VALUES ( "'.$name .'","'.$api.'",2,1,0,0);';
    }
    db::exec($sql);
    $arr1=[];
    foreach (array_keys($type) as $k1 => $type_name) {
        $type_id= $k1 + 1;
        $arr1[md5($api).'_' . $type_id] = $type_id;
        foreach ($type[$type_name] as $id => $name) {
            $tid= $type_id . sprintf("%02d", $id + 1);
		    $arr1[md5($api).'_' . $tid] = (int) $tid;
        }
    }
    $arr=array_merge($arr, $arr1);
}
$bind=[];
$bind = array_merge($bind, $arr);
$con  = var_export($bind, true);
file_put_contents(BF, "<?php\nreturn $con;");
echo "自动建立并绑定分类成功";

class db
{
    protected static $pdo = null;
    public static function exec($sql = '')
    {
        if (!isset(self::$pdo)) {
            $c         = include __DIR__.'/application/database.php';
            self::$pdo = new PDO("mysql:host={$c['hostname']};dbname={$c['database']};port={$c['hostport']}", $c['username'], $c['password'], [\PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC]);
        }
        try {
            return self::$pdo->exec($sql);
        } catch (Throwable $e) {
            print_r($e);
        }
    }
}
class Pinyin
{
    private static $pinyins = null;

    public function __construct() {

    }

    public static function get($str, $ret_format = 'all', $placeholder = '', $allow_chars = '/[a-zA-Z\d ]/') {

        if (null === self::$pinyins) {
            $data = file_get_contents('./static/data/pinyin.dat');

            $rows = explode('|', $data);

            self::$pinyins = array();
            foreach($rows as $v) {
                list($py, $vals) = explode(':', $v);
                $chars = explode(',', $vals);

                foreach ($chars as $char) {
                    self::$pinyins[$char] = $py;
                }
            }
        }

        $str = trim($str);
        $len = mb_strlen($str, 'UTF-8');
        $rs = '';
        for ($i = 0; $i < $len; $i++) {
            $chr = mb_substr($str, $i, 1, 'UTF-8');
            $asc = ord($chr);
            if ($asc < 0x80) { // 0-127
                if (preg_match($allow_chars, $chr)) { // 用参数控制正则
                    $rs .= $chr; // 0-9 a-z A-Z 空格
                } else { // 其他字符用填充符代替
                    $rs .= $placeholder;
                }
            } else { // 128-255
                if (isset(self::$pinyins[$chr])) {
                    $rs .= 'first' === $ret_format ? self::$pinyins[$chr][0] : (self::$pinyins[$chr] . '');
                } else {
                    $rs .= $placeholder;
                }
            }

            if ('one' === $ret_format && '' !== $rs) {
                return $rs[0];
            }
        }
        $rs = str_replace([' ','+','/','\\','|','\'','?','%','#','&','=','!','(',')',';',':','<','>'],'',$rs);
        return $rs;
    }

}
?>