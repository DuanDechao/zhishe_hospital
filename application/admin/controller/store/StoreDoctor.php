<?php

namespace app\admin\controller\store;

use app\admin\controller\AuthController;
use service\FormBuilder as Form;
use app\admin\model\store\StoreProductAttr;
use app\admin\model\store\StoreProductAttrResult;
use app\admin\model\store\StoreProductRelation;
use app\admin\model\system\SystemConfig;
use service\JsonService;
use traits\CurdControllerTrait;
use service\UtilService as Util;
use service\JsonService as Json;
use service\UploadService as Upload;
use think\Request;
use app\admin\model\store\StoreCategory as CategoryModel;
use app\admin\model\store\StoreDoctor as DoctorModel;
use app\admin\model\store\StoreHospital as HospitalModel;
use think\Url;
use app\admin\model\ump\StoreSeckill as StoreSeckillModel;
use app\admin\model\order\StoreOrder as StoreOrderModel;
use app\admin\model\ump\StoreBargain as StoreBargainModel;
use app\admin\model\system\SystemAttachment;
use think\Log;
use think\Db;


/**
 * 医生管理
 * Class StoreProduct
 * @package app\admin\controller\store
 */
class StoreDoctor extends AuthController
{

    use CurdControllerTrait;

    protected $bindModel = DoctorModel::class;

    /**
     * 显示资源列表
     *
     * @return \think\Response
     */
    public function index()
    {

        $type=$this->request->param('type');
        //获取分类
        //$this->assign('cate',CategoryModel::getTierList());
        //上线的医院机构
        $onsale =  DoctorModel::where(['is_show'=>1,'is_del'=>0])->count();
        //待上线的医院机构
        $forsale =  DoctorModel::where(['is_show'=>0,'is_del'=>0])->count();
        //仓库中产品
       // $warehouse =  DoctorModel::where(['is_del'=>0])->count();
        //已经售馨产品
       // $outofstock = DoctorModel::getModelObject()->where(DoctorModel::setData(4))->count();
        //警戒库存
       // $policeforce =DoctorModel::getModelObject()->where(DoctorModel::setData(5))->count();
        //回收站
        $recycle =  DoctorModel::where(['is_del'=>1])->count();

        $this->assign(compact('type','onsale','forsale','warehouse','outofstock','policeforce','recycle'));
        return $this->fetch();
    }
    /**
     * 异步查找产品
     *
     * @return json
     */
    public function product_ist(){
        $where=Util::getMore([
            ['page',1],
            ['limit',20],
            ['store_name',''],
            ['cate_id',''],
            ['excel',0],
            ['type',$this->request->param('type')]
        ]);
        return JsonService::successlayui(DoctorModel::ProductList($where));
    }
    /**
     * 设置单个产品上架|下架
     *
     * @return json
     */
    public function set_show($is_show='',$id=''){
        ($is_show=='' || $id=='') && JsonService::fail('缺少参数');
        $res=DoctorModel::where(['id'=>$id])->update(['is_show'=>(int)$is_show]);
        if($res){
            return JsonService::successful($is_show==1 ? '上架成功':'下架成功');
        }else{
            return JsonService::fail($is_show==1 ? '上架失败':'下架失败');
        }
    }
    /**
     * 快速编辑
     *
     * @return json
     */
    public function set_product($field='',$id='',$value=''){
        $field=='' || $id=='' || $value=='' && JsonService::fail('缺少参数');
        if(DoctorModel::where(['id'=>$id])->update([$field=>$value]))
            return JsonService::successful('保存成功');
        else
            return JsonService::fail('保存失败');
    }
    /**
     * 设置批量产品上架
     *
     * @return json
     */
    public function product_show(){
        $post=Util::postMore([
            ['ids',[]]
        ]);
        if(empty($post['ids'])){
            return JsonService::fail('请选择需要上架的产品');
        }else{
            $res=DoctorModel::where('id','in',$post['ids'])->update(['is_show'=>1]);
            if($res)
                return JsonService::successful('上架成功');
            else
                return JsonService::fail('上架失败');
        }
    }
    /**
     * 显示创建资源表单页.
     *
     * @return \think\Response
     */
    public function create()
    {
//        $this->assign(['title'=>'添加产品','action'=>Url::build('save'),'rules'=>$this->rules()->getContent()]);
//        return $this->fetch('public/common_form');
		$metalLabels = array();
		$metalPrices = Db::name('MetalPrice')->field('id, name')->select();
		foreach($metalPrices as $levelId => $metalPrice){
			array_push($metalLabels, ['value'=>$metalPrice['id'], 'label'=> $metalPrice['name']]);
		}
        $field = [
            Form::select('cate_id','所属医院')->setOptions(function(){
                $list = HospitalModel::getTierList();
                $menus=[];
                foreach ($list as $menu){
					$menus[] = ['value'=>$menu['id'],'label'=>$menu['store_name']];//,'disabled'=>$menu['pid']== 0];
                }
                return $menus;
			})->filterable(1)->multiple(1),
            Form::input('store_name','医生姓名')->col(Form::col(24)),
            Form::input('store_info','医生简介')->type('textarea'),
            Form::input('keyword','医生关键字')->placeholder('多个用英文状态下的逗号隔开'),
            Form::frameImageOne('image','医生照片(305*305px)',Url::build('admin/widget.images/index',array('fodder'=>'image')))->icon('image')->width('100%')->height('550px')->spin(0),
            Form::number('sex','性别')->min(0)->precision(0)->col(8),
            Form::radio('is_show','医院状态',0)->options([['label'=>'上线','value'=>1],['label'=>'下线','value'=>0]])->col(8),
        ];
        $form = Form::create(Url::build('save'));
        $form->setMethod('post')->setTitle('添加医生')->components($field)->setSuccessScript('parent.$(".J_iframe:visible")[0].contentWindow.location.reload();');
        $this->assign(compact('form'));
        return $this->fetch('public/form-builder');
    }

