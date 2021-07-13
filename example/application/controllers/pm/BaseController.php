<?php


use kirillemko\yci\models\request\InvalidRequestException;

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * @property Twig $twig
 * @property CI_Lang $lang
 * @property CI_Output $output
 * @property CI_Security $security
 *
 * @property Messages_model $messages_model
 */
class BaseController extends MY_Controller
{
    protected $accessPermissions = [];

    public function __construct()
    {
        ini_set('display_errors', 1);
        ini_set('display_startup_errors', 1);
        error_reporting(E_ALL);


        parent::__construct();

        $this->loadYiiCore();

//        set_error_handler(array(&$this, 'error_handler'));
        set_exception_handler(array(&$this, 'exception_handler'));

        $this->loadLMSConstructor();

        $this->data['pm_version'] = 1.1;
//        $this->checkAccessRules();
    }

//    public function error_handler($number, $message, $file, $line, $vars){}
    public function exception_handler($e)
    {
        if( $e instanceof InvalidRequestException ){
            $this->return_json_error($e->getErrors());
            exit();
        }
        throw $e;
    }






    protected function getAuthUser()
    {
        return $this->data['user'];
    }


    protected function action_not_found(){
        $this->output->set_status_header('404');
        exit();
    }
    protected function action_not_allowed($comment=null){
        $this->output->set_status_header('403');
        exit();
    }

    protected function renderView($viewName){

//        $roleText = '';
        $roleText = 'admin';
//        if( $this->isAuthStudentRole() ) $roleText = 'student';
//        if( $this->isAuthTeacherRole() ) $roleText = 'teacher';
//        if( $this->isAuthAdminRole() ) $roleText = 'admin';

        $this->twig->display('pm/' . $viewName, [
            'sidebar' => $roleText,
            'data'    => $this->data
        ]);
    }













    protected function checkAccessRules(){

//        $authUser = $this->getAuthUser();
//        if( !$authUser ){
//            $this->action_not_allowed();
//        }
//
//        $methodName = $this->router->fetch_method();
//        if( !array_key_exists($methodName, $this->accessRules) ){
//            $this->action_not_allowed();
//        }
//
//        if( in_array( self::ROLE_ADMIN, $this->accessRules[$methodName] ) && $this->isAuthAdminRole() ){
//            return;
//        }
//        if( in_array( self::ROLE_USER, $this->accessRules[$methodName] ) && $this->isAuthUserRole() ){
//            return;
//        }
//
//        $this->action_not_allowed();
    }


    protected function input_json_to_array(){
        return json_decode($this->security->xss_clean($this->input->raw_input_stream), true);
    }

    protected function return_json_success($data=[]){
        $toReturn = [
            'success' => 1,
            'data' => $data
        ];
        $this->return_json($toReturn);
    }
    protected function return_json_error($errors=[], $code=200){
        $this->output->set_status_header($code);
        $toReturn = [
            'success' => 0,
            'errors' => []
        ];
        if( is_array($errors) ){
            $toReturn['errors'] = $errors;
        } else {
            $toReturn['errors'][] = $errors;
        }

        $this->return_json($toReturn);
    }

    protected function return_json($toReturn)
    {
        ob_clean();
        $this->output->set_content_type('application/json');
//        echo json_encode($toReturn, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE);
//        $this->output->final_output = yii\helpers\BaseJson::encode($toReturn, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE);
        echo \yii\helpers\BaseJson::encode($toReturn, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE);
        exit();
    }







    private function loadLMSConstructor()
    {
        $this->load->model(['messages_model']);
//        $this->lang->load($this->session->userdata('lang_short_name'), $this->session->userdata('lang_name'));
        $this->load->helper(['tpl_render', 'tpl_render_helper', 'text', 'declination_helper']);

        $this->data['user'] = $this->main_model->get_user($this->ion_auth->get_user_id());
//        $this->main_model->user_action($this->data['user']['id']);
//
//        $this->data['unseen'] = $this->_generateHtmlNoficationDialog($this->data['user']['id']);
//        $this->data['unseen_notifications'] = $this->_generateHtmlNofications($this->data['user']['id']);
        //TWIG
        $config = ['functions' => ['lang', 'time_ago', 'time_ago_new'], 'functions_safe' => ['lang', 'time_ago', 'time_ago_new']];
        $this->load->library('twig', $config);
        $this->twig->addGlobal('user', $this->data['user']);
        $this->data['req_process'] = 'moscow';

        /* Получаем массив непрочитанные уведомления и сообщения для новго шаблона */
//        $this->twig->addGlobal('twig_tpl_unseen', $this->_getMassages($this->data['user']['id']));
//        $this->twig->addGlobal('twig_tpl_unseen_notifications', $this->_getNofications($this->data['user']['id']));
//        $this->twig->addGlobal('twig_tpl_link', 'teacher');
    }

    private function _generateHtmlNofications($id)
    {
        $data['unssenNotification'] = $this->messages_model->get_unseen_notification($id);
        $data['link'] = 'teacher';
        return $this->load->view('/teacher/dialogs/header_notifications', $data, true);
    }

    private function _generateHtmlNoficationDialog($id)
    {
        $data['unseen'] = $this->messages_model->get_unssen_messages($id);
        $data['link'] = 'teacher';
        return $this->load->view('/teacher/dialogs/header_notification', $data, true);
    }


    public function loadYiiCore()
    {
        defined('YII_DEBUG') or define('YII_DEBUG', true);
        defined('YII_ENV') or define('YII_ENV', 'dev');
        require_once(APPPATH . '../vendor/yiisoft/yii2/Yii.php');
        $yiiConfig = require APPPATH . 'config/yii_config.php';
        new yii\web\Application($yiiConfig); // Do NOT call run() here
    }

}
