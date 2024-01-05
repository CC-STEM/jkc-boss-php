<?php

declare(strict_types=1);

namespace App\Service;

use App\Logger\Log;
use App\Model\CourseDetailSetUp;
use App\Model\HomeAd;
use App\Model\HomeBoutiqueCourse;
use App\Model\MarketInfo;
use App\Model\Article;
use App\Model\ArticleTheme;
use App\Constants\ErrorCode;
use App\Model\VipCard;
use App\Snowflake\IdGenerator;

class FitUpService extends BaseService
{

    /**
     * 添加首页广告
     * @param array $params
     * @return array
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws \TypeError
     */
    public function addHomeAd(array $params): array
    {
        $imgUrl = $params['img_url'];
        $link = $params['link'] ?? '';

        $insertHomeAdData['id'] = IdGenerator::generate();
        $insertHomeAdData['img_url'] = $imgUrl;
        $insertHomeAdData['link'] = $link;
        HomeAd::query()->insert($insertHomeAdData);
        return ['code' => ErrorCode::SUCCESS, 'msg' => '', 'data' => null];
    }

    /**
     * 首页广告列表
     * @return array
     */
    public function homeAdList(): array
    {
        $homeAdList = HomeAd::query()->select(['id','img_url','link'])->get();
        $homeAdList = $homeAdList->toArray();
        return ['code' => ErrorCode::SUCCESS, 'msg' => '', 'data' => $homeAdList];
    }

    /**
     * 删除首页广告
     * @param int $id
     * @return array
     */
    public function deleteHomeAd(int $id): array
    {
        HomeAd::query()->where(['id'=>$id])->delete();
        return ['code' => ErrorCode::SUCCESS, 'msg' => '', 'data' => null];
    }

    /**
     * 添加首页推荐课程
     * @param array $params
     * @return array
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws \TypeError
     */
    public function addHomeBoutiqueCourse(array $params): array
    {
        $courseOnlineChildId = $params['course_online_child_id'];
        $startAt = $params['start_at'];
        $endAt = $params['end_at'];

        $insertHomeAdData['id'] = IdGenerator::generate();
        $insertHomeAdData['course_online_child_id'] = $courseOnlineChildId;
        $insertHomeAdData['start_at'] = $startAt;
        $insertHomeAdData['end_at'] = $endAt;
        HomeBoutiqueCourse::query()->insert($insertHomeAdData);
        return ['code' => ErrorCode::SUCCESS, 'msg' => '', 'data' => null];
    }

    /**
     * 首页推荐课程列表
     * @return array
     */
    public function homeBoutiqueCourseList(): array
    {
        $homeBoutiqueCourseList = HomeBoutiqueCourse::query()
            ->leftJoin('course_online_child','home_boutique_course.course_online_child_id','=','course_online_child.id')
            ->leftJoin('course_online','course_online_child.course_online_id','=','course_online.id')
            ->select(['course_online_child.name','course_online.suit_age_min','course_online.suit_age_max','course_online.type as author_type','home_boutique_course.id','home_boutique_course.start_at','home_boutique_course.end_at','home_boutique_course.created_at'])
            ->get();
        $homeBoutiqueCourseList = $homeBoutiqueCourseList->toArray();
        return ['code' => ErrorCode::SUCCESS, 'msg' => '', 'data' => $homeBoutiqueCourseList];
    }

    /**
     * 删除首页推荐课程
     * @param int $id
     * @return array
     */
    public function deleteHomeBoutiqueCourse(int $id): array
    {
        HomeBoutiqueCourse::query()->where(['id'=>$id])->delete();
        return ['code' => ErrorCode::SUCCESS, 'msg' => '', 'data' => null];
    }

    /**
     * 添加营销信息
     * @param array $params
     * @return array
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws \TypeError
     */
    public function addMarketInfo(array $params): array
    {
        $name = $params['name'];
        $imgUrl = $params['img_url'];
        $startAt = $params['start_at'];
        $endAt = $params['end_at'];
        $describe = $params['describe'] ?? '';

        $insertMarketInfoData['id'] = IdGenerator::generate();
        $insertMarketInfoData['img_url'] = $imgUrl;
        $insertMarketInfoData['name'] = $name;
        $insertMarketInfoData['start_at'] = $startAt;
        $insertMarketInfoData['end_at'] = $endAt;
        $insertMarketInfoData['describe'] = $describe;
        MarketInfo::query()->insert($insertMarketInfoData);
        return ['code' => ErrorCode::SUCCESS, 'msg' => '', 'data' => null];
    }

