<?php
namespace app\controllers\home;

use app\models\Account;
use Yii;
use yii\helpers\Url;
use yii\web\Response;
use app\models\Project;
use app\models\Template;
use app\models\Member;
use app\models\project\CreateProject;
use app\models\project\UpdateProject;
use app\models\project\QuitProject;
use app\models\project\TransferProject;
use app\models\project\DeleteProject;

class ProjectController extends PublicController
{

    public $checkLogin = false;

    /**
     * 选择项目
     * @return string
     */
    public function actionSelect()
    {

        if(Yii::$app->user->isGuest) {
            return $this->redirect(['home/account/login','callback' => Url::current()]);
        }

        $account = Yii::$app->user->identity;

        return $this->display('select', ['account' => $account]);

    }

    /**
     * 搜索项目
     * @return string
     */
    public function actionSearch()
    {

        $params = Yii::$app->request->queryParams;

        $params['status'] = Project::ACTIVE_STATUS;
        $params['type']   = Project::PUBLIC_TYPE;

        $model  = Project::findModel()->search($params);

        return $this->display('search', ['model' => $model]);

    }

    /**
     * 添加项目
     * @return string
     */
    public function actionCreate()
    {

        $request = Yii::$app->request;

        if(Yii::$app->user->isGuest) {
            return $this->redirect(['home/account/login']);
        }

        $model = CreateProject::findModel();

        if($request->isPost) {

            Yii::$app->response->format = Response::FORMAT_JSON;

            if(!$model->load($request->post())) {
                return ['status' => 'error', 'message' => '加载数据失败'];
            }

            if($model->store()) {

                return ['status' => 'success', 'message' => '添加成功'];

            }

            return ['status' => 'error', 'message' => $model->getErrorMessage(), 'label' => $model->getErrorLabel()];

        }

        return $this->display('create', ['project' => $model]);

    }

    /**
     * 编辑项目
     * @param $id
     * @return array|string
     */
    public function actionUpdate($id)
    {

        $request = Yii::$app->request;

        if(Yii::$app->user->isGuest) {
            return $this->redirect(['home/account/login']);
        }

        $model = UpdateProject::findModel(['encode_id' => $id]);

        if($request->isPost) {

            Yii::$app->response->format = Response::FORMAT_JSON;

            if(!$model->load($request->post())) {

                return ['status' => 'error', 'message' => '加载数据失败'];
            }

            if($model->store()) {

                return ['status' => 'success', 'message' => '编辑成功'];

            }

            return ['status' => 'error', 'message' => $model->getErrorMessage(), 'label' => $model->getErrorLabel()];

        }

        return $this->display('update', ['project' => $model]);

    }

    /**
     * 项目详情
     * @param $token
     * @param string $tab
     * @return string
     */
    public function actionShow($id, $tab = 'home')
    {
        $project = Project::findModel(['encode_id' => $id]);

        if($project->isPrivate()) {

            if(Yii::$app->user->isGuest) {
                return $this->redirect(['home/account/login','callback' => Url::current()]);
            }

            if(!$project->hasRule('project', 'look')) {
                return $this->error('抱歉，您无权查看');
            }
        }

        $params['project_id'] = $project->id;

        $data['project'] = $project;

        switch ($tab) {
            case 'home':

                $view  = '/home/project/home';

                break;

            case 'template':

                if(!$project->hasRule('template', 'look'))
                {
                    return $this->error('抱歉，您无权查看');
                }

                $data['template'] = Template::findModel(['project_id' => $project->id]);

                $view  = '/home/template/home';

                break;

            case 'env':

                $view = '/home/env/index';

                break;

            case 'member':

                if(!$project->hasRule('member', 'look'))
                {
                    return $this->error('抱歉，您无权查看');
                }

                $data['member'] = Member::findModel()->search($params);

                $view  = '/home/member/index';

                break;
        }

        return $this->display($view, $data);

    }

    /**
     * 转让项目
     * @return string
     */
    public function actionTransfer($id)
    {

        $request = Yii::$app->request;

        $model   = TransferProject::findModel(['encode_id' => $id]);

        if($request->isPost) {

            Yii::$app->response->format = Response::FORMAT_JSON;

            if(!$model->load($request->post())) {

                return ['status' => 'error', 'message' => '加载数据失败'];

            }

            if ($model->transfer()) {

                return ['status' => 'success', 'message' => '转让成功'];

            }

            return ['status' => 'error', 'message' => $model->getErrorMessage(), 'label' => $model->getErrorLabel()];

        }

        return $this->display('transfer', ['project' => $model]);

    }
    
    public function actionExport($id)
    {
        $project = Project::findModel(['encode_id' => $id]);

        $file_name = $project->title . '接口离线文档.html';

//        header ("Content-Type: application/force-download");
//        header ("Content-Disposition: attachment;filename=$file_name");

        return $this->display('export', ['project' => $project]);

    }

    /**
     * 删除项目
     * @param $id
     * @return array|string
     */
    public function actionDelete($id)
    {

        $request = Yii::$app->request;

        $model = DeleteProject::findModel(['encode_id' => $id]);

        if($request->isPost) {

            Yii::$app->response->format = Response::FORMAT_JSON;

            if(!$model->load($request->post())) {

                return ['status' => 'error', 'message' => '数据加载失败'];

            }

            if($model->delete()) {

                return ['status' => 'success', 'message' => '删除成功'];

            }

            return ['status' => 'error', 'message' => $model->getErrorMessage(), 'label' => $model->getErrorLabel()];

        }

        return $this->display('delete', ['project' => $model]);

    }

    /**
     * 退出项目
     * @param $id
     * @return array|string
     */
    public function actionQuit($id)
    {

        $request = Yii::$app->request;

        $model = QuitProject::findModel(['encode_id' => $id]);

        $member = Member::findModel(['project_id' => $model->id, 'user_id' => Yii::$app->user->identity->id]);

        if($request->isPost) {

            Yii::$app->response->format = Response::FORMAT_JSON;

            if(!$model->load($request->post())) {

                return ['status' => 'error', 'message' => '数据加载失败'];

            }

            if($model->quit()) {

                return ['status' => 'success', 'message' => '退出成功'];

            }

            return ['status' => 'error', 'message' => $model->getErrorMessage(), 'label' => $model->getErrorLabel()];

        }

        return $this->display('quit', ['project' => $model, 'member' => $member]);

    }
}