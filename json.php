<?php

function json_res($msg = '新消息',$data = [],$code = 0){
    return json_encode(['code'=>$code,'msg'=>$msg,'data'=>$data]);
}
