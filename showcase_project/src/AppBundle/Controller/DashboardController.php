<?php
namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Entity\User;
use AppBundle\Entity\Comment;
use Symfony\Component\HttpFoundation\Session\Session;
use Facebook\Facebook;
use Facebook\FacebookRequest;
use Facebook\FacebookResponse;
use Facebook\Exceptions\FacebookSDKException;
use Facebook\Exceptions\FacebookAuthorizationException;
use Facebook\GraphNodes\GraphObject;

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
        if (!($this->session->get('username'))):
            return $this->redirectToRoute('app_dashboard_dashboard');
        endif;
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
            $comment->setCreated(time());
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
                'user_id' => $this->session->get("user_id"),
                'avatar_src' => $this->session->get("avatar_src"),
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
        $target_dir = $this->get('kernel')->getRootDir() . '/..' . "/web/uploads/";
        $target_file = $target_dir . basename($_FILES["file"]["name"]);
        $imageFileType = pathinfo($target_file, PATHINFO_EXTENSION);
        if ($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg"
            && $imageFileType != "gif"
        ) {
            die();
        }
        if (move_uploaded_file($_FILES["file"]["tmp_name"], $target_file)) {
            return new Response($_FILES["file"]["name"]);
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
                    $user->setAvatarSrc($request->request->get('avatar_src'));
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

                $user_id = $this->getUserIdFor($this->session->get('username'));
                $this->session->set('user_id', $user_id['id']);

                $avatar_src = $this->getAvatarSrcFor($this->session->get('user_id'));
                $this->session->set('avatar_src', $avatar_src['avatar_src']);


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

    public function getUserIdFor($username)
    {
        $repository = $this->getDoctrine()->getRepository('AppBundle:User');
        $query = $repository->createQueryBuilder('p')
            ->select('p.id')
            ->where('p.username = :username')
            ->setParameter('username', $username)->getQuery();
        return $query->getSingleResult();

    }

    public function getAvatarSrcFor($user_id)
    {
        $repository = $this->getDoctrine()->getRepository('AppBundle:User');
        $query = $repository->createQueryBuilder('p')
            ->select('p.avatar_src')
            ->where('p.id = :id')
            ->setParameter('id', $user_id)->getQuery();
        return $query->getSingleResult();

    }

    /**
     * @Route("/logincallback")
     */
    public function loginCallbackAction()
    {
        $fb = new Facebook([
            'app_id' => '763157317118688',
            'app_secret' => '41ed5213e2e9161f8f31bf77c5e5c9e3',
            'default_graph_version' => 'v2.5'
        ]);

        $helper = $fb->getJavaScriptHelper();

        try {
            $accessToken = $helper->getAccessToken();
        } catch (FacebookSDKException $e) {
            // When Graph returns an error
            echo 'Graph returned an error: ' . $e->getMessage();
        } catch (FacebookSDKException $e) {
            // When validation fails or other local issues
            echo 'Facebook SDK returned an error: ' . $e->getMessage();
        }

        if (isset($accessToken)) {
            $fb->setDefaultAccessToken($accessToken);
            try {
                $requestProfile = $fb->get("/me?fields=name,email");
                $profile = $requestProfile->getGraphNode()->asArray();
            } catch (FacebookSDKException $e) {
                // When Graph returns an error
                echo 'Graph returned an error: ' . $e->getMessage();
            } catch (FacebookSDKException $e) {
                // When validation fails or other local issues
                echo 'Facebook SDK returned an error: ' . $e->getMessage();
            }
            $this->session->set('username', $profile['name']);
            $this->session->set('avatar_src', "anonymous.png");
            return $this->redirectToRoute('app_dashboard_account');
            exit;
        } else {
            echo "Unauthorized access!!!";
            exit;
        }


    }
}