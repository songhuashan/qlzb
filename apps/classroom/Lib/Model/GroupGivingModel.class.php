$sql<?php
/**
 * 课程模型 - 数据对象模型
 * @author wayne <idafoo@sina.com>
 * @version TS3.0
 */
class GroupGivingModel extends Model
{
    protected $tableName = 'group_giving';
	protected $fields = array(0=>'gid',1=>'kid',2=>'ctype',3=>'num',4=>'addtime');

	/**
	 * 添加或修改用户组信息
	 * @param array $d 相关用户组信息
	 * @return integer 相关用户组ID
	 */
	public function addgroup($data) {

	    
          
        	//$res = $this->add($data);
        	
           // $this->cleanCache();

           // return $res;
	}
    

 
}
