<?php 
namespace App\Controller;

use App\Controller\AppController;
use Cake\Event\Event;
use Cake\ORM\TableRegistry;
use Cake\Auth\DefaultPasswordHasher;
use Cake\Datasource\ConnectionManager;
use Cake\Mailer\Email;
use Cake\Routing\Router;
class UsersController extends AppController
{

    public function initialize()
    {
        parent::initialize();
        $this->loadComponent('Auth', [
            'loginRedirect' => [
                'controller' => 'users',
                'action' => 'dashboard'
            ],
            'logoutRedirect' => [
                'controller' => 'users',
                'action' => 'login'
            ],
        ]);

    }

    public function beforeFilter(Event $event)
	{
	    parent::beforeFilter($event);
	    // Allow users to register and logout.
	    // You should not add the "login" action to allow list. Doing so would
	    // cause problems with normal functioning of AuthComponent.

	    $this->Auth->allow(['index', 'add', 'logout', 'forgotPassword', 'resetPassword','view', 'verify']);

        if($this->Auth->user())
        {
            $this->viewBuilder()->layout('inner_layout');
            $this->set('loggedInUser', $this->Auth->user());
        }
        
	}

    

    public function dashboard()
    {

    }

    public function index($st=0)
	{
	    
		if($this->Auth->user())
            $this->redirect($this->Auth->redirectUrl());

        $this->viewBuilder()->layout('admin_login');

        if(isset($this->request->query['st']) && $this->request->query['st'] == 1)
            $this->Flash->error(__('Invalid App or Subdomain!!'));

        if(isset($this->request->query['st']) && $this->request->query['st'] == 2)
            $this->Flash->error(__('Your session has timed out!!'));

        if(isset($this->request->query['id']))
            $this->request->data['username'] = base64_decode($this->request->query['id']);

	    if ($this->request->is('post') && $this->request->data['username'] && $this->request->data['password']) {

            $user = $this->Auth->identify();

	        if ($user) {
                if($user['userrole'] == 'admin' || ($user['userrole'] == 'company' && $user['status'] == 2))
                {
                   $this->Auth->setUser($user);
                    return $this->redirect($this->Auth->redirectUrl()); 
                }
	            //$this->Flash->error(__('Your account is under admin approval'));
                $this->set('error_msg', 'Your account is under admin approval');
	        }
            else
            {
                $this->User = TableRegistry::get('users');
                $user = $this->User->find("all", ["conditions" => ["email" => $this->request->data['username'], "userrole !=" => "user"]])->first();

               if (count($user)) {
                    $this->request->data['username'] = $user->username;
                   // $user = $this->Auth->identify();
                  //  print_r($user);exit;
                    if($user['userrole'] == 'admin' || ($user['userrole'] == 'company' && $user['status'] == 2))
                    {
                        $this->Auth->setUser($user);
                        return $this->redirect($this->Auth->redirectUrl());
                    }
                    //$this->Flash->error(__('Your account is under admin approval'));
                    $this->set('error_msg', 'Your account is under admin approval');
                }
                else
    	        {
                    //$this->Flash->error(__('Invalid username or password, try again'));
                    $this->set('error_msg', 'Wrong credentials.Please try again.');
                }   
            }
	    }
	}

    public function login($st=0)
    {
        
        if($this->Auth->user())
            $this->redirect($this->Auth->redirectUrl());

        $this->viewBuilder()->layout('admin_login');

        if(isset($this->request->query['st']) && $this->request->query['st'] == 1)
            $this->Flash->error(__('Invalid App or Subdomain!!'));

        if(isset($this->request->query['st']) && $this->request->query['st'] == 2)
            $this->Flash->error(__('Your session has timed out!!'));

        if(isset($this->request->query['id']))
            $this->request->data['username'] = base64_decode($this->request->query['id']);

        if ($this->request->is('post') && $this->request->data['username'] && $this->request->data['password']) {

            $user = $this->Auth->identify();

            if ($user) {
                if($user['userrole'] == 'admin' || ($user['userrole'] == 'company' && $user['status'] == 2))
                {
                   $this->Auth->setUser($user);
                    return $this->redirect($this->Auth->redirectUrl()); 
                }
                //$this->Flash->error(__('Your account is under admin approval'));
                $this->set('error_msg', 'Your account is under admin approval');
            }
            else
            {
                $this->User = TableRegistry::get('users');
                $user = $this->User->find("all", ["conditions" => ["email" => $this->request->data['username'], "userrole !=" => "user"]])->first();

               if (count($user)) {
                    $this->request->data['username'] = $user->username;
                   // $user = $this->Auth->identify();
                  //  print_r($user);exit;
                    if($user['userrole'] == 'admin' || ($user['userrole'] == 'company' && $user['status'] == 2))
                    {
                        $this->Auth->setUser($user);
                        return $this->redirect($this->Auth->redirectUrl());
                    }
                    //$this->Flash->error(__('Your account is under admin approval'));
                    $this->set('error_msg', 'Your account is under admin approval');
                }
                else
                {
                    //$this->Flash->error(__('Invalid username or password, try again'));
                    $this->set('error_msg', 'Wrong credentials.Please try again.');
                }   
            }
        }
    }

