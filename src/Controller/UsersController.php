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

                return $this->redirect(['action' => 'profile']);
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
            $user = $this->Users->newEntity($this->request->getData());
            $user->uuid = Text::uuid();

            $registerData = $webauth->getRegistrationData(
                $user->uuid,
                $user->username,
                $user->display_name
            );
            // Store registration data in the session so we can use
            // it once the user has completed their u2f prompt.
            $this->request->getSession()->write('Registration', [
                'user' => $user,
                'challenge' => $registerData->challenge,
            ]);
            $this->set('registerData', $registerData);
            $this->set('user', $user);
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
            $challenge = $session->read('Registration.challenge');
            $processData = $webauth->validateRegistration(
                $request,
                $challenge,
            );
        } catch (WebAuthnException $error) {
            $this->set('success', false);
            $this->set('message', $error->getMessage());

            return;
        }

        $user = $session->read('Registration.user');
        try {
            $this->Users->getConnection()->transactional(function () use ($user, $processData) {
                $this->Users->saveOrFail($user);

                $passkey = $this->Users->Passkeys->createFromData($processData, 'initial authenticator');
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

    public function logout()
    {
        $this->Authentication->logout();
        $this->redirect(['action' => 'login']);
    }

    /**
     * View the current user's profile
     *
     * @return \Cake\Http\Response|null|void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view()
    {
        $identity = $this->Authentication->getIdentity();
        $user = $this->Users->get($identity->id, [
            'contain' => ['Passkeys'],
        ]);

        $this->set(compact('user'));
    }

    /**
     * Edit the current user.
     *
     * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit()
    {
        $identity = $this->Authentication->getIdentity();
        $user = $this->Users->get($identity->id, [
            'contain' => ['Passkeys'],
        ]);
        if ($this->request->is(['patch', 'post', 'put'])) {
            $options = ['associated' => ['Passkeys']];
            $user = $this->Users->patchEntity($user, $this->request->getData(), $options);
            if ($this->Users->save($user, $options)) {
                $this->Flash->success(__('The user has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The user could not be saved. Please, try again.'));
        }
        $this->set(compact('user'));
    }

    public function addPasskey()
    {
        $identity = $this->Authentication->getIdentity();
        $user = $this->Users->get($identity->id, [
            'contain' => ['Passkeys'],
        ]);
        if ($this->request->is(['patch', 'post', 'put'])) {
            /** @var \Authentication\AuthenticationService $authService */
            $authService = $this->Authentication->getAuthenticationService();
            $webauth = $authService->authenticators()->get('Webauthn');

            $registerData = $webauth->getRegistrationData(
                $user->uuid,
                $user->username,
                $user->display_name
            );
            // Store registration data in the session so we can use
            // it once the user has completed their u2f prompt.
            $this->request->getSession()->write('Registration', [
                'challenge' => $registerData->challenge,
            ]);
            $this->set('registerData', $registerData);
        }
        $this->set('user', $user);

        return $this->render('view');
    }

    public function completeAddPasskey()
    {
        $request = $this->request;
        $session = $request->getSession();
        $identity = $this->Authentication->getIdentity();
        try {
            /** @var \Authentication\AuthenticationService $authService */
            $authService = $this->Authentication->getAuthenticationService();
            $webauth = $authService->authenticators()->get('Webauthn');

            $challenge = $session->read('Registration.challenge');
            $createData = $webauth->validateRegistration(
                $request,
                $challenge,
            );
        } catch (WebAuthnException $error) {
            $this->set('success', false);
            $this->set('message', $error->getMessage());

            return;
        }

        // TODO random name generator
        $passKey = $this->Users->Passkeys->createFromData($createData, 'Fluffy Rhino');

        $passKey->user_id = $identity->id;
        if ($this->Users->Passkeys->save($passKey)) {
            $this->Flash->success(__('The passkey has been saved.'));

            return $this->redirect(['action' => 'view']);
        }
        $this->Flash->error(__('The pass key could not be saved. Please, try again.'));

        return $this->redirect(['action' => 'addPasskey']);
    }

    /**
     * Delete method
     *
     * @return \Cake\Http\Response|null|void Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete()
    {
        $this->request->allowMethod(['post', 'delete']);
        $identity = $this->Authentication->getIdentity();
        $user = $this->Users->get($identity->id);
        if ($this->Users->delete($user)) {
            $this->Flash->success(__('The user has been deleted.'));
        } else {
            $this->Flash->error(__('The user could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }
}
