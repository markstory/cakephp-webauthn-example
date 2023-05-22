<?php
declare(strict_types=1);

namespace App\Controller;

use Authentication\Authenticator\Result;
use Cake\Event\EventInterface;
use Cake\ORM\Exception\PersistenceFailedException;
use Cake\Utility\Text;
use Cake\View\JsonView;
use lbuchs\WebAuthn\WebAuthnException;

/**
 * Users Controller
 *
 * @property \App\Model\Table\UsersTable $Users
 * @property \Authentication\Controller\Component\AuthenticationComponent $Authentication
 * @method \App\Model\Entity\User[]|\Cake\Datasource\ResultSetInterface paginate($object = null, array $settings = [])
 */
class UsersController extends AppController
{
    public function initialize(): void
    {
        parent::initialize();

        $this->loadComponent('Authentication.Authentication');
    }

    public function beforeFilter(EventInterface $event)
    {
        parent::beforeFilter($event);
        $this->Authentication->allowUnauthenticated([
            'startRegister',
            'completeRegister',
            'login',
            'startLogin',
            'completeLogin'
        ]);
    }

    public function login()
    {
        if ($this->request->is('post')) {
            $authResult = $this->Authentication->getResult();
            if ($authResult->isValid()) {
                $this->Flash->success('You are logged in');

                return $this->redirect(['action' => 'index']);
            } else {
                if ($authResult->getStatus() == Result::FAILURE_CREDENTIALS_MISSING) {
                    $loginData = $authResult->getData();
                    $this->request->getSession()->write('Webauthn.challenge', $loginData->challenge);
                    $this->set('loginData', $loginData);
                }
            }
        }
        $this->set('user', $this->Authentication->getIdentity());
    }

    public function startRegister()
    {
        if ($this->request->is('post')) {
            /** @var \Authentication\AuthenticationService $authService */
            $authService = $this->Authentication->getAuthenticationService();
            $webauth = $authService->authenticators()->get('Webauthn');

            // Get webauth registration/challenge data.
            $userId = Text::uuid();
            $registerData = $webauth->getRegistrationData($userId, $this->request->getData('username'), $this->request->getData('displayName'));

            // Store registration data in the session so we can use
            // it once the user has completed their u2f prompt.
            $this->request->getSession()->write('Registration', [
                'id' => $userId,
                'username' => $this->request->getData('username'),
                'displayName' => $this->request->getData('displayName'),
                'challenge' => $registerData->challenge,
            ]);
            $this->set('registerData', $registerData);
        }
        $this->render('register');
    }

    public function completeRegister()
    {
        $request = $this->request;
        $request->allowMethod('POST');

        $session = $request->getSession();

        /** @var \Authentication\AuthenticationService $authService */
        $authService = $this->Authentication->getAuthenticationService();
        $webauth = $authService->authenticators()->get('Webauthn');

        $this->viewBuilder()
            ->setClassName(JsonView::class)
            ->setOption('serialize', ['success', 'message']);

        try {
            // TODO move this to authenticator class.
            $clientData = base64_decode($request->getData('clientData'));
            $attestation = base64_decode($request->getData('attestation'));
            $challenge = $session->read('Registration.challenge');

            $processData = $webauth->validateRegistration(
                $clientData,
                $attestation,
                $challenge,
            );
        } catch (WebAuthnException $error) {
            $this->set('success', false);
            $this->set('message', $error->getMessage());

            return;
        }

        $user = $this->Users->newEmptyEntity();
        $user->uuid = $session->read('Registration.id');
        $user->username = $session->read('Registration.username');
        $user->display_name = $session->read('Registration.displayName');

        try {
            $this->Users->getConnection()->transactional(function () use ($user, $processData) {
                $this->Users->saveOrFail($user);

                $passkey = $this->Users->Passkeys->createFromData($processData);
                $passkey->user_id = $user->id;
                $this->Users->Passkeys->saveOrFail($passkey);
            });

            $this->set('success', true);
            $this->set('message', 'Register Success');
        } catch (PersistenceFailedException $error) {
            $this->set('success', false);
            $this->set('message', $error->getMessage());
        }
    }

    public function logout() {
        $this->Authentication->logout();
        $this->redirect(['action' => 'login']);
    }

    /**
     * Index method
     *
     * @return \Cake\Http\Response|null|void Renders view
     */
    public function index()
    {
        $users = $this->paginate($this->Users);

        $this->set(compact('users'));
    }

    /**
     * View method
     *
     * @param string|null $id User id.
     * @return \Cake\Http\Response|null|void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view($id = null)
    {
        $user = $this->Users->get($id, [
            'contain' => ['Passkeys'],
        ]);

        $this->set(compact('user'));
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null|void Redirects on successful add, renders view otherwise.
     */
    public function add()
    {
        $user = $this->Users->newEmptyEntity();
        if ($this->request->is('post')) {
            $user = $this->Users->patchEntity($user, $this->request->getData());
            if ($this->Users->save($user)) {
                $this->Flash->success(__('The user has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The user could not be saved. Please, try again.'));
        }
        $this->set(compact('user'));
    }

    /**
     * Edit method
     *
     * @param string|null $id User id.
     * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit($id = null)
    {
        $user = $this->Users->get($id, [
            'contain' => [],
        ]);
        if ($this->request->is(['patch', 'post', 'put'])) {
            $user = $this->Users->patchEntity($user, $this->request->getData());
            if ($this->Users->save($user)) {
                $this->Flash->success(__('The user has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The user could not be saved. Please, try again.'));
        }
        $this->set(compact('user'));
    }

    /**
     * Delete method
     *
     * @param string|null $id User id.
     * @return \Cake\Http\Response|null|void Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete($id = null)
    {
        $this->request->allowMethod(['post', 'delete']);
        $user = $this->Users->get($id);
        if ($this->Users->delete($user)) {
            $this->Flash->success(__('The user has been deleted.'));
        } else {
            $this->Flash->error(__('The user could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }
}