	public function logout()
	{
	    $this->Auth->logout();
        return $this->redirect([
                'controller' => 'users',
                'action' => 'login'
            ]);
	}


    public function add()
    {
        $this->viewBuilder()->layout('admin_login');

        $user = $this->Users->newEntity();
        if ($this->request->is('post')) {
            $this->request->data['role'] = 'admin';
            $user = $this->Users->patchEntity($user, $this->request->data);
            $hasher = new DefaultPasswordHasher();
            $user->password = $hasher->hash($user->password);
            //pr($user);exit;
            if ($this->Users->save($user)) {
                $this->Flash->success(__('The user has been saved.'));
                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('Unable to add the user.'));
        }
        $this->set('user', $user);
    }


    public function verify($id)
    {
        $this->Users = TableRegistry::get('Users');
        $this->UserProfiles = TableRegistry::get('UserProfiles');

        $user_id = base64_decode(base64_decode($id));

        $user = $this->Users->get($user_id);
        $user_data = array('status' => 1);
        $user = $this->Users->patchEntity($user, $user_data);
        
        $res = $this->Users->save($user);

        $profile = $this->UserProfiles->find('all', ['conditions' => ['UserProfiles.user_id' => $user_id]])->first();
        $this->send_custom_mail(array(
                            'html_content' => $this->getEmailTemplate('new_account', 
                                            array('###SITEURL###' => Router::url('/', true),
                                                '###COMPANYNAME###' => $profile->company_name,
                                                '###USERNAME###' => $user->username,
                                                '###SUBDOMAIN###' => $profile->subdomain,
                                                '###LOGINLINK###' => Router::url('/', true).'?id='.base64_encode($user->email)
                                                )
                                            ),
                            'email_id' => array($user->email),
                            'subject' => 'Account Created successfully',
                            'apikey' => 'summa_token'
                            ));

        $this->send_custom_mail(array(
                            'html_content' => $this->getEmailTemplate('welcome', 
                                            array('###SITEURL###' => Router::url('/', true), 
                                                '###USERNAME###' => $user->username)),
                            'email_id' => array($user->email),
                            'subject' => 'Welcome to MyBuzztm',
                            'apikey' => 'summa_token'
                            ));

        $this->redirect(['action' => 'login']);
    }
    public function resendMail()
    {
        $this->autoRender = false;

        $data = $this->request->data;
        
        $this->Users = TableRegistry::get('Users');

        $user = $this->Users->find('all', ['conditions' => ['Users.email' => $this->request->data['email']]])->first();
           if($user)
           {
                    /*$email = new Email('default');
                    $email->from(['me@example.com' => 'Buzztm'])
                            ->to($data['email'])
                            ->subject('Mybuzztm Verify Email')
                            ->send('Please click the following link to verify your email . <a href="'.Router::url('/users/verify/'.base64_encode(base64_encode($user->id)), true).'">Click Here</a>');*/
                    $this->send_custom_mail(array(
                            'html_content' => $this->getEmailTemplate('email_verification', 
                                            array('###SITEURL###' => Router::url('/', true), 
                                                  '###VERIFYLINK###' => Router::url('/users/verify/'.base64_encode(base64_encode($user->id)), true))),
                            'email_id' => array($data['email']),
                            'subject' => 'Mybuzztm Verify Email',
                            'apikey' => 'summa_token'
                            ));
                            echo "success";
                }
                else
                             echo "error";
    }