    /**
     * 上传图片
     * @return \think\response\Json
     */
    public function upload()
    {
        $res = Upload::image('file','store/product/'.date('Ymd'));
        $thumbPath = Upload::thumb($res->dir);
        //产品图片上传记录
        $fileInfo = $res->fileInfo->getinfo();
        SystemAttachment::attachmentAdd($res->fileInfo->getSaveName(),$fileInfo['size'],$fileInfo['type'],$res->dir,$thumbPath,1);
        if($res->status == 200)
            return Json::successful('图片上传成功!',['name'=>$res->fileInfo->getSaveName(),'url'=>Upload::pathToUrl($thumbPath)]);
        else
            return Json::fail($res->error);
    }

    /**
     * 保存新建的资源
     *
     * @param  \think\Request  $request
     * @return \think\Response
     */
    public function save(Request $request)
    {
        $data = Util::postMore([
            'store_name',
            'store_info',
            'keyword',
            ['image',[]],
			'sex',
			['cate_id',[]],
            ['is_show',0],
        ],$request);
        if(count($data['cate_id']) < 1) return Json::fail('请选择医院');
        $data['cate_id'] = $data['cate_id'];
        if(!$data['store_name']) return Json::fail('请输入医生姓名');
//        if(!$data['store_info']) return Json::fail('请输入产品简介');
//        if(!$data['keyword']) return Json::fail('请输入产品关键字');
        if(count($data['image'])<1) return Json::fail('请上传医生照片');
        $data['image'] = $data['image'][0];
        $data['add_time'] = time();
        $data['description'] = '';
        DoctorModel::set($data);
        return Json::successful('添加产品成功!');
    }


    public function edit_content($id){
        if(!$id) return $this->failed('数据不存在');
        $product = DoctorModel::get($id);
        if(!$product) return Json::fail('数据不存在!');
        $this->assign([
            'content'=>DoctorModel::where('id',$id)->value('description'),
            'field'=>'description',
            'action'=>Url::build('change_field',['id'=>$id,'field'=>'description'])
        ]);
        return $this->fetch('public/edit_content');
    }

