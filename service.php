<?php
require_once 'json.php';
    //创建WebSocket Server对象，监听0.0.0.0:9502端口
    $ws = new Swoole\WebSocket\Server('0.0.0.0', 9502);
    $redis = new redis;
    $redis->connect('127.0.0.1', 6379);

    //监听WebSocket连接打开事件
    $ws->on('open', function ($ws, $request) use ($redis) {
        var_dump('user'.$request->fd .'进入');
        $ws->push($request->fd, (new Msg('欢迎你！',[],1,['name'=>'Admin']))->json());
    });

    //监听WebSocket消息事件
    $ws->on('message', function ($ws, $frame) use ($redis) {
        $data =  getMsg($frame);
        if ($data->getCode() == 2){
            $redis->set('user'.$frame->fd,json_encode($data->getData()['user']));
            $users = getAllUser($ws,$redis);
            sendToAll($ws,(new Msg('',[
                'users'=>$users
            ],3))->json());
        }
        if ($data->getCode() == 1){
            $user = getUser($frame->fd,$redis);
            $data->setFrom($user);
            sendToOther($ws,$frame->fd,$data->json());
        }
    });

    //监听WebSocket连接关闭事件
    $ws->on('close', function ($ws, $fd) use ($redis){
        echo "client-{$fd} is closed".PHP_EOL;
        $redis->del('user'.$fd);
        $users = getAllUser($ws,$redis);
        sendToAll($ws,(new Msg('',[
            'users'=>$users
        ],3))->json());
    });
    $ws->start();


    /**
     * @param $ws
     * @param $redis redis
     */
    function getAllUser($ws,$redis){
        $keys = $redis->keys('user*');
        var_dump($keys);
        $users = [];
        foreach ($keys as $key){
            $fd = (int)str_replace('user','',$key);
            if (!$ws->isEstablished($fd)){
                $redis->del($key);
            }else{
                $users [] = json_decode($redis->get($key));
            }
        }
        return $users;
    }

    function getUser($fd,$redis){
        $user = json_decode($redis->get('user'.$fd));
        if (!$user){
            $user = [
                'name'=>'用户信息异常',
                'avatar'=>'t1.png'
            ];
        }
        if (is_object($user)){
            $user = object_to_array($user);
        }
        return $user;
    }

    // 将消息发送给被人
    function sendToOther($ws, $ex, $data){
        foreach ($ws->connections as $fd)  {
            // 需要先判断是否是正确的websocket连接，否则有可能会push失败
            if ($ws->isEstablished($fd) && $fd != $ex) {
                $ws->push($fd,$data);
            }
        }
    }
    function sendToAll($ws, $data){
        foreach ($ws->connections as $fd) {
            // 需要先判断是否是正确的websocket连接，否则有可能会push失败
            if ($ws->isEstablished($fd)) {
                $ws->push($fd,$data);
            }
        }
    }
    function getMsg($frame){
        $data = json_decode($frame->data);
        if ($data){
            $data = object_to_array($data);
            $msg = '';
            $da = [];
            $code = 1;
            if (key_exists('msg',$data)){
                $msg = $data['msg'];
            }
            if (key_exists('data',$data)){
                $da = $data['data'];
            }
            if (key_exists('code',$data)){
                $code = $data['code'];
            }
            return new Msg($msg,$da,$code);
        }else{
            return new Msg('数据错误',[],0);
        }
    }

    function array_to_object($arr) {
        if (gettype($arr) != 'array') {
            return false;
        }
        foreach ($arr as $k => $v) {
            if (gettype($v) == 'array' || getType($v) == 'object') {
                $arr[$k] = (object)array_to_object($v);
            }
        }
        return (object)$arr;
    }
    function object_to_array($obj) {
        $obj = (array)$obj;
        foreach ($obj as $k => $v) {
            if (gettype($v) == 'resource') {
                return false;
            }
            if (gettype($v) == 'object' || gettype($v) == 'array') {
                $obj[$k] = (array)object_to_array($v);
            }
        }

        return $obj;
    }

    class Msg {
        private $msg;
        private $data;
        private $from;
        private $to;
        private $code;
        public function __construct($msg = '',$data = [], $code=1,$from = [],$to = 0)
        {
            $this->msg = $msg;
            $this->data = $data;
            $this->code = $code;
            $this->from = $from;
            $this->to = $to;
        }
        public function json(){
            return json_encode(['code'=>$this->code,'msg'=>$this->msg,'data'=>$this->data,'from'=>$this->from]);
        }

        /**
         * @return string
         */
        public function getMsg(): string
        {
            return $this->msg;
        }

        /**
         * @param string $msg
         */
        public function setMsg(string $msg): void
        {
            $this->msg = $msg;
        }

        /**
         * @return array
         */
        public function getData(): array
        {
            return $this->data;
        }

        /**
         * @param array $data
         */
        public function setData(array $data): void
        {
            $this->data = $data;
        }

        /**
         * @return array
         */
        public function getFrom(): array
        {
            return $this->from;
        }

        /**
         * @param array $from
         */
        public function setFrom(array $from): void
        {
            $this->from = $from;
        }

        /**
         * @return int
         */
        public function getTo(): int
        {
            return $this->to;
        }

        /**
         * @param int $to
         */
        public function setTo(int $to): void
        {
            $this->to = $to;
        }

        /**
         * 0 普通消息 1 上线消息
         * @return int
         */
        public function getCode(): int
        {
            return $this->code;
        }

        /**
         * @param int $code
         */
        public function setCode(int $code): void
        {
            $this->code = $code;
        }

    }

    class User{
        private $name;
        private $avatar;

        /**
         * User constructor.
         * @param string $name
         * @param string $avatar
         */
        public function __construct($name = '', $avatar = '')
        {
            $this->name = $name;
            $this->avatar = $avatar;
        }

        public function code(){
            return json_encode([
                'name'=>$this->name,
                'avatar'=>$this->avatar
            ]);
        }
        public function decode($user_str){
            $res = json_decode($user_str);
            if (key_exists('name',$res)){
                $this->name = $res['name'];
            }
            if (key_exists('avatar',$res)){
                $this->avatar = $res['avatar'];
            }
            return $this;
        }
    }