    public function changePassword()
    {
       $this->Users = TableRegistry::get('Users');
       $this->viewBuilder()->layout('buzztm_admin');
       $user_det =$this->Users->get($this->Auth->user('id')); 
       if ($this->request->is('post')) {
            $hasher = new DefaultPasswordHasher();          
            if ($hasher->check($this->request->data['old_password'], $user_det['password'])) {
                $user = $this->Users->get($this->Auth->user('id'));
                $data['modified'] = date("Y-m-d H:i:s");  
                $data['password'] = $hasher->hash($this->request->data['new_password']);
                $profile = $this->Users->patchEntity($user, $data);
                $res = $this->Users->save($profile);
                if($res)
                     $this->Flash->success(__('Password has been updated successfully!!.'));
                else
                    echo 'error';
            } else {
                $this->Flash->error(__('Old Password not valid!!.'));
            }
        }
    }

    public function forgotPassword()
    {
        $this->Users = TableRegistry::get('Users');
        $this->viewBuilder()->layout('admin_login');
        if ($this->request->is('post')) {
           $user = $this->Users->find('all', ['conditions' => ['Users.email' => $this->request->data['email']]])->first();
           if($user)
           {
                $rlink = isset($this->request->data['slug']) ? Router::url('/users/reset-password/'.base64_encode(base64_encode($user->id)), true).'/'.$this->request->data['slug']: Router::url('/users/reset-password/'.base64_encode(base64_encode($user->id)), true);

                $this->UserProfiles = TableRegistry::get('UserProfiles');
                $profile = $this->UserProfiles->find('all', ['conditions' => ['UserProfiles.user_id' => $user->id]])->first();
                $this->send_custom_mail(array(
                            'html_content' => $this->getEmailTemplate('reset_password', 
                                        array('###SITEURL###' => Router::url('/', true), 
                                              '###RESETLINK###' => $rlink,
                                                '###CNAME###' => $profile->company_name)),
                            'email_id' => array($this->request->data['email']),
                            'subject' => 'Mybuzztm Reset password link',
                            'apikey' => 'summa_token'
                            ));
                if(!isset($this->request->data['slug']))
                    $this->Flash->success(__('Reset password link has been sent to your email address.'));
           }
           else
           {
                $this->Flash->success(__('Invalid email address.'));
           }
           exit;
        }
    }

    public function resetPassword($id=0, $redirectto = '')
    {
        if ($this->request->is('post')) {
            $this->Users = TableRegistry::get('Users');
            $user = $this->Users->get($this->request->data['user_id']);
            $hasher = new DefaultPasswordHasher();
            $password = $hasher->hash($this->request->data['password']);
            $user_data = array('password' => $password);
            $user = $this->Users->patchEntity($user, $user_data);
            $res = $this->Users->save($user);
            
            $this->send_custom_mail(array(
                            'html_content' => $this->getEmailTemplate('password_updated', 
                                            array('###SITEURL###' => Router::url('/', true))),
                            'email_id' => array($user->email),
                            'subject' => 'Password Updated',
                            'apikey' => 'summa_token'
                            ));

            $this->Flash->success(__('Password changed succesfully. Try login with your new password!!'));
            exit;
        }
        else
        {
            $this->viewBuilder()->layout('admin_login');
            $user_id = base64_decode(base64_decode($id));
            $redirectto = $redirectto ? explode("-",base64_decode(base64_decode($redirectto))) : false;
            $this->set(compact('user_id', 'redirectto'));
        }
        
    }

    public function reset()
    {
        $this->viewBuilder()->layout('admin_login');
    }

    public function random_password( $length = 8 ) {
        $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()_-=+;:,.?";
        $password = substr( str_shuffle( $chars ), 0, $length );
        return $password;
    }

