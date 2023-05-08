<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class CreateUsersTable extends AbstractMigration
{
    /**
     * Change Method.
     *
     * More information on this method is available here:
     * https://book.cakephp.org/phinx/0/en/migrations.html#the-change-method
     * @return void
     */
    public function change(): void
    {
        $table = $this->table('users')
            ->addColumn('uuid', 'string', ['null' => false])
            ->addColumn('email', 'string', ['null' => false])
            ->addColumn('display_name', 'string', ['null' => false])
            ->addColumn('passkey', 'text')
            ->addIndex(['email'], ['unique' => true]);
        $table->save();
    }
}
