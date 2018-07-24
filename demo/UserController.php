<?php 
/**
 * @author lxw
 * @group(name="coinyee", description="币易接口文档描述", title="币易接口文档", project="coinyee", url="192.168.0.1")
 */

class UserController {
    
    function __construct() {
        
    }


    /**
     * @ApiDescription(获取用户列表)
     * @ApiMethod(post)
     * @ApiUrl(192.168.0.1:80/doc/GetUserInfo)
     * @ApiNotice(这是接口的说明)
     * @ApiSuccess(value="{'code':200,'msg':'success','content':[{'email':'string','age':'integer'},{'email':'string','age':'integer'}]}")
     * @ApiParams(name="id", type="integer", is_selected=true, description="User id", place="body")
     * @ApiParams(name="sort", type="enum[asc,desc]", description="User data", place="body")
     * @ApiReturn(name="id", type="integer", description="User id")
     * @ApiReturn(name="sort", type="enum[asc,desc]", description="sort data")
     * @ApiReturn(name="page", type="integer", description="data of page")
     * @ApiReturn(name="count", type="integer", description="data of page")
     */
    public function GetUserInfo(){
        echo 'hello';
    }
}

?>