    public function company($id = 0, $action = '')
    {
       $this->Users = TableRegistry::get('users');
       $this->UserDetails = TableRegistry::get('user_details');
       $siteurl =  Router::url('/', true);

       if($action == 'delete')
       {
            $this->UserDetails->deleteAll(['user_id' => $id]);
            $this->Users->delete($this->Users->get($id));
            $this->Flash->success('Company has been deleted successfully!!');
            $this->redirect(array("action" => 'company'));
       }
       elseif ($this->request->is('post') )
       {
            $data = $this->request->data;

            if(isset($data['id'])){
                $user = $this->Users->get($data['id']);
                $user = $this->Users->patchEntity($user, $this->request->data);
                $user_save  = $this->Users->save($user);
                if ($user_save) {
                    //echo  $data['id'];
                    $client = $this->UserDetails->find('all',['conditions' => ['user_details.user_id' => $data["id"]]])->first();
                    $client = $this->UserDetails->patchEntity($client, $this->request->data);
                    $client_save  = $this->UserDetails->save($client);
                    $this->Flash->success('Company Details has been updated successfully!!');
                    //$this->set('success_msg', 'Client Details has been updated successfully!!');
                }
            }
            else{
                $user = $this->Users->newEntity();
                $this->request->data['userrole'] = 'company';
                $this->request->data['status'] = 1;
                $user = $this->Users->patchEntity($user, $this->request->data);
                $hasher = new DefaultPasswordHasher();
                $user->password = $hasher->hash($user->password);
                $user_save  = $this->Users->save($user);
                //pr($user);exit;
                if ($user_save) {
                    $client = $this->UserDetails->newEntity();
                    $this->request->data['user_id'] = $user_save->id;
                    $client = $this->UserDetails->patchEntity($client, $this->request->data);
                    $client_save  = $this->UserDetails->save($client);
                    $this->Flash->success('New Company has been added successfully!!');
                    //$this->set('success_msg', 'New Client has been added successfully!!');

                }else
                $this->Flash->error('Unable to add Company!!');
                }

            $this->redirect(array("action" => 'company'));
       }
       elseif($id)
       {
            $client = $this->UserDetails->find('all', ['conditions' => ['users.id' => $id]])->contain('Users', function(\Cake\ORM\Query $q) {
                    return $q->where(['Users.id' => $id]);
                   })->first();
                $this->set('client', $client);
       }
       $users =  $this->UserDetails->find()->contain('Users', function(\Cake\ORM\Query $q) {
        return $q->where(['Users.id' => 'ClientDetails.User_id']);
       });
      // $users = $this->ClientDetails->find('all')->all()->contain('users');
      
       
       $this->set('users', $users);
       $this->set('siteurl', $siteurl);

    }

    public function projects($id = 0, $action = '')
    {
       $this->Users = TableRegistry::get('users');
       $this->UserDetails = TableRegistry::get('user_details');
       $this->Projects = TableRegistry::get('projects');
       $this->ProjectTimeline = TableRegistry::get('project_timeline');
       $siteurl =  Router::url('/', true);

       if($action == 'delete')
       {


            $this->ProjectTimeline->deleteAll(['project_id' => $id]);
            $this->Projects->delete($this->Projects->get($id));
            $this->Flash->success('Project has been deleted successfully!!');
            $this->redirect(array("action" => 'projects'));
       }
       elseif ($this->request->is('post') )
       {
            $data = $this->request->data;
            $login_id = $this->Auth->user('id');

            if(isset($data['id'])){
                $project = $this->Projects->get($data['id']);
                $project = $this->Projects->patchEntity($project, $this->request->data);
                $project_save  = $this->Projects->save($project);
                if ($project_save) {    
                        $project_timeline = $this->ProjectTimeline->newEntity();
                        $data1['project_id'] = $data['id'];
                        $data1['timeline_text']  = "Project Update";
                        $data1['timeline_description']  = "Project ".$this->request->data['project_name']." Details Updated!!";
                        $project_timeline = $this->ProjectTimeline->patchEntity($project_timeline, $data1);
                        $project_timeline_save  = $this->ProjectTimeline->save($project_timeline);
                    }
                $this->Flash->success('Project has been updated successfully!!');
            }
            else{
                    $project = $this->Projects->newEntity();
                    $this->request->data['status'] = "New";
                    $project = $this->Projects->patchEntity($project, $this->request->data);
                    $project_save  = $this->Projects->save($project);
                    if ($project_save) {    
                        $project_timeline = $this->ProjectTimeline->newEntity();
                        $data['project_id'] = $project_save->id;
                        $data['timeline_text'] = "Project";
                        $data['reference_id'] = $project_save->id;
                        $data['timeline_text']  = "New Project";
                        $data['timeline_description']  = "New Project ".$this->request->data['project_name']." Added!!";
                        $project_timeline = $this->ProjectTimeline->patchEntity($project_timeline, $data);
                        $project_timeline_save  = $this->ProjectTimeline->save($project_timeline);
                        $this->Flash->success('New Project has been added successfully!!');
                    }
                    else
                        $this->Flash->error('Unable to add Project!!');
                }

            $this->redirect(array("action" => 'projects'));
       }
       elseif($id)
       {
            $project = $this->Projects->get($id);
            $this->set('project', $project);
       }

       $projects =  $this->Projects->find('all');
       $clients =  $this->UserDetails->find('all');
      // $users = $this->ClientDetails->find('all')->all()->contain('users');
      
       
       $this->set('projects', $projects);
       $this->set('clients', $clients);
       $this->set('siteurl', $siteurl);

    }

