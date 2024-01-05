<?php

declare(strict_types=1);

namespace App\Service;

use App\Model\CourseOnlineChild;
use App\Model\Goods;
use App\Model\GoodsEditOriginData;
use App\Model\GoodsSku;
use App\Model\GoodsFile;
use App\Model\PropValue;
use App\Model\PropName;
use App\Snowflake\IdGenerator;
use App\Constants\ErrorCode;
use Hyperf\DbConnection\Db;

class GoodsService extends BaseService
{
    /**
     * 添加教具商品
     * @param array $params
     * @return array
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function addTeachingAidsGoods(array $params): array
    {
        $goodsName = $params['name'];
        $suitAgeMin = $params['suit_age_min'];
        $suitAgeMax = $params['suit_age_max'];
        $sku = $params['sku'];
        $describe = $params['describe'] ?? '';
        $imgs = $params['imgs'];
        $teachVideo = $params['teach_video'];
        $goodsVideo = $params['video_url'] ?? '';
        $propNameStr = $params['sku'][0]['prop_name_str'];
        $goodsEditOriginData = $params['prop_origin_form'];
        $commissionRate = $params['commission_rate'] ?? 0;
        $goodsId = IdGenerator::generate();

        if(empty($sku)){
            return ['code' => ErrorCode::WARNING, 'msg' => '商品规格不能为空', 'data' => null];
        }
        $propNameArray = !empty($propNameStr) ? explode(':',$propNameStr) : [];
        $propValueStrArray = array_column($sku,'prop_value_str');
        $propValueNameArray = [];
        foreach($propValueStrArray as $value){
            $propValue = explode(':',$value);
            foreach($propValue as $k=>$item){
                $propValueNameArray[$item] = $propNameArray[$k];
            }
        }
        //关联教程视频
        if(!empty($teachVideo)){
            $courseOnlineChildId = array_column($teachVideo,'id');
        }
        $courseOnlineChildId = !empty($courseOnlineChildId) ? json_encode($courseOnlineChildId,JSON_UNESCAPED_UNICODE) : '';

        //商品sku
        $priceList = [];
        $totalStock = 0;
        $insertGoodsSkuData = [];
        foreach($sku as $value){
            $skuInfo = [];
            $stock = $value['stock'] ?? 0;
            $price = $value['price'] ?? 0;
            $skuId = IdGenerator::generate();
            $totalStock += $stock;
            $priceList[] = $price;

            $skuInfo['id'] = $skuId;
            $skuInfo['goods_id'] = $goodsId;
            $skuInfo['price'] = $price;
            $skuInfo['stock'] = $stock;
            $skuInfo['prop_value_str'] = $value['prop_value_str'];
            $skuInfo['img_url'] = $value['img_url'] ?? '';
            $insertGoodsSkuData[] = $skuInfo;
        }
        sort($priceList);
        $minPrice = $priceList[0];
        //商品规格展示数据
        $insertGoodsPropReach = [];
        foreach($propValueNameArray as $key=>$value){
            $propValue = $key;
            $propName = $value;

            $goodsPropReachId = IdGenerator::generate();
            $goodsPropReachInfo['id'] = $goodsPropReachId;
            $goodsPropReachInfo['goods_id'] = $goodsId;
            $goodsPropReachInfo['prop_name'] = $propName;
            $goodsPropReachInfo['prop_value'] = $propValue;
            $insertGoodsPropReach[] = $goodsPropReachInfo;
        }
        //商品规格数据排序
        foreach($propNameArray as $key=>$value){
            foreach($insertGoodsPropReach as $k=>$item){
                if($value == $item['prop_name']){
                    $insertGoodsPropReach[$k]['sort'] = $key+1;
                }
            }
        }
        //商品文件
        $insertGoodsFileData = [];
        foreach($imgs as $value){
            $goodsFile = [];
            $goodsFile['id'] = IdGenerator::generate();
            $goodsFile['goods_id'] = $goodsId;
            $goodsFile['url'] = $value;
            $goodsFile['scene_type'] = 1;
            $insertGoodsFileData[] = $goodsFile;
        }
        foreach($teachVideo as $value){
            $goodsFile = [];
            $goodsFile['id'] = IdGenerator::generate();
            $goodsFile['goods_id'] = $goodsId;
            $goodsFile['url'] = $value;
            $goodsFile['scene_type'] = 2;
            $insertGoodsFileData[] = $goodsFile;
        }

        //商品数据
        $insertGoodsData['id'] = $goodsId;
        $insertGoodsData['name'] = $goodsName;
        $insertGoodsData['img_url'] = $imgs[0];
        $insertGoodsData['video_url'] = $goodsVideo;
        $insertGoodsData['suit_age_min'] = $suitAgeMin;
        $insertGoodsData['suit_age_max'] = $suitAgeMax;
        $insertGoodsData['describe'] = $describe;
        $insertGoodsData['stock'] = $totalStock;
        $insertGoodsData['min_price'] = $minPrice;
        $insertGoodsData['prop_name_str'] = $propNameStr;
        $insertGoodsData['course_online_child_id'] = $courseOnlineChildId;
        $insertGoodsData['commission_rate'] = $commissionRate;
        $insertGoodsData['invite_code'] = $this->functions->randomCode();
        //商品编辑原始表单数据
        $insertGoodsEditOriginData['id'] = IdGenerator::generate();
        $insertGoodsEditOriginData['data'] = $goodsEditOriginData;
        $insertGoodsEditOriginData['goods_id'] = $goodsId;

        Db::connection('jkc_edu')->beginTransaction();
        try{
            Db::connection('jkc_edu')->table('goods')->insert($insertGoodsData);
            Db::connection('jkc_edu')->table('goods_sku')->insert($insertGoodsSkuData);
            Db::connection('jkc_edu')->table('goods_prop_reach')->insert($insertGoodsPropReach);
            Db::connection('jkc_edu')->table('goods_file')->insert($insertGoodsFileData);
            Db::connection('jkc_edu')->table('goods_edit_origin_data')->insert($insertGoodsEditOriginData);
            Db::connection('jkc_edu')->commit();
        } catch(\Throwable $e){
            Db::connection('jkc_edu')->rollBack();
            throw new \Exception($e->getMessage(), 1);
        }
        return ['code' => ErrorCode::SUCCESS, 'msg' => '', 'data' => null];
    }

    /**
     * 编辑教具商品
     * @param array $params
     * @return array
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function editTeachingAidsGoods(array $params): array
    {
        $goodsId = $params['id'];
        $goodsName = $params['name'];
        $suitAgeMin = $params['suit_age_min'];
        $suitAgeMax = $params['suit_age_max'];
        $sku = $params['sku'];
        $describe = $params['describe'] ?? '';
        $imgs = $params['imgs'];
        $teachVideo = $params['teach_video'];
        $goodsVideo = $params['video_url'] ?? '';
        $propNameStr = $params['sku'][0]['prop_name_str'];
        $goodsEditOriginData = $params['prop_origin_form'];
        $commissionRate = $params['commission_rate'] ?? 0;

        $propNameArray = !empty($propNameStr) ? explode(':',$propNameStr) : [];
        $propValueStrArray = array_column($sku,'prop_value_str');
        $propValueNameArray = [];
        foreach($propValueStrArray as $value){
            $propValue = explode(':',$value);
            foreach($propValue as $k=>$item){
                $propValueNameArray[$item] = $propNameArray[$k];
            }
        }
        //关联教程视频
        if(!empty($teachVideo)){
            $courseOnlineChildId = array_column($teachVideo,'id');
        }
        $courseOnlineChildId = !empty($courseOnlineChildId) ? json_encode($courseOnlineChildId,JSON_UNESCAPED_UNICODE) : '';

        //商品sku
        $priceList = [];
        $totalStock = 0;
        $insertGoodsSkuData = [];
        foreach($sku as $value){
            $skuInfo = [];
            $stock = $value['stock'] ?? 0;
            $price = $value['price'] ?? 0;
            $skuId = IdGenerator::generate();
            $totalStock += $stock;
            $priceList[] = $price;

            $skuInfo['id'] = $skuId;
            $skuInfo['goods_id'] = $goodsId;
            $skuInfo['price'] = $price;
            $skuInfo['stock'] = $stock;
            $skuInfo['prop_value_str'] = $value['prop_value_str'];
            $skuInfo['img_url'] = $value['img_url'] ?? '';
            $insertGoodsSkuData[] = $skuInfo;
        }
        sort($priceList);
        $minPrice = $priceList[0];
        //商品规格展示数据
        $insertGoodsPropReach = [];
        foreach($propValueNameArray as $key=>$value){
            $propValue = $key;
            $propName = $value;

            $goodsPropReachId = IdGenerator::generate();
            $goodsPropReachInfo['id'] = $goodsPropReachId;
            $goodsPropReachInfo['goods_id'] = $goodsId;
            $goodsPropReachInfo['prop_name'] = $propName;
            $goodsPropReachInfo['prop_value'] = $propValue;
            $insertGoodsPropReach[] = $goodsPropReachInfo;
        }
        //商品规格数据排序
        foreach($propNameArray as $key=>$value){
            foreach($insertGoodsPropReach as $k=>$item){
                if($value == $item['prop_name']){
                    $insertGoodsPropReach[$k]['sort'] = $key+1;
                }
            }
        }
        //商品文件
        $insertGoodsFileData = [];
        foreach($imgs as $value){
            $goodsFile = [];
            $goodsFile['id'] = IdGenerator::generate();
            $goodsFile['goods_id'] = $goodsId;
            $goodsFile['url'] = $value;
            $goodsFile['scene_type'] = 1;
            $insertGoodsFileData[] = $goodsFile;
        }
        foreach($teachVideo as $value){
            $goodsFile = [];
            $goodsFile['id'] = IdGenerator::generate();
            $goodsFile['goods_id'] = $goodsId;
            $goodsFile['url'] = $value;
            $goodsFile['scene_type'] = 2;
            $insertGoodsFileData[] = $goodsFile;
        }
        //商品数据
        $updateGoodsData['name'] = $goodsName;
        $updateGoodsData['img_url'] = $imgs[0];
        $updateGoodsData['video_url'] = $goodsVideo;
        $updateGoodsData['suit_age_min'] = $suitAgeMin;
        $updateGoodsData['suit_age_max'] = $suitAgeMax;
        $updateGoodsData['describe'] = $describe;
        $updateGoodsData['stock'] = $totalStock;
        $updateGoodsData['min_price'] = $minPrice;
        $updateGoodsData['prop_name_str'] = $propNameStr;
        $updateGoodsData['course_online_child_id'] = $courseOnlineChildId;
        $updateGoodsData['commission_rate'] = $commissionRate;
        //商品编辑原始表单数据
        $insertGoodsEditOriginData['id'] = IdGenerator::generate();
        $insertGoodsEditOriginData['data'] = $goodsEditOriginData;
        $insertGoodsEditOriginData['goods_id'] = $goodsId;

        Db::connection('jkc_edu')->beginTransaction();
        try{
            Db::connection('jkc_edu')->table('goods_sku')->where(['goods_id'=>$goodsId])->delete();
            Db::connection('jkc_edu')->table('goods_prop_reach')->where(['goods_id'=>$goodsId])->delete();
            Db::connection('jkc_edu')->table('goods_file')->where(['goods_id'=>$goodsId])->delete();
            Db::connection('jkc_edu')->table('goods_edit_origin_data')->where(['goods_id'=>$goodsId])->delete();
            Db::connection('jkc_edu')->table('goods')->where('id',$goodsId)->update($updateGoodsData);
            Db::connection('jkc_edu')->table('goods_sku')->insert($insertGoodsSkuData);
            Db::connection('jkc_edu')->table('goods_prop_reach')->insert($insertGoodsPropReach);
            Db::connection('jkc_edu')->table('goods_file')->insert($insertGoodsFileData);
            Db::connection('jkc_edu')->table('goods_edit_origin_data')->insert($insertGoodsEditOriginData);
            Db::connection('jkc_edu')->commit();
        } catch(\Throwable $e){
            Db::connection('jkc_edu')->rollBack();
            throw new \Exception($e->getMessage(), 1);
        }
        return ['code' => ErrorCode::SUCCESS, 'msg' => '', 'data' => null];
    }

    /**
     * 上架教具商品
     * @param int $id
     * @return array
     */
    public function onlineTeachingAidsGoods(int $id): array
    {
        $date = date('Y-m-d H:i:s');
        Goods::query()->where(['id'=>$id])->update(['online'=>1,'online_at'=>$date]);
        return ['code' => ErrorCode::SUCCESS, 'msg' => '', 'data' => null];
    }

