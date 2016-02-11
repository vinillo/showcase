<?php
namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Entity\User;
use AppBundle\Entity\Comment;
use Symfony\Component\HttpFoundation\Session\Session;

class DashboardController extends Controller
{
    private $session;

    public function __construct()
    {
        $this->session = new Session();
    }

    /**
     * @Route("/comment_delete/{id}")
     */
    public function comment_deleteAction($id)
    {

        $em = $this->getDoctrine()->getEntityManager();
        $comment = $em->getRepository('AppBundle:Comment')->find($id);
        $em->remove($comment);
        $em->flush();
        return $this->redirectToRoute('app_dashboard_account');
    }

    /**
     * @Route("/comment_delete_all")
     */
    public function comment_delete_allAction()
    {

        $em = $this->getDoctrine()->getEntityManager();
        $query = $em->createQuery('DELETE AppBundle:Comment');
        $query->execute();
        return $this->redirectToRoute('app_dashboard_account');
    }

    /**
     * @Route("/logout")
     */
    public function logoutAction()
    {

        $this->session->invalidate();
        return $this->redirectToRoute('app_dashboard_dashboard');
    }

    /**
     * @Route("/account")
     */
    public function accountAction(Request $request)
    {

        $repository = $this->getDoctrine()->getRepository('AppBundle:Comment');
        $query = $repository->createQueryBuilder('t')
            ->orderBy('t.id', 'DESC')
            ->setMaxResults(5)
            ->getQuery();

        $result = $query->getResult();
        $comment_data = $result;

        if ($request->getMethod() == 'POST'):
            $comment = new comment();
            $comment->setTitle($request->request->get('title_comment'));
            $comment->setBody($request->request->get('comment'));
            $comment->setCreated(1);
            $comment->setDisplay(1);
            $comment->setLastEdited(1);
            $comment->setOwner(1);
            $em = $this->getDoctrine()->getManager();
            $em->persist($comment);
            $em->flush();
            return $this->redirectToRoute('app_dashboard_account');
        endif;
        $no_comments = $this->CheckCommentsExist();

        $html = $this->container->get('templating')->render(
            'dashboard/account.html.twig',
            array(
                'username' => $this->session->get("username"),
                'comment_data' => $comment_data,
                'comment_exist' => $no_comments
            )
        );
        return new Response($html);
    }
    /**
     * @Route("/dashboard/upload_avatar")
     */
    public function uploadProfileAction(Request $request)
    {
        $target_dir = $this->get('kernel')->getRootDir()."/uploads/";
        $target_file = $target_dir . basename($_FILES["file"]["name"]);
        $imageFileType = pathinfo($target_file,PATHINFO_EXTENSION);
        if($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg"
            && $imageFileType != "gif" ) {
             die();
        }
            if (move_uploaded_file($_FILES["file"]["tmp_name"], $target_file)) {
                return new Response();
            } else {
                die();
            }
    }
    /**
     * @Route("/dashboard")
     */
    public function dashboardAction(Request $request)
    {

        if ($this->session->get('username')):
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

                $this->session->set('username', $request->request->get('username'));
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

    public function CheckCommentsExist()
    {
        $repository = $this->getDoctrine()->getRepository('AppBundle:Comment');
        $query = $repository->createQueryBuilder('t')
            ->select('count(t.id)')
            ->getQuery();

        $result = $query->getSingleScalarResult();
        return $result;
    }
}