    public function delete($id) {
            $this->ProjectDocuments = TableRegistry::get('project_documents');
            $siteurl =  Router::url('/', true);
            $doc = $this->ProjectDocuments->get($id);
            $file = $siteurl .'upload/project/'.$doc->document_name;
            if (is_file($file)) {
                            unlink($file);
                        }
            $this->ProjectDocuments->delete($this->ProjectDocuments->get($id));
           // $this->Flash->success('Document has been deleted successfully!!');
            exit;
            return true;
    }

    public function projectdetail($id = 0, $action = '')
    {
       $this->Users = TableRegistry::get('users');
       $this->ClientDetails = TableRegistry::get('user_details');
       $this->Projects = TableRegistry::get('projects');
       $this->ProjectTimeline = TableRegistry::get('project_timeline');
       $this->ProjectDocuments = TableRegistry::get('project_documents');
       $siteurl =  Router::url('/', true);

       if($action == 'delete')
       {
            $this->ProjectTimeline->deleteAll(['project_id' => $id]);
            $this->Projects->delete($this->Projects->get($id));
            $this->Flash->success('Project has been deleted successfully!!');
            $this->redirect(array("action" => 'projects'));
       }
       elseif ($this->request->is('post') )
       {
            $data = $this->request->data;
            $login_id = $this->Auth->user('id');

            if(isset($data['id'])){
                $project = $this->Projects->get($data['id']);
                $project = $this->Projects->patchEntity($project, $this->request->data);
                $project_save  = $this->Projects->save($project);
                if ($project_save) {    
                        $project_timeline = $this->ProjectTimeline->newEntity();
                        $data1['project_id'] = $data['id'];
                        $data1['timeline_text']  = "Project Update";
                        $data1['timeline_description']  = "Project ".$this->request->data['project_name']." Details Updated!!";
                        $project_timeline = $this->ProjectTimeline->patchEntity($project_timeline, $data1);
                        $project_timeline_save  = $this->ProjectTimeline->save($project_timeline);
                    }
                $this->Flash->success('Project has been updated successfully!!');
            }
            else{
                    $project = $this->Projects->newEntity();
                    $project = $this->Projects->patchEntity($project, $this->request->data);
                    $project_save  = $this->Projects->save($project);
                    if ($project_save) {    
                        $project_timeline = $this->ProjectTimeline->newEntity();
                        $data['project_id'] = $project_save->id;
                        $data['timeline_text']  = "New Project";
                        $data['timeline_description']  = "New Project ".$this->request->data['project_name']." Added!!";
                        $project_timeline = $this->ProjectTimeline->patchEntity($project_timeline, $data);
                        $project_timeline_save  = $this->ProjectTimeline->save($project_timeline);
                        $this->Flash->success('New Project has been added successfully!!');
                    }
                    else
                        $this->Flash->error('Unable to add Project!!');
                }

            $this->redirect(array("action" => 'projects'));
       }
       elseif($id)
       {
            $project = $this->Projects->get($id);
            $project_timeline = $this->ProjectTimeline->find('all',['conditions' => ['project_id' => $id]]);
            $documents = $this->ProjectDocuments->find('all',['conditions' => ['project_id' => $id]]);
            //$this->dateDiff("2010-01-26", "2004-01-26");exit;

            $this->set('project', $project);
            $this->set('project_timeline', $project_timeline);
            $this->set('documents', $documents);
       }

      
       $this->set('siteurl', $siteurl);

    }

