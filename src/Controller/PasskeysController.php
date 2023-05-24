<?php
declare(strict_types=1);

namespace App\Controller;

/**
 * Passkeys Controller
 *
 * @property \App\Model\Table\PasskeysTable $Passkeys
 */
class PasskeysController extends AppController
{
    protected function getPasskey($id)
    {
        $user = $this->Authentication->getIdentity();

        return $this->Passkeys->find()
            ->where([
                'Passkeys.user_id' => $user->id,
                'Passkeys.id' => $id,
            ])->contain('Users')
            ->firstOrFail();
    }

    /**
     * Delete method
     *
     * @param string|null $id Passkey id.
     * @return \Cake\Http\Response|null|void Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete($id = null)
    {
        $this->request->allowMethod(['post', 'delete']);
        $passkey = $this->getPasskey($id);
        if ($this->Passkeys->delete($passkey)) {
            $this->Flash->success(__('The passkey has been deleted.'));
        } else {
            $this->Flash->error(__('The passkey could not be deleted. Please, try again.'));
        }

        return $this->redirect(['controller' => 'Users', 'action' => 'view']);
    }

    /**
     * Edit method
     *
     * @param string|null $id Passkey id.
     * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit($id = null)
    {
        $passkey = $this->getPasskey($id);
        if ($this->request->is(['patch', 'post', 'put'])) {
            $passkey = $this->Passkeys->patchEntity($passkey, $this->request->getData());
            if ($this->Passkeys->save($passkey)) {
                $this->Flash->success(__('The passkey has been saved.'));

                return $this->redirect(['controller' => 'Users', 'action' => 'view']);
            }
            $this->Flash->error(__('The passkey could not be saved. Please, try again.'));
        }
        $this->set(compact('passkey'));
    }
}
