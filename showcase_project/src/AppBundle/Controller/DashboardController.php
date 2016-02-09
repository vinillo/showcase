<?php
namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;


class DashboardController extends Controller
{
    /**
     * @Route("/dashboard")
     */
    public function dashboardAction(Request $request)
    {
        if($request->getMethod()=='POST')
        {

            echo "helu";
        }
        $pass_var = "Vincent";
        $html = $this->container->get('templating')->render(
            'dashboard/dashboard.html.twig',
            array('pass_name' => $pass_var)
        );
        return new Response($html);
    }
}