    public function server()
    {
        $this->ProjectDocuments = TableRegistry::get('project_documents');
        $this->ProjectTimeline = TableRegistry::get('project_timeline');
        $siteurl =  Router::url('/', true);

        if ($this->request->is('post'))
        {
            $data = $this->request->data;

            $images = [];
            $json['files'] = array();

            foreach($data['files'] as $k=>$pre)
            {
                $image = $pre;
                $uploadPath = WWW_ROOT .'upload/project';
                $imageName = time().$image['name'];
                $full_image_path = $uploadPath . '/' . $imageName;
                if(move_uploaded_file($image['tmp_name'], $full_image_path))
                    $images[] = $imageName;

            $data['document_name'] = $imageName;
            $data['type'] = pathinfo($imageName, PATHINFO_EXTENSION);
            $data['project_id'] = $data['project_doc_id'];
            $documents = $this->ProjectDocuments->newEntity();
            $documents = $this->ProjectDocuments->patchEntity($documents, $data);
            $documents = $this->ProjectDocuments->save($documents);
            $json['files'][$k]['url'] = $siteurl .'upload/project/'.$imageName;
            $json['files'][$k]['thumbnailUrl'] = $siteurl .'upload/project/'.$imageName;
            $json['files'][$k]['name'] = $imageName;
            $json['files'][$k]['deleteUrl'] = $siteurl .'users/delete/'.$documents->id;
            $json['files'][$k]['deleteType'] = "DELETE";
            }
            //print_r($data);exit;
            echo json_encode($json);
            exit;
           
        }
        else {
            $json['files'] = array();
            $docs = $this->ProjectDocuments->find('all',['conditions' => ['project_id' => $id]]);
            foreach($docs as $k=>$pre)
            {
            $json['files'][$k]['url'] = $siteurl .'upload/project/'.$imageName;
            $json['files'][$k]['thumbnailUrl'] = $siteurl .'upload/project/'.$imageName;
            $json['files'][$k]['name'] = $imageName;
            $json['files'][$k]['deleteUrl'] = $siteurl .'users/delete/'.$pre->id;
            $json['files'][$k]['deleteType'] = "DELETE";
            }
            echo json_encode($json);
            exit;
        }

    }

    public function users()
    {
        
    }

    public function dateDiff($time1, $time2, $precision = 6) {
    // If not numeric then convert texts to unix timestamps
    if (!is_int($time1)) {
      $time1 = strtotime($time1);
    }
    if (!is_int($time2)) {
      $time2 = strtotime($time2);
    }

    // If time1 is bigger than time2
    // Then swap time1 and time2
    if ($time1 > $time2) {
      $ttime = $time1;
      $time1 = $time2;
      $time2 = $ttime;
    }

    // Set up intervals and diffs arrays
    $intervals = array('year','month','day','hour','minute','second');
    $diffs = array();

    // Loop thru all intervals
    foreach ($intervals as $interval) {
      // Create temp time from time1 and interval
      $ttime = strtotime('+1 ' . $interval, $time1);
      // Set initial values
      $add = 1;
      $looped = 0;
      // Loop until temp time is smaller than time2
      while ($time2 >= $ttime) {
        // Create new temp time from time1 and interval
        $add++;
        $ttime = strtotime("+" . $add . " " . $interval, $time1);
        $looped++;
      }
 
      $time1 = strtotime("+" . $looped . " " . $interval, $time1);
      $diffs[$interval] = $looped;
    }
    
    $count = 0;
    $times = array();
    // Loop thru all diffs
    foreach ($diffs as $interval => $value) {
      // Break if we have needed precission
      if ($count >= $precision) {
        break;
      }
      // Add value and interval 
      // if value is bigger than 0
      if ($value > 0) {
        // Add s if value is not 1
        if ($value != 1) {
          $interval .= "s";
        }
        // Add value and interval to times array
        $times[] = $value . " " . $interval;
        $count++;
      }
    }

    // Return string with times
    return implode(", ", $times);
  }

}