    /**
     * 显示编辑资源表单页.
     *
     * @param  int  $id
     * @return \think\Response
     */
    public function edit($id)
    {
        if(!$id) return $this->failed('数据不存在');
        $product = DoctorModel::get($id);
        if(!$product) return Json::fail('数据不存在!');
        $form = Form::create(Url::build('update',array('id'=>$id)),[
            Form::select('cate_id','所属医院',$product->getData('cate_id'))->setOptions(function(){
                $list = HospitalModel::getTierList();
                $menus=[];
                foreach ($list as $menu){
                    $menus[] = ['value'=>$menu['id'],'label'=>$menu['store_name']];//,'disabled'=>$menu['pid']== 0];
                }
                return $menus;
            })->filterable(1)->multiple(1),
            Form::input('store_name','医生名称',$product->getData('store_name')),
            Form::input('store_info','医生简介',$product->getData('store_info'))->type('textarea'),
            Form::input('keyword','关键字',$product->getData('keyword'))->placeholder('多个用英文状态下的逗号隔开'),
            Form::frameImageOne('image','医院主图片(305*305px)',Url::build('admin/widget.images/index',array('fodder'=>'image')),$product->getData('image'))->icon('image')->width('100%')->height('550px')->spin(0),
            Form::number('sex','性别',$product->getData('sex'))->min(0)->precision(0)->col(8),
            Form::radio('is_show','医生状态',$product->getData('is_show'))->options([['label'=>'上线','value'=>1],['label'=>'下线','value'=>0]])->col(8),
        ]);
        $form->setMethod('post')->setTitle('编辑医生')->setSuccessScript('parent.$(".J_iframe:visible")[0].contentWindow.location.reload();');
        $this->assign(compact('form'));
        return $this->fetch('public/form-builder');
    }



    /**
     * 保存更新的资源
     *
     * @param  \think\Request  $request
     * @param  int  $id
     * @return \think\Response
     */
    public function update(Request $request, $id)
    {
        $data = Util::postMore([
            ['cate_id',[]],
            'store_name',
            'store_info',
            'keyword',
            ['image',[]],
            ['is_show',0],
        ],$request);
        if(count($data['cate_id']) < 1) return Json::fail('请选择所属医院');
        $data['cate_id'] = implode(',',$data['cate_id']);
        if(!$data['store_name']) return Json::fail('请输入医生姓名');
        if(count($data['image'])<1) return Json::fail('请上传医生照片');
        $data['image'] = $data['image'][0];
        DoctorModel::edit($data,$id);
        return Json::successful('修改成功!');
    }

    public function attr($id)
    {
        if(!$id) return $this->failed('数据不存在!');
        $result = StoreProductAttrResult::getResult($id);
        $image = DoctorModel::where('id',$id)->value('image');
        $this->assign(compact('id','result','product','image'));
        return $this->fetch();
    }
    /**
     * 生成属性
     * @param int $id
     */
    public function is_format_attr($id = 0){
        if(!$id) return Json::fail('产品不存在');
        list($attr,$detail) = Util::postMore([
            ['items',[]],
            ['attrs',[]]
        ],$this->request,true);
        $product = DoctorModel::get($id);
        if(!$product) return Json::fail('产品不存在');
        $attrFormat = attrFormat($attr)[1];
        if(count($detail)){
            foreach ($attrFormat as $k=>$v){
                foreach ($detail as $kk=>$vv){
                    if($v['detail'] == $vv['detail']){
                        $attrFormat[$k]['price'] = $vv['price'];
                        $attrFormat[$k]['cost'] = isset($vv['cost']) ? $vv['cost'] : $product['cost'];
                        $attrFormat[$k]['sales'] = $vv['sales'];
                        $attrFormat[$k]['pic'] = $vv['pic'];
                        $attrFormat[$k]['check'] = false;
                        $attrFormat[$k]['metal_diff'] = $vv['metal_diff'];
                        $attrFormat[$k]['metal_weight'] = $vv['metal_weight'];
                        $attrFormat[$k]['price_type'] = $vv['price_type'];
                        break;
                    }else{
                        $attrFormat[$k]['cost'] = $product['cost'];
                        $attrFormat[$k]['price'] = '';
                        $attrFormat[$k]['sales'] = '';
                        $attrFormat[$k]['pic'] = $product['image'];
                        $attrFormat[$k]['check'] = true;
                        $attrFormat[$k]['metal_diff'] = $product['metal_diff'];
                        $attrFormat[$k]['metal_weight'] = $product['metal_weight'];
                        $attrFormat[$k]['price_type'] = $product['price_type'];
                    }
                }
            }
        }else{
            foreach ($attrFormat as $k=>$v){
                $attrFormat[$k]['cost'] = $product['cost'];
                $attrFormat[$k]['price'] = $product['price'];
                $attrFormat[$k]['sales'] = $product['stock'];
                $attrFormat[$k]['pic'] = $product['image'];
                $attrFormat[$k]['check'] = false;
                $attrFormat[$k]['metal_diff'] = $product['metal_diff'];
                $attrFormat[$k]['metal_weight'] = $product['metal_weight'];
                $attrFormat[$k]['price_type'] = $product['price_type'];
            }
        }
        return Json::successful($attrFormat);
    }