    /**
     * 下架教具商品
     * @param int $id
     * @return array
     */
    public function offlineTeachingAidsGoods(int $id): array
    {
        $date = date('Y-m-d H:i:s');
        Goods::query()->where(['id'=>$id])->update(['online'=>0,'offline_at'=>$date]);
        return ['code' => ErrorCode::SUCCESS, 'msg' => '', 'data' => null];
    }

    /**
     * 删除教具商品
     * @param int $id
     * @return array
     */
    public function deleteTeachingAidsGoods(int $id): array
    {
        Goods::query()->where(['id'=>$id])->update(['is_deleted'=>1]);
        return ['code' => ErrorCode::SUCCESS, 'msg' => '', 'data' => null];
    }

    /**
     * 置顶教具商品
     * @param int $id
     * @return array
     */
    public function toppingTeachingAidsGoods(int $id): array
    {
        $topGoodsInfo = Goods::query()->where(['is_deleted'=>0])->select(['sort'])->orderBy('sort','desc')->first();
        $topSort = 0;
        if(!empty($topGoodsInfo)){
            $topGoodsInfo = $topGoodsInfo->toArray();
            $topSort = $topGoodsInfo['sort'];
        }
        $topSort += 1;

        Goods::query()->where(['id'=>$id])->update(['sort'=>$topSort]);
        return ['code' => ErrorCode::SUCCESS, 'msg' => '', 'data' => null];
    }

