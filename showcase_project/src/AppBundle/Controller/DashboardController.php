<?php
namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Entity\User;
use Symfony\Component\HttpFoundation\Session\Session;

class DashboardController extends Controller
{
    public function __construct()
    {

    }

    /**
     * @Route("/logout")
     */
    public function logoutAction()
    {
        $session = new Session();
        $session->invalidate();
        return $this->redirectToRoute('app_dashboard_dashboard');

    }
    /**
     * @Route("/account")
     */
    public function accountAction()
    {  $session = new Session();
        $html = $this->container->get('templating')->render(
            'dashboard/account.html.twig',
            array(
                'username' => $session->get("username")
            )
        );
        return new Response($html);
    }

    /**
     * @Route("/dashboard")
     */
    public function dashboardAction(Request $request)
    {
        $session = new Session();
        if ($session->get('username')):
            return $this->redirectToRoute('app_dashboard_account');
        endif;
        if ($request->getMethod() == 'POST'):
            //register
            if ($request->request->get('submit_register')):
                if ($request->request->get('username_register') == "" || $request->request->get('password_register') == "" || $request->request->get('email_register') == ""):
                    $error_msg = "fields can't be blank";
                    $html = $this->container->get('templating')->render(
                        'dashboard/dashboard.html.twig',
                        array('error_register' => $error_msg)
                    );
                    return new Response($html);
                else:
                    $user = new User();
                    $user->setUsername($request->request->get('username_register'));
                    $user->setHash($this->makeHash($request->request->get('username_register'), $request->request->get('password_register')));
                    $user->setEmail($request->request->get('email_register'));
                    $user->setActivated(0);
                    $user->setAvatarId(1);
                    $em = $this->getDoctrine()->getManager();
                    $em->persist($user);
                    $em->flush();
                    $succes_msg = "Bedankt! Uw account is aangemaakt. Je kan nu inloggen met uw nieuw account.";
                    $html = $this->container->get('templating')->render(
                        'dashboard/dashboard.html.twig',
                        array('succes' => $succes_msg)
                    );
                    return new Response($html);
                endif; //submit register
            endif;
        endif; // is empty check
        //login
        if ($request->request->get('submit_login')):
            if ($this->checkCredentials($request->request->get('username'), $request->request->get('password')) == false):
                $error_msg = "wrong password";
                $html = $this->container->get('templating')->render(
                    'dashboard/dashboard.html.twig',
                    array(
                        'error' => $error_msg)
                );
                return new Response($html);
            else:

                $session->set('username', $request->request->get('username'));
                return $this->redirectToRoute('app_dashboard_account');
            endif;
        endif;

        $html = $this->container->get('templating')->render(
            'dashboard/dashboard.html.twig',
            array()
        );
        return new Response($html);
    }

    public function makeHash($username, $password)
    {
        return sha1(strtoupper($username . ":" . $password . "2154541%&8")); //random
    }

    public function checkCredentials($inputUsername, $inputPassword)
    {
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