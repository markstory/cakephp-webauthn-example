<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Passkey $passkey
 * @var string[]|\Cake\Collection\CollectionInterface $users
 */
?>
<div class="row">
    <aside class="column">
        <div class="side-nav">
            <h4 class="heading"><?= __('Actions') ?></h4>
            <?= $this->Form->postLink(
                __('Delete'),
                ['action' => 'delete', $passkey->id],
                ['confirm' => __('Are you sure you want to delete # {0}?', $passkey->id), 'class' => 'side-nav-item']
            ) ?>
            <?= $this->Html->link(__('List Passkeys'), ['action' => 'index'], ['class' => 'side-nav-item']) ?>
        </div>
    </aside>
    <div class="column-responsive column-80">
        <div class="passkeys form content">
            <?= $this->Form->create($passkey) ?>
            <fieldset>
                <legend><?= __('Edit Passkey') ?></legend>
                <?php
                    echo $this->Form->control('display_name');
                ?>
            </fieldset>
            <?= $this->Form->button(__('Submit')) ?>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>
