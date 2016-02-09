<?php
namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Entity\User;

class DashboardController extends Controller
{
    /**
     * @Route("/dashboard")
     */
    public function dashboardAction(Request $request)
    {
        $pass_var = "Vincent";
        if ($request->getMethod() == 'POST'):
            //register
            if ($request->request->get('submit_register')):
                if ($request->request->get('username') == "" || $request->request->get('password') == "" || $request->request->get('email') == ""):
                    $error_msg = "fields can't be blank";
                    $html = $this->container->get('templating')->render(
                        'dashboard/dashboard.html.twig',
                        array('pass_name' => $pass_var,
                            'error_register' => $error_msg)
                    );
                    return new Response($html);
                else:
                    $user = new User();
                    $user->setUsername($request->request->get('username'));
                    $user->setHash($this->makeHash($request->request->get('username'), $request->request->get('password')));
                    $user->setEmail($request->request->get('email'));
                    $user->setActivated(0);
                    $user->setAvatarId(1);
                    $em = $this->getDoctrine()->getManager();
                    $em->persist($user);
                    $em->flush();
                endif; //submit register
            endif;
        endif; // is empty check
        //login
        if ($request->request->get('submit_login')):
            $user = new User();

            if ($this->checkCredentials($request->request->get('username'), $request->request->get('password')) == false):
                $error_msg = "wrong password";
                $html = $this->container->get('templating')->render(
                    'dashboard/dashboard.html.twig',
                    array('pass_name' => $pass_var,
                        'error' => $error_msg)
                );
                return new Response($html);
            else:
                echo("right password - redirect control panel");
            endif;
        endif;

        $html = $this->container->get('templating')->render(
            'dashboard/dashboard.html.twig',
            array('pass_name' => $pass_var)
        );
        return new Response($html);
    }

    public function makeHash($username, $password)
    {
        return sha1(strtoupper($username . ":" . $password . "2154541%&8")); //random
    }

    public function checkCredentials($inputUsername, $inputPassword)
    {
        $account = new User();
        $repository = $this->getDoctrine()->getRepository('AppBundle:User');
        $query = $repository->createQueryBuilder('t')
            ->select('count(t.id)')
            ->where('t.username = :username', 't.hash = :password')
            ->setParameters(['username' => $inputUsername, 'password' => $this->makeHash($inputUsername, $inputPassword)])
            ->getQuery();

        $result = $query->getSingleScalarResult();
        if ($result == 1):
            return true; // loggedin
        else:
            return false; // wrong cred
        endif;
    }
}