    /**
     * 教具商品列表
     * @param array $params
     * @return array
     */
    public function teachingAidsGoodsList(array $params): array
    {
        $id = $params['id'];
        $category = $params['category'];
        $name = $params['name'];
        $online = $params['online'];
        $offset = $this->offset;
        $limit = $this->limit;

        $where = [['is_deleted','=',0]];
        if($category !== null){
            $where[] = ['category_id','=',$category];
        }
        if($name !== null){
            $where[] = ['name','like',"%{$name}%"];
        }
        if($id !== null){
            $where[] = ['id','=',$id];
        }
        if($online !== null){
            $where[] = ['online','=',$online];
        }

        $goodsList = Goods::query()
            ->select(['id','name','img_url','suit_age_min','suit_age_max','csale','online','stock','min_price','sham_csale'])
            ->where($where)
            ->offset($offset)->limit($limit)
            ->orderBy('id','desc')
            ->get();
        $goodsList = $goodsList->toArray();
        $count = Goods::query()->where($where)->count('id');
        return ['code' => ErrorCode::SUCCESS, 'msg' => '', 'data' => ['list'=>$goodsList,'count'=>$count]];
    }

    /**
     * 教具商品详情
     * @param int $id
     * @return array
     */
    public function teachingAidsGoodsDetail(int $id): array
    {
        $goodsInfo = Goods::query()
            ->select(['id','name','video_url','describe','suit_age_min','suit_age_max','prop_name_str','online','course_online_child_id','commission_rate'])
            ->where(['id'=>$id])
            ->first();
        if(empty($goodsInfo)){
            return ['code' => ErrorCode::WARNING, 'msg' => '商品不见了', 'data' => null];
        }
        $goodsInfo = $goodsInfo->toArray();

        //关联教程视频
        $courseOnlineChildList = [];
        $courseOnlineChildIdArray = !empty($goodsInfo['course_online_child_id']) ? json_decode($goodsInfo['course_online_child_id'],true): [];
        if(!empty($courseOnlineChildIdArray)){
            $courseOnlineChildList = CourseOnlineChild::query()->select(['id','video_url'])->whereIn('id',$courseOnlineChildIdArray)->get()->toArray();
        }

        //商品sku
        $goodsSkuList = GoodsSku::query()->where(['goods_id'=>$id])->select(['stock','prop_value_str','img_url','price'])->get();
        $goodsSkuList = $goodsSkuList->toArray();
        foreach($goodsSkuList as $key=>$value){
            $goodsSkuList[$key]['prop_name_str'] = $goodsInfo['prop_name_str'];
        }

        //商品文件
        $goodsFileList = GoodsFile::query()->where(['goods_id'=>$id])->select(['url','scene_type'])->get();
        $goodsFileList = $goodsFileList->toArray();
        $goodsFileList = $this->functions->arrayGroupBy($goodsFileList,'scene_type');
        $scrollFile = isset($goodsFileList[1]) ? $goodsFileList[1] : [];

        //商品编辑原始表单数据
        $goodsEditOriginDataInfo = GoodsEditOriginData::query()->select(['data'])->where(['goods_id'=>$id])->first();
        if(!empty($goodsEditOriginDataInfo)){
            $goodsEditOriginDataInfo = $goodsEditOriginDataInfo->toArray();
        }

        $goodsInfo['sku'] = $goodsSkuList;
        $goodsInfo['imgs'] = $scrollFile;
        $goodsInfo['prop_origin_form'] = $goodsEditOriginDataInfo['data'];
        $goodsInfo['teach_video'] = $courseOnlineChildList;
        return ['code' => ErrorCode::SUCCESS, 'msg' => '', 'data' => $goodsInfo];
    }

