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
     * @Route("/account/{action}/{id}")
     */
    public function accountAction(Request $request, $action = 'get_app', $id = '1')
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
        if($this->session->get("fb_login") == true):
            $fb_bool = true;
        else:
            $fb_bool = "";
        endif;
        if($this->session->get("fb_realm_name")):
            $fb_name = $this->session->get("fb_realm_name");
        else:
            $fb_name  = $this->session->get("email");
        endif;


        if ($request->getMethod() == 'POST' && $request->get('comment_submit')):
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
        if (strpos($this->session->get("avatar_src"), 'data:image/') !== false):
            $avatar_data_img = $this->session->get("avatar_src");
        else:
            $avatar_data_img = "";
        endif;
        if (isset($action) && isset($id)):
            //action=get_app&id=' . $id
            $app_info = file_get_contents('http://localhost/hello/itsme.php');
            $app_info = json_decode($app_info, true);
            $html = $this->container->get('templating')->render(
                'dashboard/account.html.twig',
                array(
                    'username' => $this->session->get("username"),
                    'user_id' => $this->session->get("user_id"),
                    'avatar_src' => $this->session->get("avatar_src"),
                    'avatar_data_img' => $avatar_data_img,
                    'email' => $this->session->get("email"),
                    'created' => $this->session->get("created"),
                    'fb_bool' => $fb_bool,
                    'fb_real_name' => $fb_name,
                    'comment_data' => $comment_data,
                    'comment_exist' => $no_comments,
                    'tag_title' => $app_info['title'],
                    'tag_body' => $app_info['body'],
                    'tag_created_by' => $app_info['created_by'],
                )
            );
            return new Response($html);
        else:
            $html = $this->container->get('templating')->render(
                'dashboard/account.html.twig',
                array(
                    'username' => $this->session->get("username"),
                    'user_id' => $this->session->get("user_id"),
                    'avatar_src' => $this->session->get("avatar_src"),
                    'email' => $this->session->get("email"),
                    'created' => $this->session->get("created"),
                    'fb_bool' => $fb_bool,
                    'fb_real_name' => $fb_name,
                    'avatar_data_img' => $avatar_data_img,
                    'comment_data' => $comment_data,
                    'comment_exist' => $no_comments
                )
            );
            return new Response($html);
        endif;
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
                    $user->setActivated(1);
                    $user->setAvatarSrc($request->request->get('avatar_src'));
                    $user->setCreated(time());
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
                $user_id = $this->getUserResultFor($this->session->get('username'))[0]['id'];
                $this->session->set('user_id', $user_id['id']);
                $avatar_src = $this->getUserResultFor($this->session->get('username'))[0]['avatar_src'];
                $this->session->set('avatar_src', $avatar_src);
                $email = $this->getUserResultFor($this->session->get('username'))[0]['email'];
                $this->session->set('email',  $email);
                $created = $this->getUserResultFor($this->session->get('username'))[0]['created'];
                $this->session->set('created', $created);


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


    public function getUserResultFor($username)
    {

        $repository = $this->getDoctrine()->getRepository('AppBundle:User');
        $query = $repository->createQueryBuilder('p')
            ->select('p.id,p.username,p.hash,p.email,p.activated,p.avatar_src,p.created')
            ->where('p.username = :username')
            ->setParameters(['username' => $username])
            ->orderBy('p.id', 'ASC')
            ->setMaxResults(1)
            ->getQuery();

        return $query->getResult();

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
                $requestProfile = $fb->get("/me?fields=id,name,email");
                $profile = $requestProfile->getGraphNode()->asArray();
            } catch (FacebookSDKException $e) {
                // When Graph returns an error
                echo 'Graph returned an error: ' . $e->getMessage();
            } catch (FacebookSDKException $e) {
                // When validation fails or other local issues
                echo 'Facebook SDK returned an error: ' . $e->getMessage();
            }
            $this->session->set('username', $profile['name']);
            $this->session->set('email', $profile['email']);
            if (!$this->checkFbIdExistinUserFor($profile['id'].'_fb')):
                $user = new User();
                $user->setUsername($profile['id'] . "_fb");
                $user->setHash($this->makeHash($profile['name'], $profile['email']));
                $user->setEmail($profile['email']);
                $user->setActivated(1);
                $user->setAvatarSrc($profile['id']);
                $user->setCreated(time());
                $em = $this->getDoctrine()->getManager();
                $em->persist($user);
                $em->flush();
            endif;

            $fb_user_id = $this->getUserResultFor($profile['id'] . "_fb")[0]['id'];
            $fb_username = $this->getUserResultFor($profile['id'] . "_fb")[0]['username'];
            $fb_created = $this->getUserResultFor($profile['id'] . "_fb")[0]['created'];
            $fb_avatar_src = $this->getUserResultFor($profile['id'] . "_fb")[0]['avatar_src'];
            $fb_email = $this->getUserResultFor($profile['id'] . "_fb")[0]['email'];

            $this->session->set('username', $fb_username);
            $this->session->set('fb_realm_name', $profile['name']);
            $this->session->set('user_id', $fb_user_id);
            $this->session->set('avatar_src',  $fb_avatar_src);
            $this->session->set('email',  $fb_email);
            $this->session->set('created', $fb_created);
            $this->session->set('fb_login', true);
            return $this->redirectToRoute('app_dashboard_account');
        } else {
            echo "Unauthorized access!!!";
            exit;
        }
    }

    public function checkFbIdExistinUserFor($fbid)
    {
        $repository = $this->getDoctrine()->getRepository('AppBundle:User');
        $query = $repository->createQueryBuilder('t')
            ->select('count(t.username)')
            ->where('t.username = :username')
            ->setParameters(['username' => $fbid])
            ->setMaxResults(1)
            ->getQuery();
        $result = $query->getSingleScalarResult();
        if ($result >= 1):
            return true;
        else:
            return false; //no email found make account
        endif;
    }
}