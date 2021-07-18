<?php
include_once LIB_PATH."Controller.php";
include_once LIB_PATH."Html.php";

/**
 * Controller: AppCriptoController
 * @package SGi_Controllers
 */
class CriptoController extends Controller
{
    function home($auth)
    {
        $this->addTitle('Home');

        //if (!$auth->checkCsu('sgi.'))
        //{
        //     $this->adderror('No esta autorizado a visualizar esta pagina.');
        //     return null;
        //}

   
        $arr['data'] = 'HOME';
        $arr['hidden'] = '';
   
        $this->addView('ver',$arr);
    }
}