    public function set_attr($id)
    {
        if(!$id) return $this->failed('产品不存在!');
        list($attr,$detail) = Util::postMore([
            ['items',[]],
            ['attrs',[]]
        ],$this->request,true);
        $res = StoreProductAttr::createProductAttr($attr,$detail,$id);
        if($res)
            return $this->successful('编辑属性成功!');
        else
            return $this->failed(StoreProductAttr::getErrorInfo());
    }

    public function clear_attr($id)
    {
        if(!$id) return $this->failed('产品不存在!');
        if(false !== StoreProductAttr::clearProductAttr($id) && false !== StoreProductAttrResult::clearResult($id))
            return $this->successful('清空产品属性成功!');
        else
            return $this->failed(StoreProductAttr::getErrorInfo('清空产品属性失败!'));
    }

    /**
     * 删除指定资源
     *
     * @param  int  $id
     * @return \think\Response
     */
    public function delete($id)
    {
        if(!$id) return $this->failed('数据不存在');
        $data['is_del'] = 1;
        if(!DoctorModel::edit($data,$id))
            return Json::fail(DoctorModel::getErrorInfo('删除失败,请稍候再试!'));
        else
            return Json::successful('删除成功!');
    }




    /**
     * 点赞
     * @param $id
     * @return mixed|\think\response\Json|void
     */
    public function collect($id){
        if(!$id) return $this->failed('数据不存在');
        $product = DoctorModel::get($id);
        if(!$product) return Json::fail('数据不存在!');
        $this->assign(StoreProductRelation::getCollect($id));
        return $this->fetch();
    }

    /**
     * 收藏
     * @param $id
     * @return mixed|\think\response\Json|void
     */
    public function like($id){
        if(!$id) return $this->failed('数据不存在');
        $product = DoctorModel::get($id);
        if(!$product) return Json::fail('数据不存在!');
        $this->assign(StoreProductRelation::getLike($id));
        return $this->fetch();
    }
    /**
     * 修改产品价格
     * @param Request $request
     */
    public function edit_product_price(Request $request){
        $data = Util::postMore([
            ['id',0],
            ['price',0],
        ],$request);
        if(!$data['id']) return Json::fail('参数错误');
        $res = DoctorModel::edit(['price'=>$data['price']],$data['id']);
        if($res) return Json::successful('修改成功');
        else return Json::fail('修改失败');
    }

    /**
     * 修改产品库存
     * @param Request $request
     */
    public function edit_product_stock(Request $request){
        $data = Util::postMore([
            ['id',0],
            ['stock',0],
        ],$request);
        if(!$data['id']) return Json::fail('参数错误');
        $res = DoctorModel::edit(['stock'=>$data['stock']],$data['id']);
        if($res) return Json::successful('修改成功');
        else return Json::fail('修改失败');
    }



}