    /**
     * 编辑营销信息
     * @param array $params
     * @return array
     */
    public function editMarketInfo(array $params): array
    {
        $id = $params['id'];
        $name = $params['name'];
        $imgUrl = $params['img_url'];
        $startAt = $params['start_at'];
        $endAt = $params['end_at'];
        $describe = $params['describe'];

        $updateMarketInfoData['img_url'] = $imgUrl;
        $updateMarketInfoData['name'] = $name;
        $updateMarketInfoData['start_at'] = $startAt;
        $updateMarketInfoData['end_at'] = $endAt;
        $updateMarketInfoData['describe'] = $describe;
        MarketInfo::query()->where(['id'=>$id])->update($updateMarketInfoData);
        return ['code' => ErrorCode::SUCCESS, 'msg' => '', 'data' => null];
    }

    /**
     * 营销信息列表
     * @param array $params
     * @return array
     */
    public function marketInfoList(array $params): array
    {
        $name = $params['name'];
        $offset = $this->offset;
        $limit = $this->limit;

        $where = [];
        if($name !== null){
            $where[] = ['name','like',"%{$name}%"];
        }
        $marketInfoList = MarketInfo::query()
            ->select(['id','name','start_at','end_at','created_at'])
            ->where($where)
            ->offset($offset)->limit($limit)
            ->get();
        $marketInfoList = $marketInfoList->toArray();
        $date = date('Y-m-d H:i:s');

        foreach($marketInfoList as $key=>$value){
            $status = 0;
            if($value['start_at'] <= $date && $value['end_at'] > $date){
                $status = 1;
            }else if($value['end_at'] <= $date){
                $status = 2;
            }
            $marketInfoList[$key]['status'] = $status;
        }
        return ['code' => ErrorCode::SUCCESS, 'msg' => '', 'data' => $marketInfoList];
    }

    /**
     * 营销信息详情
     * @param int $id
     * @return array
     */
    public function marketInfoDetail(int $id): array
    {
        $marketInfo = MarketInfo::query()->select(['id','name','img_url','start_at','end_at','describe'])->where(['id'=>$id])->first();
        if(empty($marketInfo)){
            return ['code' => ErrorCode::WARNING, 'msg' => '数据错误', 'data' => null];
        }
        $marketInfo = $marketInfo->toArray();
        return ['code' => ErrorCode::SUCCESS, 'msg' => '', 'data' => $marketInfo];
    }

    /**
     * 删除营销信息
     * @param int $id
     * @return array
     */
    public function deleteMarketInfo(int $id): array
    {
        MarketInfo::query()->where(['id'=>$id])->delete();
        return ['code' => ErrorCode::SUCCESS, 'msg' => '', 'data' => null];
    }

    /**
     * 添加文章主题
     * @param array $params
     * @return array
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws \TypeError
     */
    public function addArticleTheme(array $params): array
    {
        $type = $params['type'];
        $name = $params['name'];
        $img = $params['img'] ?? '';

        $insertArticleThemeData['id'] = IdGenerator::generate();
        $insertArticleThemeData['name'] = $name;
        $insertArticleThemeData['type'] = $type;
        $insertArticleThemeData['img'] = $img;
        ArticleTheme::query()->insert($insertArticleThemeData);
        return ['code' => ErrorCode::SUCCESS, 'msg' => '', 'data' => null];
    }

    /**
     * 文章主题列表
     * @param array $params
     * @return array
     */
    public function articleThemeList(array $params): array
    {
        $type = $params['type'];
        $articleThemeList = ArticleTheme::query()->select(['id','name','img'])->where(['type'=>$type])->get();
        $articleThemeList = $articleThemeList->toArray();
        return ['code' => ErrorCode::SUCCESS, 'msg' => '', 'data' => $articleThemeList];
    }

    /**
     * 删除文章主题
     * @param int $id
     * @return array
     */
    public function deleteArticleTheme(int $id): array
    {
        ArticleTheme::query()->where(['id'=>$id])->delete();
        return ['code' => ErrorCode::SUCCESS, 'msg' => '', 'data' => null];
    }

    /**
     * 添加文章
     * @param array $params
     * @return array
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws \TypeError
     */
    public function addArticle(array $params): array
    {
        $articleThemeId = $params['article_theme_id'] ?? 0;
        $title = $params['title'] ?? '';
        $type = $params['type'];
        $content = $params['content'];

        if($type == 3){
            $articleInfo = Article::query()->select(['id'])->where(['type'=>3])->first();
            if(!empty($articleInfo)){
                $articleInfo = $articleInfo->toArray();
                $updateArticleData['content'] = $content;
                Article::query()->where(['id'=>$articleInfo['id']])->update($updateArticleData);
                return ['code' => ErrorCode::SUCCESS, 'msg' => '', 'data' => []];
            }
        }else if($type == 2){
            $articleInfo = Article::query()->select(['id'])->where(['article_theme_id'=>$articleThemeId])->first();
            if(!empty($articleInfo)){
                $articleInfo = $articleInfo->toArray();
                $updateArticleData['content'] = $content;
                Article::query()->where(['id'=>$articleInfo['id']])->update($updateArticleData);
                return ['code' => ErrorCode::SUCCESS, 'msg' => '', 'data' => []];
            }
        }
        $insertArticleData['id'] = IdGenerator::generate();
        $insertArticleData['article_theme_id'] = $articleThemeId;
        $insertArticleData['title'] = $title;
        $insertArticleData['type'] = $type;
        $insertArticleData['content'] = $content;
        Article::query()->insert($insertArticleData);
        return ['code' => ErrorCode::SUCCESS, 'msg' => '', 'data' => null];
    }