    /**
     * 添加商品规格名称
     * @param array $params
     * @return array
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws \TypeError
     */
    public function addPropName(array $params): array
    {
        $insertPropNameData['id'] = IdGenerator::generate();
        $insertPropNameData['name'] = $params['name'];
        $insertPropNameData['category_id'] = $params['category_id'];

        PropName::query()->insert($insertPropNameData);
        return ['code' => ErrorCode::SUCCESS, 'msg' => '', 'data' => null];
    }

    /**
     * 添加商品规格值
     * @param array $params
     * @return array
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws \TypeError
     */
    public function addPropValue(array $params): array
    {
        $insertPropValueData['id'] = IdGenerator::generate();
        $insertPropValueData['name'] = $params['name'];
        $insertPropValueData['prop_name_id'] = $params['prop_name_id'];

        PropValue::query()->insert($insertPropValueData);
        return ['code' => ErrorCode::SUCCESS, 'msg' => '', 'data' => null];
    }

    /**
     * 商品规格名称列表
     * @return array
     */
    public function propNameList(): array
    {
        $propNameList = PropName::query()->select(['id','name'])->get();
        $propNameList = $propNameList->toArray();
        return ['code' => ErrorCode::SUCCESS, 'msg' => '', 'data' => $propNameList];
    }

    /**
     * 商品规格值列表
     * @param array $params
     * @return array
     */
    public function propValueList(array $params): array
    {
        $propNameId = $params['prop_name_id'];

        $propNameList = PropName::query()->select(['id','name'])->where(['prop_name_id'=>$propNameId])->get();
        $propNameList = $propNameList->toArray();
        return ['code' => ErrorCode::SUCCESS, 'msg' => '', 'data' => $propNameList];
    }

    /**
     * 更改虚拟销量
     * @param array $params
     * @return array
     */
    public function updateShamCsale(array $params): array
    {
        $goodsId = $params['id'];
        $shamCsale = $params['sham_csale'];

        Goods::query()->where('id', $goodsId)->update(['sham_csale' => $shamCsale]);
        return ['code' => ErrorCode::SUCCESS, 'msg' => '', 'data' => null];
    }

}