    /**
     * 编辑文章
     * @param array $params
     * @return array
     */
    public function editArticle(array $params): array
    {
        $id = $params['id'];
        $title = $params['title'] ?? '';
        $content = $params['content'];

        $updateArticleData['title'] = $title;
        $updateArticleData['content'] = $content;
        Article::query()->where(['id'=>$id])->update($updateArticleData);
        return ['code' => ErrorCode::SUCCESS, 'msg' => '', 'data' => null];
    }

    /**
     * 文章列表
     * @param array $params
     * @return array
     */
    public function articleList(array $params): array
    {
        $articleThemeId = $params['article_theme_id'];
        $type = $params['type'];
        $offset = $this->offset;
        $limit = $this->limit;

        $articleList = [];
        if($articleThemeId !== null){
            $articleList = Article::query()
                ->select(['id','title','content','created_at'])
                ->where(['article_theme_id'=>$articleThemeId])
                ->offset($offset)
                ->limit($limit)
                ->get();
            $articleList = $articleList->toArray();
        }else if($type == 3){
            $articleInfo = Article::query()->select(['id','content'])->where(['type'=>3])->first();
            if(empty($articleInfo)){
                return ['code' => ErrorCode::SUCCESS, 'msg' => '', 'data' => []];
            }
            $articleInfo = $articleInfo->toArray();
            $articleList[] = $articleInfo;
        }
        return ['code' => ErrorCode::SUCCESS, 'msg' => '', 'data' => $articleList];
    }

    /**
     * 文章详情
     * @param int $id
     * @return array
     */
    public function articleDetail(int $id): array
    {
        $articleInfo = Article::query()->select(['id','article_theme_id','title','content','type'])->where(['id'=>$id])->first();
        if(empty($articleInfo)){
            return ['code' => ErrorCode::WARNING, 'msg' => '数据错误', 'data' => null];
        }
        $articleInfo = $articleInfo->toArray();
        return ['code' => ErrorCode::SUCCESS, 'msg' => '', 'data' => $articleInfo];
    }

    /**
     * 删除文章
     * @param int $id
     * @return array
     */
    public function deleteArticle(int $id): array
    {
        Article::query()->where(['id'=>$id])->delete();
        return ['code' => ErrorCode::SUCCESS, 'msg' => '', 'data' => ['id'=>$id]];
    }

    /**
     * 添加课程详情配置
     * @param array $params
     * @return array
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function addCourseDetailSetUp(array $params): array
    {
        $data = $params['list'];
        $insertCourseDetailSetUpData = [];
        foreach($data as $value){
            $courseDetailSetUpData = [];

            $vipCardInfo = VipCard::query()->select(['type','theme_type'])->where(['id'=>$value['vip_card_id']])->first();
            $vipCardInfo = $vipCardInfo->toArray();
            $courseDetailSetUpData['id'] = IdGenerator::generate();
            $courseDetailSetUpData['title'] = $value['title'];
            $courseDetailSetUpData['content'] = $value['content'];
            $courseDetailSetUpData['price'] = $value['price'];
            $courseDetailSetUpData['original_price'] = $value['original_price'];
            $courseDetailSetUpData['vip_card_id'] = $value['vip_card_id'];
            $courseDetailSetUpData['type'] = $vipCardInfo['type'];
            $courseDetailSetUpData['theme_type'] = $vipCardInfo['theme_type'];
            $insertCourseDetailSetUpData[] = $courseDetailSetUpData;
        }

        CourseDetailSetUp::query()->delete();
        if(!empty($insertCourseDetailSetUpData)){
            CourseDetailSetUp::query()->insert($insertCourseDetailSetUpData);
        }
        return ['code' => ErrorCode::SUCCESS, 'msg' => '', 'data' => null];
    }

    /**
     * 课程详情配置列表
     * @return array
     */
    public function courseDetailSetUpList(): array
    {
        $courseDetailSetUpList = CourseDetailSetUp::query()
            ->select(['title','content','price','original_price','vip_card_id'])
            ->get();
        $courseDetailSetUpList = $courseDetailSetUpList->toArray();

        return ['code' => ErrorCode::SUCCESS, 'msg' => '', 'data' => $courseDetailSetUpList];
    }

}